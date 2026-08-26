<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('name');
            $table->index(['store_id', 'code'], 'brands_store_code_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('name');
            $table->index(['store_id', 'code'], 'categories_store_code_idx');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->string('business_type', 50)->default('mobile_tech')->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex('brands_store_code_idx');
            $table->dropColumn('code');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_store_code_idx');
            $table->dropColumn('code');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('business_type');
        });
    }
};
