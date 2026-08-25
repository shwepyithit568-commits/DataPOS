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

class WebCatalogProductVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected Store $otherStore;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Store Alpha', 'slug' => 'store-alpha', 'is_active' => true]);
        $this->store->setting()->create(['store_name' => 'Store Alpha', 'default_language' => 'en']);

        $this->otherStore = Store::create(['name' => 'Store Beta', 'slug' => 'store-beta', 'is_active' => true]);
        $this->otherStore->setting()->create(['store_name' => 'Store Beta', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['phone' => '09123456789']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    private function makeProduct(array $overrides = [], ?Store $store = null): Product
    {
        $targetStore = $store ?? $this->store;
        $name = $overrides['name'] ?? 'Product ' . Str::random(4);

        return Product::create(array_merge([
            'store_id'        => $targetStore->id,
            'category_id'     => Category::create(['store_id' => $targetStore->id, 'name' => 'Cat ' . Str::random(4), 'slug' => 'cat-' . Str::random(4)])->id,
            'brand_id'        => Brand::create(['store_id' => $targetStore->id, 'name' => 'Brand ' . Str::random(4), 'slug' => 'brand-' . Str::random(4)])->id,
            'sku'             => 'SKU-' . strtoupper(Str::random(6)),
            'name'            => $name,
            'slug'            => Str::slug($name . '-' . Str::random(4)),
            'retail_price'    => 15000,
            'wholesale_price' => 12000,
            'stock_status'    => 'in_stock',
            'is_ecommerce'    => true,
            'is_featured'     => false,
        ], $overrides));
    }

    public function test_manager_can_access_web_products_index_with_stats(): void
    {
        $this->makeProduct(['name' => 'Online Item 1', 'is_ecommerce' => true]);
        $this->makeProduct(['name' => 'Online Item 2', 'is_ecommerce' => true, 'is_featured' => true]);
        $this->makeProduct(['name' => 'Counter Item 1', 'is_ecommerce' => false]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/web-products");

        $response->assertStatus(200);
        $response->assertViewIs('admin.web_products.index');
        $response->assertSee('Online Item 1');
        $response->assertSee('Online Item 2');
        $response->assertSee('Counter Item 1');
    }

    public function test_toggle_visibility_changes_state_via_ajax_and_post(): void
    {
        $product = $this->makeProduct(['is_ecommerce' => true]);

        // AJAX Toggle
        $response = $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/web-products/toggle-visibility", [
                'product_id' => $product->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'      => true,
            'is_ecommerce' => false,
        ]);
        $this->assertFalse($product->fresh()->is_ecommerce);

        // Toggle back
        $response2 = $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/web-products/toggle-visibility", [
                'product_id' => $product->id,
            ]);

        $response2->assertStatus(200);
        $response2->assertJson([
            'success'      => true,
            'is_ecommerce' => true,
        ]);
        $this->assertTrue($product->fresh()->is_ecommerce);
    }

    public function test_toggle_featured_changes_state(): void
    {
        $product = $this->makeProduct(['is_featured' => false]);

        $response = $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/web-products/toggle-featured", [
                'product_id' => $product->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'     => true,
            'is_featured' => true,
        ]);
        $this->assertTrue($product->fresh()->is_featured);
    }

    public function test_bulk_visibility_updates_selected_products(): void
    {
        $p1 = $this->makeProduct(['is_ecommerce' => true]);
        $p2 = $this->makeProduct(['is_ecommerce' => true]);
        $p3 = $this->makeProduct(['is_ecommerce' => true]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/web-products/bulk-visibility", [
                'ids'          => [$p1->id, $p2->id],
                'is_ecommerce' => 0,
            ]);

        $response->assertRedirect();
        $this->assertFalse($p1->fresh()->is_ecommerce);
        $this->assertFalse($p2->fresh()->is_ecommerce);
        $this->assertTrue($p3->fresh()->is_ecommerce);
    }

    public function test_bulk_featured_updates_selected_products(): void
    {
        $p1 = $this->makeProduct(['is_featured' => false]);
        $p2 = $this->makeProduct(['is_featured' => false]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/web-products/bulk-featured", [
                'ids'         => [$p1->id, $p2->id],
                'is_featured' => 1,
            ]);

        $response->assertRedirect();
        $this->assertTrue($p1->fresh()->is_featured);
        $this->assertTrue($p2->fresh()->is_featured);
    }

    public function test_cannot_modify_products_belonging_to_another_store(): void
    {
        $foreignProduct = $this->makeProduct(['is_ecommerce' => true], $this->otherStore);

        $response = $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/web-products/toggle-visibility", [
                'product_id' => $foreignProduct->id,
            ]);

        $response->assertStatus(404);
        $this->assertTrue($foreignProduct->fresh()->is_ecommerce);
    }

    public function test_filtering_by_visibility_and_featured(): void
    {
        $online = $this->makeProduct(['name' => 'Apple Phone Online', 'is_ecommerce' => true, 'is_featured' => true]);
        $counter = $this->makeProduct(['name' => 'Repair Cable Counter', 'is_ecommerce' => false, 'is_featured' => false]);

        // Online filter
        $res1 = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/web-products?visibility=online");
        $res1->assertSee('Apple Phone Online');
        $res1->assertDontSee('Repair Cable Counter');

        // Counter only filter
        $res2 = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/web-products?visibility=counter_only");
        $res2->assertSee('Repair Cable Counter');
        $res2->assertDontSee('Apple Phone Online');

        // Featured filter
        $res3 = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/web-products?featured=featured");
        $res3->assertSee('Apple Phone Online');
        $res3->assertDontSee('Repair Cable Counter');
    }
}
