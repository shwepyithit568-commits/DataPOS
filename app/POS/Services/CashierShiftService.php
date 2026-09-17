<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CashEvent;
use App\POS\Models\CashierShift;
use App\POS\Services\PeriodLockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cashier shift lifecycle (target-design §2.10 — Phase 2 MVP).
 *
 * - openShift: one OPEN shift per (store, register) — a cashier cannot open a
 *   second shift on a register that is already running.
 * - addCashEvent: cash in/out on an OPEN shift (totals maintained on the shift,
 *   detail rows kept in cash_events as the audit log).
 * - closeShift: expected = opening_cash + cash_sales − cash_refunds + cash_in
 *   − cash_out; difference = actual − expected. Requires actual cash.
 * - dailySummary: closed shifts for a date (branch daily summary, MVP scope).
 *
 * Money is decimal throughout — no float arithmetic is used for amounts
 * (bcmath, matching the §2.6 money policy).
 */
class CashierShiftService
{
    /**
     * @param  array{register_name:string, opening_cash?:float|string, branch_id?:int, cashier_id?:int}  $data
     */
    public function openShift(Store $store, array $data, ?User $actor = null): CashierShift
    {
        app(PeriodLockService::class)->assertDateNotLocked($store, now(), 'cashier_shift');

        if (! $store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS)) {
            throw new InventoryException('Cashier shifts are disabled for this store.');
        }

        $registerName = trim($data['register_name'] ?? '');
        if ($registerName === '') {
            throw new InventoryException('A register name is required to open a shift.');
        }

