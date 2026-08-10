<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured, admin-managed delivery methods. Fee notes stay descriptive
 * (no guaranteed fee calculation — pricing depends on location and item size).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_delivery_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('custom');
            $table->string('icon')->nullable(); // emoji
            $table->text('description')->nullable();
            $table->string('service_area')->nullable();
            $table->string('estimated_time')->nullable();
            $table->string('fee_note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['store_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_delivery_methods');
    }
};
