<?php

namespace Tests\Feature;

use App\Models\EloadAccount;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\BuyBack;
use App\POS\Models\ServiceJob;
use App\POS\Models\ServiceJobPayment;
use App\POS\Models\StockCount;
use App\POS\Models\StockCountLine;
use App\POS\Services\CustomerDebtService;
use App\POS\Services\EloadService;
use App\POS\Services\InventoryService;
use App\POS\Services\StockCountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression: money paths that used float arithmetic, skipped their balance
 * guard under concurrency, or accepted another store's records.
 */
class PosMoneyAndAtomicityTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(string $slug = 'money-store'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function user(Store $store, string $pivotRole = 'store_manager'): User
    {
        $user = User::create([
            'name'         => 'User ' . Str::random(4),
            'phone'        => '09' . rand(10000000, 99999999),
            'password'     => bcrypt('password'),
            'role'         => $pivotRole === 'store_manager' ? 'store_manager' : 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => $pivotRole, 'status' => 'active']);

        return $user;
    }

    private function product(Store $store, int $price = 10000): Product
    {
        $name = 'Item ' . Str::random(3);

        return Product::create([
            'store_id'        => $store->id,
            'sku'             => strtoupper(Str::random(8)),
            'name'            => $name,
            'slug'            => Str::slug($name . '-' . Str::random(3)),
            'retail_price'    => $price,
            'wholesale_price' => $price - 1000,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Buy back                                                          */
    /* ------------------------------------------------------------------ */

    public function test_buy_back_totals_are_exact_decimals(): void
    {
        $store = $this->makeStore();
        $manager = $this->user($store);
        $product = $this->product($store);

        $this->actingAs($manager)
            ->post(route('pos.buybacks.store', ['store_slug' => $store->slug]), [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => '0.10'],
                    ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => '0.20'],
                ],
            ])
            ->assertRedirect();

        // 3 x 0.10 + 3 x 0.20 = 0.90 exactly (floats give 0.30000000000000004).
        $buyback = BuyBack::where('store_id', $store->id)->firstOrFail();
        $this->assertSame('0.9000', (string) $buyback->total_value);
        $this->assertSame('0.9000', (string) $buyback->refund_amount);
    }

    public function test_buy_back_rejects_another_stores_product(): void
    {
        $store = $this->makeStore();
        $otherStore = $this->makeStore('other-money-store');
        $manager = $this->user($store);
        $foreignProduct = $this->product($otherStore);

        $this->actingAs($manager)
            ->post(route('pos.buybacks.store', ['store_slug' => $store->slug]), [
                'items' => [
                    ['product_id' => $foreignProduct->id, 'quantity' => 1, 'unit_price' => '10.00'],
                ],
            ])
            ->assertSessionHasErrors('items.0.product_id');

        $this->assertSame(0, BuyBack::count());
    }

    public function test_buy_back_rejects_another_stores_customer(): void
    {
        $store = $this->makeStore();
        $otherStore = $this->makeStore('other-money-store-2');
        $manager = $this->user($store);
        $product = $this->product($store);
        $foreignCustomer = $this->user($otherStore, 'retail_customer');

        $this->actingAs($manager)
            ->post(route('pos.buybacks.store', ['store_slug' => $store->slug]), [
                'customer_id' => $foreignCustomer->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '10.00'],
                ],
            ])
            ->assertSessionHasErrors('customer_id');

        $this->assertSame(0, BuyBack::count());
    }

    public function test_two_buy_back_lines_for_one_product_both_restore_stock(): void
    {
        $store = $this->makeStore();
        $manager = $this->user($store);
        $product = $this->product($store);
        $inventory = app(InventoryService::class);

        $inventory->postMovement([
            'store_id'              => $store->id,
            'product_id'            => $product->id,
            'movement_type'         => 'opening_balance',
            'quantity_delta'        => '5',
            'unit_cost'             => 1000,
            'source_type'           => 'opening_balance',
            'client_transaction_id' => 'seed:' . Str::uuid(),
            'occurred_at'           => now(),
        ]);

        $this->actingAs($manager)
            ->post(route('pos.buybacks.store', ['store_slug' => $store->slug]), [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => '50.00'],
                    ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => '50.00'],
                ],
            ]);

        $buyback = BuyBack::where('store_id', $store->id)->firstOrFail();

        $this->actingAs($manager)
            ->post(route('pos.buybacks.complete', ['store_slug' => $store->slug, 'buyback' => $buyback->id]))
            ->assertRedirect();

        // 5 seeded + 2 + 3 returned = 10; keying the ledger by product would
        // have dropped the second line and left it at 7.
        $this->assertSame('10.000', $inventory->totalOnHand($store->id, $product->id));
    }

    /* ------------------------------------------------------------------ */
    /*  Customer debt                                                     */
    /* ------------------------------------------------------------------ */

    public function test_debt_collection_rejects_another_stores_customer(): void
    {
        $store = $this->makeStore();
        $otherStore = $this->makeStore('other-money-store-3');
        $manager = $this->user($store);
        $foreignCustomer = $this->user($otherStore, 'retail_customer');
        $debts = app(CustomerDebtService::class);

        $this->expectException(InventoryException::class);

        $debts->collect($store, $foreignCustomer->id, '10.00', $manager);
    }

    public function test_debt_collection_cannot_exceed_the_outstanding_balance(): void
    {
        $store = $this->makeStore();
        $manager = $this->user($store);
        $customer = $this->user($store, 'retail_customer');
        $debts = app(CustomerDebtService::class);

        $debts->recordOpeningBalance($store, $customer->id, '100.00', $manager);

        $this->expectException(InventoryException::class);

        $debts->collect($store, $customer->id, '100.01', $manager);
    }

    public function test_debt_collection_clears_the_balance_exactly(): void
    {
        $store = $this->makeStore();
        $manager = $this->user($store);
        $customer = $this->user($store, 'retail_customer');
        $debts = app(CustomerDebtService::class);

        $debts->recordOpeningBalance($store, $customer->id, '100.00', $manager);
        $debts->collect($store, $customer->id, '100.00', $manager);

        $this->assertSame('0.00', $debts->balanceFor($store->id, $customer->id));
    }

    /* ------------------------------------------------------------------ */
    /*  Service job payments                                              */
    /* ------------------------------------------------------------------ */

    public function test_service_job_payment_cannot_exceed_the_outstanding_charge(): void
    {
        $store = $this->makeStore();
        $manager = $this->user($store);
        $job = ServiceJob::create([
            'store_id'         => $store->id,
            'job_number'       => 'JOB-' . Str::random(6),
            'customer_name'    => 'Ko Aung',
            'customer_phone'   => '09123456789',
            'device_type'      => 'phone',
            'reported_problem' => 'Screen cracked',
            'created_by'       => $manager->id,
            'status'           => 'received',
            'estimated_charge' => '100.00',
            'final_charge'     => '100.00',
        ]);

        $this->actingAs($manager)
            ->post(route('store.admin.service_jobs.payments.store', [
                'store_slug' => $store->slug,
                'job'        => $job->id,
            ]), ['method' => 'cash', 'amount' => '100.01'])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, ServiceJobPayment::count());
    }

    public function test_service_job_payment_up_to_the_charge_is_accepted(): void
    {
        $store = $this->makeStore();
        $manager = $this->user($store);
        $job = ServiceJob::create([
            'store_id'         => $store->id,
            'job_number'       => 'JOB-' . Str::random(6),
            'customer_name'    => 'Ko Aung',
            'customer_phone'   => '09123456789',
            'device_type'      => 'phone',
            'reported_problem' => 'Screen cracked',
            'created_by'       => $manager->id,
            'status'           => 'received',
            'estimated_charge' => '100.00',
            'final_charge'     => '100.00',
        ]);

        $this->actingAs($manager)
            ->post(route('store.admin.service_jobs.payments.store', [
                'store_slug' => $store->slug,
                'job'        => $job->id,
            ]), ['method' => 'cash', 'amount' => '100.00'])
            ->assertSessionHasNoErrors();

        $this->assertSame('0.00', $job->fresh()->outstandingDecimal());
    }

    public function test_scientific_notation_in_a_service_job_payment_is_a_validation_error(): void
    {
        $store = $this->makeStore();
        $manager = $this->user($store);
        $job = ServiceJob::create([
            'store_id'         => $store->id,
            'job_number'       => 'JOB-' . Str::random(6),
            'customer_name'    => 'Ko Aung',
            'customer_phone'   => '09123456789',
            'device_type'      => 'phone',
            'reported_problem' => 'Screen cracked',
            'created_by'       => $manager->id,
            'status'           => 'received',
            'estimated_charge' => '100.00',
            'final_charge'     => '100.00',
        ]);

        $this->actingAs($manager)
            ->post(route('store.admin.service_jobs.payments.store', [
                'store_slug' => $store->slug,
                'job'        => $job->id,
            ]), ['method' => 'cash', 'amount' => '1e2'])
            ->assertSessionHasErrors('amount');
    }

    /* ------------------------------------------------------------------ */
    /*  Stock count                                                       */
    /* ------------------------------------------------------------------ */

    private function stockCountSession(Store $store, string $systemQty = '10.00'): array
    {
        $product = $this->product($store);

        // created_by is a foreign key to users. Using the STORE id here only
        // worked because SQLite does not enforce foreign keys.
        $actor = $store->users()->first() ?? $this->user($store);

        $session = StockCount::create([
            'store_id'       => $store->id,
            'session_number' => 'SC-' . Str::random(6),
            'status'         => 'in_progress',
            'created_by'     => $actor->id,
        ]);

        $line = StockCountLine::create([
            'stock_count_id'    => $session->id,
            'store_id'          => $store->id,
            'product_id'        => $product->id,
            'system_quantity'   => $systemQty,
            'unit_cost'         => '100.00',
            'is_counted'        => false,
        ]);

        return [$session, $line, $product];
    }

    public function test_bulk_count_rejects_a_negative_quantity(): void
    {
        $store = $this->makeStore();
        $manager = $this->user($store);
        [$session, $line] = $this->stockCountSession($store);

        $this->actingAs($manager)
            ->post(route('store.admin.stock_count.bulk_update', [
                'store_slug'  => $store->slug,
                'stock_count' => $session->id,
            ]), ['lines' => [['id' => $line->id, 'counted_quantity' => '-5']]])
            ->assertSessionHasErrors('lines.0.counted_quantity');

        $this->assertFalse((bool) $line->fresh()->is_counted);
    }

    public function test_bulk_count_rejects_a_non_numeric_quantity(): void
    {
        $store = $this->makeStore();
        $manager = $this->user($store);
        [$session, $line] = $this->stockCountSession($store);

        $this->actingAs($manager)
            ->post(route('store.admin.stock_count.bulk_update', [
                'store_slug'  => $store->slug,
                'stock_count' => $session->id,
            ]), ['lines' => [['id' => $line->id, 'counted_quantity' => 'abc']]])
            ->assertSessionHasErrors('lines.0.counted_quantity');

        $this->assertNull($line->fresh()->counted_quantity);
    }

    public function test_bulk_count_stores_the_variance_exactly(): void
    {
        $store = $this->makeStore();
        $manager = $this->user($store);
        [$session, $line] = $this->stockCountSession($store, '10.00');

        $this->actingAs($manager)
            ->post(route('store.admin.stock_count.bulk_update', [
                'store_slug'  => $store->slug,
                'stock_count' => $session->id,
            ]), ['lines' => [['id' => $line->id, 'counted_quantity' => '7.25']]])
            ->assertSessionHasNoErrors();

        $this->assertSame('7.250', (string) $line->fresh()->counted_quantity);
        $this->assertSame('-2.750', (string) $line->fresh()->variance_quantity);
    }

    public function test_approving_an_already_approved_session_posts_nothing_further(): void
    {
        $store = $this->makeStore();
        $manager = $this->user($store);
        $service = app(StockCountService::class);
        $inventory = app(InventoryService::class);

        [$session, $line, $product] = $this->stockCountSession($store, '10.00');

        $line->setCount('7.00');
        $service->approveAndReconcile($session, $manager);

        try {
            $service->approveAndReconcile($session->fresh(), $manager);
        } catch (InventoryException) {
            // expected — the second approval must not post the variance twice
        }

        $this->assertSame('-3.000', $inventory->totalOnHand($store->id, $product->id));
    }

    /* ------------------------------------------------------------------ */
    /*  E-Load                                                            */
    /* ------------------------------------------------------------------ */

    public function test_eload_cost_and_profit_are_exact_decimals(): void
    {
        $store = $this->makeStore();
        $service = app(EloadService::class);

        EloadAccount::create([
            'store_id'         => $store->id,
            'operator'         => 'mpt',
            'name'             => 'MPT Float',
            'balance'          => '100000.00',
            'discount_percent' => '1.50',
            'is_active'        => true,
        ]);

        $transaction = $service->createTransaction($store, [
            'operator'     => 'mpt',
            'phone_number' => '09123456789',
            'amount'       => '1000.00',
        ]);

        // cost = 1000 x (100 - 1.5) / 100 = 985.00 ; profit = 15.00
        $this->assertSame('985.00', (string) $transaction->cost);
        $this->assertSame('15.00', (string) $transaction->profit);

        $this->assertSame(
            '99000.00',
            (string) EloadAccount::where('store_id', $store->id)->firstOrFail()->balance
        );
    }

    public function test_eload_refill_keeps_exact_decimals(): void
    {
        $store = $this->makeStore();
        $service = app(EloadService::class);

        $account = EloadAccount::create([
            'store_id'  => $store->id,
            'operator'  => 'atom',
            'name'      => 'Atom Float',
            'balance'   => '0.00',
            'is_active' => true,
        ]);

        $service->refillAccount($account, '0.10');
        $service->refillAccount($account, '0.20');

        $this->assertSame('0.30', (string) $account->fresh()->balance);
    }
}
