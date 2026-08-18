<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Purchase order lifecycle (alinthit_pos style, Phase 4 — early build).
 *
 * States: pending → ordered → received | cancelled
 *
 * A purchase order is a planning document: creating or ordering it does NOT
 * increase stock. Only "receiving" the PO posts purchase_received ledger
 * movements (via the existing GoodsReceipt infrastructure) which do increase
 * stock and trigger weighted-average cost recalculation (SoT §6, §11.5).
 *
 * The PO number (PO-Ymd-####) is assigned on creation and unique per store.
 * Correction after receive uses ledger reversals, never by editing this document.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('po_number', 40);
            $table->string('status', 20)->default('pending'); // pending | ordered | received | cancelled
            $table->decimal('total_quantity', 16, 3)->default(0);
            $table->decimal('total_cost', 16, 2)->default(0);
            $table->string('reference', 100)->nullable();     // supplier invoice / ref no.
            $table->text('notes')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['store_id', 'po_number']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'created_at']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
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
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
