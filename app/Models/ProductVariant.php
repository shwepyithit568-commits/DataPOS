<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'attributes',
        'sku',
        'retail_price',
        'wholesale_price',
        'stock_status',
        'quantity_on_hand',
        'image_path',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'quantity_on_hand' => 'decimal:3',
        'is_default' => 'boolean',
        'attributes' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryBalances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\POS\Models\InventoryBalance::class, 'product_variant_id');
    }

    public function getStockOnHandAttribute(): float
    {
        if ($this->relationLoaded('inventoryBalances') && $this->inventoryBalances->isNotEmpty()) {
            return (float) $this->inventoryBalances->sum('quantity_on_hand');
        }
        $sum = $this->inventoryBalances()->sum('quantity_on_hand');
        if ($sum > 0) {
            return (float) $sum;
        }
        return (float) ($this->attributes['quantity_on_hand'] ?? 0);
    }

    public function isInStock(): bool
    {
        return $this->stock_status === 'in_stock';
    }
}

