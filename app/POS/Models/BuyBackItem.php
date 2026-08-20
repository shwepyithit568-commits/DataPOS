<?php

namespace App\POS\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuyBackItem extends Model
{
    protected $fillable = [
        'buy_back_id',
        'product_id',
        'quantity',
        'unit_price',
        'unit_cost',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'unit_cost' => 'decimal:4',
    ];

    public function buyBack(): BelongsTo
    {
        return $this->belongsTo(BuyBack::class, 'buy_back_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
