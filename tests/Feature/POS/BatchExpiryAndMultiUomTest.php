<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductUnit;
use App\Models\Store;
use App\Services\BatchTrackingService;
use App\Services\UnitConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BatchExpiryAndMultiUomTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected Product $product;
    protected BatchTrackingService $batchService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name'             => 'Pharmacy Care & Agriculture',
            'slug'             => 'pharmacy-care',
            'business_profile' => 'pharmacy',
            'is_active'        => true,
        ]);

        $this->product = Product::create([
            'store_id'        => $this->store->id,
            'name'            => 'Paracetamol 500mg (Biogesic)',
            'slug'            => 'paracetamol-500mg',
            'sku'             => 'MED-PCM-500',
            'retail_price'    => 100,
            'wholesale_price' => 80,
            'buy_price'       => 60,
        ]);

        $this->batchService = app(BatchTrackingService::class);
    }

    public function test_multi_uom_quantity_and_price_conversions(): void
    {
        // 1 Base Tablet = 100 MMK
        $tabletUnit = ProductUnit::create([
            'store_id'          => $this->store->id,
            'product_id'        => $this->product->id,
            'unit_name'         => 'Tablet',
            'conversion_factor' => 1.0,
            'is_base_unit'      => true,
        ]);

        // 1 Strip = 10 Tablets (Custom promo price: 950 MMK instead of 1,000 MMK)
        $stripUnit = ProductUnit::create([
            'store_id'          => $this->store->id,
            'product_id'        => $this->product->id,
            'unit_name'         => 'Strip',
            'conversion_factor' => 10.0,
            'retail_price'      => 950,
            'wholesale_price'   => 750,
            'is_base_unit'      => false,
        ]);

        // 1 Box = 100 Tablets (Calculated multiplier fallback: 100 * 100 = 10,000 MMK)
        $boxUnit = ProductUnit::create([
            'store_id'          => $this->store->id,
            'product_id'        => $this->product->id,
            'unit_name'         => 'Box',
            'conversion_factor' => 100.0,
            'is_base_unit'      => false,
        ]);

        // Test Conversions
        $this->assertSame(50.0, UnitConversionService::convertToBaseQuantity($stripUnit, 5));
        $this->assertSame(300.0, UnitConversionService::convertToBaseQuantity($boxUnit, 3));
        $this->assertSame(4.0, UnitConversionService::convertFromBaseQuantity($boxUnit, 400));

        // Test Prices
        $this->assertSame(950.0, UnitConversionService::getUnitRetailPrice($this->product, $stripUnit));
        $this->assertSame(10000.0, UnitConversionService::getUnitRetailPrice($this->product, $boxUnit));
        $this->assertSame(750.0, UnitConversionService::getUnitWholesalePrice($this->product, $stripUnit));
        $this->assertSame(8000.0, UnitConversionService::getUnitWholesalePrice($this->product, $boxUnit));
    }

    public function test_fefo_allocation_picks_earliest_expiring_batches_first(): void
    {
        // Batch 1: Expiring in 10 days (5 units)
        ProductBatch::create([
            'store_id'           => $this->store->id,
            'product_id'         => $this->product->id,
            'batch_number'       => 'LOT-2026-A',
            'expiration_date'    => now()->addDays(10)->toDateString(),
            'initial_quantity'   => 5,
            'available_quantity' => 5,
            'status'             => 'active',
        ]);

        // Batch 2: Expiring in 40 days (10 units)
        ProductBatch::create([
            'store_id'           => $this->store->id,
            'product_id'         => $this->product->id,
            'batch_number'       => 'LOT-2026-B',
            'expiration_date'    => now()->addDays(40)->toDateString(),
            'initial_quantity'   => 10,
            'available_quantity' => 10,
            'status'             => 'active',
        ]);

        // Batch 3: Expiring in 120 days (20 units)
        ProductBatch::create([
            'store_id'           => $this->store->id,
            'product_id'         => $this->product->id,
            'batch_number'       => 'LOT-2026-C',
            'expiration_date'    => now()->addDays(120)->toDateString(),
            'initial_quantity'   => 20,
            'available_quantity' => 20,
            'status'             => 'active',
        ]);

        // Allocate 12 units (should take all 5 of Lot A and 7 of Lot B)
        $allocations = $this->batchService->allocateFefoBatches($this->product, null, 12);

        $this->assertCount(2, $allocations);
        $this->assertSame('LOT-2026-A', $allocations[0]['batch_number']);
        $this->assertSame(5.0, (float) $allocations[0]['allocated_qty']);

        $this->assertSame('LOT-2026-B', $allocations[1]['batch_number']);
        $this->assertSame(7.0, (float) $allocations[1]['allocated_qty']);
    }

    public function test_validate_batch_for_sale_blocks_expired_batch(): void
    {
        $expiredBatch = ProductBatch::create([
            'store_id'           => $this->store->id,
            'product_id'         => $this->product->id,
            'batch_number'       => 'LOT-EXPIRED',
            'expiration_date'    => now()->subDay()->toDateString(),
            'initial_quantity'   => 10,
            'available_quantity' => 10,
            'status'             => 'active',
        ]);

        $validBatch = ProductBatch::create([
            'store_id'           => $this->store->id,
            'product_id'         => $this->product->id,
            'batch_number'       => 'LOT-FRESH',
            'expiration_date'    => now()->addMonths(6)->toDateString(),
            'initial_quantity'   => 10,
            'available_quantity' => 10,
            'status'             => 'active',
        ]);

        // Expired batch throws ValidationException
        $this->expectException(ValidationException::class);
        $this->batchService->validateBatchForSale($expiredBatch);
    }

    public function test_valid_batch_passes_validation(): void
    {
        $validBatch = ProductBatch::create([
            'store_id'           => $this->store->id,
            'product_id'         => $this->product->id,
            'batch_number'       => 'LOT-FRESH-2',
            'expiration_date'    => now()->addMonths(6)->toDateString(),
            'initial_quantity'   => 10,
            'available_quantity' => 10,
            'status'             => 'active',
        ]);

        // Should not throw any exception
        $this->batchService->validateBatchForSale($validBatch);
        $this->assertTrue(true);
    }

    public function test_expiring_batches_report_and_batch_recall_trace(): void
    {
        // Batch expiring in 15 days
        ProductBatch::create([
            'store_id'           => $this->store->id,
            'product_id'         => $this->product->id,
            'batch_number'       => 'RECALL-LOT-01',
            'manufacture_date'   => now()->subMonths(6)->toDateString(),
            'expiration_date'    => now()->addDays(15)->toDateString(),
            'initial_quantity'   => 100,
            'available_quantity' => 45,
            'status'             => 'active',
        ]);

        // Batch expiring in 90 days
        ProductBatch::create([
            'store_id'           => $this->store->id,
            'product_id'         => $this->product->id,
            'batch_number'       => 'LATER-LOT-02',
            'expiration_date'    => now()->addDays(90)->toDateString(),
            'initial_quantity'   => 200,
            'available_quantity' => 180,
            'status'             => 'active',
        ]);

        // 1. Expiring soon query (30 days threshold)
        $expiringSoon = $this->batchService->getExpiringBatches($this->store, 30);
        $this->assertCount(1, $expiringSoon);
        $this->assertSame('RECALL-LOT-01', $expiringSoon->first()->batch_number);

        // 2. Recall report trace
        $recallData = $this->batchService->getBatchRecallReport($this->store, 'RECALL-LOT-01');
        $this->assertSame('RECALL-LOT-01', $recallData['batch_number']);
        $this->assertSame(1, $recallData['total_lots_found']);
        $this->assertSame(100.0, $recallData['total_initial_qty']);
        $this->assertSame(45.0, $recallData['total_available_qty']);
    }
}
