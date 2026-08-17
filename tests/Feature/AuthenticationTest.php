<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_customer_can_register_and_is_enrolled_in_store(): void
    {
        // A single active store — registration resolves it and enrolls the
        // shopper, so ecommerce customers appear in that store's POS list.
        $store = Store::create(['name' => 'Shop A', 'slug' => 'shop-a', 'is_active' => true]);

        $response = $this->post('/register', [
            'name' => 'New Customer',
            'phone' => '09123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'platform_owner', // Attempted role tampering by client
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::where('phone', '09123456789')->first();
        $this->assertNotNull($user);
        // Security check: Client role payload is ignored and role is forced to 'customer'
        $this->assertEquals('customer', $user->role);
        // Store-scoped enrollment: ecommerce + POS share one customer list.
        $this->assertTrue($user->hasStoreRole($store->id, 'retail_customer'));
    }

    public function test_registration_merges_existing_quick_added_account(): void
    {
        $store = Store::create(['name' => 'Shop A', 'slug' => 'shop-a', 'is_active' => true]);

        // A POS quick-add created the account first (random password, no login).
        $quickAdded = User::create([
            'name' => 'Daw Phyu',
            'phone' => '09123456789',
            'password' => bcrypt(\Illuminate\Support\Str::random(24)),
            'role' => 'customer',
        ]);
        $quickAdded->stores()->attach($store->id, ['role' => 'retail_customer', 'status' => 'active']);

        // The same person registers online with the same phone — merge, not
        // duplicate: the account gets a real password and stays one record.
        $response = $this->post('/register', [
            'name' => 'Daw Phyu',
            'phone' => '09123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($quickAdded);
        $this->assertSame(1, User::where('phone', '09123456789')->count());
        $this->assertTrue(Hash::check('password123', $quickAdded->fresh()->password));
    }

    public function test_registration_rejects_staff_phone(): void
    {
        Store::create(['name' => 'Shop A', 'slug' => 'shop-a', 'is_active' => true]);

        $staff = User::create([
            'name' => 'Staff One',
            'phone' => '09123456789',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $staff->stores()->attach(Store::first()->id, ['role' => 'staff', 'status' => 'active']);

        $this->post('/register', [
            'name' => 'Impostor',
            'phone' => '09123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('phone');
    }

    public function test_customer_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'Existing Customer',
            'phone' => '09987654321',
            'password' => bcrypt('secret123'),
            'role' => 'customer',
        ]);

        $response = $this->post('/login', [
            'phone' => '09987654321',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_password_is_rejected(): void
    {
        User::create([
            'name' => 'Existing Customer',
            'phone' => '09987654321',
            'password' => bcrypt('secret123'),
            'role' => 'customer',
        ]);

        $response = $this->post('/login', [
            'phone' => '09987654321',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_logout_invalidates_session_and_redirects(): void
    {
        $user = User::create([
            'name' => 'Existing Customer',
            'phone' => '09987654321',
            'password' => bcrypt('secret123'),
            'role' => 'customer',
        ]);

        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_store_staff_role_remains_store_scoped_after_authentication(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $staffUser = User::create([
            'name' => 'Store Staff',
            'phone' => '09444444444',
            'password' => bcrypt('secret123'),
            'role' => 'customer',
        ]);

        $staffUser->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        // Log in as staff
        $this->post('/login', [
            'phone' => '09444444444',
            'password' => 'secret123',
        ]);

        // Global role remains customer
        $this->assertEquals('customer', auth()->user()->role);

        // Store role is staff for Main Store
        $this->assertEquals('staff', auth()->user()->getStoreRole($store->id));

        // Store role is null for unassigned store
        $this->assertNull(auth()->user()->getStoreRole(999));
    }

    public function test_platform_owner_redirects_to_global_admin_dashboard(): void
    {
        $owner = User::create([
            'name' => 'Platform Owner',
            'phone' => '09999999999',
            'password' => bcrypt('password'),
            'role' => 'platform_owner',
        ]);

        $response = $this->post('/login', [
            'phone' => '09999999999',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($owner);
    }

    public function test_store_manager_redirects_to_store_admin_dashboard(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $manager = User::create([
            'name' => 'Store Manager',
            'phone' => '09888888888',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->post('/login', [
            'phone' => '09888888888',
            'password' => 'password',
        ]);

        $response->assertRedirect('/store/main-store/admin/dashboard');
        $this->assertAuthenticatedAs($manager);
    }

    public function test_staff_redirects_to_store_admin_dashboard(): void
    {
        $store = Store::create(['name' => 'Staff Store', 'slug' => 'staff-store']);
        $staff = User::create([
            'name' => 'Store Staff',
            'phone' => '09777777777',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $staff->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        $response = $this->post('/login', [
            'phone' => '09777777777',
            'password' => 'password',
        ]);

        $response->assertRedirect('/store/staff-store/admin/dashboard');
        $this->assertAuthenticatedAs($staff);
    }

    public function test_customer_redirects_to_storefront_of_their_store(): void
    {
        $store = Store::create(['name' => 'Retail Store', 'slug' => 'retail-store']);
        $customer = User::create([
            'name' => 'Retail Customer',
            'phone' => '09666666666',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $customer->stores()->attach($store->id, ['role' => 'retail_customer', 'status' => 'active']);

        $response = $this->post('/login', [
            'phone' => '09666666666',
            'password' => 'password',
        ]);

        $response->assertRedirect('/store/retail-store');
        $this->assertAuthenticatedAs($customer);
    }

    public function test_pending_membership_does_not_block_login(): void
    {
        $store = Store::create(['name' => 'Pending Store', 'slug' => 'pending-store']);
        $user = User::create([
            'name' => 'Pending User',
            'phone' => '09555555555',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        // Pending membership — should not be considered "primary store"
        $user->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'pending']);

        $response = $this->post('/login', [
            'phone' => '09555555555',
            'password' => 'password',
        ]);

        // No active membership → fallback to /
        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }
}
