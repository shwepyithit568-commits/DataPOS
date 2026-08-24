<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('serial_number')->index();
            $table->string('imei_primary')->nullable()->index();
            $table->string('imei_secondary')->nullable();
            $table->string('invoice_number')->nullable()->index();
            $table->foreignId('pos_sale_id')->nullable()->constrained('pos_sales')->nullOnDelete();
            $table->date('purchase_date');
            $table->integer('warranty_duration_months')->default(12);
            $table->date('warranty_expiry_date')->index();
            $table->string('warranty_type')->default('shop'); // shop, official_brand, distributor, service_only
            $table->string('status')->default('active')->index(); // active, expired, void, claimed
            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->integer('claim_count')->default(0);
            $table->dateTime('last_claimed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_warranties');
    }
};
