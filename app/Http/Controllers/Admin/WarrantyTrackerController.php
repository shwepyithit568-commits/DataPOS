<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\DeviceWarranty;
use App\POS\Services\WarrantyTrackerService;
use App\Services\StoreContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use Symfony\Component\HttpFoundation\StreamedResponse;

class WarrantyTrackerController extends Controller
{
    public function __construct(
        protected WarrantyTrackerService $warrantyService,
    ) {
    }

    /**
     * Display a listing of device warranties and summary stats.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $search = $request->input('search');
        $status = $request->input('status', 'all');
        $perPage = $request->input('per_page', '25');
        $perPageInt = $perPage === 'all' ? 5000 : (in_array((int) $perPage, [25, 50, 100, 200], true) ? (int) $perPage : 25);

        $stats = $this->warrantyService->getStatistics($store);
        $warranties = $this->warrantyService->listWarranties($store, $search, $status, $perPageInt);

        $activeFiltersCount = 0;
        if (!empty($search)) $activeFiltersCount++;
        if ($status !== 'all') $activeFiltersCount++;

        $exportUrl = route('store.admin.warranty.export', array_merge(
            ['store_slug' => $store->slug],
            $request->query()
        ));

        return view('admin.warranty.index', compact(
            'store',
            'warranties',
            'stats',
            'search',
            'status',
            'perPage',
            'activeFiltersCount',
            'exportUrl'
        ));
    }

    /**
     * Export device warranty records to Excel (.xlsx) or CSV.
     */
    public function export(StoreContext $context, Request $request): StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $search = $request->input('search');
        $status = $request->input('status', 'all');
        $format = $request->input('format', 'xlsx');

        $warranties = DeviceWarranty::forStore($store->id)
            ->with(['product', 'customer', 'sale', 'creator'])
            ->search($search)
            ->status($status)
            ->latest('id')
            ->get();

        $filename = 'warranties_' . $store->slug . '_' . now()->format('Ymd_His');

