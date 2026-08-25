<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EloadAccount;
use App\Models\EloadTransaction;
use App\POS\Services\EloadService;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EloadController extends Controller
{
    public function __construct(
        protected EloadService $eloadService
    ) {
    }

    /**
     * E-Load Dashboard & Register Index.
     */
    public function index(Request $request, string $store_slug, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $perPage = $request->input('per_page', 25);
        if ($perPage === 'all') {
            $perPage = 100000;
        } else {
            $perPage = (int) $perPage;
            if (! in_array($perPage, [20, 25, 50, 100, 200, 100000], true)) {
                $perPage = 25;
            }
        }

        $stats = $this->eloadService->getSummaryStats($store);
        $transactions = $this->eloadService->getTransactions($store, $request->all(), $perPage);
        $accounts = $this->eloadService->getAccounts($store);

        return view('admin.eload.index', compact('store', 'stats', 'transactions', 'accounts'));
    }

    /**
     * Store a new E-Load transaction.
     */
    public function store(Request $request, string $store_slug, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $request->validate([
            'operator'         => ['required', 'string', 'in:mpt,atom,ooredoo,mytel,other'],
            'phone_number'     => ['required', 'string', 'min:7', 'max:20'],
            'amount'           => ['required', 'numeric', 'min:100', 'max:10000000'],
            'type'             => ['nullable', 'string', 'in:topup,data_pack,pin_code,bill_payment'],
            'package_name'     => ['nullable', 'string', 'max:150'],
            'customer_name'    => ['nullable', 'string', 'max:100'],
            'payment_method'   => ['nullable', 'string', 'in:cash,kpay,wavepay,cbpay,ayapay,other'],
            'eload_account_id' => ['nullable', 'integer', 'exists:eload_accounts,id'],
            'cost'             => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:500'],
            'occurred_at'      => ['nullable', 'date'],
        ]);

        $transaction = $this->eloadService->createTransaction($store, $validated, $request->user());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'     => true,
                'message'     => __('messages.eload_success_msg', ['amount' => number_format($transaction->amount), 'phone' => $transaction->phone_number]),
                'transaction' => $transaction,
            ]);
        }

        return back()->with('success', __('messages.eload_success_msg', [
            'amount' => number_format($transaction->amount),
            'phone'  => $transaction->phone_number,
        ]));
    }

    /**
     * Refill operator float balance.
     */
    public function refill(Request $request, string $store_slug, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $request->validate([
            'eload_account_id' => ['required', 'integer', 'exists:eload_accounts,id'],
            'amount'           => ['required', 'numeric', 'min:100', 'max:50000000'],
            'notes'            => ['nullable', 'string', 'max:255'],
        ]);

        $account = EloadAccount::where('store_id', $store->id)->findOrFail($validated['eload_account_id']);
        $this->eloadService->refillAccount($account, (float) $validated['amount'], $validated['notes'] ?? null);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.eload_refill_success', ['operator' => strtoupper($account->operator), 'amount' => number_format($validated['amount'])]),
            ]);
        }

        return back()->with('success', __('messages.eload_refill_success', [
            'operator' => strtoupper($account->operator),
            'amount'   => number_format($validated['amount']),
        ]));
    }

    /**
     * Create or update an operator account.
     */
    public function saveAccount(Request $request, string $store_slug, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $request->validate([
            'id'               => ['nullable', 'integer'],
            'operator'         => ['required', 'string', 'in:mpt,atom,ooredoo,mytel,other'],
            'name'             => ['required', 'string', 'max:100'],
            'phone_number'     => ['nullable', 'string', 'max:50'],
            'balance'          => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active'        => ['nullable', 'boolean'],
        ]);

        $account = $this->eloadService->saveAccount($store, $validated, $request->input('id'));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'account' => $account,
                'message' => 'Operator account saved successfully.',
            ]);
        }

        return back()->with('success', 'Operator account saved successfully.');
    }

    /**
     * Delete an operator account.
     */
    public function deleteAccount(Request $request, string $store_slug, int $id, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $account = EloadAccount::where('store_id', $store->id)->findOrFail($id);
        $this->eloadService->deleteAccount($account);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Operator account removed.',
            ]);
        }

        return back()->with('success', 'Operator account removed.');
    }

    /**
     * Update transaction status (e.g. refund / void).
     */
    public function updateStatus(Request $request, string $store_slug, int $id, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:completed,pending,failed,refunded'],
        ]);

        $transaction = EloadTransaction::where('store_id', $store->id)->findOrFail($id);
        $this->eloadService->updateStatus($transaction, $validated['status']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Transaction status updated.',
            ]);
        }

        return back()->with('success', 'Transaction status updated.');
    }

    /**
     * Render printable thermal receipt slip.
     */
    public function printSlip(Request $request, string $store_slug, int $id, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $transaction = EloadTransaction::where('store_id', $store->id)
            ->with(['account', 'cashier'])
            ->findOrFail($id);

        return view('admin.eload._slip', compact('store', 'transaction'));
    }
}
