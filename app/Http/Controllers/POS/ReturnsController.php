<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Models\PosReturn;
use App\POS\Models\PosSale;
use App\Services\StoreContext;
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
 * Sales Returns management (roadmap Phase 2).
 *
 * The refund posting machinery lives in PosReturnService (atomic return
 * document + `sales_return` ledger movements + cash/credit refunds + sale
 * status update). This controller is the management layer on top of it:
 *
 *   GET /pos/returns            — history of all posted returns
 *   GET /pos/returns/export     — download Excel (.xlsx) / CSV export
 *   GET /pos/returns/new        — pick a posted sale to refund (leads to the
 *                                 existing sale-scoped refund form)
 *   GET /pos/returns/{return}   — detail of a single posted return
 *
 * Returns are immutable once posted (PosReturnService guarantees it), so no
 * store/update/destroy routes are exposed here.
 */
class ReturnsController extends Controller
{
    /** Whitelist of per-page sizes, consistent with the rest of the POS lists. */
    private const PER_PAGE_OPTIONS = [25, 50, 100];

    public function index(Request $request, StoreContext $context, string $store_slug): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();
        $search = $request->input('search', '');

        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 25;
        }

        $query = PosReturn::where('store_id', $store->id)
            ->with(['sale', 'items', 'cashier', 'payments']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('refund_number', 'like', "%{$search}%")
                    ->orWhereHas('sale', function ($sq) use ($search) {
                        $sq->where('receipt_number', 'like', "%{$search}%");
                    });
            });
        }

        $returns = $query->orderByDesc('posted_at')->paginate($perPage);

        // Stat cards: one-shot aggregates over the store's posted returns.
        $summary = [
            'total'    => PosReturn::where('store_id', $store->id)->count(),
            'refunded' => number_format((float) PosReturn::where('store_id', $store->id)->sum('total'), 2, '.', ''),
            'today'    => PosReturn::where('store_id', $store->id)
                ->whereDate('posted_at', now()->toDateString())
                ->count(),
        ];

        $exportUrl = route('pos.returns.export', array_merge($storeRouteParams, request()->only(['search'])));

        return view('pos.returns.index', compact('store', 'storeRouteParams', 'returns', 'search', 'summary', 'exportUrl'));
    }

    /**
     * Export Returns to XLSX or CSV.
     */
    public function export(Request $request, StoreContext $context, string $store_slug): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        $search = trim((string) $request->input('search', ''));
        $format = strtolower((string) $request->input('format', 'xlsx'));

        $query = PosReturn::where('store_id', $store->id)
            ->with(['sale', 'items', 'cashier', 'customer', 'payments']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('refund_number', 'like', "%{$search}%")
                    ->orWhereHas('sale', function ($sq) use ($search) {
                        $sq->where('receipt_number', 'like', "%{$search}%");
                    });
            });
        }

        $returns = $query->orderByDesc('posted_at')->get();

        if ($format === 'csv') {
            return $this->exportCsv($store, $returns);
        }

        return $this->exportXlsx($store, $returns);
    }

    /**
     * Export to CSV stream.
     */
    private function exportCsv(Store $store, $returns): StreamedResponse
    {
        $filename = 'sales_returns_' . $store->slug . '_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($returns) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($stream, [
                '#',
                'Refund Number',
                'Sale Receipt',
                'Customer',
                'Items Count',
                'Total Refunded (MMK)',
                'Refund Methods',
                'Cashier',
                'Date & Time',
                'Notes',
            ]);

            foreach ($returns as $index => $return) {
                $methods = $return->payments->map(function ($p) {
                    return ucfirst($p->method) . ': ' . number_format((float) $p->amount);
                })->implode(', ');

                fputcsv($stream, [
                    $index + 1,
                    $return->refund_number,
                    $return->sale?->receipt_number ?? '',
                    $return->customer?->name ?? '',
                    $return->items->sum('quantity'),
                    (float) $return->total,
                    $methods,
                    $return->cashier?->name ?? '',
                    $return->posted_at?->format('d/m/Y H:i') ?? '',
                    $return->notes ?? '',
                ]);
            }

            fclose($stream);
        }, $filename, $headers);
    }

    /**
     * Export to styled Excel (.xlsx).
     */
    private function exportXlsx(Store $store, $returns): BinaryFileResponse
    {
        $filename = 'sales_returns_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_returns_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Returns');

        // 1. Title Block
        $sheet->setCellValue('A1', $store->name . ' - Sales Returns Report');
        $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A') . ' | Total Returns: ' . $returns->count() . ' | Total Refunded: Ks ' . number_format((float) $returns->sum('total')));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0284C7'); // Sky-600
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        // 2. Table Headers
        $headers = [
            'A4' => '#',
            'B4' => 'Return Number',
            'C4' => 'Sale Receipt',
            'D4' => 'Customer',
            'E4' => 'Items Count',
            'F4' => 'Total Amount (MMK)',
            'G4' => 'Refund Methods',
            'H4' => 'Cashier',
            'I4' => 'Date & Time',
            'J4' => 'Notes',
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
        $sheet->getStyle('A4:J4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(24);

        // 3. Data Rows
        $row = 5;
        foreach ($returns as $index => $return) {
            $methods = $return->payments->map(function ($p) {
                return ucfirst($p->method) . ': ' . number_format((float) $p->amount);
            })->implode(', ');

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $return->refund_number);
            $sheet->setCellValue('C' . $row, $return->sale?->receipt_number ?? '—');
            $sheet->setCellValue('D' . $row, $return->customer?->name ?? 'Walk-in Customer');
            $sheet->setCellValue('E' . $row, $return->items->sum('quantity'));
            $sheet->setCellValue('F' . $row, (float) $return->total);
            $sheet->setCellValue('G' . $row, $methods ?: 'Cash');
            $sheet->setCellValue('H' . $row, $return->cashier?->name ?? '—');
            $sheet->setCellValue('I' . $row, $return->posted_at?->format('d/m/Y H:i') ?? '—');
            $sheet->setCellValue('J' . $row, $return->notes ?? '');

            // Row Zebra Striping
            if ($index % 2 === 1) {
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            // Alignments & Number formats
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getFont()->setBold(true)->getColor()->setRGB('0369A1');
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle('A' . $row . ':J' . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');

            $row++;
        }

        // 4. Totals Footer
        if ($returns->isNotEmpty()) {
            $sheet->setCellValue('A' . $row, 'TOTAL');
            $sheet->mergeCells('A' . $row . ':E' . $row);
            $sheet->setCellValue('F' . $row, (float) $returns->sum('total'));

            $footerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => '0F172A'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0F2FE']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_RIGHT],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BAE6FD']]],
            ];
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($footerStyle);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // 5. Auto-size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Pick the posted sale to return. Clicking a row opens the existing
     * sale-scoped refund form (pos.refund.create) which pre-fills the
     * refundable quantities — no refund logic is duplicated here.
     */
    public function create(Request $request, StoreContext $context, string $store_slug): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();
        $search = $request->input('search', '');

        $query = PosSale::where('store_id', $store->id)
            ->whereIn('status', ['posted', 'partially_refunded'])
            ->with(['customer']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $sales = $query->orderByDesc('posted_at')->limit(50)->get();

        return view('pos.returns.select_sale', compact('store', 'storeRouteParams', 'sales', 'search'));
    }

    public function show(StoreContext $context, string $store_slug, PosReturn $return): View
    {
        $store = $context->getStore();

        if ((int) $return->store_id !== (int) $store->id) {
            abort(404);
        }

        $return->load(['sale', 'items', 'payments', 'cashier', 'customer']);

        return view('pos.returns.show', [
            'store' => $store,
            'storeRouteParams' => $context->getRouteParams(),
            'return' => $return,
        ]);
    }
}
