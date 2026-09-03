@extends('layouts.admin.app')

@section('title', __('messages.web_catalog_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $baseParams = $storeRouteParams;
    $currentSort = request()->only('sort', 'search');
    $clearFiltersUrl = route('store.admin.web_products.index', $baseParams);

    // Accent color tokens for KPI stat cards
    $statAccents = [
        'total'        => 'bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300',
        'online'       => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300',
        'counter_only' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        'featured'     => 'bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300',
        'in_stock'     => 'bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300',
        'on_sale'      => 'bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300',
    ];

    $statBorders = [
        'total'        => 'hover:border-violet-300 dark:hover:border-violet-700/80',
        'online'       => 'hover:border-emerald-300 dark:hover:border-emerald-700/80',
        'counter_only' => 'hover:border-slate-300 dark:hover:border-slate-600',
        'featured'     => 'hover:border-amber-300 dark:hover:border-amber-700/80',
        'in_stock'     => 'hover:border-sky-300 dark:hover:border-sky-700/80',
        'on_sale'      => 'hover:border-rose-300 dark:hover:border-rose-700/80',
    ];
@endphp

@section('content')
@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection $products */
@endphp
<div class="w-full space-y-0.5 pb-6"
     x-data="{
        viewMode: localStorage.getItem('admin_web_products_view_mode') || 'table',
        setView(mode) {
            this.viewMode = mode;
            localStorage.setItem('admin_web_products_view_mode', mode);
        },
        selectedIds: [],
        selectAll: false,
        categoryModal: false,
        loadingId: null,
        toastShow: false,
        toastMsg: '',
        productStates: {
            @foreach($products as $p)
                {{ $p->id }}: {
                    is_ecommerce: {{ $p->is_ecommerce ? 'true' : 'false' }},
                    is_featured: {{ $p->is_featured ? 'true' : 'false' }}
                },
            @endforeach
        },

        showToast(msg) {
            this.toastMsg = msg;
            this.toastShow = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => this.toastShow = false, 2600);
        },

        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedIds = Array.from(document.querySelectorAll('.product-select-cb')).map(cb => parseInt(cb.value));
            } else {
                this.selectedIds = [];
            }
        },

        toggleAllFromList(ids) {
            this.selectedIds = Array.from(new Set([...this.selectedIds, ...ids]));
        },

        async toggleVisibility(productId) {
            this.loadingId = 'vis-' + productId;
            try {
                const res = await fetch('{{ route('store.admin.web_products.toggle_visibility', $storeRouteParams) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ product_id: productId })
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(data.message);
                    if (this.productStates[productId]) {
                        this.productStates[productId].is_ecommerce = Boolean(data.is_ecommerce);
                    }
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loadingId = null;
            }
        },

        async toggleFeatured(productId) {
            this.loadingId = 'feat-' + productId;
            try {
                const res = await fetch('{{ route('store.admin.web_products.toggle_featured', $storeRouteParams) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ product_id: productId })
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(data.message);
                    if (this.productStates[productId]) {
                        this.productStates[productId].is_featured = Boolean(data.is_featured);
                    }
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loadingId = null;
            }
        }
     }">

    {{-- Floating Toast Notification --}}
    <div x-show="toastShow" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-4 right-4 z-50 px-3.5 py-2 rounded-lg bg-slate-900 text-white dark:bg-white dark:text-slate-900 text-xs font-bold shadow-xl border border-slate-700 dark:border-slate-200 flex items-center gap-2">
        <span class="text-emerald-400 dark:text-emerald-600 font-black">✓</span>
        <span x-text="toastMsg"></span>
    </div>

    {{-- ── 1. Compact Header Banner (34px - 38px) ───────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 grid place-items-center text-base font-bold shadow-xs shrink-0">
                🌐
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.web_catalog_title') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.web_catalog_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0 flex-wrap">
            {{-- Category Breakdown Button --}}
            <button type="button" @click="categoryModal = true"
                    class="h-7 px-2.5 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>{{ __('messages.web_catalog_category_breakdown') }}</span>
            </button>

            {{-- Excel & CSV Export Dropdown --}}
            <div class="relative" x-data="{ exportOpen: false }">
                <button type="button" @click="exportOpen = !exportOpen"
                        class="h-7 px-2.5 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-2xs hover:shadow-emerald-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ __('messages.export_excel') }}</span>
                    <svg class="w-3 h-3 text-emerald-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div x-show="exportOpen" @click.outside="exportOpen = false" x-cloak
                     class="absolute right-0 mt-1 w-44 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-slate-200 dark:border-slate-700 py-1 z-30 text-xs font-bold">
                    <a href="{{ route('store.admin.web_products.export', array_merge($baseParams, request()->all(), ['format' => 'xlsx'])) }}"
                       @click="exportOpen = false"
                       class="flex items-center gap-2 px-3 py-1.5 text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-600">
                        <span class="text-emerald-600 font-black">📊</span>
                        <span>Excel (.xlsx)</span>
                    </a>
                    <a href="{{ route('store.admin.web_products.export', array_merge($baseParams, request()->all(), ['format' => 'csv'])) }}"
                       @click="exportOpen = false"
                       class="flex items-center gap-2 px-3 py-1.5 text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-600">
                        <span class="text-slate-500 font-black">📄</span>
                        <span>CSV (.csv)</span>
                    </a>
                </div>
            </div>

            {{-- Preview Storefront Button --}}
            <a href="{{ route('storefront.store.home', ['store_slug' => $store->slug]) }}" target="_blank"
               class="h-7 px-3 rounded-md bg-sky-600 hover:bg-sky-500 text-white text-xs font-black shadow-2xs hover:shadow-sky-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>{{ __('messages.web_catalog_preview_storefront') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 p-2 sm:p-2.5 flex items-center justify-between text-xs font-bold text-emerald-800 dark:text-emerald-200 shadow-2xs">
            <div class="flex items-center gap-2">
                <span class="text-sm">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300 font-bold px-1.5 py-0.5 cursor-pointer" aria-label="Close">&times;</button>
        </div>
    @endif

    {{-- ── 2. Centered Row-Based Stat Cards (Ultra-Dense 6 Cards) ──────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-0.5 sm:gap-1" role="list">
        {{-- Card 1: Total Products --}}
        <a href="{{ $clearFiltersUrl }}" role="listitem"
           class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition hover:border-violet-400 dark:hover:border-violet-600">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center {{ $statAccents['total'] }} shadow-inner text-xs sm:text-sm font-bold">
                📦
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit truncate">
                    {{ number_format($stats['total_products']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.web_catalog_total_products') }}
                </p>
            </div>
        </a>

        {{-- Card 2: Online Storefront --}}
        <a href="{{ route('store.admin.web_products.index', array_merge($baseParams, $currentSort, ['visibility' => 'online'])) }}" role="listitem"
           class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition hover:border-emerald-400 dark:hover:border-emerald-600 {{ request('visibility') === 'online' ? 'ring-1 ring-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/20' : '' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center {{ $statAccents['online'] }} shadow-inner text-xs sm:text-sm font-bold">
                🌐
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit truncate">
                    {{ number_format($stats['online_products']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.web_catalog_online_products') }}
                </p>
            </div>
        </a>

        {{-- Card 3: Counter Only --}}
        <a href="{{ route('store.admin.web_products.index', array_merge($baseParams, $currentSort, ['visibility' => 'counter_only'])) }}" role="listitem"
           class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition hover:border-slate-400 dark:hover:border-slate-600 {{ request('visibility') === 'counter_only' ? 'ring-1 ring-slate-500 bg-slate-50/20 dark:bg-slate-800/20' : '' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center {{ $statAccents['counter_only'] }} shadow-inner text-xs sm:text-sm font-bold">
                🏪
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-700 dark:text-slate-300 leading-none tabular-nums font-outfit truncate">
                    {{ number_format($stats['counter_only_products']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.web_catalog_counter_only') }}
                </p>
            </div>
        </a>

        {{-- Card 4: Featured --}}
        <a href="{{ route('store.admin.web_products.index', array_merge($baseParams, $currentSort, ['featured' => 'featured'])) }}" role="listitem"
           class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition hover:border-amber-400 dark:hover:border-amber-600 {{ request('featured') === 'featured' ? 'ring-1 ring-amber-500 bg-amber-50/20 dark:bg-amber-950/20' : '' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center {{ $statAccents['featured'] }} shadow-inner text-xs sm:text-sm font-bold">
                ⭐
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-outfit truncate">
                    {{ number_format($stats['featured_products']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.web_catalog_featured_products') }}
                </p>
            </div>
        </a>

        {{-- Card 5: In Stock Online --}}
        <a href="{{ route('store.admin.web_products.index', array_merge($baseParams, $currentSort, ['visibility' => 'online', 'stock_status' => 'in_stock'])) }}" role="listitem"
           class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition hover:border-sky-400 dark:hover:border-sky-600 {{ request('stock_status') === 'in_stock' ? 'ring-1 ring-sky-500 bg-sky-50/20 dark:bg-sky-950/20' : '' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center {{ $statAccents['in_stock'] }} shadow-inner text-xs sm:text-sm font-bold">
                ✓
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-sky-600 dark:text-sky-400 leading-none tabular-nums font-outfit truncate">
                    {{ number_format($stats['online_in_stock']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.web_catalog_in_stock_online') }}
                </p>
            </div>
        </a>

        {{-- Card 6: On Sale --}}
        <a href="{{ route('store.admin.web_products.index', array_merge($baseParams, $currentSort, ['sale_status' => 'on_sale'])) }}" role="listitem"
           class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition hover:border-rose-400 dark:hover:border-rose-600 {{ request('sale_status') === 'on_sale' ? 'ring-1 ring-rose-500 bg-rose-50/20 dark:bg-rose-950/20' : '' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center {{ $statAccents['on_sale'] }} shadow-inner text-xs sm:text-sm font-bold">
                🏷️
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-outfit truncate">
                    {{ number_format($stats['on_sale_products']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.web_catalog_on_sale') }}
                </p>
            </div>
        </a>
    </div>

    {{-- ── 3. Interactive Toolbar & Filters ─────────────────────────────── --}}
    <div class="bg-white dark:bg-slate-900 px-2 sm:px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-1.5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-1.5">
            {{-- Status Filter Pills --}}
            <div class="flex items-center gap-1 overflow-x-auto pb-0.5 sm:pb-0 scrollbar-none shrink-0">
                @php
                    $currentVis = request('visibility');
                    $currentFeat = request('featured');
                    $currentSale = request('sale_status');
                    $isAllActive = ! $currentVis && ! $currentFeat && ! $currentSale;
                @endphp
                <a href="{{ $clearFiltersUrl }}"
                   class="h-7 px-2.5 rounded text-xs font-bold transition inline-flex items-center gap-1.5 whitespace-nowrap {{ $isAllActive ? 'bg-sky-600 text-white shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                    <span>{{ __('messages.all') }}</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full font-mono font-black {{ $isAllActive ? 'bg-sky-700/80 text-white' : 'bg-white/80 dark:bg-slate-900 text-slate-600 dark:text-slate-300' }}">
                        {{ number_format($stats['total_products']) }}
                    </span>
                </a>
                <a href="{{ route('store.admin.web_products.index', array_merge($baseParams, $currentSort, ['visibility' => 'online'])) }}"
                   class="h-7 px-2.5 rounded text-xs font-bold transition inline-flex items-center gap-1.5 whitespace-nowrap {{ $currentVis === 'online' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                    <span>{{ __('messages.web_catalog_filter_online') }}</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full font-mono font-black {{ $currentVis === 'online' ? 'bg-emerald-700/80 text-white' : 'bg-white/80 dark:bg-slate-900 text-slate-600 dark:text-slate-300' }}">
                        {{ number_format($stats['online_products']) }}
                    </span>
                </a>
                <a href="{{ route('store.admin.web_products.index', array_merge($baseParams, $currentSort, ['visibility' => 'counter_only'])) }}"
                   class="h-7 px-2.5 rounded text-xs font-bold transition inline-flex items-center gap-1.5 whitespace-nowrap {{ $currentVis === 'counter_only' ? 'bg-slate-700 text-white shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                    <span>{{ __('messages.web_catalog_filter_counter') }}</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full font-mono font-black {{ $currentVis === 'counter_only' ? 'bg-slate-800 text-white' : 'bg-white/80 dark:bg-slate-900 text-slate-600 dark:text-slate-300' }}">
                        {{ number_format($stats['counter_only_products']) }}
                    </span>
                </a>
                <a href="{{ route('store.admin.web_products.index', array_merge($baseParams, $currentSort, ['featured' => 'featured'])) }}"
                   class="h-7 px-2.5 rounded text-xs font-bold transition inline-flex items-center gap-1.5 whitespace-nowrap {{ $currentFeat === 'featured' ? 'bg-amber-600 text-white shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                    <span>{{ __('messages.web_catalog_filter_featured') }}</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full font-mono font-black {{ $currentFeat === 'featured' ? 'bg-amber-700/80 text-white' : 'bg-white/80 dark:bg-slate-900 text-slate-600 dark:text-slate-300' }}">
                        {{ number_format($stats['featured_products']) }}
                    </span>
                </a>
                <a href="{{ route('store.admin.web_products.index', array_merge($baseParams, $currentSort, ['sale_status' => 'on_sale'])) }}"
                   class="h-7 px-2.5 rounded text-xs font-bold transition inline-flex items-center gap-1.5 whitespace-nowrap {{ $currentSale === 'on_sale' ? 'bg-rose-600 text-white shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                    <span>{{ __('messages.web_catalog_filter_on_sale_only') }}</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full font-mono font-black {{ $currentSale === 'on_sale' ? 'bg-rose-700/80 text-white' : 'bg-white/80 dark:bg-slate-900 text-slate-600 dark:text-slate-300' }}">
                        {{ number_format($stats['on_sale_products']) }}
                    </span>
                </a>
            </div>

            {{-- Table / Cards View Switcher & Count --}}
            <div class="flex items-center gap-1 self-end lg:self-auto shrink-0">
                <span class="text-[11px] font-bold text-slate-400 font-mono hidden sm:inline mr-1">
                    {{ number_format($products->total()) }} {{ __('messages.reports_items') }}
                </span>
                <div class="inline-flex rounded-md p-0.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    <button type="button" @click="setView('table')"
                            :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-xs font-black' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-200'"
                            class="h-6 px-2 rounded text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                            title="Table View">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-8 0h16a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span class="hidden sm:inline">Table</span>
                    </button>
                    <button type="button" @click="setView('cards')"
                            :class="viewMode === 'cards' || viewMode === 'card' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-xs font-black' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-200'"
                            class="h-6 px-2 rounded text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                            title="Card Grid View">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        <span class="hidden sm:inline">Cards</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Search Input, Category, Brand, Stock & Sorters Form --}}
        <form method="GET" action="{{ route('store.admin.web_products.index', $storeRouteParams) }}"
              class="flex flex-col sm:flex-row items-stretch sm:items-center gap-1.5 pt-1 border-t border-slate-100 dark:border-slate-800">
            @if(request('visibility')) <input type="hidden" name="visibility" value="{{ request('visibility') }}"> @endif
            @if(request('featured')) <input type="hidden" name="featured" value="{{ request('featured') }}"> @endif
            @if(request('sale_status')) <input type="hidden" name="sale_status" value="{{ request('sale_status') }}"> @endif

            {{-- Search input --}}
            <div class="relative flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('messages.search_by_name_sku_brand_category') }}"
                       class="w-full h-7 pl-7 pr-3 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-sky-500 transition">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-2 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </div>

            {{-- Filter: Category --}}
            <select name="category_id" onchange="this.form.submit()"
                    class="h-7 px-2 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 outline-none focus:ring-1 focus:ring-sky-500">
                <option value="">{{ __('messages.categories') }}: {{ __('messages.all') }}</option>
                @foreach($categoryGroups as $groupId => $grp)
                    <optgroup label="{{ $grp['label'] }}">
                        @foreach($grp['options'] as $catId => $catLabel)
                            <option value="{{ $catId }}" {{ request('category_id') == $catId ? 'selected' : '' }}>{{ $catLabel }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>

            {{-- Filter: Brand --}}
            <select name="brand_id" onchange="this.form.submit()"
                    class="h-7 px-2 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 outline-none focus:ring-1 focus:ring-sky-500">
                <option value="">{{ __('messages.brands') }}: {{ __('messages.all') }}</option>
                @foreach($brands as $bId => $bName)
                    <option value="{{ $bId }}" {{ request('brand_id') == $bId ? 'selected' : '' }}>{{ $bName }}</option>
                @endforeach
            </select>

            {{-- Filter: Stock Status --}}
            <select name="stock_status" onchange="this.form.submit()"
                    class="h-7 px-2 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 outline-none focus:ring-1 focus:ring-sky-500">
                <option value="">{{ __('messages.stock_status') }}: {{ __('messages.all') }}</option>
                <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>{{ __('messages.in_stock') }}</option>
                <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>{{ __('messages.web_catalog_filter_low_stock') }}</option>
                <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>{{ __('messages.out_of_stock') }}</option>
            </select>

            {{-- Sort By --}}
            <select name="sort" onchange="this.form.submit()"
                    class="h-7 px-2 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 outline-none focus:ring-1 focus:ring-sky-500">
                <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>{{ __('messages.sort_newest') }}</option>
                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>{{ __('messages.sort_oldest') }}</option>
                <option value="online_first" {{ request('sort') === 'online_first' ? 'selected' : '' }}>{{ __('messages.web_catalog_sort_online_first') }}</option>
                <option value="counter_first" {{ request('sort') === 'counter_first' ? 'selected' : '' }}>{{ __('messages.web_catalog_sort_counter_first') }}</option>
                <option value="featured_first" {{ request('sort') === 'featured_first' ? 'selected' : '' }}>{{ __('messages.web_catalog_sort_featured_first') }}</option>
                <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>{{ __('messages.sort_name_asc') }}</option>
                <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>{{ __('messages.sort_price_low_high') }}</option>
                <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>{{ __('messages.sort_price_high_low') }}</option>
            </select>

            @if(request()->hasAny(['search', 'category_id', 'brand_id', 'stock_status', 'visibility', 'featured', 'sale_status']))
                <a href="{{ $clearFiltersUrl }}"
                   class="h-7 px-2 rounded bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 hover:bg-rose-100 text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                    <span>✕</span>
                    <span>{{ __('messages.reset') }}</span>
                </a>
            @endif
        </form>
    </div>

    {{-- ── 4. Bulk Action Bar ───────────────────────────────────────────── --}}
    <div id="bulk-actions-bar" x-show="selectedIds.length > 0" x-cloak
         class="w-full bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 p-1.5 sm:p-2 rounded-lg shadow-sm text-xs border-2 border-sky-500/40 dark:border-sky-500/50">
        <div class="flex flex-wrap items-center justify-between gap-1.5 sm:gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <span class="w-2.5 h-2.5 rounded-full bg-sky-600 animate-pulse shrink-0"></span>
                <div class="font-bold text-slate-800 dark:text-slate-100 whitespace-nowrap text-xs">
                    <span class="font-black text-sky-600 dark:text-sky-400 font-mono" x-text="selectedIds.length"></span> {{ __('messages.items_selected') }}
                </div>
                <button type="button" @click="selectAll = false; selectedIds = []"
                        class="h-6 px-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded text-[11px] font-bold transition cursor-pointer">
                    {{ __('messages.cancel') }}
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-1 sm:gap-1.5">
                <button type="button" @click="selectAll = true; toggleAllFromList({{ json_encode($products->pluck('id')) }})"
                        class="h-6 px-2.5 bg-violet-600 hover:bg-violet-500 text-white rounded text-[11px] font-bold transition active:scale-95 cursor-pointer">
                    {{ __('messages.select_all') }}
                </button>

                {{-- Bulk Online --}}
                <form method="POST" action="{{ route('store.admin.web_products.bulk_visibility', $storeRouteParams) }}" class="inline-flex">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_ecommerce" value="1" />
                    <button type="submit" class="h-6 px-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[11px] font-bold transition active:scale-95 inline-flex items-center gap-1 cursor-pointer">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ __('messages.web_catalog_bulk_publish') }}</span>
                    </button>
                </form>

                {{-- Bulk Counter Only --}}
                <form method="POST" action="{{ route('store.admin.web_products.bulk_visibility', $storeRouteParams) }}" class="inline-flex">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_ecommerce" value="0" />
                    <button type="submit" class="h-6 px-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded text-[11px] font-bold transition active:scale-95 inline-flex items-center gap-1 cursor-pointer">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        <span>{{ __('messages.web_catalog_bulk_hide') }}</span>
                    </button>
                </form>

                {{-- Bulk Feature --}}
                <form method="POST" action="{{ route('store.admin.web_products.bulk_featured', $storeRouteParams) }}" class="inline-flex">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_featured" value="1" />
                    <button type="submit" class="h-6 px-2.5 bg-amber-600 hover:bg-amber-500 text-white rounded text-[11px] font-bold transition active:scale-95 inline-flex items-center gap-1 cursor-pointer">
                        <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <span>{{ __('messages.web_catalog_bulk_feature') }}</span>
                    </button>
                </form>

                {{-- Bulk Unfeature --}}
                <form method="POST" action="{{ route('store.admin.web_products.bulk_featured', $storeRouteParams) }}" class="inline-flex">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_featured" value="0" />
                    <button type="submit" class="h-6 px-2.5 bg-slate-600 hover:bg-slate-500 text-white rounded text-[11px] font-bold transition active:scale-95 cursor-pointer">
                        <span>{{ __('messages.web_catalog_bulk_unfeature') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── 5. Main Table View (Desktop & Tablet) ────────────────────────── --}}
    <div id="product-table" x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-10 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b border-slate-200 dark:border-slate-700 select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-200 dark:divide-slate-700">
                        <th class="py-2 pl-3 pr-2 w-9 text-center">
                            <input type="checkbox" x-model="selectAll" @change="toggleSelectAll"
                                   class="rounded border-slate-300 dark:border-slate-700 text-sky-600 focus:ring-sky-500 h-3.5 w-3.5 cursor-pointer">
                        </th>
                        <th class="py-2 px-2.5 min-w-[200px]">{{ __('messages.products') }}</th>
                        <th class="py-2 px-2.5 min-w-[130px]">{{ __('messages.categories') }} / {{ __('messages.brands') }}</th>
                        <th class="py-2 px-2.5 text-right min-w-[110px]">{{ __('messages.price') }}</th>
                        <th class="py-2 px-2.5 text-center min-w-[100px]">{{ __('messages.stock') }}</th>
                        <th class="py-2 px-2.5 text-center min-w-[140px]">{{ __('messages.web_catalog_filter_visibility_label') }}</th>
                        <th class="py-2 px-2.5 text-center min-w-[120px]">{{ __('messages.web_catalog_filter_featured_label') }}</th>
                        <th class="py-2 pl-2 pr-3 text-right w-20">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse($products as $p)
                        <tr id="product-row-{{ $p->id }}" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 divide-x divide-slate-100 dark:divide-slate-800/60 transition-colors group">
                            {{-- Checkbox --}}
                            <td class="py-2 pl-3 pr-2 text-center">
                                <input type="checkbox" :value="{{ $p->id }}" x-model="selectedIds"
                                       class="product-select-cb rounded border-slate-300 dark:border-slate-700 text-sky-600 focus:ring-sky-500 h-3.5 w-3.5 cursor-pointer">
                            </td>

                            {{-- Product info --}}
                            <td class="py-2 px-2.5">
                                <div class="flex items-center gap-2">
                                    @if($p->image_path)
                                        <img src="{{ asset('storage/' . $p->image_path) }}" alt="{{ $p->name }}"
                                             class="w-8 h-8 rounded-lg object-cover border border-slate-200/80 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 shrink-0">
                                    @else
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 flex items-center justify-center text-slate-400 shrink-0 text-xs">
                                            📦
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 truncate max-w-xs sm:max-w-sm text-xs leading-tight">
                                            {{ $p->name }}
                                        </div>
                                        <div class="flex items-center gap-1.5 text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                                            <span class="font-mono">{{ $p->sku ?: 'No SKU' }}</span>
                                            @if($p->variants->count() > 0)
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200/60 dark:border-slate-700">
                                                    {{ $p->variants->count() }} Variants
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Category / Brand --}}
                            <td class="py-2 px-2.5 whitespace-nowrap">
                                <div class="text-xs text-slate-800 dark:text-slate-200 font-bold truncate max-w-[140px]">
                                    {{ $p->category->name ?? '-' }}
                                </div>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-[140px]">
                                    {{ $p->brand->name ?? '-' }}
                                </div>
                            </td>

                            {{-- Price & Sale --}}
                            <td class="py-2 px-2.5 text-right whitespace-nowrap">
                                <div class="font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums text-xs">
                                    {{ format_currency($p->retail_price, $store) }}
                                </div>
                                @if($p->isOnSale())
                                    <div class="flex items-center justify-end gap-1 text-[10px] mt-0.5">
                                        <span class="line-through text-slate-400 font-mono tabular-nums">{{ format_currency($p->old_price, $store) }}</span>
                                        <span class="text-rose-600 dark:text-rose-400 font-black">-{{ $p->discountPercent() }}%</span>
                                    </div>
                                @endif
                            </td>

                            {{-- Stock Status --}}
                            <td class="py-2 px-2.5 text-center whitespace-nowrap">
                                @if($p->stock_status === 'in_stock')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        {{ __('messages.in_stock') }}
                                    </span>
                                @elseif($p->stock_status === 'low_stock')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                        {{ __('messages.web_catalog_filter_low_stock') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                        {{ __('messages.out_of_stock') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Storefront Visibility Toggle --}}
                            <td class="py-2 px-2.5 text-center whitespace-nowrap">
                                <button type="button"
                                        @click="toggleVisibility({{ $p->id }})"
                                        :disabled="loadingId === 'vis-{{ $p->id }}'"
                                        :class="productStates[{{ $p->id }}]?.is_ecommerce
                                            ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 shadow-2xs'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold transition active:scale-95 cursor-pointer">
                                    <template x-if="loadingId === 'vis-{{ $p->id }}'">
                                        <svg class="animate-spin w-3 h-3 text-current" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    </template>
                                    <template x-if="loadingId !== 'vis-{{ $p->id }}'">
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                    </template>
                                    <span x-text="productStates[{{ $p->id }}]?.is_ecommerce ? '{{ __('messages.web_catalog_status_online') }}' : '{{ __('messages.web_catalog_status_counter') }}'"></span>
                                </button>
                            </td>

                            {{-- Featured Toggle --}}
                            <td class="py-2 px-2.5 text-center whitespace-nowrap">
                                <button type="button"
                                        @click="toggleFeatured({{ $p->id }})"
                                        :disabled="loadingId === 'feat-{{ $p->id }}'"
                                        :class="productStates[{{ $p->id }}]?.is_featured
                                            ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/50 hover:bg-amber-200'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700'"
                                        class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold transition shadow-2xs active:scale-95 cursor-pointer">
                                    <template x-if="loadingId === 'feat-{{ $p->id }}'">
                                        <svg class="animate-spin w-3 h-3 text-current" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    </template>
                                    <template x-if="loadingId !== 'feat-{{ $p->id }}' && productStates[{{ $p->id }}]?.is_featured">
                                        <svg class="w-3 h-3 fill-amber-500 text-amber-500" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    </template>
                                    <template x-if="loadingId !== 'feat-{{ $p->id }}' && !productStates[{{ $p->id }}]?.is_featured">
                                        <svg class="w-3 h-3 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                    </template>
                                    <span x-text="productStates[{{ $p->id }}]?.is_featured ? '{{ __('messages.web_catalog_status_featured') }}' : '{{ __('messages.web_catalog_status_standard') }}'"></span>
                                </button>
                            </td>

                            {{-- Actions --}}
                            <td class="py-2 pl-2 pr-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1 justify-end">
                                    <template x-if="productStates[{{ $p->id }}]?.is_ecommerce">
                                        <a href="{{ route('storefront.product', ['store_slug' => $store->slug, 'slug' => $p->slug]) }}" target="_blank"
                                           class="w-6 h-6 rounded border border-sky-200 dark:border-sky-800/80 inline-flex items-center justify-center text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/50 hover:bg-sky-100 transition cursor-pointer"
                                           title="{{ __('messages.web_catalog_preview_storefront') }}">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                    </template>
                                    <a href="{{ route('store.admin.products.edit', ['store_slug' => $store->slug, 'product' => $p->id]) }}"
                                       class="w-6 h-6 rounded border border-slate-200 dark:border-slate-700 inline-flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                                       title="{{ __('messages.edit') }}">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 sm:p-12 text-center">
                                <div class="mx-auto max-w-sm">
                                    <div class="mx-auto mb-3 w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400 dark:text-slate-500 text-2xl shadow-inner">
                                        🌐
                                    </div>
                                    <p class="font-black text-slate-800 dark:text-slate-200 text-xs sm:text-sm">{{ __('messages.web_catalog_empty') }}</p>
                                    <a href="{{ $clearFiltersUrl }}" class="inline-block text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline mt-1">
                                        {{ __('messages.clear_all') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── 6. Responsive Mobile Card View ────────────────────────────────── --}}
    <div id="product-cards" x-show="viewMode === 'cards' || viewMode === 'card'" class="w-full">
        @if($products->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-0.5 sm:gap-1">
                @foreach($products as $p)
                    <div id="product-card-{{ $p->id }}" class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/90 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs transition flex flex-col justify-between space-y-1.5">
                        <div>
                            {{-- Top row: Checkbox, Category, Actions --}}
                            <div class="flex items-start justify-between gap-1 pb-1 border-b border-slate-100 dark:border-slate-800">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <input type="checkbox" :value="{{ $p->id }}" x-model="selectedIds"
                                           class="product-select-cb rounded border-slate-300 dark:border-slate-700 text-sky-600 focus:ring-sky-500 h-3.5 w-3.5 cursor-pointer shrink-0">
                                    <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 truncate max-w-[80px] sm:max-w-[110px]">
                                        {{ $p->category->name ?? 'Uncategorized' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-0.5 shrink-0">
                                    <template x-if="productStates[{{ $p->id }}]?.is_ecommerce">
                                        <a href="{{ route('storefront.product', ['store_slug' => $store->slug, 'slug' => $p->slug]) }}" target="_blank"
                                           class="p-1 text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition" title="{{ __('messages.web_catalog_preview_storefront') }}">
                                            <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                    </template>
                                    <a href="{{ route('store.admin.products.edit', ['store_slug' => $store->slug, 'product' => $p->id]) }}"
                                       class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition" title="{{ __('messages.edit') }}">
                                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </a>
                                </div>
                            </div>

                            {{-- Product Image + Name --}}
                            <div class="flex items-center gap-1.5 mt-1.5">
                                @if($p->image_path)
                                    <img src="{{ asset('storage/' . $p->image_path) }}" alt="{{ $p->name }}"
                                         class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg object-cover border border-slate-200/80 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 shrink-0">
                                @else
                                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 flex items-center justify-center text-slate-400 shrink-0 text-xs">
                                        📦
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-bold text-[11px] sm:text-xs text-slate-900 dark:text-slate-100 truncate leading-tight" title="{{ $p->name }}">{{ $p->name }}</h3>
                                    <div class="flex items-center gap-1 text-[9px] sm:text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">
                                        <span class="font-mono">{{ $p->sku ?: 'No SKU' }}</span>
                                        @if($p->brand)
                                            <span>· {{ $p->brand->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Price & Stock badges --}}
                            <div class="flex items-center justify-between gap-1 mt-1.5 pt-1.5 border-t border-slate-100 dark:border-slate-800">
                                <div class="min-w-0">
                                    <span class="font-mono font-black text-[11px] sm:text-xs text-slate-900 dark:text-slate-100 tabular-nums truncate block">
                                        {{ format_currency($p->retail_price, $store) }}
                                    </span>
                                    @if($p->isOnSale())
                                        <span class="text-[9px] text-rose-600 dark:text-rose-400 font-bold block leading-none">-{{ $p->discountPercent() }}%</span>
                                    @endif
                                </div>
                                <div class="shrink-0">
                                    @if($p->stock_status === 'in_stock')
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                            {{ __('messages.in_stock') }}
                                        </span>
                                    @elseif($p->stock_status === 'low_stock')
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                            {{ __('messages.web_catalog_filter_low_stock') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                            {{ __('messages.out_of_stock') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Card bottom toggles --}}
                        <div class="grid grid-cols-2 gap-0.5 sm:gap-1 pt-1.5 border-t border-slate-100 dark:border-slate-800">
                            {{-- Visibility toggle --}}
                            <button type="button"
                                    @click="toggleVisibility({{ $p->id }})"
                                    :disabled="loadingId === 'vis-{{ $p->id }}'"
                                    :class="productStates[{{ $p->id }}]?.is_ecommerce
                                        ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 shadow-2xs'"
                                    class="inline-flex items-center justify-center gap-0.5 px-1 py-1 rounded text-[10px] sm:text-[11px] font-bold transition active:scale-95 cursor-pointer min-w-0">
                                <template x-if="loadingId === 'vis-{{ $p->id }}'">
                                    <svg class="animate-spin w-2.5 h-2.5 sm:w-3 sm:h-3 text-current shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                </template>
                                <template x-if="loadingId !== 'vis-{{ $p->id }}'">
                                    <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                </template>
                                <span class="truncate" x-text="productStates[{{ $p->id }}]?.is_ecommerce ? '{{ __('messages.web_catalog_status_online') }}' : '{{ __('messages.web_catalog_status_counter') }}'"></span>
                            </button>

                            {{-- Featured toggle --}}
                            <button type="button"
                                    @click="toggleFeatured({{ $p->id }})"
                                    :disabled="loadingId === 'feat-{{ $p->id }}'"
                                    :class="productStates[{{ $p->id }}]?.is_featured
                                        ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/50 hover:bg-amber-200'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700'"
                                    class="inline-flex items-center justify-center gap-0.5 px-1 py-1 rounded text-[10px] sm:text-[11px] font-bold transition shadow-2xs active:scale-95 cursor-pointer min-w-0">
                                <template x-if="loadingId === 'feat-{{ $p->id }}'">
                                    <svg class="animate-spin w-2.5 h-2.5 sm:w-3 sm:h-3 text-current shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                </template>
                                <template x-if="loadingId !== 'feat-{{ $p->id }}' && productStates[{{ $p->id }}]?.is_featured">
                                    <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 fill-amber-500 text-amber-500 shrink-0" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </template>
                                <template x-if="loadingId !== 'feat-{{ $p->id }}' && !productStates[{{ $p->id }}]?.is_featured">
                                    <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 stroke-current fill-none shrink-0" viewBox="0 0 24 24" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                </template>
                                <span class="truncate" x-text="productStates[{{ $p->id }}]?.is_featured ? '{{ __('messages.web_catalog_status_featured') }}' : '{{ __('messages.web_catalog_status_standard') }}'"></span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg p-8 text-center shadow-2xs">
                <div class="max-w-sm mx-auto space-y-2">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400 text-lg shadow-inner">
                        🌐
                    </div>
                    <p class="font-bold text-xs text-slate-700 dark:text-slate-300">{{ __('messages.web_catalog_empty') }}</p>
                    <a href="{{ $clearFiltersUrl }}" class="inline-block text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline">
                        {{ __('messages.clear_all') }}
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- Pagination --}}
    @if ($products->hasPages())
        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg text-xs">
            {{ $products->links() }}
        </div>
    @endif

    {{-- ── 7. Category Breakdown Modal Dialog ───────────────────────────── --}}
    <div x-show="categoryModal" x-cloak
         @click.self="categoryModal = false"
         @keydown.escape.window="categoryModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
         role="dialog" aria-modal="true">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-xl w-full p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300 grid place-items-center text-xs font-bold">📊</span>
                    <div>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.web_catalog_category_breakdown') }}</h3>
                        <p class="text-[10px] text-slate-400">{{ __('messages.web_catalog_subtitle') }}</p>
                    </div>
                </div>
                <button type="button" @click="categoryModal = false" class="w-6 h-6 rounded-md grid place-items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-black cursor-pointer">
                    ✕
                </button>
            </div>

            <div class="space-y-1.5 max-h-80 overflow-y-auto pr-1">
                @forelse($categoryBreakdown as $cat)
                    @php
                        $onlinePercent = $cat->total_count > 0 ? round(($cat->online_count / $cat->total_count) * 100) : 0;
                    @endphp
                    <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between gap-2.5">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-xs text-slate-900 dark:text-slate-100 truncate">{{ $cat->name }}</span>
                                <span class="text-[10px] font-mono text-slate-400 font-bold">{{ $cat->online_count }} / {{ $cat->total_count }} ({{ $onlinePercent }}%)</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-full rounded-full transition-all duration-300" style="width: {{ $onlinePercent }}%"></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                {{ $cat->online_count }} Online
                            </span>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                {{ $cat->counter_count }} Counter
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-center py-6 text-xs text-slate-400">No categories created yet.</p>
                @endforelse
            </div>

            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-right">
                <button type="button" @click="categoryModal = false" class="h-7 px-3 rounded-md text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
