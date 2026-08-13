<?php

namespace App\POS\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One adjustment line: product/variant + SIGNED quantity (+ in / − out) +
 * required reason. On approval the sign picks the movement type
 * (adjustment_in / adjustment_out) with |quantity|.
 */
class InventoryAdjustmentItem extends Model
{
    protected $fillable = [
        'inventory_adjustment_id',
        'store_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
