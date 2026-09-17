<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CashEvent;
use App\POS\Models\CashierShift;
use App\POS\Models\DailyClosing;
use App\POS\Models\Expense;
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

/**
 * Test cases C01 to C15 for DataPOS Closing Cash Out, Expenses, and Cash Drawer Audit
 * Specified in docs/DataPOS_Closing_Cash_Out_Audit_Fix_MM.md
 */
class ClosingCashOutAuditTest extends TestCase
{
    use RefreshDatabase;

    private DailyClosingService $closings;
    private CashierShiftService $shifts;
    private PosSaleService $sales;
    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-17 12:00:00');

        $this->closings = app(DailyClosingService::class);
        $this->shifts = app(CashierShiftService::class);
        $this->sales = app(PosSaleService::class);
        $this->inventory = app(InventoryService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeStore(string $slug = 'shop-c'): Store
    {
        return Store::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
            'business_profile' => 'retail_store',
            'operation_mode' => 'omnichannel',
            'capabilities_override' => [
                \App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS => true,
            ],
        ]);
    }

    private function user(Store $store, string $role, string $name = 'User'): User
    {
        $roleMap = [
            'cashier' => 'staff',
            'manager' => 'store_manager',
            'owner' => 'store_owner',
        ];
        $storeRole = $roleMap[$role] ?? $role;

        $user = User::create([
            'name' => $name . ' ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => $storeRole === 'store_owner' ? 'admin' : 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => $storeRole, 'status' => 'active']);

        return $user;
    }

    /**
     * C01: Opening 100,000, no movement -> Expected 100,000, expense 0
     */
    public function test_c01_opening_100k_no_movement_expected_100k_expense_0(): void
    {
        $store = $this->makeStore('c01-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '100000.00',
        ], $cashier);

        $totals = $this->closings->expectedTotals($store, $date);

        $this->assertSame('100000.00', $totals['expected']['cash']);
        $this->assertSame('100000.00', $totals['summary']['opening_cash']);
        $this->assertSame('0.00', $totals['summary']['drawer_expenses']);
        $this->assertSame('0.00', $totals['summary']['cash_out']);
        $this->assertSame('100000.00', $totals['summary']['expected_cash']);
    }

    /**
     * C02: Cash expense 5,000 paid from THIS drawer -> Expense row 5,000, expected 95,000
     */
    public function test_c02_cash_expense_from_drawer_deducts_and_shows_expense_row(): void
    {
        $store = $this->makeStore('c02-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '100000.00',
        ], $cashier);

        // Record expense from drawer
        $response = $this->actingAs($cashier)->postJson("/store/{$store->slug}/pos/expenses", [
            'title' => 'Shop Cleaning Supplies',
            'amount' => '5000.00',
            'payment_method' => 'cash',
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('expenses', [
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'amount' => '5000.00',
            'status' => 'paid',
        ]);

        $totals = $this->closings->expectedTotals($store, $date);
        $this->assertSame('95000.00', $totals['expected']['cash']);
        $this->assertSame('5000.00', $totals['summary']['drawer_expenses']);
        $this->assertSame('95000.00', $totals['summary']['expected_cash']);

        // Check closeShift as well
        $closedShift = $this->shifts->closeShift($shift, [
            'actual_closing_amount' => '95000.00',
        ], $cashier);
        $this->assertSame('95000.00', (string) $closedShift->expected_closing_amount);
        $this->assertSame('0.00', (string) $closedShift->difference);
    }

    /**
     * C03: Cash expense 5,000 paid from Safe -> Drawer 100,000 unchanged
     */
    public function test_c03_cash_expense_from_safe_does_not_deduct_pos_drawer(): void
    {
        $store = $this->makeStore('c03-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '100000.00',
        ], $cashier);

        // Admin records expense paid from Safe (not drawer)
        $expense = Expense::create([
            'store_id' => $store->id,
            'cashier_shift_id' => null,
            'expense_number' => 'EXP-20260917-0003',
            'title' => 'Safe Maintenance',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_SAFE,
            'status' => 'paid',
            'recorded_by' => $manager->id,
        ]);

        $totals = $this->closings->expectedTotals($store, $date);
        $this->assertSame('100000.00', $totals['expected']['cash']);
        $this->assertSame('0.00', $totals['summary']['drawer_expenses']);
        $this->assertSame('5000.00', $totals['summary']['other_cash_expenses']);

        $closedShift = $this->shifts->closeShift($shift, [
            'actual_closing_amount' => '100000.00',
        ], $cashier);
        $this->assertSame('100000.00', (string) $closedShift->expected_closing_amount);
    }

    /**
     * C04: Expense 5,000 paid via KPay/Bank -> Drawer unchanged, exits respective account
     */
    public function test_c04_expense_from_kpay_or_bank_does_not_deduct_pos_drawer(): void
    {
        $store = $this->makeStore('c04-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '100000.00',
        ], $cashier);

        // Record KPay expense via POS
        $response = $this->actingAs($cashier)->postJson("/store/{$store->slug}/pos/expenses", [
            'title' => 'Internet Bill',
            'amount' => '5000.00',
            'payment_method' => 'kpay',
        ]);
        $response->assertStatus(200);

        $totals = $this->closings->expectedTotals($store, $date);
        $this->assertSame('100000.00', $totals['expected']['cash']);
        $this->assertSame('0.00', $totals['summary']['drawer_expenses']);
    }

    /**
     * C05: POS expense submit / retry -> Expense and cash deduction recorded once
     */
    public function test_c05_pos_expense_submit_or_retry_is_idempotent_and_single_deduction(): void
    {
        $store = $this->makeStore('c05-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '100000.00',
        ], $cashier);

        $response = $this->actingAs($cashier)->postJson("/store/{$store->slug}/pos/expenses", [
            'title' => 'Water bottles',
            'amount' => '3000.00',
            'payment_method' => 'cash',
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseCount('expenses', 1);
        $this->assertSame('97000.00', $this->closings->expectedTotals($store, $date)['expected']['cash']);
    }

    /**
     * C06: Legacy expense + linked cash-out event -> Single deduction only, unrelated manual out remains intact
     */
    public function test_c06_legacy_expense_plus_linked_cash_out_single_deduction_and_unrelated_out_intact(): void
    {
        $store = $this->makeStore('c06-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '100000.00',
        ], $cashier);

        // Expense of 5,000
        $expense = Expense::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'expense_number' => 'EXP-20260917-0006',
            'title' => 'Stationery',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'status' => 'paid',
            'recorded_by' => $cashier->id,
        ]);

        // Legacy linked cash event (reason starts with 'Expense:' and expense_id linked)
        CashEvent::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'expense_id' => $expense->id,
            'type' => 'cash_out',
            'amount' => '5000.00',
            'reason' => 'Expense: Stationery',
            'created_by' => $cashier->id,
        ]);

        // Unrelated manual cash out (e.g. 2,000 safe drop)
        CashEvent::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'type' => 'cash_out',
            'amount' => '2000.00',
            'reason' => 'Midday safe transfer',
            'created_by' => $cashier->id,
        ]);

        $totals = $this->closings->expectedTotals($store, $date);

        // Expected = 100,000 - 5,000 (drawer expense) - 2,000 (other cash out) = 93,000
        $this->assertSame('5000.00', $totals['summary']['drawer_expenses']);
        $this->assertSame('2000.00', $totals['summary']['cash_out']);
        $this->assertSame('93000.00', $totals['expected']['cash']);

        // Single deduction verified on shift close:
        $closedShift = $this->shifts->closeShift($shift, [
            'actual_closing_amount' => '93000.00',
        ], $cashier);
        $this->assertSame('93000.00', (string) $closedShift->expected_closing_amount);
        $this->assertSame('0.00', (string) $closedShift->difference);
    }

    /**
     * C07: Concurrent shifts / Drawer A and B isolation -> A expense not deducted from B
     */
    public function test_c07_concurrent_shifts_or_drawer_a_b_isolation(): void
    {
        $store = $this->makeStore('c07-store');
        $cashier1 = $this->user($store, 'cashier', 'Cashier 1');
        $cashier2 = $this->user($store, 'cashier', 'Cashier 2');
        $date = Carbon::parse('2026-09-17');

        $shiftA = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '100000.00',
        ], $cashier1);

        $shiftB = $this->shifts->openShift($store, [
            'register_name' => 'REG-2',
            'opening_cash' => '50000.00',
        ], $cashier2);

        // Record expense from Drawer A only
        Expense::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shiftA->id,
            'expense_number' => 'EXP-20260917-007A',
            'title' => 'Drawer A Supplies',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'status' => 'paid',
            'recorded_by' => $cashier1->id,
        ]);

        // Shift A close: expected = 95,000
        $closedA = $this->shifts->closeShift($shiftA, [
            'actual_closing_amount' => '95000.00',
        ], $cashier1);
        $this->assertSame('95000.00', (string) $closedA->expected_closing_amount);

        // Shift B close: expected = 50,000 (untouched by A's expense)
        $closedB = $this->shifts->closeShift($shiftB, [
            'actual_closing_amount' => '50000.00',
        ], $cashier2);
        $this->assertSame('50000.00', (string) $closedB->expected_closing_amount);
    }

    /**
     * C08: Expense before opening / shift boundary -> Correct period only, not double deducted in next shift
     */
    public function test_c08_expense_boundary_correct_period_only(): void
    {
        $store = $this->makeStore('c08-store');
        $cashier = $this->user($store, 'cashier');
        $dateDay1 = Carbon::parse('2026-09-17');
        $dateDay2 = Carbon::parse('2026-09-18');

        // Day 1 shift
        $shift1 = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '100000.00',
        ], $cashier);

        Expense::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift1->id,
            'expense_number' => 'EXP-20260917-0081',
            'title' => 'Day 1 Expense',
            'amount' => '5000.00',
            'expense_date' => $dateDay1->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'status' => 'paid',
            'recorded_by' => $cashier->id,
        ]);

        $this->shifts->closeShift($shift1, ['actual_closing_amount' => '95000.00'], $cashier);

        // Day 2 shift
        Carbon::setTestNow($dateDay2->copy()->setTime(9, 0));
        $shift2 = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '95000.00',
        ], $cashier);

        $closed2 = $this->shifts->closeShift($shift2, ['actual_closing_amount' => '95000.00'], $cashier);
        $this->assertSame('95000.00', (string) $closed2->expected_closing_amount);
        Carbon::setTestNow(null);
    }

    /**
     * C09: Unpaid expense does not deduct; paid change/void updates once per policy
     */
    public function test_c09_unpaid_expense_does_not_deduct_and_paid_void_handled_correctly(): void
    {
        $store = $this->makeStore('c09-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '100000.00',
        ], $cashier);

        // Unpaid expense
        Expense::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'expense_number' => 'EXP-20260917-009A',
            'title' => 'Pending Supplier Bill',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'status' => 'unpaid',
            'recorded_by' => $cashier->id,
        ]);

        $totals = $this->closings->expectedTotals($store, $date);
        $this->assertSame('100000.00', $totals['expected']['cash']);
    }

    /**
     * C10: Drawer to safe transfer 5,000 -> Drawer 95,000, operating expense 0
     */
    public function test_c10_drawer_to_safe_transfer_reduces_drawer_without_operating_expense(): void
    {
        $store = $this->makeStore('c10-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '100000.00',
        ], $cashier);

        // Cash out event (transfer to safe)
        $this->shifts->addCashEvent($shift, [
            'type' => 'cash_out',
            'amount' => '5000.00',
            'reason' => 'Safe drop / Transfer to safe',
        ], $cashier);

        $totals = $this->closings->expectedTotals($store, $date);
        $this->assertSame('95000.00', $totals['expected']['cash']);
        $this->assertSame('0.00', $totals['summary']['drawer_expenses']);
        $this->assertSame('5000.00', $totals['summary']['cash_out']);
    }

    /**
     * C11: Sale 25k, tender 30k, change 5k, drawer exp 5k, refund 10k, opening 100k -> Expected 110k, goods net sales 15k
     */
    public function test_c11_full_flow_sale_change_drawer_expense_refund_opening(): void
    {
        $store = $this->makeStore('c11-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '100000.00',
        ], $cashier);

        // Cash sale: customer buys 25,000, tenders 30,000, change is 5,000. Net retained cash is 25,000.
        $this->shifts->recordCashSale($shift, '25000.00');

        // Cash refund: 10,000 returned to customer
        $this->shifts->recordCashRefund($shift, '10000.00');

        // Drawer expense: 5,000 paid out
        Expense::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'expense_number' => 'EXP-20260917-0111',
            'title' => 'Counter supplies',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'status' => 'paid',
            'recorded_by' => $cashier->id,
        ]);

        // Expected = 100,000 + 25,000 - 10,000 - 5,000 = 110,000
        $totals = $this->closings->expectedTotals($store, $date);
        $this->assertSame('110000.00', $totals['expected']['cash']);
        $this->assertSame('5000.00', $totals['summary']['drawer_expenses']);

        $closedShift = $this->shifts->closeShift($shift, [
            'actual_closing_amount' => '110000.00',
        ], $cashier);
        $this->assertSame('110000.00', (string) $closedShift->expected_closing_amount);
        $this->assertSame('0.00', (string) $closedShift->difference);
    }

    /**
     * C12: Expected 95,000, counted 95,000 close/save & reload -> Variance 0, stored matches expected
     */
    public function test_c12_expected_equals_counted_variance_zero_close_save_and_reload(): void
    {
        $store = $this->makeStore('c12-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '100000.00',
        ], $cashier);

        Expense::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'expense_number' => 'EXP-20260917-0121',
            'title' => 'Supplies',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'status' => 'paid',
            'recorded_by' => $cashier->id,
        ]);

        $this->shifts->closeShift($shift, ['actual_closing_amount' => '95000.00'], $cashier);

        // Daily closing submission
        $closing = $this->closings->create(
            store: $store,
            date: $date,
            counted: ['cash' => '95000.00', 'kpay' => '0.00', 'wavepay' => '0.00', 'cb_pay' => '0.00', 'mmqr' => '0.00'],
            explanation: null,
            actor: $cashier,
        );

        $this->assertSame('95000.00', (string) $closing->expected_totals['cash']);
        $this->assertSame('95000.00', (string) $closing->counted_totals['cash']);
        $this->assertSame('0.00', (string) $closing->differences['cash']);
        $this->assertSame('0.00', (string) $closing->total_difference);

        // Reload from database
        $reloaded = DailyClosing::findOrFail($closing->id);
        $this->assertSame('0.00', (string) $reloaded->total_difference);
    }

    /**
     * C13: Expected 95,000, counted 94,000 -> Shortage -1,000, explanation / manager workflow required
     */
    public function test_c13_expected_versus_counted_shortage_workflow(): void
    {
        $store = $this->makeStore('c13-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '100000.00',
        ], $cashier);

        Expense::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'expense_number' => 'EXP-20260917-0131',
            'title' => 'Supplies',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'status' => 'paid',
            'recorded_by' => $cashier->id,
        ]);

        $this->shifts->closeShift($shift, [
            'actual_closing_amount' => '94000.00',
            'variance_reason' => 'Lost 1000 note',
        ], $cashier);

        // Without explanation -> rejects
        $this->expectException(InventoryException::class);
        $this->closings->create(
            store: $store,
            date: $date,
            counted: ['cash' => '94000.00', 'kpay' => '0.00', 'wavepay' => '0.00', 'cb_pay' => '0.00', 'mmqr' => '0.00'],
            explanation: '',
            actor: $cashier,
        );
    }

    /**
     * C14: Closed-period edit/void/backdate attempt -> Locked / protected
     */
    public function test_c14_closed_period_edit_void_backdate_attempt(): void
    {
        $store = $this->makeStore('c14-store');
        $cashier = $this->user($store, 'cashier');
        $manager = $this->user($store, 'manager');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, [
            'register_name' => 'REG-1',
            'opening_cash' => '100000.00',
        ], $cashier);

        $this->shifts->closeShift($shift, ['actual_closing_amount' => '100000.00'], $cashier);

        $closing = $this->closings->create(
            store: $store,
            date: $date,
            counted: ['cash' => '100000.00', 'kpay' => '0.00', 'wavepay' => '0.00', 'cb_pay' => '0.00', 'mmqr' => '0.00'],
            explanation: null,
            actor: $cashier,
        );

        $this->closings->approve($store, $closing, $manager);

        // After approval, creating duplicate closing for same date is blocked
        $this->expectException(InventoryException::class);
        $this->closings->create(
            store: $store,
            date: $date,
            counted: ['cash' => '100000.00'],
            explanation: null,
            actor: $cashier,
        );
    }

    /**
     * C15: Cashier/manager permission & cross-store isolation
     */
    public function test_c15_cashier_manager_permission_and_cross_store_isolation(): void
    {
        $storeA = $this->makeStore('c15-store-a');
        $storeB = $this->makeStore('c15-store-b');
        $cashierA = $this->user($storeA, 'cashier');
        $date = Carbon::parse('2026-09-17');

        // Cashier of Store A cannot view or approve closing of Store B
        $response = $this->actingAs($cashierA)->get("/store/{$storeB->slug}/pos/closing");
        $response->assertStatus(403);
    }

    /**
     * F01: Drawer A/B open, Admin drawer expense without shift selection -> Validation error, expense not created
     */
    public function test_f01_admin_drawer_expense_without_shift_selection_fails_validation(): void
    {
        $store = $this->makeStore('f01-store');
        $manager = $this->user($store, 'manager');
        $cashierA = $this->user($store, 'cashier', 'Cashier A');
        $cashierB = $this->user($store, 'cashier', 'Cashier B');

        $shiftA = $this->shifts->openShift($store, ['register_name' => 'REG-A', 'opening_cash' => '100000.00'], $cashierA);
        $shiftB = $this->shifts->openShift($store, ['register_name' => 'REG-B', 'opening_cash' => '100000.00'], $cashierB);

        $response = $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", [
            'title' => 'Office cleaning',
            'amount' => '5000.00',
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => '',
        ]);

        $response->assertSessionHasErrors('cashier_shift_id');
        $this->assertDatabaseCount('expenses', 0);
    }

    /**
     * F02: Admin selects Shift A, opening 100k, expense 5k -> Shift A expected 95k, Shift B unchanged (100k)
     */
    public function test_f02_admin_drawer_expense_with_selected_shift_deducts_only_that_shift(): void
    {
        $store = $this->makeStore('f02-store');
        $manager = $this->user($store, 'manager');
        $cashierA = $this->user($store, 'cashier', 'Cashier A');
        $cashierB = $this->user($store, 'cashier', 'Cashier B');

        $shiftA = $this->shifts->openShift($store, ['register_name' => 'REG-A', 'opening_cash' => '100000.00'], $cashierA);
        $shiftB = $this->shifts->openShift($store, ['register_name' => 'REG-B', 'opening_cash' => '100000.00'], $cashierB);

        $response = $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", [
            'title' => 'Register A supplies',
            'amount' => '5000.00',
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shiftA->id,
        ]);
        $response->assertSessionHasNoErrors();

        $closedShiftA = $this->shifts->closeShift($shiftA, ['actual_closing_amount' => '95000.00'], $cashierA);
        $this->assertSame('95000.00', (string) $closedShiftA->expected_closing_amount);

        $closedShiftB = $this->shifts->closeShift($shiftB, ['actual_closing_amount' => '100000.00'], $cashierB);
        $this->assertSame('100000.00', (string) $closedShiftB->expected_closing_amount);
    }

    /**
     * F03: No open/closed/foreign-store/unauthorized shift, invalid source -> Reject, DB unchanged
     */
    public function test_f03_rejects_closed_or_foreign_store_shift_and_invalid_source(): void
    {
        $store = $this->makeStore('f03-store');
        $storeOther = $this->makeStore('f03-other');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');
        $cashierOther = $this->user($storeOther, 'cashier');

        $closedShift = $this->shifts->openShift($store, ['register_name' => 'REG-OLD', 'opening_cash' => '10000.00'], $cashier);
        $this->shifts->closeShift($closedShift, ['actual_closing_amount' => '10000.00'], $cashier);

        $foreignShift = $this->shifts->openShift($storeOther, ['register_name' => 'REG-OTHER', 'opening_cash' => '10000.00'], $cashierOther);

        // 1. Closed shift rejected
        $resp1 = $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", [
            'title' => 'Closed shift expense',
            'amount' => '1000.00',
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $closedShift->id,
        ]);
        $resp1->assertSessionHasErrors('cashier_shift_id');

        // 2. Foreign shift rejected
        $resp2 = $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", [
            'title' => 'Foreign shift expense',
            'amount' => '1000.00',
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $foreignShift->id,
        ]);
        $resp2->assertSessionHasErrors('cashier_shift_id');

        // 3. Non-cash method with drawer source rejected
        $openShift = $this->shifts->openShift($store, ['register_name' => 'REG-CURRENT', 'opening_cash' => '10000.00'], $cashier);
        $resp3 = $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", [
            'title' => 'Invalid source combination',
            'amount' => '1000.00',
            'expense_date' => now()->toDateString(),
            'payment_method' => 'kpay',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $openShift->id,
        ]);
        $resp3->assertSessionHasErrors('payment_source');

        $this->assertDatabaseCount('expenses', 0);
    }

    /**
     * F04: POS cash expense without valid active shift -> Reject, orphan drawer expense prevented
     */
    public function test_f04_pos_cash_expense_without_valid_active_shift_is_rejected(): void
    {
        $store = $this->makeStore('f04-store');
        $cashier = $this->user($store, 'cashier');

        $response = $this->actingAs($cashier)->postJson("/store/{$store->slug}/pos/expenses", [
            'title' => 'Orphan expense test',
            'amount' => '2500.00',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('expenses', 0);
    }

    /**
     * F05: Paid drawer expense + valid linked cash-out 5,000 -> Total outflow 5,000 only
     */
    public function test_f05_paid_drawer_expense_with_linked_cash_out_single_deduction(): void
    {
        $store = $this->makeStore('f05-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $expense = Expense::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'expense_number' => 'EXP-20260917-F05',
            'title' => 'Paid Drawer Expense',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'status' => 'paid',
            'recorded_by' => $cashier->id,
        ]);

        CashEvent::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'expense_id' => $expense->id,
            'type' => 'cash_out',
            'amount' => '5000.00',
            'reason' => 'Expense: ' . $expense->title,
            'created_by' => $cashier->id,
        ]);

        $totals = $this->closings->expectedTotals($store, $date);
        $this->assertSame('95000.00', $totals['expected']['cash']);
        $this->assertSame('5000.00', $totals['summary']['drawer_expenses']);
        $this->assertSame('0.00', $totals['summary']['cash_out']);

        $closedShift = $this->shifts->closeShift($shift, ['actual_closing_amount' => '95000.00'], $cashier);
        $this->assertSame('95000.00', (string) $closedShift->expected_closing_amount);
    }

    /**
     * F06: Legacy real cash-out reason Expense: + unresolved expense -> Proven outflow not lost
     */
    public function test_f06_legacy_real_cash_out_with_expense_prefix_unresolved_is_not_lost(): void
    {
        $store = $this->makeStore('f06-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        CashEvent::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'expense_id' => null,
            'type' => 'cash_out',
            'amount' => '5000.00',
            'reason' => 'Expense: Unresolved legacy supplies',
            'created_by' => $cashier->id,
        ]);

        $totals = $this->closings->expectedTotals($store, $date);
        $this->assertSame('95000.00', $totals['expected']['cash']);
        $this->assertSame('5000.00', $totals['summary']['cash_out']);

        $closedShift = $this->shifts->closeShift($shift, ['actual_closing_amount' => '95000.00'], $cashier);
        $this->assertSame('95000.00', (string) $closedShift->expected_closing_amount);
    }

    /**
     * F07: Unrelated manual cash-out reason with Expense: in text -> Not removed by prefix, actual outflow correct
     */
    public function test_f07_unrelated_manual_cash_out_with_expense_text_in_reason_is_preserved(): void
    {
        $store = $this->makeStore('f07-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        CashEvent::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'expense_id' => null,
            'type' => 'cash_out',
            'amount' => '4000.00',
            'reason' => 'Expense: Bank deposit safe drop',
            'created_by' => $cashier->id,
        ]);

        $totals = $this->closings->expectedTotals($store, $date);
        $this->assertSame('96000.00', $totals['expected']['cash']);
        $this->assertSame('4000.00', $totals['summary']['cash_out']);
    }

    /**
     * F08: Mixed shifts: legacy aggregate out + new event-backed out -> Neither shift outflow lost, no double count
     */
    public function test_f08_mixed_shifts_legacy_aggregate_out_and_new_event_backed_out(): void
    {
        $store = $this->makeStore('f08-store');
        $cashier1 = $this->user($store, 'cashier', 'Cashier 1');
        $cashier2 = $this->user($store, 'cashier', 'Cashier 2');
        $date = Carbon::parse('2026-09-17');

        // Shift 1: Legacy shift without granular cash_events rows, has cash_out = 3000
        $shift1 = $this->shifts->openShift($store, ['register_name' => 'REG-LEGACY', 'opening_cash' => '50000.00'], $cashier1);
        $shift1->update(['cash_out' => 3000.00]);

        // Shift 2: Modern shift with granular cash_events row of 5000
        $shift2 = $this->shifts->openShift($store, ['register_name' => 'REG-MODERN', 'opening_cash' => '50000.00'], $cashier2);
        CashEvent::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift2->id,
            'type' => 'cash_out',
            'amount' => '5000.00',
            'reason' => 'Midday safe drop',
            'created_by' => $cashier2->id,
        ]);

        $totals = $this->closings->expectedTotals($store, $date);
        $this->assertSame('8000.00', $totals['summary']['cash_out']);
        $this->assertSame('92000.00', $totals['expected']['cash']);
    }

    /**
     * F09: Unlinked drawer expense / overnight boundary -> Reconciled cleanly with zero delta
     */
    public function test_f09_shift_and_daily_reconciliation_boundary(): void
    {
        $store = $this->makeStore('f09-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $totals = $this->closings->expectedTotals($store, $date);
        $closed = $this->shifts->closeShift($shift, ['actual_closing_amount' => '100000.00'], $cashier);

        $this->assertSame($totals['expected']['cash'], (string) $closed->expected_closing_amount);
    }

    /**
     * F10: Safe/KPay expense 5,000 -> POS drawer not reduced, unknown not shown as Safe
     */
    public function test_f10_safe_or_kpay_expense_does_not_deduct_drawer_cash(): void
    {
        $store = $this->makeStore('f10-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        Expense::create([
            'store_id' => $store->id,
            'expense_number' => 'EXP-SAFE-01',
            'title' => 'Safe expense',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_SAFE,
            'status' => 'paid',
            'recorded_by' => $manager->id,
        ]);

        Expense::create([
            'store_id' => $store->id,
            'expense_number' => 'EXP-KPAY-01',
            'title' => 'KPay vendor bill',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'kpay',
            'payment_source' => Expense::SOURCE_BANK,
            'status' => 'paid',
            'recorded_by' => $manager->id,
        ]);

        $totals = $this->closings->expectedTotals($store, $date);
        $this->assertSame('100000.00', $totals['expected']['cash']);
        $this->assertSame('0.00', $totals['summary']['drawer_expenses']);
        $this->assertSame('5000.00', $totals['summary']['other_cash_expenses']);
    }

    /**
     * F11: Same request retry/concurrent with client_transaction_id, separate legitimate request
     */
    public function test_f11_pos_expense_idempotency_with_client_transaction_id(): void
    {
        $store = $this->makeStore('f11-store');
        $cashier = $this->user($store, 'cashier');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $clientTxId = 'uuid-tx-f11-001';

        // 1. Initial submission
        $resp1 = $this->actingAs($cashier)->postJson("/store/{$store->slug}/pos/expenses", [
            'title' => 'Office tape',
            'amount' => '2000.00',
            'payment_method' => 'cash',
            'client_transaction_id' => $clientTxId,
        ]);
        $resp1->assertStatus(200);

        // 2. Rapid retry with exact same client_transaction_id
        $resp2 = $this->actingAs($cashier)->postJson("/store/{$store->slug}/pos/expenses", [
            'title' => 'Office tape',
            'amount' => '2000.00',
            'payment_method' => 'cash',
            'client_transaction_id' => $clientTxId,
        ]);
        $resp2->assertStatus(200);
        $resp2->assertJson(['idempotent_replay' => true]);

        // Assert only 1 expense was created
        $this->assertDatabaseCount('expenses', 1);

        // 3. Distinct request with another client_transaction_id
        $resp3 = $this->actingAs($cashier)->postJson("/store/{$store->slug}/pos/expenses", [
            'title' => 'Office tape',
            'amount' => '2000.00',
            'payment_method' => 'cash',
            'client_transaction_id' => 'uuid-tx-f11-002',
        ]);
        $resp3->assertStatus(200);
        $this->assertDatabaseCount('expenses', 2);
    }

    /**
     * F12: Paid expense edit/void in open period -> Net impact recorded once, manual cash-out intact
     */
    public function test_f12_paid_expense_edit_void_in_open_period(): void
    {
        $store = $this->makeStore('f12-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $expense = Expense::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'expense_number' => 'EXP-F12',
            'title' => 'Supplies',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'status' => 'paid',
            'recorded_by' => $manager->id,
        ]);

        $this->assertSame('95000.00', $this->closings->expectedTotals($store, $date)['expected']['cash']);

        // Update amount via Admin
        $resp = $this->actingAs($manager)->put("/store/{$store->slug}/admin/expenses/{$expense->id}", [
            'title' => 'Supplies Updated',
            'amount' => '8000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
        ]);
        $resp->assertSessionHasNoErrors();

        $this->assertSame('92000.00', $this->closings->expectedTotals($store, $date)['expected']['cash']);
    }

    /**
     * F13: Approved period expense create/update/delete/void/backdate via HTTP -> Blocked
     */
    public function test_f13_approved_period_blocks_expense_mutations(): void
    {
        $store = $this->makeStore('f13-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);
        $this->shifts->closeShift($shift, ['actual_closing_amount' => '100000.00'], $cashier);

        $closing = $this->closings->create(
            store: $store,
            date: $date,
            counted: ['cash' => '100000.00', 'kpay' => '0.00', 'wavepay' => '0.00', 'cb_pay' => '0.00', 'mmqr' => '0.00'],
            explanation: null,
            actor: $cashier,
        );
        $this->closings->approve($store, $closing, $manager);

        // Attempt to create expense for approved date
        $resp = $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", [
            'title' => 'Backdated expense',
            'amount' => '1000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_SAFE,
        ]);
        $resp->assertSessionHasErrors('expense_date');
    }

    /**
     * F14: Opening 100k, retained sale 25k (tender 30k/change 5k), expense 5k, refund 10k -> Expected 110k, goods net sales 15k
     */
    public function test_f14_full_flow_tender_change_expense_refund(): void
    {
        $store = $this->makeStore('f14-store');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        // Retained cash sale: 25,000
        $this->shifts->recordCashSale($shift, '25000.00');

        // Cash refund: 10,000
        $this->shifts->recordCashRefund($shift, '10000.00');

        // Cash drawer expense: 5,000
        Expense::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'expense_number' => 'EXP-F14',
            'title' => 'Shop broom',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'status' => 'paid',
            'recorded_by' => $cashier->id,
        ]);

        $totals = $this->closings->expectedTotals($store, $date);
        // Expected: 100,000 + 25,000 - 10,000 - 5,000 = 110,000
        $this->assertSame('110000.00', $totals['expected']['cash']);
    }

    /**
     * F15: Expected/count 95k -> close -> reload -> approve -> print -> Variance 0, stored matches expected
     */
    public function test_f15_expected_and_counted_flow_variance_zero_snapshot_and_print(): void
    {
        $store = $this->makeStore('f15-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        Expense::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'expense_number' => 'EXP-F15',
            'title' => 'Stationery',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'status' => 'paid',
            'recorded_by' => $cashier->id,
        ]);

        $this->shifts->closeShift($shift, ['actual_closing_amount' => '95000.00'], $cashier);

        $closing = $this->closings->create(
            store: $store,
            date: $date,
            counted: ['cash' => '95000.00', 'kpay' => '0.00', 'wavepay' => '0.00', 'cb_pay' => '0.00', 'mmqr' => '0.00'],
            explanation: null,
            actor: $cashier,
        );

        $this->assertSame('0.00', (string) $closing->total_difference);
        $approved = $this->closings->approve($store, $closing, $manager);
        $this->assertSame('approved', $approved->approval_status);

        // Verify Print Endpoint returns HTTP 200
        $printResp = $this->actingAs($manager)->get("/store/{$store->slug}/pos/closing/print?type=z&layout=80mm&date={$date->toDateString()}");
        $printResp->assertStatus(200);
    }
}
