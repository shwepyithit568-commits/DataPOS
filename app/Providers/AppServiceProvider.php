<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\StoreContext::class, function () {
            return new \App\Services\StoreContext();
        });

        // Request-scoped theme override for the storefront preview (T3).
        // Scoped binding = fresh instance per request, so a draft set by the
        // preview route can never leak into other requests (Octane-safe).
        $this->app->scoped(\App\Themes\ThemeContext::class);
    }

    /**
     * Bootstrap any application services.
     *
     * Production safety checks:
     * - Forces HTTPS for URL generation when explicitly enabled.
     * - Validates that APP_KEY is set (encryption/session security).
     * - Logs a warning if APP_DEBUG is enabled in production.
     */
    public function boot(): void
    {
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        if ($this->app->environment('production')) {
            // Warn if debug mode is enabled in production
            if (config('app.debug')) {
                Log::warning('APP_DEBUG is enabled in production environment. This exposes sensitive information.');
            }

            // Validate APP_KEY is set and not a placeholder
            $appKey = config('app.key');
            if (empty($appKey) || $appKey === 'base64:') {
                Log::critical('APP_KEY is not set or empty. Application encryption and session security are compromised.');
            }
        }

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('phone') . '|' . $request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('orders', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('glass_finder_favorite', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('reviews', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('imports', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        View::composer('layouts.admin.app', function ($view) {
            $isPlatformScope = request()->is('admin/*') && ! request()->is('store/*');
            $store = $isPlatformScope
                ? ($view->getData()['store'] ?? null)
                : ($view->getData()['store'] ?? app(\App\Services\StoreContext::class)->getStore());

            $user = auth()->user();
            $role = ($user && $store) ? $user->getStoreRole($store->id) : null;
            $navService = app(\App\Services\AdminNavigationService::class);

            $navigationTree = $navService->getFilteredNavigationTree($user, $store, request());

            // Extract pending order count only if ecommerce/orders is permitted and present
            $pendingOrderCount = 0;
            foreach ($navigationTree as $item) {
                if (($item['key'] ?? '') === 'ecommerce') {
                    $pendingOrderCount = (int) ($item['badge'] ?? 0);
                    break;
                }
            }

            $view->with([
                'isPlatformScope' => $isPlatformScope,
                'navigationTree' => $navigationTree,
                'adminPendingOrderCount' => $pendingOrderCount,
                'adminCanManageSettings' => $navService->canManageSettings($user, $store),
                'adminCanAccessStaffTools' => $navService->canAccessStaffTools($user, $store),
                'adminCanManageUsers' => $navService->canManageUsers($user, $store),
                'adminCanManageFinance' => $navService->canManageFinance($user, $store),
                'adminCurrentStoreRole' => $role,
                'adminNavService' => $navService,
            ]);
        });

        // Register custom Blade directives for system-wide currency formatting
        \Illuminate\Support\Facades\Blade::directive('currency', function ($expression) {
            return "<?php echo format_currency({$expression}); ?>";
        });
        \Illuminate\Support\Facades\Blade::directive('money', function ($expression) {
            return "<?php echo format_currency({$expression}); ?>";
        });
    }
}
