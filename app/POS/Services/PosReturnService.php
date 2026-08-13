<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CashierShift;
use App\POS\Models\PosReturn;
use App\POS\Models\PosReturnItem;
use App\POS\Models\PosReturnPayment;
use App\POS\Models\PosSale;
use App\POS\Models\PosSaleItem;
use Illuminate\Support\Facades\DB;

/**
 * POS sale returns / refunds (target-design §2.9 — posted → partially_refunded /
 * refunded; SoT §15.1 refund references its source transaction).
 *
 * post() is atomic: return document + refund number + item snapshots (original
 * COGS carried back) + `sales_return` ledger movements (+) + refund payments
 * (cash → drawer cash_refunds, credit → customer ledger) + sale status update +
 * audit record. A failed movement aborts the whole return.
 *
 * - The sale itself is never edited/deleted — it moves to partially_refunded
 *   or refunded by aggregate.
 * - Weighted average is NOT recalculated on a sales return (CostingService) —
 *   stock comes back at the original line cost.
 * - Idempotent via client_transaction_id (offline retries).
 */
class PosReturnService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CashierShiftService $shifts,
        private readonly CustomerDebtService $debts,
    ) {
    }

    /* ------------------------------------------------------------------ */
    /*  Refundable quantities (for the refund form)                        */
    /* ------------------------------------------------------------------ */

    /**
     * Quantity already returned per original sale line (posted returns only).
     *
     * @return array<int, string>  pos_sale_item_id => refunded quantity
     */
    public function refundedQuantities(Store $store, PosSale $sale): array
    {
        $rows = DB::table('pos_return_items')
            ->join('pos_returns', 'pos_returns.id', '=', 'pos_return_items.pos_return_id')
            ->where('pos_returns.store_id', $store->id)
            ->where('pos_returns.pos_sale_id', $sale->id)
            ->where('pos_returns.status', 'posted')
            ->groupBy('pos_return_items.pos_sale_item_id')
            ->selectRaw('pos_return_items.pos_sale_item_id, SUM(pos_return_items.quantity) AS qty')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row->pos_sale_item_id] = number_format((float) $row->qty, 3, '.', '');
        }

        return $out;
    }

    /**
     * Refund amount already returned against a sale's credit portion.
     */
    public function refundedCreditTotal(Store $store, PosSale $sale): string
    {
        $total = DB::table('pos_return_payments')
            ->join('pos_returns', 'pos_returns.id', '=', 'pos_return_payments.pos_return_id')
            ->where('pos_returns.store_id', $store->id)
            ->where('pos_returns.pos_sale_id', $sale->id)
            ->where('pos_returns.status', 'posted')
            ->where('pos_return_payments.method', 'credit')
            ->sum('pos_return_payments.amount');

        return number_format((float) $total, 2, '.', '');
    }

    /* ------------------------------------------------------------------ */
    /*  Posting (atomic)                                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Post a return/refund atomically.
     *
     * @param  array<int, array{pos_sale_item_id:int, quantity:string}>  $items
     * @param  array<int, array{method:string, amount:string}>  $refunds  cash | credit
     * @param  string|null  $clientTransactionId  idempotency key (offline retry)
     */
    public function post(
        Store $store,
        PosSale $sale,
        array $items,
        array $refunds,
        User $actor,
        ?CashierShift $shift = null,
        ?string $clientTransactionId = null,
    ): PosReturn {
        // Idempotent retry: a known client transaction returns the existing
        // return BEFORE any validation (the sale may already be refunded).
        if ($clientTransactionId !== null) {
            $existing = PosReturn::query()
                ->where('store_id', $store->id)
                ->where('client_transaction_id', $clientTransactionId)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        if ((int) $sale->store_id !== (int) $store->id) {
            throw new InventoryException('The sale does not belong to this store.');
        }
        if (! $sale->isPosted()) {
            throw new InventoryException('Only posted sales can be refunded.');
        }
        if ($sale->status === 'refunded') {
            throw new InventoryException("Sale {$sale->receipt_number} is already fully refunded.");
        }

        if ($items === []) {
            throw new InventoryException('Select at least one item to return.');
        }

        // Already-returned quantities per line (prevents double-returning).
        $already = $this->refundedQuantities($store, $sale);

        $sale->loadMissing(['items', 'payments']);
        $resolved = [];
        $returnTotal = '0';
        foreach ($items as $item) {
            $saleItem = $sale->items->firstWhere('id', (int) $item['pos_sale_item_id']);
            if (! $saleItem) {
                throw new InventoryException('A return line does not belong to this sale.');
            }

            $quantity = (string) $item['quantity'];
            if (bccomp($quantity, '0', 3) <= 0) {
                throw new InventoryException("Return quantity for '{$saleItem->product_name}' must be positive.");
            }

            $refundable = bcsub((string) $saleItem->quantity, $already[$saleItem->id] ?? '0', 3);
            if (bccomp($quantity, $refundable, 3) > 0) {
                throw new InventoryException(
                    "Cannot return more than the refundable quantity for '{$saleItem->product_name}' "
                    . "(refundable: " . rtrim(rtrim($refundable, '0'), '.') . ').'
                );
            }

            $lineTotal = bcmul((string) $saleItem->unit_price, $quantity, 2);
            $returnTotal = bcadd($returnTotal, $lineTotal, 2);

            $resolved[] = [
                'sale_item' => $saleItem,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
            ];
        }

        // Refund methods: cash (out of drawer) and/or credit (reduce receivable).
        $refunds = array_values(array_filter($refunds, fn ($r) => bccomp((string) ($r['amount'] ?? '0'), '0', 2) > 0));
        if ($refunds === []) {
            throw new InventoryException('At least one refund payment is required.');
        }

        $refundTotal = '0';
        $cashRefund = '0';
        $creditRefund = '0';
        foreach ($refunds as $refund) {
            $method = (string) $refund['method'];
            if (! in_array($method, ['cash', 'credit'], true)) {
                throw new InventoryException("Unknown refund method '{$method}'.");
            }
            $amount = (string) $refund['amount'];
            if (bccomp($amount, '0', 2) <= 0) {
                throw new InventoryException('Refund amounts must be positive.');
            }

            if ($method === 'cash') {
                if (! $shift?->isOpen() || (int) $shift->store_id !== (int) $store->id) {
                    throw new InventoryException('An open cashier shift is required to refund cash.');
                }
                $cashRefund = bcadd($cashRefund, $amount, 2);
            } else {
                $saleCredit = (string) ($sale->payments->firstWhere('method', 'credit')?->amount ?? '0');
                $creditLeft = bcsub($saleCredit, $this->refundedCreditTotal($store, $sale), 2);
                if (bccomp($amount, $creditLeft, 2) > 0) {
                    throw new InventoryException(
                        'Credit refund exceeds the sale\'s remaining credit portion (Ks '
                        . rtrim(rtrim($creditLeft, '0'), '.') . ').'
                    );
                }
                $creditRefund = bcadd($creditRefund, $amount, 2);
            }

            $refundTotal = bcadd($refundTotal, $amount, 2);
        }

        if (bccomp($refundTotal, $returnTotal, 2) !== 0) {
            throw new InventoryException(
                'Refund payments must equal the returned value (Ks ' . rtrim(rtrim($returnTotal, '0'), '.') . ').'
            );
        }

        $warehouseId = $this->inventory->defaultWarehouseId($store->id);

        return DB::transaction(function () use (
            $store, $sale, $resolved, $refunds, $actor, $shift, $returnTotal,
            $cashRefund, $creditRefund, $warehouseId, $clientTransactionId,
        ) {
            $refund = PosReturn::create([
                'store_id' => $store->id,
                'branch_id' => $shift?->branch_id,
                'cashier_shift_id' => $shift?->id,
                'pos_sale_id' => $sale->id,
                'cashier_id' => $actor->id,
                'customer_id' => $sale->customer_id,
                'refund_number' => $this->nextRefundNumber($store),
                'status' => 'posted',
                'total' => $returnTotal,
                'posted_at' => now(),
                'created_by' => $actor->id,
                'client_transaction_id' => $clientTransactionId,
            ]);

            foreach ($resolved as $i => $line) {
                /** @var PosSaleItem $saleItem */
                $saleItem = $line['sale_item'];

                PosReturnItem::create([
                    'pos_return_id' => $refund->id,
                    'pos_sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'product_variant_id' => $saleItem->product_variant_id,
                    'product_name' => $saleItem->product_name,
                    'sku' => $saleItem->sku,
                    'unit_price' => $saleItem->unit_price,
                    'quantity' => $line['quantity'],
                    'unit_cost' => $saleItem->unit_cost,
                    'line_total' => $line['line_total'],
                ]);

                // Stock comes back at the original line cost; the weighted
                // average is not recalculated (CostingService rule).
                $this->inventory->postMovement([
                    'store_id' => $store->id,
                    'product_id' => $saleItem->product_id,
                    'product_variant_id' => $saleItem->product_variant_id,
                    'warehouse_id' => $warehouseId,
                    'movement_type' => 'sales_return',
                    'quantity_delta' => $line['quantity'],
                    'unit_cost' => $saleItem->unit_cost,
                    'source_type' => 'pos_return',
                    'source_id' => $refund->id,
                    'client_transaction_id' => "pos_return:{$refund->id}:{$i}",
                    'occurred_at' => now(),
                    'posted_by' => $actor->id,
                ]);
            }

            foreach ($refunds as $row) {
                PosReturnPayment::create([
                    'pos_return_id' => $refund->id,
                    'method' => $row['method'],
                    'amount' => $row['amount'],
                    'created_by' => $actor->id,
                ]);
            }

            // Cash refunds leave the drawer; credit refunds reduce the receivable.
            if (bccomp($cashRefund, '0', 2) > 0) {
                $this->shifts->recordCashRefund($shift, $cashRefund);
            }
            if (bccomp($creditRefund, '0', 2) > 0) {
                $this->debts->recordSaleRefund(
                    store: $store,
                    customerId: $sale->customer_id,
                    returnId: $refund->id,
                    amount: $creditRefund,
                    actor: $actor,
                    clientTransactionId: "pos_return:{$refund->id}:debt",
                );
            }

            $this->updateSaleStatus($store, $sale);

            AuditLog::write(
                storeId: $store->id,
                action: 'pos_return_posted',
                entityType: 'pos_return',
                entityId: $refund->id,
                metadata: ['sale_id' => $sale->id, 'refund_number' => $refund->refund_number, 'total' => $returnTotal],
                actorId: $actor->id,
            );

            return $refund->load(['items', 'payments']);
        });
    }

    /**
     * Recompute the sale status from all posted returns: partially_refunded
     * until the returned total reaches the sale total, then refunded.
     */
    private function updateSaleStatus(Store $store, PosSale $sale): void
    {
        $refunded = DB::table('pos_returns')
            ->where('store_id', $store->id)
            ->where('pos_sale_id', $sale->id)
            ->where('status', 'posted')
            ->sum('total');

        $status = bccomp(number_format((float) $refunded, 2, '.', ''), (string) $sale->total, 2) >= 0
            ? 'refunded'
            : 'partially_refunded';

        $sale->update([
            'status' => $status,
            'refunded_at' => now(),
        ]);
    }

    /**
     * RET-YYYYMMDD-#### sequence per store.
     */
    private function nextRefundNumber(Store $store): string
    {
        $prefix = 'RET-' . now()->format('Ymd') . '-';
        $seq = PosReturn::query()
                ->where('store_id', $store->id)
                ->where('refund_number', 'like', $prefix . '%')
                ->count() + 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
