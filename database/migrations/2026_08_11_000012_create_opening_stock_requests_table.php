<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opening stock (MVP Phase 2 — target-design §2.10 "Opening stock").
 *
 * A request is submitted by staff and reviewed by the store manager. ONLY on
 * approval do `opening_balance` ledger movements post (atomically, one per
 * line, at the entered unit cost — CostingService sets the initial
 * weighted average). Until approved the request has no ledger impact.
 *
 * Status: pending → approved | rejected. Approved is immutable; corrections
 * use ledger reversals (SoT §15.1). client_transaction_id makes the approval
 * post idempotent so a retry never double-counts stock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_stock_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('request_number', 40);
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->decimal('total_quantity', 16, 3)->default(0);
            $table->decimal('total_cost', 16, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('review_notes')->nullable();       // manager comment on approval/rejection
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('client_transaction_id', 100)->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'request_number']);
            $table->unique(['store_id', 'client_transaction_id']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('opening_stock_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opening_stock_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 16, 3);
            $table->decimal('unit_cost', 16, 4);
            $table->decimal('line_total', 16, 2);
            $table->timestamps();

            $table->index(['store_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_stock_request_items');
        Schema::dropIfExists('opening_stock_requests');
    }
};
