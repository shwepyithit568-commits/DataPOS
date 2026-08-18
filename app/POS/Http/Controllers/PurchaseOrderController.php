<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\POS\Exceptions\InventoryException;
use App\POS\Services\PurchaseOrderService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Purchase order lifecycle (alinthit_pos style).
 *
 * GET    /purchases           — PO list (index)
 * GET    /purchases/create    — new PO form
 * POST   /purchases           — save PO as pending
 * GET    /purchases/{po}      — PO detail
 * POST   /purchases/{po}/order    — pending → ordered
 * POST   /purchases/{po}/receive  — ordered → received (posts stock)
 * POST   /purchases/{po}/cancel   — pending|ordered → cancelled
 */
class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $purchaseOrders,
    ) {
    }

    /** List POs for the store. */
    public function index(StoreContext $context): View
    {
        $store = $context->getStore();
        $status = request('status');
        $pos = $this->purchaseOrders->listForStore($store, $status);

        return view('pos.purchases.index', compact('store', 'pos', 'status'));
    }

    /** Create form — empty PO builder. */
    public function create(StoreContext $context): View
    {
        $store = $context->getStore();
        $suppliers = \App\Models\Supplier::where('store_id', $store->id)->orderBy('name')->get();

        return view('pos.purchases.create', compact('store', 'suppliers'));
    }

    /** Save a new PO as pending. */
    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'integer'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $po = $this->purchaseOrders->create(
                $store,
                $data['items'],
                $data['supplier_id'] ?? null,
                $data['reference'] ?? null,
                $data['notes'] ?? null,
                $request->user(),
            );
        } catch (InventoryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.purchases.show', ['store_slug' => $store->slug, 'purchaseOrder' => $po->id])
            ->with('success', __('messages.po_created') . ' — ' . $po->po_number);
    }

    /** PO detail page. */
    public function show(StoreContext $context, string $store_slug, int $purchaseOrder): View
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        return view('pos.purchases.show', compact('store', 'po'));
    }

    /** pending → ordered. */
    public function order(Request $request, StoreContext $context, string $store_slug, int $purchaseOrder): RedirectResponse
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        try {
            $this->purchaseOrders->markOrdered($po, $request->user());
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.po_marked_ordered') . ' — ' . $po->po_number);
    }

    /** ordered → received: posts stock. */
    public function receive(Request $request, StoreContext $context, string $store_slug, int $purchaseOrder): RedirectResponse
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        try {
            $result = $this->purchaseOrders->receive($po, $request->user());
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.po_received') . ' — ' . $result['receipt']->receipt_number);
    }

    /** pending|ordered → cancelled. */
    public function cancel(Request $request, StoreContext $context, string $store_slug, int $purchaseOrder): RedirectResponse
    {
        $store = $context->getStore();
        $po = $this->purchaseOrders->findForStore($store, $purchaseOrder);

        if (! $po) {
            abort(404);
        }

        try {
            $this->purchaseOrders->cancel($po, $request->user());
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.po_cancelled') . ' — ' . $po->po_number);
    }
}
