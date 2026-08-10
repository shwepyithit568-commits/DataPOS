<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('storefront_settings', 'tagline')) {
            return;
        }

        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->string('tagline', 160)->nullable()->after('store_name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('storefront_settings', 'tagline')) {
            return;
        }

        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->dropColumn('tagline');
        });
    }
};
