<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\WholesaleApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WholesaleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_wholesale_application(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $customer = User::create([
            'name' => 'Customer A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $response = $this->actingAs($customer)->post('/store/main-store/wholesale/apply', [
            'business_name' => 'Aung Mobile Shop',
            'phone' => '09111111111',
            'address' => 'Yangon',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('wholesale_applications', [
            'store_id' => $store->id,
            'user_id' => $customer->id,
            'business_name' => 'Aung Mobile Shop',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_application_and_user_sees_wholesale_price(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $customer = User::create([
            'name' => 'Customer A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager = User::create([
            'name' => 'Manager',
            'phone' => '09999999999',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-001',
            'name' => 'Test Product',
            'slug' => 'test-product',
            'retail_price' => 10000.00,
            'wholesale_price' => 7000.00,
            'stock_status' => 'in_stock',
        ]);

        $application = WholesaleApplication::create([
            'store_id' => $store->id,
            'user_id' => $customer->id,
            'business_name' => 'Aung Mobile Shop',
            'phone' => '09111111111',
            'status' => 'pending',
        ]);

        // 1. Pending User sees Retail Price only
        $responsePending = $this->actingAs($customer)->get('/products?store_slug=main-store');
        $responsePending->assertSee('10,000');
        $responsePending->assertDontSee('7,000');

        // 2. Admin approves application
        $responseApprove = $this->actingAs($manager)->patch('/store/main-store/admin/wholesale/applications/' . $application->id, [
            'status' => 'approved',
        ]);
        $responseApprove->assertRedirect();

        // 3. Approved User sees Wholesale Price
        $responseApproved = $this->actingAs($customer)->get('/products?store_slug=main-store');
        $responseApproved->assertSee(__('messages.wholesale') . ': Ks 7,000');
    }

    public function test_cross_store_approval_blocked(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $customer = User::create([
            'name' => 'Customer B',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $managerA = User::create([
            'name' => 'Manager A',
            'phone' => '09888888888',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $managerA->stores()->attach($storeA->id, ['role' => 'store_manager', 'status' => 'active']);

        $appB = WholesaleApplication::create([
            'store_id' => $storeB->id,
            'user_id' => $customer->id,
            'business_name' => 'B Mobile Shop',
            'phone' => '09222222222',
            'status' => 'pending',
        ]);

        // Manager A tries to approve Application on Store B -> Forbidden
        $response = $this->actingAs($managerA)->patch('/store/store-b/admin/wholesale/applications/' . $appB->id, [
            'status' => 'approved',
        ]);

        $response->assertStatus(403);
    }
}
