<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use App\POS\Enums\InventoryMovementType;
use App\POS\Models\Warehouse;
use App\POS\Services\StockLedgerService;
use App\Services\StoreContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockLedgerController extends Controller
{
    public function __construct(
        protected StockLedgerService $ledgerService,
    ) {
    }

    /**
     * Display the General Stock Movement Ledger Dashboard.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);

        $filters = [
            'search' => $request->input('search'),
            'movement_type' => $request->input('movement_type'),
            'warehouse_id' => $request->input('warehouse_id'),
            'product_id' => $request->input('product_id'),
            'flow' => $request->input('flow', 'all'),
            'from' => $from,
            'to' => $to,
        ];

        $perPage = $request->input('per_page', '25');
        $perPageInt = $perPage === 'all' ? 5000 : (in_array((int) $perPage, [25, 50, 100, 200], true) ? (int) $perPage : 25);

        $metrics = $this->ledgerService->getSummaryMetrics($store, $filters);
        $movements = $this->ledgerService->listMovements($store, $filters, $perPageInt);

        $warehouses = Warehouse::where('store_id', $store->id)->get();
        $movementTypes = InventoryMovementType::cases();

        $selectedProduct = !empty($filters['product_id'])
            ? Product::where('store_id', $store->id)->find($filters['product_id'])
            : null;

        // Calculate active filters count for toolbar badge
        $activeFiltersCount = 0;
        if (!empty($filters['search'])) $activeFiltersCount++;
        if (!empty($filters['movement_type'])) $activeFiltersCount++;
        if (!empty($filters['warehouse_id'])) $activeFiltersCount++;
        if (!empty($filters['product_id'])) $activeFiltersCount++;
        if (($filters['flow'] ?? 'all') !== 'all') $activeFiltersCount++;
        if ($preset !== 'this_month') $activeFiltersCount++;

        $exportUrl = route('store.admin.stock_ledger.export', array_merge(['store_slug' => $store->slug], $request->all()));

        return view('admin.stock_ledger.index', compact(
            'store',
            'movements',
            'metrics',
            'filters',
            'preset',
            'from',
            'to',
            'warehouses',
            'movementTypes',
            'selectedProduct',
            'activeFiltersCount',
            'exportUrl',
            'perPage'
        ));
    }

    /**
     * Display the Product-Specific Bin Card timeline with running balance.
     */
    public function binCard(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $productId = (int) ($request->route('product') ?: $request->input('product_id'));

        if (!$productId) {
            $firstProduct = Product::where('store_id', $store->id)->orderBy('name')->first();
            $productId = $firstProduct?->id;
        }

        $product = Product::where('store_id', $store->id)
            ->with(['category', 'brand'])
            ->findOrFail($productId);

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $warehouseId = $request->input('warehouse_id') ? (int) $request->input('warehouse_id') : null;

        $binCardData = $this->ledgerService->getProductBinCard($store, $product, $from, $to, $warehouseId);
        $warehouses = Warehouse::where('store_id', $store->id)->get();

        $products = Product::where('store_id', $store->id)->orderBy('name')->take(50)->get();

        return view('admin.stock_ledger.bin_card', compact(
            'store',
            'product',
            'binCardData',
            'preset',
            'from',
            'to',
            'warehouseId',
            'warehouses',
            'products'
        ));
    }

    /**
     * Export movements to XLSX or CSV using BinaryFileResponse.
     */
    public function export(StoreContext $context, Request $request): BinaryFileResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        [$from, $to] = $this->resolveDateRange($request);

        $filters = [
            'search' => $request->input('search'),
            'movement_type' => $request->input('movement_type'),
            'warehouse_id' => $request->input('warehouse_id'),
            'product_id' => $request->input('product_id'),
            'flow' => $request->input('flow', 'all'),
            'from' => $from,
            'to' => $to,
        ];

        $format = strtolower((string) $request->input('format', 'csv'));
        $movements = $this->ledgerService->listMovements($store, $filters, 5000);

        $headers = [
            __('messages.stock_ledger_date'),
            __('messages.product'),
            __('messages.sku'),
            __('messages.stock_ledger_movement_type'),
            __('messages.stock_ledger_delta_qty'),
            __('messages.stock_ledger_unit_cost') . ' (MMK)',
            __('messages.stock_ledger_total_value') . ' (MMK)',
            __('messages.stock_ledger_reference'),
            __('messages.stock_ledger_posted_by'),
            __('messages.stock_ledger_transaction_id'),
        ];

        $rows = [];
        foreach ($movements as $m) {
            $delta = (float) $m->quantity_delta;
            $cost = (float) $m->unit_cost;
            $value = round(abs($delta) * $cost, 2);

            $rows[] = [
                $m->occurred_at ? $m->occurred_at->format('Y-m-d H:i:s') : '',
                $m->product?->name ?? 'N/A',
                $m->product?->sku ?? '-',
                __('messages.movement_type_' . $m->movement_type),
                $delta,
                $cost,
                $value,
                $m->source_type ? ($m->source_type . ($m->source_id ? " #{$m->source_id}" : '')) : '-',
                $m->postedBy?->name ?? 'System',
                $m->client_transaction_id ?? '-',
            ];
        }

        if ($format === 'xlsx' || $format === 'excel') {
            $filename = 'stock-ledger-' . $store->slug . '-' . now()->format('Ymd-His') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'datapos_stock_');

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Stock Ledger');

            $sheet->fromArray([$headers], null, 'A1');
            if (!empty($rows)) {
                $sheet->fromArray($rows, null, 'A2');
            }

            // Style headers
            $highestCol = $sheet->getHighestColumn();
            $sheet->getStyle("A1:{$highestCol}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(28);

            foreach (range('A', $highestCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => (string) filesize($tempFile),
            ])->deleteFileAfterSend(true);
        }

        // CSV fallback
        $filename = 'stock-ledger-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_stock_csv_');
        $handle = fopen($tempFile, 'w');
        // UTF-8 BOM
        fputs($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) filesize($tempFile),
        ])->deleteFileAfterSend(true);
    }

    /**
     * Printable A4 Bin Card.
     */
    public function printBinCard(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $productId = (int) ($request->route('product') ?: $request->input('product_id'));
        $product = Product::where('store_id', $store->id)->findOrFail($productId);

        [$from, $to] = $this->resolveDateRange($request);
        $warehouseId = $request->input('warehouse_id') ? (int) $request->input('warehouse_id') : null;

        $binCardData = $this->ledgerService->getProductBinCard($store, $product, $from, $to, $warehouseId);

        return view('admin.stock_ledger.print_bin_card', compact('store', 'product', 'binCardData', 'from', 'to'));
    }

    /**
     * Resolve date range filter from request presets.
     */
    protected function resolveDateRange(Request $request): array
    {
        $preset = $request->input('preset', 'this_month');

        return match ($preset) {
            'today' => [now()->startOfDay(), now()->endOfDay(), 'today'],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay(), 'yesterday'],
            '7days' => [now()->subDays(6)->startOfDay(), now()->endOfDay(), '7days'],
            '30days' => [now()->subDays(29)->startOfDay(), now()->endOfDay(), '30days'],
            'this_month' => [now()->startOfMonth()->startOfDay(), now()->endOfMonth()->endOfDay(), 'this_month'],
            'last_month' => [now()->subMonth()->startOfMonth()->startOfDay(), now()->subMonth()->endOfMonth()->endOfDay(), 'last_month'],
            'all' => [null, null, 'all'],
            'custom' => [
                $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth(),
                $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay(),
                'custom',
            ],
            default => [now()->startOfMonth()->startOfDay(), now()->endOfMonth()->endOfDay(), 'this_month'],
        };
    }
}
