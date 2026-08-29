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
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service Jobs — Computer, CCTV, Network, Smartphone repairs (SoT §16-B).
 *
 * This controller is the successor of RepairController for the "Service Jobs"
 * sidebar entry. It uses the same service_jobs / service_job_* tables but
 * generates SVC-YYYYMMDD-#### numbers, exposes a customer-facing tracking page
 * via tracking_token, and auto-seeds Computer / CCTV / Network categories.
 *
 * Index layout: Tab buckets (Processing / Ready / History), search, status +
 * date-range filters, sort, per-page and CSV export — mirrors RepairController.
 */
class ServiceJobController extends Controller
{
    /** Tab buckets — same lifecycle as Repair Center. */
    public const TAB_BUCKETS = [
        'processing' => ['received', 'diagnosing', 'awaiting_approval', 'awaiting_parts', 'in_repair'],
        'ready'      => ['ready'],
        'history'    => ['delivered', 'cancelled', 'unrepairable'],
    ];

    // ── Index & Export ────────────────────────────────────────────────────

    public function index(Request $request, StoreContext $context): View
    {
        $store           = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $tab   = $this->normalizeTab($request->input('tab'));
        $query = $this->filteredQuery($request, $store, $tab);

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest'   => $query->orderBy('id'),
            'customer' => $query->orderByRaw("COALESCE(contact_name, '') ASC"),
            'status'   => $query->orderBy('status')->orderByDesc('id'),
            default    => $query->latest('id'),
        };

        $stats     = $this->statsFor($store);
        $tabCounts = $this->tabCountsFor($store);

        $perPage = request('per_page') === 'all' ? 100000 : (int) request('per_page', 25);
        $perPage = max(1, min($perPage, 100000));
        $jobs       = $query->paginate($perPage)->withQueryString();
        $totalCount = $jobs->total();

