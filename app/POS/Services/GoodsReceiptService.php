<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\GoodsReceipt;
use App\POS\Models\GoodsReceiptItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Simple stock receiving (MVP Phase 2 — receive without a PO).
 *
 * create() posts, in ONE transaction: the goods receipt document (number
 * GRV-Ymd-#### unique per store) + line snapshots + `purchase_received`
 * ledger movements (+qty at the entered unit cost) + audit. The movements
 * go through InventoryService so the weighted-average CostingService
 * recalculates the average (SoT §6: new_avg = (Q·A + q·c) / (Q + q)).
 *
 * Idempotency: a client_transaction_id submitted twice returns the existing
 * receipt (no double stock). The receipt is immutable once posted —
 * corrections use ledger reversals (SoT §15.1).
 */
class GoodsReceiptService
{
    public function __construct(
        protected InventoryService $inventory,
        protected StoreLocationService $storeLocations,
    ) {
    }

    /**
     * @param  array<int, array{product_id:int, product_variant_id?:int|null, quantity:string, unit_cost:string}>  $items
     */
    public function create(
        Store $store,
        array $items,
        ?string $reference,
        ?string $notes,
        User $actor,
        ?string $clientTransactionId = null,
    ): GoodsReceipt {
        if (empty($items)) {
            throw new InventoryException('A goods receipt needs at least one line.');
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
                throw new InventoryException("Line " . ($i + 1) . ": choose a product.");
            }
            if (bccomp($quantity, '0', 3) <= 0) {
                throw new InventoryException("Line " . ($i + 1) . ": quantity must be greater than zero.");
            }
            if (bccomp($unitCost, '0', 4) < 0) {
                throw new InventoryException("Line " . ($i + 1) . ": unit cost cannot be negative.");
            }

            $product = Product::find($productId);
            if (! $product || (int) $product->store_id !== (int) $store->id) {
                throw new InventoryException("Line " . ($i + 1) . ": product does not belong to this store.");
            }

            $lineTotal = bcmul($quantity, $unitCost, 2);
            $totalQuantity = bcadd($totalQuantity, $quantity, 3);
            $totalCost = bcadd($totalCost, $lineTotal, 2);

            // Merge duplicate product lines (the ledger posts ONE movement per
            // source + product — its unique key is the double-post guard).
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

        $ctid = $clientTransactionId ?? ('gr:' . Str::uuid());

        // Idempotent retry: same (store, client_transaction_id) → the existing receipt.
        if ($clientTransactionId !== null) {
            $existing = GoodsReceipt::query()
                ->where('store_id', $store->id)
                ->where('client_transaction_id', $clientTransactionId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        // Retry the atomic transaction on the rare concurrent receipt-number
        // collision (unique store_id+receipt_number is the backstop).
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($store, $normalized, $totalQuantity, $totalCost, $reference, $notes, $actor, $ctid) {
                    $receipt = GoodsReceipt::create([
                        'store_id' => $store->id,
                        'branch_id' => $this->storeLocations->defaultBranch($store)->id,
                        'warehouse_id' => $this->storeLocations->defaultWarehouse($store)->id,
                        'receipt_number' => $this->nextReceiptNumber($store),
                        'status' => 'posted',
                        'total_quantity' => $totalQuantity,
                        'total_cost' => $totalCost,
                        'reference' => trim((string) $reference) !== '' ? $reference : null,
                        'notes' => trim((string) $notes) !== '' ? $notes : null,
                        'posted_at' => now(),
                        'created_by' => $actor->id,
                        'client_transaction_id' => $ctid,
                    ]);

                    foreach ($normalized as $i => $line) {
                        GoodsReceiptItem::create([
                            'goods_receipt_id' => $receipt->id,
                            'store_id' => $store->id,
                            'product_id' => $line['product_id'],
                            'product_variant_id' => $line['product_variant_id'],
                            'quantity' => $line['quantity'],
                            'unit_cost' => $line['unit_cost'],
                            'line_total' => $line['line_total'],
                        ]);

                        // Ledger movement — InventoryService/CostingService
                        // recalculates the weighted average (SoT §6).
                        $this->inventory->postMovement([
                            'store' => $store,
                            'product_id' => $line['product_id'],
                            'product_variant_id' => $line['product_variant_id'],
                            'movement_type' => 'purchase_received',
                            'quantity_delta' => $line['quantity'],
                            'unit_cost' => $line['unit_cost'],
                            'source_type' => 'goods_receipt',
                            'source_id' => $receipt->id,
                            'occurred_at' => now(),
                        ], [
                            'client_transaction_id' => "{$ctid}:{$i}",
                        ]);
                    }

                    AuditLog::write(
                        storeId: $store->id,
                        action: 'goods_receipt_posted',
                        entityType: 'goods_receipt',
                        entityId: $receipt->id,
                        metadata: [
                            'receipt_number' => $receipt->receipt_number,
                            'total_cost' => $totalCost,
                            'lines' => count($normalized),
                        ],
                        actorId: $actor->id,
                    );

                    return $receipt->load(['items.product', 'postedBy']);
                });
            } catch (QueryException $e) {
                if ($attempt === 2 || ! $this->isUniqueViolation($e)) {
                    throw $e;
                }
            }
        }

        throw new InventoryException('Could not post the goods receipt — please retry.');
    }

    /** Recent receipts for the store (for the receiving page list). */
    public function recent(Store $store, int $limit = 15)
    {
        return GoodsReceipt::query()
            ->with(['items.product', 'postedBy'])
            ->where('store_id', $store->id)
            ->latest('posted_at')
            ->limit($limit)
            ->get();
    }

    /** GRV-YYYYMMDD-#### sequence per store. */
    private function nextReceiptNumber(Store $store): string
    {
        $prefix = 'GRV-' . now()->format('Ymd') . '-';
        $seq = GoodsReceipt::query()
            ->where('store_id', $store->id)
            ->where('receipt_number', 'like', $prefix . '%')
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
