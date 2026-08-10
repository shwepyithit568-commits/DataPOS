<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a unique (store_id, name) constraint.
     *
     * SAFETY AUDIT (run before adding — reported in 2026-08-02_FIXES.md):
     *   SELECT LOWER(TRIM(name)) n, COUNT(*) c FROM brands GROUP BY store_id, n HAVING c > 1;  → 0 rows
     *   SELECT store_id, slug, COUNT(*) c FROM brands GROUP BY store_id, slug HAVING c > 1;    → 0 rows
     * Total brands at audit time: 61 — no collisions, so the constraint can be
     * added without a cleanup step.
     *
     * Note: MySQL (production) compares names case-insensitively via the
     * utf8mb4_*_ci collation, which matches the controller's normalized
     * LOWER(TRIM(name)) check. SQLite (local dev) indexes case-sensitively,
     * but the controller-level rule still blocks case-only variants there.
     */
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->unique(['store_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropUnique(['brands_store_id_name_unique']);
        });
    }
};
