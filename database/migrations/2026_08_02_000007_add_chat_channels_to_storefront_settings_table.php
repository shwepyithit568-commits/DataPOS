<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('storefront_settings', 'chat_channels')) {
            return;
        }

        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->json('chat_channels')->nullable()->after('chat_button_icon');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('storefront_settings', 'chat_channels')) {
            return;
        }

        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->dropColumn('chat_channels');
        });
    }
};
