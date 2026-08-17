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

        abort_unless($store, 404, 'Store not found.');

        // Only categories that actually have products are shown in the
        // storefront — empty ones clutter the browser for customers. The
        // counts count ONLINE products only (counter-only items do not
        // advertise a category).
        $allCategories = Category::where('store_id', $store->id)
            ->withCount(['products' => fn ($q) => $q->where('is_ecommerce', true)])
            ->get();
        $withProducts = $allCategories
            ->filter(fn (Category $category) => $category->products_count > 0)
            ->values();

        // Products grouped by their direct category — the source for the
        // "brands under this category" strip (brands of the main + its subs).
        // Wrap in a base collection: Eloquent's Collection::only() treats keys as
        // model keys, which breaks on grouped (non-model) items.
        $categoryBrands = collect(Product::where('store_id', $store->id)
            ->where('is_ecommerce', true)
            ->whereNotNull('brand_id')
            ->whereNotNull('category_id')
            ->select(['id', 'brand_id', 'category_id'])
            ->with('brand')
            ->get())
            ->groupBy('category_id');

        $browseRows = $allCategories
            ->whereNull('parent_id')
            ->map(function (Category $main) use ($withProducts, $categoryBrands) {
                $children = $withProducts
                    ->where('parent_id', $main->id)
                    ->sortBy('name')
                    ->values();

                $catIds = $children->pluck('id')->push($main->id)->all();
                $brands = $categoryBrands
                    ->filter(fn ($items, $catId) => in_array($catId, $catIds))
                    ->flatten(1)
                    ->groupBy(fn (Product $product) => $product->brand_id)
                    ->map(fn ($items) => [
                        'brand' => $items->first()->brand,
                        'count' => $items->count(),
                    ])
                    ->filter(fn ($row) => $row['brand'] !== null)
                    ->sortByDesc('count')
                    ->values();

                return (object) [
                    'category' => $main,
                    'children' => $children,
                    'brands' => $brands,
                    'total' => $main->products_count + $children->sum('products_count'),
                ];
            })
            ->filter(fn ($row) => $row->category->products_count > 0 || $row->children->isNotEmpty())
            ->sortByDesc('total')
            ->values();

        return view("storefront.browse.index", compact("store", "browseRows"));
    }
}
