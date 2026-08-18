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
use App\POS\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Purchase order lifecycle (alinthit_pos style — Phase 4 early build).
 *
 * Flow:  pending → ordered → received | cancelled
 *
 * - create(): saves the PO as pending (draft) — no stock change.
 * - markOrdered(): transitions pending → ordered — no stock change.
 * - receive(): transitions ordered → received AND posts purchase_received
 *   ledger movements via the existing GoodsReceipt infrastructure.
 * - cancel(): transitions pending or ordered → cancelled — no stock change.
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
     */
    public function create(
        Store $store,
        array $items,
        ?int $supplierId = null,
        ?string $reference = null,
        ?string $notes = null,
        User $actor = null,
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

        return DB::transaction(function () use ($store, $normalized, $totalQuantity, $totalCost, $supplierId, $reference, $notes, $actor) {
            $po = PurchaseOrder::create([
                'store_id' => $store->id,
                'branch_id' => $this->storeLocations->defaultBranch($store)->id,
                'warehouse_id' => $this->storeLocations->defaultWarehouse($store)->id,
                'supplier_id' => $supplierId,
                'po_number' => $this->nextPoNumber($store),
                'status' => 'pending',
                'total_quantity' => $totalQuantity,
                'total_cost' => $totalCost,
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

            AuditLog::write(
                storeId: $store->id,
                action: 'purchase_order_created',
                entityType: 'purchase_order',
                entityId: $po->id,
                metadata: [
                    'po_number' => $po->po_number,
                    'total_cost' => $totalCost,
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
     * transaction. The goods receipt number is generated by the receipt service.
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

        // Build the items array for the GoodsReceiptService.
        $receiptItems = $items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => (string) $item->quantity,
            'unit_cost' => (string) $item->unit_cost,
        ])->toArray();

        // Atomically: update PO status + create receipt (receipt posts ledger).
        $receipt = DB::transaction(function () use ($po, $store, $receiptItems, $actor) {
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

            AuditLog::write(
                storeId: $po->store_id,
                action: 'purchase_order_received',
                entityType: 'purchase_order',
                entityId: $po->id,
                metadata: [
                    'po_number' => $po->po_number,
                    'receipt_number' => $receipt->receipt_number,
                    'total_cost' => (string) $po->total_cost,
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
