<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Store;
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
}
