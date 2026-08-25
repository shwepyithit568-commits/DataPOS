<?php

namespace App\POS\Services;

use App\Models\EloadAccount;
use App\Models\EloadTransaction;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloadService
{
    /**
     * Get summary KPI stats for E-Load module.
     */
    public function getSummaryStats(Store $store, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();
        $monthStart = Carbon::now()->startOfMonth();

        // Today's stats
        $todayStats = EloadTransaction::where('store_id', $store->id)
            ->where('status', 'completed')
            ->whereBetween('occurred_at', [$todayStart, $todayEnd])
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_volume, COALESCE(SUM(profit), 0) as total_profit')
            ->first();

        // Month-to-date stats
        $monthStats = EloadTransaction::where('store_id', $store->id)
            ->where('status', 'completed')
            ->where('occurred_at', '>=', $monthStart)
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_volume, COALESCE(SUM(profit), 0) as total_profit')
            ->first();

        // Operator float balances
        $accounts = EloadAccount::where('store_id', $store->id)->where('is_active', true)->get();
        $totalFloatBalance = (float) $accounts->sum('balance');

        $operatorBalances = [
            'mpt'     => (float) $accounts->where('operator', 'mpt')->sum('balance'),
            'atom'    => (float) $accounts->where('operator', 'atom')->sum('balance'),
            'ooredoo' => (float) $accounts->where('operator', 'ooredoo')->sum('balance'),
            'mytel'   => (float) $accounts->where('operator', 'mytel')->sum('balance'),
        ];

        return [
            'today_volume'        => (float) ($todayStats->total_volume ?? 0),
            'today_count'         => (int) ($todayStats->total_count ?? 0),
            'today_profit'        => (float) ($todayStats->total_profit ?? 0),
            'month_volume'        => (float) ($monthStats->total_volume ?? 0),
            'month_count'         => (int) ($monthStats->total_count ?? 0),
            'month_profit'        => (float) ($monthStats->total_profit ?? 0),
            'total_float_balance' => $totalFloatBalance,
            'operator_balances'   => $operatorBalances,
        ];
    }

    /**
     * Get paginated transactions list with search and filter support.
     */
    public function getTransactions(Store $store, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = EloadTransaction::where('store_id', $store->id)
            ->with(['account', 'cashier']);

        // Search: phone number, customer name, ref_no, package_name
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('ref_no', 'like', "%{$search}%")
                  ->orWhere('package_name', 'like', "%{$search}%");
            });
        }

        // Filter: operator
        if (!empty($filters['operator'])) {
            $query->where('operator', strtolower($filters['operator']));
        }

        // Filter: type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter: payment_method
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // Filter: status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter: date range
        if (!empty($filters['occurred_at_from'])) {
            $query->whereDate('occurred_at', '>=', $filters['occurred_at_from']);
        }
        if (!empty($filters['occurred_at_to'])) {
            $query->whereDate('occurred_at', '<=', $filters['occurred_at_to']);
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest'          => $query->orderBy('occurred_at', 'asc')->orderBy('id', 'asc'),
            'amount_desc'     => $query->orderBy('amount', 'desc'),
            'amount_asc'      => $query->orderBy('amount', 'asc'),
            'profit_desc'     => $query->orderBy('profit', 'desc'),
            default           => $query->orderBy('occurred_at', 'desc')->orderBy('id', 'desc'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get active operator accounts.
     */
    public function getAccounts(Store $store)
    {
        return EloadAccount::where('store_id', $store->id)
            ->orderBy('operator')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new E-Load top-up or bill payment transaction.
     */
    public function createTransaction(Store $store, array $data, ?User $user = null): EloadTransaction
    {
        return DB::transaction(function () use ($store, $data, $user) {
            $amount = (float) $data['amount'];
            $operator = strtolower($data['operator']);

            // Find matching operator account
            $account = null;
            if (!empty($data['eload_account_id'])) {
                $account = EloadAccount::where('store_id', $store->id)->find($data['eload_account_id']);
            }
            if (!$account) {
                $account = EloadAccount::where('store_id', $store->id)
                    ->where('operator', $operator)
                    ->where('is_active', true)
                    ->first();
            }

            // Calculate cost and profit from request discount percent or account discount percent
            $discountPercent = (isset($data['discount_percent']) && $data['discount_percent'] !== null && $data['discount_percent'] !== '')
                ? (float) $data['discount_percent']
                : ($account ? (float) $account->discount_percent : 0.0);
            if ($discountPercent > 0) {
                $cost = round($amount * (1 - ($discountPercent / 100)), 2);
                $profit = round($amount - $cost, 2);
            } else {
                $cost = isset($data['cost']) ? (float) $data['cost'] : $amount;
                $profit = round($amount - $cost, 2);
            }

            $refNo = $data['ref_no'] ?? ('EL-' . strtoupper(Str::random(8)));
            $occurredAt = !empty($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now();
            $status = $data['status'] ?? 'completed';

            $transaction = EloadTransaction::create([
                'store_id'         => $store->id,
                'eload_account_id' => $account?->id,
                'user_id'          => $user?->id ?? auth()->id(),
                'operator'         => $operator,
                'phone_number'     => preg_replace('/\s+/', '', (string) $data['phone_number']),
                'customer_name'    => $data['customer_name'] ?? null,
                'type'             => $data['type'] ?? 'topup',
                'package_name'     => $data['package_name'] ?? null,
                'amount'           => $amount,
                'cost'             => $cost,
                'profit'           => $profit,
                'payment_method'   => $data['payment_method'] ?? 'cash',
                'status'           => $status,
                'ref_no'           => $refNo,
                'notes'            => $data['notes'] ?? null,
                'occurred_at'      => $occurredAt,
            ]);

            // Deduct operator balance if completed
            if ($status === 'completed' && $account) {
                $account->decrement('balance', $amount);
            }

            return $transaction;
        });
    }

    /**
     * Refill / Add float balance to an operator account.
     */
    public function refillAccount(EloadAccount $account, float $amount, ?string $notes = null): void
    {
        DB::transaction(function () use ($account, $amount) {
            $account->increment('balance', $amount);
        });
    }

    /**
     * Update transaction status (e.g. refund/void or complete).
     */
    public function updateStatus(EloadTransaction $transaction, string $newStatus): void
    {
        DB::transaction(function () use ($transaction, $newStatus) {
            $oldStatus = $transaction->status;
            if ($oldStatus === $newStatus) {
                return;
            }

            $transaction->update(['status' => $newStatus]);

            // Balance adjustment on refund / cancellation
            if ($transaction->account) {
                if ($oldStatus === 'completed' && in_array($newStatus, ['refunded', 'failed'], true)) {
                    // Refund float back to account
                    $transaction->account->increment('balance', (float) $transaction->amount);
                } elseif (in_array($oldStatus, ['refunded', 'failed'], true) && $newStatus === 'completed') {
                    // Deduct float
                    $transaction->account->decrement('balance', (float) $transaction->amount);
                }
            }
        });
    }

    /**
     * Create or update an operator account.
     */
    public function saveAccount(Store $store, array $data, ?int $accountId = null): EloadAccount
    {
        $account = $accountId
            ? EloadAccount::where('store_id', $store->id)->findOrFail($accountId)
            : new EloadAccount(['store_id' => $store->id]);

        $account->fill([
            'operator'         => strtolower($data['operator']),
            'name'             => $data['name'],
            'phone_number'     => $data['phone_number'] ?? null,
            'balance'          => isset($data['balance']) ? (float) $data['balance'] : $account->balance ?? 0,
            'discount_percent' => isset($data['discount_percent']) ? (float) $data['discount_percent'] : $account->discount_percent ?? 0,
            'is_active'        => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ]);
        $account->save();

        return $account;
    }

    /**
     * Delete an operator account.
     */
    public function deleteAccount(EloadAccount $account): bool
    {
        return (bool) $account->delete();
    }
}
