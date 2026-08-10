<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Adds a secure confirmation_token column to the orders table.
 *
 * Safe for databases that already contain orders:
 *  1. Column is added as nullable (no constraint).
 *  2. Every existing order is backfilled with a unique random token.
 *  3. Unique index is created only after the backfill.
 *  4. Collisions during backfill are handled by retry.
 *  5. Rollback drops the column (drops the index implicitly).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add the column as nullable with NO unique constraint yet
        Schema::table('orders', function (Blueprint $table) {
            $table->string('confirmation_token', 64)->nullable()->after('order_number');
        });

        // Step 2: Backfill existing rows with unique random tokens
        // We fetch IDs only — avoids loading large order records into memory.
        $orderIds = DB::table('orders')->whereNull('confirmation_token')->pluck('id');

        foreach ($orderIds as $id) {
            // Retry loop for the astronomically unlikely collision
            do {
                $token = Str::random(40);
                $collision = DB::table('orders')->where('confirmation_token', $token)->exists();
            } while ($collision);

            DB::table('orders')->where('id', $id)->update(['confirmation_token' => $token]);
        }

        // Step 3: Now add the unique index
        Schema::table('orders', function (Blueprint $table) {
            $table->unique('confirmation_token', 'orders_confirmation_token_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_confirmation_token_unique');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('confirmation_token');
        });
    }
};
