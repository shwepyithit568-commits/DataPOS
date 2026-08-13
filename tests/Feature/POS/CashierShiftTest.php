<?php

namespace Tests\Feature\POS;

use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CashEvent;
use App\POS\Models\CashierShift;
use App\POS\Services\CashierShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashierShiftTest extends TestCase
{
    use RefreshDatabase;

    private CashierShiftService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CashierShiftService::class);
    }

    private function makeStore(string $slug = 'shop-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function staff(Store $store): User
    {
        $user = User::create([
            'name' => 'Cashier Staff ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        return $user;
    }

    /* ------------------------------------------------------------------ */
    /*  Open                                                               */
    /* ------------------------------------------------------------------ */

    public function test_open_shift_creates_open_shift_with_opening_cash(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $shift = $this->service->openShift($store, ['register_name' => 'Register 1', 'opening_cash' => 50000], $cashier);

        $this->assertTrue($shift->isOpen());
        $this->assertSame('Register 1', $shift->register_name);
        $this->assertSame('50000.00', (string) $shift->opening_cash);
        $this->assertSame($cashier->id, $shift->cashier_id);
        $this->assertNotNull($shift->opened_at);
    }

    public function test_open_shift_requires_register_name(): void
    {
        $store = $this->makeStore();

        $this->expectException(InventoryException::class);
        $this->service->openShift($store, ['opening_cash' => 1000]);
    }

    public function test_one_open_shift_per_register(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $this->service->openShift($store, ['register_name' => 'R1', 'opening_cash' => 1000], $cashier);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('already has an open shift');

        $this->service->openShift($store, ['register_name' => 'R1', 'opening_cash' => 2000], $cashier);
    }

    public function test_different_registers_can_be_open_simultaneously(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $this->service->openShift($store, ['register_name' => 'R1', 'opening_cash' => 1000], $cashier);
        $shiftB = $this->service->openShift($store, ['register_name' => 'R2', 'opening_cash' => 500], $cashier);

        $this->assertTrue($shiftB->isOpen());
        $this->assertSame(2, CashierShift::where('status', 'open')->count());
    }

    public function test_open_shift_for_returns_actor_shift(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $this->assertNull($this->service->openShiftFor($store, $cashier));

        $shift = $this->service->openShift($store, ['register_name' => 'R1'], $cashier);
        $this->assertSame($shift->id, $this->service->openShiftFor($store, $cashier)->id);
    }

    /* ------------------------------------------------------------------ */
    /*  Cash events                                                        */
    /* ------------------------------------------------------------------ */

    public function test_cash_in_out_events_update_totals_and_are_logged(): void
    {
        $store = $this->makeStore();
        $shift = $this->service->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $this->staff($store));

        $this->service->addCashEvent($shift, ['type' => 'cash_in', 'amount' => 10000, 'reason' => 'Extra float'], $this->staff($store));
        $this->service->addCashEvent($shift, ['type' => 'cash_out', 'amount' => 5000, 'reason' => 'Petty cash'], $this->staff($store));

        $this->assertSame('10000.00', (string) $shift->fresh()->cash_in);
        $this->assertSame('5000.00', (string) $shift->fresh()->cash_out);
        $this->assertSame(2, CashEvent::count());
    }

    public function test_cash_event_requires_positive_amount(): void
    {
        $store = $this->makeStore();
        $shift = $this->service->openShift($store, ['register_name' => 'R1'], $this->staff($store));

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('must be positive');

        $this->service->addCashEvent($shift, ['type' => 'cash_in', 'amount' => 0]);
    }

    public function test_cash_event_on_closed_shift_is_blocked(): void
    {
        $store = $this->makeStore();
        $shift = $this->service->openShift($store, ['register_name' => 'R1'], $this->staff($store));
        $this->service->closeShift($shift, ['actual_closing_amount' => 1000], $this->staff($store));

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('already closed');

        $this->service->addCashEvent($shift->fresh(), ['type' => 'cash_in', 'amount' => 100]);
    }

    /* ------------------------------------------------------------------ */
    /*  Close                                                              */
    /* ------------------------------------------------------------------ */

    public function test_close_computes_expected_and_difference(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $shift = $this->service->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);

        $this->service->addCashEvent($shift, ['type' => 'cash_in', 'amount' => 10000], $cashier);
        $this->service->addCashEvent($shift, ['type' => 'cash_out', 'amount' => 5000], $cashier);

        $closed = $this->service->closeShift($shift, ['actual_closing_amount' => 56000, 'notes' => 'Long day'], $cashier);

        // expected = 50000 + 0 − 0 + 10000 − 5000 = 55000
        $this->assertSame('55000.00', (string) $closed->expected_closing_amount);
        $this->assertSame('56000.00', (string) $closed->actual_closing_amount);
        $this->assertSame('1000.00', (string) $closed->difference);
        $this->assertSame('Long day', $closed->notes);
        $this->assertTrue($closed->isClosed());
        $this->assertNotNull($closed->closed_at);
        $this->assertSame($cashier->id, $closed->closed_by);
    }

    public function test_close_includes_cash_sales_and_refunds(): void
    {
        $store = $this->makeStore();
        $shift = $this->service->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $this->staff($store));

        // Simulate the sale module (next Phase 2 task) updating shift totals.
        $shift->update(['cash_sales' => 30000, 'cash_refunds' => 5000]);

        $closed = $this->service->closeShift($shift->fresh(), ['actual_closing_amount' => 75000], $this->staff($store));

        // expected = 50000 + 30000 − 5000 + 0 − 0 = 75000
        $this->assertSame('75000.00', (string) $closed->expected_closing_amount);
        $this->assertSame('0.00', (string) $closed->difference);
    }

    public function test_close_requires_actual_amount_and_blocks_double_close(): void
    {
        $store = $this->makeStore();
        $shift = $this->service->openShift($store, ['register_name' => 'R1'], $this->staff($store));

        $this->expectException(InventoryException::class);
        $this->service->closeShift($shift, [], $this->staff($store));
    }

    public function test_daily_summary_aggregates_closed_shifts(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $shiftA = $this->service->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);
        $this->service->closeShift($shiftA, ['actual_closing_amount' => 52000], $cashier);

        $shiftB = $this->service->openShift($store, ['register_name' => 'R2', 'opening_cash' => 30000], $cashier);
        $this->service->closeShift($shiftB, ['actual_closing_amount' => 29000], $cashier);

        $summary = $this->service->dailySummary($store, now());

        $this->assertSame(2, $summary['shift_count']);
        $this->assertSame('80000.00', $summary['opening_cash']);
        $this->assertSame('81000.00', $summary['actual']);
        $this->assertSame('1000.00', $summary['difference']); // actual − expected: +2000 + (−1000)
    }

    /* ------------------------------------------------------------------ */
    /*  HTTP + authorization                                               */
    /* ------------------------------------------------------------------ */

    public function test_pos_page_renders_for_staff(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $this->actingAs($cashier)->get("/store/{$store->slug}/pos")
            ->assertStatus(200)
            ->assertSee(__('messages.open_new_shift'));
    }

    public function test_non_staff_cannot_open_shift(): void
    {
        $store = $this->makeStore();
        $outsider = User::create([
            'name' => 'Outsider',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->actingAs($outsider)->post("/store/{$store->slug}/pos/shifts", [
            'register_name' => 'R1',
            'opening_cash' => 1000,
        ])->assertStatus(403);
    }

    public function test_cross_store_shift_tampering_is_blocked(): void
    {
        $storeA = $this->makeStore('shop-a');
        $storeB = $this->makeStore('shop-b');
        $cashierA = $this->staff($storeA);

        $shiftA = $this->service->openShift($storeA, ['register_name' => 'R1'], $cashierA);

        // Cashier of store A cannot post cash events on a store-B URL context.
        $this->actingAs($cashierA)->post("/store/{$storeB->slug}/pos/shifts/{$shiftA->id}/cash-events", [
            'type' => 'cash_in',
            'amount' => 100,
        ])->assertStatus(403);
    }

    public function test_http_flow_open_cash_event_close(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $this->actingAs($cashier)->post("/store/{$store->slug}/pos/shifts", [
            'register_name' => 'R1',
            'opening_cash' => 50000,
        ])->assertRedirect();

        $shift = CashierShift::where('store_id', $store->id)->firstOrFail();
        $this->assertTrue($shift->isOpen());

        $this->actingAs($cashier)->post("/store/{$store->slug}/pos/shifts/{$shift->id}/cash-events", [
            'type' => 'cash_in',
            'amount' => 10000,
            'reason' => 'Float',
        ])->assertRedirect();

        $this->actingAs($cashier)->post("/store/{$store->slug}/pos/shifts/{$shift->id}/close", [
            'actual_closing_amount' => 61000,
        ])->assertRedirect();

        // expected = 50000 + 10000 (cash in) = 60000; actual 61000 → +1000
        $this->assertSame('61000.00', (string) $shift->fresh()->actual_closing_amount);
        $this->assertSame('1000.00', (string) $shift->fresh()->difference);
    }
}
