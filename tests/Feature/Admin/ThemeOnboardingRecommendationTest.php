<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\Services\StoreOnboardingService;
use App\Services\ThemePublisher;
use App\Themes\ThemeRecommendation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase T6 — Business Onboarding Recommendation
 *
 * The business profile recommends a starting theme, applied ONLY during new
 * store / demo provisioning. Existing stores are never silently changed, and
 * the owner may switch to any active theme afterwards (recommendation is a
 * default, not an authorization rule).
 */
class ThemeOnboardingRecommendationTest extends TestCase
{
    use RefreshDatabase;

    protected User $platformOwner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->platformOwner = User::create([
            'name'     => 'Platform Super Owner',
            'phone'    => '09900000000',
            'password' => bcrypt('password'),
            'role'     => 'platform_owner',
        ]);
    }

    // -------------------------------------------------------------------------
    // 1. Profile → recommended theme mapping (plan §7)
    // -------------------------------------------------------------------------

    public function test_recommendation_mapping_matches_plan(): void
    {
        $this->assertSame('marketplace_pro', ThemeRecommendation::recommendForProfile('mobile_electronics'));
        $this->assertSame('retail_trust',    ThemeRecommendation::recommendForProfile('general_retail'));
        $this->assertSame('retail_trust',    ThemeRecommendation::recommendForProfile('repair_service'));
        $this->assertSame('emerald_fresh',   ThemeRecommendation::recommendForProfile('pharmacy'));
        $this->assertSame('retail_trust',    ThemeRecommendation::recommendForProfile('agriculture'));
        $this->assertSame('sunset_warm',     ThemeRecommendation::recommendForProfile('food_beverage'));
    }

    public function test_unknown_profile_gets_safe_default(): void
    {
        $this->assertSame('marketplace_pro', ThemeRecommendation::recommendForProfile('mystery_business'));
        $this->assertSame('marketplace_pro', ThemeRecommendation::recommendForProfile(''));
    }

    public function test_demo_business_types_resolve_via_their_profile(): void
    {
        $this->assertSame('marketplace_pro', ThemeRecommendation::recommendForDemoBusinessType('mobile_sale_service'));
        $this->assertSame('marketplace_pro', ThemeRecommendation::recommendForDemoBusinessType('mobile_accessories'));
        $this->assertSame('marketplace_pro', ThemeRecommendation::recommendForDemoBusinessType('cctv_network_computer'));
        $this->assertSame('emerald_fresh',   ThemeRecommendation::recommendForDemoBusinessType('pharmacy'));
        $this->assertSame('retail_trust',    ThemeRecommendation::recommendForDemoBusinessType('agriculture_inputs'));
        $this->assertSame('sunset_warm',     ThemeRecommendation::recommendForDemoBusinessType('restaurant'));
        $this->assertSame('marketplace_pro', ThemeRecommendation::recommendForDemoBusinessType('unknown_business'));
    }

    // -------------------------------------------------------------------------
    // 2. Provisioning applies the recommended theme
    // -------------------------------------------------------------------------

    public function test_provisioning_pharmacy_applies_emerald_fresh(): void
    {
        $this->actingAs($this->platformOwner)
            ->post(route('admin.stores.store'), [
                'name'             => 'City Pharmacy',
                'slug'             => 'city-pharmacy',
                'edition'          => 'pharmacy_healthcare',
                'owner_name'       => 'U Win Naing',
                'owner_phone'      => '09155551111',
                'owner_password'   => 'secret123',
                'owner_pos_pin'    => '9999',
                'default_language' => 'my',
            ])
            ->assertSessionHasNoErrors();

        $store = Store::where('slug', 'city-pharmacy')->firstOrFail();
        $this->assertSame('pharmacy', $store->business_profile);
        $this->assertSame('emerald_fresh', $store->setting->theme_preset);
    }

    public function test_provisioning_general_retail_applies_retail_trust(): void
    {
        $store = app(StoreOnboardingService::class)->provisionStore([
            'name'    => 'Corner Mart',
            'slug'    => 'corner-mart',
            'edition' => 'general_retail',
        ]);

        $this->assertSame('retail_trust', $store->setting->theme_preset);
    }

    // -------------------------------------------------------------------------
    // 3. Existing stores are never silently changed
    // -------------------------------------------------------------------------

    public function test_provisioning_never_touches_existing_stores(): void
    {
        // An existing store with a deliberate custom theme
        $existing = Store::create(['slug' => 'existing-store', 'name' => 'Existing', 'is_active' => true]);
        $existing->setting()->create([
            'store_name'   => 'Existing',
            'theme_preset' => 'midnight_tech',
            'font_preset'  => 'outfit',
            'grid_density' => 'compact',
        ]);

        // Provision a brand-new pharmacy store
        app(StoreOnboardingService::class)->provisionStore([
            'name'    => 'New Pharmacy',
            'slug'    => 'new-pharmacy',
            'edition' => 'pharmacy_healthcare',
        ]);

        // The existing store's theme is untouched
        $existing->refresh();
        $this->assertSame('midnight_tech', $existing->setting->theme_preset);
    }

    // -------------------------------------------------------------------------
    // 4. Recommendation is a default, not a restriction — owner may switch
    // -------------------------------------------------------------------------

    public function test_owner_can_switch_to_any_active_theme_after_provisioning(): void
    {
        $store = app(StoreOnboardingService::class)->provisionStore([
            'name'    => 'Switchable Pharmacy',
            'slug'    => 'switchable-pharmacy',
            'edition' => 'pharmacy_healthcare',
        ]);
        $this->assertSame('emerald_fresh', $store->setting->theme_preset);

        $owner = User::create(['name' => 'Owner', 'phone' => '09222223333', 'password' => bcrypt('p'), 'role' => 'customer']);
        $owner->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        // Publish a completely different theme — must succeed (no restriction)
        $revision = app(ThemePublisher::class)->publish($store, ['theme_preset' => 'marketplace_pro'], $owner);

        $this->assertSame('publish', $revision->action);
        $this->assertSame('marketplace_pro', $store->setting->refresh()->theme_preset);
    }
}
