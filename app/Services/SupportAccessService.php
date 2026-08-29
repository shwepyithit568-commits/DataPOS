<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class SupportAccessService
{
    public const SESSION_KEY_ACTIVE = 'support_mode_active';
    public const SESSION_KEY_REASON = 'support_mode_reason';
    public const SESSION_KEY_STORE  = 'support_mode_store_id';
    public const SESSION_KEY_SLUG   = 'support_mode_store_slug';

    /**
     * Start a support mode session with audit trail.
     */
    public function startSupportSession(User $admin, Store $store, string $reason): void
    {
        Session::put(self::SESSION_KEY_ACTIVE, true);
        Session::put(self::SESSION_KEY_REASON, trim($reason));
        Session::put(self::SESSION_KEY_STORE, $store->id);
        Session::put(self::SESSION_KEY_SLUG, $store->slug);

        AuditLog::write(
            $store->id,
            'support_mode_session_started',
            'support_session',
            $store->id,
            [
                'admin_id'    => $admin->id,
                'admin_name'  => $admin->name,
                'admin_phone' => $admin->phone,
                'reason'      => trim($reason),
                'started_at'  => now()->toDateTimeString(),
            ],
            $admin->id,
            request()->ip()
        );
    }

    /**
     * Check if a support mode session is currently active.
     */
    public function isSupportModeActive(): bool
    {
        return (bool) Session::get(self::SESSION_KEY_ACTIVE, false);
    }

    /**
     * Get the declared reason for the active support session.
     */
    public function getSupportReason(): ?string
    {
        return Session::get(self::SESSION_KEY_REASON);
    }

    /**
     * Get the store slug currently being assisted in support mode.
     */
    public function getSupportStoreSlug(): ?string
    {
        return Session::get(self::SESSION_KEY_SLUG);
    }

    /**
     * Exit and clean up support session.
     */
    public function exitSupportSession(): void
    {
        $storeId = Session::get(self::SESSION_KEY_STORE);
        $reason = Session::get(self::SESSION_KEY_REASON);

        if ($storeId) {
            AuditLog::write(
                $storeId,
                'support_mode_session_ended',
                'support_session',
                $storeId,
                [
                    'admin_id'   => auth()->id(),
                    'reason'     => $reason,
                    'ended_at'   => now()->toDateTimeString(),
                ],
                auth()->id(),
                request()->ip()
            );
        }

        Session::forget([
            self::SESSION_KEY_ACTIVE,
            self::SESSION_KEY_REASON,
            self::SESSION_KEY_STORE,
            self::SESSION_KEY_SLUG,
        ]);
    }
}
