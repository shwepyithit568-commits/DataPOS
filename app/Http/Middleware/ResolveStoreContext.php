<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Services\StoreContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveStoreContext
{
    public function __construct(protected StoreContext $storeContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('store_slug') ?? $request->header('X-Store-Slug') ?? $request->get('store_slug');

        if ($slug) {
            $store = Store::where('slug', $slug)->where('is_active', true)->first();

            if (!$store) {
                abort(404, 'Store not found or inactive.');
            }

            $this->storeContext->setStore($store);
        } else {
            $store = $this->resolveFallbackStore($request);

            if ($store) {
                $this->storeContext->setStore($store);
            }
        }

        return $next($request);
    }

    private function resolveFallbackStore(Request $request): ?Store
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('stores')) {
            return null;
        }

        $user = $request->user();

        if ($user) {
            $store = $user->activeStores()
                ->where('is_active', true)
                ->first();

            if ($store) {
                return $store;
            }
        }

        // Primary active store wins (multi-store-ready plan Phase 1) — this
        // keeps the root site working even when several stores are active.
        // If more than one store is somehow flagged primary (data drift), pick
        // the lowest id deterministically instead of returning null.
        $primary = Store::where('is_active', true)
            ->where('is_primary', true)
            ->orderBy('id')
            ->first();

        if ($primary) {
            return $primary;
        }

        // Legacy fallback: a single active store only.
        $activeStores = Store::where('is_active', true)
            ->orderBy('id')
            ->limit(2)
            ->get();

        return $activeStores->count() === 1 ? $activeStores->first() : null;
    }
}
