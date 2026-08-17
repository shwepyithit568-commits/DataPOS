<?php

namespace App\POS\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One snapshot line of an approved opening-stock reconciliation: the imported
 * opening quantity vs the ledger's recorded opening position, the difference,
 * and the signed correction that was posted (adjustment_in/out).
 */
class InventoryReconciliationItem extends Model
{
    protected $fillable = [
        'inventory_reconciliation_id',
        'store_id',
        'product_id',
        'product_variant_id',
        'imported_quantity',
        'recorded_quantity',
        'difference',
        'correction',
        'movement_type',
    ];

    protected $casts = [
        'imported_quantity' => 'decimal:3',
        'recorded_quantity' => 'decimal:3',
        'difference' => 'decimal:3',
        'correction' => 'decimal:3',
    ];

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(InventoryReconciliation::class, 'inventory_reconciliation_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
