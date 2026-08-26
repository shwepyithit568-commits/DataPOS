<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('barcode_templates')) {
            Schema::table('barcode_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('barcode_templates', 'spacing_store_to_name_mm')) {
                    $table->decimal('spacing_store_to_name_mm', 4, 2)->default(0.50)->after('padding_right_mm');
                }
                if (!Schema::hasColumn('barcode_templates', 'spacing_name_to_code_mm')) {
                    $table->decimal('spacing_name_to_code_mm', 4, 2)->default(0.50)->after('spacing_store_to_name_mm');
                }
                if (!Schema::hasColumn('barcode_templates', 'spacing_code_to_price_mm')) {
                    $table->decimal('spacing_code_to_price_mm', 4, 2)->default(0.50)->after('spacing_name_to_code_mm');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('barcode_templates')) {
            Schema::table('barcode_templates', function (Blueprint $table) {
                $columns = ['spacing_store_to_name_mm', 'spacing_name_to_code_mm', 'spacing_code_to_price_mm'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('barcode_templates', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
