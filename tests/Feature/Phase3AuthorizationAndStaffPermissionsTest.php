<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\User;
use App\Services\StorePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class Phase3AuthorizationAndStaffPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $owner;
    protected User $manager;
    protected User $staff;
    protected StaffRole $managerRole;
    protected StaffRole $staffRole;

    protected function setUp(): void
    {
        parent::setUp();
        StorePermissionService::invalidateCache();

        $this->store = Store::create([
            'name' => 'Phase 3 Test Store',
            'slug' => 'phase3-test-store',
        ]);

        $this->owner = User::create([
            'name' => 'Owner Ko',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
        ]);
        $this->owner->stores()->attach($this->store->id, ['role' => 'store_owner', 'status' => 'active']);

        $this->managerRole = StaffRole::create([
            'store_id' => $this->store->id,
            'name' => 'Shop Manager Role',
            'slug' => 'shop-manager',
            'permissions' => ['staff.edit', 'roles.edit', 'products.view', 'products.edit', 'pos_sales.view'],
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Manager Mg',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
        ]);
        $this->manager->stores()->attach($this->store->id, [
            'role' => 'store_manager',
            'status' => 'active',
            'staff_role_id' => $this->managerRole->id,
        ]);

        $this->staffRole = StaffRole::create([
            'store_id' => $this->store->id,
            'name' => 'Sales Staff Role',
            'slug' => 'sales-staff',
            'permissions' => ['pos_sales.view'],
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->staff = User::create([
            'name' => 'Staff Hla',
            'phone' => '09333333333',
            'password' => bcrypt('password'),
        ]);
        $this->staff->stores()->attach($this->store->id, [
            'role' => 'staff',
            'status' => 'active',
            'staff_role_id' => $this->staffRole->id,
        ]);
    }

    public function test_staff_permission_migration_command(): void
    {
        // 1. Dry run
        Artisan::call('staff:migrate-permissions', ['--dry-run' => true]);
        $freshManagerRole = $this->managerRole->fresh();
        $this->assertNotContains('products.create', $freshManagerRole->permissions ?? []);

        // 2. Apply migration
        Artisan::call('staff:migrate-permissions');
        $freshManagerRole = $this->managerRole->fresh();
        $this->assertContains('products.create', $freshManagerRole->permissions);
        $this->assertContains('products.update', $freshManagerRole->permissions);

        $auditLog = AuditLog::where('store_id', $this->store->id)
            ->where('action', 'staff_permissions.migrate')
            ->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('staff_permissions_migration_2026_09', $auditLog->metadata['migration_marker']);

        // 3. Rollback
        Artisan::call('staff:migrate-permissions', ['--rollback' => true]);
        $rolledBackRole = $this->managerRole->fresh();
        $this->assertNotContains('products.create', $rolledBackRole->permissions ?? []);
    }

    public function test_wildcard_submission_rejected_with_422(): void
    {
        $response = $this->actingAs($this->owner)
            ->post("/store/{$this->store->slug}/admin/security/roles", [
                'name' => 'Hacker Role',
                'permissions' => ['*'],
            ]);

        $response->assertStatus(422);
    }

    public function test_manager_cannot_modify_store_owner(): void
    {
        // 1. Manager attempting to suspend store owner -> 403 (blocked by route middleware or controller)
        $responseSuspend = $this->actingAs($this->manager)
            ->patch("/store/{$this->store->slug}/admin/users/{$this->owner->id}/suspend");
        $responseSuspend->assertStatus(403);

        // 2. Manager attempting to delete store owner -> 403
        $responseDelete = $this->actingAs($this->manager)
            ->delete("/store/{$this->store->slug}/admin/users/{$this->owner->id}");
        $responseDelete->assertStatus(403);

        // 3. Manager attempting to update store owner -> 403
        $responseUpdate = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/users/{$this->owner->id}", [
                'name' => 'Hacked Name',
                'phone' => $this->owner->phone,
                'role' => 'store_owner',
            ]);
        $responseUpdate->assertStatus(403);
    }

    public function test_user_cannot_delete_themself(): void
    {
        $response = $this->actingAs($this->owner)
            ->delete("/store/{$this->store->slug}/admin/users/{$this->owner->id}");
        $response->assertSessionHasErrors(['user']);
    }

    public function test_last_active_store_owner_cannot_be_deleted_demoted_or_suspended(): void
    {
        // Platform owner or another user trying to demote the sole store owner
        $platformOwner = User::create([
            'name' => 'Platform Owner',
            'phone' => '09999999999',
            'password' => bcrypt('password'),
            'role' => 'platform_owner',
        ]);

        // 1. Demoting last store owner fails
        $responseDemote = $this->actingAs($platformOwner)
            ->put("/store/{$this->store->slug}/admin/users/{$this->owner->id}", [
                'name' => $this->owner->name,
                'phone' => $this->owner->phone,
                'role' => 'staff',
                'status' => 'active',
            ]);
        $responseDemote->assertSessionHasErrors(['role']);

        // 2. Deleting last store owner fails
        $responseDelete = $this->actingAs($platformOwner)
            ->delete("/store/{$this->store->slug}/admin/users/{$this->owner->id}");
        $responseDelete->assertSessionHasErrors(['user']);

        // 3. Suspending last store owner fails
        $responseSuspend = $this->actingAs($platformOwner)
            ->post("/store/{$this->store->slug}/admin/users/{$this->owner->id}/suspend");
        $responseSuspend->assertSessionHasErrors(['user']);
    }

    public function test_manager_privilege_ceiling_enforcement(): void
    {
        // Manager role only has ['staff.edit', 'roles.edit', 'products.view', 'products.edit', 'pos_sales.view']
        // Attempting to assign 'stock_ledger.delete' exceeds ceiling -> 422
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/security/roles/assign-staff", [
                'action_mode' => 'create_and_assign',
                'user_id' => $this->staff->id,
                'role_name' => 'Elevated Role',
                'role_permissions' => ['stock_ledger.delete'],
            ]);

        $response->assertStatus(422);

        // Attempting to grant protected permission 'settings.manage' -> 422
        $responseProtected = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/security/roles/assign-staff", [
                'action_mode' => 'create_and_assign',
                'user_id' => $this->staff->id,
                'role_name' => 'Protected Role',
                'role_permissions' => ['settings.manage'],
            ]);

        $responseProtected->assertStatus(422);
    }
}