        return view('admin.service_jobs.index', compact(
            'store', 'storeRouteParams', 'jobs', 'totalCount', 'stats', 'tabCounts', 'tab'
        ));
    }

    public function export(Request $request, StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();
        $tab   = $this->normalizeTab($request->input('tab'));

        $jobs = $this->filteredQuery($request, $store, $tab)
            ->with(['customer', 'technician', 'payments', 'items'])
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="service-jobs-' . now()->format('Ymd-His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($jobs) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($stream, [
                'Job #', 'Voucher #', 'Date', 'Customer', 'Phone',
                'Category', 'Brand', 'Model', 'IMEI/Serial',
                'Status', 'Technician', 'Estimate (Ks)', 'Final (Ks)',
                'Paid (Ks)', 'Outstanding (Ks)', 'Line Items',
            ]);

            /** @var ServiceJob $job */
            foreach ($jobs as $job) {
                $items = $job->items
                    ->map(fn ($item) => $item->name . ' ×' . $item->quantity . ' (' . $item->item_type . ')')
                    ->implode('; ');

                fputcsv($stream, [
                    $job->job_number,
                    $job->voucher_no ?? '',
                    $job->created_at->format('Y-m-d H:i'),
                    $job->contact_name ?: ($job->customer?->name ?? ''),
                    $job->contact_phone ?: ($job->customer?->phone ?? ''),
                    $job->category ?? $job->device_type,
                    $job->brand ?? '',
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
        }, 'service-jobs-' . now()->format('Ymd-His') . '.csv', $headers);
    }

    // ── Create & Store ─────────────────────────────────────────────────────

    public function create(StoreContext $context): View
    {
        $store           = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        [$customers, $technicians, $products, $serviceSettings] = $this->createFormData($store);

        return view('admin.service_jobs.create', compact(
            'store', 'storeRouteParams', 'customers', 'technicians', 'products', 'serviceSettings'
        ));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $this->validateJob($request, $store);
        $items     = $this->normalizeItems($request, $store, 'items');

        if (empty($validated['customer_id']) && empty($validated['contact_phone']) && empty($validated['contact_name'])) {
            return back()->withInput()->withErrors([
                'contact_phone' => __('messages.repair_need_contact'),
            ]);
        }

        $job = DB::transaction(function () use ($store, $validated, $items, $request): ServiceJob {
            // device_type resolves from category > brand as fallback
            $deviceType      = $validated['device_type'] ?? $validated['category'] ?? $validated['brand'] ?? 'Device';
            $reportedProblem = !empty($validated['reported_problem']) ? $validated['reported_problem'] : 'Service Request';
            $initialStatus   = !empty($validated['status']) ? $validated['status'] : 'received';
            $creatorId       = Auth::id() ?? $store->users()->value('users.id');

            $job = ServiceJob::create([
                'store_id'            => $store->id,
                'job_number'          => ServiceJob::generateNumber($store->id),
                'voucher_no'          => $validated['voucher_no'] ?? null,
                'customer_id'         => $validated['customer_id'] ?? null,
                'contact_name'        => $validated['contact_name'] ?? null,
                'contact_phone'       => $validated['contact_phone'] ?? null,
                'shipping_address'    => $validated['shipping_address'] ?? null,
                'device_type'         => $deviceType,
                'brand'               => $validated['brand'] ?? null,
                'category'            => $validated['category'] ?? null,
                'model'               => $validated['model'] ?? null,
                'color'               => $validated['color'] ?? null,
                'storage'             => $validated['storage'] ?? null,
                'imei_serial'         => $validated['imei_serial'] ?? null,
                'reported_problem'    => $reportedProblem,
                'intake_condition'    => $validated['intake_condition'] ?? null,
                'accessories'         => $validated['accessories'] ?? null,
                'pattern_lock'        => $validated['pattern_lock'] ?? null,
                'device_password'     => $validated['device_password'] ?? null,
                'technician_id'       => $validated['technician_id'] ?? null,
                'status'              => $initialStatus,
                'estimated_charge'    => $validated['estimated_charge'] ?? 0,
                'notes'               => $validated['notes'] ?? null,
                'warranty_notes'      => $validated['warranty_notes'] ?? null,
                'estimated_completion' => $validated['estimated_completion'] ?? null,
                'created_by'          => $creatorId,
            ]);

            if (!empty($items)) {
                $job->items()->createMany($items);
            }

            ServiceJobStatus::create([
                'service_job_id' => $job->id,
                'status'         => $initialStatus,
                'note'           => 'Service ticket created',
                'changed_by'     => $creatorId,
            ]);

            // Advance payment on intake
            $advance = (float) ($validated['advance_payment'] ?? 0);
            if ($advance > 0) {
                ServiceJobPayment::create([
                    'service_job_id' => $job->id,
                    'method'         => $validated['payment_method'] ?? 'cash',
                    'amount'         => $advance,
                    'reference'      => 'Advance payment on intake',
                    'created_by'     => $creatorId,
                ]);
            }

            return $job;
        });

        return redirect()
            ->route('store.admin.service_jobs.show', [...$context->getRouteParams(), 'job' => $job->id])
            ->with('success', __('messages.repair_created'));
    }

    // ── Show & Print ───────────────────────────────────────────────────────

    public function show(StoreContext $context, string $store_slug, ServiceJob $job): View
    {
        $store = $context->getStore();
        if ($job->store_id !== $store->id) {
            abort(404);
        }

        $job->load(['customer', 'technician', 'statusHistory.changer', 'payments.creator', 'items.product']);

        return view('admin.service_jobs.show', [
            'store'           => $store,
            'storeRouteParams' => $context->getRouteParams(),
            'job'             => $job,
        ]);
    }

    public function printTicket(StoreContext $context, string $store_slug, ServiceJob $job): View
    {
        $store = $context->getStore();
        if ($job->store_id !== $store->id) {
            abort(404);
        }

        $job->load(['customer', 'technician', 'payments', 'items']);

        return view('admin.service_jobs.print', [
            'store'           => $store,
            'storeRouteParams' => $context->getRouteParams(),
            'job'             => $job,
        ]);
    }

    // ── Edit & Update ──────────────────────────────────────────────────────

    public function edit(StoreContext $context, string $store_slug, ServiceJob $job): View
    {
        $store = $context->getStore();
        if ($job->store_id !== $store->id) {
            abort(404);
        }

        $job->load('items');
        [$customers, $technicians, $products, $serviceSettings] = $this->createFormData($store);

        return view('admin.service_jobs.edit', [
            'store'           => $store,
            'storeRouteParams' => $context->getRouteParams(),
            'job'             => $job,
            'customers'       => $customers,
            'technicians'     => $technicians,
            'products'        => $products,
            'serviceSettings' => $serviceSettings,
        ]);
    }

    public function update(Request $request, StoreContext $context, string $store_slug, ServiceJob $job): RedirectResponse
    {
        if ($job->store_id !== $context->getStore()->id) {
            abort(404);
        }

        $validated = $this->validateJob($request, $context->getStore());
        $items     = $this->normalizeItems($request, $context->getStore(), 'items');

        DB::transaction(function () use ($job, $validated, $items) {
            $job->update(collect($validated)->except('items')->all());

            // Non-deducted lines are replaced; consumed parts stay.
            $job->items()->where('is_deducted', false)->delete();
            $job->items()->createMany($items);
        });

        return redirect()
            ->route('store.admin.service_jobs.show', [...$context->getRouteParams(), 'job' => $job->id])
            ->with('success', __('messages.repair_updated'));
    }

    // ── Status & Payment ───────────────────────────────────────────────────

    public function updateStatus(Request $request, StoreContext $context, string $store_slug, ServiceJob $job): RedirectResponse
    {
        if ($job->store_id !== $context->getStore()->id) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', ServiceJob::STATUSES),
            'note'   => 'nullable|string|max:500',
        ]);

        if ($job->isTerminal()) {
            return back()->withErrors(['status' => __('messages.repair_terminal')]);
        }

        if ($validated['status'] === $job->status) {
            return back()->withErrors(['status' => __('messages.repair_same_status')]);
        }

        DB::transaction(function () use ($job, $validated) {
            $job->update(['status' => $validated['status']]);

            ServiceJobStatus::create([
                'service_job_id' => $job->id,
                'status'         => $validated['status'],
                'note'           => $validated['note'] ?? null,
                'changed_by'     => Auth::id(),
            ]);
        });

        return back()->with('success', __('messages.repair_status_updated'));
    }

    public function addPayment(Request $request, StoreContext $context, string $store_slug, ServiceJob $job): RedirectResponse
    {
        if ($job->store_id !== $context->getStore()->id) {
            abort(404);
        }

        $validated = $request->validate([
            'method'    => 'required|in:cash,kpay,wavepay,cb_pay,mmqr',
            'amount'    => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
        ]);

        if ((float) $validated['amount'] > $job->outstanding()) {
            return back()->withErrors(['amount' => __('messages.repair_overpay')]);
        }

        ServiceJobPayment::create([
            'service_job_id' => $job->id,
            'method'         => $validated['method'],
            'amount'         => $validated['amount'],
            'reference'      => $validated['reference'] ?? null,
            'created_by'     => Auth::id(),
        ]);

        return back()->with('success', __('messages.repair_payment_recorded'));
    }

    public function deductItem(
        StoreContext $context,
        string $store_slug,
        ServiceJob $job,
        ServiceJobItem $item,
        InventoryService $inventory
    ): RedirectResponse {
        if ($job->store_id !== $context->getStore()->id) {
            abort(404);
        }
        if ($item->service_job_id !== $job->id) {
            abort(404);
        }
        if ($item->is_deducted) {
            return back()->withErrors(['items' => __('messages.repair_part_already_deducted')]);
        }
        if (! $item->isPart() || $item->product_id === null) {
            return back()->withErrors(['items' => __('messages.repair_part_requires_product')]);
        }
        if ($job->isTerminal()) {
            return back()->withErrors(['items' => __('messages.repair_part_terminal')]);
        }

        try {
            DB::transaction(function () use ($context, $job, $item, $inventory) {
                $inventory->postMovement([
                    'store_id'              => $context->getStore()->id,
                    'product_id'            => $item->product_id,
                    'movement_type'         => InventoryMovementType::ServiceConsumption->value,
                    'quantity_delta'        => -1 * $item->quantity,
                    'source_type'           => 'service_job',
                    'source_id'             => $job->id,
                    'client_transaction_id' => "service-job-{$job->id}-item-{$item->id}",
                    'occurred_at'           => now(),
                    'posted_by'             => Auth::id(),
                    'metadata'              => [
                        'service_job_item_id' => $item->id,
                        'job_number'          => $job->job_number,
                        'part_name'           => $item->name,
                    ],
                ], ['allow_negative' => false]);

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

    private function filteredQuery(Request $request, Store $store, string $tab): Builder
    {
        $query = ServiceJob::where('store_id', $store->id)
            ->with(['customer', 'technician', 'payments']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($w) use ($search) {
                $w->where('job_number', 'like', "%{$search}%")
                  ->orWhere('voucher_no', 'like', "%{$search}%")
                  ->orWhere('imei_serial', 'like', "%{$search}%")
                  ->orWhere('device_type', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && in_array($request->input('status'), ServiceJob::STATUSES, true)) {
            $query->where('status', $request->input('status'));
        }

        if ($tab !== 'all') {
            $query->whereIn('status', self::TAB_BUCKETS[$tab]);
        }

        $from = $request->input('date_from', $request->input('from'));
        $to   = $request->input('date_to', $request->input('to'));
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
        $total    = array_sum($byStatus);
        $terminal = ($byStatus['delivered'] ?? 0) + ($byStatus['cancelled'] ?? 0) + ($byStatus['unrepairable'] ?? 0);

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

    /** @return array<string, int> */
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
            'contact_name'        => 'nullable|string|max:120',
            'contact_phone'       => 'nullable|string|max:40',
            'shipping_address'    => 'nullable|string|max:1000',
            'device_type'         => 'nullable|string|max:120',
            'brand'               => 'nullable|string|max:120',
            'category'            => 'nullable|string|max:120',
            'model'               => 'nullable|string|max:120',
            'color'               => 'nullable|string|max:60',
            'storage'             => 'nullable|string|max:60',
            'imei_serial'         => 'nullable|string|max:60',
            'reported_problem'    => 'nullable|string|max:1000',
            'intake_condition'    => 'nullable|string|max:1000',
            'accessories'         => 'nullable|string|max:500',
            'pattern_lock'        => 'nullable|string|max:255',
            'device_password'     => 'nullable|string|max:120',
            'status'              => 'nullable|string|max:32',
            'diagnosis'           => 'nullable|string|max:1000',
            'estimated_charge'    => 'nullable|numeric|min:0',
            'final_charge'        => 'nullable|numeric|min:0',
            'advance_payment'     => 'nullable|numeric|min:0',
            'payment_method'      => 'nullable|string|max:30',
            'voucher_no'          => 'nullable|string|max:40',
            'notes'               => 'nullable|string|max:1000',
            'warranty_notes'      => 'nullable|string|max:1000',
            'estimated_completion' => 'nullable|date',
            'items'               => 'nullable|array',
            'items.*.item_type'   => 'required|in:service,part',
            'items.*.name'        => 'nullable|string|max:120',
            'items.*.sku'         => 'nullable|string|max:40',
            'items.*.product_id'  => ['nullable', Rule::exists('products', 'id')->where('store_id', $store->id)],
            'items.*.quantity'    => 'required|integer|min:1|max:100000',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);
    }

    private function normalizeItems(Request $request, Store $store, string $field): array
    {
        $raw  = (array) $request->input($field, []);
        $rows = [];

        foreach ($raw as $entry) {
            $itemType = $entry['item_type'] ?? 'part';
            $name     = trim((string) ($entry['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $productId = !empty($entry['product_id']) ? (int) $entry['product_id'] : null;
            $quantity  = (int) ($entry['quantity'] ?? 1);
            $unitPrice = (float) ($entry['unit_price'] ?? 0);

            if ($productId !== null) {
                $product = Product::find($productId);
                if (!$product || (int) $product->store_id !== (int) $store->id) {
                    continue;
                }
            }

            $rows[] = [
                'item_type'  => $itemType,
                'name'       => $name,
                'sku'        => !empty($entry['sku']) ? trim((string) $entry['sku']) : null,
                'product_id' => $productId,
                'quantity'   => $quantity,
                'unit_price' => $unitPrice,
                'subtotal'   => number_format($quantity * $unitPrice, 2, '.', ''),
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
