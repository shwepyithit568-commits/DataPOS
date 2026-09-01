<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Store;
use App\POS\Models\Branch;
use App\POS\Models\StockCount;
use App\POS\Models\StockCountLine;
use App\POS\Models\Warehouse;
use App\POS\Services\StockCountService;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
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

class StockCountController extends Controller
{
    public function __construct(
        protected StockCountService $stockCountService,
    ) {
    }

    /**
     * Display listing of stock count sessions and summary stats.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $search = $request->input('search');
        $status = $request->input('status');
        $scope = $request->input('scope');
        $sort = $request->input('sort', 'newest');
        $perPage = $request->input('per_page', 15);
        $activeView = $request->input('view', 'table');

        $filters = [
            'search' => $search,
            'status' => $status,
            'scope' => $scope,
            'sort' => $sort,
            'per_page' => $perPage,
        ];

        $activeFiltersCount = collect([$search, $status, $scope])->filter(fn ($v) => !empty($v))->count();

        $stats = $this->stockCountService->getStatistics($store);
        $sessions = $this->stockCountService->listSessions($store, $search, $status, $perPage, $scope, $sort);

        return view('admin.stock_count.index', compact(
            'store',
            'sessions',
            'stats',
            'filters',
            'search',
            'status',
            'scope',
            'sort',
            'perPage',
            'activeView',
            'activeFiltersCount'
        ));
    }

    /**
     * Show the session creation form.
     */
    public function create(StoreContext $context): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $categories = Category::where('store_id', $store->id)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $branches = Branch::where('store_id', $store->id)->where('is_active', true)->get();
        $warehouses = Warehouse::where('store_id', $store->id)->where('is_active', true)->get();

