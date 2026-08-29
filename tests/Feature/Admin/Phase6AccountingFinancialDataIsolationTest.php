<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\CashierShift;
use App\POS\Models\Expense;
use App\POS\Models\ExpenseCategory;
use App\POS\Models\PosSale;
use App\POS\Models\PosSaleItem;
use App\POS\Services\ProfitLossService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6AccountingFinancialDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Store $storeA;
    protected Store $storeB;
    protected User $staffA;
    protected User $staffB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::create(['name' => 'Store Alpha', 'slug' => 'store-alpha', 'is_active' => true]);
        $this->storeB = Store::create(['name' => 'Store Beta', 'slug' => 'store-beta', 'is_active' => true]);

        $this->staffA = User::create(['name' => 'Manager Alpha', 'phone' => '09111111111', 'password' => bcrypt('password')]);
        $this->staffB = User::create(['name' => 'Manager Beta', 'phone' => '09222222222', 'password' => bcrypt('password')]);

        $this->storeA->users()->attach($this->staffA->id, ['role' => 'store_manager', 'status' => 'active']);
        $this->storeB->users()->attach($this->staffB->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_expenses_and_categories_cross_store_isolation(): void
    {
        $catA = ExpenseCategory::create([
            'store_id' => $this->storeA->id,
            'name' => 'Shop Rent Alpha',
            'code' => 'RENT_A',
            'color' => '#10b981',
            'is_active' => true,
        ]);

        $catB = ExpenseCategory::create([
            'store_id' => $this->storeB->id,
            'name' => 'Electricity Beta',
            'code' => 'ELEC_B',
            'color' => '#f59e0b',
            'is_active' => true,
        ]);

        // 1. Staff A cannot create expense with Store B's category
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/admin/expenses", [
                'title' => 'Office Rent',
                'amount' => 500000,
                'expense_date' => now()->format('Y-m-d'),
                'expense_category_id' => $catB->id, // foreign category
                'payment_method' => 'cash',
            ])
            ->assertSessionHasErrors('expense_category_id');

        // 2. Staff A cannot update or toggle Store B's category
        $this->actingAs($this->staffA)
            ->put("/store/{$this->storeA->slug}/admin/expense-categories/{$catB->id}", [
                'name' => 'Hacked Cat',
            ])
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->patch("/store/{$this->storeA->slug}/admin/expense-categories/{$catB->id}/toggle")
            ->assertNotFound();

        // 3. Store B expense is isolated from Store A
        $expenseB = Expense::create([
            'store_id' => $this->storeB->id,
            'expense_category_id' => $catB->id,
            'expense_number' => 'EXP-B-001',
            'title' => 'Beta Electricity Bill',
            'amount' => 120000,
            'expense_date' => now()->format('Y-m-d'),
            'payment_method' => 'kpay',
            'recorded_by' => $this->staffB->id,
        ]);

        $response = $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/expenses");

        $response->assertOk();
        $response->assertDontSee('Beta Electricity Bill');

        // 4. Staff A cannot update or delete Store B's expense
        $this->actingAs($this->staffA)
            ->put("/store/{$this->storeA->slug}/admin/expenses/{$expenseB->id}", [
                'title' => 'Hacked Bill',
                'amount' => 1000,
                'expense_date' => now()->format('Y-m-d'),
                'payment_method' => 'cash',
            ])
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->delete("/store/{$this->storeA->slug}/admin/expenses/{$expenseB->id}")
            ->assertNotFound();
    }

    public function test_cashier_shift_cross_store_isolation(): void
    {
        $shiftB = CashierShift::create([
            'store_id' => $this->storeB->id,
            'cashier_id' => $this->staffB->id,
            'register_name' => 'POS-B1',
            'opened_at' => now(),
            'opening_cash_amount' => 50000,
            'status' => 'open',
        ]);

        // Staff A cannot record cash event on Store B's shift
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/shifts/{$shiftB->id}/cash-events", [
                'type' => 'in',
                'amount' => 10000,
                'reason' => 'petty cash in',
            ])
            ->assertForbidden();

        // Staff A cannot close Store B's shift
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/pos/shifts/{$shiftB->id}/close", [
                'actual_closing_amount' => 50000,
            ])
            ->assertForbidden();
    }

    public function test_profit_loss_financial_isolation(): void
    {
        $prodA = Product::create([
            'store_id' => $this->storeA->id,
            'name' => 'Product Alpha',
            'slug' => 'prod-alpha',
            'sku' => 'A-001',
            'cost_price' => 5000,
            'retail_price' => 8000,
            'wholesale_price' => 7000,
        ]);

        $prodB = Product::create([
            'store_id' => $this->storeB->id,
            'name' => 'Product Beta',
            'slug' => 'prod-beta',
            'sku' => 'B-001',
            'cost_price' => 50000,
            'retail_price' => 80000,
            'wholesale_price' => 70000,
        ]);

        // Store A sale: 8,000 Ks revenue, 5,000 Ks COGS -> 3,000 Ks profit
        $saleA = PosSale::create([
            'store_id' => $this->storeA->id,
            'receipt_number' => 'REC-A1',
            'status' => 'posted',
            'subtotal' => 8000,
            'total' => 8000,
            'posted_at' => now(),
            'cashier_id' => $this->staffA->id,
        ]);
        PosSaleItem::create([
            'pos_sale_id' => $saleA->id,
            'product_id' => $prodA->id,
            'product_name' => 'Product Alpha',
            'quantity' => 1,
            'unit_price' => 8000,
            'unit_cost' => 5000,
            'line_total' => 8000,
        ]);

        // Store B sale: 80,000 Ks revenue, 50,000 Ks COGS -> 30,000 Ks profit
        $saleB = PosSale::create([
            'store_id' => $this->storeB->id,
            'receipt_number' => 'REC-B1',
            'status' => 'posted',
            'subtotal' => 80000,
            'total' => 80000,
            'posted_at' => now(),
            'cashier_id' => $this->staffB->id,
        ]);
        PosSaleItem::create([
            'pos_sale_id' => $saleB->id,
            'product_id' => $prodB->id,
            'product_name' => 'Product Beta',
            'quantity' => 1,
            'unit_price' => 80000,
            'unit_cost' => 50000,
            'line_total' => 80000,
        ]);

        // Store A expense: 1,000 Ks
        Expense::create([
            'store_id' => $this->storeA->id,
            'expense_number' => 'EXP-A1',
            'title' => 'Alpha Water Bill',
            'amount' => 1000,
            'expense_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
        ]);

        // Store B expense: 20,000 Ks
        Expense::create([
            'store_id' => $this->storeB->id,
            'expense_number' => 'EXP-B1',
            'title' => 'Beta Heavy Power Bill',
            'amount' => 20000,
            'expense_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
        ]);

        /** @var ProfitLossService $plService */
        $plService = app(ProfitLossService::class);
        $statementA = $plService->generateStatement($this->storeA, now()->startOfMonth(), now()->endOfMonth());

        // Assert Store A's Statement includes only Store A's numbers
        $this->assertEquals(8000.0, $statementA['revenue']['gross_sales']);
        $this->assertEquals(5000.0, $statementA['cogs']['gross_cogs']);
        $this->assertEquals(3000.0, $statementA['gross_profit']);
        $this->assertEquals(1000.0, $statementA['expenses']['total']);
        $this->assertEquals(2000.0, $statementA['net_profit']); // 3,000 gross profit - 1,000 expenses
    }
}
