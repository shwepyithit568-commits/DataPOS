<?php

namespace App\POS\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\POS\Enums\InventoryMovementType;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\Branch;
use App\POS\Models\InventoryBalance;
use App\POS\Models\InventoryMovement;
use App\POS\Models\Warehouse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Shared inventory ledger service (SoT §5 / target-design §2.5).
 *
 * The ledger is the single authoritative inventory source for POS and Ecommerce.
 * Rules enforced here:
 *   1. Movements are immutable — corrections are reversal movements (model guard too).
 *   2. Duplicate posting is prevented — unique (store_id, client_transaction_id) plus
 *      a per-source-line unique backstop.
 *   3. Offline retries are idempotent — same client_transaction_id returns the existing movement.
 *   4. Balance updates are transactional and use atomic SQL arithmetic (exact decimal, no float).
 *   5. Concurrent sales cannot corrupt balances — MySQL row locks (lockForUpdate) + conditional
 *      decrement; SQLite serializes writers natively.
 *   6. inventory_balances is a derived cache — only this service writes it.
 *   7. Negative stock is blocked by default (SoT §14.3).
 */
class InventoryService
{
    public const SENTINEL_WAREHOUSE = 0;
    public const SENTINEL_VARIANT = 0;

    public function __construct(protected StoreLocationService $storeLocations, protected CostingService $costing)
    {
    }

