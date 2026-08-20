<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add supplier debt tracking columns (AlinThit POS parity).
     *
     * - total_credit: cumulative amount owed to this supplier (increases on PO receive when payment_status != paid)
     * - total_repaid: cumulative payments made to this supplier
     * - remaining_balance = total_credit - total_repaid (computed via accessor)
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->decimal('total_credit', 16, 2)->default(0)->after('notes');
            $table->decimal('total_repaid', 16, 2)->default(0)->after('total_credit');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['total_credit', 'total_repaid']);
        });
    }
};