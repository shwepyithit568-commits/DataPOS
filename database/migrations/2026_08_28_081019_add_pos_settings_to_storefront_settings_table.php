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
            if (! Schema::hasColumn('storefront_settings', 'pos_settings')) {
                $table->json('pos_settings')->nullable()->after('pos_override_pin_threshold');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            if (Schema::hasColumn('storefront_settings', 'pos_settings')) {
                $table->dropColumn('pos_settings');
            }
        });
    }
};
