<?php

namespace Tests\Feature\POS;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Integrations\OrderInventoryAdapter;
use App\POS\Models\InventoryMovement;
use App\POS\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderInventoryAdapterTest extends TestCase
{
    use RefreshDatabase;

    private OrderInventoryAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = app(OrderInventoryAdapter::class);
    }

    private function makeStore(string $slug = 'store-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function makeProduct(Store $store, string $slug = 'product-a', int $stock = 10): Product
    {
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-' . strtoupper($slug),
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'retail_price' => 10000,
            'wholesale_price' => 8000,
            'stock_status' => 'out_of_stock',
            'is_featured' => false,
        ]);

        app(InventoryService::class)->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => $stock,
            'client_transaction_id' => 'stock-' . $slug,
        ]);

        return $product;
    }

    /**
     * @param  array<int, array{product?: Product, variant?: ProductVariant, quantity: int}>  $lines
     */
    private function makeOrder(Store $store, array $lines): Order
    {
        $order = Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-TEST-' . Str::upper(Str::random(6)),
            'customer_name' => 'Test Customer',
            'customer_phone' => '09999999999',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 1000,
            'payment_status' => 'unpaid',
            'status' => 'pending_contact',
        ]);

        foreach ($lines as $line) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $line['product']->id ?? null,
                'product_variant_id' => $line['variant']->id ?? null,
                'product_name' => $line['product']->name ?? 'Glass Finder Item',
                'unit_price' => 1000,
                'quantity' => $line['quantity'],
                'subtotal' => 1000 * $line['quantity'],
            ]);
        }

        return $order;
    }

    private function totalOnHand(Store $store, Product $product): string
    {
        return app(InventoryService::class)->totalOnHand($store->id, $product->id);
    }

    /* ------------------------------------------------------------------ */
    /*  Reserve on confirm                                                 */
    /* ------------------------------------------------------------------ */

    public function test_confirm_reserves_stock(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, 'phone-case', 10);
        $order = $this->makeOrder($store, [['product' => $product, 'quantity' => 3]]);

        $this->adapter->handleStatusChange($order, 'pending_contact', 'confirmed');

        $this->assertSame('7.000', $this->totalOnHand($store, $product));
        $this->assertTrue($this->adapter->isReserved($order));
        $this->assertFalse($this->adapter->isCommitted($order));
        $this->assertSame('in_stock', $product->fresh()->stock_status); // 7 remaining
    }

    public function test_confirm_with_insufficient_stock_is_blocked(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, 'low-stock', 2);
        $order = $this->makeOrder($store, [['product' => $product, 'quantity' => 5]]);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('insufficient stock');

        $this->adapter->handleStatusChange($order, 'pending_contact', 'confirmed');

        // Nothing was deducted — the whole reservation rolled back.
        $this->assertSame('2.000', $this->totalOnHand($store, $product));
    }

    public function test_confirm_does_not_reserve_items_without_product(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, 'catalog-item', 10);
        // Glass-finder / legacy line has no product_id.
        $order = $this->makeOrder($store, [
            ['product' => $product, 'quantity' => 2],
            ['product' => null, 'quantity' => 1],
        ]);

        $this->adapter->handleStatusChange($order, 'pending_contact', 'confirmed');

        $this->assertSame('8.000', $this->totalOnHand($store, $product));
        $this->assertSame(1, InventoryMovement::where('movement_type', 'online_reserve')->count());
    }

    /* ------------------------------------------------------------------ */
    /*  Commit on delivery                                                 */
    /* ------------------------------------------------------------------ */

    public function test_delivery_commits_without_double_deduction(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, 'silicone', 10);
        $order = $this->makeOrder($store, [['product' => $product, 'quantity' => 3]]);

        $this->adapter->handleStatusChange($order, 'pending_contact', 'confirmed'); // reserve -3 → 7
        $this->adapter->handleStatusChange($order, 'confirmed', 'delivered');       // commit, 0

        $this->assertSame('7.000', $this->totalOnHand($store, $product));
        $this->assertTrue($this->adapter->isCommitted($order));
    }

    public function test_delivery_without_prior_confirm_still_deducts_once(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, 'rush-order', 10);
        $order = $this->makeOrder($store, [['product' => $product, 'quantity' => 3]]);

        $this->adapter->handleStatusChange($order, 'pending_contact', 'delivered');

        // Stock deducted exactly once (at delivered, the earliest commit point).
        $this->assertSame('7.000', $this->totalOnHand($store, $product));
        $this->assertSame(1, InventoryMovement::where('movement_type', 'online_reserve')->count());
        $this->assertSame(1, InventoryMovement::where('movement_type', 'online_confirm')->count());
    }

    /* ------------------------------------------------------------------ */
    /*  Release on cancel                                                  */
    /* ------------------------------------------------------------------ */

    public function test_cancel_releases_reserved_stock(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, 'releasable', 10);
        $order = $this->makeOrder($store, [['product' => $product, 'quantity' => 3]]);

        $this->adapter->handleStatusChange($order, 'pending_contact', 'confirmed'); // 7
        $this->adapter->handleStatusChange($order, 'confirmed', 'cancelled');       // +3 → 10

        $this->assertSame('10.000', $this->totalOnHand($store, $product));
    }

    public function test_cancel_from_pending_contact_does_nothing(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, 'never-reserved', 10);
        $order = $this->makeOrder($store, [['product' => $product, 'quantity' => 3]]);

        $this->adapter->handleStatusChange($order, 'pending_contact', 'cancelled');

        $this->assertSame('10.000', $this->totalOnHand($store, $product));
        $this->assertSame(0, InventoryMovement::where('source_type', 'like', 'order_%')->count());
    }

    public function test_cancel_after_delivery_does_not_release(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, 'committed', 10);
        $order = $this->makeOrder($store, [['product' => $product, 'quantity' => 3]]);

        $this->adapter->handleStatusChange($order, 'pending_contact', 'confirmed'); // 7
        $this->adapter->handleStatusChange($order, 'confirmed', 'delivered');       // committed
        $this->adapter->handleStatusChange($order, 'delivered', 'cancelled');

        // Committed goods are not auto-released — the return flow (Phase 2) handles this.
        $this->assertSame('7.000', $this->totalOnHand($store, $product));
        $this->assertSame(0, InventoryMovement::where('movement_type', 'online_cancel')->count());
    }

    /* ------------------------------------------------------------------ */
    /*  Idempotency & merging                                              */
    /* ------------------------------------------------------------------ */

    public function test_reserve_is_idempotent_on_retry(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, 'retry-safe', 10);
        $order = $this->makeOrder($store, [['product' => $product, 'quantity' => 3]]);

        $this->adapter->reserve($order);
        $this->adapter->reserve($order); // offline retry

        $this->assertSame('7.000', $this->totalOnHand($store, $product));
        $this->assertSame(1, InventoryMovement::where('movement_type', 'online_reserve')->count());
    }

    public function test_duplicate_product_lines_are_merged_into_one_movement(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, 'merge-me', 10);
        $order = $this->makeOrder($store, [
            ['product' => $product, 'quantity' => 1],
            ['product' => $product, 'quantity' => 2],
        ]);

        $this->adapter->reserve($order);

        $this->assertSame('7.000', $this->totalOnHand($store, $product));
        $this->assertSame(1, InventoryMovement::where('movement_type', 'online_reserve')->count());
    }

    public function test_variant_level_reservation(): void
    {
        $store = $this->makeStore();
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-VARIANT',
            'name' => 'Variant Product',
            'slug' => 'with-variant',
            'retail_price' => 10000,
            'wholesale_price' => 8000,
            'stock_status' => 'out_of_stock',
            'is_featured' => false,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Black',
            'sku' => 'SKU-V-BLACK',
            'retail_price' => 12000,
            'wholesale_price' => 9000,
        ]);

        // Stock is tracked per variant SKU — seed the variant's balance directly.
        app(InventoryService::class)->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'client_transaction_id' => 'variant-open-1',
        ]);

        $order = $this->makeOrder($store, [['product' => $product, 'variant' => $variant, 'quantity' => 4]]);

        $this->adapter->reserve($order);

        $service = app(InventoryService::class);
        $this->assertSame('6.000', $service->totalOnHand($store->id, $product->id, $variant->id));
        $this->assertSame('6.000', $service->totalOnHand($store->id, $product->id));
    }

    /* ------------------------------------------------------------------ */
    /*  Controller hook                                                    */
    /* ------------------------------------------------------------------ */

    private function storeManager(Store $store): User
    {
        $user = User::create([
            'name' => 'Manager',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        return $user;
    }

    public function test_admin_confirm_status_change_reserves_stock(): void
    {
        $store = $this->makeStore('shop-a');
        $product = $this->makeProduct($store, 'shop-product', 10);
        $order = $this->makeOrder($store, [['product' => $product, 'quantity' => 2]]);
        $manager = $this->storeManager($store);

        $response = $this->actingAs($manager)
            ->patch("/store/{$store->slug}/admin/orders/{$order->id}/status", ['status' => 'confirmed']);

        $response->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('8.000', $this->totalOnHand($store, $product));
    }

    public function test_admin_confirm_with_insufficient_stock_is_rejected_and_status_unchanged(): void
    {
        $store = $this->makeStore('shop-b');
        $product = $this->makeProduct($store, 'shop-low', 1);
        $order = $this->makeOrder($store, [['product' => $product, 'quantity' => 5]]);
        $manager = $this->storeManager($store);

        $response = $this->actingAs($manager)
            ->patch("/store/{$store->slug}/admin/orders/{$order->id}/status", ['status' => 'confirmed']);

        $response->assertSessionHasErrors('status');
        $this->assertSame('pending_contact', $order->fresh()->status);
        $this->assertSame('1.000', $this->totalOnHand($store, $product));
    }
}
