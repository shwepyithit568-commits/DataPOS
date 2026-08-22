<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\GoodsReceipt;
use App\POS\Models\GoodsReceiptItem;
use App\POS\Models\PurchaseOrder;
use App\POS\Models\PurchaseReturn;
use App\POS\Models\PurchaseReturnItem;
use App\POS\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Purchase order lifecycle (alinthit_pos style — Phase 4 early build).
 *
 * Flow:  pending → ordered → received | cancelled
 * Payment: unpaid → partial → paid
 *
 * - create(): saves the PO as pending (draft) — no stock change.
 * - markOrdered(): transitions pending → ordered — no stock change.
 * - receive(): transitions ordered → received AND posts purchase_received
 *   ledger movements via the existing GoodsReceipt infrastructure. On receive,
 *   the PO's remaining balance is seeded from total_cost and added to the
 *   supplier's total_credit (unless paid in full up front).
 * - cancel(): transitions pending or ordered → cancelled — no stock change.
 * - applyPayment(): records a payment against the PO, updates payment_status,
 *   and decrements the supplier's outstanding balance.
 * - applyPaymentFifo(): distributes a lump payment across the supplier's oldest
 *   unpaid POs until exhausted (AlinThit "makeGeneralSupplierPayment" parity).
 *
 * The PO is a planning document; only receiving it increases stock (SoT §11.5).
 * The receipt document + movements are created in ONE transaction for atomicity.
 */
class PurchaseOrderService
{
    public function __construct(
        protected InventoryService $inventory,
        protected StoreLocationService $storeLocations,
        protected GoodsReceiptService $receipts,
    ) {
    }

    /* ------------------------------------------------------------------ */
    /*  Create (pending)                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Create a new purchase order in 'pending' status.
     *
     * @param  array<int, array{product_id:int, product_variant_id?:int|null, quantity:string, unit_cost:string}>  $items
     * @param  array{payment_status?:string, paid_amount?:string}  $payment
     */
    public function create(
        Store $store,
        array $items,
        ?int $supplierId = null,
        ?string $reference = null,
        ?string $notes = null,
        User $actor = null,
        array $payment = [],
    ): PurchaseOrder {
        if (empty($items)) {
            throw new InventoryException('A purchase order needs at least one line.');
        }

        $normalized = [];
        $totalQuantity = '0';
        $totalCost = '0';

        foreach ($items as $i => $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $variantId = ! empty($line['product_variant_id']) ? (int) $line['product_variant_id'] : null;
            $quantity = (string) ($line['quantity'] ?? '0');
            $unitCost = (string) ($line['unit_cost'] ?? '0');

            if ($productId <= 0) {
                throw new InventoryException('Line ' . ($i + 1) . ': choose a product.');
            }
            if (bccomp($quantity, '0', 3) <= 0) {
                throw new InventoryException('Line ' . ($i + 1) . ': quantity must be greater than zero.');
            }
            if (bccomp($unitCost, '0', 4) < 0) {
                throw new InventoryException('Line ' . ($i + 1) . ': unit cost cannot be negative.');
            }

            $product = Product::find($productId);
            if (! $product || (int) $product->store_id !== (int) $store->id) {
                throw new InventoryException('Line ' . ($i + 1) . ': product does not belong to this store.');
            }

            $lineTotal = bcmul($quantity, $unitCost, 2);
            $totalQuantity = bcadd($totalQuantity, $quantity, 3);
            $totalCost = bcadd($totalCost, $lineTotal, 2);

            $key = $productId . ':' . ($variantId ?? '0');
            if (isset($normalized[$key])) {
                $normalized[$key]['quantity'] = bcadd($normalized[$key]['quantity'], $quantity, 3);
                $normalized[$key]['line_total'] = bcadd($normalized[$key]['line_total'], $lineTotal, 2);
            } else {
                $normalized[$key] = [
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ];
            }
        }

        $normalized = array_values($normalized);

        // Determine payment posture at creation (cash-on-delivery vs credit).
        $paymentStatus = $payment['payment_status'] ?? PurchaseOrder::PAYMENT_UNPAID;
        $paidUpFront = bccomp((string) ($payment['paid_amount'] ?? '0'), '0', 2) > 0;
        if ($paidUpFront && $paymentStatus === PurchaseOrder::PAYMENT_UNPAID) {
            $paymentStatus = PurchaseOrder::PAYMENT_PARTIAL;
        }
        $paidAmount = $paidUpFront ? bcadd((string) ($payment['paid_amount'] ?? '0'), '0', 2) : '0';
        if (bccomp($paidAmount, $totalCost, 2) >= 0) {
            $paidAmount = $totalCost;
            $paymentStatus = PurchaseOrder::PAYMENT_PAID;
        }
        $remainingBalance = bcsub($totalCost, $paidAmount, 2);

        return DB::transaction(function () use ($store, $normalized, $totalQuantity, $totalCost, $supplierId, $reference, $notes, $actor, $paymentStatus, $paidAmount, $remainingBalance) {
            $po = PurchaseOrder::create([
                'store_id' => $store->id,
                'branch_id' => $this->storeLocations->defaultBranch($store)->id,
                'warehouse_id' => $this->storeLocations->defaultWarehouse($store)->id,
                'supplier_id' => $supplierId,
                'po_number' => $this->nextPoNumber($store),
                'status' => 'pending',
                'payment_status' => $paymentStatus,
                'total_quantity' => $totalQuantity,
                'total_cost' => $totalCost,
                'paid_amount' => $paidAmount,
                'remaining_balance' => $remainingBalance,
                'reference' => trim((string) $reference) !== '' ? $reference : null,
                'notes' => trim((string) $notes) !== '' ? $notes : null,
                'created_by' => $actor?->id,
            ]);

            foreach ($normalized as $line) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'store_id' => $store->id,
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'],
                    'quantity' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'line_total' => $line['line_total'],
                ]);
            }

