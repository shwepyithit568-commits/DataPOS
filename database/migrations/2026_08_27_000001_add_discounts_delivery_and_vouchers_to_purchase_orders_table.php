<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds:
     * - subtotal: items cost sum before discount and shipping
     * - discount_amount: supplier wholesale trade discount (နုတ်ငွေ)
     * - delivery_fee: freight, shipping, or carrier charges (ပေါင်းငွေ)
     * - voucher_images: JSON array of uploaded voucher/receipt file paths
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('subtotal', 16, 2)->default(0)->after('payment_status');
            $table->decimal('discount_amount', 16, 2)->default(0)->after('subtotal');
            $table->decimal('delivery_fee', 16, 2)->default(0)->after('discount_amount');
            $table->json('voucher_images')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'discount_amount', 'delivery_fee', 'voucher_images']);
        });
    }
};
