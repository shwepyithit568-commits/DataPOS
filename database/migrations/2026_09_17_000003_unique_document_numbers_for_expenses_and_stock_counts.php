<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unique backstop for the two document series that lacked one.
     *
     * `service_jobs`, `buy_backs`, `stock_transfers` and `opening_stock_requests`
     * already had unique(store_id, <number>); `expenses` and `stock_counts`
     * did not, so a duplicate number produced by a race would be written
     * silently. Numbers are now issued from `document_sequences` under a row
     * lock — this index makes a regression fail loudly instead of quietly.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->unique(['store_id', 'expense_number'], 'expenses_store_expense_number_unique');
        });

        Schema::table('stock_counts', function (Blueprint $table) {
            $table->unique(['store_id', 'session_number'], 'stock_counts_store_session_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique('expenses_store_expense_number_unique');
        });

        Schema::table('stock_counts', function (Blueprint $table) {
            $table->dropUnique('stock_counts_store_session_number_unique');
        });
    }
};
