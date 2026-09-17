<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CashierShift;
use App\POS\Models\DailyClosing;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Branch daily closing (SoT §18 — target-design §2.10, the middle of the three
 * closing levels: cashier shift closing → branch daily closing → finance period).
 *
 * - Expected totals are DERIVED at closing time and stored immutably:
 *   cash from the day's shifts (opening + cash_sales − cash_refunds + cash_in
 *   − cash_out — exactly the shift math), e-methods from posted sales, credit
 *   as receivable info (sales − refunds).
 * - Counted totals are entered by the cashier/manager; difference = counted −
 *   expected, per method and total.
 * - Approval: pending → approved by the store manager. Blocked while pending
 *   offline transactions exist (SoT §18); a non-zero difference requires an
 *   explanation.
 */
class DailyClosingService
{
    public function __construct(
        protected StoreBusinessDateService $businessDate
    ) {}

    /* ------------------------------------------------------------------ */
    /*  Expected totals (derived from the ledgers)                         */
    /* ------------------------------------------------------------------ */

    /**
     * Expected totals per payment method for a business date, plus the
     * combined opening amount and comprehensive sales/drawer summary.
     *
     * @return array{opening_amount:string, expected:array<string,string>, date:string, summary:array<string,string>}
     */
    public function expectedTotals(Store $store, Carbon $date): array
    {
        [$start, $endExclusive] = $this->businessDate->queryRange($store, $date);

        $opening = '0.00';
        $cashSales = '0.00';
        $cashRefunds = '0.00';
        $cashIn = '0.00';
        $cashOut = '0.00';
        $drawerCash = '0.00';

        $shifts = CashierShift::query()
            ->where('store_id', $store->id)
            ->where('opened_at', '>=', $start)
            ->where('opened_at', '<', $endExclusive)
            ->get();

        $shiftIds = $shifts->pluck('id')->all();

        $shifts->each(function (CashierShift $s) use (&$opening, &$cashSales, &$cashRefunds, &$cashIn) {
            $opening = bcadd($opening, (string) $s->opening_cash, 2);
            $cashSales = bcadd($cashSales, (string) $s->cash_sales, 2);
            $cashRefunds = bcadd($cashRefunds, (string) $s->cash_refunds, 2);
            $cashIn = bcadd($cashIn, (string) $s->cash_in, 2);
        });

        // 1. Drawer-paid cash expenses (authoritative source: expenses with payment_method='cash', payment_source='drawer', status='paid')
        $drawerExpensesQuery = DB::table('expenses')
            ->where('store_id', $store->id)
            ->where('payment_method', 'cash')
            ->where('payment_source', \App\POS\Models\Expense::SOURCE_DRAWER)
            ->where('status', 'paid');

        if (!empty($shiftIds)) {
            $drawerExpensesQuery->where(function ($q) use ($shiftIds, $start, $endExclusive) {
                $q->whereIn('cashier_shift_id', $shiftIds)
                  ->orWhere(function ($sub) use ($start, $endExclusive) {
                      $sub->whereNull('cashier_shift_id')
                          ->whereDate('expense_date', '>=', $start->toDateString())
                          ->whereDate('expense_date', '<', $endExclusive->toDateString());
                  });
            });
        } else {
            $drawerExpensesQuery->whereDate('expense_date', '>=', $start->toDateString())
                                ->whereDate('expense_date', '<', $endExclusive->toDateString());
        }

        $drawerExpenses = exact_sum($drawerExpensesQuery, 'amount');

        // 2. Other Cash Out: Non-expense cash events (safe drops, owner drawings, etc.)
        // Reconciled shift-by-shift to ensure mixed legacy/current representations do NOT lose outflow (Fix B / F08).
        $cashOut = '0.00';
        if (!empty($shifts)) {
            $linkedExpenseIds = (clone $drawerExpensesQuery)->pluck('id')->all();

            foreach ($shifts as $s) {
                $shiftHasEvents = DB::table('cash_events')->where('cashier_shift_id', $s->id)->exists();
                if ($shiftHasEvents) {
                    $shiftEventOut = exact_sum(
                        DB::table('cash_events')
                            ->where('cashier_shift_id', $s->id)
                            ->where('type', 'cash_out')
                            ->where(function ($q) use ($linkedExpenseIds) {
                                if (! empty($linkedExpenseIds)) {
                                    $q->whereNull('expense_id')
                                      ->orWhereNotIn('expense_id', $linkedExpenseIds);
                                }
                            }),
                        'amount'
                    );
                    $cashOut = bcadd($cashOut, $shiftEventOut, 2);
                } else {
                    // Legacy shift without granular events: preserve the shift's recorded cash_out
                    $cashOut = bcadd($cashOut, (string) ($s->cash_out ?? '0.00'), 2);
                }
            }
        }

        // 3. Other non-drawer cash expenses (e.g. paid from Safe, Petty Cash, Bank, or legacy unresolved)
        $otherCashExpenses = exact_sum(
            DB::table('expenses')
                ->where('store_id', $store->id)
                ->where('payment_method', 'cash')
                ->where(function ($q) {
                    $q->where('payment_source', '!=', \App\POS\Models\Expense::SOURCE_DRAWER)
                      ->orWhereNull('payment_source');
                })
                ->where('status', 'paid')
                ->whereDate('expense_date', '>=', $start->toDateString())
                ->whereDate('expense_date', '<', $endExclusive->toDateString()),
            'amount'
        );

        // Authoritative Drawer Math (Section 4.3):
        // Expected Cash = Opening + Cash Sales + Other Cash In - Cash Refunds - Drawer Expenses - Other Cash Out
        $drawerCash = bcsub(
            bcsub(
                bcadd(bcadd($opening, $cashSales, 2), $cashIn, 2),
                $cashRefunds,
                2
            ),
            bcadd($drawerExpenses, $cashOut, 2),
            2
        );

        $expected = ['cash' => $drawerCash];

        // Electronic payment methods from posted sales and refunds in the date range.
        // exact_sum() rather than a SQL sum wrapped in a float cast: MySQL sums
        // DECIMAL exactly but SQLite sums in REAL and drifts over a large range,
        // and these figures are what the drawer is reconciled against.
        foreach (['kpay', 'wavepay', 'cb_pay', 'mmqr'] as $method) {
            $sold = exact_sum(
                DB::table('pos_payments')
                    ->join('pos_sales', 'pos_sales.id', '=', 'pos_payments.pos_sale_id')
                    ->where('pos_sales.store_id', $store->id)
                    ->where('pos_payments.method', $method)
                    ->whereIn('pos_sales.status', ['posted', 'partially_refunded', 'refunded'])
                    ->where('pos_sales.posted_at', '>=', $start)
                    ->where('pos_sales.posted_at', '<', $endExclusive),
                'pos_payments.amount'
            );

            $refunded = exact_sum(
                DB::table('pos_return_payments')
                    ->join('pos_returns', 'pos_returns.id', '=', 'pos_return_payments.pos_return_id')
                    ->where('pos_returns.store_id', $store->id)
                    ->where('pos_return_payments.method', $method)
                    ->where('pos_returns.status', 'posted')
                    ->where('pos_returns.posted_at', '>=', $start)
                    ->where('pos_returns.posted_at', '<', $endExclusive),
                'pos_return_payments.amount'
            );

            $expected[$method] = bcsub($sold, $refunded, 2);
        }

        // Credit sales from posted sales
        $creditSold = exact_sum(
            DB::table('pos_payments')
                ->join('pos_sales', 'pos_sales.id', '=', 'pos_payments.pos_sale_id')
                ->where('pos_sales.store_id', $store->id)
                ->where('pos_payments.method', 'credit')
                ->whereIn('pos_sales.status', ['posted', 'partially_refunded', 'refunded'])
                ->where('pos_sales.posted_at', '>=', $start)
                ->where('pos_sales.posted_at', '<', $endExclusive),
            'pos_payments.amount'
        );

        // Credit refunds reduce the receivable created that day.
        $creditRefunded = exact_sum(
            DB::table('pos_return_payments')
                ->join('pos_returns', 'pos_returns.id', '=', 'pos_return_payments.pos_return_id')
                ->where('pos_returns.store_id', $store->id)
                ->where('pos_return_payments.method', 'credit')
                ->where('pos_returns.status', 'posted')
                ->where('pos_returns.posted_at', '>=', $start)
                ->where('pos_returns.posted_at', '<', $endExclusive),
            'pos_return_payments.amount'
        );

        $expected['credit'] = bcsub($creditSold, $creditRefunded, 2);

        // Calculate sales metrics for reports
        $postedSales = fn () => DB::table('pos_sales')
            ->where('store_id', $store->id)
            ->whereIn('status', ['posted', 'partially_refunded', 'refunded'])
            ->where('posted_at', '>=', $start)
            ->where('posted_at', '<', $endExclusive);

        $postedReturns = fn () => DB::table('pos_returns')
            ->where('store_id', $store->id)
            ->where('status', 'posted')
            ->where('posted_at', '>=', $start)
            ->where('posted_at', '<', $endExclusive);

        $grossSales = exact_sum($postedSales(), 'subtotal');
        $discounts = exact_sum($postedSales(), 'discount');
        $tax = exact_sum($postedSales(), 'tax');
        $returnsTotal = exact_sum($postedReturns(), 'total');
        $salesTotal = exact_sum($postedSales(), 'total');
        $netSales = bcsub($salesTotal, $returnsTotal, 2);

        $summary = [
            'gross_sales' => $grossSales,
            'discounts' => $discounts,
            'tax' => $tax,
            'returns' => $returnsTotal,
            'net_sales' => $netSales,
            'opening_cash' => bcadd($opening, '0', 2),
            'cash_sales' => bcadd($cashSales, '0', 2),
            'cash_refunds' => bcadd($cashRefunds, '0', 2),
            'cash_in' => bcadd($cashIn, '0', 2),
            'cash_out' => bcadd($cashOut, '0', 2),
            // Cash expenses paid from the drawer (single authoritative source).
            'drawer_expenses' => bcadd($drawerExpenses, '0', 2),
            'cash_expenses' => bcadd($drawerExpenses, '0', 2),
            // Store cash expenses paid from Safe, Petty Cash, or other non-drawer accounts.
            'other_cash_expenses' => bcadd($otherCashExpenses, '0', 2),
            'expected_cash' => $drawerCash,
        ];

        // Retrieve all expenses for the business date with category/recorder/shift for drill-down breakdown.
        $expensesQuery = \App\POS\Models\Expense::query()
            ->where('store_id', $store->id);

        if (!empty($shiftIds)) {
            $expensesQuery->where(function ($q) use ($shiftIds, $start, $endExclusive) {
                $q->whereIn('cashier_shift_id', $shiftIds)
                  ->orWhere(function ($sub) use ($start, $endExclusive) {
                      $sub->whereNull('cashier_shift_id')
                          ->whereDate('expense_date', '>=', $start->toDateString())
                          ->whereDate('expense_date', '<', $endExclusive->toDateString());
                  });
            });
        } else {
            $expensesQuery->whereDate('expense_date', '>=', $start->toDateString())
                          ->whereDate('expense_date', '<', $endExclusive->toDateString());
        }

        $dayExpenses = $expensesQuery
            ->with(['category', 'recorder', 'shift'])
            ->orderBy('id', 'desc')
            ->get();

        return [
            'opening_amount' => bcadd($opening, '0', 2),
            'expected' => $expected,
            'date' => $date->toDateString(),
            'summary' => $summary,
            'expenses' => $dayExpenses,
        ];
    }

