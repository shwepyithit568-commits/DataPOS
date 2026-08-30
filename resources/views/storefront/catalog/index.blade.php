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
    if ($activeCategory) $activeFilters->push(['label' => $activeCategory->name, 'url' => $buildLink(['category_id' => null, 'category' => null])]);
    if ($activeBrand) $activeFilters->push(['label' => $activeBrand->name, 'url' => $buildLink(['brand_id' => null, 'brand' => null])]);
    if (request()->filled('min_price') || request()->filled('max_price')) {
        $activeFilters->push(['label' => 'Ks ' . number_format((float) request('min_price', 0)) . ' – Ks ' . number_format((float) request('max_price', 0)), 'url' => $buildLink(['min_price' => null, 'max_price' => null])]);
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
    {{-- Page Header: title + sort (Linn product_header row) --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white font-outfit flex items-center gap-2.5">
                <span>{{ __('messages.product_list') }}</span>
                <span class="inline-flex items-center gap-1 text-xs font-extrabold text-slate-500 dark:text-slate-600 bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-full border border-slate-200 dark:border-slate-700">
                    {{ __('messages.total_products', ['count' => $products->total()]) }}
                </span>
                @if ($activeCategory)
                    <span class="inline-flex items-center gap-1 text-xs font-extrabold text-sky-700 dark:text-sky-300 bg-sky-100 dark:bg-sky-950/80 px-2.5 py-1 rounded-full border border-sky-300 dark:border-sky-800">
                        {{ $activeCategory->icon ?: '📦' }} {{ $activeCategory->name }}
                    </span>
                @endif
            </h1>

            {{-- Grid / List view toggle (visible on all screens) --}}
            <div class="flex items-center gap-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-1 shadow-sm shrink-0">
                <a href="{{ $buildLink(['view' => 'grid']) }}" data-catalog-view="grid" class="px-3 py-1.5 rounded-lg text-xs font-extrabold transition {{ $viewMode === 'grid' ? 'bg-gradient-to-r from-violet-600 to-fuchsia-500 text-white shadow-sm' : 'text-slate-500 dark:text-slate-600 hover:text-slate-800 dark:hover:text-white' }}" title="{{ __('messages.view_grid') }}">▦ {{ __('messages.view_grid') }}</a>
                <a href="{{ $buildLink(['view' => 'list']) }}" data-catalog-view="list" class="px-3 py-1.5 rounded-lg text-xs font-extrabold transition {{ $viewMode === 'list' ? 'bg-gradient-to-r from-violet-600 to-fuchsia-500 text-white shadow-sm' : 'text-slate-500 dark:text-slate-600 hover:text-slate-800 dark:hover:text-white' }}" title="{{ __('messages.view_list') }}">☰ {{ __('messages.view_list') }}</a>
            </div>

            {{-- Sort dropdown (Linn: Release / Price Low-High / Price High-Low) -- desktop only; mobile version lives in the horizontal toolbar below --}}
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
                <label for="sortSelect" class="text-xs font-extrabold text-slate-500 dark:text-slate-600 uppercase tracking-wide">
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
                <label for="perPageSelect" class="text-xs font-extrabold text-slate-500 dark:text-slate-600 uppercase tracking-wide">
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
                    <a href="{{ $chip['url'] }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:border-rose-400 hover:text-rose-600 dark:hover:text-rose-400 transition group">
                        {{ $chip['label'] }}
                        <span class="text-slate-600 group-hover:text-rose-500">✕</span>
                    </a>
                @endforeach
                @if ($hasActiveFilters)
                    <a href="{{ $storeSlug ? url('/products?store_slug=' . $storeSlug) : url('/products') }}" class="px-2.5 py-1 rounded-full bg-rose-50 dark:bg-rose-950/80 text-xs font-extrabold text-rose-600 dark:text-rose-400 border border-rose-300 dark:border-rose-800 hover:bg-rose-100 transition">
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
                <svg class="w-4 h-4 text-slate-600 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search_name_or_sku') }}" class="w-full pl-9 pr-3 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 shadow-sm" />
            </div>
            <button type="submit" class="shrink-0 px-4 py-2.5 bg-gradient-to-r from-violet-600 to-fuchsia-500 hover:from-violet-600 hover:to-violet-500 text-white text-sm font-extrabold rounded-xl shadow-md shadow-sky-500/20 transition active:scale-95">
                {{ __('messages.filter') }}
            </button>
            @if ($hasActiveFilters)
                <a href="{{ $storeSlug ? url('/products?store_slug=' . $storeSlug) : url('/products') }}" class="shrink-0 px-3 py-2.5 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-sm rounded-xl hover:bg-slate-300 dark:hover:bg-slate-700 transition">
                    {{ __('messages.reset') }}
                </a>
            @endif
        </div>

        {{-- Row 2: filter chips (horizontal scroll) + Price toggle --}}
        <div x-data="{ isDown: false, startX: 0, scrollLeft: 0 }" @mousedown="isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft" @mouseleave="isDown = false" @mouseup="isDown = false" @mousemove="if(isDown){$event.preventDefault();const x=$event.pageX-$el.offsetLeft;const walk=(x-startX)*1.5;$el.scrollLeft=scrollLeft-walk}" class="overflow-x-auto whitespace-nowrap flex items-center gap-2 scrollbar-none pb-0.5 cursor-grab active:cursor-grabbing select-none">
            <div class="min-w-[140px] shrink-0">
                <button type="button" @click="$dispatch('cat-picker-open')"
                    class="w-full flex items-center justify-between gap-1.5 px-3 py-2.5 rounded-xl bg-white dark:bg-slate-800 border text-sm font-bold text-slate-900 dark:text-white shadow-sm {{ request()->filled('category_id') ? 'border-sky-400 dark:border-sky-500 ring-2 ring-sky-500/40' : 'border-slate-300 dark:border-slate-700' }}">
                    <span class="flex items-center gap-1.5 min-w-0">
                        <span class="shrink-0">🗂️</span>
                        <span class="truncate">{{ $activeCatName ?? __('messages.all_categories') }}</span>
                    </span>
                    <svg class="w-3.5 h-3.5 shrink-0 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
            <div class="min-w-[130px] shrink-0">
                <button type="button" @click="$dispatch('brand-picker-open')"
                    class="w-full flex items-center justify-between gap-1.5 px-3 py-2.5 rounded-xl bg-white dark:bg-slate-800 border text-sm font-bold text-slate-900 dark:text-white shadow-sm {{ request()->filled('brand_id') ? 'border-sky-400 dark:border-sky-500 ring-2 ring-sky-500/40' : 'border-slate-300 dark:border-slate-700' }}">
                    <span class="flex items-center gap-1.5 min-w-0">
                        <span class="shrink-0">🏷️</span>
                        <span class="truncate">{{ $activeBrand->name ?? __('messages.all_brands') }}</span>
                    </span>
                    <svg class="w-3.5 h-3.5 shrink-0 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
            <div class="min-w-[130px] shrink-0">
                <select name="stock_status" data-auto-submit class="w-full px-3 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 shadow-sm">
                    <option value="">{{ __('messages.stock_status') }}</option>
                    <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>{{ __('messages.in_stock') }}</option>
                    <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>{{ __('messages.out_of_stock') }}</option>
                </select>
            </div>
            <div class="min-w-[150px] shrink-0">
                <select id="sortSelectMobile" name="sort" data-auto-submit aria-label="{{ __('messages.sort_by') }}" class="w-full px-3 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 shadow-sm">
                    <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>{{ __('messages.sort_newest') }}</option>
                    <option value="price_low_high" {{ $sort === 'price_low_high' ? 'selected' : '' }}>{{ __('messages.sort_price_low_high') }}</option>
                    <option value="price_high_low" {{ $sort === 'price_high_low' ? 'selected' : '' }}>{{ __('messages.sort_price_high_low') }}</option>
                </select>
            </div>
            <div class="min-w-[110px] shrink-0">
                <select id="perPageSelectMobile" name="per_page" form="mobileCatForm" data-auto-submit aria-label="{{ __('messages.per_page') }}" class="w-full px-3 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500 shadow-sm">
                    <option value="40" {{ $perPageSel === '40' ? 'selected' : '' }}>40</option>
                    <option value="80" {{ $perPageSel === '80' ? 'selected' : '' }}>80</option>
                    <option value="120" {{ $perPageSel === '120' ? 'selected' : '' }}>120</option>
                    <option value="all" {{ $perPageSel === 'all' ? 'selected' : '' }}>{{ __('messages.all') }}</option>
                </select>
            </div>
            <button type="button" @click="priceOpen = !priceOpen"
                class="shrink-0 px-3 py-2.5 rounded-xl border text-sm font-extrabold transition flex items-center gap-1.5 shadow-sm"
                :class="priceOpen ? 'bg-gradient-to-r from-violet-600 to-fuchsia-500 text-white border-transparent shadow-md shadow-sky-500/20' : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200'">
                <span>💰</span><span>{{ __('messages.price_range') }}</span>
            </button>
        </div>

        {{-- Row 3: collapsible price range (mobile/tablet) --}}
        <div x-show="priceOpen" x-cloak x-transition class="pt-1">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-sm font-bold text-slate-600 dark:text-slate-500 mb-1">{{ __('messages.min_price') }} (Ks)</label>
                    <input type="number" name="min_price" min="0" step="1000" value="{{ request('min_price') }}" placeholder="0" class="w-full px-3 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-600 dark:text-slate-500 mb-1">{{ __('messages.max_price') }} (Ks)</label>
                    <input type="number" name="max_price" min="0" step="1000" value="{{ request('max_price') }}" placeholder="∞" class="w-full px-3 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 shadow-sm" />
                </div>
            </div>
            <button type="submit" class="mt-2 w-full px-4 py-2.5 bg-gradient-to-r from-violet-600 to-fuchsia-500 hover:from-violet-600 hover:to-violet-500 text-white text-sm font-extrabold rounded-xl shadow-md shadow-sky-500/20 transition active:scale-95">
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
                        <span class="w-8 h-8 shrink-0 rounded-xl bg-gradient-to-br from-violet-600 to-fuchsia-500 text-white flex items-center justify-center text-base shadow-md shadow-sky-500/20">🗂️</span>
                        <div class="min-w-0">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white font-outfit leading-tight">{{ __('messages.categories') }}</h3>
                            <p class="text-xs font-bold text-slate-600 dark:text-slate-500 leading-tight">{{ $totalProducts }} {{ __('messages.products') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="catOpen = false" class="w-9 h-9 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition text-sm" aria-label="Close">✕</button>
                </div>

                {{-- Search --}}
                <div class="px-3 pt-2 shrink-0">
                    <div class="relative">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-600 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" x-model="catSheetSearch" placeholder="{{ __('messages.search_categories') }}"
                            class="w-full pl-8 pr-8 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 shadow-sm" />
                        <button type="button" x-show="catSheetSearch.length > 0" x-cloak @click="catSheetSearch = ''" class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center rounded-full bg-slate-200 dark:bg-slate-600 text-slate-500 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-500 text-sm leading-none transition">✕</button>
                    </div>
                </div>

                <div class="overflow-y-auto p-2 grow" style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom))">
                    {{-- Form-associated hidden input: submits with the mobile filter form via its id --}}
                    <input type="hidden" name="category_id" form="mobileCatForm" x-ref="catInput" value="{{ request('category_id') }}" />
                    {{-- Level 1: All + Main list --}}
                    <template x-if="catLevel === 1">
                        <div class="space-y-0.5">
                            <button type="button" @click="pickCat(null)"
                                x-show="catSheetSearch === ''"
                                class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-bold transition {{ ! request()->filled('category_id') ? 'bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 text-white shadow-md' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                <span class="text-base">🏷️</span>
                                <span class="flex-1 min-w-0 text-left">{{ __('messages.all_categories') }}</span>
                                <span class="shrink-0 text-xs font-black {{ ! request()->filled('category_id') ? 'text-white/85' : 'text-slate-600 bg-slate-100 dark:bg-slate-800 rounded-full px-1.5 py-0.5' }}">{{ $totalProducts }}</span>
                            </button>
                            @foreach ($categoryTree as $mainRow)
                                @php
                                    $mainLower = strtolower(addslashes($mainRow->category->name));
                                    $childLowerArr = $mainRow->children->pluck('name')->map(fn($n) => strtolower(addslashes($n)))->values()->all();
                                @endphp
                                <button type="button"
                                    @if ($mainRow->children->isNotEmpty())
                                        @click="catLevel = 2; catMainId = {{ $mainRow->category->id }}; catSheetSearch = ''"
                                    @else
                                        @click="pickCat({{ $mainRow->category->id }})"
                                    @endif
                                    x-show="catSheetSearch === '' || '{{ $mainLower }}'.includes(catSheetSearch.toLowerCase()) || [{{ implode(',', array_map(fn($c) => "'".$c."'", $childLowerArr)) }}].some(s => s.includes(catSheetSearch.toLowerCase()))"
                                    class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-sm font-bold transition {{ request('category_id') == $mainRow->category->id ? 'bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 text-white shadow-md' : 'text-slate-800 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                    <span class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-7 h-7 shrink-0 rounded-lg bg-white dark:bg-slate-800 flex items-center justify-center text-sm shadow-sm border border-slate-200/70 dark:border-slate-700/60">{{ $mainRow->category->icon ?: '📦' }}</span>
                                        <span class="truncate">{{ $mainRow->category->name }}</span>
                                    </span>
                                    <span class="flex items-center gap-1.5 shrink-0">
                                        <span class="text-xs font-black {{ request('category_id') == $mainRow->category->id ? 'text-white/85' : 'text-slate-600 bg-slate-100 dark:bg-slate-800 rounded-full px-1.5 py-0.5' }}">{{ $mainRow->total }}</span>
                                        @if ($mainRow->children->isNotEmpty())
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                            </svg>
                                        @endif
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </template>

                    {{-- Level 2: Subs of the picked Main (one group visible at a time) --}}
                    @foreach ($categoryTree as $mainRow)
                        @if ($mainRow->children->isNotEmpty())
                            <div x-show="catLevel === 2 && catMainId === {{ $mainRow->category->id }}" x-cloak class="space-y-0.5">
                                <button type="button" @click="catLevel = 1; catMainId = null"
                                    class="w-full flex items-center gap-2 px-2 py-2 rounded-xl text-xs font-extrabold text-slate-500 dark:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-white transition">
                                    <span class="w-7 h-7 shrink-0 flex items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-300">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </span>
                                    <span>{{ __('messages.back') }}</span>
                                    <span class="truncate text-xs font-black text-slate-300 dark:text-slate-600">/ {{ $mainRow->category->name }}</span>
                                </button>
                                <button type="button" @click="pickCat({{ $mainRow->category->id }})"
                                    class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-bold transition {{ request('category_id') == $mainRow->category->id ? 'bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 text-white shadow-md' : 'text-slate-800 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                    <span class="w-7 h-7 shrink-0 rounded-lg bg-white dark:bg-slate-800 flex items-center justify-center text-sm shadow-sm border border-slate-200/70 dark:border-slate-700/60">{{ $mainRow->category->icon ?: '🗂️' }}</span>
                                    <span class="flex-1 min-w-0 text-left">{{ __('messages.all_in') }} {{ $mainRow->category->name }}</span>
                                    <span class="shrink-0 text-xs font-black {{ request('category_id') == $mainRow->category->id ? 'text-white/85' : 'text-slate-600 bg-slate-100 dark:bg-slate-800 rounded-full px-1.5 py-0.5' }}">{{ $mainRow->total }}</span>
                                    @if (request('category_id') == $mainRow->category->id)
                                        <svg class="w-4 h-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </button>
                                @foreach ($mainRow->children as $sub)
                                    <button type="button" @click="pickCat({{ $sub->id }})"
                                        x-show="catSheetSearch === '' || '{{ strtolower(addslashes($sub->name)) }}'.includes(catSheetSearch.toLowerCase())"
                                        class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-bold transition {{ request('category_id') == $sub->id ? 'bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 text-white shadow-md' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                        <span class="w-7 h-7 shrink-0 rounded-lg flex items-center justify-center text-xs {{ request('category_id') == $sub->id ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-500' }}">📁</span>
                                        <span class="flex-1 min-w-0 text-left truncate">{{ $sub->name }}</span>
                                        <span class="shrink-0 text-xs font-black {{ request('category_id') == $sub->id ? 'text-white/85' : 'text-slate-600 bg-slate-100 dark:bg-slate-800 rounded-full px-1.5 py-0.5' }}">{{ $sub->products_count }}</span>
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
                        <span class="w-8 h-8 shrink-0 rounded-xl bg-gradient-to-br from-violet-600 to-fuchsia-500 text-white flex items-center justify-center text-base shadow-md shadow-sky-500/20">🏷️</span>
                        <div class="min-w-0">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white font-outfit leading-tight">{{ __('messages.brands') }}</h3>
                            <p class="text-xs font-bold text-slate-600 dark:text-slate-500 leading-tight">{{ $brands->count() }} {{ __('messages.brands') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="brandOpen = false" class="w-9 h-9 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition text-sm" aria-label="Close">✕</button>
                </div>
                {{-- Search --}}
                <div class="px-3 pt-2 shrink-0">
                    <div class="relative">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-600 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" x-model="brandSheetSearch" placeholder="{{ __('messages.search_brands') }}"
                            class="w-full pl-8 pr-8 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 shadow-sm" />
                        <button type="button" x-show="brandSheetSearch.length > 0" x-cloak @click="brandSheetSearch = ''" class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center rounded-full bg-slate-200 dark:bg-slate-600 text-slate-500 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-500 text-sm leading-none transition">✕</button>
                    </div>
                </div>
                <div class="flex-1 min-h-0 overflow-y-auto p-2 scrollbar-thin" x-ref="brandScroll" @scroll="loadMoreBrands()" style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom));">
                    <input type="hidden" name="brand_id" form="mobileCatForm" value="{{ request('brand_id') }}" x-ref="brandInput" />
                    {{-- All brands --}}
                    <button type="button" @click="pickBrand(null)"
                        x-show="brandSheetSearch === ''"
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-bold transition {{ !request()->filled('brand_id') ? 'bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 text-white shadow-md' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <span class="text-base">🏷️</span>
                        <span class="flex-1 min-w-0 text-left">{{ __('messages.all_brands') }}</span>
                        <span class="shrink-0 text-xs font-black {{ !request()->filled('brand_id') ? 'text-white/85' : 'text-slate-600 bg-slate-100 dark:bg-slate-800 rounded-full px-1.5 py-0.5' }}">{{ $brands->count() }}</span>
                    </button>
                    {{-- Brands (popular first, virtual scroll) --}}
                    @foreach ($brandsSorted as $b)
                        @php $isBrandActive = request('brand_id') == $b->id; @endphp
                        <button type="button" @click="pickBrand({{ $b->id }})"
                            x-show="{{ $loop->index }} < brandVisible || brandSheetSearch !== '' && '{{ strtolower(addslashes($b->name)) }}'.includes(brandSheetSearch.toLowerCase())"
                            class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-bold transition {{ $isBrandActive ? 'bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 text-white shadow-md' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            <span class="flex-1 min-w-0 text-left truncate">{{ $b->name }}</span>
                            <span class="shrink-0 text-xs font-black {{ $isBrandActive ? 'text-white/85' : 'text-slate-600 bg-slate-100 dark:bg-slate-800 rounded-full px-1.5 py-0.5' }}">{{ $b->products_count }}</span>
                            @if ($isBrandActive)
                                <svg class="w-4 h-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
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
                 @mouseleave="closeCatTimeout = setTimeout(() => { activeCatHover = null }, 150)"
                 @mouseenter="if (closeCatTimeout) clearTimeout(closeCatTimeout)"
            >
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-600 font-outfit mb-3 flex items-center gap-1.5">
                    <span>🗂️</span> <span>{{ __('messages.categories') }}</span>
                </h2>
                {{-- Search input --}}
                <div class="relative mb-2">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" x-model="catSearch" placeholder="{{ __('messages.search_categories') ?? 'Search categories...' }}"
                        class="w-full pl-8 pr-8 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-sm" />
                    <button type="button" x-show="catSearch.length > 0" x-cloak @click="catSearch = ''" class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center rounded-full bg-slate-200 dark:bg-slate-600 text-slate-500 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-500 text-xs leading-none transition">✕</button>
                </div>
                <div class="space-y-1">
                    <a href="{{ $buildLink(['category_id' => null, 'category' => null]) }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl text-xs sm:text-sm font-bold transition {{ !request()->filled('category_id') && !request()->filled('category') ? 'bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 text-white shadow-md shadow-sky-500/20' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <span class="text-base">🏷️</span>
                        <span class="flex-1 min-w-0 truncate">{{ __('messages.all_categories') }}</span>
                        <span class="shrink-0 text-xs font-black {{ !request()->filled('category_id') && !request()->filled('category') ? 'text-white/80' : 'text-slate-600 bg-slate-100 dark:bg-slate-800 rounded-full px-1.5 py-0.5' }}">{{ $totalProducts }}</span>
                    </a>
                    @foreach ($categoryTree as $mainRow)
                        @php
                            $main = $mainRow->category;
                            $isMainActive = request('category_id') == $main->id || request('category') == $main->name;
                            $activeChildId = collect($mainRow->children)->firstWhere('id', (int) request('category_id'));
                            $hasChildren = $mainRow->children->isNotEmpty();
                            $mainLower = strtolower(addslashes($main->name));
                            $childLowerArr = $mainRow->children->pluck('name')->map(fn($n) => strtolower(addslashes($n)))->values()->all();
                        @endphp
                        <div class="relative pt-0.5"
                             x-show="catSearch === '' || '{{ $mainLower }}'.includes(catSearch.toLowerCase()) || [{{ implode(',', array_map(fn($c) => "'".$c."'", $childLowerArr)) }}].some(s => s.includes(catSearch.toLowerCase()))"
                             @mouseenter="if (closeCatTimeout) clearTimeout(closeCatTimeout); activeCatHover = {{ $main->id }}"
                        >
                            {{-- Main Category Row --}}
                            <a href="{{ $buildLink(['category_id' => $main->id, 'category' => null]) }}"
                               class="group flex items-center justify-between gap-2 px-2.5 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
                               :class="activeCatHover === {{ $main->id }} || {{ $isMainActive && !$activeChildId ? 'true' : 'false' }} ? 'bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 text-white shadow-md shadow-sky-500/20' : 'text-slate-800 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800'"
                            >
                                <span class="flex items-center gap-2 min-w-0">
                                    <span class="w-6 h-6 shrink-0 rounded-lg flex items-center justify-center text-xs shadow-2xs border transition-transform group-hover:scale-110 {{ $isMainActive && !$activeChildId ? 'bg-white/20 border-white/30 text-white' : 'bg-slate-100 dark:bg-slate-800 border-slate-200/70 dark:border-slate-700/60' }}">{{ $main->icon ?: '📦' }}</span>
                                    <span class="truncate font-myanmar">{{ $main->name }}</span>
                                </span>
                                <div class="flex items-center gap-1 shrink-0">
                                    <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full {{ $isMainActive && !$activeChildId ? 'text-white/90 bg-white/20' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 group-hover:bg-sky-100 group-hover:text-sky-700 dark:group-hover:bg-sky-950 dark:group-hover:text-sky-300' }}">
                                        {{ $mainRow->total }}
                                    </span>
                                    @if ($hasChildren)
                                        <svg class="h-3 w-3 transition-transform group-hover:translate-x-0.5 {{ $isMainActive && !$activeChildId ? 'text-white/80' : 'text-slate-400 group-hover:text-sky-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    @endif
                                </div>
                            </a>

                            {{-- Subcategory Flyout Panel (Hover popover to the right) --}}
                            @if ($hasChildren)
                                <div x-show="activeCatHover === {{ $main->id }}"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0 translate-x-1"
                                     x-transition:enter-end="opacity-100 translate-x-0"
                                     x-transition:leave="transition ease-in duration-100"
                                     x-transition:leave-start="opacity-100 translate-x-0"
                                     x-transition:leave-end="opacity-0 translate-x-1"
                                     @mouseenter="if (closeCatTimeout) clearTimeout(closeCatTimeout); activeCatHover = {{ $main->id }}"
                                     class="absolute left-full top-0 ml-3 w-72 sm:w-80 bg-white/95 dark:bg-slate-900/95 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xl p-3 z-50 backdrop-blur-xl"
                                     style="display: none;"
                                >
                                    <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100 dark:border-slate-800 px-1">
                                        <span class="text-xs font-black text-slate-900 dark:text-white font-myanmar flex items-center gap-1.5 truncate">
                                            <span>{{ $main->icon ?: '📦' }}</span>
                                            <span class="truncate">{{ $main->name }}</span>
                                        </span>
                                        <a href="{{ $buildLink(['category_id' => $main->id, 'category' => null]) }}" class="shrink-0 text-[11px] font-bold text-sky-600 dark:text-sky-400 hover:underline font-myanmar">
                                            {{ __('messages.view_all') }} →
                                        </a>
                                    </div>

                                    <div class="grid grid-cols-1 gap-1 max-h-[320px] overflow-y-auto">
                                        @foreach ($mainRow->children as $sub)
                                            @php
                                                $isSubActive = request('category_id') == $sub->id || request('category') == $sub->name;
                                                $subIcon = ($sub->icon && $sub->icon !== 'NULL' && $sub->icon !== 'null') ? $sub->icon : '▫️';
                                            @endphp
                                            <a href="{{ $buildLink(['category_id' => $sub->id, 'category' => null]) }}"
                                               class="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-xl text-xs font-bold transition group/sub {{ $isSubActive ? 'bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 text-white shadow-sm' : 'text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-800 hover:text-sky-600 dark:hover:text-sky-400' }}">
                                                <span class="flex items-center gap-2 min-w-0">
                                                    <span class="text-xs shrink-0 {{ $isSubActive ? 'text-white' : 'text-slate-400 group-hover/sub:text-sky-500' }}">{{ $subIcon }}</span>
                                                    <span class="truncate font-myanmar">{{ $sub->name }}</span>
                                                </span>
                                                <span class="shrink-0 text-[10px] font-black px-1.5 py-0.5 rounded-full {{ $isSubActive ? 'text-white/90 bg-white/20' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 group-hover/sub:bg-sky-100 group-hover/sub:text-sky-700 dark:group-hover/sub:bg-sky-950 dark:group-hover/sub:text-sky-300' }}">
                                                    {{ $sub->products_count }}
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Brands (with search & hover related categories flyout) --}}
            @if ($brands->count() > 0)
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/90 dark:border-slate-800/80 shadow-xl relative z-20"
                     x-data="{ activeBrandHover: null, closeBrandTimeout: null }"
                     @mouseleave="closeBrandTimeout = setTimeout(() => { activeBrandHover = null }, 150)"
                     @mouseenter="if (closeBrandTimeout) clearTimeout(closeBrandTimeout)"
                >
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-600 font-outfit mb-3 flex items-center gap-1.5">
                        <span>🏷️</span> <span>{{ __('messages.brands') }}</span>
                    </h2>
                    {{-- Search input --}}
                    <div class="relative mb-2">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" x-model="brandSearch" placeholder="{{ __('messages.search_brands') ?? 'Search brands...' }}"
                            class="w-full pl-8 pr-8 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-sm" />
                        <button type="button" x-show="brandSearch.length > 0" x-cloak @click="brandSearch = ''" class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center rounded-full bg-slate-200 dark:bg-slate-600 text-slate-500 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-500 text-xs leading-none transition">✕</button>
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ $buildLink(['brand_id' => null, 'brand' => null]) }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl text-xs sm:text-sm font-bold transition {{ !request()->filled('brand_id') && !request()->filled('brand') ? 'bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 text-white shadow-md shadow-sky-500/20' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            <span class="flex-1 min-w-0 truncate">{{ __('messages.all_brands') }}</span>
                            <span class="shrink-0 text-xs font-black {{ !request()->filled('brand_id') && !request()->filled('brand') ? 'text-white/80' : 'text-slate-600 bg-slate-100 dark:bg-slate-800 rounded-full px-1.5 py-0.5' }}">{{ $brands->count() }}</span>
                        </a>
                        @foreach ($brands as $b)
                            @php
                                $isBrandActive = request('brand_id') == $b->id || request('brand') == $b->name;
                                $hasRelCats = isset($b->related_categories) && $b->related_categories->isNotEmpty();
                            @endphp
                            <div class="relative pt-0.5"
                                 x-show="brandSearch === '' || '{{ strtolower(addslashes($b->name)) }}'.includes(brandSearch.toLowerCase())"
                                 @mouseenter="if (closeBrandTimeout) clearTimeout(closeBrandTimeout); activeBrandHover = {{ $b->id }}"
                            >
                                <a href="{{ $buildLink(['brand_id' => $b->id, 'brand' => null]) }}"
                                   class="group flex items-center justify-between gap-2 px-2.5 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all"
                                   :class="activeBrandHover === {{ $b->id }} || {{ $isBrandActive ? 'true' : 'false' }} ? 'bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 text-white shadow-md shadow-sky-500/20' : 'text-slate-800 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                >
                                    <span class="flex-1 min-w-0 truncate font-myanmar">{{ $b->name }}</span>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full {{ $isBrandActive ? 'text-white/90 bg-white/20' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 group-hover:bg-sky-100 group-hover:text-sky-700 dark:group-hover:bg-sky-950 dark:group-hover:text-sky-300' }}">
                                            {{ $b->products_count }}
                                        </span>
                                        @if ($hasRelCats)
                                            <svg class="h-3 w-3 transition-transform group-hover:translate-x-0.5 {{ $isBrandActive ? 'text-white/80' : 'text-slate-400 group-hover:text-sky-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        @endif
                                    </div>
                                </a>

                                {{-- Brand Related Categories Flyout Panel --}}
                                @if ($hasRelCats)
                                    <div x-show="activeBrandHover === {{ $b->id }}"
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 translate-x-1"
                                         x-transition:enter-end="opacity-100 translate-x-0"
                                         x-transition:leave="transition ease-in duration-100"
                                         x-transition:leave-start="opacity-100 translate-x-0"
                                         x-transition:leave-end="opacity-0 translate-x-1"
                                         @mouseenter="if (closeBrandTimeout) clearTimeout(closeBrandTimeout); activeBrandHover = {{ $b->id }}"
                                         class="absolute left-full top-0 ml-3 w-72 sm:w-80 bg-white/95 dark:bg-slate-900/95 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xl p-3 z-50 backdrop-blur-xl"
                                         style="display: none;"
                                    >
                                        <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100 dark:border-slate-800 px-1">
                                            <span class="text-xs font-black text-slate-900 dark:text-white font-myanmar flex items-center gap-1.5 truncate">
                                                <span>🏷️</span>
                                                <span class="truncate">{{ $b->name }}</span>
                                            </span>
                                            <a href="{{ $buildLink(['brand_id' => $b->id, 'brand' => null]) }}" class="shrink-0 text-[11px] font-bold text-sky-600 dark:text-sky-400 hover:underline font-myanmar">
                                                {{ __('messages.view_all') }} →
                                            </a>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 max-h-[300px] overflow-y-auto">
                                            @foreach ($b->related_categories as $relCat)
                                                @php
                                                    $isCatActive = request('brand_id') == $b->id && (request('category_id') == $relCat->id || request('category') == $relCat->name);
                                                    $cIcon = ($relCat->icon && $relCat->icon !== 'NULL' && $relCat->icon !== 'null') ? $relCat->icon : '📁';
                                                @endphp
                                                <a href="{{ $buildLink(['brand_id' => $b->id, 'category_id' => $relCat->id, 'brand' => null, 'category' => null]) }}"
                                                   class="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-xl text-xs font-bold transition group/rel {{ $isCatActive ? 'bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 text-white shadow-sm' : 'text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-800 hover:text-sky-600 dark:hover:text-sky-400' }}">
                                                    <span class="flex items-center gap-2 min-w-0">
                                                        <span class="text-xs shrink-0 {{ $isCatActive ? 'text-white' : 'text-slate-400 group-hover/rel:text-sky-500' }}">{{ $cIcon }}</span>
                                                        <span class="truncate font-myanmar">{{ $relCat->name }}</span>
                                                    </span>
                                                    <span class="shrink-0 text-[10px] font-black px-1.5 py-0.5 rounded-full {{ $isCatActive ? 'text-white/90 bg-white/20' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 group-hover/rel:bg-sky-100 group-hover/rel:text-sky-700 dark:group-hover/rel:bg-sky-950 dark:group-hover/rel:text-sky-300' }}">
                                                        {{ $relCat->products_count }}
                                                    </span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        {{-- No results message --}}
                        <div x-show="brandSearch.length > 0 && [...document.querySelectorAll('[x-show*=brandSearch]')].every(el => el.style.display === 'none')" x-cloak class="px-3 py-4 text-center text-xs text-slate-600 dark:text-slate-500">
                            🔍 {{ __('messages.no_results') ?? 'No results' }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- Price filter box (Linn style: min/max + Apply) --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/90 dark:border-slate-800/80 shadow-xl">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-600 font-outfit mb-3 flex items-center gap-1.5">
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
                            <label for="sidebarMinPrice" class="block text-xs font-bold text-slate-600 dark:text-slate-500 mb-1">{{ __('messages.min_price') }} (Ks)</label>
                            <input id="sidebarMinPrice" type="number" name="min_price" min="0" step="1000" value="{{ request('min_price') }}" placeholder="0" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 shadow-sm" />
                        </div>
                        <div>
                            <label for="sidebarMaxPrice" class="block text-xs font-bold text-slate-600 dark:text-slate-500 mb-1">{{ __('messages.max_price') }} (Ks)</label>
                            <input id="sidebarMaxPrice" type="number" name="max_price" min="0" step="1000" value="{{ request('max_price') }}" placeholder="∞" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-sky-500 shadow-sm" />
                        </div>
                    </div>
                    <button type="submit" class="w-full px-4 py-2.5 bg-gradient-to-r from-violet-600 to-fuchsia-500 hover:from-violet-600 hover:to-violet-500 text-white text-xs font-extrabold rounded-xl shadow-md shadow-sky-500/20 transition active:scale-95">
                        {{ __('messages.apply') }}
                    </button>
                </form>
            </div>

            {{-- Stock status --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/90 dark:border-slate-800/80 shadow-xl">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-600 font-outfit mb-3 flex items-center gap-1.5">
                    <span>📦</span> <span>{{ __('messages.stock_status') }}</span>
                </h2>
                <div class="space-y-1">
                    <a href="{{ $buildLink(['stock_status' => 'in_stock']) }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl text-sm font-bold transition {{ request('stock_status') === 'in_stock' ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-md shadow-emerald-500/20' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>{{ __('messages.in_stock') }}</span>
                    </a>
                    <a href="{{ $buildLink(['stock_status' => 'out_of_stock']) }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl text-sm font-bold transition {{ request('stock_status') === 'out_of_stock' ? 'bg-gradient-to-r from-rose-500 to-red-600 text-white shadow-md shadow-rose-500/20' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span>{{ __('messages.out_of_stock') }}</span>
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
