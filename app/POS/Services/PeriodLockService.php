<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\PeriodLockedException;
use App\POS\Models\DailyClosing;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class PeriodLockService
{
    /**
     * Check if a specific business date is closed and locked for the store.
     */
    public function isDateLocked(Store $store, Carbon|string $date): bool
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        return DailyClosing::query()
            ->where('store_id', $store->id)
            ->whereDate('business_date', $dateStr)
            ->where('approval_status', 'approved')
            ->exists();
    }

    /**
     * Assert that the business date is not locked.
     *
     * @throws PeriodLockedException
     */
    public function assertDateNotLocked(Store $store, Carbon|string $date, string $action = 'transaction'): void
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        if ($this->isDateLocked($store, $dateStr)) {
            throw new PeriodLockedException(
                "The business date [{$dateStr}] is locked by an approved daily closing. No {$action} can be posted to closed periods."
            );
        }
    }

    /**
     * Reopen a locked period with an explicit reason and audit log (Store Owner / Manager only).
     *
     * @throws PeriodLockedException|InvalidArgumentException
     */
    public function reopenPeriod(Store $store, Carbon|string $date, User $user, string $reason): DailyClosing
    {
        $trimmedReason = trim($reason);
        if (mb_strlen($trimmedReason) < 5) {
            throw new InvalidArgumentException('A clear explanation of at least 5 characters is required to reopen a closed period.');
        }

        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        /** @var DailyClosing|null $closing */
        $closing = DailyClosing::query()
            ->where('store_id', $store->id)
            ->whereDate('business_date', $dateStr)
            ->where('approval_status', 'approved')
            ->first();

        if (! $closing) {
            throw new PeriodLockedException("No approved daily closing found for date [{$dateStr}].");
        }

        $oldStatus = $closing->approval_status;

        $closing->update([
            'approval_status' => 'pending',
            'reopened_at'     => now(),
            'reopened_by'     => $user->id,
            'reopen_reason'   => $trimmedReason,
        ]);

        AuditLog::write(
            storeId: $store->id,
            action: 'daily_closing.reopened',
            entityType: 'daily_closing',
            entityId: $closing->id,
            metadata: [
                'business_date' => $dateStr,
                'old_status'    => $oldStatus,
                'new_status'    => 'pending',
                'reason'        => $trimmedReason,
            ],
            actorId: $user->id,
        );

        return $closing;
    }
}
