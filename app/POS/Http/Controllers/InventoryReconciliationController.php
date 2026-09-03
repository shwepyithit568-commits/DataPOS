<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Exceptions\InventoryException;
use App\POS\Services\ReconciliationService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Opening-stock reconciliation (Phase 2.5).
 *
 * GET  /store/{slug}/pos/reconciliation          — live diff report (imported
 *                                                  opening stock vs ledger)
 * GET  /store/{slug}/pos/reconciliation/export   — download Excel (.xlsx) / CSV export
 * POST /store/{slug}/pos/reconciliation/approve  — manager-only: posts the
 *                                                  correction movements and
 *                                                  snapshots the report
 */
class InventoryReconciliationController extends Controller
{
    public function __construct(protected ReconciliationService $reconciliation)
    {
    }

    public function index(StoreContext $context): View
    {
        $store = $context->getStore();
        $storeRouteParams = ['store_slug' => $store->slug];

        $report = $this->reconciliation->report($store);
        $history = $this->reconciliation->recent($store);
        $exportUrl = route('pos.reconciliation.export', $storeRouteParams);

        return view('pos.reconciliation', compact('store', 'storeRouteParams', 'report', 'history', 'exportUrl'));
    }

    /**
     * Export reconciliation report to XLSX or CSV.
     */
    public function export(Request $request, StoreContext $context, string $store_slug): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        $report = $this->reconciliation->report($store);

        if ($request->query('format') === 'csv') {
            return $this->exportCsv($store, $report);
        }

        return $this->exportXlsx($store, $report);
    }

    private function exportCsv(Store $store, array $report): StreamedResponse
    {
        $filename = 'stock_reconciliation_' . $store->slug . '_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($report) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($stream, [
                '#',
                'Product Name',
                'SKU',
                'Imported Qty',
                'Recorded Qty',
                'Discrepancy (Diff)',
                'On Hand Stock',
                'Status',
            ]);

            foreach (($report['rows'] ?? []) as $index => $row) {
                $hasDiff = abs((float) ($row['diff'] ?? 0)) > 0.0001;
                fputcsv($stream, [
                    $index + 1,
                    $row['product_name'] ?? '',
                    $row['sku'] ?? '',
                    (float) ($row['imported'] ?? $row['imported_qty'] ?? 0),
                    (float) ($row['recorded'] ?? $row['recorded_qty'] ?? 0),
                    (float) ($row['diff'] ?? 0),
                    (float) ($row['on_hand'] ?? 0),
                    $hasDiff ? 'Mismatch' : 'Clean',
                ]);
            }

            fclose($stream);
        }, $filename, $headers);
    }

    private function exportXlsx(Store $store, array $report): BinaryFileResponse
    {
        $filename = 'stock_reconciliation_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_rec_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Reconciliation');

        $totalProducts = (int)($report['products'] ?? 0);
        $diffProducts = (int)($report['diff_products'] ?? 0);
        $totalDiff = (float)($report['total_diff'] ?? 0);

        // 1. Title Block
        $sheet->setCellValue('A1', $store->name . ' - Opening Stock Reconciliation Report');
        $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A') . ' | Audited Items: ' . $totalProducts . ' | Discrepancies: ' . $diffProducts . ' | Net Variance: ' . ($totalDiff > 0 ? '+' : '') . number_format($totalDiff, 2));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0284C7'); // Sky-600
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        // 2. Table Headers
        $headers = [
            'A4' => '#',
            'B4' => 'Product Name',
            'C4' => 'SKU',
            'D4' => 'Imported Qty',
            'E4' => 'Recorded Qty',
            'F4' => 'Discrepancy (Diff)',
            'G4' => 'On Hand Stock',
            'H4' => 'Status',
        ];

        foreach ($headers as $cell => $headerText) {
            $sheet->setCellValue($cell, $headerText);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0284C7']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BAE6FD']]],
        ];
        $sheet->getStyle('A4:H4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(24);

        // 3. Data Rows
        $row = 5;
        foreach (($report['rows'] ?? []) as $index => $item) {
            $hasDiff = abs((float) ($item['diff'] ?? 0)) > 0.0001;

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item['product_name'] ?? '—');
            $sheet->setCellValue('C' . $row, $item['sku'] ?? '—');
            $sheet->setCellValue('D' . $row, (float) ($item['imported'] ?? $item['imported_qty'] ?? 0));
            $sheet->setCellValue('E' . $row, (float) ($item['recorded'] ?? $item['recorded_qty'] ?? 0));
            $sheet->setCellValue('F' . $row, (float) ($item['diff'] ?? 0));
            $sheet->setCellValue('G' . $row, (float) ($item['on_hand'] ?? 0));
            $sheet->setCellValue('H' . $row, $hasDiff ? 'Mismatch' : 'Clean');

            // Zebra striping / Mismatch highlight
            if ($hasDiff) {
                $sheet->getStyle('A' . $row . ':H' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFFBEB'); // amber-50
            } elseif ($index % 2 === 1) {
                $sheet->getStyle('A' . $row . ':H' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            // Alignments & Number formats
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getFont()->setBold(true)->getColor()->setRGB('0F172A');
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle('D' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00;0.00');

            // Borders
            $sheet->getStyle('A' . $row . ':H' . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('F1F5F9');

            $row++;
        }

        // Auto-fit column widths
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        $spreadsheet->disconnectWorksheets();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function approve(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        try {
            $record = $this->reconciliation->approve($store, $request->user(), $request->input('review_notes'));
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.reconciliation_approved') . ' — ' . $record->reconciliation_number);
    }
}
