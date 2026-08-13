<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * POS return / refund document (target-design §2.9 state machine).
 *
 * A posted return is immutable — it references its source sale, returns stock
 * to the ledger (sales_return), pays refunds (cash → drawer, credit →
 * customer ledger) and moves the sale to partially_refunded / refunded.
 */
class PosReturn extends Model
{
    protected $fillable = [
        'store_id',
        'branch_id',
        'cashier_shift_id',
        'pos_sale_id',
        'cashier_id',
        'customer_id',
        'refund_number',
        'status',
        'total',
        'notes',
        'posted_at',
        'created_by',
        'client_transaction_id',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function cashierShift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosReturnItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosReturnPayment::class);
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }
}
