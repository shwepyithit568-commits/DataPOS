<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use App\POS\Services\PosReportService;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

        $cashiers = User::query()
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

        $filename = 'sales-report-' . $store->slug . '-' . $from->format('Ymd') . '-to-' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($report, $from, $to, $store) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [__('messages.reports_sales'), $store->name]);
            fputcsv($handle, [__('messages.report_period'), $from->toFormattedDateString() . ' to ' . $to->toFormattedDateString()]);
            fputcsv($handle, [__('messages.reports_total_sales'), number_format($report['total_sales'], 2)]);
            fputcsv($handle, [__('messages.reports_total_orders'), $report['total_orders']]);
            fputcsv($handle, [__('messages.reports_total_discount'), number_format($report['total_discount'], 2)]);
            fputcsv($handle, [__('messages.reports_total_tax'), number_format($report['total_tax'], 2)]);
            fputcsv($handle, [__('messages.reports_net_sales'), number_format($report['net_sales'], 2)]);
            fputcsv($handle, []);

            fputcsv($handle, [
                __('messages.invoice_no'),
                __('messages.reports_date'),
                __('messages.cashier'),
                __('messages.customer'),
                __('messages.subtotal'),
                __('messages.discount'),
                __('messages.tax'),
                __('messages.total'),
                __('messages.reports_payment_method'),
                __('messages.status'),
            ]);
            foreach ($report['sales'] as $sale) {
                fputcsv($handle, [
                    $sale->invoice_no,
                    $sale->posted_at?->format('Y-m-d H:i'),
                    $sale->creator?->name ?? '-',
                    $sale->customer?->name ?? __('messages.reports_walk_in_customer'),
                    number_format((float) $sale->subtotal, 2),
                    number_format((float) $sale->discount, 2),
                    number_format((float) $sale->tax, 2),
                    number_format((float) $sale->total, 2),
                    $sale->payment_method,
                    $sale->status,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Cash Drawer Report.
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
     * Stock / Inventory Report.
     */
    public function stock(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $search = $request->input('search');
        $lowStockOnly = $request->boolean('low_stock');
        $report = $this->reports->stockReport($store, $search, $lowStockOnly);

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

        $technicians = User::query()
            ->whereHas('stores', fn ($q) => $q->where('stores.id', $store->id)->whereIn('store_user.role', ['store_manager', 'staff']))
            ->orderBy('name')
            ->get(['id', 'name']);

        $exportXlsxUrl = route('pos.reports.services.export', [
            'store_slug' => $store->slug,
            'preset' => $preset,
            'technician_id' => $technicianId,
            'status' => $status,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'format' => 'xlsx',
        ]);

        $exportCsvUrl = route('pos.reports.services.export', [
            'store_slug' => $store->slug,
            'preset' => $preset,
            'technician_id' => $technicianId,
            'status' => $status,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'format' => 'csv',
        ]);

        return view('pos.reports.services', compact(
            'store',
            'from',
            'to',
            'preset',
            'technicianId',
            'status',
            'report',
            'technicians',
            'exportXlsxUrl',
            'exportCsvUrl'
        ));
    }

    /**
     * Export Service & Repair Jobs as Excel (.xlsx) or CSV (.csv).
     */
    public function exportServices(Request $request, StoreContext $context): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $technicianId = $request->filled('technician_id') ? (int) $request->input('technician_id') : null;
        $status = $request->filled('status') ? (string) $request->input('status') : null;
        $format = $request->query('format', 'xlsx');

        $report = $this->reports->serviceJobsReport($store, $from, $to, $technicianId, $status);

        if ($format === 'csv') {
            return $this->exportServicesCsv($store, $report, $from, $to);
        }

        return $this->exportServicesXlsx($store, $report, $from, $to);
    }

    /**
     * Export Service Jobs as formatted Excel (XLSX).
     */
    private function exportServicesXlsx(Store $store, array $report, Carbon $from, Carbon $to): BinaryFileResponse
    {
        $filename = 'Service_Report_' . $store->slug . '_' . $from->format('Ymd') . '_' . $to->format('Ymd') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_svc_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Service & Repairs');

        // Header Title Block
        $sheet->setCellValue('A1', $store->name . ' - ' . __('messages.sidebar_service_revenue_report'));
        $sheet->setCellValue('A2', __('messages.period') . ': ' . $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y'));
        $sheet->setCellValue('A3', __('messages.export_date') . ': ' . now()->format('d/m/Y h:i A') . ' | Currency: MMK');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1E1B4B');
        $sheet->getStyle('A2:A3')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        // Summary KPI Box (Rows 5-8)
        $sheet->setCellValue('A5', __('messages.report_total_jobs') . ': ' . $report['count']);
        $sheet->setCellValue('B5', __('messages.report_completed_jobs') . ': ' . $report['completed_count']);
        $sheet->setCellValue('C5', __('messages.report_pending_jobs') . ': ' . $report['pending_count']);

        $sheet->setCellValue('A6', __('messages.report_total_revenue') . ': ' . number_format($report['total_revenue'], 0) . ' MMK');
        $sheet->setCellValue('B6', __('messages.report_total_parts_cost') . ': ' . number_format($report['total_parts_cost'], 0) . ' MMK');
        $sheet->setCellValue('C6', __('messages.report_gross_service_profit') . ': ' . number_format($report['gross_service_profit'], 0) . ' MMK');

        $sheet->getStyle('A5:C6')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A5:C6')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F1F5F9'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
        ]);

        $row = 9;

        // Table Header
        $headers = [
            'A' => __('messages.report_job_no'),
            'B' => __('messages.report_voucher_no'),
            'C' => __('messages.stock_ledger_date'),
            'D' => __('messages.customer_name'),
            'E' => __('messages.phone'),
            'F' => __('messages.report_device_type'),
            'G' => __('messages.report_model_brand'),
            'H' => __('messages.report_problem'),
            'I' => __('messages.report_technician'),
            'J' => __('messages.status'),
            'K' => __('messages.report_parts_cost') ?? 'Parts Cost',
            'L' => __('messages.report_final_charge'),
            'M' => __('messages.report_paid_amount'),
            'N' => __('messages.report_profit') ?? 'Profit',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("K{$row}:N{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(22);

        $row++;

        $jobStatusLabel = function (string $status): string {
            return match ($status) {
                'received' => __('messages.job_status_received'),
                'diagnosing' => __('messages.job_status_diagnosing'),
                'awaiting_approval' => __('messages.job_status_awaiting_approval'),
                'awaiting_parts' => __('messages.job_status_awaiting_parts'),
                'in_repair' => __('messages.job_status_in_repair'),
                'ready' => __('messages.job_status_ready'),
                'delivered' => __('messages.job_status_delivered'),
                'cancelled' => __('messages.job_status_cancelled'),
                'unrepairable' => __('messages.job_status_unrepairable'),
                default => ucfirst($status),
            };
        };

        foreach ($report['jobs'] as $job) {
            $final = (float) ($job->final_charge ?: $job->estimated_charge ?: 0);
            $paid = (float) $job->payments->sum('amount');
            $partsCost = (float) $job->items->where('type', 'part')->sum('cost');
            $profit = max(0, $final - $partsCost);

            $sheet->setCellValue("A{$row}", $job->job_number);
            $sheet->setCellValue("B{$row}", $job->voucher_no ?? '-');
            $sheet->setCellValue("C{$row}", $job->created_at?->format('d/m/Y H:i'));
            $sheet->setCellValue("D{$row}", $job->contact_name);
            $sheet->setCellValue("E{$row}", $job->contact_phone);
            $sheet->setCellValue("F{$row}", $job->device_type);
            $sheet->setCellValue("G{$row}", $job->brand . ' ' . $job->model);
            $sheet->setCellValue("H{$row}", $job->reported_problem);
            $sheet->setCellValue("I{$row}", $job->technician?->name ?? __('messages.report_unassigned'));
            $sheet->setCellValue("J{$row}", $jobStatusLabel($job->status));
            $sheet->setCellValue("K{$row}", $partsCost);
            $sheet->setCellValue("L{$row}", $final);
            $sheet->setCellValue("M{$row}", $paid);
            $sheet->setCellValue("N{$row}", $profit);

            $sheet->getStyle("K{$row}:N{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                ]);
            }

            $row++;
        }

        // Totals Row
        $sheet->setCellValue("A{$row}", __('messages.total'));
        $sheet->setCellValue("K{$row}", $report['total_parts_cost']);
        $sheet->setCellValue("L{$row}", $report['total_revenue']);
        $sheet->setCellValue("M{$row}", $report['total_paid']);
        $sheet->setCellValue("N{$row}", $report['gross_service_profit']);

        $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1E1B4B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2FF']],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '6366F1']],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '6366F1']],
            ],
        ]);
        $sheet->getStyle("K{$row}:N{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Auto-fit columns
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export Service Jobs as CSV.
     */
    private function exportServicesCsv(Store $store, array $report, Carbon $from, Carbon $to): StreamedResponse
    {
        $filename = 'service-repair-report-' . $store->slug . '-' . $from->format('Ymd') . '-to-' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($report, $from, $to, $store) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [__('messages.sidebar_service_revenue_report'), $store->name]);
            fputcsv($handle, [__('messages.report_period'), $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y')]);
            fputcsv($handle, [__('messages.report_total_jobs'), $report['count']]);
            fputcsv($handle, [__('messages.report_completed_jobs'), $report['completed_count']]);
            fputcsv($handle, [__('messages.report_pending_jobs'), $report['pending_count']]);
            fputcsv($handle, [__('messages.report_total_revenue'), number_format($report['total_revenue'], 2)]);
            fputcsv($handle, [__('messages.report_total_parts_cost'), number_format($report['total_parts_cost'], 2)]);
            fputcsv($handle, []);

            $jobStatusLabel = function (string $status): string {
                return match ($status) {
                    'received' => __('messages.job_status_received'),
                    'diagnosing' => __('messages.job_status_diagnosing'),
                    'awaiting_approval' => __('messages.job_status_awaiting_approval'),
                    'awaiting_parts' => __('messages.job_status_awaiting_parts'),
                    'in_repair' => __('messages.job_status_in_repair'),
                    'ready' => __('messages.job_status_ready'),
                    'delivered' => __('messages.job_status_delivered'),
                    'cancelled' => __('messages.job_status_cancelled'),
                    'unrepairable' => __('messages.job_status_unrepairable'),
                    default => ucfirst($status),
                };
            };

            fputcsv($handle, [
                __('messages.report_job_no'),
                __('messages.report_voucher_no'),
                __('messages.stock_ledger_date'),
                __('messages.customer_name'),
                __('messages.phone'),
                __('messages.report_device_type'),
                __('messages.report_model_brand'),
                __('messages.report_problem'),
                __('messages.report_technician'),
                __('messages.status'),
                __('messages.report_parts_cost') ?? 'Parts Cost',
                __('messages.report_final_charge'),
                __('messages.report_paid_amount'),
                __('messages.report_profit') ?? 'Profit',
            ]);

            foreach ($report['jobs'] as $job) {
                $final = (float) ($job->final_charge ?: $job->estimated_charge ?: 0);
                $paid = (float) $job->payments->sum('amount');
                $partsCost = (float) $job->items->where('type', 'part')->sum('cost');
                $profit = max(0, $final - $partsCost);

                fputcsv($handle, [
                    $job->job_number,
                    $job->voucher_no ?? '-',
                    $job->created_at?->format('Y-m-d H:i'),
                    $job->contact_name,
                    $job->contact_phone,
                    $job->device_type,
                    $job->brand . ' ' . $job->model,
                    $job->reported_problem,
                    $job->technician?->name ?? __('messages.report_unassigned'),
                    $jobStatusLabel($job->status),
                    number_format($partsCost, 2),
                    number_format($final, 2),
                    number_format($paid, 2),
                    number_format($profit, 2),
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
                'this_week' => [
                    $now->copy()->startOfWeek(),
                    $now->copy()->endOfWeek(),
                    'this_week',
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
                'this_year' => [
                    $now->copy()->startOfYear(),
                    $now->copy()->endOfYear(),
                    'this_year',
                ],
                default => [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth(),
                    'this_month',
                ],
            };
        }

        if ($request->filled('from') || $request->filled('to')) {
            $from = $request->filled('from') ? Carbon::parse($request->input('from')) : $now->copy()->startOfMonth();
            $to = $request->filled('to') ? Carbon::parse($request->input('to')) : $now->copy()->endOfMonth();
            return [$from, $to, 'custom'];
        }

        return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'this_month'];
    }
}
