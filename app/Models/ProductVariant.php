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
        if (isset($this->attributes['on_hand_qty'])) {
            return (float) $this->attributes['on_hand_qty'];
        }

        if ($this->relationLoaded('inventoryBalances')) {
            return (float) $this->inventoryBalances->sum('quantity_on_hand');
        }

        if ($this->inventoryBalances()->exists()) {
            return (float) $this->inventoryBalances()->sum('quantity_on_hand');
        }

        return (float) ($this->attributes['quantity_on_hand'] ?? 0);
    }

    public function isInStock(): bool
    {
        if ($this->stock_status === 'out_of_stock') {
            return false;
        }

        if ($this->relationLoaded('product') && in_array($this->product?->product_type, ['service', 'digital'], true)) {
            return true;
        }

        if (isset($this->attributes['on_hand_qty'])) {
            return (float) $this->attributes['on_hand_qty'] > 0;
        }

        if ($this->relationLoaded('inventoryBalances')) {
            return (float) $this->inventoryBalances->sum('quantity_on_hand') > 0;
        }

        if ($this->inventoryBalances()->exists()) {
            return (float) $this->inventoryBalances()->sum('quantity_on_hand') > 0;
        }

        return (float) ($this->attributes['quantity_on_hand'] ?? 0) > 0 || $this->stock_status === 'in_stock';
    }
}

