<?php

namespace App\Http\Middleware;

use App\Services\StoreContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreAccess
{
    public function __construct(protected StoreContext $storeContext)
    {
    }

    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->isPlatformOwner()) {
            return $next($request);
        }

        $store = $this->storeContext->getStore();

        if (!$store) {
            abort(400, 'Store context is missing.');
        }

        $requiredRoles = empty($roles) ? ['store_manager', 'staff'] : $roles;

        if (!$user->hasStoreRole($store->id, $requiredRoles)) {
            abort(403, 'Unauthorized access to this store.');
        }

        return $next($request);
    }
}
