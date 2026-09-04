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
            ->assertSee('ဆိုင်ဝန်ထမ်းများ စီမံခန့်ခွဲမှု', false)
            ->assertSee('ဝန်ထမ်းသစ် ထည့်သွင်းမည်', false)
            ->assertSee('ဖောက်သည်များ စာရင်း', false);
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
            'name' => 'Junior Staff',
            'phone' => '09444444444',
            'password' => bcrypt('OldPass@1234'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $this->actingAs($this->owner)
            ->put("/store/{$this->store->slug}/admin/users/{$user->id}", [
                'name' => 'Senior Manager',
                'phone' => '09555555555',
                'role' => 'store_manager',
                'status' => 'pending',
                'password' => 'NewPass@12345',
                'password_confirmation' => 'NewPass@12345',
            ])
            ->assertRedirect("/store/{$this->store->slug}/admin/users");

        $user->refresh();

        $this->assertSame('Senior Manager', $user->name);
        $this->assertSame('09555555555', $user->phone);
        $this->assertTrue(Hash::check('NewPass@12345', $user->password));
        $this->assertDatabaseHas('store_user', [
            'user_id' => $user->id,
            'store_id' => $this->store->id,
            'role' => 'store_manager',
            'status' => 'pending',
        ]);
    }

    public function test_platform_owner_can_suspend_store_access_without_deleting_user(): void
    {
        $user = User::create([
            'name' => 'Staff Member',
            'phone' => '09666666666',
            'password' => bcrypt('Staff@1234'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

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

    public function test_customers_are_strictly_excluded_from_store_users_page(): void
    {
        $staff = User::create([
            'name' => 'Active Staff Ko Ko',
            'phone' => '09777777771',
            'password' => bcrypt('Staff@1234'),
            'role' => 'customer',
        ]);
        $staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $retailCustomer = User::create([
            'name' => 'Retail Customer Daw Aye',
            'phone' => '09777777772',
            'password' => bcrypt('Customer@1234'),
            'role' => 'customer',
        ]);
        $retailCustomer->stores()->attach($this->store->id, ['role' => 'retail_customer', 'status' => 'active']);

        $wholesaleCustomer = User::create([
            'name' => 'Wholesale Customer U Ba',
            'phone' => '09777777773',
            'password' => bcrypt('Customer@1234'),
            'role' => 'customer',
        ]);
        $wholesaleCustomer->stores()->attach($this->store->id, ['role' => 'wholesale_customer', 'status' => 'active']);

        $response = $this->actingAs($this->owner)
            ->get("/store/{$this->store->slug}/admin/users");

        $response->assertOk();
        // Assert Staff is visible
        $response->assertSee('Active Staff Ko Ko');
        $response->assertSee('09777777771');

        // Assert Customers are STRICTLY NOT visible on the users/staff page
        $response->assertDontSee('Retail Customer Daw Aye');
        $response->assertDontSee('09777777772');
        $response->assertDontSee('Wholesale Customer U Ba');
        $response->assertDontSee('09777777773');
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
