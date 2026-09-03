<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\PurchaseOrder;
use App\POS\Models\PurchaseReturn;
use App\POS\Models\PurchaseReturnItem;
use App\POS\Services\PurchaseOrderService;
use App\POS\Services\SupplierDebtService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Purchase order lifecycle (alinthit_pos style).
 *
 * GET    /purchases                — PO list (index)
 * GET    /purchases/create         — new PO form
 * POST   /purchases                — save PO as pending
 * GET    /purchases/{po}           — PO detail
 * POST   /purchases/{po}/order     — pending → ordered
 * POST   /purchases/{po}/receive   — ordered → received (posts stock)
 * POST   /purchases/{po}/cancel    — pending|ordered → cancelled
 * POST   /purchases/{po}/pay       — apply payment to a specific PO
 * GET    /purchases/payables        — supplier payables screen
 * GET    /purchases/payables/{supplier}      — supplier detail (unpaid POs + payment history)
 * POST   /purchases/payables/{supplier}/pay  — general payment, FIFO across unpaid POs
 * GET    /purchases/export         — export PO list (Excel/PDF)
 */
class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $purchaseOrders,
        protected SupplierDebtService $supplierDebt,
    ) {
    }

    /** List POs for the store. */
    public function index(StoreContext $context): View
    {
        $store = $context->getStore();
        $allowedStatuses = ['pending', 'ordered', 'received', 'cancelled', 'returned'];
        $status = in_array((string) request('status'), $allowedStatuses, true) ? request('status') : null;
        $pos = $this->purchaseOrders->listForStore($store, $status);
        $statusCounts = PurchaseOrder::where('store_id', $store->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('pos.purchases.index', compact('store', 'pos', 'status', 'statusCounts'));
    }

    /** Create form — empty PO builder. */
    public function create(StoreContext $context): View
    {
        $store = $context->getStore();
        $suppliers = \App\Models\Supplier::where('store_id', $store->id)->orderBy('name')->get();
        $brands = \App\Models\Brand::where('store_id', $store->id)->orderBy('name')->get();
        $categories = \App\Models\Category::where('store_id', $store->id)->whereNull('parent_id')->orderBy('name')->get();

        return view('pos.purchases.create', compact('store', 'suppliers', 'brands', 'categories'));
    }

    /** Product search for PO create form (brand/category filters). */
    public function productSearch(Request $request, StoreContext $context): \Illuminate\Http\JsonResponse
    {
        $store = $context->getStore();
        $query = trim((string) $request->input('q', ''));
        $brandId = $request->input('brand_id');
        $categoryId = $request->input('category_id');

        $products = \App\Models\Product::query()
            ->where('store_id', $store->id)
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', '%' . $query . '%')
                        ->orWhere('sku', 'like', '%' . $query . '%');
                });
            })
            ->when($brandId, function ($q) use ($brandId) {
                $q->where('brand_id', $brandId);
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->with(['brand', 'category'])
            ->orderBy('name')
            ->limit(15)
            ->get();

        // Batch on-hand totals in a single query (avoid N+1).
        $balances = \App\POS\Models\InventoryBalance::where('store_id', $store->id)
            ->whereIn('product_id', $products->pluck('id'))
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity_on_hand) as total')
            ->pluck('total', 'product_id');

        return response()->json(['results' => $products->map(fn ($p) => [
            'id' => $p->id,
            'product_id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'price' => (float) $p->price,
            'cost' => (float) $p->cost,
            'balance' => (float) ($balances[$p->id] ?? 0),
            'brand' => $p->brand?->name,
            'category' => $p->category?->name,
        ])]);
    }

    /** Save a new PO as pending. */
    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('products', 'id')->where('store_id', $store->id)],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            // decimal (not plain numeric): the PO service does bcmath, which
            // throws a ValueError on scientific notation ("1e3").
            'items.*.quantity' => ['required', 'decimal:0,3', 'gt:0'],
            'items.*.unit_cost' => ['required', 'decimal:0,2', 'min:0'],
            'supplier_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('suppliers', 'id')->where('store_id', $store->id)],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'discount_amount' => ['nullable', 'decimal:0,2', 'min:0'],
            'delivery_fee' => ['nullable', 'decimal:0,2', 'min:0'],
            'payment_status' => ['nullable', 'in:unpaid,partial,paid'],
            'paid_amount' => ['nullable', 'decimal:0,2', 'min:0'],
            'voucher_images' => ['nullable', 'array'],
            'voucher_images.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,heic', 'max:10240'],
        ]);

        $payment = [];
        if (! empty($data['payment_status']) || ! empty($data['paid_amount'])) {
            $payment = [
                'payment_status' => $data['payment_status'] ?? PurchaseOrder::PAYMENT_UNPAID,
                'paid_amount' => $data['paid_amount'] ?? '0',
            ];
        }

        // Store multiple uploaded voucher/receipt images
        $uploadedVouchers = [];
        if ($request->hasFile('voucher_images')) {
            foreach ($request->file('voucher_images') as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store("purchase_vouchers/{$store->id}", 'public');
                    $uploadedVouchers[] = $path;
                }
            }
        }

        $adjustments = [
            'discount_amount' => $data['discount_amount'] ?? '0',
            'delivery_fee' => $data['delivery_fee'] ?? '0',
            'voucher_images' => $uploadedVouchers,
        ];

        try {
            $po = $this->purchaseOrders->create(
                $store,
                $data['items'],
                $data['supplier_id'] ?? null,
                $data['reference'] ?? null,
                $data['notes'] ?? null,
                $request->user(),
                $payment,
                $adjustments,
            );
        } catch (InventoryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.purchases.show', ['store_slug' => $store->slug, 'purchaseOrder' => $po->id])
            ->with('success', __('messages.po_created') . ' — ' . $po->po_number);
    }

    /** Upload additional voucher images to an existing PO. */
    public function uploadVouchers(Request $request, StoreContext $context, string $store_slug, int $purchaseOrder): RedirectResponse
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        $request->validate([
            'voucher_images' => ['required', 'array', 'min:1'],
            'voucher_images.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,heic', 'max:10240'],
        ]);

        $currentVouchers = is_array($po->voucher_images) ? $po->voucher_images : [];
        if ($request->hasFile('voucher_images')) {
            foreach ($request->file('voucher_images') as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store("purchase_vouchers/{$store->id}", 'public');
                    $currentVouchers[] = $path;
                }
            }
        }

        $po->update(['voucher_images' => array_values($currentVouchers)]);

        return back()->with('success', __('messages.po_voucher_uploaded_success'));
    }

    /** Delete a single voucher image from a PO. */
    public function deleteVoucher(Request $request, StoreContext $context, string $store_slug, int $purchaseOrder, int $index): RedirectResponse
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        $currentVouchers = is_array($po->voucher_images) ? $po->voucher_images : [];
        if (isset($currentVouchers[$index])) {
            $pathToDelete = $currentVouchers[$index];
            Storage::disk('public')->delete($pathToDelete);
            unset($currentVouchers[$index]);
            $po->update(['voucher_images' => array_values($currentVouchers)]);
        }

        return back()->with('success', __('messages.po_voucher_deleted_success'));
    }

    /** PO detail page. */
    public function show(StoreContext $context, string $store_slug, int $purchaseOrder): View
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        return view('pos.purchases.show', compact('store', 'po'));
    }

    /** Edit form for an existing PO. */
    public function edit(StoreContext $context, string $store_slug, int $purchaseOrder): View
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        $suppliers = \App\Models\Supplier::where('store_id', $store->id)->orderBy('name')->get();
        $brands = \App\Models\Brand::where('store_id', $store->id)->orderBy('name')->get();
        $categories = \App\Models\Category::where('store_id', $store->id)->whereNull('parent_id')->orderBy('name')->get();

        // Preload items with products and variants
        $po->load(['items.product', 'items.variant', 'supplier']);

        return view('pos.purchases.edit', compact('store', 'po', 'suppliers', 'brands', 'categories'));
    }

    /** Update an existing PO. */
    public function update(Request $request, StoreContext $context, string $store_slug, int $purchaseOrder): RedirectResponse
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        if ($po->isCancelled()) {
            return back()->with('error', 'Cancelled PO cannot be edited.');
        }

        // Full validation for all editable POs
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('products', 'id')->where('store_id', $store->id)],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'decimal:0,3', 'gt:0'],
            'items.*.unit_cost' => ['required', 'decimal:0,2', 'min:0'],
            'supplier_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('suppliers', 'id')->where('store_id', $store->id)],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'discount_amount' => ['nullable', 'decimal:0,2', 'min:0'],
            'delivery_fee' => ['nullable', 'decimal:0,2', 'min:0'],
            'voucher_images' => ['nullable', 'array'],
            'voucher_images.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,heic', 'max:10240'],
        ]);

        $vouchers = is_array($po->voucher_images) ? $po->voucher_images : [];
        if ($request->hasFile('voucher_images')) {
            foreach ($request->file('voucher_images') as $file) {
                if ($file && $file->isValid()) {
                    $vouchers[] = $file->store("purchase_vouchers/{$store->id}", 'public');
                }
            }
        }

        try {
            $this->purchaseOrders->update($po, [
                'items' => $data['items'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'discount_amount' => $data['discount_amount'] ?? '0',
                'delivery_fee' => $data['delivery_fee'] ?? '0',
                'voucher_images' => array_values($vouchers),
            ], $request->user());
        } catch (InventoryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.purchases.show', ['store_slug' => $store->slug, 'purchaseOrder' => $po->id])
            ->with('success', __('messages.po_updated') . ' — ' . $po->po_number);
    }

    /** Delete a purchase order. */
    public function destroy(Request $request, StoreContext $context, string $store_slug, int $purchaseOrder): RedirectResponse
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        try {
            $this->purchaseOrders->delete($po, $request->user());
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.purchases.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.po_deleted'));
    }

    /** pending → ordered. */
    public function order(Request $request, StoreContext $context, string $store_slug, int $purchaseOrder): RedirectResponse
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        try {
            $this->purchaseOrders->markOrdered($po, $request->user());
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.po_marked_ordered') . ' — ' . $po->po_number);
    }

    /** ordered → received: posts stock. */
    public function receive(Request $request, StoreContext $context, string $store_slug, int $purchaseOrder): RedirectResponse
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        try {
            $result = $this->purchaseOrders->receive($po, $request->user());
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.po_received') . ' — ' . $result['receipt']->receipt_number);
    }

    /** pending|ordered → cancelled. */
    public function cancel(Request $request, StoreContext $context, string $store_slug, int $purchaseOrder): RedirectResponse
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        try {
            $this->purchaseOrders->cancel($po, $request->user());
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.po_cancelled') . ' — ' . $po->po_number);
    }

    /** Process a purchase return (reverses stock + supplier credit). */
    public function returnItems(Request $request, StoreContext $context, string $store_slug, int $purchaseOrder): RedirectResponse
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        $validated = $request->validate([
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('products', 'id')->where('store_id', $store->id)],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            'items.*.quantity'   => ['required', 'string', 'min:0.001'],
            'reason'             => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $this->purchaseOrders->returnItems($po, $validated['items'], $validated['reason'] ?? '', $request->user());
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.po_returned') . ' — ' . $result['return']->return_number);
    }

    /** Purchase returns listing. */
    public function returnsIndex(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        $search = trim((string) $request->input('search', ''));
        $sort = $request->input('sort', 'newest');

        $query = PurchaseReturn::where('store_id', $store->id)
            ->with(['purchaseOrder', 'supplier', 'createdBy']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', '%' . $search . '%')
                  ->orWhereHas('purchaseOrder', fn ($q2) => $q2->where('po_number', 'like', '%' . $search . '%'))
                  ->orWhereHas('supplier', fn ($q2) => $q2->where('name', 'like', '%' . $search . '%'))
                  ->orWhere('reason', 'like', '%' . $search . '%');
            });
        }

        match ($sort) {
            'oldest'    => $query->oldest(),
            'highest'   => $query->orderBy('total_cost', 'desc'),
            'lowest'    => $query->orderBy('total_cost', 'asc'),
            default     => $query->latest(),
        };

        $returns = $query->paginate(25)->withQueryString();
        $totalCount = $returns->total();

        $allReturns = PurchaseReturn::where('store_id', $store->id)->get();
        $summary = [
            'total_count' => $allReturns->count(),
            'total_qty' => (float) $allReturns->sum('total_quantity'),
            'total_cost' => (float) $allReturns->sum('total_cost'),
            'suppliers_count' => $allReturns->pluck('supplier_id')->filter()->unique()->count(),
        ];

        $receivedOrders = PurchaseOrder::where('store_id', $store->id)
            ->where('status', 'received')
            ->with(['supplier', 'items.product', 'items.variant'])
            ->latest('received_at')
            ->take(50)
            ->get()
            ->map(function ($po) use ($store) {
                $items = $po->items->map(function ($item) use ($store, $po) {
                    $alreadyReturned = (float) PurchaseReturnItem::where('store_id', $store->id)
                        ->whereHas('purchaseReturn', fn ($q) => $q->where('purchase_order_id', $po->id))
                        ->where('product_id', $item->product_id)
                        ->where('product_variant_id', $item->product_variant_id)
                        ->sum('quantity');
                    $returnableQty = max(0, (float) $item->quantity - $alreadyReturned);
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'name' => $item->product?->name . ($item->variant?->name ? ' (' . $item->variant->name . ')' : ''),
                        'sku' => $item->variant?->sku ?: $item->product?->sku,
                        'unit_cost' => (float) $item->unit_cost,
                        'original_qty' => (float) $item->quantity,
                        'returnable_qty' => $returnableQty,
                        'return_qty' => '0',
                    ];
                })->filter(fn ($i) => $i['returnable_qty'] > 0)->values();

                return [
                    'id' => $po->id,
                    'po_number' => $po->po_number,
                    'supplier_name' => $po->supplier?->name ?? '—',
                    'received_at' => $po->received_at?->format('d M Y') ?? $po->created_at?->format('d M Y'),
                    'total_cost' => (float) $po->total_cost,
                    'items' => $items,
                    'items_count' => $items->count(),
                ];
            })->filter(fn ($po) => $po['items_count'] > 0)->values();

        return view('pos.purchases.returns_index', compact('store', 'returns', 'totalCount', 'search', 'sort', 'summary', 'receivedOrders'));
    }

    /** Export Purchase Returns to styled Excel (.xlsx). */
    public function returnsExport(Request $request, StoreContext $context)
    {
        $store = $context->getStore();
        $search = trim((string) $request->input('search', ''));
        $sort = $request->input('sort', 'newest');

        $query = PurchaseReturn::where('store_id', $store->id)
            ->with(['purchaseOrder', 'supplier', 'createdBy']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', '%' . $search . '%')
                  ->orWhereHas('purchaseOrder', fn ($q2) => $q2->where('po_number', 'like', '%' . $search . '%'))
                  ->orWhereHas('supplier', fn ($q2) => $q2->where('name', 'like', '%' . $search . '%'))
                  ->orWhere('reason', 'like', '%' . $search . '%');
            });
        }

        match ($sort) {
            'oldest'    => $query->oldest(),
            'highest'   => $query->orderBy('total_cost', 'desc'),
            'lowest'    => $query->orderBy('total_cost', 'asc'),
            default     => $query->latest(),
        };

        $returns = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(__('messages.po_returns_title') ?: 'Purchase Returns');

        // Header Title
        $sheet->setCellValue('A1', ($store->name ?? 'DataPOS') . ' - ' . __('messages.po_returns_title'));
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF0F172A');
        $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Subtitle
        $sheet->setCellValue('A2', now()->format('d M Y H:i') . ' | ' . count($returns) . ' Records');
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setARGB('FF64748B');
        $sheet->getRowDimension(2)->setRowHeight(18);

        // Table Column Headers (Row 4)
        $headers = [
            'A4' => __('messages.po_return_col_number'),
            'B4' => __('messages.po_col_po_number'),
            'C4' => __('messages.supplier_col_name'),
            'D4' => __('messages.reports_qty'),
            'E4' => __('messages.reports_value') . ' (Ks)',
            'F4' => __('messages.po_return_col_reason'),
            'G4' => __('messages.po_return_col_date'),
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $sheet->getStyle('A4:G4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('D4:E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension(4)->setRowHeight(24);

        $rowNum = 5;
        $totalQty = 0;
        $totalValue = 0;

        foreach ($returns as $ret) {
            $qty = (float) $ret->total_quantity;
            $val = (float) $ret->total_cost;
            $totalQty += $qty;
            $totalValue += $val;

            $sheet->setCellValue('A' . $rowNum, $ret->return_number);
            $sheet->setCellValue('B' . $rowNum, $ret->purchaseOrder?->po_number ?? '—');
            $sheet->setCellValue('C' . $rowNum, $ret->supplier?->name ?? '—');
            $sheet->setCellValue('D' . $rowNum, $qty);
            $sheet->setCellValue('E' . $rowNum, $val);
            $sheet->setCellValue('F' . $rowNum, $ret->reason ?: '—');
            $sheet->setCellValue('G' . $rowNum, $ret->returned_at?->format('Y-m-d H:i') ?? '—');

            $sheet->getStyle('A' . $rowNum . ':G' . $rowNum)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('D' . $rowNum . ':E' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            if ($rowNum % 2 === 0) {
                $sheet->getStyle('A' . $rowNum . ':G' . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            }
            $rowNum++;
        }

        // Summary Total Row
        if (count($returns) > 0) {
            $sheet->setCellValue('A' . $rowNum, __('messages.total'));
            $sheet->mergeCells('A' . $rowNum . ':C' . $rowNum);
            $sheet->setCellValue('D' . $rowNum, $totalQty);
            $sheet->setCellValue('E' . $rowNum, $totalValue);
            $sheet->getStyle('A' . $rowNum . ':G' . $rowNum)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF0F172A']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2E8F0']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('D' . $rowNum . ':E' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getRowDimension($rowNum)->setRowHeight(22);
            $rowNum++;
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'purchase_returns_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer, $spreadsheet) {
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /** Apply payment to a specific PO. */
    public function pay(Request $request, StoreContext $context, string $store_slug, int $purchaseOrder): RedirectResponse
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        $data = $request->validate([
            'amount'         => ['required', 'decimal:0,2', 'gt:0'],
            'reference'      => ['nullable', 'string', 'max:120'],
            'slip_images'    => ['nullable', 'array', 'max:4'],
            'slip_images.*'  => ['file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],  // 5 MB each
        ]);

        // Store uploaded images
        $imagePaths = [];
        if ($request->hasFile('slip_images')) {
            $dir = 'payment-slips/' . $store->slug . '/' . $po->id;
            foreach ($request->file('slip_images') as $file) {
                $imagePaths[] = $file->store($dir, 'public');
            }
        }

        try {
            $this->purchaseOrders->applyPayment($po, [
                'amount'      => $data['amount'],
                'reference'   => $data['reference'] ?? null,
                'slip_images' => $imagePaths ?: null,
            ], $request->user());
        } catch (InventoryException $e) {
            // Clean up uploaded files on failure
            foreach ($imagePaths as $path) {
                Storage::disk('public')->delete($path);
            }
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.po_payment_recorded') . ' — ' . $po->po_number);
    }

    /* ------------------------------------------------------------------ */
    /*  Payables Screen (Supplier Debt)                                      */
    /* ------------------------------------------------------------------ */

    /** List suppliers with outstanding balances and unpaid PO vouchers (payables screen). */
    public function payablesIndex(StoreContext $context): View
    {
        $store = $context->getStore();
        $suppliers = $this->supplierDebt->listSuppliersWithBalances($store);

        $unpaidOrders = PurchaseOrder::where('store_id', $store->id)
            ->where('status', PurchaseOrder::STATUS_RECEIVED)
            ->whereIn('payment_status', [PurchaseOrder::PAYMENT_UNPAID, PurchaseOrder::PAYMENT_PARTIAL])
            ->where('remaining_balance', '>', 0)
            ->with(['supplier', 'items.product', 'createdBy'])
            ->orderBy('created_at', 'asc')
            ->get();

        $totalOutstanding = (float) $unpaidOrders->sum('remaining_balance');

        return view('pos.purchases.payables.index', compact('store', 'suppliers', 'unpaidOrders', 'totalOutstanding'));
    }

    /** Export payables list as Excel (.xlsx) or CSV with UTF-8 BOM. */
    public function payablesExport(Request $request, StoreContext $context)
    {
        $store = $context->getStore();
        $format = $request->query('format', 'csv');
        $type = $request->query('type', 'vouchers');

        $suppliers = $this->supplierDebt->listSuppliersWithBalances($store);
        $unpaidOrders = PurchaseOrder::where('store_id', $store->id)
            ->where('status', PurchaseOrder::STATUS_RECEIVED)
            ->whereIn('payment_status', [PurchaseOrder::PAYMENT_UNPAID, PurchaseOrder::PAYMENT_PARTIAL])
            ->where('remaining_balance', '>', 0)
            ->with(['supplier', 'items.product', 'createdBy'])
            ->orderBy('created_at', 'asc')
            ->get();

        if ($format === 'excel' || $format === 'xlsx') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(__('messages.payables_title') ?: 'Supplier Payables');

            // Header Title
            $sheet->setCellValue('A1', ($store->name ?? 'DataPOS') . ' - ' . __('messages.sidebar_payables'));
            $sheet->mergeCells('A1:G1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF0F172A');
            $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(1)->setRowHeight(28);

            // Subtitle
            $sheet->setCellValue('A2', now()->format('d M Y H:i') . ' | ' . count($suppliers) . ' Suppliers Owed');
            $sheet->mergeCells('A2:F2');
            $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setARGB('FF64748B');
            $sheet->getRowDimension(2)->setRowHeight(18);

            // Table Column Headers (Row 4)
            $headers = [
                'A4' => __('messages.report_supplier_name'),
                'B4' => __('messages.report_contact_person'),
                'C4' => __('messages.phone'),
                'D4' => __('messages.report_unpaid_po_count'),
                'E4' => __('messages.report_outstanding_debt') . ' (Ks)',
                'F4' => __('messages.report_oldest_unpaid_date'),
            ];

            foreach ($headers as $cell => $text) {
                $sheet->setCellValue($cell, $text);
            }

            $sheet->getStyle('A4:F4')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE11D48']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle('D4:E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getRowDimension(4)->setRowHeight(24);

            $rowNum = 5;
            $totalDebt = 0;
            $totalUnpaidCount = 0;

            foreach ($suppliers as $s) {
                $balance = (float) $s['balance'];
                $unpaidCount = (int) $s['unpaid_count'];
                $totalDebt += $balance;
                $totalUnpaidCount += $unpaidCount;

                $sheet->setCellValue('A' . $rowNum, $s['supplier']->name);
                $sheet->setCellValue('B' . $rowNum, $s['supplier']->contact_person ?? '—');
                $sheet->setCellValue('C' . $rowNum, $s['supplier']->phone ?? '—');
                $sheet->setCellValue('D' . $rowNum, $unpaidCount);
                $sheet->setCellValue('E' . $rowNum, $balance);
                $sheet->setCellValue('F' . $rowNum, $s['oldest_unpaid_date'] ? $s['oldest_unpaid_date']->format('Y-m-d') : '—');

                $sheet->getStyle('A' . $rowNum . ':F' . $rowNum)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('D' . $rowNum . ':E' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                if ($rowNum % 2 === 0) {
                    $sheet->getStyle('A' . $rowNum . ':F' . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF1F2');
                }
                $rowNum++;
            }

            // Summary Total Row
            if (count($suppliers) > 0) {
                $sheet->setCellValue('A' . $rowNum, __('messages.total'));
                $sheet->mergeCells('A' . $rowNum . ':C' . $rowNum);
                $sheet->setCellValue('D' . $rowNum, $totalUnpaidCount);
                $sheet->setCellValue('E' . $rowNum, $totalDebt);
                $sheet->getStyle('A' . $rowNum . ':F' . $rowNum)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF881337']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFECDD3']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('D' . $rowNum . ':E' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getRowDimension($rowNum)->setRowHeight(22);
                $rowNum++;
            }

            foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $filename = 'payables_' . now()->format('Ymd_His') . '.xlsx';

            return response()->streamDownload(function () use ($writer, $spreadsheet) {
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0',
            ]);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="payables-' . now()->format('Ymd-His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($suppliers) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($stream, [
                __('messages.report_supplier_name'),
                __('messages.report_contact_person'),
                __('messages.phone'),
                __('messages.report_unpaid_po_count'),
                __('messages.report_outstanding_debt'),
                __('messages.report_oldest_unpaid_date'),
            ]);

            foreach ($suppliers as $s) {
                fputcsv($stream, [
                    $s['supplier']->name,
                    $s['supplier']->contact_person ?? '',
                    $s['supplier']->phone ?? '',
                    $s['unpaid_count'],
                    number_format((float) $s['balance'], 2),
                    $s['oldest_unpaid_date'] ? $s['oldest_unpaid_date']->format('Y-m-d') : '',
                ]);
            }

            fclose($stream);
        }, 'payables-' . now()->format('Ymd-His') . '.csv', $headers);
    }

    /** Supplier detail: unpaid POs + payment history. */
    public function payablesShow(StoreContext $context, string $store_slug, int $supplier): View
    {
        $store = $context->getStore();
        $supplier = \App\Models\Supplier::where('store_id', $store->id)->find($supplier);

        if (! $supplier) {
            abort(404);
        }

        $unpaid = $this->supplierDebt->getUnpaidOrders($supplier);
        $history = $this->supplierDebt->getPaymentHistory($supplier);

        return view('pos.purchases.payables.show', compact('store', 'supplier', 'unpaid', 'history'));
    }

    /** General payment to a supplier — FIFO across oldest unpaid POs. */
    public function payablesPay(Request $request, StoreContext $context, string $store_slug, int $supplier): RedirectResponse
    {
        $store = $context->getStore();
        $supplier = \App\Models\Supplier::where('store_id', $store->id)->find($supplier);

        if (! $supplier) {
            abort(404);
        }        $data = $request->validate([
            'amount' => ['required', 'decimal:0,2', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        try {


            $result = $this->supplierDebt->paySupplierGeneral(
                $supplier,
                (string) $data['amount'],
                $request->user(),
                $data['reference'] ?? null,
            );
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        $count = count($result['applied']);
        $msg = __('messages.po_general_payment_recorded', ['count' => $count]);

        if (bccomp($result['remaining'], '0', 2) > 0) {
            $msg .= ' ' . __('messages.po_payment_remainder', ['amount' => number_format((float) $result['remaining'], 2)]);
        }

        return redirect()
            ->route('pos.purchases.payables.show', ['store_slug' => $store->slug, 'supplier' => $supplier->id])
            ->with('success', $msg);
    }

    /** Export PO list (Excel CSV or Print-ready HTML for PDF). */
    public function export(Request $request, StoreContext $context)
    {
        $store = $context->getStore();
        $status = $request->query('status');
        $pos = $this->purchaseOrders->listForStore($store, $status);

        $format = $request->query('format', 'excel');
        if ($format === 'pdf') {
            return $this->exportHtmlForPdf($store, $pos, $status);
        }

        return $this->exportExcel($store, $pos);
    }

    private function exportExcel(Store $store, $pos)
    {
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, [
            __('messages.report_po_number'),
            __('messages.report_supplier_name'),
            __('messages.status'),
            __('messages.payment_status'),
            __('messages.report_total_cost'),
            __('messages.report_paid'),
            __('messages.report_balance'),
            __('messages.report_created'),
        ]);

        $poStatusLabel = function (string $status): string {
            return match ($status) {
                'pending' => __('messages.po_status_pending'),
                'ordered' => __('messages.po_status_ordered'),
                'received' => __('messages.po_status_received'),
                'cancelled' => __('messages.po_status_cancelled'),
                'returned' => __('messages.po_status_returned'),
                default => ucfirst($status),
            };
        };
        $poPaymentLabel = function (string $status): string {
            return match ($status) {
                'unpaid' => __('messages.po_payment_unpaid'),
                'partial' => __('messages.po_payment_partial'),
                'paid' => __('messages.po_payment_paid'),
                default => ucfirst($status),
            };
        };

        foreach ($pos as $po) {
            fputcsv($csv, [
                $po->po_number,
                $po->supplier?->name ?? '-',
                $poStatusLabel($po->status),
                $poPaymentLabel($po->payment_status),
                $po->total_cost,
                $po->paid_amount,
                $po->remaining_balance,
                $po->created_at?->format('Y-m-d'),
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        $filename = 'purchase_orders_' . now()->format('Ymd_His') . '.csv';

        return response($content)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /** Returns print-optimized HTML (can be printed to PDF from browser). */
    private function exportHtmlForPdf(Store $store, $pos, $status = null)
    {
        $html = view('pos.purchases.export_pdf', compact('store', 'pos', 'status'))->render();

        $filename = 'purchase_orders_' . now()->format('Ymd_His') . '.html';

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
