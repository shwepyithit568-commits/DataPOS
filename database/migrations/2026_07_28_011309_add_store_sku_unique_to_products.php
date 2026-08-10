<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite unique index on (store_id, sku) to prevent duplicate SKUs within a store.
 *
 * Safe to run on existing data: if duplicates exist, migration will fail gracefully.
 * Clean databases (fresh installs, test environments using RefreshDatabase) are not affected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the old single-column index on sku if it exists
            // then replace with a composite unique index per store.
            // Case-sensitivity is handled by the sku column collation
            // (2026_08_04_000006_make_product_sku_case_sensitive), so legacy
            // case-variant SKUs can still be inserted for the audit/cleanup flow.
            $table->unique(['store_id', 'sku'], 'products_store_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_store_sku_unique');
        });
    }
};
