<?php

namespace App\Exceptions;

use App\Models\StoreThemeDraft;
use RuntimeException;

/**
 * Thrown inside a draft DB transaction when a concurrent save/publish would
 * lose data (stale lock) or when the published theme moved on since the draft
 * was opened (base-revision conflict).
 *
 * ThemeDraftService catches this OUTSIDE the transaction so the audit entry
 * survives the rollback, then converts it to an HTTP 409 response.
 */
class ThemeDraftConflictException extends RuntimeException
{
    public function __construct(
        public readonly StoreThemeDraft $draft,
        public readonly string $reason,
        string $message,
    ) {
        parent::__construct($message);
    }
}
