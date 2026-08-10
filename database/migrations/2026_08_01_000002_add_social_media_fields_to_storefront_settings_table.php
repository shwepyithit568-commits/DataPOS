<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('storefront_settings', 'facebook_url')) {
            return;
        }

        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->string('facebook_url', 255)->nullable()->after('telegram_username');
            $table->string('youtube_url', 255)->nullable()->after('facebook_url');
            $table->string('tiktok_url', 255)->nullable()->after('youtube_url');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('storefront_settings', 'facebook_url')) {
            return;
        }

        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->dropColumn(['facebook_url', 'youtube_url', 'tiktok_url']);
        });
    }
};
