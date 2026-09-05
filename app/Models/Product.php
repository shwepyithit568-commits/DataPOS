<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'category_id',
        'brand_id',
        'sku',
        'product_type',
        'barcode',
        'name',
        'slug',
        'description',
        'compatible_models',
        'specs',
        'meta_description',
        'retail_price',
        'old_price',
        'sale_starts_at',
        'sale_ends_at',
        'wholesale_price',
        'stock_status',
        'image_path',
        'warranty',
        'return_policy',
        'is_featured',
        'reorder_level',
        'shelf_location',
        'warehouse_id',
        'supplier_id',
        'purchase_cost',
        'service_duration',
        'digital_delivery_method',
        'is_ecommerce',
    ];

    protected $casts = [
        'retail_price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
        'wholesale_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'reorder_level' => 'decimal:3',
        'purchase_cost' => 'decimal:4',
        'is_ecommerce' => 'boolean',
        'specs' => 'array',
    ];

    /** Matches the DB default — products are online until marked counter-only. */
    protected $attributes = [
        'is_ecommerce' => true,
        'product_type' => 'standard',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\POS\Models\Warehouse::class);
    }

    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order', 'asc');
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function approvedReviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->reviews()->approved();
    }

    public function variants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function units(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductUnit::class)->orderBy('conversion_factor', 'asc');
    }

    public function batches(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductBatch::class)->orderBy('expiration_date', 'asc');
    }

    /**
     * The default variant (for the price/SKU shown on cards & list pages).
     */
    public function defaultVariant(): ?ProductVariant
    {
        return $this->variants->firstWhere('is_default', true) ?? $this->variants->first();
    }

    /**
     * True when the retail price is lower than the compare-at (old) price —
     * the storefront shows a discount badge in that case.
     */
    public function isOnSale(): bool
    {
        return $this->old_price !== null
            && (float) $this->old_price > (float) $this->retail_price
            && $this->isSaleActive();
    }

    public function isSaleActive(): bool
    {
        $now = now();

        if ($this->sale_starts_at && $this->sale_starts_at->gt($now)) {
            return false;
        }

        if ($this->sale_ends_at && $this->sale_ends_at->lt($now)) {
            return false;
        }

        return true;
    }

    public function saleWindowLabel(): ?string
    {
        if (! $this->isOnSale()) {
            return null;
        }

        if ($this->sale_ends_at) {
            return 'Sale ends ' . $this->sale_ends_at->format('M j, g:i A');
        }

        if ($this->sale_starts_at) {
            return 'Limited-time sale';
        }

        return null;
    }

    /**
     * True when a sale is scheduled to start in the future — the storefront
     * shows these as "starting soon" deals with a countdown to the start.
     */
    public function isUpcomingSale(): bool
    {
        return $this->old_price !== null
            && (float) $this->old_price > (float) $this->retail_price
            && $this->sale_starts_at !== null
            && $this->sale_starts_at->gt(now());
    }

    public function discountPercent(): int
    {
        if (! $this->isOnSale()) {
            return 0;
        }
        $retail = (float) $this->retail_price;
        if ($retail <= 0) {
            return 0;
        }
        return (int) round((((float) $this->old_price - $retail) / (float) $this->old_price) * 100);
    }

    public function getAllImagePathsAttribute(): array
    {
        $gallery = $this->images->pluck('image_path')->toArray();
        if ($this->image_path && !in_array($this->image_path, $gallery)) {
            array_unshift($gallery, $this->image_path);
        }
        $gallery = array_values(array_filter($gallery));

        // Auto-pair 2nd image (Software Ad) if only 1 image exists
        if (count($gallery) === 1) {
            $primary = $gallery[0];
            if (preg_match('/data-product-(\d+)\.webp$/', $primary, $matches)) {
                $gallery[] = "placeholders/datapos-software-ad-{$matches[1]}.webp";
            } else {
                $num = ($this->id % 4) + 1;
                $gallery[] = "placeholders/datapos-software-ad-{$num}.webp";
            }
        }

        return $gallery;
    }

    public function isInStock(): bool
    {
        return $this->stock_status === 'in_stock';
    }

    /**
     * Convenience accessor: maps the legacy "price" field to retail_price
     * so existing lookups (product search, cards) keep working without a DB
     * column rename.
     */
    public function getPriceAttribute(): string
    {
        return (string) ($this->attributes['retail_price'] ?? '0');
    }

    public function inventoryBalances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\POS\Models\InventoryBalance::class);
    }

    public function getStockOnHandAttribute(): float
    {
        return (float) $this->inventoryBalances()->sum('quantity_on_hand');
    }

    /**
     * Convenience accessor: maps the legacy "cost" field to purchase_cost
     * so PO builder, costing, and product search can all use $product->cost.
     */
    public function getCostAttribute(): string
    {
        return (string) ($this->attributes['purchase_cost'] ?? '0');
    }
}
