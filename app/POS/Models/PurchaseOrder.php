<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Purchase order document (alinthit_pos style — Phase 4 early build).
 *
 * Lifecycle: pending → ordered → received | cancelled
 *
 * The PO is a planning document: creating or ordering does NOT increase stock.
 * Only "receive" posts purchase_received ledger movements via the GoodsReceipt
 * infrastructure (SoT §6, §11.5).
 *
 * Immutable once received: corrections use ledger reversals (SoT §15.1).
 */
class PurchaseOrder extends Model
{
    protected $fillable = [
        'store_id',
        'branch_id',
        'warehouse_id',
        'supplier_id',
        'po_number',
        'status',
        'total_quantity',
        'total_cost',
        'reference',
        'notes',
        'ordered_at',
        'received_at',
        'cancelled_at',
        'created_by',
    ];

    protected $casts = [
        'total_quantity' => 'decimal:3',
        'total_cost' => 'decimal:2',
        'ordered_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ------------------------------------------------------------------ */
    /*  Status helpers                                                      */
    /* ------------------------------------------------------------------ */

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isOrdered(): bool
    {
        return $this->status === 'ordered';
    }

    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
