<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\POS\Enums\InventoryMovementType;
use App\POS\Models\ServiceJobItem;
use App\POS\Services\InventoryService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SparePartController extends Controller
{
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $query = ServiceJobItem::whereHas('job', fn ($q) => $q->where('store_id', $store->id))
            ->where('item_type', 'part')
            ->with(['job.customer', 'product.category']);

        // Search by part name, SKU, Job Number, Customer Name, or Device
        $search = trim((string) $request->input('search', $request->input('q', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('job', function ($jq) use ($search) {
                        $jq->where('job_number', 'like', "%{$search}%")
                            ->orWhere('contact_name', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
                    });
            });
        }

        // Filter by Stock Deduction status
        $deductedFilter = $request->input('deducted', 'all');
        if ($deductedFilter === 'deducted') {
            $query->where('is_deducted', true);
        } elseif ($deductedFilter === 'pending') {
            $query->where('is_deducted', false);
        }

        // Filter by Category
        if ($categoryId = $request->input('category_id')) {
            $query->whereHas('product', fn ($pq) => $pq->where('category_id', $categoryId));
        }

        // Filter by Date Range (supports both date_from/date_to and from/to)
        if ($from = $request->input('date_from', $request->input('from'))) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('date_to', $request->input('to'))) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'price_desc' => $query->orderByDesc('unit_price'),
            'price_asc' => $query->orderBy('unit_price'),
            'subtotal_desc' => $query->orderByDesc('subtotal'),
            'qty_desc' => $query->orderByDesc('quantity'),
            default => $query->latest('id'),
        };

        // Summary metrics across all repair parts for this store
        $baseMetricsQuery = ServiceJobItem::whereHas('job', fn ($q) => $q->where('store_id', $store->id))
            ->where('item_type', 'part');

        $metrics = [
            'total_count' => (clone $baseMetricsQuery)->count(),
            'total_qty' => (clone $baseMetricsQuery)->sum('quantity'),
            'total_value' => (clone $baseMetricsQuery)->sum('subtotal'),
            'deducted_count' => (clone $baseMetricsQuery)->where('is_deducted', true)->count(),
            'deducted_qty' => (clone $baseMetricsQuery)->where('is_deducted', true)->sum('quantity'),
            'deducted_value' => (clone $baseMetricsQuery)->where('is_deducted', true)->sum('subtotal'),
            'pending_count' => (clone $baseMetricsQuery)->where('is_deducted', false)->count(),
            'pending_qty' => (clone $baseMetricsQuery)->where('is_deducted', false)->sum('quantity'),
            'pending_value' => (clone $baseMetricsQuery)->where('is_deducted', false)->sum('subtotal'),
        ];

        $perPage = $request->input('per_page', 25);
        $perPageCount = ($perPage === 'all' || (int) $perPage > 1000) ? 10000 : (int) $perPage;

        $items = $query->paginate($perPageCount)->withQueryString();
        $categories = Category::where('store_id', $store->id)->orderBy('name')->pluck('name', 'id')->toArray();
        $exportUrl = route('store.admin.spare_parts.export', array_merge($storeRouteParams, request()->except(['page'])));

        return view('admin.spare_parts.index', compact(
            'store',
            'storeRouteParams',
            'items',
            'metrics',
            'categories',
            'search',
            'deductedFilter',
            'sort',
            'exportUrl'
        ));
    }

    public function export(StoreContext $context, Request $request): StreamedResponse
    {
        $store = $context->getStore();

        $query = ServiceJobItem::whereHas('job', fn ($q) => $q->where('store_id', $store->id))
            ->where('item_type', 'part')
            ->with(['job.customer', 'product.category']);

        $search = trim((string) $request->input('search', $request->input('q', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('job', function ($jq) use ($search) {
                        $jq->where('job_number', 'like', "%{$search}%")
                            ->orWhere('contact_name', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
                    });
            });
        }

        $deductedFilter = $request->input('deducted', 'all');
        if ($deductedFilter === 'deducted') {
            $query->where('is_deducted', true);
        } elseif ($deductedFilter === 'pending') {
            $query->where('is_deducted', false);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->whereHas('product', fn ($pq) => $pq->where('category_id', $categoryId));
        }

        if ($from = $request->input('date_from', $request->input('from'))) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('date_to', $request->input('to'))) {
            $query->whereDate('created_at', '<=', $to);
        }

        $items = $query->latest('id')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="spare-parts-' . now()->format('Ymd-His') . '.csv"',
        ];

        return response()->stream(function () use ($items) {
            $stream = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fputs($stream, "\xEF\xBB\xBF");

            fputcsv($stream, [
                'Date',
                'Job Number',
                'Device / Model',
                'Customer',
                'Part Name',
                'SKU',
                'Category',
                'Quantity',
                'Unit Price (MMK)',
                'Subtotal (MMK)',
                'Stock Status',
            ]);

            /** @var ServiceJobItem $item */
            foreach ($items as $item) {
                $job = $item->job;
                $customerName = $job?->customer?->name ?? $job?->contact_name ?? 'Walk-in';
                $device = trim(($job?->brand ?? '') . ' ' . ($job?->model ?? $job?->device_type ?? ''));
                $categoryName = $item->product?->category?->name ?? '—';
                $status = $item->is_deducted ? 'Deducted' : 'Pending';

                fputcsv($stream, [
                    $item->created_at?->format('Y-m-d H:i') ?? '',
                    $job?->job_number ?? '',
                    $device,
                    $customerName,
                    $item->name,
                    $item->sku ?? '',
                    $categoryName,
                    $item->quantity,
                    number_format((float) $item->unit_price, 2),
                    number_format((float) $item->subtotal, 2),
                    $status,
                ]);
            }

            fclose($stream);
        }, 200, $headers);
    }

    public function deductItem(StoreContext $context, Request $request, string $store_slug, ServiceJobItem $item, InventoryService $inventory): RedirectResponse
    {
        $store = $context->getStore();
        $job = $item->job;

        if (! $job || (int) $job->store_id !== (int) $store->id) {
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
            DB::transaction(function () use ($store, $job, $item, $inventory) {
                $inventory->postMovement([
                    'store_id' => $store->id,
                    'product_id' => $item->product_id,
                    'movement_type' => InventoryMovementType::ServiceConsumption->value,
                    'quantity_delta' => -1 * $item->quantity,
                    'source_type' => 'service_job',
                    'source_id' => $job->id,
                    'client_transaction_id' => "service-job-{$job->id}-item-{$item->id}",
                    'occurred_at' => now(),
                    'posted_by' => Auth::id(),
                    'metadata' => [
                        'service_job_item_id' => $item->id,
                        'job_number' => $job->job_number,
                        'part_name' => $item->name,
                    ],
                ], [
                    'allow_negative' => false,
                ]);

                $item->update(['is_deducted' => true]);
            });

            return back()->with('success', __('messages.spare_parts_deducted_success'));
        } catch (\Exception $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }
    }
}
