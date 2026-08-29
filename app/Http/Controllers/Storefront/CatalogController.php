<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request, StoreContext $context): mixed
    {
        $store = $context->getStore();

        abort_unless((bool) $store, 404, 'Store not found.');

        if ($store->isPosOnly()) {
            if (auth()->check() && $store->users()->where('users.id', auth()->id())->exists()) {
                return redirect()->route('pos.index', ['store_slug' => $store->slug]);
            }

            return redirect()->route('storefront.store.home', ['store_slug' => $store->slug]);
        }

        // Only categories/brands that actually have products are shown in the
        // storefront filter — empty ones clutter the list for customers.
        $allCategories = Category::where('store_id', $store->id)
            ->withCount('products')
            ->get();
        $categories = $allCategories
            ->filter(fn (Category $category) => $category->products_count > 0)
            ->values();

        // Main → Sub tree for the nested storefront filters: a main category is
        // listed when it (or any of its sub-categories) has products; its
        // children are only the sub-categories that actually carry products.
        $withProducts = $categories;
        $categoryTree = $allCategories
            ->whereNull('parent_id')
            ->map(function (Category $main) use ($withProducts) {
                $children = $withProducts
                    ->where('parent_id', $main->id)
                    ->sortBy('name')
                    ->values();

                return (object) [
                    'category' => $main,
                    'children' => $children,
                    'total' => $main->products_count + $children->sum('products_count'),
                ];
            })
            ->filter(fn ($row) => $row->category->products_count > 0 || $row->children->isNotEmpty())
            ->values();

        $brands = Brand::where('store_id', $store->id)
            ->withCount('products')
            ->get()
            ->filter(fn (Brand $brand) => $brand->products_count > 0)
            ->values();

        $query = Product::where('store_id', $store->id)
            ->where('is_ecommerce', true)
            ->with(['category', 'brand', 'images', 'variants']);

        // Search by keyword or SKU
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        // Filter by Category (ID or Name). A parent category also matches its
        // sub-categories, so browsing "Spare Part" shows every child's items.
        if ($request->filled('category_id')) {
            $categoryId = (int) $request->category_id;
            $childIds = Category::where('parent_id', $categoryId)->pluck('id');
            $query->where(function ($q) use ($categoryId, $childIds) {
                $q->where('category_id', $categoryId);
                if ($childIds->isNotEmpty()) {
                    $q->orWhereIn('category_id', $childIds);
                }
            });
        } elseif ($request->filled('category')) {
            $catName = $request->category;
            $query->whereHas('category', function ($q) use ($catName) {
                $q->where('name', $catName);
            });
        }

        // Filter by Brand (ID or Name)
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        } elseif ($request->filled('brand')) {
            $brandName = $request->brand;
            $query->whereHas('brand', function ($q) use ($brandName) {
                $q->where('name', $brandName);
            });
        }

        // Filter by Stock Status
        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->stock_status);
        }

        // Price Range Filter (Ks) — Linn-style "Price" filter box
        if ($request->filled('min_price')) {
            $query->where('retail_price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('retail_price', '<=', (float) $request->max_price);
        }

        // Sort — mirrors Linn's "Release (New to Old) / Price (Low to High) / Price (High to Low)"
        $sort = $request->input('sort', 'newest');
        $query->when($sort === 'price_low_high', function ($q) {
            return $q->orderBy('retail_price', 'asc');
        }, function ($q) use ($sort) {
            return $sort === 'price_high_low'
                ? $q->orderBy('retail_price', 'desc')
                : $q->latest();
        });

        // Per-page selector (toolbar): 40 / 80 / 120 / All. Anything else falls back to 40.
        $perPage = match ($request->input('per_page')) {
            '80' => 80,
            '120' => 120,
            'all' => PHP_INT_MAX,
            default => 40,
        };

        $products = $query->paginate($perPage)->withQueryString();

        // Overall product count for the store (used by the "All Categories" sidebar row)
        $totalProducts = Product::where('store_id', $store->id)->where('is_ecommerce', true)->count();
        $user = auth()->user();

        // Check if current user is an approved wholesale customer for this active store
        $isWholesaleApproved = $user && (
            $user->isPlatformOwner() ||
            $user->getStoreRole($store->id) === 'wholesale_customer'
        );

        // Active category/brand for sidebar highlight + breadcrumb (Linn-style)
        $activeCategory = null;
        if ($request->filled('category_id')) {
            $activeCategory = $categories->firstWhere('id', (int) $request->category_id);
        } elseif ($request->filled('category')) {
            $activeCategory = $categories->firstWhere('name', $request->category);
        }

        $activeBrand = null;
        if ($request->filled('brand_id')) {
            $activeBrand = $brands->firstWhere('id', (int) $request->brand_id);
        } elseif ($request->filled('brand')) {
            $activeBrand = $brands->firstWhere('name', $request->brand);
        }

        return view('storefront.catalog.index', compact(
            'store',
            'products',
            'categories',
            'categoryTree',
            'brands',
            'isWholesaleApproved',
            'activeCategory',
            'activeBrand',
            'sort',
            'totalProducts'
        ));
    }

    /**
     * Live search suggestions (name + price) for the mobile search bar.
     * Returns a small JSON payload scoped to the active store.
     */
    public function suggestions(Request $request, StoreContext $context): \Illuminate\Http\JsonResponse
    {
        $store = $context->getStore();

        // No active store (e.g. a fresh install before any store is configured):
        // the search bar fires this endpoint on page load to preload the trending
        // chips, so answer with an empty payload instead of a 404 that only adds
        // console noise. An explicitly requested unknown store slug is still
        // rejected earlier by the ResolveStoreContext middleware (404).
        if (! $store || ! $store->hasCapability('storefront.ecommerce')) {
            return response()->json([
                'trending' => [],
                'categories' => [],
                'brands' => [],
                'products' => [],
            ]);
        }

        $search = trim((string) $request->input('search', ''));

        if ($search === '') {
            // Trending searches: most popular categories & brands (by product
            // count) shown as chips before the user types anything. Tapping a
            // chip fills the search box with its label.
            $trendingCategories = Category::where('store_id', $store->id)
                ->whereHas('products')
                ->withCount('products')
                ->orderByDesc('products_count')
                ->limit(4)
                ->get(['id', 'name', 'slug']);

            $trendingBrands = Brand::where('store_id', $store->id)
                ->whereHas('products')
                ->withCount('products')
                ->orderByDesc('products_count')
                ->limit(4)
                ->get(['id', 'name', 'slug']);

            $storeSlugQuery = $store->slug ? '&store_slug=' . $store->slug : '';

            $trending = collect()
                ->concat($trendingCategories->map(fn (Category $category) => [
                    'type' => 'category',
                    'label' => $category->name,
                    'url' => url('/products?category_id=' . $category->id . $storeSlugQuery),
                ]))
                ->concat($trendingBrands->map(fn (Brand $brand) => [
                    'type' => 'brand',
                    'label' => $brand->name,
                    'url' => url('/products?brand_id=' . $brand->id . $storeSlugQuery),
                ]))
                ->values();

            return response()->json([
                'trending' => $trending,
                'categories' => [],
                'brands' => [],
                'products' => [],
            ]);
        }

        // whereLike escapes LIKE wildcards (%, _) so a literal "%" in the query
        // doesn't match every product in the store. Only categories/brands that
        // actually carry products are suggested.
        $categories = Category::where('store_id', $store->id)
            ->whereLike('name', '%' . $search . '%')
            ->whereHas('products')
            ->withCount('products')
            ->orderByDesc('products_count')
            ->limit(3)
            ->get(['id', 'name', 'slug', 'icon']);

        $brands = Brand::where('store_id', $store->id)
            ->whereLike('name', '%' . $search . '%')
            ->whereHas('products')
            ->withCount('products')
            ->orderByDesc('products_count')
            ->limit(3)
            ->get(['id', 'name', 'slug']);

        $products = Product::where('store_id', $store->id)
            ->where('is_ecommerce', true)
            ->where(function ($q) use ($search) {
                $q->whereLike('name', '%' . $search . '%')
                  ->orWhereLike('sku', '%' . $search . '%');
            })
            ->orderByRaw("CASE WHEN stock_status = 'in_stock' THEN 0 ELSE 1 END")
            ->latest()
            ->limit(8)
            ->get(['id', 'name', 'slug', 'retail_price', 'old_price', 'image_path', 'stock_status']);

        $storeSlugQuery = $store->slug ? '&store_slug=' . $store->slug : '';

        return response()->json([
            'trending' => [],
            'categories' => $categories->map(function (Category $category) use ($storeSlugQuery) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'count' => (int) $category->products_count,
                    'icon' => $category->icon,
                    'url' => url('/products?category_id=' . $category->id . $storeSlugQuery),
                ];
            })->values(),
            'brands' => $brands->map(function (Brand $brand) use ($storeSlugQuery) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'count' => (int) $brand->products_count,
                    'url' => url('/products?brand_id=' . $brand->id . $storeSlugQuery),
                ];
            })->values(),
            'products' => $products->map(function (Product $product) use ($store) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => 'Ks ' . number_format((float) $product->retail_price),
                    'old_price' => $product->old_price !== null
                        ? 'Ks ' . number_format((float) $product->old_price)
                        : null,
                    'image' => $product->image_path ? asset('storage/' . $product->image_path) : null,
                    'url' => url('/store/' . $store->slug . '/product/' . $product->slug),
                    'stock_status' => $product->stock_status,
                ];
            })->values(),
        ]);
    }

    public function show($store_slug, $slug, StoreContext $context): View
    {
        $store = $context->getStore();

        abort_unless((bool) $store, 404, 'Store not found.');

        $product = Product::where('store_id', $store->id)
            ->where('slug', $slug)
            ->with(['category', 'brand', 'images', 'variants'])
            ->firstOrFail();

        $user = auth()->user();

        $isWholesaleApproved = $user && (
            $user->isPlatformOwner() ||
            $user->getStoreRole($store->id) === 'wholesale_customer'
        );

        // Product detail page has its own sticky action bar — hide the shared floating FABs there.
        $hideFloatingFabs = true;

        // Related products: same category or brand, excluding the current one.
        $related = Product::where('store_id', $store->id)
            ->where('is_ecommerce', true)
            ->where('id', '!=', $product->id)
            ->where(fn ($q) => $q->where('category_id', $product->category_id)->orWhere('brand_id', $product->brand_id))
            ->latest()
            ->limit(4)
            ->with(['category', 'brand', 'variants'])
            ->get();

        // Approved customer reviews for the product + summary.
        $reviews = $product->approvedReviews()->take(20)->get();
        $avgRating = $reviews->count() > 0
            ? round($reviews->avg('rating'), 1)
            : null;

        return view('storefront.catalog.show', compact('store', 'product', 'isWholesaleApproved', 'hideFloatingFabs', 'related', 'reviews', 'avgRating'));
    }
}
