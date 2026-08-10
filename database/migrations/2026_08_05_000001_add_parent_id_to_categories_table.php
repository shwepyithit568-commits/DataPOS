<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a self-referencing parent column so categories can form a
     * Category (parent) → Sub-Category (child) tree. Kept as a plain
     * nullable index (no DB-level FK) so the migration is portable between
     * SQLite (local) and MySQL (production); the model owns the relation and
     * CategoryController detaches children on delete.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->index()->after('store_id');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('parent_id');
        });
    }
};
