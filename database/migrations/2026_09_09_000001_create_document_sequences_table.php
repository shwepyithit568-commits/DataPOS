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
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 40); // sale, invoice, return, purchase_order, goods_receipt, service_job, adjustment
            $table->string('period_key', 20)->default('global'); // e.g. '2026', '202609', 'global'
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'document_type', 'period_key'], 'doc_seq_store_type_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
