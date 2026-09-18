<?php

namespace Tests\Feature\Admin;

use App\Models\StaffRole;
use App\Models\Store;
use App\Models\User;
use App\Services\AdminNavigationService;
use App\Services\StorePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Every menu item a role is shown must be a page that role can actually open.
 *
 * This is the defect class the live sweeps kept finding: the sidebar renders an
 * item (its own gate says yes) while the route behind it demands a role or a
 * permission the user does not have — the staff member clicks and gets a 403.
 * The check here is the strong one: walk the SAME filtered tree the sidebar
 * renders, read each item's route middleware, and assert the user satisfies it.
 */
class AdminSidebarReachabilityTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Reachability Shop',
            'slug' => 'reach-shop',
            'is_active' => true,
            'profile' => 'mobile',
        ]);

        // The roles every real store gets, with their permission templates.
        StaffRole::bootstrapDefaultRoles($this->store);
    }

    private function userWithRole(string $role): User
    {
        $staffRoleId = StaffRole::where('store_id', $this->store->id)->where('slug', $role)->value('id');

        $user = User::create([
            'name' => ucfirst($role) . ' User',
            'phone' => '0975' . rand(1000000, 9999999),
            'password' => bcrypt('password'),
            'password_set_at' => now(),
            'role' => 'customer',
        ]);
        $user->stores()->attach($this->store->id, [
            'role' => $role,
            'status' => 'active',
            'staff_role_id' => $staffRoleId,
        ]);

        return $user->fresh();
    }

    /** @return array<int, array{key:string, route:?string}> */
    private function leaves(array $nodes): array
    {
        $out = [];
        foreach ($nodes as $node) {
            if (! empty($node['children'])) {
                $out = array_merge($out, $this->leaves($node['children']));

                continue;
            }

            $out[] = [
                'key' => (string) ($node['key'] ?? '?'),
                'route' => $node['route_name'] ?? null,
                'url' => $node['route_params'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * Middleware a route demands → does this user satisfy it?
     *
     * Mirrors what the live request would do: `EnsureStoreAccess:<roles>` and
     * `store.permission:<key[,key…]>`.
     */
    private function routeDenial(RoutingRoute $route, User $user): ?string
    {
        $permissions = app(StorePermissionService::class);

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            if (str_starts_with($middleware, 'EnsureStoreAccess:')) {
                $roles = explode(',', substr($middleware, strlen('EnsureStoreAccess:')));
                // A platform owner bypasses; otherwise the pivot role must match
                // (with the documented hierarchy owner > manager > staff).
                if (! $user->isPlatformOwner() && ! $user->hasStoreRole($this->store->id, $roles)) {
                    return "role: {$middleware}";
                }
            }

            if (str_starts_with($middleware, 'store.permission:')) {
                // Same rule as CheckStorePermission: "a|b" is OR, "a,b" is AND.
                $spec = substr($middleware, strlen('store.permission:'));
                $allowed = str_contains($spec, '|')
                    ? $permissions->canAny($user, $this->store, array_map('trim', explode('|', $spec)))
                    : (str_contains($spec, ',')
                        ? $permissions->canAll($user, $this->store, array_map('trim', explode(',', $spec)))
                        : $permissions->can($user, $this->store, trim($spec)));

                if (! $allowed) {
                    return "permission: {$spec}";
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, string> human-readable problems
     */
    private function problemsFor(User $user): array
    {
        $routes = Route::getRoutes();
        $problems = [];

        foreach ($this->leaves(app(AdminNavigationService::class)->getFilteredNavigationTree($user, $this->store)) as $leaf) {
            if ($leaf['route'] === null) {
                continue;
            }

            $route = $routes->getByName($leaf['route']);
            if ($route === null) {
                $problems[] = "{$leaf['key']}: route '{$leaf['route']}' does not exist";

                continue;
            }

            if ($denial = $this->routeDenial($route, $user)) {
                $problems[] = "{$leaf['key']} → {$leaf['route']} denied by {$denial}";
            }
        }

        return $problems;
    }

    public function test_the_owner_sees_only_reachable_pages(): void
    {
        $this->assertSame([], $this->problemsFor($this->userWithRole('store_owner')));
    }

    public function test_the_manager_sees_only_reachable_pages(): void
    {
        $this->assertSame([], $this->problemsFor($this->userWithRole('store_manager')));
    }

    public function test_the_cashier_sees_only_reachable_pages(): void
    {
        $this->assertSame([], $this->problemsFor($this->userWithRole('staff')));
    }

    public function test_the_accountant_sees_only_reachable_pages(): void
    {
        $this->assertSame([], $this->problemsFor($this->userWithRole('accountant')));
    }

    public function test_owner_only_screens_are_not_offered_to_a_manager(): void
    {
        $manager = $this->userWithRole('store_manager');
        $keys = collect($this->leaves(app(AdminNavigationService::class)->getFilteredNavigationTree($manager, $this->store)))
            ->pluck('key')
            ->all();

        // Staff accounts and platform maintenance belong to the owner.
        $this->assertNotContains('users', $keys);

        $ownerKeys = collect($this->leaves(app(AdminNavigationService::class)->getFilteredNavigationTree($this->userWithRole('store_owner'), $this->store)))
            ->pluck('key')
            ->all();
        $this->assertContains('users', $ownerKeys);
    }

    /**
     * The checker itself must be able to say "no": the users screen is
     * owner-only, so for a manager the route-level verdict has to be a denial
     * (otherwise the four tests above would pass by accident).
     */
    public function test_the_checker_reports_a_route_a_role_cannot_open(): void
    {
        $manager = $this->userWithRole('store_manager');
        $route = Route::getRoutes()->getByName('store.admin.users.index');

        $this->assertNotNull($route);
        $this->assertNotNull($this->routeDenial($route, $manager));
    }
}
