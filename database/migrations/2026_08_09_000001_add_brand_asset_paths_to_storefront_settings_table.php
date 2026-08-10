<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separate brand assets for the Storefront (horizontal), Admin sidebar
 * (square icon) and browser favicon. All three columns are nullable and the
 * existing `logo_path` column is left untouched so stores that only have a
 * legacy logo keep working with no administrator action.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->string('storefront_logo_path')->nullable()->after('logo_path');
            $table->string('admin_logo_path')->nullable()->after('storefront_logo_path');
            $table->string('favicon_path')->nullable()->after('admin_logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->dropColumn(['storefront_logo_path', 'admin_logo_path', 'favicon_path']);
        });
    }
};
