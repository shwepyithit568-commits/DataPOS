<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Branch daily closing (SoT §18 — Daily Closing; target-design §2.10).
 *
 * One closing per (store, business_date) — MVP is single-branch; per-branch
 * closings come with the multi-branch phase.
 *
 * Expected totals per payment method are DERIVED from the ledgers at closing
 * time and stored immutably (shift-based cash: opening + cash_sales −
 * cash_refunds + cash_in − cash_out; e-methods from posted sales; credit is
 * receivable info). Counted totals are what the manager actually counted;
 * differences = counted − expected.
 *
 * A closing cannot be approved while pending offline transactions exist
 * (SoT §18). Approval is by the store manager and is audited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->date('business_date');
            $table->foreignId('closing_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('opening_amount', 16, 2)->default(0);
            $table->json('expected_totals'); // {cash, kpay, wavepay, cb_pay, mmqr, credit}
            $table->json('counted_totals');  // {cash, kpay, wavepay, cb_pay, mmqr}
            $table->json('differences');     // counted − expected per method
            $table->decimal('total_difference', 16, 2)->default(0);
            $table->text('explanation')->nullable();
            $table->unsignedInteger('pending_offline_transaction_count')->default(0);
            $table->string('approval_status', 20)->default('pending'); // pending | approved
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // One closing per store per business date (MVP single-branch).
            $table->unique(['store_id', 'business_date']);
            $table->index(['store_id', 'business_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_closings');
    }
};
