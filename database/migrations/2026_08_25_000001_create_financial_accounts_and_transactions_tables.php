<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('financial_accounts')) {
            Schema::create('financial_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained()->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('code', 60);
                $table->string('account_type', 30)->default('cash'); // cash, mobile_wallet, bank_account, other
                $table->string('account_number', 80)->nullable();
                $table->string('account_holder', 120)->nullable();
                $table->decimal('opening_balance', 16, 2)->default(0.00);
                $table->decimal('current_balance', 16, 2)->default(0.00);
                $table->string('currency', 10)->default('MMK');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['store_id', 'code']);
                $table->index(['store_id', 'account_type', 'is_active']);
            });
        }

        if (!Schema::hasTable('financial_transactions')) {
            Schema::create('financial_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained()->cascadeOnDelete();
                $table->string('transaction_number', 60);
                $table->foreignId('from_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
                $table->foreignId('to_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
                $table->string('type', 30); // deposit, withdrawal, transfer, opening_balance
                $table->string('category', 60)->nullable(); // capital_injection, owner_drawing, bank_deposit, etc.
                $table->decimal('amount', 16, 2);
                $table->decimal('fee', 16, 2)->default(0.00);
                $table->dateTime('transaction_date');
                $table->string('reference_no', 100)->nullable();
                $table->string('payer_or_payee', 150)->nullable();
                $table->text('notes')->nullable();
                $table->string('attachment_path', 255)->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['store_id', 'transaction_number']);
                $table->index(['store_id', 'type', 'transaction_date']);
                $table->index(['store_id', 'from_account_id']);
                $table->index(['store_id', 'to_account_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('financial_accounts');
    }
};
