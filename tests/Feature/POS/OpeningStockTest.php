<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\OpeningStockRequest;
use App\POS\Services\InventoryService;
use App\POS\Services\OpeningStockService;
use App\POS\Services\StoreLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OpeningStockTest extends TestCase
{
    use RefreshDatabase;

    private OpeningStockService $openingStock;

    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openingStock = app(OpeningStockService::class);
        $this->inventory = app(InventoryService::class);
    }

    private function makeStore(string $slug = 'shop-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function user(Store $store, string $role, string $name = 'User'): User
    {
        $user = User::create([
            'name' => $name . ' ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    private function staff(Store $store): User
    {
        return $this->user($store, 'staff', 'Cashier');
    }

    private function manager(Store $store): User
    {
        return $this->user($store, 'store_manager', 'Manager');
    }

    private function makeProduct(Store $store, array $overrides = []): Product
    {
        $name = $overrides['name'] ?? 'Phone ' . Str::random(3);

        return Product::create(array_merge([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(3)),
            'retail_price' => 10000,
            'wholesale_price' => 9000,
        ], $overrides));
    }

    /* ------------------------------------------------------------------ */
    /*  Create (pending)                                                   */
    /* ------------------------------------------------------------------ */

    public function test_create_makes_pending_request_without_ledger_impact(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);

        $request = $this->openingStock->create(
            $store,
            [['product_id' => $product->id, 'quantity' => '20', 'unit_cost' => '7500']],
            'Initial inventory',
            $cashier,
        );

        $this->assertTrue($request->isPending());
        $this->assertStringStartsWith('OSR-', $request->request_number);
        $this->assertSame('20.000', (string) $request->total_quantity);
        $this->assertSame('150000.00', (string) $request->total_cost);
        $this->assertSame(1, $request->items->count());

        // No ledger movement yet — the request itself has no stock impact.
        $this->assertSame('0.000', $this->inventory->totalOnHand($store->id, $product->id));
        $this->assertSame(0, \App\POS\Models\InventoryMovement::count());
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'opening_stock_submitted',
            'entity_type' => 'opening_stock_request',
            'entity_id' => $request->id,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Approve                                                            */
    /* ------------------------------------------------------------------ */

    public function test_approve_posts_movements_and_sets_weighted_average(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $product = $this->makeProduct($store);

        $request = $this->openingStock->create(
            $store,
            [['product_id' => $product->id, 'quantity' => '20', 'unit_cost' => '7500']],
            null,
            $cashier,
        );

        $approved = $this->openingStock->approve($store, $request, $manager, 'Looks good');

        $this->assertTrue($approved->isApproved());
        $this->assertSame($manager->id, $approved->reviewed_by);
        $this->assertNotNull($approved->approved_at);
        $this->assertSame('20.000', $this->inventory->totalOnHand($store->id, $product->id));

        $this->assertDatabaseHas('inventory_movements', [
            'store_id' => $store->id,
            'source_type' => 'opening_stock_request',
            'source_id' => $request->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => '20',
            'unit_cost' => '7500.0000',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'opening_stock_approved',
            'entity_id' => $request->id,
        ]);

        // Weighted average = the opening cost (first balance row).
        $warehouse = app(StoreLocationService::class)->defaultWarehouse($store);
        $balance = \App\POS\Models\InventoryBalance::query()
            ->where('store_id', $store->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->firstOrFail();
        $this->assertSame('7500.0000', (string) $balance->unit_cost_avg);
    }

    public function test_approve_is_blocked_after_approval_or_rejection(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $product = $this->makeProduct($store);

        $request = $this->openingStock->create($store, [['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '1000']], null, $cashier);
        $this->openingStock->approve($store, $request, $manager);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('already approved');

        $this->openingStock->approve($store, $request->fresh(), $manager);
        $this->assertSame('5.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_reject_marks_rejected_and_never_posts(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $product = $this->makeProduct($store);

        $request = $this->openingStock->create($store, [['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '1000']], null, $cashier);
        $rejected = $this->openingStock->reject($store, $request, $manager, 'Wrong cost');

        $this->assertTrue($rejected->isRejected());
        $this->assertSame('Wrong cost', $rejected->review_notes);
        $this->assertSame(0, \App\POS\Models\InventoryMovement::count());
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'opening_stock_rejected',
            'entity_id' => $request->id,
        ]);
    }

    public function test_cross_store_approval_is_blocked(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $cashierA = $this->staff($storeA);
        $managerB = $this->manager($storeB);
        $productA = $this->makeProduct($storeA);

        $request = $this->openingStock->create($storeA, [['product_id' => $productA->id, 'quantity' => '5', 'unit_cost' => '1000']], null, $cashierA);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('does not belong');

        $this->openingStock->approve($storeB, $request, $managerB);
    }

    public function test_validation_blocks_empty_and_cross_store_lines(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        try {
            $this->openingStock->create($store, [], null, $cashier);
            $this->fail('Expected empty-items exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('at least one line', $e->getMessage());
        }

        $storeB = $this->makeStore('store-b');
        $productB = $this->makeProduct($storeB);

        try {
            $this->openingStock->create($store, [['product_id' => $productB->id, 'quantity' => '5', 'unit_cost' => '1000']], null, $cashier);
            $this->fail('Expected cross-store exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('does not belong', $e->getMessage());
        }

        $this->assertSame(0, OpeningStockRequest::count());
    }

    /* ------------------------------------------------------------------ */
    /*  HTTP + authorization                                               */
    /* ------------------------------------------------------------------ */

    public function test_page_renders_and_staff_can_submit_but_not_approve(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);

        $this->actingAs($cashier)
            ->get("/store/{$store->slug}/pos/opening-stock")
            ->assertOk()
            ->assertSee(__('messages.opening_stock_subtitle'));

        $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/opening-stock", [
                'items' => [['product_id' => $product->id, 'quantity' => '3', 'unit_cost' => '2000']],
            ])
            ->assertRedirect();

        /** @var OpeningStockRequest $request */
        $request = OpeningStockRequest::firstOrFail();
        $this->assertTrue($request->isPending());

        // Staff cannot approve — manager-only route middleware.
        $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/opening-stock/{$request->id}/approve")
            ->assertForbidden();

        $this->assertTrue($request->fresh()->isPending());
        $this->assertSame('0.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_manager_approves_via_http(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $product = $this->makeProduct($store);

        $request = $this->openingStock->create($store, [['product_id' => $product->id, 'quantity' => '8', 'unit_cost' => '3000']], null, $cashier);

        $this->actingAs($manager)
            ->post("/store/{$store->slug}/pos/opening-stock/{$request->id}/approve", ['review_notes' => 'OK'])
            ->assertRedirect();

        $this->assertTrue($request->fresh()->isApproved());
        $this->assertSame('8.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_non_staff_cannot_submit_opening_stock(): void
    {
        $store = $this->makeStore();
        $outsider = User::create([
            'name' => 'Outsider',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->actingAs($outsider)
            ->post("/store/{$store->slug}/pos/opening-stock", [
                'items' => [['product_id' => 1, 'quantity' => '1', 'unit_cost' => '100']],
            ])
            ->assertForbidden();

        $this->assertSame(0, OpeningStockRequest::count());
    }

    public function test_opening_stock_csv_export(): void
    {
        $store = $this->makeStore();
        $manager = $this->manager($store);
        $product = $this->makeProduct($store);

        $this->openingStock->create($store, [['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '1000']], null, $manager);

        $response = $this->actingAs($manager)
            ->get("/store/{$store->slug}/pos/opening-stock/export?format=csv");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_opening_stock_xlsx_export(): void
    {
        $store = $this->makeStore();
        $manager = $this->manager($store);
        $product = $this->makeProduct($store);

        $this->openingStock->create($store, [['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '1000']], null, $manager);

        $response = $this->actingAs($manager)
            ->get("/store/{$store->slug}/pos/opening-stock/export?format=xlsx");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
