<?php

namespace Tests\Feature;

use App\BusinessProfiles\BusinessProfile;
use App\Capabilities\Capability;
use App\Http\Middleware\CheckStoreChannel;
use App\Http\Middleware\CheckStorePermission;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreContext;
use App\Services\StorePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Phase2FoundationsTest extends TestCase
{
    use RefreshDatabase;

    protected StorePermissionService $permissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionService = app(StorePermissionService::class);
        StorePermissionService::invalidateCache();
    }

    public function test_store_channel_resolution_and_precedence(): void
    {
        // 1. Omnichannel default
        $omniStore = Store::create([
            'name' => 'Omni Store',
            'slug' => 'omni-store',
            'operation_mode' => BusinessProfile::MODE_OMNICHANNEL,
        ]);
        $this->assertTrue($omniStore->hasChannel(Store::CHANNEL_POS));
        $this->assertTrue($omniStore->hasChannel(Store::CHANNEL_ONLINE_STORE));
        $this->assertTrue($omniStore->hasChannel(Store::CHANNEL_ONLINE_ORDERING));

        // 2. POS Only default
        $posStore = Store::create([
            'name' => 'POS Only Store',
            'slug' => 'pos-store',
            'operation_mode' => BusinessProfile::MODE_POS_ONLY,
        ]);
        $this->assertTrue($posStore->hasChannel(Store::CHANNEL_POS));
        $this->assertFalse($posStore->hasChannel(Store::CHANNEL_ONLINE_STORE));
        $this->assertFalse($posStore->hasChannel(Store::CHANNEL_ONLINE_ORDERING));

        // 3. Catalog Only default
        $catStore = Store::create([
            'name' => 'Catalog Only Store',
            'slug' => 'cat-store',
            'operation_mode' => BusinessProfile::MODE_CATALOG_ONLY,
        ]);
        $this->assertTrue($catStore->hasChannel(Store::CHANNEL_POS));
        $this->assertTrue($catStore->hasChannel(Store::CHANNEL_ONLINE_STORE));
        $this->assertFalse($catStore->hasChannel(Store::CHANNEL_ONLINE_ORDERING));

        // 4. Invariant: POS is protected in this phase even if explicit override attempts to set false
        $omniStore->sales_channels = ['pos' => false];
        $omniStore->save();
        $this->assertTrue($omniStore->fresh()->hasChannel(Store::CHANNEL_POS));

        // 5. Invariant: online_ordering=true requires online_store=true
        $omniStore->sales_channels = [
            'online_store' => false,
            'online_ordering' => true,
        ];
        $omniStore->save();
        $channels = $omniStore->fresh()->getSalesChannels();
        $this->assertFalse($channels['online_store']);
        $this->assertFalse($channels['online_ordering'], 'online_ordering must be false when online_store is false');

        // 6. Capability dependency: disabling storefront.ecommerce capability forces online_store=false
        $omniStore->sales_channels = null;
        $omniStore->capabilities_override = [
            Capability::STOREFRONT_ECOMMERCE => false,
        ];
        $omniStore->save();
        $this->assertFalse($omniStore->fresh()->hasChannel(Store::CHANNEL_ONLINE_STORE));
        $this->assertFalse($omniStore->fresh()->hasChannel(Store::CHANNEL_ONLINE_ORDERING));
    }

    public function test_backfill_sales_channels_command_modes(): void
    {
        $store = Store::create([
            'name' => 'Backfill Test Store',
            'slug' => 'backfill-test',
            'operation_mode' => BusinessProfile::MODE_OMNICHANNEL,
        ]);

        Product::create([
            'store_id' => $store->id,
            'name' => 'Test Item',
            'sku' => 'SKU-BACKFILL-001',
            'slug' => 'test-item',
            'retail_price' => 1000,
            'wholesale_price' => 800,
        ]);

        // 1. Dry run mode does not write to DB
        Artisan::call('store:backfill-sales-channels', ['--dry-run' => true]);
        $this->assertNull($store->fresh()->sales_channels);

        // 2. Apply mode writes to DB and writes AuditLog
        Artisan::call('store:backfill-sales-channels');
        $freshStore = $store->fresh();
        $this->assertNotNull($freshStore->sales_channels);
        $this->assertTrue($freshStore->sales_channels['pos']);
        $this->assertTrue($freshStore->sales_channels['online_store']);
        $this->assertTrue($freshStore->sales_channels['online_ordering']);

        $auditLog = AuditLog::where('store_id', $store->id)
            ->where('action', 'sales_channels.backfill')
            ->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('backfill_sales_channels_2026_09', $auditLog->metadata['migration_marker']);

        // 3. Idempotent: running again skips the store
        $output = Artisan::output();
        Artisan::call('store:backfill-sales-channels');
        $this->assertEquals($freshStore->sales_channels, $store->fresh()->sales_channels);

        // 4. Safe rollback restores previous state
        Artisan::call('store:backfill-sales-channels', ['--rollback' => true]);
        $this->assertNull($store->fresh()->sales_channels);
    }

    public function test_store_permission_service_effective_permissions_and_denies_win(): void
    {
        $store = Store::create([
            'name' => 'Auth Store',
            'slug' => 'auth-store',
            'operation_mode' => BusinessProfile::MODE_OMNICHANNEL,
        ]);

        $role = StaffRole::create([
            'store_id' => $store->id,
            'name' => 'Cashier Role',
            'slug' => 'cashier-role',
            'permissions' => ['pos_sales.view', 'pos_sales.edit', 'products.view'],
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Staff Ko',
            'phone' => '09111222333',
            'password' => bcrypt('secret'),
            'role' => 'customer',
        ]);

        // Inactive membership -> no permissions
        $user->stores()->attach($store->id, [
            'role' => 'staff',
            'status' => 'suspended',
            'staff_role_id' => $role->id,
        ]);
        $this->assertEmpty($this->permissionService->effectivePermissions($user, $store));
        $this->assertFalse($this->permissionService->can($user, $store, 'pos_sales.view'));

        // Activate membership
        $user->stores()->updateExistingPivot($store->id, ['status' => 'active']);
        StorePermissionService::invalidateCache();

        $effective = $this->permissionService->effectivePermissions($user, $store);
        $this->assertContains('pos_sales.view', $effective);
        $this->assertContains('products.view', $effective);

        // Individual grant + individual deny: Denies win!
        // Role grants: pos_sales.view, pos_sales.edit, products.view
        // Custom: grant inventory.view, deny pos_sales.edit
        $user->stores()->updateExistingPivot($store->id, [
            'custom_permissions' => [
                'grants' => ['stock_balance.view'],
                'denies' => ['pos_sales.edit'],
            ],
        ]);
        StorePermissionService::invalidateCache();

        $newEffective = $this->permissionService->effectivePermissions($user, $store);
        $this->assertContains('pos_sales.view', $newEffective);
        $this->assertContains('stock_balance.view', $newEffective);
        $this->assertNotContains('pos_sales.edit', $newEffective, 'Deny must win over role permission');

        $this->assertTrue($this->permissionService->can($user, $store, 'pos_sales.view'));
        $this->assertTrue($this->permissionService->can($user, $store, 'stock_balance.view'));
        $this->assertFalse($this->permissionService->can($user, $store, 'pos_sales.edit'));
        $this->assertFalse($this->permissionService->can($user, $store, 'pos_sales.update'), 'Denied edit also blocks update alias');
    }

    public function test_edit_update_aliasing_and_no_create_grant(): void
    {
        $store = Store::create([
            'name' => 'Alias Store',
            'slug' => 'alias-store',
        ]);

        $role = StaffRole::create([
            'store_id' => $store->id,
            'name' => 'Editor Role',
            'slug' => 'editor-role',
            'permissions' => ['products.edit'],
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Editor User',
            'phone' => '09222333444',
            'password' => bcrypt('secret'),
        ]);
        $user->stores()->attach($store->id, [
            'role' => 'staff',
            'status' => 'active',
            'staff_role_id' => $role->id,
        ]);

        // products.edit aliases products.update
        $this->assertTrue($this->permissionService->can($user, $store, 'products.edit'));
        $this->assertTrue($this->permissionService->can($user, $store, 'products.update'));

        // products.edit does NOT grant products.create
        $this->assertFalse($this->permissionService->can($user, $store, 'products.create'));
        $this->assertFalse($this->permissionService->can($user, $store, 'products.delete'));
    }

    public function test_owner_and_platform_cannot_bypass_disabled_capability_or_channel(): void
    {
        $store = Store::create([
            'name' => 'Boundary Store',
            'slug' => 'boundary-store',
            'capabilities_override' => [
                Capability::SERVICE_REPAIR_JOBS => false,
            ],
        ]);

        $owner = User::create([
            'name' => 'Store Owner',
            'phone' => '09333444555',
            'password' => bcrypt('secret'),
        ]);
        $owner->stores()->attach($store->id, [
            'role' => 'store_owner',
            'status' => 'active',
        ]);

        $platform = User::create([
            'name' => 'Platform Admin',
            'phone' => '09444555666',
            'password' => bcrypt('secret'),
            'role' => 'platform_owner',
        ]);

        // Standard permission is allowed
        $this->assertTrue($this->permissionService->can($owner, $store, 'products.view'));
        $this->assertTrue($this->permissionService->can($platform, $store, 'products.view'));

        // Disabled capability (service.repair_jobs) cannot be bypassed even by Store Owner or Platform Owner!
        $this->assertFalse($this->permissionService->can($owner, $store, 'repair_jobs.view'));
        $this->assertFalse($this->permissionService->can($platform, $store, 'repair_jobs.view'));
    }

    public function test_manager_privilege_ceiling_and_last_owner_invariants(): void
    {
        $store = Store::create([
            'name' => 'Ceiling Store',
            'slug' => 'ceiling-store',
        ]);

        $owner = User::create([
            'name' => 'Sole Owner',
            'phone' => '09100100100',
            'password' => bcrypt('secret'),
        ]);
        $owner->stores()->attach($store->id, ['role' => 'store_owner', 'status' => 'active']);

        $managerRole = StaffRole::create([
            'store_id' => $store->id,
            'name' => 'Manager Role',
            'slug' => 'manager-role',
            'permissions' => ['staff.edit', 'products.view', 'pos_sales.view'],
            'is_active' => true,
        ]);

        $manager = User::create([
            'name' => 'Shop Manager',
            'phone' => '09200200200',
            'password' => bcrypt('secret'),
        ]);
        $manager->stores()->attach($store->id, [
            'role' => 'store_manager',
            'status' => 'active',
            'staff_role_id' => $managerRole->id,
        ]);

        $cashier = User::create([
            'name' => 'Cashier User',
            'phone' => '09300300300',
            'password' => bcrypt('secret'),
        ]);
        $cashier->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        // 1. Manager can manage regular staff
        $this->assertTrue($this->permissionService->canManageStaffPermissions($manager, $store, $cashier));

        // 2. Manager cannot modify Store Owner
        $this->assertFalse($this->permissionService->canManageStaffPermissions($manager, $store, $owner));

        // 3. Manager cannot modify themself
        $this->assertFalse($this->permissionService->canManageStaffPermissions($manager, $store, $manager));

        // 4. Manager privilege ceiling: cannot grant permissions they do not hold
        $this->assertTrue($this->permissionService->canAssignPermissions($manager, $store, ['products.view']));
        $this->assertFalse($this->permissionService->canAssignPermissions($manager, $store, ['stock_ledger.delete']));

        // 5. Manager cannot grant protected permissions (settings.manage, staff_roles.manage)
        $this->assertFalse($this->permissionService->canAssignPermissions($manager, $store, ['settings.manage']));

        // 6. Last owner invariant
        $this->assertTrue($this->permissionService->isLastStoreOwner($store, $owner));
    }

    public function test_check_store_channel_middleware(): void
    {
        $store = Store::create([
            'name' => 'Channel MW Store',
            'slug' => 'channel-mw-store',
            'operation_mode' => BusinessProfile::MODE_POS_ONLY,
        ]);

        $context = app(StoreContext::class);
        $context->setStore($store);

        $middleware = app(CheckStoreChannel::class);

        // POS channel is active -> passes
        $request = Request::create('/store/channel-mw-store/pos', 'GET');
        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        }, Store::CHANNEL_POS);
        $this->assertEquals('OK', $response->getContent());

        // Online store channel is inactive -> 403
        $this->expectException(HttpException::class);
        $middleware->handle($request, function ($req) {
            return response('OK');
        }, Store::CHANNEL_ONLINE_STORE);
    }

    public function test_check_store_permission_middleware(): void
    {
        $store = Store::create([
            'name' => 'Permission MW Store',
            'slug' => 'perm-mw-store',
        ]);

        $context = app(StoreContext::class);
        $context->setStore($store);

        $user = User::create([
            'name' => 'Perm User',
            'phone' => '09777888999',
            'password' => bcrypt('secret'),
        ]);
        $user->stores()->attach($store->id, [
            'role' => 'staff',
            'status' => 'active',
            'custom_permissions' => json_encode(['products.view']),
        ]);

        $middleware = app(CheckStorePermission::class);

        // Allowed permission -> passes
        $request = Request::create('/store/perm-mw-store/admin/products', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        }, 'products.view');
        $this->assertEquals('OK', $response->getContent());

        // Denied permission -> 403
        $this->expectException(HttpException::class);
        $middleware->handle($request, function ($req) {
            return response('OK');
        }, 'products.delete');
    }
}
