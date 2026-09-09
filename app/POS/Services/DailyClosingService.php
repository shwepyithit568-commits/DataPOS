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
    /* ------------------------------------------------------------------ */
    /*  Expected totals (derived from the ledgers)                         */
    /* ------------------------------------------------------------------ */

    /**
     * Expected totals per payment method for a business date, plus the
     * combined opening amount.
     *
     * @return array{opening_amount:string, expected:array<string,string>, date:string}
     */
    /**
     * Expected totals per payment method for a business date, plus the
     * combined opening amount and comprehensive sales/drawer summary.
     *
     * @return array{opening_amount:string, expected:array<string,string>, date:string, summary:array<string,string>}
     */
    public function expectedTotals(Store $store, Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $opening = '0.00';
        $cashSales = '0.00';
        $cashRefunds = '0.00';
        $cashIn = '0.00';
        $cashOut = '0.00';
        $drawerCash = '0.00';

        // Cash expectation = the day's shifts' drawer math (open or closed).
        CashierShift::query()
            ->where('store_id', $store->id)
            ->whereBetween('opened_at', [$start, $end])
            ->get(['opening_cash', 'cash_sales', 'cash_refunds', 'cash_in', 'cash_out'])
            ->each(function (CashierShift $s) use (&$opening, &$cashSales, &$cashRefunds, &$cashIn, &$cashOut, &$drawerCash) {
                $opening = bcadd($opening, (string) $s->opening_cash, 2);
                $cashSales = bcadd($cashSales, (string) $s->cash_sales, 2);
                $cashRefunds = bcadd($cashRefunds, (string) $s->cash_refunds, 2);
                $cashIn = bcadd($cashIn, (string) $s->cash_in, 2);
                $cashOut = bcadd($cashOut, (string) $s->cash_out, 2);

                $shiftNet = bcsub(
                    bcadd(bcadd((string) $s->opening_cash, (string) $s->cash_sales, 2), (string) $s->cash_in, 2),
                    bcadd((string) $s->cash_refunds, (string) $s->cash_out, 2),
                    2
                );
                $drawerCash = bcadd($drawerCash, $shiftNet, 2);
            });

        $expected = ['cash' => $drawerCash];

        // Electronic payment methods from posted sales and refunds in the date range.
        foreach (['kpay', 'wavepay', 'cb_pay', 'mmqr'] as $method) {
            $sold = (string) DB::table('pos_payments')
                ->join('pos_sales', 'pos_sales.id', '=', 'pos_payments.pos_sale_id')
                ->where('pos_sales.store_id', $store->id)
                ->where('pos_payments.method', $method)
                ->where('pos_sales.status', 'posted')
                ->whereBetween('pos_sales.posted_at', [$start, $end])
                ->sum('pos_payments.amount');

            $refunded = (string) DB::table('pos_return_payments')
                ->join('pos_returns', 'pos_returns.id', '=', 'pos_return_payments.pos_return_id')
                ->where('pos_returns.store_id', $store->id)
                ->where('pos_return_payments.method', $method)
                ->where('pos_returns.status', 'posted')
                ->whereBetween('pos_returns.posted_at', [$start, $end])
                ->sum('pos_return_payments.amount');

            $net = bcsub(
                number_format((float) $sold, 2, '.', ''),
                number_format((float) $refunded, 2, '.', ''),
                2
            );

            $expected[$method] = $net;
        }

        // Credit sales from posted sales
        $creditSold = (string) DB::table('pos_payments')
            ->join('pos_sales', 'pos_sales.id', '=', 'pos_payments.pos_sale_id')
            ->where('pos_sales.store_id', $store->id)
            ->where('pos_payments.method', 'credit')
            ->where('pos_sales.status', 'posted')
            ->whereBetween('pos_sales.posted_at', [$start, $end])
            ->sum('pos_payments.amount');

        // Credit refunds reduce the receivable created that day.
        $creditRefunded = (string) DB::table('pos_return_payments')
            ->join('pos_returns', 'pos_returns.id', '=', 'pos_return_payments.pos_return_id')
            ->where('pos_returns.store_id', $store->id)
            ->where('pos_return_payments.method', 'credit')
            ->where('pos_returns.status', 'posted')
            ->whereBetween('pos_returns.posted_at', [$start, $end])
            ->sum('pos_return_payments.amount');

        $expected['credit'] = bcsub(
            number_format((float) $creditSold, 2, '.', ''),
            number_format((float) $creditRefunded, 2, '.', ''),
            2
        );

        // Calculate sales metrics for reports
        $grossSales = (string) DB::table('pos_sales')
            ->where('store_id', $store->id)
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$start, $end])
            ->sum('subtotal');

        $discounts = (string) DB::table('pos_sales')
            ->where('store_id', $store->id)
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$start, $end])
            ->sum('discount');

        $tax = (string) DB::table('pos_sales')
            ->where('store_id', $store->id)
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$start, $end])
            ->sum('tax');

        $returnsTotal = (string) DB::table('pos_returns')
            ->where('store_id', $store->id)
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$start, $end])
            ->sum('total');

        $netSales = (string) DB::table('pos_sales')
            ->where('store_id', $store->id)
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$start, $end])
            ->sum('total');

        $summary = [
            'gross_sales' => number_format((float) $grossSales, 2, '.', ''),
            'discounts' => number_format((float) $discounts, 2, '.', ''),
            'tax' => number_format((float) $tax, 2, '.', ''),
            'returns' => number_format((float) $returnsTotal, 2, '.', ''),
            'net_sales' => number_format((float) $netSales, 2, '.', ''),
            'opening_cash' => number_format((float) $opening, 2, '.', ''),
            'cash_sales' => number_format((float) $cashSales, 2, '.', ''),
            'cash_refunds' => number_format((float) $cashRefunds, 2, '.', ''),
            'cash_in' => number_format((float) $cashIn, 2, '.', ''),
            'cash_out' => number_format((float) $cashOut, 2, '.', ''),
            'expected_cash' => $drawerCash,
        ];

        return [
            'opening_amount' => number_format((float) $opening, 2, '.', ''),
            'expected' => $expected,
            'date' => $date->toDateString(),
            'summary' => $summary,
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

        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $totals = $this->expectedTotals($store, $date);

        // Shifts metadata
        $shiftsCount = CashierShift::query()
            ->where('store_id', $store->id)
            ->whereBetween('opened_at', [$start, $end])
            ->count();

        $openShiftsCount = CashierShift::query()
            ->where('store_id', $store->id)
            ->where('status', 'open')
            ->whereBetween('opened_at', [$start, $end])
            ->count();

        // Transaction counts
        $salesCount = DB::table('pos_sales')
            ->where('store_id', $store->id)
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$start, $end])
            ->count();

        $returnsCount = DB::table('pos_returns')
            ->where('store_id', $store->id)
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$start, $end])
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
            if (bccomp($amount, '0', 2) < 0) {
                throw new InventoryException("Counted amount for '{$method}' cannot be negative.");
            }
            $normalized[$method] = number_format((float) $amount, 2, '.', '');
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
