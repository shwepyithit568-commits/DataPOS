<?php

namespace App\Http\Middleware;

use App\Themes\ThemeContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Clears the request-scoped ThemeContext override after every response.
 *
 * ThemeContext is a scoped container binding, but long-running runtimes
 * (Octane, Swoole) and Laravel feature tests reuse the same container across
 * multiple requests within one process, and Laravel only flushes scoped
 * instances for queued jobs. This after-middleware guarantees the draft
 * preview set by the appearance preview route can never leak into a
 * subsequent request (e.g. an anonymous storefront page view).
 */
class ResetThemePreview
{
    public function __construct(protected ThemeContext $themeContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->themeContext->setConfig(null);

        return $response;
    }
}