        $existing = CashierShift::query()
            ->where('store_id', $store->id)
            ->where('register_name', $registerName)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            $owner = $existing->cashier?->name ?? 'Unknown'; // app locale-independent fallback
            $since = $existing->opened_at?->format('H:i') ?? '—';
            throw new InventoryException(
                "Register '{$registerName}' already has an open shift (#{$existing->id}) by {$owner} since {$since}."
            );
        }

        return DB::transaction(function () use ($store, $data, $registerName, $actor) {
            return CashierShift::create([
                'store_id' => $store->id,
                'branch_id' => $data['branch_id'] ?? null,
                'register_name' => $registerName,
                'cashier_id' => $data['cashier_id'] ?? $actor?->id,
                'status' => 'open',
                'opened_at' => now(),
                'opening_cash' => $data['opening_cash'] ?? 0,
                'cash_sales' => 0,
                'cash_refunds' => 0,
                'cash_in' => 0,
                'cash_out' => 0,
            ]);
        });
    }

    /**
     * The actor's open shift for the store (null when none).
     */
    public function openShiftFor(Store $store, User $cashier): ?CashierShift
    {
        if (! $store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS)) {
            return null;
        }

        return CashierShift::query()
            ->where('store_id', $store->id)
            ->where('cashier_id', $cashier->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    /**
     * @param  array{type:string, amount:float|string, reason?:string}  $data
     */
    public function addCashEvent(CashierShift $shift, array $data, ?User $actor = null): CashEvent
    {
        $this->assertOpen($shift);

        $type = $data['type'];
        // Normalize to an exact decimal: bccomp below and the increment() call
        // further down both need a value bcmath/SQL can parse verbatim.
        $amount = bcadd((string) ($data['amount'] ?? '0'), '0', 2);

        if (! in_array($type, ['cash_in', 'cash_out'], true)) {
            throw new InventoryException("Unknown cash event type '{$type}'.");
        }

        if (bccomp($amount, '0', 2) <= 0) {
            throw new InventoryException('Cash event amount must be positive.');
        }

        return DB::transaction(function () use ($shift, $type, $amount, $data, $actor) {
            $event = CashEvent::create([
                'store_id' => $shift->store_id,
                'cashier_shift_id' => $shift->id,
                'type' => $type,
                'amount' => $amount,
                'reason' => $data['reason'] ?? null,
                'created_by' => $actor?->id,
            ]);

            // Decimal string, not a float: increment() hands this straight to
            // SQL, where a float would let binary rounding reach the column.
            $shift->increment($type === 'cash_in' ? 'cash_in' : 'cash_out', $amount);

            return $event;
        });
    }

    /**
     * Add net cash retained from a posted POS sale to the shift's drawer
     * (bcmath — matches the money policy). Called inside the sale-posting
     * transaction so drawer and sale stay atomic.
     */
    public function recordCashSale(CashierShift $shift, string $amount): void
    {
        $this->assertOpen($shift);

        $shift->update([
            'cash_sales' => bcadd((string) $shift->cash_sales, $amount, 2),
        ]);
    }

    /**
     * Add a cash refund (returned to the customer) to the shift's drawer.
     * Called inside the return-posting transaction so drawer and return stay
     * atomic (bcmath).
     */
    public function recordCashRefund(CashierShift $shift, string $amount): void
    {
        $this->assertOpen($shift);

        $shift->update([
            'cash_refunds' => bcadd((string) $shift->cash_refunds, $amount, 2),
        ]);
    }

    /**
     * @param  array{actual_closing_amount:float|string, notes?:string, manager_approval?:bool}  $data
     */
    public function closeShift(CashierShift $shift, array $data, ?User $actor = null): CashierShift
    {
        $this->assertOpen($shift);

        $actual = (string) ($data['actual_closing_amount'] ?? '');
        if ($actual === '' || bccomp($actual, '0', 2) < 0) {
            throw new InventoryException('Actual closing amount is required and cannot be negative.');
        }

        return DB::transaction(function () use ($shift, $actual, $data, $actor) {
            // Shift-period cash expenses deducted from drawer:
            // ONLY expenses paid from THIS shift drawer (payment_source = 'drawer'
            // and cashier_shift_id = $shift->id and status = 'paid').
            $shiftDrawerExpenses = exact_sum(
                DB::table('expenses')
                    ->where('store_id', $shift->store_id)
                    ->where('cashier_shift_id', $shift->id)
                    ->where('payment_method', 'cash')
                    ->where('payment_source', \App\POS\Models\Expense::SOURCE_DRAWER)
                    ->where('status', 'paid'),
                'amount'
            );

            // Other cash out: calculate non-expense cash-out events (safe drops, etc.)
            // to guarantee legacy linked events or expense-linked events are NEVER double-counted.
            $hasCashEvents = DB::table('cash_events')->where('cashier_shift_id', $shift->id)->exists();
            if ($hasCashEvents) {
                $otherCashOut = exact_sum(
                    DB::table('cash_events')
                        ->where('cashier_shift_id', $shift->id)
                        ->where('type', 'cash_out')
                        ->whereNull('expense_id')
                        ->where(function ($q) {
                            $q->whereNull('reason')->orWhere('reason', 'not like', 'Expense:%');
                        }),
                    'amount'
                );
            } else {
                $otherCashOut = bcsub((string) $shift->cash_out, $shiftDrawerExpenses, 2);
                if (bccomp($otherCashOut, '0', 2) < 0) {
                    $otherCashOut = '0.00';
                }
            }

            // Expected cash = Opening + Cash Sales + Other Cash In - Cash Refunds - Drawer Expenses - Other Cash Out
            $expected = bcsub(
                bcsub(
                    bcadd(
                        bcadd((string) $shift->opening_cash, (string) $shift->cash_sales, 2),
                        (string) $shift->cash_in,
                        2
                    ),
                    (string) $shift->cash_refunds,
                    2
                ),
                bcadd($shiftDrawerExpenses, $otherCashOut, 2),
                2
            );

            $difference = bcsub($actual, $expected, 2);
            $absDiff = bccomp($difference, '0', 2) < 0 ? bcmul($difference, '-1', 2) : $difference;

            $varianceReason = trim((string) ($data['variance_reason'] ?? $data['notes'] ?? ''));
            $threshold = (string) ($data['variance_threshold'] ?? '5000.00');
            $requiresReason = ($data['require_variance_reason'] ?? false) || bccomp($absDiff, $threshold, 2) > 0;

            if (bccomp($absDiff, '0', 2) !== 0 && $requiresReason && $varianceReason === '') {
                throw new InventoryException('A variance reason is required when cash drawer difference exceeds threshold (' . $threshold . ').');
            }

            $managerSignoffId = $data['manager_signoff_id'] ?? null;
            $signedOffAt = ! empty($managerSignoffId) ? ($data['signed_off_at'] ?? now()) : null;

            $shift->update([
                'status' => 'closed',
                'expected_closing_amount' => $expected,
                'actual_closing_amount' => $actual,
                'difference' => $difference,
                'variance_reason' => $varianceReason !== '' ? $varianceReason : null,
                'manager_signoff_id' => $managerSignoffId,
                'signed_off_at' => $signedOffAt,
                'manager_approval' => $data['manager_approval'] ?? null,
                'notes' => $data['notes'] ?? null,
                'closed_at' => now(),
                'closed_by' => $actor?->id ?? $shift->cashier_id,
            ]);

            if (bccomp($difference, '0', 2) !== 0) {
                AuditLog::write(
                    storeId: $shift->store_id,
                    action: 'cashier_shift_variance_closed',
                    entityType: 'cashier_shift',
                    entityId: $shift->id,
                    metadata: [
                        'expected' => $expected,
                        'actual' => $actual,
                        'difference' => $difference,
                        'variance_reason' => $varianceReason !== '' ? $varianceReason : null,
                        'manager_signoff_id' => $managerSignoffId,
                    ],
                    actorId: $actor?->id ?? $shift->cashier_id,
                );
            }

            return $shift;
        });
    }

    /**
     * Branch daily summary (MVP): closed shifts for a date + totals.
     *
     * @return array{shifts: \Illuminate\Support\Collection<int, CashierShift>, shift_count:int, opening_cash:string, cash_sales:string, cash_refunds:string, cash_in:string, cash_out:string, expected:string, actual:string, difference:string}
     */
    public function dailySummary(Store $store, \DateTimeInterface $date): array
    {
        $carbonDate = Carbon::parse($date);
        $start = $carbonDate->copy()->startOfDay();
        $end = $carbonDate->copy()->endOfDay();

        $shifts = CashierShift::query()
            ->where('store_id', $store->id)
            ->where('status', 'closed')
            ->whereBetween('closed_at', [$start, $end])
            ->with('cashier')
            ->orderBy('closed_at')
            ->get();

        $sum = fn (string $col) => number_format((float) $shifts->sum($col), 2, '.', '');

        return [
            'shifts' => $shifts,
            'shift_count' => $shifts->count(),
            'opening_cash' => $sum('opening_cash'),
            'cash_sales' => $sum('cash_sales'),
            'cash_refunds' => $sum('cash_refunds'),
            'cash_in' => $sum('cash_in'),
            'cash_out' => $sum('cash_out'),
            'expected' => $sum('expected_closing_amount'),
            'actual' => $sum('actual_closing_amount'),
            'difference' => $sum('difference'),
        ];
    }

    protected function assertOpen(CashierShift $shift): void
    {
        if (! $shift->isOpen()) {
            throw new InventoryException("Shift #{$shift->id} is already closed.");
        }
    }
}
