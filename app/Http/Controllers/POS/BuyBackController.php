<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\BuyBack;
use App\POS\Models\BuyBackItem;
use App\POS\Models\Warehouse;
use App\POS\Services\InventoryService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BuyBackController extends Controller
{
    private const PER_PAGE_OPTIONS = [25, 50, 100];

    public function __construct(
        protected InventoryService $inventory
    ) {}

    public function index(Request $request, StoreContext $context, string $store_slug): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 25;
        }

        $query = BuyBack::where('store_id', $store->id)
            ->with(['creator', 'customer', 'items.product']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('buyback_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '' && in_array($status, ['pending', 'completed', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        $buybacks = $query->orderBy($sort, $direction)->paginate($perPage);

        // One-shot summary aggregates for 4 KPI stat cards
        $summary = [
            'total'       => BuyBack::where('store_id', $store->id)->count(),
            'total_value' => (float) BuyBack::where('store_id', $store->id)->sum('total_value'),
            'pending'     => BuyBack::where('store_id', $store->id)->where('status', 'pending')->count(),
            'completed'   => BuyBack::where('store_id', $store->id)->where('status', 'completed')->count(),
            'cancelled'   => BuyBack::where('store_id', $store->id)->where('status', 'cancelled')->count(),
        ];

        $exportUrl = route('pos.buybacks.export', array_merge($storeRouteParams, request()->only(['search', 'status'])));

        return view('pos.buybacks.index', compact('store', 'storeRouteParams', 'buybacks', 'search', 'status', 'summary', 'exportUrl'));
    }

    /**
     * Export BuyBack records to XLSX or CSV.
     */
    public function export(Request $request, StoreContext $context, string $store_slug): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $format = strtolower((string) $request->input('format', 'xlsx'));

        $query = BuyBack::where('store_id', $store->id)
            ->with(['creator', 'customer', 'items.product']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('buyback_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '' && in_array($status, ['pending', 'completed', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        $buybacks = $query->orderByDesc('created_at')->get();

        if ($format === 'csv') {
            return $this->exportCsv($store, $buybacks);
        }

        return $this->exportXlsx($store, $buybacks);
    }

    private function exportCsv(Store $store, $buybacks): StreamedResponse
    {
        $filename = 'buybacks_' . $store->slug . '_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($buybacks) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($stream, [
                '#',
                'BuyBack Number',
                'Customer',
                'Items Count',
                'Total Value (MMK)',
                'Status',
                'Reason / Notes',
                'Created By',
                'Date & Time',
            ]);

            foreach ($buybacks as $index => $bb) {
                fputcsv($stream, [
                    $index + 1,
                    $bb->buyback_number,
                    $bb->customer?->name ?? 'Walk-in Customer',
                    $bb->items->count(),
                    (float) $bb->total_value,
                    ucfirst($bb->status),
                    $bb->reason ?: $bb->notes,
                    $bb->creator?->name ?? '',
                    $bb->created_at?->format('d/m/Y H:i') ?? '',
                ]);
            }

            fclose($stream);
        }, $filename, $headers);
    }

    private function exportXlsx(Store $store, $buybacks): BinaryFileResponse
    {
        $filename = 'buybacks_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_buybacks_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Buy-Backs');

        // 1. Title Block
        $sheet->setCellValue('A1', $store->name . ' - Customer Buy-Backs Report');
        $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A') . ' | Total BuyBacks: ' . $buybacks->count() . ' | Total Value: Ks ' . number_format((float) $buybacks->sum('total_value')));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0284C7'); // Sky-600
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        // 2. Table Headers
        $headers = [
            'A4' => '#',
            'B4' => 'BuyBack Number',
            'C4' => 'Customer',
            'D4' => 'Items Count',
            'E4' => 'Total Value (MMK)',
            'F4' => 'Status',
            'G4' => 'Reason',
            'H4' => 'Created By',
            'I4' => 'Date & Time',
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
        $sheet->getStyle('A4:I4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(24);

        // 3. Data Rows
        $row = 5;
        foreach ($buybacks as $index => $bb) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $bb->buyback_number);
            $sheet->setCellValue('C' . $row, $bb->customer?->name ?? 'Walk-in Customer');
            $sheet->setCellValue('D' . $row, $bb->items->count());
            $sheet->setCellValue('E' . $row, (float) $bb->total_value);
            $sheet->setCellValue('F' . $row, ucfirst($bb->status));
            $sheet->setCellValue('G' . $row, $bb->reason ?: ($bb->notes ?: '—'));
            $sheet->setCellValue('H' . $row, $bb->creator?->name ?? '—');
            $sheet->setCellValue('I' . $row, $bb->created_at?->format('d/m/Y H:i') ?? '—');

            // Row Zebra Striping
            if ($index % 2 === 1) {
                $sheet->getStyle('A' . $row . ':I' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            // Alignments & Number formats
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getFont()->setBold(true)->getColor()->setRGB('0369A1');
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');

            $row++;
        }

        // 4. Totals Footer
        if ($buybacks->isNotEmpty()) {
            $sheet->setCellValue('A' . $row, 'TOTAL');
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->setCellValue('E' . $row, (float) $buybacks->sum('total_value'));

            $footerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => '0F172A'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0F2FE']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_RIGHT],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BAE6FD']]],
            ];
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($footerStyle);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // 5. Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ])->deleteFileAfterSend(true);
    }

    public function create(StoreContext $context, string $store_slug): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();
        $customers = User::whereHas('stores', fn ($q) => $q->where('stores.id', $store->id))->orderBy('name')->get();
        $warehouse = Warehouse::where('store_id', $store->id)->where('is_default', true)->first();
        $products = Product::where('store_id', $store->id)->orderBy('name')->get();

        return view('pos.buybacks.create', compact('store', 'storeRouteParams', 'customers', 'products', 'warehouse'));
    }

    public function store(Request $request, StoreContext $context, string $store_slug): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:users,id',
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $buyback = DB::transaction(function () use ($store, $validated) {
            $totalValue = collect($validated['items'])->sum(fn ($item) => $item['unit_price'] * $item['quantity']);

            $buyback = BuyBack::create([
                'store_id' => $store->id,
                'buyback_number' => BuyBack::generateNumber($store->id),
                'customer_id' => $validated['customer_id'] ?? null,
                'total_value' => $totalValue,
                'refund_amount' => $totalValue,
                'status' => 'pending',
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                BuyBackItem::create([
                    'buy_back_id' => $buyback->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            return $buyback;
        });

        return redirect()
            ->route('pos.buybacks.show', [...$context->getRouteParams(), 'buyback' => $buyback->id])
            ->with('success', __('messages.buyback_created'));
    }

    public function show(StoreContext $context, string $store_slug, BuyBack $buyback): View
    {
        $store = $context->getStore();

        if ((int) $buyback->store_id !== (int) $store->id) {
            abort(404);
        }

        $buyback->load(['creator', 'customer', 'items.product']);

        return view('pos.buybacks.show', [
            'store' => $store,
            'storeRouteParams' => $context->getRouteParams(),
            'buyback' => $buyback,
        ]);
    }

    public function complete(StoreContext $context, string $store_slug, BuyBack $buyback): RedirectResponse
    {
        $store = $context->getStore();

        if ((int) $buyback->store_id !== (int) $store->id) {
            abort(404);
        }

        if ($buyback->status !== 'pending') {
            return back()->withErrors(['status' => __('messages.buyback_invalid_status')]);
        }

        $warehouse = Warehouse::where('store_id', $buyback->store_id)->where('is_default', true)->first();

        DB::transaction(function () use ($buyback, $warehouse) {
            foreach ($buyback->items as $item) {
                $this->inventory->postMovement([
                    'store_id' => $buyback->store_id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $warehouse?->id,
                    'movement_type' => 'sales_return',
                    'quantity_delta' => $item->quantity,
                    'unit_cost' => $item->unit_price,
                    'client_transaction_id' => "buyback:{$buyback->id}:{$item->product_id}",
                    'posted_by' => auth()->id(),
                ]);
            }
            $buyback->update(['status' => 'completed']);
        });

        return back()->with('success', __('messages.buyback_completed'));
    }

    public function cancel(StoreContext $context, string $store_slug, BuyBack $buyback): RedirectResponse
    {
        $store = $context->getStore();

        if ((int) $buyback->store_id !== (int) $store->id) {
            abort(404);
        }

        if ($buyback->status !== 'pending') {
            return back()->withErrors(['status' => __('messages.buyback_invalid_status')]);
        }
        $buyback->update(['status' => 'cancelled']);
        return back()->with('success', __('messages.buyback_cancelled'));
    }
}
