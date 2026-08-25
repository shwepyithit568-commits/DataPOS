<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wholesale_applications', function (Blueprint $table) {
            // Internal admin-only note, separate from the applicant's notes
            $table->text('admin_note')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('wholesale_applications', function (Blueprint $table) {
            $table->dropColumn('admin_note');
        });
    }
};
