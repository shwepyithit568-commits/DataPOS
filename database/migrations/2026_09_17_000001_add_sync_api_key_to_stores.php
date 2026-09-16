<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-store credential for the offline-to-cloud sync API.
     *
     * `/api/v1/store/{slug}/sync/*` was previously unauthenticated: anyone who
     * knew a store slug could post sales (including zero-priced ones that still
     * deduct stock), collect customer debt, backdate records and pull customer
     * PII. Terminals now authenticate with a per-store key.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('sync_api_key_hash', 255)->nullable()->after('max_branches');
            // Last 4 characters, kept in clear so the admin UI can show which
            // key is installed without storing the key itself.
            $table->string('sync_api_key_last4', 4)->nullable()->after('sync_api_key_hash');
            $table->dateTime('sync_api_key_rotated_at')->nullable()->after('sync_api_key_last4');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['sync_api_key_hash', 'sync_api_key_last4', 'sync_api_key_rotated_at']);
        });
    }
};
