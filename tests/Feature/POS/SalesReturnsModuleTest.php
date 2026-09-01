<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\PosReturn;
use App\POS\Models\PosSale;
use App\POS\Services\CashierShiftService;
use App\POS\Services\CustomerDebtService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosReturnService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sales Returns management module (roadmap Phase 2) — the list / select-sale /
 * show layer over the already-proven PosReturnService posting machinery.
 */
class SalesReturnsModuleTest extends TestCase
{
    use RefreshDatabase;

    private PosSaleService $sales;

    private PosReturnService $returns;

    private InventoryService $inventory;

    private CashierShiftService $shifts;

    private CustomerDebtService $debts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sales = app(PosSaleService::class);
        $this->returns = app(PosReturnService::class);
        $this->inventory = app(InventoryService::class);
        $this->shifts = app(CashierShiftService::class);
        $this->debts = app(CustomerDebtService::class);
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers (mirror PosReturnTest)                                     */
    /* ------------------------------------------------------------------ */

    private function makeStore(string $slug = 'returns-shop'): Store
    {
        return Store::create(['name' => 'Returns Shop', 'slug' => $slug, 'is_active' => true]);
    }

    private function staff(Store $store, string $role = 'staff'): User
    {
        $user = User::create([
            'name' => 'Cashier ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    private function customer(Store $store): User
    {
        $user = User::create([
            'name' => 'Customer ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'retail_customer', 'status' => 'active']);

        return $user;
    }

    private function makeProduct(Store $store, int $price = 10000): Product
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

    private function postedSale(Store $store, User $cashier, int $price = 10000, string $qty = '2'): PosSale
    {
        $product = $this->makeProduct($store, $price);
        $this->seedStock($store, $product, '10');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, $qty);
        $total = bcadd('0', bcmul((string) $price, $qty, 2), 2);

        return $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => $total]],
            $cashier,
            $shift,
            null,
            null,
        );
    }

    private function postedReturn(Store $store, PosSale $sale, User $cashier): PosReturn
    {
        $items = $sale->items->map(fn ($i) => [
            'pos_sale_item_id' => $i->id,
            'quantity' => (string) $i->quantity,
        ])->all();

        return $this->returns->post(
            $store,
            $sale,
            $items,
            [['method' => 'cash', 'amount' => (string) $sale->total]],
            $cashier,
            $this->shifts->openShiftFor($store, $cashier),
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Index                                                              */
    /* ------------------------------------------------------------------ */

    public function test_manager_can_view_returns_index(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');

        $response = $this->actingAs($manager)->get("/store/{$store->slug}/pos/returns");

        $response->assertOk();
        $response->assertSeeText(__('messages.returns_title'));
        $response->assertSee(route('pos.returns.create', ['store_slug' => $store->slug]), false);
    }

    public function test_staff_can_view_returns_index(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);

        $response = $this->actingAs($staff)->get("/store/{$store->slug}/pos/returns");

        $response->assertOk();
    }

    public function test_index_lists_posted_return_with_receipt(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier, 15000, '2');
        $refund = $this->postedReturn($store, $sale, $cashier);

        $response = $this->actingAs($cashier)->get("/store/{$store->slug}/pos/returns");

        $response->assertOk();
        $response->assertSee($refund->refund_number, false);
        $response->assertSee($sale->receipt_number, false);
    }

    public function test_index_search_filters_by_refund_number(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $refund = $this->postedReturn($store, $this->postedSale($store, $cashier, 9000, '1'), $cashier);

        $response = $this->actingAs($cashier)
            ->get("/store/{$store->slug}/pos/returns?search=" . substr($refund->refund_number, 0, 8));

        $response->assertOk();
        $response->assertSee($refund->refund_number, false);
    }

    /* ------------------------------------------------------------------ */
    /*  Create (select sale)                                               */
    /* ------------------------------------------------------------------ */

    public function test_create_page_lists_posted_sales(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier, 12000, '1');

        $response = $this->actingAs($cashier)->get("/store/{$store->slug}/pos/returns/new");

        $response->assertOk();
        $response->assertSee($sale->receipt_number, false);
        $response->assertSee(route('pos.refund.create', ['store_slug' => $store->slug, 'sale' => $sale->id]), false);
    }

    public function test_create_page_empty_state_without_posted_sales(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);

        $response = $this->actingAs($staff)->get("/store/{$store->slug}/pos/returns/new");

