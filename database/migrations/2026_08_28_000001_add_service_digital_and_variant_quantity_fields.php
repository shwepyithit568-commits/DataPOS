<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Service/Digital product details + per-variant stock quantity.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'service_duration')) {
            Schema::table('products', function (Blueprint $table) {
                // Service/Labor items: how long the service takes (e.g. "30 min", "1 day").
                $table->string('service_duration', 100)->nullable()->after('purchase_cost');
                // Digital/Code items: how the code is delivered (SMS / Email / In-store).
                $table->string('digital_delivery_method', 100)->nullable()->after('service_duration');
            });
        }

        if (! Schema::hasColumn('product_variants', 'quantity_on_hand')) {
            Schema::table('product_variants', function (Blueprint $table) {
                // Per-variant stock quantity; the product-level stock_status for
                // variant products is derived from the sum of these.
                $table->decimal('quantity_on_hand', 12, 3)->default(0)->after('stock_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'service_duration')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn(['service_duration', 'digital_delivery_method']);
            });
        }

        if (Schema::hasColumn('product_variants', 'quantity_on_hand')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('quantity_on_hand');
            });
        }
    }
};
