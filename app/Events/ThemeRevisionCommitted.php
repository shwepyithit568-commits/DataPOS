<?php

namespace App\Events;

use App\Models\Store;
use App\Models\StoreThemeRevision;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched AFTER a theme publish or rollback transaction commits.
 *
 * Listeners must be side-effect-only (cache invalidation, analytics) and must
 * never throw in a way that could affect the already-committed publish — the
 * theme data is written by then.
 */
class ThemeRevisionCommitted
{
    use Dispatchable;

    public function __construct(
        public readonly Store $store,
        public readonly StoreThemeRevision $revision,
        public readonly string $action, // 'publish' | 'rollback'
        public readonly ?User $actor = null,
    ) {}
}
