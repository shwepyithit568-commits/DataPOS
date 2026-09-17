<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Non-destructive migration to support POS Cash Out Idempotency:
     * Adds client_transaction_id to expenses table scoped by store_id
     * to prevent double-spending / duplicated expense records on rapid retries.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('client_transaction_id', 100)
                ->nullable()
                ->after('expense_number');

            $table->unique(['store_id', 'client_transaction_id'], 'expenses_store_client_tx_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique('expenses_store_client_tx_unique');
            $table->dropColumn('client_transaction_id');
        });
    }
};
