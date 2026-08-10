<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Structured option data for each variant (e.g. [{label: "Storage", value: "256GB"},
     * {label: "Color", value: "Black"}]). The storefront renders grouped selector rows
     * from this instead of a flat pill list. Old rows keep NULL → flat fallback.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->json('attributes')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('attributes');
        });
    }
};
