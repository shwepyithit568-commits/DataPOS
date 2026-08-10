<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminSidebarNavigationUXTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store1;
    protected Store $store2;
    protected User $manager1;
    protected User $staff1;
    protected User $platformOwner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store1 = Store::create(['name' => 'Store One', 'slug' => 'store-one']);
        $this->store2 = Store::create(['name' => 'Store Two', 'slug' => 'store-two']);
        $this->store1->setting()->create(['store_name' => 'Store One', 'default_language' => 'en']);
        $this->store2->setting()->create(['store_name' => 'Store Two', 'default_language' => 'en']);

        $this->manager1 = User::factory()->create(['phone' => '09111111111']);
        $this->manager1->stores()->attach($this->store1->id, ['role' => 'store_manager']);

        $this->staff1 = User::factory()->create(['phone' => '09222222222']);
        $this->staff1->stores()->attach($this->store1->id, ['role' => 'staff']);

        $this->platformOwner = User::factory()->create([
            'phone' => '09333333333',
            'role' => 'platform_owner',
        ]);
    }

    public function test_rendered_sidebar_links_use_existing_named_routes(): void
    {
        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);

        preg_match_all('/data-route-name="([^"]+)"/', $response->getContent(), $matches);

        $this->assertNotEmpty($matches[1], 'Sidebar must render route-name metadata for links.');

        foreach ($matches[1] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Missing named route [{$routeName}].");
        }
    }

    public function test_sidebar_has_no_unavailable_placeholder_links(): void
    {
        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);
        $response->assertDontSee('href="#"', false);
        $response->assertDontSee('javascript:void', false);
        $response->assertDontSeeText('Customers');
        $response->assertDontSeeText('Activity Logs');
    }

    public function test_product_sidebar_import_and_real_toolbar_export(): void
    {
        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/products");

        $response->assertStatus(200);
        $response->assertSeeText('Product Import');
        $response->assertSeeText('Export');            // real toolbar export link
        $response->assertDontSeeText('Import / Export'); // no fake combined sidebar link
    }

    public function test_sidebar_pending_order_badge_is_store_scoped(): void
    {
        $this->createPendingOrders($this->store1, 2, 'A');
        $this->createPendingOrders($this->store2, 3, 'B');

        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);
        $response->assertSee('data-pending-order-count="2"', false);
        $response->assertDontSee('data-pending-order-count="3"', false);
    }

    public function test_platform_owner_pending_badge_uses_selected_store_only(): void
    {
        $this->createPendingOrders($this->store1, 1, 'A');
        $this->createPendingOrders($this->store2, 4, 'B');

        $response = $this->actingAs($this->platformOwner)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);
        $response->assertSee('data-pending-order-count="1"', false);
        $response->assertDontSee('data-pending-order-count="4"', false);
    }

    public function test_role_based_sidebar_visibility(): void
    {
        $managerResponse = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $managerResponse->assertStatus(200);
        $managerResponse->assertSeeText('Products');
        $managerResponse->assertSeeText('Import History');
        $managerResponse->assertSee('data-route-name="store.admin.settings.edit"', false);
        $managerResponse->assertSeeText('Home Banners');

        $staffResponse = $this->actingAs($this->staff1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $staffResponse->assertStatus(200);
        $staffResponse->assertSeeText('Products');
        $staffResponse->assertSeeText('Import History');
        $staffResponse->assertDontSee('data-route-name="store.admin.settings.edit"', false);
        $staffResponse->assertSeeText('Home Banners');

        $platformResponse = $this->actingAs($this->platformOwner)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $platformResponse->assertStatus(200);
        $platformResponse->assertSeeText('Products');
        $platformResponse->assertSeeText('Import History');
        $platformResponse->assertSee('data-route-name="store.admin.settings.edit"', false);
        $platformResponse->assertSeeText('Home Banners');
    }

    public function test_sidebar_does_not_use_unregistered_alpine_collapse_directive(): void
    {
        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);
        $response->assertDontSee('x-collapse', false);
        $response->assertSee('x-transition', false);
    }

    /** Batch 4: authorized manager gets the recovered admin layout with brand, nav label, and locale switcher. */
    public function test_admin_layout_renders_for_authorized_manager(): void
    {
        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);
        // Translated brand + navigation landmark + store name.
        $response->assertSee('aria-label="Admin navigation"', false);
        $response->assertSee('aria-label="Open menu"', false);
        $response->assertSee('aria-label="Close menu"', false);
        $response->assertSeeText('Admin Panel');
        $response->assertSeeText('Store One');
        // Locale switcher form is present.
        $response->assertSee('name="locale"', false);
    }

    /** Batch 4: a plain customer without any store role stays blocked from the admin layout. */
    public function test_customer_without_store_role_is_blocked_from_admin_layout(): void
    {
        $customer = User::factory()->create(['phone' => '09444444444', 'role' => 'customer']);

        $response = $this->actingAs($customer)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(403);
    }

    /** Batch 4: store logo renders when configured; fallback icon renders when absent. */
    public function test_store_logo_and_fallback_render_safely(): void
    {
        $this->store1->setting()->update(['logo_path' => 'store-logos/store-one.png']);

        $withLogo = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");
        $withLogo->assertStatus(200);
        $withLogo->assertSee('storage/store-logos/store-one.png', false);
        $withLogo->assertSee('alt="Store One logo"', false);

        // Store 2 has no logo configured: fallback SVG grid icon is rendered instead.
        $fallback = $this->actingAs($this->platformOwner)
            ->get("/store/{$this->store2->slug}/admin/dashboard");
        $fallback->assertStatus(200);
        $fallback->assertDontSee('store-logos', false);
        $fallback->assertSee('M4 13h6V4H4v9Zm0 7h6v-3H4v3Zm10 0h6v-9h-6v9Zm0-13h6V4h-6v3Z', false);
    }

    /** Batch 4: navigation groups and their store-scoped route links render. */
    public function test_navigation_groups_and_route_links_render(): void
    {
        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);
        foreach (['Catalog', 'Sales', 'Wholesale', 'Tools', 'Settings'] as $group) {
            $response->assertSeeText($group);
        }
        foreach ([
            'store.admin.dashboard',
            'store.admin.products.index',
            'store.admin.categories.index',
            'store.admin.brands.index',
            'store.admin.products.import',
            'store.admin.orders.index',
            'store.admin.wholesale.applications.index',
            'store.admin.glass-finder.index',
            'store.admin.import-history.index',
            'store.admin.settings.edit',
            'store.admin.banners.index',
        ] as $routeName) {
            $response->assertSee('data-route-name="' . $routeName . '"', false);
        }

        // The users link (platform owner only) renders for the platform owner.
        $ownerResponse = $this->actingAs($this->platformOwner)
            ->get("/store/{$this->store1->slug}/admin/dashboard");
        $ownerResponse->assertStatus(200);
        $ownerResponse->assertSee('data-route-name="store.admin.users.index"', false);
        $ownerResponse->assertSeeText('Users');
    }

    /** Batch 4: the active nav link exposes aria-current on every page (the
     * shared x-admin.nav-link component renders it for whichever link is
     * active — Dashboard on the dashboard page, Products on the products page). */
    public function test_active_link_state_uses_aria_current(): void
    {
        $dashboard = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");
        $dashboard->assertStatus(200);
        $dashboard->assertSee('aria-current="page"', false);

        $products = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/products");
        $products->assertStatus(200);
        // The Products submenu link is the active one here.
        $products->assertSee('aria-current="page"', false);
        // Only the active link carries it — the Dashboard link does not.
        $dashboardLink = $this->extractUntil($products->getContent(), 'data-route-name="store.admin.dashboard"', '</a>');
        $this->assertStringNotContainsString('aria-current=', $dashboardLink);
    }

    /** Batch 4: accordion groups declare aria-expanded bindings; the active group initializes open. */
    public function test_accordion_groups_expose_aria_expanded_state(): void
    {
        $dashboard = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");
        $dashboard->assertStatus(200);
        // Alpine binds aria-expanded declaratively; every group button declares the binding.
        $this->assertGreaterThanOrEqual(
            5,
            substr_count($dashboard->getContent(), ':aria-expanded="'),
            'All five accordion groups must declare an aria-expanded binding.'
        );
        // Dashboard is not a catalog path, so the Catalog group starts closed.
        $dashboard->assertSee('catalogOpen: false', false);

        // On the products page the Catalog group initializes open.
        $products = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/products");
        $products->assertStatus(200);
        $products->assertSee('catalogOpen: true', false);
    }

    /** Batch 4: mobile open/close controls and backdrop remain wired to sidebarOpen. */
    public function test_mobile_sidebar_open_close_and_backdrop(): void
    {
        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);
        $response->assertSee('aria-label="Open menu"', false);
        $response->assertSee('aria-label="Close menu"', false);
        // Backdrop closes the sidebar; nav links close it too.
        $response->assertSee('x-show="sidebarOpen"', false);
        $response->assertSee('bg-black/30', false);
        $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), 'sidebarOpen = false'));
    }

    /** Batch 4: logout remains a POST form protected by CSRF. */
    public function test_logout_remains_post_form_with_csrf(): void
    {
        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);
        $response->assertSee('method="POST"', false);
        $response->assertSee('name="_token"', false);
        $response->assertSee('/logout"', false);
    }

    /** Batch 4: sidebar labels render translated in en, my, and zh_CN with no raw keys. */
    public function test_sidebar_labels_render_in_all_supported_locales(): void
    {
        $locales = [
            'en' => [
                'admin_panel' => 'Admin Panel',
                'admin_navigation' => 'Admin navigation',
                'open_menu' => 'Open menu',
                'close_menu' => 'Close menu',
            ],
            'my' => [
                'admin_panel' => 'စီမံခန့်ခွဲမှု',
                'admin_navigation' => 'စီမံခန့်ခွဲမှု လမ်းညွှန်',
                'open_menu' => 'မီနူးဖွင့်ရန်',
                'close_menu' => 'မီနူးပိတ်ရန်',
            ],
            'zh_CN' => [
                'admin_panel' => '管理后台',
                'admin_navigation' => '后台导航',
                'open_menu' => '打开菜单',
                'close_menu' => '关闭菜单',
            ],
        ];

        foreach ($locales as $code => $labels) {
            $store = Store::create([
                'name' => "Store {$code}",
                'slug' => "store-{$code}",
            ]);
            $store->setting()->create([
                'store_name' => "Store {$code}",
                'default_language' => $code,
            ]);
            $this->manager1->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

            $response = $this->actingAs($this->manager1)
                ->get("/store/store-{$code}/admin/dashboard");

            $response->assertStatus(200);
            $response->assertSeeText($labels['admin_panel']);
            $response->assertSee('aria-label="' . $labels['admin_navigation'] . '"', false);
            $response->assertSee('aria-label="' . $labels['open_menu'] . '"', false);
            $response->assertSee('aria-label="' . $labels['close_menu'] . '"', false);
            // No raw translation key is displayed.
            $response->assertDontSee('messages.', false);
        }
    }

    /**
     * Desktop collapse toggle: renders in the sidebar header (desktop-only),
     * persists its state in localStorage, and narrows the aside to an
     * icon-only rail via the lg:w-20 binding (sidebar is visible from lg up;
     * tablets and phones use the drawer).
     */
    public function test_desktop_sidebar_collapse_toggle_renders(): void
    {
        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);

        // Desktop-only toggle button wired to the collapse handler.
        $response->assertSee('hidden lg:inline-flex', false);
        $response->assertSee('toggleSidebarCollapsed()', false);

        // State is persisted across page loads.
        $response->assertSee("localStorage.getItem('adminSidebar')", false);
        $response->assertSee("localStorage.setItem('adminSidebar'", false);

        // Collapsed aside narrows to an icon rail; expanded keeps the full width.
        $response->assertSee("sidebarCollapsed ? 'lg:w-20' : 'lg:w-72'", false);

        // Accessible label reflects the current state via translated strings.
        $response->assertSee(":aria-label=\"sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'\"", false);
    }

    /**
     * When collapsed, nav labels are hidden (lg:hidden bindings) and clicking
     * any section icon expands the sidebar and opens that section.
     */
    public function test_collapsed_sidebar_expands_on_section_icon_click(): void
    {
        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);

        // Every section button hides its label in collapsed mode...
        $this->assertGreaterThanOrEqual(
            5,
            substr_count($response->getContent(), "sidebarCollapsed ? 'lg:hidden' : ''"),
            'All five section groups must declare the collapsed label-hiding binding.'
        );

        // ...and routes every group through the single-open toggle handler
        // (which expands the sidebar first when clicked while collapsed).
        $this->assertGreaterThanOrEqual(
            5,
            substr_count($response->getContent(), "toggleGroup('"),
            'All five section groups must route through the single-open toggle handler.'
        );

        // The dashboard link also expands the sidebar when collapsed.
        $response->assertSee('expandSidebar()', false);
    }

    /** The mobile close button (drawer) is untouched by the desktop collapse feature. */
    public function test_mobile_close_button_survives_desktop_collapse_feature(): void
    {
        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);
        $response->assertSee('aria-label="Close menu"', false);
        $response->assertSee('lg:hidden inline-flex', false);
    }

    /**
     * Collapsed desktop: EVERY navigation label must hide completely — direct
     * links, accordion group labels, and submenu containers. Regression test
     * for the Home Banners link whose label was the only one without the
     * collapsed-hiding binding (it left a clipped Burmese fragment beside the
     * icon in the 80px rail).
     */
    public function test_collapsed_desktop_hides_every_nav_label(): void
    {
        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);
        $content = $response->getContent();
        $nav = $this->extractUntil($content, '<nav class=', '</nav>');

        // Inside <nav>: Dashboard + Home Banners labels (2), the six group
        // labels (6) and the six submenu containers (6) = 14 bindings.
        $this->assertGreaterThanOrEqual(
            14,
            substr_count($nav, ":class=\"sidebarCollapsed ? 'lg:hidden' : ''\""),
            'Collapsed-hiding binding must be applied to every label and submenu container.'
        );

        // Every group label AND its submenu container must carry the binding
        // (a total count can mask one missing binding when another is added).
        foreach (['catalog', 'sales', 'wholesale', 'content', 'tools', 'settings'] as $group) {
            $this->assertStringContainsString(
                "@click=\"toggleGroup('{$group}')\"",
                $nav,
                "Group [{$group}] must exist in the nav."
            );
            $container = $this->extractUntil($nav, "id=\"sidebar-sub-{$group}\"", '>');
            $this->assertStringContainsString(
                ":class=\"sidebarCollapsed ? 'lg:hidden' : ''\"",
                $container,
                "Submenu container [{$group}] must hide its labels when collapsed."
            );
        }

        // The Home Banners label must be fully hidden too (not just truncated).
        $bannerLink = $this->extractUntil($content, 'data-route-name="store.admin.banners.index"', '</a>');
        $this->assertStringContainsString(
            ":class=\"sidebarCollapsed ? 'lg:hidden' : ''\"",
            $bannerLink,
            'Home Banners label must hide completely when the sidebar is collapsed.'
        );
        // Its icon remains, so the accessible name comes from aria-label while
        // a desktop tooltip appears only in collapsed mode.
        $this->assertStringContainsString('aria-label=', $bannerLink);
        $this->assertStringContainsString(':title="sidebarCollapsed ?', $bannerLink);
    }

    /**
     * Collapsed desktop: every direct link and group button centers its icon
     * inside the 80px rail (labels hidden, icons stay centered, active
     * background stays full-width and consistent).
     */
    public function test_collapsed_desktop_centers_icons(): void
    {
        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);
        $content = $response->getContent();

        // Dashboard link, Home Banners link and the six group buttons.
        $this->assertGreaterThanOrEqual(
            8,
            substr_count($content, ":class=\"sidebarCollapsed ? 'lg:justify-center' : ''\""),
            'Every direct link and group button must center its icon in collapsed mode.'
        );

        // The Home Banners link itself must carry the centering binding.
        $bannerLink = $this->extractUntil($content, 'data-route-name="store.admin.banners.index"', '</a>');
        $this->assertStringContainsString(
            ":class=\"sidebarCollapsed ? 'lg:justify-center' : ''\"",
            $bannerLink,
            'Home Banners icon must be centered when collapsed.'
        );
    }

    /**
     * Collapsed desktop: the pending-order notification badge stays visible as
     * a corner badge (guarded by sidebarCollapsed + viewportLg so it never
     * covers the icon and never leaks into mobile/expanded layouts).
     */
    public function test_collapsed_desktop_keeps_notification_badge(): void
    {
        $this->createPendingOrders($this->store1, 2, 'A');

        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);
        $content = $response->getContent();

        // Corner badge only renders when pending orders exist; it is guarded so
        // it appears solely in collapsed desktop mode.
        $this->assertStringContainsString(
            'data-pending-order-count="2" x-cloak',
            $content,
            'Collapsed corner badge must render alongside the inline badge.'
        );
        $this->assertStringContainsString(
            "sidebarCollapsed && viewportLg ? 'inline-flex' : 'hidden'",
            $content,
            'Corner badge must only show in collapsed desktop mode.'
        );

        // No pending orders → no badge markup at all (neither corner guard nor
        // the data-pending-order-count attribute).
        Order::where('store_id', $this->store1->id)->delete();
        $empty = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");
        $empty->assertStatus(200);
        $empty->assertDontSee('data-pending-order-count=', false);
        $this->assertStringNotContainsString(
            "sidebarCollapsed && viewportLg ? 'inline-flex' : 'hidden'",
            $empty->getContent(),
            'Corner badge must not render when there are no pending orders.'
        );
    }

    /**
     * Single-open accordion: the aside defines one toggleGroup() handler that
     * closes sibling groups, closes an already-open group on re-click, and
     * expands the sidebar first when a collapsed icon is clicked. Every group
     * button routes through it and wires aria-controls to its submenu panel.
     */
    public function test_single_open_accordion_via_toggle_group(): void
    {
        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/dashboard");

        $response->assertStatus(200);
        $content = $response->getContent();

        // Handler definition retains: collapsed-click expands first, then
        // single-open semantics (close others / close self on re-click).
        $this->assertStringContainsString('toggleGroup(name)', $content);
        $this->assertStringContainsString('if (this.sidebarCollapsed) {', $content);
        $this->assertStringContainsString('this.expandSidebar();', $content);
        $this->assertStringContainsString('this.closeGroups();', $content);

        // Every group button routes through the toggle and links its panel.
        foreach (['catalog', 'sales', 'wholesale', 'content', 'tools', 'settings'] as $group) {
            $this->assertStringContainsString("@click=\"toggleGroup('{$group}')\"", $content);
            $this->assertStringContainsString("aria-controls=\"sidebar-sub-{$group}\"", $content);
            $this->assertStringContainsString("id=\"sidebar-sub-{$group}\"", $content);
            $this->assertStringContainsString(":aria-expanded=\"{$group}Open.toString()\"", $content);
        }

        // Route-aware initial open state is preserved (dashboard = none open).
        $response->assertSee('catalogOpen: false', false);
    }

    /**
     * Extract the substring starting at $needle up to (but not including) the
     * first occurrence of $until after it. Returns '' when $needle is absent.
     */
    private function extractUntil(string $haystack, string $needle, string $until): string
    {
        $start = strpos($haystack, $needle);
        if ($start === false) {
            return '';
        }

        $end = strpos($haystack, $until, $start);
        if ($end === false) {
            return substr($haystack, $start);
        }

        return substr($haystack, $start, $end - $start);
    }

    private function createPendingOrders(Store $store, int $count, string $prefix): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Order::create([
                'store_id' => $store->id,
                'order_number' => "ORD-{$prefix}-{$i}",
                'customer_name' => "Buyer {$prefix}{$i}",
                'customer_phone' => "0999999999{$i}",
                'customer_address' => 'Yangon',
                'contact_channel' => 'viber',
                'pricing_type' => 'retail',
                'total_amount' => 5000,
                'status' => 'pending_contact',
            ]);
        }
    }
}
