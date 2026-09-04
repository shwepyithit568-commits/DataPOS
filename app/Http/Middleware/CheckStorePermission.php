<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Services\StoreContext;
use App\Services\StorePermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStorePermission
{
    public function __construct(
        protected StoreContext $context,
        protected StorePermissionService $permissionService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('messages.unauthenticated')], 401);
            }

            return redirect()->guest(route('login'));
        }

        // Platform Owner bypasses store-level permission checks
        if ($user->isPlatformOwner()) {
            return $next($request);
        }

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

        // Support multiple permissions via pipe (OR logic) or comma (AND logic)
        $hasAccess = false;
        if (str_contains($permission, '|')) {
            $perms = explode('|', $permission);
            $hasAccess = $this->permissionService->canAny($user, $store, $perms);
        } elseif (str_contains($permission, ',')) {
            $perms = explode(',', $permission);
            $hasAccess = $this->permissionService->canAll($user, $store, $perms);
        } else {
            $hasAccess = $this->permissionService->can($user, $store, $permission);
        }

        if (!$hasAccess) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('messages.permission_denied'),
                    'permission' => $permission,
                ], 403);
            }

            abort(403, __('messages.permission_denied'));
        }

        return $next($request);
    }
}
