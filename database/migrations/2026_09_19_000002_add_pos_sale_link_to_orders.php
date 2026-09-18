<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The counter sale that fulfilled an online order.
 *
 * Until now the link existed only as a line in the audit log ("sale_receipt"),
 * so the order screen could not open the receipt that goes with it and no
 * report could join the two. Nullable + ON DELETE SET NULL: the order's own
 * history survives a voided/deleted sale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'pos_sale_id')) {
                $table->foreignId('pos_sale_id')->nullable()->after('promotion_id')
                    ->constrained('pos_sales')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'pos_sale_id')) {
                $table->dropConstrainedForeignId('pos_sale_id');
            }
        });
    }
};
