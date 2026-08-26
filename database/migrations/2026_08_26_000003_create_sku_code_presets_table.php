<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sku_code_presets')) {
            return;
        }

        Schema::create('sku_code_presets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('type', 50)->index(); // 'model', 'connector_spec', 'color', 'quality', 'capacity'
            $table->string('code', 50)->index();
            $table->string('name', 150);
            $table->string('color_hex', 20)->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['store_id', 'type']);
            $table->unique(['store_id', 'type', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sku_code_presets');
    }
};
