<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Services\StoreContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreCapability
{
    public function __construct(
        protected StoreContext $context
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $store = $this->context->getStore();
        $routeSlug = $request->route('store_slug');

        // Fail closed if context store does not match route-bound slug (anti-tampering)
        if ($store && $routeSlug && $store->slug !== $routeSlug) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('messages.unauthorized')], 403);
            }
            abort(403, __('messages.unauthorized'));
        }

        if (!$store) {
            if ($routeSlug) {
                $store = Store::where('slug', $routeSlug)->first();
            }
        }

        if (!$store) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('messages.store_not_found')], 404);
            }

            abort(404, __('messages.store_not_found'));
        }

        if (!$store->hasCapability($capability)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message'    => __('messages.feature_not_enabled'),
                    'capability' => $capability,
                ], 403);
            }

            abort(403, __('messages.feature_not_enabled'));
        }

        return $next($request);
    }
}
