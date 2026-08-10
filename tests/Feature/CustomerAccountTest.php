<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_access_account_dashboard_and_orders(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $user = User::create([
            'name' => 'Customer A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $order = Order::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'order_number' => 'ORD-MY-1',
            'customer_name' => 'Customer A',
            'customer_phone' => '09111111111',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 5000.00,
            'status' => 'pending_contact',
        ]);

        // Access Account Dashboard
        $responseDash = $this->actingAs($user)->get('/account?store_slug=main-store');
        $responseDash->assertStatus(200);
        $responseDash->assertSee('Customer A');

        // Access My Orders
        $responseOrders = $this->actingAs($user)->get('/account/orders?store_slug=main-store');
        $responseOrders->assertStatus(200);
        $responseOrders->assertSee('ORD-MY-1');
    }

    public function test_account_pages_without_query_string_keep_authenticated_store_branding(): void
    {
        $store = Store::create(['name' => 'DataPOS', 'slug' => 'datapos-mobile']);
        $store->setting()->create([
            'store_name' => 'DataPOS',
            'default_language' => 'my',
        ]);
        $user = User::create([
            'name' => 'Store Customer',
            'phone' => '09111111112',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'retail_customer', 'status' => 'active']);

        $response = $this->actingAs($user)->get('/account');

        $response->assertStatus(200);
        $response->assertSee('DataPOS');
        $response->assertSee('/account/orders?store_slug=datapos-mobile', false);
        $response->assertSee('/account/favorites?store_slug=datapos-mobile', false);
        $response->assertDontSee('ACDC Mobile');
    }

    public function test_root_page_uses_only_active_store_when_no_slug_is_provided(): void
    {
        $store = Store::create(['name' => 'DataPOS', 'slug' => 'datapos-mobile']);
        $store->setting()->create([
            'store_name' => 'DataPOS',
            'default_language' => 'my',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('DataPOS');
        $response->assertSee('/products?store_slug=datapos-mobile', false);
        $response->assertDontSee('ACDC Mobile');
    }

    public function test_user_a_cannot_view_user_b_order_detail(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $userA = User::create([
            'name' => 'User A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $userB = User::create([
            'name' => 'User B',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $orderB = Order::create([
            'store_id' => $store->id,
            'user_id' => $userB->id,
            'order_number' => 'ORD-USER-B',
            'customer_name' => 'User B',
            'customer_phone' => '09222222222',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 5000.00,
            'status' => 'pending_contact',
        ]);

        // User A tries to access User B Order Detail -> 403 Forbidden
        $response = $this->actingAs($userA)->get('/account/orders/' . $orderB->id . '?store_slug=main-store');
        $response->assertStatus(403);
    }

    public function test_platform_owner_sees_admin_link_on_account_page(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $user = User::create([
            'name' => 'Owner',
            'phone' => '09111111131',
            'password' => bcrypt('password'),
            'role' => 'platform_owner',
        ]);

        $response = $this->actingAs($user)->get('/account?store_slug=main-store');

        $response->assertStatus(200);
        $response->assertSee('Admin Panel', false);
        $response->assertSee(route('store.admin.dashboard', ['store_slug' => 'main-store']), false);
    }

    public function test_store_manager_sees_admin_link_on_account_page(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $user = User::create([
            'name' => 'Manager',
            'phone' => '09111111132',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($user)->get('/account?store_slug=main-store');

        $response->assertStatus(200);
        $response->assertSee('Admin Panel', false);
        $response->assertSee(route('store.admin.dashboard', ['store_slug' => 'main-store']), false);
    }

    public function test_staff_member_sees_admin_link_on_account_page(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $user = User::create([
            'name' => 'Staff',
            'phone' => '09111111133',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        $response = $this->actingAs($user)->get('/account?store_slug=main-store');

        $response->assertStatus(200);
        $response->assertSee('Admin Panel', false);
        $response->assertSee(route('store.admin.dashboard', ['store_slug' => 'main-store']), false);
    }

    public function test_regular_customer_does_not_see_admin_link(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $user = User::create([
            'name' => 'Retail',
            'phone' => '09111111134',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'retail_customer', 'status' => 'active']);

        $response = $this->actingAs($user)->get('/account?store_slug=main-store');

        $response->assertStatus(200);
        $response->assertDontSee('Admin Panel', false);
        $response->assertDontSee('/admin/dashboard', false);
    }
}
