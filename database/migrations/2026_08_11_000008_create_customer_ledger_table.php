<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer receivable ledger (SoT §17 — Debt and Finance).
 *
 * - One ledger for customer receivables; entries are immutable — corrections
 *   are reversal entries, never edits/deletes.
 * - amount is SIGNED: + increases the customer's debt (new sale debt, opening
 *   balance), − decreases it (collection, reversal of a debt entry).
 * - Every debt entry references its source (pos_sale) when possible; debt
 *   collection is always a NEW transaction — balances are never edited directly.
 * - client_transaction_id gives idempotent offline retries (unique per store).
 *
 * Money is decimal(14,2) (MMK, §2.6 policy) — no floats.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            // sale_debt | collection | reversal | opening_balance
            $table->string('type', 20);
            $table->decimal('amount', 14, 2); // signed: + debt, − payment
            $table->string('source_type', 30)->nullable(); // pos_sale | manual
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('notes', 1000)->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('client_transaction_id', 100)->nullable();
            $table->timestamps();

            // Balance queries per customer.
            $table->index(['store_id', 'customer_id']);
            // Source lookups (e.g. a sale's debt entry for reversal/refund).
            $table->index(['store_id', 'source_type', 'source_id']);
            // Idempotency: the same client transaction may never post twice.
            $table->unique(['store_id', 'client_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_ledger_entries');
    }
};
