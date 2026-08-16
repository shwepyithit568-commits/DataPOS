<?php

/**
 * Laravel dev-server router — project copy of the framework's server.php.
 *
 * Why a custom copy: the built-in PHP server discards any header() a router
 * script sets when it ends with `return false` (the static-file fast path),
 * so the framework router can never attach Cache-Control to static files.
 * Versioned build assets (public/build/*) carry content-hashed filenames that
 * never change, so we serve those ourselves with a far-future immutable cache
 * policy — the service-worker shell and every page stop re-fetching CSS/JS on
 * repeat visits. Everything else behaves exactly like the framework router.
 */

$publicPath = __DIR__.'/public';

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Build assets are content-hashed → cache forever. Served explicitly because
// `return false` would drop the Cache-Control header set above it.
if (str_starts_with($uri, '/build/') && file_exists($publicPath.$uri)) {
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Type: '.match (strtolower(pathinfo($uri, PATHINFO_EXTENSION))) {
        'css' => 'text/css',
        'js', 'mjs' => 'application/javascript',
        'json', 'map' => 'application/json',
        'woff2' => 'font/woff2',
        'woff' => 'font/woff',
        'ttf' => 'font/ttf',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'ico' => 'image/x-icon',
        default => 'application/octet-stream',
    });

    if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
        readfile($publicPath.$uri);
    }

    return;
}

// Other static files: let the built-in server handle them directly
// (matching the framework router's behavior).
if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

require_once $publicPath.'/index.php';
