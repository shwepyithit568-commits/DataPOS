<?php

namespace App\POS\Services;

use App\POS\Models\Expense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for "which cash actually left which drawer because of
 * an expense".
 *
 * Before this class existed the shift close and the branch daily close each
 * carried their own copy of the deduction rules, and the copies had drifted:
 * the shift close required an explicit `cashier_shift_id`, while the daily
 * close additionally deducted shift-less rows by expense date. The same expense
 * could therefore be attributed to one scope and not the other, and a legacy row
 * with no provable linkage was silently treated as "safe" — as if it had never
 * touched the drawer — purely because its `payment_source` was NULL.
 *
 * Rules encoded here (docs/DataPOS_Closing_Cash_Out_Audit_Fix_MM.md §4.1–4.3):
 *  - Only a PAID + CASH expense whose `payment_source` is explicitly 'drawer'
 *    AND which carries a `cashier_shift_id` inside the scope is deducted.
 *  - A NULL/empty `payment_source` is NOT proof of "safe": it is UNRESOLVED,
 *    reported separately and never merged with confirmed non-drawer expenses.
 *  - A 'drawer' claim with no shift attached is also UNRESOLVED: never guessed
 *    into a drawer, never silently dropped.
 *
 * Because both close paths build their deductions from `drawerDeductionQuery()`,
 * "sum of per-shift deductions" is guaranteed to equal "daily deduction for the
 * same shift set".
 */
class ExpenseCashAttribution
{
    /** Deducted from the drawer it is linked to. */
    public const STATE_DEDUCTED = 'deducted';

    /** Recorded but not yet paid — no cash has moved. */
    public const STATE_UNPAID = 'unpaid';

    /** Cancelled/void — never moves cash. */
    public const STATE_VOID = 'void';

    /** Confirmed non-drawer source (safe / petty cash / bank / other). */
    public const STATE_NON_DRAWER = 'non_drawer';

    /** Paid cash, but the drawer cannot be proven. Reported, never guessed. */
    public const STATE_UNRESOLVED = 'unresolved';

    /** Non-cash payment method — never affects the POS drawer. */
    public const STATE_NON_CASH = 'non_cash';

    /** Sources that are explicitly NOT the POS cash drawer. */
    public const CONFIRMED_NON_DRAWER_SOURCES = [
        Expense::SOURCE_SAFE,
        Expense::SOURCE_PETTY_CASH,
        Expense::SOURCE_BANK,
        Expense::SOURCE_OTHER,
    ];

    /**
     * Paid cash expenses genuinely paid out of the given shifts' drawers.
     *
     * THE authoritative drawer-deduction query — both `closeShift()` and
     * `expectedTotals()` must build their expense deduction from this.
     *
     * @param  list<int|string>  $shiftIds
     */
    public static function drawerDeductionQuery(int|string $storeId, array $shiftIds)
    {
        return DB::table('expenses')
            ->where('store_id', $storeId)
            ->where('status', 'paid')
            ->where('payment_method', 'cash')
            ->where('payment_source', Expense::SOURCE_DRAWER)
            ->whereIn('cashier_shift_id', $shiftIds);
    }

    /**
     * Restrict an expense query to one scope: rows linked to a shift in scope,
     * plus shift-less rows whose expense_date falls inside the window.
     */
    public static function scopeFilter($query, array $shiftIds, Carbon $start, Carbon $endExclusive)
    {
        return $query->where(function ($q) use ($shiftIds, $start, $endExclusive) {
            if (! empty($shiftIds)) {
                $q->whereIn('cashier_shift_id', $shiftIds);
            }

            $q->orWhere(function ($sub) use ($start, $endExclusive) {
                $sub->whereNull('cashier_shift_id')
                    ->whereDate('expense_date', '>=', $start->toDateString())
                    ->whereDate('expense_date', '<', $endExclusive->toDateString());
            });
        });
    }

    /**
     * Narrow a paid + cash expense query to the rows whose drawer is unproven.
     */
    public static function unprovenDrawerFilter($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('payment_source')
                ->orWhere('payment_source', '')
                ->orWhere(function ($inner) {
                    $inner->where('payment_source', Expense::SOURCE_DRAWER)
                        ->whereNull('cashier_shift_id');
                });
        });
    }

    /**
     * Narrow a paid + cash expense query to confirmed non-drawer sources.
     */
    public static function confirmedNonDrawerFilter($query)
    {
        return $query->whereIn('payment_source', self::CONFIRMED_NON_DRAWER_SOURCES);
    }

    /**
     * Classify one expense against a scope (a shift set, or a business date's
     * shift set). The UI renders whatever this returns, so a row's badge can
     * never claim a deduction the ledger math did not make.
     *
     * @param  list<int|string>  $scopeShiftIds
     */
    public static function stateFor(Expense $expense, array $scopeShiftIds): string
    {
        $status = strtolower((string) ($expense->status ?? 'paid'));

        if (in_array($status, ['void', 'cancelled', 'canceled'], true)) {
            return self::STATE_VOID;
        }

        if ($status !== 'paid') {
            return self::STATE_UNPAID;
        }

        if (strtolower((string) $expense->payment_method) !== 'cash') {
            return self::STATE_NON_CASH;
        }

        $source = (string) ($expense->payment_source ?? '');

        if ($source === Expense::SOURCE_DRAWER) {
            $shiftId = $expense->cashier_shift_id;

            return ($shiftId !== null && in_array((string) $shiftId, array_map('strval', $scopeShiftIds), true))
                ? self::STATE_DEDUCTED
                : self::STATE_UNRESOLVED;
        }

        if ($source === '') {
            return self::STATE_UNRESOLVED;
        }

        return self::STATE_NON_DRAWER;
    }

    /**
     * Whether a state means the drawer was reduced by this expense. Used by the
     * closing detail so the marked rows always add up to the drawer subtotal.
     */
    public static function isDeducted(string $state): bool
    {
        return $state === self::STATE_DEDUCTED;
    }

    /**
     * Localised label for a state.
     */
    public static function stateLabel(string $state): string
    {
        return match ($state) {
            self::STATE_DEDUCTED => __('messages.expense_state_deducted'),
            self::STATE_UNPAID => __('messages.expense_state_unpaid'),
            self::STATE_VOID => __('messages.expense_state_void'),
            self::STATE_NON_DRAWER => __('messages.expense_state_non_drawer'),
            self::STATE_UNRESOLVED => __('messages.expense_state_unresolved'),
            default => __('messages.expense_state_non_cash'),
        };
    }

    /**
     * A closing/summary is only "reconciled" once nothing is left unresolved.
     * Stops an approved period being reported as balanced/final while a real
     * cash-out is still waiting for a drawer to be named.
     */
    public static function isReconciled(string $unresolvedAmount, int $unresolvedCount): bool
    {
        return $unresolvedCount === 0 && bccomp(bcadd($unresolvedAmount, '0', 2), '0', 2) === 0;
    }
}
