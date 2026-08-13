<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CashierShift;
use App\POS\Services\CashierShiftService;
use App\POS\Services\CustomerDebtService;
use App\POS\Services\PosSaleService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * First /pos module — cashier shifts + opening cash (target-design §2.10).
 *
 * Routes are statically registered under /store/{store_slug}/pos with
 * ResolveStoreContext + EnsureStoreAccess (store_manager/staff) — the backend
 * authorization stays authoritative; the shift id is always re-validated
 * against the resolved store to block cross-store tampering.
 */
class CashierShiftController extends Controller
{
    public function __construct(
        protected CashierShiftService $shifts,
        protected PosSaleService $sales,
        protected CustomerDebtService $debts,
    ) {
    }

    public function index(StoreContext $context): View
    {
        $store = $context->getStore();
        $user = auth()->user();

        // Only the cashier's OWN open shift is shown. Showing another
        // cashier's open shift would be misleading — posting already requires
        // an own shift via openShiftFor().
        $openShift = $this->shifts->openShiftFor($store, $user);

        $summary = $this->shifts->dailySummary($store, now());

        $cart = $this->sales->cartResolved($store);
        $cartTotals = $this->sales->cartTotals($store);
        $heldSales = \App\POS\Models\PosSale::query()
            ->with('items')
            ->where('store_id', $store->id)
            ->where('status', 'held')
            ->latest()
            ->limit(10)
            ->get();
        $todaySales = $this->sales->todaySales($store);
        $outstanding = $this->debts->outstandingCustomers($store);
        $outstandingTotal = array_reduce($outstanding, fn ($carry, $c) => bcadd($carry, $c['balance'], 2), '0');

        return view('pos.index', compact('store', 'openShift', 'summary', 'cart', 'cartTotals', 'heldSales', 'todaySales', 'outstanding', 'outstandingTotal'));
    }

    public function open(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'register_name' => ['required', 'string', 'max:100'],
            'opening_cash' => ['nullable', 'numeric', 'min:0'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        try {
            $this->shifts->openShift($store, $data, auth()->user());
        } catch (InventoryException $e) {
            return back()->withErrors(['shift' => $e->getMessage()]);
        }

        return redirect()->route('pos.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.shift_opened'));
    }

    public function cashEvent(Request $request, string $store_slug, CashierShift $shift, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $this->authorizeShift($shift, $store);

        $data = $request->validate([
            'type' => ['required', 'in:cash_in,cash_out'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->shifts->addCashEvent($shift, $data, auth()->user());
        } catch (InventoryException $e) {
            return back()->withErrors(['shift' => $e->getMessage()]);
        }

        return back()->with('success', __('messages.cash_event_recorded'));
    }

    public function close(Request $request, string $store_slug, CashierShift $shift, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $this->authorizeShift($shift, $store);

        $data = $request->validate([
            'actual_closing_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'manager_approval' => ['nullable', 'boolean'],
        ]);

        try {
            $this->shifts->closeShift($shift, $data, auth()->user());
        } catch (InventoryException $e) {
            return back()->withErrors(['shift' => $e->getMessage()]);
        }

        return back()->with('success', __('messages.shift_closed'));
    }

    private function authorizeShift(CashierShift $shift, Store $store): void
    {
        if ((int) $shift->store_id !== (int) $store->id) {
            abort(403, 'Unauthorized store shift.');
        }
    }
}
