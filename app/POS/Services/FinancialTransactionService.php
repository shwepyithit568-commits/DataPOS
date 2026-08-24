<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialTransactionService
{
    /**
     * Ensure default standard financial accounts exist for the store.
     */
    public function ensureDefaultAccounts(Store $store): void
    {
        $existingCount = FinancialAccount::where('store_id', $store->id)->count();
        if ($existingCount > 0) {
            return;
        }

        $defaults = [
            [
                'name' => 'Cash in Hand (ကောင်တာငွေသား)',
                'code' => 'cash_in_hand',
                'account_type' => 'cash',
                'account_number' => null,
                'account_holder' => null,
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
                'sort_order' => 1,
                'notes' => 'Primary physical cash drawer at checkout counter',
            ],
            [
                'name' => 'KBZPay (KPay)',
                'code' => 'kpay_wallet',
                'account_type' => 'mobile_wallet',
                'account_number' => null,
                'account_holder' => $store->name,
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
                'sort_order' => 2,
                'notes' => 'Store official KBZPay mobile money wallet',
            ],
            [
                'name' => 'WavePay (Wave Money)',
                'code' => 'wave_wallet',
                'account_type' => 'mobile_wallet',
                'account_number' => null,
                'account_holder' => $store->name,
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
                'sort_order' => 3,
                'notes' => 'Store official WavePay mobile money account',
            ],
            [
                'name' => 'KBZ Bank (ကမ္ဘောဇဘဏ်)',
                'code' => 'kbz_bank',
                'account_type' => 'bank_account',
                'account_number' => null,
                'account_holder' => $store->name,
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
                'sort_order' => 4,
                'notes' => 'KBZ Bank business account',
            ],
            [
                'name' => 'CB Bank (သမဝါယမဘဏ်)',
                'code' => 'cb_bank',
                'account_type' => 'bank_account',
                'account_number' => null,
                'account_holder' => $store->name,
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
                'sort_order' => 5,
                'notes' => 'CB Bank account',
            ],
            [
                'name' => 'AYA Bank (ဧရာဝတီဘဏ်)',
                'code' => 'aya_bank',
                'account_type' => 'bank_account',
                'account_number' => null,
                'account_holder' => $store->name,
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
                'sort_order' => 6,
                'notes' => 'AYA Bank account',
            ],
        ];

        foreach ($defaults as $acc) {
            FinancialAccount::create(array_merge($acc, ['store_id' => $store->id]));
        }
    }

    /**
     * Get summary KPI statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(Store $store, ?string $from = null, ?string $to = null): array
    {
        $this->ensureDefaultAccounts($store);

        $accounts = FinancialAccount::where('store_id', $store->id)
            ->where('is_active', true)
            ->get();

        $totalLiquidity = $accounts->sum('current_balance');
        $cashInHand = $accounts->where('account_type', 'cash')->sum('current_balance');
        $bankAndWallets = $accounts->whereIn('account_type', ['mobile_wallet', 'bank_account'])->sum('current_balance');

        $now = now();
        $startDate = $from ? Carbon::parse($from)->startOfDay() : $now->copy()->startOfMonth();
        $endDate = $to ? Carbon::parse($to)->endOfDay() : $now->copy()->endOfDay();

        $txQuery = FinancialTransaction::where('store_id', $store->id)
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        $totalDeposits = (clone $txQuery)->where('type', 'deposit')->sum('amount');
        $totalWithdrawals = (clone $txQuery)->where('type', 'withdrawal')->sum('amount');
        $totalTransferFees = (clone $txQuery)->where('type', 'transfer')->sum('fee');
        $totalOutflow = $totalWithdrawals + $totalTransferFees;

        return [
            'total_liquidity' => (float) $totalLiquidity,
            'cash_in_hand' => (float) $cashInHand,
            'bank_and_wallets' => (float) $bankAndWallets,
            'total_deposits' => (float) $totalDeposits,
            'total_outflow' => (float) $totalOutflow,
            'accounts_count' => $accounts->count(),
            'period_label' => $startDate->format('M d, Y') . ' — ' . $endDate->format('M d, Y'),
        ];
    }

    /**
     * Get all active accounts for dropdowns and dashboard cards.
     *
     * @return Collection<int, FinancialAccount>
     */
    public function getAccounts(Store $store): Collection
    {
        $this->ensureDefaultAccounts($store);

        return FinancialAccount::where('store_id', $store->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Generate unique sequential transaction number.
     */
    public function generateTransactionNumber(Store $store): string
    {
        $prefix = 'TRX-' . date('Ymd') . '-';
        $latest = FinancialTransaction::where('store_id', $store->id)
            ->where('transaction_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        $seq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest->transaction_number, $matches)) {
            $seq = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Record a Deposit (ငွေသွင်း - Cash in).
     */
    public function recordDeposit(Store $store, array $data, User $user): FinancialTransaction
    {
        return DB::transaction(function () use ($store, $data, $user) {
            $account = FinancialAccount::where('store_id', $store->id)
                ->lockForUpdate()
                ->findOrFail($data['to_account_id']);

            $amount = (float) $data['amount'];
            $txnNumber = $this->generateTransactionNumber($store);

            $transaction = FinancialTransaction::create([
                'store_id' => $store->id,
                'transaction_number' => $txnNumber,
                'from_account_id' => null,
                'to_account_id' => $account->id,
                'type' => 'deposit',
                'category' => $data['category'] ?? 'capital_injection',
                'amount' => $amount,
                'fee' => 0.00,
                'transaction_date' => !empty($data['transaction_date']) ? Carbon::parse($data['transaction_date']) : now(),
                'reference_no' => $data['reference_no'] ?? null,
                'payer_or_payee' => $data['payer_or_payee'] ?? null,
                'notes' => $data['notes'] ?? null,
                'attachment_path' => $data['attachment_path'] ?? null,
                'recorded_by' => $user->id,
            ]);

            // Increment account balance
            $account->increment('current_balance', $amount);

            AuditLog::write(
                $store->id,
                'financial_deposit_recorded',
                'financial_transactions',
                $transaction->id,
                [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'amount' => $amount,
                    'new_balance' => (float) $account->fresh()->current_balance,
                ],
                $user->id
            );

            return $transaction;
        });
    }

    /**
     * Record a Withdrawal (ငွေထုတ် - Cash out).
     */
    public function recordWithdrawal(Store $store, array $data, User $user): FinancialTransaction
    {
        return DB::transaction(function () use ($store, $data, $user) {
            $account = FinancialAccount::where('store_id', $store->id)
                ->lockForUpdate()
                ->findOrFail($data['from_account_id']);

            $amount = (float) $data['amount'];
            $txnNumber = $this->generateTransactionNumber($store);

            $transaction = FinancialTransaction::create([
                'store_id' => $store->id,
                'transaction_number' => $txnNumber,
                'from_account_id' => $account->id,
                'to_account_id' => null,
                'type' => 'withdrawal',
                'category' => $data['category'] ?? 'owner_drawing',
                'amount' => $amount,
                'fee' => 0.00,
                'transaction_date' => !empty($data['transaction_date']) ? Carbon::parse($data['transaction_date']) : now(),
                'reference_no' => $data['reference_no'] ?? null,
                'payer_or_payee' => $data['payer_or_payee'] ?? null,
                'notes' => $data['notes'] ?? null,
                'attachment_path' => $data['attachment_path'] ?? null,
                'recorded_by' => $user->id,
            ]);

            // Decrement account balance
            $account->decrement('current_balance', $amount);

            AuditLog::write(
                $store->id,
                'financial_withdrawal_recorded',
                'financial_transactions',
                $transaction->id,
                [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'amount' => $amount,
                    'new_balance' => (float) $account->fresh()->current_balance,
                ],
                $user->id
            );

            return $transaction;
        });
    }

    /**
     * Record an Account-to-Account Transfer (စာရင်းအချင်းချင်း ငွေလွှဲ).
     */
    public function recordTransfer(Store $store, array $data, User $user): FinancialTransaction
    {
        return DB::transaction(function () use ($store, $data, $user) {
            $fromAccount = FinancialAccount::where('store_id', $store->id)
                ->lockForUpdate()
                ->findOrFail($data['from_account_id']);

            $toAccount = FinancialAccount::where('store_id', $store->id)
                ->lockForUpdate()
                ->findOrFail($data['to_account_id']);

            if ($fromAccount->id === $toAccount->id) {
                throw new \InvalidArgumentException('Source and destination accounts must be different.');
            }

            $amount = (float) $data['amount'];
            $fee = isset($data['fee']) ? (float) $data['fee'] : 0.00;
            $txnNumber = $this->generateTransactionNumber($store);

            $transaction = FinancialTransaction::create([
                'store_id' => $store->id,
                'transaction_number' => $txnNumber,
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'type' => 'transfer',
                'category' => $data['category'] ?? 'internal_transfer',
                'amount' => $amount,
                'fee' => $fee,
                'transaction_date' => !empty($data['transaction_date']) ? Carbon::parse($data['transaction_date']) : now(),
                'reference_no' => $data['reference_no'] ?? null,
                'payer_or_payee' => $data['payer_or_payee'] ?? null,
                'notes' => $data['notes'] ?? null,
                'attachment_path' => $data['attachment_path'] ?? null,
                'recorded_by' => $user->id,
            ]);

            // Deduct from source (amount + fee)
            $fromAccount->decrement('current_balance', $amount + $fee);

            // Add to destination (amount)
            $toAccount->increment('current_balance', $amount);

            AuditLog::write(
                $store->id,
                'financial_transfer_recorded',
                'financial_transactions',
                $transaction->id,
                [
                    'from_account_id' => $fromAccount->id,
                    'to_account_id' => $toAccount->id,
                    'amount' => $amount,
                    'fee' => $fee,
                ],
                $user->id
            );

            return $transaction;
        });
    }

    /**
     * Create a new Financial Account.
     */
    public function createAccount(Store $store, array $data, ?User $user = null): FinancialAccount
    {
        $code = !empty($data['code']) ? $data['code'] : \Illuminate\Support\Str::slug($data['name'], '_');
        // Ensure code uniqueness
        $existing = FinancialAccount::where('store_id', $store->id)->where('code', $code)->exists();
        if ($existing) {
            $code .= '_' . time();
        }

        $openingBalance = isset($data['opening_balance']) ? (float) $data['opening_balance'] : 0.00;

        $account = FinancialAccount::create([
            'store_id' => $store->id,
            'name' => $data['name'],
            'code' => $code,
            'account_type' => $data['account_type'] ?? 'bank_account',
            'account_number' => $data['account_number'] ?? null,
            'account_holder' => $data['account_holder'] ?? null,
            'opening_balance' => $openingBalance,
            'current_balance' => $openingBalance,
            'currency' => 'MMK',
            'is_active' => true,
            'sort_order' => (int) ($data['sort_order'] ?? 10),
            'notes' => $data['notes'] ?? null,
        ]);

        if ($openingBalance > 0 && $user) {
            // Record opening balance transaction
            FinancialTransaction::create([
                'store_id' => $store->id,
                'transaction_number' => $this->generateTransactionNumber($store),
                'from_account_id' => null,
                'to_account_id' => $account->id,
                'type' => 'deposit',
                'category' => 'opening_balance',
                'amount' => $openingBalance,
                'fee' => 0.00,
                'transaction_date' => now(),
                'reference_no' => 'INIT-BAL',
                'payer_or_payee' => 'Opening Balance',
                'notes' => 'Account opening balance',
                'recorded_by' => $user->id,
            ]);
        }

        return $account;
    }

    /**
     * List and filter transactions with pagination.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<FinancialTransaction>
     */
    public function listTransactions(Store $store, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $this->ensureDefaultAccounts($store);

        $query = FinancialTransaction::where('store_id', $store->id)
            ->with(['fromAccount', 'toAccount', 'recorder']);

        if (!empty($filters['account_id'])) {
            $accId = (int) $filters['account_id'];
            $query->where(function (Builder $q) use ($accId) {
                $q->where('from_account_id', $accId)
                    ->orWhere('to_account_id', $accId);
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('transaction_date', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (!empty($filters['date_to'])) {
            $query->where('transaction_date', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('payer_or_payee', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return $query->latest('transaction_date')->latest('id')->paginate($perPage);
    }

    /**
     * Stream CSV export of financial transactions.
     */
    public function exportCsv(Store $store, array $filters = []): StreamedResponse
    {
        $query = FinancialTransaction::where('store_id', $store->id)
            ->with(['fromAccount', 'toAccount', 'recorder']);

        if (!empty($filters['account_id'])) {
            $accId = (int) $filters['account_id'];
            $query->where(function (Builder $q) use ($accId) {
                $q->where('from_account_id', $accId)
                    ->orWhere('to_account_id', $accId);
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('transaction_date', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (!empty($filters['date_to'])) {
            $query->where('transaction_date', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('payer_or_payee', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $transactions = $query->latest('transaction_date')->latest('id')->get();
        $filename = 'financial-transactions-' . $store->slug . '-' . date('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($file, [
                'Transaction #',
                'Date & Time',
                'Type',
                'From Account',
                'To Account',
                'Category / Purpose',
                'Amount (MMK)',
                'Fee (MMK)',
                'Reference / Slip No',
                'Payer / Payee',
                'Recorded By',
                'Notes',
            ]);

            foreach ($transactions as $t) {
                fputcsv($file, [
                    $t->transaction_number,
                    $t->transaction_date->format('Y-m-d H:i:s'),
                    strtoupper($t->type),
                    $t->fromAccount?->name ?? '-',
                    $t->toAccount?->name ?? '-',
                    $t->category ?? '-',
                    number_format((float) $t->amount, 2, '.', ''),
                    number_format((float) $t->fee, 2, '.', ''),
                    $t->reference_no ?? '-',
                    $t->payer_or_payee ?? '-',
                    $t->recorder?->name ?? '-',
                    $t->notes ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
