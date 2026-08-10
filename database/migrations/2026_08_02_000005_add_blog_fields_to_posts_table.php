<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('posts', 'category')) {
            return;
        }

        Schema::table('posts', function (Blueprint $table) {
            // Blog category label (e.g. Tips & Tricks, How-to Guide…)
            $table->string('category', 100)->nullable()->after('slug');
            // Comma-separated tags
            $table->string('tags', 255)->nullable()->after('excerpt');
            // SEO meta fields
            $table->string('meta_keywords', 255)->nullable()->after('content');
            $table->text('meta_description')->nullable()->after('meta_keywords');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('posts', 'category')) {
            return;
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['category', 'tags', 'meta_keywords', 'meta_description']);
        });
    }
};
