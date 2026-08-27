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

    public function test_manager_can_view_admin_wholesale_index_and_show(): void
    {
        $store = Store::create(['name' => 'Store Wholesale', 'slug' => 'store-wholesale']);
        $store->setting()->create(['store_name' => 'Store Wholesale', 'default_language' => 'en']);

        $manager = User::create([
            'name' => 'Wholesale Manager',
            'phone' => '09777777777',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $application = WholesaleApplication::create([
            'store_id' => $store->id,
            'user_id' => $manager->id,
            'business_name' => 'Grand Tech Mobile',
            'phone' => '09777777777',
            'address' => 'Mandalay',
            'status' => 'pending',
            'notes' => 'Looking for wholesale bulk orders',
        ]);

        $responseIndex = $this->actingAs($manager)->get("/store/{$store->slug}/admin/wholesale/applications");
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Grand Tech Mobile');

        $responseShow = $this->actingAs($manager)->get("/store/{$store->slug}/admin/wholesale/applications/{$application->id}");
        $responseShow->assertStatus(200);
        $responseShow->assertSee('Grand Tech Mobile');
        $responseShow->assertSee('Looking for wholesale bulk orders');
    }

    public function test_wholesale_admin_index_renders_in_all_locales_without_key_leaks(): void
    {
        $manager = User::create([
            'name' => 'Manager Locale',
            'phone' => '09666666666',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        foreach (['en', 'my', 'zh_CN'] as $code) {
            $store = Store::create(['name' => "Store {$code}", 'slug' => "store-wh-{$code}"]);
            $store->setting()->create(['store_name' => "Store {$code}", 'default_language' => $code]);
            $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

            $response = $this->actingAs($manager)->get("/store/{$store->slug}/admin/wholesale/applications");
            $response->assertStatus(200);
            $response->assertDontSee('messages.', false);
        }
    }

    public function test_wholesale_export_csv(): void
    {
        $store = Store::create(['name' => 'Store Wh Export', 'slug' => 'store-wh-export']);
        $store->setting()->create(['store_name' => 'Store Wh Export', 'default_language' => 'en']);

        $manager = User::create([
            'name' => 'Manager Export',
            'phone' => '09555555555',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        WholesaleApplication::create([
            'store_id' => $store->id,
            'user_id' => $manager->id,
            'business_name' => 'Apex Mobile Distribution',
            'phone' => '09555555555',
            'address' => 'Yangon Downtown',
            'status' => 'approved',
            'notes' => 'Bulk phone distributor',
        ]);

        $response = $this->actingAs($manager)->get("/store/{$store->slug}/admin/wholesale/applications/export");
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();

        // Verify UTF-8 BOM and data
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Wholesale Applications Report', $csv);
        $this->assertStringContainsString('Apex Mobile Distribution', $csv);
        $this->assertStringContainsString('APPROVED', $csv);
    }
}
