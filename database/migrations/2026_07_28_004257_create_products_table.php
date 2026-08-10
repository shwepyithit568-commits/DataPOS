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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->index();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('retail_price', 12, 2);
            $table->decimal('wholesale_price', 12, 2);
            $table->enum('stock_status', ['in_stock', 'out_of_stock'])->default('in_stock');
            $table->string('image_path')->nullable();
            $table->string('warranty')->nullable();
            $table->string('return_policy')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->unique(['store_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
