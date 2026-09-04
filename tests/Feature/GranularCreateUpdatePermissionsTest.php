<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\User;
use App\Services\StorePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GranularCreateUpdatePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected StorePermissionService $permissionService;
    protected Store $store;
    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionService = app(StorePermissionService::class);
        StorePermissionService::invalidateCache();

        $this->store = Store::create([
            'name' => 'Main POS Store',
            'slug' => 'main-pos-store',
        ]);

        $this->owner = User::create([
            'name' => 'Store Boss',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
        ]);
        $this->owner->stores()->attach($this->store->id, [
            'role' => 'store_owner',
            'status' => 'active',
        ]);
    }

    /**
     * Scenario 1 & 4: View-only user gets 403 on create, update, and delete; DB is not mutated.
     */
    public function test_view_only_user_cannot_create_update_or_delete_products_and_db_is_not_mutated(): void
    {
        $viewOnlyRole = StaffRole::create([
            'store_id' => $this->store->id,
            'name' => 'Viewer Only',
            'slug' => 'viewer-only',
            'permissions' => ['products.view'],
            'is_system' => false,
            'is_active' => true,
        ]);

        $staffUser = User::create([
            'name' => 'Staff Viewer',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
        ]);
        $staffUser->stores()->attach($this->store->id, [
            'role' => 'staff',
            'status' => 'active',
            'staff_role_id' => $viewOnlyRole->id,
        ]);

        // 1. Attempt Create (POST) -> 403 Forbidden
        $createPayload = [
            'name' => 'Unauthorized New Product',
            'retail_price' => 5000,
            'wholesale_price' => 4000,
            'sku' => 'UNAUTH-001',
            'is_featured' => 0,
        ];

        $response = $this->actingAs($staffUser)
            ->post("/store/{$this->store->slug}/admin/products", $createPayload);

        $response->assertStatus(403);
        $this->assertDatabaseCount('products', 0);

        // Seed an existing product into DB
        $existingProduct = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Original Product Name',
            'slug' => 'original-product-name',
            'retail_price' => 10000,
            'wholesale_price' => 8000,
            'sku' => 'ORIG-001',
            'stock_status' => 'in_stock',
            'is_featured' => false,
        ]);

        // 2. Attempt Update (PUT) -> 403 Forbidden
        $updatePayload = [
            'name' => 'Tampered Product Name',
            'retail_price' => 99999,
            'wholesale_price' => 88888,
            'sku' => 'ORIG-001',
            'is_featured' => 0,
        ];

        $response = $this->actingAs($staffUser)
            ->put("/store/{$this->store->slug}/admin/products/{$existingProduct->id}", $updatePayload);

        $response->assertStatus(403);
        $this->assertDatabaseHas('products', [
            'id' => $existingProduct->id,
            'name' => 'Original Product Name',
            'retail_price' => 10000,
        ]);

        // 3. Attempt Delete (DELETE) -> 403 Forbidden
        $response = $this->actingAs($staffUser)
            ->delete("/store/{$this->store->slug}/admin/products/{$existingProduct->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('products', [
            'id' => $existingProduct->id,
        ]);
    }

    /**
     * Scenario 5: User with products.create can create a product.
     */
    public function test_user_with_create_permission_can_create_product(): void
    {
        $creatorRole = StaffRole::create([
            'store_id' => $this->store->id,
            'name' => 'Product Creator',
            'slug' => 'product-creator',
            'permissions' => ['products.view', 'products.create'],
            'is_system' => false,
            'is_active' => true,
        ]);

        $creatorUser = User::create([
            'name' => 'Product Author',
            'phone' => '09333333333',
            'password' => bcrypt('password'),
        ]);
        $creatorUser->stores()->attach($this->store->id, [
            'role' => 'staff',
            'status' => 'active',
            'staff_role_id' => $creatorRole->id,
        ]);

        $payload = [
            'name' => 'Legit Brand New Product',
            'retail_price' => 15000,
            'wholesale_price' => 12000,
            'sku' => 'LEGIT-001',
            'is_featured' => 0,
        ];

        $response = $this->actingAs($creatorUser)
            ->post("/store/{$this->store->slug}/admin/products", $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'name' => 'Legit Brand New Product',
            'store_id' => $this->store->id,
            'retail_price' => 15000,
        ]);
    }

    /**
     * Scenario 6: User with products.update can update product.
     */
    public function test_user_with_update_permission_can_update_product(): void
    {
        $editorRole = StaffRole::create([
            'store_id' => $this->store->id,
            'name' => 'Product Editor',
            'slug' => 'product-editor',
            'permissions' => ['products.view', 'products.update'],
            'is_system' => false,
            'is_active' => true,
        ]);

        $editorUser = User::create([
            'name' => 'Product Editor User',
            'phone' => '09444444444',
            'password' => bcrypt('password'),
        ]);
        $editorUser->stores()->attach($this->store->id, [
            'role' => 'staff',
            'status' => 'active',
            'staff_role_id' => $editorRole->id,
        ]);

        $product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Initial Name',
            'slug' => 'initial-name',
            'retail_price' => 5000,
            'wholesale_price' => 4000,
            'sku' => 'EDIT-001',
            'stock_status' => 'in_stock',
            'is_featured' => false,
        ]);

        $payload = [
            'name' => 'Successfully Updated Name',
            'retail_price' => 6500,
            'wholesale_price' => 5000,
            'sku' => 'EDIT-001',
            'is_featured' => 0,
        ];

        $response = $this->actingAs($editorUser)
            ->put("/store/{$this->store->slug}/admin/products/{$product->id}", $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Successfully Updated Name',
            'retail_price' => 6500,
        ]);
    }

    /**
     * Scenario 3: StorePermissionService enforces parent-view dependency.
     */
    public function test_parent_view_dependency_in_store_permission_service(): void
    {
        $user = User::create([
            'name' => 'Isolated Action User',
            'phone' => '09555555555',
            'password' => bcrypt('password'),
        ]);

        // Grants create and update, but deliberately omits .view
        $user->stores()->attach($this->store->id, [
            'role' => 'staff',
            'status' => 'active',
            'custom_permissions' => json_encode(['products.create', 'products.update', 'products.delete']),
        ]);

        // Without products.view, effectivePermissions must strip non-view actions
        $this->assertFalse($this->permissionService->can($user, $this->store, 'products.create'));
        $this->assertFalse($this->permissionService->can($user, $this->store, 'products.update'));
        $this->assertFalse($this->permissionService->can($user, $this->store, 'products.delete'));

        // Add products.view
        DB::table('store_user')
            ->where('store_id', $this->store->id)
            ->where('user_id', $user->id)
            ->update(['custom_permissions' => json_encode(['products.view', 'products.create', 'products.update', 'products.delete'])]);

        StorePermissionService::invalidateCache($this->store->id, $user->id);

        $this->assertTrue($this->permissionService->can($user, $this->store, 'products.create'));
        $this->assertTrue($this->permissionService->can($user, $this->store, 'products.update'));
        $this->assertTrue($this->permissionService->can($user, $this->store, 'products.delete'));
    }

    /**
     * Scenario 5 & 8: StaffRoleController rejects action permissions without parent-view.
     */
    public function test_staff_role_controller_rejects_missing_parent_view_and_persists_valid_matrix(): void
    {
        // 1. Submit action permissions without view -> Rejected with 422 / session errors
        $invalidPayload = [
            'name' => 'Invalid Actions Only Role',
            'description' => 'Should fail due to missing view',
            'color' => '#0284c7',
            'permissions' => ['products.create', 'products.update'],
        ];

        $response = $this->actingAs($this->owner)
            ->post("/store/{$this->store->slug}/admin/security/roles", $invalidPayload);

        $response->assertSessionHasErrors('permissions');
        $this->assertDatabaseMissing('staff_roles', ['name' => 'Invalid Actions Only Role']);

        // 2. Submit with corresponding view -> Success and persisted in DB
        $validPayload = [
            'name' => 'Complete Products Manager',
            'description' => 'Proper role with view, create, update, delete',
            'color' => '#10b981',
            'permissions' => ['products.view', 'products.create', 'products.update', 'products.delete'],
        ];

        $response = $this->actingAs($this->owner)
            ->post("/store/{$this->store->slug}/admin/security/roles", $validPayload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $createdRole = StaffRole::where('store_id', $this->store->id)
            ->where('name', 'Complete Products Manager')
            ->first();

        $this->assertNotNull($createdRole);
        $this->assertEquals(['products.view', 'products.create', 'products.update', 'products.delete'], $createdRole->permissions);

        // 3. Update existing role without view -> Rejected
        $updateInvalid = [
            'name' => 'Complete Products Manager',
            'permissions' => ['products.delete'], // missing view
        ];

        $response = $this->actingAs($this->owner)
            ->put("/store/{$this->store->slug}/admin/security/roles/{$createdRole->id}", $updateInvalid);

        $response->assertSessionHasErrors('permissions');
    }

    /**
     * Scenario 7 & 9: staff:migrate-permissions handles dry-run, live migration, and rollback.
     */
    public function test_staff_migrate_permissions_command_dry_run_and_rollback(): void
    {
        // 1. Seed a legacy role with .edit
        $legacyRole = StaffRole::create([
            'store_id' => $this->store->id,
            'name' => 'Legacy Role',
            'slug' => 'legacy-role',
            'permissions' => ['products.view', 'products.edit'],
            'is_system' => false,
            'is_active' => true,
        ]);

        // 2. Seed a user with custom_permissions containing .edit
        $customUser = User::create([
            'name' => 'Custom User',
            'phone' => '09666666666',
            'password' => bcrypt('password'),
        ]);
        $customUser->stores()->attach($this->store->id, [
            'role' => 'staff',
            'status' => 'active',
            'custom_permissions' => json_encode(['pos_sales.view', 'pos_sales.edit']),
        ]);

        // Test Dry-Run: DB must NOT change
        Artisan::call('staff:migrate-permissions', ['--dry-run' => true]);

        $legacyRole->refresh();
        $this->assertEquals(['products.view', 'products.edit'], $legacyRole->permissions);

        $rawCustom = DB::table('store_user')
            ->where('store_id', $this->store->id)
            ->where('user_id', $customUser->id)
            ->value('custom_permissions');
        $this->assertEquals(['pos_sales.view', 'pos_sales.edit'], json_decode($rawCustom, true));

        // Test Live Run: Both role and custom_permissions are expanded to .create and .update
        Artisan::call('staff:migrate-permissions');

        $legacyRole->refresh();
        $this->assertContains('products.update', $legacyRole->permissions);
        $this->assertContains('products.create', $legacyRole->permissions);

        $rawCustom = DB::table('store_user')
            ->where('store_id', $this->store->id)
            ->where('user_id', $customUser->id)
            ->value('custom_permissions');
        $customArr = json_decode($rawCustom, true);
        $this->assertContains('pos_sales.update', $customArr);
        $this->assertContains('pos_sales.create', $customArr);

        // Test Rollback: Safely restores both role and custom permissions from audit snapshot
        Artisan::call('staff:migrate-permissions', ['--rollback' => true]);

        $legacyRole->refresh();
        $this->assertEquals(['products.view', 'products.edit'], $legacyRole->permissions);

        $rawCustom = DB::table('store_user')
            ->where('store_id', $this->store->id)
            ->where('user_id', $customUser->id)
            ->value('custom_permissions');
        $this->assertEquals(['pos_sales.view', 'pos_sales.edit'], json_decode($rawCustom, true));
    }

    /**
     * Scenario 10: Fail-closed migration down() verification.
     */
    public function test_fail_closed_migration_down_verifies_audit_snapshot(): void
    {
        DB::table('stores')->where('id', $this->store->id)->update([
            'sales_channels' => json_encode(['pos' => true, 'online' => false]),
        ]);

        $this->assertTrue(Schema::hasColumn('stores', 'sales_channels'));

        $migration = require database_path('migrations/2026_09_05_000005_add_sales_channels_to_stores_table.php');
        $migration->down();

        // Check that a snapshot file was written and verified in storage/app/backups
        $backupDir = storage_path('app/backups');
        $files = glob($backupDir . '/sales_channels_rollback_snapshot_*.json');
        $this->assertNotEmpty($files);
        $latestSnapshot = end($files);
        $content = json_decode(file_get_contents($latestSnapshot), true);
        $this->assertIsArray($content);
        $this->assertNotEmpty($content);
        $this->assertEquals($this->store->id, $content[0]['id']);

        // And verify column was dropped
        $this->assertFalse(Schema::hasColumn('stores', 'sales_channels'));

        // Clean up created test backup file
        if ($latestSnapshot && file_exists($latestSnapshot)) {
            @unlink($latestSnapshot);
        }
    }
}
