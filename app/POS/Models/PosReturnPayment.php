<?php

namespace App\POS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Refund payment row of a pos_return — method cash (back to the drawer) or
 * credit (reduces the customer's receivable). Immutable like the return.
 */
class PosReturnPayment extends Model
{
    protected $fillable = [
        'pos_return_id',
        'method',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function posReturn(): BelongsTo
    {
        return $this->belongsTo(PosReturn::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
