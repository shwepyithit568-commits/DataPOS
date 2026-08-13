<?php

namespace App\POS\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One opening-stock line: product/variant + quantity + unit cost. The unit
 * cost becomes the initial weighted-average cost on approval (SoT §6).
 */
class OpeningStockRequestItem extends Model
{
    protected $fillable = [
        'opening_stock_request_id',
        'store_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_cost',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'line_total' => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(OpeningStockRequest::class, 'opening_stock_request_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
