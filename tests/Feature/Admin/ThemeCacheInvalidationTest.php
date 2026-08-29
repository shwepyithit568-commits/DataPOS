<?php

namespace Tests\Feature\Admin;

use App\Listeners\InvalidateStorefrontCache;
use App\Models\AuditLog;
use App\Models\Store;
use App\Models\StoreThemeRevision;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\Services\ThemePublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Phase T4 — Publish/Rollback Integration Completion
 *
 * 1. Commit-after cache invalidation: publish/rollback flips ONLY the target
 *    store's public pages to immediate revalidation (max-age=0 window); other
 *    stores and steady-state keep max-age=60. Never Cache::flush().
 * 2. Publish failure atomicity: a failure inside the transaction leaves the
 *    published config, revision history and audit log untouched.
 */
class ThemeCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected Store $storeB;
    protected User  $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['slug' => 'cache-test-a', 'name' => 'Cache A', 'is_active' => true]);
        $this->storeB = Store::create(['slug' => 'cache-test-b', 'name' => 'Cache B', 'is_active' => true]);

        foreach ([$this->store, $this->storeB] as $s) {
            StorefrontSetting::create([
                'store_id'            => $s->id,
                'store_name'          => $s->name,
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
        }

        $this->manager = User::create(['name' => 'Cache Mgr', 'phone' => '09222334455', 'password' => bcrypt('p'), 'role' => 'customer']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    // -------------------------------------------------------------------------
    // 1. Commit-after invalidation — bump marker set for the target store only
    // -------------------------------------------------------------------------

    public function test_publish_sets_revalidation_marker_for_target_store_only(): void
    {
        $publisher = app(ThemePublisher::class);

        $publisher->publish($this->store, ['theme_preset' => 'retail_trust'], $this->manager);

        $this->assertTrue(Cache::has(InvalidateStorefrontCache::bumpKey($this->store->id)));
        $this->assertFalse(Cache::has(InvalidateStorefrontCache::bumpKey($this->storeB->id)));
    }

    public function test_rollback_sets_revalidation_marker(): void
    {
        $publisher = app(ThemePublisher::class);

        $publishRev = $publisher->publish($this->store, ['theme_preset' => 'retail_trust'], $this->manager);
        Cache::forget(InvalidateStorefrontCache::bumpKey($this->store->id));
        $this->assertFalse(Cache::has(InvalidateStorefrontCache::bumpKey($this->store->id)));

        $publisher->rollback($this->store, $publishRev, $this->manager);

        $this->assertTrue(Cache::has(InvalidateStorefrontCache::bumpKey($this->store->id)));
    }

    // -------------------------------------------------------------------------
    // 2. Storefront Cache-Control — max-age=0 after publish, max-age=60 otherwise
    // -------------------------------------------------------------------------

    public function test_storefront_revalidates_immediately_after_publish(): void
    {
        // Before any publish: normal max-age=60
        $before = $this->get('/store/' . $this->store->slug);
        $before->assertOk();
        $this->assertStringContainsString('max-age=60', $before->headers->get('Cache-Control', ''));

        app(ThemePublisher::class)->publish($this->store, ['theme_preset' => 'retail_trust'], $this->manager);

        // Target store: immediate revalidation
        $after = $this->get('/store/' . $this->store->slug);
        $after->assertOk();
        $this->assertStringContainsString('max-age=0', $after->headers->get('Cache-Control', ''));

        // Other store: unaffected, still max-age=60
        $other = $this->get('/store/' . $this->storeB->slug);
        $other->assertOk();
        $this->assertStringContainsString('max-age=60', $other->headers->get('Cache-Control', ''));
    }

    public function test_marker_expiry_restores_normal_max_age(): void
    {
        app(ThemePublisher::class)->publish($this->store, ['theme_preset' => 'retail_trust'], $this->manager);

        // Simulate the 90s window expiring
        Cache::forget(InvalidateStorefrontCache::bumpKey($this->store->id));

        $response = $this->get('/store/' . $this->store->slug);
        $response->assertOk();
        $this->assertStringContainsString('max-age=60', $response->headers->get('Cache-Control', ''));
    }

    // -------------------------------------------------------------------------
    // 3. Publish failure atomicity — nothing persists on failure
    // -------------------------------------------------------------------------

    public function test_publish_failure_leaves_published_config_and_history_unchanged(): void
    {
        $publisher = app(ThemePublisher::class);

        // Break revision creation inside the transaction (fires before insert)
        StoreThemeRevision::saving(function () {
            throw new \RuntimeException('simulated revision failure');
        });

        try {
            $publisher->publish($this->store, ['theme_preset' => 'retail_trust'], $this->manager);
            $this->fail('Expected publish to throw.');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated revision failure', $e->getMessage());
        }

        // Published config unchanged
        $setting = StorefrontSetting::where('store_id', $this->store->id)->first();
        $this->assertSame('marketplace_pro', $setting->theme_preset);
        $this->assertSame('#0ea5e9', $setting->theme_primary_color);

        // No revision rows, no publish audit rows
        $this->assertDatabaseCount('store_theme_revisions', 0);
        $this->assertDatabaseMissing('audit_logs', ['store_id' => $this->store->id, 'action' => 'store_theme_publish']);

        // No cache marker was set (event dispatches only after commit)
        $this->assertFalse(Cache::has(InvalidateStorefrontCache::bumpKey($this->store->id)));
    }
}