            // Paid-up-front at creation: immediately credit the supplier.
            if ($po->supplier_id && bccomp($paidAmount, '0', 2) > 0) {
                $po->supplier->increment('total_repaid', $paidAmount);
            }

            AuditLog::write(
                storeId: $store->id,
                action: 'purchase_order_created',
                entityType: 'purchase_order',
                entityId: $po->id,
                metadata: [
                    'po_number' => $po->po_number,
                    'total_cost' => $totalCost,
                    'paid_amount' => $paidAmount,
                    'payment_status' => $paymentStatus,
                    'lines' => count($normalized),
                ],
                actorId: $actor?->id,
            );

            return $po->load(['items.product', 'supplier', 'createdBy']);
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Status transitions                                                  */
    /* ------------------------------------------------------------------ */

    /** pending → ordered: confirms the PO is placed with the supplier. */
    public function markOrdered(PurchaseOrder $po, User $actor): PurchaseOrder
    {
        if (! $po->isPending()) {
            throw new InventoryException("PO {$po->po_number} cannot be ordered (status: {$po->status}).");
        }

        $po->update([
            'status' => 'ordered',
            'ordered_at' => now(),
        ]);

        AuditLog::write(
            storeId: $po->store_id,
            action: 'purchase_order_ordered',
            entityType: 'purchase_order',
            entityId: $po->id,
            metadata: ['po_number' => $po->po_number],
            actorId: $actor->id,
        );

        return $po->fresh(['items.product', 'supplier', 'createdBy']);
    }

