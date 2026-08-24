<?php

namespace App\POS\Services;

use App\Models\Product;
use App\Models\Store;
use App\POS\Models\InventoryMovement;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockLedgerService
{
    public function __construct(
        protected InventoryService $inventoryService,
    ) {
    }

    /**
     * Build base query for inventory movements of a store.
     */
    protected function baseQuery(Store $store, array $filters = []): Builder
    {
        $query = InventoryMovement::query()
            ->where('store_id', $store->id)
            ->with(['product', 'productVariant', 'postedBy']);

        if (!empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }

        if (!empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (!empty($filters['flow'])) {
            if ($filters['flow'] === 'inflow') {
                $query->where('quantity_delta', '>', 0);
            } elseif ($filters['flow'] === 'outflow') {
                $query->where('quantity_delta', '<', 0);
            }
        }

        if (!empty($filters['from'])) {
            $from = $filters['from'] instanceof Carbon ? $filters['from'] : Carbon::parse($filters['from'])->startOfDay();
            $query->where('occurred_at', '>=', $from);
        }

        if (!empty($filters['to'])) {
            $to = $filters['to'] instanceof Carbon ? $filters['to'] : Carbon::parse($filters['to'])->endOfDay();
            $query->where('occurred_at', '<=', $to);
        }

        if (!empty($filters['search']) && trim($filters['search']) !== '') {
            $term = trim($filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('source_type', 'like', "%{$term}%")
                    ->orWhere('client_transaction_id', 'like', "%{$term}%")
                    ->orWhereHas('product', function ($pq) use ($term) {
                        $pq->where('name', 'like', "%{$term}%")
                            ->orWhere('sku', 'like', "%{$term}%");
                    })
                    ->orWhereHas('postedBy', fn ($uq) => $uq->where('name', 'like', "%{$term}%"));
            });
        }

        return $query;
    }

    /**
     * Get summary metrics for the stock ledger dashboard.
     */
    public function getSummaryMetrics(Store $store, array $filters = []): array
    {
        $query = $this->baseQuery($store, $filters);

        $totalRecords = (clone $query)->count();

        $inflow = (float) (clone $query)->where('quantity_delta', '>', 0)->sum('quantity_delta');
        $outflow = (float) (clone $query)->where('quantity_delta', '<', 0)->sum(DB::raw('ABS(quantity_delta)'));
        $netDelta = $inflow - $outflow;

        $uniqueProducts = (clone $query)->distinct('product_id')->count('product_id');

        return [
            'total_records' => $totalRecords,
            'total_inflow' => $inflow,
            'total_outflow' => $outflow,
            'net_delta' => $netDelta,
            'unique_products' => $uniqueProducts,
        ];
    }

    /**
     * List movements with pagination.
     */
    public function listMovements(Store $store, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->baseQuery($store, $filters)
            ->latest('occurred_at')
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Generate product-specific Bin Card timeline with running balance.
     */
    public function getProductBinCard(Store $store, Product $product, ?Carbon $from = null, ?Carbon $to = null, ?int $warehouseId = null): array
    {
        // 1. Calculate opening balance before $from
        $openingQuery = InventoryMovement::query()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id);

        if ($warehouseId) {
            $openingQuery->where('warehouse_id', $warehouseId);
        }

        if ($from) {
            $openingQuery->where('occurred_at', '<', $from->copy()->startOfDay());
            $openingBalance = (float) $openingQuery->sum('quantity_delta');
        } else {
            $openingBalance = 0.0;
        }

        // 2. Query movements within the period ordered chronologically
        $movementsQuery = InventoryMovement::query()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->with(['postedBy', 'productVariant']);

        if ($warehouseId) {
            $movementsQuery->where('warehouse_id', $warehouseId);
        }

        if ($from) {
            $movementsQuery->where('occurred_at', '>=', $from->copy()->startOfDay());
        }

        if ($to) {
            $movementsQuery->where('occurred_at', '<=', $to->copy()->endOfDay());
        }

        $movements = $movementsQuery
            ->orderBy('occurred_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 3. Compute running balance at each step
        $runningBalance = $openingBalance;
        $totalIn = 0.0;
        $totalOut = 0.0;

        $timeline = [];
        foreach ($movements as $m) {
            $delta = (float) $m->quantity_delta;
            $inQty = $delta > 0 ? $delta : 0.0;
            $outQty = $delta < 0 ? abs($delta) : 0.0;

            $totalIn += $inQty;
            $totalOut += $outQty;
            $runningBalance += $delta;

            $timeline[] = [
                'id' => $m->id,
                'occurred_at' => $m->occurred_at,
                'movement_type' => $m->movement_type,
                'movement_label' => $m->type()->label(),
                'quantity_delta' => $delta,
                'in_qty' => $inQty,
                'out_qty' => $outQty,
                'unit_cost' => (float) $m->unit_cost,
                'total_cost' => round(abs($delta) * (float) $m->unit_cost, 2),
                'running_balance' => $runningBalance,
                'source_type' => $m->source_type,
                'source_id' => $m->source_id,
                'client_transaction_id' => $m->client_transaction_id,
                'metadata' => $m->metadata,
                'posted_by_name' => $m->postedBy?->name ?? 'System',
            ];
        }

        $currentOnHand = (float) $this->inventoryService->totalOnHand($store->id, $product->id);

        return [
            'product' => $product,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'current_on_hand' => $currentOnHand,
            'timeline' => array_reverse($timeline), // Display newest first in UI table
            'timeline_chronological' => $timeline,
        ];
    }

    /**
     * Stream CSV export of stock ledger movements.
     */
    public function exportMovementsCsv(Store $store, array $filters): StreamedResponse
    {
        $movements = $this->baseQuery($store, $filters)
            ->latest('occurred_at')
            ->latest('id')
            ->get();

        $filename = 'stock-ledger-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($movements) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Date & Time',
                'Product Name',
                'SKU',
                'Movement Type',
                'Quantity Delta',
                'Unit Cost (MMK)',
                'Total Value (MMK)',
                'Source / Reference',
                'Posted By',
                'Transaction ID',
            ]);

            foreach ($movements as $m) {
                $delta = (float) $m->quantity_delta;
                $cost = (float) $m->unit_cost;
                $value = round(abs($delta) * $cost, 2);

                fputcsv($handle, [
                    $m->occurred_at ? $m->occurred_at->format('Y-m-d H:i:s') : '',
                    $m->product?->name ?? 'N/A',
                    $m->product?->sku ?? '-',
                    $m->type()->label(),
                    $delta,
                    $cost,
                    $value,
                    $m->source_type ? ($m->source_type . ($m->source_id ? " #{$m->source_id}" : '')) : '-',
                    $m->postedBy?->name ?? 'System',
                    $m->client_transaction_id ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
