<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\ThemeGovernance;
use App\Models\User;
use App\Services\ThemeGovernanceService;
use App\Services\ThemePublisher;
use App\Themes\ThemeRecommendation;
use App\Themes\ThemeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase T7 — Platform Theme Governance
 *
 * Lifecycle (active/deprecated/hidden) is managed by the Platform Owner via a
 * DB-backed override + audited. Existing stores keep rendering any theme they
 * already use (deprecated/hidden never break them); only NEW selection and
 * onboarding recommendations are gated.
 */
class ThemeGovernanceTest extends TestCase
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
    // 1. Defaults + override persistence
    // -------------------------------------------------------------------------

    public function test_all_themes_default_to_active(): void
    {
        $service = app(ThemeGovernanceService::class);

        foreach (ThemeRegistry::ids() as $id) {
            $this->assertSame('active', $service->effectiveStatus($id));
        }
    }

    public function test_set_status_persists_override_and_audits(): void
    {
        $service = app(ThemeGovernanceService::class);

        $service->setStatus('sunset_warm', 'deprecated', 'retail_trust', $this->platformOwner);

        $this->assertSame('deprecated', $service->effectiveStatus('sunset_warm'));
        $this->assertSame('retail_trust', $service->replacementFor('sunset_warm'));

        $audit = AuditLog::where('action', 'theme_lifecycle_change')->latest('id')->first();
        $this->assertNotNull($audit);
        $this->assertSame('sunset_warm', $audit->metadata['theme_id'] ?? null);
        $this->assertSame('active', $audit->metadata['from_status'] ?? null);
        $this->assertSame('deprecated', $audit->metadata['to_status'] ?? null);
        $this->assertSame($this->platformOwner->id, $audit->actor_id);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(ThemeGovernanceService::class)->setStatus('sunset_warm', 'exploded', null, $this->platformOwner);
    }

    public function test_invalid_replacement_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(ThemeGovernanceService::class)->setStatus('sunset_warm', 'deprecated', 'not_a_theme', $this->platformOwner);
    }

    // -------------------------------------------------------------------------
    // 2. Hidden themes: not selectable, not in the picker — still renderable
    // -------------------------------------------------------------------------

    public function test_hidden_theme_is_excluded_from_selectable_ids(): void
    {
        $service = app(ThemeGovernanceService::class);

        $service->setStatus('retail_trust', 'hidden', null, $this->platformOwner);

        $this->assertNotContains('retail_trust', $service->selectableIds());
        $this->assertContains('marketplace_pro', $service->selectableIds());
    }

    public function test_hidden_theme_disappears_from_appearance_picker(): void
    {
        $service = app(ThemeGovernanceService::class);
        $service->setStatus('retail_trust', 'hidden', null, $this->platformOwner);

        $store = Store::create(['slug' => 'pick-store', 'name' => 'Pick Store', 'is_active' => true]);
        $store->setting()->create(['store_name' => 'Pick Store']);
        $manager = User::create(['name' => 'Mgr', 'phone' => '09222334455', 'password' => bcrypt('p'), 'role' => 'customer']);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $content = (string) $this->actingAs($manager)
            ->get(route('store.admin.settings.section', ['store_slug' => $store->slug, 'section' => 'appearance']))
            ->getContent();

        $this->assertStringNotContainsString('Retail Trust (Clean Blue)', $content);
        $this->assertStringContainsString('Marketplace Pro (Cloud White)', $content);
    }

    public function test_deprecated_theme_stays_renderable_and_publishable(): void
    {
        $service = app(ThemeGovernanceService::class);
        $service->setStatus('sunset_warm', 'deprecated', 'retail_trust', $this->platformOwner);

        $store = Store::create(['slug' => 'dep-store', 'name' => 'Dep Store', 'is_active' => true]);
        $store->setting()->create([
            'store_name'   => 'Dep Store',
            'theme_preset' => 'sunset_warm',
        ]);

        // Publishing a deprecated theme must succeed (existing stores never break)
        $revision = app(ThemePublisher::class)->publish($store, ['theme_preset' => 'sunset_warm'], $this->platformOwner);

        $this->assertSame('publish', $revision->action);
        $this->assertSame('sunset_warm', $store->setting->refresh()->theme_preset);
        $this->assertSame('deprecated', $service->effectiveStatus('sunset_warm'));
    }

    public function test_deprecated_theme_shows_badge_in_picker(): void
    {
        $service = app(ThemeGovernanceService::class);
        $service->setStatus('retail_trust', 'deprecated', 'marketplace_pro', $this->platformOwner);

        $store = Store::create(['slug' => 'badge-store', 'name' => 'Badge Store', 'is_active' => true]);
        $store->setting()->create(['store_name' => 'Badge Store']);
        $manager = User::create(['name' => 'Mgr', 'phone' => '09222334455', 'password' => bcrypt('p'), 'role' => 'customer']);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $content = (string) $this->actingAs($manager)
            ->get(route('store.admin.settings.section', ['store_slug' => $store->slug, 'section' => 'appearance']))
            ->getContent();

        $this->assertStringContainsString('Retail Trust (Clean Blue)', $content);
        $this->assertStringContainsString('Deprecated', $content);
    }

    // -------------------------------------------------------------------------
    // 3. Onboarding recommendation only returns active themes
    // -------------------------------------------------------------------------

    public function test_onboarding_recommendation_avoids_hidden_theme(): void
    {
        $service = app(ThemeGovernanceService::class);

        // pharmacy → emerald_fresh by default; hide emerald_fresh with replacement retail_trust
        $service->setStatus('emerald_fresh', 'hidden', 'retail_trust', $this->platformOwner);

        $this->assertSame('retail_trust', ThemeRecommendation::recommendForProfile('pharmacy'));

        // Hide the replacement too → falls back to the safe default
        $service->setStatus('retail_trust', 'hidden', null, $this->platformOwner);
        $this->assertSame('marketplace_pro', ThemeRecommendation::recommendForProfile('pharmacy'));
    }

    // -------------------------------------------------------------------------
    // 4. Governance UI — platform owner only
    // -------------------------------------------------------------------------

    public function test_governance_page_requires_platform_owner(): void
    {
        $this->actingAs($this->platformOwner)
            ->get(route('admin.theme-governance.index'))
            ->assertOk()
            ->assertSee('Theme Governance')
            ->assertSee('marketplace_pro');

        $store = Store::create(['slug' => 'ui-store', 'name' => 'UI Store', 'is_active' => true]);
        $manager = User::create(['name' => 'Mgr', 'phone' => '09222334455', 'password' => bcrypt('p'), 'role' => 'customer']);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->actingAs($manager)->get(route('admin.theme-governance.index'))->assertForbidden();
    }

    public function test_governance_update_via_http_is_audited(): void
    {
        $this->actingAs($this->platformOwner)
            ->post(route('admin.theme-governance.update'), [
                'theme_id'       => 'sunset_warm',
                'status'         => 'deprecated',
                'replacement_id' => 'retail_trust',
            ])
            ->assertRedirect();

        $this->assertSame('deprecated', app(ThemeGovernanceService::class)->effectiveStatus('sunset_warm'));
        $this->assertDatabaseHas('theme_governance', [
            'theme_id'       => 'sunset_warm',
            'status'         => 'deprecated',
            'replacement_id' => 'retail_trust',
        ]);
    }
}
