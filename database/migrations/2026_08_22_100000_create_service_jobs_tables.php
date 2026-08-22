<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair Center / Service Jobs (SoT §16 — Service Module).
 *
 * A service job represents a CUSTOMER-OWNED device — it is never counted as
 * store inventory. The job carries the full lifecycle: intake (device, IMEI,
 * reported problem, condition, accessories) → diagnosis → technician work →
 * ready → delivered. Cancellation / unrepairable states are explicit.
 *
 * Money is decimal(12,2) (MMK, §2.6 policy). Payments are immutable receipt
 * rows; outstanding balance is derived, never stored-and-edited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('job_number', 32); // RPR-YYYYMMDD-####
            // Optional paper voucher number (§16 — Optional Paper voucher_no).
            $table->string('voucher_no', 40)->nullable();
            // Linked customer account, OR walk-in contact fields (§16 Contact).
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('contact_name', 120)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('device_type', 60);
            $table->string('model', 120)->nullable();
            $table->string('imei_serial', 60)->nullable();
            $table->text('reported_problem');
            $table->text('intake_condition')->nullable();
            $table->string('accessories', 500)->nullable();
            $table->text('diagnosis')->nullable();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            // received|diagnosing|awaiting_approval|awaiting_parts|in_repair|ready|delivered|cancelled|unrepairable
            $table->string('status', 24)->default('received');
            $table->decimal('estimated_charge', 12, 2)->default(0);
            $table->decimal('final_charge', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('warranty_notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['store_id', 'job_number']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'imei_serial']);
        });

        // §16 Status History — every status change is an immutable row.
        Schema::create('service_job_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_id')->constrained()->cascadeOnDelete();
            $table->string('status', 24);
            $table->string('note', 500)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('service_job_id');
        });

        // §16 Payments — immutable receipts; outstanding is derived.
        Schema::create('service_job_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20); // cash|kpay|wavepay|cb_pay|mmqr
            $table->decimal('amount', 12, 2);
            $table->string('reference', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('service_job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_job_payments');
        Schema::dropIfExists('service_job_statuses');
        Schema::dropIfExists('service_jobs');
    }
};
