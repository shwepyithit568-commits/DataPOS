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
        if (! Schema::hasTable('restaurant_tables')) {
            Schema::create('restaurant_tables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name', 100);
                $table->string('zone', 50)->default('Indoor'); // Indoor, Outdoor, VIP, Bar
                $table->unsignedInteger('capacity')->default(4);
                $table->string('status', 30)->default('available'); // available, occupied, reserved, dirty
                $table->string('active_session_id', 100)->nullable();
                $table->timestamps();

                $table->index(['store_id', 'zone', 'status']);
            });
        }

        if (! Schema::hasTable('kitchen_order_tickets')) {
            Schema::create('kitchen_order_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('table_id')->nullable()->constrained('restaurant_tables')->nullOnDelete();
                $table->foreignId('server_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ticket_number', 50);
                $table->string('order_type', 30)->default('dine_in'); // dine_in, takeaway, delivery
                $table->json('items'); // [{product_id, name, qty, modifiers}]
                $table->string('status', 30)->default('pending'); // pending, preparing, ready, served, cancelled
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['store_id', 'status']);
                $table->index(['store_id', 'ticket_number']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kitchen_order_tickets');
        Schema::dropIfExists('restaurant_tables');
    }
};
