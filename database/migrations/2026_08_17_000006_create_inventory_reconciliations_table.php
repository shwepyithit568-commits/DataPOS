<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opening-stock reconciliation (Phase 2.5 — pilot exit criterion "reconciliation
 * diff = 0").
 *
 * The store's IMPORTED opening stock = the quantities in APPROVED
 * opening_stock_requests (the manager already signed those off). The
 * RECORDED opening stock = what the inventory ledger actually carries
 * (opening_balance movements + their reversals + previous reconciliation
 * correction adjustments). The reconciliation report compares the two per
 * product and lets the manager APPROVE — which posts adjustment_in/out
 * correction movements (source_type = inventory_reconciliation) so the
 * ledger's opening position matches the imported opening stock.
 *
 * The record is a single-step approval (no pending state): the report is
 * computed live, approval snapshots it for the audit trail and posts the
 * corrections atomically. client_transaction_id makes the whole post
 * idempotent so a retry never double-counts stock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('reconciliation_number', 40);
            $table->string('status', 20)->default('approved'); // single-step: approved
            $table->unsignedInteger('diff_count')->default(0);  // products with a non-zero diff
            $table->decimal('total_diff', 16, 3)->default(0);   // sum of |diff| at approval time
            $table->text('notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('client_transaction_id', 100)->nullable();
            $table->json('snapshot')->nullable();               // full report at approval time (audit)
            $table->timestamps();

            $table->unique(['store_id', 'reconciliation_number']);
            $table->unique(['store_id', 'client_transaction_id']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('inventory_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_reconciliation_id');
            $table->foreignId('store_id');
            $table->foreignId('product_id');
            $table->foreignId('product_variant_id')->nullable();
            $table->decimal('imported_quantity', 16, 3)->default(0); // approved OSR lines
            $table->decimal('recorded_quantity', 16, 3)->default(0); // ledger opening position
            $table->decimal('difference', 16, 3)->default(0);        // imported − recorded
            $table->decimal('correction', 16, 3)->default(0);        // signed correction posted
            $table->string('movement_type', 20)->nullable();         // adjustment_in | adjustment_out | null
            $table->timestamps();

            $table->index(['store_id', 'product_id']);

            // Explicit short FK names — the default Laravel name
            // (`inventory_reconciliation_items_inventory_reconciliation_id_foreign`,
            // 66 chars) exceeds MySQL/MariaDB's 64-char identifier limit and
            // fails the migration there (SQLite is unaffected).
            $table->foreign('inventory_reconciliation_id', 'rec_items_rec_id_fk')
                ->references('id')->on('inventory_reconciliations')->cascadeOnDelete();
            $table->foreign('store_id', 'rec_items_store_id_fk')
                ->references('id')->on('stores')->cascadeOnDelete();
            $table->foreign('product_id', 'rec_items_product_id_fk')
                ->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('product_variant_id', 'rec_items_variant_id_fk')
                ->references('id')->on('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reconciliation_items');
        Schema::dropIfExists('inventory_reconciliations');
    }
};
