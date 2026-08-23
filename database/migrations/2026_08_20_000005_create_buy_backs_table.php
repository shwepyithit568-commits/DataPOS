<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buy_backs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('buyback_number', 32); // BB-YYYYMMDD-####
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pos_sale_id')->nullable()->constrained('pos_sales')->nullOnDelete();
            $table->decimal('total_value', 14, 4)->default(0);
            $table->decimal('refund_amount', 14, 4)->default(0);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['store_id', 'buyback_number']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('buy_back_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buy_back_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->timestamps();

            $table->index(['buy_back_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buy_back_items');
        Schema::dropIfExists('buy_backs');
    }
};
