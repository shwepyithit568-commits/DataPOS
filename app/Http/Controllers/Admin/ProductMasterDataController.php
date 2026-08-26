<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\VariantPreset;
use App\Services\StoreContext;
use App\Support\AdminListReturn;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Products Master Data" hub (alinthit_pos master-data style): one page with
 * horizontal scroll tabs — Categories / Brands / Variant Settings. Each tab
 * embeds the SAME content partial the standalone index page uses, so the hub
 * cannot drift from the real lists.
 *
 * The tab lives in the URL (?tab=...) so refresh/back keep the tab, and the
 * partials build their internal URLs from the current request (toolbar /
 * paginator), so search / filter / sort / pagination stay on this page.
 * AdminListReturn capture per tab makes create/edit round-trips land back on
 * this page at the same tab (the standalone controllers already redirect via
 * AdminListReturn / back()).
 */
class ProductMasterDataController extends Controller
{
    protected const TABS = [
        'categories',
        'brands',
        'connectors',
        'colors',
        'shelves',
        'warranties',
        'return-policies',
        'variant-presets',
    ];

    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        $storeId = $store->id;

        $activeTab = $request->query('tab', 'categories');
        if (! in_array($activeTab, self::TABS, true)) {
            $activeTab = 'categories';
        }

        $summary = $this->summaryStats($storeId);

        $data = match ($activeTab) {
            'brands' => $this->brandsData($request, $storeId),
            'variant-presets' => $this->presetsData($request, $storeId),
            'connectors' => $this->masterPresetData($request, $storeId, 'connector_spec', 'connectors'),
            'colors' => $this->masterPresetData($request, $storeId, 'color', 'colors'),
            'shelves' => $this->masterPresetData($request, $storeId, 'shelf_location', 'shelves'),
            'warranties' => $this->masterPresetData($request, $storeId, 'warranty', 'warranties'),
            'return-policies' => $this->masterPresetData($request, $storeId, 'return_policy', 'return-policies'),
            default => $this->categoriesData($request, $storeId),
        };

