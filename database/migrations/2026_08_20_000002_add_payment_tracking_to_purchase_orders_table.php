<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add payment tracking columns to purchase_orders (AlinThit POS parity).
     *
     * - payment_status: 'unpaid' | 'partial' | 'paid'
     * - paid_amount: cumulative payments applied to this PO
     * - remaining_balance: total_cost - paid_amount (computed via accessor)
     *
     * When a PO is received and payment_status != 'paid', the remaining_balance
     * increases the supplier's total_credit. Payments reduce remaining_balance
     * and increase supplier's total_repaid.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('payment_status', 10)->default('unpaid')->after('status');
            $table->decimal('paid_amount', 16, 2)->default(0)->after('payment_status');
            $table->decimal('remaining_balance', 16, 2)->default(0)->after('paid_amount');

            $table->index(['store_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'payment_status']);
            $table->dropColumn(['payment_status', 'paid_amount', 'remaining_balance']);
        });
    }
};