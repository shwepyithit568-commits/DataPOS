<?php

namespace Tests\Feature\OfflineSync;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\SyncOutboxRecord;
use App\Models\User;
use App\POS\Models\CashierShift;
use App\POS\Models\CustomerLedgerEntry;
use App\POS\Models\PosSale;
use App\POS\Services\InventoryService;
use App\Services\OfflineSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineSyncEngineTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $owner;
    private User $customer;
    private Product $product;
    private CashierShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name'          => 'Yangon Tech Store',
            'slug'          => 'yangon-tech',
            'business_type' => 'mobile_accessories',
            'currency'      => 'MMK',
            'is_active'     => true,
        ]);

        $this->owner = User::factory()->create(['role' => 'store_manager']);
        $this->owner->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->customer->stores()->attach($this->store->id, ['role' => 'customer']);

        $category = Category::create([
            'store_id' => $this->store->id,
            'name'     => 'Accessories',
            'slug'     => 'accessories',
        ]);

        $this->product = Product::create([
            'store_id'        => $this->store->id,
            'category_id'     => $category->id,
            'name'            => 'USB-C Fast Cable',
            'slug'            => 'usb-c-fast-cable',
            'sku'             => 'CABLE-001',
            'retail_price'    => '5000.00',
            'wholesale_price' => '4000.00',
            'cost_price'      => '2500.00',
            'is_active'       => true,
        ]);

        // Seed initial stock
        $invService = app(InventoryService::class);
        $invService->postMovement([
            'store_id'       => $this->store->id,
            'product_id'     => $this->product->id,
            'movement_type'  => 'adjustment_in',
            'quantity_delta' => 50,
            'unit_cost'      => '2500.00',
            'created_by'     => $this->owner->id,
        ]);

        $this->shift = CashierShift::create([
            'store_id'      => $this->store->id,
            'cashier_id'    => $this->owner->id,
            'register_name' => 'Main Register',
            'opened_at'     => now(),
            'opening_cash'  => '100000.00',
            'status'        => 'open',
        ]);
    }

    public function test_offline_sale_enqueues_into_sync_outbox_records(): void
    {
        $syncService = app(OfflineSyncService::class);
        $clientTxId = 'TX-OFFLINE-' . Str::random(12);

        $record = $syncService->enqueue(
            store: $this->store,
            recordType: 'pos_sale',
            clientTxId: $clientTxId,
            payload: [
                'cashier_id' => $this->owner->id,
                'lines'      => [
                    [
                        'product_id' => $this->product->id,
                        'quantity'   => '2',
                        'unit_price' => '5000.00',
                    ]
                ],
                'payments'   => [
                    [
                        'method' => 'cash',
                        'amount' => '10000.00',
                    ]
                ]
            ]
        );

        $this->assertInstanceOf(SyncOutboxRecord::class, $record);
        $this->assertEquals('pending', $record->status);
        $this->assertEquals($clientTxId, $record->client_transaction_id);
        $this->assertDatabaseHas('sync_outbox_records', [
            'store_id'              => $this->store->id,
            'client_transaction_id' => $clientTxId,
            'status'                => 'pending',
        ]);
    }

    public function test_push_sync_atomically_ingests_sale_and_marks_synced(): void
    {
        $clientTxId = 'TX-SALE-' . Str::random(12);

        $payload = [
            'cashier_id'       => $this->owner->id,
            'cashier_shift_id' => $this->shift->id,
            'lines'            => [
                [
                    'product_id' => $this->product->id,
                    'quantity'   => '3',
                    'unit_price' => '5000.00',
                ]
            ],
            'payments'         => [
                [
                    'method' => 'cash',
                    'amount' => '15000.00',
                ]
            ]
        ];

        // Enqueue locally first
        app(OfflineSyncService::class)->enqueue(
            store: $this->store,
            recordType: 'pos_sale',
            clientTxId: $clientTxId,
            payload: $payload
        );

        // Push via API
        $response = $this->actingAs($this->owner)->postJson("/api/v1/store/{$this->store->slug}/sync/push", [
            'records' => [
                [
                    'client_transaction_id' => $clientTxId,
                    'record_type'           => 'pos_sale',
                    'payload'               => $payload,
                    'created_offline_at'    => now()->subMinutes(10)->toIso8601String(),
                ]
            ]
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('results.0.status', 'synced');

        // Verify sale posted in DB
        $this->assertDatabaseHas('pos_sales', [
            'store_id'              => $this->store->id,
            'client_transaction_id' => $clientTxId,
        ]);

        // Verify outbox record updated to synced
        $this->assertDatabaseHas('sync_outbox_records', [
            'store_id'              => $this->store->id,
            'client_transaction_id' => $clientTxId,
            'status'                => 'synced',
        ]);

        // Verify inventory decreased by 3 (50 - 3 = 47)
        $invService = app(InventoryService::class);
        $balance = $invService->totalOnHand($this->store->id, $this->product->id);
        $this->assertEquals('47.000', $balance);
    }

    public function test_duplicate_push_sync_is_idempotent(): void
    {
        $clientTxId = 'TX-IDEMPOTENT-' . Str::random(12);

        $payload = [
            'cashier_id'       => $this->owner->id,
            'cashier_shift_id' => $this->shift->id,
            'lines'            => [
                [
                    'product_id' => $this->product->id,
                    'quantity'   => '1',
                    'unit_price' => '5000.00',
                ]
            ],
            'payments'         => [
                [
                    'method' => 'cash',
                    'amount' => '5000.00',
                ]
            ]
        ];

        // 1st Push
        $firstResponse = $this->actingAs($this->owner)->postJson("/api/v1/store/{$this->store->slug}/sync/push", [
            'records' => [
                [
                    'client_transaction_id' => $clientTxId,
                    'record_type'           => 'pos_sale',
                    'payload'               => $payload,
                ]
            ]
        ]);
        $firstResponse->assertOk()->assertJsonPath('results.0.status', 'synced');

        $initialSaleCount = PosSale::where('store_id', $this->store->id)->count();

        // 2nd Duplicate Push
        $secondResponse = $this->actingAs($this->owner)->postJson("/api/v1/store/{$this->store->slug}/sync/push", [
            'records' => [
                [
                    'client_transaction_id' => $clientTxId,
                    'record_type'           => 'pos_sale',
                    'payload'               => $payload,
                ]
            ]
        ]);
        $secondResponse->assertOk()
            ->assertJsonPath('results.0.status', 'synced')
            ->assertJsonPath('results.0.idempotent', true);

        // Verify count didn't increase
        $this->assertEquals($initialSaleCount, PosSale::where('store_id', $this->store->id)->count());
    }

    public function test_offline_debt_collection_sync_updates_customer_ledger(): void
    {
        // 1. Create opening debt receivable
        $debtService = app(\App\POS\Services\CustomerDebtService::class);
        $debtService->recordSaleDebt(
            store: $this->store,
            customerId: $this->customer->id,
            saleId: 999,
            amount: '20000.00',
            actor: $this->owner,
            clientTransactionId: 'TX-INIT-DEBT-' . Str::random(8)
        );

        $this->assertEquals('20000.00', $debtService->balanceFor($this->store->id, $this->customer->id));

        // 2. Sync an offline debt collection of 5,000
        $clientTxId = 'TX-DEBT-COLLECT-' . Str::random(12);
        $response = $this->actingAs($this->owner)->postJson("/api/v1/store/{$this->store->slug}/sync/push", [
            'records' => [
                [
                    'client_transaction_id' => $clientTxId,
                    'record_type'           => 'customer_debt',
                    'payload'               => [
                        'customer_id' => $this->customer->id,
                        'amount'      => '5000.00',
                        'notes'       => 'Collected offline',
                        'user_id'     => $this->owner->id,
                    ],
                ]
            ]
        ]);

        $response->assertOk()->assertJsonPath('results.0.status', 'synced');

        // Verify remaining balance is 15,000 (20,000 - 5,000)
        $this->assertEquals('15000.00', $debtService->balanceFor($this->store->id, $this->customer->id));
    }

    public function test_pull_delta_returns_updated_products_and_categories(): void
    {
        $response = $this->actingAs($this->owner)->getJson("/api/v1/store/{$this->store->slug}/sync/pull");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'delta' => [
                    'products',
                    'categories',
                    'customers',
                ]
            ]);

        $this->assertNotEmpty($response->json('delta.products'));
    }

    public function test_sync_status_endpoint_reports_live_health(): void
    {
        $response = $this->actingAs($this->owner)->getJson("/api/v1/store/{$this->store->slug}/sync/status");

        $response->assertOk()
            ->assertJsonPath('online', true)
            ->assertJsonPath('health.is_healthy', true);
    }

    public function test_admin_can_view_sync_manager_and_retry_all(): void
    {
        // Create a pending record
        app(OfflineSyncService::class)->enqueue(
            store: $this->store,
            recordType: 'pos_sale',
            clientTxId: 'TX-PENDING-' . Str::random(8),
            payload: [
                'cashier_id' => $this->owner->id,
                'lines'      => [['product_id' => $this->product->id, 'quantity' => '1', 'unit_price' => '5000.00']],
                'payments'   => [['method' => 'cash', 'amount' => '5000.00']],
            ]
        );

        // View index
        $indexResponse = $this->actingAs($this->owner)->get(route('store.admin.sync.index', ['store_slug' => $this->store->slug]));
        $indexResponse->assertOk()
            ->assertSeeText(__('messages.sync_manager'));

        // Retry all
        $retryResponse = $this->actingAs($this->owner)->post(route('store.admin.sync.retry_all', ['store_slug' => $this->store->slug]));
        $retryResponse->assertRedirect();
    }
}
