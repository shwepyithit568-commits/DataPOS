<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\SyncCheckpoint;
use App\Models\SyncOutboxRecord;
use App\Models\User;
use App\POS\Models\CashierShift;
use App\POS\Models\CustomerLedgerEntry;
use App\POS\Models\PosSale;
use App\POS\Services\CashierShiftService;
use App\POS\Services\CustomerDebtService;
use App\POS\Services\PosSaleService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfflineSyncService
{
    public function __construct(
        private readonly PosSaleService $posSaleService,
        private readonly CustomerDebtService $debtService,
        private readonly CashierShiftService $shiftService,
    ) {
    }

    /**
     * Enqueue an offline created record locally for future sync.
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
        return SyncOutboxRecord::updateOrCreate(
            [
                'store_id'              => $store->id,
                'client_transaction_id' => $clientTxId,
            ],
            [
                'branch_id'          => $branchId,
                'device_id'          => $deviceId,
                'record_type'        => $recordType,
                'payload'            => $payload,
                'status'             => 'pending',
                'error_message'      => null,
                'created_offline_at' => $offlineCreatedAt ?? now(),
            ]
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

        // 3. Resolve active or auto-created shift for offline sync
        $shiftId = $payload['cashier_shift_id'] ?? null;
        $shift = $shiftId ? CashierShift::find($shiftId) : null;
        if (! $shift || ! $shift->isOpen() || (int) $shift->store_id !== (int) $store->id) {
            $shift = CashierShift::query()
                ->where('store_id', $store->id)
                ->where('status', 'open')
                ->latest()
                ->first();

            if (! $shift) {
                $shift = CashierShift::create([
                    'store_id'       => $store->id,
                    'cashier_id'     => $actor->id,
                    'register_name'  => 'Offline Sync Register',
                    'opened_at'      => $offlineCreatedAt,
                    'opening_cash'   => '0.00',
                    'status'         => 'open',
                ]);
            }
        }

        // 4. Post the sale via PosSaleService
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

        $sale = $this->posSaleService->post(
            store: $store,
            lines: $normalizedLines,
            payments: $payments,
            actor: $actor,
            shift: $shift,
            customerId: $customerId
        );

        // Stamp client_transaction_id and offline created date
        $sale->update([
            'client_transaction_id' => $clientTxId,
            'posted_at'             => $offlineCreatedAt,
        ]);

        return [
            'server_id'      => $sale->id,
            'receipt_number' => $sale->receipt_number,
            'idempotent'     => false,
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
            ->select(['id', 'store_id', 'category_id', 'brand_id', 'name', 'sku', 'barcode', 'retail_price', 'wholesale_price', 'cost_price', 'is_active', 'updated_at'])
            ->get();

        $categories = Category::query()
            ->where('store_id', $store->id)
            ->where('updated_at', '>=', $since)
            ->select(['id', 'store_id', 'parent_id', 'name', 'slug', 'updated_at'])
            ->get();

        $customers = $store->users()
            ->wherePivot('role', 'customer')
            ->where('users.updated_at', '>=', $since)
            ->select(['users.id', 'users.name', 'users.phone', 'users.email', 'users.updated_at'])
            ->get();

        return [
            'server_time' => now()->toIso8601String(),
            'since'       => $since->toIso8601String(),
            'products'    => $products,
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
