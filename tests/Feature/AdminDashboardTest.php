<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $customer = User::create([
            'name' => 'Customer',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $response = $this->actingAs($customer)->get('/store/main-store/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_store_manager_sees_own_store_dashboard_and_statistics(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = User::create([
            'name' => 'Manager A',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-A1',
            'name' => 'Product A1',
            'slug' => 'product-a1',
            'retail_price' => 1000.00,
            'wholesale_price' => 800.00,
            'stock_status' => 'in_stock',
        ]);

        Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-STAT-1',
            'customer_name' => 'Client A',
            'customer_phone' => '09333333333',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 1000.00,
            'status' => 'pending_contact',
        ])->forceFill(['created_at' => now()->subHours(3)])->save(); // stale — older than the 2h uncontacted threshold

        Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-STAT-2',
            'customer_name' => 'Client B',
            'customer_phone' => '09333333334',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 2000.00,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($manager)->get('/store/store-a/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Admin Dashboard');
        $response->assertSee('Store A');
        $response->assertSee('ORD-STAT-1');
        $response->assertSee('Product A1');
        $response->assertSee('Cancelled Orders');
        $response->assertSee('data-cancelled-orders-stat', false);
        // Today / This Week stat cards (2 today orders; revenue excludes the
        // cancelled order → 1000 + 2000 = Ks 3,000 total, Ks 1,000 revenue)
        $response->assertSee('Today Orders');
        $response->assertSee('This Week Orders');
        $response->assertSee('data-today-orders-stat', false);
        $response->assertSee('Revenue: Ks 1,000');
        // Stale pending_contact order (older than 2 hours) gets highlighted
        $response->assertSee('2h+ uncontacted');
    }

    public function test_admin_root_redirects_to_store_dashboard(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = User::create([
            'name' => 'Manager A',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($manager)->get('/store/store-a/admin/');

        $response->assertRedirect('/store/store-a/admin/dashboard');
    }

    public function test_admin_root_preserves_auth_middleware(): void
    {
        Store::create(['name' => 'Store A', 'slug' => 'store-a']);

        $response = $this->get('/store/store-a/admin/');

        $response->assertRedirect('/login');
    }

    public function test_admin_root_blocks_cross_store_admin_access(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);
        $managerA = User::create([
            'name' => 'Manager A',
            'phone' => '09444444444',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $managerA->stores()->attach($storeA->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($managerA)->get('/store/store-b/admin/');

        $response->assertStatus(403);
    }

    public function test_platform_owner_can_view_store_selection_and_switch_stores(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $owner = User::create([
            'name' => 'Platform Owner',
            'phone' => '09999999999',
            'password' => bcrypt('password'),
            'role' => 'platform_owner',
        ]);

        // Global dashboard select view
        $responseGlobal = $this->actingAs($owner)->get('/admin/dashboard');
        $responseGlobal->assertStatus(200);
        $responseGlobal->assertSee('Platform Owner Store Selector');
        $responseGlobal->assertSee('Store A');
        $responseGlobal->assertSee('Store B');

        // Access specific store dashboard
        $responseStoreB = $this->actingAs($owner)->get('/store/store-b/admin/dashboard');
        $responseStoreB->assertStatus(200);
        $responseStoreB->assertSee('Admin Dashboard');
        $responseStoreB->assertSee('Store B');
    }
}
