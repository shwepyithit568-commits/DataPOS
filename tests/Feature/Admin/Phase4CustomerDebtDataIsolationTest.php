<?php

namespace Tests\Feature\Admin;

use App\Models\MembershipTier;
use App\Models\Store;
use App\Models\User;
use App\Models\WholesaleApplication;
use App\POS\Models\CustomerLedgerEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4CustomerDebtDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Store $storeA;
    protected Store $storeB;
    protected User $staffA;
    protected User $staffB;
    protected User $customerA;
    protected User $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::create(['name' => 'Store Alpha', 'slug' => 'store-alpha', 'is_active' => true]);
        $this->storeB = Store::create(['name' => 'Store Beta', 'slug' => 'store-beta', 'is_active' => true]);

        $this->staffA = User::create(['name' => 'Staff Alpha', 'phone' => '09111111111', 'password' => bcrypt('password')]);
        $this->staffB = User::create(['name' => 'Staff Beta', 'phone' => '09222222222', 'password' => bcrypt('password')]);

        $this->storeA->users()->attach($this->staffA->id, ['role' => 'store_manager', 'status' => 'active']);
        $this->storeB->users()->attach($this->staffB->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->customerA = User::create(['name' => 'Customer Alpha', 'phone' => '09333333333', 'password' => bcrypt('password')]);
        $this->customerB = User::create(['name' => 'Customer Beta', 'phone' => '09444444444', 'password' => bcrypt('password')]);

        $this->customerA->stores()->attach($this->storeA->id, ['role' => 'retail_customer', 'status' => 'active']);
        $this->customerB->stores()->attach($this->storeB->id, ['role' => 'retail_customer', 'status' => 'active']);
    }

    public function test_customer_directory_cross_store_isolation(): void
    {
        // 1. Directory listing in Store A sees Customer Alpha but not Customer Beta
        $response = $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/customers");

        $response->assertOk();
        $response->assertSee('Customer Alpha');
        $response->assertDontSee('Customer Beta');

        // 2. Staff A cannot view or update Customer Beta
        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/customers/{$this->customerB->id}")
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/admin/customers/{$this->customerB->id}/update", [
                'name' => 'Hacked Beta',
                'phone' => '09444444444',
                'role' => 'retail_customer',
                'status' => 'active',
            ])
            ->assertNotFound();
    }

    public function test_customer_receivables_and_debt_collection_isolation(): void
    {
        // Customer A owes Store A 15,000 Ks
        CustomerLedgerEntry::create([
            'store_id' => $this->storeA->id,
            'customer_id' => $this->customerA->id,
            'type' => CustomerLedgerEntry::TYPE_SALE_DEBT,
            'amount' => '15000.00',
            'client_transaction_id' => 'deb:a1',
        ]);

        // Customer B owes Store B 50,000 Ks
        CustomerLedgerEntry::create([
            'store_id' => $this->storeB->id,
            'customer_id' => $this->customerB->id,
            'type' => CustomerLedgerEntry::TYPE_SALE_DEBT,
            'amount' => '50000.00',
            'client_transaction_id' => 'deb:b1',
        ]);

        // 1. Store A Receivables list shows Customer A and 15,000 Ks but NOT Customer B / 50,000 Ks
        $response = $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/receivables");

        $response->assertOk();
        $response->assertSee('Customer Alpha');
        $response->assertDontSee('Customer Beta');

        // 2. Staff A cannot view or collect debt for Customer B
        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/receivables/{$this->customerB->id}")
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/admin/receivables/{$this->customerB->id}/collect", [
                'amount' => 5000,
            ])
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/receivables/{$this->customerB->id}/statement")
            ->assertNotFound();
    }

    public function test_wholesale_application_cross_store_isolation(): void
    {
        $appB = WholesaleApplication::create([
            'store_id' => $this->storeB->id,
            'user_id' => $this->customerB->id,
            'business_name' => 'Beta Wholesale Enterprise',
            'phone' => '09444444444',
            'status' => 'pending',
        ]);

        // Staff A cannot view, print, or approve Store B's wholesale application
        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/wholesale/applications/{$appB->id}")
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/wholesale/applications/{$appB->id}/print")
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->patch("/store/{$this->storeA->slug}/admin/wholesale/applications/{$appB->id}", [
                'status' => 'approved',
            ])
            ->assertForbidden();
    }

    public function test_membership_tiers_and_points_cross_store_isolation(): void
    {
        $tierA = MembershipTier::create([
            'store_id' => $this->storeA->id,
            'name' => 'Gold Alpha',
            'code' => 'GOLD_A',
            'min_spending' => 100000,
            'discount_percent' => 5,
            'point_multiplier' => 1.5,
            'badge_color' => 'amber',
            'is_active' => true,
        ]);

        $tierB = MembershipTier::create([
            'store_id' => $this->storeB->id,
            'name' => 'Platinum Beta',
            'code' => 'PLAT_B',
            'min_spending' => 500000,
            'discount_percent' => 10,
            'point_multiplier' => 2.0,
            'badge_color' => 'purple',
            'is_active' => true,
        ]);

        // 1. Staff A cannot update or delete Store B's tier
        $this->actingAs($this->staffA)
            ->put("/store/{$this->storeA->slug}/admin/membership/tiers/{$tierB->id}", [
                'name' => 'Hacked Tier',
                'code' => 'HACKED',
                'min_spending' => 0,
                'discount_percent' => 50,
                'point_multiplier' => 5,
                'badge_color' => 'rose',
            ])
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->delete("/store/{$this->storeA->slug}/admin/membership/tiers/{$tierB->id}")
            ->assertNotFound();

        // 2. Staff A cannot assign Store B's tier to Customer A
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/admin/membership/assign-tier", [
                'customer_id' => $this->customerA->id,
                'tier_id' => $tierB->id, // foreign tier
            ])
            ->assertSessionHasErrors('tier_id');
    }
}
