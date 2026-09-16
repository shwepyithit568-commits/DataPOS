<?php

namespace App\POS\Services;

use App\Models\Store;
use App\POS\Models\Expense;
use App\POS\Models\PosReturn;
use App\POS\Models\PosReturnItem;
use App\POS\Models\PosSale;
use App\POS\Models\PosSaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfitLossService
{
    /**
     * Generate complete Income & Profit/Loss Statement for a store in a date range.
     *
     * Money figures are decimal strings (accumulated with bcmath so the printed
     * statement is exact); the margins are floats because they are ratios.
     *
     * @return array{
     *   period: array{from: string, to: string, label: string},
     *   revenue: array{gross_sales: string, discounts: string, returns: string, net_sales: string},
     *   cogs: array{gross_cogs: string, returns_cogs: string, net_cogs: string},
     *   gross_profit: string,
     *   gross_margin: float,
     *   expenses: array{total: string, by_category: array<int, array{id: int|null, name: string, color: string, amount: string, percent: float}>},
     *   net_profit: string,
     *   net_margin: float,
     *   metrics: array{order_count: int, aov: string, profit_per_order: string},
     *   top_products: array<int, array{product_id: int, name: string, quantity: string, revenue: string, cogs: string, profit: string, margin: float}>
     * }
     */
    public function generateStatement(Store $store, Carbon $from, Carbon $to, ?int $branchId = null): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        // ── 1. Sales Query ──
        $salesQuery = PosSale::where('store_id', $store->id)
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$start, $end]);

        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
        }

        $saleIds = (clone $salesQuery)->pluck('id');
        $orderCount = $saleIds->count();
        // exact_sum(): MySQL sums DECIMAL exactly, SQLite sums in REAL and
        // drifts over a large report — the helper makes both agree.
        $discounts = exact_sum(clone $salesQuery, 'discount');

        // ── 2. Sales Items (Gross Sales & Gross COGS) ──
        $itemsQuery = DB::table('pos_sale_items')->whereIn('pos_sale_id', $saleIds);
        $grossSales = exact_sum(clone $itemsQuery, 'line_total');
        $grossCogs = exact_sum(clone $itemsQuery, 'quantity * unit_cost');

        // ── 3. Returns & Refunds ──
        // `pos_returns` has `total` and `posted_at`; the previous column names
        // (`refund_amount`, `total_cost`, `occurred_at`) do not exist. SQLite
        // reads an unknown "quoted" identifier as a string literal, so the old
        // query silently matched zero rows and the statement never deducted a
        // single return — and on MySQL it would fail outright.
        $returnsQuery = PosReturn::where('store_id', $store->id)
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$start, $end]);

        if ($branchId) {
            $returnsQuery->where('branch_id', $branchId);
        }

        $returnsAmount = exact_sum(clone $returnsQuery, 'total');

        // Cost of the goods coming back lives on the return lines.
        $returnsCogs = exact_sum(
            DB::table('pos_return_items')
                ->join('pos_returns', 'pos_returns.id', '=', 'pos_return_items.pos_return_id')
                ->where('pos_returns.store_id', $store->id)
                ->where('pos_returns.status', 'posted')
                ->whereBetween('pos_returns.posted_at', [$start, $end])
                ->when($branchId, fn ($q) => $q->where('pos_returns.branch_id', $branchId)),
            'pos_return_items.quantity * pos_return_items.unit_cost'
        );

        // ── 4. Net Sales & Net COGS ──
        $netSales = bcsub(bcsub($grossSales, $discounts, 2), $returnsAmount, 2);
        if (bccomp($netSales, '0', 2) < 0) {
            $netSales = '0.00';
        }

        $netCogs = bcsub($grossCogs, $returnsCogs, 2);
        if (bccomp($netCogs, '0', 2) < 0) {
            $netCogs = '0.00';
        }

        // ── 5. Gross Profit & Margin ──
        $grossProfit = bcsub($netSales, $netCogs, 2);
        $grossMargin = bccomp($netSales, '0', 2) > 0
            ? round(((float) $grossProfit / (float) $netSales) * 100, 2)
            : 0.0;

        // ── 6. Operating Expenses ──
        $expensesQuery = Expense::where('store_id', $store->id)
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->with('category');

        $expenses = $expensesQuery->get();
        $totalExpenses = bc_sum($expenses->pluck('amount'));

        // Group expenses by category
        $expensesByCategory = [];
        $grouped = $expenses->groupBy('expense_category_id');
        foreach ($grouped as $catId => $catExpenses) {
            $first = $catExpenses->first();
            $catAmount = bc_sum($catExpenses->pluck('amount'));
            $catName = $first->category?->name ?? 'အထွေထွေ စရိတ် (General)';
            $catColor = $first->category?->color ?? '#64748b';
            $percent = bccomp($totalExpenses, '0', 2) > 0
                ? round(((float) $catAmount / (float) $totalExpenses) * 100, 1)
                : 0.0;

            $expensesByCategory[] = [
                'id' => $catId,
                'name' => $catName,
                'color' => $catColor,
                'amount' => $catAmount,
                'percent' => $percent,
            ];
        }

        // Sort expenses by highest amount
        usort($expensesByCategory, fn($a, $b) => bccomp($a['amount'], $b['amount'], 2));

        // ── 7. Service & Repair Revenue (if applicable) ──
        $serviceJobsQuery = \App\POS\Models\ServiceJob::where('store_id', $store->id)
            ->whereBetween('created_at', [$start, $end]);

        $serviceJobs = $serviceJobsQuery->with(['items', 'payments'])->get();
        $serviceJobsCount = $serviceJobs->count();

        $serviceRevenue = '0.00';
        $servicePartsCost = '0.00';

        foreach ($serviceJobs as $job) {
            $finalCharge = bcadd((string) ($job->final_charge ?: $job->estimated_charge ?: '0'), '0', 2);
            $paid = bc_sum($job->payments->pluck('amount'));
            $partsCost = bc_sum($job->items->where('type', 'part')->pluck('cost'));

            // If job is delivered/ready, count final charge or paid amount
            $billable = in_array($job->status, ['ready', 'delivered'], true) ? $finalCharge : $paid;
            $serviceRevenue = bcadd($serviceRevenue, bccomp($paid, $billable, 2) >= 0 ? $paid : $billable, 2);
            $servicePartsCost = bcadd($servicePartsCost, $partsCost, 2);
        }

        $serviceGrossProfit = bcsub($serviceRevenue, $servicePartsCost, 2);
        if (bccomp($serviceGrossProfit, '0', 2) < 0) {
            $serviceGrossProfit = '0.00';
        }
        $hasServices = ($serviceJobsCount > 0 || bccomp($serviceRevenue, '0', 2) > 0);

        // Combined Net Revenue & Combined COGS
        $totalCombinedRevenue = bcadd($netSales, $serviceRevenue, 2);
        $totalCombinedCogs = bcadd($netCogs, $servicePartsCost, 2);
        $totalGrossProfit = bcadd($grossProfit, $serviceGrossProfit, 2);

        // ── 8. Net Profit & Margin ──
        $netProfit = bcsub($totalGrossProfit, $totalExpenses, 2);
        // Margins are ratios, not money — float is correct for the percentage.
        $hasCombinedRevenue = bccomp($totalCombinedRevenue, '0', 2) > 0;
        $netMargin = $hasCombinedRevenue ? round(((float) $netProfit / (float) $totalCombinedRevenue) * 100, 2) : 0.0;
        $grossMargin = $hasCombinedRevenue ? round(((float) $totalGrossProfit / (float) $totalCombinedRevenue) * 100, 2) : 0.0;

        // ── 9. Operational Metrics ──
        $aov = $orderCount > 0 ? bcdiv($netSales, (string) $orderCount, 2) : '0.00';
        $profitPerOrder = $orderCount > 0 ? bcdiv($netProfit, (string) $orderCount, 2) : '0.00';

        // ── 10. Top Profitable Products ──
        $topProductsRaw = DB::table('pos_sale_items')
            ->whereIn('pos_sale_id', $saleIds)
            ->groupBy('product_name')
            ->selectRaw('
                product_name AS name,
                SUM(quantity) AS quantity,
                SUM(line_total) AS revenue,
                SUM(quantity * unit_cost) AS cogs,
                SUM(line_total - (quantity * unit_cost)) AS profit
            ')
            ->orderByDesc('profit')
            ->take(5)
            ->get();

        $topProducts = [];
        foreach ($topProductsRaw as $row) {
            $rev = bcadd((string) ($row->revenue ?? '0'), '0', 2);
            $prof = bcadd((string) ($row->profit ?? '0'), '0', 2);
            $topProducts[] = [
                'name' => $row->name,
                'quantity' => bcadd((string) ($row->quantity ?? '0'), '0', 3),
                'revenue' => $rev,
                'cogs' => bcadd((string) ($row->cogs ?? '0'), '0', 2),
                'profit' => $prof,
                'margin' => bccomp($rev, '0', 2) > 0 ? round(((float) $prof / (float) $rev) * 100, 1) : 0.0,
            ];
        }

        return [
            'period' => [
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                'label' => $start->translatedFormat('d M Y') . ' — ' . $end->translatedFormat('d M Y'),
            ],
            'revenue' => [
                'gross_sales' => $grossSales,
                'discounts' => $discounts,
                'returns' => $returnsAmount,
                'net_sales' => $netSales,
                'service_revenue' => $serviceRevenue,
                'total_revenue' => $totalCombinedRevenue,
            ],
            'cogs' => [
                'gross_cogs' => $grossCogs,
                'returns_cogs' => $returnsCogs,
                'net_cogs' => $netCogs,
                'service_parts_cost' => $servicePartsCost,
                'total_cogs' => $totalCombinedCogs,
            ],
            'services' => [
                'has_services' => $hasServices,
                'jobs_count' => $serviceJobsCount,
                'revenue' => $serviceRevenue,
                'parts_cost' => $servicePartsCost,
                'gross_profit' => $serviceGrossProfit,
                'margin' => $serviceRevenue > 0 ? round(($serviceGrossProfit / $serviceRevenue) * 100, 1) : 0.0,
            ],
            'gross_profit' => $totalGrossProfit,
            'gross_margin' => $grossMargin,
            'expenses' => [
                'total' => $totalExpenses,
                'by_category' => $expensesByCategory,
            ],
            'net_profit' => $netProfit,
            'net_margin' => $netMargin,
            'metrics' => [
                'order_count' => $orderCount,
                'aov' => $aov,
                'profit_per_order' => $profitPerOrder,
            ],
            'top_products' => $topProducts,
        ];
    }
}
