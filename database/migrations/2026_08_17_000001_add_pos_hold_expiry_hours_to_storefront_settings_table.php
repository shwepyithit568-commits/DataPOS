<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-store POS held-sale auto-expiry window (hours). Null/absent means
     * the global 24h default; 0 disables auto-expiry for the store.
     */
    public function up(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('pos_hold_expiry_hours')->nullable()->default(24)->after('default_language');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->dropColumn('pos_hold_expiry_hours');
        });
    }
};
