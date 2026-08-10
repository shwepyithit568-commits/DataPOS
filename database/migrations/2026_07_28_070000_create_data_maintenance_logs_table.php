<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('execution_id')->index();
            $table->string('operation')->index();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->string('record_type')->index();
            $table->unsignedBigInteger('record_id')->index();
            $table->string('field_name');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->json('metadata')->nullable();
            $table->string('executed_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['execution_id', 'record_type', 'record_id'], 'maintenance_execution_record_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_maintenance_logs');
    }
};
