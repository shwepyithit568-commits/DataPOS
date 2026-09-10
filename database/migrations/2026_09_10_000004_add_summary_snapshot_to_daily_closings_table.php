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
        Schema::table('daily_closings', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_closings', 'summary_snapshot')) {
                $table->json('summary_snapshot')->nullable()->after('differences');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_closings', function (Blueprint $table) {
            if (Schema::hasColumn('daily_closings', 'summary_snapshot')) {
                $table->dropColumn('summary_snapshot');
            }
        });
    }
};
