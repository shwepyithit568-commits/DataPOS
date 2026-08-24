<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            $table->string('name', 200);           // e.g. "Thingyan Sale 2026"
            $table->string('code', 80)->nullable(); // Coupon code, e.g. "THADINGYUT10"
            $table->string('type', 30);             // percent_off | flat_off | bogo | free_shipping
            $table->decimal('value', 12, 2)->default(0); // % or flat amount
            $table->decimal('min_order_amount', 12, 2)->default(0); // minimum cart total to apply

            // scope — null = apply to all
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            $table->unsignedInteger('total_uses_limit')->nullable();    // null = unlimited
            $table->unsignedInteger('per_customer_limit')->nullable();  // null = unlimited
            $table->unsignedInteger('used_count')->default(0);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false); // true = auto-apply, false = coupon code entry

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['store_id', 'is_active'], 'promotions_store_active_idx');
            $table->index(['store_id', 'code'], 'promotions_store_code_idx');
        });

        // Track per-sale coupon usage
        Schema::create('promotion_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pos_sale_id')->nullable()->constrained('pos_sales')->nullOnDelete();
            $table->decimal('discount_applied', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['promotion_id', 'customer_id'], 'promo_usage_promo_customer_idx');
        });

        // Add coupon_id link to pos_sales for traceability
        if (!Schema::hasColumn('pos_sales', 'promotion_id')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                $table->foreignId('promotion_id')->nullable()->constrained('promotions')->nullOnDelete()->after('discount');
                $table->string('coupon_code', 80)->nullable()->after('promotion_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            if (Schema::hasColumn('pos_sales', 'coupon_code')) {
                $table->dropColumn('coupon_code');
            }
            if (Schema::hasColumn('pos_sales', 'promotion_id')) {
                $table->dropForeign(['promotion_id']);
                $table->dropColumn('promotion_id');
            }
        });

        Schema::dropIfExists('promotion_usages');
        Schema::dropIfExists('promotions');
    }
};
