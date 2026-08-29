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

        if (! $store) {
            $slug = $request->route('store_slug')
                ?? $request->input('store_slug')
                ?? $request->query('store_slug')
                ?? $request->header('X-Store-Slug');

            if ($slug) {
                $store = Store::where('slug', $slug)->first();
            }
        }

        if (! $store && $request->filled('glass_finder_item_id')) {
            $item = \App\Models\GlassFinderItem::find($request->input('glass_finder_item_id'));
            if ($item?->store) {
                $store = $item->store;
            }
        }

        if (! $store) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Store not found.'], 404);
            }

            abort(404, 'Store not found.');
        }

        if (! $store->hasCapability($capability)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message'    => 'This feature is not enabled for your store profile.',
                    'capability' => $capability,
                ], 403);
            }

            abort(403, 'This feature is not enabled for your store profile.');
        }

        return $next($request);
    }
}
