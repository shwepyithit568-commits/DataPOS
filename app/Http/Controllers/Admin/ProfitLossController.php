<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\POS\Services\ProfitLossService;
use App\Services\StoreContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfitLossController extends Controller
{
    public function __construct(
        protected ProfitLossService $plService,
    ) {
    }

    /**
     * Display the Profit & Loss Statement Dashboard.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);

        $statement = $this->plService->generateStatement($store, $from, $to);

        // Previous period for growth comparison
        $daysDiff = $from->diffInDays($to) + 1;
        $prevFrom = $from->copy()->subDays($daysDiff);
        $prevTo = $to->copy()->subDays($daysDiff);
        $prevStatement = $this->plService->generateStatement($store, $prevFrom, $prevTo);

        $comparison = [
            'revenue_diff' => $statement['revenue']['net_sales'] - $prevStatement['revenue']['net_sales'],
            'profit_diff' => $statement['net_profit'] - $prevStatement['net_profit'],
            'revenue_growth' => $prevStatement['revenue']['net_sales'] > 0
                ? round((($statement['revenue']['net_sales'] - $prevStatement['revenue']['net_sales']) / $prevStatement['revenue']['net_sales']) * 100, 1)
                : 0.0,
            'profit_growth' => abs($prevStatement['net_profit']) > 0
                ? round((($statement['net_profit'] - $prevStatement['net_profit']) / abs($prevStatement['net_profit'])) * 100, 1)
                : 0.0,
        ];

        return view('admin.profit_loss.index', compact(
            'store',
            'statement',
            'preset',
            'from',
            'to',
            'comparison'
        ));
    }

    /**
     * Render clean printable A4 Financial Statement.
     */
    public function statement(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $statement = $this->plService->generateStatement($store, $from, $to);

        return view('admin.profit_loss.statement', compact(
            'store',
            'statement',
            'preset',
            'from',
            'to'
        ));
    }

    /**
     * Export P&L report as downloadable CSV.
     */
    public function export(StoreContext $context, Request $request): StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $statement = $this->plService->generateStatement($store, $from, $to);

        $filename = 'Profit_Loss_' . $store->slug . '_' . $from->format('Ymd') . '_' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($store, $statement, $from, $to) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel Burmese font compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [$store->name . ' - Profit & Loss Financial Statement']);
            fputcsv($handle, ['Period', $from->format('d/m/Y') . ' to ' . $to->format('d/m/Y')]);
            fputcsv($handle, ['Generated At', now()->format('d/m/Y h:i A')]);
            fputcsv($handle, []);

            // 1. Revenue
            fputcsv($handle, ['1. REVENUE (အရောင်းဝင်ငွေ)', 'Amount (MMK)']);
            fputcsv($handle, ['Gross Sales (စုစုပေါင်း အရောင်း)', number_format($statement['revenue']['gross_sales'], 2)]);
            fputcsv($handle, ['Less: Sales Discounts (လျော့စျေးများ)', '-' . number_format($statement['revenue']['discounts'], 2)]);
            fputcsv($handle, ['Less: Sales Returns & Refunds (ပြန်အမ်းငွေများ)', '-' . number_format($statement['revenue']['returns'], 2)]);
            fputcsv($handle, ['NET SALES REVENUE (အသားတင် အရောင်းရငွေ)', number_format($statement['revenue']['net_sales'], 2)]);
            fputcsv($handle, []);

            // 2. Cost of Goods Sold
            fputcsv($handle, ['2. COST OF GOODS SOLD (COGS - ပစ္စည်းအရင်းစရိတ်)', 'Amount (MMK)']);
            fputcsv($handle, ['Gross Cost of Goods Sold', number_format($statement['cogs']['gross_cogs'], 2)]);
            fputcsv($handle, ['Less: Cost of Returned Goods', '-' . number_format($statement['cogs']['returns_cogs'], 2)]);
            fputcsv($handle, ['NET COST OF GOODS SOLD', number_format($statement['cogs']['net_cogs'], 2)]);
            fputcsv($handle, []);

            // 3. Gross Profit
            fputcsv($handle, ['3. GROSS PROFIT (စုစုပေါင်း အကြမ်းအမြတ်)', number_format($statement['gross_profit'], 2)]);
            fputcsv($handle, ['Gross Profit Margin %', $statement['gross_margin'] . '%']);
            fputcsv($handle, []);

            // 4. Operating Expenses
            fputcsv($handle, ['4. OPERATING EXPENSES (ဆိုင်လည်ပတ်စရိတ်များ)', 'Amount (MMK)', '% of Total']);
            foreach ($statement['expenses']['by_category'] as $cat) {
                fputcsv($handle, [$cat['name'], number_format($cat['amount'], 2), $cat['percent'] . '%']);
            }
            fputcsv($handle, ['TOTAL OPERATING EXPENSES (စုစုပေါင်း လည်ပတ်စရိတ်)', number_format($statement['expenses']['total'], 2), '100%']);
            fputcsv($handle, []);

            // 5. Net Profit
            fputcsv($handle, ['5. NET PROFIT / LOSS (အသားတင် အမြတ်/အရှုံး)', number_format($statement['net_profit'], 2)]);
            fputcsv($handle, ['Net Profit Margin %', $statement['net_margin'] . '%']);
            fputcsv($handle, []);

            // 6. Metrics
            fputcsv($handle, ['6. OPERATIONAL METRICS', 'Value']);
            fputcsv($handle, ['Total Orders Count', $statement['metrics']['order_count']]);
            fputcsv($handle, ['Average Order Value (AOV)', number_format($statement['metrics']['aov'], 2) . ' MMK']);
            fputcsv($handle, ['Average Profit per Order', number_format($statement['metrics']['profit_per_order'], 2) . ' MMK']);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Resolve date range from preset or custom inputs.
     */
    private function resolveDateRange(Request $request): array
    {
        $preset = $request->input('preset', 'this_month');
        $now = Carbon::now();

        switch ($preset) {
            case 'today':
                $from = $now->copy()->startOfDay();
                $to = $now->copy()->endOfDay();
                break;
            case 'yesterday':
                $from = $now->copy()->subDay()->startOfDay();
                $to = $now->copy()->subDay()->endOfDay();
                break;
            case 'this_week':
                $from = $now->copy()->startOfWeek();
                $to = $now->copy()->endOfWeek();
                break;
            case 'last_month':
                $from = $now->copy()->subMonth()->startOfMonth();
                $to = $now->copy()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $from = $now->copy()->startOfYear();
                $to = $now->copy()->endOfYear();
                break;
            case 'custom':
                $fromStr = $request->input('from');
                $toStr = $request->input('to');
                $from = ! empty($fromStr) ? Carbon::parse($fromStr)->startOfDay() : $now->copy()->startOfMonth();
                $to = ! empty($toStr) ? Carbon::parse($toStr)->endOfDay() : $now->copy()->endOfDay();
                break;
            case 'this_month':
            default:
                $preset = 'this_month';
                $from = $now->copy()->startOfMonth();
                $to = $now->copy()->endOfMonth();
                break;
        }

        return [$from, $to, $preset];
    }
}
