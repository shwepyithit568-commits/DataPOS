<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Manager-PIN verification with a per-user attempt counter + lockout.
 *
 * The POS PIN is only 4-6 digits, so a cashier could brute-force a manager's
 * PIN by repeatedly posting discounted cart lines. Every failed attempt is
 * counted for the ACTING user inside a sliding window; MAX_ATTEMPTS failures
 * lock the PIN prompt for that user until the window expires (a correct PIN
 * clears the counter). Failures are audit-logged (pos_pin_failed) so the
 * owner can see who was trying and when.
 */
class PosPinVerifier
{
    public const MAX_ATTEMPTS = 5;

    public const WINDOW_MINUTES = 15;

    private function failureKey(User $actor): string
    {
        return 'pos.pin.failures.' . $actor->getKey();
    }

    private function lockedUntilKey(User $actor): string
    {
        return 'pos.pin.locked_until.' . $actor->getKey();
    }

    public function failureCount(User $actor): int
    {
        return (int) Cache::get($this->failureKey($actor), 0);
    }

    public function isLocked(User $actor): bool
    {
        $until = (int) Cache::get($this->lockedUntilKey($actor), 0);

        return $until > time();
    }

    /** Whole minutes until the lockout expires (0 when not locked). */
    public function remainingLockoutMinutes(User $actor): int
    {
        $remaining = (int) Cache::get($this->lockedUntilKey($actor), 0) - time();

        return $remaining > 0 ? (int) ceil($remaining / 60) : 0;
    }

    /**
     * Verify the PIN for the acting user. Returns the approving manager on
     * success, null on failure or while locked (callers pick the message).
     * A successful match clears the attempt counter.
     */
    public function verify(Store $store, User $actor, string $pin): ?User
    {
        if ($this->isLocked($actor)) {
            return null;
        }

        $approver = $this->resolveManagerByPin($store, $pin);

        if ($approver !== null) {
            Cache::forget($this->failureKey($actor));
            Cache::forget($this->lockedUntilKey($actor));

            return $approver;
        }

        $this->recordFailure($actor, $store);

        return null;
    }

    private function recordFailure(User $actor, Store $store): void
    {
        $ttlSeconds = self::WINDOW_MINUTES * 60;
        $count = $this->failureCount($actor) + 1;

        Cache::put($this->failureKey($actor), $count, $ttlSeconds);

        if ($count >= self::MAX_ATTEMPTS) {
            Cache::put($this->lockedUntilKey($actor), time() + $ttlSeconds, $ttlSeconds);
        }

        AuditLog::write(
            storeId: $store->id,
            action: 'pos_pin_failed',
            entityType: 'pos_cart',
            metadata: ['attempt' => $count, 'max' => self::MAX_ATTEMPTS],
            actorId: $actor->getKey(),
        );
    }

    /** Active store managers/owners — the same rule the controller used. */
    private function resolveManagerByPin(Store $store, string $pin): ?User
    {
        return User::whereHas('stores', function ($q) use ($store) {
            $q->where('store_id', $store->id)
                ->whereIn('role', ['store_manager', 'store_owner'])
                ->where('status', 'active');
        })->get()
            ->first(fn (User $user) => $user->posPinMatches($pin));
    }
}
