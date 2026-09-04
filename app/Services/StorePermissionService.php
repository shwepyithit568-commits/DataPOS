<?php

namespace App\Services;

use App\Capabilities\Capability;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StorePermissionService
{
    /**
     * Protected permissions that regular managers cannot grant or alter.
     */
    protected const PROTECTED_PERMISSIONS = [
        'staff_roles.manage',
        'staff.manage',
        'settings.manage',
        'channels.manage',
        'modules.manage',
        'store.delete',
        'store.transfer',
    ];

    /**
     * In-memory request cache: "storeId:userId" => array of effective permissions.
     *
     * @var array<string, array<string>>
     */
    protected static array $requestCache = [];

    /**
     * Check if a user has a specific permission in the store.
     *
     * Note: Store capabilities and sales channel availability are orthogonal
     * concerns verified by store.capability and store.channel middleware and
     * route/navigation metadata, NOT guessed from permission string prefixes.
     */
    public function can(User $user, Store $store, string $permission): bool
    {
        // 1. Platform Owner has universal authority across all stores
        if ($user->isPlatformOwner()) {
            return true;
        }

        // 2. Store Owner has full authority in their own store
        if ($user->isStoreOwner($store->id)) {
            return true;
        }

        // 3. Regular staff and managers evaluate effective permissions
        $effective = $this->effectivePermissions($user, $store);

        if (empty($effective)) {
            return false;
        }

        // Exact match
        if (in_array($permission, $effective, true)) {
            return true;
        }

        // Aliasing: orders.* <-> ecommerce_orders.*
        if (str_starts_with($permission, 'orders.')) {
            $ecomPerm = 'ecommerce_orders.' . substr($permission, 7);
            if (in_array($ecomPerm, $effective, true)) {
                return true;
            }
        } elseif (str_starts_with($permission, 'ecommerce_orders.')) {
            $orderPerm = 'orders.' . substr($permission, 17);
            if (in_array($orderPerm, $effective, true)) {
                return true;
            }
        }

        // Handle .edit <-> .update aliasing (Plan §6.1: .edit aliases .update only; it must not grant create)
        if (str_ends_with($permission, '.update')) {
            $editAlias = substr($permission, 0, -7) . '.edit';
            if (in_array($editAlias, $effective, true)) {
                return true;
            }
        } elseif (str_ends_with($permission, '.edit')) {
            $updateAlias = substr($permission, 0, -5) . '.update';
            if (in_array($updateAlias, $effective, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has ANY of the given permissions.
     */
    public function canAny(User $user, Store $store, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($user, $store, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has ALL of the given permissions.
     */
    public function canAll(User $user, Store $store, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->can($user, $store, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve effective permissions for a user in a store.
     *
     * Effective permissions:
     * (active StaffRole permissions ∪ individual grants) − individual denies.
     * Denies win.
     * Inactive membership or inactive role yields empty permissions.
     *
     * @return array<string>
     */
    public function effectivePermissions(User $user, Store $store): array
    {
        $cacheKey = "{$store->id}:{$user->id}";
        if (isset(self::$requestCache[$cacheKey])) {
            return self::$requestCache[$cacheKey];
        }

        // Platform Owner has universal authority without needing a store membership pivot
        if ($user->isPlatformOwner()) {
            return self::$requestCache[$cacheKey] = $this->getAllCanonicalPermissions();
        }

        $membership = $user->getStoreMembership($store->id);
        if (!$membership || $membership->status !== 'active') {
            return self::$requestCache[$cacheKey] = [];
        }

        // Store Owner gets all system role permissions
        if ($membership->role === 'store_owner') {
            return self::$requestCache[$cacheKey] = $this->getAllCanonicalPermissions();
        }

        $rolePermissions = [];

        // Fetch StaffRole if assigned
        if (!empty($membership->staff_role_id)) {
            $role = StaffRole::find($membership->staff_role_id);
            if ($role && $role->is_active && is_array($role->permissions)) {
                $rolePermissions = $role->permissions;
            }
        } elseif (empty($membership->custom_permissions)) {
            // Backward-compatible fallback for legacy memberships where neither staff_role_id nor custom_permissions is populated:
            if ($membership->role === 'store_manager') {
                // Store Manager gets all operational canonical permissions except protected permissions
                $rolePermissions = array_values(array_diff($this->getAllCanonicalPermissions(), self::PROTECTED_PERMISSIONS));
            } elseif ($membership->role === 'staff') {
                // Default staff gets non-delete, non-protected operational permissions
                $allPerms = $this->getAllCanonicalPermissions();
                $rolePermissions = array_values(array_filter($allPerms, function ($perm) {
                    if (in_array($perm, self::PROTECTED_PERMISSIONS, true)) {
                        return false;
                    }
                    if (str_ends_with($perm, '.delete')) {
                        return false;
                    }
                    if (str_starts_with($perm, 'settings.') || str_starts_with($perm, 'backups.') || str_starts_with($perm, 'database.') || str_starts_with($perm, 'roles.') || str_starts_with($perm, 'staff.')) {
                        return false;
                    }
                    return true;
                }));
            }
        }

        // Parse custom_permissions on store_user pivot
        [$grants, $denies] = $this->parseCustomPermissions($membership->custom_permissions);

        // Quarantine wildcard: '*' does not expand to protected permissions and logs a warning
        if (in_array('*', $grants, true)) {
            Log::warning("Legacy wildcard '*' encountered for user {$user->id} in store {$store->id}. Wildcard quarantined.");
            $grants = array_diff($this->getAllCanonicalPermissions(), self::PROTECTED_PERMISSIONS);
        }

        // (role permissions ∪ grants)
        $combined = array_unique(array_merge($rolePermissions, $grants));

        // − denies (Denies win)
        $effective = array_values(array_diff($combined, $denies));

        return self::$requestCache[$cacheKey] = $effective;
    }

    /**
     * Check if an actor can manage staff permissions for a target user in a store.
     *
     * Rules (Plan §6.1):
     * - Actor must have active membership in store (or be Platform Owner)
     * - Target must belong to the same store
     * - Target cannot be a Platform Owner
     * - Manager cannot modify a Store Owner or Platform Owner
     * - Manager cannot modify themself
     * - Manager cannot grant permissions they do not hold (privilege ceiling)
     * - Manager cannot grant protected permissions
     * - Last active Store Owner cannot be deleted, suspended, or demoted
     */
    public function canManageStaffPermissions(User $actor, Store $store, User $target): bool
    {
        // Cross-store boundary: target must belong to this store
        $targetMembership = $target->getStoreMembership($store->id);
        if (!$targetMembership && !$target->isPlatformOwner()) {
            return false;
        }

        // Platform Owner target protection
        if ($target->isPlatformOwner() && !$actor->isPlatformOwner()) {
            return false;
        }

        // Platform Owner actor can manage anyone in store scope, except last owner invariants
        if ($actor->isPlatformOwner()) {
            return true;
        }

        // Actor must have active membership in this store
        $actorRole = $actor->getStoreRole($store->id);
        if (!$actorRole) {
            return false;
        }

        // Store Owner can manage any staff in their store (except Platform Owner)
        if ($actorRole === 'store_owner') {
            // Cannot modify Platform Owner
            if ($target->isPlatformOwner()) {
                return false;
            }
            return true;
        }

        // Regular Manager / Staff checks
        // Must have staff.manage or roles.manage explicit permission
        if (!$this->canAny($actor, $store, ['roles.edit', 'staff.edit', 'staff_roles.edit'])) {
            return false;
        }

        // Manager cannot modify themself
        if ($actor->id === $target->id) {
            return false;
        }

        // Manager cannot modify a Store Owner or Platform Owner
        $targetRole = $target->getStoreRole($store->id);
        if ($targetRole === 'store_owner' || $target->isPlatformOwner()) {
            return false;
        }

        return true;
    }

    /**
     * Check if a proposed set of permissions violates the manager's privilege ceiling.
     * A manager cannot grant permissions they do not hold, nor grant protected permissions.
     *
     * @param array<string> $targetPermissions
     */
    public function canAssignPermissions(User $actor, Store $store, array $targetPermissions): bool
    {
        if ($actor->isPlatformOwner() || $actor->isStoreOwner($store->id)) {
            return true;
        }

        $actorEffective = $this->effectivePermissions($actor, $store);

        foreach ($targetPermissions as $perm) {
            // Cannot grant protected permissions
            if (in_array($perm, self::PROTECTED_PERMISSIONS, true)) {
                return false;
            }

            // Cannot grant permissions actor does not hold
            if (!in_array($perm, $actorEffective, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if target user is the last active store owner of the store.
     */
    public function isLastStoreOwner(Store $store, User $target): bool
    {
        if ($target->getStoreRole($store->id) !== 'store_owner') {
            return false;
        }

        $activeOwnerCount = $store->users()
            ->wherePivot('role', 'store_owner')
            ->wherePivot('status', 'active')
            ->count();

        return $activeOwnerCount <= 1;
    }

    /**
     * Invalidate cached permissions for a user or store.
     */
    public static function invalidateCache(?int $storeId = null, ?int $userId = null): void
    {
        if ($storeId === null && $userId === null) {
            self::$requestCache = [];
            return;
        }

        foreach (array_keys(self::$requestCache) as $key) {
            [$sId, $uId] = explode(':', $key);
            if (($storeId === null || (int)$sId === $storeId) && ($userId === null || (int)$uId === $userId)) {
                unset(self::$requestCache[$key]);
            }
        }
    }

    /**
     * Parse custom_permissions JSON/array into grants and denies.
     *
     * @return array{0: array<string>, 1: array<string>} [grants, denies]
     */
    protected function parseCustomPermissions(mixed $custom): array
    {
        if (empty($custom)) {
            return [[], []];
        }

        if (is_string($custom)) {
            $decoded = json_decode($custom, true);
            $custom = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($custom)) {
            return [[], []];
        }

        $grants = [];
        $denies = [];

        // Format 1: ['grants' => [...], 'denies' => [...]]
        if (isset($custom['grants']) || isset($custom['denies'])) {
            $grants = array_values(array_filter((array)($custom['grants'] ?? []), 'is_string'));
            $denies = array_values(array_filter((array)($custom['denies'] ?? []), 'is_string'));
            return [$grants, $denies];
        }

        // Format 2: Map of 'permission' => bool
        $isMap = !empty($custom) && array_keys($custom) !== range(0, count($custom) - 1);
        if ($isMap) {
            foreach ($custom as $perm => $allowed) {
                if ($allowed) {
                    $grants[] = (string) $perm;
                } else {
                    $denies[] = (string) $perm;
                }
            }
            return [$grants, $denies];
        }

        // Format 3: List of string permissions, with '-' or '!' denoting denies
        foreach ($custom as $perm) {
            if (!is_string($perm)) {
                continue;
            }
            $perm = trim($perm);
            if (str_starts_with($perm, '!') || str_starts_with($perm, '-')) {
                $denies[] = substr($perm, 1);
            } else {
                $grants[] = $perm;
            }
        }

        return [$grants, $denies];
    }

    /**
     * Get all registered canonical permissions across StaffRole::PERMISSION_GROUPS.
     *
     * @return array<string>
     */
    protected function getAllCanonicalPermissions(): array
    {
        $permissions = [];
        foreach (StaffRole::PERMISSION_GROUPS as $group) {
            foreach ($group['modules'] ?? [] as $module) {
                foreach ($module['permissions'] ?? [] as $perm) {
                    $permissions[] = $perm;
                    if (str_starts_with($perm, 'ecommerce_orders.')) {
                        $permissions[] = 'orders.' . substr($perm, 17);
                    }
                }
            }
        }

        return array_values(array_unique($permissions));
    }
}
