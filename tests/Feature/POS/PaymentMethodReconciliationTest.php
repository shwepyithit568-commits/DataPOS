<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\PosPayment;
use App\POS\Models\PosReturn;
use App\POS\Models\PosReturnPayment;
use App\POS\Models\PosSale;
use App\POS\Services\CashierShiftService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosReportService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentMethodReconciliationTest extends TestCase
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

    private function makeStore(string $slug = 'shop-reconcile'): Store
    {
        return Store::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
            'currency' => 'MMK',
        ]);
    }

    private function staff(Store $store, string $name = 'Cashier'): User
    {
        $user = User::create([
            'name' => $name . ' ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        return $user;
    }

    private function makeProduct(Store $store, array $overrides = []): Product
    {
        $name = $overrides['name'] ?? 'Item ' . Str::random(4);

        return Product::create(array_merge([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(3)),
            'retail_price' => 10000,
            'wholesale_price' => 8000,
        ], $overrides));
    }

    private function seedStock(Store $store, Product $product, string $qty = '50', string $cost = '7000'): void
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

    public function test_payment_reconciliation_aggregates_multiple_tender_types_with_change_and_refunds(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-01', 'opening_cash' => 50000], $cashier);

        // Product 1: 15,000 Ks
        $product1 = $this->makeProduct($store, ['retail_price' => 15000]);
        $this->seedStock($store, $product1, '10');

        // Product 2: 15,000 Ks
        $product2 = $this->makeProduct($store, ['retail_price' => 15000]);
        $this->seedStock($store, $product2, '10');

        // Sale 1: Cash 20,000 paid, 5,000 change given for a 15,000 item
        $this->sales->addToCart($store, $product1->id, null, '1');
        $sale1 = $this->sales->post($store, $this->sales->cartLines($store), [
            ['method' => 'cash', 'amount' => '20000', 'change_given' => '5000'],
        ], $cashier, $shift);

        // Sale 2: Split payment: 10,000 KPay + 5,000 Wave for a 15,000 item
        $this->sales->addToCart($store, $product2->id, null, '1');
        $sale2 = $this->sales->post($store, $this->sales->cartLines($store), [
            ['method' => 'kpay', 'amount' => '10000', 'reference' => 'KP-998877'],
            ['method' => 'wavepay', 'amount' => '5000', 'reference' => 'WV-112233'],
        ], $cashier, $shift);

        // Return: Refund 5,000 via KPay
        $return = PosReturn::create([
            'store_id' => $store->id,
            'pos_sale_id' => $sale2->id,
            'cashier_id' => $cashier->id,
            'cashier_shift_id' => $shift->id,
            'return_number' => 'RET-0001',
            'total_refund' => '5000.00',
            'reason' => 'Customer exchange',
            'refund_method' => 'kpay',
            'restock_items' => false,
            'posted_at' => now(),
        ]);
        PosReturnPayment::create([
            'pos_return_id' => $return->id,
            'method' => 'kpay',
            'amount' => '5000.00',
        ]);

        $report = $this->reports->paymentMethodReconciliation($store, Carbon::today(), Carbon::today());

        // Assertions
        $this->assertSame(3, $report['payment_count']); // 1 cash, 1 kpay, 1 wavepay
        $this->assertSame('35000.00', $report['total_collected']);
        $this->assertSame('5000.00', $report['total_change']);
        $this->assertSame('30000.00', $report['net_sales']);
        $this->assertSame('5000.00', $report['total_refunded']);
        $this->assertSame('25000.00', $report['net_settlement']);
        $this->assertSame('15000.00', $report['cash_collected']);
        $this->assertSame('15000.00', $report['digital_collected']);

        // Check method details
        $methodsByName = collect($report['methods'])->keyBy('method');

        // Cash method
        $this->assertTrue($methodsByName->has('cash'));
        $cashRow = $methodsByName->get('cash');
        $this->assertSame(1, $cashRow['count']);
        $this->assertSame('20000.00', $cashRow['total_amount']);
        $this->assertSame('5000.00', $cashRow['change_given']);
        $this->assertSame('15000.00', $cashRow['net_amount']);
        $this->assertSame('0.00', $cashRow['refund_amount']);

        // KPay method
        $this->assertTrue($methodsByName->has('kpay'));
        $kpayRow = $methodsByName->get('kpay');
        $this->assertSame(1, $kpayRow['count']);
        $this->assertSame('10000.00', $kpayRow['total_amount']);
        $this->assertSame('5000.00', $kpayRow['refund_amount']);
        $this->assertSame('10000.00', $kpayRow['net_amount']);
        $this->assertSame(1, $kpayRow['reference_count']);

        // WavePay method
        $this->assertTrue($methodsByName->has('wavepay'));
        $waveRow = $methodsByName->get('wavepay');
        $this->assertSame(1, $waveRow['count']);
        $this->assertSame('5000.00', $waveRow['total_amount']);
        $this->assertSame('5000.00', $waveRow['net_amount']);
        $this->assertSame(1, $waveRow['reference_count']);
    }

    public function test_payments_report_http_render_for_staff(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-01', 'opening_cash' => 50000], $cashier);

        $product = $this->makeProduct($store, ['retail_price' => 12000]);
        $this->seedStock($store, $product, '10');

        $this->sales->addToCart($store, $product->id, null, '1');
        $this->sales->post($store, $this->sales->cartLines($store), [
            ['method' => 'kpay', 'amount' => '12000', 'reference' => 'KP-001122'],
        ], $cashier, $shift);

        $this->actingAs($cashier);

        $response = $this->get("/store/{$store->slug}/pos/reports/payments");
        $response->assertOk();
        $response->assertSee(__('messages.reports_payments'));
        $response->assertSee('KP-001122');
        $response->assertSee('formatCurrency', false);
    }

    public function test_payments_export_csv_and_xlsx_with_formula_sanitization(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-01', 'opening_cash' => 50000], $cashier);

        $product = $this->makeProduct($store, ['retail_price' => 10000]);
        $this->seedStock($store, $product, '10');

        // Reference with formula injection attempt
        $maliciousRef = '=CMD|\' /C calc\'!A0';
        $this->sales->addToCart($store, $product->id, null, '1');
        $this->sales->post($store, $this->sales->cartLines($store), [
            ['method' => 'kpay', 'amount' => '10000', 'reference' => $maliciousRef],
        ], $cashier, $shift);

        $this->actingAs($cashier);

        // Test CSV export
        $csvResponse = $this->get("/store/{$store->slug}/pos/reports/payments/export?format=csv");
        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', $csvResponse->headers->get('content-type'));
        // Formula injection should be escaped with quote prefix "'="
        $this->assertStringContainsString("'=CMD|", $csvResponse->streamedContent());

        // Test XLSX export
        $xlsxResponse = $this->get("/store/{$store->slug}/pos/reports/payments/export?format=xlsx");
        $xlsxResponse->assertOk();
        $this->assertStringContainsString('spreadsheet', $xlsxResponse->headers->get('content-type'));
    }

    public function test_payment_reconciliation_store_isolation(): void
    {
        $storeA = $this->makeStore('store-iso-a');
        $storeB = $this->makeStore('store-iso-b');

        $cashierA = $this->staff($storeA, 'CashierA');
        $cashierB = $this->staff($storeB, 'CashierB');

        $shiftA = $this->shifts->openShift($storeA, ['register_name' => 'REG-A', 'opening_cash' => 50000], $cashierA);
        $shiftB = $this->shifts->openShift($storeB, ['register_name' => 'REG-B', 'opening_cash' => 50000], $cashierB);

        $prodA = $this->makeProduct($storeA, ['retail_price' => 10000]);
        $prodB = $this->makeProduct($storeB, ['retail_price' => 20000]);
        $this->seedStock($storeA, $prodA, '5');
        $this->seedStock($storeB, $prodB, '5');

        // Sale in Store A
        $this->sales->addToCart($storeA, $prodA->id, null, '1');
        $this->sales->post($storeA, $this->sales->cartLines($storeA), [
            ['method' => 'kpay', 'amount' => '10000', 'reference' => 'STORE-A-REF'],
        ], $cashierA, $shiftA);

        // Sale in Store B
        $this->sales->addToCart($storeB, $prodB->id, null, '1');
        $this->sales->post($storeB, $this->sales->cartLines($storeB), [
            ['method' => 'wavepay', 'amount' => '20000', 'reference' => 'STORE-B-REF'],
        ], $cashierB, $shiftB);

        // Store A report
        $reportA = $this->reports->paymentMethodReconciliation($storeA, Carbon::today(), Carbon::today());
        $this->assertSame(1, $reportA['payment_count']);
        $this->assertSame('10000.00', $reportA['total_collected']);
        $this->assertSame('STORE-A-REF', $reportA['payments']->first()->reference);

        // Store B report
        $reportB = $this->reports->paymentMethodReconciliation($storeB, Carbon::today(), Carbon::today());
        $this->assertSame(1, $reportB['payment_count']);
        $this->assertSame('20000.00', $reportB['total_collected']);
        $this->assertSame('STORE-B-REF', $reportB['payments']->first()->reference);
    }
}
