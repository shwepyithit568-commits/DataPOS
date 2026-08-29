<?php

namespace App\ViewModels\Storefront;

use App\Models\Product;
use App\Models\Store;

class ProductCardViewModel
{
    public function __construct(
        public readonly Product $product,
        public readonly ?Store $store = null,
        public readonly bool $isWholesaleApproved = false
    ) {}

    public function id(): int
    {
        return $this->product->id;
    }

    public function name(): string
    {
        return $this->product->name;
    }

    public function sku(): ?string
    {
        return $this->product->sku;
    }

    public function brandName(): ?string
    {
        return $this->product->brand?->name;
    }

    public function categoryName(): ?string
    {
        return $this->product->category?->name;
    }

    public function price(): float
    {
        if ($this->isWholesaleApproved && $this->product->wholesale_price && $this->product->wholesale_price > 0) {
            return (float) $this->product->wholesale_price;
        }

        return (float) $this->product->retail_price;
    }

    public function formattedPrice(): string
    {
        return 'Ks ' . number_format($this->price(), 0);
    }

    public function oldPrice(): ?float
    {
        if ($this->isWholesaleApproved) {
            return null;
        }

        return $this->product->old_price ? (float) $this->product->old_price : null;
    }

    public function formattedOldPrice(): ?string
    {
        $old = $this->oldPrice();
        return $old ? 'Ks ' . number_format($old, 0) : null;
    }

    public function discountPercentage(): int
    {
        $old = $this->oldPrice();
        $curr = $this->price();

        if ($old && $old > $curr && $old > 0) {
            return (int) round((($old - $curr) / $old) * 100);
        }

        return 0;
    }

    public function isOutOfStock(): bool
    {
        return $this->product->stock_status === 'out_of_stock';
    }

    public function isFeatured(): bool
    {
        return (bool) $this->product->is_featured;
    }

    public function imageUrl(): string
    {
        $firstVariantImg = $this->product->variants?->first(fn ($v) => !empty($v->image_path))?->image_path;
        $path = $firstVariantImg ?: $this->product->image_path;

        if ($path) {
            return asset('storage/' . $path);
        }

        return asset('images/product-placeholder.png');
    }

    public function detailUrl(): string
    {
        $storeSlug = $this->store?->slug ?? request('store_slug');
        if ($storeSlug) {
            return route('storefront.product', ['store_slug' => $storeSlug, 'slug' => $this->product->slug]);
        }

        return url('/products/' . $this->product->slug);
    }
}
