<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32); // status|brand|category|model|color|storage|defect|accessory
            $table->string('name', 120);
            $table->string('code', 60)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('service_settings')->nullOnDelete();
            $table->timestamps();

            $table->index(['store_id', 'type']);
            $table->index(['store_id', 'is_active']);
        });

        Schema::table('service_jobs', function (Blueprint $table) {
            $table->string('brand', 120)->nullable()->after('device_type');
            $table->string('category', 120)->nullable()->after('brand');
            $table->string('color', 60)->nullable()->after('model');
            $table->string('storage', 60)->nullable()->after('color');
            $table->string('pattern_lock', 255)->nullable()->after('accessories');
            $table->string('device_password', 120)->nullable()->after('pattern_lock');
            $table->text('shipping_address')->nullable()->after('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'brand',
                'category',
                'color',
                'storage',
                'pattern_lock',
                'device_password',
                'shipping_address',
            ]);
        });

        Schema::dropIfExists('service_settings');
    }
};
