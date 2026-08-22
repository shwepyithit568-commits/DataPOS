<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair Center / Service Jobs — Parts & Services line items (§16 Used Parts).
 *
 * A service job can carry line items: repair services (labour) and spare
 * parts. Part rows may reference an inventory product so stock can be
 * deducted through the ledger (`service_consumption`, SoT §5) when the part
 * is physically consumed. Line-item money is decimal(12,2) (MMK, §2.6).
 *
 * Also adds `estimated_completion` so the Ready-for-pickup promise date can
 * be tracked (Repairs Center parity with the mobile admin reference).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_job_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_id')->constrained()->cascadeOnDelete();
            $table->string('item_type', 12); // service|part
            $table->string('name', 120);
            $table->string('sku', 40)->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->boolean('is_deducted')->default(false);
            $table->timestamps();

            $table->index('service_job_id');
        });

        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dateTime('estimated_completion')->nullable()->after('warranty_notes');
        });
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dropColumn('estimated_completion');
        });

        Schema::dropIfExists('service_job_items');
    }
};
