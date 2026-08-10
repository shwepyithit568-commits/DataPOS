<?php

namespace Tests\Feature;

use App\Models\DataMaintenanceLog;
use App\Models\GlassFinderItem;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductSkuUniquenessAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionBlockerRemediationTest extends TestCase
{
    use RefreshDatabase;

    private function legacyGlassItem(Store $store, array $overrides = []): int
    {
        return DB::table('glass_finder_items')->insertGetId(array_merge([
            'store_id' => $store->id,
            'brand' => 'Apple',
            'phone_model' => 'iPhone 15 Pro Max',
            'glass_code' => 'G-I15PM-B',
            'normalized_glass_code' => 'G-I15PM-B',
            'stock_status' => 'in_stock',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function product(Store $store, string $sku, string $name = 'Phone'): Product
    {
        return Product::create([
            'store_id' => $store->id,
            'sku' => $sku,
            'name' => $name,
            'slug' => str()->slug($name . '-' . str()->random(5)),
            'retail_price' => 100,
            'wholesale_price' => 90,
            'stock_status' => 'in_stock',
        ]);
    }

    public function test_product_sku_audit_classifies_without_writes(): void
    {
        $store = Store::create(['name' => 'SKU Store', 'slug' => 'sku-store']);
        $this->product($store, 'SKU-001', 'Phone A');
        $this->product($store, 'sku-001', 'Phone B');
        $this->product($store, 'SKU 002', 'Phone C');
        $this->product($store, 'SKU002', 'Phone D');
        $this->product($store, '', 'Blank SKU');

        $before = Product::pluck('sku', 'id')->all();
        $analysis = app(ProductSkuUniquenessAudit::class)->analyze('sku-store');

        $this->assertEquals($before, Product::pluck('sku', 'id')->all());
        $this->assertEquals(5, $analysis['summary']['total_products_inspected']);
        $this->assertEquals(1, $analysis['summary']['blank_sku_rows']);
        $this->assertEquals(1, $analysis['summary']['case_only_duplicate_groups']);
        $this->assertEquals(1, $analysis['summary']['whitespace_normalized_duplicate_groups']);
        $this->assertContains($store->slug, $analysis['summary']['affected_stores']);
    }

    public function test_sku_cleanup_requires_mapping_and_dry_run_writes_nothing(): void
    {
        $store = Store::create(['name' => 'Cleanup Store', 'slug' => 'cleanup-store']);
        $first = $this->product($store, 'SKU-001', 'Phone A');
        $second = $this->product($store, 'sku-001', 'Phone B');
        $mappingPath = tempnam(sys_get_temp_dir(), 'sku_map_') . '.json';
        file_put_contents($mappingPath, json_encode([
            (string) $first->id => 'SKU-001-A',
            (string) $second->id => 'SKU-001-B',
        ]));

        $this->artisan('products:cleanup-skus', ['--store' => 'cleanup-store', '--map' => $mappingPath])
            ->expectsOutputToContain('Product SKU cleanup DRY-RUN summary')
            ->assertExitCode(0);

        $this->assertDatabaseHas('products', ['id' => $first->id, 'sku' => 'SKU-001']);
        $this->assertDatabaseHas('products', ['id' => $second->id, 'sku' => 'sku-001']);
        $this->assertDatabaseCount('data_maintenance_logs', 0);
    }

    public function test_sku_cleanup_apply_logs_old_and_new_values(): void
    {
        $store = Store::create(['name' => 'Apply SKU Store', 'slug' => 'apply-sku-store']);
        $first = $this->product($store, 'SKU-001', 'Phone A');
        $second = $this->product($store, 'sku-001', 'Phone B');
        $mappingPath = tempnam(sys_get_temp_dir(), 'sku_map_') . '.json';
        file_put_contents($mappingPath, json_encode([
            (string) $first->id => 'SKU-001-A',
            (string) $second->id => 'SKU-001-B',
        ]));

        $this->artisan('products:cleanup-skus', ['--store' => 'apply-sku-store', '--map' => $mappingPath, '--apply' => true])
            ->expectsOutputToContain('Execution ID:')
            ->assertExitCode(0);

        $this->assertDatabaseHas('products', ['id' => $first->id, 'sku' => 'SKU-001-A']);
        $this->assertDatabaseHas('data_maintenance_logs', [
            'operation' => 'product_sku_cleanup',
            'record_type' => Product::class,
            'record_id' => $first->id,
            'field_name' => 'sku',
            'old_value' => 'SKU-001',
            'new_value' => 'SKU-001-A',
        ]);
    }

    public function test_glass_finder_duplicate_audit_classifies_exact_conflict_and_compatibility(): void
    {
        $store = Store::create(['name' => 'Glass Audit Store', 'slug' => 'glass-audit-store']);
        $this->legacyGlassItem($store, ['phone_model' => 'Same Model', 'glass_code' => 'GX-001', 'normalized_glass_code' => 'GX-001', 'stock_status' => 'in_stock']);
        $this->legacyGlassItem($store, ['phone_model' => 'Same Model', 'glass_code' => 'GX 001', 'normalized_glass_code' => 'GX 001', 'stock_status' => 'in_stock']);
        $this->legacyGlassItem($store, ['phone_model' => 'Conflict Model', 'glass_code' => 'CF-001', 'normalized_glass_code' => 'CF-001', 'stock_status' => 'in_stock']);
        $this->legacyGlassItem($store, ['phone_model' => 'Conflict Model', 'glass_code' => 'CF 001', 'normalized_glass_code' => 'CF 001', 'stock_status' => 'out_of_stock']);
        $this->legacyGlassItem($store, ['phone_model' => 'Other Model', 'glass_code' => 'GX-001', 'normalized_glass_code' => 'GX-001-A']);

        $this->artisan('glass-finder:audit-duplicates', ['--store' => 'glass-audit-store'])
            ->expectsOutputToContain('Exact duplicate groups: 1')
            ->expectsOutputToContain('Conflicting duplicate groups: 1')
            ->expectsOutputToContain('Valid compatibility groups: 1')
            ->assertExitCode(0);
    }

    public function test_normalization_logs_execution_old_new_values_and_unique_execution_ids(): void
    {
        $store = Store::create(['name' => 'Normalize Store', 'slug' => 'normalize-store']);
        $firstId = $this->legacyGlassItem($store, ['glass_code' => 'G-I15PM-B', 'normalized_glass_code' => 'G-I15PM-B']);

        $this->artisan('glass-finder:normalize', ['--store' => 'normalize-store', '--apply' => true])
            ->expectsOutputToContain('Execution ID:')
            ->assertExitCode(0);

        $log = DataMaintenanceLog::where('record_id', $firstId)->firstOrFail();
        $this->assertSame('glass_finder_normalize', $log->operation);
        $this->assertSame('G-I15PM-B', $log->old_value);
        $this->assertSame('gi15pmb', $log->new_value);
        $this->assertNotEmpty($log->execution_id);

        $secondId = $this->legacyGlassItem($store, ['phone_model' => 'iPhone 15 Pro', 'glass_code' => 'G-I15P-F', 'normalized_glass_code' => 'G-I15P-F']);

        $this->artisan('glass-finder:normalize', ['--store' => 'normalize-store', '--apply' => true])
            ->expectsOutputToContain('Execution ID:')
            ->assertExitCode(0);

        $secondLog = DataMaintenanceLog::where('record_id', $secondId)->firstOrFail();
        $this->assertNotSame($log->execution_id, $secondLog->execution_id);
    }

    public function test_rollback_dry_run_and_apply_restore_values(): void
    {
        $store = Store::create(['name' => 'Rollback Store', 'slug' => 'rollback-store']);
        $itemId = $this->legacyGlassItem($store, ['glass_code' => 'G-I15PM-B', 'normalized_glass_code' => 'G-I15PM-B']);

        app(\App\Services\GlassFinderNormalizationAudit::class)->apply('rollback-store');
        $executionId = DataMaintenanceLog::where('record_id', $itemId)->value('execution_id');

        $this->artisan('data-maintenance:rollback', ['execution_id' => $executionId, '--store' => 'rollback-store'])
            ->expectsOutputToContain('Data maintenance rollback DRY-RUN summary')
            ->expectsOutputToContain('Reversible rows: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('glass_finder_items', ['id' => $itemId, 'normalized_glass_code' => 'gi15pmb']);

        $this->artisan('data-maintenance:rollback', ['execution_id' => $executionId, '--store' => 'rollback-store', '--apply' => true])
            ->expectsOutputToContain('Rows restored: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('glass_finder_items', ['id' => $itemId, 'normalized_glass_code' => 'G-I15PM-B']);
    }

    public function test_rollback_skips_records_changed_after_maintenance(): void
    {
        $store = Store::create(['name' => 'Skip Rollback Store', 'slug' => 'skip-rollback-store']);
        $itemId = $this->legacyGlassItem($store, ['glass_code' => 'G-I15PM-B', 'normalized_glass_code' => 'G-I15PM-B']);

        app(\App\Services\GlassFinderNormalizationAudit::class)->apply('skip-rollback-store');
        $executionId = DataMaintenanceLog::where('record_id', $itemId)->value('execution_id');

        DB::table('glass_finder_items')->where('id', $itemId)->update(['normalized_glass_code' => 'manualchange']);

        $this->artisan('data-maintenance:rollback', ['execution_id' => $executionId, '--store' => 'skip-rollback-store', '--apply' => true])
            ->expectsOutputToContain('Rows restored: 0')
            ->expectsOutputToContain('Skipped rows: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('glass_finder_items', ['id' => $itemId, 'normalized_glass_code' => 'manualchange']);
    }

    public function test_store_scoped_rollback_does_not_touch_other_store(): void
    {
        $storeA = Store::create(['name' => 'Rollback A', 'slug' => 'rollback-a']);
        $storeB = Store::create(['name' => 'Rollback B', 'slug' => 'rollback-b']);
        $itemA = $this->legacyGlassItem($storeA, ['glass_code' => 'A-CODE', 'normalized_glass_code' => 'A-CODE']);
        $itemB = $this->legacyGlassItem($storeB, ['glass_code' => 'B-CODE', 'normalized_glass_code' => 'B-CODE']);

        $resultA = app(\App\Services\GlassFinderNormalizationAudit::class)->apply('rollback-a');
        app(\App\Services\GlassFinderNormalizationAudit::class)->apply('rollback-b');

        $this->artisan('data-maintenance:rollback', ['execution_id' => $resultA['execution_id'], '--store' => 'rollback-a', '--apply' => true])
            ->expectsOutputToContain('Rows restored: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('glass_finder_items', ['id' => $itemA, 'normalized_glass_code' => 'A-CODE']);
        $this->assertDatabaseHas('glass_finder_items', ['id' => $itemB, 'normalized_glass_code' => 'bcode']);
    }

    public function test_constraint_only_migration_has_no_mutation_logic_and_blocks_dirty_data(): void
    {
        $path = database_path('migrations/2026_07_28_050000_ensure_safe_unique_constraints.php');
        $contents = file_get_contents($path);
        $this->assertStringNotContainsString('->update(', $contents);
        $this->assertStringNotContainsString('->delete(', $contents);

        Schema::table('products', function ($table) {
            $table->dropUnique('products_store_sku_unique');
        });
        Schema::table('glass_finder_items', function ($table) {
            // MySQL requires the FK to be dropped before the unique index it
            // supports (the unique is the only store_id index on this table).
            $table->dropForeign(['store_id']);
            $table->dropUnique('store_phone_glass_unique');
        });

        $store = Store::create(['name' => 'Dirty Store', 'slug' => 'dirty-store']);
        $this->product($store, 'DUP-SKU', 'Dirty A');
        $this->product($store, 'DUP-SKU', 'Dirty B');

        $migration = include $path;

        try {
            $migration->up();
            $this->fail('Constraint-only migration should block dirty data.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Duplicate product SKUs exist', $e->getMessage());
        }

        $this->assertEquals(2, Product::where('sku', 'DUP-SKU')->count());
    }
}
