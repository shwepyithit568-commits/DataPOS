<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Goods receipt document (simple stock receiving, MVP Phase 2).
 *
 * Immutable once posted: corrections are done with reversal movements on the
 * ledger, never by editing this document (SoT §15.1). The receipt number
 * (GRV-Ymd-####) is assigned at posting time and unique per store.
 */
class GoodsReceipt extends Model
{
    protected $fillable = [
        'store_id',
        'branch_id',
        'warehouse_id',
        'receipt_number',
        'status',
        'total_quantity',
        'total_cost',
        'reference',
        'notes',
        'posted_at',
        'created_by',
        'client_transaction_id',
    ];

    protected $casts = [
        'total_quantity' => 'decimal:3',
        'total_cost' => 'decimal:2',
        'posted_at' => 'datetime',
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
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }
}
