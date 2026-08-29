<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\POS\Models\Branch;
use App\POS\Models\InventoryAdjustment;
use App\POS\Models\InventoryBalance;
use App\POS\Models\OpeningStockRequest;
use App\POS\Models\PurchaseOrder;
use App\POS\Models\StockCount;
use App\POS\Models\StockTransfer;
use App\POS\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3InventoryLogisticsDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Store $storeA;
    protected Store $storeB;
    protected User $staffA;
    protected User $staffB;
    protected Warehouse $warehouseA;
    protected Warehouse $warehouseB;
    protected Product $productA;
    protected Product $productB;
    protected Supplier $supplierA;
    protected Supplier $supplierB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::create(['name' => 'Store Alpha', 'slug' => 'store-alpha', 'is_active' => true]);
        $this->storeB = Store::create(['name' => 'Store Beta', 'slug' => 'store-beta', 'is_active' => true]);

        $this->staffA = User::create(['name' => 'Staff Alpha', 'phone' => '09111111111', 'password' => bcrypt('password')]);
        $this->staffB = User::create(['name' => 'Staff Beta', 'phone' => '09222222222', 'password' => bcrypt('password')]);

        $this->storeA->users()->attach($this->staffA->id, ['role' => 'store_manager', 'status' => 'active']);
        $this->storeB->users()->attach($this->staffB->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->warehouseA = Warehouse::create(['store_id' => $this->storeA->id, 'name' => 'Warehouse A', 'code' => 'WHA', 'is_active' => true]);
        $this->warehouseB = Warehouse::create(['store_id' => $this->storeB->id, 'name' => 'Warehouse B', 'code' => 'WHB', 'is_active' => true]);

        $this->supplierA = Supplier::create(['store_id' => $this->storeA->id, 'name' => 'Supplier A', 'phone' => '09111111112']);
        $this->supplierB = Supplier::create(['store_id' => $this->storeB->id, 'name' => 'Supplier B', 'phone' => '09222222223']);

        $this->productA = Product::create([
            'store_id' => $this->storeA->id,
            'name' => 'Product Alpha',
            'slug' => 'product-alpha',
            'sku' => 'SKU-A',
            'cost_price' => 1000,
            'retail_price' => 1500,
            'wholesale_price' => 1200,
        ]);

        $this->productB = Product::create([
            'store_id' => $this->storeB->id,
            'name' => 'Product Beta',
            'slug' => 'product-beta',
            'sku' => 'SKU-B',
            'cost_price' => 2000,
            'retail_price' => 3000,
            'wholesale_price' => 2500,
        ]);

        InventoryBalance::create([
            'store_id' => $this->storeA->id,
            'warehouse_id' => $this->warehouseA->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 50,
            'unit_cost_avg' => 1000,
        ]);

        InventoryBalance::create([
            'store_id' => $this->storeB->id,
            'warehouse_id' => $this->warehouseB->id,
            'product_id' => $this->productB->id,
            'quantity_on_hand' => 50,
            'unit_cost_avg' => 2000,
        ]);
    }

    public function test_stock_transfers_cross_store_isolation(): void
    {
        $warehouseA2 = Warehouse::create(['store_id' => $this->storeA->id, 'name' => 'Warehouse A2', 'code' => 'WHA2', 'is_active' => true]);

        // 1. Staff A cannot create a transfer with Store B's warehouse or product
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/transfers", [
                'from_warehouse_id' => $this->warehouseA->id,
                'to_warehouse_id' => $this->warehouseB->id, // foreign warehouse
                'items' => [
                    ['product_id' => $this->productA->id, 'quantity' => 5],
                ],
            ])
            ->assertSessionHasErrors('to_warehouse_id');

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/transfers", [
                'from_warehouse_id' => $this->warehouseA->id,
                'to_warehouse_id' => $warehouseA2->id,
                'items' => [
                    ['product_id' => $this->productB->id, 'quantity' => 5], // foreign product
                ],
            ])
            ->assertSessionHasErrors('items.0.product_id');

        // 2. Transfer B created in Store B cannot be viewed, shipped, received, or cancelled by Staff A
        $transferB = StockTransfer::create([
            'store_id' => $this->storeB->id,
            'transfer_number' => 'TRF-B-001',
            'from_warehouse_id' => $this->warehouseB->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'status' => 'pending',
            'created_by' => $this->staffB->id,
        ]);

        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/pos/transfers/{$transferB->id}")
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/transfers/{$transferB->id}/ship")
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/transfers/{$transferB->id}/receive")
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/transfers/{$transferB->id}/cancel")
            ->assertForbidden();
    }

    public function test_stock_counts_cross_store_isolation(): void
    {
        $catB = Category::create(['store_id' => $this->storeB->id, 'name' => 'Category B', 'slug' => 'cat-b']);

        // 1. Staff A cannot create a stock count session with Store B's category or warehouse
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/admin/stock-count", [
                'scope' => 'category',
                'category_ids' => [$catB->id],
                'warehouse_id' => $this->warehouseB->id,
            ])
            ->assertSessionHasErrors(['category_ids.0', 'warehouse_id']);

        // 2. Stock count session in Store B cannot be viewed by Staff A
        $sessionB = StockCount::create([
            'store_id' => $this->storeB->id,
            'session_number' => 'SC-B-001',
            'scope' => 'all',
            'status' => 'in_progress',
            'total_items' => 10,
            'counted_items' => 0,
            'variance_items' => 0,
            'created_by' => $this->staffB->id,
        ]);

        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/stock-count/{$sessionB->id}")
            ->assertNotFound();
    }

    public function test_purchase_orders_and_payables_cross_store_isolation(): void
    {
        // 1. Staff A cannot create a PO with Store B's supplier or product
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/purchases", [
                'supplier_id' => $this->supplierB->id,
                'items' => [
                    ['product_id' => $this->productB->id, 'quantity' => 10, 'unit_cost' => 1000],
                ],
            ])
            ->assertSessionHasErrors(['supplier_id', 'items.0.product_id']);

        // 2. PO in Store B cannot be viewed, ordered, received, or paid by Staff A
        $poB = PurchaseOrder::create([
            'store_id' => $this->storeB->id,
            'supplier_id' => $this->supplierB->id,
            'po_number' => 'PO-B-001',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total_cost' => 20000,
            'paid_amount' => 0,
            'remaining_balance' => 20000,
            'created_by' => $this->staffB->id,
        ]);

        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/pos/purchases/{$poB->id}")
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/purchases/{$poB->id}/order")
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/purchases/{$poB->id}/receive")
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/purchases/{$poB->id}/cancel")
            ->assertNotFound();

        // 3. Staff A cannot view or pay Store B's supplier payables
        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/pos/purchases/payables/{$this->supplierB->id}")
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/purchases/payables/{$this->supplierB->id}/pay", [
                'amount' => 5000,
            ])
            ->assertNotFound();
    }

    public function test_opening_stock_and_adjustments_cross_store_isolation(): void
    {
        // 1. Staff A cannot submit opening stock with Store B's product
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/opening-stock", [
                'items' => [
                    ['product_id' => $this->productB->id, 'quantity' => 10, 'unit_cost' => 1000],
                ],
            ])
            ->assertSessionHas('error');

        // 2. Staff A cannot approve or reject Opening Stock Request from Store B
        $osrB = OpeningStockRequest::create([
            'store_id' => $this->storeB->id,
            'branch_id' => Branch::create(['store_id' => $this->storeB->id, 'name' => 'Branch B', 'code' => 'BRB', 'is_active' => true])->id,
            'warehouse_id' => $this->warehouseB->id,
            'request_number' => 'OSR-B-001',
            'status' => 'pending',
            'total_quantity' => 10,
            'total_cost' => 10000,
            'submitted_by' => $this->staffB->id,
            'client_transaction_id' => 'osr:test-b',
        ]);

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/opening-stock/{$osrB->id}/approve")
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/opening-stock/{$osrB->id}/reject")
            ->assertNotFound();

        // 3. Staff A cannot submit adjustment with Store B's product
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/adjustments", [
                'items' => [
                    ['product_id' => $this->productB->id, 'quantity' => 5, 'reason' => 'Damaged'],
                ],
            ])
            ->assertSessionHas('error');

        // 4. Staff A cannot approve or reject Inventory Adjustment from Store B
        $adjB = InventoryAdjustment::create([
            'store_id' => $this->storeB->id,
            'warehouse_id' => $this->warehouseB->id,
            'adjustment_number' => 'ADJ-B-001',
            'status' => 'pending',
            'total_quantity' => 5,
            'submitted_by' => $this->staffB->id,
            'client_transaction_id' => 'adj:test-b',
        ]);

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/adjustments/{$adjB->id}/approve")
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/adjustments/{$adjB->id}/reject")
            ->assertNotFound();
    }
}

