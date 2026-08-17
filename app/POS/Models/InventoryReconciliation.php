<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Opening-stock reconciliation record (Phase 2.5).
 *
 * Single-step approval: the manager approves the live diff report, correction
 * movements post, and this row snapshots the report for the audit trail.
 * Approved is immutable; further corrections go through a new reconciliation.
 */
class InventoryReconciliation extends Model
{
    protected $fillable = [
        'store_id',
        'branch_id',
        'warehouse_id',
        'reconciliation_number',
        'status',
        'diff_count',
        'total_diff',
        'notes',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'approved_at',
        'client_transaction_id',
        'snapshot',
    ];

    protected $casts = [
        'diff_count' => 'integer',
        'total_diff' => 'decimal:3',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'snapshot' => 'array',
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
        return $this->hasMany(InventoryReconciliationItem::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
