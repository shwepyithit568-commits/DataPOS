<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform-controlled theme lifecycle override (T7).
 *
 * One row per theme id. When absent, the static ThemeRegistry manifest status
 * applies. Never affects rendering of stores already using a theme — a hidden
 * or deprecated theme stays fully renderable; only NEW selection is gated.
 */
class ThemeGovernance extends Model
{
    protected $table = 'theme_governance';

    public const STATUS_ACTIVE     = 'active';
    public const STATUS_DEPRECATED = 'deprecated';
    public const STATUS_HIDDEN     = 'hidden';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_DEPRECATED,
        self::STATUS_HIDDEN,
    ];

    protected $fillable = [
        'theme_id',
        'status',
        'replacement_id',
        'updated_by',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
