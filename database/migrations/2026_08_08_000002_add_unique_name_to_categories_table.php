<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unique (store_id, name) constraint for categories.
     *
     * AUDIT (2026-08-08, before adding): no duplicate normalized names and no
     * duplicate slugs per store exist in either the local or production
     * database (39 categories, 0 collisions) — safe to add.
     *
     * Decision: category display names are unique GLOBAL per store — a Main
     * Category and a Sub-category may NOT share the same display name. This
     * matches the controller's normalized (LOWER(TRIM(name))) comparison and
     * keeps the two-level tree unambiguous. On MySQL (production) the default
     * case-insensitive collation enforces case-insensitive uniqueness; the
     * controller enforces the same normalized rule on SQLite (local/testing).
     *
     * The existing unique (store_id, slug) constraint remains untouched.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unique(['store_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['categories_store_id_name_unique']);
        });
    }
};
