<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exact store-location configuration. All columns nullable/false by default so
 * existing stores keep working with the legacy address-search link until an
 * exact Google Maps URL or coordinates are provided.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->boolean('map_enabled')->default(false)->after('tiktok_url');
            $table->string('google_maps_url')->nullable()->after('map_enabled');
            $table->decimal('map_latitude', 10, 7)->nullable()->after('google_maps_url');
            $table->decimal('map_longitude', 10, 7)->nullable()->after('map_latitude');
            $table->string('map_title')->nullable()->after('map_longitude');
            $table->boolean('map_embed_enabled')->default(false)->after('map_title');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->dropColumn([
                'map_enabled',
                'google_maps_url',
                'map_latitude',
                'map_longitude',
                'map_title',
                'map_embed_enabled',
            ]);
        });
    }
};
