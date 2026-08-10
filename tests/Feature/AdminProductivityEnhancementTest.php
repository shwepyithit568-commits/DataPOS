<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductivityEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected Store $storeA;
    protected Store $storeB;
    protected User $managerA;
    protected User $managerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $this->storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $this->managerA = User::factory()->create(['phone' => '09111111111']);
        $this->managerA->stores()->attach($this->storeA->id, ['role' => 'store_manager']);

        $this->managerB = User::factory()->create(['phone' => '09222222222']);
        $this->managerB->stores()->attach($this->storeB->id, ['role' => 'store_manager']);
    }

    public function test_admin_cannot_access_other_store_data(): void
    {
        $productB = Product::create([
            'store_id' => $this->storeB->id,
            'name' => 'Secret Store B Item',
            'sku' => 'SECRET-B',
            'slug' => 'secret-store-b-item',
            'retail_price' => 10000,
            'wholesale_price' => 7000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->actingAs($this->managerA)
            ->get("/store/{$this->storeA->slug}/admin/products?search=Secret");

        $response->assertStatus(200);
        $response->assertDontSee('Secret Store B Item');
    }

    public function test_search_and_filter_and_pagination(): void
    {
        $cat = Category::create(['store_id' => $this->storeA->id, 'name' => 'Audio', 'slug' => 'audio']);
        $brand = Brand::create(['store_id' => $this->storeA->id, 'name' => 'Sony', 'slug' => 'sony']);

        $p1 = Product::create([
            'store_id' => $this->storeA->id,
            'category_id' => $cat->id,
            'brand_id' => $brand->id,
            'name' => 'Wireless Headphone',
            'sku' => 'SONY-WH100',
            'slug' => 'wireless-headphone',
            'retail_price' => 50000,
            'wholesale_price' => 40000,
            'stock_status' => 'in_stock',
        ]);

        $p2 = Product::create([
            'store_id' => $this->storeA->id,
            'name' => 'Wired Earphone',
            'sku' => 'EAR-01',
            'slug' => 'wired-earphone',
            'retail_price' => 5000,
            'wholesale_price' => 3000,
            'stock_status' => 'out_of_stock',
        ]);

        // Search by Brand name
        $response1 = $this->actingAs($this->managerA)
            ->get("/store/{$this->storeA->slug}/admin/products?search=Sony");

        $response1->assertStatus(200);
        $response1->assertSee('Wireless Headphone');
        $response1->assertDontSee('Wired Earphone');

        // Filter by Stock Status
        $response2 = $this->actingAs($this->managerA)
            ->get("/store/{$this->storeA->slug}/admin/products?stock_status=out_of_stock");

        $response2->assertStatus(200);
        $response2->assertSee('Wired Earphone');
        $response2->assertDontSee('Wireless Headphone');
    }

    public function test_quick_create_category_and_brand_respects_store_isolation(): void
    {
        // 1. Quick create Category as Manager A
        $catResponse = $this->actingAs($this->managerA)
            ->postJson("/store/{$this->storeA->slug}/admin/categories/quick-store", [
                'name' => 'Fast Chargers',
            ]);

        $catResponse->assertStatus(200);
        $catResponse->assertJson(['success' => true]);

        $catId = $catResponse->json('id');
        $this->assertDatabaseHas('categories', [
            'id' => $catId,
            'store_id' => $this->storeA->id,
            'name' => 'Fast Chargers',
        ]);

        // Manager B cannot see Manager A's quick created category
        $responseB = $this->actingAs($this->managerB)
            ->get("/store/{$this->storeB->slug}/admin/products/create");

        $responseB->assertStatus(200);
        $responseB->assertDontSee('Fast Chargers');

        // 2. Quick create Brand as Manager A
        $brandResponse = $this->actingAs($this->managerA)
            ->postJson("/store/{$this->storeA->slug}/admin/brands/quick-store", [
                'name' => 'Baseus',
            ]);

        $brandResponse->assertStatus(200);
        $brandResponse->assertJson(['success' => true]);

        $brandId = $brandResponse->json('id');
        $this->assertDatabaseHas('brands', [
            'id' => $brandId,
            'store_id' => $this->storeA->id,
            'name' => 'Baseus',
        ]);
    }

    public function test_bulk_stock_update_and_bulk_delete(): void
    {
        $p1 = Product::create([
            'store_id' => $this->storeA->id,
            'name' => 'Bulk Item 1',
            'sku' => 'BULK-1',
            'slug' => 'bulk-item-1',
            'retail_price' => 5000,
            'wholesale_price' => 3000,
            'stock_status' => 'in_stock',
        ]);

        $p2 = Product::create([
            'store_id' => $this->storeA->id,
            'name' => 'Bulk Item 2',
            'sku' => 'BULK-2',
            'slug' => 'bulk-item-2',
            'retail_price' => 6000,
            'wholesale_price' => 4000,
            'stock_status' => 'in_stock',
        ]);

        // Bulk Stock Update to out_of_stock
        $stockResponse = $this->actingAs($this->managerA)
            ->post("/store/{$this->storeA->slug}/admin/products/bulk-stock", [
                'ids' => [$p1->id, $p2->id],
                'stock_status' => 'out_of_stock',
            ]);

        $stockResponse->assertRedirect();
        $this->assertEquals('out_of_stock', $p1->fresh()->stock_status);
        $this->assertEquals('out_of_stock', $p2->fresh()->stock_status);

        // Bulk Delete
        $deleteResponse = $this->actingAs($this->managerA)
            ->post("/store/{$this->storeA->slug}/admin/products/bulk-delete", [
                'ids' => [$p1->id, $p2->id],
            ]);

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('products', ['id' => $p1->id]);
        $this->assertDatabaseMissing('products', ['id' => $p2->id]);
    }
}
