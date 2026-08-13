<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * POS sales (target-design §2.8 — sale state machine; SoT §8).
 *
 * Lifecycle: draft (session cart) → held → posted → (later) partially_refunded /
 * refunded / reversed. Voided applies before posting only. Receipt number is
 * assigned atomically at posting time — never before.
 *
 * Money is decimal(12,2) (MMK, §2.6); unit cost decimal(14,4) matches the
 * weighted-average costing precision; quantity decimal(10,3) per the decimal
 * quantity foundation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete(); // retail customers are users
            $table->string('receipt_number', 40)->nullable()->index();
            $table->string('status', 20)->default('draft'); // draft|held|posted|partially_refunded|refunded|reversed|voided
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // One receipt number per store.
            $table->unique(['store_id', 'receipt_number']);
        });

        Schema::create('pos_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            // Snapshots — product names/prices may change after the sale.
            $table->string('product_name');
            $table->string('sku', 100)->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_cost', 14, 4)->nullable(); // COGS carried at posting
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->index('pos_sale_id');
        });

        Schema::create('pos_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20); // cash|kpay|wavepay|cb_pay|mmqr
            $table->decimal('amount', 12, 2);
            $table->decimal('change_given', 12, 2)->default(0);
            $table->string('reference', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('pos_sale_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_payments');
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_sales');
    }
};
