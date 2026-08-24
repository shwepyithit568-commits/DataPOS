<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 50);
            $table->decimal('min_spending', 14, 2)->default(0.00);
            $table->decimal('discount_percent', 5, 2)->default(0.00);
            $table->decimal('point_multiplier', 4, 2)->default(1.00);
            $table->string('badge_color', 30)->default('slate');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'code'], 'membership_tiers_store_code_unique');
            $table->index(['store_id', 'min_spending'], 'membership_tiers_store_spend_idx');
        });

        Schema::table('store_user', function (Blueprint $table) {
            if (!Schema::hasColumn('store_user', 'membership_tier_id')) {
                $table->foreignId('membership_tier_id')->nullable()->constrained('membership_tiers')->nullOnDelete();
            }
            if (!Schema::hasColumn('store_user', 'loyalty_points')) {
                $table->integer('loyalty_points')->default(0);
            }
            if (!Schema::hasColumn('store_user', 'total_spent')) {
                $table->decimal('total_spent', 14, 2)->default(0.00);
            }
        });

        Schema::create('loyalty_point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30); // earned, redeemed, adjusted, bonus
            $table->integer('points'); // signed integer (+ or -)
            $table->integer('balance_after');
            $table->foreignId('pos_sale_id')->nullable()->constrained('pos_sales')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['store_id', 'customer_id'], 'loyalty_txns_store_customer_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_point_transactions');

        Schema::table('store_user', function (Blueprint $table) {
            $table->dropForeign(['membership_tier_id']);
            $table->dropColumn(['membership_tier_id', 'loyalty_points', 'total_spent']);
        });

        Schema::dropIfExists('membership_tiers');
    }
};
