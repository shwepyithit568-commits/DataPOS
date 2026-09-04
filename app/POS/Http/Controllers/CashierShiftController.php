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

        $shiftsEnabled = $store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS);

        // Only the cashier's OWN open shift is shown when shift tracking is enabled.
        $openShift = $shiftsEnabled ? $this->shifts->openShiftFor($store, $user) : null;

        // Open shifts held by OTHER cashiers — surfaced so the page can say
        // "Register X is in use" instead of a silent "no shift" + rejection.
        $occupiedRegisters = $shiftsEnabled
            ? CashierShift::query()
                ->with('cashier')
                ->where('store_id', $store->id)
                ->where('status', 'open')
                ->when($openShift, fn ($q) => $q->where('id', '!=', $openShift->id))
                ->orderBy('register_name')
                ->get()
            : collect();

        $summary = $shiftsEnabled
            ? $this->shifts->dailySummary($store, now())
            : [
                'shifts' => collect(),
                'shift_count' => 0,
                'opening_cash' => '0.00',
                'cash_sales' => '0.00',
                'cash_refunds' => '0.00',
                'cash_in' => '0.00',
                'cash_out' => '0.00',
                'expected' => '0.00',
                'actual' => '0.00',
                'difference' => '0.00',
            ];

        $cart = $this->sales->cartResolved($store);
        $cartTotals = $this->sales->cartTotals($store);
        $todaySales = $this->sales->todaySales($store);
        $outstanding = $this->debts->outstandingCustomers($store);
        $outstandingTotal = array_reduce($outstanding, fn ($carry, $c) => bcadd($carry, $c['balance'], 2), '0');

        return view('pos.index', compact('store', 'openShift', 'occupiedRegisters', 'summary', 'cart', 'cartTotals', 'todaySales', 'outstanding', 'outstandingTotal'));
    }

    public function open(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'register_name' => ['required', 'string', 'max:100'],
            // decimal (not plain numeric): bcmath rejects scientific notation
            // ("1e3") with a ValueError — this rule blocks it before the shift
            // service's bc* calls ever see it.
            'opening_cash' => ['nullable', 'decimal:0,2', 'min:0'],
            'branch_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('branches', 'id')->where('store_id', $store->id)],
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
            'amount' => ['required', 'decimal:0,2', 'gt:0'],
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
            'actual_closing_amount' => ['required', 'decimal:0,2', 'min:0'],
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
