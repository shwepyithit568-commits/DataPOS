<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\PosSale;
use App\POS\Services\CashierShiftService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosReportService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosReportTest extends TestCase
{
    use RefreshDatabase;

    private PosReportService $reports;

    private PosSaleService $sales;

    private InventoryService $inventory;

    private CashierShiftService $shifts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reports = app(PosReportService::class);
        $this->sales = app(PosSaleService::class);
        $this->inventory = app(InventoryService::class);
        $this->shifts = app(CashierShiftService::class);
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

    /** Post a sale for a fixed amount; returns the sale. */
    private function postSale(Store $store, User $cashier, array $payments, string $price = '10000'): PosSale
    {
        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-' . Str::random(2), 'opening_cash' => 50000], $cashier);
        $product = $this->makeProduct($store, ['retail_price' => $price]);
        $this->seedStock($store, $product, '10');
        $this->sales->addToCart($store, $product->id, null, '1');

        return $this->sales->post($store, $this->sales->cartLines($store), $payments, $cashier, $shift);
    }

    /* ------------------------------------------------------------------ */
    /*  Sales report                                                       */
    /* ------------------------------------------------------------------ */

    public function test_sales_report_totals_and_method_breakdown(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $this->postSale($store, $cashier, [['method' => 'cash', 'amount' => '12000']], '12000');
        $this->postSale($store, $cashier, [
            ['method' => 'cash', 'amount' => '5000'],
            ['method' => 'kpay', 'amount' => '5000'],
        ], '10000');

        $report = $this->reports->salesReport($store, Carbon::today(), Carbon::today());

        $this->assertSame(2, $report['count']);
        $this->assertSame('22000.00', $report['total']);
        $this->assertSame('17000.00', $report['methods']['cash']);
        $this->assertSame('5000.00', $report['methods']['kpay']);
    }

    public function test_sales_report_filters_by_cashier_and_range(): void
    {
        $store = $this->makeStore();
        $cashierA = $this->staff($store);
        $cashierB = $this->staff($store);

        $this->postSale($store, $cashierA, [['method' => 'cash', 'amount' => '10000']]);

        $saleB = $this->postSale($store, $cashierB, [['method' => 'cash', 'amount' => '10000']]);
        $saleB->update(['posted_at' => Carbon::yesterday()->setTime(12, 0)]);

        // Today, cashier A only → 1 sale, 10000.
        $report = $this->reports->salesReport($store, Carbon::today(), Carbon::today(), $cashierA->id);
        $this->assertSame(1, $report['count']);
        $this->assertSame('10000.00', $report['total']);

        // Both days, all cashiers → 2 sales.
        $report = $this->reports->salesReport($store, Carbon::yesterday(), Carbon::today());
        $this->assertSame(2, $report['count']);

        // Yesterday only → 1 sale (cashier B's).
        $report = $this->reports->salesReport($store, Carbon::yesterday(), Carbon::yesterday());
        $this->assertSame(1, $report['count']);
        $this->assertSame('10000.00', $report['total']);
    }

    public function test_sales_report_is_store_scoped(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $this->postSale($storeB, $this->staff($storeB), [['method' => 'cash', 'amount' => '10000']]);

        $report = $this->reports->salesReport($storeA, Carbon::today(), Carbon::today());

        $this->assertSame(0, $report['count']);
        $this->assertSame('0', $report['total']);
    }

    /* ------------------------------------------------------------------ */
    /*  Cash drawer report                                                 */
    /* ------------------------------------------------------------------ */

    public function test_cash_report_aggregates_shift_drawer_math(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $shiftA = $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);
        $shiftA->update(['cash_sales' => 30000, 'cash_refunds' => 5000]);
        $this->shifts->addCashEvent($shiftA, ['type' => 'cash_in', 'amount' => 10000], $cashier);
        $this->shifts->closeShift($shiftA, ['actual_closing_amount' => 85000], $cashier);

        $report = $this->reports->cashReport($store, Carbon::today(), Carbon::today());

        $this->assertSame(1, $report['shift_count']);
        $this->assertSame('50000.00', $report['opening_cash']);
        $this->assertSame('30000.00', $report['cash_sales']);
        $this->assertSame('5000.00', $report['cash_refunds']);
        $this->assertSame('10000.00', $report['cash_in']);
        // expected = 50000 + 30000 − 5000 + 10000 = 85000
        $this->assertSame('85000.00', $report['expected']);
        $this->assertSame('85000.00', $report['actual']);
        $this->assertSame('0.00', $report['difference']);
    }

    public function test_cash_report_covers_only_requested_range(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $shift = $this->shifts->openShift($store, ['register_name' => 'R1', 'opening_cash' => 50000], $cashier);
        $this->shifts->closeShift($shift, ['actual_closing_amount' => 50000], $cashier);

        $report = $this->reports->cashReport($store, Carbon::today()->subDays(2), Carbon::today()->subDays(1));

        $this->assertSame(0, $report['shift_count']);
    }

    /* ------------------------------------------------------------------ */
    /*  Stock report                                                       */
    /* ------------------------------------------------------------------ */

    public function test_stock_report_shows_ledger_qty_cost_and_value(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, ['name' => 'S24 Ultra TG', 'sku' => 'SAM-S24-TG']);
        $this->seedStock($store, $product, '10', '9000');

        $report = $this->reports->stockReport($store);

        $this->assertSame(1, $report['rows']->count());
        $row = $report['rows']->first();
        $this->assertSame('10.000', $row['quantity_on_hand']);
        $this->assertSame('9000.0000', $row['unit_cost_avg']);
        $this->assertSame('90000.00', $row['value']);
        $this->assertSame('90000.00', $report['total_value']);
        $this->assertSame('10.000', $report['total_units']);
    }

    public function test_stock_report_searches_by_name_and_sku_and_is_store_scoped(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');

        $productA = $this->makeProduct($storeA, ['name' => 'Unique Phone A', 'sku' => 'AAA-111']);
        $this->seedStock($storeA, $productA, '5', '7000');
        $productB = $this->makeProduct($storeB, ['name' => 'Unique Phone B', 'sku' => 'BBB-222']);
        $this->seedStock($storeB, $productB, '5', '7000');

        // Name search finds only store A's product.
        $report = $this->reports->stockReport($storeA, 'Unique Phone A');
        $this->assertSame(1, $report['rows']->count());
        $this->assertSame($productA->id, $report['rows']->first()['product']->id);

        // SKU search on store A never leaks store B's product.
        $report = $this->reports->stockReport($storeA, 'BBB-222');
        $this->assertSame(0, $report['rows']->count());
    }

    /* ------------------------------------------------------------------ */
    /*  HTTP + authorization                                               */
    /* ------------------------------------------------------------------ */

    public function test_report_pages_render_for_staff(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $this->postSale($store, $cashier, [['method' => 'cash', 'amount' => '10000']]);

        $this->actingAs($cashier);

        $this->get("/store/{$store->slug}/pos/reports/sales")
            ->assertOk()
            ->assertSee(__('messages.reports_sales'))
            ->assertSee('RCP-');
        $this->get("/store/{$store->slug}/pos/reports/cash")->assertOk()->assertSee(__('messages.expected_cash'));
        $this->get("/store/{$store->slug}/pos/reports/stock")->assertOk()->assertSee(__('messages.reports_stock_value'));
    }

    public function test_non_staff_cannot_view_reports(): void
    {
        $store = $this->makeStore();
        $outsider = User::create([
            'name' => 'Outsider',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->actingAs($outsider)
            ->get("/store/{$store->slug}/pos/reports/sales")
            ->assertForbidden();
    }
}
