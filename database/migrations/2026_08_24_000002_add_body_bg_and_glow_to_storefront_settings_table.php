<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add body background color and ambient glow style settings.
     */
    public function up(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->string('theme_body_bg', 7)->nullable()->after('theme_header_bg');
            $table->string('theme_glow_style', 20)->nullable()->default('vivid')->after('theme_body_bg');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->dropColumn(['theme_body_bg', 'theme_glow_style']);
        });
    }
};
