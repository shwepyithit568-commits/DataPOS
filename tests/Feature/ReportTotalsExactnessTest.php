<?php

namespace Tests\Feature;

use App\POS\Models\Expense;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\PosReturn;
use App\POS\Models\PosReturnItem;
use App\POS\Models\PosSale;
use App\POS\Models\PosSaleItem;
use App\POS\Services\DailyClosingService;
use App\POS\Services\ProfitLossService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression: report totals must equal the exact decimal sum.
 *
 * Two separate defects are covered here:
 *  - SQL aggregates were cast to float and accumulated in PHP;
 *  - the P&L queried columns that do not exist (`refund_amount`, `total_cost`,
 *    `occurred_at` on pos_returns). SQLite reads an unknown "quoted" identifier
 *    as a string literal, so those queries silently matched zero rows and the
 *    statement never deducted a return — while MySQL would reject the SQL.
 */
class ReportTotalsExactnessTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $admin;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Report Store', 'slug' => 'report-store', 'is_active' => true]);

        $this->admin = User::factory()->create(['role' => 'store_manager']);
        $this->admin->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $name = 'Widget ' . Str::random(3);
        $this->product = Product::create([
            'store_id'        => $this->store->id,
            'sku'             => strtoupper(Str::random(8)),
            'name'            => $name,
            'slug'            => Str::slug($name . '-' . Str::random(3)),
            'retail_price'    => '50000.00',
            'wholesale_price' => '45000.00',
        ]);
    }

    private function postedSale(string $subtotal, string $discount, string $total, string $qty = '2', string $unitCost = '30000.00'): PosSale
    {
        $sale = PosSale::create([
            'store_id'       => $this->store->id,
            'receipt_number' => 'REC-' . Str::random(8),
            'status'         => 'posted',
            'subtotal'       => $subtotal,
            'discount'       => $discount,
            'tax'            => '0.00',
            'total'          => $total,
            'posted_at'      => now(),
            'created_by'     => $this->admin->id,
        ]);

        PosSaleItem::create([
            'pos_sale_id'  => $sale->id,
            'product_id'   => $this->product->id,
            'product_name' => $this->product->name,
            'sku'          => $this->product->sku,
            'unit_price'   => bcdiv($subtotal, $qty, 2),
            'quantity'     => $qty,
            'unit_cost'    => $unitCost,
            'line_total'   => $subtotal,
        ]);

        return $sale;
    }

    /* ------------------------------------------------------------------ */
    /*  exact_sum                                                          */
    /* ------------------------------------------------------------------ */

    public function test_exact_sum_matches_bcmath_for_a_plain_column(): void
    {
        $this->postedSale('100.10', '0.00', '100.10', '1', '60.00');
        $this->postedSale('250.20', '0.00', '250.20', '1', '60.00');
        $this->postedSale('0.30', '0.00', '0.30', '1', '60.00');

        $this->assertSame(
            '350.60',
            exact_sum(PosSale::where('store_id', $this->store->id), 'total')
        );
    }

    public function test_exact_sum_handles_expressions(): void
    {
        $this->postedSale('100.00', '0.00', '100.00', '3', '10.10');

        // 3 x 10.10 = 30.30
        $this->assertSame(
            '30.30',
            exact_sum(PosSaleItem::whereIn('pos_sale_id', PosSale::where('store_id', $this->store->id)->pluck('id')), 'quantity * unit_cost')
        );
    }

    public function test_exact_sum_does_not_drift_where_a_float_sum_does(): void
    {
        // SQLite has no DECIMAL: SUM() runs in REAL (a double) and drifts once a
        // report covers enough rows. 200k rows is where the difference first
        // shows at two decimal places (measured), so that is what this asserts.
        $rows = 200000;
        $value = '12999.99';

        for ($offset = 0; $offset < $rows; $offset += 5000) {
            $chunk = [];
            for ($i = 0; $i < 5000; $i++) {
                $chunk[] = [
                    'store_id' => $this->store->id, 'title' => 'probe', 'amount' => $value,
                    'expense_number' => 'PRB-' . $offset . '-' . $i,
                    'expense_date' => '2026-01-01', 'payment_method' => 'cash',
                ];
            }
            DB::table('expenses')->insert($chunk);
        }

        $expected = bcmul($value, (string) $rows, 2);

        $this->assertSame($expected, exact_sum(Expense::where('store_id', $this->store->id), 'amount'));

        // The plain SQL aggregate is the thing that drifts — this documents why
        // exact_sum exists rather than a normalised `(float) sum()`.
        $raw = number_format((float) DB::table('expenses')->where('store_id', $this->store->id)->sum('amount'), 2, '.', '');
        $this->assertNotSame($expected, $raw);
    }

    /* ------------------------------------------------------------------ */
    /*  Profit & Loss — returns must actually be deducted                  */
    /* ------------------------------------------------------------------ */

    public function test_profit_loss_deducts_a_posted_return(): void
    {
        $sale = $this->postedSale('100000.00', '0.00', '100000.00');

        $return = PosReturn::create([
            'store_id'       => $this->store->id,
            'pos_sale_id'    => $sale->id,
            'refund_number'  => 'RET-' . Str::random(8),
            'status'         => 'posted',
            'total'          => '20000.00',
            'posted_at'      => now(),
            'created_by'     => $this->admin->id,
        ]);

        PosReturnItem::create([
            'pos_return_id'   => $return->id,
            'product_id'      => $this->product->id,
            'product_name'    => $this->product->name,
            'sku'             => $this->product->sku,
            'unit_price'      => '10000.00',
            'quantity'        => '2',
            'unit_cost'       => '6000.00', // returned COGS = 12,000
            'line_total'      => '20000.00',
        ]);

        $statement = app(ProfitLossService::class)->generateStatement(
            $this->store,
            Carbon::now()->copy()->startOfMonth(),
            Carbon::now()->copy()->endOfMonth()
        );

        // Gross 100,000 - return 20,000 = 80,000 net sales.
        $this->assertSame('20000.00', $statement['revenue']['returns']);
        $this->assertSame('80000.00', $statement['revenue']['net_sales']);

        // COGS 60,000 - returned cost 12,000 = 48,000.
        $this->assertSame('12000.00', $statement['cogs']['returns_cogs']);
        $this->assertSame('48000.00', $statement['cogs']['net_cogs']);
    }

    public function test_profit_loss_matches_the_bcmath_arithmetic(): void
    {
        $this->postedSale('100000.00', '5000.00', '95000.00');

        Expense::create([
            'store_id'       => $this->store->id,
            'expense_number' => 'EXP-' . Str::random(6),
            'title'          => 'Rent',
            'amount'         => '15000.00',
            'expense_date'   => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $statement = app(ProfitLossService::class)->generateStatement(
            $this->store,
            Carbon::now()->copy()->startOfMonth(),
            Carbon::now()->copy()->endOfMonth()
        );

        $this->assertSame('95000.00', $statement['revenue']['net_sales']);
        $this->assertSame('60000.00', $statement['cogs']['net_cogs']);
        $this->assertSame('35000.00', $statement['gross_profit']);
        $this->assertSame('20000.00', $statement['net_profit']); // 35,000 - 15,000 rent
        $this->assertSame('20000.00', $statement['metrics']['profit_per_order']);
    }

    /* ------------------------------------------------------------------ */
    /*  Daily closing                                                      */
    /* ------------------------------------------------------------------ */

    public function test_daily_closing_summary_uses_exact_decimals(): void
    {
        $this->postedSale('100.10', '0.00', '100.10', '1', '60.00');
        $this->postedSale('200.20', '0.00', '200.20', '1', '60.00');

        $expected = app(DailyClosingService::class)->expectedTotals($this->store, Carbon::now());

        $this->assertSame('300.30', $expected['summary']['gross_sales']);
        $this->assertSame('300.30', $expected['summary']['net_sales']);
    }

    /* ------------------------------------------------------------------ */
    /*  Store data export                                                  */
    /* ------------------------------------------------------------------ */

    public function test_store_data_export_totals_actual_revenue(): void
    {
        $this->postedSale('100.00', '0.00', '100.00');

        $archive = app(\App\Services\StoreDataExportService::class)->exportStoreArchive($this->store);

        // `total` is the real column — the export previously read a
        // non-existent `final_total`, so this figure was always 0.
        $this->assertSame('100.00', $archive['sales_summary']['total_revenue']);
    }
}
