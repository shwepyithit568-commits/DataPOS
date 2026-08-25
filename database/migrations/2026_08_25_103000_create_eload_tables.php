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
        Schema::create('eload_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('operator', 30); // mpt, atom, ooredoo, mytel, other
            $table->string('name', 100);
            $table->string('phone_number', 50)->nullable();
            $table->decimal('balance', 14, 2)->default(0.00);
            $table->decimal('discount_percent', 5, 2)->default(0.00); // e.g. 4.00 for 4%
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['store_id', 'operator']);
            $table->index(['store_id', 'is_active']);
        });

        Schema::create('eload_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('eload_account_id')->nullable()->constrained('eload_accounts')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('operator', 30); // mpt, atom, ooredoo, mytel, other
            $table->string('phone_number', 50);
            $table->string('customer_name', 100)->nullable();
            $table->string('type', 30)->default('topup'); // topup, data_pack, pin_code, bill_payment
            $table->string('package_name', 150)->nullable();
            $table->decimal('amount', 14, 2); // selling price (e.g. 5,000 Ks)
            $table->decimal('cost', 14, 2)->default(0.00); // buying cost (e.g. 4,800 Ks)
            $table->decimal('profit', 14, 2)->default(0.00); // profit (amount - cost, e.g. 200 Ks)
            $table->string('payment_method', 30)->default('cash'); // cash, kpay, wavepay, cbpay, ayapay, other
            $table->string('status', 30)->default('completed'); // completed, pending, failed, refunded
            $table->string('ref_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->index(['store_id', 'operator']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'occurred_at']);
            $table->index(['store_id', 'phone_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eload_transactions');
        Schema::dropIfExists('eload_accounts');
    }
};
