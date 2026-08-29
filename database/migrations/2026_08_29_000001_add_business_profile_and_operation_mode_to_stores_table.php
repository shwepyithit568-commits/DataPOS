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
        Schema::table('stores', function (Blueprint $table) {
            $table->string('business_profile', 50)->nullable()->after('business_type')->index();
            $table->string('operation_mode', 30)->default('omnichannel')->after('business_profile');
            $table->json('capabilities_override')->nullable()->after('operation_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropIndex(['business_profile']);
            $table->dropColumn(['business_profile', 'operation_mode', 'capabilities_override']);
        });
    }
};
