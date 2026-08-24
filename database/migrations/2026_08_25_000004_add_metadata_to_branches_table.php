<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'phone')) {
                $table->string('phone', 100)->nullable()->after('code');
            }
            if (!Schema::hasColumn('branches', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('branches', 'manager_name')) {
                $table->string('manager_name', 120)->nullable()->after('address');
            }
            if (!Schema::hasColumn('branches', 'notes')) {
                $table->text('notes')->nullable()->after('manager_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['phone', 'address', 'manager_name', 'notes']);
        });
    }
};
