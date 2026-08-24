<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('name', 100);
            $table->string('symbol', 20)->default('');
            $table->decimal('exchange_rate', 16, 4)->default(1.0000);
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'code'], 'currencies_store_code_unique');
            $table->index(['store_id', 'is_active'], 'currencies_store_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
