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
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('session_number', 50)->index();
            $table->string('scope', 20)->default('all'); // all, category
            $table->json('category_ids')->nullable();
            $table->string('status', 20)->default('in_progress'); // draft, in_progress, approved, cancelled
            $table->integer('total_items')->default(0);
            $table->integer('counted_items')->default(0);
            $table->integer('variance_items')->default(0);
            $table->decimal('total_variance_qty', 12, 3)->default(0.000);
            $table->decimal('total_variance_cost', 14, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'created_at']);
        });

        Schema::create('stock_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained('stock_counts')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedBigInteger('product_variant_id')->default(0);
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->decimal('system_quantity', 12, 3)->default(0.000);
            $table->decimal('counted_quantity', 12, 3)->nullable();
            $table->decimal('variance_quantity', 12, 3)->default(0.000);
            $table->decimal('unit_cost', 14, 2)->default(0.00);
            $table->decimal('variance_cost', 14, 2)->default(0.00);
            $table->boolean('is_counted')->default(false);
            $table->string('notes', 255)->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();

            $table->index(['stock_count_id', 'product_id']);
            $table->index(['stock_count_id', 'is_counted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_count_lines');
        Schema::dropIfExists('stock_counts');
    }
};
