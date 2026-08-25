<?php

namespace App\POS\Services;

use App\Models\Store;
use App\Models\User;
use App\POS\Models\CashierShift;
use App\POS\Models\InventoryBalance;
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
            ->with('product')
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
}
