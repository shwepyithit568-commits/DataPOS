<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminNavigationService
{
    public function __construct(
        protected StoreContext $storeContext
    ) {
    }

    /**
     * Determine if current request is strictly in Platform Scope (/admin/* but not /store/*).
     */
    public function isPlatformScope(?Request $request = null): bool
    {
        $req = $request ?? request();
        return $req->is('admin/*') && ! $req->is('store/*');
    }

    /**
     * Determine if current request is in Store Scope (/store/*).
     */
    public function isStoreScope(?Request $request = null): bool
    {
        $req = $request ?? request();
        return filled($req->route('store_slug')) || $req->is('store/*');
    }

    /**
     * Check if the authenticated user has permission for a given key in the store.
     */
    public function userHasPermission(?User $user, ?Store $store, string|array $permissions): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isPlatformOwner()) {
            return true;
        }

        if (! $store) {
            return false;
        }

        // Store Owner has full access
        if ($user->isStoreOwner($store->id)) {
            return true;
        }

        $storeRole = $user->getStoreRole($store->id);
        if (! $storeRole) {
            return false;
        }

        $permList = (array) $permissions;
        if (empty($permList)) {
            return true;
        }

        // Store Manager has default access to operational and managerial modules
        if ($storeRole === 'store_manager') {
            return true;
        }

        // If staff, check their StaffRole
        $membership = $user->getStoreMembership($store->id);
        if ($membership && ! empty($membership->staff_role_id)) {
            $staffRole = StaffRole::find($membership->staff_role_id);
            if ($staffRole && $staffRole->is_active) {
                foreach ($permList as $perm) {
                    if ($staffRole->hasPermission($perm)) {
                        return true;
                    }
                }
            }
        }

        // Check custom_permissions on pivot if present
        if ($membership && ! empty($membership->custom_permissions)) {
            $custom = is_array($membership->custom_permissions)
                ? $membership->custom_permissions
                : json_decode($membership->custom_permissions, true);
            if (is_array($custom)) {
                if (in_array('*', $custom, true)) {
                    return true;
                }
                foreach ($permList as $perm) {
                    if (in_array($perm, $custom, true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get pending order count safely scoped to the active store.
     */
    public function getPendingOrderCount(?Store $store): int
    {
        if (! $store) {
            return 0;
        }

        return Order::where('store_id', $store->id)
            ->where('status', 'pending_contact')
            ->count();
    }
}
