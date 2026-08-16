<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opts a public storefront page into browser caching.
 *
 * These pages are static-ish (catalog data + shared layout) and the service
 * worker already stale-while-revalidates them, so the browser may serve its
 * own short-lived copy for repeat visits. A per-response ETag (covering the
 * exact rendered bytes, nonce included) makes the revalidation after
 * `max-age` a cheap 304 instead of a full re-render.
 *
 * `private` keeps each browser's copy separate (locale + session variation
 * never leaks between users via a shared cache). Only applied to public GET
 * pages (home / product / browse): private pages (POS, admin, account, auth)
 * keep SecurityHeaders' Cache-Control: no-store.
 */
class CachePublicPage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Signal SecurityHeaders (global middleware, runs outside this one)
        // to leave Cache-Control alone on this response.
        $request->attributes->set('cache_public_page', true);

        if ($request->isMethod('GET') && $response->isOk()
            && str_contains((string) $response->headers->get('Content-Type', ''), 'text/html')) {
            // ETag over the body with the per-request entropy normalized away:
            // the CSP nonce and the components' random SVG ids (flag.blade.php,
            // product-card.blade.php) otherwise change the hash on every request
            // and a 304 can never match. A 304 keeps the cached copy, which is
            // self-consistent (its own nonce + CSP header travel together). If
            // a future component adds another random id, the ETag silently
            // stops matching — it degrades to the max-age path, never a wrong
            // response.
            $content = (string) $response->getContent();
            $nonce = \Illuminate\Support\Facades\View::getShared()['cspNonce'] ?? null;
            if ($nonce !== null && $nonce !== '') {
                $content = str_replace($nonce, 'NONCE', $content);
            }
            $content = preg_replace('/(id="|url\(#|href="#)(flag-[a-z]{2,5}(_[A-Z]{2})?-[A-Za-z0-9]{6}|c\d+-[A-Za-z0-9]{6})/', '$1RANDOM', $content);
            $content = preg_replace("/(cardKey: ')(c\d+-[A-Za-z0-9]{6})/", '$1RANDOM', $content);
            $etag = '"' . md5((string) $content) . '"';
            $response->headers->set('ETag', $etag);
            $response->headers->set('Cache-Control', 'private, max-age=60, must-revalidate');

            // Laravel never calls Symfony's Response::prepare(), so conditional
            // GET is not handled automatically — return the 304 ourselves when
            // the client's cached copy matches the ETag (setNotModified keeps
            // the ETag + Cache-Control headers and strips the body).
            if ($response->isNotModified($request)) {
                return $response->setNotModified();
            }
        }

        return $response;
    }
}
