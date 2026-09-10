<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CashierShift;
use App\POS\Models\DailyClosing;
use App\POS\Models\PosSale;
use App\POS\Services\CashierShiftService;
use App\POS\Services\DailyClosingService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DailyClosingTest extends TestCase
{
    use RefreshDatabase;

    private DailyClosingService $closings;

    private PosSaleService $sales;

    private InventoryService $inventory;

    private CashierShiftService $shifts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->closings = app(DailyClosingService::class);
        $this->sales = app(PosSaleService::class);
        $this->inventory = app(InventoryService::class);
        $this->shifts = app(CashierShiftService::class);
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

    private function seedStock(Store $store, Product $product, string $qty = '10'): void
    {
        $this->inventory->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => $qty,
            'unit_cost' => 8000,
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed:' . Str::uuid(),
            'occurred_at' => now(),
        ]);
    }

    /** Post a sale for a fixed amount against a chosen payment method. */
    private function postSale(Store $store, User $cashier, array $payments, string $price = '10000', ?User $customer = null): PosSale
    {
        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-' . Str::random(2), 'opening_cash' => 50000], $cashier);
        $product = $this->makeProduct($store, ['retail_price' => $price]);
        $this->seedStock($store, $product, '10');
        $this->sales->addToCart($store, $product->id, null, '1');

        return $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            $payments,
            $cashier,
            $shift,
            null,
            $customer?->id,
        );
    }

    private function retailCustomer(Store $store): User
    {
        return $this->user($store, 'retail_customer', 'Customer');
    }

    /* ------------------------------------------------------------------ */
    /*  Expected totals                                                    */
    /* ------------------------------------------------------------------ */

    public function test_expected_cash_matches_shift_drawer_math(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $shiftA = $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);
        $shiftA->update(['cash_sales' => 30000, 'cash_refunds' => 5000]);
        $shiftA->update(['cash_in' => 10000, 'cash_out' => 2000]);

        // expected = 50000 + 30000 − 5000 + 10000 − 2000 = 83000
        $totals = $this->closings->expectedTotals($store, Carbon::today());

        $this->assertSame('83000.00', $totals['expected']['cash']);
        $this->assertSame('50000.00', $totals['opening_amount']);
    }

    public function test_expected_e_methods_come_from_posted_sales(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $this->postSale($store, $cashier, [['method' => 'kpay', 'amount' => '8000']], '8000');
        $this->postSale($store, $cashier, [['method' => 'cash', 'amount' => '12000']], '12000');

        $totals = $this->closings->expectedTotals($store, Carbon::today());

        $this->assertSame('8000.00', $totals['expected']['kpay']);
        $this->assertSame('0.00', $totals['expected']['wavepay']);
        // two shifts × opening 50000 + cash_sales 12000 (kpay sale doesn't touch the drawer)
        $this->assertSame('112000.00', $totals['expected']['cash']);
    }

    public function test_expected_credit_reduces_by_credit_refunds(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $customer = $this->retailCustomer($store);

        $sale = $this->postSale($store, $cashier, [['method' => 'credit', 'amount' => '15000']], '15000', $customer);

        $totals = $this->closings->expectedTotals($store, Carbon::today());
        $this->assertSame('15000.00', $totals['expected']['credit']);

        // A credit refund reduces the receivable expectation.
        $return = \App\POS\Models\PosReturn::query()->create([
            'store_id' => $store->id,
            'pos_sale_id' => $sale->id,
            'return_number' => 'RET-TEST-1',
            'status' => 'posted',
            'total' => 5000,
            'posted_at' => now(),
        ]);
        \App\POS\Models\PosReturnPayment::query()->create([
            'pos_return_id' => $return->id,
            'method' => 'credit',
            'amount' => 5000,
        ]);

        $totals = $this->closings->expectedTotals($store, Carbon::today());
        $this->assertSame('10000.00', $totals['expected']['credit']);
    }

    /* ------------------------------------------------------------------ */
    /*  Create                                                             */
    /* ------------------------------------------------------------------ */

    public function test_create_pending_closing_snapshots_totals(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $shift = $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);
        $shift->update(['cash_sales' => 12000]);
        $this->postSale($store, $cashier, [['method' => 'kpay', 'amount' => '8000']], '8000');

        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 112000, 'kpay' => 8000, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashier,
        );

        $this->assertTrue($closing->isPending());
        $this->assertSame('112000.00', $closing->expected_totals['cash']);
        $this->assertSame('8000.00', $closing->expected_totals['kpay']);
        $this->assertSame('0.00', (string) $closing->total_difference);
        $this->assertSame('112000.00', $closing->counted_totals['cash']);
        $this->assertSame('0.00', $closing->differences['cash']);
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'daily_closing_created',
            'entity_type' => 'daily_closing',
            'entity_id' => $closing->id,
        ]);
    }

    public function test_create_requires_explanation_when_difference_non_zero(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('explanation is required');

        $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 48000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashier,
        );
    }

    public function test_create_blocks_duplicate_and_future_dates(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        $counted = ['cash' => 50000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0];

        $this->closings->create($store, Carbon::today(), $counted, null, $cashier);

        try {
            $this->closings->create($store, Carbon::today(), $counted, null, $cashier);
            $this->fail('Expected duplicate exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('already exists', $e->getMessage());
        }

        try {
            $this->closings->create($store, Carbon::tomorrow(), $counted, null, $cashier);
            $this->fail('Expected future-date exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('future', $e->getMessage());
        }

        $this->assertSame(1, DailyClosing::count());
    }

    /* ------------------------------------------------------------------ */
    /*  Approval                                                           */
    /* ------------------------------------------------------------------ */

    public function test_approve_by_manager_sets_approver_and_audits(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 50000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashier,
        );

        $approved = $this->closings->approve($store, $closing, $manager);

        $this->assertTrue($approved->isApproved());
        $this->assertSame($manager->id, $approved->approver_id);
        $this->assertNotNull($approved->approved_at);
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'daily_closing_approved',
            'entity_id' => $closing->id,
        ]);
    }

    public function test_approve_blocks_double_approval_and_cross_store(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $cashier = $this->staff($storeA);
        $managerB = $this->manager($storeB);
        $this->shifts->openShift($storeA, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        $closing = $this->closings->create(
            $storeA,
            Carbon::today(),
            ['cash' => 50000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashier,
        );

        // Cross-store approval is refused at the service level.
        try {
            $this->closings->approve($storeB, $closing, $managerB);
            $this->fail('Expected cross-store exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('does not belong', $e->getMessage());
        }

        $managerA = $this->manager($storeA);
        $this->closings->approve($storeA, $closing, $managerA);

        try {
            $this->closings->approve($storeA, $closing->fresh(), $managerA);
            $this->fail('Expected double-approval exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('already approved', $e->getMessage());
        }
    }

    public function test_approve_blocks_on_pending_offline_transactions(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 50000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashier,
        );
        $closing->update(['pending_offline_transaction_count' => 2]);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('offline');

        $this->closings->approve($store, $closing->fresh(), $manager);
    }

    public function test_approve_blocks_difference_without_explanation(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 51000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            'Petty cash missing 1000',
            $cashier,
        );
        // Simulate an explanation being cleared later (still blocked at approve).
        $closing->update(['explanation' => null]);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('explanation is required');

        $this->closings->approve($store, $closing->fresh(), $manager);
    }

    /* ------------------------------------------------------------------ */
    /*  HTTP + authorization                                               */
    /* ------------------------------------------------------------------ */

    public function test_closing_page_renders_for_staff(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        $this->actingAs($cashier)
            ->get("/store/{$store->slug}/pos/closing")
            ->assertOk()
            ->assertSee(__('messages.closing_title'))
            ->assertSee(__('messages.closing_create'));
    }

    public function test_staff_can_create_but_not_approve(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/closing", [
                'business_date' => today()->toDateString(),
                'counted' => ['cash' => 50000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            ])
            ->assertRedirect();

        $closing = DailyClosing::firstOrFail();
        $this->assertTrue($closing->isPending());

        // Staff cannot approve — manager-only route middleware.
        $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/closing/{$closing->id}/approve")
            ->assertForbidden();

        $this->assertTrue($closing->fresh()->isPending());
    }

    public function test_manager_approves_via_http(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 50000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashier,
        );

        $this->actingAs($manager)
            ->post("/store/{$store->slug}/pos/closing/{$closing->id}/approve")
            ->assertRedirect();

        $this->assertTrue($closing->fresh()->isApproved());
        $this->assertSame($manager->id, $closing->fresh()->approver_id);
    }

    public function test_non_staff_cannot_view_closing(): void
    {
        $store = $this->makeStore();
        $outsider = User::create([
            'name' => 'Outsider',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->actingAs($outsider)
            ->get("/store/{$store->slug}/pos/closing")
            ->assertForbidden();
    }

    public function test_cross_store_closing_is_blocked(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $cashierA = $this->staff($storeA);
        $managerB = $this->manager($storeB);

        $closing = $this->closings->create(
            $storeA,
            Carbon::today(),
            ['cash' => 0, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashierA,
        );

        // Store B's manager cannot approve store A's closing (route context mismatch → 404 via EnsureStoreAccess).
        $this->actingAs($managerB)
            ->post("/store/{$storeB->slug}/pos/closing/{$closing->id}/approve")
            ->assertNotFound();
    }

    /* ------------------------------------------------------------------ */
    /*  Phase 2: Comprehensive X-Report & Z-Report Production Tests       */
    /* ------------------------------------------------------------------ */

    public function test_x_report_get_request_is_non_mutating_repeatable_and_audited(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $shift = $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);
        $this->postSale($store, $cashier, [['method' => 'kpay', 'amount' => '8000']], '8000');

        // Baseline business table counts
        $initialClosingsCount = DailyClosing::count();
        $initialShiftsCount = CashierShift::count();
        $initialSalesCount = DB::table('pos_sales')->count();
        $initialPaymentsCount = DB::table('pos_payments')->count();
        $initialReturnsCount = DB::table('pos_returns')->count();
        $initialStockMovementsCount = DB::table('inventory_movements')->count();
        $initialAuditLogsCount = DB::table('audit_logs')->count();

        // 1. Request X-Report repeatedly (GET requests)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->actingAs($cashier)->get("/store/{$store->slug}/pos/closing/x-report");
            $response->assertOk();
            $response->assertSee('X-Report');
            $response->assertSee(__('messages.reading_only'));
        }

        // 2. Assert STRICT business-data non-mutation (core operational tables unaffected)
        $this->assertSame($initialClosingsCount, DailyClosing::count(), 'X-Report must not create a daily_closings row.');
        $this->assertSame($initialShiftsCount, CashierShift::count(), 'X-Report must not alter cashier shifts count.');
        $this->assertSame($initialSalesCount, DB::table('pos_sales')->count(), 'X-Report must not alter sales count.');
        $this->assertSame($initialPaymentsCount, DB::table('pos_payments')->count(), 'X-Report must not alter payments count.');
        $this->assertSame($initialReturnsCount, DB::table('pos_returns')->count(), 'X-Report must not alter returns count.');
        $this->assertSame($initialStockMovementsCount, DB::table('inventory_movements')->count(), 'X-Report must not alter inventory ledger.');

        $this->assertTrue($shift->fresh()->isOpen(), 'X-Report must not close an open cashier shift.');
        $this->assertFalse(app(\App\POS\Services\PeriodLockService::class)->isDateLocked($store, Carbon::today()), 'X-Report must not lock the business date.');

        // 3. Assert Security Audit Log behavior:
        // Audit log records an intentional read event ('x_report_viewed') without altering any business transaction tables.
        $this->assertSame($initialAuditLogsCount + 3, DB::table('audit_logs')->count(), 'Each X-Report reading produces a distinct security audit record.');
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'x_report_viewed',
            'entity_type' => 'x_report',
        ]);
    }

    public function test_unauthorized_cross_store_and_future_date_x_report_are_blocked(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $cashierA = $this->staff($storeA);
        $outsider = User::create([
            'name' => 'Outsider',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        // Unauthorized user 403
        $this->actingAs($outsider)
            ->get("/store/{$storeA->slug}/pos/closing/x-report")
            ->assertForbidden();

        // Cross-store user 403/404
        $this->actingAs($cashierA)
            ->get("/store/{$storeB->slug}/pos/closing/x-report")
            ->assertForbidden();

        // Future date 422
        $tomorrow = Carbon::tomorrow()->toDateString();
        $this->actingAs($cashierA)
            ->get("/store/{$storeA->slug}/pos/closing/x-report?date={$tomorrow}")
            ->assertStatus(422);
    }

    public function test_electronic_refund_reduces_matching_electronic_method(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $customer = $this->retailCustomer($store);

        // Sale with WavePay 20000
        $sale = $this->postSale($store, $cashier, [['method' => 'wavepay', 'amount' => '20000']], '20000', $customer);

        $totalsBefore = $this->closings->expectedTotals($store, Carbon::today());
        $this->assertSame('20000.00', $totalsBefore['expected']['wavepay']);

        // Return with WavePay 5000 refund
        $return = \App\POS\Models\PosReturn::query()->create([
            'store_id' => $store->id,
            'pos_sale_id' => $sale->id,
            'return_number' => 'RET-WAVE-1',
            'status' => 'posted',
            'total' => 5000,
            'posted_at' => now(),
        ]);
        \App\POS\Models\PosReturnPayment::query()->create([
            'pos_return_id' => $return->id,
            'method' => 'wavepay',
            'amount' => 5000,
        ]);

        $totalsAfter = $this->closings->expectedTotals($store, Carbon::today());
        $this->assertSame('15000.00', $totalsAfter['expected']['wavepay'], 'WavePay refund must reduce matching expected total.');
    }

    public function test_split_payment_not_double_counted_and_credit_does_not_affect_drawer_cash(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $customer = $this->retailCustomer($store);

        // Split sale: Cash 10000, KPay 15000, Credit 25000 (Total 50000)
        $shift = $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);
        $product = $this->makeProduct($store, ['retail_price' => '50000']);
        $this->seedStock($store, $product, '5');
        $this->sales->addToCart($store, $product->id, null, '1');

        $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [
                ['method' => 'cash', 'amount' => '10000'],
                ['method' => 'kpay', 'amount' => '15000'],
                ['method' => 'credit', 'amount' => '25000'],
            ],
            $cashier,
            $shift,
            null,
            $customer->id,
        );

        $totals = $this->closings->expectedTotals($store, Carbon::today());

        $this->assertSame('15000.00', $totals['expected']['kpay']);
        $this->assertSame('25000.00', $totals['expected']['credit']);
        // Drawer cash = opening 50000 + cash sale 10000 = 60000 (KPay and Credit do not increase drawer cash)
        $this->assertSame('60000.00', $totals['expected']['cash']);
        $this->assertSame('50000.00', $totals['summary']['net_sales']);
    }

    public function test_approved_closing_is_immutable_and_direct_reopen_disabled(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('pos.closing.reopen'));

        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 50000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashier,
        );

        $this->closings->approve($store, $closing, $manager);
        $this->assertTrue($closing->fresh()->isApproved());

        // 1. Direct reopen route is absent -> POST returns 404 for both cashier and manager
        $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/closing/{$closing->id}/reopen", ['reason' => 'Cashier attempt'])
            ->assertNotFound();

        $this->actingAs($manager)
            ->post("/store/{$store->slug}/pos/closing/{$closing->id}/reopen", ['reason' => 'Manager attempt'])
            ->assertNotFound();

        // 2. Business date is period-locked
        $this->assertTrue(app(\App\POS\Services\PeriodLockService::class)->isDateLocked($store, Carbon::today()));

        // 3. Resubmitting on same date is rejected
        $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/closing", [
                'business_date' => today()->toDateString(),
                'counted' => ['cash' => 50000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            ])
            ->assertRedirect(); // Fails domain create and redirects with error flash

        $this->assertSame(1, DailyClosing::where('store_id', $store->id)->count());
    }

    public function test_print_view_renders_all_six_layouts_and_markers(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 50000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashier,
        );
        $this->closings->approve($store, $closing, $manager);

        $layouts = [
            '58mm' => 'size: 58mm auto',
            '80mm' => 'size: 80mm auto',
            'a5_portrait' => 'size: A5 portrait',
            'a5_landscape' => 'size: A5 landscape',
            'a4_portrait' => 'size: A4 portrait',
            'a4_landscape' => 'size: A4 landscape',
        ];

        // 1. Test Z-Report print across all 6 layouts
        foreach ($layouts as $layoutKey => $expectedPageCss) {
            $resp = $this->actingAs($cashier)->get("/store/{$store->slug}/pos/closing/{$closing->id}/print?layout={$layoutKey}");
            $resp->assertOk();
            $resp->assertSee($expectedPageCss, false);
            $resp->assertSee('Z-REPORT', false);
            $resp->assertSee('FINAL/APPROVED DAILY CLOSING', false);
            $resp->assertSee('50,000'); // Opening float / cash rendered
        }

        // 2. Test X-Report print across all 6 layouts
        foreach ($layouts as $layoutKey => $expectedPageCss) {
            $resp = $this->actingAs($cashier)->get("/store/{$store->slug}/pos/closing/print?type=x&layout={$layoutKey}");
            $resp->assertOk();
            $resp->assertSee($expectedPageCss, false);
            $resp->assertSee('X-REPORT', false);
            $resp->assertSee('READING ONLY', false);
            $resp->assertSee('id="btnPrint"', false);
            $resp->assertSee('id="btnSavePdf"', false);
            $resp->assertSee('html2pdf.bundle.min.js', false);
            $resp->assertSee('id="reportDocument"', false);
        }
    }

    public function test_reports_daily_closing_alias_routes_and_navigation(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        // 1. Alias routes resolve with 200
        $this->actingAs($cashier)
            ->get("/store/{$store->slug}/pos/reports/daily-closing")
            ->assertOk()
            ->assertSee(__('messages.closing_title'));

        $this->actingAs($cashier)
            ->get("/store/{$store->slug}/pos/reports/daily-closing/print?type=x&layout=80mm")
            ->assertOk()
            ->assertSee('X-REPORT', false);

        // 2. Navigation tree contains daily closing under POS group without duplication in Reports group
        $navService = app(\App\Services\AdminNavigationService::class);
        $tree = $navService->getFilteredNavigationTree($cashier, $store);

        // POS group contains operational pos_closing
        $posGroup = collect($tree)->firstWhere('key', 'pos');
        $this->assertNotNull($posGroup, 'POS navigation group must exist.');
        $closingChild = collect($posGroup['children'])->firstWhere('key', 'pos_closing');
        $this->assertNotNull($closingChild, 'Daily Closing must be present in POS navigation.');
        $this->assertSame(route('pos.closing.index', ['store_slug' => $store->slug]), $closingChild['url']);

        // Reports group does not contain duplicate reports_daily_closing
        $reportsGroup = collect($tree)->firstWhere('key', 'reports');
        $this->assertNotNull($reportsGroup, 'Reports navigation group must exist.');
        $this->assertNull(collect($reportsGroup['children'])->firstWhere('key', 'reports_daily_closing'), 'Daily Closing must not be duplicated in Reports group.');
    }

    public function test_trilingual_translation_keys_parity_for_phase_two(): void
    {
        $requiredKeys = [
            'closing_title',
            'closing_expected',
            'closing_counted',
            'closing_difference',
            'x_report',
            'z_report',
            'x_report_reading',
            'z_report_closing',
            'reading_only',
            'final_closing',
            'expected',
            'counted',
            'difference',
            'over',
            'short',
            'submit',
            'approve',
            'pending',
            'approved',
            'print_58mm',
            'print_80mm',
            'print_a5',
            'print_a5_portrait',
            'print_a5_landscape',
            'print_a4',
            'print_a4_portrait',
            'print_a4_landscape',
            'paper_size',
            'orientation',
            'portrait',
            'landscape',
            'reprint',
            'explanation_required',
            'cannot_modify_approved_closing',
            'pending_offline_warning',
            'gross_sales',
            'net_sales',
            'discounts',
            'tax_collected',
            'returns_refunds',
            'opening_float',
            'expected_cash',
            'counted_cash',
            'cashier_signature',
            'manager_signature',
            'invalid_date_format',
            'future_date_not_allowed',
            'closing_not_found',
            'financial_truth_tampering_rejected',
            'invalid_counted_payload',
            'nested_counted_key_rejected',
            'unknown_counted_payment_methods',
            'legacy_closing_notice',
        ];

        foreach (['my', 'en', 'zh_CN'] as $locale) {
            $trans = include resource_path("../lang/{$locale}/messages.php");
            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey($key, $trans, "Locale '{$locale}' is missing required key '{$key}'.");
            }
        }
    }

    public function test_store_timezone_business_date_boundary(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $shift = $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        // Sale posted at 23:59:00 of today (Asia/Yangon)
        $todayLate = Carbon::today('Asia/Yangon')->setTime(23, 59, 0);
        $product = $this->makeProduct($store, ['retail_price' => '10000']);
        $this->seedStock($store, $product, '10');
        $this->sales->addToCart($store, $product->id, null, '1');

        Carbon::setTestNow($todayLate);

        $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'kpay', 'amount' => '10000']],
            $cashier,
            $shift,
        );

        $totalsToday = $this->closings->expectedTotals($store, Carbon::today('Asia/Yangon'));
        $this->assertSame('10000.00', $totalsToday['expected']['kpay']);

        // Expected totals for tomorrow should NOT include today's late night sale
        $totalsTomorrow = $this->closings->expectedTotals($store, Carbon::tomorrow('Asia/Yangon'));
        $this->assertSame('0.00', $totalsTomorrow['expected']['kpay']);

        Carbon::setTestNow(); // Reset test now
    }

    public function test_period_lock_blocks_sales_and_returns_after_approval(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $customer = $this->retailCustomer($store);

        $sale = $this->postSale($store, $cashier, [['method' => 'cash', 'amount' => '10000']], '10000', $customer);

        $expected = $this->closings->expectedTotals($store, Carbon::today());
        $counted = [
            'cash' => $expected['expected']['cash'],
            'kpay' => '0',
            'wavepay' => '0',
            'cb_pay' => '0',
            'mmqr' => '0',
        ];

        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            $counted,
            null,
            $cashier,
        );
        $this->closings->approve($store, $closing, $manager);

        // 1. PeriodLockService confirms date is locked
        $periodLock = app(\App\POS\Services\PeriodLockService::class);
        $this->assertTrue($periodLock->isDateLocked($store, Carbon::today()));

        // 2. New sale on locked date is strictly blocked
        $this->expectException(\App\POS\Exceptions\PeriodLockedException::class);
        $periodLock->assertDateNotLocked($store, Carbon::today(), 'POS sales cannot be posted on a locked business date.');
    }

    public function test_concurrent_approval_blocks_race_condition(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 50000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashier,
        );

        // First approval succeeds
        $firstApproved = $this->closings->approve($store, $closing, $manager);
        $this->assertTrue($firstApproved->isApproved());

        // Second concurrent approval attempt fails cleanly with exception
        $this->expectException(InventoryException::class);
        $this->closings->approve($store, $closing, $manager);
    }

    public function test_unknown_counted_keys_and_negative_amounts_are_rejected(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        // 1. Unknown counted key via HTTP is rejected
        $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/closing", [
                'business_date' => today()->toDateString(),
                'counted' => [
                    'cash' => '50000',
                    'bitcoin' => '100', // Unknown key
                ],
            ])
            ->assertSessionHasErrors('counted');

        // 2. Negative amount via HTTP is rejected
        $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/closing", [
                'business_date' => today()->toDateString(),
                'counted' => [
                    'cash' => '-500',
                ],
            ])
            ->assertSessionHasErrors('counted.cash');

        // 3. Unknown counted key directly via Domain Service throws InventoryException
        $this->expectException(InventoryException::class);
        $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => '50000', 'crypto_currency' => '10'],
            null,
            $cashier,
        );
    }

    public function test_cross_store_print_and_closing_access_are_blocked(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $cashierA = $this->staff($storeA);
        $cashierB = $this->staff($storeB);

        $this->shifts->openShift($storeA, ['register_name' => 'R1', 'opening_cash' => 50000], $cashierA);
        $closingA = $this->closings->create(
            $storeA,
            Carbon::today(),
            ['cash' => 50000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashierA,
        );

        // Cashier B cannot access Store A's print URL (403 forbidden due to store access policy)
        $this->actingAs($cashierB)
            ->get("/store/{$storeA->slug}/pos/closing/{$closingA->id}/print?layout=80mm")
            ->assertForbidden();

        // Cashier B cannot access Store A's X-Report print URL
        $this->actingAs($cashierB)
            ->get("/store/{$storeA->slug}/pos/closing/print?type=x&layout=80mm")
            ->assertForbidden();

        // Mismatched store slug and closing ID returns 404
        $this->actingAs($cashierB)
            ->get("/store/{$storeB->slug}/pos/closing/{$closingA->id}/print?layout=80mm")
            ->assertNotFound();
    }

    public function test_z_report_reprint_is_immutable_under_source_data_changes(): void
    {
        $store = $this->makeStore('immutable-print-shop');
        $mgr = $this->manager($store);
        $cashier = $this->staff($store);

        $this->postSale($store, $cashier, [['method' => 'cash', 'amount' => '10000']], '10000');

        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 60000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashier
        );
        $this->closings->approve($store, $closing, $mgr);

        // Verify snapshot was stored
        $closing->refresh();
        $snapshot = $closing->getSummarySnapshot();
        $this->assertSame('10000.00', $snapshot['gross_sales']);
        $this->assertSame('60000.00', $snapshot['expected_cash']);

        // Directly mutate or inject new sales in DB after closing was approved
        DB::table('pos_sales')->insert([
            'store_id' => $store->id,
            'receipt_number' => 'MUTATED-' . time(),
            'subtotal' => 50000,
            'discount' => 0,
            'tax' => 0,
            'total' => 50000,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Reprint Z-Report: must render original snapshotted numbers, NEVER live recalculated numbers
        $response = $this->actingAs($mgr)
            ->get("/store/{$store->slug}/pos/closing/{$closing->id}/print?type=z&layout=80mm");
        $response->assertOk();

        // View data totals summary must match snapshot exactly
        $viewTotals = $response->viewData('totals');
        $this->assertSame('10000.00', $viewTotals['summary']['gross_sales']);
        $this->assertSame('60000.00', $viewTotals['summary']['expected_cash']);
    }

    public function test_z_print_without_persisted_closing_is_rejected_with_404(): void
    {
        $store = $this->makeStore('no-closing-shop');
        $mgr = $this->manager($store);

        // Date has no closing submitted
        $unclosedDate = Carbon::today()->subDay()->toDateString();

        // Attempting to print Z-Report returns 404
        $responseZ = $this->actingAs($mgr)
            ->get("/store/{$store->slug}/pos/closing/print?type=z&date={$unclosedDate}&layout=80mm");
        $responseZ->assertNotFound();

        // X-Report reading on same date succeeds (200)
        $responseX = $this->actingAs($mgr)
            ->get("/store/{$store->slug}/pos/closing/print?type=x&date={$unclosedDate}&layout=80mm");
        $responseX->assertOk();
    }

    public function test_print_markers_distinguish_x_reading_pending_z_and_approved_z(): void
    {
        $store = $this->makeStore('markers-shop');
        $mgr = $this->manager($store);
        $cashier = $this->staff($store);

        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 10000], $cashier);

        // 1. X-Report marker
        $resX = $this->actingAs($mgr)->get("/store/{$store->slug}/pos/closing/print?type=x&layout=80mm");
        $resX->assertOk();
        $resX->assertSee('*** X-REPORT — READING ONLY / စာရင်းကြည့်ရှုရန်သာ ***');

        // Create pending closing
        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 10000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashier
        );

        // 2. Pending Z-Report marker
        $resPending = $this->actingAs($mgr)->get("/store/{$store->slug}/pos/closing/{$closing->id}/print?type=z&layout=80mm");
        $resPending->assertOk();
        $resPending->assertSee('*** PENDING Z-REPORT — SUBMITTED (UNAPPROVED) / ဆိုင်းငံ့ နေ့စဉ်စာရင်းချုပ် ***');
        $resPending->assertDontSee('*** Z-REPORT — FINAL/APPROVED DAILY CLOSING');

        // Approve closing
        $this->closings->approve($store, $closing, $mgr);
        $closing->refresh();

        // 3. Approved Z-Report marker
        $resApproved = $this->actingAs($mgr)->get("/store/{$store->slug}/pos/closing/{$closing->id}/print?type=z&layout=80mm");
        $resApproved->assertOk();
        $resApproved->assertSee('*** Z-REPORT — FINAL/APPROVED DAILY CLOSING / အတည်ပြုပြီး နေ့စဉ်စာရင်းချုပ် ***');
    }

    public function test_client_tampering_with_financial_truth_fields_rejected_with_422(): void
    {
        $store = $this->makeStore('tamper-shop');
        $cashier = $this->staff($store);

        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 10000], $cashier);

        // Client attempts to inject calculated financial truth fields
        $response = $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/closing", [
                'business_date' => today()->toDateString(),
                'counted' => ['cash' => '10000'],
                'expected_totals' => ['cash' => '0'], // Malicious forgery
            ]);

        $response->assertSessionHasErrors('counted');
    }

    public function test_counted_nested_array_and_malformed_payload_rejected_with_422(): void
    {
        $store = $this->makeStore('malformed-shop');
        $cashier = $this->staff($store);

        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 10000], $cashier);

        // 1. Nested array inside counted
        $resNested = $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/closing", [
                'business_date' => today()->toDateString(),
                'counted' => [
                    'cash' => ['nested' => 1000],
                ],
            ]);
        $resNested->assertSessionHasErrors('counted');

        // 2. Scientific notation
        $resScientific = $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/closing", [
                'business_date' => today()->toDateString(),
                'counted' => [
                    'cash' => '1e3',
                ],
            ]);
        $resScientific->assertSessionHasErrors('counted.cash');
    }

    public function test_cashier_without_approval_permission_cannot_approve_closing(): void
    {
        $store = $this->makeStore('cashier-gating-shop');
        $cashier = $this->staff($store);

        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 10000], $cashier);
        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 10000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashier
        );

        // Cashier attempts approval -> 403 Forbidden
        $response = $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/closing/{$closing->id}/approve");
        $response->assertForbidden();
    }

    public function test_user_with_pos_closing_update_only_cannot_approve_closing(): void
    {
        $store = $this->makeStore('no-alias-shop');
        \App\Models\StaffRole::bootstrapDefaultRoles($store);

        // Create custom role with update permission only (NO approve)
        $role = \App\Models\StaffRole::create([
            'store_id' => $store->id,
            'name' => 'Updater Only',
            'slug' => 'updater-only',
            'is_system' => false,
            'is_active' => true,
            'permissions' => ['pos_closing.view', 'pos_closing.create', 'pos_closing.update'],
        ]);

        $updaterUser = User::create([
            'name' => 'Updater Staff',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $updaterUser->stores()->attach($store->id, [
            'role' => 'staff',
            'staff_role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 10000], $updaterUser);
        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 10000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $updaterUser
        );

        // Attempting approval must fail with 403 Forbidden (Strictly NO update -> approve alias!)
        $response = $this->actingAs($updaterUser)
            ->post("/store/{$store->slug}/pos/closing/{$closing->id}/approve");
        $response->assertForbidden();
    }

    public function test_custom_staff_role_with_explicit_pos_closing_approve_can_approve(): void
    {
        $store = $this->makeStore('custom-approve-shop');
        \App\Models\StaffRole::bootstrapDefaultRoles($store);

        // Store Owner explicitly creates a role with pos_closing.approve
        $role = \App\Models\StaffRole::create([
            'store_id' => $store->id,
            'name' => 'Closing Approver Lead',
            'slug' => 'closing-approver-lead',
            'is_system' => false,
            'is_active' => true,
            'permissions' => ['pos_closing.view', 'pos_closing.create', 'pos_closing.approve'],
        ]);

        $leadUser = User::create([
            'name' => 'Lead Staff',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $leadUser->stores()->attach($store->id, [
            'role' => 'staff',
            'staff_role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 10000], $leadUser);
        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 10000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $leadUser
        );

        // Lead user approves successfully (302 Redirect)
        $response = $this->actingAs($leadUser)
            ->post("/store/{$store->slug}/pos/closing/{$closing->id}/approve");
        $response->assertRedirect();
        $closing->refresh();
        $this->assertTrue($closing->isApproved());
    }

    public function test_inactive_membership_cannot_approve_closing_even_with_permissions(): void
    {
        $store = $this->makeStore('inactive-user-shop');
        $mgr = $this->manager($store);
        $cashier = $this->staff($store);

        $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 10000], $cashier);
        $closing = $this->closings->create(
            $store,
            Carbon::today(),
            ['cash' => 10000, 'kpay' => 0, 'wavepay' => 0, 'cb_pay' => 0, 'mmqr' => 0],
            null,
            $cashier
        );

        // Set manager's store membership to inactive
        DB::table('store_user')->where('store_id', $store->id)->where('user_id', $mgr->id)->update([
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($mgr)
            ->post("/store/{$store->slug}/pos/closing/{$closing->id}/approve");
        $response->assertForbidden();
    }

    public function test_overnight_shift_and_opening_float_not_double_counted_across_closings(): void
    {
        $store = $this->makeStore('overnight-shift-shop');
        $cashier = $this->staff($store);

        $day1 = Carbon::parse('2026-09-08 10:00:00');
        $day2 = Carbon::parse('2026-09-09 10:00:00');

        // Shift 1 opened on Day 1 at 22:00:00 with float 50,000
        $shift1 = CashierShift::create([
            'store_id' => $store->id,
            'register_name' => 'Overnight-R1',
            'cashier_id' => $cashier->id,
            'status' => 'open',
            'opened_at' => Carbon::parse('2026-09-08 22:00:00'),
            'opening_cash' => 50000,
            'cash_sales' => 10000,
            'cash_refunds' => 0,
            'cash_in' => 0,
            'cash_out' => 0,
        ]);

        // Day 1 totals: opening_amount = 50,000
        $day1Totals = $this->closings->expectedTotals($store, $day1);
        $this->assertSame('50000.00', $day1Totals['opening_amount']);

        // Shift 2 opened on Day 2 at 08:00:00 with float 30,000
        $shift2 = CashierShift::create([
            'store_id' => $store->id,
            'register_name' => 'Morning-R2',
            'cashier_id' => $cashier->id,
            'status' => 'open',
            'opened_at' => Carbon::parse('2026-09-09 08:00:00'),
            'opening_cash' => 30000,
            'cash_sales' => 5000,
            'cash_refunds' => 0,
            'cash_in' => 0,
            'cash_out' => 0,
        ]);

        // Day 2 totals: Shift 1's opening float MUST NOT be double-counted!
        $day2Totals = $this->closings->expectedTotals($store, $day2);
        $this->assertSame('30000.00', $day2Totals['opening_amount']);
    }
}
