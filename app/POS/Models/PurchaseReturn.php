<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Purchase return record — documents returned goods from a received PO.
 *
 * Each return posts purchase_returned inventory movements (outbound) and
 * adjusts the supplier's total_credit and the PO's payment_status.
 */
class PurchaseReturn extends Model
{
    protected $fillable = [
        'store_id',
        'purchase_order_id',
        'supplier_id',
        'return_number',
        'total_quantity',
        'total_cost',
        'reason',
        'created_by',
        'returned_at',
    ];

    protected $casts = [
        'total_quantity' => 'decimal:3',
        'total_cost' => 'decimal:2',
        'returned_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
