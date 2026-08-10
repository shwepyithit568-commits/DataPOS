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
        Schema::create('glass_finder_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('brand')->index();
            $table->string('phone_model')->index();
            $table->string('glass_code');
            $table->string('normalized_glass_code')->index();
            $table->enum('stock_status', ['in_stock', 'out_of_stock'])->default('in_stock');
            $table->timestamps();

            $table->unique(['store_id', 'phone_model', 'normalized_glass_code'], 'store_phone_glass_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glass_finder_items');
    }
};
