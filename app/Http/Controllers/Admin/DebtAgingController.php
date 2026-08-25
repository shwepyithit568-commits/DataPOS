<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\POS\Services\DebtAgingService;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DebtAgingController extends Controller
{
    public function __construct(
        protected DebtAgingService $agingService,
    ) {
    }

    /**
     * Display Debt Aging Analysis Dashboard.
     */
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $filters = [
            'search' => $request->query('search'),
            'bucket' => $request->query('bucket'),
            'risk'   => $request->query('risk'),
            'sort'   => $request->query('sort', 'total_due_desc'),
            'page'   => $request->query('page', 1),
        ];

        $perPage = $request->query('per_page', 25);
        if (! in_array((string) $perPage, ['25', '50', '100', 'all'], true)) {
            $perPage = 25;
        }

        $analysis = $this->agingService->getAgingAnalysis($store, $filters, $perPage);
        $metrics = $analysis['metrics'];
        $customers = $analysis['customers'];

        return view('admin.debt_aging.index', compact(
            'store',
            'metrics',
            'customers',
            'filters',
            'perPage'
        ));
    }

    /**
     * Export Debt Aging Analysis to CSV (UTF-8 BOM).
     */
    public function exportCsv(Request $request, StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $filters = [
            'search' => $request->query('search'),
            'bucket' => $request->query('bucket'),
            'risk'   => $request->query('risk'),
            'sort'   => $request->query('sort', 'total_due_desc'),
        ];

        $analysis = $this->agingService->getAgingAnalysis($store, $filters, 'all');
        $metrics = $analysis['metrics'];
        $customers = $analysis['customers'];

        $filename = 'debt-aging-report-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($store, $metrics, $customers) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['Debt Aging Analysis Report', $store->name]);
            fputcsv($handle, ['Generated Date', now()->toFormattedDateString() . ' ' . now()->format('h:i A')]);
            fputcsv($handle, ['Total Outstanding Debt (Ks)', number_format($metrics['total_outstanding'], 2)]);
            fputcsv($handle, ['0 - 30 Days (Current)', number_format($metrics['bucket_0_30'], 2)]);
            fputcsv($handle, ['31 - 60 Days (Follow-up)', number_format($metrics['bucket_31_60'], 2)]);
            fputcsv($handle, ['61 - 90 Days (Warning)', number_format($metrics['bucket_61_90'], 2)]);
            fputcsv($handle, ['Over 90 Days (Critical Overdue)', number_format($metrics['bucket_90_plus'], 2)]);
            fputcsv($handle, ['Total Debtors Count', $metrics['total_debtors']]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Customer Name',
                'Phone Number',
                'Total Outstanding (Ks)',
                '0 - 30 Days (Ks)',
                '31 - 60 Days (Ks)',
                '61 - 90 Days (Ks)',
                '90+ Days (Ks)',
                'Oldest Unpaid Date',
                'Max Overdue Days',
                'Risk Level',
            ]);

            foreach ($customers as $c) {
                fputcsv($handle, [
                    $c['customer_name'],
                    $c['customer_phone'] ?? '-',
                    number_format($c['total_due'], 2),
                    number_format($c['bucket_0_30'], 2),
                    number_format($c['bucket_31_60'], 2),
                    number_format($c['bucket_61_90'], 2),
                    number_format($c['bucket_90_plus'], 2),
                    $c['oldest_unpaid_date'] ?? '-',
                    $c['max_overdue_days'] . ' days',
                    ucfirst($c['risk_level']),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Print A4 Debt Aging Statement.
     */
    public function printReport(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $filters = [
            'search' => $request->query('search'),
            'bucket' => $request->query('bucket'),
            'risk'   => $request->query('risk'),
            'sort'   => $request->query('sort', 'total_due_desc'),
        ];

        $analysis = $this->agingService->getAgingAnalysis($store, $filters, 'all');
        $metrics = $analysis['metrics'];
        $customers = $analysis['customers'];

        return view('admin.debt_aging.print', compact(
            'store',
            'metrics',
            'customers'
        ));
    }
}
