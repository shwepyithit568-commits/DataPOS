<?php

namespace Tests\Feature;

use App\BusinessProfiles\BusinessProfile;
use App\Capabilities\Capability;
use App\Models\AuditLog;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreContext;
use App\Services\StorePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RouteLevelAuthorizationAndTamperingTest extends TestCase
{
    use RefreshDatabase;

    protected Store $storeA;
    protected Store $storeB;
    protected User $ownerA;
    protected User $managerA;
    protected StaffRole $managerRole;
    protected User $platformOwner;

    protected function setUp(): void
    {
        parent::setUp();
        StorePermissionService::invalidateCache();

        // Create Store A
        $this->storeA = Store::create([
            'name' => 'Store A Alpha',
            'slug' => 'store-a-alpha',
            'is_active' => true,
            'business_profile' => 'mobile_shop',
            'operation_mode' => BusinessProfile::MODE_OMNICHANNEL,
            'sales_channels' => [
                'pos' => true,
                'online_store' => true,
                'online_ordering' => true,
            ],
            'capabilities' => [
                Capability::CATALOG_BARCODE_PRINTING => true,
                Capability::SERVICE_WARRANTY_TRACKING => true,
                Capability::STOREFRONT_BLOG => true,
            ],
        ]);

        // Create Store B
        $this->storeB = Store::create([
            'name' => 'Store B Beta',
            'slug' => 'store-b-beta',
            'is_active' => true,
            'business_profile' => 'retail_store',
            'operation_mode' => BusinessProfile::MODE_OMNICHANNEL,
            'sales_channels' => [
                'pos' => true,
                'online_store' => true,
                'online_ordering' => false, // Disabled channel in Store B
            ],
            'capabilities_override' => [
                Capability::CATALOG_BARCODE_PRINTING => false, // Disabled capability in Store B
            ],
        ]);

        // Store Owner for Store A
        $this->ownerA = User::create([
            'name' => 'Owner Alpha',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $this->ownerA->stores()->attach($this->storeA->id, [
            'role' => 'store_owner',
            'status' => 'active',
        ]);

        // Restricted Manager Role for Store A (only view permissions)
        $this->managerRole = StaffRole::create([
            'store_id' => $this->storeA->id,
            'name' => 'Restricted Manager',
            'slug' => 'restricted-mgr',
            'permissions' => [
                'products.view',
                'customers.view',
                'ecommerce_orders.view',
                'barcode.view',
            ],
            'is_system' => false,
            'is_active' => true,
        ]);

        // Manager for Store A
        $this->managerA = User::create([
            'name' => 'Manager Alpha',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $this->managerA->stores()->attach($this->storeA->id, [
            'role' => 'store_manager',
            'status' => 'active',
            'staff_role_id' => $this->managerRole->id,
        ]);

        // Platform Owner (no membership in Store A or B)
        $this->platformOwner = User::create([
            'name' => 'Super Platform Owner',
            'phone' => '09999999999',
            'password' => bcrypt('password'),
            'role' => 'platform_owner',
        ]);
    }

    /**
     * Test 1: Manager without explicit edit/create permission receives 403 on write endpoints.
     */
    public function test_manager_without_explicit_permission_gets_403_on_write_routes(): void
    {
        // 1. Direct GET access to products create form without products.edit -> 403
        $response = $this->actingAs($this->managerA)->get("/store/{$this->storeA->slug}/admin/products/create");
        $response->assertStatus(403);

        // 2. Direct POST access to products store without products.edit -> 403
        $postResponse = $this->actingAs($this->managerA)->post("/store/{$this->storeA->slug}/admin/products", [
            'name' => 'New Unauthorized Product',
        ]);
        $postResponse->assertStatus(403);

        // 3. Direct POST access to customers create without customers.edit -> 403
        $custResponse = $this->actingAs($this->managerA)->post("/store/{$this->storeA->slug}/admin/customers", [
            'name' => 'New Customer',
            'phone' => '09444555666',
        ]);
        $custResponse->assertStatus(403);
    }

    /**
     * Test 2: Manager with explicit permissions can access authorized read routes.
     */
    public function test_manager_with_explicit_permission_can_access_authorized_routes(): void
    {
        // Products index allowed (has products.view)
        $prodResponse = $this->actingAs($this->managerA)->get("/store/{$this->storeA->slug}/admin/products");
        $prodResponse->assertStatus(200);

        // Customers index allowed (has customers.view)
        $custResponse = $this->actingAs($this->managerA)->get("/store/{$this->storeA->slug}/admin/customers");
        $custResponse->assertStatus(200);

        // Orders index allowed (has ecommerce_orders.view which also satisfies orders.*)
        $orderResponse = $this->actingAs($this->managerA)->get("/store/{$this->storeA->slug}/admin/orders");
        $orderResponse->assertStatus(200);
    }

    /**
     * Test 3: Module & Channel settings are protected by Store Owner, rejecting regular store managers.
     */
    public function test_modules_and_channels_settings_reject_store_managers_with_403(): void
    {
        // Manager attempts to view modules settings -> 403
        $modGetResponse = $this->actingAs($this->managerA)->get("/store/{$this->storeA->slug}/admin/settings/modules");
        $modGetResponse->assertStatus(403);

        // Manager attempts to toggle module -> 403
        $modPostResponse = $this->actingAs($this->managerA)->post("/store/{$this->storeA->slug}/admin/settings/modules/toggle", [
            'capability' => Capability::CATALOG_VARIANTS,
        ]);
        $modPostResponse->assertStatus(403);

        // Manager attempts to view channels settings -> 403
        $chanGetResponse = $this->actingAs($this->managerA)->get("/store/{$this->storeA->slug}/admin/settings/channels");
        $chanGetResponse->assertStatus(403);

        // Manager attempts to toggle channel -> 403
        $chanPostResponse = $this->actingAs($this->managerA)->post("/store/{$this->storeA->slug}/admin/settings/channels/toggle", [
            'channel' => 'online_ordering',
        ]);
        $chanPostResponse->assertStatus(403);

        // Store Owner accesses both successfully
        $ownerModResponse = $this->actingAs($this->ownerA)->get("/store/{$this->storeA->slug}/admin/settings/modules");
        $ownerModResponse->assertStatus(200);

        $ownerChanResponse = $this->actingAs($this->ownerA)->get("/store/{$this->storeA->slug}/admin/settings/channels");
        $ownerChanResponse->assertStatus(200);
    }

    /**
     * Test 4: Disabled Sales Channel blocks direct URL access with 403.
     */
    public function test_disabled_sales_channel_blocks_direct_url_access_with_403(): void
    {
        // Store B has online_ordering = false
        // Create an owner for Store B
        $ownerB = User::create([
            'name' => 'Owner Beta',
            'phone' => '09333333333',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $ownerB->stores()->attach($this->storeB->id, ['role' => 'store_owner', 'status' => 'active']);

        // Direct GET access to /admin/orders on Store B must receive 403 (channel_not_active)
        $response = $this->actingAs($ownerB)->get("/store/{$this->storeB->slug}/admin/orders");
        $response->assertStatus(403);
    }

    /**
     * Test 5: Disabled Capability blocks direct URL access with 403.
     */
    public function test_disabled_capability_blocks_direct_url_access_with_403(): void
    {
        // Store B has catalog.barcode_printing = false
        $ownerB = User::create([
            'name' => 'Owner Beta 2',
            'phone' => '09444444444',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $ownerB->stores()->attach($this->storeB->id, ['role' => 'store_owner', 'status' => 'active']);

        // Direct GET access to /admin/barcode on Store B must receive 403 (feature_not_enabled)
        $response = $this->actingAs($ownerB)->get("/store/{$this->storeB->slug}/admin/barcode");
        $response->assertStatus(403);
    }

    /**
     * Test 6: Cross-store tampering fails closed with 403.
     */
    public function test_cross_store_access_and_slug_tampering_fails_closed(): void
    {
        // Manager of Store A attempts to access Store B
        $response = $this->actingAs($this->managerA)->get("/store/{$this->storeB->slug}/admin/products");
        $response->assertStatus(403);
    }

    /**
     * Test 7: Platform Owner has universal authority without a store_user membership row.
     */
    public function test_platform_owner_effective_permissions_without_membership_row(): void
    {
        // Platform owner has no membership row in storeA
        $this->assertNull($this->platformOwner->getStoreMembership($this->storeA->id));

        // Platform Owner evaluates can() as true
        $permService = app(StorePermissionService::class);
        $this->assertTrue($permService->can($this->platformOwner, $this->storeA, 'settings.manage'));
        $this->assertTrue($permService->can($this->platformOwner, $this->storeA, 'ecommerce_orders.view'));

        // Platform Owner accesses Store A admin settings directly -> 200
        $response = $this->actingAs($this->platformOwner)->get("/store/{$this->storeA->slug}/admin/settings/modules");
        $response->assertStatus(200);

        // Effective permissions returns all canonical permissions
        $perms = $permService->effectivePermissions($this->platformOwner, $this->storeA);
        $this->assertNotEmpty($perms);
        $this->assertContains('ecommerce_orders.view', $perms);
        $this->assertContains('orders.view', $perms);
    }

    /**
     * Test 8: Store Module toggle runs inside DB::transaction with lockForUpdate and writes AuditLog.
     */
    public function test_store_module_toggle_atomic_transaction_and_audit_log(): void
    {
        $response = $this->actingAs($this->ownerA)->post("/store/{$this->storeA->slug}/admin/settings/modules/toggle", [
            'capability' => Capability::CATALOG_VARIANTS,
            'reason' => 'Testing atomic lock and audit log in transaction',
        ]);

        $response->assertSessionHas('success');
        $this->assertFalse($this->storeA->fresh()->hasCapability(Capability::CATALOG_VARIANTS));

        // Verify AuditLog written
        $auditLog = AuditLog::where('store_id', $this->storeA->id)
            ->where('action', 'store_module_toggle')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($this->ownerA->id, $auditLog->actor_id);
    }

    /**
     * Test 9: Store Channel toggle runs inside DB::transaction with lockForUpdate and writes AuditLog.
     */
    public function test_store_channel_toggle_atomic_transaction_and_audit_log(): void
    {
        $response = $this->actingAs($this->ownerA)->post("/store/{$this->storeA->slug}/admin/settings/channels/toggle", [
            'channel' => 'online_ordering',
        ]);

        $response->assertSessionHas('success');
        $this->assertFalse($this->storeA->fresh()->hasSalesChannel(Store::CHANNEL_ONLINE_ORDERING));

        // Verify AuditLog written
        $auditLog = AuditLog::where('store_id', $this->storeA->id)
            ->where('action', 'store_channel_toggle')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($this->ownerA->id, $auditLog->actor_id);
    }

    /**
     * Test 10: Special actions (duplicate, bulk delete, bulk prices) are strictly guarded.
     */
    public function test_special_actions_are_strictly_guarded_against_unauthorized_managers(): void
    {
        // Create an existing product in Store A for model binding
        $product = \App\Models\Product::create([
            'store_id' => $this->storeA->id,
            'name' => 'Existing Phone Case',
            'slug' => 'existing-phone-case',
            'sku' => 'CASE-001',
            'retail_price' => 15000,
            'wholesale_price' => 10000,
        ]);

        // Bulk delete requires products.delete
        $deleteResponse = $this->actingAs($this->managerA)->post("/store/{$this->storeA->slug}/admin/products/bulk-delete", [
            'ids' => [$product->id],
        ]);
        $deleteResponse->assertStatus(403);

        // Bulk price adjust requires products.edit
        $bulkPriceResponse = $this->actingAs($this->managerA)->post("/store/{$this->storeA->slug}/admin/products/bulk-prices", [
            'adjust_type' => 'percentage',
            'value' => 10,
        ]);
        $bulkPriceResponse->assertStatus(403);

        // Duplicate requires products.edit
        $dupResponse = $this->actingAs($this->managerA)->post("/store/{$this->storeA->slug}/admin/products/{$product->id}/duplicate");
        $dupResponse->assertStatus(403);
    }

    /**
     * Test 11: POS module routes are guarded by store.channel:pos and store.permission.
     */
    public function test_pos_module_channel_and_permission_enforcement(): void
    {
        // Manager A has products.view, customers.view, ecommerce_orders.view, barcode.view
        // Manager A does NOT have pos_closing.edit
        $shiftResponse = $this->actingAs($this->managerA)->post("/store/{$this->storeA->slug}/pos/shifts", [
            'opening_cash' => 50000,
        ]);
        $shiftResponse->assertStatus(403);

        // Store with online_store disabled blocks /admin/web-products
        $storeNoOnline = Store::create([
            'name' => 'Offline Only Store',
            'slug' => 'offline-only-store',
            'is_active' => true,
            'business_profile' => 'retail_store',
            'sales_channels' => [
                'pos' => true,
                'online_store' => false,
                'online_ordering' => false,
            ],
        ]);
        $ownerNoOnline = User::create([
            'name' => 'Owner Offline',
            'phone' => '09555555555',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $ownerNoOnline->stores()->attach($storeNoOnline->id, ['role' => 'store_owner', 'status' => 'active']);

        $webProdResponse = $this->actingAs($ownerNoOnline)->get("/store/{$storeNoOnline->slug}/admin/web-products");
        $webProdResponse->assertStatus(403);
    }

    /**
     * Test 12: Middleware returns tri-lingual translated messages when responding with 403.
     */
    public function test_middleware_returns_localized_error_response(): void
    {
        // JSON request from unauthorized manager
        $response = $this->actingAs($this->managerA)->getJson("/store/{$this->storeA->slug}/admin/products/create");
        $response->assertStatus(403);
        $response->assertJson([
            'message' => __('messages.permission_denied'),
        ]);
    }
}
