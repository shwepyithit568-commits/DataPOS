@extends('layouts.admin.app')

@section('title', __('messages.web_catalog_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

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
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
        viewMode: localStorage.getItem('admin_web_products_view_mode') || 'table',
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
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_web_products_view_mode', $event.detail)"
     @bulk-actions-request.window="
        if (selectedIds.length === 0) {
            showToast('{{ __('messages.bulk_select_first_toast') }}');
            document.getElementById('product-table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            $nextTick(() => document.getElementById('bulk-actions-bar')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        }
     ">

    {{-- Toast Notification --}}
    <div x-show="toastShow" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-4 right-4 z-50 px-4 py-2.5 rounded-lg bg-slate-900 text-white dark:bg-white dark:text-slate-900 text-xs font-bold shadow-xl border border-slate-700 dark:border-slate-200 flex items-center gap-2">
        <span class="text-emerald-400 dark:text-emerald-600">✓</span>
        <span x-text="toastMsg"></span>
    </div>

    {{-- ============================================================
         PAGE HEADER — eyebrow badge, title, subtitle, CTA row
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0">
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🌐 {{ __('messages.web_catalog_title') }}</span>
            </h1>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 shrink-0">
            <button type="button" @click="categoryModal = true"
                    class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs active:scale-95">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>{{ __('messages.web_catalog_category_breakdown') }}</span>
            </button>
            <a href="{{ route('storefront.store.home', ['store_slug' => $store->slug]) }}" target="_blank"
               class="px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-lg text-xs font-bold bg-sky-600 hover:bg-sky-700 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>{{ __('messages.web_catalog_preview_storefront') }}</span>
            </a>
        </div>
    </header>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="w-full p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ============================================================
         KPI STAT CARDS — 6-up responsive layout
         ============================================================ --}}
    <div class="w-full grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-2.5" role="list" aria-label="{{ __('messages.web_catalog_title') }}">
        {{-- Total --}}
        <a href="{{ $clearFiltersUrl }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['total'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['total'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['total_products']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.web_catalog_total_products') }}
                </p>
            </div>
        </a>

        {{-- Online Storefront --}}
        <a href="{{ route('store.admin.web_products.index', array_merge($baseParams, $currentSort, ['visibility' => 'online'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['online'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['online'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['online_products']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.web_catalog_online_products') }}
                </p>
            </div>
        </a>

        {{-- Counter Only --}}
        <a href="{{ route('store.admin.web_products.index', array_merge($baseParams, $currentSort, ['visibility' => 'counter_only'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['counter_only'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['counter_only'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-slate-700 dark:text-slate-300 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['counter_only_products']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.web_catalog_counter_only') }}
                </p>
            </div>
        </a>

        {{-- Featured --}}
        <a href="{{ route('store.admin.web_products.index', array_merge($baseParams, $currentSort, ['featured' => 'featured'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['featured'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['featured'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['featured_products']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.web_catalog_featured_products') }}
                </p>
            </div>
        </a>

        {{-- In Stock Online --}}
        <a href="{{ route('store.admin.web_products.index', array_merge($baseParams, $currentSort, ['visibility' => 'online', 'stock_status' => 'in_stock'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['in_stock'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['in_stock'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-sky-600 dark:text-sky-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['online_in_stock']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.web_catalog_in_stock_online') }}
                </p>
            </div>
        </a>

        {{-- On Sale --}}
        <a href="{{ route('store.admin.web_products.index', array_merge($baseParams, $currentSort, ['sale_status' => 'on_sale'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['on_sale'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['on_sale'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['on_sale_products']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.web_catalog_on_sale') }}
                </p>
            </div>
        </a>
    </div>

    {{-- ============================================================
         REUSABLE ADMIN TOOLBAR COMPONENT
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', '')"
        :searchPlaceholder="__('messages.search_by_name_sku_brand_category')"
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest'         => __('messages.sort_newest'),
            'oldest'         => __('messages.sort_oldest'),
            'online_first'   => __('messages.web_catalog_sort_online_first'),
            'counter_first'  => __('messages.web_catalog_sort_counter_first'),
            'featured_first' => __('messages.web_catalog_sort_featured_first'),
            'name_asc'       => __('messages.sort_name_asc'),
            'price_asc'      => __('messages.sort_price_low_high'),
            'price_desc'     => __('messages.sort_price_high_low'),
        ]"
        :filters="[
            'visibility' => [
                'label'   => __('messages.web_catalog_filter_visibility_label'),
                'options' => [
                    'online'       => __('messages.web_catalog_filter_online'),
                    'counter_only' => __('messages.web_catalog_filter_counter'),
                ]
            ],
            'featured' => [
                'label'   => __('messages.web_catalog_filter_featured_label'),
                'options' => [
                    'featured' => __('messages.web_catalog_filter_featured'),
                    'standard' => __('messages.web_catalog_filter_standard'),
                ]
            ],
            'stock_status' => [
                'label'   => __('messages.stock_status'),
                'options' => [
                    'in_stock'     => __('messages.in_stock'),
                    'low_stock'    => __('messages.web_catalog_filter_low_stock'),
                    'out_of_stock' => __('messages.out_of_stock'),
                ]
            ],
            'category_id' => [
                'label'   => __('messages.categories'),
                'options' => $categories,
                'groups'  => $categoryGroups,
            ],
            'brand_id' => [
                'label'   => __('messages.brands'),
                'options' => $brands,
            ],
            'sale_status' => [
                'label'   => __('messages.web_catalog_filter_sale_label'),
                'options' => [
                    'on_sale' => __('messages.web_catalog_filter_on_sale_only'),
                    'regular' => __('messages.web_catalog_filter_regular_price'),
                ]
            ],
        ]"
        :showViewToggle="true"
        :showExportImport="false"
        :paginator="$products"
        :perPageOptions="[20 => '20', 50 => '50', 100 => '100', 200 => '200', 'all' => __('messages.all')]"
        :bulkActions="true"
    />

    {{-- ============================================================
         BULK ACTION BAR
         ============================================================ --}}
    <div id="bulk-actions-bar" x-show="selectedIds.length > 0" x-cloak class="w-full bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 p-2.5 sm:p-3 rounded-lg shadow-sm text-sm border-2 border-sky-500/40 dark:border-sky-500/50 scroll-mt-24">
        <div class="flex flex-wrap items-center justify-between gap-2.5 sm:gap-3">
            <div class="flex items-center gap-2 min-w-0">
                <span class="w-3 h-3 rounded-full bg-sky-600 animate-pulse shrink-0"></span>
                <div class="font-bold text-slate-800 dark:text-slate-100 whitespace-nowrap text-xs sm:text-sm">
                    <span class="font-black text-sky-600 dark:text-sky-400 font-mono" x-text="selectedIds.length"></span> {{ __('messages.items_selected') }}
                </div>
                <button type="button" @click="selectAll = false; selectedIds = []"
                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-bold shadow-2xs transition">
                    {{ __('messages.cancel') }}
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                <button type="button" @click="selectAll = true; toggleAllFromList({{ json_encode($products->pluck('id')) }})"
                    class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-xs font-bold shadow-2xs transition active:scale-95">
                    {{ __('messages.select_all') }}
                </button>

                {{-- Bulk Online --}}
                <form method="POST" action="{{ route('store.admin.web_products.bulk_visibility', $storeRouteParams) }}" class="inline-flex">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_ecommerce" value="1" />
                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-2xs transition active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
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
                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-700 hover:bg-slate-800 text-white rounded-lg text-xs font-bold shadow-2xs transition active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
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
                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold shadow-2xs transition active:scale-95">
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
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
                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white rounded-lg text-xs font-bold shadow-2xs transition active:scale-95">
                        <span>{{ __('messages.web_catalog_bulk_unfeature') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TABLE VIEW — spreadsheet grid with sticky header
         ============================================================ --}}
    <div id="product-table" x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        <th class="py-2.5 pl-3 pr-2 w-10 text-center">
                            <input type="checkbox" x-model="selectAll" @change="toggleSelectAll"
                                   class="rounded border-slate-300 dark:border-slate-700 text-sky-600 focus:ring-sky-500 h-4 w-4">
                        </th>
                        <th class="py-2.5 px-3 min-w-[200px]">{{ __('messages.products') }}</th>
                        <th class="py-2.5 px-3 min-w-[140px]">{{ __('messages.categories') }} / {{ __('messages.brands') }}</th>
                        <th class="py-2.5 px-3 text-right min-w-[120px]">{{ __('messages.price') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[100px]">{{ __('messages.stock') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[160px]">{{ __('messages.web_catalog_filter_visibility_label') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[130px]">{{ __('messages.web_catalog_filter_featured_label') }}</th>
                        <th class="py-2.5 pl-3 pr-4 text-right w-24">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/90 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse($products as $p)
                        <tr id="product-row-{{ $p->id }}" class="hover:bg-sky-50/60 dark:hover:bg-sky-950/20 divide-x divide-slate-200/80 dark:divide-slate-800 transition-colors group">
                            {{-- Checkbox --}}
                            <td class="py-2.5 pl-3 pr-2 text-center">
                                <input type="checkbox" :value="{{ $p->id }}" x-model="selectedIds"
                                       class="product-select-cb rounded border-slate-300 dark:border-slate-700 text-sky-600 focus:ring-sky-500 h-4 w-4">
                            </td>

                            {{-- Product info --}}
                            <td class="py-2.5 px-3">
                                <div class="flex items-center gap-2.5">
                                    @if($p->image_path)
                                        <img src="{{ asset('storage/' . $p->image_path) }}" alt="{{ $p->name }}"
                                             class="w-9 h-9 rounded-lg object-cover border border-slate-200/80 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 flex items-center justify-center text-slate-400 shrink-0">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 truncate max-w-xs sm:max-w-sm">
                                            {{ $p->name }}
                                        </div>
                                        <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
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
                            <td class="py-2.5 px-3">
                                <div class="text-xs text-slate-800 dark:text-slate-200 font-medium">
                                    {{ $p->category->name ?? '-' }}
                                </div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500">
                                    {{ $p->brand->name ?? '-' }}
                                </div>
                            </td>

                            {{-- Price & Sale --}}
                            <td class="py-2.5 px-3 text-right">
                                <div class="font-mono font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                    {{ number_format($p->retail_price) }} Ks
                                </div>
                                @if($p->isOnSale())
                                    <div class="flex items-center justify-end gap-1 text-[10px] mt-0.5">
                                        <span class="line-through text-slate-400 font-mono tabular-nums">{{ number_format($p->old_price) }}</span>
                                        <span class="text-rose-600 dark:text-rose-400 font-black">-{{ $p->discountPercent() }}%</span>
                                    </div>
                                @endif
                            </td>

                            {{-- Stock --}}
                            <td class="py-2.5 px-3 text-center">
                                @if($p->stock_status === 'in_stock')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        {{ __('messages.in_stock') }}
                                    </span>
                                @elseif($p->stock_status === 'low_stock')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                        {{ __('messages.web_catalog_filter_low_stock') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                        {{ __('messages.out_of_stock') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Storefront Visibility Toggle --}}
                            <td class="py-2.5 px-3 text-center">
                                <button type="button"
                                        @click="toggleVisibility({{ $p->id }})"
                                        :disabled="loadingId === 'vis-{{ $p->id }}'"
                                        :class="productStates[{{ $p->id }}]?.is_ecommerce
                                            ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 shadow-2xs'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold transition active:scale-95">
                                    <template x-if="loadingId === 'vis-{{ $p->id }}'">
                                        <svg class="animate-spin w-3.5 h-3.5 text-current" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    </template>
                                    <template x-if="loadingId !== 'vis-{{ $p->id }}'">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                    </template>
                                    <span x-text="productStates[{{ $p->id }}]?.is_ecommerce ? '{{ __('messages.web_catalog_status_online') }}' : '{{ __('messages.web_catalog_status_counter') }}'"></span>
                                </button>
                            </td>

                            {{-- Featured Toggle --}}
                            <td class="py-2.5 px-3 text-center">
                                <button type="button"
                                        @click="toggleFeatured({{ $p->id }})"
                                        :disabled="loadingId === 'feat-{{ $p->id }}'"
                                        :class="productStates[{{ $p->id }}]?.is_featured
                                            ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/50 hover:bg-amber-200'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700'"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold transition shadow-2xs active:scale-95">
                                    <template x-if="productStates[{{ $p->id }}]?.is_featured">
                                        <svg class="w-3.5 h-3.5 fill-amber-500 text-amber-500" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    </template>
                                    <template x-if="!productStates[{{ $p->id }}]?.is_featured">
                                        <svg class="w-3.5 h-3.5 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                    </template>
                                    <span x-text="productStates[{{ $p->id }}]?.is_featured ? '{{ __('messages.web_catalog_status_featured') }}' : '{{ __('messages.web_catalog_status_standard') }}'"></span>
                                </button>
                            </td>

                            {{-- Actions --}}
                            <td class="py-2.5 pl-3 pr-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <template x-if="productStates[{{ $p->id }}]?.is_ecommerce">
                                        <a href="{{ route('storefront.product', ['store_slug' => $store->slug, 'slug' => $p->slug]) }}"
                                           target="_blank"
                                           title="{{ __('messages.web_catalog_preview_storefront') }}"
                                           class="p-1.5 rounded-lg text-slate-500 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/50 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                    </template>
                                    <a href="{{ route('store.admin.products.edit', ['store_slug' => $store->slug, 'product' => $p->id]) }}"
                                       title="{{ __('messages.edit') }}"
                                       class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="max-w-sm mx-auto space-y-2.5">
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/></svg>
                                    </div>
                                    <p class="font-bold text-xs text-slate-700 dark:text-slate-300">{{ __('messages.web_catalog_empty') }}</p>
                                    <a href="{{ $clearFiltersUrl }}" class="inline-block text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline">
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

    {{-- ============================================================
         CARD VIEW — responsive grid
         ============================================================ --}}
    <div id="product-cards" x-show="viewMode === 'card'" class="w-full">
        @if($products->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5">
                @foreach($products as $p)
                    <div id="product-card-{{ $p->id }}" class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 shadow-2xs hover:shadow-sm transition flex flex-col justify-between space-y-2.5">
                        <div>
                            {{-- Top row: checkbox, category, actions --}}
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" :value="{{ $p->id }}" x-model="selectedIds"
                                           class="product-select-cb rounded border-slate-300 dark:border-slate-700 text-sky-600 focus:ring-sky-500 h-4 w-4">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 truncate max-w-[120px]">
                                        {{ $p->category->name ?? 'Uncategorized' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <template x-if="productStates[{{ $p->id }}]?.is_ecommerce">
                                        <a href="{{ route('storefront.product', ['store_slug' => $store->slug, 'slug' => $p->slug]) }}" target="_blank"
                                           class="p-1 text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition" title="{{ __('messages.web_catalog_preview_storefront') }}">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                    </template>
                                    <a href="{{ route('store.admin.products.edit', ['store_slug' => $store->slug, 'product' => $p->id]) }}"
                                       class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition" title="{{ __('messages.edit') }}">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </a>
                                </div>
                            </div>

                            {{-- Product Image + Name --}}
                            <div class="flex items-center gap-2.5 mt-2">
                                @if($p->image_path)
                                    <img src="{{ asset('storage/' . $p->image_path) }}" alt="{{ $p->name }}"
                                         class="w-11 h-11 rounded-lg object-cover border border-slate-200/80 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 shrink-0">
                                @else
                                    <div class="w-11 h-11 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 flex items-center justify-center text-slate-400 shrink-0">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-bold text-xs text-slate-900 dark:text-slate-100 truncate" title="{{ $p->name }}">{{ $p->name }}</h3>
                                    <div class="flex items-center gap-1.5 text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">
                                        <span class="font-mono">{{ $p->sku ?: 'No SKU' }}</span>
                                        @if($p->brand)
                                            <span>· {{ $p->brand->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Price & Stock badges --}}
                            <div class="flex items-center justify-between gap-2 mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                                <div>
                                    <span class="font-mono font-bold text-xs text-slate-900 dark:text-slate-100 tabular-nums">
                                        {{ number_format($p->retail_price) }} Ks
                                    </span>
                                    @if($p->isOnSale())
                                        <span class="text-[10px] text-rose-600 dark:text-rose-400 font-bold ml-1">-{{ $p->discountPercent() }}%</span>
                                    @endif
                                </div>
                                <div>
                                    @if($p->stock_status === 'in_stock')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                            {{ __('messages.in_stock') }}
                                        </span>
                                    @elseif($p->stock_status === 'low_stock')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                            {{ __('messages.web_catalog_filter_low_stock') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                            {{ __('messages.out_of_stock') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Card bottom toggles --}}
                        <div class="grid grid-cols-2 gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                            {{-- Visibility toggle --}}
                            <button type="button"
                                    @click="toggleVisibility({{ $p->id }})"
                                    :disabled="loadingId === 'vis-{{ $p->id }}'"
                                    :class="productStates[{{ $p->id }}]?.is_ecommerce
                                        ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 shadow-2xs'"
                                    class="inline-flex items-center justify-center gap-1 px-2 py-1 rounded-lg text-xs font-bold transition active:scale-95">
                                <template x-if="loadingId === 'vis-{{ $p->id }}'">
                                    <svg class="animate-spin w-3 h-3 text-current" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                </template>
                                <template x-if="loadingId !== 'vis-{{ $p->id }}'">
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                </template>
                                <span class="text-[11px]" x-text="productStates[{{ $p->id }}]?.is_ecommerce ? '{{ __('messages.web_catalog_status_online') }}' : '{{ __('messages.web_catalog_status_counter') }}'"></span>
                            </button>

                            {{-- Featured toggle --}}
                            <button type="button"
                                    @click="toggleFeatured({{ $p->id }})"
                                    :disabled="loadingId === 'feat-{{ $p->id }}'"
                                    :class="productStates[{{ $p->id }}]?.is_featured
                                        ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/50 hover:bg-amber-200'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700'"
                                    class="inline-flex items-center justify-center gap-1 px-2 py-1 rounded-lg text-xs font-bold transition shadow-2xs active:scale-95">
                                <template x-if="productStates[{{ $p->id }}]?.is_featured">
                                    <svg class="w-3 h-3 fill-amber-500 text-amber-500" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </template>
                                <template x-if="!productStates[{{ $p->id }}]?.is_featured">
                                    <svg class="w-3 h-3 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                </template>
                                <span class="text-[11px]" x-text="productStates[{{ $p->id }}]?.is_featured ? '{{ __('messages.web_catalog_status_featured') }}' : '{{ __('messages.web_catalog_status_standard') }}'"></span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg p-12 text-center shadow-2xs">
                <div class="max-w-sm mx-auto space-y-2.5">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/></svg>
                    </div>
                    <p class="font-bold text-xs text-slate-700 dark:text-slate-300">{{ __('messages.web_catalog_empty') }}</p>
                    <a href="{{ $clearFiltersUrl }}" class="inline-block text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline">
                        {{ __('messages.clear_all') }}
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- ============================================================
         CATEGORY VISIBILITY BREAKDOWN MODAL
         ============================================================ --}}
    <div x-show="categoryModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="categoryModal = false"
             class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-xl w-full p-4 sm:p-5 space-y-3.5">
            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300 grid place-items-center text-xs">📊</span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('messages.web_catalog_category_breakdown') }}</h3>
                        <p class="text-[11px] text-slate-400">{{ __('messages.web_catalog_subtitle') }}</p>
                    </div>
                </div>
                <button type="button" @click="categoryModal = false" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                @forelse($categoryBreakdown as $cat)
                    @php
                        $onlinePercent = $cat->total_count > 0 ? round(($cat->online_count / $cat->total_count) * 100) : 0;
                    @endphp
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-xs text-slate-900 dark:text-slate-100 truncate">{{ $cat->name }}</span>
                                <span class="text-[10px] font-mono text-slate-400 font-bold">{{ $cat->online_count }} / {{ $cat->total_count }} ({{ $onlinePercent }}%)</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-full rounded-full transition-all duration-300" style="width: {{ $onlinePercent }}%"></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                {{ $cat->online_count }} Online
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                {{ $cat->counter_count }} Counter
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-center py-6 text-xs text-slate-400">No categories created yet.</p>
                @endforelse
            </div>

            <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 text-right">
                <button type="button" @click="categoryModal = false" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition active:scale-95">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
