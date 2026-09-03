<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Enums\InventoryMovementType;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\ServiceJob;
use App\POS\Models\ServiceJobItem;
use App\POS\Models\ServiceJobPayment;
use App\POS\Models\ServiceJobStatus;
use App\POS\Models\ServiceSetting;
use App\POS\Services\InventoryService;
use App\Services\StoreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Repair Center / Service Jobs (SoT §16 — Service Module).
 *
 * A service job tracks a customer-owned device through the repair lifecycle.
 * The device is never store inventory. Every status change appends an
 * immutable history row; payments are immutable receipts and the outstanding
 * balance is derived, never edited.
 *
 * Jobs carry line items (repair services + spare parts, §16 Used Parts).
 * Part rows linked to a product can be consumed through the inventory
 * ledger (`service_consumption`) with a one-way `is_deducted` flag.
 *
 * Index layout mirrors the mobile Repairs Center reference: tab buckets
 * (Processing / Ready / History), search, status + date-range filters,
 * sort, per-page and CSV export.
 */
class RepairController extends Controller
{
    /** Tab buckets (alinthit Repairs Center parity). */
    public const TAB_BUCKETS = [
        'processing' => ['received', 'diagnosing', 'awaiting_approval', 'awaiting_parts', 'in_repair'],
        'ready'      => ['ready'],
        'history'    => ['delivered', 'cancelled', 'unrepairable'],
        'debt'       => [],
    ];

    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $tab = $this->normalizeTab($request->input('tab'));
        $query = $this->filteredQuery($request, $store, $tab);

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest'   => $query->orderBy('id'),
            'customer' => $query->orderByRaw("COALESCE(contact_name, '') ASC"),
            'status'   => $query->orderBy('status')->orderByDesc('id'),
            default    => $query->latest('id'),
        };

        $stats = $this->statsFor($store);
        $tabCounts = $this->tabCountsFor($store);

        // Clamp the per-page value: hand-edited URLs can pass garbage
        // (negative numbers crash paginate(); anything > 100000 is capped).
        $perPage = request('per_page') === 'all' ? 100000 : (int) request('per_page', 25);
        $perPage = max(1, min($perPage, 100000));
        $jobs = $query->paginate($perPage)->withQueryString();
        $totalCount = $jobs->total();

        return view('admin.repairs.index', compact(
            'store', 'storeRouteParams', 'jobs', 'totalCount', 'stats', 'tabCounts', 'tab'
        ));
    }

    /**
     * Stream the currently-filtered job list as styled Excel (.xlsx) or CSV.
     */
    public function export(Request $request, StoreContext $context): Response
    {
        $store = $context->getStore();
        $tab = $this->normalizeTab($request->input('tab'));
        $format = strtolower((string) $request->query('format', 'csv'));

        $jobs = $this->filteredQuery($request, $store, $tab)
            ->with(['customer', 'technician', 'payments', 'items'])
            ->get();

        if ($format === 'xlsx') {
            $filename = 'Repairs_' . ($tab ?: 'all') . '_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'datapos_repairs_');

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Repairs');

            $sheet->setCellValue('A1', $store->name . ' - Repair Center Export (' . strtoupper($tab ?: 'all') . ')');
            $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A') . ' | Total Count: ' . $jobs->count());
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('4C1D95');
            $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

            $row = 4;
            $headers = [
                'A' => 'Job #',
                'B' => 'Date',
                'C' => 'Customer',
                'D' => 'Phone',
                'E' => 'Device Type',
                'F' => 'Model',
                'G' => 'IMEI / Serial',
                'H' => 'Status',
                'I' => 'Technician',
                'J' => 'Estimated Charge',
                'K' => 'Final Charge',
                'L' => 'Paid Amount',
                'M' => 'Outstanding',
                'N' => 'Line Items',
            ];
            foreach ($headers as $col => $title) {
                $sheet->setCellValue("{$col}{$row}", $title);
            }
            $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '6D28D9']],
            ]);

            $row++;
            foreach ($jobs as $job) {
                $items = $job->items
                    ->map(fn ($item) => $item->name . ' ×' . $item->quantity . ' (' . $item->item_type . ')')
                    ->implode('; ');

                $sheet->setCellValue("A{$row}", $job->job_number);
                $sheet->setCellValue("B{$row}", $job->created_at->format('Y-m-d H:i'));
                $sheet->setCellValue("C{$row}", $job->contact_name ?: ($job->customer?->name ?? ''));
                $sheet->setCellValue("D{$row}", $job->contact_phone ?: ($job->customer?->phone ?? ''));
                $sheet->setCellValue("E{$row}", $job->device_type);
                $sheet->setCellValue("F{$row}", $job->model ?? '');
                $sheet->setCellValue("G{$row}", $job->imei_serial ?? '');
                $sheet->setCellValue("H{$row}", __('messages.repair_status_' . $job->status));
                $sheet->setCellValue("I{$row}", $job->technician?->name ?? '');
                $sheet->setCellValue("J{$row}", (float) $job->estimated_charge);
                $sheet->setCellValue("K{$row}", $job->final_charge !== null ? (float) $job->final_charge : '');
                $sheet->setCellValue("L{$row}", (float) $job->paidAmount());
                $sheet->setCellValue("M{$row}", (float) $job->outstanding());
                $sheet->setCellValue("N{$row}", $items);
                $row++;
            }

            foreach (range('A', 'N') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="repairs-' . now()->format('Ymd-His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($jobs) {
            $stream = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens the file with correct encoding.
            fwrite($stream, "\xEF\xBB\xBF");

            fputcsv($stream, [
                'Job #', 'Date', 'Customer', 'Phone', 'Device Type', 'Model', 'IMEI/Serial',
                'Status', 'Technician', 'Estimate', 'Final', 'Paid', 'Outstanding',
                'Line Items',
            ]);

            /** @var ServiceJob $job */
            foreach ($jobs as $job) {
                $items = $job->items
                    ->map(fn ($item) => $item->name . ' ×' . $item->quantity . ' (' . $item->item_type . ')')
                    ->implode('; ');

                fputcsv($stream, [
                    $job->job_number,
                    $job->created_at->format('Y-m-d H:i'),
                    $job->contact_name ?: ($job->customer?->name ?? ''),
                    $job->contact_phone ?: ($job->customer?->phone ?? ''),
                    $job->device_type,
                    $job->model ?? '',
                    $job->imei_serial ?? '',
                    $job->status,
                    $job->technician?->name ?? '',
                    number_format((float) $job->estimated_charge),
                    $job->final_charge !== null ? number_format((float) $job->final_charge) : '',
                    number_format($job->paidAmount()),
                    number_format($job->outstanding()),
                    $items,
                ]);
            }

            fclose($stream);
        }, 'repairs-' . now()->format('Ymd-His') . '.csv', $headers);
    }

    public function create(StoreContext $context): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        [$customers, $technicians, $products, $serviceSettings] = $this->createFormData($store);

        return view('admin.repairs.create', compact(
            'store', 'storeRouteParams', 'customers', 'technicians', 'products', 'serviceSettings'
        ));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $this->validateJob($request, $store);
        $items = $this->normalizeItems($request, $store, 'items');

        // A job needs either a linked customer or at least a contact phone/name.
        if (empty($validated['customer_id']) && empty($validated['contact_phone']) && empty($validated['contact_name'])) {
            return back()->withInput()->withErrors([
                'contact_phone' => __('messages.repair_need_contact'),
            ]);
        }

        $job = DB::transaction(function () use ($store, $validated, $items, $request): ServiceJob {
            $deviceType = !empty($validated['device_type']) ? $validated['device_type'] : (!empty($validated['category']) ? $validated['category'] : (!empty($validated['brand']) ? $validated['brand'] : 'Smartphone'));
            $reportedProblem = !empty($validated['reported_problem']) ? $validated['reported_problem'] : 'Repair Service';
            $initialStatus = !empty($validated['status']) ? $validated['status'] : 'received';
            $creatorId = Auth::id() ?? $request->user()?->id ?? $store->users()->value('users.id');

            $job = ServiceJob::create([
                'store_id' => $store->id,
                'job_number' => ServiceJob::generateNumber($store->id),
                'voucher_no' => $validated['voucher_no'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'contact_name' => $validated['contact_name'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'device_type' => $deviceType,
                'brand' => $validated['brand'] ?? null,
                'category' => $validated['category'] ?? null,
                'model' => $validated['model'] ?? null,
                'color' => $validated['color'] ?? null,
                'storage' => $validated['storage'] ?? null,
                'imei_serial' => $validated['imei_serial'] ?? null,
                'reported_problem' => $reportedProblem,
                'intake_condition' => $validated['intake_condition'] ?? null,
                'accessories' => $validated['accessories'] ?? null,
                'pattern_lock' => $validated['pattern_lock'] ?? null,
                'device_password' => $validated['device_password'] ?? null,
                'technician_id' => $validated['technician_id'] ?? null,
                'status' => $initialStatus,
                'estimated_charge' => $validated['estimated_charge'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'warranty_notes' => $validated['warranty_notes'] ?? null,
                'estimated_completion' => $validated['estimated_completion'] ?? null,
                'created_by' => $creatorId,
            ]);

            $job->items()->createMany($items);

            ServiceJobStatus::create([
                'service_job_id' => $job->id,
                'status' => $initialStatus,
                'note' => 'Device ticket created',
                'changed_by' => $creatorId,
            ]);

            // Handle Advance Payment if provided
            $advance = (float) ($validated['advance_payment'] ?? 0);
            if ($advance > 0) {
                ServiceJobPayment::create([
                    'service_job_id' => $job->id,
                    'method' => $validated['payment_method'] ?? 'cash',
                    'amount' => $advance,
                    'reference' => 'Advance payment on ticket intake',
                    'created_by' => $creatorId,
                ]);
            }

            return $job;
        });

        return redirect()
            ->route('store.admin.repairs.show', [...$context->getRouteParams(), 'repair' => $job->id])
            ->with('success', __('messages.repair_created'));
    }

    public function show(StoreContext $context, string $store_slug, ServiceJob $repair): View
    {
        $store = $context->getStore();
        if ($repair->store_id !== $store->id) {
            abort(404);
        }

        $repair->load(['customer', 'technician', 'statusHistory.changer', 'payments.creator', 'items.product']);

        $trackingUrl = route('storefront.service.track.token', [
            'store_slug' => $store->slug,
            'token' => $repair->tracking_token,
        ]);

        return view('admin.repairs.show', [
            'store' => $store,
            'storeRouteParams' => $context->getRouteParams(),
            'repair' => $repair,
            'trackingUrl' => $trackingUrl,
        ]);
    }

    /**
     * Printable repair ticket / voucher for the customer handover.
     * Supports multiple paper sizes (80mm, 58mm, A5, A4) loaded from store VoucherTemplate.
     */
    public function printTicket(StoreContext $context, string $store_slug, ServiceJob $repair, Request $request): View
    {
        $store = $context->getStore();
        if ($repair->store_id !== $store->id) {
            abort(404);
        }

        $repair->load(['customer', 'technician', 'payments', 'items']);

        // Load active voucher templates from /admin/vouchers for this store
        $templates = \App\Models\VoucherTemplate::where('store_id', $store->id)
            ->where('is_active', true)
            ->get();

        if ($templates->isEmpty()) {
            app(\App\POS\Services\VoucherTemplateService::class)->ensureDefaultTemplates($store);
            $templates = \App\Models\VoucherTemplate::where('store_id', $store->id)
                ->where('is_active', true)
                ->get();
        }

        $selectedTemplateId = $request->query('template_id');
        $requestedPaperSize = $request->query('paper_size');

        $template = null;
        if ($selectedTemplateId) {
            $template = $templates->firstWhere('id', (int) $selectedTemplateId);
        } elseif ($requestedPaperSize) {
            $template = $templates->firstWhere('paper_size', $requestedPaperSize);
        }

        if (! $template) {
            $template = $templates->firstWhere('is_default', true) ?? $templates->first();
        }

        $paperSize = $requestedPaperSize ?: ($template?->paper_size ?? '80mm');

        $trackingUrl = $repair->tracking_token
            ? route('storefront.service.track.token', ['store_slug' => $store->slug, 'token' => $repair->tracking_token])
            : null;

        $trackingQrSvg = null;
        if ($trackingUrl) {
            try {
                $trackingQrSvg = \App\Services\QrCodeEncoder::generateSvg($trackingUrl, 96);
            } catch (\Throwable $e) {
                $trackingQrSvg = null;
            }
        }

        return view('admin.repairs.print', [
            'store' => $store,
            'storeRouteParams' => $context->getRouteParams(),
            'repair' => $repair,
            'template' => $template,
            'templates' => $templates,
            'paperSize' => $paperSize,
            'trackingUrl' => $trackingUrl,
            'trackingQrSvg' => $trackingQrSvg,
        ]);
    }

    public function edit(StoreContext $context, string $store_slug, ServiceJob $repair): View
    {
        $store = $context->getStore();
        if ($repair->store_id !== $store->id) {
            abort(404);
        }

        $repair->load('items');
        [$customers, $technicians, $products, $serviceSettings] = $this->createFormData($store);

        return view('admin.repairs.edit', [
            'store' => $store,
            'storeRouteParams' => $context->getRouteParams(),
            'repair' => $repair,
            'customers' => $customers,
            'technicians' => $technicians,
            'products' => $products,
            'serviceSettings' => $serviceSettings,
        ]);
    }

    public function update(Request $request, StoreContext $context, string $store_slug, ServiceJob $repair): RedirectResponse
    {
        if ($repair->store_id !== $context->getStore()->id) {
            abort(404);
        }

        $validated = $this->validateJob($request, $context->getStore());
        $items = $this->normalizeItems($request, $context->getStore(), 'items');

        DB::transaction(function () use ($repair, $validated, $items) {
            // `items` is handled separately below — never mass-assigned.
            $repair->update(collect($validated)->except('items')->all());

            // Consumed parts stay — they already moved stock. Only the
            // editable (non-deducted) lines are replaced by the form payload.
            $repair->items()->where('is_deducted', false)->delete();
            $repair->items()->createMany($items);
        });

        return redirect()
            ->route('store.admin.repairs.show', [...$context->getRouteParams(), 'repair' => $repair->id])
            ->with('success', __('messages.repair_updated'));
    }

    /**
     * Advance the lifecycle status. Appends an immutable history row (§16).
     */
    public function updateStatus(Request $request, StoreContext $context, string $store_slug, ServiceJob $repair): RedirectResponse
    {
        if ($repair->store_id !== $context->getStore()->id) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', ServiceJob::STATUSES),
            'note' => 'nullable|string|max:500',
        ]);

        if ($repair->isTerminal()) {
            return back()->withErrors(['status' => __('messages.repair_terminal')]);
        }

        if ($validated['status'] === $repair->status) {
            return back()->withErrors(['status' => __('messages.repair_same_status')]);
        }

        $notify = $request->boolean('notify_customer');

        DB::transaction(function () use ($repair, $validated) {
            $repair->update(['status' => $validated['status']]);

            ServiceJobStatus::create([
                'service_job_id' => $repair->id,
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
                'changed_by' => Auth::id(),
            ]);
        });

        $redirect = back()->with('success', __('messages.repair_status_updated'));
        if ($notify) {
            $redirect->with('notify_customer', true);
        }

        return $redirect;
    }

    /**
     * Record an immutable payment receipt (§16 Payments).
     */
    public function addPayment(Request $request, StoreContext $context, string $store_slug, ServiceJob $repair): RedirectResponse
    {
        if ($repair->store_id !== $context->getStore()->id) {
            abort(404);
        }

        $validated = $request->validate([
            'method' => 'required|in:cash,kpay,wavepay,cb_pay,mmqr',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
        ]);

        // Never collect more than the outstanding balance.
        if ((float) $validated['amount'] > $repair->outstanding()) {
            return back()->withErrors(['amount' => __('messages.repair_overpay')]);
        }

        ServiceJobPayment::create([
            'service_job_id' => $repair->id,
            'method' => $validated['method'],
            'amount' => $validated['amount'],
            'reference' => $validated['reference'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', __('messages.repair_payment_recorded'));
    }

    /**
     * Consume a linked spare part through the inventory ledger (§16 Used Parts).
     * Idempotent: a consumed part can never be deducted twice.
     */
    public function deductItem(StoreContext $context, string $store_slug, ServiceJob $repair, ServiceJobItem $item, InventoryService $inventory): RedirectResponse
    {
        if ($repair->store_id !== $context->getStore()->id) {
            abort(404);
        }

        if ($item->service_job_id !== $repair->id) {
            abort(404);
        }

        if ($item->is_deducted) {
            return back()->withErrors(['items' => __('messages.repair_part_already_deducted')]);
        }

        if (! $item->isPart() || $item->product_id === null) {
            return back()->withErrors(['items' => __('messages.repair_part_requires_product')]);
        }

        if ($repair->isTerminal()) {
            return back()->withErrors(['items' => __('messages.repair_part_terminal')]);
        }

        try {
            DB::transaction(function () use ($context, $repair, $item, $inventory) {
                $inventory->postMovement([
                    'store_id' => $context->getStore()->id,
                    'product_id' => $item->product_id,
                    'movement_type' => InventoryMovementType::ServiceConsumption->value,
                    'quantity_delta' => -1 * $item->quantity,
                    'source_type' => 'service_job',
                    'source_id' => $repair->id,
                    'client_transaction_id' => "service-job-{$repair->id}-item-{$item->id}",
                    'occurred_at' => now(),
                    'posted_by' => Auth::id(),
                    'metadata' => [
                        'service_job_item_id' => $item->id,
                        'job_number' => $repair->job_number,
                        'part_name' => $item->name,
                    ],
                ], [
                    'allow_negative' => false,
                ]);

                $item->update(['is_deducted' => true]);
            });
        } catch (InventoryException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }

        return back()->with('success', __('messages.repair_part_deducted'));
    }

    /* ------------------------------------------------------------------ */
    /*  Internals                                                          */
    /* ------------------------------------------------------------------ */

    private function normalizeTab(?string $tab): string
    {
        return array_key_exists((string) $tab, self::TAB_BUCKETS) ? (string) $tab : 'all';
    }

    /**
     * Shared search/filter builder for the index, tabs and CSV export.
     */
    private function filteredQuery(Request $request, Store $store, string $tab): Builder
    {
        // `payments` is eager-loaded so per-row outstanding() calls in the
        // view never trigger N+1 queries (paidAmount uses the loaded relation).
        $query = ServiceJob::where('store_id', $store->id)
            ->with(['customer', 'technician', 'payments']);

        // Search by job number, voucher, IMEI/serial, device, model, contact.
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($w) use ($search) {
                $w->where('job_number', 'like', "%{$search}%")
                  ->orWhere('voucher_no', 'like', "%{$search}%")
                  ->orWhere('imei_serial', 'like', "%{$search}%")
                  ->orWhere('device_type', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && in_array($request->input('status'), ServiceJob::STATUSES, true)) {
            $query->where('status', $request->input('status'));
        }

        // Tab buckets.
        if ($tab === 'debt') {
            $query->whereNotIn('status', ['cancelled', 'unrepairable'])
                ->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNotNull('final_charge')
                            ->whereRaw('(final_charge - COALESCE((SELECT SUM(amount) FROM service_job_payments WHERE service_job_payments.service_job_id = service_jobs.id), 0)) > 0');
                    })->orWhere(function ($sub) {
                        $sub->whereNull('final_charge')
                            ->where('estimated_charge', '>', 0)
                            ->whereRaw('(estimated_charge - COALESCE((SELECT SUM(amount) FROM service_job_payments WHERE service_job_payments.service_job_id = service_jobs.id), 0)) > 0');
                    });
                });
        } elseif ($tab !== 'all' && isset(self::TAB_BUCKETS[$tab]) && ! empty(self::TAB_BUCKETS[$tab])) {
            $query->whereIn('status', self::TAB_BUCKETS[$tab]);
        }

        // Date range (created_at). The toolbar's date filter posts
        // date_from/date_to; from/to kept as a shorter URL alias.
        $from = $request->input('date_from', $request->input('from'));
        $to = $request->input('date_to', $request->input('to'));
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    private function statsFor(Store $store): array
    {
        $byStatus = $this->statusCounts($store);

        $total = array_sum($byStatus);
        $terminal = ($byStatus['delivered'] ?? 0) + ($byStatus['cancelled'] ?? 0) + ($byStatus['unrepairable'] ?? 0);

        // Debt needs the derived outstanding per priced job — one query with
        // payments eager-loaded, computed in PHP (no per-row queries).
        $debt = (float) ServiceJob::where('store_id', $store->id)
            ->whereNotIn('status', ['cancelled', 'unrepairable'])
            ->whereNotNull('final_charge')
            ->with('payments')
            ->get()
            ->sum(fn (ServiceJob $job) => $job->outstanding());

        return [
            'total'  => $total,
            'active' => $total - $terminal,
            'ready'  => $byStatus['ready'] ?? 0,
            'debt'   => $debt,
        ];
    }

    private function tabCountsFor(Store $store): array
    {
        $byStatus = $this->statusCounts($store);

        $debtCount = ServiceJob::where('store_id', $store->id)
            ->whereNotIn('status', ['cancelled', 'unrepairable'])
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('final_charge')
                        ->whereRaw('(final_charge - COALESCE((SELECT SUM(amount) FROM service_job_payments WHERE service_job_payments.service_job_id = service_jobs.id), 0)) > 0');
                })->orWhere(function ($sub) {
                    $sub->whereNull('final_charge')
                        ->where('estimated_charge', '>', 0)
                        ->whereRaw('(estimated_charge - COALESCE((SELECT SUM(amount) FROM service_job_payments WHERE service_job_payments.service_job_id = service_jobs.id), 0)) > 0');
                });
            })->count();

        return [
            'all'        => array_sum($byStatus),
            'processing' => array_sum(array_intersect_key($byStatus, array_flip(self::TAB_BUCKETS['processing']))),
            'ready'      => $byStatus['ready'] ?? 0,
            'history'    => array_sum(array_intersect_key($byStatus, array_flip(self::TAB_BUCKETS['history']))),
            'debt'       => $debtCount,
        ];
    }

    /**
     * One grouped query: [status => count] for the store's jobs.
     *
     * @return array<string, int>
     */
    private function statusCounts(Store $store): array
    {
        return ServiceJob::where('store_id', $store->id)
            ->selectRaw('status, COUNT(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    private function createFormData(Store $store): array
    {
        $customers = User::whereHas('stores', fn ($q) => $q
            ->where('stores.id', $store->id)
            ->whereIn('store_user.role', ['retail_customer', 'wholesale_customer'])
            ->where('store_user.status', 'active'))
            ->orderBy('name')->get();

        $technicians = User::whereHas('stores', fn ($q) => $q
            ->where('stores.id', $store->id)
            ->whereIn('store_user.role', ['store_manager', 'staff'])
            ->where('store_user.status', 'active'))
            ->orderBy('name')->get();

        // Store products for the spare-part picker (parts & services line items).
        $products = Product::where('store_id', $store->id)
            ->where('is_active', true)
            ->with(['category.parent', 'brand'])
            ->orderBy('name')
            ->get(['id', 'store_id', 'category_id', 'brand_id', 'name', 'sku', 'retail_price']);

        $serviceSettings = ServiceSetting::allGroupedFor($store->id);

        return [$customers, $technicians, $products, $serviceSettings];
    }

    private function validateJob(Request $request, Store $store): array
    {
        return $request->validate([
            // A linked customer / technician must belong to this store.
            'customer_id' => [
                'nullable',
                Rule::exists('store_user', 'user_id')
                    ->where('store_id', $store->id)
                    ->whereIn('store_user.role', ['retail_customer', 'wholesale_customer'])
                    ->where('store_user.status', 'active'),
            ],
            'technician_id' => [
                'nullable',
                Rule::exists('store_user', 'user_id')
                    ->where('store_id', $store->id)
                    ->whereIn('store_user.role', ['store_manager', 'staff'])
                    ->where('store_user.status', 'active'),
            ],
            'contact_name' => 'nullable|string|max:120',
            'contact_phone' => 'nullable|string|max:40',
            'shipping_address' => 'nullable|string|max:1000',
            'device_type' => 'nullable|string|max:120',
            'brand' => 'nullable|string|max:120',
            'category' => 'nullable|string|max:120',
            'model' => 'nullable|string|max:120',
            'color' => 'nullable|string|max:60',
            'storage' => 'nullable|string|max:60',
            'imei_serial' => 'nullable|string|max:60',
            'reported_problem' => 'nullable|string|max:1000',
            'intake_condition' => 'nullable|string|max:1000',
            'accessories' => 'nullable|string|max:500',
            'pattern_lock' => 'nullable|string|max:255',
            'device_password' => 'nullable|string|max:120',
            'status' => 'nullable|string|max:32',
            'diagnosis' => 'nullable|string|max:1000',
            'estimated_charge' => 'nullable|numeric|min:0',
            'final_charge' => 'nullable|numeric|min:0',
            'advance_payment' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:30',
            'voucher_no' => 'nullable|string|max:40',
            'notes' => 'nullable|string|max:1000',
            'warranty_notes' => 'nullable|string|max:1000',
            'estimated_completion' => 'nullable|date',
            'items' => 'nullable|array',
            'items.*.item_type' => 'required|in:service,part',
            'items.*.name' => 'nullable|string|max:120',
            'items.*.sku' => 'nullable|string|max:40',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:100000',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);
    }

    /**
     * Build validated line-item rows; guards product ownership and keeps the
     * subtotal formatted as an exact 2-decimal money string.
     */
    private function normalizeItems(Request $request, Store $store, string $field): array
    {
        $raw = (array) $request->input($field, []);
        $rows = [];

        foreach ($raw as $entry) {
            $itemType = $entry['item_type'] ?? 'part';
            $name = trim((string) ($entry['name'] ?? ''));

            // Skip blank rows the front-end editor leaves behind.
            if ($name === '') {
                continue;
            }

            $productId = ! empty($entry['product_id']) ? (int) $entry['product_id'] : null;
            $quantity = (int) ($entry['quantity'] ?? 1);
            $unitPrice = (float) ($entry['unit_price'] ?? 0);

            // A part linked to a product must belong to this store.
            if ($productId !== null) {
                $product = Product::find($productId);
                if (! $product || (int) $product->store_id !== (int) $store->id) {
                    continue;
                }
            }

            $rows[] = [
                'item_type' => $itemType,
                'name' => $name,
                'sku' => ! empty($entry['sku']) ? trim((string) $entry['sku']) : null,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                // Exact decimal arithmetic for money.
                'subtotal' => number_format($quantity * $unitPrice, 2, '.', ''),
            ];
        }

        return $rows;
    }

    /**
     * Quick-add a technician (staff user) for service job intake.
     */
    public function quickAddTechnician(Request $request, StoreContext $context): JsonResponse
    {
        $store = $context->getStore();
        if (! $store) {
            return response()->json(['success' => false, 'message' => 'Store not found'], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
        ]);

        $phone = trim($validated['phone']);
        $name = trim($validated['name']);

        // Check if user with this phone exists
        $user = User::where('phone', $phone)->first();

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'phone' => $phone,
                'password' => bcrypt('password123'),
                'role' => 'customer',
            ]);
        } else {
            if ($name !== '') {
                $user->name = $name;
                $user->save();
            }
        }

        // Attach to store with staff role and active status
        $currentMembership = $user->stores()->where('stores.id', $store->id)->first();
        if (! $currentMembership) {
            $user->stores()->attach($store->id, [
                'role' => 'staff',
                'status' => 'active',
            ]);
        } else {
            $user->stores()->updateExistingPivot($store->id, [
                'role' => 'staff',
                'status' => 'active',
            ]);
        }

        return response()->json([
            'success' => true,
            'technician' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
            ],
        ]);
    }
}
