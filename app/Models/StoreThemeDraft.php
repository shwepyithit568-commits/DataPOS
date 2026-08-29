<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Store Theme Draft — one active, isolated draft per store.
 *
 * Rules (enforced by application layer, not DB constraint):
 *  - theme_config MUST be a complete ThemeConfig::toArray() snapshot (9 fields).
 *  - This model MUST NEVER be used to update storefront_settings.
 *  - Customer-facing code MUST NOT load this model.
 *  - lock_version MUST be incremented on every successful save.
 *
 * @property int    $id
 * @property int    $store_id
 * @property array  $theme_config
 * @property int|null $base_revision_id
 * @property int|null $updated_by
 * @property int    $lock_version
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class StoreThemeDraft extends Model
{
    protected $fillable = [
        'store_id',
        'theme_config',
        'base_revision_id',
        'updated_by',
        'lock_version',
    ];

    protected $casts = [
        'theme_config'     => 'array',
        'lock_version'     => 'integer',
        'base_revision_id' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function baseRevision(): BelongsTo
    {
        return $this->belongsTo(StoreThemeRevision::class, 'base_revision_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Determine whether the draft is potentially in conflict:
     * the latest published revision for this store is different from
     * the one that was published when this draft was last re-based.
     *
     * @param int|null $latestPublishedRevisionId
     */
    public function isConflicting(?int $latestPublishedRevisionId): bool
    {
        // No published revision yet → no conflict possible
        if ($latestPublishedRevisionId === null) {
            return false;
        }

        // Draft has no base → was created before any publish; no conflict
        if ($this->base_revision_id === null) {
            return false;
        }

        return $this->base_revision_id !== $latestPublishedRevisionId;
    }
}
