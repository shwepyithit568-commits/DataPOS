<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and append security HTTP response headers.
     *
     * script-src uses a per-request nonce so inline <script> blocks (theme
     * bootstrap, scroll restore, view remember, blog editor, JSON-LD) are
     * allowed only when they carry the matching nonce attribute. 'unsafe-eval'
     * stays because Alpine's standard build compiles directive expressions
     * with the Function constructor; remove it only after migrating to the
     * alpinejs/csp build. Inline event-handler attributes were replaced by
     * delegated listeners in resources/js/csp-helpers.js, so 'unsafe-inline'
     * is no longer needed for scripts.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Per-request nonce shared with every rendered view so Blade layouts
        // can stamp their inline <script> tags (nonce="{{ $cspNonce }}").
        $nonce = base64_encode(random_bytes(18));
        View::share('cspNonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Dynamic HTML pages carry a per-request CSP nonce, a CSRF token and
        // live session state — never let browsers/webviews heuristically cache
        // them (a stale copy leaks an expired nonce/token). Static assets are
        // untouched: only text/html responses get Cache-Control: no-store.
        // Storefront public pages opted in via CachePublicPage keep their own
        // private max-age + ETag policy instead.
        if (str_contains((string) $response->headers->get('Content-Type', ''), 'text/html')
            && ! $request->attributes->get('cache_public_page', false)) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        // Content-Security-Policy: restricts resource loading origins.
        // script-src: nonce-based (see the docblock above); 'unsafe-eval' is
        // required by Alpine's standard build. frame-src allows the Google Maps
        // embed (mapEmbedSrc) and the YouTube video embeds on the how-to-order
        // page. style-src keeps 'unsafe-inline' for Tailwind/Vite inline styles.
        //
        // Local dev: when the Vite dev server is running (public/hot exists),
        // @vite injects asset URLs on the cross-origin dev server (port 5173).
        // Allow that origin here or every injected stylesheet/script/font is
        // refused ('self' only covers the app origin). Production keeps the
        // strict 'self'-only policy because the condition never matches there.
        $devSrc = '';
        $devConnect = '';
        if (app()->environment('local') && file_exists(public_path('hot'))) {
            $devSrc = ' http://localhost:5173 http://127.0.0.1:5173';
            $devConnect = ' ws://localhost:5173 ws://127.0.0.1:5173'; // Vite HMR websocket
        }

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; "
            . "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'{$devSrc}; "
            . "style-src 'self' 'unsafe-inline'{$devSrc}; "
            . "img-src 'self' data: blob:{$devSrc}; "
            . "font-src 'self'{$devSrc}; "
            . "connect-src 'self'{$devSrc}{$devConnect}; "
            . "frame-src 'self' https://www.google.com https://maps.google.com https://www.youtube.com; "
            . "frame-ancestors 'none'; "
            . "form-action 'self'; "
            . "base-uri 'self'; "
            . "object-src 'none'"
        );

        // Strict-Transport-Security: only set on HTTPS responses
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }
}
