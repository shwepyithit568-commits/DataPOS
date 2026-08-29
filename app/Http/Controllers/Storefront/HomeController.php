<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Render the storefront homepage for the current active store context.
     */
    public function index(Request $request, StoreContext $context): mixed
    {
        $store = $context->getStore();
        $setting = $store?->setting;

        if ($store && $store->isPosOnly()) {
            if (auth()->check() && $store->users()->where('users.id', auth()->id())->exists()) {
                return redirect()->route('pos.index', ['store_slug' => $store->slug]);
            }

            return view('storefront.pos_only', compact('store', 'setting'));
        }

        $banners = $store?->homeBanners()->where('page', 'home')->where('is_active', true)->get() ?? collect();

        // Only categories with products show on the storefront (empty ones are hidden)
        $allCategories = $store
            ? Category::where('store_id', $store->id)
                ->withCount(['products' => fn ($q) => $q->where('is_ecommerce', true)])
                ->get()
            : collect();

        $categories = $allCategories->filter(fn ($category) => $category->products_count > 0)->values();
        $mainCategoryIds = $allCategories->whereNull('parent_id')->pluck('id');

        // Representative cover photo per main category
        $coverByCategory = $mainCategoryIds->isNotEmpty()
            ? Product::whereIn('category_id', $mainCategoryIds)
                ->where('is_ecommerce', true)
                ->select('id', 'category_id', 'image_path')
                ->with(['variants' => fn ($v) => $v->whereNotNull('image_path')->where('image_path', '!=', '')])
                ->where(fn ($q) => $q->whereNotNull('image_path')->where('image_path', '!=', '')
                    ->orWhereHas('variants', fn ($v) => $v->whereNotNull('image_path')->where('image_path', '!=', '')))
                ->orderByDesc('is_featured')
                ->orderByDesc('id')
                ->get()
                ->unique('category_id')
                ->mapWithKeys(fn ($p) => [$p->category_id => $p->variants->first()?->image_path ?: $p->image_path])
                ->all()
            : [];

        $categoryTree = $allCategories
            ->whereNull('parent_id')
            ->map(function ($main) use ($categories, $coverByCategory) {
                $children = $categories
                    ->where('parent_id', $main->id)
                    ->sortByDesc('products_count')
                    ->values();
                return (object) [
                    'category' => $main,
                    'children' => $children,
                    'total' => $main->products_count + $children->sum('products_count'),
                    'cover' => $coverByCategory[$main->id] ?? null,
                ];
            })
            ->filter(fn ($row) => $row->category->products_count > 0 || $row->children->isNotEmpty())
            ->sortByDesc('total')
            ->values();

        $featuredProducts = $store
            ? Product::where('store_id', $store->id)
                ->where('is_ecommerce', true)
                ->where('is_featured', true)
                ->where('stock_status', 'in_stock')
                ->with(['category', 'brand', 'variants'])
                ->take(10)
                ->get()
            : collect();

        $newArrivals = $store
            ? Product::where('store_id', $store->id)
                ->where('is_ecommerce', true)
                ->where('stock_status', 'in_stock')
                ->with(['category', 'brand', 'variants'])
                ->latest()
                ->take(10)
                ->get()
            : collect();

        // Flash-sale deals: active windows first, then scheduled ones
        $now = now();
        $flashSales = $store
            ? Product::where('store_id', $store->id)
                ->where('is_ecommerce', true)
                ->whereNotNull('old_price')
                ->whereColumn('old_price', '>', 'retail_price')
                ->where('stock_status', 'in_stock')
                ->where(fn ($q) => $q->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', $now))
                ->where(fn ($q) => $q->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>=', $now))
                ->with(['category', 'brand', 'variants'])
                ->orderByRaw('sale_ends_at IS NULL, sale_ends_at')
                ->orderBy('retail_price')
                ->get()
            : collect();

        $upcomingSales = $store
            ? Product::where('store_id', $store->id)
                ->where('is_ecommerce', true)
                ->whereNotNull('old_price')
                ->whereColumn('old_price', '>', 'retail_price')
                ->where('stock_status', 'in_stock')
                ->where('sale_starts_at', '>', $now)
                ->with(['category', 'brand', 'variants'])
                ->orderBy('sale_starts_at')
                ->get()
            : collect();

        $activeTarget = $flashSales->pluck('sale_ends_at')->filter()->min();
        $upcomingTarget = $upcomingSales->pluck('sale_starts_at')->filter()->min();
        if ($upcomingTarget && (! $activeTarget || $upcomingTarget->lt($activeTarget))) {
            $flashTarget = $upcomingTarget;
            $flashTargetStarts = true;
        } else {
            $flashTarget = $activeTarget;
            $flashTargetStarts = false;
        }

        return view('welcome', compact(
            'store',
            'setting',
            'banners',
            'categories',
            'categoryTree',
            'featuredProducts',
            'newArrivals',
            'flashSales',
            'upcomingSales',
            'flashTarget',
            'flashTargetStarts'
        ));
    }
}
