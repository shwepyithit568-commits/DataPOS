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
        Schema::table('storefront_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('storefront_settings', 'currency_settings')) {
                $table->json('currency_settings')->nullable()->after('pos_settings');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            if (Schema::hasColumn('storefront_settings', 'currency_settings')) {
                $table->dropColumn('currency_settings');
            }
        });
    }
};
