<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\POS\Exceptions\InventoryException;
use App\POS\Services\GoodsReceiptService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Simple stock receiving (MVP Phase 2).
 *
 * GET  /store/{slug}/pos/receiving — goods-receipt form + recent receipts
 * POST /store/{slug}/pos/receiving — post the receipt (purchase_received
 *                                    ledger movements + weighted-average cost)
 */
class GoodsReceiptController extends Controller
{
    public function __construct(
        protected GoodsReceiptService $receipts,
    ) {
    }

    public function index(StoreContext $context): View
    {
        $store = $context->getStore();

        $recent = $this->receipts->recent($store);

        return view('pos.receiving', compact('store', 'recent'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'client_transaction_id' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $receipt = $this->receipts->create(
                $store,
                $data['items'],
                $data['reference'] ?? null,
                $data['notes'] ?? null,
                $request->user(),
                $data['client_transaction_id'] ?? null,
            );
        } catch (InventoryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.receiving.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.receiving_posted') . ' — ' . $receipt->receipt_number);
    }
}