    /**
     * Post one ledger movement and apply its effect to the balance cache.
     *
     * @param  array  $data  store_id|store, product_id, movement_type, quantity_delta,
     *                       plus optional: branch_id, warehouse_id, product_variant_id, unit_cost,
     *                       source_type, source_id, client_transaction_id, occurred_at, posted_by, metadata
     * @param  array  $options  allow_negative (default: config), client_transaction_id override
     */
    public function postMovement(array $data, array $options = []): InventoryMovement
    {
        $store = isset($data['store']) && $data['store'] instanceof Store
            ? $data['store']
            : Store::findOrFail($data['store_id']);

        $product = Product::findOrFail($data['product_id']);

        if ((int) $product->store_id !== (int) $store->id) {
            throw new InventoryException("Product #{$product->id} does not belong to store #{$store->id} — cross-store posting is forbidden.");
        }

        $type = InventoryMovementType::from($data['movement_type']);
        $delta = (float) $data['quantity_delta'];

        // Warehouse: omitted → the store's default warehouse (auto-created if missing).
        // Provided → must belong to this store.
        $warehouseId = $this->resolveWarehouseId($store, $data['warehouse_id'] ?? null);

        // Branch (optional): if provided it must belong to this store.
        $branchId = $data['branch_id'] ?? null;
        if ($branchId !== null) {
            $branch = Branch::find($branchId);
            if (! $branch || (int) $branch->store_id !== (int) $store->id) {
                throw new InventoryException("Branch #{$branchId} does not belong to store #{$store->id} — cross-store posting is forbidden.");
            }
        }

        // Sign validation — reversal is the only type with a free sign.
        $expectedSign = $type->expectedSign();
        if ($expectedSign !== null) {
            $sign = $delta <=> 0;
            if ($sign !== $expectedSign) {
                throw new InventoryException("quantity_delta sign mismatch for {$type->value}: expected {$expectedSign}, got {$sign}.");
            }
        }

        // Variant must belong to the product.
        if (! empty($data['product_variant_id'])) {
            $variant = ProductVariant::find($data['product_variant_id']);
            if (! $variant || (int) $variant->product_id !== (int) $product->id) {
                throw new InventoryException("Variant #{$data['product_variant_id']} does not belong to product #{$product->id}.");
            }
        }

        // Unit cost: explicit value wins; otherwise the current weighted average
        // (COGS at the time of the event — target-design §2.7).
        $unitCost = $this->costing->resolveUnitCost($data, $store->id, $product->id, $warehouseId, $data['product_variant_id'] ?? null);

        $clientTransactionId = $options['client_transaction_id']
            ?? ($data['client_transaction_id'] ?? null);

        // Idempotent retry: same (store, client_transaction_id) returns the existing movement.
        if ($clientTransactionId !== null) {
            $existing = InventoryMovement::query()
                ->where('store_id', $store->id)
                ->where('client_transaction_id', $clientTransactionId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $allowNegative = $options['allow_negative']
            ?? (bool) config('inventory.allow_negative_stock', false);

        try {
            return DB::transaction(function () use ($data, $store, $product, $type, $delta, $clientTransactionId, $allowNegative, $warehouseId, $branchId, $unitCost) {
                $movement = InventoryMovement::create([
                    'store_id' => $store->id,
                    'branch_id' => $branchId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $product->id,
                    'product_variant_id' => $data['product_variant_id'] ?? self::SENTINEL_VARIANT,
                    'movement_type' => $type->value,
                    'quantity_delta' => $data['quantity_delta'],
                    'unit_cost' => $unitCost,
                    'source_type' => $data['source_type'] ?? null,
                    'source_id' => $data['source_id'] ?? null,
                    'client_transaction_id' => $clientTransactionId,
                    'occurred_at' => $data['occurred_at'] ?? now(),
                    'posted_by' => $data['posted_by'] ?? auth()->id(),
                    'reversal_of_id' => $data['reversal_of_id'] ?? null,
                    'metadata' => $data['metadata'] ?? null,
                ]);

                // Record-only movements (online_confirm, delta 0) do not touch balances.
                if ($delta !== 0.0) {
                    // Costing FIRST — the balance row still holds the pre-movement
                    // quantity, which the weighted-average batch formula needs.
                    $this->costing->applyCosting($movement);

                    $this->applyToBalance($store->id, $product->id, $warehouseId, $data['product_variant_id'] ?? null, $delta, $allowNegative);

                    if ((bool) config('inventory.sync_stock_status_cache', true)) {
                        $this->refreshProductStockStatus($store->id, $product->id);
                    }
                }

                return $movement;
            });
        } catch (QueryException $e) {
            // A concurrent duplicate (client_transaction_id) that slipped past the pre-check:
            // return the already-posted movement instead of failing the retry.
            if ($clientTransactionId !== null && $this->isUniqueViolation($e)) {
                $existing = InventoryMovement::query()
                    ->where('store_id', $store->id)
                    ->where('client_transaction_id', $clientTransactionId)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            throw $e;
        }
    }

    /**
     * Correct a posted movement with a reversal movement (SoT §15.1).
     *
     * Pass client_transaction_id in $options for idempotent retries.
     */
    public function reverseMovement(InventoryMovement $movement, array $options = []): InventoryMovement
    {
        if ($movement->reversal_of_id !== null) {
            throw new InventoryException("Movement #{$movement->id} is itself a reversal — a reversal cannot be reversed.");
        }

        if (InventoryMovement::query()->where('reversal_of_id', $movement->id)->exists()) {
            throw new InventoryException("Movement #{$movement->id} has already been reversed.");
        }

        $delta = -1 * (float) $movement->quantity_delta;

        return $this->postMovement([
            'store_id' => $movement->store_id,
            'branch_id' => $movement->branch_id,
            'warehouse_id' => $movement->warehouse_id,
            'product_id' => $movement->product_id,
            'product_variant_id' => $movement->product_variant_id,
            'movement_type' => InventoryMovementType::Reversal->value,
            'quantity_delta' => $delta,
            'unit_cost' => $movement->unit_cost,
            'source_type' => ($movement->source_type ? $movement->source_type : 'movement') . '_reversal',
            'source_id' => $movement->source_id ?? $movement->id,
            'client_transaction_id' => $options['client_transaction_id']
                ?? 'reversal-' . $movement->id . '-' . Str::uuid(),
            'occurred_at' => $options['occurred_at'] ?? now(),
            'posted_by' => $options['posted_by'] ?? $movement->posted_by,
            'reversal_of_id' => $movement->id,
            'metadata' => array_merge($options['metadata'] ?? [], [
                'reason' => $options['reason'] ?? null,
                'reversed_movement_id' => $movement->id,
            ]),
        ], $options);
    }

    /** Read the balance cache row for a (store, warehouse, product, variant) key. */
    public function balanceFor(int $storeId, int $productId, ?int $productVariantId = null, ?int $warehouseId = null): ?InventoryBalance
    {
        return InventoryBalance::query()
            ->where('store_id', $storeId)
            ->where('warehouse_id', $warehouseId ?? self::SENTINEL_WAREHOUSE)
            ->where('product_id', $productId)
            ->where('product_variant_id', $productVariantId ?? self::SENTINEL_VARIANT)
            ->first();
    }

    /**
     * Total on hand for a product (all warehouses, optionally one variant).
     * Returns an exact decimal string.
     */
    public function totalOnHand(int $storeId, int $productId, ?int $productVariantId = null): string
    {
        $sum = DB::table('inventory_balances')
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->when($productVariantId !== null, fn ($q) => $q->where('product_variant_id', $productVariantId))
            ->sum('quantity_on_hand');

        // Normalize to 3 decimals (SQLite returns '6', MySQL '6.000'). Values are
        // exact 3-decimal quantities, so formatting is lossless.
        return number_format((float) $sum, 3, '.', '');
    }

    /**
     * One grouped balance lookup for many products — the POS grid/search hot
     * path. Returns [productId => ['total' => string, 'variants' => [variantId => string]]]
     * with 3-decimal normalized strings, so callers never run a SUM query per
     * product AND per variant (the pre-fix grid issued ~N+1 queries per page).
     *
     * `total` matches totalOnHand(): the sum of ALL balance rows for the
     * product (product-level + every variant). `variants` maps each
     * variant id to its own total.
     *
     * @param  array<int, int>  $productIds
     * @return array<int, array{total:string, variants:array<int, string>}>
     */
    public function balancesForProducts(int $storeId, array $productIds): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        if ($productIds === []) {
            return [];
        }

        $rows = DB::table('inventory_balances')
            ->where('store_id', $storeId)
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, product_variant_id, SUM(quantity_on_hand) as total')
            ->groupBy('product_id', 'product_variant_id')
            ->get();

        $balances = [];
        foreach ($rows as $row) {
            $productId = (int) $row->product_id;
            $variantId = $row->product_variant_id !== null ? (int) $row->product_variant_id : null;
            $amount = (float) $row->total;

            $balances[$productId]['total'] = ($balances[$productId]['total'] ?? 0) + $amount;
            if ($variantId !== null) {
                $balances[$productId]['variants'][$variantId] = ($balances[$productId]['variants'][$variantId] ?? 0) + $amount;
            }
        }

        foreach ($balances as $productId => &$entry) {
            $entry['total'] = number_format((float) ($entry['total'] ?? 0), 3, '.', '');
            // NOTE: iterate the array directly — `($entry['variants'] ?? [])`
            // wraps it in a temporary and foreach-by-reference then writes to
            // the copy, silently dropping the formatting.
            if (isset($entry['variants'])) {
                foreach ($entry['variants'] as $variantId => &$variantTotal) {
                    $variantTotal = number_format((float) $variantTotal, 3, '.', '');
                }
                unset($variantTotal);
            } else {
                $entry['variants'] = [];
            }
        }
        unset($entry);

        return $balances;
    }

    /**
     * Rebuild the entire balance cache from the movement ledger.
     *
     * @return int number of balance rows written
     */
    public function rebuildBalances(): int
    {
        DB::table('inventory_balances')->delete();

        $rows = DB::table('inventory_movements')
            ->select('store_id', 'warehouse_id', 'product_id', 'product_variant_id')
            ->selectRaw('SUM(quantity_delta) as total')
            ->groupBy('store_id', 'warehouse_id', 'product_id', 'product_variant_id')
            ->get();

        $now = now();
        $count = 0;

        foreach ($rows as $row) {
            $warehouseKey = $row->warehouse_id ?? self::SENTINEL_WAREHOUSE;
            $variantKey = $row->product_variant_id ?? self::SENTINEL_VARIANT;

            // Weighted-average cost is also derived — recompute from the ledger.
            $average = $this->costing->recomputeAverage($row->store_id, $warehouseKey, $row->product_id, $variantKey);

            DB::table('inventory_balances')->insert([
                'store_id' => $row->store_id,
                'warehouse_id' => $warehouseKey,
                'product_id' => $row->product_id,
                'product_variant_id' => $variantKey,
                'quantity_on_hand' => $row->total,
                'unit_cost_avg' => $average,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Compare stored balances against movements (read-only).
     *
     * @return array{stored:int, computed:int, mismatches:array<int, array<string, mixed>>}
     */
    public function verifyBalances(): array
    {
        $computed = DB::table('inventory_movements')
            ->select('store_id', 'warehouse_id', 'product_id', 'product_variant_id')
            ->selectRaw('SUM(quantity_delta) as total')
            ->groupBy('store_id', 'warehouse_id', 'product_id', 'product_variant_id')
            ->get()
            ->keyBy(fn ($row) => $this->balanceKey($row->store_id, $row->warehouse_id, $row->product_id, $row->product_variant_id));

        $stored = DB::table('inventory_balances')->get();
        $computedCount = $computed->count();
        $mismatches = [];

        foreach ($stored as $row) {
            $key = $this->balanceKey($row->store_id, $row->warehouse_id, $row->product_id, $row->product_variant_id);
            $expected = isset($computed[$key]) ? (string) $computed[$key]->total : '0';
            if ((string) $row->quantity_on_hand !== $expected) {
                $mismatches[] = [
                    'store_id' => $row->store_id,
                    'warehouse_id' => $row->warehouse_id,
                    'product_id' => $row->product_id,
                    'product_variant_id' => $row->product_variant_id,
                    'stored' => (string) $row->quantity_on_hand,
                    'expected' => $expected,
                ];
            }
            unset($computed[$key]);
        }

        // Balance rows missing entirely.
        foreach ($computed as $key => $row) {
            $mismatches[] = [
                'store_id' => $row->store_id,
                'warehouse_id' => $row->warehouse_id ?? 0,
                'product_id' => $row->product_id,
                'product_variant_id' => $row->product_variant_id ?? 0,
                'stored' => '(missing)',
                'expected' => (string) $row->total,
            ];
        }

        return [
            'stored' => $stored->count(),
            'computed' => $computedCount,
            'mismatches' => $mismatches,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Internals                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Apply a non-zero delta to the balance cache row atomically.
     * Uses exact SQL decimal arithmetic — no PHP float math on money/quantity.
     */
    protected function applyToBalance(int $storeId, int $productId, int $warehouseKey, ?int $productVariantId, float $delta, bool $allowNegative): void
    {
        $variantKey = $productVariantId ?? self::SENTINEL_VARIANT;

        // Negative guard: conditional decrement that only succeeds when stock suffices.
        // The UPDATE takes a row lock on MySQL, serializing concurrent sales.
        if ($delta < 0 && ! $allowNegative) {
            $affected = DB::table('inventory_balances')
                ->where('store_id', $storeId)
                ->where('warehouse_id', $warehouseKey)
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantKey)
                ->whereRaw('quantity_on_hand + ? >= 0', [$delta])
                ->increment('quantity_on_hand', $delta);

            if ($affected === 0) {
                $onHand = DB::table('inventory_balances')
                    ->where('store_id', $storeId)
                    ->where('warehouse_id', $warehouseKey)
                    ->where('product_id', $productId)
                    ->where('product_variant_id', $variantKey)
                    ->value('quantity_on_hand');

                throw new InventoryException(
                    'Insufficient stock — movement would make the balance negative. '
                    . "Product #{$productId} on hand: " . ($onHand ?? '0') . ", requested delta: {$delta}."
                );
            }

            return;
        }

        $balance = $this->lockOrCreateBalance($storeId, $warehouseKey, $productId, $variantKey);
        $balance->increment('quantity_on_hand', $delta);
    }

    /** Fetch (with row lock on MySQL) or create the balance row for a key. */
    protected function lockOrCreateBalance(int $storeId, int $warehouseKey, int $productId, int $variantKey): InventoryBalance
    {
        $query = InventoryBalance::query()
            ->where('store_id', $storeId)
            ->where('warehouse_id', $warehouseKey)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantKey);

        if (DB::connection()->getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        $balance = $query->first();

        if ($balance) {
            return $balance;
        }

        try {
            return InventoryBalance::create([
                'store_id' => $storeId,
                'warehouse_id' => $warehouseKey,
                'product_id' => $productId,
                'product_variant_id' => $variantKey,
                'quantity_on_hand' => 0,
            ]);
        } catch (QueryException $e) {
            // Concurrent creation race — the other transaction inserted the row first.
            if ($this->isUniqueViolation($e)) {
                $balance = InventoryBalance::query()
                    ->where('store_id', $storeId)
                    ->where('warehouse_id', $warehouseKey)
                    ->where('product_id', $productId)
                    ->where('product_variant_id', $variantKey)
                    ->first();

                if ($balance) {
                    return $balance;
                }
            }

            throw $e;
        }
    }

    /** products.stock_status is a derived cache (SoT §5) — refresh from the ledger. */
    protected function refreshProductStockStatus(int $storeId, int $productId): void
    {
        $total = DB::table('inventory_balances')
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->sum('quantity_on_hand');

        Product::query()->where('id', $productId)->update([
            'stock_status' => (float) $total > 0 ? 'in_stock' : 'out_of_stock',
        ]);
    }

    /**
     * Resolve a warehouse id for a store: the provided id (validated to belong
     * to the store) or the store's default warehouse (auto-created if missing).
     */
    public function resolveWarehouseId(Store $store, ?int $warehouseId): int
    {
        if ($warehouseId !== null) {
            $warehouse = Warehouse::find($warehouseId);
            if (! $warehouse || (int) $warehouse->store_id !== (int) $store->id) {
                throw new InventoryException("Warehouse #{$warehouseId} does not belong to store #{$store->id} — cross-store posting is forbidden.");
            }

            return (int) $warehouse->id;
        }

        return (int) $this->storeLocations->defaultWarehouse($store)->id;
    }

    /** The store's default warehouse id (auto-created if missing). */
    public function defaultWarehouseId(int $storeId): int
    {
        return $this->resolveWarehouseId(Store::findOrFail($storeId), null);
    }

    protected function balanceKey(int $storeId, ?int $warehouseId, int $productId, ?int $variantId): string
    {
        return implode(':', [$storeId, $warehouseId ?? self::SENTINEL_WAREHOUSE, $productId, $variantId ?? self::SENTINEL_VARIANT]);
    }

    protected function isUniqueViolation(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'UNIQUE constraint failed')
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'unique constraint');
    }
}
