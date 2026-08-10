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

        $activeStores = Store::where('is_active', true)
            ->limit(2)
            ->get();

        return $activeStores->count() === 1 ? $activeStores->first() : null;
    }
}
