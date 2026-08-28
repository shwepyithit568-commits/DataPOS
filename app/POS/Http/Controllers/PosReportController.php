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
     * Export POS Sales as Excel (.xlsx) or CSV (.csv).
     */
    public function exportSales(Request $request, StoreContext $context): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $cashierId = $request->filled('cashier_id') ? (int) $request->input('cashier_id') : null;
        $format = $request->query('format', 'csv');

        $report = $this->reports->salesReport($store, $from, $to, $cashierId);

        if ($format === 'xlsx') {
            return $this->exportSalesXlsx($store, $report, $from, $to);
        }

        return $this->exportSalesCsv($store, $report, $from, $to);
    }

    /**
     * Export Sales as CSV.
     */
    private function exportSalesCsv(Store $store, array $report, Carbon $from, Carbon $to): StreamedResponse
    {
        $filename = 'sales-report-' . $store->slug . '-' . $from->format('Ymd') . '-to-' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($report, $from, $to, $store) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [__('messages.reports_sales'), $store->name]);
            fputcsv($handle, [__('messages.report_period'), $from->toFormattedDateString() . ' to ' . $to->toFormattedDateString()]);
            fputcsv($handle, [__('messages.reports_total_sales'), number_format((float) ($report['total'] ?? $report['total_sales'] ?? 0), 2)]);
            fputcsv($handle, [__('messages.reports_total_orders'), $report['count'] ?? $report['total_orders'] ?? 0]);
            fputcsv($handle, []);

            fputcsv($handle, [
                __('messages.receipt'),
                __('messages.reports_date'),
                __('messages.cashier'),
                __('messages.customer'),
                __('messages.reports_items'),
                __('messages.subtotal'),
                __('messages.discount'),
                __('messages.tax'),
                __('messages.total'),
                __('messages.reports_payment_method'),
                __('messages.status'),
            ]);
            foreach ($report['sales'] as $sale) {
                fputcsv($handle, [
                    $sale->receipt_number ?: $sale->invoice_no,
                    $sale->posted_at?->format('Y-m-d H:i'),
                    $sale->cashier?->name ?? $sale->creator?->name ?? '-',
                    $sale->customer?->name ?? __('messages.reports_walk_in_customer'),
                    $sale->items->sum('quantity'),
                    number_format((float) $sale->subtotal, 2),
                    number_format((float) $sale->discount, 2),
                    number_format((float) $sale->tax, 2),
                    number_format((float) $sale->total, 2),
                    $sale->payments->pluck('method')->implode(', ') ?: ($sale->payment_method ?? '-'),
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
     * Export Sales as Excel (.xlsx).
     */
    private function exportSalesXlsx(Store $store, array $report, Carbon $from, Carbon $to): BinaryFileResponse
    {
        $filename = 'Sales_Report_' . $store->slug . '_' . $from->format('Ymd') . '_' . $to->format('Ymd') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_sales_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Report');

        // Header Title Block
        $sheet->setCellValue('A1', $store->name . ' - ' . __('messages.reports_sales'));
        $sheet->setCellValue('A2', __('messages.period') . ': ' . $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y'));
        $sheet->setCellValue('A3', __('messages.export_date') . ': ' . now()->format('d/m/Y h:i A') . ' | Currency: MMK');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0369A1');
        $sheet->getStyle('A2:A3')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        // Summary KPI Box
        $totalSales = (float) ($report['total'] ?? $report['total_sales'] ?? 0);
        $totalOrders = (int) ($report['count'] ?? $report['total_orders'] ?? 0);
        $totalItems = (int) $report['sales']->sum(fn($s) => $s->items->sum('quantity'));
        $aov = $totalOrders > 0 ? round($totalSales / $totalOrders, 2) : 0;

        $sheet->setCellValue('A5', __('messages.reports_grand_total') . ': ' . number_format($totalSales, 0) . ' MMK');
        $sheet->setCellValue('B5', __('messages.reports_sale_count') . ': ' . $totalOrders);
        $sheet->setCellValue('C5', __('messages.items_sold') . ': ' . $totalItems);
        $sheet->setCellValue('D5', __('messages.aov_metric') . ': ' . number_format($aov, 0) . ' MMK');

        $sheet->getStyle('A5:D5')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A5:D5')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F9FF'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BAE6FD']],
            ],
        ]);

        $row = 8;
        $headers = [
            'A' => __('messages.receipt'),
            'B' => __('messages.reports_date'),
            'C' => __('messages.cashier'),
            'D' => __('messages.customer'),
            'E' => __('messages.reports_items'),
            'F' => __('messages.subtotal'),
            'G' => __('messages.discount'),
            'H' => __('messages.tax'),
            'I' => __('messages.total'),
            'J' => __('messages.reports_payment_method'),
            'K' => __('messages.status'),
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0284C7'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("E{$row}:I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(22);

        $row++;

        foreach ($report['sales'] as $sale) {
            $sheet->setCellValue("A{$row}", $sale->receipt_number ?: $sale->invoice_no);
            $sheet->setCellValue("B{$row}", $sale->posted_at?->format('d/m/Y H:i'));
            $sheet->setCellValue("C{$row}", $sale->cashier?->name ?? $sale->creator?->name ?? '—');
            $sheet->setCellValue("D{$row}", $sale->customer?->name ?? 'Walk-in Customer');
            $sheet->setCellValue("E{$row}", $sale->items->sum('quantity'));
            $sheet->setCellValue("F{$row}", (float) $sale->subtotal);
            $sheet->setCellValue("G{$row}", (float) $sale->discount);
            $sheet->setCellValue("H{$row}", (float) $sale->tax);
            $sheet->setCellValue("I{$row}", (float) $sale->total);
            $sheet->setCellValue("J{$row}", $sale->payments->pluck('method')->implode(', ') ?: ($sale->payment_method ?? '-'));
            $sheet->setCellValue("K{$row}", ucfirst($sale->status));

            $sheet->getStyle("F{$row}:I{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC'],
                    ],
                ]);
            }
            $row++;
        }

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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
     * Export Cash Drawer Report as Excel (.xlsx) or CSV (.csv).
     */
    public function exportCash(Request $request, StoreContext $context): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $format = $request->query('format', 'xlsx');
        $report = $this->reports->cashReport($store, $from, $to);

        if ($format === 'csv') {
            return $this->exportCashCsv($store, $report, $from, $to);
        }

        return $this->exportCashXlsx($store, $report, $from, $to);
    }

    /**
     * Export Cash Report as CSV.
     */
    private function exportCashCsv(Store $store, array $report, Carbon $from, Carbon $to): StreamedResponse
    {
        $filename = 'cash-drawer-report-' . $store->slug . '-' . $from->format('Ymd') . '-to-' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($report, $from, $to, $store) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [__('messages.reports_cash'), $store->name]);
            fputcsv($handle, [__('messages.report_period'), $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y')]);
            fputcsv($handle, [__('messages.reports_shift_count'), $report['shift_count']]);
            fputcsv($handle, [__('messages.expected_cash'), number_format((float) $report['expected'], 2)]);
            fputcsv($handle, [__('messages.actual_cash'), number_format((float) $report['actual'], 2)]);
            fputcsv($handle, [__('messages.difference'), number_format((float) $report['difference'], 2)]);
            fputcsv($handle, []);

            fputcsv($handle, [
                __('messages.register'),
                __('messages.cashier'),
                __('messages.opening_cash'),
                __('messages.cash_sales'),
                __('messages.cash_refunds'),
                __('messages.cash_in_out'),
                __('messages.expected_cash'),
                __('messages.actual'),
                __('messages.difference'),
                __('messages.status'),
            ]);

            foreach ($report['shifts'] as $shift) {
                fputcsv($handle, [
                    $shift->register_name,
                    $shift->cashier?->name ?? '—',
                    number_format((float) $shift->opening_cash, 2),
                    number_format((float) $shift->cash_sales, 2),
                    number_format((float) $shift->cash_refunds, 2),
                    '+' . number_format((float) $shift->cash_in, 2) . ' / -' . number_format((float) $shift->cash_out, 2),
                    $shift->expected_closing_amount !== null ? number_format((float) $shift->expected_closing_amount, 2) : '—',
                    $shift->actual_closing_amount !== null ? number_format((float) $shift->actual_closing_amount, 2) : '—',
                    $shift->difference !== null ? number_format((float) $shift->difference, 2) : '—',
                    $shift->status,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export Cash Report as Excel (.xlsx).
     */
    private function exportCashXlsx(Store $store, array $report, Carbon $from, Carbon $to): BinaryFileResponse
    {
        $filename = 'Cash_Report_' . $store->slug . '_' . $from->format('Ymd') . '_' . $to->format('Ymd') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_cash_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cash Drawer');

        // Header Title Block
        $sheet->setCellValue('A1', $store->name . ' - ' . __('messages.reports_cash'));
        $sheet->setCellValue('A2', __('messages.period') . ': ' . $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y'));
        $sheet->setCellValue('A3', __('messages.export_date') . ': ' . now()->format('d/m/Y h:i A') . ' | Currency: MMK');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('065F46');
        $sheet->getStyle('A2:A3')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        // Summary KPI Box
        $sheet->setCellValue('A5', __('messages.reports_shift_count') . ': ' . $report['shift_count']);
        $sheet->setCellValue('B5', __('messages.expected_cash') . ': ' . number_format((float) $report['expected'], 0) . ' MMK');
        $sheet->setCellValue('C5', __('messages.actual_cash') . ': ' . number_format((float) $report['actual'], 0) . ' MMK');
        $sheet->setCellValue('D5', __('messages.difference') . ': ' . number_format((float) $report['difference'], 0) . ' MMK');

        $sheet->getStyle('A5:D5')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A5:D5')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'ECFDF5'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'A7F3D0']],
            ],
        ]);

        $row = 8;
        $headers = [
            'A' => __('messages.register'),
            'B' => __('messages.cashier'),
            'C' => __('messages.opening_cash'),
            'D' => __('messages.cash_sales'),
            'E' => __('messages.cash_refunds'),
            'F' => __('messages.cash_in_out'),
            'G' => __('messages.expected_cash'),
            'H' => __('messages.actual'),
            'I' => __('messages.difference'),
            'J' => __('messages.status'),
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("C{$row}:I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(22);

        $row++;

        foreach ($report['shifts'] as $shift) {
            $sheet->setCellValue("A{$row}", $shift->register_name);
            $sheet->setCellValue("B{$row}", $shift->cashier?->name ?? '—');
            $sheet->setCellValue("C{$row}", (float) $shift->opening_cash);
            $sheet->setCellValue("D{$row}", (float) $shift->cash_sales);
            $sheet->setCellValue("E{$row}", (float) $shift->cash_refunds);
            $sheet->setCellValue("F{$row}", '+' . number_format((float) $shift->cash_in, 0) . ' / -' . number_format((float) $shift->cash_out, 0));
            $sheet->setCellValue("G{$row}", $shift->expected_closing_amount !== null ? (float) $shift->expected_closing_amount : '—');
            $sheet->setCellValue("H{$row}", $shift->actual_closing_amount !== null ? (float) $shift->actual_closing_amount : '—');
            $sheet->setCellValue("I{$row}", $shift->difference !== null ? (float) $shift->difference : '—');
            $sheet->setCellValue("J{$row}", ucfirst($shift->status));

            $sheet->getStyle("C{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            if ($shift->expected_closing_amount !== null) {
                $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            }
            if ($shift->actual_closing_amount !== null) {
                $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            }
            if ($shift->difference !== null) {
                $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            }

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC'],
                    ],
                ]);
            }
            $row++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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

        $search = $request->input('q') ?? $request->input('search');
        $report = $this->reports->stockReport($store, $search);

        return view('pos.reports.stock', compact('store', 'report'));
    }

    /**
     * Export Stock / Inventory Report as Excel (.xlsx) or CSV (.csv).
     */
    public function exportStock(Request $request, StoreContext $context): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $search = $request->input('q') ?? $request->input('search');
        $format = $request->query('format', 'xlsx');
        $report = $this->reports->stockReport($store, $search);

        if ($format === 'csv') {
            return $this->exportStockCsv($store, $report);
        }

        return $this->exportStockXlsx($store, $report);
    }

    /**
     * Export Stock Report as CSV.
     */
    private function exportStockCsv(Store $store, array $report): StreamedResponse
    {
        $filename = 'stock-valuation-report-' . $store->slug . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($report, $store) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [__('messages.reports_stock'), $store->name]);
            fputcsv($handle, [__('messages.export_date'), now()->format('d/m/Y H:i')]);
            fputcsv($handle, [__('messages.reports_stock_total_skus'), count($report['rows'])]);
            fputcsv($handle, [__('messages.reports_total_units'), number_format((float) $report['total_units'], 3)]);
            fputcsv($handle, [__('messages.reports_stock_value'), number_format((float) $report['total_value'], 2)]);
            fputcsv($handle, []);

            fputcsv($handle, [
                '#',
                __('messages.product'),
                __('messages.category'),
                __('messages.sku'),
                __('messages.status'),
                __('messages.on_hand_qty'),
                __('messages.average_cost'),
                __('messages.stock_value'),
            ]);

            foreach ($report['rows'] as $index => $row) {
                $qty = (float) $row['quantity_on_hand'];
                $status = $qty > 5 ? __('messages.reports_stock_in_stock') : ($qty > 0 ? __('messages.low_stock') : __('messages.reports_stock_out_of_stock'));

                fputcsv($handle, [
                    $index + 1,
                    $row['product']?->name ?? '—',
                    $row['product']?->category?->name ?? '—',
                    $row['product']?->sku ?: '—',
                    $status,
                    number_format($qty, 3),
                    number_format((float) $row['unit_cost_avg'], 2),
                    number_format((float) $row['value'], 2),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                '',
                __('messages.total'),
                '',
                '',
                '',
                number_format((float) $report['total_units'], 3),
                '',
                number_format((float) $report['total_value'], 2),
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export Stock Report as Excel (.xlsx).
     */
    private function exportStockXlsx(Store $store, array $report): BinaryFileResponse
    {
        $filename = 'Stock_Valuation_Report_' . $store->slug . '_' . now()->format('Ymd') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_stock_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Balance');

        // Header Title Block
        $sheet->setCellValue('A1', $store->name . ' - ' . __('messages.sidebar_stock_balance'));
        $sheet->setCellValue('A2', __('messages.export_date') . ': ' . now()->format('d/m/Y h:i A') . ' | Currency: MMK');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('92400E');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        // Summary KPI Box
        $zeroStockCount = $report['rows']->filter(fn($r) => (float)$r['quantity_on_hand'] <= 0)->count();

        $sheet->setCellValue('A4', __('messages.reports_stock_total_skus') . ': ' . count($report['rows']));
        $sheet->setCellValue('B4', __('messages.reports_total_units') . ': ' . number_format((float) $report['total_units'], 3));
        $sheet->setCellValue('C4', __('messages.reports_stock_value') . ': ' . number_format((float) $report['total_value'], 0) . ' MMK');
        $sheet->setCellValue('D4', __('messages.reports_stock_low_stock') . ': ' . $zeroStockCount);

        $sheet->getStyle('A4:D4')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A4:D4')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FEF3C7'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FCD34D']],
            ],
        ]);

        $row = 7;
        $headers = [
            'A' => '#',
            'B' => __('messages.product'),
            'C' => __('messages.category'),
            'D' => __('messages.sku'),
            'E' => __('messages.status'),
            'F' => __('messages.on_hand_qty'),
            'G' => __('messages.average_cost'),
            'H' => __('messages.stock_value'),
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D97706'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("F{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(22);

        $row++;

        foreach ($report['rows'] as $index => $item) {
            $qty = (float) $item['quantity_on_hand'];
            $cost = (float) $item['unit_cost_avg'];
            $val = (float) $item['value'];
            $status = $qty > 5 ? __('messages.reports_stock_in_stock') : ($qty > 0 ? __('messages.low_stock') : __('messages.reports_stock_out_of_stock'));

            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValue("B{$row}", $item['product']?->name ?? '—');
            $sheet->setCellValue("C{$row}", $item['product']?->category?->name ?? '—');
            $sheet->setCellValue("D{$row}", $item['product']?->sku ?: '—');
            $sheet->setCellValue("E{$row}", $status);
            $sheet->setCellValue("F{$row}", $qty);
            $sheet->setCellValue("G{$row}", $cost);
            $sheet->setCellValue("H{$row}", $val);

            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.000');
            $sheet->getStyle("G{$row}:H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFBEB'],
                    ],
                ]);
            }
            $row++;
        }

        // Totals Footer Row
        $sheet->setCellValue("A{$row}", '');
        $sheet->setCellValue("B{$row}", __('messages.total'));
        $sheet->setCellValue("C{$row}", '');
        $sheet->setCellValue("D{$row}", '');
        $sheet->setCellValue("E{$row}", '');
        $sheet->setCellValue("F{$row}", (float) $report['total_units']);
        $sheet->setCellValue("G{$row}", '');
        $sheet->setCellValue("H{$row}", (float) $report['total_value']);

        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FEF3C7'],
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D97706']],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => 'D97706']],
            ],
        ]);
        $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.000');
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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
