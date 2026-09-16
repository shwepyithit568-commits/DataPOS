<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the offline-to-cloud sync API with a per-store API key.
 *
 * Terminals authenticate with `Authorization: Bearer <key>` or `X-Sync-Key`.
 * The key is issued from the Sync admin screen and stored hashed on the store.
 */
class AuthenticateSyncRequest
{
    public const STORE_ATTRIBUTE = 'sync_store';

    public function handle(Request $request, Closure $next): Response
    {
        $store = Store::where('slug', (string) $request->route('slug'))->first();

        // An unknown slug and a bad key return the same response so the
        // endpoint cannot be used to enumerate store slugs.
        if (! $store || ! $store->verifySyncApiKey($this->presentedKey($request))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing sync API key.',
            ], 401);
        }

        // The controller reads the resolved store instead of re-querying.
        $request->attributes->set(self::STORE_ATTRIBUTE, $store);

        return $next($request);
    }

    private function presentedKey(Request $request): ?string
    {
        $bearer = $request->bearerToken();
        if (is_string($bearer) && $bearer !== '') {
            return $bearer;
        }

        $header = $request->header('X-Sync-Key');

        return is_string($header) && $header !== '' ? $header : null;
    }
}
