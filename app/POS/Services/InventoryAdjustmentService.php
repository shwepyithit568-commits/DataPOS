<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\InventoryAdjustment;
use App\POS\Models\InventoryAdjustmentItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Inventory adjustments with manager approval (MVP Phase 2 — final module).
 *
 * - create(): a PENDING request (signed quantities + per-line reason) — no
 *   ledger impact yet.
 * - approve(): manager-only, atomic — each line posts `adjustment_in`
 *   (qty > 0) or `adjustment_out` (qty < 0) with |qty|; the movement carries
 *   the CURRENT weighted-average cost and the average is NOT recalculated
 *   (SoT §6: adjustments are not cost-carrying). Idempotent via
 *   client_transaction_id; insufficient stock blocks an out-adjustment with
 *   no trace.
 * - reject(): manager-only, pending → rejected.
 */
class InventoryAdjustmentService
{
    public function __construct(
        protected InventoryService $inventory,
        protected StoreLocationService $storeLocations,
    ) {
    }

    /**
     * @param  array<int, array{product_id:int, product_variant_id?:int|null, quantity:string, reason:string}>  $items
     */
    public function create(Store $store, array $items, ?string $notes, User $actor): InventoryAdjustment
    {
        $normalized = $this->normalizeItems($store, $items);

        return DB::transaction(function () use ($store, $normalized, $notes, $actor) {
            $adjustment = InventoryAdjustment::create([
                'store_id' => $store->id,
                'branch_id' => $this->storeLocations->defaultBranch($store)->id,
                'warehouse_id' => $this->storeLocations->defaultWarehouse($store)->id,
                'adjustment_number' => $this->nextAdjustmentNumber($store),
                'status' => 'pending',
                'total_quantity' => $normalized['total_quantity'],
                'notes' => trim((string) $notes) !== '' ? $notes : null,
                'submitted_by' => $actor->id,
                'client_transaction_id' => 'adj:' . Str::uuid(),
            ]);

            foreach ($normalized['items'] as $line) {
                InventoryAdjustmentItem::create([
                    'inventory_adjustment_id' => $adjustment->id,
                    'store_id' => $store->id,
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'],
                    'quantity' => $line['quantity'],
                    'reason' => $line['reason'],
                ]);
            }

            AuditLog::write(
                storeId: $store->id,
                action: 'inventory_adjustment_submitted',
                entityType: 'inventory_adjustment',
                entityId: $adjustment->id,
                metadata: ['adjustment_number' => $adjustment->adjustment_number, 'total_quantity' => $normalized['total_quantity']],
                actorId: $actor->id,
            );

            return $adjustment->load(['items.product']);
        });
    }

