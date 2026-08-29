<?php

namespace App\Listeners;

use App\Events\ThemeRevisionCommitted;
use Illuminate\Support\Facades\Cache;

/**
 * Target-store cache invalidation after a theme publish/rollback commits.
 *
 * The public storefront is cached client-side (private, max-age=60 + ETag via
 * CachePublicPage), which the server cannot "forget". Instead, for a short
 * window after the commit we flip that store's public pages to max-age=0 so
 * browsers revalidate immediately (the content-derived ETag returns 304 when
 * nothing changed, or a fresh 200 with the new theme).
 *
 * Rules (ThemePlan §13 / §12):
 *  - Keyed by store_id — other stores are never touched.
 *  - Cache::flush() is never used.
 *  - The key prefix `storefront:theme:bumped:{store_id}` is also the hook a
 *    future server-side response cache would use to forget that store's page
 *    keys (`storefront:page:{store_id}:*`).
 */
class InvalidateStorefrontCache
{
    /** How long the immediate-revalidation window lasts after a commit. */
    public const WINDOW_SECONDS = 90;

    public static function bumpKey(int $storeId): string
    {
        return "storefront:theme:bumped:{$storeId}";
    }

    public function handle(ThemeRevisionCommitted $event): void
    {
        $storeId = $event->store->id;

        Cache::put(self::bumpKey($storeId), now()->timestamp, self::WINDOW_SECONDS);
    }
}
