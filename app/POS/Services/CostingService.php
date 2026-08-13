<?php

namespace App\POS\Services;

use App\POS\Enums\InventoryMovementType;
use App\POS\Models\InventoryBalance;
use App\POS\Models\InventoryMovement;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Weighted-average costing (target-design §2.7 / SoT §10.4).
 *
 * Rules:
 *   - Receiving (`opening_balance`, `purchase_received`): recalculates the
 *     average — new_avg = (Q·A + q·c) / (Q + q).
 *   - `purchase_returned`: recalculates with a negative batch — unchanged when
 *     the returned cost equals the current average (no batch tracking).
 *   - Sales / returns / adjustments / transfers / reservations: do NOT change
 *     the average; the movement carries the current average as its unit cost
 *     (COGS at the time of the event).
 *   - `sales_return` restores the original sale-line cost without recalculating.
 *   - Reversals: the average for the key is recomputed by replaying the
 *     remaining movement sequence (reversed originals are excluded).
 *   - Serial / IMEI items: when a movement is flagged `costing => specific`
 *     (metadata) its unit_cost is authoritative for that unit and the average
 *     is left untouched.
 *
 * All arithmetic uses bcmath (exact decimal, no float) — money is never stored
 * or computed as a PHP float.
 */
class CostingService
{
    public const COST_CARRYING_TYPES = [
        'opening_balance',
        'purchase_received',
        'purchase_returned',
    ];

    /**
     * Resolve the unit cost a movement should carry (current average by
     * default — i.e. COGS at the time of the event).
     */
    public function resolveUnitCost(InventoryMovement|array $movementData, int $storeId, int $productId, ?int $warehouseId, ?int $productVariantId): string
    {
        $explicit = is_array($movementData) ? ($movementData['unit_cost'] ?? null) : $movementData->unit_cost;

        if ($explicit !== null && $explicit !== '') {
            return (string) $explicit;
        }

        return $this->currentAverage($storeId, $productId, $productVariantId, $warehouseId);
    }

    /**
     * Apply costing for a freshly posted movement. Called inside the posting
     * transaction, BEFORE the balance quantity update (so the balance row still
     * holds the pre-movement quantity).
     */
    public function applyCosting(InventoryMovement $movement): void
    {
        // Serial / IMEI specific cost — the movement carries its own cost, the
        // average is untouched.
        if (($movement->metadata['costing'] ?? null) === 'specific') {
            return;
        }

        $warehouseKey = $movement->warehouse_id ?? InventoryService::SENTINEL_WAREHOUSE;
        $variantKey = $movement->product_variant_id ?? InventoryService::SENTINEL_VARIANT;

        if ($movement->movement_type === InventoryMovementType::Reversal->value) {
            $newAverage = $this->recomputeAverage(
                $movement->store_id,
                $warehouseKey,
                $movement->product_id,
                $variantKey
            );
            $this->writeAverage($movement->store_id, $warehouseKey, $movement->product_id, $variantKey, $newAverage);

            return;
        }

        if (! in_array($movement->movement_type, self::COST_CARRYING_TYPES, true)) {
            return; // avg unchanged
        }

        $balance = InventoryBalance::query()
            ->where('store_id', $movement->store_id)
            ->where('warehouse_id', $warehouseKey)
            ->where('product_id', $movement->product_id)
            ->where('product_variant_id', $variantKey)
            ->first();

        $Q = $balance ? (string) $balance->quantity_on_hand : '0';
        $A = $balance ? (string) $balance->unit_cost_avg : '0';
        $q = (string) $movement->quantity_delta;
        $c = $movement->unit_cost !== null ? (string) $movement->unit_cost : $A;

        $newAverage = $this->weightedAverage($Q, $A, $q, $c);
        $this->writeAverage($movement->store_id, $warehouseKey, $movement->product_id, $variantKey, $newAverage);
    }

