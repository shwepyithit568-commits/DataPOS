<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shared inventory ledger — POS ရော Ecommerce ရဲ့ တစ်ခုတည်းသော stock source of truth.
 *
 * Reference: Source_of_Truth_MM.md §5 / docs/pos-resale-plan/02-target-design.md §2.5
 *
 * - `inventory_movements`  — immutable append-only ledger. Corrections use `reversal` movements.
 * - `inventory_balances`   — derived performance cache (quantity_on_hand = SUM of movements).
 *   Direct writes are forbidden; rebuild via `php artisan inventory:reconcile`.
 *
 * Notes:
 * - `branch_id` / `warehouse_id` are nullable plain columns for now — the branches/warehouses
 *   tables land in a later Phase 1 migration and will add real FK constraints then.
 * - `inventory_balances.warehouse_id` / `product_variant_id` use sentinel 0 ("no warehouse /
 *   no variant") so the 4-column unique key is strictly enforced on both SQLite and MySQL
 *   (NULLs are treated as distinct by both engines). The service maps null → 0 when writing balances.
 * - Idempotency: unique (store_id, client_transaction_id). Duplicate-posting backstop:
 *   unique (store_id, source_type, source_id, product_id, product_variant_id) — one movement
 *   per source document line. Lifecycle events sharing a source doc (online_reserve / online_confirm
 *   / online_cancel) must use distinct `source_type` values (e.g. order_reserve / order_confirm /
 *   order_cancel).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->nullable()->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // 0 = no variant (sentinel) so the per-source-line unique backstop is strict
            // on both SQLite and MySQL (NULLs are treated as distinct by both engines).
            $table->unsignedBigInteger('product_variant_id')->default(0)->index();
            $table->string('movement_type', 40)->index();
            $table->decimal('quantity_delta', 12, 3);
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('client_transaction_id', 64)->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'client_transaction_id'], 'inv_movements_store_ctid_unique');
            $table->unique(
                ['store_id', 'source_type', 'source_id', 'product_id', 'product_variant_id'],
                'inv_movements_store_source_line_unique'
            );
            $table->index(['warehouse_id', 'product_id', 'product_variant_id'], 'inv_movements_loc_product_idx');
            $table->index('occurred_at', 'inv_movements_occurred_at_idx');
        });

        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('warehouse_id')->default(0);
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_variant_id')->default(0);
            $table->decimal('quantity_on_hand', 12, 3)->default(0);
            $table->timestamps();

            $table->unique(
                ['store_id', 'warehouse_id', 'product_id', 'product_variant_id'],
                'inv_balances_key_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('inventory_movements');
    }
};
