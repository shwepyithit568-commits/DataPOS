<?php

namespace Tests\Feature\POS;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\PosSale;
use App\POS\Services\CashierShiftService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * POS home UI endpoints — product grid + live cart state (AJAX two-panel
 * cashier interface, reference UI from alinthit_pos).
 */
class PosUiEndpointsTest extends TestCase
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

    private function manager(Store $store, string $pin = '1234'): User
    {
        $user = User::create([
            'name' => 'Manager ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
            'pos_pin' => $pin,
        ]);
        $user->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        return $user;
    }

    private function setOverridePinThreshold(Store $store, ?int $percent): void
    {
        \App\Models\StorefrontSetting::updateOrCreate(
            ['store_id' => $store->id],
            ['store_name' => $store->name, 'pos_override_pin_threshold' => $percent],
        );
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

    /* ------------------------------------------------------------------ */
    /*  Product grid                                                       */
    /* ------------------------------------------------------------------ */

    public function test_product_grid_returns_products_with_balances_and_filters(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));

        $cat = Category::create(['store_id' => $store->id, 'name' => 'Glass', 'slug' => 'glass']);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Nillkin', 'slug' => 'nillkin']);
        $glass = $this->makeProduct($store, ['category_id' => $cat->id, 'brand_id' => $brand->id, 'name' => 'iPhone Glass']);
        $case = $this->makeProduct($store, ['name' => 'Case']);
        $this->seedStock($store, $glass, '5');

        $response = $this->getJson("/store/{$store->slug}/pos/products-grid");
        $response->assertOk();
        $response->assertJsonCount(2, 'products');
        // Ordered by name → Case, iPhone Glass.
        $response->assertJsonPath('products.1.id', $glass->id);
        $response->assertJsonPath('products.1.balance', '5.000');
        $response->assertJsonPath('products.1.category', 'Glass');
        $response->assertJsonPath('products.1.brand', 'Nillkin');
        $response->assertJsonCount(1, 'categories');
        $response->assertJsonCount(1, 'brands');

        // Category filter narrows the grid.
        $filtered = $this->getJson("/store/{$store->slug}/pos/products-grid?category_id={$cat->id}");
        $filtered->assertOk();
        $filtered->assertJsonCount(1, 'products');
        $filtered->assertJsonPath('products.0.id', $glass->id);

        // Text query filter.
        $searched = $this->getJson("/store/{$store->slug}/pos/products-grid?q=case");
        $searched->assertOk();
        $searched->assertJsonCount(1, 'products');
        $searched->assertJsonPath('products.0.id', $case->id);
    }

    public function test_product_grid_is_store_scoped(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $this->actingAs($this->staff($storeA));

        $this->makeProduct($storeB, ['name' => 'Secret Store B Phone', 'sku' => 'SECRET-999']);

        $response = $this->getJson("/store/{$storeA->slug}/pos/products-grid");
        $response->assertOk();
        $response->assertJsonCount(0, 'products');
        $response->assertDontSee('SECRET-999');
    }

    /* ------------------------------------------------------------------ */
    /*  Live cart state + AJAX mutations                                   */
    /* ------------------------------------------------------------------ */

    public function test_cart_state_and_json_mutations(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));
        $product = $this->makeProduct($store, ['retail_price' => 10000]);
        $this->seedStock($store, $product, '10');

        // Empty snapshot (same 'cart' wrapper as every AJAX mutation response).
        $empty = $this->getJson("/store/{$store->slug}/pos/cart-state");
        $empty->assertOk();
        $empty->assertJsonCount(0, 'cart.lines');
        $empty->assertJsonPath('cart.shift_open', false);
        $empty->assertJsonPath('cart.expired_count', 0);

        // Add (JSON).
        $added = $this->postJson("/store/{$store->slug}/pos/cart", [
            'product_id' => $product->id,
            'quantity' => '2',
        ]);
        $added->assertOk();
        $added->assertJsonPath('cart.lines.0.product_id', $product->id);
        $added->assertJsonPath('cart.lines.0.quantity', '2');
        $added->assertJsonPath('cart.lines.0.line_total', '20000.00');
        $added->assertJsonPath('cart.totals.total', '20000.00');

        // Update quantity (JSON).
        $updated = $this->postJson("/store/{$store->slug}/pos/cart/0", ['quantity' => '3']);
        $updated->assertOk();
        $updated->assertJsonPath('cart.lines.0.quantity', '3');
        $updated->assertJsonPath('cart.totals.total', '30000.00');

        // Remove (JSON).
        $removed = $this->deleteJson("/store/{$store->slug}/pos/cart/0");
        $removed->assertOk();
        $removed->assertJsonCount(0, 'cart.lines');

        // Clear (JSON).
        $this->postJson("/store/{$store->slug}/pos/cart", ['product_id' => $product->id, 'quantity' => '1'])->assertOk();
        $cleared = $this->postJson("/store/{$store->slug}/pos/cart/clear");
        $cleared->assertOk();
        $cleared->assertJsonCount(0, 'cart.lines');
    }

    public function test_cart_mutation_errors_return_json_422(): void
    {
        // Adding a product from ANOTHER store must fail at add time with a
        // JSON error (422) when the request is AJAX — never a redirect.
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $this->actingAs($this->staff($storeA));
        $productB = $this->makeProduct($storeB);

        $response = $this->postJson("/store/{$storeA->slug}/pos/cart", [
            'product_id' => $productB->id,
            'quantity' => '1',
        ]);
        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Product does not belong to this store.');
    }

    public function test_cart_state_reports_open_shift(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $this->actingAs($cashier);
        $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => 50000], $cashier);

        $state = $this->getJson("/store/{$store->slug}/pos/cart-state");
        $state->assertOk();
        $state->assertJsonPath('cart.shift_open', true);
    }

    /* ------------------------------------------------------------------ */
    /*  Hold / resume / void / post (HTTP — regression for the missing     */
    /*  $store_slug signature param: these used to 500 with a TypeError    */
    /*  because Laravel filled $sale positionally with the store slug).    */
    /* ------------------------------------------------------------------ */

    public function test_hold_resume_void_http_endpoints(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $this->actingAs($cashier);
        $product = $this->makeProduct($store, ['retail_price' => 20000]);
        $this->seedStock($store, $product, '5');
        $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => 0], $cashier);

        // Add 2 units, then hold via HTTP.
        $this->postJson("/store/{$store->slug}/pos/cart", ['product_id' => $product->id, 'quantity' => '2'])->assertOk();
        $held = $this->postJson("/store/{$store->slug}/pos/hold");
        $held->assertOk();
        $held->assertJsonCount(0, 'cart.lines');

        $sale = PosSale::where('store_id', $store->id)->where('status', 'held')->latest('id')->first();
        $this->assertNotNull($sale);

        // Resume via HTTP — regression: previously 500 (TypeError: string given).
        $resumed = $this->postJson("/store/{$store->slug}/pos/resume/{$sale->id}");
        $resumed->assertOk();
        $resumed->assertJsonCount(1, 'cart.lines');
        $resumed->assertJsonPath('cart.lines.0.product_id', $product->id);

        // The recalled sale leaves the held list (marked 'resumed', not held).
        $this->assertSame('resumed', $sale->refresh()->status);
        $this->assertSame(0, $resumed->json('cart.held_count'));
        $this->assertSame([], $resumed->json('cart.held'));

        // A recalled sale cannot be resumed a second time.
        $this->postJson("/store/{$store->slug}/pos/resume/{$sale->id}")->assertStatus(422);

        // Void the recalled sale via HTTP.
        $voided = $this->postJson("/store/{$store->slug}/pos/void/{$sale->id}");
        $voided->assertOk();
        $this->assertSame('voided', $sale->refresh()->status);
    }

    public function test_post_held_sale_via_http_with_sale_param(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $this->actingAs($cashier);
        $product = $this->makeProduct($store, ['retail_price' => 20000]);
        $this->seedStock($store, $product, '5');
        $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => 0], $cashier);

        $this->postJson("/store/{$store->slug}/pos/cart", ['product_id' => $product->id, 'quantity' => '1'])->assertOk();
        $held = $this->postJson("/store/{$store->slug}/pos/hold");
        $held->assertOk();

        // The cart-state payload carries the held list (id, total, items).
        $this->assertSame(1, $held->json('cart.held_count'));
        $this->assertCount(1, $held->json('cart.held'));
        $this->assertSame('20000.00', $held->json('cart.held.0.total'));
        $this->assertSame(1, $held->json('cart.held.0.items_count'));
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $held->json('cart.held.0.held_at'));

        $sale = PosSale::where('store_id', $store->id)->where('status', 'held')->latest('id')->first();
        $this->assertNotNull($sale);

        // Post the held sale directly via /post/{sale} — regression: same
        // missing $store_slug bug would 500 here too.
        $posted = $this->post("/store/{$store->slug}/pos/post/{$sale->id}", [
            'payments' => [['method' => 'cash', 'amount' => '20000']],
        ]);
        $posted->assertRedirect();
        $posted->assertSessionHas('success');
        $this->assertSame('posted', $sale->refresh()->status);
        $this->assertSame('4.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    /* ------------------------------------------------------------------ */
    /*  Customer quick-add (shared users + per-store membership)           */
    /* ------------------------------------------------------------------ */

    public function test_quick_add_customer_creates_user_and_store_membership(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));

        $response = $this->postJson("/store/{$store->slug}/pos/customers", [
            'name' => 'Daw Phyu',
            'phone' => '09 123 456 789',
        ]);

        $response->assertOk();
        $response->assertJsonPath('customer.name', 'Daw Phyu');
        $response->assertJsonPath('customer.phone', '09 123 456 789');

        $user = User::where('phone', '09 123 456 789')->first();
        $this->assertNotNull($user);
        $this->assertSame('customer', $user->role); // role tampering impossible
        $this->assertTrue($user->hasStoreRole($store->id, 'retail_customer'));
    }

    public function test_quick_add_customer_dedups_by_normalized_phone(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));

        // Same person, different phone spellings — one user record only.
        $first = $this->postJson("/store/{$store->slug}/pos/customers", [
            'name' => 'U Ba', 'phone' => '09 123 456 789',
        ])->assertOk();
        $second = $this->postJson("/store/{$store->slug}/pos/customers", [
            'name' => 'U Ba', 'phone' => '09123456789',
        ])->assertOk();

        $this->assertSame($first->json('customer.id'), $second->json('customer.id'));
        $this->assertSame(1, User::all()->filter(fn (User $u) => User::normalizePhone($u->phone) === '9123456789')->count());
        $this->assertArrayHasKey('cart', $second->json()); // live cart snapshot included
    }

    public function test_quick_add_customer_attaches_existing_user_from_other_store(): void
    {
        $storeA = $this->makeStore('shop-a');
        $storeB = $this->makeStore('shop-b');
        $staffA = $this->staff($storeA);
        $staffB = $this->staff($storeB);

        // A customer already enrolled at store A (by store A's own staff).
        $this->actingAs($staffA);
        $existing = $this->postJson("/store/{$storeA->slug}/pos/customers", [
            'name' => 'Ma Hla', 'phone' => '09777123456',
        ])->assertOk();

        // The same person walks into store B — attaching keeps ONE user record
        // and adds a second per-store membership (no cross-store list leak).
        $this->actingAs($staffB);
        $attached = $this->postJson("/store/{$storeB->slug}/pos/customers", [
            'name' => 'Ma Hla', 'phone' => '09777123456',
        ])->assertOk();

        $this->assertSame($existing->json('customer.id'), $attached->json('customer.id'));
        $this->assertSame(1, User::all()->filter(fn (User $u) => User::normalizePhone($u->phone) === '9777123456')->count());
        $this->assertTrue($attached->json('customer')['id'] != null);
        $user = User::where('id', $attached->json('customer.id'))->first();
        $this->assertTrue($user->hasStoreRole($storeA->id, 'retail_customer'));
        $this->assertTrue($user->hasStoreRole($storeB->id, 'retail_customer'));
    }

    public function test_quick_add_customer_rejects_staff_phone(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $this->actingAs($cashier);

        // A staff account can never be claimed as a customer.
        $this->postJson("/store/{$store->slug}/pos/customers", [
            'name' => 'Hacker', 'phone' => $cashier->phone,
        ])->assertStatus(422)->assertJsonPath('error', __('messages.pos_customer_staff_phone'));
    }

    public function test_quick_add_customer_validates_phone(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));

        $this->postJson("/store/{$store->slug}/pos/customers", [
            'name' => 'No Phone', 'phone' => '12',
        ])->assertStatus(422)->assertJsonPath('error', __('messages.pos_customer_invalid_phone'));
    }

    /* ------------------------------------------------------------------ */
    /*  Customer tier (retail / wholesale) — quick-add type + attach       */
    /* ------------------------------------------------------------------ */

    public function test_quick_add_wholesale_customer_sets_tier_and_prices_cart(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));
        $product = $this->makeProduct($store, ['retail_price' => 10000, 'wholesale_price' => 9000]);
        $this->seedStock($store, $product);

        $response = $this->postJson("/store/{$store->slug}/pos/customers", [
            'name' => 'U Ko', 'phone' => '09111222333', 'type' => 'wholesale_customer',
        ])->assertOk();

        $this->assertSame('wholesale_customer', $response->json('customer.role'));
        $this->assertSame('wholesale_customer', $response->json('cart.customer.role'));
        $user = User::where('id', $response->json('customer.id'))->first();
        $this->assertTrue($user->hasStoreRole($store->id, 'wholesale_customer'));

        // The attached wholesale tier prices the cart at wholesale immediately.
        $this->postJson("/store/{$store->slug}/pos/cart", ['product_id' => $product->id, 'quantity' => '1'])->assertOk();
        $cart = $this->getJson("/store/{$store->slug}/pos/cart-state")->json('cart');
        $this->assertSame('9000.00', $cart['lines'][0]['unit_price']);
        $this->assertSame('9000.00', $cart['totals']['total']);

        // The line + totals carry the retail comparison so the cashier sees
        // the discount being applied (Ks 10,000 retail → Ks 9,000 wholesale).
        $this->assertSame('10000.00', $cart['lines'][0]['retail_unit_price']);
        $this->assertSame('10000.00', $cart['lines'][0]['line_retail_total']);
        $this->assertSame('10000.00', $cart['totals']['retail_subtotal']);
    }

    public function test_quick_add_retail_customer_keeps_retail_pricing(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));
        $product = $this->makeProduct($store, ['retail_price' => 10000, 'wholesale_price' => 9000]);
        $this->seedStock($store, $product);

        $this->postJson("/store/{$store->slug}/pos/customers", [
            'name' => 'Daw Nyo', 'phone' => '09111222334', 'type' => 'retail_customer',
        ])->assertOk()->assertJsonPath('cart.customer.role', 'retail_customer');

        $this->postJson("/store/{$store->slug}/pos/cart", ['product_id' => $product->id, 'quantity' => '1'])->assertOk();
        $cart = $this->getJson("/store/{$store->slug}/pos/cart-state")->json('cart');
        $this->assertSame('10000.00', $cart['lines'][0]['unit_price']);
    }

    public function test_attach_and_detach_endpoints_control_cart_customer(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $this->actingAs($cashier);
        $customer = $this->postJson("/store/{$store->slug}/pos/customers", [
            'name' => 'Ma Cho', 'phone' => '09777888777',
        ])->json('customer');

        // Quick-add auto-attaches; detach to start from walk-in.
        $this->postJson("/store/{$store->slug}/pos/customers/detach")->assertOk();
        $this->assertNull($this->getJson("/store/{$store->slug}/pos/cart-state")->json('cart.customer'));

        $this->postJson("/store/{$store->slug}/pos/customers/{$customer['id']}/attach")->assertOk();
        $cart = $this->getJson("/store/{$store->slug}/pos/cart-state")->json('cart');
        $this->assertSame($customer['id'], $cart['customer']['id']);
        $this->assertSame('retail_customer', $cart['customer']['role']);
    }

    public function test_attach_cross_store_customer_is_rejected(): void
    {
        $storeA = $this->makeStore('shop-a');
        $storeB = $this->makeStore('shop-b');
        $this->actingAs($this->staff($storeA));
        $customer = $this->postJson("/store/{$storeA->slug}/pos/customers", [
            'name' => 'Ma Cho', 'phone' => '09777888778',
        ])->json('customer');

        $this->actingAs($this->staff($storeB));
        $this->postJson("/store/{$storeB->slug}/pos/customers/{$customer['id']}/attach")
            ->assertStatus(422);
    }

    public function test_product_grid_shows_wholesale_prices_for_attached_wholesale_customer(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));
        $product = $this->makeProduct($store, ['retail_price' => 10000, 'wholesale_price' => 9000]);
        $this->seedStock($store, $product);

        $grid = $this->getJson("/store/{$store->slug}/pos/products-grid")->json('products');
        $this->assertSame('10000.00', $grid[0]['price']);
        $this->assertSame('retail', $grid[0]['tier']);
        $this->assertSame('10000.00', $grid[0]['retail_price']);

        $this->postJson("/store/{$store->slug}/pos/customers", [
            'name' => 'U Mya', 'phone' => '09777444555', 'type' => 'wholesale_customer',
        ])->assertOk();

        $grid = $this->getJson("/store/{$store->slug}/pos/products-grid")->json('products');
        $this->assertSame('9000.00', $grid[0]['price']);
        $this->assertSame('wholesale', $grid[0]['tier']);
        // The card shows the retail comparison so the cashier sees the discount.
        $this->assertSame('10000.00', $grid[0]['retail_price']);
    }

    public function test_customers_search_returns_role_for_tier_badge(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));
        $this->postJson("/store/{$store->slug}/pos/customers", [
            'name' => 'Daw Yee', 'phone' => '09777222333', 'type' => 'wholesale_customer',
        ])->assertOk();

        $customers = $this->getJson("/store/{$store->slug}/pos/customers?q=yee")->json('customers');
        $this->assertSame('wholesale_customer', $customers[0]['role']);
    }

    public function test_line_price_override_endpoint(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));
        $product = $this->makeProduct($store, ['retail_price' => 10000]);
        $this->seedStock($store, $product, '10');

        $this->postJson("/store/{$store->slug}/pos/cart", [
            'product_id' => $product->id,
            'quantity' => '2',
        ])->assertOk();

        // Set a negotiated price via the endpoint.
        $overridden = $this->postJson("/store/{$store->slug}/pos/cart/0/price", ['unit_price' => '8500']);
        $overridden->assertOk();
        $overridden->assertJsonPath('cart.lines.0.unit_price', '8500.00');
        $overridden->assertJsonPath('cart.lines.0.original_unit_price', '10000.00');
        $overridden->assertJsonPath('cart.totals.total', '17000.00');

        // Empty value clears the override back to the tier price.
        $cleared = $this->postJson("/store/{$store->slug}/pos/cart/0/price", ['unit_price' => '']);
        $cleared->assertOk();
        $cleared->assertJsonPath('cart.lines.0.unit_price', '10000.00');
        $cleared->assertJsonPath('cart.lines.0.original_unit_price', null);
        $cleared->assertJsonPath('cart.totals.total', '20000.00');

        // Negative price is rejected.
        $this->postJson("/store/{$store->slug}/pos/cart/0/price", ['unit_price' => '-5'])
            ->assertStatus(422);
    }

    /* ------------------------------------------------------------------ */
    /*  Manager-PIN approval for deep price overrides                       */
    /* ------------------------------------------------------------------ */

    public function test_price_override_below_threshold_needs_no_pin(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));
        $this->setOverridePinThreshold($store, 50);
        $product = $this->makeProduct($store, ['retail_price' => 10000]);
        $this->seedStock($store, $product);
        $this->postJson("/store/{$store->slug}/pos/cart", ['product_id' => $product->id, 'quantity' => '1'])->assertOk();

        // 20% discount (8000 vs 10000) is under the 50% threshold — no PIN.
        $response = $this->postJson("/store/{$store->slug}/pos/cart/0/price", ['unit_price' => '8000']);
        $response->assertOk();
        $response->assertJsonPath('cart.lines.0.unit_price', '8000.00');
        $response->assertJsonPath('cart.lines.0.approved_by', null);
    }

    public function test_price_override_above_threshold_requires_manager_pin(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));
        $this->setOverridePinThreshold($store, 10);
        $product = $this->makeProduct($store, ['retail_price' => 10000]);
        $this->seedStock($store, $product);
        $this->postJson("/store/{$store->slug}/pos/cart", ['product_id' => $product->id, 'quantity' => '1'])->assertOk();

        // 20% discount exceeds 10% — 422 with the pin_required flag.
        $response = $this->postJson("/store/{$store->slug}/pos/cart/0/price", ['unit_price' => '8000']);
        $response->assertStatus(422);
        $response->assertJsonPath('pin_required', true);
        $response->assertJsonPath('error', __('messages.pos_price_pin_required'));

        // The line is untouched — still the tier price.
        $this->getJson("/store/{$store->slug}/pos/cart-state")
            ->assertJsonPath('cart.lines.0.unit_price', '10000.00');
    }

    public function test_price_override_wrong_manager_pin_is_rejected(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));
        $this->setOverridePinThreshold($store, 10);
        $this->manager($store, '1234');
        $product = $this->makeProduct($store, ['retail_price' => 10000]);
        $this->seedStock($store, $product);
        $this->postJson("/store/{$store->slug}/pos/cart", ['product_id' => $product->id, 'quantity' => '1'])->assertOk();

        $response = $this->postJson("/store/{$store->slug}/pos/cart/0/price", [
            'unit_price' => '8000',
            'manager_pin' => '9999',
        ]);
        $response->assertStatus(422);
        $response->assertJsonPath('pin_required', true);
        $response->assertJsonPath('error', __('messages.pos_price_pin_invalid'));
    }

    public function test_price_override_with_correct_manager_pin_succeeds_and_records_approver(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $this->actingAs($cashier);
        $this->setOverridePinThreshold($store, 10);
        $manager = $this->manager($store, '1234');
        $product = $this->makeProduct($store, ['retail_price' => 10000]);
        $this->seedStock($store, $product, '5');
        $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => 0], $cashier);
        $this->postJson("/store/{$store->slug}/pos/cart", ['product_id' => $product->id, 'quantity' => '2'])->assertOk();

        // With the manager PIN the deep override goes through and is attributed.
        $response = $this->postJson("/store/{$store->slug}/pos/cart/0/price", [
            'unit_price' => '8000',
            'manager_pin' => '1234',
        ]);
        $response->assertOk();
        $response->assertJsonPath('cart.lines.0.unit_price', '8000.00');
        $response->assertJsonPath('cart.lines.0.approved_by', $manager->id);
        $response->assertJsonPath('cart.lines.0.approved_by_name', $manager->name);

        // The approver survives posting onto the sale item (audit trail).
        $posted = $this->post("/store/{$store->slug}/pos/post", [
            'payments' => [['method' => 'cash', 'amount' => '16000']],
            'customer_id' => null,
        ]);
        $posted->assertRedirect();

        $item = \App\POS\Models\PosSaleItem::where('unit_price', '8000.00')->latest('id')->first();
        $this->assertNotNull($item);
        $this->assertSame((int) $manager->id, (int) $item->approved_by);
        $this->assertSame('10000.00', (string) $item->original_unit_price);
    }

    public function test_price_override_threshold_disabled_needs_no_pin(): void
    {
        $store = $this->makeStore();
        $this->actingAs($this->staff($store));
        // No settings row / threshold null → PIN enforcement off.
        $product = $this->makeProduct($store, ['retail_price' => 10000]);
        $this->seedStock($store, $product);
        $this->postJson("/store/{$store->slug}/pos/cart", ['product_id' => $product->id, 'quantity' => '1'])->assertOk();

        $response = $this->postJson("/store/{$store->slug}/pos/cart/0/price", ['unit_price' => '5000']);
        $response->assertOk();
        $response->assertJsonPath('cart.lines.0.unit_price', '5000.00');
        $response->assertJsonPath('cart.lines.0.approved_by', null);
    }
}
