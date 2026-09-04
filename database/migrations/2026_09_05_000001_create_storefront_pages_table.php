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
        Schema::create('storefront_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('title_my');
            $table->string('title_en');
            $table->string('title_zh_cn')->nullable();
            $table->string('slug');
            $table->text('summary_my')->nullable();
            $table->text('summary_en')->nullable();
            $table->text('summary_zh_cn')->nullable();
            $table->longText('content_my')->nullable();
            $table->longText('content_en')->nullable();
            $table->longText('content_zh_cn')->nullable();
            $table->string('featured_image_path')->nullable();
            $table->string('meta_title_my')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->string('meta_title_zh_cn')->nullable();
            $table->text('meta_description_my')->nullable();
            $table->text('meta_description_en')->nullable();
            $table->text('meta_description_zh_cn')->nullable();
            $table->string('status', 20)->default('draft'); // draft, published
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['store_id', 'slug'], 'store_slug_unique');
            $table->index(['store_id', 'status', 'is_enabled', 'published_at'], 'store_page_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storefront_pages');
    }
};
