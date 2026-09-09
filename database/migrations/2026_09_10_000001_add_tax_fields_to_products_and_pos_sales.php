<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Products table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_taxable')) {
                $table->boolean('is_taxable')->default(true)->after('is_featured');
            }
            if (!Schema::hasColumn('products', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->nullable()->after('is_taxable');
            }
        });

        // 2. POS sale items table
        Schema::table('pos_sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_sale_items', 'is_taxable')) {
                $table->boolean('is_taxable')->default(true)->after('line_total');
            }
            if (!Schema::hasColumn('pos_sale_items', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('is_taxable');
            }
            if (!Schema::hasColumn('pos_sale_items', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate');
            }
        });

        // 3. POS sales table
        Schema::table('pos_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_sales', 'tax_type')) {
                $table->string('tax_type', 20)->default('exclusive')->after('tax');
            }
            if (!Schema::hasColumn('pos_sales', 'taxable_amount')) {
                $table->decimal('taxable_amount', 12, 2)->default(0)->after('tax_type');
            }
            if (!Schema::hasColumn('pos_sales', 'exempt_amount')) {
                $table->decimal('exempt_amount', 12, 2)->default(0)->after('taxable_amount');
            }
        });

        // 4. Orders table (Storefront online orders)
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'tax')) {
                $table->decimal('tax', 12, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('orders', 'tax_type')) {
                $table->string('tax_type', 20)->default('exclusive')->after('tax');
            }
            if (!Schema::hasColumn('orders', 'taxable_amount')) {
                $table->decimal('taxable_amount', 12, 2)->default(0)->after('tax_type');
            }
        });

        // 5. Order items table
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'is_taxable')) {
                $table->boolean('is_taxable')->default(true)->after('subtotal');
            }
            if (!Schema::hasColumn('order_items', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('is_taxable');
            }
            if (!Schema::hasColumn('order_items', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['is_taxable', 'tax_rate', 'tax_amount']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tax', 'tax_type', 'taxable_amount']);
        });

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropColumn(['tax_type', 'taxable_amount', 'exempt_amount']);
        });

        Schema::table('pos_sale_items', function (Blueprint $table) {
            $table->dropColumn(['is_taxable', 'tax_rate', 'tax_amount']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_taxable', 'tax_rate']);
        });
    }
};
