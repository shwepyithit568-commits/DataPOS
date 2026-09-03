<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\InventoryMovement;
use App\POS\Models\InventoryReconciliation;
use App\POS\Services\InventoryService;
use App\POS\Services\OpeningStockService;
use App\POS\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private ReconciliationService $reconciliation;

    private OpeningStockService $openingStock;

    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reconciliation = app(ReconciliationService::class);
        $this->openingStock = app(OpeningStockService::class);
        $this->inventory = app(InventoryService::class);
    }

    private function makeStore(string $slug = 'shop-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function user(Store $store, string $role, string $name = 'User'): User
    {
        $user = User::create([
            'name' => $name . ' ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    private function staff(Store $store): User
    {
        return $this->user($store, 'staff', 'Cashier');
    }

    private function manager(Store $store): User
    {
        return $this->user($store, 'store_manager', 'Manager');
    }

    private function makeProduct(Store $store, array $overrides = []): Product
    {
        $name = $overrides['name'] ?? 'Phone ' . Str::random(3);

        return Product::create(array_merge([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(3)),
            'retail_price' => 10000,
            'wholesale_price' => 9000,
        ], $overrides));
    }

    /** Approve an opening-stock request for a product (the "imported" opening stock). */
    private function approveOpening(Store $store, Product $product, string $qty, string $cost = '8000'): void
    {
        $request = $this->openingStock->create(
            $store,
            [['product_id' => $product->id, 'quantity' => $qty, 'unit_cost' => $cost]],
            'Pilot cutover',
            $this->staff($store),
        );
        $this->openingStock->approve($store, $request, $this->manager($store));
    }

    /** Seed stock directly into the ledger WITHOUT an approved OSR (legacy/missing import). */
    private function seedStock(Store $store, Product $product, string $qty, string $cost = '8000'): InventoryMovement
    {
        return $this->inventory->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => $qty,
            'unit_cost' => $cost,
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed:' . Str::uuid(),
            'occurred_at' => now(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Report                                                             */
    /* ------------------------------------------------------------------ */

    public function test_report_is_clean_when_approved_opening_matches_ledger(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);
        $this->approveOpening($store, $product, '10');

        $report = $this->reconciliation->report($store);

        $this->assertSame(1, $report['products']);
        $this->assertSame(0, $report['diff_products']);
        $this->assertSame('0.000', $report['total_diff']);
        $this->assertTrue($report['clean']);

        $row = $report['rows'][0];
        $this->assertSame($product->id, $row['product_id']);
        $this->assertSame('10.000', $row['imported']);
        $this->assertSame('10.000', $row['recorded']);
        $this->assertSame('0.000', $row['diff']);
        $this->assertSame('10.000', $row['on_hand']);
    }

    public function test_report_flags_under_and_over_recorded_products(): void
    {
        $store = $this->makeStore();

        // A: approved OSR matches the ledger → no diff.
        $productA = $this->makeProduct($store, ['name' => 'A Phone']);
        $this->approveOpening($store, $productA, '10');

        // B: recorded in the ledger but never imported (missing OSR) → diff −5.
        $productB = $this->makeProduct($store, ['name' => 'B Phone']);
        $this->seedStock($store, $productB, '5');

        // C: approved OSR but the opening movement was reversed → diff +8.
        $productC = $this->makeProduct($store, ['name' => 'C Phone']);
        $this->approveOpening($store, $productC, '8');
        $openingMovement = InventoryMovement::query()
            ->where('store_id', $store->id)
            ->where('product_id', $productC->id)
            ->where('movement_type', 'opening_balance')
            ->firstOrFail();
        $this->inventory->reverseMovement($openingMovement);

        $report = $this->reconciliation->report($store);

        $this->assertSame(3, $report['products']);
        $this->assertSame(2, $report['diff_products']);
        $this->assertSame('13.000', $report['total_diff']);
        $this->assertFalse($report['clean']);

        $byProduct = collect($report['rows'])->keyBy('product_id');
        $this->assertSame('0.000', $byProduct[$productA->id]['diff']);
        $this->assertSame('-5.000', $byProduct[$productB->id]['diff']);
        $this->assertSame('8.000', $byProduct[$productC->id]['diff']);
        $this->assertSame('5.000', $byProduct[$productB->id]['on_hand']);
    }

    /* ------------------------------------------------------------------ */
    /*  Approve                                                            */
    /* ------------------------------------------------------------------ */

    public function test_approve_posts_corrections_and_converges_the_report(): void
    {
        $store = $this->makeStore();
        $manager = $this->manager($store);

        $productA = $this->makeProduct($store, ['name' => 'A Phone']);
        $this->approveOpening($store, $productA, '10');

        $productB = $this->makeProduct($store, ['name' => 'B Phone']);
        $this->seedStock($store, $productB, '5');

        $productC = $this->makeProduct($store, ['name' => 'C Phone']);
        $this->approveOpening($store, $productC, '8');
        $openingMovement = InventoryMovement::query()
            ->where('store_id', $store->id)
            ->where('product_id', $productC->id)
            ->where('movement_type', 'opening_balance')
            ->firstOrFail();
        $this->inventory->reverseMovement($openingMovement);

        $record = $this->reconciliation->approve($store, $manager, 'Cutover verified');

        $this->assertStringStartsWith('REC-', $record->reconciliation_number);
        $this->assertSame(2, $record->diff_count);
        $this->assertSame('13.000', (string) $record->total_diff);
        $this->assertSame('Cutover verified', $record->review_notes);
        $this->assertSame($manager->id, $record->reviewed_by);

        // B: over-recorded → adjustment_out −5. C: under-recorded → adjustment_in +8.
        $this->assertDatabaseHas('inventory_movements', [
            'store_id' => $store->id,
            'source_type' => 'inventory_reconciliation',
            'source_id' => $record->id,
            'movement_type' => 'adjustment_out',
            'quantity_delta' => '-5',
            'product_id' => $productB->id,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'store_id' => $store->id,
            'source_type' => 'inventory_reconciliation',
            'source_id' => $record->id,
            'movement_type' => 'adjustment_in',
            'quantity_delta' => '8',
            'product_id' => $productC->id,
        ]);

        // On-hand reflects the corrections.
        $this->assertSame('10.000', $this->inventory->totalOnHand($store->id, $productA->id));
        $this->assertSame('0.000', $this->inventory->totalOnHand($store->id, $productB->id));
        $this->assertSame('8.000', $this->inventory->totalOnHand($store->id, $productC->id));

        // Snapshot lines are recorded per corrected product.
        $this->assertSame(2, $record->items()->count());
        $this->assertDatabaseHas('inventory_reconciliation_items', [
            'inventory_reconciliation_id' => $record->id,
            'product_id' => $productB->id,
            'imported_quantity' => '0',
            'recorded_quantity' => '5',
            'difference' => '-5',
            'correction' => '5',
            'movement_type' => 'adjustment_out',
        ]);

        // The report now converges: reconciliation corrections count as recorded.
        $report = $this->reconciliation->report($store);
        $this->assertTrue($report['clean']);
        $this->assertSame(0, $report['diff_products']);

        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'inventory_reconciliation_approved',
            'entity_id' => $record->id,
        ]);
        $this->assertSame(1, $this->reconciliation->recent($store)->count());
    }

    public function test_second_approval_posts_no_further_corrections(): void
    {
        $store = $this->makeStore();
        $manager = $this->manager($store);

        $product = $this->makeProduct($store);
        $this->approveOpening($store, $product, '6');

        $this->reconciliation->approve($store, $manager);
        $second = $this->reconciliation->approve($store, $manager);

        $this->assertSame(0, $second->diff_count);
        $this->assertSame('0.000', (string) $second->total_diff);
        $this->assertSame('6.000', $this->inventory->totalOnHand($store->id, $product->id));
        // Only corrections from the FIRST approval exist — the second posts none.
        $this->assertSame(0, InventoryMovement::where('source_type', 'inventory_reconciliation')->count());
    }

    public function test_insufficient_stock_blocks_out_correction_without_trace(): void
    {
        $store = $this->makeStore();
        $manager = $this->manager($store);

        // Recorded 5 but never imported (diff −5) — and the 5 were already sold.
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');
        $this->inventory->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'pos_sale',
            'quantity_delta' => '-5',
            'source_type' => 'pos_sale',
            'client_transaction_id' => 'sale:' . Str::uuid(),
            'occurred_at' => now(),
        ]);
        $this->assertSame('0.000', $this->inventory->totalOnHand($store->id, $product->id));

        try {
            $this->reconciliation->approve($store, $manager);
            $this->fail('Expected insufficient-stock exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('Insufficient stock', $e->getMessage());
        }

        // Nothing persisted: no record, no reconciliation movements.
        $this->assertSame(0, InventoryReconciliation::count());
        $this->assertSame(0, InventoryMovement::where('source_type', 'inventory_reconciliation')->count());
    }

    /* ------------------------------------------------------------------ */
    /*  HTTP + authorization                                               */
    /* ------------------------------------------------------------------ */

    public function test_page_renders_for_staff_but_only_manager_can_approve(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');

        $this->actingAs($cashier)
            ->get("/store/{$store->slug}/pos/reconciliation")
            ->assertOk()
            ->assertSee(__('messages.reconciliation_subtitle'));

        // Staff cannot approve — manager-only route middleware.
        $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/reconciliation/approve")
            ->assertForbidden();

        $this->assertSame(0, InventoryReconciliation::count());

        // Manager approves via HTTP.
        $this->actingAs($manager)
            ->post("/store/{$store->slug}/pos/reconciliation/approve", ['review_notes' => 'OK'])
            ->assertRedirect();

        $this->assertSame(1, InventoryReconciliation::count());
        $record = InventoryReconciliation::firstOrFail();
        $this->assertSame('OK', $record->review_notes);
        $this->assertSame('0.000', $this->inventory->totalOnHand($store->id, $product->id)); // 5 seeded − 5 correction
    }

    public function test_outsider_and_cross_store_manager_are_blocked(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $outsider = User::create([
            'name' => 'Outsider',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $managerB = $this->manager($storeB);
        $productA = $this->makeProduct($storeA);
        $this->seedStock($storeA, $productA, '3');

        $this->actingAs($outsider)
            ->get("/store/{$storeA->slug}/pos/reconciliation")
            ->assertForbidden();

        // A manager of another store cannot approve this store's reconciliation.
        $this->actingAs($managerB)
            ->post("/store/{$storeA->slug}/pos/reconciliation/approve")
            ->assertForbidden();

        $this->assertSame(0, InventoryReconciliation::count());
        $this->assertSame('3.000', $this->inventory->totalOnHand($storeA->id, $productA->id));
    }

    public function test_export_csv_and_xlsx(): void
    {
        $store = $this->makeStore();
        $manager = $this->manager($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');

        // 1. CSV export
        $csvResponse = $this->actingAs($manager)
            ->get("/store/{$store->slug}/pos/reconciliation/export?format=csv");

        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csvResponse->headers->get('content-type'));

        // 2. XLSX export
        $xlsxResponse = $this->actingAs($manager)
            ->get("/store/{$store->slug}/pos/reconciliation/export");

        $xlsxResponse->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $xlsxResponse->headers->get('content-type'));
    }
}
