<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('barcode_templates')) {
            Schema::create('barcode_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained()->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('type', 20)->default('thermal'); // 'thermal' or 'sheet'
                $table->decimal('width_mm', 6, 2)->default(50.00);
                $table->decimal('height_mm', 6, 2)->default(30.00);
                $table->decimal('gap_x_mm', 5, 2)->default(0.00);
                $table->decimal('gap_y_mm', 5, 2)->default(0.00);
                $table->decimal('padding_top_mm', 5, 2)->default(1.20);
                $table->decimal('padding_bottom_mm', 5, 2)->default(1.20);
                $table->decimal('padding_left_mm', 5, 2)->default(2.00);
                $table->decimal('padding_right_mm', 5, 2)->default(2.00);
                $table->decimal('margin_top_mm', 5, 2)->default(0.00);
                $table->decimal('margin_bottom_mm', 5, 2)->default(0.00);
                $table->decimal('margin_left_mm', 5, 2)->default(0.00);
                $table->decimal('margin_right_mm', 5, 2)->default(0.00);
                $table->unsignedSmallInteger('cols')->default(1);
                $table->unsignedSmallInteger('rows')->default(1);
                $table->unsignedSmallInteger('bar_height')->default(28);
                $table->decimal('bar_width', 4, 2)->default(1.35);
                $table->string('store_font', 15)->default('9px');
                $table->string('name_font', 15)->default('9px');
                $table->unsignedTinyInteger('name_max_lines')->default(2);
                $table->string('price_font', 15)->default('11px');
                $table->string('code_type', 20)->default('barcode_128'); // 'barcode_128', 'qr_code'
                $table->boolean('show_store_name')->default(true);
                $table->boolean('show_product_name')->default(true);
                $table->boolean('show_price')->default(true);
                $table->boolean('show_code_text')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->index(['store_id', 'is_default']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_templates');
    }
};
