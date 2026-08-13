<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\POS\Models\InventoryBalance;
use App\POS\Models\InventoryMovement;
use App\POS\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostingServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InventoryService::class);
    }

    private function makeStore(string $slug = 'store-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function makeProduct(Store $store, string $slug = 'product-a'): Product
    {
        return Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-' . strtoupper($slug),
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'retail_price' => 10000,
            'wholesale_price' => 8000,
            'stock_status' => 'out_of_stock',
            'is_featured' => false,
        ]);
    }

    private function average(Store $store, Product $product): string
    {
        return (string) $this->service->balanceFor($store->id, $product->id, null, $store->defaultWarehouse->id)->unit_cost_avg;
    }

    /* ------------------------------------------------------------------ */
    /*  Receiving                                                          */
    /* ------------------------------------------------------------------ */

    public function test_receiving_recalculates_weighted_average(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'unit_cost' => 8000,
        ]);
        $this->assertSame('8000.0000', $this->average($store, $product));

        // new_avg = (10×8000 + 5×10000) / 15 = 130000/15 = 8666.6667
        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase_received',
            'quantity_delta' => 5,
            'unit_cost' => 10000,
        ]);

        $this->assertSame('8666.6667', $this->average($store, $product));
    }

    public function test_first_receipt_establishes_average(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase_received',
            'quantity_delta' => 8,
            'unit_cost' => 12345,
        ]);

        $this->assertSame('12345.0000', $this->average($store, $product));
    }

    /* ------------------------------------------------------------------ */
    /*  COGS carry (no average change)                                     */
    /* ------------------------------------------------------------------ */

    public function test_sale_carries_cogs_without_changing_average(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'unit_cost' => 8000,
        ]);
        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase_received',
            'quantity_delta' => 5,
            'unit_cost' => 10000,
        ]);

        $sale = $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'pos_sale',
            'quantity_delta' => -3,
        ]);

        // COGS auto-filled from the current average.
        $this->assertSame('8666.6667', (string) $sale->unit_cost);
        $this->assertSame('8666.6667', $this->average($store, $product));
    }

    public function test_sales_return_restores_original_cost_without_recalc(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'unit_cost' => 8000,
        ]);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'pos_sale',
            'quantity_delta' => -3,
        ]);

        // Return restores the original sale-line cost; average is NOT recalculated.
        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'sales_return',
            'quantity_delta' => 1,
            'unit_cost' => 8000, // original sale-line cost
        ]);

        $this->assertSame('8000.0000', $this->average($store, $product));
        $this->assertSame('8.000', $this->service->totalOnHand($store->id, $product->id));
    }

    /* ------------------------------------------------------------------ */
    /*  Purchase return & adjustments                                      */
    /* ------------------------------------------------------------------ */

    public function test_purchase_return_at_current_average_keeps_average(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'unit_cost' => 8000,
        ]);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase_returned',
            'quantity_delta' => -2,
            'unit_cost' => 8000,
        ]);

        $this->assertSame('8000.0000', $this->average($store, $product));
        $this->assertSame('8.000', $this->service->totalOnHand($store->id, $product->id));
    }

    public function test_purchase_return_to_zero_resets_average(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 5,
            'unit_cost' => 8000,
        ]);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase_returned',
            'quantity_delta' => -5,
            'unit_cost' => 8000,
        ]);

        $this->assertSame('0.0000', $this->average($store, $product));
        $this->assertSame('0.000', $this->service->totalOnHand($store->id, $product->id));
    }

    public function test_adjustment_uses_current_average_without_changing_it(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'unit_cost' => 8000,
        ]);

        $adjustment = $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'adjustment_in',
            'quantity_delta' => 2,
        ]);

        $this->assertSame('8000.0000', (string) $adjustment->unit_cost);
        $this->assertSame('8000.0000', $this->average($store, $product));
    }

    /* ------------------------------------------------------------------ */
    /*  Serial / IMEI specific cost                                        */
    /* ------------------------------------------------------------------ */

    public function test_serial_specific_cost_skips_average(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'unit_cost' => 8000,
        ]);

        $serial = $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase_received',
            'quantity_delta' => 1,
            'unit_cost' => 9999,
            'metadata' => ['serial_number' => 'IMEI-123456789', 'costing' => 'specific'],
        ]);

        // Specific cost recorded on the movement; average untouched.
        $this->assertSame('9999.0000', (string) $serial->unit_cost);
        $this->assertSame('8000.0000', $this->average($store, $product));
    }

    /* ------------------------------------------------------------------ */
    /*  Reversal replay                                                    */
    /* ------------------------------------------------------------------ */

    public function test_reversal_of_receipt_recomputes_average(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'unit_cost' => 8000,
        ]);

        $receipt = $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase_received',
            'quantity_delta' => 5,
            'unit_cost' => 10000,
        ]);
        $this->assertSame('8666.6667', $this->average($store, $product));

        $this->service->reverseMovement($receipt);

        // Only the opening balance remains → average back to 8000.
        $this->assertSame('8000.0000', $this->average($store, $product));
        $this->assertSame('10.000', $this->service->totalOnHand($store->id, $product->id));
    }

    public function test_reversal_of_sale_keeps_average(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'unit_cost' => 8000,
        ]);

        $sale = $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'pos_sale',
            'quantity_delta' => -3,
        ]);

        $this->service->reverseMovement($sale);

        $this->assertSame('8000.0000', $this->average($store, $product));
        $this->assertSame('10.000', $this->service->totalOnHand($store->id, $product->id));
    }

    /* ------------------------------------------------------------------ */
    /*  Reconcile & variants                                               */
    /* ------------------------------------------------------------------ */

    public function test_reconcile_rebuild_recomputes_averages(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'unit_cost' => 8000,
        ]);
        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase_received',
            'quantity_delta' => 5,
            'unit_cost' => 10000,
        ]);

        // Tamper the cache, then rebuild — average must be derived again.
        InventoryBalance::query()->update(['quantity_on_hand' => 99, 'unit_cost_avg' => 1]);

        $this->service->rebuildBalances();

        $this->assertSame('15.000', $this->service->totalOnHand($store->id, $product->id));
        $this->assertSame('8666.6667', $this->average($store, $product));
    }

    public function test_variant_keeps_its_own_average(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Black',
            'sku' => 'SKU-BLACK',
            'retail_price' => 12000,
            'wholesale_price' => 9000,
        ]);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'unit_cost' => 9000,
        ]);
        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'unit_cost' => 7000,
        ]);

        $variantBalance = $this->service->balanceFor($store->id, $product->id, $variant->id, $store->defaultWarehouse->id);
        $this->assertSame('7000.0000', (string) $variantBalance->unit_cost_avg);
        $this->assertSame('9000.0000', $this->average($store, $product));
        $this->assertSame(InventoryMovement::count(), 2);
    }
}
