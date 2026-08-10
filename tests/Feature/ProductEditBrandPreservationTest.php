<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: Admin Product Edit — an existing product's Brand dropdown was
 * not preselected (Alpine x-model initialized before the x-for options
 * rendered), so an untouched edit submitted a blank brand_id and cleared the
 * persisted brand. Fix: x-init re-applies the persisted value after options
 * render (same as the category selects), and update() preserves nullable
 * fields (brand_id, category_id, warranty, return_policy) when the request
 * omits them while still honoring an explicit blank as an intentional clear.
 */
class ProductEditBrandPreservationTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(string $slug = 'preserve-store'): Store
    {
        return Store::create(['name' => 'Preserve Store', 'slug' => $slug, 'is_active' => true]);
    }

    private function makeProduct(Store $store, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'store_id' => $store->id,
            'sku' => 'PRESERVE-001',
            'name' => 'Preserve Product',
            'slug' => 'preserve-product',
            'retail_price' => 10000,
            'wholesale_price' => 7000,
            'stock_status' => 'in_stock',
            'description' => 'A plain description.',
        ], $overrides));
    }

    private function managerFor(Store $store): User
    {
        $manager = User::create([
            'name' => 'Manager',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        return $manager;
    }

    private function updatePayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'name' => $product->name,
            'sku' => $product->sku,
            'retail_price' => $product->retail_price,
            'wholesale_price' => $product->wholesale_price,
            'stock_status' => $product->stock_status,
            'description' => $product->description ?? '',
        ], $overrides);
    }

    /** The edit page initializes selectedBrand from the persisted brand_id and applies it to the select. */
    public function test_edit_page_preselects_persisted_brand_and_syncs_select(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Xiaomi', 'slug' => 'xiaomi']);
        $product = $this->makeProduct($store, ['brand_id' => $brand->id]);

        $response = $this->actingAs($manager)->get('/store/preserve-store/admin/products/' . $product->id . '/edit');

        $response->assertOk();
        // Alpine state is seeded from the persisted brand_id…
        $response->assertSee("selectedBrand: '" . $brand->id . "'", false);
        // …and the brand select carries the same race fix the category selects use.
        $response->assertSee('$nextTick(() => $el.value = selectedBrand)', false);
        // The preview resolves the brand by the same id that will be submitted.
        $response->assertSee(__('messages.spec_brand'));
        $response->assertSee('Xiaomi');
    }

    /** Open + save without touching Brand: the submitted persisted brand_id keeps the brand unchanged. */
    public function test_update_without_changing_brand_preserves_brand(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Xiaomi', 'slug' => 'xiaomi']);
        $product = $this->makeProduct($store, ['brand_id' => $brand->id]);

        // What the fixed form submits when the admin changes nothing else.
        $response = $this->actingAs($manager)->put('/store/preserve-store/admin/products/' . $product->id, $this->updatePayload($product, [
            'brand_id' => $brand->id,
        ]));

        $response->assertRedirect();
        $this->assertSame($brand->id, $product->fresh()->brand_id);
    }

    /** If brand_id is absent from the request entirely, preserve the persisted brand instead of clearing it. */
    public function test_update_omitting_brand_id_preserves_existing_brand(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Xiaomi', 'slug' => 'xiaomi']);
        $product = $this->makeProduct($store, ['brand_id' => $brand->id]);

        $response = $this->actingAs($manager)->put('/store/preserve-store/admin/products/' . $product->id, $this->updatePayload($product));

        $response->assertRedirect();
        $this->assertSame($brand->id, $product->fresh()->brand_id);
    }

    /** Changing Brand and saving persists the new brand. */
    public function test_update_with_new_brand_persists(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $oldBrand = Brand::create(['store_id' => $store->id, 'name' => 'Xiaomi', 'slug' => 'xiaomi']);
        $newBrand = Brand::create(['store_id' => $store->id, 'name' => 'Redmi', 'slug' => 'redmi']);
        $product = $this->makeProduct($store, ['brand_id' => $oldBrand->id]);

        $response = $this->actingAs($manager)->put('/store/preserve-store/admin/products/' . $product->id, $this->updatePayload($product, [
            'brand_id' => $newBrand->id,
        ]));

        $response->assertRedirect();
        $this->assertSame($newBrand->id, $product->fresh()->brand_id);
    }

    /** Explicitly selecting the blank option is an intentional clear and is honored. */
    public function test_update_with_explicit_blank_brand_clears_it(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Xiaomi', 'slug' => 'xiaomi']);
        $product = $this->makeProduct($store, ['brand_id' => $brand->id]);

        $response = $this->actingAs($manager)->put('/store/preserve-store/admin/products/' . $product->id, $this->updatePayload($product, [
            'brand_id' => '',
        ]));

        $response->assertRedirect();
        $this->assertNull($product->fresh()->brand_id);
    }

    /** The same absent-field preservation applies to category_id. */
    public function test_update_omitting_category_id_preserves_existing_category(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $category = Category::create(['store_id' => $store->id, 'name' => 'Body Frame', 'slug' => 'body-frame']);
        $product = $this->makeProduct($store, ['category_id' => $category->id]);

        $response = $this->actingAs($manager)->put('/store/preserve-store/admin/products/' . $product->id, $this->updatePayload($product));

        $response->assertRedirect();
        $this->assertSame($category->id, $product->fresh()->category_id);
    }

    /** Warranty is preserved when absent, and cleared only when explicitly blank. */
    public function test_update_warranty_preserved_when_absent_cleared_when_blank(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $product = $this->makeProduct($store, ['warranty' => '1 Month Warranty']);

        // Absent from the request → preserved.
        $this->actingAs($manager)->put('/store/preserve-store/admin/products/' . $product->id, $this->updatePayload($product));
        $this->assertSame('1 Month Warranty', $product->fresh()->warranty);

        // Explicitly blank → cleared (intentional).
        $this->actingAs($manager)->put('/store/preserve-store/admin/products/' . $product->id, $this->updatePayload($product, [
            'warranty' => '',
        ]));
        $this->assertNull($product->fresh()->warranty);
    }
}
