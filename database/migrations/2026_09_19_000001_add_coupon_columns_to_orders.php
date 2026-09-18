<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online orders can carry a coupon too.
 *
 * The POS sale has had `promotion_id`/`coupon_code` since the beginning, but the
 * storefront order never had anywhere to record a discount — a coupon collected
 * at checkout had no column to land in. `promotion_usages` also only pointed at
 * a POS sale, so an online redemption had no provenance.
 *
 * All three order columns are additive with safe defaults, so existing rows keep
 * their current totals (discount 0 = nothing to subtract).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('taxable_amount');
            }

            if (! Schema::hasColumn('orders', 'coupon_code')) {
                $table->string('coupon_code', 40)->nullable()->after('discount_amount');
            }

            if (! Schema::hasColumn('orders', 'promotion_id')) {
                // Nullable FK like `pos_sales.promotion_id`: a promotion deleted
                // after the fact must not take the order's history with it.
                $table->foreignId('promotion_id')->nullable()->after('coupon_code')
                    ->constrained('promotions')->nullOnDelete();
            }
        });

        Schema::table('promotion_usages', function (Blueprint $table) {
            if (! Schema::hasColumn('promotion_usages', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('pos_sale_id')
                    ->constrained('orders')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['discount_amount', 'coupon_code', 'promotion_id'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('promotion_usages', function (Blueprint $table) {
            if (Schema::hasColumn('promotion_usages', 'order_id')) {
                $table->dropColumn('order_id');
            }
        });
    }
};
