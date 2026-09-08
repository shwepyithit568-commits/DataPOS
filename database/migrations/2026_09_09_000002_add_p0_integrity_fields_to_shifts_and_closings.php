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
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->text('variance_reason')->nullable()->after('closing_note');
            $table->foreignId('manager_signoff_id')->nullable()->constrained('users')->nullOnDelete()->after('variance_reason');
            $table->dateTime('signed_off_at')->nullable()->after('manager_signoff_id');
        });

        Schema::table('daily_closings', function (Blueprint $table) {
            $table->dateTime('reopened_at')->nullable()->after('approved_at');
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete()->after('reopened_at');
            $table->text('reopen_reason')->nullable()->after('reopened_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_closings', function (Blueprint $table) {
            $table->dropForeign(['reopened_by']);
            $table->dropColumn(['reopened_at', 'reopened_by', 'reopen_reason']);
        });

        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropForeign(['manager_signoff_id']);
            $table->dropColumn(['variance_reason', 'manager_signoff_id', 'signed_off_at']);
        });
    }
};
