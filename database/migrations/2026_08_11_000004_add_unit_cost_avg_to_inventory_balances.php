<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Weighted-average unit cost on the balance cache (target-design §2.7).
 *
 * inventory_balances.unit_cost_avg is a derived cache like quantity_on_hand —
 * maintained by CostingService (batch recalc on receive/return, replay on
 * reversal) and rebuilt by `php artisan inventory:reconcile`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->decimal('unit_cost_avg', 14, 4)->default(0)->after('quantity_on_hand');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->dropColumn('unit_cost_avg');
        });
    }
};
