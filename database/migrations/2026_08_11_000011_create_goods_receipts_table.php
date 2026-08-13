<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simple stock receiving (MVP Phase 2 — target-design §2.9/§2.10).
 *
 * A goods receipt is the source DOCUMENT for `purchase_received` ledger
 * movements: it records what arrived, at what unit cost, and carries a
 * unique receipt number (GRV-Ymd-#### per store) + client_transaction_id
 * so retries are idempotent. Full purchasing/POs come in a later
 * Operations phase — this is receive-without-PO.
 *
 * The ledger remains the source of truth; the receipt document references
 * the movements via source_type=goods_receipt, source_id=goods_receipts.id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('receipt_number', 40);
            $table->string('status', 20)->default('posted'); // posted (immutable)
            $table->decimal('total_quantity', 16, 3)->default(0);
            $table->decimal('total_cost', 16, 2)->default(0);
            $table->string('reference', 100)->nullable();   // supplier / invoice no.
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('client_transaction_id', 100)->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'receipt_number']);
            // Idempotent retry: one receipt per client transaction per store.
            $table->unique(['store_id', 'client_transaction_id']);
            $table->index(['store_id', 'posted_at']);
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
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
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
    }
};
