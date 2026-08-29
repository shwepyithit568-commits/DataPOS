<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_theme_drafts', function (Blueprint $table) {
            $table->id();

            // One active draft per store (enforced by UNIQUE)
            $table->foreignId('store_id')
                ->unique()
                ->constrained('stores')
                ->cascadeOnDelete();

            // Complete normalized ThemeConfig snapshot (9 safe keys)
            $table->json('theme_config');

            // The revision that was "published" when this draft was last
            // created or re-based.  NULL = store had no revisions yet.
            // SET NULL on delete so old history pruning doesn't break drafts.
            $table->foreignId('base_revision_id')
                ->nullable()
                ->constrained('store_theme_revisions')
                ->nullOnDelete();

            // Actor who last touched this draft
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Optimistic concurrency counter.  Incremented on every successful
            // save.  A save carrying a stale lock_version is rejected (HTTP 409).
            $table->unsignedInteger('lock_version')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_theme_drafts');
    }
};
