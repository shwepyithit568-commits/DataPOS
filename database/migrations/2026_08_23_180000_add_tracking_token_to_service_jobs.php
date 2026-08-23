<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Adds a public tracking_token to service_jobs so customers can check
 * their job status without logging in (token-based anonymous access).
 *
 * Pattern mirrors orders.confirmation_token (see 2026_07_28_030000).
 * URL: /store/{slug}/track/service/{token}
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: nullable first — safe for tables that already have rows.
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->string('tracking_token', 64)->nullable()->after('voucher_no');
        });

        // Step 2: backfill existing rows with unique random tokens.
        $ids = DB::table('service_jobs')->whereNull('tracking_token')->pluck('id');

        foreach ($ids as $id) {
            do {
                $token = Str::random(40);
                $collision = DB::table('service_jobs')
                    ->where('tracking_token', $token)->exists();
            } while ($collision);

            DB::table('service_jobs')->where('id', $id)
                ->update(['tracking_token' => $token]);
        }

        // Step 3: enforce uniqueness once every row has a token.
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->unique('tracking_token', 'service_jobs_tracking_token_unique');
        });
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dropUnique('service_jobs_tracking_token_unique');
        });

        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dropColumn('tracking_token');
        });
    }
};
