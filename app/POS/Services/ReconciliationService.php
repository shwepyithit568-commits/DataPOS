<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\InventoryMovement;
use App\POS\Models\InventoryReconciliation;
use App\POS\Models\InventoryReconciliationItem;
use App\POS\Models\OpeningStockRequestItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Opening-stock reconciliation (Phase 2.5 — pilot exit criterion "reconciliation
 * diff = 0").
 *
 * The store's IMPORTED opening stock is the quantity in APPROVED
 * opening_stock_requests (the manager already signed those off). The RECORDED
 * opening position is what the inventory ledger actually carries —
 * `opening_balance` movements + reversals of those movements + previous
 * reconciliation correction adjustments (source_type = inventory_reconciliation).
 *
 * report(): per product/variant — imported vs recorded vs diff (imported −
 * recorded), plus current on-hand for context. Approval posts adjustment_in/out
 * correction movements (the ledger is immutable — SoT §15.1) so the recorded
 * opening position converges to the imported opening stock, and snapshots the
 * report for the audit trail. Idempotent via client_transaction_id.
 */
class ReconciliationService
{
    public function __construct(
        protected InventoryService $inventory,
        protected StoreLocationService $storeLocations,
    ) {
    }

    /**
     * Live reconciliation report for a store.
     *
     * @return array{
     *   rows: array<int, array<string, mixed>>,
     *   products: int,
     *   diff_products: int,
     *   total_diff: string,
     *   clean: bool,
     * }
     */
    public function report(Store $store): array
    {
        // Imported opening stock = approved opening-stock request lines.
        $imported = OpeningStockRequestItem::query()
            ->join('opening_stock_requests', 'opening_stock_requests.id', '=', 'opening_stock_request_items.opening_stock_request_id')
            ->where('opening_stock_request_items.store_id', $store->id)
            ->where('opening_stock_requests.status', 'approved')
            ->select('opening_stock_request_items.product_id', 'opening_stock_request_items.product_variant_id')
            ->selectRaw('SUM(opening_stock_request_items.quantity) as qty')
            ->groupBy('opening_stock_request_items.product_id', 'opening_stock_request_items.product_variant_id')
            ->get();

        // Recorded opening position = opening_balance + their reversals + prior
        // reconciliation corrections (so the report converges to 0 after approval).
        $openingMovementIds = InventoryMovement::query()
            ->where('store_id', $store->id)
            ->where('movement_type', 'opening_balance')
            ->pluck('id');

        $recorded = InventoryMovement::query()
            ->where('store_id', $store->id)
            ->where(function ($q) use ($openingMovementIds) {
                $q->where('movement_type', 'opening_balance')
                    ->orWhere(function ($q2) use ($openingMovementIds) {
                        $q2->where('movement_type', 'reversal')->whereIn('reversal_of_id', $openingMovementIds);
                    })
                    ->orWhere('source_type', 'inventory_reconciliation');
            })
            ->select('product_id', 'product_variant_id')
            ->selectRaw('SUM(quantity_delta) as qty')
            ->groupBy('product_id', 'product_variant_id')
            ->get();

        // Current on-hand per product/variant (all warehouses) — context column.
        $onHand = DB::table('inventory_balances')
            ->where('store_id', $store->id)
            ->select('product_id', 'product_variant_id')
            ->selectRaw('SUM(quantity_on_hand) as qty')
            ->groupBy('product_id', 'product_variant_id')
            ->get();

        $key = fn ($productId, $variantId) => (int) $productId . ':' . (int) ($variantId ?? 0);

        $importedMap = $imported->keyBy(fn ($row) => $key($row->product_id, $row->product_variant_id));
        $recordedMap = $recorded->keyBy(fn ($row) => $key($row->product_id, $row->product_variant_id));
        $onHandMap = $onHand->keyBy(fn ($row) => $key($row->product_id, $row->product_variant_id));

        $productIds = collect()
            ->merge($imported->pluck('product_id'))
            ->merge($recorded->pluck('product_id'))
            ->unique()
            ->values();
        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        $rows = [];
        foreach ($productIds as $productId) {
            $variantIds = collect()
                ->merge($imported->where('product_id', (int) $productId)->pluck('product_variant_id'))
                ->merge($recorded->where('product_id', (int) $productId)->pluck('product_variant_id'))
                ->unique()
                ->values();

            foreach ($variantIds as $variantId) {
                $variantKey = (int) ($variantId ?? 0);
                $k = $key($productId, $variantKey);

                $importedQty = $this->qty((string) ($importedMap[$k]->qty ?? '0'));
                $recordedQty = $this->qty((string) ($recordedMap[$k]->qty ?? '0'));
                $diff = bcsub($importedQty, $recordedQty, 3);

                $rows[] = [
                    'product_id' => (int) $productId,
                    'product_variant_id' => $variantKey,
                    'product_name' => $products[(int) $productId]->name ?? 'Product #' . $productId,
                    'sku' => $products[(int) $productId]->sku ?? null,
                    'imported' => $importedQty,
                    'recorded' => $recordedQty,
                    'diff' => $diff,
                    'on_hand' => $this->qty((string) ($onHandMap[$k]->qty ?? '0')),
                ];
            }
        }

        // Differences first, then by product name.
        usort($rows, function (array $a, array $b) {
            $diffCmp = bccomp(bccomp($a['diff'], '0', 3) < 0 ? bcmul($a['diff'], '-1', 3) : $a['diff'], bccomp($b['diff'], '0', 3) < 0 ? bcmul($b['diff'], '-1', 3) : $b['diff'], 3);
            if ($diffCmp !== 0) {
                return $diffCmp < 0 ? 1 : -1;
            }

            return strcmp((string) $a['product_name'], (string) $b['product_name']);
        });

        $diffRows = array_values(array_filter($rows, fn ($row) => bccomp($row['diff'], '0', 3) !== 0));
        $totalDiff = array_reduce($diffRows, function (string $carry, array $row) {
            $abs = bccomp($row['diff'], '0', 3) < 0 ? bcmul($row['diff'], '-1', 3) : $row['diff'];

            return bcadd($carry, $abs, 3);
        }, '0');
        $totalDiff = bcadd($totalDiff, '0', 3); // normalize scale (bcadd pads to the requested scale)

        return [
            'rows' => $rows,
            'products' => count($rows),
            'diff_products' => count($diffRows),
            'total_diff' => $totalDiff,
            'clean' => $diffRows === [],
        ];
    }

    /**
     * Manager approval — posts correction movements for every non-zero diff so
     * the ledger's opening position matches the imported opening stock, then
     * snapshots the report. Atomic + idempotent (client_transaction_id).
     */
    public function approve(Store $store, User $actor, ?string $notes = null): InventoryReconciliation
    {
        $report = $this->report($store);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($store, $actor, $notes, $report) {
                    $record = InventoryReconciliation::create([
                        'store_id' => $store->id,
                        'branch_id' => $this->storeLocations->defaultBranch($store)->id,
                        'warehouse_id' => $this->storeLocations->defaultWarehouse($store)->id,
                        'reconciliation_number' => $this->nextNumber($store),
                        'status' => 'approved',
                        'diff_count' => $report['diff_products'],
                        'total_diff' => $report['total_diff'],
                        'notes' => trim((string) $notes) !== '' ? $notes : null,
                        'review_notes' => trim((string) $notes) !== '' ? $notes : null,
                        'reviewed_by' => $actor->id,
                        'reviewed_at' => now(),
                        'approved_at' => now(),
                        'client_transaction_id' => 'rec:' . Str::uuid(),
                        'snapshot' => $report,
                    ]);

                    $defaultWarehouse = $this->storeLocations->defaultWarehouse($store);

                    foreach ($report['rows'] as $i => $row) {
                        if (bccomp($row['diff'], '0', 3) === 0) {
                            continue;
                        }

                        // The diff IS the signed delta: + → adjustment_in, − → adjustment_out.
                        $delta = $row['diff'];
                        $type = bccomp($delta, '0', 3) > 0 ? 'adjustment_in' : 'adjustment_out';

                        // Target warehouse resolution: if deducting stock (adjustment_out), target the warehouse holding on-hand stock or the initial opening movement.
                        $targetWarehouseId = null;
                        if (bccomp($delta, '0', 3) < 0) {
                            $targetWarehouseId = DB::table('inventory_balances')
                                ->where('store_id', $store->id)
                                ->where('product_id', $row['product_id'])
                                ->where('product_variant_id', $row['product_variant_id'] !== 0 ? $row['product_variant_id'] : 0)
                                ->where('quantity_on_hand', '>', 0)
                                ->orderByDesc('quantity_on_hand')
                                ->value('warehouse_id');
                        }

                        $targetWarehouseId = $targetWarehouseId
                            ?? InventoryMovement::where('store_id', $store->id)
                                ->where('product_id', $row['product_id'])
                                ->where('movement_type', 'opening_balance')
                                ->value('warehouse_id')
                            ?? $defaultWarehouse->id;

                        $this->inventory->postMovement([
                            'store' => $store,
                            'warehouse_id' => $targetWarehouseId,
                            'product_id' => $row['product_id'],
                            'product_variant_id' => $row['product_variant_id'] !== 0 ? $row['product_variant_id'] : null,
                            'movement_type' => $type,
                            'quantity_delta' => $delta,
                            'source_type' => 'inventory_reconciliation',
                            'source_id' => $record->id,
                            'occurred_at' => now(),
                            'metadata' => ['reason' => 'Opening-stock reconciliation correction'],
                        ], [
                            'client_transaction_id' => "rec:{$record->id}:{$i}",
                        ]);

                        InventoryReconciliationItem::create([
                            'inventory_reconciliation_id' => $record->id,
                            'store_id' => $store->id,
                            'product_id' => $row['product_id'],
                            'product_variant_id' => $row['product_variant_id'] !== 0 ? $row['product_variant_id'] : null,
                            'imported_quantity' => $row['imported'],
                            'recorded_quantity' => $row['recorded'],
                            'difference' => $row['diff'],
                            'correction' => bccomp($delta, '0', 3) < 0 ? bcmul($delta, '-1', 3) : $delta,
                            'movement_type' => $type,
                        ]);
                    }

                    AuditLog::write(
                        storeId: $store->id,
                        action: 'inventory_reconciliation_approved',
                        entityType: 'inventory_reconciliation',
                        entityId: $record->id,
                        metadata: [
                            'reconciliation_number' => $record->reconciliation_number,
                            'diff_count' => (string) $report['diff_products'],
                            'total_diff' => $report['total_diff'],
                        ],
                        actorId: $actor->id,
                    );

                    return $record->fresh(['items.product', 'reviewedBy']);
                });
            } catch (QueryException $e) {
                if ($attempt === 2 || ! $this->isUniqueViolation($e)) {
                    throw $e;
                }
            }
        }

        throw new InventoryException('Could not approve the reconciliation — please retry.');
    }

    /** The store's approved reconciliations, newest first. */
    public function recent(Store $store, int $limit = 10)
    {
        return InventoryReconciliation::query()
            ->with(['items.product', 'reviewedBy'])
            ->where('store_id', $store->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /* ------------------------------------------------------------------ */
    /*  Internals                                                          */
    /* ------------------------------------------------------------------ */

    /** Normalize a quantity to an exact 3-decimal string. */
    private function qty(string $value): string
    {
        return number_format((float) $value, 3, '.', '');
    }

    /** REC-YYYYMMDD-#### sequence per store. */
    private function nextNumber(Store $store): string
    {
        $prefix = 'REC-' . now()->format('Ymd') . '-';
        $seq = InventoryReconciliation::query()
            ->where('store_id', $store->id)
            ->where('reconciliation_number', 'like', $prefix . '%')
            ->count() + 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'UNIQUE constraint failed')
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'unique constraint');
    }
}