        if ($format === 'xlsx' && class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Warranties');

            $headers = [
                'ID', 'Product Name', 'Serial Number', 'IMEI Primary', 'IMEI Secondary',
                'Customer Name', 'Customer Phone', 'Invoice No', 'Purchase Date',
                'Duration (Months)', 'Expiry Date', 'Days Remaining', 'Warranty Type',
                'Status', 'Claims Count', 'Created At'
            ];

            // Header row styling
            $sheet->fromArray($headers, null, 'A1');
            $headerRange = 'A1:P1';
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'], // Indigo-600
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(26);

            $rowIdx = 2;
            foreach ($warranties as $w) {
                $sheet->fromArray([
                    $w->id,
                    $w->product_name,
                    $w->serial_number,
                    $w->imei_primary ?? '',
                    $w->imei_secondary ?? '',
                    $w->customer_name ?? 'Walk-in Customer',
                    $w->customer_phone ?? '',
                    $w->invoice_number ?? '',
                    $w->purchase_date ? $w->purchase_date->format('Y-m-d') : '',
                    $w->warranty_duration_months,
                    $w->warranty_expiry_date ? $w->warranty_expiry_date->format('Y-m-d') : '',
                    $w->days_remaining,
                    ucfirst(str_replace('_', ' ', $w->warranty_type)),
                    ucfirst($w->computed_status),
                    $w->claim_count ?? 0,
                    $w->created_at ? $w->created_at->format('Y-m-d H:i') : '',
                ], null, 'A' . $rowIdx);

                if ($rowIdx % 2 === 1) {
                    $sheet->getStyle('A' . $rowIdx . ':P' . $rowIdx)->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8FAFC'],
                        ],
                    ]);
                }
                $sheet->getRowDimension($rowIdx)->setRowHeight(20);
                $rowIdx++;
            }

            foreach (range('A', 'P') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0',
            ]);
        }

        // CSV Stream fallback
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($warranties) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID', 'Product Name', 'Serial Number', 'IMEI Primary', 'IMEI Secondary',
                'Customer Name', 'Customer Phone', 'Invoice No', 'Purchase Date',
                'Duration (Months)', 'Expiry Date', 'Days Remaining', 'Warranty Type',
                'Status', 'Claims Count', 'Created At'
            ]);

            foreach ($warranties as $w) {
                fputcsv($handle, [
                    $w->id,
                    $w->product_name,
                    $w->serial_number,
                    $w->imei_primary ?? '',
                    $w->imei_secondary ?? '',
                    $w->customer_name ?? 'Walk-in Customer',
                    $w->customer_phone ?? '',
                    $w->invoice_number ?? '',
                    $w->purchase_date ? $w->purchase_date->format('Y-m-d') : '',
                    $w->warranty_duration_months,
                    $w->warranty_expiry_date ? $w->warranty_expiry_date->format('Y-m-d') : '',
                    $w->days_remaining,
                    ucfirst(str_replace('_', ' ', $w->warranty_type)),
                    ucfirst($w->computed_status),
                    $w->claim_count ?? 0,
                    $w->created_at ? $w->created_at->format('Y-m-d H:i') : '',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Quick Barcode/IMEI Scanner JSON endpoint.
     */
    public function quickScan(StoreContext $context, Request $request): JsonResponse
    {
        $store = $context->getStore();
        if (! $store) {
            return response()->json([], 404);
        }

        $query = $request->input('q', '');
        $results = $this->warrantyService->quickScanLookup($store, $query);

        return response()->json($results->map(function (DeviceWarranty $w) {
            return [
                'id' => $w->id,
                'product_name' => $w->product_name,
                'serial_number' => $w->serial_number,
                'imei_primary' => $w->imei_primary,
                'customer_name' => $w->customer_name,
                'customer_phone' => $w->customer_phone,
                'purchase_date' => $w->purchase_date->format('d/m/Y'),
                'expiry_date' => $w->warranty_expiry_date->format('d/m/Y'),
                'days_remaining' => $w->days_remaining,
                'status' => $w->computed_status,
                'show_url' => route('store.admin.warranty.show', ['store_slug' => $w->store->slug, 'warranty' => $w->id]),
            ];
        }));
    }

    /**
     * Show the form for creating a new device warranty.
     */
    public function create(StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $products = Product::where('store_id', $store->id)->orderBy('name')->take(100)->get();
        $customers = User::where(function ($q) use ($store) {
            $q->where('role', 'customer')
                ->orWhereHas('stores', fn($sq) => $sq->where('stores.id', $store->id));
        })->orderBy('name')->take(100)->get();

        return view('admin.warranty.create', compact('store', 'products', 'customers'));
    }

    /**
     * Store a newly created device warranty.
     */
    public function store(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $validated = $request->validate([
            'product_id' => ['nullable', \Illuminate\Validation\Rule::exists('products', 'id')->where('store_id', $store->id)],
            'product_name' => 'required|string|max:255',
            'customer_id' => 'nullable|exists:users,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'serial_number' => 'required|string|max:100',
            'imei_primary' => 'nullable|string|max:50',
            'imei_secondary' => 'nullable|string|max:50',
            'invoice_number' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'warranty_duration_months' => 'required|integer|min:1|max:120',
            'warranty_type' => 'required|string|in:shop,official_brand,distributor,service_only',
            'status' => 'required|string|in:active,expired,void,claimed',
            'terms_conditions' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $warranty = $this->warrantyService->register($store, $validated, $request->user());

        return redirect()->route('store.admin.warranty.show', [
            'store_slug' => $store->slug,
            'warranty' => $warranty->id,
        ])->with('success', __('messages.warranty_registered_success'));
    }

    /**
     * Display the specified device warranty with service history.
     */
    public function show(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $warranty = $this->resolveWarranty($store, $request->route('warranty'));
        $warranty->load(['product', 'customer', 'sale', 'creator']);
        $serviceJobs = $this->warrantyService->getServiceHistory($warranty);

        return view('admin.warranty.show', compact('store', 'warranty', 'serviceJobs'));
    }

    /**
     * Show form for editing the warranty.
     */
    public function edit(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $warranty = $this->resolveWarranty($store, $request->route('warranty'));
        $products = Product::where('store_id', $store->id)->orderBy('name')->take(100)->get();
        $customers = User::where(function ($q) use ($store) {
            $q->where('role', 'customer')
                ->orWhereHas('stores', fn($sq) => $sq->where('stores.id', $store->id));
        })->orderBy('name')->take(100)->get();

        return view('admin.warranty.edit', compact('store', 'warranty', 'products', 'customers'));
    }

    /**
     * Update the specified device warranty.
     */
    public function update(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $warranty = $this->resolveWarranty($store, $request->route('warranty'));

        $validated = $request->validate([
            'product_id' => ['nullable', \Illuminate\Validation\Rule::exists('products', 'id')->where('store_id', $store->id)],
            'product_name' => 'required|string|max:255',
            'customer_id' => 'nullable|exists:users,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'serial_number' => 'required|string|max:100',
            'imei_primary' => 'nullable|string|max:50',
            'imei_secondary' => 'nullable|string|max:50',
            'invoice_number' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'warranty_duration_months' => 'required|integer|min:1|max:120',
            'warranty_type' => 'required|string|in:shop,official_brand,distributor,service_only',
            'status' => 'required|string|in:active,expired,void,claimed',
            'terms_conditions' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $purchaseDate = Carbon::parse($validated['purchase_date']);
        $months = (int) $validated['warranty_duration_months'];
        $expiryDate = $purchaseDate->copy()->addMonths($months);

        $warranty->update(array_merge($validated, [
            'warranty_expiry_date' => $expiryDate->toDateString(),
        ]));

        return redirect()->route('store.admin.warranty.show', [
            'store_slug' => $store->slug,
            'warranty' => $warranty->id,
        ])->with('success', __('messages.warranty_updated_success'));
    }

    /**
     * Record a warranty claim or repair.
     */
    public function claim(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $warranty = $this->resolveWarranty($store, $request->route('warranty'));

        $validated = $request->validate([
            'claim_reason' => 'required|string|max:500',
            'resolution' => 'nullable|string|max:500',
            'status' => 'nullable|string|in:active,claimed,void',
        ]);

        $this->warrantyService->recordClaim($warranty, $validated, $request->user());

        return redirect()->route('store.admin.warranty.show', [
            'store_slug' => $store->slug,
            'warranty' => $warranty->id,
        ])->with('success', __('messages.warranty_claim_recorded'));
    }

    /**
     * Render printable A4 Warranty Certificate.
     */
    public function certificate(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $warranty = $this->resolveWarranty($store, $request->route('warranty'));
        $warranty->load(['product', 'customer', 'creator']);

        return view('admin.warranty.certificate', compact('store', 'warranty'));
    }

    /**
     * Resolve warranty instance or model ID safely.
     */
    private function resolveWarranty(Store $store, mixed $warranty): DeviceWarranty
    {
        if ($warranty instanceof DeviceWarranty) {
            if ($warranty->store_id !== $store->id) {
                abort(404);
            }

            return $warranty;
        }

        return DeviceWarranty::where('store_id', $store->id)->findOrFail((int) $warranty);
    }
}
