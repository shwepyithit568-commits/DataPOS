<?php

namespace App\POS\Services;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Enums\InventoryMovementType;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\StockCount;
use App\POS\Models\StockCountLine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockCountService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected StoreLocationService $storeLocations,
    ) {
    }

    /**
     * Get summary counts for the stock count dashboard.
     */
    public function getStatistics(Store $store): array
    {
        $base = StockCount::query()->where('store_id', $store->id);

        return [
            'total' => (clone $base)->count(),
            'in_progress' => (clone $base)->where('status', StockCount::STATUS_IN_PROGRESS)->count(),
            'approved' => (clone $base)->where('status', StockCount::STATUS_APPROVED)->count(),
            'cancelled' => (clone $base)->where('status', StockCount::STATUS_CANCELLED)->count(),
        ];
    }

    /**
     * List stock count sessions with optional search and status filter.
     */
    public function listSessions(Store $store, ?string $search = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = StockCount::query()
            ->where('store_id', $store->id)
            ->with(['createdBy', 'approvedBy', 'branch', 'warehouse'])
            ->latest('id');

        if ($status && in_array($status, [StockCount::STATUS_DRAFT, StockCount::STATUS_IN_PROGRESS, StockCount::STATUS_APPROVED, StockCount::STATUS_CANCELLED], true)) {
            $query->where('status', $status);
        }

        if ($search && trim($search) !== '') {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('session_number', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%")
                    ->orWhereHas('createdBy', fn ($uq) => $uq->where('name', 'like', "%{$term}%"));
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new stock count session and snapshot current on-hand quantities into lines.
     */
    public function createSession(Store $store, array $data, ?User $user = null): StockCount
    {
        return DB::transaction(function () use ($store, $data, $user) {
            $datePrefix = now()->format('Ymd');
            $countToday = StockCount::where('store_id', $store->id)
                ->whereDate('created_at', now()->toDateString())
                ->count() + 1;
            $sessionNumber = sprintf('SC-%s-%04d', $datePrefix, $countToday);

            $scope = $data['scope'] ?? StockCount::SCOPE_ALL;
            $categoryIds = ($scope === StockCount::SCOPE_CATEGORY && !empty($data['category_ids']))
                ? array_map('intval', (array) $data['category_ids'])
                : null;

            $warehouseId = !empty($data['warehouse_id']) ? (int) $data['warehouse_id'] : null;
            if (!$warehouseId) {
                $defaultWarehouse = $this->storeLocations->defaultWarehouse($store);
                $warehouseId = $defaultWarehouse?->id;
            }

            $branchId = !empty($data['branch_id']) ? (int) $data['branch_id'] : null;

            $session = StockCount::create([
                'store_id' => $store->id,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'session_number' => $sessionNumber,
                'scope' => $scope,
                'category_ids' => $categoryIds,
                'status' => StockCount::STATUS_IN_PROGRESS,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user?->id ?? auth()->id(),
            ]);

            // Query active products for this store
            $productsQuery = Product::query()
                ->where('store_id', $store->id)
                ->orderBy('name');

            if ($scope === StockCount::SCOPE_CATEGORY && !empty($categoryIds)) {
                $productsQuery->whereIn('category_id', $categoryIds);
            }

            $products = $productsQuery->get();
            $productIds = $products->pluck('id')->all();

            // Fetch on-hand quantities for these products
            $balances = $this->inventoryService->balancesForProducts($store->id, $productIds);

            $lines = [];
            $now = now();
            foreach ($products as $product) {
                $systemQty = isset($balances[$product->id]) ? (float) $balances[$product->id]['total'] : 0.000;
                $unitCost = (float) ($product->cost_price ?? $product->buy_price ?? 0);

                $lines[] = [
                    'stock_count_id' => $session->id,
                    'store_id' => $store->id,
                    'product_id' => $product->id,
                    'product_variant_id' => 0,
                    'category_id' => $product->category_id,
                    'system_quantity' => $systemQty,
                    'counted_quantity' => null,
                    'variance_quantity' => 0.000,
                    'unit_cost' => $unitCost,
                    'variance_cost' => 0.00,
                    'is_counted' => false,
                    'notes' => null,
                    'counted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($lines)) {
                // Chunk insert for high item counts
                foreach (array_chunk($lines, 200) as $chunk) {
                    StockCountLine::insert($chunk);
                }
            }

            $session->recalculateStats();

            return $session;
        });
    }

    /**
     * Save count for a single line item.
     */
    public function saveCountLine(StockCount $session, int $lineId, float $countedQty, ?string $notes = null): StockCountLine
    {
        if ($session->isApproved() || $session->isCancelled()) {
            throw new InventoryException('Cannot update a closed or cancelled stock count session.');
        }

        $line = StockCountLine::where('stock_count_id', $session->id)
            ->where('id', $lineId)
            ->firstOrFail();

        $line->setCount($countedQty, $notes);
        $session->recalculateStats();

        return $line;
    }

    /**
     * Batch save counts from the sheet.
     */
    public function bulkSaveCounts(StockCount $session, array $counts): void
    {
        if ($session->isApproved() || $session->isCancelled()) {
            throw new InventoryException('Cannot update a closed or cancelled stock count session.');
        }

        DB::transaction(function () use ($session, $counts) {
            foreach ($counts as $item) {
                if (!isset($item['id']) || !isset($item['counted_quantity']) || $item['counted_quantity'] === '' || $item['counted_quantity'] === null) {
                    continue;
                }

                $line = StockCountLine::where('stock_count_id', $session->id)
                    ->where('id', (int) $item['id'])
                    ->first();

                if ($line) {
                    $line->setCount((float) $item['counted_quantity'], $item['notes'] ?? null);
                }
            }

            $session->recalculateStats();
        });
    }

    /**
     * Quick scan lookup by SKU, Barcode, or Product Name.
     */
    public function quickScan(StockCount $session, string $query): array
    {
        $term = trim($query);
        if ($term === '') {
            return [];
        }

        $lines = StockCountLine::query()
            ->where('stock_count_id', $session->id)
            ->where(function ($q) use ($term) {
                $q->whereHas('product', function ($pq) use ($term) {
                    $pq->where('sku', $term)
                        ->orWhere('name', 'like', "%{$term}%");
                })->orWhereHas('variant', function ($vq) use ($term) {
                    $vq->where('sku', $term)
                        ->orWhere('name', 'like', "%{$term}%");
                });
            })
            ->with(['product', 'category', 'variant'])
            ->take(10)
            ->get();

        return $lines->map(function (StockCountLine $line) {
            return [
                'line_id' => $line->id,
                'product_id' => $line->product_id,
                'product_name' => $line->product?->name ?? 'Unknown',
                'sku' => $line->product?->sku ?? '-',
                'barcode' => $line->variant?->sku ?? $line->product?->sku ?? '-',
                'category_name' => $line->category?->name ?? '-',
                'system_quantity' => (float) $line->system_quantity,
                'counted_quantity' => $line->counted_quantity !== null ? (float) $line->counted_quantity : null,
                'variance_quantity' => (float) $line->variance_quantity,
                'is_counted' => $line->is_counted,
            ];
        })->all();
    }

    /**
     * Approve and reconcile physical stock count.
     * Posts adjustment movements to the inventory ledger for all lines with non-zero variance.
     */
    public function approveAndReconcile(StockCount $session, ?User $user = null): StockCount
    {
        if ($session->isApproved()) {
            throw new InventoryException('Stock count session is already approved and reconciled.');
        }

        if ($session->isCancelled()) {
            throw new InventoryException('Cannot approve a cancelled stock count session.');
        }

        return DB::transaction(function () use ($session, $user) {
            $session->recalculateStats();

            // Fetch all counted lines with non-zero variance
            $varianceLines = $session->lines()
                ->where('is_counted', true)
                ->where('variance_quantity', '!=', 0)
                ->get();

            $store = $session->store;
            $approverId = $user?->id ?? auth()->id();

            foreach ($varianceLines as $line) {
                $variance = (float) $line->variance_quantity;

                if ($variance > 0) {
                    // Physical stock is GREATER than system stock -> post adjustment_in
                    $this->inventoryService->postMovement([
                        'store' => $store,
                        'store_id' => $store->id,
                        'branch_id' => $session->branch_id,
                        'warehouse_id' => $session->warehouse_id,
                        'product_id' => $line->product_id,
                        'product_variant_id' => $line->product_variant_id ?: null,
                        'movement_type' => InventoryMovementType::AdjustmentIn->value,
                        'quantity_delta' => $variance,
                        'unit_cost' => (float) $line->unit_cost,
                        'source_type' => 'stock_count',
                        'source_id' => $session->id,
                        'client_transaction_id' => 'sc-' . $session->id . '-line-' . $line->id . '-' . Str::uuid(),
                        'occurred_at' => now(),
                        'posted_by' => $approverId,
                        'metadata' => [
                            'session_number' => $session->session_number,
                            'line_id' => $line->id,
                            'system_qty' => (float) $line->system_quantity,
                            'counted_qty' => (float) $line->counted_quantity,
                            'variance_qty' => $variance,
                            'notes' => $line->notes ?: 'Physical stock count reconciliation',
                        ],
                    ], ['allow_negative' => true]);
                } elseif ($variance < 0) {
                    // Physical stock is LESS than system stock -> post adjustment_out
                    $this->inventoryService->postMovement([
                        'store' => $store,
                        'store_id' => $store->id,
                        'branch_id' => $session->branch_id,
                        'warehouse_id' => $session->warehouse_id,
                        'product_id' => $line->product_id,
                        'product_variant_id' => $line->product_variant_id ?: null,
                        'movement_type' => InventoryMovementType::AdjustmentOut->value,
                        'quantity_delta' => $variance, // Already negative (e.g. -2)
                        'unit_cost' => (float) $line->unit_cost,
                        'source_type' => 'stock_count',
                        'source_id' => $session->id,
                        'client_transaction_id' => 'sc-' . $session->id . '-line-' . $line->id . '-' . Str::uuid(),
                        'occurred_at' => now(),
                        'posted_by' => $approverId,
                        'metadata' => [
                            'session_number' => $session->session_number,
                            'line_id' => $line->id,
                            'system_qty' => (float) $line->system_quantity,
                            'counted_qty' => (float) $line->counted_quantity,
                            'variance_qty' => $variance,
                            'notes' => $line->notes ?: 'Physical stock count reconciliation',
                        ],
                    ], ['allow_negative' => true]);
                }
            }

            $session->status = StockCount::STATUS_APPROVED;
            $session->approved_by = $approverId;
            $session->approved_at = now();
            $session->save();

            return $session;
        });
    }

    /**
     * Cancel an in-progress session.
     */
    public function cancelSession(StockCount $session): StockCount
    {
        if ($session->isApproved()) {
            throw new InventoryException('Cannot cancel an approved stock count session.');
        }

        $session->status = StockCount::STATUS_CANCELLED;
        $session->save();

        return $session;
    }
}
