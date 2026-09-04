<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Opening-stock request with manager approval (MVP Phase 2).
 *
 * Pending → approved (posts `opening_balance` ledger movements + sets the
 * initial weighted average) | rejected. Approved is immutable.
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class OpeningStockRequest extends Model
{
    protected $fillable = [
        'store_id',
        'branch_id',
        'warehouse_id',
        'request_number',
        'status',
        'total_quantity',
        'total_cost',
        'notes',
        'review_notes',
        'submitted_by',
        'reviewed_by',
        'reviewed_at',
        'approved_at',
        'client_transaction_id',
    ];

    protected $casts = [
        'total_quantity' => 'decimal:3',
        'total_cost' => 'decimal:2',
        'reviewed_at' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(OpeningStockRequestItem::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
