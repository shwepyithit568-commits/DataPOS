<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Constraint-only migration.
     *
     * This migration intentionally never rewrites SKUs, deletes Glass Finder rows,
     * or silently resolves production data collisions. Run the audit/cleanup
     * Artisan commands first, then apply constraints only when data is clean.
     */
    public function up(): void
    {
        $duplicateProducts = DB::table('products')
            ->select('store_id', 'sku', DB::raw('COUNT(*) as count'))
            ->groupBy('store_id', 'sku')
            ->having('count', '>', 1)
            ->get();

        if ($duplicateProducts->isNotEmpty()) {
            throw new RuntimeException('Duplicate product SKUs exist. Run products:audit-sku-uniqueness and resolve them before adding constraints.');
        }

        $duplicateGlass = DB::table('glass_finder_items')
            ->select('store_id', 'phone_model', 'normalized_glass_code', DB::raw('COUNT(*) as count'))
            ->groupBy('store_id', 'phone_model', 'normalized_glass_code')
            ->having('count', '>', 1)
            ->get();

        if ($duplicateGlass->isNotEmpty()) {
            throw new RuntimeException('Duplicate Glass Finder business keys exist. Run glass-finder:audit-normalization and resolve them before adding constraints.');
        }

        $this->addUniqueIfMissing('products', ['store_id', 'sku'], 'products_store_sku_unique');
        $this->addUniqueIfMissing('glass_finder_items', ['store_id', 'phone_model', 'normalized_glass_code'], 'store_phone_glass_unique');
    }

    private function addUniqueIfMissing(string $tableName, array $columns, string $indexName): void
    {
        try {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                $table->unique($columns, $indexName);
            });
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if (! str_contains($message, 'already exists') && ! str_contains($message, 'Duplicate key name')) {
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_store_sku_unique');
        });

        Schema::table('glass_finder_items', function (Blueprint $table) {
            // MySQL (InnoDB) uses this unique index to support the store_id
            // foreign key — it is the only index whose leftmost column is
            // store_id. Drop the FK first, then the unique index, then restore
            // the FK so the rollback stays valid on both SQLite and MySQL.
            $table->dropForeign(['store_id']);
            $table->dropUnique('store_phone_glass_unique');
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
        });
    }
};
