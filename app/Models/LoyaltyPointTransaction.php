<?php

namespace App\Models;

use App\POS\Models\PosSale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPointTransaction extends Model
{
    protected $fillable = [
        'store_id',
        'customer_id',
        'type',
        'points',
        'balance_after',
        'pos_sale_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
