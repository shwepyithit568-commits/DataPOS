<?php

namespace App\POS\Integrations;

use App\Models\Order;
use App\POS\Enums\InventoryMovementType;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\InventoryMovement;
use App\POS\Services\InventoryService;
use Illuminate\Support\Facades\DB;

/**
 * Ecommerce inventory adapter (target-design §2.5 / SoT §5).
 *
 * Translates ORDER LIFECYCLE EVENTS into ledger movements so POS sales and
 * online orders draw from the SAME stock pool — overselling is impossible
 * because the ledger's negative-stock guard rejects any deduction beyond
 * available stock.
 *
 * Reservation policy:
 *   - RESERVE (`online_reserve`, −)  when the order is confirmed (or delivered
 *     directly, skipping confirm) — stock is held from availability.
 *   - COMMIT (`online_confirm`, 0)   when the order is delivered — record-only
 *     transition, no double deduction.
 *   - RELEASE (`online_cancel`, +)   when a reserved-but-not-committed order is
 *     cancelled — availability is restored.
 *
 * Existing `orders` / `order_items` are untouched — this adapter only appends
 * movements. Order reference is recorded in source_type / source_id
 * (order_reserve / order_confirm / order_cancel + order id), with a stable
 * per-line client_transaction_id so retries are idempotent.
 *
 * Order items without a product_id (glass-finder / legacy lines) have no
 * catalog stock and are skipped. Lines of the same product+variant are merged
 * into a single movement.
 */
class OrderInventoryAdapter
{
    public function __construct(protected InventoryService $inventory)
    {
    }

    /**
     * React to an order status transition. Call BEFORE persisting the new
     * status so a failed reservation blocks the transition (no oversell).
     */
    public function handleStatusChange(Order $order, string $fromStatus, string $toStatus): void
    {
        switch ($toStatus) {
            case 'confirmed':
                $this->reserve($order);
                break;

            case 'delivered':
                // Defensive: an order may skip 'confirmed' — deduct at the
                // earliest of (confirmed, delivered), never twice.
                if (! $this->isReserved($order)) {
                    $this->reserve($order);
                }
                $this->commit($order);
                break;

            case 'cancelled':
                $this->release($order);
                break;
        }
    }

    /** Hold stock for the order (`online_reserve`). Throws on insufficient stock. */
    public function reserve(Order $order): void
    {
        if ($this->isReserved($order)) {
            return; // idempotent
        }

        try {
            DB::transaction(function () use ($order) {
                foreach ($this->inventoryLines($order) as $index => $line) {
                    $this->inventory->postMovement([
                        'store_id' => $order->store_id,
                        'product_id' => $line['product_id'],
                        'product_variant_id' => $line['product_variant_id'],
                        'movement_type' => InventoryMovementType::OnlineReserve->value,
                        'quantity_delta' => -$line['quantity'],
                        'source_type' => 'order_reserve',
                        'source_id' => $order->id,
                        'client_transaction_id' => "order-reserve-{$order->id}-{$index}",
                        'metadata' => ['order_number' => $order->order_number],
                    ]);
                }
            });
        } catch (InventoryException $e) {
            throw new InventoryException(
                "Cannot confirm order #{$order->order_number}: insufficient stock ({$e->getMessage()})",
                0,
                $e
            );
        }
    }

    /** Mark reserved stock as committed (`online_confirm`, record-only). */
    public function commit(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($this->inventoryLines($order) as $index => $line) {
                $this->inventory->postMovement([
                    'store_id' => $order->store_id,
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'],
                    'movement_type' => InventoryMovementType::OnlineConfirm->value,
                    'quantity_delta' => 0,
                    'source_type' => 'order_confirm',
                    'source_id' => $order->id,
                    'client_transaction_id' => "order-confirm-{$order->id}-{$index}",
                    'metadata' => ['order_number' => $order->order_number],
                ]);
            }
        });
    }

    /**
     * Release a reservation (`online_cancel`, +) when a reserved-but-not-committed
     * order is cancelled. Committed (delivered) orders keep their deduction —
     * returning those goods is a sales-return flow (Phase 2), not a cancel.
     */
    public function release(Order $order): void
    {
        if (! $this->isReserved($order) || $this->isCommitted($order)) {
            return;
        }

        DB::transaction(function () use ($order) {
            foreach ($this->inventoryLines($order) as $index => $line) {
                $this->inventory->postMovement([
                    'store_id' => $order->store_id,
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'],
                    'movement_type' => InventoryMovementType::OnlineCancel->value,
                    'quantity_delta' => $line['quantity'],
                    'source_type' => 'order_cancel',
                    'source_id' => $order->id,
                    'client_transaction_id' => "order-cancel-{$order->id}-{$index}",
                    'metadata' => ['order_number' => $order->order_number],
                ]);
            }
        });
    }

    public function isReserved(Order $order): bool
    {
        return $this->hasEvent($order, 'order_reserve');
    }

    public function isCommitted(Order $order): bool
    {
        return $this->hasEvent($order, 'order_confirm');
    }

    /* ------------------------------------------------------------------ */
    /*  Internals                                                          */
    /* ------------------------------------------------------------------ */

    protected function hasEvent(Order $order, string $sourceType): bool
    {
        return InventoryMovement::query()
            ->where('store_id', $order->store_id)
            ->where('source_type', $sourceType)
            ->where('source_id', $order->id)
            ->exists();
    }

    /**
     * Catalog lines as grouped [product_id, product_variant_id, quantity].
     * Skips items without a product (glass-finder / legacy) and merges
     * duplicate product+variant lines so the per-source-line unique key holds.
     *
     * @return array<int, array{product_id:int, product_variant_id:?int, quantity:float}>
     */
    protected function inventoryLines(Order $order): array
    {
        $lines = [];

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $key = $item->product_id . ':' . ($item->product_variant_id ?? 0);

            if (! isset($lines[$key])) {
                $lines[$key] = [
                    'product_id' => (int) $item->product_id,
                    'product_variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
                    'quantity' => 0.0,
                ];
            }

            $lines[$key]['quantity'] += (float) $item->quantity;
        }

        return array_values($lines);
    }
}
