<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\StoreThemeDraft;
use App\Models\StoreThemeRevision;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\Services\ThemeDraftService;
use App\Themes\ThemeConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase T2 — Persistent Draft & Conflict Safety
 *
 * Core invariant being tested: ThemeDraftService::save() MUST NEVER touch
 * storefront_settings.  Customer-facing queries should always read from
 * storefront_settings; only ThemePublisher::publish() may update it.
 */
class ThemeDraftTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User  $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'slug'      => 'draft-test-store',
            'name'      => 'Draft Test Store',
            'is_active' => true,
        ]);

        StorefrontSetting::create([
            'store_id'            => $this->store->id,
            'store_name'          => 'Draft Test Store',
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
            'name'     => 'Draft Manager',
            'phone'    => '09222334455',
            'password' => bcrypt('password'),
            'role'     => 'customer',
        ]);

        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    // -------------------------------------------------------------------------
    // 1. getOrCreate — seeds from published state
    // -------------------------------------------------------------------------

    public function test_draft_is_created_from_published_state_on_first_open(): void
    {
        $this->assertDatabaseMissing('store_theme_drafts', ['store_id' => $this->store->id]);

        $service = app(ThemeDraftService::class);
        $draft   = $service->getOrCreate($this->store, $this->manager);

        $this->assertDatabaseHas('store_theme_drafts', ['store_id' => $this->store->id]);
        $this->assertSame('marketplace_pro', $draft->theme_config['theme_preset']);
        $this->assertSame(1, $draft->lock_version);
    }

    public function test_get_or_create_returns_existing_draft_unchanged(): void
    {
        $service = app(ThemeDraftService::class);
        $first   = $service->getOrCreate($this->store, $this->manager);
        $second  = $service->getOrCreate($this->store, $this->manager);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('store_theme_drafts', 1);
    }

    // -------------------------------------------------------------------------
    // 2. T2 Core Rule: save() must NOT touch storefront_settings
    // -------------------------------------------------------------------------

    public function test_save_draft_does_not_touch_storefront_settings(): void
    {
        $service = app(ThemeDraftService::class);
        $draft   = $service->getOrCreate($this->store, $this->manager);

        // Save a very different theme to the draft
        $service->save($this->store, [
            'theme_preset'        => 'midnight_tech',
            'theme_primary_color' => '#38bdf8',
            'theme_dark_mode'     => 'dark',
        ], $draft->lock_version, $this->manager);

        // storefront_settings MUST be unchanged
        $setting = StorefrontSetting::where('store_id', $this->store->id)->first();
        $this->assertSame('marketplace_pro', $setting->theme_preset);
        $this->assertSame('#0ea5e9',         $setting->theme_primary_color);
        $this->assertSame('auto',            $setting->theme_dark_mode);
    }

    public function test_save_draft_via_api_does_not_touch_storefront_settings(): void
    {
        $service = app(ThemeDraftService::class);
        $draft   = $service->getOrCreate($this->store, $this->manager);

        $response = $this->actingAs($this->manager)
            ->postJson(route('store.admin.appearance.draft.save', ['store_slug' => $this->store->slug]), [
                'theme_config' => [
                    'theme_preset'        => 'sunset_warm',
                    'theme_primary_color' => '#e11d48',
                    'theme_dark_mode'     => 'light',
                ],
                'lock_version' => $draft->lock_version,
            ]);

        $response->assertOk();

        // storefront_settings must still be unchanged
        $setting = StorefrontSetting::where('store_id', $this->store->id)->first();
        $this->assertSame('marketplace_pro', $setting->theme_preset);
        $this->assertSame('#0ea5e9',         $setting->theme_primary_color);
    }

    // -------------------------------------------------------------------------
    // 3. Optimistic locking — lock_version increments on every save
    // -------------------------------------------------------------------------

    public function test_save_draft_increments_lock_version(): void
    {
        $service = app(ThemeDraftService::class);
        $draft   = $service->getOrCreate($this->store, $this->manager);
        $this->assertSame(1, $draft->lock_version);

        $updated = $service->save($this->store, ['theme_preset' => 'retail_trust'], 1, $this->manager);
        $this->assertSame(2, $updated->lock_version);

        $updated2 = $service->save($this->store, ['theme_preset' => 'emerald_fresh'], 2, $this->manager);
        $this->assertSame(3, $updated2->lock_version);
    }

    public function test_stale_lock_version_is_rejected_with_409(): void
    {
        $service = app(ThemeDraftService::class);
        $service->getOrCreate($this->store, $this->manager);

        $response = $this->actingAs($this->manager)
            ->postJson(route('store.admin.appearance.draft.save', ['store_slug' => $this->store->slug]), [
                'theme_config' => ['theme_preset' => 'retail_trust'],
                'lock_version' => 999, // stale / wrong
            ]);

        $response->assertStatus(409);
    }

    // -------------------------------------------------------------------------
    // 4. Cross-store draft isolation
    // -------------------------------------------------------------------------

    public function test_cross_store_draft_isolation(): void
    {
        $storeB = Store::create(['slug' => 'store-b', 'name' => 'Store B', 'is_active' => true]);
        StorefrontSetting::create([
            'store_id'   => $storeB->id,
            'store_name' => 'Store B',
        ]);
        $managerB = User::create(['name' => 'Mgr B', 'phone' => '09333445566', 'password' => bcrypt('p'), 'role' => 'customer']);
        $managerB->stores()->attach($storeB->id, ['role' => 'store_manager', 'status' => 'active']);

        $service = app(ThemeDraftService::class);
        $draftA  = $service->getOrCreate($this->store, $this->manager);
        $draftB  = $service->getOrCreate($storeB, $managerB);

        // Save to Store A's draft
        $service->save($this->store, ['theme_preset' => 'midnight_tech'], $draftA->lock_version, $this->manager);

        // Store B's draft must be untouched
        $draftBRefreshed = StoreThemeDraft::find($draftB->id);
        $this->assertSame(1, $draftBRefreshed->lock_version);
        $this->assertNotSame('midnight_tech', $draftBRefreshed->theme_config['theme_preset'] ?? null);
    }

    // -------------------------------------------------------------------------
    // 5. Publish from draft — storefront_settings updated, revision created
    // -------------------------------------------------------------------------

    public function test_publish_from_draft_updates_storefront_and_creates_revision(): void
    {
        $service = app(ThemeDraftService::class);
        $draft   = $service->getOrCreate($this->store, $this->manager);

        // Save the new design to draft
        $saved = $service->save($this->store, [
            'theme_preset'        => 'retail_trust',
            'theme_primary_color' => '#2563eb',
        ], $draft->lock_version, $this->manager);

        // Publish via API
        $response = $this->actingAs($this->manager)
            ->postJson(route('store.admin.appearance.publish', ['store_slug' => $this->store->slug]), [
                'lock_version' => $saved->lock_version,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['revision_number', 'message']);

        // storefront_settings MUST NOW reflect the published config
        $setting = StorefrontSetting::where('store_id', $this->store->id)->first();
        $this->assertSame('retail_trust', $setting->theme_preset);
        $this->assertSame('#2563eb',      $setting->theme_primary_color);

        // A revision row must exist
        $this->assertDatabaseHas('store_theme_revisions', [
            'store_id' => $this->store->id,
            'action'   => 'publish',
        ]);

        // Draft's base_revision_id must be updated to the new revision
        $draftRefreshed = StoreThemeDraft::where('store_id', $this->store->id)->first();
        $latestRevision = StoreThemeRevision::where('store_id', $this->store->id)->latest('revision_number')->first();
        $this->assertSame($latestRevision->id, $draftRefreshed->base_revision_id);
    }

    // -------------------------------------------------------------------------
    // 6. Conflict detection — another actor published since draft was opened
    // -------------------------------------------------------------------------

    public function test_publish_detects_conflict_when_another_actor_published_first(): void
    {
        $service = app(ThemeDraftService::class);

        // 1. Publish an initial revision so we have a baseline revision ID
        $initialRevision = app(\App\Services\ThemePublisher::class)->publish($this->store, [
            'theme_preset' => 'retail_trust',
        ], $this->manager);

        // 2. Load draft — its base_revision_id will be $initialRevision->id
        $draft = $service->getOrCreate($this->store, $this->manager);
        $this->assertSame($initialRevision->id, $draft->base_revision_id);

        // 3. Another actor publishes a new revision (different session)
        $anotherManager = User::create(['name' => 'Other Mgr', 'phone' => '09444556677', 'password' => bcrypt('p'), 'role' => 'customer']);
        $anotherManager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $newRevision = app(\App\Services\ThemePublisher::class)->publish($this->store, [
            'theme_preset' => 'sunset_warm',
        ], $anotherManager);

        // 4. Now our draft's base_revision_id ($initialRevision->id) != latest published revision ID ($newRevision->id)
        //    → ThemeDraftService::publish() should abort with 409 conflict
        $response = $this->actingAs($this->manager)
            ->postJson(route('store.admin.appearance.publish', ['store_slug' => $this->store->slug]), [
                'lock_version' => $draft->lock_version,
            ]);

        $response->assertStatus(409);
    }

    // -------------------------------------------------------------------------
    // 7. Discard draft
    // -------------------------------------------------------------------------

    public function test_discard_draft_removes_the_row(): void
    {
        $service = app(ThemeDraftService::class);
        $service->getOrCreate($this->store, $this->manager);

        $this->assertDatabaseCount('store_theme_drafts', 1);

        $response = $this->actingAs($this->manager)
            ->deleteJson(route('store.admin.appearance.draft.discard', ['store_slug' => $this->store->slug]));

        $response->assertOk();
        $this->assertDatabaseCount('store_theme_drafts', 0);
    }

    public function test_get_or_create_after_discard_reseeds_from_published(): void
    {
        $service = app(ThemeDraftService::class);
        $draft   = $service->getOrCreate($this->store, $this->manager);

        // Mutate the draft
        $service->save($this->store, ['theme_preset' => 'midnight_tech'], $draft->lock_version, $this->manager);

        // Discard
        $service->discard($this->store);

        // Re-create — must re-seed from published (marketplace_pro)
        $newDraft = $service->getOrCreate($this->store, $this->manager);
        $this->assertSame('marketplace_pro', $newDraft->theme_config['theme_preset']);
        $this->assertSame(1, $newDraft->lock_version);
    }

    // -------------------------------------------------------------------------
    // 8. resetToPublished — called after rollback
    // -------------------------------------------------------------------------

    public function test_rollback_resets_draft_to_restored_state(): void
    {
        $service = app(ThemeDraftService::class);

        // Create a published revision first
        app(\App\Services\ThemePublisher::class)->publish($this->store, [
            'theme_preset' => 'retail_trust',
        ], $this->manager);

        $draft = $service->getOrCreate($this->store, $this->manager);

        // Rollback the latest revision → should reset draft
        $revision = StoreThemeRevision::where('store_id', $this->store->id)
            ->where('action', 'publish')
            ->firstOrFail();

        app(\App\Services\ThemePublisher::class)->rollback($this->store, $revision, $this->manager);
        $resetDraft = $service->resetToPublished($this->store, $this->manager);

        // Draft should now reflect the rolled-back published state
        $setting = StorefrontSetting::where('store_id', $this->store->id)->first();
        $this->assertSame($setting->theme_preset, $resetDraft->theme_config['theme_preset']);

        // lock_version incremented so stale saves are rejected
        $this->assertGreaterThan($draft->lock_version, $resetDraft->lock_version);
    }

    // -------------------------------------------------------------------------
    // 9. Draft config normalization — unknown keys blocked even in draft
    // -------------------------------------------------------------------------

    public function test_draft_config_is_normalized_via_themeconfig_dto(): void
    {
        $service = app(ThemeDraftService::class);
        $draft   = $service->getOrCreate($this->store, $this->manager);

        $updated = $service->save($this->store, [
            'theme_preset'   => 'retail_trust',
            'evil_key'       => 'injected',
            'layout_variant' => 'hacked',
        ], $draft->lock_version, $this->manager);

        $this->assertArrayNotHasKey('evil_key',       $updated->theme_config);
        $this->assertArrayNotHasKey('layout_variant', $updated->theme_config);
        $this->assertCount(9, $updated->theme_config);
    }

    // -------------------------------------------------------------------------
    // 10. GET /admin/appearance/draft — returns JSON draft state
    // -------------------------------------------------------------------------

    public function test_get_draft_endpoint_returns_draft_state(): void
    {
        $response = $this->actingAs($this->manager)
            ->getJson(route('store.admin.appearance.draft.show', ['store_slug' => $this->store->slug]));

        $response->assertOk()
            ->assertJsonStructure([
                'draft' => ['theme_config', 'lock_version', 'base_revision_id'],
                'conflict',
                'latest_revision_id',
            ]);

        $this->assertFalse($response->json('conflict'));
    }

    // -------------------------------------------------------------------------
    // 11. Authorization — staff/cashier cannot edit or preview drafts
    // -------------------------------------------------------------------------

    public function test_staff_cannot_access_draft_api(): void
    {
        $staff = User::create(['name' => 'Draft Staff', 'phone' => '09555667788', 'password' => bcrypt('p'), 'role' => 'customer']);
        $staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $this->actingAs($staff)
            ->getJson(route('store.admin.appearance.draft.show', ['store_slug' => $this->store->slug]))
            ->assertForbidden();

        $this->actingAs($staff)
            ->postJson(route('store.admin.appearance.draft.save', ['store_slug' => $this->store->slug]), [
                'theme_config' => ['theme_preset' => 'retail_trust'],
                'lock_version' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->postJson(route('store.admin.appearance.publish', ['store_slug' => $this->store->slug]), [
                'lock_version' => 1,
            ])
            ->assertForbidden();
    }

    public function test_cross_store_manager_cannot_access_draft_api(): void
    {
        $storeB = Store::create(['slug' => 'store-b', 'name' => 'Store B', 'is_active' => true]);
        $managerB = User::create(['name' => 'Mgr B', 'phone' => '09666778899', 'password' => bcrypt('p'), 'role' => 'customer']);
        $managerB->stores()->attach($storeB->id, ['role' => 'store_manager', 'status' => 'active']);

        // Store B manager hits Store A's draft endpoints → 403
        $this->actingAs($managerB)
            ->getJson(route('store.admin.appearance.draft.show', ['store_slug' => $this->store->slug]))
            ->assertForbidden();

        $this->actingAs($managerB)
            ->postJson(route('store.admin.appearance.draft.save', ['store_slug' => $this->store->slug]), [
                'theme_config' => ['theme_preset' => 'retail_trust'],
                'lock_version' => 1,
            ])
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // 12. Conflict flag on GET /draft
    // -------------------------------------------------------------------------

    public function test_get_draft_conflict_flag_is_true_after_concurrent_publish(): void
    {
        $service = app(ThemeDraftService::class);

        // Initial publish → draft base = initial revision
        app(\App\Services\ThemePublisher::class)->publish($this->store, ['theme_preset' => 'retail_trust'], $this->manager);
        $service->getOrCreate($this->store, $this->manager);

        // Another actor publishes again while our draft is open
        $anotherManager = User::create(['name' => 'Other Mgr', 'phone' => '09777889900', 'password' => bcrypt('p'), 'role' => 'customer']);
        $anotherManager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
        app(\App\Services\ThemePublisher::class)->publish($this->store, ['theme_preset' => 'sunset_warm'], $anotherManager);

        $response = $this->actingAs($this->manager)
            ->getJson(route('store.admin.appearance.draft.show', ['store_slug' => $this->store->slug]));

        $response->assertOk();
        $this->assertTrue($response->json('conflict'));
    }

    // -------------------------------------------------------------------------
    // 13. Audit — draft conflicts and discards are recorded (ThemePlan §12)
    // -------------------------------------------------------------------------

    public function test_publish_conflict_is_audited(): void
    {
        $service = app(ThemeDraftService::class);

        app(\App\Services\ThemePublisher::class)->publish($this->store, ['theme_preset' => 'retail_trust'], $this->manager);
        $draft = $service->getOrCreate($this->store, $this->manager);

        $anotherManager = User::create(['name' => 'Other Mgr', 'phone' => '09888990011', 'password' => bcrypt('p'), 'role' => 'customer']);
        $anotherManager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
        app(\App\Services\ThemePublisher::class)->publish($this->store, ['theme_preset' => 'sunset_warm'], $anotherManager);

        $this->actingAs($this->manager)
            ->postJson(route('store.admin.appearance.publish', ['store_slug' => $this->store->slug]), [
                'lock_version' => $draft->lock_version,
            ])
            ->assertStatus(409);

        $audit = \App\Models\AuditLog::where('store_id', $this->store->id)
            ->where('action', 'store_theme_draft_conflict')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit, 'Publish conflict must be written to the audit log.');
        $this->assertSame('base_revision', $audit->metadata['reason'] ?? null);
        $this->assertSame($draft->id, $audit->entity_id);
    }

    public function test_discard_draft_is_audited(): void
    {
        $service = app(ThemeDraftService::class);
        $draft   = $service->getOrCreate($this->store, $this->manager);

        $this->actingAs($this->manager)
            ->deleteJson(route('store.admin.appearance.draft.discard', ['store_slug' => $this->store->slug]))
            ->assertOk();

        $audit = \App\Models\AuditLog::where('store_id', $this->store->id)
            ->where('action', 'store_theme_draft_discard')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit, 'Draft discard must be written to the audit log.');
        $this->assertSame($draft->id, $audit->entity_id);
    }
}
