<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('printers')) {
            Schema::create('printers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained()->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('connection_type', 30)->default('browser'); // browser, network, usb, bluetooth
                $table->string('paper_width', 20)->default('80mm'); // 80mm, 58mm
                $table->string('ip_address', 60)->nullable();
                $table->unsignedInteger('port')->default(9100);
                $table->string('device_path', 255)->nullable();
                $table->string('printer_role', 30)->default('receipt'); // receipt, kitchen, service, label
                $table->unsignedSmallInteger('print_copies')->default(1);
                $table->boolean('auto_cut')->default(true);
                $table->boolean('cash_drawer_kick')->default(true);
                $table->boolean('beep_on_print')->default(false);
                $table->boolean('print_logo')->default(true);
                $table->unsignedSmallInteger('feed_lines')->default(2);
                $table->text('header_text')->nullable();
                $table->text('footer_text')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['store_id', 'is_active', 'is_default']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('printers');
    }
};
