<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrowseController extends Controller
{
    /**
     * AliExpress-style two-pane category browser:
     *
     * - Left rail: main categories (vertical scroll).
     * - Right panel: the brands (horizontal strip) and sub-categories of the
     *   selected main category.
     *
     * Sub-category / brand tiles deep-link into the 1-column product list on
     * the catalog page (?view=list).
     */
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();

        abort_unless($store !== null, 404, 'Store not found.');

        // Load all categories configured in Admin Master Data Category Tab with online product counts
        $allCategories = Category::where('store_id', $store->id)
            ->withCount(['products' => fn ($q) => $q->where('is_ecommerce', true)])
            ->orderBy('name')
            ->get();

        // Products grouped by direct category for brands strip
        $categoryBrands = collect(Product::where('store_id', $store->id)
            ->where('is_ecommerce', true)
            ->whereNotNull('brand_id')
            ->whereNotNull('category_id')
            ->select(['id', 'brand_id', 'category_id', 'image_path'])
            ->with('brand')
            ->get())
            ->groupBy('category_id');

        $browseRows = $allCategories
            ->whereNull('parent_id')
            ->map(function (Category $main) use ($allCategories, $categoryBrands) {
                $children = $allCategories
                    ->where('parent_id', $main->id)
                    ->sortBy('name')
                    ->values();

                $total = $main->products_count + $children->sum('products_count');

                $catIds = $children->pluck('id')->push($main->id)->all();
                $brands = $categoryBrands
                    ->filter(fn ($items, $catId) => in_array($catId, $catIds))
                    ->flatten(1)
                    ->groupBy(fn (Product $product) => $product->brand_id)
                    ->map(function ($items) {
                        $first = $items->first();
                        $imagePath = $first->brand?->logo_path ?? $first->image_path;
                        return [
                            'brand' => $first->brand,
                            'image' => $imagePath ? asset('storage/' . $imagePath) : null,
                            'count' => $items->count(),
                        ];
                    })
                    ->filter(fn ($row) => $row['brand'] !== null)
                    ->sortByDesc('count')
                    ->values();

                return (object) [
                    'category' => $main,
                    'children' => $children,
                    'brands' => $brands,
                    'total' => $total,
                ];
            })
            ->values();

        $ecommerceProducts = Product::where('store_id', $store->id)
            ->where('is_ecommerce', true)
            ->with(['brand', 'category'])
            ->get();

        return view('storefront.browse.index', [
            'store' => $store,
            'browseRows' => $browseRows,
            'products' => $ecommerceProducts,
        ]);
    }
}
