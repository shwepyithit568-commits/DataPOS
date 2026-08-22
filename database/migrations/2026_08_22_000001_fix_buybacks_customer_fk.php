<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support ALTER TABLE to drop/recreate FK constraints,
        // so we recreate the table with the correct FK reference to users.
        Schema::dropIfExists('buy_back_items_backup');
        Schema::dropIfExists('buy_backs_backup');

        // Backup existing data
        DB::statement('CREATE TABLE buy_backs_backup AS SELECT * FROM buy_backs');
        DB::statement('CREATE TABLE buy_back_items_backup AS SELECT * FROM buy_back_items');

        Schema::dropIfExists('buy_back_items');
        Schema::dropIfExists('buy_backs');

        Schema::create('buy_backs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('buyback_number', 32);
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pos_sale_id')->nullable()->constrained('pos_sales')->nullOnDelete();
            $table->decimal('total_value', 14, 4)->default(0);
            $table->decimal('refund_amount', 14, 4)->default(0);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['store_id', 'buyback_number']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('buy_back_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buy_back_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->timestamps();

            $table->index(['buy_back_id', 'product_id']);
        });

        // Restore data
        DB::statement('INSERT INTO buy_backs SELECT * FROM buy_backs_backup');
        DB::statement('INSERT INTO buy_back_items SELECT * FROM buy_back_items_backup');

        Schema::dropIfExists('buy_back_items_backup');
        Schema::dropIfExists('buy_backs_backup');
    }

    public function down(): void
    {
        // Not easily reversible without backup data; acceptable for dev.
    }
};
