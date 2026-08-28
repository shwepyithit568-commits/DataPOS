<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\InventoryAdjustment;
use App\POS\Services\InventoryAdjustmentService;
use App\POS\Services\InventoryService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Inventory adjustments with manager approval (MVP Phase 2 — final module).
 *
 * GET  /store/{slug}/pos/adjustments              — submit form + request list
 * POST /store/{slug}/pos/adjustments              — create a PENDING request
 * POST /store/{slug}/pos/adjustments/{adj}/approve — manager approval → posts
 *                                                     adjustment_in/out movements
 * POST /store/{slug}/pos/adjustments/{adj}/reject  — manager rejection
 */
class InventoryAdjustmentController extends Controller
{
    public function __construct(
        protected InventoryAdjustmentService $adjustments,
        protected InventoryService $inventory,
    ) {
    }

    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();

        // Calculate KPI stats across all store adjustment requests
        $allStoreQuery = InventoryAdjustment::query()->where('store_id', $store->id);
        $totalCount = (clone $allStoreQuery)->count();
        $pendingCount = (clone $allStoreQuery)->where('status', 'pending')->count();
        $approvedCount = (clone $allStoreQuery)->where('status', 'approved')->count();
        $rejectedCount = (clone $allStoreQuery)->where('status', 'rejected')->count();
        $netQuantity = (clone $allStoreQuery)->where('status', 'approved')->sum('total_quantity');

        $stats = [
            'total' => $totalCount,
            'pending' => $pendingCount,
            'approved' => $approvedCount,
            'rejected' => $rejectedCount,
            'net_quantity' => (float) $netQuantity,
        ];

        // Filters
        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));
        $sort = $request->query('sort', 'newest');
        $perPageParam = $request->query('per_page', 25);
        $perPage = ($perPageParam === 'all' || (int)$perPageParam > 500) ? 500 : (int) $perPageParam;

        $query = InventoryAdjustment::query()
            ->with(['items.product', 'items.productVariant', 'submittedBy', 'reviewedBy', 'warehouse'])
            ->where('store_id', $store->id);

        if (!empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('review_notes', 'like', "%{$search}%")
                  ->orWhereHas('submittedBy', function ($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('items.product', function ($itemQ) use ($search) {
                      $itemQ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                  });
            });
        }

        match ($sort) {
            'oldest' => $query->oldest(),
            'qty_desc' => $query->orderByDesc('total_quantity'),
            'qty_asc' => $query->orderBy('total_quantity'),
            default => $query->latest(),
        };

        $requests = $query->paginate($perPage)->withQueryString();

        // Attach each line's current on-hand so the manager can sanity-check.
        $requests->getCollection()->each(function ($req) {
            $req->items->each(function ($item) {
                $item->on_hand = $this->inventory->totalOnHand($item->store_id, $item->product_id, $item->product_variant_id);
            });
        });

        $filters = [
            'status' => $status,
            'search' => $search,
            'sort' => $sort,
            'per_page' => $perPageParam,
        ];

        $activeFiltersCount = (!empty($status) && $status !== 'all' ? 1 : 0) + ($search !== '' ? 1 : 0);

        return view('pos.adjustments', compact('store', 'requests', 'stats', 'filters', 'activeFiltersCount'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            // decimal (not plain numeric): bcmath throws a ValueError on
            // scientific notation ("1e3"). Sign is allowed (negative = count
            // down), which the decimal rule permits.
            'items.*.quantity' => ['required', 'decimal:0,3', 'not_in:0'],
            'items.*.reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $requestDoc = $this->adjustments->create(
                $store,
                $data['items'],
                $data['notes'] ?? null,
                $request->user(),
            );
        } catch (InventoryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.adjustments.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.adjustment_submitted') . ' — ' . $requestDoc->adjustment_number);
    }

    public function approve(Request $request, string $store_slug, InventoryAdjustment $inventoryAdjustment, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ((int) $inventoryAdjustment->store_id !== (int) $store->id) {
            abort(404);
        }

        try {
            $this->adjustments->approve($store, $inventoryAdjustment, $request->user(), $request->input('review_notes'));
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.adjustment_approved') . ' — ' . $inventoryAdjustment->adjustment_number);
    }

    public function reject(Request $request, string $store_slug, InventoryAdjustment $inventoryAdjustment, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ((int) $inventoryAdjustment->store_id !== (int) $store->id) {
            abort(404);
        }

        try {
            $this->adjustments->reject($store, $inventoryAdjustment, $request->user(), $request->input('review_notes'));
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.adjustment_rejected') . ' — ' . $inventoryAdjustment->adjustment_number);
    }
}
