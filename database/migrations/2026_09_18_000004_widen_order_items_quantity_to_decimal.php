<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store order-line quantities at the scale the rest of the system uses.
     *
     * `order_items.quantity` was an INT while `inventory_movements.quantity_delta`
     * and `pos_sale_items.quantity` are DECIMAL(12,3), and the order→inventory
     * adapter accumulates merged line quantities with bcadd(..., 3). The column
     * was therefore the only place a quantity could not be fractional, and on
     * MySQL (which runs here WITHOUT STRICT_TRANS_TABLES, so this is silent) a
     * fractional write is truncated to 0 — a line that reserves and reports
     * nothing — while SQLite stores it unchanged. Two engines disagreeing about
     * the same order line is exactly the class of defect this pass is fixing.
     *
     * Widening preserves every existing value exactly (1 stays 1.000) and is
     * reversible. The storefront's own validation still accepts integers only;
     * this changes what can be STORED, not what is accepted.
     */
    public function up(): void
    {
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->decimal('quantity', 10, 3)->default(1)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->integer('quantity')->default(1)->change();
            });
        }
    }
};
