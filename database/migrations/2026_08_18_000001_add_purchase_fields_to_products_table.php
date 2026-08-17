<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('reorder_level', 12, 3)->nullable()->after('stock_status');
            $table->unsignedBigInteger('supplier_id')->nullable()->after('reorder_level');
            $table->decimal('purchase_cost', 14, 4)->nullable()->after('supplier_id');

            // Explicit short FK name (64-char identifier limit guard).
            $table->foreign('supplier_id', 'products_supplier_id_fk')
                ->references('id')
                ->on('suppliers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_supplier_id_fk');
            $table->dropColumn(['reorder_level', 'supplier_id', 'purchase_cost']);
        });
    }
};
