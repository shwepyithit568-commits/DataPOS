<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $owner;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'ACDC Mobile', 'slug' => 'acdc-mobile']);
        $this->store->setting()->create(['store_name' => 'ACDC Mobile', 'default_language' => 'my']);

        $this->owner = User::create([
            'name' => 'Owner',
            'phone' => '09111111111',
            'password' => bcrypt('Owner@123456'),
            'role' => 'platform_owner',
        ]);

        $this->manager = User::create([
            'name' => 'Manager',
            'phone' => '09222222222',
            'password' => bcrypt('Manager@123456'),
            'role' => 'customer',
        ]);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_platform_owner_can_view_users_page(): void
    {
        $this->actingAs($this->owner)
            ->get("/store/{$this->store->slug}/admin/users")
            ->assertOk()
            ->assertSee('Users & Customers', false)
            ->assertSee('Create User Account');
    }

    public function test_store_manager_cannot_view_users_page(): void
    {
        $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/users")
            ->assertForbidden();
    }

    public function test_platform_owner_can_create_store_user(): void
    {
        $this->actingAs($this->owner)
            ->post("/store/{$this->store->slug}/admin/users", [
                'name' => 'Staff One',
                'phone' => '09333333333',
                'role' => 'staff',
                'status' => 'active',
                'password' => 'Staff@123456',
                'password_confirmation' => 'Staff@123456',
            ])
            ->assertRedirect("/store/{$this->store->slug}/admin/users");

        $user = User::where('phone', '09333333333')->firstOrFail();

        $this->assertSame('customer', $user->role);
        $this->assertDatabaseHas('store_user', [
            'user_id' => $user->id,
            'store_id' => $this->store->id,
            'role' => 'staff',
            'status' => 'active',
        ]);
        $this->assertTrue(Hash::check('Staff@123456', $user->password));
    }

    public function test_platform_owner_can_update_user_and_reset_password(): void
    {
        $user = User::create([
            'name' => 'Retail Customer',
            'phone' => '09444444444',
            'password' => bcrypt('OldPass@1234'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($this->store->id, ['role' => 'retail_customer', 'status' => 'active']);

        $this->actingAs($this->owner)
            ->put("/store/{$this->store->slug}/admin/users/{$user->id}", [
                'name' => 'Wholesale Customer',
                'phone' => '09555555555',
                'role' => 'wholesale_customer',
                'status' => 'pending',
                'password' => 'NewPass@12345',
                'password_confirmation' => 'NewPass@12345',
            ])
            ->assertRedirect("/store/{$this->store->slug}/admin/users");

        $user->refresh();

        $this->assertSame('Wholesale Customer', $user->name);
        $this->assertSame('09555555555', $user->phone);
        $this->assertTrue(Hash::check('NewPass@12345', $user->password));
        $this->assertDatabaseHas('store_user', [
            'user_id' => $user->id,
            'store_id' => $this->store->id,
            'role' => 'wholesale_customer',
            'status' => 'pending',
        ]);
    }

    public function test_platform_owner_can_suspend_store_access_without_deleting_user(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'phone' => '09666666666',
            'password' => bcrypt('Customer@1234'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($this->store->id, ['role' => 'retail_customer', 'status' => 'active']);

        $this->actingAs($this->owner)
            ->patch("/store/{$this->store->slug}/admin/users/{$user->id}/suspend")
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('store_user', [
            'user_id' => $user->id,
            'store_id' => $this->store->id,
            'status' => 'suspended',
        ]);
    }

    public function test_platform_owner_cannot_remove_own_owner_role(): void
    {
        $this->actingAs($this->owner)
            ->from("/store/{$this->store->slug}/admin/users/{$this->owner->id}/edit")
            ->put("/store/{$this->store->slug}/admin/users/{$this->owner->id}", [
                'name' => 'Owner',
                'phone' => '09111111111',
                'role' => 'staff',
                'status' => 'active',
            ])
            ->assertRedirect("/store/{$this->store->slug}/admin/users/{$this->owner->id}/edit")
            ->assertSessionHasErrors('role');

        $this->assertSame('platform_owner', $this->owner->refresh()->role);
    }
}
