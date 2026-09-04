<?php

namespace Tests\Feature;

use App\BusinessProfiles\BusinessProfile;
use App\Capabilities\Capability;
use App\Models\Order;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\User;
use App\Services\AdminNavigationService;
use App\Services\StorePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase5NavigationArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected AdminNavigationService $navService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->navService = app(AdminNavigationService::class);
        StorePermissionService::invalidateCache();
    }

    private function createStore(array $attributes = []): Store
    {
        return Store::create(array_merge([
            'name' => 'Nav Test Store ' . Str::random(4),
            'slug' => 'nav-store-' . Str::lower(Str::random(6)),
            'is_active' => true,
            'business_profile' => 'retail_store',
            'operation_mode' => BusinessProfile::MODE_OMNICHANNEL,
        ], $attributes));
    }

    private function createStaff(Store $store, string $role = 'staff', array $permissions = []): User
    {
        $user = User::create([
            'name' => 'User ' . Str::random(4),
            'phone' => '09' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $staffRole = null;
        if (!empty($permissions)) {
            $staffRole = StaffRole::create([
                'store_id' => $store->id,
                'name' => 'Custom Role ' . Str::random(4),
                'slug' => 'custom-role-' . Str::lower(Str::random(4)),
                'permissions' => $permissions,
                'is_system' => false,
                'is_active' => true,
            ]);
        }

        $user->stores()->attach($store->id, [
            'role' => $role,
            'status' => 'active',
            'staff_role_id' => $staffRole?->id,
        ]);

        return $user;
    }

    private function createPlatformOwner(): User
    {
        return User::create([
            'name' => 'Super Platform Admin',
            'phone' => '09' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'platform_owner',
        ]);
    }

    /**
     * Rule: Platform routes never resolve store badges/counts or store menus.
     */
    public function test_platform_routes_return_platform_only_navigation_without_store_queries(): void
    {
        $platformOwner = $this->createPlatformOwner();
        $store = $this->createStore();

        // Seed an order that would count in store scope
        Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-1001',
            'customer_name' => 'Daw Mya',
            'customer_phone' => '0912345678',
            'status' => 'pending_contact',
            'total_amount' => 50000,
        ]);

        $request = Request::create('/admin/dashboard', 'GET');

        // Track executed database queries
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $tree = $this->navService->getFilteredNavigationTree($platformOwner, null, $request);

        $keys = array_column($tree, 'key');
        $this->assertContains('platform_dashboard', $keys);
        $this->assertContains('platform_stores', $keys);
        $this->assertContains('platform_theme_governance', $keys);

        // Store groups must NOT appear
        $this->assertNotContains('pos', $keys);
        $this->assertNotContains('inventory', $keys);
        $this->assertNotContains('ecommerce', $keys);

        // No queries should be run on the orders table
        foreach ($queries as $sql) {
            $this->assertStringNotContainsString('orders', strtolower($sql));
        }
    }

    /**
     * Rule: Store routes require active StoreContext and membership.
     */
    public function test_store_routes_require_membership(): void
    {
        $store = $this->createStore();
        $outsider = User::create([
            'name' => 'Outsider',
            'phone' => '09' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $request = Request::create("/store/{$store->slug}/admin/dashboard", 'GET');
        $request->route = (new \Illuminate\Routing\Route('GET', 'store/{store_slug}/admin/dashboard', []))->bind($request);

        $tree = $this->navService->getFilteredNavigationTree($outsider, $store, $request);
        $this->assertEmpty($tree, 'Users without membership in store must receive an empty navigation tree.');
    }

    /**
     * Rule: Empty groups disappear.
     */
    public function test_empty_groups_disappear_when_children_not_permitted(): void
    {
        $store = $this->createStore();
        // Staff with only POS sales permission
        $cashier = $this->createStaff($store, 'staff', ['pos_sales.view']);

        $request = Request::create("/store/{$store->slug}/admin/dashboard", 'GET');
        $request->route = (new \Illuminate\Routing\Route('GET', 'store/{store_slug}/admin/dashboard', []))->bind($request);

        $tree = $this->navService->getFilteredNavigationTree($cashier, $store, $request);
        $groupKeys = array_column($tree, 'key');

        // POS should be visible because pos_sales is permitted
        $this->assertContains('pos', $groupKeys);

        // Groups where cashier has zero permissions must disappear
        $this->assertNotContains('purchasing', $groupKeys);
        $this->assertNotContains('finance', $groupKeys);
        $this->assertNotContains('service', $groupKeys);
        $this->assertNotContains('setup', $groupKeys);
    }

    /**
     * Rule: Badge/KPI resolvers execute ONLY after scope/channel/capability/permission checks.
     * Query listener proves disabled badge/KPI queries do not run.
     */
    public function test_lazy_badge_resolvers_do_not_query_database_when_module_disabled(): void
    {
        // Store with POS Only (online_store = false)
        $store = $this->createStore([
            'operation_mode' => BusinessProfile::MODE_POS_ONLY,
            'sales_channels' => [
                Store::CHANNEL_ONLINE_STORE => false,
                Store::CHANNEL_ONLINE_ORDERING => false,
            ],
        ]);
        $owner = $this->createStaff($store, 'store_owner');

        // Seed an order
        Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-9999',
            'customer_name' => 'U Ba',
            'customer_phone' => '0911122233',
            'status' => 'pending_contact',
            'total_amount' => 75000,
        ]);

        $request = Request::create("/store/{$store->slug}/admin/dashboard", 'GET');
        $request->route = (new \Illuminate\Routing\Route('GET', 'store/{store_slug}/admin/dashboard', []))->bind($request);

        $orderQueries = 0;
        DB::listen(function ($query) use (&$orderQueries) {
            if (str_contains(strtolower($query->sql), 'orders') && str_contains(strtolower($query->sql), 'count')) {
                $orderQueries++;
            }
        });

        $tree = $this->navService->getFilteredNavigationTree($owner, $store, $request);
        $groupKeys = array_column($tree, 'key');

        $this->assertNotContains('ecommerce', $groupKeys, 'Ecommerce group must be hidden when online_store channel is false.');
        $this->assertSame(0, $orderQueries, 'Pending order count query must NOT execute when online_store is disabled.');
    }

    /**
     * Rule: When module is enabled and permitted, lazy badge executes and attaches count.
     */
    public function test_lazy_badge_resolves_when_module_enabled_and_permitted(): void
    {
        $store = $this->createStore([
            'operation_mode' => BusinessProfile::MODE_OMNICHANNEL,
        ]);
        $owner = $this->createStaff($store, 'store_owner');

        Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-1234',
            'customer_name' => 'Ko Aung',
            'customer_phone' => '0977889900',
            'status' => 'pending_contact',
            'total_amount' => 45000,
        ]);

        $request = Request::create("/store/{$store->slug}/admin/dashboard", 'GET');
        $request->route = (new \Illuminate\Routing\Route('GET', 'store/{store_slug}/admin/dashboard', []))->bind($request);

        $tree = $this->navService->getFilteredNavigationTree($owner, $store, $request);
        $ecommerceGroup = collect($tree)->firstWhere('key', 'ecommerce');

        $this->assertNotNull($ecommerceGroup);
        $this->assertEquals(1, $ecommerceGroup['badge'], 'Ecommerce group badge must reflect the pending order count.');

        $ordersChild = collect($ecommerceGroup['children'])->firstWhere('key', 'orders');
        $this->assertNotNull($ordersChild);
        $this->assertEquals(1, $ordersChild['badge']);
    }

    /**
     * Rule: POS main navigation must remain usable when cashier shifts are disabled.
     */
    public function test_pos_main_navigation_remains_usable_when_cashier_shifts_disabled(): void
    {
        $store = $this->createStore([
            'capabilities_override' => [
                Capability::OPERATIONS_CASHIER_SHIFTS => false,
            ],
        ]);
        $cashier = $this->createStaff($store, 'staff', ['pos_sales.view']);

        $request = Request::create("/store/{$store->slug}/admin/dashboard", 'GET');
        $request->route = (new \Illuminate\Routing\Route('GET', 'store/{store_slug}/admin/dashboard', []))->bind($request);

        $tree = $this->navService->getFilteredNavigationTree($cashier, $store, $request);
        $posGroup = collect($tree)->firstWhere('key', 'pos');

        $this->assertNotNull($posGroup);
        $posChildrenKeys = array_column($posGroup['children'], 'key');

        // POS sale must be available
        $this->assertContains('pos_sale', $posChildrenKeys);

        // POS closing must NOT be available because shifts capability is disabled
        $this->assertNotContains('pos_closing', $posChildrenKeys);
    }

    /**
     * Rule: Every returned item has a valid, absolute/generated URL and never a hash link '#'.
     */
    public function test_filtered_tree_contains_no_hash_links(): void
    {
        $store = $this->createStore();
        $owner = $this->createStaff($store, 'store_owner');

        $request = Request::create("/store/{$store->slug}/admin/dashboard", 'GET');
        $request->route = (new \Illuminate\Routing\Route('GET', 'store/{store_slug}/admin/dashboard', []))->bind($request);

        $tree = $this->navService->getFilteredNavigationTree($owner, $store, $request);

        foreach ($tree as $item) {
            if ($item['type'] === 'link') {
                $this->assertNotEquals('#', $item['url'], "Link {$item['key']} must not have a hash URL.");
            } elseif ($item['type'] === 'group') {
                foreach ($item['children'] as $child) {
                    $this->assertNotEquals('#', $child['url'], "Child link {$child['key']} in group {$item['key']} must not have a hash URL.");
                }
            }
        }
    }

    /**
     * Rule: Blade admin layout renders filtered navigation tree properly.
     */
    public function test_blade_admin_layout_renders_tree_successfully(): void
    {
        $store = $this->createStore();
        $owner = $this->createStaff($store, 'store_owner');

        $response = $this->actingAs($owner)
            ->get("/store/{$store->slug}/admin/dashboard");

        $response->assertOk();
        $response->assertSee(__('messages.admin_panel'), false);
        $response->assertSee(__('messages.sidebar_pos_group'), false);
        $response->assertSee(__('messages.sidebar_inventory'), false);
    }
}
