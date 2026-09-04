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
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('login'));
        }

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
                    'message' => 'You do not have permission to perform this action.',
                    'permission' => $permission,
                ], 403);
            }

            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
