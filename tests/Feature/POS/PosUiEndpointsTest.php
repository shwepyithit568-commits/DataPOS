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
}
