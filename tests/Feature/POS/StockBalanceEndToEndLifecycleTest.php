<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\InventoryBalance;
use App\POS\Services\InventoryService;
use App\POS\Services\PosReportService;
use App\POS\Services\StoreLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockBalanceEndToEndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventory;
    private PosReportService $reports;
    private StoreLocationService $locations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventory = app(InventoryService::class);
        $this->reports = app(PosReportService::class);
        $this->locations = app(StoreLocationService::class);
    }

    public function test_stock_balance_across_purchase_sale_return_and_adjustment_lifecycle(): void
    {
        $store = Store::create([
            'name' => 'Tech Retail Store',
            'slug' => 'tech-retail-store',
            'currency' => 'MMK',
            'is_active' => true,
        ]);

        $manager = User::create([
            'name' => 'Store Manager',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'store_owner',
        ]);
        $store->users()->attach($manager->id, ['role' => 'store_owner']);

        $warehouse = $this->locations->defaultWarehouse($store);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Anker PowerPort 20W Fast Charger',
            'slug' => 'anker-powerport-20w-fast-charger',
            'sku' => 'ANK-20W-001',
            'retail_price' => 35000,
            'wholesale_price' => 30000,
            'purchase_price' => 20000,
            'is_active' => true,
        ]);

        // Step 1: Initial State (Opening Stock: 10 units @ 20,000 MMK)
        $this->inventory->postMovement([
            'store' => $store,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => '10.000',
            'unit_cost' => '20000.0000',
            'occurred_at' => now(),
        ]);

        $report1 = $this->reports->stockReport($store);
        $this->assertSame('10.000', $this->inventory->totalOnHand($store->id, $product->id));
        $this->assertSame('10.000', $report1['total_units']);
        $this->assertEquals(200000.00, (float) $report1['total_value']); // 10 * 20,000

        // Step 2: Purchase Inflow (အဝယ်: 20 units @ 22,000 MMK)
        $this->inventory->postMovement([
            'store' => $store,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase_received',
            'quantity_delta' => '20.000',
            'unit_cost' => '22000.0000',
            'occurred_at' => now(),
        ]);

        $report2 = $this->reports->stockReport($store);
        // On hand = 10 + 20 = 30
        $this->assertSame('30.000', $this->inventory->totalOnHand($store->id, $product->id));
        $this->assertSame('30.000', $report2['total_units']);
        // Weighted Avg Cost: (10*20000 + 20*22000)/30 = (200000 + 440000)/30 = 640000/30 = 21333.3333
        $balance = InventoryBalance::where('product_id', $product->id)->firstOrFail();
        $this->assertEqualsWithDelta(21333.3333, (float) $balance->unit_cost_avg, 0.01);
        $this->assertEqualsWithDelta(640000.00, (float) $report2['total_value'], 1.0);

        // Step 3: POS Counter Sale (အရောင်း: 8 units sold)
        $this->inventory->postMovement([
            'store' => $store,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'movement_type' => 'pos_sale',
            'quantity_delta' => '-8.000',
            'occurred_at' => now(),
        ]);

        $report3 = $this->reports->stockReport($store);
        // On hand = 30 - 8 = 22
        $this->assertSame('22.000', $this->inventory->totalOnHand($store->id, $product->id));
        $this->assertSame('22.000', $report3['total_units']);

        // Step 4: Sales Return / Refund (အရောင်းပြန်သွင်း: 3 units returned)
        $this->inventory->postMovement([
            'store' => $store,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'movement_type' => 'sales_return',
            'quantity_delta' => '3.000',
            'occurred_at' => now(),
        ]);

        $report4 = $this->reports->stockReport($store);
        // On hand = 22 + 3 = 25
        $this->assertSame('25.000', $this->inventory->totalOnHand($store->id, $product->id));
        $this->assertSame('25.000', $report4['total_units']);

        // Step 5: Exchange Return / Inflow (အလဲ အဝင်: 1 unit returned in exchange)
        $this->inventory->postMovement([
            'store' => $store,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'movement_type' => 'exchange_return',
            'quantity_delta' => '1.000',
            'occurred_at' => now(),
        ]);

        // Step 6: Stock Adjustment Out / Damage (အတိုးအလျော့ အထွက်: 3 units damaged/adjusted out)
        $this->inventory->postMovement([
            'store' => $store,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'movement_type' => 'adjustment_out',
            'quantity_delta' => '-3.000',
            'occurred_at' => now(),
        ]);

        $report5 = $this->reports->stockReport($store);
        // Final On hand = 25 + 1 - 3 = 23 units
        $this->assertSame('23.000', $this->inventory->totalOnHand($store->id, $product->id));
        $this->assertSame('23.000', $report5['total_units']);

        // Verify Web UI Report endpoint
        $response = $this->actingAs($manager)
            ->get("/store/{$store->slug}/pos/reports/stock");

        $response->assertOk();
        $response->assertSee('Anker PowerPort 20W Fast Charger');
        $response->assertSee('23'); // Quantity on hand
    }
}
