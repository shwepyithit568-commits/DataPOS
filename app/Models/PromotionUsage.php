<?php

namespace App\Models;

use App\POS\Models\PosSale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionUsage extends Model
{
    protected $fillable = [
        'promotion_id',
        'store_id',
        'customer_id',
        'pos_sale_id',
        'discount_applied',
    ];

    protected $casts = [
        'discount_applied' => 'float',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }
}
