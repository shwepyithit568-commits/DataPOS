@extends('layouts.storefront.app')

@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $listUrl = function (array $params = []) use ($storeSlug) {
        if ($storeSlug) {
            $params['store_slug'] = $storeSlug;
        }
        return url('/products?' . http_build_query($params));
    };
    $reqCatId = (string) request('category_id');
    $matchingRow = $reqCatId ? $browseRows->first(fn ($row) => (string) $row->category->id === $reqCatId) : null;
    $activeMainId = $matchingRow ? (string) $matchingRow->category->id : ($browseRows->first()?->category->id ?? '');

    // Prepare serializable category tree for Alpine.js
    $categoriesJson = $browseRows->map(function ($row) use ($listUrl) {
        $main = $row->category;
        $childIds = $row->children->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
        $allIds = array_merge([(string) $main->id], $childIds);

        return [
            'id' => (string) $main->id,
            'name' => $main->name,
            'icon' => $main->icon ?: '📦',
            'image' => $main->image_path ? asset('storage/' . $main->image_path) : null,
            'total' => (int) $row->total,
            'all_ids' => $allIds,
            'all_url' => $listUrl(['category_id' => $main->id]),
            'brands' => $row->brands->map(function ($b) use ($listUrl) {
                $brand = $b['brand'];
                return [
                    'id' => (string) $brand->id,
                    'name' => $brand->name,
                    'count' => (int) $b['count'],
                    'image' => $b['image'] ?? ($brand->logo_path ? asset('storage/' . $brand->logo_path) : null),
                    'logo' => $brand->logo_path ? asset('storage/' . $brand->logo_path) : null,
                    'initial' => mb_substr($brand->name, 0, 1),
                    'url' => $listUrl(['brand_id' => $brand->id]),
                ];
            })->values()->all(),
            'children' => $row->children->map(function ($sub) {
                return [
                    'id' => (string) $sub->id,
                    'name' => $sub->name,
                    'icon' => $sub->icon ?: '📁',
                    'count' => (int) $sub->products_count,
                ];
            })->values()->all(),
        ];
    })->values()->all();
@endphp

