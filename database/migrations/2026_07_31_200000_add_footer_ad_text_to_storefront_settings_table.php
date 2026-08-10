<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('storefront_settings', 'footer_ad_text')) {
            return;
        }

        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->string('footer_ad_text', 255)->nullable()->after('payment_info');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('storefront_settings', 'footer_ad_text')) {
            return;
        }

        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->dropColumn('footer_ad_text');
        });
    }
};
