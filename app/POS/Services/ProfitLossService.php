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
     * @return array{
     *   period: array{from: string, to: string, label: string},
     *   revenue: array{gross_sales: float, discounts: float, returns: float, net_sales: float},
     *   cogs: array{gross_cogs: float, returns_cogs: float, net_cogs: float},
     *   gross_profit: float,
     *   gross_margin: float,
     *   expenses: array{total: float, by_category: array<int, array{id: int|null, name: string, color: string, amount: float, percent: float}>},
     *   net_profit: float,
     *   net_margin: float,
     *   metrics: array{order_count: int, aov: float, profit_per_order: float},
     *   top_products: array<int, array{product_id: int, name: string, quantity: float, revenue: float, cogs: float, profit: float, margin: float}>
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
        $discounts = (float) (clone $salesQuery)->sum('discount');

        // ── 2. Sales Items (Gross Sales & Gross COGS) ──
        $itemStats = DB::table('pos_sale_items')
            ->whereIn('pos_sale_id', $saleIds)
            ->selectRaw('
                SUM(line_total) AS gross_sales,
                SUM(quantity * unit_cost) AS gross_cogs
            ')
            ->first();

        $grossSales = (float) ($itemStats->gross_sales ?? 0);
        $grossCogs = (float) ($itemStats->gross_cogs ?? 0);

        // ── 3. Returns & Refunds ──
        $returnsQuery = PosReturn::where('store_id', $store->id)
            ->whereBetween('occurred_at', [$start, $end]);

        if ($branchId) {
            $returnsQuery->where('branch_id', $branchId);
        }

        $returnsAmount = (float) (clone $returnsQuery)->sum('refund_amount');
        $returnsCogs = (float) (clone $returnsQuery)->sum('total_cost');

        // ── 4. Net Sales & Net COGS ──
        $netSales = max(0, $grossSales - $discounts - $returnsAmount);
        $netCogs = max(0, $grossCogs - $returnsCogs);

        // ── 5. Gross Profit & Margin ──
        $grossProfit = $netSales - $netCogs;
        $grossMargin = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0.0;

        // ── 6. Operating Expenses ──
        $expensesQuery = Expense::where('store_id', $store->id)
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->with('category');

        $expenses = $expensesQuery->get();
        $totalExpenses = (float) $expenses->sum('amount');

        // Group expenses by category
        $expensesByCategory = [];
        $grouped = $expenses->groupBy('expense_category_id');
        foreach ($grouped as $catId => $catExpenses) {
            $first = $catExpenses->first();
            $catAmount = (float) $catExpenses->sum('amount');
            $catName = $first->category?->name ?? 'အထွေထွေ စရိတ် (General)';
            $catColor = $first->category?->color ?? '#64748b';
            $percent = $totalExpenses > 0 ? round(($catAmount / $totalExpenses) * 100, 1) : 0.0;

            $expensesByCategory[] = [
                'id' => $catId,
                'name' => $catName,
                'color' => $catColor,
                'amount' => $catAmount,
                'percent' => $percent,
            ];
        }

        // Sort expenses by highest amount
        usort($expensesByCategory, fn($a, $b) => $b['amount'] <=> $a['amount']);

        // ── 7. Service & Repair Revenue (if applicable) ──
        $serviceJobsQuery = \App\POS\Models\ServiceJob::where('store_id', $store->id)
            ->whereBetween('created_at', [$start, $end]);

        $serviceJobs = $serviceJobsQuery->with(['items', 'payments'])->get();
        $serviceJobsCount = $serviceJobs->count();

        $serviceRevenue = 0.0;
        $servicePartsCost = 0.0;

        foreach ($serviceJobs as $job) {
            $finalCharge = (float) ($job->final_charge ?: $job->estimated_charge ?: 0);
            $paid = (float) $job->payments->sum('amount');
            // If job is delivered/ready, count final charge or paid amount
            $serviceRevenue += max($paid, in_array($job->status, ['ready', 'delivered'], true) ? $finalCharge : $paid);
            $servicePartsCost += (float) $job->items->where('type', 'part')->sum('cost');
        }

        $serviceGrossProfit = max(0, $serviceRevenue - $servicePartsCost);
        $hasServices = ($serviceJobsCount > 0 || $serviceRevenue > 0);

        // Combined Net Revenue & Combined COGS
        $totalCombinedRevenue = $netSales + $serviceRevenue;
        $totalCombinedCogs = $netCogs + $servicePartsCost;
        $totalGrossProfit = $grossProfit + $serviceGrossProfit;

        // ── 8. Net Profit & Margin ──
        $netProfit = $totalGrossProfit - $totalExpenses;
        $netMargin = $totalCombinedRevenue > 0 ? round(($netProfit / $totalCombinedRevenue) * 100, 2) : 0.0;
        $grossMargin = $totalCombinedRevenue > 0 ? round(($totalGrossProfit / $totalCombinedRevenue) * 100, 2) : 0.0;

        // ── 9. Operational Metrics ──
        $aov = $orderCount > 0 ? round($netSales / $orderCount, 2) : 0.0;
        $profitPerOrder = $orderCount > 0 ? round($netProfit / $orderCount, 2) : 0.0;

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
            $rev = (float) $row->revenue;
            $prof = (float) $row->profit;
            $topProducts[] = [
                'name' => $row->name,
                'quantity' => (float) $row->quantity,
                'revenue' => $rev,
                'cogs' => (float) $row->cogs,
                'profit' => $prof,
                'margin' => $rev > 0 ? round(($prof / $rev) * 100, 1) : 0.0,
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
