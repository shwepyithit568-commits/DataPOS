<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Production performance optimization:
 * Add indexes on frequently filtered columns that are not covered
 * by existing foreign-key or unique constraints.
 *
 * InnoDB auto-indexes foreign key columns, so store_id, category_id,
 * brand_id, user_id, and order_id are already covered.
 *
 * This migration adds indexes on:
 *  - products.stock_status  — dashboard COUNT queries
 *  - products.is_featured   — future featured-product listings
 *  - orders.created_at      — "latest()" sorting on admin/customer list pages
 */
return new class extends Migration
{
    public function up(): void
    {
        // products: stock_status is filtered in dashboard COUNT queries
        Schema::table('products', function (Blueprint $table) {
            $table->index('stock_status', 'products_stock_status_idx');
        });

        // products: is_featured for future featured product queries
        Schema::table('products', function (Blueprint $table) {
            $table->index('is_featured', 'products_is_featured_idx');
        });

        // orders: created_at is sorted by latest() in admin and customer lists
        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at', 'orders_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_stock_status_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_is_featured_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_created_at_idx');
        });
    }
};
