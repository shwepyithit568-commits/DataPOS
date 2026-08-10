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
            $store = $view->getData()['store'] ?? app(\App\Services\StoreContext::class)->getStore();
            $user = auth()->user();
            $role = ($user && $store) ? $user->getStoreRole($store->id) : null;

            $view->with([
                'adminPendingOrderCount' => $store
                    ? \App\Models\Order::where('store_id', $store->id)
                        ->where('status', 'pending_contact')
                        ->count()
                    : 0,
                'adminCanManageSettings' => $user && $store && $user->hasStoreRole($store->id, 'store_manager'),
                'adminCanAccessStaffTools' => $user && $store && $user->hasStoreRole($store->id, ['store_manager', 'staff']),
                'adminCanManageUsers' => $user && $user->isPlatformOwner(),
                'adminCurrentStoreRole' => $role,
            ]);
        });
    }
}
