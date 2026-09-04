<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Services\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminSidebarScopeAndRoleTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store1;
    protected Store $store2;
    protected User $platformOwner;
    protected User $storeOwner;
    protected User $storeManager;
    protected User $cashier;
    protected User $regularStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store1 = Store::create(['name' => 'Flagship Store', 'slug' => 'flagship-store', 'is_active' => true]);
        $this->store2 = Store::create(['name' => 'Branch Store', 'slug' => 'branch-store', 'is_active' => true]);

        $this->store1->setting()->create(['store_name' => 'Flagship Store', 'default_language' => 'en']);
        $this->store2->setting()->create(['store_name' => 'Branch Store', 'default_language' => 'en']);

        $this->platformOwner = User::factory()->create([
            'phone' => '09100000001',
            'role' => 'platform_owner',
        ]);

        $this->storeOwner = User::factory()->create(['phone' => '09100000002']);
        $this->storeOwner->stores()->attach($this->store1->id, ['role' => 'store_owner', 'status' => 'active']);

        $this->storeManager = User::factory()->create(['phone' => '09100000003']);
        $this->storeManager->stores()->attach($this->store1->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->cashier = User::factory()->create(['phone' => '09100000004']);
        $this->cashier->stores()->attach($this->store1->id, ['role' => 'cashier', 'status' => 'active']);

        $this->regularStaff = User::factory()->create(['phone' => '09100000005']);
        $this->regularStaff->stores()->attach($this->store1->id, ['role' => 'staff', 'status' => 'active']);
    }

    /**
     * 1. Platform Owner can access platform stores and sees ONLY platform navigation.
     */
    public function test_platform_owner_sees_only_platform_scope_navigation_on_admin_routes(): void
    {
        $response = $this->actingAs($this->platformOwner)
            ->get('/admin/stores');

        $response->assertOk();
        $response->assertSee('data-scope="platform"', false);
        $response->assertDontSee('data-scope="store"', false);

        // Platform links must be visible
        $response->assertSee('data-route-name="admin.dashboard"', false);
        $response->assertSee('data-route-name="admin.stores.index"', false);
        $response->assertSee('data-route-name="admin.theme-governance.index"', false);

        // Store-scoped links must NOT bleed into platform scope
        $response->assertDontSee('data-route-name="store.admin.products.index"', false);
        $response->assertDontSee('data-route-name="pos.index"', false);
        $response->assertDontSee('data-route-name="store.admin.settings.edit"', false);
        $response->assertDontSee('data-route-name="store.admin.pages.index"', false);
        $response->assertDontSee('data-route-name="store.admin.navigation.index"', false);
    }

    /**
     * 2. Non-platform users receive 403 on platform routes.
     */
    public function test_non_platform_user_receives_403_on_platform_routes(): void
    {
        // Store manager cannot access platform stores
        $response = $this->actingAs($this->storeManager)
            ->get('/admin/stores');
        $response->assertStatus(403);

        // Store manager cannot access platform global dashboard
        $responseDashboard = $this->actingAs($this->storeManager)
            ->get('/admin/dashboard');
        $responseDashboard->assertStatus(403);

        // Regular staff cannot access platform routes
        $responseStaff = $this->actingAs($this->regularStaff)
            ->get('/admin/stores');
        $responseStaff->assertStatus(403);
    }

    /**
     * 3. Platform route does not inherit a StoreContext.
     */
    public function test_platform_route_does_not_accidentally_inherit_store_context(): void
    {
        $response = $this->actingAs($this->platformOwner)
            ->get('/admin/stores');

        $response->assertOk();

        // Check StoreContext in service container
        $context = app(StoreContext::class);
        $this->assertNull($context->getStore(), 'Platform routes should not set store context.');
    }

    /**
     * 4. Store Owner sees authorized store groups and not platform navigation.
     */
    public function test_store_owner_sees_authorized_store_groups_and_not_platform_navigation(): void
    {
        $response = $this->actingAs($this->storeOwner)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertOk();
        $response->assertSee('data-scope="store"', false);
        $response->assertDontSee('data-scope="platform"', false);

        // Store groups are rendered
        $response->assertSee('data-route-name="store.admin.dashboard"', false);
        $response->assertSee('data-route-name="pos.index"', false);
        $response->assertSee('data-route-name="store.admin.products.index"', false);
        $response->assertSee('data-route-name="store.admin.settings.edit"', false);

        // Platform links must NOT render in store scope
        $response->assertDontSee('data-route-name="admin.stores.index"', false);
        $response->assertDontSee('data-route-name="admin.theme-governance.index"', false);
    }

    /**
     * 5. Role-based isolation: Store Manager cannot see platform-owner-only items.
     */
    public function test_store_manager_cannot_see_platform_only_user_management(): void
    {
        $response = $this->actingAs($this->storeManager)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertOk();
        // store.admin.users.index is platform owner only in store scope
        $response->assertDontSee('data-route-name="store.admin.users.index"', false);
    }

    /**
     * 6. Store Isolation: Store 1 manager cannot access Store 2 admin pages.
     */
    public function test_store_isolation_blocks_cross_store_access(): void
    {
        // Attempt to access Store 2 dashboard
        $response = $this->actingAs($this->storeManager)
            ->get("/store/{$this->store2->slug}/admin/dashboard");
        $response->assertStatus(403);

        // Attempt to access Store 2 storefront pages
        $responsePages = $this->actingAs($this->storeManager)
            ->get("/store/{$this->store2->slug}/admin/pages");
        $responsePages->assertStatus(403);

        // Attempt to access Store 2 storefront navigation
        $responseNav = $this->actingAs($this->storeManager)
            ->get("/store/{$this->store2->slug}/admin/navigation");
        $responseNav->assertStatus(403);
    }

    /**
     * 7. Sidebar markup compliance: no hash links, correct accessibility tags.
     */
    public function test_sidebar_markup_has_no_placeholder_or_hash_links(): void
    {
        $response = $this->actingAs($this->storeManager)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertOk();
        $content = $response->getContent();

        // No placeholder hrefs
        $this->assertStringNotContainsString('href="#"', $content);
        $this->assertStringNotContainsString('href="javascript:void(0)"', $content);
        $this->assertStringNotContainsString('href="javascript:;"', $content);

        // Active link exposes aria-current
        $response->assertSee('aria-current="page"', false);

        // Accordion triggers expose aria-expanded
        $response->assertSee('aria-expanded="', false);

        // Accordion triggers expose aria-controls
        $response->assertSee('aria-controls="', false);
    }

    /**
     * 8. Every rendered link uses an existing named route.
     */
    public function test_all_rendered_sidebar_links_have_valid_named_routes(): void
    {
        $response = $this->actingAs($this->storeOwner)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertOk();

        preg_match_all('/data-route-name="([^"]+)"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Missing named route: {$routeName}");
        }
    }
}
