<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persist the canonical fingerprint of the request that created an expense.
     *
     * An expense may carry a `client_transaction_id` so a lost response or a
     * double-click cannot create a second row. Until now the only evidence the
     * replay was the SAME submission was the key itself: the same key with a
     * different amount was silently answered with the original row, and a
     * genuinely corrected resubmission looked like a success.
     *
     * The fingerprint lets the server tell the two apart:
     *   same key + same fingerprint  → replay the original (200)
     *   same key + different payload → 409 conflict, nothing written
     *   different key                → a new, legitimate expense
     *
     * Additive and nullable on purpose: existing rows keep working (a NULL
     * fingerprint is compared field-by-field against the incoming payload), and
     * the column can be dropped without touching expense data.
     */
    public function up(): void
    {
        if (Schema::hasColumn('expenses', 'request_fingerprint')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->char('request_fingerprint', 64)
                ->nullable()
                ->after('client_transaction_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('expenses', 'request_fingerprint')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('request_fingerprint');
        });
    }
};
