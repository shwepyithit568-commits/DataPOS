<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\User;
use App\Services\AdminNavigationService;
use App\Services\StorePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The menu and the routes must agree.
 *
 * The per-role default permission sets left out keys that the pages behind the
 * menu entries already demand (P&L export, the membership page, the business
 * reconciliation report), and two owner-only settings pages were listed for
 * managers — so the sidebar offered links that answered 403. Measured on the
 * 2026-09-18 UAT day run: four 403 links plus 403 export buttons.
 */
class MenuRoutePermissionAgreementTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        StorePermissionService::invalidateCache();

        $this->store = Store::create([
            'name' => 'Menu Agreement Shop',
            'slug' => 'menu-agreement-shop',
            'is_active' => true,
        ]);

        // Role templates are seeded per store; this creates owner/manager/
        // cashier/accountant/stock keeper rows with their permission sets.
        StaffRole::bootstrapDefaultRoles($this->store);
    }

    private function member(string $storeRole, string $name): User
    {
        $user = User::create([
            'name' => $name,
            'phone' => '09' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $staffRole = StaffRole::where('store_id', $this->store->id)
            ->where('slug', $storeRole)
            ->first();

        $user->stores()->attach($this->store->id, [
            'role' => in_array($storeRole, ['store_owner', 'store_manager'], true) ? $storeRole : 'staff',
            'status' => 'active',
            'staff_role_id' => $staffRole?->id,
        ]);

        return $user;
    }

    public function test_manager_role_holds_every_key_its_menu_pages_require(): void
    {
        $managerRole = StaffRole::where('store_id', $this->store->id)->where('slug', 'store_manager')->sole();

        foreach (['membership.view', 'stock_reconciliation.view', 'profit_loss.view', 'profit_loss.export'] as $key) {
            $this->assertContains($key, $managerRole->permissions, "store_manager is missing {$key}");
        }
    }

    public function test_accountant_role_can_export_the_profit_and_loss_statement(): void
    {
        $accountantRole = StaffRole::where('store_id', $this->store->id)->where('slug', 'accountant')->sole();

        $this->assertContains('profit_loss.view', $accountantRole->permissions);
        $this->assertContains('profit_loss.export', $accountantRole->permissions);
    }

    public function test_owner_only_settings_pages_are_not_offered_to_a_manager(): void
    {
        $manager = $this->member('store_manager', 'Manager Mg');

        $nav = app(AdminNavigationService::class)->getFilteredNavigationTree($manager, $this->store);

        // json_encode escapes slashes by default, which would make the
        // "not contains" assertion below pass without checking anything.
        $html = json_encode($nav, JSON_UNESCAPED_SLASHES);

        $this->assertStringNotContainsString('/admin/settings/modules', $html);
        $this->assertStringNotContainsString('/admin/settings/channels', $html);

        // ...while the pages this role can really open stay listed.
        $this->assertStringContainsString('/admin/membership', $html);
        $this->assertStringContainsString('/pos/reports/reconciliation', $html);
    }

    public function test_owner_only_settings_pages_are_still_offered_to_the_owner(): void
    {
        $owner = $this->member('store_owner', 'Owner Ko');

        $nav = app(AdminNavigationService::class)->getFilteredNavigationTree($owner, $this->store);
        $html = json_encode($nav, JSON_UNESCAPED_SLASHES);

        $this->assertStringContainsString('/admin/settings/modules', $html);
        $this->assertStringContainsString('/admin/settings/channels', $html);
    }

    public function test_the_menu_and_the_route_use_the_same_key_for_membership(): void
    {
        $route = app('router')->getRoutes()->getByName('store.admin.membership.index');
        $this->assertNotNull($route);

        $middleware = collect($route->gatherMiddleware())->implode('|');
        $this->assertStringContainsString('store.permission:membership.view', $middleware);

        // ...and the nav node must not be gated on some other module's keys.
        $source = file_get_contents(app_path('Services/AdminNavigationService.php'));
        $this->assertStringContainsString("'route_name' => 'store.admin.membership.index'", $source);
        $this->assertStringContainsString("'required_permissions' => ['membership.view']", $source);
    }

    public function test_the_menu_and_the_route_use_the_same_key_for_reconciliation(): void
    {
        $route = app('router')->getRoutes()->getByName('pos.reports.reconciliation');
        $this->assertNotNull($route);

        $middleware = collect($route->gatherMiddleware())->implode('|');
        $this->assertStringContainsString('store.permission:stock_reconciliation.view', $middleware);

        $source = file_get_contents(app_path('Services/AdminNavigationService.php'));
        $this->assertStringContainsString("'required_permissions' => ['stock_reconciliation.view'],", $source);
    }

    // ── The sync command for stores that already exist ────────────────────────

    public function test_sync_command_adds_the_missing_keys_and_is_idempotent(): void
    {
        // Simulate a store whose manager role was created before the keys existed.
        $managerRole = StaffRole::where('store_id', $this->store->id)->where('slug', 'store_manager')->sole();
        $managerRole->permissions = array_values(array_diff($managerRole->permissions, [
            'membership.view', 'profit_loss.export', 'stock_reconciliation.view',
        ]));
        $managerRole->save();

        $accountantRole = StaffRole::where('store_id', $this->store->id)->where('slug', 'accountant')->sole();
        $accountantRole->permissions = array_values(array_diff($accountantRole->permissions, ['profit_loss.export']));
        $accountantRole->save();

        Artisan::call('staff:sync-role-permissions', ['--store' => $this->store->id]);

        $this->assertContains('membership.view', $managerRole->fresh()->permissions);
        $this->assertContains('profit_loss.export', $managerRole->fresh()->permissions);
        $this->assertContains('stock_reconciliation.view', $managerRole->fresh()->permissions);
        $this->assertContains('profit_loss.export', $accountantRole->fresh()->permissions);

        $this->assertSame(2, AuditLog::where('action', 'staff_permissions.sync_route_keys')->count());

        // Second run: nothing left to add, so nothing is written.
        Artisan::call('staff:sync-role-permissions', ['--store' => $this->store->id]);
        $this->assertSame(2, AuditLog::where('action', 'staff_permissions.sync_route_keys')->count());
    }

    public function test_sync_command_dry_run_writes_nothing(): void
    {
        $managerRole = StaffRole::where('store_id', $this->store->id)->where('slug', 'store_manager')->sole();
        $before = array_values(array_diff($managerRole->permissions, [
            'membership.view', 'profit_loss.export', 'stock_reconciliation.view',
        ]));
        $managerRole->permissions = $before;
        $managerRole->save();

        Artisan::call('staff:sync-role-permissions', ['--store' => $this->store->id, '--dry-run' => true]);

        $this->assertSame($before, $managerRole->fresh()->permissions);
        $this->assertSame(0, AuditLog::where('action', 'staff_permissions.sync_route_keys')->count());
    }

    // ── Dashboard revenue ─────────────────────────────────────────────────────

    public function test_unconfirmed_online_orders_are_not_reported_as_revenue(): void
    {
        $owner = $this->member('store_owner', 'Owner Ko');

        $this->order('pending_contact', '10000.00');
        $this->order('confirmed', '25000.00');
        $this->order('cancelled', '5000.00');

        $response = $this->actingAs($owner)->get("/store/{$this->store->slug}/admin/dashboard");
        $response->assertOk();

        $revenue = $response->viewData('stats')['todayRevenue'] ?? null;
        $this->assertNotNull($revenue, 'dashboard stats were not passed to the view');
        $this->assertSame(25000.0, (float) $revenue);
    }

    private function order(string $status, string $total): Order
    {
        return Order::create([
            'store_id' => $this->store->id,
            'order_number' => 'WEB-' . Str::random(6),
            'status' => $status,
            'total_amount' => $total,
            'customer_name' => 'Web Customer',
            'customer_phone' => '09971111111',
        ]);
    }
}
