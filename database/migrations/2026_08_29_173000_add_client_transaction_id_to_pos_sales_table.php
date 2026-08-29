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
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->string('client_transaction_id', 100)->nullable()->after('receipt_number');
            $table->unique(['store_id', 'client_transaction_id'], 'pos_sales_store_client_tx_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropUnique('pos_sales_store_client_tx_unique');
            $table->dropColumn('client_transaction_id');
        });
    }
};
