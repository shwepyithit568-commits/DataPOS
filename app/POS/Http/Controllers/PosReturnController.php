<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\PosSale;
use App\POS\Services\CashierShiftService;
use App\POS\Services\PosReturnService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * POS sale returns / refunds (target-design §2.9 — SoT §15.1).
 *
 * GET  /store/{slug}/pos/sales/{sale}/refund   — refund form (refundable qty)
 * POST /store/{slug}/pos/sales/{sale}/refunds  — post the return atomically
 *
 * Store-scoped like the rest of the POS module; every operation re-validates
 * the sale against the resolved store server-side.
 */
class PosReturnController extends Controller
{
    public function __construct(
        protected PosReturnService $returns,
        protected CashierShiftService $shifts,
    ) {
    }

    public function create(Request $request, string $store_slug, PosSale $sale, StoreContext $context): View
    {
        $store = $context->getStore();

        if ((int) $sale->store_id !== (int) $store->id) {
            abort(404);
        }
        if (! $sale->isPosted()) {
            abort(404);
        }

        $sale->load(['items', 'payments', 'cashier', 'customer']);
        $refunded = $this->returns->refundedQuantities($store, $sale);
        // Remaining credit portion of the sale — the refund form caps its
        // credit field with this (over-refunding the receivable is blocked).
        $creditLeft = $this->returns->refundableCreditTotal($store, $sale);
        $shift = $this->shifts->openShiftFor($store, $request->user());

        return view('pos.refund', compact('store', 'sale', 'refunded', 'creditLeft', 'shift'));
    }

    public function store(Request $request, string $store_slug, PosSale $sale, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ((int) $sale->store_id !== (int) $store->id) {
            abort(404);
        }

        $user = $request->user();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.pos_sale_item_id' => ['required', 'integer'],
            // decimal (not plain numeric): bcmath throws on scientific
            // notation ("1e3") — block it here so the return math never 500s.
            'items.*.quantity' => ['required', 'decimal:0,3', 'gt:0'],
            'refunds' => ['required', 'array', 'min:1'],
            'refunds.*.method' => ['required', 'string', 'in:cash,credit'],
            'refunds.*.amount' => ['nullable', 'decimal:0,2', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $shift = $this->shifts->openShiftFor($store, $user);

        $items = array_map(fn ($i) => [
            'pos_sale_item_id' => (int) $i['pos_sale_item_id'],
            'quantity' => (string) $i['quantity'],
        ], $data['items']);

        try {
            $refund = $this->returns->post(
                store: $store,
                sale: $sale,
                items: $items,
                refunds: $data['refunds'],
                actor: $user,
                shift: $shift,
                clientTransactionId: 'pos_return:' . $store->id . ':' . $sale->id . ':' . now()->format('YmdHis') . ':' . random_int(1000, 9999),
            );
        } catch (InventoryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.returns.show', ['store_slug' => $store->slug, 'return' => $refund->id])
            ->with('success', __('messages.refund_posted') . " {$refund->refund_number}");
    }
}
