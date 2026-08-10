<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Preserves the admin list view across an edit/create round-trip.
 *
 * The list controllers call capture() while rendering the index so the current
 * filter state (search, filters, sort, per_page, page) is remembered in the
 * session. update()/store() then resolve() that URL instead of redirecting to
 * a bare list path that would drop every query parameter.
 */
class AdminListReturn
{
    /**
     * Remember the current (filtered) list URI — path + query string, no
     * origin — for a given session key. Storing the relative URI keeps the
     * redirect host-agnostic (the app may be reached via LAN IP, localhost or
     * the production domain).
     */
    public static function capture(Request $request, string $sessionKey): void
    {
        session()->put($sessionKey, $request->getRequestUri());
    }

    /**
     * Read the stored filtered list URI without consuming it — for rendering
     * the edit/create page (back links). The session entry is kept so the
     * subsequent update()/store() redirect can still resolve it.
     */
    public static function peek(string $sessionKey, string $fallback): string
    {
        $url = (string) session()->get($sessionKey, '');

        if ($url === '' || ! str_starts_with($url, '/store/') || str_contains($url, '://')) {
            return $fallback;
        }

        return $url;
    }

    /**
     * Resolve the URI to redirect to after an edit/create: the stored filtered
     * list URI when it is a safe relative /store/ path, otherwise the fallback.
     *
     * Consumes the stored URI so a later create/edit does not reuse stale
     * filter state from an unrelated earlier list visit.
     */
    public static function resolve(string $sessionKey, string $fallback): string
    {
        $url = self::peek($sessionKey, $fallback);

        session()->forget($sessionKey);

        return $url;
    }
}
