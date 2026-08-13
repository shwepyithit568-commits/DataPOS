<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\OpeningStockRequest;
use App\POS\Models\OpeningStockRequestItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Opening stock with manager review (MVP Phase 2 — target-design §2.10).
 *
 * - create(): a PENDING request — no ledger impact yet. The manager reviews
 *   the lines (the UI shows each product's current on-hand) and approves or
 *   rejects.
 * - approve(): manager-only, atomic — `opening_balance` ledger movements
 *   post one per line at the entered unit cost (CostingService sets the
 *   initial weighted average, SoT §6), the request becomes immutable, and
 *   the whole post is idempotent via client_transaction_id.
 * - reject(): manager-only, pending → rejected (a new request can resubmit).
 */
class OpeningStockService
{
    public function __construct(
        protected InventoryService $inventory,
        protected StoreLocationService $storeLocations,
    ) {
    }

    /**
     * @param  array<int, array{product_id:int, product_variant_id?:int|null, quantity:string, unit_cost:string}>  $items
     */
    public function create(Store $store, array $items, ?string $notes, User $actor): OpeningStockRequest
    {
        $normalized = $this->normalizeItems($store, $items);

        return DB::transaction(function () use ($store, $normalized, $notes, $actor) {
            $request = OpeningStockRequest::create([
                'store_id' => $store->id,
                'branch_id' => $this->storeLocations->defaultBranch($store)->id,
                'warehouse_id' => $this->storeLocations->defaultWarehouse($store)->id,
                'request_number' => $this->nextRequestNumber($store),
                'status' => 'pending',
                'total_quantity' => $normalized['total_quantity'],
                'total_cost' => $normalized['total_cost'],
                'notes' => trim((string) $notes) !== '' ? $notes : null,
                'submitted_by' => $actor->id,
                'client_transaction_id' => 'osr:' . Str::uuid(),
            ]);

            foreach ($normalized['items'] as $line) {
                OpeningStockRequestItem::create([
                    'opening_stock_request_id' => $request->id,
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
                action: 'opening_stock_submitted',
                entityType: 'opening_stock_request',
                entityId: $request->id,
                metadata: ['request_number' => $request->request_number, 'total_cost' => $normalized['total_cost']],
                actorId: $actor->id,
            );

            return $request->load(['items.product']);
        });
    }

    /**
     * Manager approval — posts the opening_balance movements atomically.
     * Idempotent: the movements carry per-line client_transaction_ids derived
     * from the request's own id, so a retry never double-counts stock.
     */
    public function approve(Store $store, OpeningStockRequest $request, User $actor, ?string $reviewNotes = null): OpeningStockRequest
    {
        $this->assertOwned($store, $request);

        if (! $request->isPending()) {
            throw new InventoryException("Opening-stock request {$request->request_number} is already {$request->status}.");
        }

        // Retry the atomic transaction on a rare unique-collision race.
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($store, $request, $actor, $reviewNotes) {
                    $request = $request->fresh(['items']);

                    if (! $request->isPending()) {
                        throw new InventoryException("Opening-stock request {$request->request_number} is already {$request->status}.");
                    }

                    foreach ($request->items as $i => $item) {
                        $this->inventory->postMovement([
                            'store' => $store,
                            'product_id' => $item->product_id,
                            'product_variant_id' => $item->product_variant_id,
                            'movement_type' => 'opening_balance',
                            'quantity_delta' => (string) $item->quantity,
                            'unit_cost' => (string) $item->unit_cost,
                            'source_type' => 'opening_stock_request',
                            'source_id' => $request->id,
                            'occurred_at' => now(),
                        ], [
                            'client_transaction_id' => "osr:{$request->id}:{$i}",
                        ]);
                    }

                    $request->update([
                        'status' => 'approved',
                        'reviewed_by' => $actor->id,
                        'reviewed_at' => now(),
                        'approved_at' => now(),
                        'review_notes' => trim((string) $reviewNotes) !== '' ? $reviewNotes : null,
                    ]);

                    AuditLog::write(
                        storeId: $store->id,
                        action: 'opening_stock_approved',
                        entityType: 'opening_stock_request',
                        entityId: $request->id,
                        metadata: ['request_number' => $request->request_number, 'total_cost' => (string) $request->total_cost],
                        actorId: $actor->id,
                    );

                    return $request->fresh(['items.product']);
                });
            } catch (QueryException $e) {
                if ($attempt === 2 || ! $this->isUniqueViolation($e)) {
                    throw $e;
                }
            }
        }

        throw new InventoryException('Could not approve the opening-stock request — please retry.');
    }

    /** Manager rejection — pending → rejected. */
    public function reject(Store $store, OpeningStockRequest $request, User $actor, ?string $reason = null): OpeningStockRequest
    {
        $this->assertOwned($store, $request);

        if (! $request->isPending()) {
            throw new InventoryException("Opening-stock request {$request->request_number} is already {$request->status}.");
        }

        $request->update([
            'status' => 'rejected',
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_notes' => trim((string) $reason) !== '' ? $reason : null,
        ]);

        AuditLog::write(
            storeId: $store->id,
            action: 'opening_stock_rejected',
            entityType: 'opening_stock_request',
            entityId: $request->id,
            metadata: ['request_number' => $request->request_number],
            actorId: $actor->id,
        );

        return $request->fresh(['items.product']);
    }

    /** The store's requests, newest first. */
    public function recent(Store $store, int $limit = 20)
    {
        return OpeningStockRequest::query()
            ->with(['items.product', 'submittedBy', 'reviewedBy'])
            ->where('store_id', $store->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /* ------------------------------------------------------------------ */
    /*  Internals                                                          */
    /* ------------------------------------------------------------------ */

    /** @return array{items: array, total_quantity: string, total_cost: string} */
    private function normalizeItems(Store $store, array $items): array
    {
        if (empty($items)) {
            throw new InventoryException('An opening-stock request needs at least one line.');
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

        return ['items' => $normalized, 'total_quantity' => $totalQuantity, 'total_cost' => $totalCost];
    }

    private function assertOwned(Store $store, OpeningStockRequest $request): void
    {
        if ((int) $request->store_id !== (int) $store->id) {
            throw new InventoryException('This request does not belong to the store.');
        }
    }

    /** OSR-YYYYMMDD-#### sequence per store. */
    private function nextRequestNumber(Store $store): string
    {
        $prefix = 'OSR-' . now()->format('Ymd') . '-';
        $seq = OpeningStockRequest::query()
            ->where('store_id', $store->id)
            ->where('request_number', 'like', $prefix . '%')
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
