<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_master_presets')) {
            Schema::create('product_master_presets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->string('type', 50)->index(); // 'connector_spec', 'color', 'shelf_location', 'warranty', 'return_policy'
                $table->string('code', 50)->nullable()->index();
                $table->string('name', 255);
                $table->text('content')->nullable(); // Extended text/policy
                $table->string('color_hex', 20)->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['store_id', 'type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_master_presets');
    }
};
