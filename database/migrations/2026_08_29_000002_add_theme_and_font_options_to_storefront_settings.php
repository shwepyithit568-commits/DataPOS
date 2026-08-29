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
            if (! Schema::hasColumn('storefront_settings', 'font_preset')) {
                $table->string('font_preset', 50)->default('outfit')->after('theme_dark_mode');
            }
            if (! Schema::hasColumn('storefront_settings', 'grid_density')) {
                $table->string('grid_density', 50)->default('compact')->after('font_preset');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            if (Schema::hasColumn('storefront_settings', 'font_preset')) {
                $table->dropColumn('font_preset');
            }
            if (Schema::hasColumn('storefront_settings', 'grid_density')) {
                $table->dropColumn('grid_density');
            }
        });
    }
};
