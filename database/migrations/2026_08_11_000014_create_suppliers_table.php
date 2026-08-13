<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store-scoped supplier master data (SoT "Customers & Suppliers" foundation).
     * Suppliers are referenced by goods receipts (receiving reference) and will
     * feed purchasing/payables in the Operations phase.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // A supplier is unique within a store by phone when provided;
            // NULL phones stay unique-per-row (SQLite/MySQL both allow
            // multiple NULLs in a unique index).
            $table->unique(['store_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
