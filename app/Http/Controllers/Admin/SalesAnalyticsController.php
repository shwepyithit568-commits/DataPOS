<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\POS\Services\SalesAnalyticsService;
use App\Services\StoreContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesAnalyticsController extends Controller
{
    public function __construct(
        protected SalesAnalyticsService $analyticsService,
    ) {
    }

    /**
     * Display the Sales Analytics & Deep Charts dashboard.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $channel = $request->input('channel', 'all');
        if (! in_array($channel, ['all', 'pos', 'online'], true)) {
            $channel = 'all';
        }

        $report = $this->analyticsService->generateReport($store, $from, $to, $channel);

        // Previous period for growth comparison
        $daysDiff = $from->diffInDays($to) + 1;
        $prevFrom = $from->copy()->subDays($daysDiff);
        $prevTo = $to->copy()->subDays($daysDiff);
        $prevReport = $this->analyticsService->generateReport($store, $prevFrom, $prevTo, $channel);

        $comparison = [
            'revenue_diff' => $report['kpi']['net_sales'] - $prevReport['kpi']['net_sales'],
            'orders_diff'  => $report['kpi']['total_orders'] - $prevReport['kpi']['total_orders'],
            'profit_diff'  => $report['kpi']['gross_profit'] - $prevReport['kpi']['gross_profit'],
            'revenue_growth' => $prevReport['kpi']['net_sales'] > 0
                ? round((($report['kpi']['net_sales'] - $prevReport['kpi']['net_sales']) / $prevReport['kpi']['net_sales']) * 100, 1)
                : 0.0,
            'orders_growth' => $prevReport['kpi']['total_orders'] > 0
                ? round((($report['kpi']['total_orders'] - $prevReport['kpi']['total_orders']) / $prevReport['kpi']['total_orders']) * 100, 1)
                : 0.0,
            'profit_growth' => abs($prevReport['kpi']['gross_profit']) > 0
                ? round((($report['kpi']['gross_profit'] - $prevReport['kpi']['gross_profit']) / abs($prevReport['kpi']['gross_profit'])) * 100, 1)
                : 0.0,
        ];

        return view('admin.sales_analytics.index', compact(
            'store',
            'report',
            'preset',
            'channel',
            'from',
            'to',
            'comparison'
        ));
    }

    /**
     * Export Top-Selling Products & Timeline as CSV.
     */
    public function exportCsv(StoreContext $context, Request $request): StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $channel = $request->input('channel', 'all');
        $report = $this->analyticsService->generateReport($store, $from, $to, $channel);

        $filename = 'sales-analytics-' . $store->slug . '-' . $from->format('Ymd') . '-to-' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($report, $from, $to, $store) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header summary
            fputcsv($handle, ['Sales Analytics Report', $store->name]);
            fputcsv($handle, ['Period', $from->toFormattedDateString() . ' to ' . $to->toFormattedDateString()]);
            fputcsv($handle, ['Gross Sales (Ks)', number_format($report['kpi']['gross_sales'], 2)]);
            fputcsv($handle, ['Discounts Given (Ks)', number_format($report['kpi']['discounts'], 2)]);
            fputcsv($handle, ['Net Sales (Ks)', number_format($report['kpi']['net_sales'], 2)]);
            fputcsv($handle, ['Estimated COGS (Ks)', number_format($report['kpi']['total_cost'], 2)]);
            fputcsv($handle, ['Gross Profit (Ks)', number_format($report['kpi']['gross_profit'], 2)]);
            fputcsv($handle, ['Gross Margin (%)', $report['kpi']['gross_margin'] . '%']);
            fputcsv($handle, ['Total Invoices/Orders', $report['kpi']['total_orders']]);
            fputcsv($handle, ['Total Items Sold', $report['kpi']['total_items']]);
            fputcsv($handle, ['Average Order Value (Ks)', number_format($report['kpi']['aov'], 2)]);
            fputcsv($handle, []);

            // Top Products Section
            fputcsv($handle, ['--- TOP SELLING PRODUCTS ---']);
            fputcsv($handle, ['Rank', 'Product Name', 'SKU', 'Category', 'Brand', 'Units Sold', 'Revenue (Ks)', 'Cost (Ks)', 'Profit (Ks)', 'Margin (%)']);
            foreach ($report['top_products'] as $idx => $p) {
                fputcsv($handle, [
                    $idx + 1,
                    $p['name'],
                    $p['sku'],
                    $p['category_name'] ?? '-',
                    $p['brand_name'] ?? '-',
                    $p['quantity'],
                    number_format($p['revenue'], 2),
                    number_format($p['cost'], 2),
                    number_format($p['profit'], 2),
                    $p['margin'] . '%',
                ]);
            }
            fputcsv($handle, []);

            // Cashier Section
            if (!empty($report['cashier_performance'])) {
                fputcsv($handle, ['--- CASHIER / STAFF PERFORMANCE ---']);
                fputcsv($handle, ['Cashier Name', 'Email', 'Receipts Count', 'Total Sales (Ks)', 'Discounts (Ks)', 'AOV (Ks)']);
                foreach ($report['cashier_performance'] as $c) {
                    fputcsv($handle, [
                        $c['name'],
                        $c['email'],
                        $c['orders_count'],
                        number_format($c['total_sales'], 2),
                        number_format($c['total_discounts'], 2),
                        number_format($c['aov'], 2),
                    ]);
                }
                fputcsv($handle, []);
            }

            // Daily Sales Timeline
            fputcsv($handle, ['--- DAILY SALES TIMELINE ---']);
            fputcsv($handle, ['Date', 'Day', 'Total Revenue (Ks)', 'POS Revenue (Ks)', 'Web Revenue (Ks)', 'Orders Count']);
            foreach ($report['timeline']['series'] as $t) {
                fputcsv($handle, [
                    $t['date'],
                    $t['short_day'],
                    number_format($t['revenue'], 2),
                    number_format($t['pos_revenue'], 2),
                    number_format($t['web_revenue'], 2),
                    $t['orders'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Resolve date range from query parameters.
     */
    protected function resolveDateRange(Request $request): array
    {
        $preset = $request->query('preset', '30days');
        $now = now();

        return match ($preset) {
            'today' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                'today',
            ],
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
                'yesterday',
            ],
            '7days' => [
                $now->copy()->subDays(6)->startOfDay(),
                $now->copy()->endOfDay(),
                '7days',
            ],
            'this_month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                'this_month',
            ],
            'last_month' => [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
                'last_month',
            ],
            'this_year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                'this_year',
            ],
            'custom' => [
                $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : $now->copy()->subDays(29)->startOfDay(),
                $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : $now->copy()->endOfDay(),
                'custom',
            ],
            default => [ // '30days'
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
                '30days',
            ],
        };
    }
}
