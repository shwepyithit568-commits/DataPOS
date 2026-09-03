<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\OpeningStockRequest;
use App\POS\Services\OpeningStockService;
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
 * Opening stock with manager review (MVP Phase 2).
 *
 * GET  /store/{slug}/pos/opening-stock              — submit form + request list
 * GET  /store/{slug}/pos/opening-stock/export       — download Excel (.xlsx) / CSV export
 * POST /store/{slug}/pos/opening-stock              — create a PENDING request
 * POST /store/{slug}/pos/opening-stock/{req}/approve — manager approval → posts
 *                                                      opening_balance movements
 * POST /store/{slug}/pos/opening-stock/{req}/reject  — manager rejection
 */
class OpeningStockController extends Controller
{
    public function __construct(
        protected OpeningStockService $openingStock,
        protected \App\POS\Services\InventoryService $inventory,
    ) {
    }

    public function index(StoreContext $context): View
    {
        $store = $context->getStore();
        $storeRouteParams = ['store_slug' => $store->slug];

        $requests = $this->openingStock->recent($store);

        // Attach each line's current on-hand so the manager can sanity-check.
        $requests->each(function ($req) {
            $req->items->each(function ($item) {
                $item->on_hand = $this->inventory->totalOnHand($item->store_id, $item->product_id, $item->product_variant_id);
            });
        });

        $exportUrl = route('pos.opening-stock.export', $storeRouteParams);

        return view('pos.opening_stock', compact('store', 'storeRouteParams', 'requests', 'exportUrl'));
    }

    /**
     * Export opening stock requests to XLSX or CSV.
     */
    public function export(Request $request, StoreContext $context, string $store_slug): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        $requests = $this->openingStock->recent($store);

        if ($request->query('format') === 'csv') {
            return $this->exportCsv($store, $requests);
        }

        return $this->exportXlsx($store, $requests);
    }

    private function exportCsv(Store $store, $requests): StreamedResponse
    {
        $filename = 'opening_stock_requests_' . $store->slug . '_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($requests) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($stream, [
                '#',
                'Request Number',
                'Submitted By',
                'Items Count',
                'Total Quantity',
                'Total Valuation (MMK)',
                'Status',
                'Submitted At',
                'Reviewed By',
                'Notes',
            ]);

            foreach ($requests as $index => $req) {
                $totalQty = $req->items->sum('quantity');
                fputcsv($stream, [
                    $index + 1,
                    $req->request_number,
                    $req->submitter?->name ?? '—',
                    $req->items->count(),
                    (float) $totalQty,
                    (float) $req->total_cost,
                    ucfirst($req->status),
                    $req->created_at->format('d/m/Y H:i'),
                    $req->approver?->name ?? '—',
                    $req->notes ?? '',
                ]);
            }

            fclose($stream);
        }, $filename, $headers);
    }

    private function exportXlsx(Store $store, $requests): BinaryFileResponse
    {
        $filename = 'opening_stock_requests_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_osr_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Opening Stock Requests');

        $totalRequests = $requests->count();
        $pendingCount = $requests->where('status', 'pending')->count();
        $approvedCount = $requests->where('status', 'approved')->count();
        $totalValuation = $requests->where('status', 'approved')->sum(fn($r) => (float)$r->total_cost);

        // 1. Title Block
        $sheet->setCellValue('A1', $store->name . ' - Opening Stock Requests Report');
        $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A') . ' | Total Requests: ' . $totalRequests . ' | Pending: ' . $pendingCount . ' | Approved: ' . $approvedCount . ' | Total Approved Valuation: Ks ' . number_format($totalValuation));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('059669'); // Emerald-600
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        // 2. Table Headers
        $headers = [
            'A4' => '#',
            'B4' => 'Request Number',
            'C4' => 'Submitted By',
            'D4' => 'Items Count',
            'E4' => 'Total Quantity',
            'F4' => 'Total Valuation (MMK)',
            'G4' => 'Status',
            'H4' => 'Submitted At',
            'I4' => 'Reviewed By',
            'J4' => 'Notes',
        ];

        foreach ($headers as $cell => $headerText) {
            $sheet->setCellValue($cell, $headerText);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'A7F3D0']]],
        ];
        $sheet->getStyle('A4:J4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(24);

        // 3. Data Rows
        $row = 5;
        foreach ($requests as $index => $req) {
            $totalQty = $req->items->sum('quantity');

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $req->request_number);
            $sheet->setCellValue('C' . $row, $req->submitter?->name ?? '—');
            $sheet->setCellValue('D' . $row, $req->items->count());
            $sheet->setCellValue('E' . $row, (float) $totalQty);
            $sheet->setCellValue('F' . $row, (float) $req->total_cost);
            $sheet->setCellValue('G' . $row, ucfirst($req->status));
            $sheet->setCellValue('H' . $row, $req->created_at->format('d/m/Y H:i'));
            $sheet->setCellValue('I' . $row, $req->approver?->name ?? '—');
            $sheet->setCellValue('J' . $row, $req->notes ?? '');

            // Zebra striping
            if ($req->status === 'pending') {
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFFBEB'); // amber-50
            } elseif ($index % 2 === 1) {
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            // Alignments & Number formats
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getFont()->setBold(true)->getColor()->setRGB('0F172A');
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00;0.00');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00;0.00');

            // Borders
            $sheet->getStyle('A' . $row . ':J' . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('F1F5F9');

            $row++;
        }

        // Auto-fit column widths
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        $spreadsheet->disconnectWorksheets();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            // decimal (not plain numeric): the service does bcmath, which
            // throws a ValueError on scientific notation ("1e3").
            'items.*.quantity' => ['required', 'decimal:0,3', 'gt:0'],
            'items.*.unit_cost' => ['required', 'decimal:0,2', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $requestDoc = $this->openingStock->create(
                $store,
                $data['items'],
                $data['notes'] ?? null,
                $request->user(),
            );
        } catch (InventoryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.opening-stock.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.opening_stock_submitted') . ' — ' . $requestDoc->request_number);
    }

    public function approve(Request $request, string $store_slug, OpeningStockRequest $openingStockRequest, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ((int) $openingStockRequest->store_id !== (int) $store->id) {
            abort(404);
        }

        try {
            $this->openingStock->approve($store, $openingStockRequest, $request->user(), $request->input('review_notes'));
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.opening_stock_approved') . ' — ' . $openingStockRequest->request_number);
    }

    public function reject(Request $request, string $store_slug, OpeningStockRequest $openingStockRequest, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ((int) $openingStockRequest->store_id !== (int) $store->id) {
            abort(404);
        }

        try {
            $this->openingStock->reject($store, $openingStockRequest, $request->user(), $request->input('review_notes'));
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.opening_stock_rejected') . ' — ' . $openingStockRequest->request_number);
    }
}
