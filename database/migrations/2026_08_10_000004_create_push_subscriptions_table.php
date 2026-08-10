<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Push subscription store for Web Push (laravel-notification-channels/webpush).
     *
     * Schema notes:
     *  - `endpoint` is a varchar(500) with a UNIQUE index. The task asked for a
     *    TEXT column plus a unique index, but MySQL cannot index a TEXT column
     *    without a prefix length, so a 500-char string is the standard way to
     *    get a true unique index on both MySQL and SQLite.
     *  - `keys` stores the browser subscription keys (p256dh + auth) as JSON,
     *    as requested. The PushSubscription model maps these to the package's
     *    `public_key` / `auth_token` attributes via accessors.
     */
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('endpoint', 500)->unique();
            $table->json('keys')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
