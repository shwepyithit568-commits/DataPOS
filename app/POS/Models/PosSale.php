<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * POS sale (target-design §2.8 state machine).
 *
 * status: draft → held → posted → partially_refunded / refunded / reversed.
 * A held sale that is recalled becomes 'resumed' (leaves the held list, keeps
 * the audit trail) and can then be posted or voided. Voided applies to
 * draft/held only — a posted sale is immutable and can only be refunded/
 * reversed (a later module). Receipt number is assigned at posting.
 */
class PosSale extends Model
{
    protected $fillable = [
        'store_id',
        'branch_id',
        'cashier_shift_id',
        'cashier_id',
        'customer_id',
        'receipt_number',
        'status',
        'subtotal',
        'discount',
        'tax',
        'total',
        'client_transaction_id',
        'notes',
        'posted_at',
        'refunded_at',
        'voided_at',
        'created_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'posted_at' => 'datetime',
        'refunded_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
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
        return $this->hasMany(PosSaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class);
    }

    public function isPosted(): bool
    {
        return in_array($this->status, ['posted', 'partially_refunded', 'refunded', 'reversed'], true);
    }

    public function isHeld(): bool
    {
        return $this->status === 'held';
    }
}
