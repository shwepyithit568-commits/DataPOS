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
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type', 30)->default('standard')->after('sku');
            $table->string('barcode', 100)->nullable()->after('product_type');
            $table->string('shelf_location', 100)->nullable()->after('reorder_level');
            $table->foreignId('warehouse_id')->nullable()->after('shelf_location')->constrained('warehouses')->nullOnDelete();
            $table->text('compatible_models')->nullable()->after('description');
            $table->json('specs')->nullable()->after('compatible_models');

            $table->index(['store_id', 'barcode'], 'products_store_barcode_idx');
            $table->index(['store_id', 'product_type'], 'products_store_product_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropIndex('products_store_barcode_idx');
            $table->dropIndex('products_store_product_type_idx');
            $table->dropColumn([
                'product_type',
                'barcode',
                'shelf_location',
                'warehouse_id',
                'compatible_models',
                'specs',
            ]);
        });
    }
};
