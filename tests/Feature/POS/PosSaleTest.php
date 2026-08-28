<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\PosSale;
use App\POS\Services\CashierShiftService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosSaleTest extends TestCase
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

    private function makeStore(string $slug = 'shop-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
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

    /** Opening balance so a sale has stock to draw from. */
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

    /* ------------------------------------------------------------------ */
    /*  Search                                                             */
    /* ------------------------------------------------------------------ */

    public function test_search_products_finds_by_sku_and_name(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, ['name' => 'Samsung Galaxy S25', 'sku' => 'SAM-S25-BLK']);
        $this->seedStock($store, $product);

        $bySku = $this->sales->searchProducts($store, 'SAM-S25');
        $byName = $this->sales->searchProducts($store, 'galaxy');

        $this->assertCount(1, $bySku);
        $this->assertSame($product->id, $bySku[0]['product_id']);
        $this->assertSame('10.000', (string) $bySku[0]['balance']);
        $this->assertCount(1, $byName);
    }

    public function test_search_is_store_scoped(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $this->makeProduct($storeB, ['name' => 'Secret Store B Phone', 'sku' => 'SECRET-999']);

        $this->assertSame([], $this->sales->searchProducts($storeA, 'SECRET-999'));
    }

    /* ------------------------------------------------------------------ */
    /*  Cart                                                               */
    /* ------------------------------------------------------------------ */

    public function test_add_to_cart_merges_same_product(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, ['retail_price' => 15000]);
        $this->seedStock($store, $product, '5');

        $this->sales->addToCart($store, $product->id, null, '1');
        $this->sales->addToCart($store, $product->id, null, '2');

        $lines = $this->sales->cartResolved($store);
        $this->assertCount(1, $lines);
        $this->assertSame('3.000', $lines[0]['quantity']);
        $this->assertSame('45000.00', $lines[0]['line_total']);
        $this->assertSame('45000.00', $this->sales->cartTotals($store)['total']);
    }

    public function test_cart_rejects_cross_store_product(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $productB = $this->makeProduct($storeB);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('does not belong');

        $this->sales->addToCart($storeA, $productB->id, null, '1');
    }

    /* ------------------------------------------------------------------ */
    /*  Posting (atomic)                                                   */
    /* ------------------------------------------------------------------ */

    public function test_post_sale_is_atomic_and_updates_shift_and_stock(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 12000]);
        $this->seedStock($store, $product, '10');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '3');

        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '36000']],
            $cashier,
            $shift,
        );

        $this->assertTrue($sale->isPosted());
        $this->assertStringStartsWith('RCP-', $sale->receipt_number);
        $this->assertSame('36000.00', (string) $sale->total);
        $this->assertSame('7.000', $this->inventory->totalOnHand($store->id, $product->id));
        $this->assertSame('36000.00', (string) $shift->refresh()->cash_sales);
        $this->assertSame([], $this->sales->cartLines($store));

        // Movement source is the sale itself — one ledger row, immutable.
        $this->assertDatabaseHas('inventory_movements', [
            'source_type' => 'pos_sale',
            'source_id' => $sale->id,
            'movement_type' => 'pos_sale',
            'quantity_delta' => '-3',
        ]);
        $this->assertSame('8000.0000', (string) $sale->items->first()->unit_cost); // COGS carried
    }

    public function test_receipt_number_assigned_only_at_posting(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');

        $held = $this->sales->holdCart($store, $cashier, $shift);
        $this->assertNull($held->receipt_number);

        $posted = $this->sales->post(
            $store,
            $held->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'product_variant_id' => $i->product_variant_id,
                'quantity' => (string) $i->quantity,
            ])->all(),
            [['method' => 'cash', 'amount' => '10000']],
            $cashier,
            $shift,
            $held,
        );

        $this->assertStringStartsWith('RCP-', $posted->receipt_number);
        $this->assertNotNull($posted->posted_at);
    }

    public function test_receipt_numbers_are_sequential_per_store(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '10');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');
        $saleA = $this->sales->post($store, $this->sales->cartLines($store), [['method' => 'cash', 'amount' => '10000']], $cashier, $shift);

        $this->sales->addToCart($store, $product->id, null, '1');
        $saleB = $this->sales->post($store, $this->sales->cartLines($store), [['method' => 'cash', 'amount' => '10000']], $cashier, $shift);

        $this->assertNotSame($saleA->receipt_number, $saleB->receipt_number);
        $this->assertStringEndsWith('0002', $saleB->receipt_number);
    }

    public function test_insufficient_stock_blocks_post_and_leaves_no_trace(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '1');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '2');

        try {
            $this->sales->post($store, $this->sales->cartLines($store), [['method' => 'cash', 'amount' => '20000']], $cashier, $shift);
            $this->fail('Expected insufficient-stock exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('Insufficient stock', $e->getMessage());
        }

        $this->assertSame(0, PosSale::count());
        $this->assertSame(0, \App\POS\Models\PosPayment::count());
        $this->assertSame('1.000', $this->inventory->totalOnHand($store->id, $product->id));
        $this->assertSame('0.00', (string) $shift->refresh()->cash_sales);
    }

    public function test_split_payment_only_cash_goes_to_drawer(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 20000]);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');

        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [
                ['method' => 'cash', 'amount' => '12000'],
                ['method' => 'kpay', 'amount' => '8000'],
            ],
            $cashier,
            $shift,
        );

        $this->assertSame('20000.00', (string) $sale->total);
        $this->assertSame(2, $sale->payments->count());
        $this->assertSame('12000.00', (string) $shift->refresh()->cash_sales); // only the cash part
    }

    public function test_cash_change_is_returned_and_drawer_keeps_total_only(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 34500]);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');

        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '50000']],
            $cashier,
            $shift,
        );

        $cash = $sale->payments->firstWhere('method', 'cash');
        $this->assertSame('50000.00', (string) $cash->amount);
        $this->assertSame('15500.00', (string) $cash->change_given);
        $this->assertSame('34500.00', (string) $shift->refresh()->cash_sales);
    }

    public function test_underpayment_is_blocked(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 10000]);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('do not cover');

        $this->sales->post($store, $this->sales->cartLines($store), [['method' => 'cash', 'amount' => '5000']], $cashier, $shift);
    }

    public function test_non_cash_overpayment_is_blocked(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 10000]);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('exceeds');

        $this->sales->post($store, $this->sales->cartLines($store), [['method' => 'kpay', 'amount' => '15000']], $cashier, $shift);
    }

    public function test_post_requires_open_shift(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');
        $this->sales->addToCart($store, $product->id, null, '1');

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('open cashier shift');

        $this->sales->post($store, $this->sales->cartLines($store), [['method' => 'cash', 'amount' => '10000']], $cashier, null);
    }

    /* ------------------------------------------------------------------ */
    /*  Hold / resume / void                                               */
    /* ------------------------------------------------------------------ */

    public function test_hold_creates_held_sale_and_clears_cart(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 25000]);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '2');

        $held = $this->sales->holdCart($store, $cashier, $shift);

        $this->assertTrue($held->isHeld());
        $this->assertSame('50000.00', (string) $held->total);
        $this->assertSame([], $this->sales->cartLines($store));
        $this->assertSame(1, $held->items()->count());

        // Resume loads it back into the cart.
        $this->sales->resumeHeld($store, $held);
        $lines = $this->sales->cartResolved($store);
        $this->assertCount(1, $lines);
        $this->assertSame('2.000', $lines[0]['quantity']);

        // Posting the held sale keeps the same row, now posted.
        $posted = $this->sales->post(
            $store,
            $this->sales->cartLines($store), // resume restored these
            [['method' => 'cash', 'amount' => '50000']],
            $cashier,
            $shift,
            $held,
        );

        $this->assertSame($held->id, $posted->id);
        $this->assertTrue($posted->isPosted());
        $this->assertNotNull($posted->receipt_number);
        $this->assertSame('3.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_stale_holds_are_auto_expired_and_fresh_ones_kept(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 25000]);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');
        $stale = $this->sales->holdCart($store, $cashier, $shift);
        $stale->forceFill(['created_at' => now()->subDays(2)])->save();

        $this->sales->addToCart($store, $product->id, null, '1');
        $fresh = $this->sales->holdCart($store, $cashier, $shift);

        $expired = $this->sales->expireStaleHolds($store, 24);
        $this->assertSame(1, $expired);
        $this->assertSame('voided', $stale->refresh()->status);
        $this->assertNotNull($stale->voided_at);
        $this->assertStringContainsString('Expired', $stale->notes);
        $this->assertSame('held', $fresh->refresh()->status);

        // cart-state surfaces only the fresh hold, with a held-since time, and
        // reports the auto-expiry count so the UI can flash a notice.
        $state = $this->sales->cartState($store, $cashier);
        $this->assertSame(1, $state['held_count']);
        $this->assertCount(1, $state['held']);
        $this->assertSame($fresh->id, $state['held'][0]['id']);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $state['held'][0]['held_at']);
        $this->assertSame(0, $state['expired_count']);

        // Expiry stats for the held-sales strip: default 24h window, the fresh
        // hold is the oldest, and nothing is close to expiring yet.
        $this->assertSame(24, $state['expiry']['threshold_hours']);
        $this->assertNotNull($state['expiry']['oldest_held_at']);
        $this->assertSame(0, $state['expiry']['soon_count']);
    }

    public function test_hold_expiry_window_is_per_store_and_can_be_disabled(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 25000]);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        // Custom 2-hour window for this store.
        $setting = $store->setting()->create(['store_name' => $store->name, 'pos_hold_expiry_hours' => 2]);

        $this->sales->addToCart($store, $product->id, null, '1');
        $old = $this->sales->holdCart($store, $cashier, $shift);
        $old->forceFill(['created_at' => now()->subHours(3)])->save();

        $this->sales->addToCart($store, $product->id, null, '1');
        $recent = $this->sales->holdCart($store, $cashier, $shift);
        $recent->forceFill(['created_at' => now()->subMinutes(90)])->save();

        // A cart-state read auto-expires the 3h-old hold and reports the count.
        $state = $this->sales->cartState($store, $cashier);
        $this->assertSame(1, $state['expired_count']);
        $this->assertSame(1, $state['held_count']);
        $this->assertSame($recent->id, $state['held'][0]['id']);
        $this->assertSame('voided', $old->refresh()->status);
        $this->assertSame('held', $recent->refresh()->status);
        $this->assertSame(0, $this->sales->expireStaleHolds($store)); // nothing left

        // Expiry stats: the 2h window is reported, the 1.5h-old hold is the
        // oldest, and it is flagged as soon-to-expire (under an hour left).
        $this->assertSame(2, $state['expiry']['threshold_hours']);
        $this->assertSame(1, $state['expiry']['soon_count']);
        $this->assertNotNull($state['expiry']['oldest_held_at']);

        // Setting the window to 0 disables auto-expiry entirely.
        $setting->update(['pos_hold_expiry_hours' => 0]);
        $store->unsetRelation('setting'); // drop the cached relation so the new value is read
        $this->sales->addToCart($store, $product->id, null, '1');
        $kept = $this->sales->holdCart($store, $cashier, $shift);
        $kept->forceFill(['created_at' => now()->subDays(3)])->save();
        $this->assertSame(0, $this->sales->expireStaleHolds($store));
        $disabled = $this->sales->cartState($store, $cashier);
        $this->assertSame(0, $disabled['expired_count']);
        $this->assertSame(0, $disabled['expiry']['threshold_hours']);
        $this->assertSame(0, $disabled['expiry']['soon_count']);
        $this->assertNotNull($disabled['expiry']['oldest_held_at']); // 3-day-old kept hold
        $this->assertSame('held', $kept->refresh()->status);
    }

    public function test_abandoned_resumed_sales_are_auto_expired(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 25000]);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');
        $held = $this->sales->holdCart($store, $cashier, $shift);
        $this->sales->resumeHeld($store, $held);
        $held->forceFill(['created_at' => now()->subDays(2)])->save();

        // A recalled-but-never-posted sale ages out of the window too — it
        // would otherwise linger as a zombie row no list ever shows.
        $expired = $this->sales->expireStaleHolds($store, 24);
        $this->assertSame(1, $expired);
        $this->assertSame('voided', $held->refresh()->status);
        $this->assertNotNull($held->voided_at);
        $this->assertStringContainsString('Abandoned', $held->notes);

        // A resumed sale inside the window is kept.
        $this->sales->addToCart($store, $product->id, null, '1');
        $fresh = $this->sales->holdCart($store, $cashier, $shift);
        $this->sales->resumeHeld($store, $fresh);
        $this->assertSame(0, $this->sales->expireStaleHolds($store));
        $this->assertSame('resumed', $fresh->refresh()->status);
    }

    public function test_void_held_sale_marks_voided_without_stock_impact(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');
        $held = $this->sales->holdCart($store, $cashier, $shift);

        $this->sales->voidHeld($store, $held, $cashier);

        $this->assertSame('voided', $held->refresh()->status);
        $this->assertNotNull($held->voided_at);
        $this->assertSame('5.000', $this->inventory->totalOnHand($store->id, $product->id));
        $this->assertSame(0, \App\POS\Models\PosPayment::count());
    }

    /* ------------------------------------------------------------------ */
    /*  HTTP + authorization                                               */
    /* ------------------------------------------------------------------ */

    public function test_http_cart_add_and_post_flow(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 10000]);
        $this->seedStock($store, $product, '5');
        $this->openShift($store, $cashier);

        $this->actingAs($cashier);

        $this->post("/store/{$store->slug}/pos/cart", [
            'product_id' => $product->id,
            'quantity' => '2',
        ])->assertRedirect();

        $this->post("/store/{$store->slug}/pos/post", [
            'payments' => [
                ['method' => 'cash', 'amount' => '20000'],
                ['method' => 'kpay', 'amount' => '0'],
            ],
        ])->assertRedirect();

        $sale = PosSale::first();
        $this->assertNotNull($sale);
        $this->assertTrue($sale->isPosted());
        $this->assertSame('20000.00', (string) $sale->total);
        $this->assertSame(1, $sale->payments->count()); // zero rows dropped
        $this->assertSame('3.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    public function test_non_staff_cannot_add_to_cart(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);
        $outsider = User::create([
            'name' => 'Outsider',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->actingAs($outsider)
            ->post("/store/{$store->slug}/pos/cart", ['product_id' => $product->id, 'quantity' => '1'])
            ->assertForbidden();
    }

    public function test_cross_store_sale_tamper_is_blocked(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $cashierB = $this->staff($storeB);
        $productA = $this->makeProduct($storeA);
        $this->seedStock($storeA, $productA, '5');

        $this->actingAs($cashierB)
            ->post("/store/{$storeB->slug}/pos/cart", ['product_id' => $productA->id, 'quantity' => '1'])
            ->assertRedirect(); // rejected gracefully by the service (flash error)
        $this->assertSame([], $this->sales->cartLines($storeB));
    }

    /* ------------------------------------------------------------------ */
    /*  Customer tier pricing (retail vs wholesale)                        */
    /* ------------------------------------------------------------------ */

    private function customer(Store $store, string $role = 'retail_customer', string $phone = '09999990001'): User
    {
        $user = User::create([
            'name' => 'Customer ' . Str::random(3),
            'phone' => $phone,
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    public function test_walk_in_and_retail_customer_pay_retail_price(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 10000, 'wholesale_price' => 9000]);
        $this->seedStock($store, $product);
        $shift = $this->openShift($store, $cashier);
        $retail = $this->customer($store, 'retail_customer');

        $this->sales->addToCart($store, $product->id, null, '1');
        $this->assertSame('10000.00', $this->sales->cartResolved($store)[0]['unit_price']); // walk-in

        $this->sales->attachCartCustomer($store, $retail);
        $this->assertSame('10000.00', $this->sales->cartResolved($store)[0]['unit_price']); // retail tier
        $this->assertSame('10000.00', $this->sales->cartTotals($store)['total']);

        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '10000']],
            $cashier,
            $shift,
        );

        $this->assertSame('10000.00', (string) $sale->total);
        $this->assertSame('10000.00', (string) $sale->items->first()->unit_price);
        $this->assertSame($retail->id, (int) $sale->customer_id);
    }

    public function test_wholesale_customer_cart_and_post_use_wholesale_price(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 10000, 'wholesale_price' => 9000]);
        $this->seedStock($store, $product);
        $shift = $this->openShift($store, $cashier);
        $wholesale = $this->customer($store, 'wholesale_customer');

        $this->sales->addToCart($store, $product->id, null, '1');
        $this->assertSame('10000.00', $this->sales->cartResolved($store)[0]['unit_price']); // walk-in first

        $this->sales->attachCartCustomer($store, $wholesale);
        $this->assertSame('9000.00', $this->sales->cartResolved($store)[0]['unit_price']); // wholesale tier
        $this->assertSame('9000.00', $this->sales->cartTotals($store)['total']);

        // Detaching returns the cart to walk-in retail pricing…
        $this->sales->attachCartCustomer($store, null);
        $this->assertSame('10000.00', $this->sales->cartResolved($store)[0]['unit_price']);

        // …and re-attaching re-prices it wholesale before posting.
        $this->sales->attachCartCustomer($store, $wholesale);
        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '9000']],
            $cashier,
            $shift,
        );

        $this->assertSame('9000.00', (string) $sale->total);
        $this->assertSame('9000.00', (string) $sale->items->first()->unit_price);
        $this->assertSame($wholesale->id, (int) $sale->customer_id);
    }

    public function test_wholesale_price_falls_back_to_retail_when_unset(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        // No wholesale price on this product — wholesale tier pays retail.
        $product = $this->makeProduct($store, ['retail_price' => 10000, 'wholesale_price' => 0]);
        $this->seedStock($store, $product);
        $wholesale = $this->customer($store, 'wholesale_customer');

        $this->sales->addToCart($store, $product->id, null, '1');
        $this->sales->attachCartCustomer($store, $wholesale);

        $this->assertSame('10000.00', $this->sales->cartResolved($store)[0]['unit_price']);
    }

    public function test_attach_rejects_cross_store_customer(): void
    {
        $storeA = $this->makeStore('shop-a');
        $storeB = $this->makeStore('shop-b');
        $customerA = $this->customer($storeA, 'wholesale_customer');

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('does not belong');

        $this->sales->attachCartCustomer($storeB, $customerA);
    }

    /* ------------------------------------------------------------------ */
    /*  Logged-in customer resolution (storefront session → register)      */
    /* ------------------------------------------------------------------ */

    public function test_cart_customer_resolves_from_logged_in_wholesale_customer(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 10000, 'wholesale_price' => 9000]);
        $this->seedStock($store, $product);
        $wholesale = $this->customer($store, 'wholesale_customer');

        // The wholesale shopper is the authenticated session (they logged into
        // the storefront); the register prices their cart at their tier with
        // no explicit attach.
        $this->actingAs($wholesale);
        $this->sales->addToCart($store, $product->id, null, '1');

        $this->assertSame($wholesale->id, $this->sales->cartCustomer($store)?->id);
        $this->assertSame('9000.00', $this->sales->cartResolved($store)[0]['unit_price']);
        $this->assertSame('9000.00', $this->sales->cartTotals($store)['total']);

        $state = $this->sales->cartState($store, $cashier);
        $this->assertSame($wholesale->id, $state['customer']['id']);
        $this->assertSame('wholesale_customer', $state['customer']['role']);
    }

    public function test_explicit_walk_in_overrides_logged_in_customer(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, ['retail_price' => 10000, 'wholesale_price' => 9000]);
        $this->seedStock($store, $product);
        $wholesale = $this->customer($store, 'wholesale_customer');

        $this->actingAs($wholesale);
        $this->sales->addToCart($store, $product->id, null, '1');

        // The cashier deliberately drops to walk-in — retail pricing despite
        // the logged-in wholesale shopper.
        $this->sales->attachCartCustomer($store, null);
        $this->assertNull($this->sales->cartCustomer($store));
        $this->assertSame('10000.00', $this->sales->cartResolved($store)[0]['unit_price']);
    }

    public function test_explicit_attach_overrides_logged_in_customer(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, ['retail_price' => 10000, 'wholesale_price' => 9000]);
        $this->seedStock($store, $product);
        $wholesale = $this->customer($store, 'wholesale_customer');
        $retail = $this->customer($store, 'retail_customer', '09999990002');

        $this->actingAs($wholesale);
        $this->sales->addToCart($store, $product->id, null, '1');

        // The cashier picks a different customer from search — their tier wins.
        $this->sales->attachCartCustomer($store, $retail);
        $this->assertSame($retail->id, $this->sales->cartCustomer($store)?->id);
        $this->assertSame('10000.00', $this->sales->cartResolved($store)[0]['unit_price']);
    }

    public function test_logged_in_staff_is_not_auto_attached(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 10000, 'wholesale_price' => 9000]);
        $this->seedStock($store, $product);

        $this->actingAs($cashier);
        $this->sales->addToCart($store, $product->id, null, '1');

        // Staff have no customer membership — the register stays walk-in.
        $this->assertNull($this->sales->cartCustomer($store));
        $this->assertSame('10000.00', $this->sales->cartResolved($store)[0]['unit_price']);
    }

    public function test_posted_sale_records_logged_in_customer_and_tier(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 10000, 'wholesale_price' => 9000]);
        $this->seedStock($store, $product);
        $shift = $this->openShift($store, $cashier);
        $wholesale = $this->customer($store, 'wholesale_customer');

        $this->actingAs($wholesale);
        $this->sales->addToCart($store, $product->id, null, '1');

        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '9000']],
            $cashier,
            $shift,
        );

        $this->assertSame('9000.00', (string) $sale->total);
        $this->assertSame('9000.00', (string) $sale->items->first()->unit_price);
        $this->assertSame($wholesale->id, (int) $sale->customer_id);
    }

    /* ------------------------------------------------------------------ */
    /*  Receipt (print / reprint audit)                                    */
    /* ------------------------------------------------------------------ */

    private function postedSale(Store $store, User $cashier): PosSale
    {
        $product = $this->makeProduct($store, ['retail_price' => 20000, 'sku' => 'RCP-PROD-1']);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');

        return $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [
                ['method' => 'cash', 'amount' => '25000'],
                ['method' => 'kpay', 'amount' => '0'],
            ],
            $cashier,
            $shift,
        );
    }

    public function test_receipt_renders_sale_data_and_logs_first_print(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier);

        $this->actingAs($cashier)
            ->get("/store/{$store->slug}/pos/sales/{$sale->id}/receipt")
            ->assertOk()
            ->assertSee($sale->receipt_number)
            ->assertSee('20,000')
            ->assertSee('Cash')
            ->assertSee('5,000'); // change

        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'pos_receipt_printed',
            'entity_type' => 'pos_sale',
            'entity_id' => $sale->id,
        ]);
    }

    public function test_reprint_is_logged_separately_with_count(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier);

        $this->actingAs($cashier)->get("/store/{$store->slug}/pos/sales/{$sale->id}/receipt")->assertOk();
        $this->actingAs($cashier)->get("/store/{$store->slug}/pos/sales/{$sale->id}/receipt")
            ->assertOk()
            ->assertSee('REPRINT');

        $this->assertSame(1, \App\Models\AuditLog::countFor('pos_receipt_printed', 'pos_sale', $sale->id));
        $this->assertSame(1, \App\Models\AuditLog::countFor('pos_receipt_reprinted', 'pos_sale', $sale->id));
        $this->assertSame(2, \App\Models\AuditLog::where('entity_type', 'pos_sale')->where('entity_id', $sale->id)->count());
    }

    public function test_receipt_cross_store_is_blocked(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $cashierA = $this->staff($storeA);
        $cashierB = $this->staff($storeB);
        $sale = $this->postedSale($storeA, $cashierA);

        $this->actingAs($cashierB)
            ->get("/store/{$storeB->slug}/pos/sales/{$sale->id}/receipt")
            ->assertNotFound();

        $this->assertSame(0, \App\Models\AuditLog::where('entity_type', 'pos_sale')->where('entity_id', $sale->id)->count());
    }

    public function test_non_staff_cannot_view_receipt(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier);
        $outsider = User::create([
            'name' => 'Outsider',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->actingAs($outsider)
            ->get("/store/{$store->slug}/pos/sales/{$sale->id}/receipt")
            ->assertForbidden();
    }

    public function test_unposted_sale_has_no_receipt(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);
        $this->sales->addToCart($store, $product->id, null, '1');
        $held = $this->sales->holdCart($store, $cashier, $shift);

        $this->actingAs($cashier)
            ->get("/store/{$store->slug}/pos/sales/{$held->id}/receipt")
            ->assertNotFound();
    }

    /* ------------------------------------------------------------------ */
    /*  Per-line price override (negotiation)                              */
    /* ------------------------------------------------------------------ */

    public function test_line_price_override_prices_cart_and_persists_on_post(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 10000, 'wholesale_price' => 9000]);
        $this->seedStock($store, $product);
        $shift = $this->openShift($store, $cashier);
        $wholesale = $this->customer($store, 'wholesale_customer');

        $this->sales->addToCart($store, $product->id, null, '2');
        $this->sales->attachCartCustomer($store, $wholesale);
        $this->assertSame('9000.00', $this->sales->cartResolved($store)[0]['unit_price']);

        // Cashier negotiates the line down to 8000.
        $this->sales->setCartLinePrice($store, 0, '8000');
        $line = $this->sales->cartResolved($store)[0];
        $this->assertSame('8000.00', $line['unit_price']);
        $this->assertSame('9000.00', $line['original_unit_price']); // tier price it replaced
        $this->assertSame('16000.00', $line['line_total']);
        $this->assertSame('16000.00', $this->sales->cartTotals($store)['total']);

        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '16000']],
            $cashier,
            $shift,
        );

        $this->assertSame('16000.00', (string) $sale->total);
        $item = $sale->items->first();
        $this->assertSame('8000.00', (string) $item->unit_price);
        $this->assertSame('9000.00', (string) $item->original_unit_price);
    }

    public function test_line_price_override_clears_back_to_tier(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 10000, 'wholesale_price' => 9000]);
        $this->seedStock($store, $product);
        $wholesale = $this->customer($store, 'wholesale_customer');

        $this->sales->addToCart($store, $product->id, null, '1');
        $this->sales->attachCartCustomer($store, $wholesale);
        $this->sales->setCartLinePrice($store, 0, '8000');
        $this->assertSame('8000.00', $this->sales->cartResolved($store)[0]['unit_price']);

        // Clearing the override (null) returns the line to the tier price.
        $this->sales->setCartLinePrice($store, 0, null);
        $line = $this->sales->cartResolved($store)[0];
        $this->assertSame('9000.00', $line['unit_price']);
        $this->assertNull($line['original_unit_price']);
    }

    public function test_line_price_override_survives_hold_and_resume(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store, ['retail_price' => 10000]);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');
        $this->sales->setCartLinePrice($store, 0, '7500');
        $held = $this->sales->holdCart($store, $cashier, $shift);
        $this->assertSame('7500.00', (string) $held->total);
        $this->assertSame('7500.00', (string) $held->items->first()->unit_price);
        $this->assertSame('10000.00', (string) $held->items->first()->original_unit_price);

        $this->sales->resumeHeld($store, $held);
        $line = $this->sales->cartResolved($store)[0];
        $this->assertSame('7500.00', $line['unit_price']);
        $this->assertSame('10000.00', $line['original_unit_price']);

        // Posting the resumed sale keeps the negotiated price.
        $posted = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '7500']],
            $cashier,
            $shift,
            $held,
        );
        $this->assertSame('7500.00', (string) $posted->items->first()->unit_price);
        $this->assertSame('10000.00', (string) $posted->items->first()->original_unit_price);
    }
}
