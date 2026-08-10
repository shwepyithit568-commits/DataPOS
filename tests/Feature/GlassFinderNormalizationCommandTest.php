<?php

namespace Tests\Feature;

use App\Models\GlassFinderItem;
use App\Models\Store;
use App\Services\GlassFinderNormalizationAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GlassFinderNormalizationCommandTest extends TestCase
{
    use RefreshDatabase;

    private function legacyItem(Store $store, array $overrides = []): int
    {
        return DB::table('glass_finder_items')->insertGetId(array_merge([
            'store_id' => $store->id,
            'brand' => 'Apple',
            'phone_model' => 'iPhone 15 Pro Max',
            'glass_code' => 'GX-001',
            'normalized_glass_code' => 'GX-001',
            'stock_status' => 'in_stock',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    public function test_audit_detects_already_normalized_row(): void
    {
        $store = Store::create(['name' => 'Audit Store', 'slug' => 'audit-store']);
        GlassFinderItem::create([
            'store_id' => $store->id,
            'brand' => 'Apple',
            'phone_model' => 'iPhone 15',
            'glass_code' => 'GX-001',
            'stock_status' => 'in_stock',
        ]);

        $analysis = app(GlassFinderNormalizationAudit::class)->analyze('audit-store');

        $this->assertEquals(1, $analysis['summary']['rows_inspected']);
        $this->assertEquals(1, $analysis['summary']['already_normalized_rows']);
        $this->assertEquals(0, $analysis['summary']['rows_requiring_updates']);
    }

    public function test_audit_detects_uppercase_dash_legacy_value(): void
    {
        $store = Store::create(['name' => 'Legacy Store', 'slug' => 'legacy-store']);
        $this->legacyItem($store, [
            'glass_code' => 'G-I15PM-B',
            'normalized_glass_code' => 'G-I15PM-B',
        ]);

        $analysis = app(GlassFinderNormalizationAudit::class)->analyze('legacy-store');

        $this->assertEquals(1, $analysis['summary']['rows_requiring_updates']);
        $this->assertEquals(1, $analysis['summary']['safe_update_rows']);
        $this->assertEquals('gi15pmb', $analysis['safe_update_rows']->first()['expected_normalized_glass_code']);
    }

    public function test_audit_classifies_exact_duplicate_collision(): void
    {
        $store = Store::create(['name' => 'Exact Store', 'slug' => 'exact-store']);
        $this->legacyItem($store, ['glass_code' => 'GX-001', 'normalized_glass_code' => 'GX-001']);
        $this->legacyItem($store, ['glass_code' => 'GX 001', 'normalized_glass_code' => 'GX 001']);

        $analysis = app(GlassFinderNormalizationAudit::class)->analyze('exact-store');

        $this->assertEquals(1, $analysis['summary']['exact_duplicate_groups']);
        $this->assertEquals(0, $analysis['summary']['conflicting_duplicate_groups']);
        $this->assertEquals(2, $analysis['summary']['rows_blocked_from_automatic_update']);
    }

    public function test_audit_classifies_conflicting_duplicate_collision(): void
    {
        $store = Store::create(['name' => 'Conflict Store', 'slug' => 'conflict-store']);
        $this->legacyItem($store, ['glass_code' => 'GX-001', 'normalized_glass_code' => 'GX-001', 'stock_status' => 'in_stock']);
        $this->legacyItem($store, ['glass_code' => 'GX 001', 'normalized_glass_code' => 'GX 001', 'stock_status' => 'out_of_stock']);

        $analysis = app(GlassFinderNormalizationAudit::class)->analyze('conflict-store');

        $this->assertEquals(0, $analysis['summary']['exact_duplicate_groups']);
        $this->assertEquals(1, $analysis['summary']['conflicting_duplicate_groups']);
        $this->assertEquals(2, $analysis['summary']['rows_blocked_from_automatic_update']);
    }

    public function test_same_code_with_different_models_remains_valid_compatibility_group(): void
    {
        $store = Store::create(['name' => 'Compatible Store', 'slug' => 'compatible-store']);
        $this->legacyItem($store, ['phone_model' => 'iPhone 15 Pro Max', 'glass_code' => 'GX-001', 'normalized_glass_code' => 'GX-001']);
        $this->legacyItem($store, ['phone_model' => 'iPhone 15 Pro', 'glass_code' => 'GX 001', 'normalized_glass_code' => 'GX 001']);

        $analysis = app(GlassFinderNormalizationAudit::class)->analyze('compatible-store');

        $this->assertEquals(1, $analysis['summary']['valid_compatibility_groups']);
        $this->assertEquals(0, $analysis['summary']['exact_duplicate_groups']);
        $this->assertEquals(0, $analysis['summary']['conflicting_duplicate_groups']);
        $this->assertEquals(2, $analysis['summary']['safe_update_rows']);
    }

    public function test_dry_run_command_performs_no_writes(): void
    {
        $store = Store::create(['name' => 'Dry Store', 'slug' => 'dry-store']);
        $id = $this->legacyItem($store, ['glass_code' => 'G-I15PM-B', 'normalized_glass_code' => 'G-I15PM-B']);

        $this->artisan('glass-finder:audit-normalization', ['--store' => 'dry-store'])
            ->expectsOutputToContain('Rows requiring updates: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('glass_finder_items', [
            'id' => $id,
            'normalized_glass_code' => 'G-I15PM-B',
        ]);
    }

    public function test_apply_updates_safe_rows(): void
    {
        $store = Store::create(['name' => 'Apply Store', 'slug' => 'apply-store']);
        $id = $this->legacyItem($store, ['glass_code' => 'G-I15PM-B', 'normalized_glass_code' => 'G-I15PM-B']);

        $this->artisan('glass-finder:normalize', ['--store' => 'apply-store', '--apply' => true])
            ->expectsOutputToContain('Rows updated: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('glass_finder_items', [
            'id' => $id,
            'glass_code' => 'G-I15PM-B',
            'normalized_glass_code' => 'gi15pmb',
        ]);
    }

    public function test_apply_skips_collision_rows(): void
    {
        $store = Store::create(['name' => 'Skip Store', 'slug' => 'skip-store']);
        $first = $this->legacyItem($store, ['glass_code' => 'GX-001', 'normalized_glass_code' => 'GX-001']);
        $second = $this->legacyItem($store, ['glass_code' => 'GX 001', 'normalized_glass_code' => 'GX 001']);

        $this->artisan('glass-finder:normalize', ['--store' => 'skip-store', '--apply' => true])
            ->expectsOutputToContain('Rows blocked from automatic update: 2')
            ->expectsOutputToContain('Rows updated: 0')
            ->assertExitCode(0);

        $this->assertDatabaseHas('glass_finder_items', ['id' => $first, 'normalized_glass_code' => 'GX-001']);
        $this->assertDatabaseHas('glass_finder_items', ['id' => $second, 'normalized_glass_code' => 'GX 001']);
    }

    public function test_command_is_idempotent(): void
    {
        $store = Store::create(['name' => 'Idempotent Store', 'slug' => 'idempotent-store']);
        $this->legacyItem($store, ['glass_code' => 'G-I15PM-B', 'normalized_glass_code' => 'G-I15PM-B']);

        $this->artisan('glass-finder:normalize', ['--store' => 'idempotent-store', '--apply' => true])
            ->expectsOutputToContain('Rows updated: 1')
            ->assertExitCode(0);

        $this->artisan('glass-finder:normalize', ['--store' => 'idempotent-store', '--apply' => true])
            ->expectsOutputToContain('Rows updated: 0')
            ->assertExitCode(0);
    }

    public function test_store_scoped_execution_updates_only_selected_store(): void
    {
        $storeA = Store::create(['name' => 'Scoped Store A', 'slug' => 'scoped-store-a']);
        $storeB = Store::create(['name' => 'Scoped Store B', 'slug' => 'scoped-store-b']);
        $idA = $this->legacyItem($storeA, ['glass_code' => 'A-CODE', 'normalized_glass_code' => 'A-CODE']);
        $idB = $this->legacyItem($storeB, ['glass_code' => 'B-CODE', 'normalized_glass_code' => 'B-CODE']);

        $this->artisan('glass-finder:normalize', ['--store' => 'scoped-store-a', '--apply' => true])
            ->expectsOutputToContain('Rows updated: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('glass_finder_items', ['id' => $idA, 'normalized_glass_code' => 'acode']);
        $this->assertDatabaseHas('glass_finder_items', ['id' => $idB, 'normalized_glass_code' => 'B-CODE']);
    }

    public function test_cross_store_same_business_key_does_not_collide(): void
    {
        $storeA = Store::create(['name' => 'Cross Store A', 'slug' => 'cross-store-a']);
        $storeB = Store::create(['name' => 'Cross Store B', 'slug' => 'cross-store-b']);
        $this->legacyItem($storeA, ['glass_code' => 'GX-001', 'normalized_glass_code' => 'GX-001']);
        $this->legacyItem($storeB, ['glass_code' => 'GX 001', 'normalized_glass_code' => 'GX 001']);

        $analysis = app(GlassFinderNormalizationAudit::class)->analyze();

        $this->assertEquals(0, $analysis['summary']['exact_duplicate_groups']);
        $this->assertEquals(0, $analysis['summary']['conflicting_duplicate_groups']);
        $this->assertEquals(2, $analysis['summary']['safe_update_rows']);
    }
}