    /** Read the current weighted-average unit cost for a key (decimal string, default '0'). */
    public function currentAverage(int $storeId, int $productId, ?int $productVariantId = null, ?int $warehouseId = null): string
    {
        $balance = InventoryBalance::query()
            ->where('store_id', $storeId)
            ->where('warehouse_id', $warehouseId ?? InventoryService::SENTINEL_WAREHOUSE)
            ->where('product_id', $productId)
            ->where('product_variant_id', $productVariantId ?? InventoryService::SENTINEL_VARIANT)
            ->first();

        return $balance ? (string) $balance->unit_cost_avg : '0';
    }

    /**
     * Recompute a key's average by replaying its movement sequence
     * (reversed originals excluded; reversal rows carry no cost effect).
     */
    public function recomputeAverage(int $storeId, int $warehouseKey, int $productId, int $variantKey): string
    {
        $movements = DB::table('inventory_movements')
            ->where('store_id', $storeId)
            ->where('warehouse_id', $warehouseKey)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantKey)
            ->whereNotIn('id', function ($query) use ($storeId, $warehouseKey, $productId, $variantKey) {
                $query->select('reversal_of_id')
                    ->from('inventory_movements')
                    ->where('reversal_of_id', '!=', null)
                    ->where('store_id', $storeId)
                    ->where('warehouse_id', $warehouseKey)
                    ->where('product_id', $productId)
                    ->where('product_variant_id', $variantKey);
            })
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get(['movement_type', 'quantity_delta', 'unit_cost']);

        $Q = '0';
        $A = '0';

        foreach ($movements as $row) {
            if (! in_array($row->movement_type, self::COST_CARRYING_TYPES, true)) {
                continue;
            }

            $q = (string) $row->quantity_delta;
            $c = $row->unit_cost !== null ? (string) $row->unit_cost : $A;
            $A = $this->weightedAverage($Q, $A, $q, $c);
            $Q = bcadd($Q, $q, 3);
        }

        return $A;
    }

    /* ------------------------------------------------------------------ */
    /*  Internals                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * new_avg = (Q·A + q·c) / (Q + q), rounded half-up to 4 decimals.
     * When the resulting quantity is ≤ 0 the average resets to 0.
     */
    protected function weightedAverage(string $Q, string $A, string $q, string $c): string
    {
        $newQ = bcadd($Q, $q, 3);

        if (bccomp($newQ, '0', 3) <= 0) {
            return '0';
        }

        // Exact to 8 decimals, then round half-up to 4.
        $numerator = bcadd(bcmul($Q, $A, 8), bcmul($q, $c, 8), 8);
        $quotient = bcdiv($numerator, $newQ, 8);
        $halfUnit = '0.00005';
        $scaled = bcmul(bcadd($quotient, $halfUnit, 8), '10000', 0);

        return bcdiv($scaled, '10000', 4);
    }

    /** Upsert the average on the balance cache row. */
    protected function writeAverage(int $storeId, int $warehouseKey, int $productId, int $variantKey, string $average): void
    {
        $exists = InventoryBalance::query()
            ->where('store_id', $storeId)
            ->where('warehouse_id', $warehouseKey)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantKey)
            ->exists();

        if ($exists) {
            InventoryBalance::query()
                ->where('store_id', $storeId)
                ->where('warehouse_id', $warehouseKey)
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantKey)
                ->update(['unit_cost_avg' => $average]);

            return;
        }

        try {
            InventoryBalance::create([
                'store_id' => $storeId,
                'warehouse_id' => $warehouseKey,
                'product_id' => $productId,
                'product_variant_id' => $variantKey,
                'quantity_on_hand' => 0,
                'unit_cost_avg' => $average,
            ]);
        } catch (QueryException $e) {
            // Concurrent creation race — the other transaction inserted first.
            if (str_contains($e->getMessage(), 'UNIQUE constraint failed')
                || str_contains($e->getMessage(), 'Duplicate entry')) {
                InventoryBalance::query()
                    ->where('store_id', $storeId)
                    ->where('warehouse_id', $warehouseKey)
                    ->where('product_id', $productId)
                    ->where('product_variant_id', $variantKey)
                    ->update(['unit_cost_avg' => $average]);

                return;
            }

            throw $e;
        }
    }
}
