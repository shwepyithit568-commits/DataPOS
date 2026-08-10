<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variant_presets', function (Blueprint $table) {
            $table->string('category_family', 40)->nullable()->after('name')->index();
        });

        DB::table('variant_presets')
            ->whereNull('category_family')
            ->where(function ($query) {
                $query->where('name', 'like', '%Mobile%')
                    ->orWhere('name', 'like', '%Phone%');
            })
            ->update(['category_family' => 'mobile']);

        DB::table('variant_presets')
            ->whereNull('category_family')
            ->where(function ($query) {
                $query->where('name', 'like', '%Accessories%')
                    ->orWhere('name', 'like', '%Accessory%');
            })
            ->update(['category_family' => 'accessories']);

        DB::table('variant_presets')
            ->whereNull('category_family')
            ->where(function ($query) {
                $query->where('name', 'like', '%CCTV%')
                    ->orWhere('name', 'like', '%Camera%');
            })
            ->update(['category_family' => 'cctv']);

        DB::table('variant_presets')
            ->whereNull('category_family')
            ->where(function ($query) {
                $query->where('name', 'like', '%Computer%')
                    ->orWhere('name', 'like', '%Laptop%')
                    ->orWhere('name', 'like', '%RAM%');
            })
            ->update(['category_family' => 'computer']);

        DB::table('variant_presets')
            ->whereNull('category_family')
            ->where('name', 'like', '%Fashion%')
            ->update(['category_family' => 'fashion']);
    }

    public function down(): void
    {
        Schema::table('variant_presets', function (Blueprint $table) {
            $table->dropIndex(['category_family']);
            $table->dropColumn('category_family');
        });
    }
};
