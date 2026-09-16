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
use Illuminate\Validation\Rule;
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
        $products = Product::where('store_id', $store->id)->orderBy('name')->get(['id', 'name', 'sku', 'barcode', 'retail_price', 'wholesale_price', 'purchase_cost']);
        $productArray = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku ?? '',
            'barcode' => $p->barcode ?? '',
            'cost' => (float) ($p->purchase_cost ?? 0),
            'price' => (float) ($p->retail_price ?? 0),
        ])->values()->all();

        return view('pos.buybacks.create', compact('store', 'storeRouteParams', 'customers', 'products', 'productArray', 'warehouse'));
    }

    public function store(Request $request, StoreContext $context, string $store_slug): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            // Both relations are store-scoped: an unscoped `exists` would let a
            // terminal attach another store's product or customer to this buy back.
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($store) {
                    $query->whereIn('users.id', function ($sub) use ($store) {
                        $sub->select('user_id')
                            ->from('store_user')
                            ->where('store_id', $store->id)
                            ->whereIn('role', ['retail_customer', 'wholesale_customer'])
                            ->where('status', 'active');
                    });
                }),
            ],
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('store_id', $store->id)],
            'items.*.quantity' => 'required|numeric|min:0.001',
            // decimal (not plain numeric): money is accumulated with bcmath
            // below, which throws a ValueError on scientific notation ("1e3").
            'items.*.unit_price' => 'required|decimal:0,2|min:0',
        ]);

        $buyback = DB::transaction(function () use ($store, $validated) {
            $totalValue = '0.00';
            foreach ($validated['items'] as $item) {
                $lineTotal = bcmul((string) $item['unit_price'], (string) $item['quantity'], 2);
                $totalValue = bcadd($totalValue, $lineTotal, 2);
            }

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

    public function show(Request $request, StoreContext $context, string $store_slug, BuyBack $buyback): View|\Illuminate\Http\JsonResponse
    {
        $store = $context->getStore();

        if ((int) $buyback->store_id !== (int) $store->id) {
            abort(404);
        }

        $buyback->load(['creator', 'customer', 'items.product']);

        if ($request->wantsJson() || $request->ajax() || $request->query('format') === 'json') {
            $fmtQty = static function ($qty): string {
                $f = (float) $qty;
                if ($f == (int) $f) {
                    return (string) (int) $f;
                }
                return rtrim(rtrim(number_format($f, 3, '.', ''), '0'), '.');
            };

            return response()->json([
                'id' => $buyback->id,
                'buyback_number' => $buyback->buyback_number,
                'status' => $buyback->status,
                'status_label' => __('messages.' . $buyback->status),
                'total_value' => (float) $buyback->total_value,
                'total_value_formatted' => format_currency($buyback->total_value, $store),
                'total_formatted' => format_currency($buyback->total_value, $store),
                'refund_amount' => (float) $buyback->refund_amount,
                'refund_amount_formatted' => format_currency($buyback->refund_amount, $store),
                'reason' => $buyback->reason ?: '—',
                'notes' => $buyback->notes ?: null,
                'created_at' => $buyback->created_at->format('d M Y, H:i'),
                'created_at_formatted' => $buyback->created_at->format('d M Y, H:i'),
                'creator' => $buyback->creator ? [
                    'id' => $buyback->creator->id,
                    'name' => $buyback->creator->name,
                ] : null,
                'creator_name' => $buyback->creator?->name ?? '—',
                'customer' => $buyback->customer ? [
                    'id' => $buyback->customer->id,
                    'name' => $buyback->customer->name,
                    'phone' => $buyback->customer->phone,
                ] : null,
                'items' => $buyback->items->map(function ($item) use ($store, $fmtQty) {
                    $lineTotal = (float) $item->unit_price * (float) $item->quantity;
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'name' => $item->product?->name ?? '—',
                        'product_name' => $item->product?->name ?? '—',
                        'sku' => $item->product?->sku ?? '',
                        'quantity' => (float) $item->quantity,
                        'quantity_formatted' => $fmtQty($item->quantity),
                        'unit_price' => (float) $item->unit_price,
                        'unit_price_formatted' => format_currency($item->unit_price, $store),
                        'line_total' => $lineTotal,
                        'line_total_formatted' => format_currency($lineTotal, $store),
                    ];
                }),
                'print_url' => route('pos.buybacks.print', ['store_slug' => $store->slug, 'buyback' => $buyback->id]),
                'detail_url' => route('pos.buybacks.show', ['store_slug' => $store->slug, 'buyback' => $buyback->id]),
                'show_url' => route('pos.buybacks.show', ['store_slug' => $store->slug, 'buyback' => $buyback->id]),
            ]);
        }

        return view('pos.buybacks.show', [
            'store' => $store,
            'storeRouteParams' => $context->getRouteParams(),
            'buyback' => $buyback,
        ]);
    }

    /**
     * Print Customer Buy-Back & Trade-In Slip (80mm / 58mm / A5 / A4).
     */
    public function print(Request $request, StoreContext $context, string $store_slug, BuyBack $buyback): View
    {
        $store = $context->getStore();

        if ((int) $buyback->store_id !== (int) $store->id) {
            abort(404);
        }

        $buyback->load(['creator', 'customer', 'items.product']);

        $templateService = app(\App\POS\Services\VoucherTemplateService::class);
        $paperSize = (string) ($request->input('paper_size') ?: $templateService->getDocumentPaperSize($store, 'pos_sale'));
        if (! in_array($paperSize, ['58mm', '80mm', 'a5', 'a4'], true)) {
            $paperSize = '80mm';
        }
        $voucherTemplate = $templateService->getTemplateForDocument($store, 'pos_sale', $paperSize);

        return view('pos.buybacks.print', compact('store', 'buyback', 'voucherTemplate', 'paperSize'));
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
                    // Keyed per line, not per product: two lines for the same
                    // product must both restore stock, and the unique index on
                    // (store_id, client_transaction_id) still makes completion
                    // idempotent.
                    'client_transaction_id' => "buyback:{$buyback->id}:item:{$item->id}",
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
