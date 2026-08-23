<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add storefront theme / colour-scheme columns to storefront_settings.
     *
     * theme_preset        — named palette ('sky','violet','rose','emerald','amber','custom')
     * theme_primary_color — main brand colour (buttons, links, highlights)  #rrggbb
     * theme_accent_color  — secondary / CTA colour                          #rrggbb
     * theme_header_bg     — header background override                      #rrggbb
     * theme_dark_mode     — force display mode: 'auto' | 'light' | 'dark'
     */
    public function up(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->string('theme_preset', 30)->nullable()->after('default_language');
            $table->string('theme_primary_color', 7)->nullable()->after('theme_preset');
            $table->string('theme_accent_color', 7)->nullable()->after('theme_primary_color');
            $table->string('theme_header_bg', 7)->nullable()->after('theme_accent_color');
            $table->string('theme_dark_mode', 10)->nullable()->after('theme_header_bg');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->dropColumn([
                'theme_preset',
                'theme_primary_color',
                'theme_accent_color',
                'theme_header_bg',
                'theme_dark_mode',
            ]);
        });
    }
};