        return view('admin.stock_count.create', compact('store', 'categories', 'branches', 'warehouses'));
    }

    /**
     * Store a newly created stock count session.
     */
    public function store(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'scope' => 'required|in:all,category',
            'category_ids' => 'nullable|array',
            'category_ids.*' => ['integer', \Illuminate\Validation\Rule::exists('categories', 'id')->where('store_id', $store->id)],
            'branch_id' => ['nullable', \Illuminate\Validation\Rule::exists('branches', 'id')->where('store_id', $store->id)],
            'warehouse_id' => ['nullable', \Illuminate\Validation\Rule::exists('warehouses', 'id')->where('store_id', $store->id)],
            'notes' => 'nullable|string|max:1000',
        ]);

        $session = $this->stockCountService->createSession($store, $validated, auth()->user());

        return redirect()
            ->route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id])
            ->with('success', __('messages.stock_count_session_created', ['number' => $session->session_number]));
    }

    /**
     * Display the interactive stock take sheet.
     */
    public function show(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $stock_count = (int) $request->route('stock_count');
        $session = StockCount::where('store_id', $store->id)
            ->where('id', $stock_count)
            ->with(['createdBy', 'approvedBy', 'branch', 'warehouse'])
            ->firstOrFail();

        $tab = $request->input('tab', 'all');
        $search = $request->input('search');
        $categoryId = $request->input('category_id');

        $linesQuery = StockCountLine::query()
            ->where('stock_count_id', $session->id)
            ->with(['product', 'category', 'variant']);

        if ($tab === 'counted') {
            $linesQuery->where('is_counted', true);
        } elseif ($tab === 'variance') {
            $linesQuery->where('is_counted', true)->where('variance_quantity', '!=', 0);
        } elseif ($tab === 'uncounted') {
            $linesQuery->where('is_counted', false);
        }

        if ($categoryId) {
            $linesQuery->where('category_id', $categoryId);
        }

        if ($search && trim($search) !== '') {
            $term = trim($search);
            $linesQuery->whereHas('product', function ($pq) use ($term) {
                $pq->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            });
        }

        $lines = $linesQuery->orderBy('id')->paginate(50)->withQueryString();

        $sessionCategories = Category::where('store_id', $store->id)
            ->whereIn('id', StockCountLine::where('stock_count_id', $session->id)->pluck('category_id')->unique())
            ->get();

        return view('admin.stock_count.show', compact('store', 'session', 'lines', 'tab', 'search', 'categoryId', 'sessionCategories'));
    }

    /**
     * AJAX endpoint to update a single count line.
     */
    public function updateLine(StoreContext $context, Request $request): JsonResponse
    {
        $store = $context->getStore();
        if (!$store) {
            return response()->json(['error' => 'Store not found'], 404);
        }

        $stock_count = (int) $request->route('stock_count');
        $line = (int) $request->route('line');

        $session = StockCount::where('store_id', $store->id)->where('id', $stock_count)->firstOrFail();

        $validated = $request->validate([
            'counted_quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $updatedLine = $this->stockCountService->saveCountLine(
                $session,
                $line,
                (float) $validated['counted_quantity'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => __('messages.stock_count_saved'),
                'line' => [
                    'id' => $updatedLine->id,
                    'counted_quantity' => (float) $updatedLine->counted_quantity,
                    'variance_quantity' => (float) $updatedLine->variance_quantity,
                    'variance_cost' => (float) $updatedLine->variance_cost,
                    'is_counted' => $updatedLine->is_counted,
                ],
                'session' => [
                    'total_items' => $session->total_items,
                    'counted_items' => $session->counted_items,
                    'variance_items' => $session->variance_items,
                    'total_variance_qty' => (float) $session->total_variance_qty,
                    'total_variance_cost' => (float) $session->total_variance_cost,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Batch save counts from the sheet form.
     */
    public function bulkUpdate(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $stock_count = (int) $request->route('stock_count');
        $session = StockCount::where('store_id', $store->id)->where('id', $stock_count)->firstOrFail();

        $counts = $request->input('lines', []);
        if (is_array($counts) && !empty($counts)) {
            $this->stockCountService->bulkSaveCounts($session, $counts);
        }

        return redirect()
            ->route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id, 'tab' => $request->input('tab', 'all')])
            ->with('success', __('messages.stock_count_saved'));
    }

    /**
     * Barcode/SKU live scanner lookup endpoint.
     */
    public function quickScan(StoreContext $context, Request $request): JsonResponse
    {
        $store = $context->getStore();
        if (!$store) {
            return response()->json([], 404);
        }

        $stock_count = (int) $request->route('stock_count');
        $session = StockCount::where('store_id', $store->id)->where('id', $stock_count)->firstOrFail();
        $query = (string) $request->input('q', '');

        $results = $this->stockCountService->quickScan($session, $query);

        return response()->json($results);
    }

    /**
     * Approve and reconcile physical stock count adjustments.
     */
    public function approve(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $stock_count = (int) $request->route('stock_count');
        $session = StockCount::where('store_id', $store->id)->where('id', $stock_count)->firstOrFail();

        try {
            $this->stockCountService->approveAndReconcile($session, auth()->user());

            return redirect()
                ->route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id])
                ->with('success', __('messages.stock_count_approved_success', ['number' => $session->session_number]));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel an in-progress session.
     */
    public function cancel(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $stock_count = (int) $request->route('stock_count');
        $session = StockCount::where('store_id', $store->id)->where('id', $stock_count)->firstOrFail();

        try {
            $this->stockCountService->cancelSession($session);

            return redirect()
                ->route('store.admin.stock_count.index', ['store_slug' => $store->slug])
                ->with('success', __('messages.stock_count_cancelled'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Printable physical stock audit sheet.
     */
    public function printSheet(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $stock_count = (int) $request->route('stock_count');
        $session = StockCount::where('store_id', $store->id)
            ->where('id', $stock_count)
            ->with(['createdBy', 'approvedBy', 'branch', 'warehouse', 'lines.product', 'lines.category'])
            ->firstOrFail();

        return view('admin.stock_count.print', compact('store', 'session'));
    }

    /**
     * Export stock count sessions list to XLSX or CSV.
     */
    public function export(StoreContext $context, Request $request): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $search = $request->input('search');
        $status = $request->input('status');
        $scope = $request->input('scope');
        $sort = $request->input('sort', 'newest');
        $format = strtolower((string) $request->input('format', 'xlsx'));

        $sessions = $this->stockCountService->listSessions($store, $search, $status, 5000, $scope, $sort);

        $headers = [
            __('messages.stock_count_session_number') ?? 'Session Number',
            __('messages.stock_count_scope') ?? 'Scope',
            __('messages.location') ?? 'Location',
            __('messages.status') ?? 'Status',
            __('messages.stock_count_total_items') ?? 'Total Items',
            __('messages.stock_count_counted_items') ?? 'Counted Lines',
            __('messages.stock_count_variance_items') ?? 'Discrepancy Lines',
            __('messages.stock_count_variance_qty') ?? 'Variance Qty',
            __('messages.stock_count_variance_cost') . ' (MMK)' ?? 'Variance Cost (MMK)',
            __('messages.stock_count_created_by') ?? 'Created By',
            __('messages.created_at') ?? 'Created Date',
            __('messages.stock_count_approved_by') ?? 'Approved By',
            __('messages.stock_count_approved_at') ?? 'Approved Date',
            __('messages.notes') ?? 'Notes',
        ];

        $rows = [];
        foreach ($sessions as $s) {
            $location = $s->warehouse?->name ?? ($s->branch?->name ?? 'Entire Store');
            $statusLabel = match ($s->status) {
                StockCount::STATUS_APPROVED => __('messages.stock_count_status_approved') ?? 'Approved',
                StockCount::STATUS_CANCELLED => __('messages.stock_count_status_cancelled') ?? 'Cancelled',
                default => __('messages.stock_count_status_in_progress') ?? 'In Progress',
            };

            $rows[] = [
                $s->session_number,
                $s->scope === StockCount::SCOPE_CATEGORY ? __('messages.stock_count_scope_category') : __('messages.stock_count_scope_all'),
                $location,
                $statusLabel,
                (int) $s->total_items,
                (int) $s->counted_items,
                (int) $s->variance_items,
                (float) $s->total_variance_qty,
                (float) $s->total_variance_cost,
                $s->createdBy?->name ?? 'System',
                $s->created_at ? $s->created_at->format('Y-m-d H:i:s') : '',
                $s->approvedBy?->name ?? '-',
                $s->approved_at ? $s->approved_at->format('Y-m-d H:i:s') : '-',
                $s->notes ?? '',
            ];
        }

        if ($format === 'xlsx' || $format === 'excel') {
            $filename = 'stock-counts-' . $store->slug . '-' . now()->format('Ymd-His') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'datapos_sc_');

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Stock Counts');

            $sheet->fromArray([$headers], null, 'A1');
            if (!empty($rows)) {
                $sheet->fromArray($rows, null, 'A2');
            }

            $lastCol = 'N';
            $lastRow = max(1, count($rows) + 1);

            $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(26);

            if (!empty($rows)) {
                $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Right align numeric columns E, F, G, H, I
                $sheet->getStyle("E2:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("E2:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("H2:H{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.000');
                $sheet->getStyle("I2:I{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            }

            foreach (range('A', $lastCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // CSV export
        $filename = 'stock-counts-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv';
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Export detailed stock count session lines to XLSX or CSV.
     */
    public function exportSession(StoreContext $context, Request $request): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $stock_count = (int) $request->route('stock_count');
        $stockCount = StockCount::where('store_id', $store->id)
            ->where('id', $stock_count)
            ->firstOrFail();

        $format = strtolower((string) $request->input('format', 'xlsx'));
        $lines = $stockCount->lines()->with(['product', 'variant', 'category'])->get();

        $headers = [
            __('messages.product') ?? 'Product Name',
            __('messages.sku') ?? 'SKU',
            __('messages.barcode') ?? 'Barcode',
            __('messages.category') ?? 'Category',
            __('messages.stock_count_system_qty') ?? 'Expected Qty',
            __('messages.stock_count_counted_qty') ?? 'Physical Counted',
            __('messages.stock_count_variance_qty') ?? 'Variance Qty',
            __('messages.stock_count_unit_cost') . ' (MMK)' ?? 'Unit Cost (MMK)',
            __('messages.stock_count_variance_cost') . ' (MMK)' ?? 'Variance Value (MMK)',
            __('messages.status') ?? 'Status',
            __('messages.stock_count_counted_at') ?? 'Counted At',
            __('messages.notes') ?? 'Notes',
        ];

        $rows = [];
        foreach ($lines as $line) {
            $rows[] = [
                $line->product?->name ?? 'N/A',
                $line->variant?->sku ?? ($line->product?->sku ?? '-'),
                $line->variant?->barcode ?? ($line->product?->barcode ?? '-'),
                $line->category?->name ?? '-',
                (float) $line->system_quantity,
                $line->is_counted ? (float) $line->counted_quantity : 0,
                $line->is_counted ? (float) $line->variance_quantity : 0,
                (float) $line->unit_cost,
                $line->is_counted ? (float) $line->variance_cost : 0,
                $line->is_counted ? (__('messages.stock_count_counted') ?? 'Counted') : (__('messages.stock_count_pending') ?? 'Pending'),
                $line->counted_at ? $line->counted_at->format('Y-m-d H:i:s') : '-',
                $line->notes ?? '',
            ];
        }

        if ($format === 'xlsx' || $format === 'excel') {
            $filename = 'stock-count-sheet-' . $stockCount->session_number . '-' . now()->format('Ymd-His') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'datapos_sc_detail_');

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($stockCount->session_number);

            $sheet->fromArray([$headers], null, 'A1');
            if (!empty($rows)) {
                $sheet->fromArray($rows, null, 'A2');
            }

            $lastCol = 'L';
            $lastRow = max(1, count($rows) + 1);

            $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(26);

            if (!empty($rows)) {
                $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Right align numeric columns E, F, G, H, I
                $sheet->getStyle("E2:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("E2:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.000');
                $sheet->getStyle("H2:I{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            }

            foreach (range('A', $lastCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // CSV export
        $filename = 'stock-count-sheet-' . $stockCount->session_number . '-' . now()->format('Ymd-His') . '.csv';
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
