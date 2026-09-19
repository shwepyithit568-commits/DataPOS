<?php

namespace Tests\Feature\OfflineSync;

use App\Capabilities\Capability;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\SyncOutboxRecord;
use App\Models\User;
use App\POS\Models\CashierShift;
use App\POS\Models\PosSale;
use App\POS\Services\InventoryService;
use App\POS\Services\PosSaleService;
use App\Services\OfflineSyncService;
use App\Services\SyncClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The terminal side of shop ⇄ cloud replication.
 *
 * OfflineSyncEngineTest covers the CENTRAL's ingest endpoints. This file covers
 * the other half: capturing sales locally while the shop has no internet, and
 * pushing them out later without losing or duplicating money.
 */
class OfflineSyncClientTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $owner;

    private User $customer;

    private Product $product;

    private CashierShift $shift;

    private string $syncKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name'          => 'Mandalay Mobile',
            'slug'          => 'mandalay-mobile',
            'business_type' => 'mobile_accessories',
            'currency'      => 'MMK',
            'is_active'     => true,
        ]);

        $this->syncKey = $this->store->generateSyncApiKey();

        $this->owner = User::factory()->create(['role' => 'store_manager']);
        $this->owner->stores()->attach($this->store->id, ['role' => 'store_manager']);

        // The role the app actually writes for a member of a store.
        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->customer->stores()->attach($this->store->id, ['role' => 'retail_customer']);

        $category = Category::create([
            'store_id' => $this->store->id,
            'name'     => 'Chargers',
            'slug'     => 'chargers',
        ]);

        $this->product = Product::create([
            'store_id'        => $this->store->id,
            'category_id'     => $category->id,
            'name'            => '33W GaN Charger',
            'slug'            => '33w-gan-charger',
            'sku'             => 'CHG-33W',
            'retail_price'    => '5000.00',
            'wholesale_price' => '4200.00',
            'is_active'       => true,
        ]);

        $inventory = app(InventoryService::class);
        $inventory->postMovement([
            'store_id'       => $this->store->id,
            'product_id'     => $this->product->id,
            'movement_type'  => 'adjustment_in',
            'quantity_delta' => 40,
            'unit_cost'      => '3000.00',
            'created_by'     => $this->owner->id,
        ]);

        $this->shift = CashierShift::create([
            'store_id'      => $this->store->id,
            'cashier_id'    => $this->owner->id,
            'register_name' => 'Counter 1',
            'opened_at'     => now(),
            'opening_cash'  => '100000.00',
            'status'        => 'open',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Capture                                                            */
    /* ------------------------------------------------------------------ */

    public function test_a_standalone_shop_queues_nothing(): void
    {
        $this->postSale();

        // Default configuration: this installation IS the shop, so there is
        // nothing to replicate and no queue row to grow stale.
        $this->assertDatabaseCount('sync_outbox_records', 0);
    }

    public function test_a_terminal_queues_every_posted_sale_with_the_money_it_took(): void
    {
        $this->enableTerminal();

        $sale = $this->postSale(quantity: '2', unitPrice: '5000.00', discount: '1000.00');

        $record = SyncOutboxRecord::where('store_id', $this->store->id)->sole();

        $this->assertSame('pos_sale', $record->record_type);
        $this->assertSame('pending', $record->status);
        $this->assertSame($sale->client_transaction_id, $record->client_transaction_id);

        // The prices charged at the counter, and the discount that was actually
        // given — the central must never re-price the bill from its own catalog.
        $this->assertSame('1000.00', (string) $record->payload['discount']);
        $this->assertSame('9000.00', (string) $record->payload['expected_total']);
        $this->assertSame('5000.00', (string) $record->payload['lines'][0]['unit_price']);
        $this->assertSame('2.000', (string) $record->payload['lines'][0]['quantity']);
        $this->assertSame('9000.00', (string) $record->payload['payments'][0]['amount']);
    }

    public function test_a_failed_sale_leaves_no_orphan_queue_row(): void
    {
        $this->enableTerminal();

        // Ask for more than the ledger holds: the whole sale must roll back,
        // and the outbox row goes with it (it is written in the same
        // transaction, so a queued phantom is impossible).
        try {
            $this->postSale(quantity: '9999');
        } catch (\Throwable) {
            // expected — insufficient stock
        }

        $this->assertDatabaseCount('sync_outbox_records', 0);
    }

    /* ------------------------------------------------------------------ */
    /*  Push                                                               */
    /* ------------------------------------------------------------------ */

    public function test_push_sends_the_queue_and_marks_records_synced(): void
    {
        $this->enableTerminal();
        $sale = $this->postSale();

        Http::fake([
            '*central.test*' => Http::response([
                'success' => true,
                'results' => [[
                    'client_transaction_id' => $sale->client_transaction_id,
                    'status'                => 'synced',
                ]],
            ]),
        ]);

        $result = app(SyncClientService::class)->push($this->store);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['pushed']);
        $this->assertSame(1, $result['synced']);
        $this->assertSame(0, $result['failed']);

        $this->assertDatabaseHas('sync_outbox_records', [
            'client_transaction_id' => $sale->client_transaction_id,
            'status'                => 'synced',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://central.test/api/v1/store/mandalay-mobile/sync/push'
                && $request->hasHeader('X-Sync-Key', 'test-sync-key')
                && count($request->data()['records']) === 1;
        });
    }

    public function test_push_keeps_everything_queued_while_the_shop_is_offline(): void
    {
        $this->enableTerminal();
        $sale = $this->postSale();

        Http::fake(function () {
            throw new ConnectionException('cURL error 7: Failed to connect');
        });

        $result = app(SyncClientService::class)->push($this->store);

        $this->assertFalse($result['ok']);
        $this->assertSame('offline', $result['message']);

        // Untouched, and not counted as a failed attempt — the shop was simply
        // offline, which is a normal state, not an error to burn retries on.
        $record = SyncOutboxRecord::where('client_transaction_id', $sale->client_transaction_id)->sole();
        $this->assertSame('pending', $record->status);
        $this->assertSame(0, $record->retry_count);
    }

    public function test_push_leaves_the_queue_alone_when_the_key_is_rejected(): void
    {
        $this->enableTerminal();
        $sale = $this->postSale();

        Http::fake(['*central.test*' => Http::response(['success' => false], 401)]);

        $result = app(SyncClientService::class)->push($this->store);

        $this->assertSame('unauthorized', $result['message']);
        $this->assertDatabaseHas('sync_outbox_records', [
            'client_transaction_id' => $sale->client_transaction_id,
            'status'                => 'pending',
        ]);
    }

    public function test_push_records_why_the_central_refused_a_record(): void
    {
        $this->enableTerminal();
        $sale = $this->postSale();

        Http::fake([
            '*central.test*' => Http::response([
                'success' => true,
                'results' => [[
                    'client_transaction_id' => $sale->client_transaction_id,
                    'status'                => 'failed',
                    'error'                 => 'Insufficient stock for 33W GaN Charger',
                ]],
            ]),
        ]);

        $result = app(SyncClientService::class)->push($this->store);

        $this->assertSame(1, $result['failed']);

        $record = SyncOutboxRecord::where('client_transaction_id', $sale->client_transaction_id)->sole();
        $this->assertSame('failed', $record->status);
        $this->assertSame(1, $record->retry_count);
        $this->assertStringContainsString('Insufficient stock', (string) $record->error_message);
    }

    public function test_push_surfaces_a_total_mismatch_instead_of_hiding_it(): void
    {
        $this->enableTerminal();
        $sale = $this->postSale();

        Http::fake([
            '*central.test*' => Http::response([
                'success' => true,
                'results' => [[
                    'client_transaction_id' => $sale->client_transaction_id,
                    'status'                => 'synced',
                    'warning'               => 'total mismatch: terminal 9000.00 vs central 9500.00',
                ]],
            ]),
        ]);

        app(SyncClientService::class)->push($this->store);

        $record = SyncOutboxRecord::where('client_transaction_id', $sale->client_transaction_id)->sole();
        $this->assertSame('synced', $record->status);
        $this->assertStringStartsWith('warning:', (string) $record->error_message);
    }

    public function test_push_refuses_to_run_with_incomplete_configuration(): void
    {
        config([
            'sync.enabled' => true,
            'sync.role'    => 'terminal',
            'sync.central_url' => '',
            'sync.store_slug'  => '',
            'sync.api_key'     => '',
        ]);

        $sale = $this->postSale();

        Http::fake();

        $result = app(SyncClientService::class)->push($this->store);

        $this->assertSame('not_configured', $result['message']);
        $this->assertContains('DATAPOS_SYNC_CENTRAL_URL', $result['problems']);
        Http::assertNothingSent();

        // The queue still holds the sale: nothing was dropped on the floor.
        $this->assertNotNull($sale->client_transaction_id);
        $this->assertDatabaseHas('sync_outbox_records', [
            'client_transaction_id' => $sale->client_transaction_id,
            'status'                => 'pending',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Pull                                                               */
    /* ------------------------------------------------------------------ */

    public function test_pull_delta_reports_customers_with_the_roles_the_app_writes(): void
    {
        $delta = app(OfflineSyncService::class)->getPullDelta($this->store);

        // Regression: the delta filtered on the pivot role 'customer', which
        // nothing in the application ever writes (it writes retail_customer /
        // wholesale_customer), so this list was silently always empty.
        $this->assertContains(
            $this->customer->id,
            collect($delta['customers'])->pluck('id')->all()
        );
    }

    public function test_pull_delta_carries_stock_so_a_terminal_cannot_oversell(): void
    {
        $inventory = app(InventoryService::class);

        $variant = $this->product->variants()->create([
            'name'            => 'Black',
            'sku'             => 'CHG-33W-BLK',
            'retail_price'    => '5300.00',
            'wholesale_price' => '4500.00',
            'is_default'      => true,
        ]);

        $inventory->postMovement([
            'store_id'           => $this->store->id,
            'product_id'         => $this->product->id,
            'product_variant_id' => $variant->id,
            'movement_type'      => 'adjustment_in',
            'quantity_delta'     => 6,
            'unit_cost'          => '3100.00',
            'created_by'         => $this->owner->id,
        ]);

        $delta = app(OfflineSyncService::class)->getPullDelta($this->store);

        $productRow = collect($delta['products'])->firstWhere('id', $this->product->id);
        $variantRow = collect($delta['variants'])->firstWhere('id', $variant->id);

        $this->assertNotNull($productRow, 'the product must be in the delta');
        $this->assertNotNull($variantRow, 'variants must be in the delta');

        // The product-level figure is the sum of everything the product holds
        // (its own row plus its variant rows).
        $this->assertSame('46.000', (string) $productRow->quantity_on_hand);
        $this->assertSame('6.000', (string) $variantRow['quantity_on_hand']);
        $this->assertSame('5300.00', (string) $variantRow['retail_price']);
    }

    /* ------------------------------------------------------------------ */
    /*  Central-side ingest fidelity                                       */
    /* ------------------------------------------------------------------ */

    public function test_ingest_keeps_the_counter_discount_so_both_books_agree(): void
    {
        $this->withHeaders(['X-Sync-Key' => $this->syncKey]);

        $response = $this->postJson("/api/v1/store/{$this->store->slug}/sync/push", [
            'records' => [[
                'client_transaction_id' => 'sale-discounted-1',
                'record_type'           => 'pos_sale',
                'payload'               => [
                    'cashier_id'  => $this->owner->id,
                    'lines'       => [[
                        'product_id' => $this->product->id,
                        'quantity'   => '2',
                        'unit_price' => '5000.00',
                    ]],
                    'payments'       => [['method' => 'cash', 'amount' => '9000.00']],
                    'discount'       => '1000.00',
                    'expected_total' => '9000.00',
                ],
            ]],
        ]);

        $response->assertOk()->assertJsonPath('results.0.status', 'synced');

        $sale = PosSale::where('client_transaction_id', 'sale-discounted-1')->firstOrFail();

        // Before the discount was carried, the central re-priced this bill with
        // its own (usually empty) session discount and booked 10,000 instead of
        // the 9,000 the cashier actually took.
        $this->assertSame('1000.00', (string) $sale->discount);
        $this->assertSame('9000.00', (string) $sale->total);
        $this->assertNull($response->json('results.0.warning'));
    }

    public function test_ingest_flags_a_total_that_does_not_tie_out(): void
    {
        $this->withHeaders(['X-Sync-Key' => $this->syncKey]);

        $response = $this->postJson("/api/v1/store/{$this->store->slug}/sync/push", [
            'records' => [[
                'client_transaction_id' => 'sale-mismatch-1',
                'record_type'           => 'pos_sale',
                'payload'               => [
                    'cashier_id'  => $this->owner->id,
                    'lines'       => [[
                        'product_id' => $this->product->id,
                        'quantity'   => '1',
                        'unit_price' => '5000.00',
                    ]],
                    'payments'       => [['method' => 'cash', 'amount' => '5000.00']],
                    // The counter claims a different total than the central derives.
                    'expected_total' => '4500.00',
                ],
            ]],
        ]);

        $response->assertOk()->assertJsonPath('results.0.status', 'synced');

        $this->assertStringContainsString('total mismatch', (string) $response->json('results.0.warning'));
    }

    public function test_a_sale_made_offline_days_ago_is_not_booked_into_todays_shift(): void
    {
        $this->store->forceFill([
            'capabilities_override' => [Capability::OPERATIONS_CASHIER_SHIFTS => true],
        ])->save();
        $this->store->refresh();

        // A shift is open at the central right now — the old code attached every
        // pushed sale to it, mixing three days of takings into today's cash-up.
        $todayShift = CashierShift::create([
            'store_id'      => $this->store->id,
            'cashier_id'    => $this->owner->id,
            'register_name' => 'Central Counter',
            'opened_at'     => now(),
            'opening_cash'  => '0.00',
            'status'        => 'open',
        ]);

        $this->withHeaders(['X-Sync-Key' => $this->syncKey]);

        $offlineAt = now()->subDays(3)->setTime(14, 30);

        $response = $this->postJson("/api/v1/store/{$this->store->slug}/sync/push", [
            'records' => [[
                'client_transaction_id' => 'sale-backdated-1',
                'record_type'           => 'pos_sale',
                'payload'               => [
                    'cashier_id'  => $this->owner->id,
                    'lines'       => [[
                        'product_id' => $this->product->id,
                        'quantity'   => '1',
                        'unit_price' => '5000.00',
                    ]],
                    'payments' => [['method' => 'cash', 'amount' => '5000.00']],
                ],
                'created_offline_at' => $offlineAt->toIso8601String(),
            ]],
        ]);

        $response->assertOk()->assertJsonPath('results.0.status', 'synced');

        $sale = PosSale::where('client_transaction_id', 'sale-backdated-1')->firstOrFail();

        $this->assertNotSame($todayShift->id, $sale->cashier_shift_id);

        $offlineShift = CashierShift::findOrFail($sale->cashier_shift_id);
        $this->assertSame(OfflineSyncService::OFFLINE_REGISTER, $offlineShift->register_name);
        $this->assertSame(
            $offlineAt->toDateString(),
            $offlineShift->opened_at->toDateString()
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Commands                                                           */
    /* ------------------------------------------------------------------ */

    public function test_sync_status_command_describes_the_replication_setup(): void
    {
        // A half-configured terminal must name the missing key rather than
        // leaving the owner wondering why nothing ever leaves the shop.
        config(['sync.enabled' => true, 'sync.role' => 'terminal']);

        $this->artisan('sync:status')
            ->expectsOutputToContain('DATAPOS_SYNC_CENTRAL_URL')
            ->assertSuccessful();
    }

    public function test_sync_push_command_drains_the_queue(): void
    {
        $this->enableTerminal();
        $sale = $this->postSale();

        Http::fake([
            '*central.test*' => Http::response([
                'success' => true,
                'results' => [[
                    'client_transaction_id' => $sale->client_transaction_id,
                    'status'                => 'synced',
                ]],
            ]),
        ]);

        $this->artisan('sync:push')->assertSuccessful();

        $this->assertDatabaseHas('sync_outbox_records', [
            'client_transaction_id' => $sale->client_transaction_id,
            'status'                => 'synced',
        ]);
    }

    public function test_sync_push_command_is_successful_while_offline(): void
    {
        $this->enableTerminal();
        $this->postSale();

        Http::fake(function () {
            throw new ConnectionException('cURL error 7: Failed to connect');
        });

        // A shop with no internet is not a failing shop: cron must not page
        // anyone, and the queue survives.
        $this->artisan('sync:push')->assertSuccessful();

        $this->assertSame(1, SyncOutboxRecord::where('status', 'pending')->count());
    }

    public function test_the_sync_screens_test_connection_endpoint_answers_for_a_manager(): void
    {
        // The Sync screen's "Test connection" button fetches this route. A wrong
        // route name or permission would leave the owner clicking into nothing.
        config(['sync.enabled' => true, 'sync.role' => 'terminal']);

        $this->actingAs($this->owner)
            ->getJson(route('store.admin.sync.test', ['store_slug' => $this->store->slug]))
            ->assertOk()
            ->assertJsonPath('success', true)
            // Config is incomplete in this test, so it must say exactly that
            // rather than pretending the central answered.
            ->assertJsonPath('message', 'not_configured');
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

    private function enableTerminal(): void
    {
        config([
            'sync.enabled'     => true,
            'sync.role'        => 'terminal',
            'sync.central_url' => 'https://central.test',
            'sync.store_slug'  => $this->store->slug,
            'sync.api_key'     => 'test-sync-key',
        ]);
    }

    private function postSale(
        string $quantity = '1',
        string $unitPrice = '5000.00',
        string $discount = '0.00'
    ): PosSale {
        $subtotal = bcmul($unitPrice, $quantity, 2);

        return app(PosSaleService::class)->post(
            store: $this->store,
            lines: [[
                'product_id'         => $this->product->id,
                'product_variant_id' => null,
                'quantity'           => $quantity,
                'unit_price'         => $unitPrice,
            ]],
            payments: [[
                'method' => 'cash',
                'amount' => bcsub($subtotal, $discount, 2),
            ]],
            actor: $this->owner,
            shift: $this->shift,
            explicitDiscount: $discount,
        );
    }
}
