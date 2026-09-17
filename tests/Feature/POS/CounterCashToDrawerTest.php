<?php

namespace Tests\Feature\POS;

use App\Models\StaffRole;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\CashEvent;
use App\POS\Models\CustomerLedgerEntry;
use App\POS\Models\DailyClosing;
use App\POS\Services\CashierShiftService;
use App\POS\Services\CounterCashPosting;
use App\Services\StorePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Counter cash that is not a POS sale — debt collections and repair payments —
 * has to reach the drawer the day's closing is reconciled against.
 *
 * Before this, the money physically entered the drawer while `cash_in` stayed
 * at zero, so the closing screen reported a shortage equal to every collection
 * and repair payment taken that day (55,000 MMK on the 2026-09-18 UAT run).
 */
class CounterCashToDrawerTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        StorePermissionService::invalidateCache();

        $this->store = Store::create([
            'name' => 'Counter Cash Shop',
            'slug' => 'counter-cash-shop',
            'is_active' => true,
        ]);
    }

    private function member(string $storeRole, array $permissions = [], string $name = 'Member'): User
    {
        $user = User::create([
            'name' => $name . ' ' . Str::random(4),
            'phone' => '09' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $pivot = ['role' => $storeRole, 'status' => 'active'];

        if ($permissions !== []) {
            $role = StaffRole::create([
                'store_id' => $this->store->id,
                'name' => $name . ' Role',
                'slug' => Str::slug($name) . '-' . Str::random(4),
                'permissions' => $permissions,
                'is_system' => false,
                'is_active' => true,
            ]);
            $pivot['staff_role_id'] = $role->id;
        }

        $user->stores()->attach($this->store->id, $pivot);

        return $user;
    }

    private function cashier(array $extra = []): User
    {
        return $this->member('staff', array_merge([
            'pos_sales.view',
            'repairs.view',
            'repairs.create',
            'repairs.update',
        ], $extra), 'Cashier');
    }

    private function openShift(User $cashier, string $register = 'Front Counter', string $opening = '200000'): \App\POS\Models\CashierShift
    {
        return app(CashierShiftService::class)->openShift($this->store, [
            'register_name' => $register,
            'opening_cash' => $opening,
        ], $cashier);
    }

    private function customerWithDebt(string $amount = '22000.00'): User
    {
        $customer = $this->member('retail_customer', [], 'Customer');

        CustomerLedgerEntry::create([
            'store_id' => $this->store->id,
            'customer_id' => $customer->id,
            'type' => CustomerLedgerEntry::TYPE_SALE_DEBT,
            'amount' => $amount,
            'source_type' => 'manual',
            'occurred_at' => now(),
        ]);

        return $customer;
    }

    private function collect(User $actor, User $customer, string $amount = '10000', string $method = 'cash')
    {
        return $this->actingAs($actor)->post(
            "/store/{$this->store->slug}/admin/receivables/{$customer->id}/collect",
            ['amount' => $amount, 'payment_method' => $method]
        );
    }

    // ── Debt collections ──────────────────────────────────────────────────────

    public function test_cash_collection_lands_in_the_actor_own_open_shift(): void
    {
        $manager = $this->member('store_manager', [], 'Manager');
        $shift = $this->openShift($manager);
        $customer = $this->customerWithDebt();

        $this->collect($manager, $customer)->assertRedirect();

        $this->assertSame('10000.00', $shift->fresh()->cash_in);

        $event = CashEvent::where('cashier_shift_id', $shift->id)->sole();
        $this->assertSame('cash_in', $event->type);
        $this->assertSame('10000.00', $event->amount);
        $this->assertSame(
            __('messages.cash_reason_debt_collection') . ' — ' . $customer->name,
            $event->reason
        );
    }

    public function test_cash_collection_reaches_the_only_open_register_when_the_actor_has_no_shift(): void
    {
        // The shop's everyday case: the manager takes the money at the counter
        // while the cashier's register is the one holding the drawer.
        $cashier = $this->cashier();
        $shift = $this->openShift($cashier);
        $manager = $this->member('store_manager', [], 'Manager');
        $customer = $this->customerWithDebt();

        $this->collect($manager, $customer)->assertRedirect();

        $this->assertSame('10000.00', $shift->fresh()->cash_in);
        $this->assertSame(1, CashEvent::where('cashier_shift_id', $shift->id)->count());
        $this->assertNull(app(CashierShiftService::class)->openShiftFor($this->store, $manager));
    }

    public function test_non_cash_collection_never_touches_the_drawer(): void
    {
        $cashier = $this->cashier();
        $shift = $this->openShift($cashier);
        $manager = $this->member('store_manager', [], 'Manager');
        $customer = $this->customerWithDebt();

        $this->collect($manager, $customer, '10000', 'kpay')->assertRedirect();

        $this->assertSame('0.00', $shift->fresh()->cash_in);
        $this->assertSame(0, CashEvent::count());
    }

    public function test_two_open_registers_leave_counter_cash_unposted_with_a_warning(): void
    {
        $cashierA = $this->cashier();
        $cashierB = $this->cashier();
        $shiftA = $this->openShift($cashierA, 'Front Counter');
        $shiftB = $this->openShift($cashierB, 'Back Counter');

        $manager = $this->member('store_manager', [], 'Manager');
        $customer = $this->customerWithDebt();

        $response = $this->collect($manager, $customer);

        $response->assertRedirect()->assertSessionHas('warning', __('messages.counter_cash_not_posted'));

        $this->assertSame('0.00', $shiftA->fresh()->cash_in);
        $this->assertSame('0.00', $shiftB->fresh()->cash_in);
        $this->assertSame(0, CashEvent::count());
    }

    public function test_counter_cash_is_not_posted_into_an_approved_business_date(): void
    {
        $cashier = $this->cashier();
        $shift = $this->openShift($cashier);

        DailyClosing::create([
            'store_id' => $this->store->id,
            'business_date' => $shift->opened_at->toDateString(),
            'expected_totals' => ['cash' => '200000.00'],
            'counted_totals' => ['cash' => '200000.00'],
            'differences' => ['cash' => '0.00'],
            'approval_status' => 'approved',
            'total_difference' => 0,
        ]);

        $manager = $this->member('store_manager', [], 'Manager');
        $customer = $this->customerWithDebt();
        $this->collect($manager, $customer)->assertRedirect()->assertSessionHas('warning');

        $this->assertSame('0.00', $shift->fresh()->cash_in);
    }

    public function test_a_collection_without_an_open_shift_still_records_the_payment(): void
    {
        $manager = $this->member('store_manager', [], 'Manager');
        $customer = $this->customerWithDebt();

        $this->collect($manager, $customer)->assertRedirect()->assertSessionHas('warning');

        // The ledger must keep the money even when the drawer cannot take it.
        $this->assertSame(
            '-10000.00',
            CustomerLedgerEntry::where('customer_id', $customer->id)
                ->where('type', CustomerLedgerEntry::TYPE_COLLECTION)
                ->value('amount')
        );
        $this->assertSame(0, CashEvent::count());
    }

    // ── Repair payments ───────────────────────────────────────────────────────

    private function createRepair(User $actor, string $advance, string $method = 'cash'): int
    {
        $response = $this->actingAs($actor)->post("/store/{$this->store->slug}/admin/repairs", [
            'contact_name' => 'Ko Aung',
            'contact_phone' => '09970000001',
            'device_type' => 'Smartphone',
            'model' => 'iPhone 13 Pro Max',
            'reported_problem' => 'Broken screen',
            'estimated_charge' => '45000',
            'advance_payment' => $advance,
            'payment_method' => $method,
        ]);

        $response->assertRedirect();

        return (int) $response->headers->get('Location') ? $this->lastJobId() : 0;
    }

    private function lastJobId(): int
    {
        return (int) \App\POS\Models\ServiceJob::query()->latest('id')->value('id');
    }

    public function test_cash_repair_advance_on_intake_lands_in_the_drawer(): void
    {
        $cashier = $this->cashier();
        $shift = $this->openShift($cashier);

        $this->createRepair($cashier, '10000');

        $this->assertSame('10000.00', $shift->fresh()->cash_in);

        $event = CashEvent::where('cashier_shift_id', $shift->id)->sole();
        $this->assertStringContainsString(__('messages.cash_reason_repair_advance'), $event->reason);
    }

    public function test_non_cash_repair_advance_never_touches_the_drawer(): void
    {
        $cashier = $this->cashier();
        $shift = $this->openShift($cashier);

        $this->createRepair($cashier, '10000', 'kpay');

        $this->assertSame('0.00', $shift->fresh()->cash_in);
        $this->assertSame(0, CashEvent::count());
    }

    public function test_cash_repair_balance_payment_lands_in_the_drawer(): void
    {
        $cashier = $this->cashier();
        $shift = $this->openShift($cashier);

        $this->createRepair($cashier, '10000');
        $jobId = $this->lastJobId();

        $this->actingAs($cashier)
            ->post("/store/{$this->store->slug}/admin/repairs/{$jobId}/payments", [
                'method' => 'cash',
                'amount' => '35000',
            ])
            ->assertRedirect();

        // 10,000 advance + 35,000 balance, both cash, both in the same drawer.
        $this->assertSame('45000.00', $shift->fresh()->cash_in);
        $this->assertSame(2, CashEvent::where('cashier_shift_id', $shift->id)->where('type', 'cash_in')->count());
    }

    public function test_non_cash_repair_balance_payment_never_touches_the_drawer(): void
    {
        $cashier = $this->cashier();
        $shift = $this->openShift($cashier);

        $this->createRepair($cashier, '10000');
        $jobId = $this->lastJobId();

        $this->actingAs($cashier)
            ->post("/store/{$this->store->slug}/admin/repairs/{$jobId}/payments", [
                'method' => 'kpay',
                'amount' => '35000',
            ])
            ->assertRedirect();

        $this->assertSame('10000.00', $shift->fresh()->cash_in);
        $this->assertSame(1, CashEvent::count());
    }

    // ── The service itself ────────────────────────────────────────────────────

    public function test_posting_is_atomic_and_idempotent_per_call(): void
    {
        $cashier = $this->cashier();
        $shift = $this->openShift($cashier);
        $service = app(CashierShiftService::class);

        $service->postCounterCashIn($this->store, '1000', 'first', $cashier);
        $service->postCounterCashIn($this->store, '2000', 'second', $cashier);

        $this->assertSame('3000.00', $shift->fresh()->cash_in);
        $this->assertSame(2, CashEvent::count());
    }

    public function test_a_zero_amount_is_not_posted(): void
    {
        $cashier = $this->cashier();
        $shift = $this->openShift($cashier);

        $this->assertNull(app(CashierShiftService::class)->postCounterCashIn($this->store, '0', 'nothing', $cashier));
        $this->assertSame('0.00', $shift->fresh()->cash_in);
    }

    public function test_the_posting_helper_reports_the_register_it_credited(): void
    {
        $cashier = $this->cashier();
        $this->openShift($cashier, 'Main Drawer');

        $result = app(CounterCashPosting::class)->postSafely($this->store, '5000', 'walk-in', $cashier);

        $this->assertTrue($result['posted']);
        $this->assertSame('Main Drawer', $result['register']);
        $this->assertStringContainsString('Main Drawer', $result['message']);
    }

    public function test_the_posting_helper_never_throws_when_the_drawer_is_ambiguous(): void
    {
        $this->openShift($this->cashier(), 'Front Counter');
        $this->openShift($this->cashier(), 'Back Counter');

        $result = app(CounterCashPosting::class)->postSafely($this->store, '5000', 'walk-in', $this->member('store_manager', [], 'Manager'));

        $this->assertFalse($result['posted']);
        $this->assertNull($result['register']);
        $this->assertSame(__('messages.counter_cash_not_posted'), $result['message']);
    }
}
