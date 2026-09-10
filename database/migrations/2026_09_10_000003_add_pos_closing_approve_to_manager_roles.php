<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roles = DB::table('staff_roles')
            ->where('slug', 'store_manager')
            ->where('is_system', true)
            ->get(['id', 'permissions']);

        foreach ($roles as $role) {
            $permissions = [];
            if (!empty($role->permissions)) {
                $decoded = is_string($role->permissions)
                    ? json_decode($role->permissions, true)
                    : (array) $role->permissions;
                if (is_array($decoded)) {
                    $permissions = $decoded;
                }
            }

            if (!in_array('pos_closing.approve', $permissions, true)) {
                $permissions[] = 'pos_closing.approve';
                DB::table('staff_roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode(array_values($permissions))]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $roles = DB::table('staff_roles')
            ->where('slug', 'store_manager')
            ->where('is_system', true)
            ->get(['id', 'permissions']);

        foreach ($roles as $role) {
            if (empty($role->permissions)) {
                continue;
            }

            $permissions = is_string($role->permissions)
                ? json_decode($role->permissions, true)
                : (array) $role->permissions;

            if (is_array($permissions) && in_array('pos_closing.approve', $permissions, true)) {
                $filtered = array_values(array_filter(
                    $permissions,
                    fn ($p) => $p !== 'pos_closing.approve'
                ));

                DB::table('staff_roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($filtered)]);
            }
        }
    }
};
