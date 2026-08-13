<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\Branch;
use App\POS\Models\InventoryBalance;
use App\POS\Models\Warehouse;
use App\POS\Services\InventoryService;
use App\POS\Services\StoreLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultLocationsTest extends TestCase
{
    use RefreshDatabase;

    private StoreLocationService $locations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locations = app(StoreLocationService::class);
    }

    private function makeStore(string $slug = 'store-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function makeProduct(Store $store, string $slug = 'product-a'): Product
    {
        return Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-' . strtoupper($slug),
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'retail_price' => 10000,
            'wholesale_price' => 8000,
            'stock_status' => 'out_of_stock',
            'is_featured' => false,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Auto-creation                                                      */
    /* ------------------------------------------------------------------ */

    public function test_ensure_defaults_creates_branch_and_warehouse(): void
    {
        $store = $this->makeStore();

        $result = $this->locations->ensureDefaults($store);

        $this->assertTrue($result['branch']->is_default);
        $this->assertTrue($result['warehouse']->is_default);
        $this->assertSame($store->id, $result['branch']->store_id);
        $this->assertSame($store->id, $result['warehouse']->store_id);
        $this->assertSame($result['branch']->id, $result['warehouse']->branch_id);

        // Store relations resolve.
        $this->assertSame($result['branch']->id, $store->fresh()->defaultBranch->id);
        $this->assertSame($result['warehouse']->id, $store->fresh()->defaultWarehouse->id);
    }

    public function test_ensure_defaults_is_idempotent(): void
    {
        $store = $this->makeStore();

        $first = $this->locations->ensureDefaults($store);
        $second = $this->locations->ensureDefaults($store);
        $third = $this->locations->defaultWarehouse($store);

        $this->assertSame($first['branch']->id, $second['branch']->id);
        $this->assertSame($first['warehouse']->id, $second['warehouse']->id);
        $this->assertSame($first['warehouse']->id, $third->id);
        $this->assertSame(1, Branch::query()->count());
        $this->assertSame(1, Warehouse::query()->count());
    }

    public function test_store_creation_via_admin_controller_creates_default_locations(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'phone' => '09999999999',
            'password' => bcrypt('password'),
            'role' => 'platform_owner',
        ]);

        $response = $this->actingAs($owner)->post('/admin/stores', [
            'name' => 'New Shop',
            'slug' => 'new-shop',
            'default_language' => 'my',
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $store = Store::where('slug', 'new-shop')->firstOrFail();
        $this->assertNotNull($store->defaultBranch);
        $this->assertNotNull($store->defaultWarehouse);
        $this->assertSame($store->defaultBranch->id, $store->defaultWarehouse->branch_id);
    }

    public function test_production_create_store_command_creates_default_locations(): void
    {
        $this->artisan('production:create-store', [
            '--name' => 'CLI Shop',
            '--slug' => 'cli-shop',
        ])->assertExitCode(0);

        $store = Store::where('slug', 'cli-shop')->firstOrFail();
        $this->assertNotNull($store->defaultBranch);
        $this->assertNotNull($store->defaultWarehouse);
    }

    /* ------------------------------------------------------------------ */
    /*  Ledger warehouse resolution                                        */
    /* ------------------------------------------------------------------ */

    public function test_ledger_post_without_warehouse_uses_default_warehouse(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);
        $service = app(InventoryService::class);

        $movement = $service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 25,
        ]);

        $this->assertSame($store->defaultWarehouse->id, $movement->warehouse_id);
        $this->assertSame(
            '25.000',
            (string) $service->balanceFor($store->id, $product->id, null, $store->defaultWarehouse->id)->quantity_on_hand
        );
    }

    public function test_per_warehouse_balances_are_tracked_separately(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);
        $service = app(InventoryService::class);

        $this->locations->ensureDefaults($store);
        $main = $store->defaultWarehouse;

        $second = Warehouse::create([
            'store_id' => $store->id,
            'branch_id' => $store->defaultBranch->id,
            'name' => 'Second Warehouse',
            'code' => 'W2',
            'is_default' => false,
        ]);

        $service->postMovement([
            'store_id' => $store->id,
            'warehouse_id' => $main->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'client_transaction_id' => 'w1-open',
        ]);
        $service->postMovement([
            'store_id' => $store->id,
            'warehouse_id' => $second->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 4,
            'client_transaction_id' => 'w2-open',
        ]);

        $this->assertSame('10.000', (string) $service->balanceFor($store->id, $product->id, null, $main->id)->quantity_on_hand);
        $this->assertSame('4.000', (string) $service->balanceFor($store->id, $product->id, null, $second->id)->quantity_on_hand);
        $this->assertSame('14.000', $service->totalOnHand($store->id, $product->id));
        $this->assertSame(2, InventoryBalance::query()->count());
    }

    public function test_cross_store_warehouse_is_blocked(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $productA = $this->makeProduct($storeA);
        $service = app(InventoryService::class);

        $warehouseB = $this->locations->ensureDefaults($storeB)['warehouse'];

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('cross-store posting');

        $service->postMovement([
            'store_id' => $storeA->id,
            'warehouse_id' => $warehouseB->id,
            'product_id' => $productA->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 3,
        ]);
    }

    public function test_cross_store_branch_is_blocked(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $productA = $this->makeProduct($storeA);
        $service = app(InventoryService::class);

        $branchB = $this->locations->ensureDefaults($storeB)['branch'];

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('cross-store posting');

        $service->postMovement([
            'store_id' => $storeA->id,
            'branch_id' => $branchB->id,
            'product_id' => $productA->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 3,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Backfill command                                                   */
    /* ------------------------------------------------------------------ */

    public function test_ensure_locations_command_backfills_existing_stores(): void
    {
        // Store created BEFORE the locations feature — no default locations yet.
        $store = $this->makeStore('legacy-store');

        $this->assertNull($store->defaultBranch);

        $this->artisan('inventory:ensure-locations', ['--store' => $store->slug])
            ->expectsOutputToContain('✅')
            ->assertExitCode(0);

        $this->assertNotNull($store->fresh()->defaultBranch);
        $this->assertNotNull($store->fresh()->defaultWarehouse);
    }

    public function test_ensure_locations_command_targets_all_stores_by_default(): void
    {
        $this->makeStore('store-a');
        $this->makeStore('store-b');
        $this->makeStore('store-c');

        $this->artisan('inventory:ensure-locations')->assertExitCode(0);

        $this->assertSame(3, Branch::query()->count());
        $this->assertSame(3, Warehouse::query()->count());
    }
}
