<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generic audit trail (plan Phase 1 — "Audit and approvals"; SoT §13/§8).
 *
 * First consumer: POS receipt printing/reprinting logging. Later consumers:
 * manager approvals, inventory adjustments, support-mode writes, reprints of
 * ecommerce invoices, etc. Movements themselves are the immutable source of
 * truth for inventory; this table records *who did what, when* on top.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);                    // e.g. pos_receipt_printed / pos_receipt_reprinted
            $table->string('entity_type', 60)->nullable();   // e.g. pos_sale
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['store_id', 'entity_type', 'entity_id']);
            $table->index(['actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
