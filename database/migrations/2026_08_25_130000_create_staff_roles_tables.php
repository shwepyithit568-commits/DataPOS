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
        Schema::create('staff_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('color')->default('#0284c7');
            $table->json('permissions')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'slug']);
        });

        Schema::table('store_user', function (Blueprint $table) {
            if (!Schema::hasColumn('store_user', 'staff_role_id')) {
                $table->foreignId('staff_role_id')->nullable()->after('role')->constrained('staff_roles')->nullOnDelete();
            }
            if (!Schema::hasColumn('store_user', 'custom_permissions')) {
                $table->json('custom_permissions')->nullable()->after('staff_role_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_user', function (Blueprint $table) {
            if (Schema::hasColumn('store_user', 'staff_role_id')) {
                $table->dropForeign(['staff_role_id']);
                $table->dropColumn('staff_role_id');
            }
            if (Schema::hasColumn('store_user', 'custom_permissions')) {
                $table->dropColumn('custom_permissions');
            }
        });

        Schema::dropIfExists('staff_roles');
    }
};
