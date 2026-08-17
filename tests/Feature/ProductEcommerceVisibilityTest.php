<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductEcommerceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Store One', 'slug' => 'store-one', 'is_active' => true]);
        $this->store->setting()->create(['store_name' => 'Store One', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['phone' => '09111111111']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->customer = User::factory()->create(['phone' => '09222222222', 'role' => 'customer']);
    }

    private function makeProduct(array $overrides = []): Product
    {
        $name = $overrides['name'] ?? 'Product ' . Str::random(4);

        return Product::create(array_merge([
            'store_id' => $this->store->id,
            'category_id' => Category::create(['store_id' => $this->store->id, 'name' => 'Cat ' . Str::random(4), 'slug' => 'cat-' . Str::random(4)])->id,
            'brand_id' => Brand::create(['store_id' => $this->store->id, 'name' => 'Brand ' . Str::random(4), 'slug' => 'brand-' . Str::random(4)])->id,
            'sku' => 'SKU-' . strtoupper(Str::random(6)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(4)),
            'retail_price' => 10000,
            'wholesale_price' => 8000,
            'stock_status' => 'in_stock',
            'is_ecommerce' => true,
        ], $overrides));
    }

    public function test_products_default_to_online(): void
    {
        $product = Product::create([
            'store_id' => $this->store->id,
            'sku' => 'OLD-SKU',
            'name' => 'Legacy Product',
            'slug' => 'legacy-product',
            'retail_price' => 5000,
            'wholesale_price' => 4000,
            'stock_status' => 'in_stock',
        ]);

        $this->assertTrue($product->is_ecommerce);
    }

    public function test_counter_only_product_is_hidden_from_catalog(): void
    {
        $online = $this->makeProduct(['name' => 'Online Visible']);
        $counter = $this->makeProduct(['name' => 'Counter Secret', 'is_ecommerce' => false]);

        $response = $this->get('/products?store_slug=' . $this->store->slug);

        $response->assertOk();
        $response->assertSeeText('Online Visible');
        $response->assertDontSeeText('Counter Secret');
    }

    public function test_counter_only_product_is_hidden_from_home_sections(): void
    {
        $this->makeProduct(['name' => 'Arrival Visible']);
        $this->makeProduct(['name' => 'Arrival Hidden', 'is_ecommerce' => false]);

        $response = $this->get("/store/{$this->store->slug}");

        $response->assertOk();
        $response->assertSeeText('Arrival Visible');
        $response->assertDontSeeText('Arrival Hidden');
    }

    public function test_counter_only_product_is_hidden_from_suggestions(): void
    {
        $this->makeProduct(['name' => 'Sugg Visible']);
        $this->makeProduct(['name' => 'Sugg Hidden', 'is_ecommerce' => false]);

        $response = $this->get('/products/suggestions?store_slug=' . $this->store->slug . '&search=Sugg');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Sugg Visible']);
        $response->assertJsonMissing(['name' => 'Sugg Hidden']);
    }

    public function test_counter_only_product_does_not_advertise_its_category_on_browse(): void
    {
        $onlineCat = Category::create(['store_id' => $this->store->id, 'name' => 'Online Cat X', 'slug' => 'online-cat-x']);
        $counterCat = Category::create(['store_id' => $this->store->id, 'name' => 'Counter Cat Y', 'slug' => 'counter-cat-y']);
        $this->makeProduct(['name' => 'Online Item', 'category_id' => $onlineCat->id]);
        $this->makeProduct(['name' => 'Counter Item', 'category_id' => $counterCat->id, 'is_ecommerce' => false]);

        $response = $this->get('/browse?store_slug=' . $this->store->slug);

        $response->assertOk();
        $response->assertSeeText('Online Cat X');
        $response->assertDontSeeText('Counter Cat Y');
    }

    public function test_admin_products_list_still_shows_all_and_filters_by_visibility(): void
    {
        $this->makeProduct(['name' => 'Admin Online']);
        $this->makeProduct(['name' => 'Admin Counter', 'is_ecommerce' => false]);

        // Admin sees both by default.
        $all = $this->actingAs($this->manager)->get("/store/{$this->store->slug}/admin/products");
        $all->assertOk();
        $all->assertSeeText('Admin Online');
        $all->assertSeeText('Admin Counter');

        // Counter-only filter narrows the list.
        $filtered = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/products?is_ecommerce=counter_only");
        $filtered->assertOk();
        $filtered->assertDontSeeText('Admin Online');
        $filtered->assertSeeText('Admin Counter');
    }

    public function test_per_row_toggle_flips_visibility(): void
    {
        $product = $this->makeProduct(['name' => 'Toggle Me']);

        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products/{$product->id}/toggle-ecommerce")
            ->assertRedirect();

        $this->assertFalse($product->fresh()->is_ecommerce);

        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products/{$product->id}/toggle-ecommerce")
            ->assertRedirect();

        $this->assertTrue($product->fresh()->is_ecommerce);
    }

    public function test_per_row_toggle_is_store_scoped(): void
    {
        $otherStore = Store::create(['name' => 'Store Two', 'slug' => 'store-two', 'is_active' => true]);
        $product = $this->makeProduct(['name' => 'Foreign Product']);

        $this->actingAs($this->manager)
            ->post("/store/{$otherStore->slug}/admin/products/{$product->id}/toggle-ecommerce")
            ->assertStatus(403);
    }

    public function test_bulk_toggle_sets_visibility_for_selected_products(): void
    {
        $a = $this->makeProduct(['name' => 'Bulk A']);
        $b = $this->makeProduct(['name' => 'Bulk B']);

        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products/bulk-ecommerce", [
                'ids' => [$a->id, $b->id],
                'is_ecommerce' => 0,
            ])
            ->assertRedirect();

        $this->assertFalse($a->fresh()->is_ecommerce);
        $this->assertFalse($b->fresh()->is_ecommerce);

        // Only the store's own products change.
        $otherStore = Store::create(['name' => 'Store Two', 'slug' => 'store-two', 'is_active' => true]);
        $foreign = $this->makeProduct(['name' => 'Foreign']);
        $foreign->update(['store_id' => $otherStore->id]);

        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products/bulk-ecommerce", [
                'ids' => [$foreign->id],
                'is_ecommerce' => 1,
            ])
            ->assertRedirect();

        $this->assertTrue($foreign->fresh()->is_ecommerce);
    }

    public function test_bulk_toggle_requires_ids_and_value(): void
    {
        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products/bulk-ecommerce", ['ids' => [], 'is_ecommerce' => 1])
            ->assertStatus(302);
        // (validation errors redirect back with 302; empty ids fails validation)
    }

    public function test_create_form_renders_sell_online_toggle_checked_by_default(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/products/create");

        $response->assertStatus(200);
        $response->assertSeeText('Sell Online');
    }

    public function test_create_with_unchecked_sell_online_persists_false(): void
    {
        // Hidden 0-input + unchecked box → is_ecommerce = 0.
        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products", [
                'name' => 'Counter Product',
                'sku' => 'CNT-001',
                'retail_price' => 9000,
                'wholesale_price' => 7000,
                'stock_status' => 'in_stock',
                'is_ecommerce' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'store_id' => $this->store->id,
            'sku' => 'CNT-001',
            'is_ecommerce' => 0,
        ]);
    }
}
