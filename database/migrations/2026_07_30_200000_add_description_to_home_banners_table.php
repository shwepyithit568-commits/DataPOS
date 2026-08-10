<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('home_banners', 'description')) {
            return;
        }

        Schema::table('home_banners', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('home_banners', 'description')) {
            return;
        }

        Schema::table('home_banners', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
