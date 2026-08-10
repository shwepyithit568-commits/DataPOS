<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('storefront_settings', 'how_to_intro')) {
            return;
        }

        Schema::table('storefront_settings', function (Blueprint $table) {
            // "How to Order" page content — editable from Admin (per store).
            $table->text('how_to_intro')->nullable()->after('payment_info');
            // JSON array: [{ "icon": "📱", "title": "...", "desc": "..." }, ...]
            $table->json('how_to_steps')->nullable()->after('how_to_intro');
            // JSON array: [{ "title": "...", "url": "https://youtube.com/watch?v=..." }, ...]
            $table->json('how_to_videos')->nullable()->after('how_to_steps');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('storefront_settings', 'how_to_intro')) {
            return;
        }

        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->dropColumn(['how_to_intro', 'how_to_steps', 'how_to_videos']);
        });
    }
};
