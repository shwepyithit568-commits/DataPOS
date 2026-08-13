<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventory adjustments with manager approval (MVP Phase 2 — last module).
 *
 * A cashier submits an adjustment request (signed quantities + per-line
 * reason). ONLY on manager approval do `adjustment_in` / `adjustment_out`
 * ledger movements post (atomically). Adjustments carry the current
 * weighted-average cost and do NOT change the average (SoT §6).
 *
 * Status: pending → approved | rejected. Approved is immutable; corrections
 * use ledger reversals (SoT §15.1). client_transaction_id makes the approval
 * post idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('adjustment_number', 40);
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->decimal('total_quantity', 16, 3)->default(0); // signed sum of lines
            $table->text('notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('client_transaction_id', 100)->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'adjustment_number']);
            $table->unique(['store_id', 'client_transaction_id']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('inventory_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 16, 3);      // signed: + in / − out
            $table->string('reason', 255);           // required per line
            $table->timestamps();

            $table->index(['store_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustment_items');
        Schema::dropIfExists('inventory_adjustments');
    }
};
