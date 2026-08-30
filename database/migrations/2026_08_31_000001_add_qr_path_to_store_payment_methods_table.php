<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_payment_methods', function (Blueprint $table) {
            if (! Schema::hasColumn('store_payment_methods', 'qr_path')) {
                $table->string('qr_path')->nullable()->after('icon_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_payment_methods', function (Blueprint $table) {
            if (Schema::hasColumn('store_payment_methods', 'qr_path')) {
                $table->dropColumn('qr_path');
            }
        });
    }
};
