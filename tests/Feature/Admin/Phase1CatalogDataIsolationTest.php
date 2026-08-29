<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\POS\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1CatalogDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Store $storeA;
    protected Store $storeB;
    protected User $ownerA;
    protected User $ownerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::create(['name' => 'Store Alpha', 'slug' => 'store-alpha']);
        $this->storeA->setting()->create(['store_name' => 'Store Alpha', 'default_language' => 'en']);

        $this->storeB = Store::create(['name' => 'Store Beta', 'slug' => 'store-beta']);
        $this->storeB->setting()->create(['store_name' => 'Store Beta', 'default_language' => 'en']);

        $this->ownerA = User::factory()->create(['name' => 'Owner Alpha', 'phone' => '09111111111']);
        $this->ownerA->stores()->attach($this->storeA->id, ['role' => 'store_owner', 'status' => 'active']);

        $this->ownerB = User::factory()->create(['name' => 'Owner Beta', 'phone' => '09222222222']);
        $this->ownerB->stores()->attach($this->storeB->id, ['role' => 'store_owner', 'status' => 'active']);
    }

    public function test_category_cross_store_access_and_parent_isolation(): void
    {
        $catA = Category::create(['store_id' => $this->storeA->id, 'name' => 'Electronics A', 'slug' => 'electronics-a']);
        $catB = Category::create(['store_id' => $this->storeB->id, 'name' => 'Medicines B', 'slug' => 'medicines-b']);

        // 1. Owner A cannot edit Store B category
        $this->actingAs($this->ownerA)
            ->get("/store/{$this->storeA->slug}/admin/categories/{$catB->id}/edit")
            ->assertForbidden();

        // 2. Owner A cannot update Store B category
        $this->actingAs($this->ownerA)
            ->put("/store/{$this->storeA->slug}/admin/categories/{$catB->id}", [
                'name' => 'Hacked Name',
            ])
            ->assertForbidden();

        // 3. Owner A cannot delete Store B category
        $this->actingAs($this->ownerA)
            ->delete("/store/{$this->storeA->slug}/admin/categories/{$catB->id}")
            ->assertForbidden();

        // 4. Owner A cannot assign Store B category as parent_id
        $response = $this->actingAs($this->ownerA)
            ->post("/store/{$this->storeA->slug}/admin/categories", [
                'name' => 'Sub Category',
                'parent_id' => $catB->id,
            ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_brand_cross_store_access_isolation(): void
    {
        $brandA = Brand::create(['store_id' => $this->storeA->id, 'name' => 'Apple A', 'slug' => 'apple-a']);
        $brandB = Brand::create(['store_id' => $this->storeB->id, 'name' => 'Samsung B', 'slug' => 'samsung-b']);

        // 1. Cannot edit other store brand
        $this->actingAs($this->ownerA)
            ->get("/store/{$this->storeA->slug}/admin/brands/{$brandB->id}/edit")
            ->assertForbidden();

        // 2. Cannot update other store brand
        $this->actingAs($this->ownerA)
            ->put("/store/{$this->storeA->slug}/admin/brands/{$brandB->id}", [
                'name' => 'Hacked Brand',
            ])
            ->assertForbidden();

        // 3. Cannot delete other store brand
        $this->actingAs($this->ownerA)
            ->delete("/store/{$this->storeA->slug}/admin/brands/{$brandB->id}")
            ->assertForbidden();
    }

    public function test_supplier_and_warehouse_cross_store_isolation(): void
    {
        $supplierB = Supplier::create(['store_id' => $this->storeB->id, 'name' => 'Wholesale Supplier B', 'phone' => '0999999999']);
        $warehouseB = Warehouse::create(['store_id' => $this->storeB->id, 'name' => 'Main Warehouse B', 'code' => 'WH-B', 'is_active' => true]);

        // 1. Cannot edit Supplier B from Store A
        $this->actingAs($this->ownerA)
            ->get("/store/{$this->storeA->slug}/admin/suppliers/{$supplierB->id}/edit")
            ->assertForbidden();

        // 2. Cannot update Supplier B from Store A
        $this->actingAs($this->ownerA)
            ->put("/store/{$this->storeA->slug}/admin/suppliers/{$supplierB->id}", [
                'name' => 'Hacked Supplier',
            ])
            ->assertForbidden();

        // 3. Cannot update Warehouse B from Store A
        $this->actingAs($this->ownerA)
            ->put("/store/{$this->storeA->slug}/admin/warehouses/{$warehouseB->id}", [
                'name' => 'Hacked Warehouse',
            ])
            ->assertForbidden();
    }

    public function test_product_creation_rejects_foreign_store_relationships(): void
    {
        $catB = Category::create(['store_id' => $this->storeB->id, 'name' => 'Category B', 'slug' => 'cat-b']);
        $brandB = Brand::create(['store_id' => $this->storeB->id, 'name' => 'Brand B', 'slug' => 'brand-b']);
        $supplierB = Supplier::create(['store_id' => $this->storeB->id, 'name' => 'Supplier B']);
        $warehouseB = Warehouse::create(['store_id' => $this->storeB->id, 'name' => 'Warehouse B']);

        // Attempting to create Product in Store A with Store B's IDs must fail validation
        $response = $this->actingAs($this->ownerA)
            ->post("/store/{$this->storeA->slug}/admin/products", [
                'name' => 'New Isolated Product',
                'sku' => 'ISO-001',
                'retail_price' => 10000,
                'wholesale_price' => 8000,
                'category_id' => $catB->id,
                'brand_id' => $brandB->id,
                'supplier_id' => $supplierB->id,
                'warehouse_id' => $warehouseB->id,
            ]);

        $response->assertSessionHasErrors(['category_id', 'brand_id', 'supplier_id', 'warehouse_id']);
        $this->assertDatabaseMissing('products', ['sku' => 'ISO-001']);
    }

    public function test_product_update_and_bulk_actions_reject_foreign_store_ids(): void
    {
        $prodA = Product::create([
            'store_id' => $this->storeA->id,
            'name' => 'Product A',
            'slug' => 'prod-a',
            'sku' => 'PROD-A',
            'retail_price' => 15000,
            'wholesale_price' => 12000,
            'stock_status' => 'in_stock',
        ]);

        $prodB = Product::create([
            'store_id' => $this->storeB->id,
            'name' => 'Product B',
            'slug' => 'prod-b',
            'sku' => 'PROD-B',
            'retail_price' => 25000,
            'wholesale_price' => 20000,
            'stock_status' => 'in_stock',
        ]);

        // 1. Cannot access product details of another store
        $this->actingAs($this->ownerA)
            ->get("/store/{$this->storeA->slug}/admin/products/{$prodB->id}/details")
            ->assertForbidden();

        // 2. Cannot edit product of another store
        $this->actingAs($this->ownerA)
            ->get("/store/{$this->storeA->slug}/admin/products/{$prodB->id}/edit")
            ->assertForbidden();

        // 3. Bulk stock status update rejects Store B IDs in Store A request
        $bulkStockRes = $this->actingAs($this->ownerA)
            ->post("/store/{$this->storeA->slug}/admin/products/bulk-stock", [
                'ids' => [$prodB->id],
                'stock_status' => 'out_of_stock',
            ]);

        $bulkStockRes->assertSessionHasErrors('ids.0');

        // 4. Bulk delete rejects Store B IDs in Store A request
        $bulkDeleteRes = $this->actingAs($this->ownerA)
            ->post("/store/{$this->storeA->slug}/admin/products/bulk-delete", [
                'ids' => [$prodB->id],
            ]);

        $bulkDeleteRes->assertSessionHasErrors('ids.0');
        $this->assertDatabaseHas('products', ['id' => $prodB->id]);
    }
}
