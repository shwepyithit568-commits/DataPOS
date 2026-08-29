<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\Services\ThemePublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase T8 — Admin/POS Polish
 *
 * 1. Restrained Admin brand accent: the active sidebar link follows the
 *    store's theme primary; semantic colors stay system-controlled.
 * 2. POS personal display mode (standard_light / high_contrast_daylight /
 *    oled_dark): per-device localStorage preference, fully independent of the
 *    storefront published theme/revision.
 */
class ThemeAdminPosPolishTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User  $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['slug' => 'polish-store', 'name' => 'Polish Store', 'is_active' => true]);
        $this->store->setting()->create([
            'store_name'          => 'Polish Store',
            'theme_preset'        => 'marketplace_pro',
            'theme_primary_color' => '#123456',
            'theme_accent_color'  => '#7c3aed',
            'theme_header_bg'     => '#ffffff',
            'theme_body_bg'       => '#f8fafc',
            'theme_glow_style'    => 'vivid',
            'theme_dark_mode'     => 'auto',
            'font_preset'         => 'outfit',
            'grid_density'        => 'compact',
        ]);

        $this->manager = User::create(['name' => 'Polish Mgr', 'phone' => '09222334455', 'password' => bcrypt('p'), 'role' => 'customer']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    // -------------------------------------------------------------------------
    // 1. Restrained Admin brand accent
    // -------------------------------------------------------------------------

    public function test_admin_layout_uses_store_theme_primary_as_brand_accent(): void
    {
        $content = (string) $this->actingAs($this->manager)
            ->get(route('store.admin.dashboard', ['store_slug' => $this->store->slug]))
            ->getContent();

        $this->assertStringContainsString('--admin-accent: #123456', $content);
        // The override is scoped to the sidebar active link only
        $this->assertStringContainsString('aside a.bg-violet-600', $content);
    }

    public function test_admin_accent_falls_back_to_manifest_default_when_no_override(): void
    {
        $this->store->setting()->update(['theme_primary_color' => null]);

        $content = (string) $this->actingAs($this->manager)
            ->get(route('store.admin.dashboard', ['store_slug' => $this->store->slug]))
            ->getContent();

        // marketplace_pro manifest primary
        $this->assertStringContainsString('--admin-accent: #0ea5e9', $content);
    }

    public function test_admin_brand_accent_does_not_reach_pos_semantics(): void
    {
        // The accent is defined ONLY as --admin-accent on :root; the storefront
        // theme tokens (--sf-*) must never leak into the admin surface.
        $content = (string) $this->actingAs($this->manager)
            ->get(route('store.admin.dashboard', ['store_slug' => $this->store->slug]))
            ->getContent();

        $this->assertStringNotContainsString('--sf-primary', $content);
    }

    // -------------------------------------------------------------------------
    // 2. POS personal display mode — independent of storefront theme
    // -------------------------------------------------------------------------

    public function test_pos_layout_offers_three_display_modes(): void
    {
        $cashier = User::create(['name' => 'Cashier', 'phone' => '09333445566', 'password' => bcrypt('p'), 'role' => 'customer']);
        $cashier->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $content = (string) $this->actingAs($cashier)
            ->get(route('pos.index', ['store_slug' => $this->store->slug]))
            ->getContent();

        $this->assertStringContainsString('Standard Light', $content);
        $this->assertStringContainsString('High-Contrast Daylight', $content);
        $this->assertStringContainsString('OLED Dark', $content);
        // Preference key + persistence mechanism
        $this->assertStringContainsString("localStorage.getItem('posDisplayMode')", $content);
    }

    public function test_pos_layout_is_independent_of_storefront_theme(): void
    {
        $cashier = User::create(['name' => 'Cashier', 'phone' => '09333445566', 'password' => bcrypt('p'), 'role' => 'customer']);
        $cashier->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $before = (string) $this->actingAs($cashier)
            ->get(route('pos.index', ['store_slug' => $this->store->slug]))
            ->getContent();

        // No storefront theme tokens in the POS surface
        $this->assertStringNotContainsString('--sf-primary', $before);

        // Publishing a very different storefront theme must not change the POS
        // page (POS preference is per-device localStorage, not the theme).
        app(ThemePublisher::class)->publish($this->store, ['theme_preset' => 'midnight_tech'], $this->manager);

        $after = (string) $this->actingAs($cashier)
            ->get(route('pos.index', ['store_slug' => $this->store->slug]))
            ->getContent();

        $this->assertStringNotContainsString('--sf-primary', $after);
        // Structural markers identical: same mode dropdown, no theme bleed
        $this->assertStringContainsString('High-Contrast Daylight', $after);
    }
}
