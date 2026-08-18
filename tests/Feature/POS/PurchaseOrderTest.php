<?php

namespace Tests\Feature\POS;

use App\Models\Supplier;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\PurchaseOrder;
use App\POS\Services\InventoryService;
use App\POS\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseOrderService $service;
    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PurchaseOrderService::class);
        $this->inventory = app(InventoryService::class);
    }

    private function makeStore(string $slug = 'shop-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function staff(Store $store): User
    {
        $user = User::create([
            'name' => 'Staff ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        return $user;
    }

    private function makeProduct(Store $store, array $overrides = []): Product
    {
        $name = $overrides['name'] ?? 'Widget ' . Str::random(3);

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

    private function makeSupplier(Store $store): Supplier
    {
        return Supplier::create([
            'store_id' => $store->id,
            'name' => 'Acme Corp',
            'phone' => '09123456789',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Create                                                             */
    /* ------------------------------------------------------------------ */

    public function test_create_po_as_pending(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create(
            $store,
            [['product_id' => $product->id, 'quantity' => '20', 'unit_cost' => '7500']],
            null,
            'INV-001',
            'Test PO',
            $actor,
        );

        $this->assertTrue($po->isPending());
        $this->assertStringStartsWith('PO-', $po->po_number);
        $this->assertSame('20.000', (string) $po->total_quantity);
        $this->assertSame('150000.00', (string) $po->total_cost);
        $this->assertSame(1, $po->items->count());
        $this->assertSame('INV-001', $po->reference);
        $this->assertSame('Test PO', $po->notes);
        $this->assertSame($actor->id, $po->created_by);

        // Stock must NOT change when creating a PO.
        $this->assertSame('0.000', $this->inventory->totalOnHand($store->id, $product->id));

        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'purchase_order_created',
            'entity_type' => 'purchase_order',
        ]);
    }

    public function test_create_po_with_supplier(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $po = $this->service->create(
            $store,
            [['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '10000']],
            $supplier->id,
            null,
            null,
            $actor,
        );

        $this->assertSame($supplier->id, $po->supplier_id);
    }

    public function test_create_po_merges_duplicate_product_lines(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create(
            $store,
            [
                ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '8000'],
                ['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '8000'],
            ],
        );

        $this->assertSame(1, $po->items->count());
        $this->assertSame('15.000', (string) $po->total_quantity);
    }

    /* ------------------------------------------------------------------ */
    /*  Mark Ordered                                                       */
    /* ------------------------------------------------------------------ */

    public function test_mark_ordered_transitions_pending_to_ordered(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '5000'],
        ], null, null, null, $actor);

        $ordered = $this->service->markOrdered($po, $actor);

        $this->assertTrue($ordered->isOrdered());
        $this->assertNotNull($ordered->ordered_at);
        // Still no stock change.
        $this->assertSame('0.000', $this->inventory->totalOnHand($store->id, $product->id));

        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'purchase_order_ordered',
        ]);
    }

    public function test_mark_ordered_rejects_non_pending(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '5000'],
        ], null, null, null, $actor);

        // Mark ordered first.
        $this->service->markOrdered($po, $actor);

        // Try to mark ordered again.
        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('cannot be ordered');
        $this->service->markOrdered($po->fresh(), $actor);
    }

    /* ------------------------------------------------------------------ */
    /*  Receive (→ stock)                                                   */
    /* ------------------------------------------------------------------ */

    public function test_receive_posts_stock_and_creates_goods_receipt(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '10', '8000');

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '20', 'unit_cost' => '9000'],
        ], null, 'INV-100', null, $actor);

        $this->service->markOrdered($po, $actor);

        $result = $this->service->receive($po, $actor);

        // PO is now received.
        $this->assertTrue($result['po']->isReceived());
        $this->assertNotNull($result['po']->received_at);

        // Goods receipt was created.
        $this->assertNotNull($result['receipt']);
        $this->assertStringStartsWith('GRV-', $result['receipt']->receipt_number);
        $this->assertSame('20.000', (string) $result['receipt']->total_quantity);

        // Stock increased by the PO quantity (10 existing + 20 received = 30).
        $this->assertSame('30.000', $this->inventory->totalOnHand($store->id, $product->id));

        // Weighted average recalc: (10×8000 + 20×9000) / 30 = 8666.6667
        $warehouse = app(\App\POS\Services\StoreLocationService::class)->defaultWarehouse($store);
        $balance = \App\POS\Models\InventoryBalance::query()
            ->where('store_id', $store->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->firstOrFail();
        $this->assertSame('8666.6667', (string) $balance->unit_cost_avg);

        $this->assertDatabaseHas('inventory_movements', [
            'store_id' => $store->id,
            'source_type' => 'goods_receipt',
            'movement_type' => 'purchase_received',
            'quantity_delta' => '20',
            'unit_cost' => '9000.0000',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'purchase_order_received',
        ]);
    }

    public function test_receive_without_existing_stock(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '15', 'unit_cost' => '6000'],
        ]);

        $this->service->markOrdered($po, $actor);
        $result = $this->service->receive($po, $actor);

        $this->assertTrue($result['po']->isReceived());
        $this->assertSame('15.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_receive_rejects_non_ordered(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '5000'],
        ]);

        // Still pending — cannot receive.
        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage("must be in 'ordered' status");
        $this->service->receive($po, $actor);
    }

    public function test_receive_already_received_is_rejected(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '5000'],
        ]);

        $this->service->markOrdered($po, $actor);
        $this->service->receive($po, $actor);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage("must be in 'ordered' status");
        $this->service->receive($po->fresh(), $actor);
    }

    /* ------------------------------------------------------------------ */
    /*  Cancel                                                             */
    /* ------------------------------------------------------------------ */

    public function test_cancel_pending_po(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '5000'],
        ]);

        $cancelled = $this->service->cancel($po, $actor);

        $this->assertTrue($cancelled->isCancelled());
        $this->assertNotNull($cancelled->cancelled_at);
        $this->assertSame('0.000', $this->inventory->totalOnHand($store->id, $product->id));

        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'purchase_order_cancelled',
        ]);
    }

    public function test_cancel_ordered_po(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '5000'],
        ]);

        $this->service->markOrdered($po, $actor);
        $cancelled = $this->service->cancel($po->fresh(), $actor);

        $this->assertTrue($cancelled->isCancelled());
    }

    public function test_cancel_received_po_is_rejected(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '5000'],
        ]);

        $this->service->markOrdered($po, $actor);
        $this->service->receive($po, $actor);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('already received');
        $this->service->cancel($po->fresh(), $actor);
    }

    public function test_cancel_already_cancelled_is_rejected(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '5000'],
        ]);

        $this->service->cancel($po, $actor);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('already cancelled');
        $this->service->cancel($po->fresh(), $actor);
    }

    /* ------------------------------------------------------------------ */
    /*  Full lifecycle                                                     */
    /* ------------------------------------------------------------------ */

    public function test_full_lifecycle_pending_ordered_received(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5', '10000');

        // Create pending.
        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '12000'],
        ]);
        $this->assertTrue($po->isPending());
        $this->assertSame('5.000', $this->inventory->totalOnHand($store->id, $product->id));

        // Mark ordered.
        $this->service->markOrdered($po, $actor);
        $this->assertSame('5.000', $this->inventory->totalOnHand($store->id, $product->id));

        // Receive.
        $result = $this->service->receive($po->fresh(), $actor);
        $this->assertTrue($result['po']->isReceived());
        $this->assertSame('15.000', $this->inventory->totalOnHand($store->id, $product->id));

        // Weighted avg: (5×10000 + 10×12000) / 15 = 11333.3333
        $warehouse = app(\App\POS\Services\StoreLocationService::class)->defaultWarehouse($store);
        $balance = \App\POS\Models\InventoryBalance::query()
            ->where('store_id', $store->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->firstOrFail();
        $this->assertSame('11333.3333', (string) $balance->unit_cost_avg);
    }

    /* ------------------------------------------------------------------ */
    /*  Validation                                                         */
    /* ------------------------------------------------------------------ */

    public function test_empty_items_rejected(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('at least one line');
        $this->service->create($store, [], null, null, null, $actor);
    }

    public function test_zero_quantity_rejected(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('quantity must be greater');
        $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '0', 'unit_cost' => '5000'],
        ], null, null, null, $actor);
    }

    public function test_cross_store_product_rejected(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $actorB = $this->staff($storeB);
        $productA = $this->makeProduct($storeA);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('does not belong');
        $this->service->create($storeB, [
            ['product_id' => $productA->id, 'quantity' => '5', 'unit_cost' => '1000'],
        ], null, null, null, $actorB);
    }

    /* ------------------------------------------------------------------ */
    /*  HTTP + authorization                                               */
    /* ------------------------------------------------------------------ */

    public function test_po_index_page_renders(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);

        $this->actingAs($actor)
            ->get("/store/{$store->slug}/pos/purchases")
            ->assertOk()
            ->assertSee(__('messages.po_list_title'));
    }

    public function test_po_create_page_renders(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);

        $this->actingAs($actor)
            ->get("/store/{$store->slug}/pos/purchases/create")
            ->assertOk()
            ->assertSee(__('messages.po_create_title'));
    }

    public function test_http_create_po_flow(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $this->actingAs($actor)
            ->post("/store/{$store->slug}/pos/purchases", [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '7000'],
                ],
                'reference' => 'SUP-42',
            ])
            ->assertRedirect();

        $po = PurchaseOrder::firstOrFail();
        $this->assertTrue($po->isPending());
        $this->assertSame('SUP-42', $po->reference);
    }

    public function test_http_order_receive_cancel_flow(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        // Create via service.
        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '8', 'unit_cost' => '6000'],
        ], null, null, null, $actor);

        // Mark ordered via HTTP.
        $this->actingAs($actor)
            ->post("/store/{$store->slug}/pos/purchases/{$po->id}/order")
            ->assertRedirect();
        $this->assertTrue($po->fresh()->isOrdered());

        // Receive via HTTP.
        $this->actingAs($actor)
            ->post("/store/{$store->slug}/pos/purchases/{$po->id}/receive")
            ->assertRedirect();
        $this->assertTrue($po->fresh()->isReceived());
        $this->assertSame('8.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_http_cancel_pending_flow(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '5000'],
        ], null, null, null, $actor);

        $this->actingAs($actor)
            ->post("/store/{$store->slug}/pos/purchases/{$po->id}/cancel")
            ->assertRedirect();
        $this->assertTrue($po->fresh()->isCancelled());
    }

    public function test_non_staff_cannot_access_purchases(): void
    {
        $store = $this->makeStore();
        $outsider = User::create([
            'name' => 'Outsider',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->actingAs($outsider)
            ->get("/store/{$store->slug}/pos/purchases")
            ->assertForbidden();
    }

    public function test_show_page_renders(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '5000'],
        ], null, null, null, $actor);

        $this->actingAs($actor)
            ->get("/store/{$store->slug}/pos/purchases/{$po->id}")
            ->assertOk()
            ->assertSee($po->po_number)
            ->assertSee(__('messages.po_btn_order'));
    }
}
