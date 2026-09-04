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
        Schema::create('storefront_navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('menu_key', 100);
            $table->string('label_my');
            $table->string('label_en');
            $table->string('label_zh_cn')->nullable();
            $table->string('icon_key', 50)->default('home');
            $table->string('destination_type', 30)->default('system'); // system, page, custom_url
            $table->string('destination_key', 50)->nullable(); // e.g. home, products, categories, glass_finder, service_tracking, how_to_order, blog, cart, account, login, register
            $table->foreignId('storefront_page_id')->nullable()->constrained('storefront_pages')->nullOnDelete();
            $table->text('custom_url')->nullable();
            $table->boolean('show_desktop')->default(true);
            $table->boolean('show_mobile_drawer')->default(true);
            $table->boolean('show_mobile_bottom')->default(false);
            $table->boolean('requires_auth')->default(false);
            $table->string('required_capability', 100)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'menu_key'], 'store_menu_key_unique');
            $table->index(['store_id', 'is_enabled', 'sort_order'], 'store_nav_sort_idx');
            $table->index(['store_id', 'destination_type'], 'store_nav_dest_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storefront_navigation_items');
    }
};
