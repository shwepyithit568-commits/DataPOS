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
use App\POS\Services\InventoryService;
use App\Services\StoreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
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
     * Stream the currently-filtered job list as an Excel-friendly CSV.
     */
    public function export(Request $request, StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();
        $tab = $this->normalizeTab($request->input('tab'));

        $jobs = $this->filteredQuery($request, $store, $tab)
            ->with(['customer', 'technician', 'payments', 'items'])
            ->get();

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
                'Status', 'Technician', 'Estimate (Ks)', 'Final (Ks)', 'Paid (Ks)', 'Outstanding (Ks)',
                'Line Items',
            ]);

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

        [$customers, $technicians, $products] = $this->createFormData($store);

        return view('admin.repairs.create', compact(
            'store', 'storeRouteParams', 'customers', 'technicians', 'products'
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

        $job = DB::transaction(function () use ($store, $validated, $items): ServiceJob {
            $job = ServiceJob::create([
                'store_id' => $store->id,
                'job_number' => ServiceJob::generateNumber($store->id),
                'voucher_no' => $validated['voucher_no'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'contact_name' => $validated['contact_name'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'device_type' => $validated['device_type'],
                'model' => $validated['model'] ?? null,
                'imei_serial' => $validated['imei_serial'] ?? null,
                'reported_problem' => $validated['reported_problem'],
                'intake_condition' => $validated['intake_condition'] ?? null,
                'accessories' => $validated['accessories'] ?? null,
                'technician_id' => $validated['technician_id'] ?? null,
                'status' => 'received',
                'estimated_charge' => $validated['estimated_charge'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'warranty_notes' => $validated['warranty_notes'] ?? null,
                'estimated_completion' => $validated['estimated_completion'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $job->items()->createMany($items);

            ServiceJobStatus::create([
                'service_job_id' => $job->id,
                'status' => 'received',
                'note' => 'Device received',
                'changed_by' => Auth::id(),
            ]);

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

        return view('admin.repairs.show', [
            'store' => $store,
            'storeRouteParams' => $context->getRouteParams(),
            'repair' => $repair,
        ]);
    }

    /**
     * Printable A5-style job ticket for the customer handover.
     */
    public function printTicket(StoreContext $context, string $store_slug, ServiceJob $repair): View
    {
        $store = $context->getStore();
        if ($repair->store_id !== $store->id) {
            abort(404);
        }

        $repair->load(['customer', 'technician', 'payments', 'items']);

        return view('admin.repairs.print', [
            'store' => $store,
            'storeRouteParams' => $context->getRouteParams(),
            'repair' => $repair,
        ]);
    }

    public function edit(StoreContext $context, string $store_slug, ServiceJob $repair): View
    {
        $store = $context->getStore();
        if ($repair->store_id !== $store->id) {
            abort(404);
        }

        $repair->load('items');
        [$customers, $technicians, $products] = $this->createFormData($store);

        return view('admin.repairs.edit', [
            'store' => $store,
            'storeRouteParams' => $context->getRouteParams(),
            'repair' => $repair,
            'customers' => $customers,
            'technicians' => $technicians,
            'products' => $products,
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

        DB::transaction(function () use ($repair, $validated) {
            $repair->update(['status' => $validated['status']]);

            ServiceJobStatus::create([
                'service_job_id' => $repair->id,
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
                'changed_by' => Auth::id(),
            ]);
        });

        return back()->with('success', __('messages.repair_status_updated'));
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
        if ($tab !== 'all') {
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

        return [
            'all'        => array_sum($byStatus),
            'processing' => array_sum(array_intersect_key($byStatus, array_flip(self::TAB_BUCKETS['processing']))),
            'ready'      => $byStatus['ready'] ?? 0,
            'history'    => array_sum(array_intersect_key($byStatus, array_flip(self::TAB_BUCKETS['history']))),
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
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'retail_price']);

        return [$customers, $technicians, $products];
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
            'device_type' => 'required|string|max:60',
            'model' => 'nullable|string|max:120',
            'imei_serial' => 'nullable|string|max:60',
            'reported_problem' => 'required|string|max:1000',
            'intake_condition' => 'nullable|string|max:1000',
            'accessories' => 'nullable|string|max:500',
            'diagnosis' => 'nullable|string|max:1000',
            'estimated_charge' => 'nullable|numeric|min:0',
            'final_charge' => 'nullable|numeric|min:0',
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
}
