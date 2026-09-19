<?php

namespace App\Services;

use App\Capabilities\Capability;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\SyncCheckpoint;
use App\Models\SyncOutboxRecord;
use App\Models\User;
use App\POS\Models\CashierShift;
use App\POS\Models\CustomerLedgerEntry;
use App\POS\Models\PosSale;
use App\POS\Services\CashierShiftService;
use App\POS\Services\CustomerDebtService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosSaleService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfflineSyncService
{
    /**
     * The register that offline-synced sales are booked against at the central.
     * One per business day of the sale (see ingestPosSale).
     */
    public const OFFLINE_REGISTER = 'Offline Sync Register';

    public function __construct(
        private readonly PosSaleService $posSaleService,
        private readonly CustomerDebtService $debtService,
        private readonly CashierShiftService $shiftService,
        private readonly SyncOutboxWriter $writer,
        private readonly InventoryService $inventory,
    ) {
    }

    /**
     * Enqueue an offline created record locally for future sync.
     *
     * Delegates to SyncOutboxWriter, which is also the writer the POS uses while
     * posting a sale (this class cannot be called from there: its constructor
     * needs PosSaleService).
     */
    public function enqueue(
        Store $store,
        string $recordType,
        string $clientTxId,
        array $payload,
        ?Carbon $offlineCreatedAt = null,
        ?string $deviceId = null,
        ?int $branchId = null
    ): SyncOutboxRecord {
        return $this->writer->enqueue(
            store: $store,
            recordType: $recordType,
            clientTxId: $clientTxId,
            payload: $payload,
            offlineCreatedAt: $offlineCreatedAt,
            deviceId: $deviceId,
            branchId: $branchId,
        );
    }

    /**
     * Get pending outbox queue for a store.
     */
    public function getPendingQueue(Store $store, int $limit = 50): Collection
    {
        return SyncOutboxRecord::query()
            ->where('store_id', $store->id)
            ->whereIn('status', ['pending', 'failed'])
            ->orderBy('created_offline_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Process batch of pushed offline records on the central/server side.
     * Guaranteed atomic per batch or idempotent per client_transaction_id.
     */
    public function processPushBatch(Store $store, array $records): array
    {
        $results = [];

        foreach ($records as $recordData) {
            $clientTxId = $recordData['client_transaction_id'] ?? null;
            $recordType = $recordData['record_type'] ?? 'pos_sale';
            $payload = $recordData['payload'] ?? [];
            $offlineCreatedAt = isset($recordData['created_offline_at']) ? Carbon::parse($recordData['created_offline_at']) : now();

            if (! $clientTxId) {
                $results[] = [
                    'client_transaction_id' => null,
                    'status'                => 'failed',
                    'error'                 => 'Missing client_transaction_id',
                ];
                continue;
            }

            try {
                $result = DB::transaction(function () use ($store, $recordType, $clientTxId, $payload, $offlineCreatedAt) {
                    return match ($recordType) {
                        'pos_sale'      => $this->ingestPosSale($store, $clientTxId, $payload, $offlineCreatedAt),
                        'customer_debt' => $this->ingestCustomerDebt($store, $clientTxId, $payload, $offlineCreatedAt),
                        default         => ['status' => 'skipped', 'message' => "Unknown record type: {$recordType}"],
                    };
                });

                // Update local outbox record status if exists
                SyncOutboxRecord::where('store_id', $store->id)
                    ->where('client_transaction_id', $clientTxId)
                    ->update([
                        'status'        => 'synced',
                        'synced_at'     => now(),
                        'error_message' => null,
                    ]);

                $results[] = array_merge([
                    'client_transaction_id' => $clientTxId,
                    'status'                => 'synced',
                ], $result);
            } catch (\Throwable $e) {
                Log::error("OfflineSync Error for store [{$store->id}] tx [{$clientTxId}]: " . $e->getMessage());

                SyncOutboxRecord::where('store_id', $store->id)
                    ->where('client_transaction_id', $clientTxId)
                    ->update([
                        'status'        => 'failed',
                        'error_message' => $e->getMessage(),
                        'retry_count'   => DB::raw('retry_count + 1'),
                    ]);

                $results[] = [
                    'client_transaction_id' => $clientTxId,
                    'status'                => 'failed',
                    'error'                 => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Ingest an offline POS Sale idempotently.
     */
    private function ingestPosSale(Store $store, string $clientTxId, array $payload, Carbon $offlineCreatedAt): array
    {
        // 1. Idempotency check: Has this client_transaction_id already been posted?
        $existingSale = PosSale::query()
            ->where('store_id', $store->id)
            ->where('client_transaction_id', $clientTxId)
            ->first();

        if ($existingSale) {
            return [
                'server_id'      => $existingSale->id,
                'receipt_number' => $existingSale->receipt_number,
                'idempotent'     => true,
            ];
        }

        // 2. Resolve actor/cashier
        $actorId = $payload['cashier_id'] ?? $payload['user_id'] ?? null;
        $actor = $actorId ? User::find($actorId) : $store->users()->first();

        if (! $actor) {
            throw new \RuntimeException('The store has no user to attribute this offline record to.');
        }

        // 3. Resolve the shift this sale belongs to.
        //
        // A terminal's shift id is meaningless here (different database), and an
        // offline sale made three days ago must NOT be booked into whatever
        // shift happens to be open at the central right now — that silently
        // mixes days together in shift and cash-up reports. Offline sales get
        // their own register, one per business day of the sale itself.
        $shift = null;

        if ($store->hasCapability(Capability::OPERATIONS_CASHIER_SHIFTS)) {
            $shift = CashierShift::query()
                ->where('store_id', $store->id)
                ->where('register_name', self::OFFLINE_REGISTER)
                ->whereDate('opened_at', $offlineCreatedAt->toDateString())
                ->first();

            if (! $shift) {
                $shift = CashierShift::create([
                    'store_id'      => $store->id,
                    'cashier_id'    => $actor->id,
                    'register_name' => self::OFFLINE_REGISTER,
                    'opened_at'     => $offlineCreatedAt,
                    'opening_cash'  => '0.00',
                    'status'        => 'open',
                ]);
            }
        }

        // 4. Post the sale via PosSaleService, carrying the counter's own money.
        $lines = $payload['lines'] ?? [];
        $normalizedLines = array_map(function ($line) {
            return [
                'product_id'         => (int) $line['product_id'],
                'product_variant_id' => $line['product_variant_id'] ?? null,
                'quantity'           => (string) ($line['quantity'] ?? '1'),
                'unit_price'         => isset($line['unit_price']) ? (string) $line['unit_price'] : null,
            ];
        }, $lines);

        $payments = $payload['payments'] ?? [];
        $customerId = $payload['customer_id'] ?? null;

        // The discount that was actually given at the counter (manual + coupon +
        // redeemed points). Without it the central would price the bill with its
        // own session discount — usually none — and the two sets of books would
        // disagree on every discounted sale.
        $discount = (string) ($payload['discount'] ?? '0.00');

        $sale = $this->posSaleService->post(
            store: $store,
            lines: $normalizedLines,
            payments: $payments,
            actor: $actor,
            shift: $shift,
            customerId: $customerId,
            explicitDiscount: $discount,
        );

        // Stamp client_transaction_id and offline created date
        $sale->update([
            'client_transaction_id' => $clientTxId,
            'posted_at'             => $offlineCreatedAt,
        ]);

        // Money check (replication only re-derives what the counter already
        // decided): report a divergence instead of quietly calling it "synced".
        $warning = null;
        $expectedTotal = isset($payload['expected_total']) ? (string) $payload['expected_total'] : null;

        if ($expectedTotal !== null && bccomp($expectedTotal, (string) $sale->total, 2) !== 0) {
            $warning = "total mismatch: terminal {$expectedTotal} vs central {$sale->total}";
            Log::warning("Offline sync total mismatch for store [{$store->id}] tx [{$clientTxId}]: " . $warning);
        }

        return [
            'server_id'      => $sale->id,
            'receipt_number' => $sale->receipt_number,
            'idempotent'     => false,
            'warning'        => $warning,
        ];
    }

    /**
     * Ingest an offline customer debt collection idempotently.
     */
    private function ingestCustomerDebt(Store $store, string $clientTxId, array $payload, Carbon $offlineCreatedAt): array
    {
        $existingEntry = CustomerLedgerEntry::query()
            ->where('store_id', $store->id)
            ->where('client_transaction_id', $clientTxId)
            ->first();

        if ($existingEntry) {
            return [
                'server_id'  => $existingEntry->id,
                'idempotent' => true,
            ];
        }

        $customerId = (int) ($payload['customer_id'] ?? 0);
        $amount = (string) ($payload['amount'] ?? '0');
        $notes = $payload['notes'] ?? 'Offline Debt Collection';
        $actorId = $payload['user_id'] ?? null;
        $actor = $actorId ? User::find($actorId) : $store->users()->first();

        $entry = $this->debtService->collect(
            store: $store,
            customerId: $customerId,
            amount: $amount,
            actor: $actor,
            notes: $notes,
            clientTransactionId: $clientTxId
        );

        $entry->update(['occurred_at' => $offlineCreatedAt]);

        return [
            'server_id'  => $entry->id,
            'idempotent' => false,
        ];
    }

    /**
     * Get delta updates (products, categories, customers) modified since a given timestamp.
     */
    public function getPullDelta(Store $store, ?Carbon $since = null): array
    {
        $since = $since ?? Carbon::createFromTimestamp(0);

        $products = Product::query()
            ->where('store_id', $store->id)
            ->where('updated_at', '>=', $since)
            // products has no cost_price/is_active column — selecting them threw on
            // MySQL and silently degraded on SQLite (quoted unknown identifier =
            // string literal), so the pull delta never returned a real cost.
            // purchase_cost is the real column, and a product only exists here while
            // it is sellable, so is_active is reported as 1 for every row.
            ->select([
                'id', 'store_id', 'category_id', 'brand_id', 'name', 'sku', 'barcode',
                'retail_price', 'wholesale_price', 'purchase_cost', 'updated_at',
            ])
            ->selectRaw('CAST(purchase_cost AS DECIMAL(12,2)) as cost_price')
            ->selectRaw('1 as is_active')
            ->get();

        $categories = Category::query()
            ->where('store_id', $store->id)
            ->where('updated_at', '>=', $since)
            ->select(['id', 'store_id', 'parent_id', 'name', 'slug', 'updated_at'])
            ->get();

        // Customers are the store's retail/wholesale members. The pivot role
        // vocabulary is retail_customer / wholesale_customer; an earlier version
        // filtered on 'customer' — a role nothing ever writes — so the delta
        // returned an empty customer list on every pull, forever.
        $customers = $store->users()
            ->wherePivotIn('role', ['retail_customer', 'wholesale_customer'])
            ->where('users.updated_at', '>=', $since)
            ->select(['users.id', 'users.name', 'users.phone', 'users.email', 'users.updated_at'])
            ->get();

        // Stock, so the receiving side can price AND refuse to oversell. Without
        // it an offline terminal sells whatever it likes and the two ledgers
        // disagree the moment the connection returns.
        $productIds = $products->pluck('id')->all();
        $warehouseId = $this->inventory->defaultWarehouseId($store->id);

        $balances = $productIds === [] ? collect() : DB::table('inventory_balances')
            ->where('store_id', $store->id)
            // The warehouse the POS actually sells from, so the number here is
            // the same number the counter is capped by.
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, product_variant_id, SUM(quantity_on_hand) as quantity_on_hand')
            ->groupBy('product_id', 'product_variant_id')
            ->get();

        $productBalances = [];
        $variantBalances = [];

        foreach ($balances as $row) {
            // Normalised to 3 decimals: SQLite hands back SUM() as '6' while
            // MySQL says '6.000', and a replication payload must not change
            // shape depending on which engine the shop happens to run.
            $qty = bcadd((string) $row->quantity_on_hand, '0', 3);
            $productId = (int) $row->product_id;

            // A variant product keeps its stock on variant rows, so the product's
            // own figure is the sum of everything it holds.
            $productBalances[$productId] = bcadd($productBalances[$productId] ?? '0', $qty, 3);

            if ((int) $row->product_variant_id !== 0) {
                $variantBalances[(int) $row->product_variant_id] = $qty;
            }
        }

        $variants = $productIds === [] ? collect() : ProductVariant::query()
            ->whereIn('product_id', $productIds)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ProductVariant $variant) => [
                'id'               => $variant->id,
                'product_id'       => (int) $variant->product_id,
                'name'             => $variant->name,
                'sku'              => $variant->sku,
                'retail_price'     => (string) $variant->retail_price,
                'wholesale_price'  => $variant->wholesale_price !== null ? (string) $variant->wholesale_price : null,
                'quantity_on_hand' => $variantBalances[$variant->id] ?? '0.000',
            ])
            ->values();

        $products = $products->map(function (Product $product) use ($productBalances) {
            $product->setAttribute('quantity_on_hand', $productBalances[$product->id] ?? '0.000');

            return $product;
        });

        return [
            'server_time' => now()->toIso8601String(),
            'since'       => $since->toIso8601String(),
            'warehouse_id' => $warehouseId,
            'products'    => $products,
            'variants'    => $variants,
            'categories'  => $categories,
            'customers'   => $customers,
        ];
    }

    /**
     * Update checkpoint for a store entity type.
     */
    public function updateCheckpoint(Store $store, string $entityType, Carbon $syncedAt): void
    {
        SyncCheckpoint::updateOrCreate(
            [
                'store_id'    => $store->id,
                'entity_type' => $entityType,
            ],
            [
                'last_synced_at' => $syncedAt,
            ]
        );
    }

    /**
     * Get overall sync health stats for store.
     */
    public function getSyncHealth(Store $store): array
    {
        $pendingCount = SyncOutboxRecord::where('store_id', $store->id)->where('status', 'pending')->count();
        $failedCount = SyncOutboxRecord::where('store_id', $store->id)->where('status', 'failed')->count();
        $syncedCount = SyncOutboxRecord::where('store_id', $store->id)->where('status', 'synced')->count();
        $lastSynced = SyncOutboxRecord::where('store_id', $store->id)->where('status', 'synced')->max('synced_at');

        return [
            'pending_count'  => $pendingCount,
            'failed_count'   => $failedCount,
            'synced_count'   => $syncedCount,
            'last_synced_at' => $lastSynced ? Carbon::parse($lastSynced)->toIso8601String() : null,
            'is_healthy'     => $failedCount === 0,
        ];
    }
}
