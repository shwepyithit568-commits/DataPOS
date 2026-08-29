<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_governance', function (Blueprint $table) {
            $table->id();

            // One override row per theme id (from ThemeRegistry).
            $table->string('theme_id', 60)->unique();

            // Lifecycle override: active | deprecated | hidden.
            // The static manifest status is the fallback when no row exists.
            $table->string('status', 20)->default('active');

            // Recommended replacement theme when deprecated (nullable).
            $table->string('replacement_id', 60)->nullable();

            // Platform Owner who changed the lifecycle.
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_governance');
    }
};
