<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Services\StoreContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreChannel
{
    public function __construct(
        protected StoreContext $context
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $channel): Response
    {
        $store = $this->context->getStore();

        if (!$store) {
            $slug = $request->route('store_slug')
                ?? $request->input('store_slug')
                ?? $request->query('store_slug')
                ?? $request->header('X-Store-Slug');

            if ($slug) {
                $store = Store::where('slug', $slug)->first();
            }
        }

        if (!$store) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Store not found.'], 404);
            }

            abort(404, 'Store not found.');
        }

        if (!$store->hasChannel($channel)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This sales channel is not active for this store.',
                    'channel' => $channel,
                ], 403);
            }

            abort(403, 'This sales channel is not active for this store.');
        }

        return $next($request);
    }
}
