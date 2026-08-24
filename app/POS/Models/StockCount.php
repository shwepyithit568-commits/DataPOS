<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Physical stock count session (Phase 2 - sidebar_stock_count).
 */
class StockCount extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_CANCELLED = 'cancelled';

    public const SCOPE_ALL = 'all';
    public const SCOPE_CATEGORY = 'category';

    protected $table = 'stock_counts';

    protected $fillable = [
        'store_id',
        'branch_id',
        'warehouse_id',
        'session_number',
        'scope',
        'category_ids',
        'status',
        'total_items',
        'counted_items',
        'variance_items',
        'total_variance_qty',
        'total_variance_cost',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'category_ids' => 'array',
        'total_items' => 'integer',
        'counted_items' => 'integer',
        'variance_items' => 'integer',
        'total_variance_qty' => 'decimal:3',
        'total_variance_cost' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockCountLine::class, 'stock_count_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Recalculate summary metrics for this session from lines.
     */
    public function recalculateStats(): void
    {
        $this->total_items = $this->lines()->count();
        $this->counted_items = $this->lines()->where('is_counted', true)->count();
        $this->variance_items = $this->lines()
            ->where('is_counted', true)
            ->where('variance_quantity', '!=', 0)
            ->count();
        $this->total_variance_qty = (float) $this->lines()
            ->where('is_counted', true)
            ->sum('variance_quantity');
        $this->total_variance_cost = (float) $this->lines()
            ->where('is_counted', true)
            ->sum('variance_cost');

        $this->save();
    }
}
