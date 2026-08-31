<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the `finance_access` middleware: finance-sensitive pages (P&L,
 * receivables, expenses, cash/bank transactions) must be server-side denied to
 * plain staff/cashier while remaining open to the store manager, and the POS
 * back-office must keep working for staff. (audit §5.3 / §15.6)
 */
class FinanceAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Finance Access Store',
            'slug' => 'finance-access-store',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create(['role' => 'store_manager']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create(['role' => 'staff']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);
    }

    public static function financeRoutes(): array
    {
        return [
            'profit_loss' => 'store.admin.profit_loss.index',
            'receivables' => 'store.admin.receivables.index',
            'expenses' => 'store.admin.expenses.index',
            'transactions' => 'store.admin.transactions.index',
        ];
    }

    public function test_staff_is_server_side_denied_from_finance_pages(): void
    {
        foreach (static::financeRoutes() as $key => $route) {
            $response = $this->actingAs($this->staff)
                ->get(route($route, ['store_slug' => $this->store->slug]));

            $response->assertStatus(403, "staff should be denied from {$key}");
        }
    }

    public function test_manager_can_access_finance_pages(): void
    {
        foreach (static::financeRoutes() as $key => $route) {
            $response = $this->actingAs($this->manager)
                ->get(route($route, ['store_slug' => $this->store->slug]));

            $response->assertStatus(200, "manager should reach {$key}");
        }
    }

    public function test_staff_can_open_pos_backoffice(): void
    {
        $response = $this->actingAs($this->staff)
            ->get("/store/{$this->store->slug}/pos");

        $response->assertStatus(200);
    }

    public function test_customer_is_denied_from_finance_pages(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)
            ->get(route('store.admin.profit_loss.index', ['store_slug' => $this->store->slug]));

        // Not part of the store admin roles → denied by EnsureStoreAccess before
        // finance_access even runs (400 = no store context / 403 = no role).
        $response->assertStatus(403);
    }
}