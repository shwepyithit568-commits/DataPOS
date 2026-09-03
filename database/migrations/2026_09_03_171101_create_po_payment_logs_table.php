<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('purchase_order_id')->index();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();

            // Payment details
            $table->decimal('amount', 15, 2);
            $table->string('reference', 120)->nullable();

            // Slip images: JSON array of relative storage paths (up to 4)
            $table->json('slip_images')->nullable();

            // Actor
            $table->unsignedBigInteger('paid_by')->nullable()->index();
            $table->timestamp('paid_at')->useCurrent();

            $table->timestamps();

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_payment_logs');
    }
};
