<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the final agreed amount (set by admin after phone/Viber/Telegram
     * negotiation — glass-finder orders have no built-in price) and the
     * payment status (unpaid/paid) used in the COD/KPay workflow.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'agreed_amount')) {
                $table->decimal('agreed_amount', 12, 2)->nullable()->after('total_amount');
            }
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid')->after('agreed_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'agreed_amount')) {
                $table->dropColumn('agreed_amount');
            }
            if (Schema::hasColumn('orders', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};
