<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Store;
use App\Models\StoreThemeRevision;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\Themes\ThemeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeEngineTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'slug'      => 'test-theme-store',
            'name'      => 'Test Theme Store',
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name'     => 'Theme Manager',
            'phone'    => '09111223344',
            'password' => bcrypt('password'),
            'role'     => 'customer',
        ]);

        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_theme_registry_loads_all_manifests_and_presets(): void
    {
        $themes = ThemeRegistry::all();

        $this->assertArrayHasKey('marketplace_pro', $themes);
        $this->assertArrayHasKey('retail_trust', $themes);
        $this->assertArrayHasKey('emerald_fresh', $themes);
        $this->assertArrayHasKey('midnight_tech', $themes);
        $this->assertArrayHasKey('sunset_warm', $themes);

        $retailManifest = ThemeRegistry::get('retail_trust');
        $this->assertSame('Retail Trust', $retailManifest->nameEn);
        $this->assertSame('#2563eb', $retailManifest->primaryColor());
        $this->assertSame('inter', $retailManifest->defaultFont);
        $this->assertSame('comfortable', $retailManifest->defaultDensity);

        // Test legacy aliases map safely
        $legacyManifest = ThemeRegistry::get('sky');
        $this->assertSame('marketplace_pro', $legacyManifest->id);
    }

    public function test_store_manager_can_access_theme_customizer_page(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.theme.index', ['store_slug' => $this->store->slug]));

        $response->assertRedirect(route('store.admin.settings.section', ['store_slug' => $this->store->slug, 'section' => 'appearance']));

        $followResponse = $this->actingAs($this->manager)
            ->get(route('store.admin.settings.section', ['store_slug' => $this->store->slug, 'section' => 'appearance']));

        $followResponse->assertOk();
        $followResponse->assertSee('Marketplace Pro');
        $followResponse->assertSee('Retail Trust');
        $followResponse->assertSee('Storefront Typography');
        $followResponse->assertSee('Product Grid Density');
    }

    public function test_store_manager_can_update_theme_preset_and_tokens(): void
    {
        $response = $this->actingAs($this->manager)
            ->post('/store/' . $this->store->slug . '/admin/settings', [
                'section'             => 'appearance',
                'theme_preset'        => 'retail_trust',
                'theme_primary_color' => '#1d4ed8',
                'theme_accent_color'  => '#d97706',
                'theme_header_bg'     => '#ffffff',
                'theme_body_bg'       => '#f8fafc',
                'theme_glow_style'    => 'subtle',
                'theme_dark_mode'     => 'auto',
                'font_preset'         => 'inter',
                'grid_density'        => 'comfortable',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('storefront_settings', [
            'store_id'            => $this->store->id,
            'theme_preset'        => 'retail_trust',
            'theme_primary_color' => '#1d4ed8',
            'font_preset'         => 'inter',
            'grid_density'        => 'comfortable',
        ]);

        $this->assertDatabaseHas('store_theme_revisions', [
            'store_id' => $this->store->id,
            'revision_number' => 1,
            'action' => 'baseline',
        ]);
        $this->assertDatabaseHas('store_theme_revisions', [
            'store_id' => $this->store->id,
            'revision_number' => 2,
            'action' => 'publish',
            'actor_id' => $this->manager->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $this->store->id,
            'action' => 'store_theme_publish',
            'actor_id' => $this->manager->id,
        ]);
    }

    public function test_manager_can_restore_an_exact_previous_theme_as_a_new_revision(): void
    {
        StorefrontSetting::create([
            'store_id' => $this->store->id,
            'store_name' => $this->store->name,
            'theme_preset' => 'marketplace_pro',
            'theme_primary_color' => '#0ea5e9',
            'theme_accent_color' => '#7c3aed',
            'font_preset' => 'outfit',
            'grid_density' => 'compact',
        ]);

        $this->actingAs($this->manager)->post('/store/' . $this->store->slug . '/admin/settings', [
            'section' => 'appearance',
            'theme_preset' => 'retail_trust',
            'theme_primary_color' => '#1d4ed8',
            'theme_accent_color' => '#d97706',
            'font_preset' => 'inter',
            'grid_density' => 'comfortable',
        ])->assertSessionHasNoErrors();

        $baseline = StoreThemeRevision::where('store_id', $this->store->id)
            ->where('action', 'baseline')
            ->firstOrFail();

        $response = $this->actingAs($this->manager)->post(route(
            'store.admin.settings.appearance.rollback',
            ['store_slug' => $this->store->slug, 'revision' => $baseline->id],
        ));

        $response->assertRedirect()->assertSessionHasNoErrors();
        $setting = $this->store->setting()->firstOrFail();
        $this->assertSame('marketplace_pro', $setting->theme_preset);
        $this->assertSame('#0ea5e9', $setting->theme_primary_color);
        $this->assertSame('#7c3aed', $setting->theme_accent_color);
        $this->assertSame('outfit', $setting->font_preset);
        $this->assertSame('compact', $setting->grid_density);

        $this->assertDatabaseHas('store_theme_revisions', [
            'store_id' => $this->store->id,
            'revision_number' => 3,
            'action' => 'rollback',
            'source_revision_id' => $baseline->id,
            'actor_id' => $this->manager->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $this->store->id,
            'action' => 'store_theme_rollback',
        ]);
    }

    public function test_manager_cannot_restore_another_stores_revision(): void
    {
        $otherStore = Store::create([
            'slug' => 'revision-owner',
            'name' => 'Revision Owner',
            'is_active' => true,
        ]);
        $revision = StoreThemeRevision::create([
            'store_id' => $otherStore->id,
            'revision_number' => 1,
            'theme_config' => ['theme_preset' => 'retail_trust'],
            'action' => 'baseline',
        ]);

        $this->actingAs($this->manager)
            ->post(route('store.admin.settings.appearance.rollback', [
                'store_slug' => $this->store->slug,
                'revision' => $revision->id,
            ]))
            ->assertNotFound();
    }

    public function test_appearance_page_lists_revision_history(): void
    {
        StoreThemeRevision::create([
            'store_id' => $this->store->id,
            'revision_number' => 1,
            'theme_config' => ['theme_preset' => 'retail_trust'],
            'action' => 'publish',
            'actor_id' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)
            ->get(route('store.admin.settings.section', [
                'store_slug' => $this->store->slug,
                'section' => 'appearance',
            ]))
            ->assertOk()
            ->assertSee(__('messages.theme_history_title'))
            ->assertSee('Retail Trust')
            ->assertSee('Theme Manager');
    }

    public function test_storefront_renders_updated_css_variables_and_font(): void
    {
        StorefrontSetting::create([
            'store_id'            => $this->store->id,
            'store_name'          => 'Test Theme Store',
            'theme_preset'        => 'retail_trust',
            'theme_primary_color' => '#1d4ed8',
            'theme_accent_color'  => '#d97706',
            'font_preset'         => 'inter',
            'grid_density'        => 'comfortable',
        ]);

        $response = $this->get('/store/' . $this->store->slug);

        $response->assertOk();
        $response->assertSee('--sf-primary:        #1d4ed8', false);
        $response->assertSee('--sf-accent:         #d97706', false);
        $response->assertSee("--sf-font-family:    'Inter', 'Padauk', system-ui, sans-serif", false);
    }

    public function test_cross_store_manager_cannot_update_other_store_theme(): void
    {
        $otherStore = Store::create([
            'slug'      => 'other-store',
            'name'      => 'Other Store',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->post('/store/' . $otherStore->slug . '/admin/settings', [
                'section'      => 'appearance',
                'theme_preset' => 'retail_trust',
            ]);

        $response->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // T1 Contract Hardening tests (added 2026-08-29)
    // -------------------------------------------------------------------------

    public function test_unknown_keys_in_publish_do_not_reach_revision_json(): void
    {
        $this->actingAs($this->manager)
            ->post('/store/' . $this->store->slug . '/admin/settings', [
                'section'        => 'appearance',
                'theme_preset'   => 'retail_trust',
                'evil_script'    => '<script>alert(1)</script>',
                'layout_variant' => 'hacked_layout',
                '__proto__'      => 'polluted',
            ])
            ->assertSessionHasNoErrors();

        $revision = StoreThemeRevision::where('store_id', $this->store->id)
            ->where('action', 'publish')
            ->firstOrFail();

        $this->assertArrayNotHasKey('evil_script',    $revision->theme_config);
        $this->assertArrayNotHasKey('layout_variant', $revision->theme_config);
        $this->assertArrayNotHasKey('__proto__',      $revision->theme_config);
        // Exactly 11 keys: 9 safe tokens + schema_version + theme_version
        $this->assertCount(11, $revision->theme_config);
        $this->assertSame(\App\Themes\ThemeConfig::SCHEMA_VERSION, $revision->theme_config['schema_version']);
        $this->assertSame('1', $revision->theme_config['theme_version']);
    }

    public function test_uppercase_color_is_normalized_to_lowercase_in_revision_json(): void
    {
        $this->actingAs($this->manager)
            ->post('/store/' . $this->store->slug . '/admin/settings', [
                'section'             => 'appearance',
                'theme_preset'        => 'retail_trust',
                'theme_primary_color' => '#1D4ED8',  // uppercase
                'theme_accent_color'  => '#D97706',  // uppercase
            ])
            ->assertSessionHasNoErrors();

        $revision = StoreThemeRevision::where('store_id', $this->store->id)
            ->where('action', 'publish')
            ->firstOrFail();

        $this->assertSame('#1d4ed8', $revision->theme_config['theme_primary_color']);
        $this->assertSame('#d97706', $revision->theme_config['theme_accent_color']);
    }

    public function test_revision_snapshot_includes_schema_version(): void
    {
        $this->actingAs($this->manager)
            ->post('/store/' . $this->store->slug . '/admin/settings', [
                'section'      => 'appearance',
                'theme_preset' => 'retail_trust',
            ])
            ->assertSessionHasNoErrors();

        $revision = StoreThemeRevision::where('store_id', $this->store->id)
            ->where('action', 'publish')
            ->firstOrFail();

        $this->assertArrayHasKey('schema_version', $revision->theme_config);
        $this->assertSame(\App\Themes\ThemeConfig::SCHEMA_VERSION, $revision->theme_config['schema_version']);
    }

    public function test_legacy_preset_id_is_resolved_and_saved_as_canonical_id(): void
    {
        // 'sky' is a legacy alias for 'marketplace_pro'
        $this->actingAs($this->manager)
            ->post('/store/' . $this->store->slug . '/admin/settings', [
                'section'      => 'appearance',
                'theme_preset' => 'sky',
            ])
            ->assertSessionHasNoErrors();

        $setting = StorefrontSetting::where('store_id', $this->store->id)->firstOrFail();
        $this->assertSame('marketplace_pro', $setting->theme_preset);
    }

    public function test_appearance_and_settings_render_without_translation_key_leaks_in_all_locales(): void
    {
        foreach (['en', 'my', 'zh_CN'] as $locale) {
            $response = $this->actingAs($this->manager)
                ->withSession(['locale' => $locale])
                ->get(route('store.admin.settings.section', [
                    'store_slug' => $this->store->slug,
                    'section'    => 'appearance',
                ]));

            $response->assertOk();
            $content = $response->getContent();
            $this->assertFalse(
                (bool) preg_match('/messages\.[a-zA-Z0-9_-]+/', $content),
                "Found leaked translation key in locale [{$locale}] on appearance settings"
            );
        }
    }
}