    /** Manager approval — posts the adjustment movements atomically (idempotent). */
    public function approve(Store $store, InventoryAdjustment $adjustment, User $actor, ?string $reviewNotes = null): InventoryAdjustment
    {
        $this->assertOwned($store, $adjustment);

        if (! $adjustment->isPending()) {
            throw new InventoryException("Adjustment {$adjustment->adjustment_number} is already {$adjustment->status}.");
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($store, $adjustment, $actor, $reviewNotes) {
                    $adjustment = $adjustment->fresh(['items']);

                    if (! $adjustment->isPending()) {
                        throw new InventoryException("Adjustment {$adjustment->adjustment_number} is already {$adjustment->status}.");
                    }

                    foreach ($adjustment->items as $i => $item) {
                        $quantity = (string) $item->quantity;

                        // Out-adjustments post a NEGATIVE delta → InventoryService
                        // enforces non-negative balances (allowNegative defaults off).
                        $this->inventory->postMovement([
                            'store' => $store,
                            'product_id' => $item->product_id,
                            'product_variant_id' => $item->product_variant_id,
                            'movement_type' => bccomp($quantity, '0', 3) > 0 ? 'adjustment_in' : 'adjustment_out',
                            'quantity_delta' => $quantity,
                            'source_type' => 'inventory_adjustment',
                            'source_id' => $adjustment->id,
                            'occurred_at' => now(),
                        ], [
                            'client_transaction_id' => "adj:{$adjustment->id}:{$i}",
                        ]);
                    }

                    $adjustment->update([
                        'status' => 'approved',
                        'reviewed_by' => $actor->id,
                        'reviewed_at' => now(),
                        'approved_at' => now(),
                        'review_notes' => trim((string) $reviewNotes) !== '' ? $reviewNotes : null,
                    ]);

                    AuditLog::write(
                        storeId: $store->id,
                        action: 'inventory_adjustment_approved',
                        entityType: 'inventory_adjustment',
                        entityId: $adjustment->id,
                        metadata: ['adjustment_number' => $adjustment->adjustment_number, 'total_quantity' => (string) $adjustment->total_quantity],
                        actorId: $actor->id,
                    );

                    return $adjustment->fresh(['items.product']);
                });
            } catch (QueryException $e) {
                if ($attempt === 2 || ! $this->isUniqueViolation($e)) {
                    throw $e;
                }
            }
        }

        throw new InventoryException('Could not approve the adjustment — please retry.');
    }

    /** Manager rejection — pending → rejected. */
    public function reject(Store $store, InventoryAdjustment $adjustment, User $actor, ?string $reason = null): InventoryAdjustment
    {
        $this->assertOwned($store, $adjustment);

        if (! $adjustment->isPending()) {
            throw new InventoryException("Adjustment {$adjustment->adjustment_number} is already {$adjustment->status}.");
        }

        $adjustment->update([
            'status' => 'rejected',
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_notes' => trim((string) $reason) !== '' ? $reason : null,
        ]);

        AuditLog::write(
            storeId: $store->id,
            action: 'inventory_adjustment_rejected',
            entityType: 'inventory_adjustment',
            entityId: $adjustment->id,
            metadata: ['adjustment_number' => $adjustment->adjustment_number],
            actorId: $actor->id,
        );

        return $adjustment->fresh(['items.product']);
    }

    /** The store's adjustments, newest first. */
    public function recent(Store $store, int $limit = 20)
    {
        return InventoryAdjustment::query()
            ->with(['items.product', 'submittedBy', 'reviewedBy'])
            ->where('store_id', $store->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /* ------------------------------------------------------------------ */
    /*  Internals                                                          */
    /* ------------------------------------------------------------------ */

    /** @return array{items: array, total_quantity: string} */
    private function normalizeItems(Store $store, array $items): array
    {
        if (empty($items)) {
            throw new InventoryException('An adjustment needs at least one line.');
        }

        $normalized = [];
        $totalQuantity = '0';

        foreach ($items as $i => $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $variantId = ! empty($line['product_variant_id']) ? (int) $line['product_variant_id'] : null;
            $quantity = (string) ($line['quantity'] ?? '0');
            $reason = trim((string) ($line['reason'] ?? ''));

            if ($productId <= 0) {
                throw new InventoryException('Line ' . ($i + 1) . ': choose a product.');
            }
            if (bccomp($quantity, '0', 3) === 0) {
                throw new InventoryException('Line ' . ($i + 1) . ': quantity cannot be zero.');
            }
            if ($reason === '') {
                throw new InventoryException('Line ' . ($i + 1) . ': a reason is required.');
            }

            $product = Product::find($productId);
            if (! $product || (int) $product->store_id !== (int) $store->id) {
                throw new InventoryException('Line ' . ($i + 1) . ': product does not belong to this store.');
            }

            $totalQuantity = bcadd($totalQuantity, $quantity, 3);

            // Merge duplicate product lines (the ledger posts ONE movement per
            // source + product — its unique key is the double-post guard).
            $key = $productId . ':' . ($variantId ?? '0');
            if (isset($normalized[$key])) {
                $normalized[$key]['quantity'] = bcadd($normalized[$key]['quantity'], $quantity, 3);
                if (trim($normalized[$key]['reason']) !== $reason) {
                    $normalized[$key]['reason'] .= '; ' . $reason;
                }
            } else {
                $normalized[$key] = [
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                ];
            }
        }

        // Lines that cancelled out (+3 / −3) are dropped entirely.
        $normalized = array_values(array_filter($normalized, fn ($line) => bccomp($line['quantity'], '0', 3) !== 0));

        if (empty($normalized)) {
            throw new InventoryException('All adjustment lines cancelled out — nothing to adjust.');
        }

        return ['items' => $normalized, 'total_quantity' => $totalQuantity];
    }

    private function assertOwned(Store $store, InventoryAdjustment $adjustment): void
    {
        if ((int) $adjustment->store_id !== (int) $store->id) {
            throw new InventoryException('This adjustment does not belong to the store.');
        }
    }

    /** ADJ-YYYYMMDD-#### sequence per store. */
    private function nextAdjustmentNumber(Store $store): string
    {
        $prefix = 'ADJ-' . now()->format('Ymd') . '-';
        $seq = InventoryAdjustment::query()
            ->where('store_id', $store->id)
            ->where('adjustment_number', 'like', $prefix . '%')
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
