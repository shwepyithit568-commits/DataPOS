<?php

namespace App\POS\Services;

use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Exceptions\PeriodLockedException;
use Illuminate\Http\RedirectResponse;

/**
 * Cash taken over the counter outside the POS sale flow.
 *
 * Debt collections and repair payments take physical cash into the same drawer
 * the day's closing is reconciled against, but their screens are not the POS
 * sale screen — so nothing used to reach `cashier_shifts.cash_in` and the
 * closing reported a shortage equal to every collection and repair payment
 * taken that day. This class is the single place those screens post the money,
 * and the single place the "could not determine the drawer" outcome is worded.
 */
class CounterCashPosting
{
    public function __construct(private readonly CashierShiftService $shifts) {}

    /**
     * @param  string  $reason  Human-readable provenance stored on the cash event.
     * @return array{posted:bool, register:?string, message:?string}
     */
    public function post(Store $store, string $amount, string $reason, ?User $actor = null): array
    {
        $event = $this->shifts->postCounterCashIn($store, $amount, $reason, $actor);

        if (! $event) {
            return [
                'posted' => false,
                'register' => null,
                'message' => __('messages.counter_cash_not_posted'),
            ];
        }

        $register = $event->shift?->register_name;

        return [
            'posted' => true,
            'register' => $register,
            'message' => $register
                ? __('messages.counter_cash_posted', ['register' => $register])
                : null,
        ];
    }

    /**
     * Same as post(), but never lets a drawer problem fail the operation that
     * took the money. The customer has already paid and the receipt/ledger row
     * is the source of truth; a drawer that cannot be credited is reported as a
     * warning for the cashier to fix, not raised as an error.
     */
    public function postSafely(Store $store, string $amount, string $reason, ?User $actor = null): array
    {
        try {
            return $this->post($store, $amount, $reason, $actor);
        } catch (InventoryException|PeriodLockedException) {
            return [
                'posted' => false,
                'register' => null,
                'message' => __('messages.counter_cash_not_posted'),
            ];
        }
    }

    /**
     * Apply the outcome to a redirect: the operation's own message as success,
     * plus either "the drawer was credited" or a warning that it was not — the
     * drawer total stays short until the cashier records it by hand, and that
     * must never be a silent outcome.
     */
    public function flash(RedirectResponse $redirect, string $postedMessage, array $result): RedirectResponse
    {
        if ($result['posted']) {
            $suffix = $result['message'] ? ' · ' . $result['message'] : '';

            return $redirect->with('success', $postedMessage . $suffix);
        }

        return $redirect->with('success', $postedMessage)->with('warning', $result['message']);
    }
}
