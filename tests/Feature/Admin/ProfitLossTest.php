<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\Expense;
use App\POS\Models\ExpenseCategory;
use App\POS\Models\PosSale;
use App\POS\Models\PosSaleItem;
use App\POS\Services\ProfitLossService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitLossTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $admin;
    protected Product $product;
    protected ProfitLossService $plService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'DataPOS Mobile Hub',
            'slug' => 'datapos-mobile',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'store_manager',
        ]);
        $this->admin->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Remax 20000mAh Powerbank',
            'slug' => 'remax-20000mah-powerbank',
            'sku' => '885912345678',
            'retail_price' => 50000,
            'wholesale_price' => 35000,
        ]);

        $this->plService = app(ProfitLossService::class);
    }

    public function test_admin_can_access_profit_loss_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('store.admin.profit_loss.index', [
            'store_slug' => $this->store->slug,
        ]));

        $response->assertStatus(200);
        $response->assertSee('DataPOS Mobile Hub');
    }

    public function test_profit_loss_service_accurately_calculates_financials(): void
    {
        $now = Carbon::now();

        // 1. Create a posted sale: 2 units of Powerbank (Price 50,000 Ks, Cost 30,000 Ks each) -> Total 100,000 Ks, COGS 60,000 Ks
        $sale = PosSale::create([
            'store_id' => $this->store->id,
            'receipt_number' => 'REC-1001',
            'status' => 'posted',
            'subtotal' => 100000,
            'discount' => 5000, // 5,000 Ks discount -> Net Sales 95,000 Ks
            'tax' => 0,
            'total' => 95000,
            'posted_at' => $now,
            'created_by' => $this->admin->id,
        ]);

        PosSaleItem::create([
            'pos_sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'sku' => $this->product->sku,
            'unit_price' => 50000,
            'quantity' => 2,
            'unit_cost' => 30000, // Cost 30,000 x 2 = 60,000 Ks
            'line_total' => 100000,
        ]);

        // 2. Create an expense: Rent 15,000 Ks
        $category = ExpenseCategory::create([
            'store_id' => $this->store->id,
            'name' => 'Shop Rent',
            'color' => '#6366f1',
        ]);

        Expense::create([
            'store_id' => $this->store->id,
            'expense_category_id' => $category->id,
            'expense_number' => 'EXP-001',
            'title' => 'Shop Space Rent',
            'amount' => 15000,
            'expense_date' => $now->toDateString(),
            'payment_method' => 'cash',
        ]);

        // 3. Generate Statement
        $statement = $this->plService->generateStatement($this->store, $now->copy()->startOfMonth(), $now->copy()->endOfMonth());

        // Net Sales = 100,000 - 5,000 (discount) = 95,000 Ks
        $this->assertEquals(95000.0, $statement['revenue']['net_sales']);

        // Net COGS = 60,000 Ks
        $this->assertEquals(60000.0, $statement['cogs']['net_cogs']);

        // Gross Profit = 95,000 - 60,000 = 35,000 Ks
        $this->assertEquals(35000.0, $statement['gross_profit']);
        $this->assertEquals(36.84, $statement['gross_margin']); // (35,000 / 95,000) * 100

        // Total Operating Expenses = 15,000 Ks
        $this->assertEquals(15000.0, $statement['expenses']['total']);

        // Net Operating Profit = 35,000 - 15,000 = 20,000 Ks
        $this->assertEquals(20000.0, $statement['net_profit']);
        $this->assertEquals(21.05, $statement['net_margin']); // (20,000 / 95,000) * 100
    }

    public function test_profit_loss_statement_a4_printable_page_renders_200(): void
    {
        $response = $this->actingAs($this->admin)->get(route('store.admin.profit_loss.statement', [
            'store_slug' => $this->store->slug,
            'preset' => 'this_month',
        ]));

        $response->assertStatus(200);
        $response->assertSee('statement-container');
        $response->assertSee('REVENUE');
        $response->assertSee('COST OF GOODS SOLD');
    }

    public function test_profit_loss_export_csv_downloads_valid_stream(): void
    {
        $response = $this->actingAs($this->admin)->get(route('store.admin.profit_loss.export', [
            'store_slug' => $this->store->slug,
            'preset' => 'this_month',
            'format' => 'csv',
        ]));

        $response->assertStatus(200);
        $this->assertTrue($response->headers->contains('content-type', 'text/csv; charset=UTF-8'));
    }

    public function test_profit_loss_export_xlsx_downloads_valid_file(): void
    {
        $response = $this->actingAs($this->admin)->get(route('store.admin.profit_loss.export', [
            'store_slug' => $this->store->slug,
            'preset' => 'this_month',
            'format' => 'xlsx',
        ]));

        $response->assertStatus(200);
        $this->assertTrue($response->headers->contains('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
    }

    public function test_profit_loss_service_includes_service_jobs_when_present(): void
    {
        // Create a completed service job with charge
        $job = \App\POS\Models\ServiceJob::create([
            'store_id' => $this->store->id,
            'job_number' => 'SVC-20260828-0001',
            'contact_name' => 'Ko Aung',
            'contact_phone' => '0912345678',
            'device_type' => 'Smartphone',
            'reported_problem' => 'Screen Broken',
            'status' => 'delivered',
            'final_charge' => 45000,
            'created_by' => $this->admin->id,
        ]);

        \App\POS\Models\ServiceJobPayment::create([
            'service_job_id' => $job->id,
            'method' => 'cash',
            'amount' => 45000,
            'created_by' => $this->admin->id,
        ]);

        $service = app(ProfitLossService::class);
        $statement = $service->generateStatement($this->store, now()->startOfMonth(), now()->endOfMonth());

        $this->assertTrue($statement['services']['has_services']);
        $this->assertEquals(45000, $statement['services']['revenue']);
        $this->assertGreaterThanOrEqual(45000, $statement['revenue']['total_revenue']);
    }
}

