<?php

namespace App\POS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One returned line of a pos_return — snapshots from the original sale line.
 */
class PosReturnItem extends Model
{
    protected $fillable = [
        'pos_return_id',
        'pos_sale_item_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'sku',
        'unit_price',
        'quantity',
        'unit_cost',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'line_total' => 'decimal:2',
    ];

    public function posReturn(): BelongsTo
    {
        return $this->belongsTo(PosReturn::class);
    }
}
