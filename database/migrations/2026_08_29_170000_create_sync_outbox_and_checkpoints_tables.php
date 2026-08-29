<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sync_outbox_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('device_id', 64)->nullable()->index();
            $table->string('record_type', 40)->index(); // pos_sale, customer_debt, inventory_movement, cashier_shift, expense
            $table->string('client_transaction_id', 64)->index();
            $table->json('payload');
            $table->string('status', 20)->default('pending')->index(); // pending, syncing, synced, failed, conflict
            $table->text('error_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('created_offline_at')->useCurrent()->index();
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['store_id', 'client_transaction_id'], 'sync_outbox_store_client_tx_unique');
            $table->index(['store_id', 'status'], 'sync_outbox_store_status_idx');
        });

        Schema::create('sync_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('entity_type', 50); // products, customers, categories, promotions
            $table->timestamp('last_synced_at')->useCurrent();
            $table->string('last_cursor', 100)->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'entity_type'], 'sync_checkpoints_store_entity_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_checkpoints');
        Schema::dropIfExists('sync_outbox_records');
    }
};
