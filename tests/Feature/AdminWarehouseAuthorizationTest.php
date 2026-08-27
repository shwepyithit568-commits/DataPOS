<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\POS\Models\Branch;
use App\POS\Models\Warehouse;
use App\POS\Services\StoreLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWarehouseAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

    private function makeStore(string $slug = 'store-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function makeUser(string $phone, string $role = 'customer'): User
    {
        return User::create([
            'name' => 'Tester',
            'phone' => $phone,
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
    }

    private function attach(User $user, Store $store, string $role, string $status = 'active'): void
    {
        $user->stores()->attach($store->id, ['role' => $role, 'status' => $status]);
    }

    private function defaultLocations(Store $store): array
    {
        return app(StoreLocationService::class)->ensureDefaults($store);
    }

    /* ------------------------------------------------------------------ */
    /*  Index access control                                               */
    /* ------------------------------------------------------------------ */

    public function test_manager_of_store_can_view_warehouses(): void
    {
        $store = $this->makeStore();
        $manager = $this->makeUser('09111111111');
        $this->attach($manager, $store, 'store_manager');

        $this->actingAs($manager)
            ->get("/store/{$store->slug}/admin/warehouses")
            ->assertOk();
    }

    public function test_staff_of_store_can_view_warehouses(): void
    {
        $store = $this->makeStore();
        $staff = $this->makeUser('09222222222');
        $this->attach($staff, $store, 'staff');

        $this->actingAs($staff)
            ->get("/store/{$store->slug}/admin/warehouses")
            ->assertOk();
    }

    public function test_unassigned_customer_is_blocked_from_warehouses(): void
    {
        $store = $this->makeStore();
        $customer = $this->makeUser('09333333333', 'customer');

        $this->actingAs($customer)
            ->get("/store/{$store->slug}/admin/warehouses")
            ->assertForbidden();
    }

    public function test_manager_of_another_store_is_blocked_from_warehouses(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $managerB = $this->makeUser('09444444444');
        $this->attach($managerB, $storeB, 'store_manager');

        // manager of store B must not reach store A's warehouse management.
        $this->actingAs($managerB)
            ->get("/store/{$storeA->slug}/admin/warehouses")
            ->assertForbidden();
    }

    /* ------------------------------------------------------------------ */
    /*  Create — store/branch scoping                                      */
    /* ------------------------------------------------------------------ */

    public function test_manager_can_create_warehouse_with_own_branch(): void
    {
        $store = $this->makeStore();
        $manager = $this->makeUser('09555555555');
        $this->attach($manager, $store, 'store_manager');
        $this->defaultLocations($store);
        $otherBranch = Branch::create(['store_id' => $store->id, 'name' => 'Other Branch', 'code' => 'OB']);

        $this->actingAs($manager)
            ->from("/store/{$store->slug}/admin/warehouses")
            ->post("/store/{$store->slug}/admin/warehouses", [
                'name' => 'Store Front',
                'code' => 'SF',
                'branch_id' => $otherBranch->id,
            ])
            ->assertRedirect("/store/{$store->slug}/admin/warehouses");

        $this->assertDatabaseHas('warehouses', [
            'store_id' => $store->id,
            'branch_id' => $otherBranch->id,
            'name' => 'Store Front',
        ]);
    }

    public function test_creating_warehouse_with_another_stores_branch_is_rejected(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $manager = $this->makeUser('60000000006');
        $this->attach($manager, $storeA, 'store_manager');
        $this->defaultLocations($storeA);
        $storeB->branches()->create(['name' => 'Store B Branch', 'code' => 'SBB']);

        $this->actingAs($manager)
            ->from("/store/{$storeA->slug}/admin/warehouses")
            ->post("/store/{$storeA->slug}/admin/warehouses", [
                'name' => 'Leaky',
                'branch_id' => Branch::where('store_id', $storeB->id)->first()->id,
            ])
            ->assertSessionHasErrors('branch_id');

        $this->assertDatabaseMissing('warehouses', ['name' => 'Leaky']);
    }

    /* ------------------------------------------------------------------ */
    /*  Update / destroy — cross-store guard                               */
    /* ------------------------------------------------------------------ */

    public function test_manager_cannot_update_other_stores_warehouse(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $managerA = $this->makeUser('09600000001');
        $this->attach($managerA, $storeA, 'store_manager');
        $this->defaultLocations($storeB);

        $warehouseB = Warehouse::where('store_id', $storeB->id)->firstOrFail();

        $this->actingAs($managerA)
            ->put("/store/{$storeA->slug}/admin/warehouses/{$warehouseB->id}", [
                'name' => 'Hijacked',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('warehouses', ['id' => $warehouseB->id, 'name' => $warehouseB->name]);
    }

    public function test_manager_cannot_delete_other_stores_warehouse(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $managerA = $this->makeUser('09600000002');
        $this->attach($managerA, $storeA, 'store_manager');
        $this->defaultLocations($storeB);

        $warehouseB = Warehouse::where('store_id', $storeB->id)->firstOrFail();

        $this->actingAs($managerA)
            ->delete("/store/{$storeA->slug}/admin/warehouses/{$warehouseB->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('warehouses', ['id' => $warehouseB->id]);
    }

    public function test_manager_can_search_and_filter_warehouses_with_ui_metrics(): void
    {
        $store = $this->makeStore();
        $manager = $this->makeUser('09700000001');
        $this->attach($manager, $store, 'store_manager');

        Warehouse::create([
            'store_id' => $store->id,
            'name' => 'Main North Warehouse',
            'code' => 'WH-NORTH',
            'is_active' => true,
        ]);

        Warehouse::create([
            'store_id' => $store->id,
            'name' => 'South Depot Location',
            'code' => 'WH-SOUTH',
            'is_active' => false,
        ]);

        // Search test
        $responseSearch = $this->actingAs($manager)
            ->get("/store/{$store->slug}/admin/warehouses?search=North");
        $responseSearch->assertOk();
        $responseSearch->assertSee('Main North Warehouse');
        $responseSearch->assertDontSee('South Depot Location');

        // Status filter test
        $responseActive = $this->actingAs($manager)
            ->get("/store/{$store->slug}/admin/warehouses?status=active");
        $responseActive->assertOk();
        $responseActive->assertSee('Main North Warehouse');
        $responseActive->assertDontSee('South Depot Location');
    }
}