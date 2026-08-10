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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number')->unique()->index();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('customer_address')->nullable();
            $table->text('customer_note')->nullable();
            $table->enum('contact_channel', ['viber', 'telegram', 'phone'])->default('viber');
            $table->enum('pricing_type', ['retail', 'wholesale'])->default('retail');
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['pending_contact', 'confirmed', 'delivered', 'cancelled'])->default('pending_contact');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
