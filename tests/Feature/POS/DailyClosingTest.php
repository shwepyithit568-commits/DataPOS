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
}
