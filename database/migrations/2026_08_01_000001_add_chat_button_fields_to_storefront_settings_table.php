<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('storefront_settings', 'chat_button_label')) {
            return;
        }

        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->string('chat_button_label', 50)->nullable()->after('telegram_username');
            $table->string('chat_button_url', 255)->nullable()->after('chat_button_label');
            $table->string('chat_button_icon', 10)->nullable()->after('chat_button_url');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('storefront_settings', 'chat_button_label')) {
            return;
        }

        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->dropColumn(['chat_button_label', 'chat_button_url', 'chat_button_icon']);
        });
    }
};