<div class="w-full space-y-1 sm:space-y-2 pb-16 sm:pb-12"
     x-data="{
         search: '',
         active: '{{ $activeMainId }}',
         activeSub: 'all',
         activeBrand: 'all',
         categories: {{ Js::from($categoriesJson) }},
         get filteredCategories() {
             const q = this.search.trim().toLowerCase();
             if (!q) return this.categories;
             return this.categories.filter(c => {
                 const matchMain = c.name.toLowerCase().includes(q);
                 const matchSub = c.children.some(s => s.name.toLowerCase().includes(q));
                 return matchMain || matchSub;
             });
         },
         get currentCategory() {
             const found = this.categories.find(c => c.id === this.active);
             return found || this.filteredCategories[0] || null;
         },
         selectCategory(id) {
             this.active = id;
             this.activeSub = 'all';
             this.activeBrand = 'all';
             this.$nextTick(() => {
                 const rightPane = this.$refs.rightPane;
                 if (rightPane) rightPane.scrollTop = 0;
                 const activeBtn = this.$refs.rail?.querySelector('[aria-selected=&quot;true&quot;]');
                 if (activeBtn) activeBtn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
             });
         },
         isProductVisible(catId, brandId, prodName) {
             const q = this.search.trim().toLowerCase();
             if (q && !prodName.toLowerCase().includes(q)) {
                 return false;
             }
             const cur = this.currentCategory;
             if (!cur) return false;

             // Sub-category filter
             if (this.activeSub !== 'all' && String(catId) !== String(this.activeSub)) {
                 return false;
             }
             if (this.activeSub === 'all' && !cur.all_ids.includes(String(catId))) {
                 return false;
             }

             // Brand filter
             if (this.activeBrand !== 'all' && String(brandId) !== String(this.activeBrand)) {
                 return false;
             }

             return true;
         },
         init() {
             if (this.categories.length > 0 && !this.active) {
                 this.active = this.categories[0].id;
             }
             this.$nextTick(() => {
                 const activeBtn = this.$refs.rail?.querySelector('[aria-selected=&quot;true&quot;]');
                 if (activeBtn) activeBtn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
             });
         }
     }"
     x-init="init()">


    {{-- ═══════════════════════════════════════════════
         Main Two-Pane Split Container
         - Left: Main Category (Square Product Card Style)
         - Right: Sub Category Horizontal Scroll + Brands Horizontal Scroll + Products Grid
    ═══════════════════════════════════════════════ --}}
    <div class="sf-browse-container rounded-lg sm:rounded-xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">

        {{-- ══════════════════════════════════
             LEFT RAIL: Main Categories as Square Product Cards
             — Aspect Square, Icon/Thumbnail, Title, Product Count
             — 3D Tactile Push System with border-b-[3px]
        ══════════════════════════════════ --}}
        <nav class="sf-browse-rail border-r border-slate-200/90 dark:border-slate-800/90 bg-slate-50/70 dark:bg-slate-900/60 overflow-y-auto overscroll-contain scrollbar-thin p-0.5 sm:p-1 space-y-0.5 sm:space-y-1 select-none"
             x-ref="rail"
             aria-label="{{ __('messages.browse_categories') }}">

            <template x-for="cat in filteredCategories" :key="cat.id">
                <button
                    type="button"
                    @click="selectCategory(cat.id)"
                    class="sf-btn-3d w-full aspect-[3/4] flex flex-col p-0 rounded-none transition-all duration-150 relative text-center group overflow-hidden select-none"
                    :class="active === cat.id
                        ? 'active shadow-md'
                        : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700'"
                    :aria-selected="active === cat.id ? 'true' : 'false'"
                >
                    {{-- Count Badge in top right corner --}}
                    <span class="absolute top-1 right-1 rounded-full px-1.5 py-0.2 text-[8px] sm:text-[9px] font-black z-10 shadow-xs"
                          :class="active === cat.id ? 'bg-white/30 text-white backdrop-blur-xs' : 'bg-black/60 text-white backdrop-blur-xs'"
                          x-text="cat.total"></span>

                    {{-- Upper 3/4: Edge-to-edge full bleed image with no side borders --}}
                    <div class="relative w-full h-[77%] sm:h-[80%] bg-slate-100 dark:bg-slate-800/90 overflow-hidden shrink-0 border-b border-slate-200/50 dark:border-slate-700/50 flex items-center justify-center">
                        <template x-if="cat.image">
                            <img :src="cat.image" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200 pointer-events-none" loading="lazy" decoding="async">
                        </template>
                        <template x-if="!cat.image">
                            <span class="text-2xl sm:text-3xl text-slate-500 dark:text-slate-400" x-text="cat.icon" aria-hidden="true"></span>
                        </template>
                    </div>

                    {{-- Lower 1/4: Category Name --}}
                    <div class="flex-1 w-full flex items-center justify-center px-0.5 py-0 min-h-0">
                        <span class="block text-[8.5px] sm:text-[9.5px] lg:text-[10px] font-black leading-tight line-clamp-2 w-full text-center" x-text="cat.name"></span>
                    </div>
                </button>
            </template>

            {{-- Empty search fallback for left rail --}}
            <template x-if="filteredCategories.length === 0">
                <div class="p-2 text-center text-slate-400 dark:text-slate-500 text-[10px] font-bold">
                    {{ __('messages.browse_no_match') }}
                </div>
            </template>
        </nav>

        {{-- ══════════════════════════════════
             RIGHT PANEL:
             1. Sub-Category Horizontal Scroll (Top Bar)
             2. Brands Card Horizontal Scroll (Below Sub-Category)
             3. Products Grid
        ══════════════════════════════════ --}}
        <main class="flex-1 min-w-0 bg-white dark:bg-slate-900 overflow-y-auto overscroll-contain scrollbar-thin px-1 sm:px-2 lg:px-2.5 pb-2 pt-0 space-y-1.5 sm:space-y-2"
              x-ref="rightPane">

            <template x-if="currentCategory">
                <div class="space-y-1.5 sm:space-y-2">

                    {{-- ══ Sticky Controls: Sub-Categories Strip + Brands Strip ══ --}}
                    <div class="sticky top-0 z-30 bg-white dark:bg-slate-900 -mx-1 sm:-mx-2 lg:-mx-2.5 px-1 sm:px-2 lg:px-2.5 pt-1.5 pb-1 sm:pt-2 sm:pb-1.5 border-b border-slate-200/80 dark:border-slate-800 shadow-xs space-y-1">

                        {{-- ══ 1. Sub-Categories Horizontal Scroll Strip ══ --}}
                        <div x-show="currentCategory && currentCategory.children && currentCategory.children.length > 0">
                            <div class="flex items-center gap-0.5 sm:gap-1 overflow-x-auto scrollbar-thin py-0.5 -mx-0.5 px-0.5 select-none">
                                {{-- 'All' Sub-category Pill --}}
                                <button
                                    type="button"
                                    @click="activeSub = 'all'"
                                    class="sf-btn-3d shrink-0 !flex-row items-center gap-1.5 px-3.5 h-9 sm:h-10 rounded-none text-xs font-black transition"
                                    :class="activeSub === 'all' ? 'active' : ''"
                                >
                                    <span>📂</span>
                                    <span>{{ __('messages.all') ?? 'All' }}</span>
                                    <span class="rounded-full px-1.5 py-0.2 text-[10px] font-black"
                                          :class="activeSub === 'all' ? 'bg-white/30 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400'"
                                          x-text="currentCategory ? currentCategory.total : 0"></span>
                                </button>

                                {{-- Sub-category Pills --}}
                                <template x-for="sub in (currentCategory ? currentCategory.children : [])" :key="sub.id">
                                    <button
                                        type="button"
                                        @click="activeSub = sub.id"
                                        class="sf-btn-3d shrink-0 !flex-row items-center gap-1.5 px-3.5 h-9 sm:h-10 rounded-none text-xs font-black transition"
                                        :class="activeSub === sub.id ? 'active' : ''"
                                    >
                                        <span x-text="sub.icon || '📁'"></span>
                                        <span class="whitespace-nowrap" x-text="sub.name"></span>
                                        <span class="rounded-full px-1.5 py-0.2 text-[10px] font-black"
                                              :class="activeSub === sub.id ? 'bg-white/30 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400'"
                                              x-text="sub.count"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- ══ 2. Brands Card Horizontal Scroll (Directly below Sub-Category) ══ --}}
                        <div x-show="currentCategory && currentCategory.brands && currentCategory.brands.length > 0" class="space-y-1 pt-0.5 border-t border-slate-100 dark:border-slate-800/60">
                            {{-- Brands Strip Header --}}
                            <div class="flex items-center justify-between px-0.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs">🏷️</span>
                                    <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('messages.brands') }}</span>
                                </div>
                                <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500" x-text="(currentCategory && currentCategory.brands ? currentCategory.brands.length : 0) + ' {{ __('messages.brands') }}'"></span>
                            </div>

                            {{-- Brand Cards Horizontal Track --}}
                            <div class="flex items-center gap-0.5 sm:gap-1 overflow-x-auto scrollbar-thin py-0.5 -mx-0.5 px-0.5 select-none">
                                {{-- All Brands Card --}}
                                <button
                                    type="button"
                                    @click="activeBrand = 'all'"
                                    class="sf-btn-3d sf-brand-card shrink-0 flex flex-col p-0 rounded-none transition text-center relative group overflow-hidden select-none"
                                    :class="activeBrand === 'all'
                                        ? 'active shadow-sm'
                                        : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                >
                                    <span class="absolute top-1 right-1 rounded-full px-1.5 py-0.2 text-[8px] sm:text-[9px] font-black z-10 shadow-xs"
                                          :class="activeBrand === 'all' ? 'bg-white/30 text-white backdrop-blur-xs' : 'bg-black/60 text-white backdrop-blur-xs'"
                                          x-text="currentCategory ? currentCategory.total : 0"></span>
                                    <div class="relative w-full h-[74%] sm:h-[76%] bg-slate-100 dark:bg-slate-800/90 overflow-hidden shrink-0 border-b border-slate-200/50 dark:border-slate-700/50 flex items-center justify-center">
                                        <span class="text-xl sm:text-2xl group-hover:scale-110 transition" aria-hidden="true">🏷️</span>
                                    </div>
                                    <div class="flex-1 w-full flex items-center justify-center px-0.5 py-0 min-h-0">
                                        <span class="block text-[8.5px] sm:text-[9.5px] font-black leading-none line-clamp-1 w-full px-0.5 text-center">{{ __('messages.all') ?? 'All' }}</span>
                                    </div>
                                </button>

                                {{-- Brand Cards --}}
                                <template x-for="brand in (currentCategory ? currentCategory.brands : [])" :key="brand.id">
                                    <button
                                        type="button"
                                        @click="activeBrand = (activeBrand === brand.id ? 'all' : brand.id)"
                                        class="sf-btn-3d sf-brand-card shrink-0 flex flex-col p-0 rounded-none transition text-center relative group overflow-hidden select-none"
                                        :class="activeBrand === brand.id
                                            ? 'active shadow-sm'
                                            : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                    >
                                        <span class="absolute top-1 right-1 rounded-full px-1.5 py-0.2 text-[8px] sm:text-[9px] font-black z-10 shadow-xs"
                                              :class="activeBrand === brand.id ? 'bg-white/30 text-white backdrop-blur-xs' : 'bg-black/60 text-white backdrop-blur-xs'"
                                              x-text="brand.count"></span>
                                        <div class="relative w-full h-[74%] sm:h-[76%] bg-slate-100 dark:bg-slate-800/90 overflow-hidden shrink-0 border-b border-slate-200/50 dark:border-slate-700/50 flex items-center justify-center">
                                            <template x-if="brand.image || brand.logo">
                                                <img :src="brand.image || brand.logo" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200 pointer-events-none" loading="lazy" decoding="async">
                                            </template>
                                            <template x-if="!brand.image && !brand.logo">
                                                <span class="font-black text-sm sm:text-base text-slate-700 dark:text-slate-200" x-text="brand.initial"></span>
                                            </template>
                                        </div>
                                        <div class="flex-1 w-full flex items-center justify-center px-0.5 py-0 min-h-0">
                                            <span class="block text-[8.5px] sm:text-[9.5px] font-black leading-none line-clamp-1 w-full px-0.5 text-center" x-text="brand.name"></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>

                    </div>

                    {{-- ══ Active Category / Filter Indicator Header & View in Catalog ══ --}}
                    <div class="flex items-center justify-between gap-2 px-1">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-base sm:text-lg" x-text="currentCategory.icon"></span>
                            <h2 class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-100 truncate" x-text="currentCategory.name"></h2>
                        </div>
                        <a :href="currentCategory.all_url"
                           class="sf-btn-3d shrink-0 !flex-row items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold text-slate-600 dark:text-slate-300 hover:text-[color:var(--sf-primary)]">
                            <span>{{ __('messages.browse_view_catalog') }}</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>

                    {{-- ══ 3. Products Grid ══ --}}
                    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-0.5 sm:gap-1 lg:gap-1.5">
                        @foreach ($products as $product)
                            <div
                                x-show="isProductVisible('{{ $product->category_id }}', '{{ $product->brand_id ?? '' }}', '{{ addslashes($product->name) }}')"
                                x-cloak
                                class="relative isolate transition-all duration-150"
                            >
                                <x-product-card
                                    :product="$product"
                                    :store="$store"
                                    :isWholesaleApproved="false"
                                    :dense="false"
                                    :rounded="'rounded-none'"
                                />
                            </div>
                        @endforeach
                    </div>

                    {{-- ══ Fallback when no products match ══ --}}
                    <div class="p-8 sm:p-12 text-center text-slate-400 dark:text-slate-500 space-y-2"
                         x-show="false">
                        <span class="text-3xl" aria-hidden="true">📦</span>
                        <p class="text-xs font-bold">{{ __('messages.no_products_found') }}</p>
                    </div>

                </div>
            </template>

            {{-- Empty overall fallback --}}
            <template x-if="!currentCategory">
                <div class="flex flex-col items-center justify-center p-8 sm:p-12 text-center space-y-2">
                    <span class="text-4xl" aria-hidden="true">🗂️</span>
                    <p class="text-sm font-black text-slate-700 dark:text-slate-300">{{ __('messages.browse_no_match') }}</p>
                </div>
            </template>
        </main>
    </div>
</div>
@endsection
