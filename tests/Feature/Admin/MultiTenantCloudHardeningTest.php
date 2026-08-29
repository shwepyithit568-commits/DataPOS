<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\SubscriptionPlanService;
use App\Services\SupportAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantCloudHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $platformOwner;
    protected User $storeOwner;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->platformOwner = User::create([
            'name'     => 'Platform Super Owner',
            'phone'    => '09900000001',
            'password' => bcrypt('password'),
            'role'     => 'platform_owner',
        ]);

        $this->storeOwner = User::create([
            'name'     => 'Ko Aung (Store Owner)',
            'phone'    => '09900000002',
            'password' => bcrypt('password'),
            'role'     => 'customer',
        ]);

        $this->store = Store::create([
            'name'              => 'Cloud Test Store',
            'slug'              => 'cloud-test-store',
            'subscription_tier' => 'standard',
            'is_active'         => true,
        ]);

        $this->store->users()->attach($this->storeOwner->id, [
            'role'   => 'store_owner',
            'status' => 'active',
        ]);
    }

    public function test_subscription_plan_limits_enforced_on_starter_and_standard(): void
    {
        $this->store->update([
            'subscription_tier' => 'starter',
            'max_products'      => 2,
        ]);

        $this->assertSame(2, $this->store->maxProducts());
        $this->assertSame(1, $this->store->maxBranches());
        $this->assertTrue($this->store->canAddProduct());

        // Create 2 products
        Product::create([
            'store_id'        => $this->store->id,
            'name'            => 'Item 1',
            'slug'            => 'item-1',
            'sku'             => 'ITM-1',
            'retail_price'    => 5000,
            'wholesale_price' => 4500,
            'buy_price'       => 3000,
        ]);
        Product::create([
            'store_id'        => $this->store->id,
            'name'            => 'Item 2',
            'slug'            => 'item-2',
            'sku'             => 'ITM-2',
            'retail_price'    => 7500,
            'wholesale_price' => 6800,
            'buy_price'       => 4500,
        ]);

        $this->assertFalse($this->store->canAddProduct());
    }

    public function test_platform_owner_can_initiate_support_mode_with_mandatory_audit_log(): void
    {
        $response = $this->actingAs($this->platformOwner)->post(route('admin.support-mode.enter'), [
            'store_id' => $this->store->id,
            'reason'   => 'Customer requested urgent troubleshooting on printer setup',
        ]);

        $response->assertRedirect(route('store.admin.dashboard', ['store_slug' => $this->store->slug]));
        $this->assertTrue(session(SupportAccessService::SESSION_KEY_ACTIVE));
        $this->assertSame('Customer requested urgent troubleshooting on printer setup', session(SupportAccessService::SESSION_KEY_REASON));

        // Verify immutable AuditLog
        $this->assertTrue(AuditLog::where('store_id', $this->store->id)
            ->where('action', 'support_mode_session_started')
            ->exists());
    }

    public function test_support_mode_session_requires_reason_and_rejects_non_platform_owners(): void
    {
        // Non-platform owner rejected
        $response = $this->actingAs($this->storeOwner)->post(route('admin.support-mode.enter'), [
            'store_id' => $this->store->id,
            'reason'   => 'Unauthorized attempt',
        ]);
        $response->assertForbidden();

        // Platform owner without reason rejected
        $badResponse = $this->actingAs($this->platformOwner)->post(route('admin.support-mode.enter'), [
            'store_id' => $this->store->id,
            'reason'   => '',
        ]);
        $badResponse->assertSessionHasErrors('reason');
    }

    public function test_support_mode_can_be_exited_cleanly(): void
    {
        $this->actingAs($this->platformOwner)->post(route('admin.support-mode.enter'), [
            'store_id' => $this->store->id,
            'reason'   => 'Exiting test session',
        ]);

        $exitResponse = $this->actingAs($this->platformOwner)->post(route('admin.support-mode.exit'));
        $exitResponse->assertRedirect(route('admin.stores.index'));

        $this->assertFalse(session()->has(SupportAccessService::SESSION_KEY_ACTIVE));

        $this->assertTrue(AuditLog::where('store_id', $this->store->id)
            ->where('action', 'support_mode_session_ended')
            ->exists());
    }

    public function test_store_data_export_endpoint_returns_valid_json_archive(): void
    {
        // Populate category and product
        $category = Category::create([
            'store_id' => $this->store->id,
            'name'     => 'Test Category',
            'slug'     => 'test-cat',
        ]);
        Product::create([
            'store_id'        => $this->store->id,
            'category_id'     => $category->id,
            'name'            => 'Export Product',
            'slug'            => 'export-product',
            'sku'             => 'EXP-01',
            'retail_price'    => 12000,
            'wholesale_price' => 11000,
            'buy_price'       => 8000,
        ]);

        $response = $this->actingAs($this->storeOwner)
            ->get(route('store.admin.settings.export-data', ['store_slug' => $this->store->slug]));

        $response->assertOk();
        $response->assertHeader('Content-Disposition');

        $data = $response->json();
        $this->assertSame('Cloud Test Store', $data['store_metadata']['name']);
        $this->assertNotEmpty($data['categories']);
        $this->assertNotEmpty($data['products']);
        $this->assertSame('Export Product', $data['products'][0]['name']);
    }
}
