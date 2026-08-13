<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\PosReturn;
use App\POS\Models\PosSale;
use App\POS\Services\CashierShiftService;
use App\POS\Services\CustomerDebtService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosReturnService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosReturnTest extends TestCase
{
    use RefreshDatabase;

    private PosSaleService $sales;

    private PosReturnService $returns;

    private InventoryService $inventory;

    private CashierShiftService $shifts;

    private CustomerDebtService $debts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sales = app(PosSaleService::class);
        $this->returns = app(PosReturnService::class);
        $this->inventory = app(InventoryService::class);
        $this->shifts = app(CashierShiftService::class);
        $this->debts = app(CustomerDebtService::class);
    }

    private function makeStore(string $slug = 'shop-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function staff(Store $store): User
    {
        $user = User::create([
            'name' => 'Cashier ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        return $user;
    }

    private function makeCustomer(Store $store): User
    {
        $user = User::create([
            'name' => 'Customer ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'retail_customer', 'status' => 'active']);

        return $user;
    }

    private function makeProduct(Store $store, int $price = 10000): Product
    {
        $name = 'Phone ' . Str::random(3);

        return Product::create([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(3)),
            'retail_price' => $price,
            'wholesale_price' => $price - 1000,
        ]);
    }

    private function seedStock(Store $store, Product $product, string $qty = '10'): void
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

    private function openShift(Store $store, User $cashier)
    {
        return $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => 50000], $cashier);
    }

    /**
     * Post a sale of $qty units at $price paid by cash (or credit when $credit).
     */
    private function postedSale(Store $store, User $cashier, int $price = 10000, string $qty = '2', bool $credit = false): PosSale
    {
        $product = $this->makeProduct($store, $price);
        $this->seedStock($store, $product, '10');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, $qty);
        $total = bcadd('0', bcmul((string) $price, $qty, 2), 2);

        $customer = $credit ? $this->makeCustomer($store) : null;

        return $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [$credit ? ['method' => 'credit', 'amount' => $total] : ['method' => 'cash', 'amount' => $total]],
            $cashier,
            $shift,
            null,
            $customer?->id,
        );
    }

    private function saleItems(PosSale $sale): array
    {
        return $sale->items->map(fn ($i) => [
            'pos_sale_item_id' => $i->id,
            'quantity' => (string) $i->quantity,
        ])->all();
    }

    /* ------------------------------------------------------------------ */
    /*  Full refund                                                        */
    /* ------------------------------------------------------------------ */

    public function test_full_cash_refund_restores_stock_and_marks_sale_refunded(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier, 15000, '2');

        $this->assertSame('8.000', $this->inventory->totalOnHand($store->id, $sale->items->first()->product_id));

        $refund = $this->returns->post(
            $store,
            $sale,
            $this->saleItems($sale),
            [['method' => 'cash', 'amount' => '30000']],
            $cashier,
            $this->shifts->openShiftFor($store, $cashier),
        );

        $this->assertTrue($refund->isPosted());
        $this->assertStringStartsWith('RET-', $refund->refund_number);
        $this->assertSame('30000.00', (string) $refund->total);
        $this->assertCount(1, $refund->items); // 1 merged line, qty 2
        $this->assertSame('2.000', (string) $refund->items->first()->quantity);

        // Stock restored at the original line cost.
        $this->assertSame('10.000', $this->inventory->totalOnHand($store->id, $sale->items->first()->product_id));
        $this->assertDatabaseHas('inventory_movements', [
            'source_type' => 'pos_return',
            'source_id' => $refund->id,
            'movement_type' => 'sales_return',
            'quantity_delta' => '2',
        ]);

        // Cash went back out of the drawer.
        $this->assertSame('30000.00', (string) $this->shifts->openShiftFor($store, $cashier)->refresh()->cash_refunds);

        // Sale moved to refunded — never edited.
        $this->assertSame('refunded', $sale->refresh()->status);
        $this->assertNotNull($sale->refunded_at);
        $this->assertSame('30000.00', (string) $sale->total); // original total unchanged

        // Audited.
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'pos_return_posted',
            'entity_type' => 'pos_return',
            'entity_id' => $refund->id,
        ]);
    }

    public function test_partial_refund_marks_sale_partially_refunded(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier, 10000, '2');

        $refund = $this->returns->post(
            $store,
            $sale,
            [['pos_sale_item_id' => $sale->items->first()->id, 'quantity' => '1']],
            [['method' => 'cash', 'amount' => '10000']],
            $cashier,
            $this->shifts->openShiftFor($store, $cashier),
        );

        $this->assertSame('10000.00', (string) $refund->total);
        $this->assertSame('partially_refunded', $sale->refresh()->status);

        // Only 1 unit is now refundable.
        $refunded = $this->returns->refundedQuantities($store, $sale);
        $this->assertSame('1.000', (string) $refunded[$sale->items->first()->id]);
    }

    /* ------------------------------------------------------------------ */
    /*  Guards                                                            */
    /* ------------------------------------------------------------------ */

    public function test_refund_more_than_sold_is_blocked_without_trace(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier, 10000, '2');
        $productId = $sale->items->first()->product_id;

        try {
            $this->returns->post(
                $store,
                $sale,
                [['pos_sale_item_id' => $sale->items->first()->id, 'quantity' => '3']],
                [['method' => 'cash', 'amount' => '30000']],
                $cashier,
                $this->shifts->openShiftFor($store, $cashier),
            );
            $this->fail('Expected refundable-quantity exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('refundable quantity', $e->getMessage());
        }

        $this->assertSame(0, PosReturn::count());
        $this->assertSame('8.000', $this->inventory->totalOnHand($store->id, $productId));
        $this->assertSame('posted', $sale->refresh()->status);
    }

    public function test_refund_of_fully_refunded_sale_is_blocked(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier, 10000, '1');
        $shift = $this->shifts->openShiftFor($store, $cashier);

        $this->returns->post($store, $sale, $this->saleItems($sale), [['method' => 'cash', 'amount' => '10000']], $cashier, $shift);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('already fully refunded');

        $this->returns->post($store, $sale, $this->saleItems($sale), [['method' => 'cash', 'amount' => '10000']], $cashier, $shift);
    }

    public function test_refund_of_unposted_sale_is_blocked(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);
        $this->sales->addToCart($store, $product->id, null, '1');
        $held = $this->sales->holdCart($store, $cashier, $shift);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('Only posted sales');

        $this->returns->post($store, $held, $this->saleItems($held), [['method' => 'cash', 'amount' => '10000']], $cashier, $shift);
    }

    public function test_cash_refund_requires_open_shift(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier, 10000, '1');

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('open cashier shift');

        $this->returns->post($store, $sale, $this->saleItems($sale), [['method' => 'cash', 'amount' => '10000']], $cashier, null);
    }

    public function test_refund_amounts_must_equal_return_value(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier, 20000, '1');

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('must equal');

        $this->returns->post($store, $sale, $this->saleItems($sale), [['method' => 'cash', 'amount' => '15000']], $cashier, $this->shifts->openShiftFor($store, $cashier));
    }

    /* ------------------------------------------------------------------ */
    /*  Credit refunds                                                     */
    /* ------------------------------------------------------------------ */

    public function test_credit_refund_reduces_customer_debt(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier, 15000, '1', credit: true);
        $this->assertSame('15000.00', $this->debts->balanceFor($store->id, $sale->customer_id));

        $refund = $this->returns->post(
            $store,
            $sale,
            $this->saleItems($sale),
            [['method' => 'credit', 'amount' => '15000']],
            $cashier,
            $this->shifts->openShiftFor($store, $cashier),
        );

        // Receivable cancelled via a NEW refund ledger entry (SoT §17).
        $this->assertSame('0.00', $this->debts->balanceFor($store->id, $sale->customer_id));
        $this->assertDatabaseHas('customer_ledger_entries', [
            'store_id' => $store->id,
            'customer_id' => $sale->customer_id,
            'type' => 'refund',
            'amount' => '-15000.00',
            'source_type' => 'pos_return',
            'source_id' => $refund->id,
        ]);
        $this->assertSame('refunded', $sale->refresh()->status);

        // The original sale_debt entry is untouched.
        $this->assertDatabaseHas('customer_ledger_entries', ['source_id' => $sale->id, 'type' => 'sale_debt', 'amount' => '15000.00']);
    }

    public function test_credit_refund_exceeding_remaining_credit_is_blocked(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        // 20,000 sale paid 10,000 cash + 10,000 credit.
        $customer = $this->makeCustomer($store);
        $product = $this->makeProduct($store, 20000);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);
        $this->sales->addToCart($store, $product->id, null, '1');
        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'cash', 'amount' => '10000'], ['method' => 'credit', 'amount' => '10000']],
            $cashier,
            $shift,
            null,
            $customer->id,
        );

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('remaining credit');

        $this->returns->post(
            $store,
            $sale,
            $this->saleItems($sale),
            [['method' => 'credit', 'amount' => '15000'], ['method' => 'cash', 'amount' => '5000']],
            $cashier,
            $shift,
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Idempotency + cross-store                                          */
    /* ------------------------------------------------------------------ */

    public function test_same_client_transaction_posts_one_return(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier, 10000, '1');
        $shift = $this->shifts->openShiftFor($store, $cashier);

        $first = $this->returns->post($store, $sale, $this->saleItems($sale), [['method' => 'cash', 'amount' => '10000']], $cashier, $shift, 'retry-txn-1');
        $second = $this->returns->post($store, $sale, $this->saleItems($sale), [['method' => 'cash', 'amount' => '10000']], $cashier, $shift, 'retry-txn-1');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, PosReturn::count());
    }

    public function test_cross_store_refund_is_blocked(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $cashierA = $this->staff($storeA);
        $cashierB = $this->staff($storeB);
        $sale = $this->postedSale($storeA, $cashierA, 10000, '1');

        $this->actingAs($cashierB)
            ->post("/store/{$storeB->slug}/pos/sales/{$sale->id}/refunds", [
                'items' => [['pos_sale_item_id' => $sale->items->first()->id, 'quantity' => '1']],
                'refunds' => [['method' => 'cash', 'amount' => '10000']],
            ])
            ->assertNotFound();

        $this->assertSame(0, PosReturn::count());
        $this->assertSame('posted', $sale->refresh()->status);
    }

    public function test_non_staff_cannot_view_refund_form(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier, 10000, '1');
        $outsider = User::create([
            'name' => 'Outsider', 'phone' => '09' . rand(10000000, 99999999), 'password' => bcrypt('x'), 'role' => 'customer',
        ]);

        $this->actingAs($outsider)
            ->get("/store/{$store->slug}/pos/sales/{$sale->id}/refund")
            ->assertForbidden();
    }

    /* ------------------------------------------------------------------ */
    /*  HTTP flow                                                          */
    /* ------------------------------------------------------------------ */

    public function test_http_refund_form_and_post_flow(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $sale = $this->postedSale($store, $cashier, 12000, '2');

        $this->actingAs($cashier);

        $this->get("/store/{$store->slug}/pos/sales/{$sale->id}/refund")
            ->assertOk()
            ->assertSee($sale->receipt_number)
            ->assertSee(__('messages.post_refund'));

        $this->post("/store/{$store->slug}/pos/sales/{$sale->id}/refunds", [
            'items' => [
                ['pos_sale_item_id' => $sale->items->first()->id, 'quantity' => '2'],
            ],
            'refunds' => [
                ['method' => 'cash', 'amount' => '24000'],
                ['method' => 'credit', 'amount' => '0'],
            ],
        ])->assertRedirect();

        $this->assertSame(1, PosReturn::count());
        $this->assertSame('refunded', $sale->refresh()->status);
        $this->assertSame('24000.00', (string) $this->shifts->openShiftFor($store, $cashier)->refresh()->cash_refunds);
    }
}
