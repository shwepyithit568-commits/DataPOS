<?php

namespace App\Http\Middleware;

use App\Services\StoreContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Finance-sensitive admin pages (P&L, receivables, payables, expenses,
 * cash/bank transactions) are reserved for the store owner / store manager.
 *
 * A plain `staff` member (e.g. a cashier) works the POS but must not see or
 * post finance records. This is a SERVER-SIDE gate — hiding links in the UI
 * alone is not enough (audit §5.3 / §15.6).
 */
class EnsureFinanceAccess
{
    public function __construct(protected StoreContext $storeContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // Platform owner is trusted to inspect any store.
        if ($user->isPlatformOwner()) {
            return $next($request);
        }

        $store = $this->storeContext->getStore();

        if (! $store) {
            abort(400, 'Store context is missing.');
        }

        if (! $user->hasStoreRole($store->id, ['store_owner', 'store_manager'])) {
            abort(403, 'Finance access requires manager or owner role.');
        }

        return $next($request);
    }
}