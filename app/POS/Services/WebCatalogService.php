<?php

namespace App\POS\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WebCatalogService
{
    /**
     * Summary statistics for Web Catalog Product Visibility dashboard.
     */
    public function getSummaryStats(Store $store): array
    {
        $totalProducts = Product::where('store_id', $store->id)->count();
        $onlineProducts = Product::where('store_id', $store->id)->where('is_ecommerce', true)->count();
        $counterOnlyProducts = $totalProducts - $onlineProducts;
        $featuredProducts = Product::where('store_id', $store->id)->where('is_featured', true)->count();
        $onlineInStock = Product::where('store_id', $store->id)
            ->where('is_ecommerce', true)
            ->where('stock_status', 'in_stock')
            ->count();
        $onSaleProducts = Product::where('store_id', $store->id)
            ->where('is_ecommerce', true)
            ->whereNotNull('old_price')
            ->whereColumn('old_price', '>', 'retail_price')
            ->count();

        $activeOnlineCategories = Category::where('store_id', $store->id)
            ->whereHas('products', fn ($q) => $q->where('is_ecommerce', true))
            ->count();

        return [
            'total_products'         => $totalProducts,
            'online_products'        => $onlineProducts,
            'counter_only_products'  => $counterOnlyProducts,
            'featured_products'      => $featuredProducts,
            'online_in_stock'        => $onlineInStock,
            'on_sale_products'       => $onSaleProducts,
            'online_categories'      => $activeOnlineCategories,
        ];
    }

    /**
     * Get paginated products with filtering for Web Catalog management.
     */
    public function getProducts(Store $store, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::where('store_id', $store->id)
            ->with(['category', 'brand', 'variants']);

        // Search by keyword (name, sku, brand, category)
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('variants', fn ($vq) => $vq->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            });
        }

        // Filter by visibility (all / online / counter_only)
        if (!empty($filters['visibility'])) {
            if ($filters['visibility'] === 'online') {
                $query->where('is_ecommerce', true);
            } elseif ($filters['visibility'] === 'counter_only') {
                $query->where('is_ecommerce', false);
            }
        }

        // Filter by featured
        if (!empty($filters['featured'])) {
            if ($filters['featured'] === 'featured') {
                $query->where('is_featured', true);
            } elseif ($filters['featured'] === 'standard') {
                $query->where('is_featured', false);
            }
        }

        // Filter by stock status
        if (!empty($filters['stock_status'])) {
            $query->where('stock_status', $filters['stock_status']);
        }

        // Filter by category
        if (!empty($filters['category_id'])) {
            $catId = (int) $filters['category_id'];
            $allCategories = Category::where('store_id', $store->id)->get();
            $childIds = $allCategories->where('parent_id', $catId)->pluck('id');
            $allIds = $childIds->push($catId)->unique()->values()->all();
            $query->whereIn('category_id', $allIds);
        }

        // Filter by brand
        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', (int) $filters['brand_id']);
        }

        // Filter by discount / on-sale
        if (!empty($filters['sale_status'])) {
            if ($filters['sale_status'] === 'on_sale') {
                $query->whereNotNull('old_price')->whereColumn('old_price', '>', 'retail_price');
            } elseif ($filters['sale_status'] === 'regular') {
                $query->where(function ($q) {
                    $q->whereNull('old_price')->orWhereColumn('old_price', '<=', 'retail_price');
                });
            }
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'oldest':
                $query->oldest('id');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'price_asc':
                $query->orderBy('retail_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('retail_price', 'desc');
                break;
            case 'online_first':
                $query->orderBy('is_ecommerce', 'desc')->latest('id');
                break;
            case 'counter_first':
                $query->orderBy('is_ecommerce', 'asc')->latest('id');
                break;
            case 'featured_first':
                $query->orderBy('is_featured', 'desc')->latest('id');
                break;
            case 'newest':
            default:
                $query->latest('id');
                break;
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Toggle the online storefront visibility (`is_ecommerce`).
     */
    public function toggleVisibility(Product $product, Store $store): bool
    {
        if ($product->store_id !== $store->id) {
            abort(403, 'Unauthorized store product.');
        }

        $newState = ! $product->is_ecommerce;
        $product->update(['is_ecommerce' => $newState]);

        return $newState;
    }

    /**
     * Toggle the featured on web status (`is_featured`).
     */
    public function toggleFeatured(Product $product, Store $store): bool
    {
        if ($product->store_id !== $store->id) {
            abort(403, 'Unauthorized store product.');
        }

        $newState = ! $product->is_featured;
        $product->update(['is_featured' => $newState]);

        return $newState;
    }

    /**
     * Bulk update visibility (`is_ecommerce`) for multiple products in store.
     */
    public function bulkSetVisibility(Store $store, array $productIds, bool $isEcommerce): int
    {
        return Product::where('store_id', $store->id)
            ->whereIn('id', $productIds)
            ->update(['is_ecommerce' => $isEcommerce]);
    }

    /**
     * Bulk update featured flag (`is_featured`) for multiple products in store.
     */
    public function bulkSetFeatured(Store $store, array $productIds, bool $isFeatured): int
    {
        return Product::where('store_id', $store->id)
            ->whereIn('id', $productIds)
            ->update(['is_featured' => $isFeatured]);
    }

    /**
     * Category breakdown for storefront catalog overview.
     */
    public function getCategoryBreakdown(Store $store): Collection
    {
        return Category::where('store_id', $store->id)
            ->withCount([
                'products as total_count',
                'products as online_count' => fn ($q) => $q->where('is_ecommerce', true),
                'products as counter_count' => fn ($q) => $q->where('is_ecommerce', false),
            ])
            ->orderBy('name')
            ->get();
    }
}
