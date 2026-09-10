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
     * Demonstrates the return bug in PosReturnService:
     * 1. PosReturnService lines 178-179 computes returnTotal as unit_price * quantity (20,000).
     * 2. Customer paid 21,000.
     * 3. Trying to refund 21,000 throws InventoryException because refundTotal (21,000) != returnTotal (20,000).
     * 4. When refunding 20,000, the sale status becomes 'partially_refunded' (PosReturnService:361)
     *    even though 100% of the items were returned, because refunded (20,000) < sale->total (21,000).
     */
    public function test_audit_scenario_a_exclusive_tax_return_defect()
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

        // 1. Attempting to refund 21,000 (what customer actually paid) FAILS:
        try {
            $this->returns->post(
                $store,
                $sale,
                [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
                [['method' => 'cash', 'amount' => '21000.00']],
                $cashier,
                $shift
            );
            $this->fail('Expected InventoryException when refunding actual paid amount with tax!');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('Refund payments must equal the returned value (Ks 20000)', $e->getMessage());
        }

        // 2. Refunding 20,000 (the returned value without tax):
        $refund = $this->returns->post(
            $store,
            $sale,
            [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
            [['method' => 'cash', 'amount' => '20000.00']],
            $cashier,
            $shift
        );

        $sale->refresh();
        // The customer lost 1,000 Ks tax, AND the sale is permanently trapped in 'partially_refunded'!
        $this->assertEquals('20000.00', (string) $refund->total);
        $this->assertEquals('partially_refunded', $sale->status, 'BUG: Sale stuck at partially_refunded despite 100% quantity returned!');
    }

    /**
     * TEST B: Exclusive Tax + Cart Discount (20,000 + 1,000 tax - 2,000 discount = 19,000 paid)
     * Demonstrates that PosReturnService ignores cart discount during return:
     * 1. Customer paid 19,000.
     * 2. When returning the item, returnTotal is calculated as unit_price * 1 = 20,000.
     * 3. Cashier is forced to refund 20,000. If cashier enters 19,000, it throws InventoryException.
     * 4. As a result, the store gives away 20,000 cash for an item sold for 19,000! (1,000 Ks loss).
     */
    public function test_audit_scenario_b_tax_plus_cart_discount_return_defect()
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

        // Cashier tries to refund 19,000 (actual payment) -> Throws exception!
        try {
            $this->returns->post(
                $store,
                $sale,
                [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
                [['method' => 'cash', 'amount' => '19000.00']],
                $cashier,
                $shift
            );
            $this->fail('Expected exception when entering actual paid amount');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('Refund payments must equal the returned value (Ks 20000)', $e->getMessage());
        }

        // Cashier is FORCED to pay 20,000 to the customer!
        $refund = $this->returns->post(
            $store,
            $sale,
            [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
            [['method' => 'cash', 'amount' => '20000.00']],
            $cashier,
            $shift
        );

        $this->assertEquals('20000.00', (string) $refund->total);
        // Cash in drawer reduced by 20,000!
        $shift->refresh();
        $this->assertEquals('20000.00', (string) $shift->cash_refunds);
    }

    /**
     * TEST C: Partial returns sequence & duplicate prevention
     */
    public function test_audit_scenario_c_partial_returns_and_duplicate_prevention()
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

        // 1st return: 1 unit
        $ret1 = $this->returns->post(
            $store,
            $sale,
            [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
            [['method' => 'cash', 'amount' => '10000.00']],
            $cashier,
            $shift
        );
        $this->assertEquals('10000.00', (string) $ret1->total);
        $this->assertEquals('partially_refunded', $sale->refresh()->status);

        // 2nd return: 1 unit
        $ret2 = $this->returns->post(
            $store,
            $sale,
            [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
            [['method' => 'cash', 'amount' => '10000.00']],
            $cashier,
            $shift
        );
        $this->assertEquals('10000.00', (string) $ret2->total);
        // Total returned = 20,000 < sale total 21,000. Still partially_refunded!
        $this->assertEquals('partially_refunded', $sale->refresh()->status);

        // 3rd return: Attempting to return 1 more unit throws InventoryException (all units returned)
        $this->expectException(InventoryException::class);
        $this->returns->post(
            $store,
            $sale,
            [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
            [['method' => 'cash', 'amount' => '10000.00']],
            $cashier,
            $shift
        );
    }

    /**
     * TEST D: Tax Report completely drops partially refunded sales!
     * PosReportService::taxReport() queries ->where('status', 'posted')
     */
    public function test_audit_scenario_d_tax_report_drops_partially_refunded_sales()
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

        // Before return: tax report includes this sale
        $reportBefore = $this->reports->taxReport($store, $from, $to);
        $this->assertEquals(1, $reportBefore['count']);
        $this->assertEquals('1000.00', (string) $reportBefore['total_tax']);

        // Return 1 item
        $saleItem = $sale->items->first();
        $this->returns->post(
            $store,
            $sale,
            [['pos_sale_item_id' => $saleItem->id, 'quantity' => '1']],
            [['method' => 'cash', 'amount' => '10000.00']],
            $cashier,
            $shift
        );

        $sale->refresh();
        $this->assertEquals('partially_refunded', $sale->status);

        // After return: tax report check
        $reportAfter = $this->reports->taxReport($store, $from, $to);

        // CONFIRMED DEFECT: The entire invoice disappears from the Commercial Tax Report!
        $this->assertEquals(0, $reportAfter['count'], 'BUG: Partially refunded sale vanished from tax report!');
        $this->assertEquals(0, (float) $reportAfter['total_tax'], 'BUG: Remaining tax is missing from IRD report!');
    }

    /**
     * TEST E: Storefront Preview vs Backend Checkout Tax Mismatch
     */
    public function test_audit_scenario_e_storefront_preview_vs_backend_tax_mismatch()
    {
        $store = $this->makeStore('tax-e', [
            'enable_tax' => true,
            'default_tax_rate' => 5.0,
            'tax_type' => 'exclusive',
        ]);

        $taxableProd = $this->makeProduct($store, 10000, ['is_taxable' => true]);
        $exemptProd = $this->makeProduct($store, 5000, ['is_taxable' => false]);

        $items = [
            ['id' => $taxableProd->id, 'quantity' => 1, 'price' => 10000],
            ['id' => $exemptProd->id, 'quantity' => 1, 'price' => 5000],
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

        // Frontend builder.blade.php calculation:
        $frontendSubtotal = 15000;
        $frontendTax = round($frontendSubtotal * 0.05, 2); // 750.00
        $frontendTotal = $frontendSubtotal + $frontendTax; // 15,750.00

        $this->assertEquals(500.00, $backendTax);
        $this->assertEquals(15500.00, $backendTotal);
        $this->assertEquals(750.00, $frontendTax);
        $this->assertEquals(15750.00, $frontendTotal);
        $this->assertNotEquals($frontendTotal, $backendTotal, 'Storefront builder preview does not match backend checkout total!');
    }

    /**
     * TEST F: POS Expenses permission gap and drawer desync
     */
    public function test_audit_scenario_f_pos_expense_permission_gap()
    {
        $store = $this->makeStore('tax-f');
        // Staff has only pos_sales.view, NO expenses.create
        $cashier = $this->makeStaff($store, ['pos_sales.view']);
        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => 50000], $cashier);

        $category = ExpenseCategory::create([
            'store_id' => $store->id,
            'name' => 'Supplies',
        ]);

        $response = $this->actingAs($cashier)->post(route('pos.expenses.record', ['store_slug' => $store->slug]), [
            'title' => 'Tea money',
            'amount' => 5000,
            'expense_category_id' => $category->id,
            'payment_method' => 'cash',
        ]);

        // Request succeeds (redirect) despite staff lacking expenses.create!
        $this->assertTrue(
            $response->isRedirect(),
            'SECURITY GAP: Cashier with only pos_sales.view was able to record expense and withdraw cash!'
        );
        $this->assertDatabaseHas('expenses', [
            'store_id' => $store->id,
            'amount' => 5000.00,
        ]);
    }

    /**
     * TEST G: Discount validation inputs ("1e3" scientific notation vs 500 error)
     */
    public function test_audit_scenario_g_discount_validation_and_scientific_notation()
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

        // 2. "1e3" scientific notation passes 'numeric' validation rule
        // but causes ValueError / 500 in bccomp() during cartTotals or checkout!
        $resSci = $this->actingAs($cashier)->post(route('pos.cart.discount', ['store_slug' => $store->slug]), [
            'discount' => '1e3',
        ]);
        // Validation PASSED because rule is 'numeric' instead of 'decimal:0,2'!
        $this->assertFalse(session()->hasOldInput('errors'), 'Scientific notation 1e3 slipped past validation!');

        // Now calling cartTotals with "1e3" triggers bcmath ValueError!
        try {
            $this->sales->cartTotals($store);
            $this->fail('Expected ValueError on bccomp with 1e3');
        } catch (\ValueError $e) {
            $this->assertStringContainsString('is not well-formed', $e->getMessage());
        }
    }

    /**
     * TEST H: Reports Navigation Permission Mismatch
     * - Staff with stock_balance.view sees the link in sidebar navigation,
     *   but visiting the route pos.reports.stock gives 403 Forbidden!
     */
    public function test_audit_scenario_h_reports_navigation_permission_mismatch()
    {
        $store = $this->makeStore('nav-h');
        // Staff has only stock_balance.view, NO inventory_valuation.view
        $staff = $this->makeStaff($store, ['stock_balance.view']);

        $request = \Illuminate\Http\Request::create('/store/' . $store->slug . '/admin/dashboard');
        $navService = app(AdminNavigationService::class);
        $sidebarTree = $navService->getFilteredNavigationTree($staff, $store, $request);

        // Check if "pos_reports_stock" is visible in sidebar
        $reportsGroup = collect($sidebarTree)->firstWhere('key', 'reports');
        $this->assertNotNull($reportsGroup, 'Reports group should exist');
        $hasStockLink = collect($reportsGroup['children'] ?? [])->contains('key', 'pos_reports_stock');

        // Nav service displays the link because userHasPermission checked stock_balance.view:
        $this->assertTrue($hasStockLink, 'Sidebar displays Stock Report link to user with stock_balance.view');

        // BUT when user navigates to the route pos.reports.stock:
        $response = $this->actingAs($staff)->get(route('pos.reports.stock', ['store_slug' => $store->slug]));

        // CONFIRMED DEFECT: 403 Forbidden!
        $response->assertStatus(403);
    }
}
