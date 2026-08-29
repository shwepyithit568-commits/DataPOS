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
        if (! Schema::hasTable('product_units')) {
            Schema::create('product_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('unit_name', 100);
                $table->decimal('conversion_factor', 12, 4)->default(1.0000); // Multiplier to base unit
                $table->decimal('retail_price', 14, 2)->nullable();
                $table->decimal('wholesale_price', 14, 2)->nullable();
                $table->string('barcode', 100)->nullable();
                $table->boolean('is_base_unit')->default(false);
                $table->timestamps();

                $table->index(['store_id', 'product_id']);
                $table->index(['store_id', 'barcode']);
            });
        }

        if (! Schema::hasTable('product_batches')) {
            Schema::create('product_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
                $table->string('batch_number', 100);
                $table->date('manufacture_date')->nullable();
                $table->date('expiration_date');
                $table->decimal('initial_quantity', 12, 4)->default(0);
                $table->decimal('available_quantity', 12, 4)->default(0);
                $table->decimal('cost_price', 14, 2)->nullable();
                $table->string('status', 30)->default('active'); // active, quarantined, expired, depleted
                $table->timestamps();

                $table->index(['store_id', 'product_id', 'status']);
                $table->index(['store_id', 'expiration_date']);
                $table->index(['store_id', 'batch_number']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_batches');
        Schema::dropIfExists('product_units');
    }
};
