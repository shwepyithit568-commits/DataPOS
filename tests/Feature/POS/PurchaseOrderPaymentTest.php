<?php

namespace Tests\Feature\POS;

use App\Models\Supplier;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\PurchaseOrder;
use App\POS\Services\InventoryService;
use App\POS\Services\PurchaseOrderService;
use App\POS\Services\SupplierDebtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseOrderPaymentTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseOrderService $service;
    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PurchaseOrderService::class);
        $this->inventory = app(InventoryService::class);
    }

    private function makeStore(string $slug = 'shop-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function staff(Store $store): User
    {
        $user = User::create([
            'name' => 'Staff ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        return $user;
    }

    private function makeProduct(Store $store, array $overrides = []): Product
    {
        $name = $overrides['name'] ?? 'Widget ' . Str::random(3);

        return Product::create(array_merge([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(3)),
            'retail_price' => 10000,
            'wholesale_price' => 9000,
        ], $overrides));
    }

    private function makeSupplier(Store $store, string $name = 'Acme Corp'): Supplier
    {
        return Supplier::create([
            'store_id' => $store->id,
            'name' => $name,
            'phone' => '09' . rand(10000000, 99999999),
        ]);
    }

    /**
     * Create + order + receive a PO, returning the refreshed model.
     */
    private function receivedPo(
        Store $store,
        User $actor,
        Product $product,
        string $qty = '10',
        string $cost = '5000',
        ?int $supplierId = null,
    ): PurchaseOrder {
        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => $qty, 'unit_cost' => $cost],
        ], $supplierId, null, null, $actor);

        $this->service->markOrdered($po, $actor);
        $result = $this->service->receive($po->fresh(), $actor);

        return $result['po'];
    }

    /* ------------------------------------------------------------------ */
    /*  Model: Payment status defaults                                     */
    /* ------------------------------------------------------------------ */

    public function test_create_po_defaults_to_unpaid(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '5000'],
        ], null, null, null, $actor);

        $this->assertSame('unpaid', $po->payment_status);
        $this->assertSame('0.00', (string) $po->paid_amount);
        $this->assertSame('50000.00', (string) $po->remaining_balance);
        $this->assertFalse($po->isPaid());
        $this->assertTrue($po->isUnpaid());
        $this->assertFalse($po->isPartiallyPaid());
    }

    public function test_create_po_with_upfront_partial_payment(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '5000'],
        ], null, null, null, $actor, [
            'payment_status' => 'partial',
            'paid_amount' => '20000',
        ]);

        $this->assertSame('partial', $po->payment_status);
        $this->assertSame('20000.00', (string) $po->paid_amount);
        $this->assertSame('30000.00', (string) $po->remaining_balance);
    }

    public function test_create_po_fully_paid_upfront(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '5000'],
        ], null, null, null, $actor, [
            'payment_status' => 'paid',
            'paid_amount' => '50000',
        ]);

        $this->assertSame('paid', $po->payment_status);
        $this->assertSame('50000.00', (string) $po->paid_amount);
        $this->assertSame('0.00', (string) $po->remaining_balance);
        $this->assertTrue($po->isPaid());
    }

    /* ------------------------------------------------------------------ */
    /*  Service: applyPayment (specific PO)                                */
    /* ------------------------------------------------------------------ */

    public function test_apply_partial_payment_to_received_po(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->receivedPo($store, $actor, $product);

        $this->assertSame('50000.00', (string) $po->remaining_balance);
        $this->assertTrue($po->canReceivePayment());

        $paid = $this->service->applyPayment($po, ['amount' => '20000'], $actor);

        $this->assertSame('20000.00', (string) $paid->paid_amount);
        $this->assertSame('30000.00', (string) $paid->remaining_balance);
        $this->assertSame('partial', $paid->payment_status);
        $this->assertTrue($paid->isPartiallyPaid());
    }

    public function test_apply_full_payment_marks_paid(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->receivedPo($store, $actor, $product);

        $paid = $this->service->applyPayment($po, ['amount' => '50000'], $actor);

        $this->assertSame('50000.00', (string) $paid->paid_amount);
        $this->assertSame('0.00', (string) $paid->remaining_balance);
        $this->assertSame('paid', $paid->payment_status);
        $this->assertTrue($paid->isPaid());
        $this->assertFalse($paid->canReceivePayment());
    }

    public function test_apply_payment_exceeding_balance_rejected(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $po = $this->receivedPo($store, $actor, $product);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('exceeds the remaining balance');
        $this->service->applyPayment($po, ['amount' => '60000'], $actor);
    }

    public function test_apply_zero_payment_rejected(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $po = $this->receivedPo($store, $actor, $product);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('greater than zero');
        $this->service->applyPayment($po, ['amount' => '0'], $actor);
    }

    public function test_apply_payment_to_pending_po_rejected(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '5000'],
        ], null, null, null, $actor);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('must be received');
        $this->service->applyPayment($po, ['amount' => '10000'], $actor);
    }

    public function test_apply_partial_then_remaining(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->receivedPo($store, $actor, $product);

        $this->service->applyPayment($po, ['amount' => '15000'], $actor);
        $po = $po->fresh();
        $this->assertSame('partial', $po->payment_status);
        $this->assertSame('35000.00', (string) $po->remaining_balance);

        $this->service->applyPayment($po, ['amount' => '35000'], $actor);
        $po = $po->fresh();
        $this->assertSame('paid', $po->payment_status);
        $this->assertSame('0.00', (string) $po->remaining_balance);
    }

    public function test_apply_payment_records_audit_log(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->receivedPo($store, $actor, $product);
        $this->service->applyPayment($po, ['amount' => '10000', 'reference' => 'PAY-001'], $actor);

        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'purchase_order_payment',
            'entity_type' => 'purchase_order',
            'entity_id' => $po->id,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Supplier balance tracking                                          */
    /* ------------------------------------------------------------------ */

    public function test_receive_increases_supplier_total_credit(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $this->receivedPo($store, $actor, $product, '10', '5000', $supplier->id);

        $supplier->refresh();
        $this->assertSame('50000.00', (string) $supplier->total_credit);
        $this->assertSame('0.00', (string) $supplier->total_repaid);
        $this->assertTrue($supplier->has_outstanding_balance);
        $this->assertSame('50000.00', (string) $supplier->remaining_balance);
    }

    public function test_payment_decreases_supplier_credit(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $po = $this->receivedPo($store, $actor, $product, '10', '5000', $supplier->id);

        $this->service->applyPayment($po, ['amount' => '20000'], $actor);

        $supplier->refresh();
        $this->assertSame('30000.00', (string) $supplier->remaining_balance);
        $this->assertSame('20000.00', (string) $supplier->total_repaid);
    }

    public function test_upfront_payment_increases_supplier_repaid(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '5000'],
        ], $supplier->id, null, null, $actor, [
            'payment_status' => 'partial',
            'paid_amount' => '20000',
        ]);

        $supplier->refresh();
        $this->assertSame('20000.00', (string) $supplier->total_repaid);
    }

    public function test_cancel_reverses_upfront_payment(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '5000'],
        ], $supplier->id, null, null, $actor, [
            'payment_status' => 'partial',
            'paid_amount' => '20000',
        ]);

        $this->service->cancel($po, $actor);

        $supplier->refresh();
        $this->assertSame('0.00', (string) $supplier->total_repaid);
    }

    public function test_supplier_no_outstanding_when_no_pos(): void
    {
        $store = $this->makeStore();
        $supplier = $this->makeSupplier($store);

        $this->assertSame('0.00', (string) $supplier->remaining_balance);
        $this->assertFalse($supplier->has_outstanding_balance);
    }

    /* ------------------------------------------------------------------ */
    /*  Service: applyPaymentFifo (general payment)                        */
    /* ------------------------------------------------------------------ */

    public function test_fifo_distributes_oldest_first(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $po1 = $this->receivedPo($store, $actor, $product, '10', '5000', $supplier->id);
        $po2 = $this->receivedPo($store, $actor, $product, '5', '10000', $supplier->id);

        $supplier->refresh();
        $this->assertSame('100000.00', (string) $supplier->total_credit);

        $result = $this->service->applyPaymentFifo($supplier, '70000', $actor);

        $this->assertCount(2, $result['applied']);
        $this->assertSame($po1->id, $result['applied'][0]['po']);
        $this->assertSame('50000.00', $result['applied'][0]['amount']);
        $this->assertSame($po2->id, $result['applied'][1]['po']);
        $this->assertSame('20000.00', $result['applied'][1]['amount']);
        $this->assertSame('0.00', $result['remaining']);

        $this->assertSame('paid', $po1->fresh()->payment_status);
        $this->assertSame('partial', $po2->fresh()->payment_status);
        $this->assertSame('30000.00', (string) $po2->fresh()->remaining_balance);

        $supplier->refresh();
        $this->assertSame('30000.00', (string) $supplier->remaining_balance);
        $this->assertSame('70000.00', (string) $supplier->total_repaid);
    }

    public function test_fifo_excess_returns_remainder(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $po = $this->receivedPo($store, $actor, $product, '10', '5000', $supplier->id);

        $result = $this->service->applyPaymentFifo($supplier, '60000', $actor);

        $this->assertCount(1, $result['applied']);
        $this->assertSame('50000.00', $result['applied'][0]['amount']);
        $this->assertSame('10000.00', $result['remaining']);
        $this->assertSame('paid', $po->fresh()->payment_status);
    }

    public function test_fifo_zero_rejected(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $supplier = $this->makeSupplier($store);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('greater than zero');
        $this->service->applyPaymentFifo($supplier, '0', $actor);
    }

    public function test_fifo_no_unpaid_pos_returns_all_remainder(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $po = $this->receivedPo($store, $actor, $product, '10', '5000', $supplier->id);
        $this->service->applyPayment($po, ['amount' => '50000'], $actor);

        $result = $this->service->applyPaymentFifo($supplier, '10000', $actor);
        $this->assertCount(0, $result['applied']);
        $this->assertSame('10000.00', $result['remaining']);
    }

    public function test_fifo_skips_pending_pos(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '5000'],
        ], $supplier->id, null, null, $actor);

        $result = $this->service->applyPaymentFifo($supplier, '10000', $actor);
        $this->assertCount(0, $result['applied']);
        $this->assertSame('10000.00', $result['remaining']);
    }

    public function test_fifo_distributes_across_three_pos(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $po1 = $this->receivedPo($store, $actor, $product, '10', '5000', $supplier->id); // 50000
        $po2 = $this->receivedPo($store, $actor, $product, '5', '5000', $supplier->id);  // 25000
        $po3 = $this->receivedPo($store, $actor, $product, '3', '5000', $supplier->id);  // 15000

        // Pay 60000 — should pay po1 (50000) + po2 (10000)
        $result = $this->service->applyPaymentFifo($supplier, '60000', $actor);

        $this->assertCount(2, $result['applied']);
        $this->assertSame($po1->id, $result['applied'][0]['po']);
        $this->assertSame('50000.00', $result['applied'][0]['amount']);
        $this->assertSame($po2->id, $result['applied'][1]['po']);
        $this->assertSame('10000.00', $result['applied'][1]['amount']);
        $this->assertSame('0.00', $result['remaining']);

        $this->assertSame('paid', $po1->fresh()->payment_status);
        $this->assertSame('partial', $po2->fresh()->payment_status);
        $this->assertSame('unpaid', $po3->fresh()->payment_status);
    }

    /* ------------------------------------------------------------------ */
    /*  SupplierDebtService                                                */
    /* ------------------------------------------------------------------ */

    public function test_debt_service_lists_suppliers_with_balances(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $this->receivedPo($store, $actor, $product, '10', '5000', $supplier->id);

        $debtService = app(SupplierDebtService::class);
        $suppliers = $debtService->listSuppliersWithBalances($store);

        $this->assertCount(1, $suppliers);
        $this->assertSame($supplier->id, $suppliers[0]['supplier']->id);
        $this->assertSame('50000', (string) $suppliers[0]['balance']);
        $this->assertSame(1, $suppliers[0]['unpaid_count']);
    }

    public function test_debt_service_excludes_fully_paid_suppliers(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $po = $this->receivedPo($store, $actor, $product, '10', '5000', $supplier->id);
        $this->service->applyPayment($po, ['amount' => '50000'], $actor);

        $debtService = app(SupplierDebtService::class);
        $suppliers = $debtService->listSuppliersWithBalances($store);

        $this->assertCount(0, $suppliers);
    }

    public function test_debt_service_get_unpaid_orders(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $po1 = $this->receivedPo($store, $actor, $product, '10', '5000', $supplier->id);
        $po2 = $this->receivedPo($store, $actor, $product, '5', '5000', $supplier->id);
        $this->service->applyPayment($po1, ['amount' => '50000'], $actor); // fully paid

        $debtService = app(SupplierDebtService::class);
        $unpaid = $debtService->getUnpaidOrders($supplier);

        $this->assertCount(1, $unpaid);
        $this->assertSame($po2->id, $unpaid->first()->id);
    }

    public function test_debt_service_recalculate_balances(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $po = $this->receivedPo($store, $actor, $product, '10', '5000', $supplier->id);

        // Manually corrupt the supplier balance.
        $supplier->update(['total_credit' => '0', 'total_repaid' => '0']);
        $supplier->refresh();
        $this->assertSame('0.00', (string) $supplier->total_credit);

        // Recalculate should fix it from PO data.
        $debtService = app(SupplierDebtService::class);
        $debtService->recalculateSupplierBalances($supplier);

        $supplier->refresh();
        $this->assertSame('50000.00', (string) $supplier->total_credit);
    }

    /* ------------------------------------------------------------------ */
    /*  HTTP: payables + pay routes                                        */
    /* ------------------------------------------------------------------ */

    public function test_payables_index_renders(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);

        $this->actingAs($actor)
            ->get("/store/{$store->slug}/pos/purchases/payables")
            ->assertOk()
            ->assertSee(__('messages.payables_title'));
    }

    public function test_payables_show_renders(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $this->receivedPo($store, $actor, $product, '10', '5000', $supplier->id);

        $this->actingAs($actor)
            ->get("/store/{$store->slug}/pos/purchases/payables/{$supplier->id}")
            ->assertOk()
            ->assertSee($supplier->name)
            ->assertSee('50,000');
    }

    public function test_payables_pay_applies_fifo(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $po = $this->receivedPo($store, $actor, $product, '10', '5000', $supplier->id);

        $this->actingAs($actor)
            ->post("/store/{$store->slug}/pos/purchases/payables/{$supplier->id}/pay", [
                'amount' => '30000',
            ])
            ->assertRedirect();

        $po = $po->fresh();
        $this->assertSame('30000.00', (string) $po->paid_amount);
        $this->assertSame('partial', $po->payment_status);

        $supplier->refresh();
        $this->assertSame('20000.00', (string) $supplier->remaining_balance);
    }

    public function test_http_pay_specific_po(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->receivedPo($store, $actor, $product);

        $this->actingAs($actor)
            ->post("/store/{$store->slug}/pos/purchases/{$po->id}/pay", [
                'amount' => '25000',
            ])
            ->assertRedirect();

        $po = $po->fresh();
        $this->assertSame('25000.00', (string) $po->paid_amount);
        $this->assertSame('partial', $po->payment_status);
    }

    public function test_http_pay_validation_rejects_zero(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->receivedPo($store, $actor, $product);

        $this->actingAs($actor)
            ->post("/store/{$store->slug}/pos/purchases/{$po->id}/pay", [
                'amount' => '0',
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_export_renders(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);

        $this->actingAs($actor)
            ->get("/store/{$store->slug}/pos/purchases/export?format=excel")
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_export_pdf_renders_html(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);

        $this->actingAs($actor)
            ->get("/store/{$store->slug}/pos/purchases/export?format=pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function test_po_show_displays_payment_summary_for_received(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);

        $po = $this->receivedPo($store, $actor, $product);
        $this->service->applyPayment($po, ['amount' => '20000'], $actor);

        $this->actingAs($actor)
            ->get("/store/{$store->slug}/pos/purchases/{$po->id}")
            ->assertOk()
            ->assertSee(__('messages.payables_payment_summary'))
            ->assertSee('20,000')
            ->assertSee('30,000');
    }

    public function test_payables_export_csv(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $this->receivedPo($store, $actor, $product, '10', '5000', $supplier->id);

        $response = $this->actingAs($actor)
            ->get("/store/{$store->slug}/pos/purchases/payables/export");

        $response->assertOk();
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response->baseResponse);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_payables_redirects_to_pos_purchases_payables(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);

        $response = $this->actingAs($actor)
            ->get("/store/{$store->slug}/admin/payables");

        $response->assertRedirect(route('pos.purchases.payables', ['store_slug' => $store->slug]));
    }
}
