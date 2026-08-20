<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Supplier;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Supplier payables / debt management (AlinThit POS parity).
 *
 * Provides the "payables screen" data and payment operations:
 * - listSuppliersWithBalances: suppliers with outstanding debt
 * - getUnpaidOrders: all unpaid POs for a supplier (FIFO order)
 * - makeRepayment: pay a specific PO or apply general payment FIFO
 * - recalculateBalances: reconcile cached columns from PO data
 */
class SupplierDebtService
{
    public function __construct(
        protected PurchaseOrderService $poService,
    ) {
    }

    /* ------------------------------------------------------------------ */
    /*  Queries for Payables Screen                                         */
    /* ------------------------------------------------------------------ */

    /**
     * Get all suppliers for a store with their outstanding balances,
     * ordered by highest debt first (actionable first).
     *
     * @return Collection<int, array{supplier: Supplier, balance: string, unpaid_count: int}>
     */
    public function listSuppliersWithBalances(Store $store): Collection
    {
        return Supplier::query()
            ->where('store_id', $store->id)
            ->whereHas('purchaseOrders', fn ($q) => $q
                ->whereIn('payment_status', [PurchaseOrder::PAYMENT_UNPAID, PurchaseOrder::PAYMENT_PARTIAL])
                ->where('remaining_balance', '>', 0)
            )
            ->with([
                'purchaseOrders' => fn ($q) => $q
                    ->whereIn('payment_status', [PurchaseOrder::PAYMENT_UNPAID, PurchaseOrder::PAYMENT_PARTIAL])
                    ->where('remaining_balance', '>', 0)
                    ->orderBy('created_at', 'asc')
            ])
            ->get()
            ->map(function (Supplier $s) {
                $unpaid = $s->purchaseOrders;
                $totalOwed = $unpaid->sum(fn ($po) => (string) $po->remaining_balance);

                return [
                    'supplier' => $s,
                    'balance' => $totalOwed ?: '0',
                    'unpaid_count' => $unpaid->count(),
                    'oldest_unpaid_date' => $unpaid->first()?->created_at,
                ];
            })
            ->sortByDesc('balance')
            ->values();
    }

    /**
     * Get all unpaid purchase orders for a supplier, ordered oldest-first (FIFO).
     *
     * @return Collection<int, PurchaseOrder>
     */
    public function getUnpaidOrders(Supplier $supplier): Collection
    {
        return $supplier->unpaidPurchaseOrders()
            ->where('status', PurchaseOrder::STATUS_RECEIVED)
            ->with(['items.product', 'supplier'])
            ->get();
    }

    /**
     * Get payment history for a supplier (all payments applied to their POs).
     */
    public function getPaymentHistory(Supplier $supplier, int $limit = 50): Collection
    {
        return AuditLog::query()
            ->where('store_id', $supplier->store_id)
            ->where('action', 'purchase_order_payment')
            ->whereRaw("json_extract(metadata, '$.supplier_id') = ?", [$supplier->id])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /* ------------------------------------------------------------------ */
    /*  Payment Operations                                                  */
    /* ------------------------------------------------------------------ */

    /**
     * Pay a specific purchase order (full or partial).
     *
     * @param  array{amount:string, reference?:string, paid_at?:string}  $payment
     */
    public function paySpecificPurchaseOrder(PurchaseOrder $po, array $payment, User $actor): PurchaseOrder
    {
        return $this->poService->applyPayment($po, $payment, $actor);
    }

    /**
     * Apply a general payment across the supplier's unpaid POs (FIFO).
     * This is the "makeGeneralSupplierPayment" equivalent from AlinThit.
     *
     * @return array{applied: array<int, array{po:int, amount:string}>, remaining:string}
     */
    public function paySupplierGeneral(Supplier $supplier, string $amount, User $actor, ?string $reference = null): array
    {
        return $this->poService->applyPaymentFifo($supplier, $amount, $actor, $reference);
    }

    /* ------------------------------------------------------------------ */
    /*  Reconciliation                                                      */
    /* ------------------------------------------------------------------ */

    /**
     * Recalculate a supplier's total_credit and total_repaid from their POs.
     * Use after data imports or if balances drift.
     */
    public function recalculateSupplierBalances(Supplier $supplier): void
    {
        $credit = $supplier->purchaseOrders()
            ->where('payment_status', '!=', PurchaseOrder::PAYMENT_PAID)
            ->sum('remaining_balance');

        $repaid = $supplier->purchaseOrders()->sum('paid_amount');

        $supplier->update([
            'total_credit' => $credit ?: 0,
            'total_repaid' => $repaid ?: 0,
        ]);

        AuditLog::write(
            storeId: $supplier->store_id,
            action: 'supplier_balances_recalculated',
            entityType: 'supplier',
            entityId: $supplier->id,
            metadata: [
                'supplier_name' => $supplier->name,
                'total_credit' => (string) $credit,
                'total_repaid' => (string) $repaid,
            ],
        );
    }

    /**
     * Recalculate all suppliers for a store (batch operation).
     */
    public function recalculateAllSuppliers(Store $store): void
    {
        Supplier::where('store_id', $store->id)->chunkById(100, function ($suppliers) {
            foreach ($suppliers as $supplier) {
                $this->recalculateSupplierBalances($supplier);
            }
        });
    }
}