<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'subscription_tier')) {
                $table->string('subscription_tier', 50)->default('standard')->after('business_profile');
            }
            if (! Schema::hasColumn('stores', 'max_products')) {
                $table->unsignedInteger('max_products')->nullable()->after('subscription_tier');
            }
            if (! Schema::hasColumn('stores', 'max_branches')) {
                $table->unsignedInteger('max_branches')->nullable()->after('max_products');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'subscription_tier')) {
                $table->dropColumn('subscription_tier');
            }
            if (Schema::hasColumn('stores', 'max_products')) {
                $table->dropColumn('max_products');
            }
            if (Schema::hasColumn('stores', 'max_branches')) {
                $table->dropColumn('max_branches');
            }
        });
    }
};
