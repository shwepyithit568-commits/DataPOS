<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * POS sale returns / refunds (target-design §2.9 — posted → partially_refunded /
 * refunded; SoT §15.1 refunds reference the source transaction).
 *
 * - A return is a NEW immutable document referencing its source sale; the sale
 *   itself is never edited — status moves to partially_refunded / refunded.
 * - Returned stock re-enters the ledger as `sales_return` movements at the
 *   original line cost (weighted-average is not recalculated, CostingService).
 * - Refund payments: `cash` goes back out of the cashier drawer (cash_refunds);
 *   `credit` reduces the customer's receivable (customer ledger, type refund).
 * - refund_number is assigned at posting, unique per store (RET-YYYYMMDD-####).
 * - client_transaction_id gives idempotent offline retries (unique per store).
 *
 * Money decimal(12,2) (MMK, §2.6); qty decimal(10,3); unit_cost decimal(14,4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();
            $table->foreignId('pos_sale_id')->constrained()->cascadeOnDelete(); // source transaction
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('refund_number', 40)->nullable()->index();
            $table->string('status', 20)->default('posted'); // posted (MVP; voided later)
            $table->decimal('total', 12, 2)->default(0);     // refund value
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('client_transaction_id', 100)->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'refund_number']);
            $table->unique(['store_id', 'client_transaction_id']);
            $table->index(['store_id', 'pos_sale_id']);
        });

        Schema::create('pos_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_sale_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            // Snapshots from the original sale line.
            $table->string('product_name');
            $table->string('sku', 100)->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_cost', 14, 4)->nullable(); // original COGS carried back
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->index('pos_return_id');
        });

        Schema::create('pos_return_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_return_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20); // cash | credit
            $table->decimal('amount', 12, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('pos_return_id');
        });

        // Mark when a sale last moved to partially_refunded / refunded.
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_return_payments');
        Schema::dropIfExists('pos_return_items');
        Schema::dropIfExists('pos_returns');

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropColumn('refunded_at');
        });
    }
};
