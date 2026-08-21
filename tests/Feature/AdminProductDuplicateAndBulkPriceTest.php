<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminProductDuplicateAndBulkPriceTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $admin;
    protected Category $category;
    protected Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->store = Store::create(['name' => 'DataPOS Products', 'slug' => 'datapos-products']);
        $this->admin = User::factory()->create(['phone' => '09111112222']);
        $this->admin->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->category = Category::create(['store_id' => $this->store->id, 'name' => 'Chargers', 'slug' => 'chargers']);
        $this->brand = Brand::create(['store_id' => $this->store->id, 'name' => 'U-WiNN', 'slug' => 'u-winn']);
    }

    private function makeProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'sku' => 'SKU-' . strtoupper(Str::random(6)),
            'name' => 'A88 Micro',
            'slug' => 'a88-micro-' . Str::random(6),
            'retail_price' => 30000,
            'wholesale_price' => 15000,
            'stock_status' => 'in_stock',
        ], $overrides));
    }

    /** Copy creates a new product with a fresh SKU/slug and copied values. */
    public function test_admin_can_duplicate_product_with_new_sku_and_slug(): void
    {
        $product = $this->makeProduct(['name' => 'A88 Micro', 'sku' => 'A88']);

        $response = $this->actingAs($this->admin)
            ->post("/store/{$this->store->slug}/admin/products/{$product->id}/duplicate");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $copy = Product::where('store_id', $this->store->id)
            ->where('sku', 'A88-copy')
            ->firstOrFail();

        $this->assertEquals('A88 Micro (Copy)', $copy->name);
        $this->assertNotEquals($product->slug, $copy->slug);
        $this->assertEquals($product->retail_price, $copy->retail_price);
        $this->assertEquals($product->wholesale_price, $copy->wholesale_price);
        $this->assertEquals($this->category->id, $copy->category_id);
        $this->assertEquals($this->brand->id, $copy->brand_id);
        $this->assertFalse((bool) $copy->is_featured);
    }

    /** Export streams the full product list as an Excel-friendly CSV. */
    public function test_admin_can_export_products_as_csv(): void
    {
        $this->makeProduct([
            'name' => 'A88 Micro',
            'sku' => 'A88',
            'warranty' => '7 days',
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/store/{$this->store->slug}/admin/products/export");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('SKU', $content);
        $this->assertStringContainsString('Retail Price (Ks)', $content);
        $this->assertStringContainsString('A88', $content);
        $this->assertStringContainsString('A88 Micro', $content);
        $this->assertStringContainsString('Chargers', $content);
        $this->assertStringContainsString('U-WiNN', $content);
        $this->assertStringContainsString('30,000', $content);
        $this->assertStringContainsString('15,000', $content);
        $this->assertStringContainsString('7 days', $content);
    }

    /** The export only ever contains the current store's products. */
    public function test_product_export_is_scoped_to_store(): void
    {
        $other = Store::create(['name' => 'Other Store', 'slug' => 'other-store']);
        Product::create([
            'store_id' => $other->id,
            'sku' => 'OTHER-1',
            'name' => 'Other Product',
            'slug' => 'other-product-1',
            'retail_price' => 500,
            'wholesale_price' => 400,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/store/{$this->store->slug}/admin/products/export");

        $content = $response->streamedContent();

        $this->assertStringNotContainsString('OTHER-1', $content);
    }

    /** Toolbar offers an "All" per-page option so the whole catalog is viewable. */
    public function test_admin_products_page_offers_all_per_page_option(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/store/{$this->store->slug}/admin/products");

        $response->assertOk();
        $response->assertSee('value="all"', false);
        $response->assertSee('>' . __('messages.all') . '</option>', false);
    }

    /** Export honours the selected per-page size; "all" (or absent) exports everything. */
    public function test_product_export_respects_per_page_choice(): void
    {
        for ($i = 1; $i <= 55; $i++) {
            $this->makeProduct([
                'sku' => 'LIM-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'name' => "Limit Product $i",
            ]);
        }

        $limited = $this->actingAs($this->admin)
            ->get("/store/{$this->store->slug}/admin/products/export?per_page=50");
        $limited->assertOk();
        $content = $limited->streamedContent();
        $this->assertStringContainsString('LIM-50', $content);
        $this->assertStringNotContainsString('LIM-51', $content);

        $all = $this->actingAs($this->admin)
            ->get("/store/{$this->store->slug}/admin/products/export?per_page=all");
        $all->assertOk();
        $this->assertStringContainsString('LIM-55', $all->streamedContent());
    }

    /** Bulk bar offers Select All (current page) and Cancel (clear selection). */
    public function test_bulk_bar_has_select_all_and_cancel_buttons(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/store/{$this->store->slug}/admin/products");

        $response->assertOk();
        $response->assertSee(__('messages.select_all'), false);
        $response->assertSee(__('messages.cancel'), false);
        $response->assertSee('selectedIds = []', false);
    }

    /** Gallery images are copied to new files so the two products stay independent. */
    public function test_duplicate_copies_gallery_images_to_new_files(): void
    {
        Storage::disk('public')->put('products/original.jpg', 'binary');

        $product = $this->makeProduct(['image_path' => 'products/original.jpg']);
        $product->images()->create(['image_path' => 'products/original.jpg', 'is_primary' => true, 'sort_order' => 1]);

        $this->actingAs($this->admin)
            ->post("/store/{$this->store->slug}/admin/products/{$product->id}/duplicate");

        $copy = Product::where('store_id', $this->store->id)
            ->where('sku', $product->sku . '-copy')
            ->firstOrFail();

        $this->assertNotNull($copy->image_path);
        $this->assertNotEquals('products/original.jpg', $copy->image_path);
        $this->assertTrue(Storage::disk('public')->exists($copy->image_path));

        $this->assertEquals(1, $copy->images()->count());
        $this->assertNotEquals(
            $product->images()->first()->image_path,
            $copy->images()->first()->image_path
        );
    }

    /** When the "-copy" SKU is taken, a numeric suffix is appended. */
    public function test_duplicate_sku_collision_appends_counter(): void
    {
        $product = $this->makeProduct(['sku' => 'B77']);
        $this->makeProduct(['sku' => 'B77-copy']);

        $this->actingAs($this->admin)
            ->post("/store/{$this->store->slug}/admin/products/{$product->id}/duplicate");

        $this->assertDatabaseHas('products', ['store_id' => $this->store->id, 'sku' => 'B77-copy-2']);
    }

    /** Bulk price adjustment by percentage on both prices. */
    public function test_bulk_adjust_prices_by_percentage(): void
    {
        $p1 = $this->makeProduct(['retail_price' => 10000, 'wholesale_price' => 5000]);
        $p2 = $this->makeProduct(['retail_price' => 20000, 'wholesale_price' => 10000]);

        $response = $this->actingAs($this->admin)
            ->post("/store/{$this->store->slug}/admin/products/bulk-prices", [
                'ids' => [$p1->id, $p2->id],
                'apply_to' => 'both',
                'direction' => 'increase',
                'mode' => 'percent',
                'value' => 10,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(11000, (float) $p1->fresh()->retail_price);
        $this->assertEquals(5500, (float) $p1->fresh()->wholesale_price);
        $this->assertEquals(22000, (float) $p2->fresh()->retail_price);
    }

    /** Retail-only adjustment leaves wholesale untouched. */
    public function test_bulk_adjust_retail_only_by_fixed_amount(): void
    {
        $p1 = $this->makeProduct(['retail_price' => 10000, 'wholesale_price' => 5000]);

        $this->actingAs($this->admin)
            ->post("/store/{$this->store->slug}/admin/products/bulk-prices", [
                'ids' => [$p1->id],
                'apply_to' => 'retail',
                'direction' => 'decrease',
                'mode' => 'amount',
                'value' => 1000,
            ]);

        $this->assertEquals(9000, (float) $p1->fresh()->retail_price);
        $this->assertEquals(5000, (float) $p1->fresh()->wholesale_price);
    }

    /** Prices never drop below zero. */
    public function test_bulk_price_never_goes_below_zero(): void
    {
        $p1 = $this->makeProduct(['retail_price' => 500, 'wholesale_price' => 300]);

        $this->actingAs($this->admin)
            ->post("/store/{$this->store->slug}/admin/products/bulk-prices", [
                'ids' => [$p1->id],
                'apply_to' => 'both',
                'direction' => 'decrease',
                'mode' => 'amount',
                'value' => 1000,
            ]);

        $this->assertEquals(0, (float) $p1->fresh()->retail_price);
        $this->assertEquals(0, (float) $p1->fresh()->wholesale_price);
    }

    /** Another store's product cannot be duplicated from this store. */
    public function test_cross_store_duplicate_is_forbidden(): void
    {
        $otherStore = Store::create(['name' => 'Other', 'slug' => 'other-store']);
        $otherProduct = Product::create([
            'store_id' => $otherStore->id,
            'sku' => 'OTHER-1',
            'name' => 'Other Product',
            'slug' => 'other-product',
            'retail_price' => 1000,
            'wholesale_price' => 500,
            'stock_status' => 'in_stock',
        ]);

        $this->actingAs($this->admin)
            ->post("/store/{$this->store->slug}/admin/products/{$otherProduct->id}/duplicate")
            ->assertForbidden();
    }
}
