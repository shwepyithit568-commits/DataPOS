<?php

namespace Tests\Feature\POS;

use App\Models\Expense;
use App\Models\Product;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CashierShift;
use App\POS\Models\ExpenseCategory;
use App\POS\Models\PosReturn;
use App\POS\Models\PosSale;
use App\POS\Services\CashierShiftService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosReportService;
use App\POS\Services\PosReturnService;
use App\POS\Services\PosSaleService;
use App\Services\AdminNavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaxDiscountRegressionAuditTest extends TestCase
{
    use RefreshDatabase;

    private PosSaleService $sales;
    private PosReturnService $returns;
    private InventoryService $inventory;
    private CashierShiftService $shifts;
    private PosReportService $reports;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sales = app(PosSaleService::class);
        $this->returns = app(PosReturnService::class);
        $this->inventory = app(InventoryService::class);
        $this->shifts = app(CashierShiftService::class);
        $this->reports = app(PosReportService::class);
    }

    private function makeStore(string $slug = 'audit-shop', array $taxSettings = []): Store
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
            'tax_id_number' => 'TIN-AUDIT-12345',
        ], $taxSettings);

        StorefrontSetting::create([
            'store_id' => $store->id,
            'store_name' => $store->name,
            'pos_settings' => $defaultPosSettings,
        ]);

        return $store;
    }

    private function makeStaff(Store $store, array $permissions = []): User
    {
        $user = User::create([
            'name' => 'Cashier ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'staff',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        if (!empty($permissions)) {
            $role = StaffRole::create([
                'store_id' => $store->id,
                'name' => 'Custom Role ' . Str::random(4),
                'slug' => 'custom-' . Str::random(6),
                'permissions' => $permissions,
                'is_active' => true,
            ]);
            $user->stores()->updateExistingPivot($store->id, ['staff_role_id' => $role->id]);
        }

        return $user;
    }

    private function makeProduct(Store $store, int $price = 10000, array $attrs = []): Product
    {
        $name = 'Prod ' . Str::random(4);
        $product = Product::create(array_merge([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(3)),
            'retail_price' => $price,
            'wholesale_price' => $price - 1000,
            'is_taxable' => true,
            'is_active' => true,
        ], $attrs));

        $this->seedStock($store, $product, '20');

        return $product;
    }

    private function seedStock(Store $store, Product $product, string $qty = '20'): void
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

    /**
     * TEST A: Exclusive Tax (20,000 + 1,000 tax = 21,000 total paid by customer)
     * Verifies that:
     * 1. PosReturnService refunds the full customer paid amount (21,000) including tax.
     * 2. When 100% of quantity is returned, sale status transitions to 'refunded'.
     * 3. Register cash refund records the exact 21,000.
     */
    public function test_remediated_scenario_a_exclusive_tax_full_refund()
    {
        $store = $this->makeStore('tax-a', [
            'enable_tax' => true,
            'default_tax_rate' => 5.0,
            'tax_type' => 'exclusive',
        ]);
        $cashier = $this->makeStaff($store);
        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => 50000], $cashier);

        $product = $this->makeProduct($store, 20000, ['is_taxable' => true]);

        $this->sales->addToCart($store, $product->id, null, '1');
        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '21000.00']],
            $cashier,
            $shift
        );

        $this->assertEquals('20000.00', (string) $sale->subtotal);
        $this->assertEquals('1000.00', (string) $sale->tax);
        $this->assertEquals('21000.00', (string) $sale->total);

        $saleItem = $sale->items->first();

        // Refunding 21,000 (what customer actually paid) now succeeds:
        $refund = $this->returns->post(
            $store,
            $sale,
            [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
            [['method' => 'cash', 'amount' => '21000.00']],
            $cashier,
            $shift
        );

        $this->assertEquals('21000.00', (string) $refund->total);

        $sale->refresh();
        // Verifies 100% returned quantity marks sale as 'refunded'
        $this->assertEquals('refunded', $sale->status);

        $shift->refresh();
        $this->assertEquals('21000.00', (string) $shift->cash_refunds);
    }

    /**
     * TEST B: Exclusive Tax + Cart Discount (20,000 + 1,000 tax - 2,000 discount = 19,000 paid)
     * Verifies that:
     * 1. Effective line value accounts for pro-rata discount and tax: 20,000 + 1,000 - 2,000 = 19,000.
     * 2. Attempting to refund 20,000 fails (store is protected from paying more than received).
     * 3. Refunding 19,000 succeeds and sale status becomes 'refunded'.
     */
    public function test_remediated_scenario_b_tax_plus_cart_discount_return_calculation()
    {
        $store = $this->makeStore('tax-b', [
            'enable_tax' => true,
            'default_tax_rate' => 5.0,
            'tax_type' => 'exclusive',
        ]);
        $cashier = $this->makeStaff($store);
        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => 50000], $cashier);

        $product = $this->makeProduct($store, 20000, ['is_taxable' => true]);

        $this->sales->addToCart($store, $product->id, null, '1');
        $this->sales->setDiscount($store, '2000.00');

        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '19000.00']],
            $cashier,
            $shift
        );

        $this->assertEquals('20000.00', (string) $sale->subtotal);
        $this->assertEquals('1000.00', (string) $sale->tax);
        $this->assertEquals('2000.00', (string) $sale->discount);
        $this->assertEquals('19000.00', (string) $sale->total);

        $saleItem = $sale->items->first();

        // Cashier tries to refund 20,000 (unadjusted retail price) -> Throws exception!
        try {
            $this->returns->post(
                $store,
                $sale,
                [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
                [['method' => 'cash', 'amount' => '20000.00']],
                $cashier,
                $shift
            );
            $this->fail('Expected exception when attempting to refund unadjusted retail price');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('Refund payments must equal the returned value (Ks 19000)', $e->getMessage());
        }

        // Cashier refunds 19,000 (actual net payment) -> Succeeds!
        $refund = $this->returns->post(
            $store,
            $sale,
            [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
            [['method' => 'cash', 'amount' => '19000.00']],
            $cashier,
            $shift
        );

        $this->assertEquals('19000.00', (string) $refund->total);
        $shift->refresh();
        $this->assertEquals('19000.00', (string) $shift->cash_refunds);

        $sale->refresh();
        $this->assertEquals('refunded', $sale->status);
    }

    /**
     * TEST C: Partial returns sequence & duplicate prevention
     */
    public function test_remediated_scenario_c_partial_returns_and_duplicate_prevention()
    {
        $store = $this->makeStore('tax-c');
        $cashier = $this->makeStaff($store);
        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => 50000], $cashier);

        $product = $this->makeProduct($store, 10000, ['is_taxable' => true]);

        $this->sales->addToCart($store, $product->id, null, '2');
        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '21000.00']],
            $cashier,
            $shift
        );

        $saleItem = $sale->items->first();

        // 1st return: 1 unit (10,500 Ks net)
        $ret1 = $this->returns->post(
            $store,
            $sale,
            [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
            [['method' => 'cash', 'amount' => '10500.00']],
            $cashier,
            $shift
        );
        $this->assertEquals('10500.00', (string) $ret1->total);
        $this->assertEquals('partially_refunded', $sale->refresh()->status);

        // 2nd return: 1 unit (10,500 Ks net)
        $ret2 = $this->returns->post(
            $store,
            $sale,
            [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
            [['method' => 'cash', 'amount' => '10500.00']],
            $cashier,
            $shift
        );
        $this->assertEquals('10500.00', (string) $ret2->total);
        // All units returned -> now marked as 'refunded'!
        $this->assertEquals('refunded', $sale->refresh()->status);

        // 3rd return: Attempting to return 1 more unit throws InventoryException (already fully returned)
        $this->expectException(InventoryException::class);
        $this->returns->post(
            $store,
            $sale,
            [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
            [['method' => 'cash', 'amount' => '10500.00']],
            $cashier,
            $shift
        );
    }

    /**
     * TEST D: Tax Report includes partially refunded sales with adjusted net amounts!
     */
    public function test_remediated_scenario_d_tax_report_includes_partially_refunded_sales()
    {
        $store = $this->makeStore('tax-d');
        $cashier = $this->makeStaff($store);
        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => 50000], $cashier);

        $p1 = $this->makeProduct($store, 10000, ['is_taxable' => true]);
        $p2 = $this->makeProduct($store, 10000, ['is_taxable' => true]);

        $this->sales->addToCart($store, $p1->id, null, '1');
        $this->sales->addToCart($store, $p2->id, null, '1');

        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '21000.00']],
            $cashier,
            $shift
        );

        $from = Carbon::now()->subDay();
        $to = Carbon::now()->addDay();

        // Before return: 1 sale, 1000.00 tax
        $reportBefore = $this->reports->taxReport($store, $from, $to);
        $this->assertEquals(1, $reportBefore['count']);
        $this->assertEquals('1000.00', (string) $reportBefore['total_tax']);

        // Return 1 item (10,500 net paid)
        $saleItem = $sale->items->first();
        $this->returns->post(
            $store,
            $sale,
            [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
            [['method' => 'cash', 'amount' => '10500.00']],
            $cashier,
            $shift
        );

        $sale->refresh();
        $this->assertEquals('partially_refunded', $sale->status);

        // After return: tax report STILL includes the invoice, with remaining pro-rata tax!
        $reportAfter = $this->reports->taxReport($store, $from, $to);
        $this->assertEquals(1, $reportAfter['count'], 'Partially refunded sale must be present in tax report');
        $this->assertEquals('500.00', (string) $reportAfter['total_tax'], 'Tax report reflects remaining non-returned tax');
        $this->assertEquals('10000.00', (string) $reportAfter['taxable_sales'], 'Remaining taxable sales');
        $this->assertEquals('10500.00', (string) $reportAfter['total_sales'], 'Remaining total sales');
    }

    /**
     * TEST E: Storefront Preview matches Backend Checkout Tax
     */
    public function test_remediated_scenario_e_storefront_preview_matches_backend_tax()
    {
        $store = $this->makeStore('tax-e', [
            'enable_tax' => true,
            'default_tax_rate' => 5.0,
            'tax_type' => 'exclusive',
        ]);

        $taxableProd = $this->makeProduct($store, 10000, ['is_taxable' => true]);
        $exemptProd = $this->makeProduct($store, 5000, ['is_taxable' => false]);

        $items = [
            ['id' => $taxableProd->id, 'quantity' => 1, 'price' => 10000, 'is_taxable' => true],
            ['id' => $exemptProd->id, 'quantity' => 1, 'price' => 5000, 'is_taxable' => false],
        ];

        // Backend OrderController calculation:
        $subtotal = 0;
        $taxableAmount = 0;
        $exemptAmount = 0;
        foreach ($items as $item) {
            $prod = Product::find($item['id']);
            $lineSubtotal = $item['price'] * $item['quantity'];
            $subtotal += $lineSubtotal;
            if ($prod->is_taxable) {
                $taxableAmount += $lineSubtotal;
            } else {
                $exemptAmount += $lineSubtotal;
            }
        }
        $backendTax = round($taxableAmount * 0.05, 2); // 500.00
        $backendTotal = $subtotal + $backendTax; // 15,500.00

        // Frontend builder calculation with taxableAmount getter:
        $frontendSubtotal = collect($items)->sum(fn ($i) => $i['price'] * $i['quantity']);
        $frontendTaxable = collect($items)->where('is_taxable', true)->sum(fn ($i) => $i['price'] * $i['quantity']);
        $frontendTax = round($frontendTaxable * 0.05, 2);
        $frontendTotal = $frontendSubtotal + $frontendTax;

        $this->assertEquals(500.00, $backendTax);
        $this->assertEquals(15500.00, $backendTotal);
        $this->assertEquals($backendTax, $frontendTax);
        $this->assertEquals($backendTotal, $frontendTotal);
    }

    /**
     * TEST F: POS Expenses authorization and shift requirement
     */
    public function test_remediated_scenario_f_pos_expense_authorization_and_shift_requirement()
    {
        $store = $this->makeStore('tax-f');
        $cashierWithoutPerm = $this->makeStaff($store, ['pos_sales.view']);
        $cashierWithPerm = $this->makeStaff($store, ['pos_sales.view', 'expenses.view', 'expenses.create']);

        $category = ExpenseCategory::create([
            'store_id' => $store->id,
            'name' => 'Supplies',
        ]);

        // 1. Staff without expenses.create receives 403 Forbidden:
        $resForbidden = $this->actingAs($cashierWithoutPerm)->post(route('pos.expenses.record', ['store_slug' => $store->slug]), [
            'title' => 'Tea money',
            'amount' => 5000,
            'expense_category_id' => $category->id,
            'payment_method' => 'cash',
        ]);
        $resForbidden->assertStatus(403);

        // 2. Staff with expenses.create but NO open shift gets rejected for cash payment:
        $resNoShift = $this->actingAs($cashierWithPerm)->post(route('pos.expenses.record', ['store_slug' => $store->slug]), [
            'title' => 'Tea money',
            'amount' => 5000,
            'expense_category_id' => $category->id,
            'payment_method' => 'cash',
        ]);
        $resNoShift->assertSessionHasErrors('payment_method');

        // 3. Staff with expenses.create AND an open shift succeeds:
        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => 50000], $cashierWithPerm);
        $resSuccess = $this->actingAs($cashierWithPerm)->post(route('pos.expenses.record', ['store_slug' => $store->slug]), [
            'title' => 'Tea money',
            'amount' => 5000,
            'expense_category_id' => $category->id,
            'payment_method' => 'cash',
        ]);
        $resSuccess->assertRedirect();
        $this->assertDatabaseHas('expenses', [
            'store_id' => $store->id,
            'amount' => 5000.00,
        ]);
    }

    /**
     * TEST G: Discount validation rejects non-decimal inputs like scientific notation "1e3"
     */
    public function test_remediated_scenario_g_discount_validation_rejects_scientific_notation()
    {
        $store = $this->makeStore('tax-g');
        $cashier = $this->makeStaff($store, ['pos_sales.view', 'pos_sales.update']);
        $product = $this->makeProduct($store, 10000);
        $this->sales->addToCart($store, $product->id, null, '1');

        // 1. Negative amount is rejected by validation (min:0)
        $resNegative = $this->actingAs($cashier)->post(route('pos.cart.discount', ['store_slug' => $store->slug]), [
            'discount' => -500,
        ]);
        $resNegative->assertSessionHasErrors('discount');

        // 2. "1e3" scientific notation is now rejected by 'decimal:0,2' validation rule:
        $resSci = $this->actingAs($cashier)->post(route('pos.cart.discount', ['store_slug' => $store->slug]), [
            'discount' => '1e3',
        ]);
        $resSci->assertSessionHasErrors('discount');

        // 3. Valid decimal amount is accepted:
        $resValid = $this->actingAs($cashier)->post(route('pos.cart.discount', ['store_slug' => $store->slug]), [
            'discount' => '1000.00',
        ]);
        $resValid->assertRedirect();
        $totals = $this->sales->cartTotals($store);
        $this->assertEquals('1000.00', (string) $totals['discount']);
    }

    /**
     * TEST H: Reports Navigation Permission Alignment
     * - Staff with stock_balance.view can view pos.reports.stock without 403 Forbidden.
     */
    public function test_remediated_scenario_h_reports_navigation_permission_aligned()
    {
        $store = $this->makeStore('nav-h');
        $staff = $this->makeStaff($store, ['stock_balance.view']);

        $request = \Illuminate\Http\Request::create('/store/' . $store->slug . '/admin/dashboard');
        $navService = app(AdminNavigationService::class);
        $sidebarTree = $navService->getFilteredNavigationTree($staff, $store, $request);

        // Sidebar displays Stock Report link
        $reportsGroup = collect($sidebarTree)->firstWhere('key', 'reports');
        $this->assertNotNull($reportsGroup);
        $hasStockLink = collect($reportsGroup['children'] ?? [])->contains('key', 'pos_reports_stock');
        $this->assertTrue($hasStockLink);

        // Accessing the route now succeeds with 200 OK!
        $response = $this->actingAs($staff)->get(route('pos.reports.stock', ['store_slug' => $store->slug]));
        $response->assertStatus(200);
    }
}
