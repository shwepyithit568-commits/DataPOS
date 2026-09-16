<?php

namespace App\POS\Models;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single line item in a physical stock count session.
 */
class StockCountLine extends Model
{
    protected $table = 'stock_count_lines';

    protected $fillable = [
        'stock_count_id',
        'store_id',
        'product_id',
        'product_variant_id',
        'category_id',
        'system_quantity',
        'counted_quantity',
        'variance_quantity',
        'unit_cost',
        'variance_cost',
        'is_counted',
        'notes',
        'counted_at',
    ];

    protected $casts = [
        'product_variant_id' => 'integer',
        'system_quantity' => 'decimal:3',
        'counted_quantity' => 'decimal:3',
        'variance_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'variance_cost' => 'decimal:2',
        'is_counted' => 'boolean',
        'counted_at' => 'datetime',
    ];

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'stock_count_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Update counted quantity and compute variance.
     *
     * Variance drives the inventory adjustment posted on approval, so it is
     * derived with bcmath rather than float subtraction/rounding.
     */
    public function setCount(float|int|string $countedQty, ?string $notes = null): void
    {
        $counted = bcadd((string) $countedQty, '0', 3);

        if (bccomp($counted, '0', 3) < 0) {
            throw new \InvalidArgumentException('Counted quantity cannot be negative.');
        }

        $system = bcadd((string) ($this->system_quantity ?? '0'), '0', 3);
        $variance = bcsub($counted, $system, 3);

        $this->counted_quantity = $counted;
        $this->variance_quantity = $variance;
        $this->variance_cost = bcmul($variance, bcadd((string) ($this->unit_cost ?? '0'), '0', 4), 2);
        $this->is_counted = true;
        $this->counted_at = now();
        if ($notes !== null) {
            $this->notes = $notes;
        }
        $this->save();
    }
}
