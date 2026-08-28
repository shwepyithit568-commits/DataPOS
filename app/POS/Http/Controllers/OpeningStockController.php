<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\OpeningStockRequest;
use App\POS\Services\OpeningStockService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Opening stock with manager review (MVP Phase 2).
 *
 * GET  /store/{slug}/pos/opening-stock              — submit form + request list
 * POST /store/{slug}/pos/opening-stock              — create a PENDING request
 * POST /store/{slug}/pos/opening-stock/{req}/approve — manager approval → posts
 *                                                      opening_balance movements
 * POST /store/{slug}/pos/opening-stock/{req}/reject  — manager rejection
 */
class OpeningStockController extends Controller
{
    public function __construct(
        protected OpeningStockService $openingStock,
        protected \App\POS\Services\InventoryService $inventory,
    ) {
    }

    public function index(StoreContext $context): View
    {
        $store = $context->getStore();

        $requests = $this->openingStock->recent($store);

        // Attach each line's current on-hand so the manager can sanity-check.
        $requests->each(function ($req) {
            $req->items->each(function ($item) {
                $item->on_hand = $this->inventory->totalOnHand($item->store_id, $item->product_id, $item->product_variant_id);
            });
        });

        return view('pos.opening_stock', compact('store', 'requests'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            // decimal (not plain numeric): the service does bcmath, which
            // throws a ValueError on scientific notation ("1e3").
            'items.*.quantity' => ['required', 'decimal:0,3', 'gt:0'],
            'items.*.unit_cost' => ['required', 'decimal:0,2', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $requestDoc = $this->openingStock->create(
                $store,
                $data['items'],
                $data['notes'] ?? null,
                $request->user(),
            );
        } catch (InventoryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.opening-stock.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.opening_stock_submitted') . ' — ' . $requestDoc->request_number);
    }

    public function approve(Request $request, string $store_slug, OpeningStockRequest $openingStockRequest, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ((int) $openingStockRequest->store_id !== (int) $store->id) {
            abort(404);
        }

        try {
            $this->openingStock->approve($store, $openingStockRequest, $request->user(), $request->input('review_notes'));
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.opening_stock_approved') . ' — ' . $openingStockRequest->request_number);
    }

    public function reject(Request $request, string $store_slug, OpeningStockRequest $openingStockRequest, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ((int) $openingStockRequest->store_id !== (int) $store->id) {
            abort(404);
        }

        try {
            $this->openingStock->reject($store, $openingStockRequest, $request->user(), $request->input('review_notes'));
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.opening_stock_rejected') . ' — ' . $openingStockRequest->request_number);
    }
}
