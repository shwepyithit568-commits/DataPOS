<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CustomerLedgerEntry;
use App\POS\Services\CustomerDebtService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerReceivableController extends Controller
{
    public function __construct(
        protected CustomerDebtService $debts,
    ) {
    }

    /**
     * Display receivables dashboard & customer debt listing.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $search = $request->input('search');
        $filter = $request->input('filter');

        $summary = $this->debts->getReceivablesSummary($store);
        $customers = $this->debts->listCustomersWithBalancesPaginated($store, $search, $filter, 15);

        return view('admin.receivables.index', compact('store', 'summary', 'customers', 'search', 'filter'));
    }

    /**
     * Display detailed customer ledger timeline & collection form.
     */
    public function show(StoreContext $context, string $store_slug, int $customer): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $customerUser = User::find($customer);
        if (! $customerUser) {
            abort(404, 'Customer not found.');
        }

        $balance = $this->debts->balanceFor($store->id, $customerUser->id);
        $history = $this->debts->history($store, $customerUser->id, 100);

        $totalIncurred = CustomerLedgerEntry::query()
            ->where('store_id', $store->id)
            ->where('customer_id', $customerUser->id)
            ->where('amount', '>', 0)
            ->sum('amount');

        $totalCollected = CustomerLedgerEntry::query()
            ->where('store_id', $store->id)
            ->where('customer_id', $customerUser->id)
            ->where('type', CustomerLedgerEntry::TYPE_COLLECTION)
            ->sum(\Illuminate\Support\Facades\DB::raw('ABS(amount)'));

        return view('admin.receivables.show', [
            'store' => $store,
            'customer' => $customerUser,
            'balance' => $balance,
            'history' => $history,
            'totalIncurred' => number_format((float) $totalIncurred, 2, '.', ''),
            'totalCollected' => number_format((float) $totalCollected, 2, '.', ''),
        ]);
    }

    /**
     * Record a debt collection payment from customer.
     */
    public function collect(Request $request, StoreContext $context, string $store_slug, int $customer): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $customerUser = User::find($customer);
        if (! $customerUser) {
            abort(404, 'Customer not found.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['nullable', 'string', 'in:cash,kpay,wave,bank,other'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $outstanding = $this->debts->balanceFor($store->id, $customerUser->id);
        if (bccomp((string) $data['amount'], $outstanding, 2) > 0) {
            return back()->withInput()->with('error', __('messages.receivables_collect_exceeds_error', [
                'max' => number_format((float) $outstanding, 0),
            ]));
        }

        $note = trim(($data['payment_method'] ? strtoupper($data['payment_method']) . ' ' : '') .
            ($data['reference_no'] ? "(Ref: {$data['reference_no']}) " : '') .
            ($data['notes'] ?? ''));

        try {
            $this->debts->collect(
                store: $store,
                customerId: $customerUser->id,
                amount: (string) $data['amount'],
                actor: $request->user(),
                notes: $note ?: 'Customer debt collection',
            );
        } catch (InventoryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('store.admin.receivables.show', ['store_slug' => $store->slug, 'customer' => $customerUser->id])
            ->with('success', __('messages.debt_collected') . ' — ' . number_format((float) $data['amount'], 0) . ' Ks');
    }

    /**
     * Render printable customer statement (A4 / 80mm).
     */
    public function statement(StoreContext $context, Request $request, string $store_slug, int $customer): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $customerUser = User::find($customer);
        if (! $customerUser) {
            abort(404, 'Customer not found.');
        }

        $balance = $this->debts->balanceFor($store->id, $customerUser->id);
        $history = $this->debts->history($store, $customerUser->id, 200);

        $format = $request->input('format', 'a4'); // 'a4' or 'thermal'

        return view('admin.receivables.statement', [
            'store' => $store,
            'customer' => $customerUser,
            'balance' => $balance,
            'history' => $history,
            'format' => $format,
        ]);
    }
}
