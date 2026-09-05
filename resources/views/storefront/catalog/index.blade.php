@extends('layouts.storefront.app')

@section('content')
<script nonce="{{ $cspNonce }}">
    // Remember the customer's grid/list view choice. When the catalog is
    // opened without an explicit ?view= param, restore the saved preference
    // (grid is the default, so only 'list' needs a redirect).
    (function () {
        try {
            var stored = localStorage.getItem('catalog_view');
            if (!stored || stored === 'grid') return;
            if (new URLSearchParams(window.location.search).get('view')) return;
            var u = new URL(window.location.href);
            u.searchParams.set('view', stored);
            window.location.replace(u.toString());
        } catch (e) {}
    })();
</script>
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $baseUrl = url('/products');
    $params = request()->query();

    // Link builder that preserves current filters and merges overrides.
    $buildLink = function (array $overrides = []) use ($baseUrl, $params) {
        $merged = array_merge($params, $overrides);
        $merged = array_filter($merged, fn ($v) => $v !== null && $v !== '');
        return $merged ? $baseUrl . '?' . http_build_query($merged) : $baseUrl;
    };

    $hasActiveFilters = request()->anyFilled([
        'search', 'category_id', 'category', 'brand_id', 'brand',
        'stock_status', 'min_price', 'max_price',
    ]);

    // Per-page selector state: only 40/80/120/all are valid; missing → 40.
    $perPageRaw = request('per_page');
    $perPageSel = in_array($perPageRaw, ['40', '80', '120'], true) ? $perPageRaw : ($perPageRaw === 'all' ? 'all' : '40');

    // Dense hairline grid (default) vs 1-column list (deep-linked from /browse).
    $viewMode = request('view') === 'list' ? 'list' : 'grid';

    $activeFilters = collect([]);
    if ($activeCategory) $activeFilters->push(['label' => $activeCategory->localized_name ?? $activeCategory->name, 'url' => $buildLink(['category_id' => null, 'category' => null])]);
    if ($activeBrand) $activeFilters->push(['label' => $activeBrand->name, 'url' => $buildLink(['brand_id' => null, 'brand' => null])]);
    if (request()->filled('min_price') || request()->filled('max_price')) {
        $activeFilters->push(['label' => format_currency((float) request('min_price', 0), $store) . ' – ' . format_currency((float) request('max_price', 0), $store), 'url' => $buildLink(['min_price' => null, 'max_price' => null])]);
    }
    if (request()->filled('stock_status')) {
        $activeFilters->push([
            'label' => request('stock_status') === 'in_stock' ? __('messages.in_stock') : __('messages.out_of_stock'),
            'url' => $buildLink(['stock_status' => null]),
        ]);
    }
    if (request()->filled('search')) {
        $activeFilters->push(['label' => '🔍 ' . request('search'), 'url' => $buildLink(['search' => null])]);
    }
@endphp

