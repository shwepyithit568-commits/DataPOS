<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\GoodsReceipt;
use App\POS\Services\GoodsReceiptService;
use App\POS\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GoodsReceiptTest extends TestCase
{
    use RefreshDatabase;

    private GoodsReceiptService $receipts;

    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->receipts = app(GoodsReceiptService::class);
        $this->inventory = app(InventoryService::class);
    }

    private function makeStore(string $slug = 'shop-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function staff(Store $store): User
    {
        $user = User::create([
            'name' => 'Receiver ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        return $user;
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
    /*  Posting                                                           */
    /* ------------------------------------------------------------------ */

    public function test_post_creates_document_items_movements_and_audit(): void
    {
        $store = $this->makeStore();
        $receiver = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '10', '8000');

        $receipt = $this->receipts->create(
            $store,
            [['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '9000']],
            'SUP-001',
            'First batch',
            $receiver,
        );

        $this->assertTrue($receipt->isPosted());
        $this->assertStringStartsWith('GRV-', $receipt->receipt_number);
        $this->assertSame('5.000', (string) $receipt->total_quantity);
        $this->assertSame('45000.00', (string) $receipt->total_cost);
        $this->assertSame(1, $receipt->items->count());
        $this->assertSame('15.000', $this->inventory->totalOnHand($store->id, $product->id));

        $this->assertDatabaseHas('inventory_movements', [
            'store_id' => $store->id,
            'source_type' => 'goods_receipt',
            'source_id' => $receipt->id,
            'movement_type' => 'purchase_received',
            'quantity_delta' => '5',
            'unit_cost' => '9000.0000',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'goods_receipt_posted',
            'entity_type' => 'goods_receipt',
            'entity_id' => $receipt->id,
        ]);
    }

    public function test_receiving_recalculates_weighted_average(): void
    {
        $store = $this->makeStore();
        $receiver = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '10', '8000'); // Q=10, A=8000

        $this->receipts->create(
            $store,
            [['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '10000']],
            null,
            null,
            $receiver,
        );

        // (10×8000 + 10×10000) / 20 = 9000
        $warehouse = app(\App\POS\Services\StoreLocationService::class)->defaultWarehouse($store);
        $balance = \App\POS\Models\InventoryBalance::query()
            ->where('store_id', $store->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->firstOrFail();
        $this->assertSame('9000.0000', (string) $balance->unit_cost_avg);
        $this->assertSame('20.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_receipt_numbers_are_sequential_per_store(): void
    {
        $store = $this->makeStore();
        $receiver = $this->staff($store);
        $product = $this->makeProduct($store);

        $a = $this->receipts->create($store, [['product_id' => $product->id, 'quantity' => '1', 'unit_cost' => '1000']], null, null, $receiver);
        $b = $this->receipts->create($store, [['product_id' => $product->id, 'quantity' => '1', 'unit_cost' => '1000']], null, null, $receiver);

        $this->assertNotSame($a->receipt_number, $b->receipt_number);
        $this->assertStringEndsWith('0002', $b->receipt_number);
    }

    /* ------------------------------------------------------------------ */
    /*  Validation + idempotency                                           */
    /* ------------------------------------------------------------------ */

    public function test_client_transaction_id_makes_retries_idempotent(): void
    {
        $store = $this->makeStore();
        $receiver = $this->staff($store);
        $product = $this->makeProduct($store);

        $ctid = 'gr:test-' . Str::uuid();
        $this->receipts->create($store, [['product_id' => $product->id, 'quantity' => '4', 'unit_cost' => '2000']], null, null, $receiver, $ctid);
        $this->receipts->create($store, [['product_id' => $product->id, 'quantity' => '4', 'unit_cost' => '2000']], null, null, $receiver, $ctid);

        $this->assertSame(1, GoodsReceipt::count());
        $this->assertSame('4.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_cross_store_product_is_rejected_without_trace(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $receiverB = $this->staff($storeB);
        $productA = $this->makeProduct($storeA);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('does not belong');

        $this->receipts->create(
            $storeB,
            [['product_id' => $productA->id, 'quantity' => '5', 'unit_cost' => '1000']],
            null,
            null,
            $receiverB,
        );

        $this->assertSame(0, GoodsReceipt::count());
        $this->assertSame(0, \App\POS\Models\InventoryMovement::count());
    }

    public function test_empty_lines_and_non_positive_quantity_are_rejected(): void
    {
        $store = $this->makeStore();
        $receiver = $this->staff($store);

        try {
            $this->receipts->create($store, [], null, null, $receiver);
            $this->fail('Expected empty-items exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('at least one line', $e->getMessage());
        }

        $product = $this->makeProduct($store);

        try {
            $this->receipts->create($store, [['product_id' => $product->id, 'quantity' => '0', 'unit_cost' => '1000']], null, null, $receiver);
            $this->fail('Expected quantity exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('quantity must be greater', $e->getMessage());
        }

        $this->assertSame(0, GoodsReceipt::count());
    }

    /* ------------------------------------------------------------------ */
    /*  HTTP + authorization                                               */
    /* ------------------------------------------------------------------ */

    public function test_receiving_page_renders_for_staff(): void
    {
        $store = $this->makeStore();
        $receiver = $this->staff($store);

        $this->actingAs($receiver)
            ->get("/store/{$store->slug}/pos/receiving")
            ->assertOk()
            ->assertSee(__('messages.receiving_subtitle'))
            ->assertSee(__('messages.receiving_post'));
    }

    public function test_http_post_receipt_flow(): void
    {
        $store = $this->makeStore();
        $receiver = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');

        $this->actingAs($receiver)
            ->post("/store/{$store->slug}/pos/receiving", [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => '3', 'unit_cost' => '6000'],
                ],
                'reference' => 'SUP-X',
            ])
            ->assertRedirect();

        $receipt = GoodsReceipt::firstOrFail();
        $this->assertSame('3.000', (string) $receipt->total_quantity);
        $this->assertSame('18000.00', (string) $receipt->total_cost);
        $this->assertSame('8.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_non_staff_cannot_post_receipt(): void
    {
        $store = $this->makeStore();
        $outsider = User::create([
            'name' => 'Outsider',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->actingAs($outsider)
            ->post("/store/{$store->slug}/pos/receiving", [
                'items' => [['product_id' => 1, 'quantity' => '1', 'unit_cost' => '100']],
            ])
            ->assertForbidden();

        $this->assertSame(0, GoodsReceipt::count());
    }
}
