<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make the products.sku column case-sensitive (binary collation).
 *
 * SQLite already compares strings binary, but MySQL/MariaDB defaults to
 * utf8mb4_unicode_ci (case-insensitive), which made the products_store_sku_unique
 * index reject case/whitespace-variant legacy SKUs (e.g. "SKU-001" vs "sku-001")
 * at insert time. Those variants must be insertable so the
 * products:audit-sku-uniqueness / products:cleanup-skus remediation flow
 * (used to clean AppSheet/legacy data before enforcing constraints) can detect
 * and fix them. Exact duplicate SKUs remain blocked.
 */
return new class extends Migration
{
    public function up(): void
    {
        // SQLite compares strings binary by default (already case-sensitive),
        // and it has no utf8mb4_bin collation, so this change is MySQL-only.
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('products', function (Blueprint $table) {
                $table->string('sku')->collation('utf8mb4_bin')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('products', function (Blueprint $table) {
                $table->string('sku')->collation('utf8mb4_unicode_ci')->change();
            });
        }
    }
};
