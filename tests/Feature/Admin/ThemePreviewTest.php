<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\Services\ThemeDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase T3 — Real Isolated Preview
 *
 * Core invariant: the authenticated preview route renders the production
 * storefront with the DRAFT config; the published storefront_settings and the
 * anonymous storefront response are never affected.
 */
class ThemePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User  $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'slug'      => 'preview-test-store',
            'name'      => 'Preview Test Store',
            'is_active' => true,
        ]);

        StorefrontSetting::create([
            'store_id'            => $this->store->id,
            'store_name'          => 'Preview Test Store',
            'theme_preset'        => 'marketplace_pro',
            'theme_primary_color' => '#0ea5e9',
            'theme_accent_color'  => '#7c3aed',
            'theme_header_bg'     => '#ffffff',
            'theme_body_bg'       => '#f8fafc',
            'theme_glow_style'    => 'vivid',
            'theme_dark_mode'     => 'auto',
            'font_preset'         => 'outfit',
            'grid_density'        => 'compact',
        ]);

        $this->manager = User::create([
            'name'     => 'Preview Manager',
            'phone'    => '09222334455',
            'password' => bcrypt('password'),
            'role'     => 'customer',
        ]);

        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    private function previewUrl(): string
    {
        return route('store.admin.appearance.preview', ['store_slug' => $this->store->slug]);
    }

    /**
     * Assert a CSS custom property is rendered with the expected value,
     * tolerant of the whitespace Blade leaves between `--var:` and the value.
     */
    private function assertCssVar(string $content, string $var, string $expected): void
    {
        $this->assertSame(
            1,
            preg_match('/' . preg_quote($var, '/') . ':\s*' . preg_quote($expected, '/') . '/', $content),
            "Expected {$var}: {$expected} in rendered CSS."
        );
    }

    // -------------------------------------------------------------------------
    // 1. Preview renders draft tokens with non-cacheable headers
    // -------------------------------------------------------------------------

    public function test_manager_can_preview_draft_colors_with_no_store_headers(): void
    {
        $service = app(ThemeDraftService::class);
        $draft   = $service->getOrCreate($this->store, $this->manager);

        $service->save($this->store, [
            'theme_preset'        => 'retail_trust',
            'theme_primary_color' => '#2563eb',
            'theme_accent_color'  => '#f59e0b',
        ], $draft->lock_version, $this->manager);

        $response = $this->actingAs($this->manager)->get($this->previewUrl());

        $response->assertOk();
        $content = $response->getContent();

        // Draft tokens are rendered into the CSS custom properties
        $this->assertCssVar($content, '--sf-primary', '#2563eb');
        $this->assertCssVar($content, '--sf-accent', '#f59e0b');

        // Never cached by browsers/proxies, never indexed by search engines
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control', ''));
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        // The published setting must remain untouched
        $setting = StorefrontSetting::where('store_id', $this->store->id)->first();
        $this->assertSame('marketplace_pro', $setting->theme_preset);
        $this->assertSame('#0ea5e9', $setting->theme_primary_color);
    }

    // -------------------------------------------------------------------------
    // 2. Draft visible in preview, published visible to anonymous customers
    // -------------------------------------------------------------------------

    public function test_preview_shows_draft_but_anonymous_storefront_keeps_published(): void
    {
        $service = app(ThemeDraftService::class);
        $draft   = $service->getOrCreate($this->store, $this->manager);

        $service->save($this->store, [
            'theme_preset' => 'midnight_tech',
        ], $draft->lock_version, $this->manager);

        // Preview (authenticated) → draft tokens
        $preview = $this->actingAs($this->manager)->get($this->previewUrl());
        $preview->assertOk();
        $this->assertCssVar($preview->getContent(), '--sf-primary', '#38bdf8');

        // Anonymous storefront home → published tokens, draft must NOT leak.
        // (--sf-primary is the authoritative token; the layout also contains a
        // static <meta name="theme-color"> tag, so we only assert the CSS var.)
        $home = $this->get('/store/' . $this->store->slug);
        $home->assertOk();
        $this->assertCssVar($home->getContent(), '--sf-primary', '#0ea5e9');
    }

    public function test_preview_without_draft_uses_published_config(): void
    {
        // No draft exists yet — getOrCreate seeds from published, preview shows it
        $response = $this->actingAs($this->manager)->get($this->previewUrl());

        $response->assertOk();
        $this->assertCssVar($response->getContent(), '--sf-primary', '#0ea5e9');
    }

    // -------------------------------------------------------------------------
    // 3. Authorization — only this store's manager may preview
    // -------------------------------------------------------------------------

    public function test_staff_cannot_access_preview(): void
    {
        $staff = User::create([
            'name'     => 'Preview Staff',
            'phone'    => '09555667788',
            'password' => bcrypt('p'),
            'role'     => 'customer',
        ]);
        $staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $this->actingAs($staff)->get($this->previewUrl())->assertForbidden();
    }

    public function test_cross_store_manager_cannot_access_preview(): void
    {
        $storeB = Store::create(['slug' => 'preview-store-b', 'name' => 'Store B', 'is_active' => true]);
        $managerB = User::create([
            'name'     => 'Mgr B',
            'phone'    => '09666778899',
            'password' => bcrypt('p'),
            'role'     => 'customer',
        ]);
        $managerB->stores()->attach($storeB->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->actingAs($managerB)->get($this->previewUrl())->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // 4. CSP framing — preview may be framed same-origin; everything else stays 'none'
    // -------------------------------------------------------------------------

    public function test_preview_response_allows_same_origin_framing(): void
    {
        $response = $this->actingAs($this->manager)->get($this->previewUrl());

        $response->assertOk();
        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringNotContainsString("frame-ancestors 'none'", $csp);
    }

    public function test_regular_pages_still_block_framing(): void
    {
        // A normal (non-preview) admin page keeps frame-ancestors 'none'
        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.settings.section', ['store_slug' => $this->store->slug, 'section' => 'appearance']));

        $response->assertOk();
        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }
}
