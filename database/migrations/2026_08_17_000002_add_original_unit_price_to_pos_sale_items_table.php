<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot of the pre-negotiation (tier) unit price so the receipt can show
     * "Ks 10,000 → Ks 9,000" when a cashier overrides a line price. NULL means
     * the line was charged at the normal tier price (no override).
     */
    public function up(): void
    {
        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->decimal('original_unit_price', 12, 2)->nullable()->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->dropColumn('original_unit_price');
        });
    }
};