    /**
     * ordered → received: goods arrive and stock is posted.
     *
     * Creates a GoodsReceipt + purchase_received ledger movements in the SAME
     * transaction. On receive, the PO's outstanding balance is added to the
     * supplier's total_credit (credit increases when goods arrive on credit).
     *
     * Also refreshes each Product.purchase_cost using a weighted-average of
     * the existing on-hand stock value plus the received items value, so the
     * "default cost" shown in the PO builder / costing stays up-to-date.
     *
     * @return array{po: PurchaseOrder, receipt: GoodsReceipt}
     */
    public function receive(PurchaseOrder $po, User $actor): array
    {
        if (! $po->isOrdered()) {
            throw new InventoryException("PO {$po->po_number} must be in 'ordered' status to receive (current: {$po->status}).");
        }

        $store = Store::findOrFail($po->store_id);
        $items = $po->items()->get();

        if ($items->isEmpty()) {
            throw new InventoryException("PO {$po->po_number} has no items to receive.");
        }

        // Snapshot on-hand inventory qty BEFORE the receipt posts stock,
        // so we can compute weighted-average purchase_cost per product.
        $balanceBefore = [];
        foreach ($items as $item) {
            $key = (int) $item->product_id;
            if (! isset($balanceBefore[$key])) {
                $qty = \App\POS\Models\InventoryBalance::where('store_id', $store->id)
                    ->where('product_id', $key)
                    ->sum('quantity');
                $balanceBefore[$key] = bcadd((string) $qty, '0', 3);
            }
        }

        // Build the items array for the GoodsReceiptService.
        $receiptItems = $items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => (string) $item->quantity,
            'unit_cost' => (string) $item->unit_cost,
        ])->toArray();

        // Atomically: update PO status + create receipt (receipt posts ledger).
        $receipt = DB::transaction(function () use ($po, $store, $receiptItems, $items, $balanceBefore, $actor) {
            $receipt = $this->receipts->create(
                $store,
                $receiptItems,
                $po->reference,
                "PO {$po->po_number}" . ($po->notes ? " — {$po->notes}" : ''),
                $actor,
            );

            $po->update([
                'status' => 'received',
                'received_at' => now(),
            ]);

            // Outstanding balance becomes supplier debt on receipt.
            if ($po->supplier_id && bccomp((string) $po->remaining_balance, '0', 2) > 0) {
                $po->supplier->increment('total_credit', $po->remaining_balance);
            }

            // Refresh product.purchase_cost using weighted-average costing.
            // Formula:
            //   new_cost = (old_cost * old_qty + received_cost * received_qty)
            //              / (old_qty + received_qty)
            // Aggregate by product (variants contribute to their parent's pool).
            $pool = [];
            foreach ($items as $item) {
                $pid = (int) $item->product_id;
                if (! isset($pool[$pid])) {
                    $pool[$pid] = ['received_qty' => '0', 'received_value' => '0'];
                }
                $pool[$pid]['received_qty'] = bcadd($pool[$pid]['received_qty'], (string) $item->quantity, 3);
                $pool[$pid]['received_value'] = bcadd(
                    $pool[$pid]['received_value'],
                    bcmul((string) $item->quantity, (string) $item->unit_cost, 2),
                    2
                );
            }

            foreach ($pool as $pid => $agg) {
                $product = Product::find($pid);
                if (! $product || (int) $product->store_id !== (int) $store->id) {
                    continue;
                }
                $oldQty = $balanceBefore[$pid] ?? '0';
                $oldCost = (string) ($product->purchase_cost ?? '0');
                $oldValue = bcmul($oldQty, $oldCost, 2);
                $newQty = bcadd($oldQty, $agg['received_qty'], 3);

                if (bccomp($newQty, '0', 3) > 0) {
                    $newValue = bcadd($oldValue, $agg['received_value'], 2);
                    $newCost = bcdiv($newValue, $newQty, 4);
                    $product->update(['purchase_cost' => $newCost]);
                } else {
                    // Nothing on hand before + nothing received — just take the PO unit cost.
                    $unitCost = bcdiv($agg['received_value'], $agg['received_qty'] ?: '1', 4);
                    $product->update(['purchase_cost' => $unitCost]);
                }
            }

            AuditLog::write(
                storeId: $po->store_id,
                action: 'purchase_order_received',
                entityType: 'purchase_order',
                entityId: $po->id,
                metadata: [
                    'po_number' => $po->po_number,
                    'receipt_number' => $receipt->receipt_number,
                    'total_cost' => (string) $po->total_cost,
                    'remaining_balance' => (string) $po->remaining_balance,
                ],
                actorId: $actor->id,
            );

            return $receipt;
        });

        return [
            'po' => $po->fresh(['items.product', 'supplier', 'createdBy']),
            'receipt' => $receipt->load(['items.product', 'postedBy']),
        ];
    }

    /** pending | ordered → cancelled: PO is voided. No stock change. */
    public function cancel(PurchaseOrder $po, User $actor): PurchaseOrder
    {
        if ($po->isReceived()) {
            throw new InventoryException("PO {$po->po_number} is already received and cannot be cancelled — use a purchase return instead.");
        }
        if ($po->isCancelled()) {
            throw new InventoryException("PO {$po->po_number} is already cancelled.");
        }

        // Reverse any up-front payment credited to the supplier.
        if ($po->supplier_id && bccomp((string) $po->paid_amount, '0', 2) > 0) {
            $po->supplier->decrement('total_repaid', $po->paid_amount);
        }

        $po->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        AuditLog::write(
            storeId: $po->store_id,
            action: 'purchase_order_cancelled',
            entityType: 'purchase_order',
            entityId: $po->id,
            metadata: ['po_number' => $po->po_number],
            actorId: $actor->id,
        );

        return $po->fresh(['items.product', 'supplier', 'createdBy']);
    }

    /* ------------------------------------------------------------------ */
    /* ------------------------------------------------------------------ */
    /*  Purchase Returns                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Process a purchase return for a received PO.
     *
     * Reverses stock (purchase_returned movements), adjusts supplier credit,
     * and recalculates PO payment status. Supports partial returns (specific
     * items and quantities).
     *
     * @param array<int, array{product_id:int, product_variant_id?:int|null, quantity:string}> $returnItems
     */
    public function returnItems(PurchaseOrder $po, array $returnItems, string $reason, User $actor): array
    {
        if (! $po->isReceived()) {
            throw new InventoryException("PO {$po->po_number} must be in 'received' status to process returns (current: {$po->status}).");
        }

        if (empty($returnItems)) {
            throw new InventoryException('At least one item must be returned.');
        }

        $store = Store::findOrFail($po->store_id);

        // Validate return quantities against what was received.
        $poItems = $po->items()->get();
        $normalized = [];
        $totalQuantity = '0';
        $totalCost = '0';

        foreach ($returnItems as $i => $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $variantId = ! empty($line['product_variant_id']) ? (int) $line['product_variant_id'] : null;
            $returnQty = (string) ($line['quantity'] ?? '0');

            if (bccomp($returnQty, '0', 3) <= 0) {
                throw new InventoryException("Line " . ($i + 1) . ": return quantity must be greater than zero.");
            }

            // Find the matching PO item.
            $poItem = $poItems->first(fn ($item) =>
                (int) $item->product_id === $productId
                && (int) ($item->product_variant_id ?? 0) === (int) ($variantId ?? 0)
            );

            if (! $poItem) {
                throw new InventoryException("Line " . ($i + 1) . ": product not found on this PO.");
            }

            // Calculate remaining returnable quantity (original - already returned).
            $alreadyReturned = PurchaseReturnItem::where('store_id', $store->id)
                ->whereHas('purchaseReturn', fn ($q) => $q->where('purchase_order_id', $po->id))
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->sum('quantity');

            $returnable = bcsub((string) $poItem->quantity, (string) $alreadyReturned, 3);

            if (bccomp($returnQty, $returnable, 3) > 0) {
                throw new InventoryException(
                    "Line " . ($i + 1) . ": cannot return {$returnQty} — only {$returnable} available to return."
                );
            }

            $unitCost = (string) $poItem->unit_cost;
            $lineTotal = bcmul($returnQty, $unitCost, 2);
            $totalQuantity = bcadd($totalQuantity, $returnQty, 3);
            $totalCost = bcadd($totalCost, $lineTotal, 2);

            $key = $productId . ':' . ($variantId ?? '0');
            if (isset($normalized[$key])) {
                $normalized[$key]['quantity'] = bcadd($normalized[$key]['quantity'], $returnQty, 3);
                $normalized[$key]['line_total'] = bcadd($normalized[$key]['line_total'], $lineTotal, 2);
            } else {
                $normalized[$key] = [
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'quantity' => $returnQty,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ];
            }
        }

        $normalized = array_values($normalized);

        // Create the return record + inventory movements in one transaction.
        $return = DB::transaction(function () use ($po, $store, $normalized, $totalQuantity, $totalCost, $reason, $actor) {
            $returnNumber = $this->nextReturnNumber($store);

            $return = PurchaseReturn::create([
                'store_id' => $store->id,
                'purchase_order_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'return_number' => $returnNumber,
                'total_quantity' => $totalQuantity,
                'total_cost' => $totalCost,
                'reason' => $reason ?: null,
                'created_by' => $actor->id,
                'returned_at' => now(),
            ]);

            foreach ($normalized as $i => $line) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'store_id' => $store->id,
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'],
                    'quantity' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'line_total' => $line['line_total'],
                ]);

                // Post outbound inventory movement (stock decreases).
                $this->inventory->postMovement([
                    'store' => $store,
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'],
                    'movement_type' => 'purchase_returned',
                    'quantity_delta' => '-' . $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'source_type' => 'purchase_return',
                    'source_id' => $return->id,
                    'occurred_at' => now(),
                ], [
                    'client_transaction_id' => "pr:{$return->id}:{$i}",
                ]);
            }

            // Recalculate PO totals.
            $newTotalCost = bcsub((string) $po->total_cost, $totalCost, 2);
            $newTotalQty = bcsub((string) $po->total_quantity, $totalQuantity, 3);

            // Recalculate payment after return.
            $newPaidAmount = min((string) $po->paid_amount, $newTotalCost);
            $newRemaining = bcsub($newTotalCost, $newPaidAmount, 2);

            $newPaymentStatus = 'unpaid';
            if (bccomp($newRemaining, '0', 2) <= 0) {
                $newPaymentStatus = 'paid';
            } elseif (bccomp($newPaidAmount, '0', 2) > 0) {
                $newPaymentStatus = 'partial';
            }

            // If fully returned, mark as returned.
            $newStatus = $po->status;
            if (bccomp($newTotalQty, '0', 3) <= 0) {
                $newStatus = PurchaseOrder::STATUS_RETURNED;
            }

            $po->update([
                'total_cost' => $newTotalCost,
                'total_quantity' => $newTotalQty,
                'paid_amount' => $newPaidAmount,
                'remaining_balance' => $newRemaining,
                'payment_status' => $newPaymentStatus,
                'status' => $newStatus,
            ]);

            // Adjust supplier credit.
            if ($po->supplier_id) {
                $po->supplier->decrement('total_credit', $totalCost);

                // Recalculate repaid (min of paid_amount and total_credit).
                $supplier = $po->supplier->fresh();
                $newRepaid = min((string) $supplier->total_repaid, (string) $supplier->total_credit);
                if (bccomp($newRepaid, '0', 2) < 0) {
                    $newRepaid = '0';
                }
                $po->supplier->update(['total_repaid' => $newRepaid]);
            }

            AuditLog::write(
                storeId: $po->store_id,
                action: 'purchase_order_returned',
                entityType: 'purchase_return',
                entityId: $return->id,
                metadata: [
                    'return_number' => $returnNumber,
                    'po_number' => $po->po_number,
                    'total_cost' => $totalCost,
                    'total_quantity' => $totalQuantity,
                ],
                actorId: $actor->id,
            );

            return $return->load(['items.product', 'createdBy']);
        });

        return [
            'return' => $return,
            'po' => $po->fresh(['items.product', 'supplier', 'createdBy']),
        ];
    }

    private function nextReturnNumber(Store $store): string
    {
        $prefix = 'PR-' . now()->format('Ymd') . '-';
        $seq = PurchaseReturn::query()
            ->where('store_id', $store->id)
            ->where('return_number', 'like', $prefix . '%')
            ->count() + 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /*  Payments (AlinThit POS parity)                                       */
    /* ------------------------------------------------------------------ */

    /**
     * Record a payment against a specific purchase order.
     *
     * Updates paid_amount / remaining_balance / payment_status and decrements
     * the supplier's outstanding total_credit (increasing total_repaid).
     *
     * @param  array{amount:string, reference?:string, paid_at?:string}  $payment
     */
    public function applyPayment(PurchaseOrder $po, array $payment, User $actor): PurchaseOrder
    {
        if (! $po->isReceived()) {
            throw new InventoryException("PO {$po->po_number} must be received before payments are applied.");
        }

        $amount = bcadd((string) ($payment['amount'] ?? '0'), '0', 2);
        if (bccomp($amount, '0', 2) <= 0) {
            throw new InventoryException('Payment amount must be greater than zero.');
        }
        if (bccomp($amount, (string) $po->remaining_balance, 2) > 0) {
            throw new InventoryException("Payment amount exceeds the remaining balance of {$po->remaining_balance}.");
        }

        return DB::transaction(function () use ($po, $payment, $actor, $amount) {
            $po->increment('paid_amount', $amount);
            $po->decrement('remaining_balance', $amount);

            $newStatus = bccomp((string) $po->remaining_balance, '0', 2) === 0
                ? PurchaseOrder::PAYMENT_PAID
                : PurchaseOrder::PAYMENT_PARTIAL;
            $po->update(['payment_status' => $newStatus]);

            if ($po->supplier_id) {
                $po->supplier->decrement('total_credit', $amount);
                $po->supplier->increment('total_repaid', $amount);
            }

            AuditLog::write(
                storeId: $po->store_id,
                action: 'purchase_order_payment',
                entityType: 'purchase_order',
                entityId: $po->id,
                metadata: [
                    'po_number' => $po->po_number,
                    'amount' => $amount,
                    'remaining_balance' => (string) $po->remaining_balance,
                    'reference' => $payment['reference'] ?? null,
                ],
                actorId: $actor->id,
            );

            return $po->fresh(['items.product', 'supplier', 'createdBy']);
        });
    }

    /**
     * Distribute a lump payment across the supplier's oldest unpaid POs (FIFO),
     * exactly like AlinThit's makeGeneralSupplierPayment.
     *
     * @return array{applied: array<int, array{po:int, amount:string}>, remaining:string}
     */
    public function applyPaymentFifo(\App\Models\Supplier $supplier, string $amount, User $actor, ?string $reference = null): array
    {
        $amount = bcadd($amount, '0', 2);
        if (bccomp($amount, '0', 2) <= 0) {
            throw new InventoryException('Payment amount must be greater than zero.');
        }

        $remaining = $amount;
        $applied = [];

        return DB::transaction(function () use ($supplier, $amount, $actor, $reference, &$applied, &$remaining) {
            $unpaid = $supplier->unpaidPurchaseOrders()
                ->where('status', PurchaseOrder::STATUS_RECEIVED)
                ->get();

            foreach ($unpaid as $po) {
                if (bccomp($remaining, '0', 2) === 0) {
                    break;
                }

                $toApply = bccomp($remaining, (string) $po->remaining_balance, 2) >= 0
                    ? (string) $po->remaining_balance
                    : $remaining;

                $po->increment('paid_amount', $toApply);
                $po->decrement('remaining_balance', $toApply);
                $po->update([
                    'payment_status' => bccomp((string) $po->remaining_balance, '0', 2) === 0
                        ? PurchaseOrder::PAYMENT_PAID
                        : PurchaseOrder::PAYMENT_PARTIAL,
                ]);

                $supplier->decrement('total_credit', $toApply);
                $supplier->increment('total_repaid', $toApply);

                $remaining = bcsub($remaining, $toApply, 2);
                $applied[] = ['po' => $po->id, 'amount' => $toApply];

                AuditLog::write(
                    storeId: $supplier->store_id,
                    action: 'purchase_order_payment',
                    entityType: 'purchase_order',
                    entityId: $po->id,
                    metadata: [
                        'po_number' => $po->po_number,
                        'amount' => $toApply,
                        'remaining_balance' => (string) $po->remaining_balance,
                        'supplier_id' => $supplier->id,
                        'general_payment' => true,
                        'reference' => $reference,
                    ],
                    actorId: $actor->id,
                );
            }

            return ['applied' => $applied, 'remaining' => $remaining];
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Queries                                                             */
    /* ------------------------------------------------------------------ */

    /** PO list for a store, optionally filtered by status. */
    public function listForStore(Store $store, ?string $status = null, int $limit = 50)
    {
        $query = PurchaseOrder::query()
            ->with(['items.product', 'supplier', 'createdBy'])
            ->where('store_id', $store->id);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->latest('created_at')->limit($limit)->get();
    }

    /** Single PO by ID, scoped to store. */
    public function findForStore(Store $store, int $id): ?PurchaseOrder
    {
        return PurchaseOrder::query()
            ->with(['items.product', 'items.variant', 'supplier', 'createdBy'])
            ->where('store_id', $store->id)
            ->find($id);
    }

    /* ------------------------------------------------------------------ */
    /*  PO number: PO-YYYYMMDD-#### (sequential per store per day)         */
    /* ------------------------------------------------------------------ */

    private function nextPoNumber(Store $store): string
    {
        $prefix = 'PO-' . now()->format('Ymd') . '-';
        $seq = PurchaseOrder::query()
            ->where('store_id', $store->id)
            ->where('po_number', 'like', $prefix . '%')
            ->count() + 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
