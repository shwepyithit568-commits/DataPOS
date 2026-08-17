<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductFormPurchaseFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Store One', 'slug' => 'store-one']);
        $this->store->setting()->create(['store_name' => 'Store One', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['phone' => '09111111111']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->customer = User::factory()->create(['phone' => '09222222222', 'role' => 'customer']);
    }

    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Phone',
            'sku' => 'TEST-SKU-001',
            'retail_price' => 1200000,
            'wholesale_price' => 1000000,
            'stock_status' => 'in_stock',
        ], $overrides);
    }

    public function test_create_with_initial_stock_posts_opening_balance_movement(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products", $this->productPayload([
                'initial_stock' => 5,
                'purchase_cost' => 900000,
            ]));

        $response->assertRedirect();

        $product = Product::where('sku', 'TEST-SKU-001')->firstOrFail();

        $balance = DB::table('inventory_balances')
            ->where('store_id', $this->store->id)
            ->where('product_id', $product->id)
            ->sum('quantity_on_hand');

        $this->assertEqualsWithDelta(5, (float) $balance, 0.001);
        $this->assertDatabaseHas('inventory_movements', [
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'source_type' => 'product_create',
            'quantity_delta' => 5,
        ]);
        // The ledger refresh wins over the form field.
        $this->assertSame('in_stock', $product->fresh()->stock_status);
        // Initial stock valued at the purchase cost.
        $this->assertEqualsWithDelta(900000, (float) DB::table('inventory_movements')->where('product_id', $product->id)->value('unit_cost'), 0.001);
    }

    public function test_create_with_zero_initial_stock_posts_no_movement(): void
    {
        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products", $this->productPayload(['initial_stock' => 0]));

        $product = Product::where('sku', 'TEST-SKU-001')->firstOrFail();

        $this->assertDatabaseMissing('inventory_movements', [
            'store_id' => $this->store->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_create_with_auto_sku_generates_unique_sku(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products", $this->productPayload([
                'sku' => '',
                'auto_sku' => 1,
            ]));

        $response->assertRedirect();

        $product = Product::where('store_id', $this->store->id)->firstOrFail();
        $this->assertStringStartsWith('SKU-', $product->sku);
        $this->assertNotSame('', $product->sku);

        // Second product gets a different SKU.
        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products", $this->productPayload([
                'name' => 'Test Phone Two',
                'sku' => '',
                'auto_sku' => 1,
            ]));

        $skus = Product::where('store_id', $this->store->id)->pluck('sku');
        $this->assertSame($skus->count(), $skus->unique()->count());
    }

    public function test_create_saves_supplier_reorder_level_and_purchase_cost(): void
    {
        $supplier = Supplier::create(['store_id' => $this->store->id, 'name' => 'Mobile Hub Trading']);

        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products", $this->productPayload([
                'supplier_id' => $supplier->id,
                'reorder_level' => 10,
                'purchase_cost' => 850000.5,
            ]));

        $product = Product::where('sku', 'TEST-SKU-001')->firstOrFail();
        $this->assertSame($supplier->id, $product->supplier_id);
        $this->assertEqualsWithDelta(10, (float) $product->reorder_level, 0.001);
        $this->assertEqualsWithDelta(850000.5, (float) $product->purchase_cost, 0.001);
    }

    public function test_update_saves_purchase_fields(): void
    {
        $product = Product::create([
            'store_id' => $this->store->id,
            'sku' => 'TEST-SKU-001',
            'name' => 'Test Phone',
            'slug' => 'test-phone',
            'retail_price' => 1200000,
            'wholesale_price' => 1000000,
            'stock_status' => 'in_stock',
        ]);
        $supplier = Supplier::create(['store_id' => $this->store->id, 'name' => 'New Supplier']);

        $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/products/{$product->id}", $this->productPayload([
                'reorder_level' => 7,
                'supplier_id' => $supplier->id,
                'purchase_cost' => 700000,
            ]));

        $product->refresh();
        $this->assertSame($supplier->id, $product->supplier_id);
        $this->assertEqualsWithDelta(7, (float) $product->reorder_level, 0.001);
        $this->assertEqualsWithDelta(700000, (float) $product->purchase_cost, 0.001);
    }

    public function test_supplier_quick_store_creates_and_reuses_by_name(): void
    {
        $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/suppliers/quick-store", [
                'name' => 'Mobile Hub Trading',
                'phone' => '09123456789',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('suppliers', [
            'store_id' => $this->store->id,
            'name' => 'Mobile Hub Trading',
        ]);

        // Same name again → reuses the existing row (no duplicates).
        $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/suppliers/quick-store", [
                'name' => 'mobile hub trading',
            ])
            ->assertOk();

        $this->assertSame(1, Supplier::where('store_id', $this->store->id)->count());
    }

    public function test_supplier_quick_store_requires_name(): void
    {
        $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/suppliers/quick-store", ['name' => ''])
            ->assertStatus(422);
    }

    public function test_supplier_quick_store_blocked_for_non_staff(): void
    {
        $this->actingAs($this->customer)
            ->postJson("/store/{$this->store->slug}/admin/suppliers/quick-store", ['name' => 'X'])
            ->assertStatus(403);
    }

    public function test_supplier_quick_store_is_store_scoped(): void
    {
        $otherStore = Store::create(['name' => 'Store Two', 'slug' => 'store-two']);
        $otherStore->setting()->create(['store_name' => 'Store Two', 'default_language' => 'en']);

        // Manager of store-one cannot quick-add a supplier to store-two.
        $this->actingAs($this->manager)
            ->postJson("/store/{$otherStore->slug}/admin/suppliers/quick-store", ['name' => 'X'])
            ->assertStatus(403);
    }

    public function test_create_form_renders_inventory_purchase_section(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/products/create");

        $response->assertStatus(200);
        $response->assertSeeText('Inventory & Purchase');
        $response->assertSeeText('Initial stock');
        $response->assertSeeText('Reorder level');
        $response->assertSeeText('Purchase cost');
        $response->assertSeeText('Auto-generate SKU');
    }

    public function test_category_quick_store_creates_sub_category_under_main(): void
    {
        $main = Category::create(['store_id' => $this->store->id, 'name' => 'Mobile Phones', 'slug' => 'mobile-phones']);

        $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/categories/quick-store", [
                'name' => 'Apple',
                'parent_id' => $main->id,
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'parent_id' => $main->id,
                'parent' => 'Mobile Phones',
            ]);

        $this->assertDatabaseHas('categories', [
            'store_id' => $this->store->id,
            'name' => 'Apple',
            'parent_id' => $main->id,
        ]);
    }

    public function test_category_quick_store_rejects_foreign_parent(): void
    {
        $otherStore = Store::create(['name' => 'Store Two', 'slug' => 'store-two']);
        $otherMain = Category::create(['store_id' => $otherStore->id, 'name' => 'Foreign', 'slug' => 'foreign']);

        $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/categories/quick-store", [
                'name' => 'Sneaky',
                'parent_id' => $otherMain->id,
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('categories', ['store_id' => $this->store->id, 'name' => 'Sneaky']);
    }

    public function test_category_quick_store_rejects_sub_of_sub(): void
    {
        $main = Category::create(['store_id' => $this->store->id, 'name' => 'Mobile Phones', 'slug' => 'mobile-phones']);
        $sub = Category::create(['store_id' => $this->store->id, 'name' => 'Apple', 'slug' => 'apple', 'parent_id' => $main->id]);

        $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/categories/quick-store", [
                'name' => 'iPhone 16',
                'parent_id' => $sub->id,
            ])
            ->assertStatus(422);
    }

    public function test_create_form_quick_category_modal_offers_sub_of_option(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/products/create");

        $response->assertStatus(200);
        // The modal lets the cashier pick Main or a Sub-of-main (Master Data
        // connection) — the type label and the Main option render.
        $response->assertSeeText('Add as');
        $response->assertSeeText('Main Category');
    }
}
