<?php

namespace App\Support;

use App\Contracts\LoggablePushNotification;
use App\Models\PushNotificationLog;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Single entry point for Web Push notifications to a store's admins.
 *
 * - Dedupe: Cache::add is atomic, so two requests for the same event
 *   (double-click on "place order", a retried status PATCH, …) can only ever
 *   send once per dedupe key. The key must uniquely identify the event.
 * - The notification itself implements ShouldQueue, so the caller is never
 *   slowed down by the webpush HTTP delivery; only the dedupe marker and the
 *   audit-log row are written here.
 * - Best-effort: any failure is logged and swallowed — a push problem must
 *   never break order creation or an admin status update.
 */
class AdminPushNotifier
{
    public function __construct(private readonly int $dedupeTtlSeconds = 3600)
    {
    }

    /**
     * @param  string  $dedupeKey  unique per event, e.g. "order-created.42"
     *                             or "order-status.42.confirmed"
     */
    public function dispatch(Store $store, string $dedupeKey, LoggablePushNotification $notification): void
    {
        if (! Cache::add('push.dedupe.' . $dedupeKey, true, $this->dedupeTtlSeconds)) {
            return; // already notified for this exact event
        }

        try {
            $admins = $store->users()
                ->wherePivot('status', 'active')
                ->wherePivotIn('role', ['store_manager', 'staff'])
                ->get();

            if ($admins->isEmpty()) {
                return;
            }

            Notification::send($admins, $notification);

            PushNotificationLog::create([
                'type' => $notification->logType(),
                'title' => $notification->logTitle(),
                'body' => $notification->logBody(),
                'url' => $notification->logUrl(),
                'recipient_count' => $admins->count(),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Web push (' . $notification->logType() . ') dispatch failed: ' . $e->getMessage());
        }
    }
}
