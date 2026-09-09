<?php

namespace App\POS\Services;

use App\Models\Store;
use App\Models\User;
use App\POS\Models\CashierShift;
use App\POS\Models\InventoryBalance;
use App\POS\Models\PosReturn;
use App\POS\Models\PosSale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Minimal Phase 2 reports (target-design §2.10) — all numbers DERIVED from
 * the authoritative sources, never hand-entered:
 *
 *  - Sales report: posted pos_sales (+ payments) in a date range, filterable
 *    by cashier; per-method totals included.
 *  - Cash drawer report: cashier shifts in a range (open + closed) with the
 *    drawer math (opening + cash_sales − cash_refunds + cash_in − cash_out),
 *    expected vs actual on closed shifts, and aggregates.
 *  - Stock-on-hand report: inventory_balances (the derived ledger cache,
 *    SoT §5) joined to products; value = quantity × weighted-average cost
 *    (SoT §6).
 */
class PosReportService
{
    /* ------------------------------------------------------------------ */
    /*  Sales                                                              */
    /* ------------------------------------------------------------------ */

    /**
     * @return array{sales: Collection, count:int, total:string, methods:array<string,string>}
     */
    public function salesReport(Store $store, Carbon $from, Carbon $to, ?int $cashierId = null): array
    {
        $query = PosSale::query()
            ->with(['items', 'cashier', 'customer', 'payments'])
            ->where('store_id', $store->id)
            ->whereNotNull('posted_at')
            ->whereBetween('posted_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        if ($cashierId) {
            $query->where('cashier_id', $cashierId);
        }

        $sales = $query->latest('posted_at')->get();

        $total = '0';
        $methods = [];
        foreach ($sales as $sale) {
            $total = bcadd($total, (string) $sale->total, 2);
            foreach ($sale->payments as $payment) {
                $methods[$payment->method] = bcadd(
                    $methods[$payment->method] ?? '0',
                    (string) $payment->amount,
                    2
                );
            }
        }

        return [
            'sales' => $sales,
            'count' => $sales->count(),
            'total' => $total,
            'methods' => $methods,
        ];
    }

