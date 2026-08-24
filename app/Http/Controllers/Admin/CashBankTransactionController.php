<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\POS\Services\FinancialTransactionService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashBankTransactionController extends Controller
{
    public function __construct(
        protected FinancialTransactionService $transactionService
    ) {
    }

    /**
     * Display the Cash & Bank Transactions Register dashboard.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $filters = [
            'account_id' => $request->input('account_id'),
            'type' => $request->input('type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
            'preset' => $request->input('preset', 'this_month'),
        ];

        // Apply date preset if dates are not explicitly supplied
        if (empty($filters['date_from']) && empty($filters['date_to'])) {
            $now = now();
            switch ($filters['preset']) {
                case 'today':
                    $filters['date_from'] = $now->format('Y-m-d');
                    $filters['date_to'] = $now->format('Y-m-d');
                    break;
                case 'yesterday':
                    $y = $now->copy()->subDay();
                    $filters['date_from'] = $y->format('Y-m-d');
                    $filters['date_to'] = $y->format('Y-m-d');
                    break;
                case '7days':
                    $filters['date_from'] = $now->copy()->subDays(6)->format('Y-m-d');
                    $filters['date_to'] = $now->format('Y-m-d');
                    break;
                case '30days':
                    $filters['date_from'] = $now->copy()->subDays(29)->format('Y-m-d');
                    $filters['date_to'] = $now->format('Y-m-d');
                    break;
                case 'this_month':
                    $filters['date_from'] = $now->copy()->startOfMonth()->format('Y-m-d');
                    $filters['date_to'] = $now->copy()->endOfMonth()->format('Y-m-d');
                    break;
                case 'last_month':
                    $lm = $now->copy()->subMonth();
                    $filters['date_from'] = $lm->copy()->startOfMonth()->format('Y-m-d');
                    $filters['date_to'] = $lm->copy()->endOfMonth()->format('Y-m-d');
                    break;
                case 'all':
                default:
                    // No date filter
                    break;
            }
        }

        $stats = $this->transactionService->getStatistics($store, $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $accounts = $this->transactionService->getAccounts($store);
        $transactions = $this->transactionService->listTransactions($store, $filters, 25);

        return view('admin.transactions.index', compact(
            'store',
            'stats',
            'accounts',
            'transactions',
            'filters'
        ));
    }

    /**
     * Record a Deposit (Cash In).
     */
    public function deposit(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'to_account_id' => 'required|exists:financial_accounts,id',
            'amount' => 'required|numeric|min:1',
            'category' => 'nullable|string|max:60',
            'transaction_date' => 'nullable|date',
            'reference_no' => 'nullable|string|max:100',
            'payer_or_payee' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->transactionService->recordDeposit($store, $validated, $request->user());

        return redirect()->route('store.admin.transactions.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.transactions_deposit_success'));
    }

    /**
     * Record a Withdrawal (Cash Out).
     */
    public function withdraw(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'from_account_id' => 'required|exists:financial_accounts,id',
            'amount' => 'required|numeric|min:1',
            'category' => 'nullable|string|max:60',
            'transaction_date' => 'nullable|date',
            'reference_no' => 'nullable|string|max:100',
            'payer_or_payee' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->transactionService->recordWithdrawal($store, $validated, $request->user());

        return redirect()->route('store.admin.transactions.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.transactions_withdraw_success'));
    }

    /**
     * Record an Account-to-Account Fund Transfer.
     */
    public function transfer(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'from_account_id' => 'required|exists:financial_accounts,id',
            'to_account_id' => 'required|exists:financial_accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:1',
            'fee' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:60',
            'transaction_date' => 'nullable|date',
            'reference_no' => 'nullable|string|max:100',
            'payer_or_payee' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->transactionService->recordTransfer($store, $validated, $request->user());

        return redirect()->route('store.admin.transactions.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.transactions_transfer_success'));
    }

    /**
     * Create a new Financial Account.
     */
    public function storeAccount(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'account_type' => 'required|in:cash,mobile_wallet,bank_account,other',
            'account_number' => 'nullable|string|max:80',
            'account_holder' => 'nullable|string|max:120',
            'opening_balance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->transactionService->createAccount($store, $validated, $request->user());

        return redirect()->route('store.admin.transactions.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.transactions_account_created'));
    }

    /**
     * Export transactions to CSV.
     */
    public function export(StoreContext $context, Request $request): StreamedResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $filters = [
            'account_id' => $request->input('account_id'),
            'type' => $request->input('type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
        ];

        return $this->transactionService->exportCsv($store, $filters);
    }

    /**
     * Printable voucher view.
     */
    public function printVoucher(StoreContext $context, string $store_slug, int|string|FinancialTransaction $transaction): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        if (!($transaction instanceof FinancialTransaction)) {
            $transaction = FinancialTransaction::where('store_id', $store->id)->findOrFail($transaction);
        } elseif ($transaction->store_id !== $store->id) {
            abort(404);
        }

        $transaction->load(['fromAccount', 'toAccount', 'recorder']);

        return view('admin.transactions.voucher', compact('store', 'transaction'));
    }
}
