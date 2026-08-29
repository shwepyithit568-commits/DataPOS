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

        $search = trim((string) $request->input('search', $request->input('q', '')));
        $filter = $request->input('filter', 'all');
        $perPage = $request->input('per_page', 25);
        $perPageCount = ($perPage === 'all' || (int) $perPage > 1000) ? 10000 : (int) $perPage;

        $summary = $this->debts->getReceivablesSummary($store);
        $customers = $this->debts->listCustomersWithBalancesPaginated($store, $search, $filter, $perPageCount);
        $customers->appends($request->except('page'));

        $exportUrl = route('store.admin.receivables.export', array_merge($context->getRouteParams(), request()->except(['page'])));

        return view('admin.receivables.index', compact('store', 'summary', 'customers', 'search', 'filter', 'exportUrl'));
    }

    /**
     * Export receivables as CSV with UTF-8 BOM.
     */
    public function exportCsv(StoreContext $context, Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $search = trim((string) $request->input('search', $request->input('q', '')));
        $filter = $request->input('filter', 'all');

        $customers = $this->debts->listCustomersWithBalancesPaginated($store, $search, $filter, 10000);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="receivables-' . now()->format('Ymd-His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($customers) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($stream, [
                'Customer Name',
                'Phone',
                'Total Incurred (Ks)',
                'Total Paid (Ks)',
                'Outstanding Balance (Ks)',
                'Status',
                'Last Activity',
            ]);

            foreach ($customers as $cust) {
                $bal = (float) $cust->balance;
                $status = $bal > 0 ? 'Active Debt' : 'Settled';

                fputcsv($stream, [
                    $cust->name,
                    $cust->phone ?? '',
                    number_format((float) ($cust->total_debt_incurred ?? 0), 2),
                    number_format((float) ($cust->total_collected ?? 0), 2),
                    number_format($bal, 2),
                    $status,
                    $cust->last_activity ? \Carbon\Carbon::parse($cust->last_activity)->format('Y-m-d H:i') : '',
                ]);
            }

            fclose($stream);
        }, 'receivables-' . now()->format('Ymd-His') . '.csv', $headers);
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

        $hasStoreAssociation = $customerUser->stores()->where('stores.id', $store->id)->exists()
            || CustomerLedgerEntry::where('store_id', $store->id)->where('customer_id', $customerUser->id)->exists();

        if (! $hasStoreAssociation) {
            abort(404, 'Customer not found in this store.');
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

        $hasStoreAssociation = $customerUser->stores()->where('stores.id', $store->id)->exists()
            || CustomerLedgerEntry::where('store_id', $store->id)->where('customer_id', $customerUser->id)->exists();

        if (! $hasStoreAssociation) {
            abort(404, 'Customer not found in this store.');
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

        $hasStoreAssociation = $customerUser->stores()->where('stores.id', $store->id)->exists()
            || CustomerLedgerEntry::where('store_id', $store->id)->where('customer_id', $customerUser->id)->exists();

        if (! $hasStoreAssociation) {
            abort(404, 'Customer not found in this store.');
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
