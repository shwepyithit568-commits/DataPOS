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

    public function index(StoreContext $context): View
    {
        $store = $context->getStore();

        $requests = $this->adjustments->recent($store);

        // Attach each line's current on-hand so the manager can sanity-check.
        $requests->each(function ($req) {
            $req->items->each(function ($item) {
                $item->on_hand = $this->inventory->totalOnHand($item->store_id, $item->product_id, $item->product_variant_id);
            });
        });

        return view('pos.adjustments', compact('store', 'requests'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'not_in:0'],
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