<div class="space-y-1 sm:space-y-1.5 lg:space-y-2">
    {{-- Page Header: title + sort (product_header row) --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 border border-slate-200/90 dark:border-slate-800/80 shadow-xl">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white font-outfit flex items-center gap-2.5">
                <span>{{ __('messages.product_list') }}</span>
                @if ($activeCategory)
                    <span class="inline-flex items-center gap-1 text-xs font-extrabold text-slate-800 dark:text-slate-100 bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-full border border-slate-200 dark:border-slate-700">
                        {{ $activeCategory->icon ?: '📦' }} {{ $activeCategory->localized_name ?? $activeCategory->name }}
                    </span>
                @endif
            </h1>

            {{-- Grid / List view toggle (visible on all screens) with 3D tactile buttons --}}
            <div class="flex items-center gap-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 p-1 shadow-xs shrink-0">
                <a href="{{ $buildLink(['view' => 'grid']) }}" data-catalog-view="grid" class="sf-btn-3d px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $viewMode === 'grid' ? 'active' : '' }}" title="{{ __('messages.view_grid') }}">▦ {{ __('messages.view_grid') }}</a>
                <a href="{{ $buildLink(['view' => 'list']) }}" data-catalog-view="list" class="sf-btn-3d px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $viewMode === 'list' ? 'active' : '' }}" title="{{ __('messages.view_list') }}">☰ {{ __('messages.view_list') }}</a>
            </div>

            {{-- Sort dropdown (Release / Price Low-High / Price High-Low) --}}
            <form method="GET" action="{{ $baseUrl }}" class="hidden lg:flex items-center gap-2 shrink-0">
                @foreach (request()->except(['sort', 'page', 'per_page']) as $key => $value)
                    @if (is_array($value))
                        @foreach ($value as $v)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}" />
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
                    @endif
                @endforeach
                <label for="sortSelect" class="text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                    {{ __('messages.sort_by') }}
                </label>
                <select
                    id="sortSelect"
                    name="sort"
                    data-auto-submit
                    class="px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 shadow-sm"
                >
                    <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>{{ __('messages.sort_newest') }}</option>
                    <option value="price_low_high" {{ $sort === 'price_low_high' ? 'selected' : '' }}>{{ __('messages.sort_price_low_high') }}</option>
                    <option value="price_high_low" {{ $sort === 'price_high_low' ? 'selected' : '' }}>{{ __('messages.sort_price_high_low') }}</option>
                </select>
                <label for="perPageSelect" class="text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                    {{ __('messages.per_page') }}
                </label>
                <select
                    id="perPageSelect"
                    name="per_page"
                    data-auto-submit
                    class="px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 shadow-sm"
                >
                    <option value="40" {{ $perPageSel === '40' ? 'selected' : '' }}>40</option>
                    <option value="80" {{ $perPageSel === '80' ? 'selected' : '' }}>80</option>
                    <option value="120" {{ $perPageSel === '120' ? 'selected' : '' }}>120</option>
                    <option value="all" {{ $perPageSel === 'all' ? 'selected' : '' }}>{{ __('messages.all') }}</option>
                </select>
            </form>
        </div>

        {{-- Active filter chips --}}
        @if ($activeFilters->count() > 0 || $hasActiveFilters)
            <div class="flex flex-wrap items-center gap-1.5 mt-3 pt-3 border-t border-slate-200/60 dark:border-slate-800/60">
                @foreach ($activeFilters as $chip)
                    <a href="{{ $chip['url'] }}" class="sf-btn-3d inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold transition group">
                        <span>{{ $chip['label'] }}</span>
                        <span class="text-slate-400 group-hover:text-rose-500 font-black">✕</span>
                    </a>
                @endforeach
                @if ($hasActiveFilters)
                    <a href="{{ $storeSlug ? url('/products?store_slug=' . $storeSlug) : url('/products') }}" class="sf-btn-3d-danger px-2.5 py-1 rounded-full text-xs font-bold transition">
                        {{ __('messages.clear_filters') }}
                    </a>
                @endif
            </div>
        @endif
    </div>

    {{-- Mobile & Tablet: compact filter toolbar (sidebar is hidden on < lg) --}}
    @php
        // Mobile Category picker (Main → Sub): chip label + which drill-down section to open
        $activeCatId = request('category_id');
        $activeCatName = null;
        $activeCatMainId = null;
        if ($activeCatId) {
            foreach ($categoryTree as $catRow) {
                if ((string) $catRow->category->id === (string) $activeCatId) {
                    $activeCatName = $catRow->category->name;
                    $activeCatMainId = $catRow->category->id;
                    break;
                }
                foreach ($catRow->children as $subCat) {
                    if ((string) $subCat->id === (string) $activeCatId) {
                        $activeCatName = $subCat->name;
                        $activeCatMainId = $catRow->category->id;
                        break 2;
                    }
                }
            }
            if ($activeCatMainId !== null && (string) $activeCatMainId === (string) $activeCatId) {
                $activeCatName = __('messages.all_in') . ' ' . $activeCatName;
            }
        }
    @endphp
    <form method="GET" action="{{ $baseUrl }}" id="mobileCatForm" class="lg:hidden bg-white dark:bg-slate-900 rounded-2xl p-3 border border-slate-200/90 dark:border-slate-800/80 shadow-xl space-y-2.5" x-data="{ priceOpen: {{ (request()->filled('min_price') || request()->filled('max_price')) ? 'true' : 'false' }} }">
        @if ($storeSlug)
            <input type="hidden" name="store_slug" value="{{ $storeSlug }}" />
        @endif
        {{-- Preserve other active params (category/brand name fallbacks, prices, etc.) across auto-submits --}}
        @foreach (request()->except(['store_slug', 'search', 'sort', 'category_id', 'brand_id', 'stock_status', 'min_price', 'max_price', 'page', 'per_page']) as $key => $value)
            @if (is_array($value))
                @foreach ($value as $v)
                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}" />
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
            @endif
        @endforeach

        {{-- Row 1: Search (full width) + Filter + Reset --}}
        <div class="flex items-center gap-2">
            <div class="relative flex-1 min-w-0">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search_name_or_sku') }}" class="w-full pl-9 pr-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 shadow-xs" />
            </div>
            <button type="submit" class="sf-btn-3d-primary shrink-0 px-4 py-2 text-sm font-bold rounded-xl shadow-sm">
                {{ __('messages.filter') }}
            </button>
            @if ($hasActiveFilters)
                <a href="{{ $storeSlug ? url('/products?store_slug=' . $storeSlug) : url('/products') }}" class="sf-btn-3d shrink-0 px-3 py-2 text-xs font-bold rounded-xl">
                    {{ __('messages.reset') }}
                </a>
            @endif
        </div>

        {{-- Row 2: filter chips (horizontal scroll) + Price toggle --}}
        <div x-data="{ isDown: false, startX: 0, scrollLeft: 0 }" @mousedown="isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft" @mouseleave="isDown = false" @mouseup="isDown = false" @mousemove="if(isDown){$event.preventDefault();const x=$event.pageX-$el.offsetLeft;const walk=(x-startX)*1.5;$el.scrollLeft=scrollLeft-walk}" class="overflow-x-auto whitespace-nowrap flex items-center gap-2 scrollbar-none pb-0.5 cursor-grab active:cursor-grabbing select-none">
            <div class="min-w-[140px] shrink-0">
                <button type="button" @click="$dispatch('cat-picker-open')"
                    class="sf-btn-3d !flex-row w-full flex items-center justify-between gap-1.5 px-3 py-2 text-sm font-bold {{ request()->filled('category_id') ? 'active' : '' }}">
                    <span class="flex items-center gap-1.5 min-w-0">
                        <span class="shrink-0">🗂️</span>
                        <span class="truncate">{{ $activeCatName ?? __('messages.all_categories') }}</span>
                    </span>
                    <svg class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
            <div class="min-w-[130px] shrink-0">
                <button type="button" @click="$dispatch('brand-picker-open')"
                    class="sf-btn-3d !flex-row w-full flex items-center justify-between gap-1.5 px-3 py-2 text-sm font-bold {{ request()->filled('brand_id') ? 'active' : '' }}">
                    <span class="flex items-center gap-1.5 min-w-0">
                        <span class="shrink-0">🏷️</span>
                        <span class="truncate">{{ $activeBrand->name ?? __('messages.all_brands') }}</span>
                    </span>
                    <svg class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
            <div class="min-w-[130px] shrink-0">
                <select name="stock_status" data-auto-submit class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 shadow-xs">
                    <option value="">{{ __('messages.stock_status') }}</option>
                    <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>{{ __('messages.in_stock') }}</option>
                    <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>{{ __('messages.out_of_stock') }}</option>
                </select>
            </div>
            <div class="min-w-[150px] shrink-0">
                <select id="sortSelectMobile" name="sort" data-auto-submit aria-label="{{ __('messages.sort_by') }}" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 shadow-xs">
                    <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>{{ __('messages.sort_newest') }}</option>
                    <option value="price_low_high" {{ $sort === 'price_low_high' ? 'selected' : '' }}>{{ __('messages.sort_price_low_high') }}</option>
                    <option value="price_high_low" {{ $sort === 'price_high_low' ? 'selected' : '' }}>{{ __('messages.sort_price_high_low') }}</option>
                </select>
            </div>
            <div class="min-w-[110px] shrink-0">
                <select id="perPageSelectMobile" name="per_page" form="mobileCatForm" data-auto-submit aria-label="{{ __('messages.per_page') }}" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 shadow-xs">
                    <option value="40" {{ $perPageSel === '40' ? 'selected' : '' }}>40</option>
                    <option value="80" {{ $perPageSel === '80' ? 'selected' : '' }}>80</option>
                    <option value="120" {{ $perPageSel === '120' ? 'selected' : '' }}>120</option>
                    <option value="all" {{ $perPageSel === 'all' ? 'selected' : '' }}>{{ __('messages.all') }}</option>
                </select>
            </div>
            <button type="button" @click="priceOpen = !priceOpen"
                class="sf-btn-3d !flex-row shrink-0 px-3 py-2 rounded-xl text-sm font-bold transition flex items-center gap-1.5"
                :class="priceOpen ? 'active' : ''">
                <span>💰</span><span>{{ __('messages.price_range') }}</span>
            </button>
        </div>

        {{-- Row 3: collapsible price range (mobile/tablet) --}}
        <div x-show="priceOpen" x-cloak x-transition class="pt-1">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-sm font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.min_price') }}</label>
                    <input type="number" name="min_price" min="0" step="1000" value="{{ request('min_price') }}" placeholder="0" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 shadow-xs" />
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.max_price') }}</label>
                    <input type="number" name="max_price" min="0" step="1000" value="{{ request('max_price') }}" placeholder="∞" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 shadow-xs" />
                </div>
            </div>
            <button type="submit" class="sf-btn-3d-primary mt-2 w-full py-2.5 text-sm font-bold rounded-xl">
                {{ __('messages.apply') }}
            </button>
        </div>
    </form>

    @push('modals')
    {{-- Two-step Category picker (bottom sheet, pushed to layout root so it covers the mobile bottom nav): Main → Sub --}}
    <div x-data="{
        catOpen: false,
        catLevel: {{ $activeCatMainId ? 2 : 1 }},
        catMainId: {{ $activeCatMainId ?? 'null' }},
        catSheetSearch: '',
        pickCat(id) {
            this.$refs.catInput.value = id ?? '';
            this.catOpen = false;
            this.$refs.catInput.form.submit();
        }
    }" @cat-picker-open.window="catOpen = true; catLevel = {{ $activeCatMainId ? 2 : 1 }}; catMainId = {{ $activeCatMainId ?? 'null' }}; catSheetSearch = ''" @keydown.escape.window="catOpen = false">
    <div x-show="catOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[60]"
        x-data="{ kbHeight: 0, updateKb() { if (window.visualViewport) { this.kbHeight = Math.max(0, window.innerHeight - window.visualViewport.height); } } }"
        @cat-picker-open.window="updateKb()"
        x-init="window.visualViewport && window.visualViewport.addEventListener('resize', () => updateKb())">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="catOpen = false"></div>
            <div class="absolute bottom-0 inset-x-0 bg-white dark:bg-slate-900 rounded-t-2xl shadow-2xl border-t border-slate-200 dark:border-slate-700 max-h-[75dvh] flex flex-col"
                :style="'padding-bottom: ' + kbHeight + 'px'"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
                {{-- Drag handle --}}
                <div class="mx-auto mt-2 h-1 w-10 rounded-full bg-slate-200 dark:bg-slate-700 shrink-0" aria-hidden="true"></div>
                {{-- Sheet header --}}
                <div class="flex items-center justify-between px-4 pt-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 shrink-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-8 h-8 shrink-0 rounded-xl sf-btn-3d-primary flex items-center justify-center text-base">🗂️</span>
                        <div class="min-w-0">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white font-outfit leading-tight">{{ __('messages.categories') }}</h3>
                            <p class="text-xs font-bold text-slate-500 dark:text-slate-400 leading-tight">{{ $totalProducts }} {{ __('messages.products') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="catOpen = false" class="w-9 h-9 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition text-sm" aria-label="Close">✕</button>
                </div>

                {{-- Search --}}
                <div class="px-3 pt-2 shrink-0">
                    <div class="relative">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" x-model="catSheetSearch" placeholder="{{ __('messages.search_categories') }}"
                            class="w-full pl-8 pr-8 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 shadow-xs" />
                        <button type="button" x-show="catSheetSearch.length > 0" x-cloak @click="catSheetSearch = ''" class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center rounded-full bg-slate-200 dark:bg-slate-600 text-slate-500 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-500 text-sm leading-none transition">✕</button>
                    </div>
                </div>

                <div class="overflow-y-auto p-2 grow" style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom))">
                    {{-- Form-associated hidden input: submits with the mobile filter form via its id --}}
                    <input type="hidden" name="category_id" form="mobileCatForm" x-ref="catInput" value="{{ request('category_id') }}" />
                    {{-- Level 1: All + Main list --}}
                    <template x-if="catLevel === 1">
                        <div class="space-y-1">
                            <button type="button" @click="pickCat(null)"
                                x-show="catSheetSearch === ''"
                                class="w-full !flex-row items-center justify-start gap-2.5 px-3 py-2.5 rounded-xl text-sm font-bold transition sf-btn-3d {{ ! request()->filled('category_id') ? 'active' : '' }}">
                                <span class="text-base shrink-0">🏷️</span>
                                <span class="flex-1 min-w-0 text-left">{{ __('messages.all_categories') }}</span>
                            </button>
                            @foreach ($categoryTree as $mainRow)
                                @php
                                    $catName = $mainRow->category->localized_name ?? $mainRow->category->name;
                                    $mainLower = strtolower(addslashes($catName));
                                    $childLowerArr = $mainRow->children->map(fn($n) => strtolower(addslashes($n->localized_name ?? $n->name)))->values()->all();
                                @endphp
                                <button type="button"
                                    @if ($mainRow->children->isNotEmpty())
                                        @click="catLevel = 2; catMainId = {{ $mainRow->category->id }}; catSheetSearch = ''"
                                    @else
                                        @click="pickCat({{ $mainRow->category->id }})"
                                    @endif
                                    x-show="catSheetSearch === '' || '{{ $mainLower }}'.includes(catSheetSearch.toLowerCase()) || [{{ implode(',', array_map(fn($c) => "'".$c."'", $childLowerArr)) }}].some(s => s.includes(catSheetSearch.toLowerCase()))"
                                    class="w-full !flex-row items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-sm font-bold transition sf-btn-3d {{ request('category_id') == $mainRow->category->id ? 'active' : '' }}">
                                    <span class="flex items-center gap-2.5 min-w-0 flex-1 text-left">
                                        <span class="w-7 h-7 shrink-0 rounded-lg bg-white/80 dark:bg-slate-800 flex items-center justify-center text-sm shadow-xs border border-slate-200/70 dark:border-slate-700/60">{{ $mainRow->category->icon ?: '📦' }}</span>
                                        <span class="truncate font-myanmar text-left">{{ $catName }}</span>
                                    </span>
                                    @if ($mainRow->children->isNotEmpty())
                                        <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                        </svg>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </template>

                    {{-- Level 2: Subs of the picked Main (one group visible at a time) --}}
                    @foreach ($categoryTree as $mainRow)
                        @if ($mainRow->children->isNotEmpty())
                            @php
                                $mainCatName = $mainRow->category->localized_name ?? $mainRow->category->name;
                            @endphp
                            <div x-show="catLevel === 2 && catMainId === {{ $mainRow->category->id }}" x-cloak class="space-y-1">
                                <button type="button" @click="catLevel = 1; catMainId = null"
                                    class="w-full !flex-row items-center gap-2 px-2 py-2 rounded-xl text-xs font-extrabold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-white transition">
                                    <span class="w-7 h-7 shrink-0 flex items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-300">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </span>
                                    <span>{{ __('messages.back') }}</span>
                                    <span class="truncate text-xs font-black text-slate-400 dark:text-slate-500">/ {{ $mainCatName }}</span>
                                </button>
                                <button type="button" @click="pickCat({{ $mainRow->category->id }})"
                                    class="w-full !flex-row items-center justify-between gap-2.5 px-3 py-2.5 rounded-xl text-sm font-bold transition sf-btn-3d {{ request('category_id') == $mainRow->category->id ? 'active' : '' }}">
                                    <span class="flex items-center gap-2.5 min-w-0 flex-1 text-left">
                                        <span class="w-7 h-7 shrink-0 rounded-lg bg-white/80 dark:bg-slate-800 flex items-center justify-center text-sm shadow-xs border border-slate-200/70 dark:border-slate-700/60">{{ $mainRow->category->icon ?: '🗂️' }}</span>
                                        <span class="truncate font-myanmar text-left">{{ __('messages.all_in') }} {{ $mainCatName }}</span>
                                    </span>
                                    @if (request('category_id') == $mainRow->category->id)
                                        <svg class="w-4 h-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </button>
                                @foreach ($mainRow->children as $sub)
                                    @php
                                        $subCatName = $sub->localized_name ?? $sub->name;
                                    @endphp
                                    <button type="button" @click="pickCat({{ $sub->id }})"
                                        x-show="catSheetSearch === '' || '{{ strtolower(addslashes($subCatName)) }}'.includes(catSheetSearch.toLowerCase())"
                                        class="w-full !flex-row items-center justify-between gap-2.5 px-3 py-2.5 rounded-xl text-sm font-bold transition sf-btn-3d {{ request('category_id') == $sub->id ? 'active' : '' }}">
                                        <span class="flex items-center gap-2.5 min-w-0 flex-1 text-left">
                                            <span class="w-7 h-7 shrink-0 rounded-lg flex items-center justify-center text-xs {{ request('category_id') == $sub->id ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">📁</span>
                                            <span class="truncate font-myanmar text-left">{{ $subCatName }}</span>
                                        </span>
                                        @if (request('category_id') == $sub->id)
                                            <svg class="w-4 h-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Brand picker (bottom sheet) --}}
    @if ($brands->count() > 0)
    @php
        $brandsSorted = $brands->sortByDesc('products_count')->values();
    @endphp
    <div x-data="{
        brandOpen: false,
        brandSheetSearch: '',
        brandLoaded: {{ $brandsSorted->count() }},
        get brandVisible() {
            return this.brandSheetSearch !== '' ? 999 : this.brandLoaded;
        },
        loadMoreBrands() {
            if (this.brandSheetSearch !== '') return;
            const c = this.$refs.brandScroll;
            if (c.scrollHeight - c.scrollTop - c.clientHeight < 80) {
                this.brandLoaded = Math.min(this.brandLoaded + 12, {{ $brandsSorted->count() }});
            }
        },
        pickBrand(id) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'brand_id';
            input.value = id ?? '';
            document.getElementById('mobileCatForm').appendChild(input);
            document.getElementById('mobileCatForm').submit();
        }
    }" @brand-picker-open.window="brandOpen = true; brandSheetSearch = ''" @keydown.escape.window="brandOpen = false">
    <div x-show="brandOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[60]"
        x-data="{ kbHeight: 0, updateKb() { if (window.visualViewport) { this.kbHeight = Math.max(0, window.innerHeight - window.visualViewport.height); } } }"
        @brand-picker-open.window="updateKb()"
        x-init="window.visualViewport && window.visualViewport.addEventListener('resize', () => updateKb())">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="brandOpen = false"></div>
            <div class="absolute bottom-0 inset-x-0 bg-white dark:bg-slate-900 rounded-t-2xl shadow-2xl border-t border-slate-200 dark:border-slate-700 max-h-[75dvh] flex flex-col"
                :style="'padding-bottom: ' + kbHeight + 'px'"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
                {{-- Drag handle --}}
                <div class="mx-auto mt-2 h-1 w-10 rounded-full bg-slate-200 dark:bg-slate-700 shrink-0" aria-hidden="true"></div>
                {{-- Sheet header --}}
                <div class="flex items-center justify-between px-4 pt-2.5 pb-3 border-b border-slate-100 dark:border-slate-800 shrink-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-8 h-8 shrink-0 rounded-xl sf-btn-3d-primary flex items-center justify-center text-base">🏷️</span>
                        <div class="min-w-0">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white font-outfit leading-tight">{{ __('messages.brands') }}</h3>
                            <p class="text-xs font-bold text-slate-500 dark:text-slate-400 leading-tight">{{ $brands->count() }} {{ __('messages.brands') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="brandOpen = false" class="w-9 h-9 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition text-sm" aria-label="Close">✕</button>
                </div>
                {{-- Search --}}
                <div class="px-3 pt-2 shrink-0">
                    <div class="relative">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" x-model="brandSheetSearch" placeholder="{{ __('messages.search_brands') }}"
                            class="w-full pl-8 pr-8 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 shadow-xs" />
                        <button type="button" x-show="brandSheetSearch.length > 0" x-cloak @click="brandSheetSearch = ''" class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center rounded-full bg-slate-200 dark:bg-slate-600 text-slate-500 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-500 text-sm leading-none transition">✕</button>
                    </div>
                </div>
                <div class="flex-1 min-h-0 overflow-y-auto p-2 scrollbar-thin" x-ref="brandScroll" @scroll="loadMoreBrands()" style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom));">
                    <input type="hidden" name="brand_id" form="mobileCatForm" value="{{ request('brand_id') }}" x-ref="brandInput" />
                    {{-- All brands --}}
                    <button type="button" @click="pickBrand(null)"
                        x-show="brandSheetSearch === ''"
                        class="w-full !flex-row items-center justify-between gap-2.5 px-3 py-2.5 rounded-xl text-sm font-bold transition sf-btn-3d {{ !request()->filled('brand_id') ? 'active' : '' }}">
                        <span class="flex items-center gap-2.5 min-w-0 flex-1 text-left">
                            <span class="text-base shrink-0">🏷️</span>
                            <span class="truncate text-left">{{ __('messages.all_brands') }}</span>
                        </span>
                        <span class="shrink-0 text-xs font-black {{ !request()->filled('brand_id') ? 'text-white/90' : 'text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 rounded-full px-2 py-0.5' }}">{{ $brands->count() }}</span>
                    </button>
                    {{-- Brands (popular first, virtual scroll) --}}
                    @foreach ($brandsSorted as $b)
                        @php $isBrandActive = request('brand_id') == $b->id; @endphp
                        <button type="button" @click="pickBrand({{ $b->id }})"
                            x-show="{{ $loop->index }} < brandVisible || brandSheetSearch !== '' && '{{ strtolower(addslashes($b->name)) }}'.includes(brandSheetSearch.toLowerCase())"
                            class="w-full !flex-row items-center justify-between gap-2.5 px-3 py-2.5 rounded-xl text-sm font-bold transition sf-btn-3d {{ $isBrandActive ? 'active' : '' }}">
                            <span class="flex items-center gap-2.5 min-w-0 flex-1 text-left">
                                <span class="w-7 h-7 shrink-0 rounded-lg bg-white/80 dark:bg-slate-800 flex items-center justify-center text-xs font-black text-slate-700 dark:text-slate-300 border border-slate-200/70 dark:border-slate-700/60 shadow-xs">{{ strtoupper(substr($b->name, 0, 2)) }}</span>
                                <span class="truncate text-left font-myanmar">{{ $b->name }}</span>
                            </span>
                            @if ($isBrandActive)
                                <svg class="w-4 h-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            @else
                                <span class="shrink-0 text-xs font-black text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800/80 rounded-full px-2 py-0.5">{{ $b->products_count }}</span>
                            @endif
                        </button>
                    @endforeach
                    {{-- Loading indicator --}}
                    <div x-show="brandLoaded < {{ $brandsSorted->count() }} && brandSheetSearch === ''" class="py-3 text-center">
                        <div class="inline-flex items-center gap-2 text-xs text-slate-600">
                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span>{{ __('messages.loading') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endpush

    {{-- Two-column layout: sidebar + product grid (Linn style) --}}
    <div class="lg:grid lg:grid-cols-[270px_minmax(0,1fr)] lg:gap-6 items-start">
        {{-- Sidebar (desktop only) — Categories / Brands / Price / Stock --}}
        <aside class="hidden lg:block sticky top-24 space-y-4 z-30" x-data="{
            activeCatHover: null,
            closeCatTimeout: null,
            brandSearch: '',
            catSearch: ''
        }">
            {{-- Categories with Hover Flyout Menu --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/90 dark:border-slate-800/80 shadow-xl relative z-30"
                 @mouseleave="closeCatTimeout = setTimeout(() => { activeCatHover = null }, 200)"
                 @mouseenter="if (closeCatTimeout) clearTimeout(closeCatTimeout)"
            >
                <div class="flex items-center justify-between pb-2.5 mb-2 border-b border-slate-100 dark:border-slate-800 px-1">
                    <span class="flex items-center gap-2 text-sm sm:text-base font-black uppercase tracking-wider text-slate-900 dark:text-white font-myanmar">
                        <span class="text-sky-500 text-base">🗂️</span> {{ __('messages.categories') }}
                    </span>
                    <a href="{{ $buildLink(['category_id' => null, 'category' => null]) }}" class="sf-btn-3d active !flex-row px-2.5 py-1 text-xs font-black font-myanmar leading-none">
                        {{ __('messages.all') }}
                    </a>
                </div>

                {{-- Search input --}}
                <div class="relative mb-2">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" x-model="catSearch" placeholder="{{ __('messages.search_categories') ?? 'Search categories...' }}"
                        class="w-full pl-8 pr-8 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-sm" />
                    <button type="button" x-show="catSearch.length > 0" x-cloak @click="catSearch = ''" class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center rounded-full bg-slate-200 dark:bg-slate-600 text-slate-500 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-500 text-xs leading-none transition">✕</button>
                </div>

                <div class="space-y-1 max-h-[440px] overflow-y-auto pr-0.5 select-none scrollbar-thin">
                    <a href="{{ $buildLink(['category_id' => null, 'category' => null]) }}" class="sf-btn-3d w-full !flex-row !justify-between px-3 py-1.5 rounded-xl text-sm sm:text-[15px] font-black transition-all group {{ !request()->filled('category_id') && !request()->filled('category') ? 'active' : '' }}">
                        <span class="flex items-center gap-2 min-w-0 flex-1 text-left">
                            <span class="text-base shrink-0">🏷️</span>
                            <span class="truncate font-black text-sm sm:text-[15px]">{{ __('messages.all_categories') }}</span>
                        </span>
                    </a>
                    @foreach ($categoryTree as $mainRow)
                        @php
                            $main = $mainRow->category;
                            $mainName = $main->name;
                            $isMainActive = request('category_id') == $main->id || request('category') == $main->name;
                            $activeChildId = collect($mainRow->children)->firstWhere('id', (int) request('category_id'));
                            $hasChildren = $mainRow->children->isNotEmpty();
                            $mainLower = strtolower(addslashes($mainName));
                            $childLowerArr = $mainRow->children->map(fn($n) => strtolower(addslashes($n->name)))->values()->all();
                            $mainIcon = ($main->icon && $main->icon !== 'NULL' && $main->icon !== 'null') ? $main->icon : '📦';
                        @endphp
                        <div class="relative"
                             x-show="catSearch === '' || '{{ $mainLower }}'.includes(catSearch.toLowerCase()) || [{{ implode(',', array_map(fn($c) => "'".$c."'", $childLowerArr)) }}].some(s => s.includes(catSearch.toLowerCase()))"
                             @mouseenter="if (closeCatTimeout) clearTimeout(closeCatTimeout); activeCatHover = {{ $main->id }}"
                        >
                            {{-- Main Category Row (3D Button) --}}
                            <a href="{{ $buildLink(['category_id' => $main->id, 'category' => null]) }}"
                               class="sf-btn-3d w-full !flex-row !justify-between px-3 py-1.5 rounded-xl text-sm sm:text-[15px] font-black transition-all group"
                               :class="activeCatHover === {{ $main->id }} || {{ $isMainActive && !$activeChildId ? 'true' : 'false' }} ? 'active' : ''"
                            >
                                <span class="flex items-center gap-2 min-w-0 flex-1 text-left">
                                    <span class="text-base shrink-0 group-hover:scale-110 transition-transform">{{ $mainIcon }}</span>
                                    <span class="truncate font-black text-sm sm:text-[15px]">{{ $mainName }}</span>
                                </span>
                                @if ($hasChildren)
                                    <div class="flex items-center shrink-0">
                                        <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" :class="activeCatHover === {{ $main->id }} || {{ $isMainActive && !$activeChildId ? 'true' : 'false' }} ? 'text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                @endif
                            </a>
                        </div>
                    @endforeach
                </div>

                {{-- Subcategory Flyout Panels (Slim 256px, Zero GPU lag - Outside scroll container) --}}
                @foreach ($categoryTree as $mainRow)
                    @if ($mainRow->children->isNotEmpty())
                        @php
                            $main = $mainRow->category;
                            $mainName = $main->name;
                            $mainIcon = ($main->icon && $main->icon !== 'NULL' && $main->icon !== 'null') ? $main->icon : '📦';
                        @endphp
                        <div x-show="activeCatHover === {{ $main->id }}"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-x-1"
                             x-transition:enter-end="opacity-100 translate-x-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-x-0"
                             x-transition:leave-end="opacity-0 translate-x-1"
                             @mouseenter="if (closeCatTimeout) clearTimeout(closeCatTimeout); activeCatHover = {{ $main->id }}"
                             @mouseleave="closeCatTimeout = setTimeout(() => { activeCatHover = null }, 200)"
                             class="absolute left-full inset-y-0 ml-2 w-56 sm:w-64 bg-white dark:bg-slate-900 rounded-2xl border-2 border-slate-200 dark:border-slate-800 shadow-2xl p-2.5 sm:p-3 z-50 flex flex-col before:absolute before:-left-3 before:top-0 before:bottom-0 before:w-3 before:content-['']"
                             style="display: none;"
                        >
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100 dark:border-slate-800 px-1 shrink-0">
                                <span class="text-sm sm:text-base font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                                    <span class="text-base sm:text-lg">{{ $mainIcon }}</span>
                                    <span class="truncate">{{ $mainName }}</span>
                                </span>
                                <a href="{{ $buildLink(['category_id' => $main->id, 'category' => null]) }}" class="sf-btn-3d active !flex-row px-2.5 py-1 text-xs font-black font-myanmar leading-none">
                                    <span>{{ __('messages.view_all') }}</span>
                                    <span>→</span>
                                </a>
                            </div>

                            <div class="flex-1 overflow-y-auto pr-1 space-y-1 select-none scrollbar-thin">
                                @foreach ($mainRow->children as $sub)
                                    @php
                                        $isSubActive = request('category_id') == $sub->id || request('category') == $sub->name;
                                        $subIcon = ($sub->icon && $sub->icon !== 'NULL' && $sub->icon !== 'null') ? $sub->icon : '▫️';
                                        $subName = $sub->name;
                                    @endphp
                                    <a href="{{ $buildLink(['category_id' => $sub->id, 'category' => null]) }}"
                                       class="sf-btn-3d w-full !flex-row !justify-between px-3 py-1.5 rounded-xl text-sm sm:text-[15px] font-black transition-all group/sub {{ $isSubActive ? 'active' : '' }}">
                                        <span class="flex items-center gap-2 min-w-0 flex-1 text-left">
                                            <span class="text-sm shrink-0 text-slate-500 group-hover/sub:text-sky-500">{{ $subIcon }}</span>
                                            <span class="truncate font-black">{{ $subName }}</span>
                                        </span>
                                        <div class="flex items-center shrink-0">
                                            <svg class="h-3.5 w-3.5 text-slate-400 opacity-0 group-hover/sub:opacity-100 group-hover/sub:text-sky-500 transition-all group-hover/sub:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Brands (with search & hover related categories flyout) --}}
            @if ($brands->count() > 0)
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/90 dark:border-slate-800/80 shadow-xl relative z-20"
                     x-data="{ activeBrandHover: null, closeBrandTimeout: null }"
                     @mouseleave="closeBrandTimeout = setTimeout(() => { activeBrandHover = null }, 200)"
                     @mouseenter="if (closeBrandTimeout) clearTimeout(closeBrandTimeout)"
                >
                    <div class="flex items-center justify-between pb-2.5 mb-2 border-b border-slate-100 dark:border-slate-800 px-1">
                        <span class="flex items-center gap-2 text-sm sm:text-base font-black uppercase tracking-wider text-slate-900 dark:text-white font-myanmar">
                            <span class="text-sky-500 text-base">🏷️</span> {{ __('messages.brands') }}
                        </span>
                        <a href="{{ $buildLink(['brand_id' => null, 'brand' => null]) }}" class="sf-btn-3d active !flex-row px-2.5 py-1 text-xs font-black font-myanmar leading-none">
                            {{ __('messages.all') }}
                        </a>
                    </div>
                    {{-- Search input --}}
                    <div class="relative mb-2">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" x-model="brandSearch" placeholder="{{ __('messages.search_brands') ?? 'Search brands...' }}"
                            class="w-full pl-8 pr-8 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-sm" />
                        <button type="button" x-show="brandSearch.length > 0" x-cloak @click="brandSearch = ''" class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center rounded-full bg-slate-200 dark:bg-slate-600 text-slate-500 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-500 text-xs leading-none transition">✕</button>
                    </div>
                    <div class="space-y-1 max-h-[440px] overflow-y-auto pr-0.5 select-none scrollbar-thin">
                        <a href="{{ $buildLink(['brand_id' => null, 'brand' => null]) }}" class="sf-btn-3d w-full !flex-row !justify-between px-3 py-1.5 rounded-xl text-sm sm:text-[15px] font-black transition-all group {{ !request()->filled('brand_id') && !request()->filled('brand') ? 'active' : '' }}">
                            <span class="flex-1 min-w-0 truncate text-left">{{ __('messages.all_brands') }}</span>
                        </a>
                        @foreach ($brands as $b)
                            @php
                                $isBrandActive = request('brand_id') == $b->id || request('brand') == $b->name;
                                $hasRelCats = isset($b->related_categories) && $b->related_categories->isNotEmpty();
                            @endphp
                            <div class="relative"
                                 x-show="brandSearch === '' || '{{ strtolower(addslashes($b->name)) }}'.includes(brandSearch.toLowerCase())"
                                 @mouseenter="if (closeBrandTimeout) clearTimeout(closeBrandTimeout); activeBrandHover = {{ $b->id }}"
                            >
                                <a href="{{ $buildLink(['brand_id' => $b->id, 'brand' => null]) }}"
                                   class="sf-btn-3d w-full !flex-row !justify-between px-3 py-1.5 rounded-xl text-sm sm:text-[15px] font-black transition-all group"
                                   :class="activeBrandHover === {{ $b->id }} || {{ $isBrandActive ? 'true' : 'false' }} ? 'active' : ''"
                                >
                                    <span class="flex-1 min-w-0 truncate text-left">{{ $b->name }}</span>
                                    @if ($hasRelCats)
                                        <div class="flex items-center shrink-0">
                                            <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" :class="activeBrandHover === {{ $b->id }} || {{ $isBrandActive ? 'true' : 'false' }} ? 'text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </div>
                                    @endif
                                </a>
                            </div>
                        @endforeach
                        {{-- No results message --}}
                        <div x-show="brandSearch.length > 0 && [...document.querySelectorAll('[x-show*=brandSearch]')].every(el => el.style.display === 'none')" x-cloak class="px-3 py-4 text-center text-xs text-slate-500 dark:text-slate-400">
                            🔍 {{ __('messages.no_results') ?? 'No results' }}
                        </div>
                    </div>

                    {{-- Brand Related Categories Flyout Panels (Slim 256px - Outside scroll container) --}}
                    @foreach ($brands as $b)
                        @if (isset($b->related_categories) && $b->related_categories->isNotEmpty())
                            <div x-show="activeBrandHover === {{ $b->id }}"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 translate-x-1"
                                 x-transition:enter-end="opacity-100 translate-x-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 translate-x-0"
                                 x-transition:leave-end="opacity-0 translate-x-1"
                                 @mouseenter="if (closeBrandTimeout) clearTimeout(closeBrandTimeout); activeBrandHover = {{ $b->id }}"
                                 @mouseleave="closeBrandTimeout = setTimeout(() => { activeBrandHover = null }, 200)"
                                 class="absolute left-full inset-y-0 ml-2 w-56 sm:w-64 bg-white dark:bg-slate-900 rounded-2xl border-2 border-slate-200 dark:border-slate-800 shadow-2xl p-2.5 sm:p-3 z-50 flex flex-col before:absolute before:-left-3 before:top-0 before:bottom-0 before:w-3 before:content-['']"
                                 style="display: none;"
                            >
                                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100 dark:border-slate-800 px-1 shrink-0">
                                    <span class="text-sm sm:text-base font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                                        <span>🏷️</span>
                                        <span class="truncate">{{ $b->name }}</span>
                                    </span>
                                    <a href="{{ $buildLink(['brand_id' => $b->id, 'brand' => null]) }}" class="sf-btn-3d active !flex-row px-2.5 py-1 text-xs font-black font-myanmar leading-none">
                                        <span>{{ __('messages.view_all') }}</span>
                                        <span>→</span>
                                    </a>
                                </div>

                                <div class="flex-1 overflow-y-auto pr-1 space-y-1 select-none scrollbar-thin">
                                    @foreach ($b->related_categories as $relCat)
                                        @php
                                            $isCatActive = request('brand_id') == $b->id && (request('category_id') == $relCat->id || request('category') == $relCat->name);
                                            $cIcon = ($relCat->icon && $relCat->icon !== 'NULL' && $relCat->icon !== 'null') ? $relCat->icon : '📁';
                                            $relCatName = $relCat->name;
                                        @endphp
                                        <a href="{{ $buildLink(['brand_id' => $b->id, 'category_id' => $relCat->id, 'brand' => null, 'category' => null]) }}"
                                           class="sf-btn-3d w-full !flex-row !justify-between px-3 py-1.5 rounded-xl text-sm sm:text-[15px] font-black transition-all group/sub {{ $isCatActive ? 'active' : '' }}">
                                            <span class="flex items-center gap-2 min-w-0 flex-1 text-left">
                                                <span class="text-sm shrink-0 text-slate-500 group-hover/sub:text-sky-500">{{ $cIcon }}</span>
                                                <span class="truncate font-black">{{ $relCatName }}</span>
                                            </span>
                                            <div class="flex items-center shrink-0">
                                                <svg class="h-3.5 w-3.5 text-slate-400 opacity-0 group-hover/sub:opacity-100 group-hover/sub:text-sky-500 transition-all group-hover/sub:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Price filter box (Linn style: min/max + Apply) --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/90 dark:border-slate-800/80 shadow-xl">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 font-outfit mb-3 flex items-center gap-1.5">
                    <span>💰</span> <span>{{ __('messages.price_range') }}</span>
                </h2>
                <form method="GET" action="{{ $baseUrl }}" class="space-y-3">
                    @foreach (request()->except(['min_price', 'max_price', 'page']) as $key => $value)
                        @if (is_array($value))
                            @foreach ($value as $v)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}" />
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
                        @endif
                    @endforeach
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="sidebarMinPrice" class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.min_price') }}</label>
                            <input id="sidebarMinPrice" type="number" name="min_price" min="0" step="1000" value="{{ request('min_price') }}" placeholder="0" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 shadow-xs" />
                        </div>
                        <div>
                            <label for="sidebarMaxPrice" class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.max_price') }}</label>
                            <input id="sidebarMaxPrice" type="number" name="max_price" min="0" step="1000" value="{{ request('max_price') }}" placeholder="∞" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 shadow-xs" />
                        </div>
                    </div>
                    <button type="submit" class="sf-btn-3d-primary w-full py-2.5 text-xs font-bold rounded-xl shadow-xs">
                        {{ __('messages.apply') }}
                    </button>
                </form>
            </div>

            {{-- Stock status --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/90 dark:border-slate-800/80 shadow-xl">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 font-outfit mb-3 flex items-center gap-1.5">
                    <span>📦</span> <span>{{ __('messages.stock_status') }}</span>
                </h2>
                <div class="space-y-1">
                    <a href="{{ $buildLink(['stock_status' => 'in_stock']) }}" class="w-full flex items-center gap-2 px-2.5 py-2 rounded-xl text-sm font-bold transition sf-btn-3d {{ request('stock_status') === 'in_stock' ? '!bg-emerald-500 !text-white !border-b-emerald-700' : '' }}">
                        <span class="w-2 h-2 rounded-full {{ request('stock_status') === 'in_stock' ? 'bg-white' : 'bg-emerald-500' }}"></span>
                        <span class="flex-1 text-left">{{ __('messages.in_stock') }}</span>
                    </a>
                    <a href="{{ $buildLink(['stock_status' => 'out_of_stock']) }}" class="w-full flex items-center gap-2 px-2.5 py-2 rounded-xl text-sm font-bold transition sf-btn-3d {{ request('stock_status') === 'out_of_stock' ? '!bg-rose-500 !text-white !border-b-rose-700' : '' }}">
                        <span class="w-2 h-2 rounded-full {{ request('stock_status') === 'out_of_stock' ? 'bg-white' : 'bg-rose-500' }}"></span>
                        <span class="flex-1 text-left">{{ __('messages.out_of_stock') }}</span>
                    </a>
                </div>
            </div>
        </aside>

        {{-- Main: product grid / list --}}
        <div class="min-w-0">
            @if ($viewMode === 'list')
                {{-- List (deep-linked from /browse) — glued hairline rows; 1 column on mobile, 2-3 on tablet/desktop --}}
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-px bg-slate-200 dark:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
                    @forelse ($products as $product)
                        <x-product-card-list
                            :product="$product"
                            :store="$store"
                            :isWholesaleApproved="$isWholesaleApproved ?? false"
                        />
                    @empty
                        <div class="bg-white dark:bg-slate-900 p-12 text-center text-slate-500 dark:text-slate-600 space-y-3">
                            <div class="text-4xl">🔍</div>
                            <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 font-outfit">
                                {{ __('messages.no_products_found') }}
                            </h3>
                            <p class="text-xs font-myanmar">
                                {{ __('messages.no_products_hint') }}
                            </p>
                        </div>
                    @endforelse
                </div>
            @else
                {{-- Dense hairline-divided grid with adaptive responsive columns for all display widths --}}
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 min-[1920px]:grid-cols-6 gap-px bg-slate-200 dark:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
                    @forelse ($products as $product)
                        <x-product-card
                            :product="$product"
                            :store="$store"
                            :isWholesaleApproved="$isWholesaleApproved ?? false"
                            :dense="true"
                        />
                    @empty
                        <div class="col-span-full bg-white dark:bg-slate-900 p-12 text-center text-slate-500 dark:text-slate-600 space-y-3">
                            <div class="text-4xl">🔍</div>
                            <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 font-outfit">
                                {{ __('messages.no_products_found') }}
                            </h3>
                            <p class="text-xs font-myanmar">
                                {{ __('messages.no_products_hint') }}
                            </p>
                        </div>
                    @endforelse
                </div>
            @endif

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
