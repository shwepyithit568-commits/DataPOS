<?php

namespace App\POS\Models;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Derived performance cache (SoT §5 / target-design §2.5 rule 6).
 *
 * quantity_on_hand = SUM(inventory_movements.quantity_delta) for the
 * (store_id, warehouse_id, product_id, product_variant_id) key.
 *
 * Direct writes to this table are forbidden — it is maintained by
 * InventoryService and rebuilt by `php artisan inventory:reconcile`.
 * warehouse_id / product_variant_id use sentinel 0 ("none") so the
 * unique key is strictly enforced on SQLite and MySQL alike.
 */
class InventoryBalance extends Model
{
    protected $table = 'inventory_balances';

    protected $fillable = [
        'store_id',
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'quantity_on_hand',
        'unit_cost_avg',
    ];

    protected $casts = [
        'quantity_on_hand' => 'decimal:3',
        'unit_cost_avg' => 'decimal:4',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
