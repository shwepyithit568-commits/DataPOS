<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One immutable row in the customer receivable ledger (SoT §17).
 *
 * amount is signed: + = debt increases (sale_debt / opening_balance),
 * − = debt decreases (collection / reversal). The customer's outstanding
 * balance is SUM(amount) — never a stored, directly-editable field.
 */
class CustomerLedgerEntry extends Model
{
    public const TYPE_SALE_DEBT = 'sale_debt';
    public const TYPE_COLLECTION = 'collection';
    public const TYPE_REVERSAL = 'reversal';
    public const TYPE_OPENING_BALANCE = 'opening_balance';

    protected $fillable = [
        'store_id',
        'customer_id',
        'branch_id',
        'type',
        'amount',
        'source_type',
        'source_id',
        'notes',
        'occurred_at',
        'created_by',
        'client_transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
