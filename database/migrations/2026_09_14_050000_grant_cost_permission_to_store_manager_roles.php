<?php

use App\Models\StaffRole;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Purchase cost / wholesale price / reorder level became separately
     * grantable (`products.view_cost`). Store managers could already see and
     * edit those values, so they keep the permission; every other role must be
     * granted it deliberately from the Roles screen.
     */
    private const PERMISSION = 'products.view_cost';

    public function up(): void
    {
        StaffRole::query()
            ->where('slug', 'store_manager')
            ->get()
            ->each(function (StaffRole $role) {
                $permissions = $role->permissions ?? [];
                if (! in_array(self::PERMISSION, $permissions, true)) {
                    $permissions[] = self::PERMISSION;
                    $role->update(['permissions' => array_values($permissions)]);
                }
            });
    }

    public function down(): void
    {
        StaffRole::query()
            ->where('slug', 'store_manager')
            ->get()
            ->each(function (StaffRole $role) {
                $role->update([
                    'permissions' => array_values(array_diff($role->permissions ?? [], [self::PERMISSION])),
                ]);
            });
    }
};
