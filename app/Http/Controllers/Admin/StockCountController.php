<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Store;
use App\POS\Models\Branch;
use App\POS\Models\StockCount;
use App\POS\Models\StockCountLine;
use App\POS\Models\Warehouse;
use App\POS\Services\StockCountService;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockCountController extends Controller
{
    public function __construct(
        protected StockCountService $stockCountService,
    ) {
    }

    /**
     * Display listing of stock count sessions and summary stats.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $search = $request->input('search');
        $status = $request->input('status');

        $stats = $this->stockCountService->getStatistics($store);
        $sessions = $this->stockCountService->listSessions($store, $search, $status, 15);

        return view('admin.stock_count.index', compact('store', 'sessions', 'stats', 'search', 'status'));
    }

    /**
     * Show the session creation form.
     */
    public function create(StoreContext $context): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $categories = Category::where('store_id', $store->id)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $branches = Branch::where('store_id', $store->id)->where('is_active', true)->get();
        $warehouses = Warehouse::where('store_id', $store->id)->where('is_active', true)->get();

        return view('admin.stock_count.create', compact('store', 'categories', 'branches', 'warehouses'));
    }

    /**
     * Store a newly created stock count session.
     */
    public function store(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'scope' => 'required|in:all,category',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'branch_id' => 'nullable|exists:branches,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $session = $this->stockCountService->createSession($store, $validated, auth()->user());

        return redirect()
            ->route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id])
            ->with('success', __('messages.stock_count_session_created', ['number' => $session->session_number]));
    }

    /**
     * Display the interactive stock take sheet.
     */
    public function show(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $stock_count = (int) $request->route('stock_count');
        $session = StockCount::where('store_id', $store->id)
            ->where('id', $stock_count)
            ->with(['createdBy', 'approvedBy', 'branch', 'warehouse'])
            ->firstOrFail();

        $tab = $request->input('tab', 'all');
        $search = $request->input('search');
        $categoryId = $request->input('category_id');

        $linesQuery = StockCountLine::query()
            ->where('stock_count_id', $session->id)
            ->with(['product', 'category', 'variant']);

        if ($tab === 'counted') {
            $linesQuery->where('is_counted', true);
        } elseif ($tab === 'variance') {
            $linesQuery->where('is_counted', true)->where('variance_quantity', '!=', 0);
        } elseif ($tab === 'uncounted') {
            $linesQuery->where('is_counted', false);
        }

        if ($categoryId) {
            $linesQuery->where('category_id', $categoryId);
        }

        if ($search && trim($search) !== '') {
            $term = trim($search);
            $linesQuery->whereHas('product', function ($pq) use ($term) {
                $pq->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            });
        }

        $lines = $linesQuery->orderBy('id')->paginate(50)->withQueryString();

        $sessionCategories = Category::where('store_id', $store->id)
            ->whereIn('id', StockCountLine::where('stock_count_id', $session->id)->pluck('category_id')->unique())
            ->get();

        return view('admin.stock_count.show', compact('store', 'session', 'lines', 'tab', 'search', 'categoryId', 'sessionCategories'));
    }

    /**
     * AJAX endpoint to update a single count line.
     */
    public function updateLine(StoreContext $context, Request $request): JsonResponse
    {
        $store = $context->getStore();
        if (!$store) {
            return response()->json(['error' => 'Store not found'], 404);
        }

        $stock_count = (int) $request->route('stock_count');
        $line = (int) $request->route('line');

        $session = StockCount::where('store_id', $store->id)->where('id', $stock_count)->firstOrFail();

        $validated = $request->validate([
            'counted_quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $updatedLine = $this->stockCountService->saveCountLine(
                $session,
                $line,
                (float) $validated['counted_quantity'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => __('messages.stock_count_saved'),
                'line' => [
                    'id' => $updatedLine->id,
                    'counted_quantity' => (float) $updatedLine->counted_quantity,
                    'variance_quantity' => (float) $updatedLine->variance_quantity,
                    'variance_cost' => (float) $updatedLine->variance_cost,
                    'is_counted' => $updatedLine->is_counted,
                ],
                'session' => [
                    'total_items' => $session->total_items,
                    'counted_items' => $session->counted_items,
                    'variance_items' => $session->variance_items,
                    'total_variance_qty' => (float) $session->total_variance_qty,
                    'total_variance_cost' => (float) $session->total_variance_cost,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Batch save counts from the sheet form.
     */
    public function bulkUpdate(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $stock_count = (int) $request->route('stock_count');
        $session = StockCount::where('store_id', $store->id)->where('id', $stock_count)->firstOrFail();

        $counts = $request->input('lines', []);
        if (is_array($counts) && !empty($counts)) {
            $this->stockCountService->bulkSaveCounts($session, $counts);
        }

        return redirect()
            ->route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id, 'tab' => $request->input('tab', 'all')])
            ->with('success', __('messages.stock_count_saved'));
    }

    /**
     * Barcode/SKU live scanner lookup endpoint.
     */
    public function quickScan(StoreContext $context, Request $request): JsonResponse
    {
        $store = $context->getStore();
        if (!$store) {
            return response()->json([], 404);
        }

        $stock_count = (int) $request->route('stock_count');
        $session = StockCount::where('store_id', $store->id)->where('id', $stock_count)->firstOrFail();
        $query = (string) $request->input('q', '');

        $results = $this->stockCountService->quickScan($session, $query);

        return response()->json($results);
    }

    /**
     * Approve and reconcile physical stock count adjustments.
     */
    public function approve(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $stock_count = (int) $request->route('stock_count');
        $session = StockCount::where('store_id', $store->id)->where('id', $stock_count)->firstOrFail();

        try {
            $this->stockCountService->approveAndReconcile($session, auth()->user());

            return redirect()
                ->route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id])
                ->with('success', __('messages.stock_count_approved_success', ['number' => $session->session_number]));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel an in-progress session.
     */
    public function cancel(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $stock_count = (int) $request->route('stock_count');
        $session = StockCount::where('store_id', $store->id)->where('id', $stock_count)->firstOrFail();

        try {
            $this->stockCountService->cancelSession($session);

            return redirect()
                ->route('store.admin.stock_count.index', ['store_slug' => $store->slug])
                ->with('success', __('messages.stock_count_cancelled'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Printable physical stock audit sheet.
     */
    public function printSheet(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $stock_count = (int) $request->route('stock_count');
        $session = StockCount::where('store_id', $store->id)
            ->where('id', $stock_count)
            ->with(['createdBy', 'approvedBy', 'branch', 'warehouse', 'lines.product', 'lines.category'])
            ->firstOrFail();

        return view('admin.stock_count.print', compact('store', 'session'));
    }
}
