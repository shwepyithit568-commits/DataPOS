<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sale price + SEO meta on products
        if (! Schema::hasColumn('products', 'old_price')) {
            Schema::table('products', function (Blueprint $table) {
                // Compare-at / sale price (Ks) — storefront shows ~~old~~ + % OFF when retail < old.
                $table->decimal('old_price', 12, 2)->nullable()->after('retail_price');
                $table->text('meta_description')->nullable()->after('description');
            });
        }

        // Product variants (e.g. iPhone 15 Pro Max — 256GB / 512GB with different price & SKU)
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');                        // e.g. "256GB", "Black"
            $table->string('sku', 100)->nullable();
            $table->decimal('retail_price', 12, 2);
            $table->decimal('wholesale_price', 12, 2)->nullable();
            $table->string('stock_status', 20)->default('in_stock');   // in_stock / out_of_stock
            $table->string('image_path')->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');

        if (Schema::hasColumn('products', 'old_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn(['old_price', 'meta_description']);
            });
        }
    }
};
