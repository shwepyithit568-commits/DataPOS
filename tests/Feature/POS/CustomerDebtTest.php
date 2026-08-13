<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CustomerLedgerEntry;
use App\POS\Models\PosSale;
use App\POS\Services\CashierShiftService;
use App\POS\Services\CustomerDebtService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerDebtTest extends TestCase
{
    use RefreshDatabase;

    private PosSaleService $sales;

    private InventoryService $inventory;

    private CashierShiftService $shifts;

    private CustomerDebtService $debts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sales = app(PosSaleService::class);
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

    private function makeCustomer(Store $store, string $role = 'retail_customer'): User
    {
        $user = User::create([
            'name' => 'Customer ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => $role, 'status' => 'active']);

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

    /* ------------------------------------------------------------------ */
    /*  Customer search                                                    */
    /* ------------------------------------------------------------------ */

    public function test_customer_search_finds_store_customers_by_name_and_phone(): void
    {
        $store = $this->makeStore();
        $customer = $this->makeCustomer($store);
        $customer->update(['name' => 'Daw Khin Khin', 'phone' => '09123456789']);

        $this->actingAs($this->staff($store));

        $byName = $this->getJson("/store/{$store->slug}/pos/customers?q=khin")
            ->assertOk()
            ->json('customers');
        $this->assertCount(1, $byName);
        $this->assertSame($customer->id, $byName[0]['id']);

        $byPhone = $this->getJson("/store/{$store->slug}/pos/customers?q=0912345")
            ->assertOk()
            ->json('customers');
        $this->assertCount(1, $byPhone);
    }

    public function test_customer_search_is_store_scoped(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $customerB = $this->makeCustomer($storeB);
        $customerB->update(['name' => 'Zaw Zaw Aung', 'phone' => '09999999999']);

        $this->actingAs($this->staff($storeA));

        $this->getJson("/store/{$storeA->slug}/pos/customers?q=zaw")
            ->assertOk()
            ->assertJsonCount(0, 'customers');
    }

    public function test_customer_search_excludes_staff_and_inactive_memberships(): void
    {
        $store = $this->makeStore();
        $staffMember = $this->staff($store);
        $staffMember->update(['name' => 'Staff Bo Bo']);

        $pending = User::create([
            'name' => 'Pending Customer', 'phone' => '09111111111', 'password' => bcrypt('x'), 'role' => 'customer',
        ]);
        $pending->stores()->attach($store->id, ['role' => 'retail_customer', 'status' => 'pending']);

        $this->actingAs($staffMember);

        $this->getJson("/store/{$store->slug}/pos/customers?q=b")
            ->assertOk()
            ->assertJsonCount(0, 'customers');
    }

    /* ------------------------------------------------------------------ */
    /*  Credit (debt) posting                                              */
    /* ------------------------------------------------------------------ */

    public function test_full_credit_sale_creates_receivable_and_leaves_drawer_untouched(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $customer = $this->makeCustomer($store);
        $product = $this->makeProduct($store, 15000);
        $this->seedStock($store, $product, '10');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '2');

        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [['method' => 'credit', 'amount' => '30000']],
            $cashier,
            $shift,
            null,
            $customer->id,
        );

        $this->assertTrue($sale->isPosted());
        $this->assertSame($customer->id, (int) $sale->customer_id);
        $this->assertSame('30000.00', (string) $sale->total);

        // Receivable recorded on the customer ledger, referencing the sale.
        $this->assertDatabaseHas('customer_ledger_entries', [
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'type' => 'sale_debt',
            'amount' => '30000.00',
            'source_type' => 'pos_sale',
            'source_id' => $sale->id,
        ]);

        $this->assertSame('30000.00', $this->debts->balanceFor($store->id, $customer->id));

        // Credit is not cash — the drawer gets nothing.
        $this->assertSame('0.00', (string) $shift->refresh()->cash_sales);

        // Stock still moved (the goods left the shelf).
        $this->assertSame('8.000', $this->inventory->totalOnHand($store->id, $product->id));

        // Payment row recorded with the credit method.
        $this->assertSame('credit', $sale->payments->first()->method);
    }

    public function test_split_cash_plus_credit_drawer_keeps_cash_only(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $customer = $this->makeCustomer($store);
        $product = $this->makeProduct($store, 20000);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');

        $sale = $this->sales->post(
            $store,
            $this->sales->cartLines($store),
            [
                ['method' => 'cash', 'amount' => '12000'],
                ['method' => 'credit', 'amount' => '8000'],
            ],
            $cashier,
            $shift,
            null,
            $customer->id,
        );

        $this->assertSame('20000.00', (string) $sale->total);
        $this->assertSame('12000.00', (string) $shift->refresh()->cash_sales); // cash only
        $this->assertSame('8000.00', $this->debts->balanceFor($store->id, $customer->id));
    }

    public function test_credit_requires_attached_customer(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('customer must be attached');

        $this->sales->post($store, $this->sales->cartLines($store), [['method' => 'credit', 'amount' => '10000']], $cashier, $shift, null, null);
    }

    public function test_credit_with_cross_store_customer_is_blocked(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $cashierA = $this->staff($storeA);
        $customerB = $this->makeCustomer($storeB);
        $product = $this->makeProduct($storeA);
        $this->seedStock($storeA, $product, '5');
        $shift = $this->openShift($storeA, $cashierA);

        $this->sales->addToCart($storeA, $product->id, null, '1');

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('does not belong to this store');

        $this->sales->post($storeA, $this->sales->cartLines($storeA), [['method' => 'credit', 'amount' => '10000']], $cashierA, $shift, null, $customerB->id);
    }

    public function test_credit_overpayment_is_blocked(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $customer = $this->makeCustomer($store);
        $product = $this->makeProduct($store, 10000);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('exceeds');

        $this->sales->post($store, $this->sales->cartLines($store), [['method' => 'credit', 'amount' => '15000']], $cashier, $shift, null, $customer->id);
    }

    public function test_credit_without_customer_leaves_no_trace(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '10');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');

        try {
            $this->sales->post($store, $this->sales->cartLines($store), [['method' => 'credit', 'amount' => '10000']], $cashier, $shift);
            $this->fail('Expected credit-without-customer exception.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('customer must be attached', $e->getMessage());
        }

        $this->assertSame(0, PosSale::count());
        $this->assertSame(0, CustomerLedgerEntry::count());
        $this->assertSame('10.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    /* ------------------------------------------------------------------ */
    /*  Collections                                                        */
    /* ------------------------------------------------------------------ */

    public function test_collection_reduces_balance_with_a_new_entry(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $customer = $this->makeCustomer($store);
        $product = $this->makeProduct($store, 30000);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');
        $sale = $this->sales->post($store, $this->sales->cartLines($store), [['method' => 'credit', 'amount' => '30000']], $cashier, $shift, null, $customer->id);
        $this->assertSame('30000.00', $this->debts->balanceFor($store->id, $customer->id));

        $entry = $this->debts->collect($store, $customer->id, '20000', $cashier, 'Partial payment');

        $this->assertSame('collection', $entry->type);
        $this->assertSame('-20000.00', (string) $entry->amount);
        $this->assertSame('10000.00', $this->debts->balanceFor($store->id, $customer->id));

        // Fully settle.
        $this->debts->collect($store, $customer->id, '10000', $cashier);
        $this->assertSame('0.00', $this->debts->balanceFor($store->id, $customer->id));

        // The original sale debt entry is untouched (immutable ledger).
        $this->assertDatabaseHas('customer_ledger_entries', ['source_id' => $sale->id, 'type' => 'sale_debt', 'amount' => '30000.00']);
    }

    public function test_over_collection_is_blocked(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $customer = $this->makeCustomer($store);
        $product = $this->makeProduct($store, 10000);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');
        $this->sales->post($store, $this->sales->cartLines($store), [['method' => 'credit', 'amount' => '10000']], $cashier, $shift, null, $customer->id);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('more than the outstanding balance');

        $this->debts->collect($store, $customer->id, '50000', $cashier);
    }

    public function test_outstanding_customers_lists_only_debtors(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $debtor = $this->makeCustomer($store);
        $payer = $this->makeCustomer($store);
        $product = $this->makeProduct($store, 25000);
        $this->seedStock($store, $product, '10');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');
        $this->sales->post($store, $this->sales->cartLines($store), [['method' => 'credit', 'amount' => '25000']], $cashier, $shift, null, $debtor->id);

        $this->sales->addToCart($store, $product->id, null, '1');
        $this->sales->post($store, $this->sales->cartLines($store), [['method' => 'cash', 'amount' => '25000']], $cashier, $shift, null, $payer->id);

        $outstanding = $this->debts->outstandingCustomers($store);

        $this->assertCount(1, $outstanding);
        $this->assertSame($debtor->id, $outstanding[0]['customer_id']);
        $this->assertSame('25000.00', $outstanding[0]['balance']);
    }

    /* ------------------------------------------------------------------ */
    /*  Idempotency                                                        */
    /* ------------------------------------------------------------------ */

    public function test_same_client_transaction_posts_once(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $customer = $this->makeCustomer($store);

        $this->debts->recordSaleDebt($store, $customer->id, 999, '5000', $cashier, 'client-txn-1');
        $again = $this->debts->recordSaleDebt($store, $customer->id, 999, '5000', $cashier, 'client-txn-1');

        $this->assertSame(1, CustomerLedgerEntry::where('store_id', $store->id)->where('client_transaction_id', 'client-txn-1')->count());
        $this->assertSame('5000.00', $this->debts->balanceFor($store->id, $customer->id));
        $this->assertSame($again->id, CustomerLedgerEntry::first()->id);
    }

    public function test_reversal_corrects_a_posted_entry(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $customer = $this->makeCustomer($store);

        $debt = $this->debts->recordSaleDebt($store, $customer->id, 111, '7000', $cashier);
        $this->debts->reverse($store, $debt, $cashier, 'Wrong sale — voided');

        $this->assertSame('0.00', $this->debts->balanceFor($store->id, $customer->id));
        $this->assertDatabaseHas('customer_ledger_entries', ['type' => 'reversal', 'source_id' => $debt->id, 'amount' => '-7000.00']);
    }

    /* ------------------------------------------------------------------ */
    /*  HTTP flow                                                          */
    /* ------------------------------------------------------------------ */

    public function test_http_credit_sale_and_collection_flow(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $customer = $this->makeCustomer($store);
        $customer->update(['name' => 'U Phone Buyer']);
        $product = $this->makeProduct($store, 18000);
        $this->seedStock($store, $product, '5');
        $this->openShift($store, $cashier);

        $this->actingAs($cashier);

        $this->post("/store/{$store->slug}/pos/cart", ['product_id' => $product->id, 'quantity' => '1'])->assertRedirect();

        $this->post("/store/{$store->slug}/pos/post", [
            'customer_id' => $customer->id,
            'payments' => [
                ['method' => 'cash', 'amount' => '8000'],
                ['method' => 'credit', 'amount' => '10000'],
                ['method' => 'kpay', 'amount' => '0'],
            ],
        ])->assertRedirect();

        $sale = PosSale::first();
        $this->assertNotNull($sale);
        $this->assertSame($customer->id, (int) $sale->customer_id);
        $this->assertSame('10000.00', $this->debts->balanceFor($store->id, $customer->id));

        // Collect via HTTP.
        $this->post("/store/{$store->slug}/pos/customers/{$customer->id}/collect", ['amount' => '6000'])
            ->assertRedirect();
        $this->assertSame('4000.00', $this->debts->balanceFor($store->id, $customer->id));

        // Audited.
        $this->assertDatabaseHas('audit_logs', ['store_id' => $store->id, 'action' => 'customer_debt_collected']);
    }

    public function test_http_credit_without_customer_is_rejected(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $product = $this->makeProduct($store);
        $this->seedStock($store, $product, '5');
        $this->openShift($store, $cashier);

        $this->actingAs($cashier);
        $this->post("/store/{$store->slug}/pos/cart", ['product_id' => $product->id, 'quantity' => '1'])->assertRedirect();

        $this->post("/store/{$store->slug}/pos/post", [
            'payments' => [['method' => 'credit', 'amount' => '10000']],
        ])->assertRedirect();

        $this->assertSame(0, PosSale::count());
        $this->assertSame(0, CustomerLedgerEntry::count());
    }

    public function test_non_staff_cannot_collect_debt(): void
    {
        $store = $this->makeStore();
        $customer = $this->makeCustomer($store);
        $outsider = User::create([
            'name' => 'Outsider', 'phone' => '09' . rand(10000000, 99999999), 'password' => bcrypt('x'), 'role' => 'customer',
        ]);

        $this->actingAs($outsider)
            ->post("/store/{$store->slug}/pos/customers/{$customer->id}/collect", ['amount' => '1000'])
            ->assertForbidden();
    }

    public function test_cross_store_collection_is_blocked(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $cashierA = $this->staff($storeA);
        $cashierB = $this->staff($storeB);
        $customerA = $this->makeCustomer($storeA);
        $product = $this->makeProduct($storeA);
        $this->seedStock($storeA, $product, '5');
        $shift = $this->openShift($storeA, $cashierA);

        $this->sales->addToCart($storeA, $product->id, null, '1');
        $this->sales->post($storeA, $this->sales->cartLines($storeA), [['method' => 'credit', 'amount' => '10000']], $cashierA, $shift, null, $customerA->id);

        $this->actingAs($cashierB)
            ->post("/store/{$storeB->slug}/pos/customers/{$customerA->id}/collect", ['amount' => '1000'])
            ->assertForbidden();

        $this->assertSame('10000.00', $this->debts->balanceFor($storeA->id, $customerA->id));
    }

    public function test_receipt_shows_balance_due_for_credit_sale(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $customer = $this->makeCustomer($store);
        $customer->update(['name' => 'Ma Balance']);
        $product = $this->makeProduct($store, 15000);
        $this->seedStock($store, $product, '5');
        $shift = $this->openShift($store, $cashier);

        $this->sales->addToCart($store, $product->id, null, '1');
        $sale = $this->sales->post($store, $this->sales->cartLines($store), [['method' => 'credit', 'amount' => '15000']], $cashier, $shift, null, $customer->id);

        $this->actingAs($cashier)
            ->get("/store/{$store->slug}/pos/sales/{$sale->id}/receipt")
            ->assertOk()
            ->assertSee('Balance due')
            ->assertSee('Ma Balance')
            ->assertSee('15,000');
    }
}