        $response->assertOk();
        $response->assertSeeText(__('messages.no_sales_found'));
    }

    /* ------------------------------------------------------------------ */
    /*  Show                                                               */
    /* ------------------------------------------------------------------ */

    public function test_show_page_renders_return_detail(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier, 15000, '2');
        $refund = $this->postedReturn($store, $sale, $cashier);

        $response = $this->actingAs($cashier)->get("/store/{$store->slug}/pos/returns/{$refund->id}");

        $response->assertOk();
        $response->assertSee($refund->refund_number, false);
        $response->assertSee($sale->receipt_number, false);
        $response->assertSee($refund->items->first()->product_name, false);
    }

    /** Posting a refund through the sale-scoped form lands back on the return detail. */
    public function test_refund_post_redirects_to_return_detail(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier, 15000, '2');

        $items = $sale->items->map(fn ($i) => [
            'pos_sale_item_id' => $i->id,
            'quantity' => (string) $i->quantity,
        ])->all();

        $response = $this->actingAs($cashier)->post(
            "/store/{$store->slug}/pos/sales/{$sale->id}/refunds",
            [
                'items' => $items,
                'refunds' => [['method' => 'cash', 'amount' => '30000']],
            ]
        );

        $refund = PosReturn::where('store_id', $store->id)->latest('id')->first();

        $response->assertRedirect(route('pos.returns.show', ['store_slug' => $store->slug, 'return' => $refund->id]));
    }

    /* ------------------------------------------------------------------ */
    /*  Access control                                                     */
    /* ------------------------------------------------------------------ */

    public function test_cross_store_return_is_blocked(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $managerA = $this->staff($storeA, 'store_manager');
        $managerB = $this->staff($storeB, 'store_manager');

        $sale = $this->postedSale($storeB, $managerB, 10000, '1');
        $refund = $this->postedReturn($storeB, $sale, $managerB);

        // Manager of store A is not a member of store B → middleware rejects.
        $response = $this->actingAs($managerA)->get("/store/{$storeB->slug}/pos/returns");
        $response->assertForbidden();

        // Member of store B opening a return that belongs to store A → 404.
        $saleA = $this->postedSale($storeA, $managerA, 10000, '1');
        $refundA = $this->postedReturn($storeA, $saleA, $managerA);

        $response = $this->actingAs($managerB)->get("/store/{$storeB->slug}/pos/returns/{$refundA->id}");
        $response->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $store = $this->makeStore();

        $response = $this->get("/store/{$store->slug}/pos/returns");

        $response->assertRedirect(route('login'));
    }

    public function test_customer_without_staff_role_is_blocked(): void
    {
        $store = $this->makeStore();
        $buyer = $this->customer($store);

        $response = $this->actingAs($buyer)->get("/store/{$store->slug}/pos/returns");

        $response->assertForbidden();
    }

    /* ------------------------------------------------------------------ */
    /*  Navigation / roadmap                                               */
    /* ------------------------------------------------------------------ */

    public function test_sidebar_links_returns_to_the_new_module_not_placeholder(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');

        $response = $this->actingAs($manager)->get("/store/{$store->slug}/pos/returns");

        $response->assertOk();
        // The sidebar now points at the real module.
        $response->assertSee('/pos/returns', false);
        // The placeholder route for returns is gone from the roadmap registry.
        $this->assertArrayNotHasKey('returns', \App\Http\Controllers\Admin\ComingSoonController::modules());
    }

    public function test_coming_soon_returns_slug_now_404s(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');

        $response = $this->actingAs($manager)->get("/store/{$store->slug}/admin/coming-soon/returns");

        $response->assertNotFound();
    }

    public function test_manager_can_export_returns_as_csv_and_xlsx(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');
        $sale = $this->postedSale($store, $manager);
        $this->postedReturn($store, $sale, $manager);

        // CSV export
        $csvResponse = $this->actingAs($manager)->get("/store/{$store->slug}/pos/returns/export?format=csv");
        $csvResponse->assertOk();
        $this->assertStringContainsString('.csv', (string) $csvResponse->headers->get('content-disposition'));

        // XLSX export
        $xlsxResponse = $this->actingAs($manager)->get("/store/{$store->slug}/pos/returns/export?format=xlsx");
        $xlsxResponse->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $xlsxResponse->headers->get('content-disposition'));
    }
}
