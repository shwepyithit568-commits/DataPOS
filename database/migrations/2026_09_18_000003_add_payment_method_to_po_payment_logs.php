<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Record how a purchase-order payment was made.
     *
     * The cash reconciliation subtracts "supplier PO payments paid in cash" from
     * the expected drawer, but `po_payment_logs` never stored a payment method —
     * the column the reconciliation filtered on did not exist. On MySQL that
     * crashed the reconciliation screen outright (Unknown column 'payment_method');
     * on SQLite the quoted unknown identifier degraded into the string literal
     * 'payment_method', so the filter silently matched nothing and every supplier
     * cash payment counted as zero.
     *
     * Nullable on purpose: rows written before this migration (and any caller that
     * does not state a method) are UNRECORDED, not assumed to be cash. The
     * reconciliation deducts only explicit cash and reports the rest so the gap is
     * visible instead of being guessed away.
     */
    public function up(): void
    {
        if (Schema::hasColumn('po_payment_logs', 'payment_method')) {
            return;
        }

        Schema::table('po_payment_logs', function (Blueprint $table) {
            $table->string('payment_method', 20)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('po_payment_logs', 'payment_method')) {
            return;
        }

        Schema::table('po_payment_logs', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
