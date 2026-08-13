<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Branches (business/sales locations) and warehouses (inventory locations).
 *
 * Reference: docs/pos-resale-plan/02-target-design.md §2.11 / SoT §14.2.
 *
 * - A branch may have one or more warehouses.
 * - Every store gets ONE default branch + ONE default warehouse automatically
 *   (StoreLocationService::ensureDefaults — also called on store creation).
 * - inventory_movements.branch_id / warehouse_id now reference these tables
 *   (nullable for now — older movements may not carry a location).
 * - inventory_balances.warehouse_id keeps the sentinel-0 key (derived cache —
 *   no FK; 0 means "no warehouse assigned"). New movements resolve to a real
 *   default warehouse, so new balance rows carry real warehouse ids.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'name'], 'branches_store_name_unique');
            $table->index(['store_id', 'is_default'], 'branches_store_default_idx');
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'name'], 'warehouses_store_name_unique');
            $table->index(['store_id', 'is_default'], 'warehouses_store_default_idx');
            $table->index('branch_id', 'warehouses_branch_idx');
        });

        // Attach the ledger to the new location tables.
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['warehouse_id']);
        });

        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('branches');
    }
};
