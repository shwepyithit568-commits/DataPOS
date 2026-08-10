<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformOwner();
    }

    public function view(User $user, Store $store): bool
    {
        if ($user->isPlatformOwner()) {
            return true;
        }

        return $user->hasStoreRole($store->id, ['store_manager', 'staff', 'wholesale_customer', 'retail_customer']);
    }

    public function manage(User $user, Store $store): bool
    {
        if ($user->isPlatformOwner()) {
            return true;
        }

        return $user->hasStoreRole($store->id, ['store_manager']);
    }
}
