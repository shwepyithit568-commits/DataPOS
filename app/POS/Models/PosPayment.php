<?php

namespace App\POS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One payment line of a posted POS sale. A sale may have several lines
 * (split payments: cash + KPay + …). change_given applies to cash overpay.
 */
class PosPayment extends Model
{
    protected $fillable = [
        'pos_sale_id',
        'method',
        'amount',
        'change_given',
        'reference',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'change_given' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }
}
