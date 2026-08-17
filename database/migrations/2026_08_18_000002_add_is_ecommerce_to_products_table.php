<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Per-product "sell online" flag (old-project is_ecommerce):
            // default TRUE so every existing product stays on the storefront
            // (the current behavior) until a shop marks items counter-only.
            $table->boolean('is_ecommerce')->default(true)->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_ecommerce');
        });
    }
};
