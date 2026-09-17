<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Non-destructive migration to support strict cash drawer attribution:
     * 1. Links expenses directly to cashier_shifts and designates the payment_source
     *    ('drawer', 'safe', 'petty_cash', 'bank', 'other') so only drawer-paid cash
     *    expenses are deducted from the POS cash drawer.
     * 2. Adds status ('paid', 'unpaid', 'void') to expenses to ensure only paid expenses move cash.
     * 3. Links cash_events to expenses (expense_id) to prevent double-deduction.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('cashier_shift_id')
                ->nullable()
                ->after('store_id')
                ->constrained('cashier_shifts')
                ->nullOnDelete();

            $table->string('payment_source', 50)
                ->nullable()
                ->after('payment_method')
                ->index();

            $table->string('status', 30)
                ->default('paid')
                ->after('amount')
                ->index();
        });

        Schema::table('cash_events', function (Blueprint $table) {
            $table->foreignId('expense_id')
                ->nullable()
                ->after('cashier_shift_id')
                ->constrained('expenses')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_events', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropColumn('expense_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['cashier_shift_id']);
            $table->dropColumn(['cashier_shift_id', 'payment_source', 'status']);
        });
    }
};
