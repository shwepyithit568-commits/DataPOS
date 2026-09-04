<?php

namespace Tests\Feature\Admin;

use App\Models\StaffRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffRoleTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;
    protected Store $otherStore;
    protected User $otherManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Roles Store 1', 'slug' => 'roles-store-1']);
        $this->store->setting()->create(['store_name' => 'Roles Store 1', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['name' => 'Manager U Ba', 'phone' => '09111222333']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_owner', 'status' => 'active']);

        $this->staff = User::factory()->create(['name' => 'Cashier Ko Kyaw', 'phone' => '09444555666']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $this->otherStore = Store::create(['name' => 'Roles Store 2', 'slug' => 'roles-store-2']);
        $this->otherStore->setting()->create(['store_name' => 'Roles Store 2', 'default_language' => 'en']);

        $this->otherManager = User::factory()->create(['name' => 'Other Manager', 'phone' => '09888777666']);
        $this->otherManager->stores()->attach($this->otherStore->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_manager_can_access_roles_dashboard_and_bootstraps_defaults(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/security/roles");

        $response->assertOk();
        $response->assertSee(__('messages.roles_title'));
        $response->assertSee('Store Manager');
        $response->assertSee('Cashier / Sales Staff');
        $response->assertSee('Accountant');
        $response->assertSee('Technician');
        $response->assertSee('Stock Keeper');

        $this->assertDatabaseHas('staff_roles', [
            'store_id' => $this->store->id,
            'slug'     => 'store_manager',
        ]);
    }

    public function test_manager_can_create_custom_staff_role(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/security/roles", [
                'name'        => 'VIP Support Lead',
                'description' => 'Handles VIP customer warranty claims and special discounts.',
                'color'       => '#9333ea',
                'permissions' => ['pos.sell', 'pos.discount_override', 'services.create', 'reports.sales'],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('staff_roles', [
            'store_id' => $this->store->id,
            'name'     => 'VIP Support Lead',
            'color'    => '#9333ea',
        ]);
    }

    public function test_manager_can_update_role_permissions(): void
    {
        StaffRole::bootstrapDefaultRoles($this->store);
        $role = StaffRole::where('store_id', $this->store->id)->where('slug', 'cashier')->firstOrFail();

        $response = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/security/roles/{$role->id}", [
                'name'        => 'Junior Cashier',
                'description' => 'Restricted counter sales only.',
                'color'       => '#10b981',
                'permissions' => ['pos.sell', 'inventory.view'],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $role->refresh();
        $this->assertSame('Junior Cashier', $role->name);
        $this->assertSame(['pos.sell', 'inventory.view'], $role->permissions);
    }

    public function test_system_role_cannot_be_deleted_but_custom_role_can(): void
    {
        StaffRole::bootstrapDefaultRoles($this->store);
        $systemRole = StaffRole::where('store_id', $this->store->id)->where('slug', 'store_manager')->firstOrFail();

        // Attempt deleting system role
        $sysRes = $this->actingAs($this->manager)
            ->delete("/store/{$this->store->slug}/admin/security/roles/{$systemRole->id}");

        $sysRes->assertSessionHasErrors('error');
        $this->assertDatabaseHas('staff_roles', ['id' => $systemRole->id]);

        // Create custom role and delete
        $customRole = StaffRole::create([
            'store_id'    => $this->store->id,
            'name'        => 'Temporary Intern',
            'slug'        => 'temp-intern',
            'permissions' => ['inventory.view'],
            'is_system'   => false,
        ]);

        $delRes = $this->actingAs($this->manager)
            ->delete("/store/{$this->store->slug}/admin/security/roles/{$customRole->id}");

        $delRes->assertRedirect();
        $delRes->assertSessionHas('success');
        $this->assertDatabaseMissing('staff_roles', ['id' => $customRole->id]);
    }

    public function test_manager_can_assign_staff_role(): void
    {
        StaffRole::bootstrapDefaultRoles($this->store);
        $cashierRole = StaffRole::where('store_id', $this->store->id)->where('slug', 'cashier')->firstOrFail();

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/security/roles/assign-staff", [
                'user_id'       => $this->staff->id,
                'staff_role_id' => $cashierRole->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('store_user', [
            'store_id'      => $this->store->id,
            'user_id'       => $this->staff->id,
            'staff_role_id' => $cashierRole->id,
        ]);
    }

    public function test_manager_can_create_and_assign_custom_role_on_the_fly(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/security/roles/assign-staff", [
                'user_id'          => $this->staff->id,
                'action_mode'      => 'create_and_assign',
                'role_name'        => 'Custom Junior Cashier',
                'role_description' => 'Tailored specifically for Staff Ko Ko',
                'role_color'       => '#10b981',
                'role_permissions' => ['pos_sales.view', 'pos_sales.edit', 'products.view'],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $createdRole = StaffRole::where('store_id', $this->store->id)->where('name', 'Custom Junior Cashier')->firstOrFail();
        $this->assertFalse($createdRole->is_system);
        $this->assertEquals(['pos_sales.view', 'pos_sales.edit', 'products.view'], $createdRole->permissions);

        $this->assertDatabaseHas('store_user', [
            'store_id'      => $this->store->id,
            'user_id'       => $this->staff->id,
            'staff_role_id' => $createdRole->id,
        ]);
    }

    public function test_staff_roles_export_and_isolation(): void
    {
        StaffRole::create([
            'store_id'    => $this->otherStore->id,
            'name'        => 'Secret Store 2 Role',
            'slug'        => 'sec-role',
            'permissions' => ['*'],
            'is_system'   => false,
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/security/roles");

        $response->assertOk();
        $response->assertDontSee('Secret Store 2 Role');

        $csvRes = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/security/roles/export");

        $csvRes->assertOk();
        $csvRes->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_roles_type_filter_system_and_custom(): void
    {
        StaffRole::bootstrapDefaultRoles($this->store);

        $customRole = StaffRole::create([
            'store_id'    => $this->store->id,
            'name'        => 'Custom Shift Lead',
            'slug'        => 'custom-shift-lead',
            'permissions' => ['pos.view'],
            'is_system'   => false,
            'is_active'   => true,
        ]);

        // Filter system roles
        $sysRes = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/security/roles?type=system");

        $sysRes->assertOk();
        $sysRoles = collect($sysRes->viewData('roles')->items());
        $this->assertTrue($sysRoles->contains('slug', 'store_manager'));
        $this->assertTrue($sysRoles->contains('slug', 'cashier'));
        $this->assertFalse($sysRoles->contains('slug', 'custom-shift-lead'));

        // Filter custom roles
        $customRes = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/security/roles?type=custom");

        $customRes->assertOk();
        $customRoles = collect($customRes->viewData('roles')->items());
        $this->assertTrue($customRoles->contains('slug', 'custom-shift-lead'));
        $this->assertFalse($customRoles->contains('slug', 'store_manager'));
        $this->assertFalse($customRoles->contains('slug', 'cashier'));
    }

    public function test_store_owner_role_cannot_have_wildcard_stripped_or_be_deactivated(): void
    {
        StaffRole::bootstrapDefaultRoles($this->store);
        $ownerRole = StaffRole::where('store_id', $this->store->id)->where('slug', 'store_owner')->firstOrFail();

        // Attempt to restrict store_owner role to only pos.view and deactivate
        $response = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/security/roles/{$ownerRole->id}", [
                'name'        => 'Store Owner Modified',
                'description' => 'Attempted restriction',
                'color'       => '#ff0000',
                'is_active'   => '0',
                'permissions' => ['pos.view'],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $ownerRole->refresh();
        $this->assertSame('Store Owner Modified', $ownerRole->name);
        $this->assertTrue((bool) $ownerRole->is_active, 'Store owner role must remain active');
        $this->assertSame(['*'], $ownerRole->permissions, 'Store owner role must retain wildcard full permissions');
    }
}
