<?php

namespace App\POS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Line item of a POS sale. product_name/sku/unit_price are snapshots taken at
 * sale time so later product edits never mutate a posted receipt.
 */
class PosSaleItem extends Model
{
    protected $fillable = [
        'pos_sale_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'sku',
        'unit_price',
        'original_unit_price',
        'approved_by',
        'quantity',
        'unit_cost',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'original_unit_price' => 'decimal:2',
        'approved_by' => 'integer',
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'line_total' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }
}
