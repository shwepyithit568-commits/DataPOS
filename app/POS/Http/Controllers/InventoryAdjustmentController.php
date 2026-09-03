<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\InventoryAdjustment;
use App\POS\Services\InventoryAdjustmentService;
use App\POS\Services\InventoryService;
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
 * Inventory adjustments with manager approval (MVP Phase 2 — final module).
 *
 * GET  /store/{slug}/pos/adjustments              — submit form + request list
 * GET  /store/{slug}/pos/adjustments/export       — download Excel (.xlsx) / CSV export
 * POST /store/{slug}/pos/adjustments              — create a PENDING request
 * POST /store/{slug}/pos/adjustments/{adj}/approve — manager approval → posts
 *                                                     adjustment_in/out movements
 * POST /store/{slug}/pos/adjustments/{adj}/reject  — manager rejection
 */
class InventoryAdjustmentController extends Controller
{
    public function __construct(
        protected InventoryAdjustmentService $adjustments,
        protected InventoryService $inventory,
    ) {
    }

    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();

        // Calculate KPI stats across all store adjustment requests
        $allStoreQuery = InventoryAdjustment::query()->where('store_id', $store->id);
        $totalCount = (clone $allStoreQuery)->count();
        $pendingCount = (clone $allStoreQuery)->where('status', 'pending')->count();
        $approvedCount = (clone $allStoreQuery)->where('status', 'approved')->count();
        $rejectedCount = (clone $allStoreQuery)->where('status', 'rejected')->count();
        $netQuantity = (clone $allStoreQuery)->where('status', 'approved')->sum('total_quantity');

        $stats = [
            'total' => $totalCount,
            'pending' => $pendingCount,
            'approved' => $approvedCount,
            'rejected' => $rejectedCount,
            'net_quantity' => (float) $netQuantity,
        ];

