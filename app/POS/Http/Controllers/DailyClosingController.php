<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\DailyClosing;
use App\POS\Services\DailyClosingService;
use App\POS\Services\StoreBusinessDateService;
use App\Services\ExportDataSanitizer;
use App\Services\StoreContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Branch daily closing (SoT §18).
 *
 * GET  /store/{slug}/pos/closing                   — expected vs counted + status
 * POST /store/{slug}/pos/closing                   — create a pending closing
 * POST /store/{slug}/pos/closing/{closing}/approve — manager approval
 */
class DailyClosingController extends Controller
{
    public function __construct(
        protected DailyClosingService $closings,
        protected StoreBusinessDateService $businessDate,
    ) {
    }

    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();

        $date = $this->businessDate->parseDate($store, $request->query('date'));

        $totals = $this->closings->expectedTotals($store, $date);
        $closing = $this->closings->forDate($store, $date);

        return view('pos.closing', compact('store', 'date', 'totals', 'closing'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $user = $request->user();

        // Anti-tampering check: client cannot forge calculated financial truth fields
        if ($request->hasAny(['expected_totals', 'differences', 'total_difference', 'opening_amount'])) {
            throw ValidationException::withMessages([
                'counted' => [__('messages.financial_truth_tampering_rejected')],
            ]);
        }

        $countedInput = $request->input('counted');
        if (!is_array($countedInput)) {
            throw ValidationException::withMessages([
                'counted' => [__('messages.invalid_counted_payload')],
            ]);
        }

        foreach ($countedInput as $val) {
            if (is_array($val) || is_object($val)) {
                throw ValidationException::withMessages([
                    'counted' => [__('messages.nested_counted_key_rejected')],
                ]);
            }
        }

        $countedKeys = array_keys($countedInput);
        $allowedCounted = DailyClosing::countedMethods();
        $unknownKeys = array_diff($countedKeys, $allowedCounted);
        if (!empty($unknownKeys)) {
            throw ValidationException::withMessages([
                'counted' => [__('messages.unknown_counted_payment_methods') . ': ' . implode(', ', $unknownKeys)],
            ]);
        }

        $data = $request->validate([
            'business_date' => ['required', 'date_format:Y-m-d'],
            'counted.cash' => ['required', 'decimal:0,2', 'min:0'],
            'counted.kpay' => ['nullable', 'decimal:0,2', 'min:0'],
            'counted.wavepay' => ['nullable', 'decimal:0,2', 'min:0'],
            'counted.cb_pay' => ['nullable', 'decimal:0,2', 'min:0'],
            'counted.mmqr' => ['nullable', 'decimal:0,2', 'min:0'],
            'explanation' => ['nullable', 'string', 'max:2000'],
        ]);

        $date = $this->businessDate->parseDate($store, $data['business_date']);
        $this->businessDate->assertNotFuture($store, $date);

        $counted = [
            'cash' => (string) $data['counted']['cash'],
            'kpay' => (string) ($data['counted']['kpay'] ?? 0),
            'wavepay' => (string) ($data['counted']['wavepay'] ?? 0),
            'cb_pay' => (string) ($data['counted']['cb_pay'] ?? 0),
            'mmqr' => (string) ($data['counted']['mmqr'] ?? 0),
        ];

        try {
            $closing = $this->closings->create(
                store: $store,
                date: $date,
                counted: $counted,
                explanation: $data['explanation'] ?? null,
                actor: $user,
            );
        } catch (InventoryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.closing.index', ['store_slug' => $store->slug, 'date' => $closing->business_date->toDateString()])
            ->with('success', __('messages.closing_created') . ' — ' . $closing->business_date->toDateString());
    }

    public function approve(Request $request, string $store_slug, DailyClosing $closing, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ((int) $closing->store_id !== (int) $store->id) {
            abort(404);
        }

        try {
            $this->closings->approve($store, $closing, $request->user());
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.closing_approved') . ' — ' . $closing->business_date->toDateString());
    }

    public function xReport(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        $date = $this->businessDate->parseDate($store, $request->query('date'));
        try {
            $this->businessDate->assertNotFuture($store, $date);
        } catch (ValidationException) {
            abort(422, __('messages.future_date_not_allowed'));
        }

        $xData = $this->closings->xReport($store, $date, $request->user());

        return view('pos.closing_x_report', compact('store', 'date', 'xData'));
    }

    public function print(Request $request, string $store_slug, StoreContext $context, ?DailyClosing $closing = null): View
    {
        $store = $context->getStore();

        if ($closing && (int) $closing->store_id !== (int) $store->id) {
            abort(404);
        }

        $allowedLayouts = ['58mm', '80mm', 'a5_portrait', 'a5_landscape', 'a4_portrait', 'a4_landscape'];
        $layout = (string) $request->query('layout', '80mm');
        if (! in_array($layout, $allowedLayouts, true)) {
            $layout = '80mm';
        }

        $dateString = $closing ? $closing->business_date->toDateString() : $request->query('date');
        $date = $this->businessDate->parseDate($store, $dateString);
        try {
            $this->businessDate->assertNotFuture($store, $date);
        } catch (ValidationException) {
            abort(422, __('messages.future_date_not_allowed'));
        }

        $type = (string) $request->query('type', $closing ? 'z' : 'x');
        if (! in_array($type, ['x', 'z'], true)) {
            $type = 'z';
        }

        $xData = null;
        $totals = null;

        if ($type === 'x') {
            $xData = $this->closings->xReport($store, $date, $request->user());
            $totals = $xData['totals'];
        } else {
            // Z-Report: requires persisted closing
            if (!$closing) {
                $closing = $this->closings->forDate($store, $date);
            }

            if (!$closing) {
                abort(404, __('messages.closing_not_found'));
            }

            // Always use persisted snapshot, never call expectedTotals() on reprint
            $totals = [
                'opening_amount' => (string) $closing->opening_amount,
                'expected' => $closing->expected_totals ?? [],
                'counted' => $closing->counted_totals ?? [],
                'differences' => $closing->differences ?? [],
                'total_difference' => (string) $closing->total_difference,
                'date' => $closing->business_date->toDateString(),
                'summary' => $closing->getSummarySnapshot(),
            ];
        }

        $paperSize = in_array($layout, ['58mm', '80mm', 'a5_portrait', 'a5_landscape', 'a4_portrait', 'a4_landscape'], true)
            ? (str_starts_with((string) $layout, 'a5') ? 'a5' : (str_starts_with((string) $layout, 'a4') ? 'a4' : $layout))
            : '80mm';

        $voucherTemplate = app(\App\POS\Services\VoucherTemplateService::class)->getActiveTemplate($store, $paperSize);

        return view('pos.closing_print', compact('store', 'date', 'type', 'layout', 'closing', 'totals', 'xData', 'voucherTemplate'));
    }

    public function export(Request $request, StoreContext $context): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $date = $this->businessDate->parseDate($store, $request->query('date'));
        try {
            $this->businessDate->assertNotFuture($store, $date);
        } catch (ValidationException) {
            abort(422, __('messages.future_date_not_allowed'));
        }

        $closing = $this->closings->forDate($store, $date);
        if ($closing) {
            $totals = [
                'opening_amount' => (string) $closing->opening_amount,
                'expected' => $closing->expected_totals ?? [],
                'counted' => $closing->counted_totals ?? [],
                'differences' => $closing->differences ?? [],
                'total_difference' => (string) $closing->total_difference,
                'date' => $closing->business_date->toDateString(),
                'summary' => $closing->getSummarySnapshot(),
            ];
        } else {
            $totals = $this->closings->expectedTotals($store, $date);
        }

        $format = strtolower((string) $request->query('format', 'xlsx'));
        if (! in_array($format, ['xlsx', 'csv'], true)) {
            $format = 'xlsx';
        }

        ExportDataSanitizer::auditExport($store, 'daily_closing', $request->user(), [
            'format' => $format,
            'date' => $date->toDateString(),
            'closing_id' => $closing?->id,
            'status' => $closing ? ($closing->isApproved() ? 'approved' : 'pending') : 'unclosed',
        ]);

        if ($format === 'csv') {
            return $this->exportClosingCsv($store, $date, $totals, $closing);
        }

        return $this->exportClosingXlsx($store, $date, $totals, $closing);
    }

    private function exportClosingCsv(Store $store, Carbon $date, array $totals, ?DailyClosing $closing): StreamedResponse
    {
        $filename = 'Daily_Closing_' . $store->slug . '_' . $date->format('Ymd') . '.csv';
        $methods = DailyClosing::expectedMethods();
        $summary = $totals['summary'] ?? [];

        return response()->streamDownload(function () use ($store, $date, $totals, $closing, $methods, $summary) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, ExportDataSanitizer::utf8Bom());

            // 1. Title Block
            fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([$store->name, __('messages.closing_title')]));
            fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([__('messages.business_date'), $date->toDateString()]));
            $statusStr = $closing ? ($closing->isApproved() ? __('messages.approved') : __('messages.pending')) : __('messages.unclosed');
            fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([__('messages.status'), $statusStr]));
            if ($closing) {
                fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([__('messages.closed_by'), $closing->closingUser?->name ?? '—', $closing->closed_at?->toDateTimeString() ?? '']));
                if ($closing->approver) {
                    fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([__('messages.closing_approver'), $closing->approver->name, $closing->approved_at?->toDateTimeString() ?? '']));
                }
                if ($closing->explanation) {
                    fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([__('messages.explanation'), $closing->explanation]));
                }
            }
            fputcsv($handle, []);

            // 2. Breakdown Table
            fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([
                '#',
                __('messages.payment_method'),
                __('messages.closing_expected'),
                __('messages.closing_counted'),
                __('messages.closing_difference'),
            ]));

            $idx = 1;
            foreach ($methods as $method) {
                $isCredit = $method === 'credit';
                $expected = (float) ($totals['expected'][$method] ?? 0);
                $counted = $isCredit ? 0 : (float) ($totals['counted'][$method] ?? 0);
                $diff = (float) ($totals['differences'][$method] ?? 0);

                fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([
                    $idx++,
                    __('messages.payment_' . $method),
                    number_format($expected, 2, '.', ''),
                    $isCredit ? '-' : number_format($counted, 2, '.', ''),
                    number_format($diff, 2, '.', ''),
                ]));
            }

            fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([
                '',
                __('messages.closing_total_difference'),
                '',
                '',
                number_format((float) ($totals['total_difference'] ?? 0), 2, '.', ''),
            ]));

            fputcsv($handle, []);

            // 3. Financial Summary
            fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([__('messages.daily_financial_summary')]));
            fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([__('messages.opening_float'), number_format((float) ($totals['opening_amount'] ?? 0), 2, '.', '')]));
            fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([__('messages.gross_sales'), number_format((float) ($summary['gross_sales'] ?? 0), 2, '.', '')]));
            fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([__('messages.discounts'), number_format((float) ($summary['discount_total'] ?? 0), 2, '.', '')]));
            fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([__('messages.net_sales'), number_format((float) ($summary['net_sales'] ?? 0), 2, '.', '')]));
            fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([__('messages.tax_collected'), number_format((float) ($summary['tax_total'] ?? 0), 2, '.', '')]));
            fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([__('messages.cash_in_drawer') ?? 'Cash In', number_format((float) ($summary['cash_in'] ?? 0), 2, '.', '')]));
            fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([__('messages.cash_out_drawer') ?? 'Cash Out', number_format((float) ($summary['cash_out'] ?? 0), 2, '.', '')]));
            fputcsv($handle, ExportDataSanitizer::sanitizeCsvRow([__('messages.expected_cash'), number_format((float) ($totals['expected']['cash'] ?? 0), 2, '.', '')]));

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportClosingXlsx(Store $store, Carbon $date, array $totals, ?DailyClosing $closing): BinaryFileResponse
    {
        $filename = 'Daily_Closing_' . $store->slug . '_' . $date->format('Ymd') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_closing_');
        $methods = DailyClosing::expectedMethods();
        $summary = $totals['summary'] ?? [];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daily Closing');

        // Header Title Block
        $sheet->setCellValue('A1', $store->name . ' — ' . __('messages.closing_title'));
        $sheet->setCellValue('A2', __('messages.business_date') . ': ' . $date->format('d/m/Y') . ' | ' . __('messages.export_date') . ': ' . now()->format('d/m/Y h:i A'));
        $statusStr = $closing ? ($closing->isApproved() ? '✅ ' . __('messages.approved') : '⏳ ' . __('messages.pending')) : '📝 ' . __('messages.unclosed');
        $sheet->setCellValue('A3', __('messages.status') . ': ' . $statusStr);

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0284C7');
        $sheet->getStyle('A2:A3')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        // KPI Box
        $sheet->setCellValue('A5', __('messages.expected_cash'));
        $sheet->setCellValue('A6', (float) ($totals['expected']['cash'] ?? 0));
        $sheet->setCellValue('B5', __('messages.opening_float'));
        $sheet->setCellValue('B6', (float) ($totals['opening_amount'] ?? 0));
        $sheet->setCellValue('C5', __('messages.net_sales'));
        $sheet->setCellValue('C6', (float) ($summary['net_sales'] ?? 0));
        $sheet->setCellValue('D5', __('messages.closing_total_difference'));
        $sheet->setCellValue('D6', (float) ($totals['total_difference'] ?? 0));

        $sheet->getStyle('A5:D5')->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('64748B');
        $sheet->getStyle('A6:D6')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A6:D6')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A5:D6')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8FAFC'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Table 1: Payment Method Breakdown
        $row = 8;
        $headers = [
            'A' => '#',
            'B' => __('messages.payment_method'),
            'C' => __('messages.closing_expected'),
            'D' => __('messages.closing_counted'),
            'E' => __('messages.closing_difference'),
        ];
        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0284C7']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BAE6FD']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);

        $row++;
        $idx = 1;
        foreach ($methods as $method) {
            $isCredit = $method === 'credit';
            $expected = (float) ($totals['expected'][$method] ?? 0);
            $counted = $isCredit ? null : (float) ($totals['counted'][$method] ?? 0);
            $diff = (float) ($totals['differences'][$method] ?? 0);

            $sheet->setCellValue("A{$row}", $idx++);
            $sheet->setCellValue("B{$row}", __('messages.payment_' . $method));
            $sheet->setCellValue("C{$row}", $expected);
            if ($isCredit) {
                $sheet->setCellValue("D{$row}", '—');
            } else {
                $sheet->setCellValue("D{$row}", $counted);
            }
            $sheet->setCellValue("E{$row}", $diff);

            if ($row % 2 === 1) {
                $sheet->getStyle("A{$row}:E{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}:E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("C{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

            if ($diff < 0) {
                $sheet->getStyle("E{$row}")->getFont()->getColor()->setRGB('E11D48');
            } elseif ($diff > 0) {
                $sheet->getStyle("E{$row}")->getFont()->getColor()->setRGB('D97706');
            }

            $sheet->getStyle("A{$row}:E{$row}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');

            $row++;
        }

        // Total Difference Row
        $sheet->setCellValue("A{$row}", __('messages.closing_total_difference'));
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("E{$row}", (float) ($totals['total_difference'] ?? 0));
        $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);
        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $row += 2;
        // Table 2: Operations Financial Summary Header
        $sheet->setCellValue("A{$row}", __('messages.daily_financial_summary'));
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(20);
        $row++;

        $summaryRows = [
            __('messages.opening_float') => (float) ($totals['opening_amount'] ?? 0),
            __('messages.gross_sales') => (float) ($summary['gross_sales'] ?? 0),
            __('messages.discounts') => (float) ($summary['discount_total'] ?? 0),
            __('messages.net_sales') => (float) ($summary['net_sales'] ?? 0),
            __('messages.tax_collected') => (float) ($summary['tax_total'] ?? 0),
            (__('messages.cash_in_drawer') ?? 'Cash In') => (float) ($summary['cash_in'] ?? 0),
            (__('messages.cash_out_drawer') ?? 'Cash Out') => (float) ($summary['cash_out'] ?? 0),
            __('messages.expected_cash') => (float) ($totals['expected']['cash'] ?? 0),
        ];

        foreach ($summaryRows as $label => $val) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", $val);

            if ($row % 2 === 1) {
                $sheet->getStyle("A{$row}:B{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
