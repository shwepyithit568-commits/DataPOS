<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\POS\Services\CashierShiftService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosReportService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommercialTaxTest extends TestCase
{
    use RefreshDatabase;

    private PosSaleService $sales;
    private PosReportService $reports;
    private InventoryService $inventory;
    private CashierShiftService $shifts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sales = app(PosSaleService::class);
        $this->reports = app(PosReportService::class);
        $this->inventory = app(InventoryService::class);
        $this->shifts = app(CashierShiftService::class);
    }

    private function makeStore(string $slug = 'tax-shop', array $taxSettings = []): Store
    {
        $store = Store::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
        ]);

        $defaultPosSettings = array_merge([
            'enable_tax' => true,
            'default_tax_rate' => 5.0,
            'tax_type' => 'exclusive',
            'show_tax_id' => true,
            'tax_id_number' => 'TIN-987654321',
        ], $taxSettings);

        StorefrontSetting::create([
            'store_id' => $store->id,
            'store_name' => $store->name,
            'pos_settings' => $defaultPosSettings,
        ]);

        return $store;
    }

    private function staff(Store $store): User
    {
        $user = User::create([
            'name' => 'Cashier ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'staff',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        return $user;
    }

    private function makeProduct(Store $store, string $name, float $price, bool $taxable = true, ?float $customRate = null): Product
    {
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-' . Str::upper(Str::random(6)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(3)),
            'retail_price' => $price,
            'wholesale_price' => $price,
            'is_active' => true,
            'is_taxable' => $taxable,
            'tax_rate' => $customRate,
        ]);

        $this->inventory->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => '20',
            'unit_cost' => 5000,
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed:' . Str::uuid(),
            'occurred_at' => now(),
        ]);

        return $product;
    }

    private function openShift(Store $store, User $cashier)
    {
        return $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => 50000], $cashier);
    }

    public function test_exclusive_tax_calculation_and_post(): void
    {
        $store = $this->makeStore('shop-exclusive', [
            'enable_tax' => true,
            'default_tax_rate' => 5.0,
            'tax_type' => 'exclusive',
        ]);
        $cashier = $this->staff($store);
        $shift = $this->openShift($store, $cashier);

        $taxableProd = $this->makeProduct($store, 'Laptop Screen', 10000, true);
        $exemptProd = $this->makeProduct($store, 'Basic Medicine/Exempt', 5000, false);

        $this->sales->addToCart($store, $taxableProd->id, null, '1');
        $this->sales->addToCart($store, $exemptProd->id, null, '1');

        $totals = $this->sales->cartTotals($store);

        // Subtotal = 10000 + 5000 = 15000
        // Taxable = 10000, Tax (5%) = 500
        // Total = 15000 + 500 = 15500
        $this->assertEquals('15000.00', $totals['subtotal']);
        $this->assertEquals('500.00', $totals['tax']);
        $this->assertEquals('exclusive', $totals['tax_type']);
        $this->assertEquals('10000.00', $totals['taxable_subtotal']);
        $this->assertEquals('5000.00', $totals['exempt_subtotal']);
        $this->assertEquals('15500.00', $totals['total']);

        // Post sale
        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '15500']],
            $cashier,
            $shift,
        );

        $this->assertTrue($sale->isPosted());
        $this->assertEquals('15500.00', (string) $sale->total);
        $this->assertEquals('500.00', (string) $sale->tax);
        $this->assertEquals('exclusive', $sale->tax_type);
        $this->assertEquals('10000.00', (string) $sale->taxable_amount);
        $this->assertEquals('5000.00', (string) $sale->exempt_amount);

        // Check sale items
        $taxableItem = $sale->items->firstWhere('product_id', $taxableProd->id);
        $this->assertTrue((bool) $taxableItem->is_taxable);
        $this->assertEquals('500.00', (string) $taxableItem->tax_amount);

        $exemptItem = $sale->items->firstWhere('product_id', $exemptProd->id);
        $this->assertFalse((bool) $exemptItem->is_taxable);
        $this->assertEquals('0.00', (string) $exemptItem->tax_amount);
    }

    public function test_inclusive_tax_calculation_and_post(): void
    {
        $store = $this->makeStore('shop-inclusive', [
            'enable_tax' => true,
            'default_tax_rate' => 5.0,
            'tax_type' => 'inclusive',
        ]);
        $cashier = $this->staff($store);
        $shift = $this->openShift($store, $cashier);

        $taxableProd = $this->makeProduct($store, 'USB Cable', 10500, true);
        $this->sales->addToCart($store, $taxableProd->id, null, '1');

        $totals = $this->sales->cartTotals($store);

        // Inclusive tax: total = 10500, tax = 10500 * 5 / 105 = 500.00
        $this->assertEquals('10500.00', $totals['subtotal']);
        $this->assertEquals('500.00', $totals['tax']);
        $this->assertEquals('inclusive', $totals['tax_type']);
        $this->assertEquals('10500.00', $totals['total']);

        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '10500']],
            $cashier,
            $shift,
        );

        $this->assertEquals('10500.00', (string) $sale->total);
        $this->assertEquals('500.00', (string) $sale->tax);
        $this->assertEquals('inclusive', $sale->tax_type);
        $this->assertEquals('10500.00', (string) $sale->taxable_amount);
    }

    public function test_commercial_tax_report_and_endpoints(): void
    {
        $store = $this->makeStore('tax-rep-shop', [
            'enable_tax' => true,
            'default_tax_rate' => 5.0,
            'tax_type' => 'exclusive',
        ]);
        $cashier = $this->staff($store);
        $shift = $this->openShift($store, $cashier);

        $taxableProd = $this->makeProduct($store, 'Phone Battery', 20000, true);
        $this->sales->addToCart($store, $taxableProd->id, null, '1');

        // Post sale: 20000 + 1000 (tax) = 21000
        $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '21000']],
            $cashier,
            $shift,
        );

        $today = Carbon::today();
        $report = $this->reports->taxReport($store, $today, $today);

        $this->assertEquals(1, $report['count']);
        $this->assertEquals('21000.00', $report['total_sales']);
        $this->assertEquals('20000.00', $report['taxable_sales']);
        $this->assertEquals('0.00', $report['exempt_sales']);
        $this->assertEquals('1000.00', $report['total_tax']);
        $this->assertEquals('20000.00', $report['net_sales']);

        // Create manager with full report permissions
        $manager = User::create([
            'name' => 'Store Manager',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'staff',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        // Test HTTP Report View
        $response = $this->actingAs($manager)
            ->get(route('pos.reports.tax', ['store_slug' => $store->slug]));
        $response->assertOk();
        $response->assertSee('TIN-987654321');
        $response->assertSee('21,000');
    }

    public function test_tax_report_aliases_do_not_404(): void
    {
        $store = $this->makeStore('tax-alias-shop');
        $manager = User::create([
            'name' => 'Alias Manager',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'staff',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        // 1. Direct /store/{slug}/reports/tax
        $this->actingAs($manager)
            ->get("/store/{$store->slug}/reports/tax")
            ->assertOk();

        // 2. Admin URL /store/{slug}/admin/reports/tax
        $this->actingAs($manager)
            ->get("/store/{$store->slug}/admin/reports/tax")
            ->assertOk();

        // 3. Admin URL /store/{slug}/admin/reports/commercial-tax
        $this->actingAs($manager)
            ->get("/store/{$store->slug}/admin/reports/commercial-tax")
            ->assertOk();

        // 4. POS Route /store/{slug}/pos/reports/tax
        $this->actingAs($manager)
            ->get("/store/{$store->slug}/pos/reports/tax")
            ->assertOk();
    }

    public function test_pos_cart_discount_and_posting_with_tax_and_discount(): void
    {
        $store = $this->makeStore('pos-discount-shop', [
            'enable_tax' => true,
            'default_tax_rate' => 5.0,
            'tax_type' => 'exclusive',
        ]);
        $cashier = $this->staff($store);
        $shift = $this->openShift($store, $cashier);

        $product = $this->makeProduct($store, 'Smart Case', 20000, true);

        // Add to cart as cashier
        $this->actingAs($cashier)->postJson(route('pos.cart.add', ['store_slug' => $store->slug]), [
            'product_id' => $product->id,
            'quantity' => '1',
        ])->assertOk();

        // Set discount via pos.cart.discount endpoint
        $response = $this->actingAs($cashier)->postJson(route('pos.cart.discount', ['store_slug' => $store->slug]), [
            'discount' => '2000',
        ]);
        $response->assertOk();
        $response->assertJsonPath('cart.totals.subtotal', '20000.00');
        $response->assertJsonPath('cart.totals.tax', '1000.00');
        $response->assertJsonPath('cart.totals.discount', '2000');
        $response->assertJsonPath('cart.totals.total', '19000.00');

        // Post the sale: subtotal 20000 + tax 1000 - discount 2000 = total 19000
        $postRes = $this->actingAs($cashier)->post(route('pos.post', ['store_slug' => $store->slug]), [
            'payments' => [
                ['method' => 'cash', 'amount' => '19000'],
            ],
            'discount' => '2000',
        ]);
        $postRes->assertRedirect();

        $sale = \App\POS\Models\PosSale::where('store_id', $store->id)->latest('id')->first();
        $this->assertNotNull($sale);
        $this->assertEquals('20000.00', $sale->subtotal);
        $this->assertEquals('1000.00', $sale->tax);
        $this->assertEquals('2000.00', $sale->discount);
        $this->assertEquals('19000.00', $sale->total);
    }
}

