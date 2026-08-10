<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Production hardening: Add composite indexes to improve query performance
 * on frequently filtered columns in the orders and wholesale_applications tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        // orders: (store_id, status) composite index for dashboard status-count queries
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['store_id', 'status'], 'orders_store_status_idx');
        });

        // wholesale_applications: (store_id, status) composite index for admin filter queries
        Schema::table('wholesale_applications', function (Blueprint $table) {
            $table->index(['store_id', 'status'], 'wholesale_applications_store_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_store_status_idx');
        });

        Schema::table('wholesale_applications', function (Blueprint $table) {
            $table->dropIndex('wholesale_applications_store_status_idx');
        });
    }
};
