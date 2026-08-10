<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured, admin-managed payment methods (replaces hard-coded footer
 * badges). Account numbers stay masked by default and are only exposed on the
 * storefront when `show_account_details` is deliberately enabled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->default('custom');
            $table->string('icon_type')->default('initials'); // builtin | custom | initials
            $table->string('icon_value')->nullable();          // builtin key or emoji
            $table->string('icon_path')->nullable();           // custom uploaded icon
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_account_details')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['store_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_payment_methods');
    }
};
