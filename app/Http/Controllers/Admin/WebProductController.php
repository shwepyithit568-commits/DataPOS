<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\POS\Services\WebCatalogService;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebProductController extends Controller
{
    public function __construct(
        protected WebCatalogService $webCatalogService
    ) {
    }

    /**
     * Web Catalog & Storefront Visibility Dashboard.
     */
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $perPage = $request->input('per_page', 50);
        if ($perPage === 'all') {
            $perPage = 100000;
        } else {
            $perPage = (int) $perPage;
            if (! in_array($perPage, [20, 50, 100, 200, 100000], true)) {
                $perPage = 50;
            }
        }

        $stats = $this->webCatalogService->getSummaryStats($store);
        $products = $this->webCatalogService->getProducts($store, $request->all(), $perPage);

        // Category tree for hierarchical filters and active pills
        $allCategories = Category::where('store_id', $store->id)->get();
        $categoryTree = $allCategories
            ->whereNull('parent_id')
            ->map(fn ($main) => (object) [
                'category' => $main,
                'children' => $allCategories->where('parent_id', $main->id)->values(),
            ])
            ->values();

        $categories = [];
        $categoryGroups = [];
        foreach ($categoryTree as $row) {
            $categories[$row->category->id] = $row->category->name;
            $groupOptions = [$row->category->id => 'All in ' . $row->category->name];
            foreach ($row->children as $child) {
                $categories[$child->id] = $child->name;
                $groupOptions[$child->id] = $child->name;
            }
            $categoryGroups[$row->category->id] = ['label' => $row->category->name, 'options' => $groupOptions];
        }

        $brands = Brand::where('store_id', $store->id)->pluck('name', 'id')->toArray();
        $categoryBreakdown = $this->webCatalogService->getCategoryBreakdown($store);

        return view('admin.web_products.index', compact(
            'store',
            'stats',
            'products',
            'categories',
            'categoryGroups',
            'brands',
            'categoryBreakdown'
        ));
    }

    /**
     * Toggle a single product's online storefront visibility (`is_ecommerce`).
     */
    public function toggleVisibility(Request $request, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $request->validate([
            'product_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('products', 'id')->where('store_id', $store->id)],
        ]);

        $product = Product::where('store_id', $store->id)->findOrFail($request->product_id);
        $newState = $this->webCatalogService->toggleVisibility($product, $store);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'      => true,
                'is_ecommerce' => $newState,
                'message'      => $newState
                    ? __('messages.web_catalog_status_online')
                    : __('messages.web_catalog_status_counter'),
            ]);
        }

        return back()->with('success', __('messages.web_catalog_updated_msg', [
            'name'   => $product->name,
            'status' => $newState ? 'Online Storefront' : 'In-Store / Counter Only',
        ]));
    }

    /**
     * Toggle a single product's featured status on web storefront (`is_featured`).
     */
    public function toggleFeatured(Request $request, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $request->validate([
            'product_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('products', 'id')->where('store_id', $store->id)],
        ]);

        $product = Product::where('store_id', $store->id)->findOrFail($request->product_id);
        $newState = $this->webCatalogService->toggleFeatured($product, $store);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'     => true,
                'is_featured' => $newState,
                'message'     => $newState
                    ? __('messages.web_catalog_status_featured')
                    : 'Removed from Featured',
            ]);
        }

        return back()->with('success', __('messages.web_catalog_updated_msg', [
            'name'   => $product->name,
            'status' => $newState ? 'Featured on Web' : 'Standard',
        ]));
    }

    /**
     * Bulk update online visibility for selected products.
     */
    public function bulkVisibility(Request $request, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $request->validate([
            'ids'          => ['required', 'array', 'min:1', 'max:500'],
            'ids.*'        => ['integer'],
            'is_ecommerce' => ['required', 'boolean'],
        ]);

        $count = $this->webCatalogService->bulkSetVisibility(
            $store,
            $validated['ids'],
            $request->boolean('is_ecommerce')
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'count'   => $count,
                'message' => __('messages.web_catalog_bulk_updated_msg', ['count' => $count]),
            ]);
        }

        return back()->with('success', __('messages.web_catalog_bulk_updated_msg', ['count' => $count]));
    }

    /**
     * Bulk update featured flag for selected products.
     */
    public function bulkFeatured(Request $request, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $request->validate([
            'ids'         => ['required', 'array', 'min:1', 'max:500'],
            'ids.*'       => ['integer'],
            'is_featured' => ['required', 'boolean'],
        ]);

        $count = $this->webCatalogService->bulkSetFeatured(
            $store,
            $validated['ids'],
            $request->boolean('is_featured')
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'count'   => $count,
                'message' => __('messages.web_catalog_bulk_updated_msg', ['count' => $count]),
            ]);
        }

        return back()->with('success', __('messages.web_catalog_bulk_updated_msg', ['count' => $count]));
    }
}
