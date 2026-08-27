<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Models\Branch;
use App\POS\Services\ProfitLossService;
use App\Services\StoreContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
        $branchId = $request->filled('branch_id') && is_numeric($request->branch_id) ? (int) $request->branch_id : null;

        $branches = Branch::where('store_id', $store->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $statement = $this->plService->generateStatement($store, $from, $to, $branchId);

        // Previous period for growth comparison
        $daysDiff = $from->diffInDays($to) + 1;
        $prevFrom = $from->copy()->subDays($daysDiff);
        $prevTo = $to->copy()->subDays($daysDiff);
        $prevStatement = $this->plService->generateStatement($store, $prevFrom, $prevTo, $branchId);

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

        $exportUrl = route('store.admin.profit_loss.export', array_merge([
            'store_slug' => $store->slug,
            'preset' => $preset,
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'branch_id' => $branchId,
        ]));

        return view('admin.profit_loss.index', compact(
            'store',
            'statement',
            'preset',
            'from',
            'to',
            'branches',
            'branchId',
            'comparison',
            'exportUrl'
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
        $branchId = $request->filled('branch_id') && is_numeric($request->branch_id) ? (int) $request->branch_id : null;
        $selectedBranch = $branchId ? Branch::where('store_id', $store->id)->find($branchId) : null;

        $statement = $this->plService->generateStatement($store, $from, $to, $branchId);

        return view('admin.profit_loss.statement', compact(
            'store',
            'statement',
            'preset',
            'from',
            'to',
            'selectedBranch',
            'branchId'
        ));
    }

    /**
     * Export P&L report as downloadable CSV or formatted Excel (XLSX).
     */
    public function export(StoreContext $context, Request $request): StreamedResponse|BinaryFileResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $branchId = $request->filled('branch_id') && is_numeric($request->branch_id) ? (int) $request->branch_id : null;
        $statement = $this->plService->generateStatement($store, $from, $to, $branchId);

        $format = strtolower((string) $request->input('format', 'csv'));
        if ($format === 'xlsx' || $format === 'excel') {
            return $this->exportXlsx($store, $statement, $from, $to);
        }

        $filename = 'Profit_Loss_' . $store->slug . '_' . $from->format('Ymd') . '_' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($store, $statement, $from, $to) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel Burmese font compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [$store->name . ' - ' . __('messages.profit_loss_title')]);
            fputcsv($handle, [__('messages.period'), $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y')]);
            fputcsv($handle, [__('messages.export_date'), now()->format('d/m/Y h:i A')]);
            fputcsv($handle, []);

            // 1. Revenue
            fputcsv($handle, ['1. ' . __('messages.pl_revenue'), __('messages.amount_mmk')]);
            fputcsv($handle, [__('messages.pl_gross_sales'), number_format($statement['revenue']['gross_sales'], 2)]);
            if ($statement['revenue']['discounts'] > 0) {
                fputcsv($handle, ['Less: ' . __('messages.pl_discounts_given'), '-' . number_format($statement['revenue']['discounts'], 2)]);
            }
            if ($statement['revenue']['returns'] > 0) {
                fputcsv($handle, ['Less: ' . __('messages.pl_returns_refunds'), '-' . number_format($statement['revenue']['returns'], 2)]);
            }
            if (!empty($statement['services']['has_services'])) {
                fputcsv($handle, ['Add: ' . __('messages.pl_service_repair_revenue'), number_format($statement['services']['revenue'], 2)]);
                fputcsv($handle, [__('messages.pl_total_combined_revenue'), number_format($statement['revenue']['total_revenue'] ?? $statement['revenue']['net_sales'], 2)]);
            } else {
                fputcsv($handle, [__('messages.pl_net_revenue'), number_format($statement['revenue']['net_sales'], 2)]);
            }
            fputcsv($handle, []);

            // 2. Cost of Goods Sold
            fputcsv($handle, ['2. ' . __('messages.pl_cost_of_goods_sold') . ' (COGS)', __('messages.amount_mmk')]);
            fputcsv($handle, [__('messages.pl_gross_cogs'), number_format($statement['cogs']['gross_cogs'], 2)]);
            if ($statement['cogs']['returns_cogs'] > 0) {
                fputcsv($handle, ['Less: ' . __('messages.pl_returned_goods_cost'), '-' . number_format($statement['cogs']['returns_cogs'], 2)]);
            }
            if (!empty($statement['services']['has_services']) && $statement['services']['parts_cost'] > 0) {
                fputcsv($handle, ['Add: ' . __('messages.pl_spare_parts_cost'), number_format($statement['services']['parts_cost'], 2)]);
                fputcsv($handle, [__('messages.pl_total_combined_cogs'), number_format($statement['cogs']['total_cogs'] ?? $statement['cogs']['net_cogs'], 2)]);
            } else {
                fputcsv($handle, [__('messages.pl_net_cogs'), number_format($statement['cogs']['net_cogs'], 2)]);
            }
            fputcsv($handle, []);

            // 3. Gross Profit
            fputcsv($handle, ['3. ' . __('messages.pl_gross_profit'), number_format($statement['gross_profit'], 2)]);
            fputcsv($handle, [__('messages.pl_gross_margin'), $statement['gross_margin'] . '%']);
            fputcsv($handle, []);

            // 4. Operating Expenses
            fputcsv($handle, ['4. ' . __('messages.pl_operating_expenses'), __('messages.amount_mmk'), __('messages.percent_of_total')]);
            foreach ($statement['expenses']['by_category'] as $cat) {
                fputcsv($handle, [$cat['name'], number_format($cat['amount'], 2), $cat['percent'] . '%']);
            }
            fputcsv($handle, [__('messages.pl_total_operating_expenses'), number_format($statement['expenses']['total'], 2), '100%']);
            fputcsv($handle, []);

            // 5. Net Profit
            fputcsv($handle, ['5. ' . __('messages.pl_net_profit'), number_format($statement['net_profit'], 2)]);
            fputcsv($handle, [__('messages.pl_net_margin'), $statement['net_margin'] . '%']);
            fputcsv($handle, []);

            // 6. Metrics
            fputcsv($handle, ['6. ' . __('messages.operational_metrics_title'), 'Value']);
            fputcsv($handle, [__('messages.total_orders'), $statement['metrics']['order_count']]);
            fputcsv($handle, [__('messages.aov_metric'), number_format($statement['metrics']['aov'], 2) . ' MMK']);
            fputcsv($handle, [__('messages.profit_per_order'), number_format($statement['metrics']['profit_per_order'], 2) . ' MMK']);

            // 7. Top Products
            if (!empty($statement['top_products'])) {
                fputcsv($handle, []);
                fputcsv($handle, ['7. ' . __('messages.top_profitable_products_title'), 'Qty', 'Revenue (MMK)', 'Profit (MMK)', 'Margin %']);
                foreach ($statement['top_products'] as $prod) {
                    fputcsv($handle, [
                        $prod['name'],
                        $prod['quantity'],
                        number_format($prod['revenue'], 2),
                        number_format($prod['profit'], 2),
                        $prod['margin'] . '%',
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export P&L report as a beautifully styled Excel (XLSX) workbook.
     */
    private function exportXlsx(Store $store, array $statement, Carbon $from, Carbon $to): BinaryFileResponse
    {
        $filename = 'Profit_Loss_' . $store->slug . '_' . $from->format('Ymd') . '_' . $to->format('Ymd') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_pl_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Profit & Loss');

        // Header Title Block
        $sheet->setCellValue('A1', $store->name . ' - ' . __('messages.profit_loss_title'));
        $sheet->setCellValue('A2', __('messages.period') . ': ' . $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y'));
        $sheet->setCellValue('A3', __('messages.export_date') . ': ' . now()->format('d/m/Y h:i A') . ' | Currency: MMK');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1E1B4B');
        $sheet->getStyle('A2:A3')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        $row = 5;

        // Table Header
        $sheet->setCellValue("A{$row}", __('messages.account_particulars'));
        $sheet->setCellValue("B{$row}", __('messages.amount_mmk'));
        $sheet->setCellValue("C{$row}", __('messages.total_mmk'));
        $sheet->setCellValue("D{$row}", __('messages.percent_of_total'));

        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("B{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(24);

        $row++;

        // Helper closures for section formatting
        $writeSectionHeader = function ($title) use ($sheet, &$row) {
            $sheet->setCellValue("A{$row}", $title);
            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0F172A'], 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F1F5F9'],
                ],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(20);
            $row++;
        };

        $writeSubtotalRow = function ($label, $amount, $color = '0284C7') use ($sheet, &$row) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("C{$row}", $amount);
            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $color]],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
                    'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
                ],
            ]);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getRowDimension($row)->setRowHeight(20);
            $row++;
        };

        // 1. REVENUE
        $writeSectionHeader('1. ' . __('messages.pl_revenue'));
        $sheet->setCellValue("A{$row}", '   ' . __('messages.pl_gross_sales'));
        $sheet->setCellValue("B{$row}", $statement['revenue']['gross_sales']);
        $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;

        if ($statement['revenue']['discounts'] > 0) {
            $sheet->setCellValue("A{$row}", '   Less: ' . __('messages.pl_discounts_given'));
            $sheet->setCellValue("B{$row}", -$statement['revenue']['discounts']);
            $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('-#,##0.00');
            $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB('E11D48');
            $row++;
        }

        if ($statement['revenue']['returns'] > 0) {
            $sheet->setCellValue("A{$row}", '   Less: ' . __('messages.pl_returns_refunds'));
            $sheet->setCellValue("B{$row}", -$statement['revenue']['returns']);
            $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('-#,##0.00');
            $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB('E11D48');
            $row++;
        }

        if (!empty($statement['services']['has_services'])) {
            $sheet->setCellValue("A{$row}", '   Add: ' . __('messages.pl_service_repair_revenue') . ' (' . $statement['services']['jobs_count'] . ' Jobs)');
            $sheet->setCellValue("B{$row}", $statement['services']['revenue']);
            $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB('4F46E5');
            $row++;

            $writeSubtotalRow(__('messages.pl_total_combined_revenue'), $statement['revenue']['total_revenue'] ?? $statement['revenue']['net_sales'], '0284C7');
        } else {
            $writeSubtotalRow(__('messages.pl_net_revenue'), $statement['revenue']['net_sales'], '0284C7');
        }

        // 2. COGS
        $writeSectionHeader('2. ' . __('messages.pl_cost_of_goods_sold') . ' (COGS)');
        $sheet->setCellValue("A{$row}", '   ' . __('messages.pl_gross_cogs'));
        $sheet->setCellValue("B{$row}", $statement['cogs']['gross_cogs']);
        $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;

        if ($statement['cogs']['returns_cogs'] > 0) {
            $sheet->setCellValue("A{$row}", '   Less: ' . __('messages.pl_returned_goods_cost'));
            $sheet->setCellValue("B{$row}", -$statement['cogs']['returns_cogs']);
            $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('-#,##0.00');
            $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB('059669');
            $row++;
        }

        if (!empty($statement['services']['has_services']) && $statement['services']['parts_cost'] > 0) {
            $sheet->setCellValue("A{$row}", '   Add: ' . __('messages.pl_spare_parts_cost'));
            $sheet->setCellValue("B{$row}", $statement['services']['parts_cost']);
            $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB('D97706');
            $row++;

            $writeSubtotalRow(__('messages.pl_total_combined_cogs'), $statement['cogs']['total_cogs'] ?? $statement['cogs']['net_cogs'], 'D97706');
        } else {
            $writeSubtotalRow(__('messages.pl_net_cogs'), $statement['cogs']['net_cogs'], 'D97706');
        }

        // 3. GROSS PROFIT
        $sheet->setCellValue("A{$row}", '3. ' . __('messages.pl_gross_profit') . ' (Margin: ' . $statement['gross_margin'] . '%)');
        $sheet->setCellValue("C{$row}", $statement['gross_profit']);
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '312E81']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2FF']],
        ]);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        // 4. OPERATING EXPENSES
        $writeSectionHeader('4. ' . __('messages.pl_operating_expenses'));
        foreach ($statement['expenses']['by_category'] as $cat) {
            $sheet->setCellValue("A{$row}", '   ' . $cat['name']);
            $sheet->setCellValue("B{$row}", $cat['amount']);
            $sheet->setCellValue("D{$row}", $cat['percent'] . '%');
            $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
        }
        $writeSubtotalRow(__('messages.pl_total_operating_expenses'), $statement['expenses']['total'], 'E11D48');

        // 5. NET PROFIT / LOSS
        $isProf = $statement['net_profit'] >= 0;
        $sheet->setCellValue("A{$row}", '5. ' . ($isProf ? __('messages.pl_net_profit') : __('messages.pl_net_loss')) . ' [Net Margin: ' . $statement['net_margin'] . '%]');
        $sheet->setCellValue("C{$row}", $statement['net_profit']);
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $isProf ? '14532D' : '881337']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isProf ? 'DCFCE7' : 'FFE4E6']],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $isProf ? '15803D' : 'BE123C']],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => $isProf ? '15803D' : 'BE123C']],
            ],
        ]);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getRowDimension($row)->setRowHeight(26);
        $row += 2;

        // 6. OPERATIONAL METRICS
        $sheet->setCellValue("A{$row}", '6. ' . __('messages.operational_metrics_title'));
        $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true)->setSize(11);
        $row++;

        $sheet->setCellValue("A{$row}", '   ' . __('messages.total_orders'));
        $sheet->setCellValue("B{$row}", $statement['metrics']['order_count']);
        $row++;

        $sheet->setCellValue("A{$row}", '   ' . __('messages.aov_metric'));
        $sheet->setCellValue("B{$row}", $statement['metrics']['aov']);
        $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;

        $sheet->setCellValue("A{$row}", '   ' . __('messages.profit_per_order'));
        $sheet->setCellValue("B{$row}", $statement['metrics']['profit_per_order']);
        $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $row += 2;

        // 7. TOP PRODUCTS
        if (!empty($statement['top_products'])) {
            $sheet->setCellValue("A{$row}", '7. ' . __('messages.top_profitable_products_title'));
            $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true)->setSize(11);
            $row++;

            $sheet->setCellValue("A{$row}", __('messages.product'));
            $sheet->setCellValue("B{$row}", 'Qty Sold');
            $sheet->setCellValue("C{$row}", 'Profit (MMK)');
            $sheet->setCellValue("D{$row}", 'Margin %');
            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '475569']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            ]);
            $row++;

            foreach ($statement['top_products'] as $prod) {
                $sheet->setCellValue("A{$row}", $prod['name']);
                $sheet->setCellValue("B{$row}", $prod['quantity']);
                $sheet->setCellValue("C{$row}", $prod['profit']);
                $sheet->setCellValue("D{$row}", $prod['margin'] . '%');
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("B{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $row++;
            }
        }

        // Align right for numeric columns
        $sheet->getStyle("B5:C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Auto-fit column widths
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        $spreadsheet->disconnectWorksheets();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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

