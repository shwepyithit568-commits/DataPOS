<?php

namespace Tests\Feature\POS;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Integrations\OrderInventoryAdapter;
use App\POS\Services\CashierShiftService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Import Web Order — list importable online orders from the POS and fulfil
 * them at the counter without double-deducting stock.
 */
class WebOrderImportTest extends TestCase
{
    use RefreshDatabase;

    private PosSaleService $sales;

    private InventoryService $inventory;

    private CashierShiftService $shifts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sales = app(PosSaleService::class);
        $this->inventory = app(InventoryService::class);
        $this->shifts = app(CashierShiftService::class);
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

    private function makeStore(string $slug = 'web-shop'): Store
    {
        return Store::create(['name' => 'Web Shop', 'slug' => $slug, 'is_active' => true]);
    }

    private function staff(Store $store): User
    {
        $user = User::create([
            'name' => 'Cashier ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        return $user;
    }

    private function makeProduct(Store $store, int $price = 15000): Product
    {
        $name = 'Phone ' . Str::random(3);

        return Product::create([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(3)),
            'retail_price' => $price,
            'wholesale_price' => $price - 1000,
        ]);
    }

    private function seedStock(Store $store, Product $product, string $qty = '10'): void
    {
        $this->inventory->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => $qty,
            'unit_cost' => 8000,
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed:' . Str::uuid(),
            'occurred_at' => now(),
        ]);
    }

    private function openShift(Store $store, User $cashier)
    {
        return $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => 50000], $cashier);
    }

    private function makeOrder(Store $store, Product $product, string $status = 'pending_contact', int $qty = 2): Order
    {
        $order = Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-' . Str::upper(Str::random(6)),
            'customer_name' => 'Web Customer',
            'customer_phone' => '09998765432',
            'contact_channel' => 'phone',
            'pricing_type' => 'retail',
            'total_amount' => $product->retail_price * $qty,
            'payment_status' => 'unpaid',
            'status' => $status,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'product_name' => $product->name,
            'unit_price' => $product->retail_price,
            'quantity' => $qty,
            'subtotal' => $product->retail_price * $qty,
        ]);

        return $order;
    }

    private function postCounterSale(Store $store, User $cashier, Order $order, array $payments): void
    {
        $this->actingAs($cashier)->post(
            "/store/{$store->slug}/pos/post",
            ['payments' => $payments, 'web_order_id' => $order->id]
        )->assertRedirect();
    }

    /* ------------------------------------------------------------------ */
    /*  Endpoint                                                           */
    /* ------------------------------------------------------------------ */

    public function test_web_orders_lists_importable_pending_orders(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);
        $product = $this->makeProduct($store);
        $order = $this->makeOrder($store, $product);

        $response = $this->actingAs($staff)->getJson("/store/{$store->slug}/pos/web-orders");

        $response->assertOk()
            ->assertJsonPath('orders.0.id', $order->id)
            ->assertJsonPath('orders.0.order_number', $order->order_number)
            ->assertJsonPath('orders.0.items.0.product_id', $product->id)
            ->assertJsonPath('orders.0.items.0.quantity', 2);
    }

    public function test_web_orders_excludes_delivered_and_cancelled(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->makeOrder($store, $product, 'delivered');
        $this->makeOrder($store, $product, 'cancelled');
        $pending = $this->makeOrder($store, $product, 'confirmed');

        $response = $this->actingAs($staff)->getJson("/store/{$store->slug}/pos/web-orders");

        $response->assertOk();
        $ids = collect($response->json('orders'))->pluck('id');
        $this->assertCount(1, $ids);
        $this->assertTrue($ids->contains($pending->id));
    }

    public function test_web_orders_search_by_order_number(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);
        $product = $this->makeProduct($store);
        $a = $this->makeOrder($store, $product);
        $this->makeOrder($store, $product);

        $response = $this->actingAs($staff)
            ->getJson("/store/{$store->slug}/pos/web-orders?q=" . $a->order_number);

        $response->assertOk();
        $ids = collect($response->json('orders'))->pluck('id');
        $this->assertCount(1, $ids);
        $this->assertTrue($ids->contains($a->id));
    }

    public function test_web_orders_are_store_scoped(): void
    {
        $storeA = $this->makeStore('shop-a');
        $storeB = $this->makeStore('shop-b');
        $staffA = $this->staff($storeA);
        $staffB = $this->staff($storeB);
        $product = $this->makeProduct($storeA);
        $this->makeOrder($storeA, $product);

        // Staff of B never sees A's orders.
        $response = $this->actingAs($staffB)->getJson("/store/{$storeB->slug}/pos/web-orders");
        $response->assertOk()->assertJsonPath('orders', []);

        // And A's staff can't even reach B's endpoint.
        $this->actingAs($staffA)->getJson("/store/{$storeB->slug}/pos/web-orders")->assertForbidden();
    }

    /* ------------------------------------------------------------------ */
    /*  Fulfilment (no double deduction)                                   */
    /* ------------------------------------------------------------------ */

    public function test_fulfilling_pending_order_at_counter_deducts_stock_once(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, 15000);
        $this->seedStock($store, $product, '10');
        $this->openShift($store, $cashier);

        $order = $this->makeOrder($store, $product, 'pending_contact', 2);
        $this->sales->addToCart($store, $product->id, null, '2');

        $this->postCounterSale($store, $cashier, $order, [['method' => 'cash', 'amount' => '30000']]);

        $this->assertSame('delivered', $order->refresh()->status);
        // One deduction only (the counter sale) — the order itself was never reserved.
        $this->assertSame('8.000', $this->inventory->totalOnHand($store->id, $product->id));
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'pos_web_order_fulfilled',
            'entity_type' => 'order',
            'entity_id' => $order->id,
        ]);
    }

    public function test_fulfilling_confirmed_order_releases_reservation(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, 15000);
        $this->seedStock($store, $product, '10');
        $this->openShift($store, $cashier);

        $order = $this->makeOrder($store, $product, 'confirmed', 2);

        // Admin confirmed the order earlier → stock was reserved.
        app(OrderInventoryAdapter::class)->reserve($order);
        $this->assertSame('8.000', $this->inventory->totalOnHand($store->id, $product->id));

        $this->sales->addToCart($store, $product->id, null, '2');
        $this->postCounterSale($store, $cashier, $order, [['method' => 'cash', 'amount' => '30000']]);

        $this->assertSame('delivered', $order->refresh()->status);
        // Counter sale (−2) + reservation release (+2) = single effective deduction.
        $this->assertSame('8.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_an_already_delivered_order_cannot_be_sold_again(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, 15000);
        $this->seedStock($store, $product, '10');
        $this->openShift($store, $cashier);

        $order = $this->makeOrder($store, $product, 'delivered', 2);
        $this->sales->addToCart($store, $product->id, null, '2');

        $this->actingAs($cashier)->post(
            "/store/{$store->slug}/pos/post",
            ['payments' => [['method' => 'cash', 'amount' => '30000']], 'web_order_id' => $order->id]
        )->assertSessionHas('error');

        // The order stays delivered, no second fulfilment is audited, and — the
        // part that matters to the shopper — no second sale was posted.
        $this->assertSame('delivered', $order->refresh()->status);
        $this->assertDatabaseMissing('audit_logs', [
            'store_id' => $store->id,
            'action' => 'pos_web_order_fulfilled',
            'entity_id' => $order->id,
        ]);
        $this->assertSame(0, \App\POS\Models\PosSale::where('store_id', $store->id)->where('status', 'posted')->count());
        $this->assertSame('10.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $store = $this->makeStore();

        $this->get("/store/{$store->slug}/pos/web-orders")->assertRedirect(route('login'));
    }

    /* ------------------------------------------------------------------ */
    /*  Import into the cart (prices the order, not today's shelf)          */
    /* ------------------------------------------------------------------ */

    private function importOrder(Store $store, User $staff, Order $order)
    {
        return $this->actingAs($staff)->postJson("/store/{$store->slug}/pos/web-orders/{$order->id}/import");
    }

    public function test_importing_an_order_prices_the_cart_at_the_order_total(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);
        $product = $this->makeProduct($store, 25000);
        $this->seedStock($store, $product, '10');

        // The shopper ordered the charger with a 10% coupon: 25,000 → 22,500.
        $order = $this->makeOrder($store, $product, 'confirmed', 1);
        $order->update(['total_amount' => '22500.00', 'discount_amount' => '2500.00', 'coupon_code' => 'WEB10']);

        $response = $this->importOrder($store, $staff, $order);

        $response->assertOk()
            ->assertJsonPath('cart.totals.subtotal', '25000.00')
            ->assertJsonPath('cart.totals.manual_discount', '2500.00')
            ->assertJsonPath('cart.totals.discount', '2500.00')
            ->assertJsonPath('cart.totals.total', '22500.00');

        // The coupon is NOT redeemed again: the order already consumed its use.
        $this->assertDatabaseCount('promotion_usages', 0);
    }

    public function test_importing_an_order_uses_the_agreed_amount_when_staff_renegotiated(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);
        $product = $this->makeProduct($store, 25000);
        $this->seedStock($store, $product, '10');

        $order = $this->makeOrder($store, $product, 'confirmed', 2);   // list 50,000
        $order->update(['agreed_amount' => '45000.00']);

        $this->importOrder($store, $staff, $order)
            ->assertOk()
            ->assertJsonPath('cart.totals.subtotal', '50000.00')
            ->assertJsonPath('cart.totals.discount', '5000.00')
            ->assertJsonPath('cart.totals.total', '45000.00');
    }

    public function test_importing_an_order_never_charges_more_than_todays_price(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);
        $product = $this->makeProduct($store, 15000);
        $this->seedStock($store, $product, '10');

        // The order was written when the price was higher (or a line went
        // missing): the counter charges today's lower price, never a surcharge.
        $order = $this->makeOrder($store, $product, 'confirmed', 1);
        $order->update(['total_amount' => '20000.00']);

        $this->importOrder($store, $staff, $order)
            ->assertOk()
            ->assertJsonPath('cart.totals.manual_discount', '0')
            ->assertJsonPath('cart.totals.total', '15000.00');
    }

    public function test_importing_an_order_attaches_the_shopper_and_replaces_the_cart(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);
        $product = $this->makeProduct($store, 15000);
        $other = $this->makeProduct($store, 5000);
        $this->seedStock($store, $product, '10');
        $this->seedStock($store, $other, '10');

        $shopper = User::create([
            'name' => 'Web Shopper',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $shopper->stores()->attach($store->id, ['role' => 'retail_customer', 'status' => 'active']);

        $order = $this->makeOrder($store, $product, 'pending_contact', 1);
        $order->update(['user_id' => $shopper->id]);

        // Something unrelated is already in the cart — the import replaces it.
        $this->sales->addToCart($store, $other->id, null, '3');

        $response = $this->importOrder($store, $staff, $order);

        $response->assertOk()
            ->assertJsonPath('cart.customer.id', $shopper->id)
            ->assertJsonCount(1, 'cart.lines')
            ->assertJsonPath('cart.lines.0.product_id', $product->id);
    }

    public function test_importing_an_order_from_another_store_is_refused(): void
    {
        $storeA = $this->makeStore('shop-a');
        $storeB = $this->makeStore('shop-b');
        $staffB = $this->staff($storeB);
        $product = $this->makeProduct($storeA);
        $order = $this->makeOrder($storeA, $product);

        $this->importOrder($storeB, $staffB, $order)->assertStatus(422);
    }

    public function test_a_delivered_order_cannot_be_imported(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '10');

        $order = $this->makeOrder($store, $product, 'delivered', 1);

        $this->importOrder($store, $staff, $order)->assertStatus(422);
    }

    /**
     * The order holds its units while it waits (confirmed → reserved). A shop
     * with exactly those units left must still be able to hand the order over,
     * which is why the reservation is released before the sale takes them.
     */
    public function test_an_order_holding_the_last_unit_can_still_be_fulfilled(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, 15000);
        $this->seedStock($store, $product, '1');
        $this->openShift($store, $cashier);

        $order = $this->makeOrder($store, $product, 'confirmed', 1);
        app(OrderInventoryAdapter::class)->reserve($order);
        $this->assertSame('0.000', $this->inventory->totalOnHand($store->id, $product->id));

        $this->importOrder($store, $cashier, $order)->assertOk();
        $this->postCounterSale($store, $cashier, $order, [['method' => 'cash', 'amount' => '15000']]);

        $this->assertSame('delivered', $order->refresh()->status);
        $this->assertSame('0.000', $this->inventory->totalOnHand($store->id, $product->id));
    }
}
