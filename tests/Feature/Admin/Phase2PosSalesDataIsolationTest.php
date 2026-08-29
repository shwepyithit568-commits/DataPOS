<?php

namespace Tests\Feature\Admin;

use App\Models\EloadAccount;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\CashierShift;
use App\POS\Models\DailyClosing;
use App\POS\Models\PosSale;
use App\POS\Models\Warehouse;
use App\POS\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2PosSalesDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Store $storeA;
    protected Store $storeB;
    protected User $staffA;
    protected User $staffB;
    protected User $managerA;
    protected User $managerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::create(['name' => 'Store Alpha', 'slug' => 'store-alpha']);
        $this->storeA->setting()->create(['store_name' => 'Store Alpha', 'default_language' => 'en']);

        $this->storeB = Store::create(['name' => 'Store Beta', 'slug' => 'store-beta']);
        $this->storeB->setting()->create(['store_name' => 'Store Beta', 'default_language' => 'en']);

        $this->staffA = User::factory()->create(['name' => 'Staff Alpha', 'phone' => '09111111111']);
        $this->staffA->stores()->attach($this->storeA->id, ['role' => 'staff', 'status' => 'active']);

        $this->managerA = User::factory()->create(['name' => 'Manager Alpha', 'phone' => '09111111112']);
        $this->managerA->stores()->attach($this->storeA->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staffB = User::factory()->create(['name' => 'Staff Beta', 'phone' => '09222222221']);
        $this->staffB->stores()->attach($this->storeB->id, ['role' => 'staff', 'status' => 'active']);

        $this->managerB = User::factory()->create(['name' => 'Manager Beta', 'phone' => '09222222222']);
        $this->managerB->stores()->attach($this->storeB->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_pos_sale_and_receipt_cross_store_isolation(): void
    {
        $saleB = PosSale::create([
            'store_id' => $this->storeB->id,
            'cashier_id' => $this->staffB->id,
            'status' => 'posted',
            'receipt_number' => 'REC-B-001',
            'total' => '50000.00',
            'posted_at' => now(),
        ]);

        // 1. Staff A cannot access receipt of Store B
        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/pos/sales/{$saleB->id}/receipt")
            ->assertNotFound();

        // 2. Staff A cannot resume or void Store B sale
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/resume/{$saleB->id}")
            ->assertRedirect(); // Fails gracefully or aborts
    }

    public function test_pos_returns_cross_store_isolation(): void
    {
        $saleB = PosSale::create([
            'store_id' => $this->storeB->id,
            'cashier_id' => $this->staffB->id,
            'status' => 'posted',
            'receipt_number' => 'REC-B-002',
            'total' => '30000.00',
            'posted_at' => now(),
        ]);

        // Staff A cannot open refund page for Store B sale
        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/pos/sales/{$saleB->id}/refund")
            ->assertNotFound();

        // Staff A cannot submit refund for Store B sale
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/sales/{$saleB->id}/refunds", [
                'items' => [['pos_sale_item_id' => 1, 'quantity' => 1]],
                'refunds' => [['method' => 'cash', 'amount' => 1000]],
            ])
            ->assertNotFound();
    }

    public function test_shift_and_daily_closing_cross_store_isolation(): void
    {
        $shiftB = CashierShift::create([
            'store_id' => $this->storeB->id,
            'cashier_id' => $this->staffB->id,
            'register_name' => 'Counter 1',
            'opening_cash' => '10000.00',
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $closingB = DailyClosing::create([
            'store_id' => $this->storeB->id,
            'business_date' => today(),
            'approval_status' => 'pending',
            'opening_amount' => '10000.00',
            'expected_totals' => ['cash' => '50000.00'],
            'counted_totals' => ['cash' => '50000.00'],
            'differences' => ['cash' => '0.00'],
            'total_difference' => '0.00',
            'created_by' => $this->staffB->id,
        ]);

        // 1. Staff A cannot perform cashEvent or close Shift B (blocked as 403/404)
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/shifts/{$shiftB->id}/cash-events", [
                'type' => 'cash_in',
                'amount' => 5000,
            ])
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/shifts/{$shiftB->id}/close", [
                'actual_closing_amount' => 15000,
            ])
            ->assertForbidden();

        // 2. Manager A cannot approve Daily Closing B
        $this->actingAs($this->managerA)
            ->post("/store/{$this->storeA->slug}/pos/closing/{$closingB->id}/approve")
            ->assertNotFound();
    }

    public function test_eload_cross_store_isolation(): void
    {
        $accountB = EloadAccount::create([
            'store_id' => $this->storeB->id,
            'operator' => 'mpt',
            'name' => 'MPT Main B',
            'balance' => 500000,
            'is_active' => true,
        ]);

        // 1. Staff A submitting E-Load transaction with Store B's account ID must fail validation
        $response = $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/admin/eload", [
                'operator' => 'mpt',
                'phone_number' => '09123456789',
                'amount' => 1000,
                'eload_account_id' => $accountB->id,
            ]);

        $response->assertSessionHasErrors('eload_account_id');

        // 2. Staff A cannot refill Store B's E-Load account
        $refillRes = $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/admin/eload/refill", [
                'eload_account_id' => $accountB->id,
                'amount' => 50000,
            ]);

        $refillRes->assertSessionHasErrors('eload_account_id');
    }

    public function test_pos_web_order_fulfillment_isolation(): void
    {
        $orderB = Order::create([
            'store_id' => $this->storeB->id,
            'order_number' => 'ORD-B-999',
            'status' => 'confirmed',
            'total_amount' => '45000.00',
            'customer_name' => 'Buyer B',
            'customer_phone' => '09333333333',
            'delivery_address' => 'Yangon',
        ]);

        // Web orders JSON endpoint in Store A must not show Store B's order
        $response = $this->actingAs($this->staffA)
            ->getJson("/store/{$this->storeA->slug}/pos/web-orders?q=ORD-B-999");

        $response->assertOk();
        $this->assertEmpty($response->json('orders'));
    }
}
