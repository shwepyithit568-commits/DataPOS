<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\StockTransfer;
use App\POS\Models\Warehouse;
use App\POS\Models\InventoryBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $user;
    protected Warehouse $warehouseFrom;
    protected Warehouse $warehouseTo;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'slug' => 'datapos-mobile',
            'name' => 'DataPOS Mobile',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Manager User',
            'phone' => '09123456789',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $this->store->users()->attach($this->user->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->warehouseFrom = Warehouse::create([
            'store_id' => $this->store->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'is_active' => true,
        ]);

        $this->warehouseTo = Warehouse::create([
            'store_id' => $this->store->id,
            'name' => 'Branch Store',
            'code' => 'WH-BRANCH',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'iPhone 15 Pro Max',
            'slug' => 'iphone-15-pro-max',
            'sku' => 'IPH15PM',
            'barcode' => '8801234567890',
            'cost_price' => 2500000,
            'retail_price' => 2800000,
            'wholesale_price' => 2600000,
        ]);

        InventoryBalance::create([
            'store_id' => $this->store->id,
            'warehouse_id' => $this->warehouseFrom->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 10,
            'unit_cost_avg' => 2500000,
        ]);
    }

    public function test_transfers_index_page_loads_with_kpi_and_toolbar(): void
    {
        StockTransfer::create([
            'store_id' => $this->store->id,
            'transfer_number' => 'TRF-20260827-0001',
            'from_warehouse_id' => $this->warehouseFrom->id,
            'to_warehouse_id' => $this->warehouseTo->id,
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('pos.transfers.index', ['store_slug' => $this->store->slug]));

        $response->assertOk();
        $response->assertSee('TRF-20260827-0001');
        $response->assertSee($this->warehouseFrom->name);
        $response->assertSee($this->warehouseTo->name);
    }

    public function test_transfers_create_page_loads_with_warehouses_and_inventory(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('pos.transfers.create', ['store_slug' => $this->store->slug]));

        $response->assertOk();
        $response->assertSee($this->warehouseFrom->name);
        $response->assertSee($this->warehouseTo->name);
    }

    public function test_transfers_can_be_stored(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('pos.transfers.store', ['store_slug' => $this->store->slug]), [
                'from_warehouse_id' => $this->warehouseFrom->id,
                'to_warehouse_id' => $this->warehouseTo->id,
                'notes' => 'Test stock transfer notes',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'store_id' => $this->store->id,
            'from_warehouse_id' => $this->warehouseFrom->id,
            'to_warehouse_id' => $this->warehouseTo->id,
            'status' => 'pending',
        ]);
    }

    public function test_transfers_workflow_ship_and_receive(): void
    {
        $transfer = StockTransfer::create([
            'store_id' => $this->store->id,
            'transfer_number' => StockTransfer::generateNumber($this->store->id),
            'from_warehouse_id' => $this->warehouseFrom->id,
            'to_warehouse_id' => $this->warehouseTo->id,
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $transfer->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 3,
            'unit_cost' => 2500000,
        ]);

        // Ship
        $shipResponse = $this->actingAs($this->user)
            ->post(route('pos.transfers.ship', ['store_slug' => $this->store->slug, 'transfer' => $transfer->id]));
        $shipResponse->assertRedirect();
        $this->assertEquals('in_transit', $transfer->fresh()->status);

        // Receive
        $receiveResponse = $this->actingAs($this->user)
            ->post(route('pos.transfers.receive', ['store_slug' => $this->store->slug, 'transfer' => $transfer->id]));
        $receiveResponse->assertRedirect();
        $this->assertEquals('completed', $transfer->fresh()->status);
    }
}
