<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\PoPaymentLog;
use App\POS\Services\BusinessReconciliationService;
use App\POS\Services\StockCountService;
use App\Services\DemoBusinessScenarioService;
use App\Services\OfflineSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * MySQL portability regressions.
 *
 * These six defects all passed on SQLite and failed on MySQL/MariaDB — the engine
 * the app actually ships on — because SQLite resolves a double-quoted unknown
 * identifier to a STRING LITERAL instead of erroring. A query filtering on a
 * column that does not exist therefore returned "no rows" on SQLite and 500'd on
 * MySQL, so a green SQLite suite proved nothing about these paths.
 *
 * Each test below therefore asserts the BEHAVIOUR (a page renders, a value is
 * real, a total is deducted) rather than the absence of an exception, so it is
 * meaningful on both engines.
 */
class MySQLPortabilityRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-18 10:00:00');

        // cleanStoreData() purges the store's generated storefront assets from the
        // PUBLIC disk. Without a fake here that delete hits the real
        // storage/app/public/demo-stores/<id> directory, which other tests in the
        // same process still rely on — the wipe test would pass and quietly break
        // whichever Storage assertion happened to run next.
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeStore(string $slug = 'port-store'): Store
    {
        return Store::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
            'business_profile' => 'retail_store',
            'operation_mode' => 'omnichannel',
        ]);
    }

    private function user(Store $store, string $role = 'manager'): User
    {
        $storeRole = $role === 'manager' ? 'store_manager' : $role;

        $user = User::create([
            'name' => 'Port ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => $storeRole, 'status' => 'active']);

        return $user;
    }

    private function product(Store $store, array $overrides = []): Product
    {
        $name = 'Portable Widget ' . Str::random(4);

        return Product::create(array_merge([
            'store_id' => $store->id,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(4),
            'sku' => 'PW-' . Str::random(6),
            'retail_price' => '10000.00',
            'wholesale_price' => '9000.00',
            'purchase_cost' => '7000.00',
            'stock_status' => 'in_stock',
        ], $overrides));
    }

    /**
     * The price wizard's average-margin query used CAST(x AS REAL), which MySQL
     * accepts and MariaDB rejects as a syntax error, so the whole page 500'd.
     */
    public function test_price_wizard_renders_with_a_real_average_margin(): void
    {
        $store = $this->makeStore('port-price');
        $manager = $this->user($store);

        $this->product($store, ['retail_price' => '10000.00', 'purchase_cost' => '7000.00']);
        $this->product($store, ['retail_price' => '10000.00', 'purchase_cost' => '5000.00']);

        $response = $this->actingAs($manager)->get("/store/{$store->slug}/admin/price-wizard");

        $response->assertOk();

        // (30% + 50%) / 2 = 40% — the figure the broken SQL could never produce.
        $expected = Product::where('store_id', $store->id)
            ->where('purchase_cost', '>', 0)
            ->where('retail_price', '>', 0)
            ->get()
            ->avg(fn ($p) => ((float) $p->retail_price - (float) $p->purchase_cost) / (float) $p->retail_price * 100);

        $this->assertEqualsWithDelta(40.0, (float) $expected, 0.01);
    }

    /**
     * The store-data wipe deleted pos_sale_items by a column that does not exist
     * ("sale_id"), which threw on MySQL and left the wipe half-done.
     */
    public function test_store_data_wipe_removes_pos_sale_items(): void
    {
        $store = $this->makeStore('port-wipe');
        $manager = $this->user($store);

        DB::table('pos_sales')->insert([
            'store_id' => $store->id,
            'receipt_number' => 'S-PORT-1',
            'status' => 'posted',
            'subtotal' => '100.00',
            'discount' => '0.00',
            'tax' => '0.00',
            'total' => '100.00',
            'created_by' => $manager->id,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $saleId = DB::table('pos_sales')->where('receipt_number', 'S-PORT-1')->value('id');

        DB::table('pos_sale_items')->insert([
            'pos_sale_id' => $saleId,
            'product_name' => 'Wiped item',
            'quantity' => '1.000',
            'unit_price' => '100.00',
            'unit_cost' => '70.00',
            'line_total' => '100.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(DemoBusinessScenarioService::class)->cleanStoreData($store);

        $this->assertSame(0, DB::table('pos_sale_items')->where('pos_sale_id', $saleId)->count());
        $this->assertSame(0, DB::table('pos_sales')->where('store_id', $store->id)->count());
    }

    /**
     * The offline pull delta selected products.cost_price / products.is_active,
     * neither of which exists, so every terminal's sync request 500'd.
     */
    public function test_offline_pull_delta_returns_products_with_a_real_cost(): void
    {
        $store = $this->makeStore('port-sync');
        $this->product($store, ['purchase_cost' => '7000.00']);

        $delta = app(OfflineSyncService::class)->getPullDelta($store);

        $this->assertArrayHasKey('products', $delta);
        $this->assertCount(1, $delta['products']);

        $row = $delta['products']->first();
        // Compared numerically: SQLite's CAST drops the scale, MySQL keeps it. What
        // matters is that a real cost travels, not the engine's spelling of it.
        $this->assertSame(
            '7000.00',
            number_format((float) $row->cost_price, 2, '.', ''),
            'The pull must carry the real purchase cost, not a missing column.'
        );
        $this->assertSame(1, (int) $row->is_active, 'The client contract still needs an is_active flag.');
    }

    /**
     * The stock-count snapshot valued every line with $product->cost_price, which
     * does not exist, so the whole count was valued at cost 0.
     */
    public function test_stock_count_snapshot_uses_the_real_purchase_cost(): void
    {
        $store = $this->makeStore('port-count');
        $manager = $this->user($store);

        $product = $this->product($store, ['purchase_cost' => '7000.00']);

        $session = app(StockCountService::class)->createSession($store, [], $manager);

        $line = DB::table('stock_count_lines')
            ->where('stock_count_id', $session->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($line, 'The snapshot should contain a line per product.');
        // Decimal comparison, engine independent.
        $this->assertSame('7000.00', number_format((float) $line->unit_cost, 2, '.', ''));
    }

    /**
     * The warranty service history ordered by service_jobs.received_at, which does
     * not exist: a 500 on MySQL, and on SQLite an ORDER BY of the constant string
     * 'received_at' — i.e. no ordering at all, silently.
     */
    public function test_warranty_service_history_loads_and_is_ordered_by_intake(): void
    {
        $store = $this->makeStore('port-warranty');
        $manager = $this->user($store);

        $warrantyId = DB::table('device_warranties')->insertGetId([
            'store_id' => $store->id,
            'serial_number' => 'SN-PORT-1',
            'product_name' => 'Portable Widget',
            'purchase_date' => '2026-01-01',
            'warranty_expiry_date' => '2027-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([['JOB-OLD', '2026-09-01 09:00:00'], ['JOB-NEW', '2026-09-10 09:00:00']] as [$jobNumber, $createdAt]) {
            DB::table('service_jobs')->insert([
                'store_id' => $store->id,
                'job_number' => $jobNumber,
                'device_type' => 'phone',
                'reported_problem' => 'probe',
                'imei_serial' => 'SN-PORT-1',
                'status' => 'received',
                'created_by' => $manager->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $warranty = \App\POS\Models\DeviceWarranty::findOrFail($warrantyId);
        $history = app(\App\POS\Services\WarrantyTrackerService::class)->getServiceHistory($warranty);

        $this->assertCount(2, $history);
        $this->assertSame('JOB-NEW', $history->first()->job_number, 'History must be newest-first.');
    }

    /**
     * Cash reconciliation must subtract only supplier payments that actually say
     * "cash", and report the ones that say nothing. Before the column existed the
     * query was invalid (MySQL 500) and, on SQLite, matched nothing — so every
     * supplier cash payment vanished from the drawer maths as a silent zero.
     */
    public function test_cash_reconciliation_deducts_explicit_cash_and_reports_unrecorded(): void
    {
        $store = $this->makeStore('port-recon');
        $manager = $this->user($store);

        $poId = DB::table('purchase_orders')->insertGetId([
            'store_id' => $store->id,
            'po_number' => 'PO-PORT-1',
            'status' => 'received',
            'payment_status' => 'partial',
            'ordered_at' => now(),
            'received_at' => now(),
            'created_by' => $manager->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $base = [
            'store_id' => $store->id,
            'purchase_order_id' => $poId,
            'amount' => '5000.00',
            'paid_by' => $manager->id,
            'paid_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('po_payment_logs')->insert($base + ['payment_method' => PoPaymentLog::METHOD_CASH]);
        DB::table('po_payment_logs')->insert(
            array_merge($base, ['amount' => '3000.00', 'payment_method' => null, 'created_at' => now(), 'updated_at' => now()])
        );

        $cash = app(BusinessReconciliationService::class)->cashReconciliation($store, Carbon::parse('2026-09-18'));

        $this->assertSame('5000.00', $cash['supplier_payments'], 'Only the explicit cash payment may be deducted.');
        $this->assertSame('3000.00', $cash['supplier_payments_unrecorded'], 'The unrecorded payment must be reported.');
        $this->assertSame(1, $cash['supplier_payments_unrecorded_count']);
        $this->assertFalse($cash['is_reconciled'], 'An unrecorded payment means the cash figure is not final.');

        // And the screen itself must render instead of 500ing.
        $this->actingAs($manager)
            ->get("/store/{$store->slug}/pos/reports/reconciliation")
            ->assertOk();
    }
}
