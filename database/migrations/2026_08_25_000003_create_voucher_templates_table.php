<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('voucher_templates')) {
            Schema::create('voucher_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained()->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('paper_size', 20)->default('80mm'); // 80mm, 58mm, a4, a5
                $table->string('style_preset', 40)->default('clean_minimal'); // clean_minimal, modern_tech, classic_border
                $table->string('header_title', 150)->nullable();
                $table->string('header_subtitle', 200)->nullable();
                $table->boolean('show_logo')->default(true);
                $table->string('logo_path', 255)->nullable();
                $table->text('address')->nullable();
                $table->string('phone', 120)->nullable();
                $table->boolean('show_qr')->default(true);
                $table->string('qr_type', 30)->default('kpay'); // kpay, wave, bank, custom
                $table->string('qr_image_path', 255)->nullable();
                $table->string('qr_label', 150)->nullable();
                $table->boolean('show_customer_info')->default(true);
                $table->boolean('show_cashier_name')->default(true);
                $table->boolean('show_tax_breakdown')->default(true);
                $table->boolean('show_discount_line')->default(true);
                $table->boolean('show_barcode')->default(true);
                $table->text('footer_greeting')->nullable();
                $table->text('footer_policy')->nullable();
                $table->string('font_size', 20)->default('medium'); // small, medium, large
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['store_id', 'paper_size', 'is_default']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_templates');
    }
};
