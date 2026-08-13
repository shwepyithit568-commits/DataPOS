<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cashier shifts + cash in/out events (target-design §2.10 — MVP: cashier shift
 * closing + simple branch daily summary; finance period closing is Operations).
 *
 * Shift fields (minimum set from §2.10): branch, device/register, cashier,
 * opening_time, opening_cash, cash_sales, cash_refunds, cash_in/out,
 * expected_closing_amount, actual_closing_amount, difference, notes,
 * closed_by, manager_approval.
 *
 * Money is DECIMAL (never float). MMK has no subunit — decimal(16,2) keeps the
 * 2-decimal precision the rest of the codebase already uses.
 *
 * One OPEN shift per (store, register): unique (store_id, register_name, status).
 * cash_events is the detailed audit log; the shift's cash_in/cash_out totals
 * are maintained by CashierShiftService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('register_name', 100);
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('open'); // open | closed
            $table->timestamp('opened_at');
            $table->decimal('opening_cash', 16, 2)->default(0);
            $table->decimal('cash_sales', 16, 2)->default(0);
            $table->decimal('cash_refunds', 16, 2)->default(0);
            $table->decimal('cash_in', 16, 2)->default(0);
            $table->decimal('cash_out', 16, 2)->default(0);
            $table->decimal('expected_closing_amount', 16, 2)->nullable();
            $table->decimal('actual_closing_amount', 16, 2)->nullable();
            $table->decimal('difference', 16, 2)->nullable();
            $table->boolean('manager_approval')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['store_id', 'register_name', 'status'], 'cashier_shifts_open_register_unique');
            $table->index(['store_id', 'status'], 'cashier_shifts_store_status_idx');
            $table->index(['store_id', 'opened_at'], 'cashier_shifts_store_date_idx');
        });

        Schema::create('cash_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_shift_id')->constrained('cashier_shifts')->cascadeOnDelete();
            $table->string('type', 20); // cash_in | cash_out
            $table->decimal('amount', 16, 2);
            $table->string('reason', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cashier_shift_id'], 'cash_events_shift_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_events');
        Schema::dropIfExists('cashier_shifts');
    }
};