    /**
     * Commercial Tax Report (ကုန်သွယ်လုပ်ငန်းခွန် အစီရင်ခံစာ) for Myanmar IRD.
     *
     * @return array{sales: Collection, count:int, total_sales:string, taxable_sales:string, exempt_sales:string, total_tax:string, net_sales:string}
     */
    public function taxReport(Store $store, Carbon $from, Carbon $to, ?int $cashierId = null): array
    {
        $query = PosSale::query()
            ->with(['items', 'cashier', 'customer'])
            ->where('store_id', $store->id)
            ->where('status', 'posted')
            ->whereNotNull('posted_at')
            ->whereBetween('posted_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        if ($cashierId) {
            $query->where('cashier_id', $cashierId);
        }

        $sales = $query->latest('posted_at')->get();

        $totalSales = '0';
        $taxableSales = '0';
        $exemptSales = '0';
        $totalTax = '0';

        foreach ($sales as $sale) {
            $totalSales = bcadd($totalSales, (string) $sale->total, 2);
            $totalTax = bcadd($totalTax, (string) $sale->tax, 2);

            $taxable = (string) $sale->taxable_amount;
            $exempt = (string) $sale->exempt_amount;

            if (bccomp($taxable, '0', 2) === 0 && bccomp($exempt, '0', 2) === 0) {
                if (bccomp((string) $sale->tax, '0', 2) > 0) {
                    $taxable = (string) $sale->subtotal;
                } else {
                    $exempt = (string) $sale->subtotal;
                }
            }

            $taxableSales = bcadd($taxableSales, $taxable, 2);
            $exemptSales = bcadd($exemptSales, $exempt, 2);
        }

        $netSales = bcsub($totalSales, $totalTax, 2);

        return [
            'sales' => $sales,
            'count' => $sales->count(),
            'total_sales' => $totalSales,
            'taxable_sales' => $taxableSales,
            'exempt_sales' => $exemptSales,
            'total_tax' => $totalTax,
            'net_sales' => $netSales,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Cash drawer                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * @return array{shifts: Collection, shift_count:int, opening_cash:string, cash_sales:string, cash_refunds:string, cash_in:string, cash_out:string, expected:string, actual:string, difference:string}
     */
    public function cashReport(Store $store, Carbon $from, Carbon $to): array
    {
        $shifts = CashierShift::query()
            ->with('cashier')
            ->where('store_id', $store->id)
            ->whereBetween('opened_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('opened_at')
            ->get();

        $sum = fn (string $col) => number_format((float) $shifts->sum($col), 2, '.', '');

        return [
            'shifts' => $shifts,
            'shift_count' => $shifts->count(),
            'opening_cash' => $sum('opening_cash'),
            'cash_sales' => $sum('cash_sales'),
            'cash_refunds' => $sum('cash_refunds'),
            'cash_in' => $sum('cash_in'),
            'cash_out' => $sum('cash_out'),
            'expected' => $sum('expected_closing_amount'),
            'actual' => $sum('actual_closing_amount'),
            'difference' => $sum('difference'),
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Stock on hand                                                      */
    /* ------------------------------------------------------------------ */

    /**
     * Stock-on-hand from the ledger cache. Value = qty × weighted-average
     * cost (bcmath). Optional search on SKU / product name.
     *
     * @return array{rows: Collection, total_value:string, total_units:string}
     */
    public function stockReport(Store $store, ?string $q = null): array
    {
        $query = InventoryBalance::query()
            ->with(['product.category', 'warehouse'])
            ->where('store_id', $store->id)
            ->where('warehouse_id', '!=', 0);

        if ($q !== null && trim($q) !== '') {
            $query->whereHas('product', function ($productQuery) use ($q) {
                $productQuery->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        $balances = $query->orderByDesc('quantity_on_hand')->get();

        $rows = $balances->map(function (InventoryBalance $balance) {
            $qty = (string) $balance->quantity_on_hand;
            $cost = (string) $balance->unit_cost_avg;

            return [
                'product' => $balance->product,
                'warehouse' => $balance->warehouse,
                'quantity_on_hand' => $qty,
                'unit_cost_avg' => $cost,
                'value' => bcmul($qty, $cost, 2),
            ];
        });

        $totalValue = '0';
        $totalUnits = '0';
        foreach ($rows as $row) {
            $totalValue = bcadd($totalValue, $row['value'], 2);
            $totalUnits = bcadd($totalUnits, $row['quantity_on_hand'], 3);
        }

        return [
            'rows' => $rows,
            'total_value' => $totalValue,
            'total_units' => $totalUnits,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Service Jobs & Repair Reports                                     */
    /* ------------------------------------------------------------------ */

    /**
     * Generate Service / Repair Jobs Report.
     */
    public function serviceJobsReport(Store $store, Carbon $from, Carbon $to, ?int $technicianId = null, ?string $status = null): array
    {
        $query = \App\POS\Models\ServiceJob::query()
            ->with(['technician', 'customer', 'items', 'payments'])
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        if ($technicianId) {
            $query->where('technician_id', $technicianId);
        }

        if ($status && in_array($status, \App\POS\Models\ServiceJob::STATUSES, true)) {
            $query->where('status', $status);
        }

        $jobs = $query->latest('created_at')->get();

        $totalEstimated = 0.0;
        $totalFinalCharge = 0.0;
        $totalPaid = 0.0;
        $totalPartsCost = 0.0;

        $statusCounts = [
            'received'          => 0,
            'diagnosing'        => 0,
            'awaiting_approval' => 0,
            'awaiting_parts'    => 0,
            'in_repair'         => 0,
            'ready'             => 0,
            'delivered'         => 0,
            'cancelled'         => 0,
            'unrepairable'      => 0,
        ];

        $techPerformance = [];

        foreach ($jobs as $job) {
            $final = (float) ($job->final_charge ?: $job->estimated_charge ?: 0);
            $paid = (float) $job->payments->sum('amount');
            $partsCost = (float) $job->items->where('type', 'part')->sum('cost');

            $totalEstimated += (float) ($job->estimated_charge ?? 0);
            $totalFinalCharge += $final;
            $totalPaid += $paid;
            $totalPartsCost += $partsCost;

            if (isset($statusCounts[$job->status])) {
                $statusCounts[$job->status]++;
            }

            // Technician aggregate
            $techId = (int) ($job->technician_id ?? 0);
            $techName = $job->technician?->name ?? 'Unassigned';
            if (!isset($techPerformance[$techId])) {
                $techPerformance[$techId] = [
                    'id'          => $techId,
                    'name'        => $techName,
                    'jobs_count'  => 0,
                    'completed'   => 0,
                    'revenue'     => 0.0,
                    'parts_cost'  => 0.0,
                ];
            }
            $techPerformance[$techId]['jobs_count']++;
            if (in_array($job->status, ['ready', 'delivered'], true)) {
                $techPerformance[$techId]['completed']++;
            }
            $techPerformance[$techId]['revenue'] += $final;
            $techPerformance[$techId]['parts_cost'] += $partsCost;
        }

        $grossServiceProfit = $totalFinalCharge - $totalPartsCost;
        $completedCount = $statusCounts['ready'] + $statusCounts['delivered'];
        $pendingCount = $jobs->count() - $completedCount - $statusCounts['cancelled'] - $statusCounts['unrepairable'];

        return [
            'jobs'                 => $jobs,
            'count'                => $jobs->count(),
            'completed_count'      => $completedCount,
            'pending_count'        => max(0, $pendingCount),
            'total_revenue'        => $totalFinalCharge,
            'total_paid'           => $totalPaid,
            'total_parts_cost'     => $totalPartsCost,
            'gross_service_profit' => $grossServiceProfit,
            'status_counts'        => $statusCounts,
            'technicians'          => array_values($techPerformance),
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Payment-Method Reconciliation (§10.5, §11)                         */
    /* ------------------------------------------------------------------ */

    /**
     * Reconcile payment methods across POS sales, cash drawer shifts, and returns.
     *
     * @return array{
     *   methods: array<string, array{
     *     method: string,
     *     name: string,
     *     is_digital: bool,
     *     count: int,
     *     total_amount: string,
     *     change_given: string,
     *     net_amount: string,
     *     refund_amount: string,
     *     reference_count: int,
     *     share_percentage: float
     *   }>,
     *   total_collected: string,
     *   total_change: string,
     *   net_sales: string,
     *   total_refunded: string,
     *   net_settlement: string,
     *   digital_collected: string,
     *   cash_collected: string,
     *   credit_collected: string,
     *   payment_count: int,
     *   payments: Collection
     * }
     */
    public function paymentMethodReconciliation(Store $store, Carbon $from, Carbon $to): array
    {
        $salesQuery = PosSale::query()
            ->with(['payments', 'cashier', 'customer'])
            ->where('store_id', $store->id)
            ->whereNotNull('posted_at')
            ->whereBetween('posted_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        $sales = $salesQuery->get();

        // All payments across sales
        $allPayments = $sales->flatMap(fn (PosSale $s) => $s->payments);

        // Fetch all returns within the same period for refund tracing
        $returns = PosReturn::query()
            ->with('payments')
            ->where('store_id', $store->id)
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();

        $refundsByMethod = [];
        $totalRefunded = '0.00';
        foreach ($returns as $ret) {
            foreach ($ret->payments as $rp) {
                $m = strtolower(trim((string) $rp->method));
                $refundsByMethod[$m] = bcadd($refundsByMethod[$m] ?? '0.00', (string) $rp->amount, 2);
                $totalRefunded = bcadd($totalRefunded, (string) $rp->amount, 2);
            }
        }

        $methodsAgg = [];
        $totalCollected = '0';
        $totalChange = '0';
        $digitalCollected = '0';
        $cashCollected = '0';
        $creditCollected = '0';

        $digitalMethods = ['kpay', 'kbzpay', 'wavepay', 'ayapay', 'cbpay', 'cb_pay', 'mmqr', 'bank_transfer', 'card'];

        foreach ($allPayments as $p) {
            $m = strtolower(trim((string) $p->method));
            if (! isset($methodsAgg[$m])) {
                $methodsAgg[$m] = [
                    'method' => $m,
                    'count' => 0,
                    'total_amount' => '0',
                    'change_given' => '0',
                    'reference_count' => 0,
                ];
            }

            $methodsAgg[$m]['count']++;
            $methodsAgg[$m]['total_amount'] = bcadd($methodsAgg[$m]['total_amount'], (string) $p->amount, 2);
            $methodsAgg[$m]['change_given'] = bcadd($methodsAgg[$m]['change_given'], (string) ($p->change_given ?? 0), 2);

            if (! empty($p->reference) && trim((string) $p->reference) !== '') {
                $methodsAgg[$m]['reference_count']++;
            }

            $totalCollected = bcadd($totalCollected, (string) $p->amount, 2);
            $totalChange = bcadd($totalChange, (string) ($p->change_given ?? 0), 2);

            if (in_array($m, $digitalMethods, true)) {
                $digitalCollected = bcadd($digitalCollected, (string) $p->amount, 2);
            } elseif ($m === 'cash') {
                $cashNet = bcsub((string) $p->amount, (string) ($p->change_given ?? 0), 2);
                $cashCollected = bcadd($cashCollected, $cashNet, 2);
            } elseif ($m === 'credit') {
                $creditCollected = bcadd($creditCollected, (string) $p->amount, 2);
            }
        }

        $netSales = bcsub($totalCollected, $totalChange, 2);
        $netSettlement = bcsub($netSales, $totalRefunded, 2);

        $methodNames = [
            'cash' => 'Cash',
            'kpay' => 'KBZPay',
            'kbzpay' => 'KBZPay',
            'wavepay' => 'WavePay',
            'ayapay' => 'AYA Pay',
            'cbpay' => 'CB Pay',
            'cb_pay' => 'CB Pay',
            'mmqr' => 'MMQR',
            'bank_transfer' => 'Bank Transfer',
            'card' => 'Debit/Credit Card',
            'credit' => 'Customer Credit',
            'cod' => 'Cash on Delivery',
        ];

        $reconciledMethods = [];
        foreach ($methodsAgg as $mKey => $mData) {
            $net = bcsub($mData['total_amount'], $mData['change_given'], 2);
            $refRefund = $refundsByMethod[$mKey] ?? '0.00';
            $share = (float) $totalCollected > 0
                ? round(((float) $mData['total_amount'] / (float) $totalCollected) * 100, 1)
                : 0.0;

            $reconciledMethods[$mKey] = [
                'method' => $mKey,
                'name' => $methodNames[$mKey] ?? strtoupper($mKey),
                'is_digital' => in_array($mKey, $digitalMethods, true),
                'count' => $mData['count'],
                'total_amount' => $mData['total_amount'],
                'change_given' => $mData['change_given'],
                'net_amount' => $net,
                'refund_amount' => $refRefund,
                'reference_count' => $mData['reference_count'],
                'share_percentage' => $share,
            ];
        }

        // Sort methods by total collected descending
        uasort($reconciledMethods, fn ($a, $b) => bccomp($b['total_amount'], $a['total_amount'], 2));

        // Sort individual payment ledger chronologically (latest first)
        $latestPayments = $allPayments->sortByDesc(fn ($p) => $p->created_at);

        return [
            'methods' => $reconciledMethods,
            'total_collected' => $totalCollected,
            'total_change' => $totalChange,
            'net_sales' => $netSales,
            'total_refunded' => $totalRefunded,
            'net_settlement' => $netSettlement,
            'digital_collected' => $digitalCollected,
            'cash_collected' => $cashCollected,
            'credit_collected' => $creditCollected,
            'payment_count' => $allPayments->count(),
            'payments' => $latestPayments,
        ];
    }
}