        return view('admin.master_data.index', array_merge([
            'store' => $store,
            'activeTab' => $activeTab,
            'summary' => $summary,
            'embedded' => true,
        ], $data));
    }

    /**
     * Cross-tab aggregate summary counts for the top stat cards.
     */
    private function summaryStats(int $storeId): array
    {
        $categoriesCount = Category::where('store_id', $storeId)->count();
        $brandsCount = Brand::where('store_id', $storeId)->count();
        $productsCount = Product::where('store_id', $storeId)->count();
        
        $connectorsCount = \App\Models\ProductMasterPreset::where('store_id', $storeId)->where('type', 'connector_spec')->count();
        $colorsCount = \App\Models\ProductMasterPreset::where('store_id', $storeId)->where('type', 'color')->count();
        $shelvesCount = \App\Models\ProductMasterPreset::where('store_id', $storeId)->where('type', 'shelf_location')->count();
        $warrantiesCount = \App\Models\ProductMasterPreset::where('store_id', $storeId)->where('type', 'warranty')->count();
        $returnPoliciesCount = \App\Models\ProductMasterPreset::where('store_id', $storeId)->where('type', 'return_policy')->count();

        $presets = VariantPreset::where('store_id', $storeId)
            ->select('id', 'options')
            ->get();
        $presetsCount = $presets->count();
        $presetsTotalRows = $presets->sum(
            fn (VariantPreset $p) => is_countable($p->options ?? []) ? count($p->options ?? []) : 0
        );

        return [
            'categories' => $categoriesCount,
            'brands' => $brandsCount,
            'connectors' => $connectorsCount,
            'colors' => $colorsCount,
            'shelves' => $shelvesCount,
            'warranties' => $warrantiesCount,
            'return_policies' => $returnPoliciesCount,
            'presets' => $presetsCount,
            'products' => $productsCount,
            'presets_total_rows' => $presetsTotalRows,
        ];
    }

    /**
     * Master Presets data for a specific type.
     */
    private function masterPresetData(Request $request, int $storeId, string $type, string $tabName): array
    {
        $search = trim((string) $request->query('search', ''));

        $query = \App\Models\ProductMasterPreset::where('store_id', $storeId)->where('type', $type);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $presets = $query->orderBy('sort_order')->orderBy('code')->orderBy('name')->paginate(50)->withQueryString();

        return [
            'presetList' => $presets,
            'presetType' => $type,
            'tabName' => $tabName,
            'search' => $search,
        ];
    }

    /**
     * Category tree data — mirrors CategoryController@index so the tab shows
     * the identical tree, counts, search/filter and highlight behaviour.
     */
    private function categoriesData(Request $request, int $storeId): array
    {
        $full = Category::where('store_id', $storeId)
            ->with('parent')
            ->withCount(['products', 'children'])
            ->get();

        $parentProductTotals = [];
        foreach ($full as $category) {
            $parentProductTotals[$category->id] = ($parentProductTotals[$category->id] ?? 0) + $category->products_count;
            if ($category->parent_id) {
                $parentProductTotals[$category->parent_id] = ($parentProductTotals[$category->parent_id] ?? 0) + $category->products_count;
            }
        }

        $all = $full;
        $matchingCount = null;

        if ($request->filled('search')) {
            $needle = mb_strtolower(trim((string) $request->search));
            $matched = $full->filter(fn (Category $category) => str_contains(mb_strtolower($category->name), $needle)
                || str_contains(mb_strtolower($category->slug), $needle));

            $includedIds = $matched->pluck('id')->toBase();
            foreach ($matched as $category) {
                if ($category->parent_id === null) {
                    $includedIds = $includedIds->merge($full->where('parent_id', $category->id)->pluck('id'));
                } else {
                    $includedIds->push($category->parent_id);
                }
            }

            $all = $full->whereIn('id', $includedIds->unique()->values())->values();
            $matchingCount = $matched->count();
        }

        if ($request->filled('has_image')) {
            $matched = $full->filter(fn (Category $category) => $request->has_image === 'with'
                ? $category->image_path !== null
                : $category->image_path === null);

            $includedIds = $matched->pluck('id')->toBase();
            foreach ($matched as $category) {
                if ($category->parent_id === null) {
                    $includedIds = $includedIds->merge($full->where('parent_id', $category->id)->pluck('id'));
                } else {
                    $includedIds->push($category->parent_id);
                }
            }

            $all = $full->whereIn('id', $includedIds->unique()->values())->values();
        }

        $parents = $all->whereNull('parent_id')->sortBy('name')->values();
        $children = $all->whereNotNull('parent_id')->sortBy('name')->groupBy('parent_id');

        $highlightId = session('highlight_category');
        $highlightParentId = null;
        if ($highlightId) {
            $highlighted = Category::find((int) $highlightId);
            $highlightParentId = $highlighted ? ($highlighted->parent_id ?? $highlighted->id) : null;
        }

        AdminListReturn::capture($request, 'admin_categories_return');

        return [
            'parents' => $parents,
            'children' => $children,
            'parentProductTotals' => $parentProductTotals,
            'totalCount' => $all->count(),
            'matchingCount' => $matchingCount,
            'hasNoCategories' => $full->count() === 0,
            'autoOpen' => $request->filled('search') || $request->filled('has_image'),
            'imageMaxMb' => CategoryController::IMAGE_MAX_KB / 1024,
            'highlightParentId' => $highlightParentId,
        ];
    }

    /**
     * Brand list data — mirrors BrandController@index (search, logo filter,
     * sort, pagination) so the tab behaves exactly like the standalone page.
     */
    private function brandsData(Request $request, int $storeId): array
    {
        $query = Brand::where('store_id', $storeId)->withCount('products');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('has_logo')) {
            if ($request->has_logo === 'with') {
                $query->whereNotNull('logo_path');
            } elseif ($request->has_logo === 'without') {
                $query->whereNull('logo_path');
            }
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest'        => $query->oldest(),
            'name_asc'      => $query->orderBy('name', 'asc'),
            'name_desc'     => $query->orderBy('name', 'desc'),
            'most_products' => $query->orderBy('products_count', 'desc'),
            default         => $query->latest(),
        };

        $perPageRequested = (int) $request->query('per_page', 25);
        $perPage = in_array($perPageRequested, BrandController::ALLOWED_PER_PAGE, true) ? $perPageRequested : 25;

        $brands = $query->paginate($perPage)->withQueryString();

        AdminListReturn::capture($request, 'admin_brands_return');

        return [
            'brands' => $brands,
            'totalCount' => $brands->total(),
            'imageMaxMb' => BrandController::IMAGE_MAX_KB / 1024,
        ];
    }

    private function presetsData(Request $request, int $storeId): array
    {
        AdminListReturn::capture($request, 'admin_variant_presets_return');

        return [
            'presets' => VariantPreset::where('store_id', $storeId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ];
    }
}