        // Filters
        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));
        $sort = $request->query('sort', 'newest');
        $perPageParam = $request->query('per_page', 25);
        $perPage = ($perPageParam === 'all' || (int)$perPageParam > 500) ? 500 : (int) $perPageParam;

        $query = InventoryAdjustment::query()
            ->with(['items.product', 'items.productVariant', 'submittedBy', 'reviewedBy', 'warehouse'])
            ->where('store_id', $store->id);

        if (!empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('review_notes', 'like', "%{$search}%")
                  ->orWhereHas('submittedBy', function ($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('items.product', function ($itemQ) use ($search) {
                      $itemQ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                  });
            });
        }

        match ($sort) {
            'oldest' => $query->oldest(),
            'qty_desc' => $query->orderByDesc('total_quantity'),
            'qty_asc' => $query->orderBy('total_quantity'),
            default => $query->latest(),
        };

        $requests = $query->paginate($perPage)->withQueryString();

        // Attach each line's current on-hand so the manager can sanity-check.
        $requests->getCollection()->each(function ($req) {
            $req->items->each(function ($item) {
                $item->on_hand = $this->inventory->totalOnHand($item->store_id, $item->product_id, $item->product_variant_id);
            });
        });

        $filters = [
            'status' => $status,
            'search' => $search,
            'sort' => $sort,
            'per_page' => $perPageParam,
        ];

        $activeFiltersCount = (!empty($status) && $status !== 'all' ? 1 : 0) + ($search !== '' ? 1 : 0);
        $storeRouteParams = ['store_slug' => $store->slug];
        $exportUrl = route('pos.adjustments.export', array_merge($storeRouteParams, request()->only(['search', 'status', 'sort'])));

        return view('pos.adjustments', compact('store', 'storeRouteParams', 'requests', 'stats', 'filters', 'activeFiltersCount', 'exportUrl'));
    }

    /**
     * Export adjustments to XLSX or CSV.
     */
    public function export(Request $request, StoreContext $context, string $store_slug): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();

        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));
        $sort = $request->query('sort', 'newest');

        $query = InventoryAdjustment::query()
            ->with(['items.product', 'items.productVariant', 'submittedBy', 'reviewedBy'])
            ->where('store_id', $store->id);

        if (!empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('review_notes', 'like', "%{$search}%")
                  ->orWhereHas('submittedBy', function ($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('items.product', function ($itemQ) use ($search) {
                      $itemQ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                  });
            });
        }

        match ($sort) {
            'oldest' => $query->oldest(),
            'qty_desc' => $query->orderByDesc('total_quantity'),
            'qty_asc' => $query->orderBy('total_quantity'),
            default => $query->latest(),
        };

        $adjustments = $query->get();

        if ($request->query('format') === 'csv') {
            return $this->exportCsv($store, $adjustments);
        }

        return $this->exportXlsx($store, $adjustments);
    }

    private function exportCsv(Store $store, $adjustments): StreamedResponse
    {
        $filename = 'stock_adjustments_' . $store->slug . '_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($adjustments) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($stream, [
                '#',
                'Adjustment Number',
                'Status',
                'Total Items',
                'Net Quantity Change',
                'Reason Summary',
                'Submitted By',
                'Reviewed By',
                'Date & Time',
                'Notes',
            ]);

            foreach ($adjustments as $index => $adj) {
                $allReasons = $adj->items->pluck('reason')->filter()->unique()->join(', ');
                fputcsv($stream, [
                    $index + 1,
                    $adj->adjustment_number,
                    ucfirst($adj->status),
                    $adj->items->count(),
                    (float) $adj->total_quantity,
                    $allReasons,
                    $adj->submittedBy?->name ?? '',
                    $adj->reviewedBy?->name ?? '',
                    $adj->created_at?->format('d/m/Y H:i') ?? '',
                    $adj->notes ?? '',
                ]);
            }

            fclose($stream);
        }, $filename, $headers);
    }

    private function exportXlsx(Store $store, $adjustments): BinaryFileResponse
    {
        $filename = 'stock_adjustments_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_adj_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Adjustments');

        // 1. Title Block
        $sheet->setCellValue('A1', $store->name . ' - Stock Adjustments Report');
        $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A') . ' | Total Adjustments: ' . $adjustments->count() . ' | Net Quantity: ' . number_format((float) $adjustments->where('status', 'approved')->sum('total_quantity'), 2));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('D97706'); // Amber-600
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        // 2. Table Headers
        $headers = [
            'A4' => '#',
            'B4' => 'Adjustment Number',
            'C4' => 'Status',
            'D4' => 'Lines Count',
            'E4' => 'Net Quantity Change',
            'F4' => 'Reason Summary',
            'G4' => 'Submitted By',
            'H4' => 'Reviewed By',
            'I4' => 'Date & Time',
            'J4' => 'Notes',
        ];

        foreach ($headers as $cell => $headerText) {
            $sheet->setCellValue($cell, $headerText);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D97706']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FDE68A']]],
        ];
        $sheet->getStyle('A4:J4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(24);

        // 3. Data Rows
        $row = 5;
        foreach ($adjustments as $index => $adj) {
            $allReasons = $adj->items->pluck('reason')->filter()->unique()->join(', ');

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $adj->adjustment_number);
            $sheet->setCellValue('C' . $row, ucfirst($adj->status));
            $sheet->setCellValue('D' . $row, $adj->items->count());
            $sheet->setCellValue('E' . $row, (float) $adj->total_quantity);
            $sheet->setCellValue('F' . $row, $allReasons ?: '—');
            $sheet->setCellValue('G' . $row, $adj->submittedBy?->name ?? '—');
            $sheet->setCellValue('H' . $row, $adj->reviewedBy?->name ?? '—');
            $sheet->setCellValue('I' . $row, $adj->created_at?->format('d/m/Y H:i') ?? '—');
            $sheet->setCellValue('J' . $row, $adj->notes ?? '');

            // Row Zebra Striping
            if ($index % 2 === 1) {
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFFBEB'); // amber-50
            }

            // Alignments
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getFont()->setBold(true)->getColor()->setRGB('B45309');
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00;0.00');

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
            // decimal (not plain numeric): bcmath throws a ValueError on
            // scientific notation ("1e3"). Sign is allowed (negative = count
            // down), which the decimal rule permits.
            'items.*.quantity' => ['required', 'decimal:0,3', 'not_in:0'],
            'items.*.reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $requestDoc = $this->adjustments->create(
                $store,
                $data['items'],
                $data['notes'] ?? null,
                $request->user(),
            );
        } catch (InventoryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.adjustments.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.adjustment_submitted') . ' — ' . $requestDoc->adjustment_number);
    }

    public function approve(Request $request, string $store_slug, InventoryAdjustment $inventoryAdjustment, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ((int) $inventoryAdjustment->store_id !== (int) $store->id) {
            abort(404);
        }

        try {
            $this->adjustments->approve($store, $inventoryAdjustment, $request->user(), $request->input('review_notes'));
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.adjustment_approved') . ' — ' . $inventoryAdjustment->adjustment_number);
    }

    public function reject(Request $request, string $store_slug, InventoryAdjustment $inventoryAdjustment, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ((int) $inventoryAdjustment->store_id !== (int) $store->id) {
            abort(404);
        }

        try {
            $this->adjustments->reject($store, $inventoryAdjustment, $request->user(), $request->input('review_notes'));
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.adjustment_rejected') . ' — ' . $inventoryAdjustment->adjustment_number);
    }
}