    /**
     * Generate an X-Report (Reading Only / Interim Midday View).
     *
     * STRICT RULES:
     * - Read-only: Does NOT mutate the database.
     * - Does NOT create a DailyClosing record.
     * - Does NOT lock the business date.
     * - Does NOT close any cashier shifts.
     * - Can be executed repeatedly without side effects.
     * - Records an audit log for viewing/reading only.
     */
    public function xReport(Store $store, Carbon $date, User $actor): array
    {
        if ($date->isFuture()) {
            throw new InventoryException('Cannot generate X-Report for a future business date.');
        }

        [$start, $endExclusive] = $this->businessDate->queryRange($store, $date);

        $totals = $this->expectedTotals($store, $date);

        // Shifts metadata
        $shiftsCount = CashierShift::query()
            ->where('store_id', $store->id)
            ->where('opened_at', '>=', $start)
            ->where('opened_at', '<', $endExclusive)
            ->count();

        $openShiftsCount = CashierShift::query()
            ->where('store_id', $store->id)
            ->where('status', 'open')
            ->where('opened_at', '>=', $start)
            ->where('opened_at', '<', $endExclusive)
            ->count();

        // Transaction counts
        $salesCount = DB::table('pos_sales')
            ->where('store_id', $store->id)
            ->whereIn('status', ['posted', 'partially_refunded', 'refunded'])
            ->where('posted_at', '>=', $start)
            ->where('posted_at', '<', $endExclusive)
            ->count();

        $returnsCount = DB::table('pos_returns')
            ->where('store_id', $store->id)
            ->where('status', 'posted')
            ->where('posted_at', '>=', $start)
            ->where('posted_at', '<', $endExclusive)
            ->count();

        // Log X-Report reading event
        AuditLog::write(
            storeId: $store->id,
            action: 'x_report_viewed',
            entityType: 'x_report',
            entityId: 0,
            metadata: [
                'business_date' => $date->toDateString(),
                'shifts_count' => $shiftsCount,
                'sales_count' => $salesCount,
            ],
            actorId: $actor->id,
        );

        return [
            'type' => 'x_report',
            'marker' => 'X-REPORT — READING ONLY / စာရင်းကြည့်ရှုရန်သာ',
            'business_date' => $date->toDateString(),
            'generated_at' => now(),
            'actor' => $actor,
            'shifts_count' => $shiftsCount,
            'open_shifts_count' => $openShiftsCount,
            'sales_count' => $salesCount,
            'returns_count' => $returnsCount,
            'totals' => $totals,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Create / approve                                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Create a pending closing for a business date. Fails if one already
     * exists (unique store + business_date).
     *
     * @param  array<string, string|float|int>  $counted  method => counted amount
     */
    public function create(Store $store, Carbon $date, array $counted, ?string $explanation, User $actor): DailyClosing
    {
        if ($date->isFuture()) {
            throw new InventoryException('Cannot close a future business date.');
        }

        $dateString = $date->toDateString();

        $existing = DailyClosing::query()
            ->where('store_id', $store->id)
            ->whereDate('business_date', $dateString)
            ->first();

        if ($existing) {
            throw new InventoryException("A daily closing already exists for {$dateString} (status: {$existing->approval_status}).");
        }

        $totals = $this->expectedTotals($store, $date);

        // Reject any unknown payment methods in counted array
        $unknownKeys = array_diff(array_keys($counted), DailyClosing::countedMethods());
        if (!empty($unknownKeys)) {
            throw new InventoryException('Unknown counted payment method: ' . implode(', ', $unknownKeys));
        }

        // Counted: only drawer/collection methods; missing → 0, normalized to 2dp.
        $normalized = [];
        foreach (DailyClosing::countedMethods() as $method) {
            $amount = (string) ($counted[$method] ?? '0');

            // A value bcmath cannot parse would throw a ValueError; treat it as
            // invalid input rather than a server error.
            if (preg_match('/^-?\d+(\.\d+)?$/', trim($amount)) !== 1) {
                throw new InventoryException("Counted amount for '{$method}' must be a number.");
            }

            $amount = bcadd($amount, '0', 2);

            if (bccomp($amount, '0', 2) < 0) {
                throw new InventoryException("Counted amount for '{$method}' cannot be negative.");
            }

            $normalized[$method] = $amount;
        }

        $differences = [];
        $totalDifference = '0';
        foreach (DailyClosing::countedMethods() as $method) {
            $diff = bcsub($normalized[$method], $totals['expected'][$method], 2);
            $differences[$method] = $diff;
            $totalDifference = bcadd($totalDifference, $diff, 2);
        }

        if (bccomp($totalDifference, '0', 2) !== 0 && trim((string) $explanation) === '') {
            throw new InventoryException('An explanation is required when the counted totals differ from expected.');
        }

        return DB::transaction(function () use ($store, $date, $dateString, $totals, $normalized, $differences, $totalDifference, $explanation, $actor) {
            $closing = DailyClosing::create([
                'store_id' => $store->id,
                'branch_id' => app(StoreLocationService::class)->defaultBranch($store)->id,
                'business_date' => $dateString,
                'closing_user_id' => $actor->id,
                'opening_amount' => $totals['opening_amount'],
                'expected_totals' => $totals['expected'],
                'counted_totals' => $normalized,
                'differences' => $differences,
                'summary_snapshot' => [
                    'version' => 1,
                    'metrics' => $totals['summary'],
                ],
                'total_difference' => $totalDifference,
                'explanation' => trim((string) $explanation) !== '' ? $explanation : null,
                'pending_offline_transaction_count' => 0, // MVP — offline queue (Phase 3) not wired yet
                'approval_status' => 'pending',
                'closed_at' => now(),
                'created_by' => $actor->id,
            ]);

            AuditLog::write(
                storeId: $store->id,
                action: 'daily_closing_created',
                entityType: 'daily_closing',
                entityId: $closing->id,
                metadata: ['business_date' => $dateString, 'total_difference' => $totalDifference],
                actorId: $actor->id,
            );

            return $closing;
        });
    }

    /**
     * Approve a pending closing (store manager). SoT §18: blocked while
     * unresolved offline transactions exist; a non-zero difference requires
     * an explanation (recorded at creation or here).
     */
    public function approve(Store $store, DailyClosing $closing, User $actor): DailyClosing
    {
        if ((int) $closing->store_id !== (int) $store->id) {
            throw new InventoryException('This closing does not belong to the store.');
        }

        return DB::transaction(function () use ($store, $closing, $actor) {
            $locked = DailyClosing::query()
                ->where('id', $closing->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || ! $locked->isPending()) {
                throw new InventoryException("Closing for {$closing->business_date} is already " . ($locked ? $locked->approval_status : 'approved') . '.');
            }
            if ($locked->pending_offline_transaction_count > 0) {
                throw new InventoryException(
                    'Unresolved pending offline transactions exist — final closing cannot be approved (SoT §18).'
                );
            }
            if (bccomp((string) $locked->total_difference, '0', 2) !== 0 && trim((string) $locked->explanation) === '') {
                throw new InventoryException('An explanation is required before approving a closing with a difference.');
            }

            $locked->update([
                'approval_status' => 'approved',
                'approver_id' => $actor->id,
                'approved_at' => now(),
            ]);

            AuditLog::write(
                storeId: $store->id,
                action: 'daily_closing_approved',
                entityType: 'daily_closing',
                entityId: $locked->id,
                metadata: ['business_date' => (string) $locked->business_date, 'total_difference' => (string) $locked->total_difference],
                actorId: $actor->id,
            );

            return $locked->refresh();
        });
    }

    /**
     * The store's closing for a date (latest if recreated), or null.
     */
    public function forDate(Store $store, Carbon $date): ?DailyClosing
    {
        return DailyClosing::query()
            ->with(['closingUser', 'approver'])
            ->where('store_id', $store->id)
            ->whereDate('business_date', $date->toDateString())
            ->latest()
            ->first();
    }
}
