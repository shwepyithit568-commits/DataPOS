<?php

namespace App\Http\Middleware;

use App\Services\StoreContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(protected StoreContext $storeContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolveLocale($request));

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $supported = array_keys(config('localization.supported', []));
        $sessionKey = config('localization.session_key', 'locale');

        $sessionLocale = $request->session()->get($sessionKey);
        if (in_array($sessionLocale, $supported, true)) {
            return $sessionLocale;
        }

        $store = $this->storeContext->getStore();
        $setting = $store?->relationLoaded('setting')
            ? $store->setting
            : $store?->setting()->first();

        if (in_array($setting?->default_language, $supported, true)) {
            return $setting->default_language;
        }

        $appLocale = config('app.locale');
        if (in_array($appLocale, $supported, true)) {
            return $appLocale;
        }

        $fallbackLocale = config('app.fallback_locale');

        return in_array($fallbackLocale, $supported, true) ? $fallbackLocale : 'en';
    }
}
