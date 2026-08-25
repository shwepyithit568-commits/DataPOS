<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\POS\Services\PosReportService;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * POS Reports (Sales / Cash / Stock / Service & Repairs).
 */
class PosReportController extends Controller
{
    public function __construct(
        protected PosReportService $reports,
    ) {
    }

    /**
     * POS Sales Report.
     */
    public function sales(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $cashierId = $request->filled('cashier_id') ? (int) $request->input('cashier_id') : null;

        $report = $this->reports->salesReport($store, $from, $to, $cashierId);

        $cashiers = \App\Models\User::query()
            ->whereHas('stores', fn ($q) => $q->where('stores.id', $store->id)->whereIn('store_user.role', ['store_manager', 'staff']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pos.reports.sales', compact('store', 'from', 'to', 'preset', 'cashierId', 'report', 'cashiers'));
    }

    /**
     * Export POS Sales as CSV.
     */
    public function exportSales(Request $request, StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $cashierId = $request->filled('cashier_id') ? (int) $request->input('cashier_id') : null;
        $report = $this->reports->salesReport($store, $from, $to, $cashierId);

        $filename = 'pos-sales-report-' . $store->slug . '-' . $from->format('Ymd') . '-to-' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($report, $from, $to, $store) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['POS Sales Report', $store->name]);
            fputcsv($handle, ['Period', $from->toFormattedDateString() . ' to ' . $to->toFormattedDateString()]);
            fputcsv($handle, ['Total Sales (Ks)', number_format((float) $report['total'], 2)]);
            fputcsv($handle, ['Receipt Count', $report['count']]);
            fputcsv($handle, []);

            fputcsv($handle, ['Receipt No', 'Date Time', 'Cashier', 'Customer', 'Items Count', 'Total (Ks)', 'Status', 'Payment Methods']);
            foreach ($report['sales'] as $sale) {
                $pm = $sale->payments->pluck('method')->implode(', ');
                fputcsv($handle, [
                    $sale->receipt_number,
                    $sale->posted_at?->format('Y-m-d H:i:s'),
                    $sale->cashier?->name ?? '-',
                    $sale->customer?->name ?? '-',
                    $sale->items->sum('quantity'),
                    number_format((float) $sale->total, 2),
                    $sale->status,
                    $pm ?: 'Cash',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Cash Drawer Shift Report.
     */
    public function cash(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $report = $this->reports->cashReport($store, $from, $to);

        return view('pos.reports.cash', compact('store', 'from', 'to', 'preset', 'report'));
    }

    /**
     * Stock On Hand & Ledger Report.
     */
    public function stock(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $report = $this->reports->stockReport($store, $request->query('q'));

        return view('pos.reports.stock', compact('store', 'report'));
    }

    /**
     * Service & Repair Jobs Report.
     */
    public function services(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $technicianId = $request->filled('technician_id') ? (int) $request->input('technician_id') : null;
        $status = $request->filled('status') ? (string) $request->input('status') : null;

        $report = $this->reports->serviceJobsReport($store, $from, $to, $technicianId, $status);

        $technicians = \App\Models\User::query()
            ->whereHas('stores', fn ($q) => $q->where('stores.id', $store->id)->whereIn('store_user.role', ['store_manager', 'staff']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pos.reports.services', compact('store', 'from', 'to', 'preset', 'technicianId', 'status', 'report', 'technicians'));
    }

    /**
     * Export Service & Repair Jobs as CSV.
     */
    public function exportServices(Request $request, StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $technicianId = $request->filled('technician_id') ? (int) $request->input('technician_id') : null;
        $status = $request->filled('status') ? (string) $request->input('status') : null;

        $report = $this->reports->serviceJobsReport($store, $from, $to, $technicianId, $status);

        $filename = 'service-repair-report-' . $store->slug . '-' . $from->format('Ymd') . '-to-' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($report, $from, $to, $store) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Service & Repair Jobs Report', $store->name]);
            fputcsv($handle, ['Period', $from->toFormattedDateString() . ' to ' . $to->toFormattedDateString()]);
            fputcsv($handle, ['Total Jobs', $report['count']]);
            fputcsv($handle, ['Completed Jobs', $report['completed_count']]);
            fputcsv($handle, ['Pending Jobs', $report['pending_count']]);
            fputcsv($handle, ['Total Revenue (Ks)', number_format($report['total_revenue'], 2)]);
            fputcsv($handle, ['Total Parts Cost (Ks)', number_format($report['total_parts_cost'], 2)]);
            fputcsv($handle, ['Gross Service Profit (Ks)', number_format($report['gross_service_profit'], 2)]);
            fputcsv($handle, []);

            fputcsv($handle, ['Job No', 'Voucher #', 'Date', 'Customer Name', 'Phone', 'Device Type', 'Model / Brand', 'Problem', 'Technician', 'Status', 'Estimated (Ks)', 'Final Charge (Ks)', 'Paid (Ks)']);
            foreach ($report['jobs'] as $job) {
                fputcsv($handle, [
                    $job->job_number,
                    $job->voucher_no ?? '-',
                    $job->created_at?->format('Y-m-d H:i'),
                    $job->contact_name,
                    $job->contact_phone,
                    $job->device_type,
                    $job->brand . ' ' . $job->model,
                    $job->reported_problem,
                    $job->technician?->name ?? 'Unassigned',
                    $job->status,
                    number_format((float) $job->estimated_charge, 2),
                    number_format((float) ($job->final_charge ?: $job->estimated_charge), 2),
                    number_format((float) $job->payments->sum('amount'), 2),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Resolve date range presets.
     */
    protected function resolveDateRange(Request $request): array
    {
        $preset = $request->query('preset');
        $now = today();

        if ($preset) {
            return match ($preset) {
                'today' => [
                    $now->copy(),
                    $now->copy(),
                    'today',
                ],
                'yesterday' => [
                    $now->copy()->subDay(),
                    $now->copy()->subDay(),
                    'yesterday',
                ],
                '7days' => [
                    $now->copy()->subDays(6),
                    $now->copy(),
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
                default => [
                    $now->copy()->subDays(6),
                    $now->copy(),
                    '7days',
                ],
            };
        }

        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : $now->copy()->subDays(6);
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : $now->copy();

        return [$from, $to, 'custom'];
    }
}
