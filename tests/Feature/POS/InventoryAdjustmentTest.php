<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\InventoryAdjustment;
use App\POS\Services\InventoryAdjustmentService;
use App\POS\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private InventoryAdjustmentService $adjustments;

    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adjustments = app(InventoryAdjustmentService::class);
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

    private function seedStock(Store $store, Product $product, string $qty = '10', string $cost = '8000'): void
    {
        $this->inventory->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => $qty,
            'unit_cost' => $cost,
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed:' . Str::uuid(),
            'occurred_at' => now(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Create (pending)                                                   */
    /* ------------------------------------------------------------------ */

    public function test_create_makes_pending_request_without_ledger_impact(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '10');

        $request = $this->adjustments->create(
            $store,
            [['product_id' => $product->id, 'quantity' => '-2', 'reason' => 'Damaged in transit']],
            'End-of-day stock take',
            $cashier,
        );

        $this->assertTrue($request->isPending());
        $this->assertStringStartsWith('ADJ-', $request->adjustment_number);
        $this->assertSame('-2.000', (string) $request->total_quantity);
        $this->assertSame('Damaged in transit', $request->items->first()->reason);

        // No ledger impact until approval.
        $this->assertSame('10.000', $this->inventory->totalOnHand($store->id, $product->id));
        $this->assertSame(1, \App\POS\Models\InventoryMovement::count()); // only the seed
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'inventory_adjustment_submitted',
            'entity_id' => $request->id,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Approve                                                            */
    /* ------------------------------------------------------------------ */

    public function test_approve_posts_in_and_out_movements_and_keeps_average(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '10', '8000');

        $request = $this->adjustments->create(
            $store,
            [
                ['product_id' => $product->id, 'quantity' => '3', 'reason' => 'Found extra stock'],
                ['product_id' => $product->id, 'quantity' => '-2', 'reason' => 'Damaged'],
            ],
            null,
            $cashier,
        );

        $approved = $this->adjustments->approve($store, $request, $manager, 'OK');

        $this->assertTrue($approved->isApproved());
        $this->assertSame($manager->id, $approved->reviewed_by);
        $this->assertSame('11.000', $this->inventory->totalOnHand($store->id, $product->id)); // 10 +3 −2

        // Duplicate product lines MERGE into one net movement per source+product.
        $this->assertDatabaseHas('inventory_movements', [
            'store_id' => $store->id,
            'source_type' => 'inventory_adjustment',
            'source_id' => $request->id,
            'movement_type' => 'adjustment_in',
            'quantity_delta' => '1', // +3 and −2 net to +1
        ]);
        $this->assertSame(
            1,
            \App\POS\Models\InventoryMovement::where('source_type', 'inventory_adjustment')->where('source_id', $request->id)->count()
        );
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'inventory_adjustment_approved',
            'entity_id' => $request->id,
        ]);

        // Adjustments carry the current average and do NOT recalculate it.
        $warehouse = app(\App\POS\Services\StoreLocationService::class)->defaultWarehouse($store);
        $balance = \App\POS\Models\InventoryBalance::query()
            ->where('store_id', $store->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->firstOrFail();
        $this->assertSame('8000.0000', (string) $balance->unit_cost_avg);
    }

    public function test_insufficient_stock_blocks_out_adjustment_without_trace(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '2');

        $request = $this->adjustments->create(
            $store,
            [['product_id' => $product->id, 'quantity' => '-5', 'reason' => 'Stock take short']],
            null,
            $cashier,
        );

        try {
            $this->adjustments->approve($store, $request, $manager);
            $this->fail('Expected insufficient-stock exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('Insufficient stock', $e->getMessage());
        }

        // Request stays pending, no adjustment movements posted, stock unchanged.
        $this->assertTrue($request->fresh()->isPending());
        $this->assertSame('2.000', $this->inventory->totalOnHand($store->id, $product->id));
        $this->assertSame(0, \App\POS\Models\InventoryMovement::where('source_type', 'inventory_adjustment')->count());
    }

    public function test_reason_is_required_and_zero_quantity_is_blocked(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);

        try {
            $this->adjustments->create($store, [['product_id' => $product->id, 'quantity' => '2', 'reason' => '']], null, $cashier);
            $this->fail('Expected reason exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('reason is required', $e->getMessage());
        }

        try {
            $this->adjustments->create($store, [['product_id' => $product->id, 'quantity' => '0', 'reason' => 'Test']], null, $cashier);
            $this->fail('Expected zero-quantity exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('cannot be zero', $e->getMessage());
        }

        $this->assertSame(0, InventoryAdjustment::count());
    }

    public function test_double_approve_and_reject_are_blocked(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');

        $request = $this->adjustments->create($store, [['product_id' => $product->id, 'quantity' => '1', 'reason' => 'Test']], null, $cashier);
        $this->adjustments->approve($store, $request, $manager);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('already approved');

        $this->adjustments->approve($store, $request->fresh(), $manager);
        $this->assertSame('6.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_reject_marks_rejected_and_never_posts(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');

        $request = $this->adjustments->create($store, [['product_id' => $product->id, 'quantity' => '-1', 'reason' => 'Disputed']], null, $cashier);
        $rejected = $this->adjustments->reject($store, $request, $manager, 'Not verified');

        $this->assertTrue($rejected->isRejected());
        $this->assertSame('Not verified', $rejected->review_notes);
        $this->assertSame('5.000', $this->inventory->totalOnHand($store->id, $product->id));
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'inventory_adjustment_rejected',
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

        $request = $this->adjustments->create($storeA, [['product_id' => $productA->id, 'quantity' => '1', 'reason' => 'Test']], null, $cashierA);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('does not belong');

        $this->adjustments->approve($storeB, $request, $managerB);
    }

    /* ------------------------------------------------------------------ */
    /*  HTTP + authorization                                               */
    /* ------------------------------------------------------------------ */

    public function test_page_renders_and_staff_can_submit_but_not_approve(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');

        $this->actingAs($cashier)
            ->get("/store/{$store->slug}/pos/adjustments")
            ->assertOk()
            ->assertSee(__('messages.adjustment_subtitle'));

        $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/adjustments", [
                'items' => [['product_id' => $product->id, 'quantity' => '-1', 'reason' => 'Damaged']],
            ])
            ->assertRedirect();

        $request = InventoryAdjustment::firstOrFail();
        $this->assertTrue($request->isPending());

        // Staff cannot approve — manager-only route middleware.
        $this->actingAs($cashier)
            ->post("/store/{$store->slug}/pos/adjustments/{$request->id}/approve")
            ->assertForbidden();

        $this->assertTrue($request->fresh()->isPending());
        $this->assertSame('5.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_manager_approves_via_http(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $manager = $this->manager($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');

        $request = $this->adjustments->create($store, [['product_id' => $product->id, 'quantity' => '2', 'reason' => 'Count found extra']], null, $cashier);

        $this->actingAs($manager)
            ->post("/store/{$store->slug}/pos/adjustments/{$request->id}/approve", ['review_notes' => 'OK'])
            ->assertRedirect();

        $this->assertTrue($request->fresh()->isApproved());
        $this->assertSame('7.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_non_staff_cannot_submit_adjustment(): void
    {
        $store = $this->makeStore();
        $outsider = User::create([
            'name' => 'Outsider',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->actingAs($outsider)
            ->post("/store/{$store->slug}/pos/adjustments", [
                'items' => [['product_id' => 1, 'quantity' => '1', 'reason' => 'Test']],
            ])
            ->assertForbidden();

        $this->assertSame(0, InventoryAdjustment::count());
    }
}
