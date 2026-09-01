@extends('layouts.admin.app')

@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    // Accent color tokens for the 4 stat cards + featured/online pill badges.
    // Matches the Master Data hub visual language for cross-page consistency.
    $statAccents = [
        'total'        => 'bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300',
        'in_stock'     => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300',
        'out_of_stock' => 'bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300',
        'featured'     => 'bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300',
        'online'       => 'bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300',
    ];

    $statBorders = [
        'total'        => 'hover:border-violet-300 dark:hover:border-violet-700/80',
        'in_stock'     => 'hover:border-emerald-300 dark:hover:border-emerald-700/80',
        'out_of_stock' => 'hover:border-rose-300 dark:hover:border-rose-700/80',
        'featured'     => 'hover:border-amber-300 dark:hover:border-amber-700/80',
    ];

    // Build clean filter-preserving URLs for stat-card clicks.
    $baseParams  = ['store_slug' => $store->slug];
    $currentSort = request()->only('sort', 'search');
    $clearFiltersUrl = route('store.admin.products.index', $baseParams);
@endphp
<div class="w-full space-y-0.5 pb-6"
    x-data="{
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
        selectedIds: [],
        selectAll: false,
        priceFormOpen: false,
        toastShow: false,
        toastMsg: '',
        detailsOpen: false,
        detailsLoading: false,
        detailsHtml: '',
        openDetails(url) {
            this.detailsOpen = true;
            this.detailsLoading = true;
            this.detailsHtml = '';
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(r => r.text())
                .then(html => { this.detailsHtml = html; this.detailsLoading = false; })
                .catch(() => {
                    this.detailsHtml = '{{ __('messages.products_load_failed') }}';
                    this.detailsLoading = false;
                });
        },
        setDetailsTab(name) {
            this.$refs.detailsBody.querySelectorAll('[data-spec-tab]').forEach(t => {
                const active = t.getAttribute('data-spec-tab') === name;
                t.setAttribute('aria-selected', active ? 'true' : 'false');
                t.classList.toggle('border-violet-500', active);
                t.classList.toggle('text-violet-600', active);
                t.classList.toggle('dark:text-violet-400', active);
                t.classList.toggle('border-transparent', !active);
                t.classList.toggle('text-slate-500', !active);
                t.classList.toggle('dark:text-slate-400', !active);
            });
            this.$refs.detailsBody.querySelectorAll('[data-spec-panel]').forEach(p => {
                p.hidden = p.getAttribute('data-spec-panel') !== name;
            });
        },
        onDetailsClick(e) {
            const tab = e.target.closest('[data-spec-tab]');
            if (tab) this.setDetailsTab(tab.getAttribute('data-spec-tab'));
        },
        onDetailsKeydown(e) {
            const el = document.activeElement;
            if (!el || !el.matches('[data-spec-tab]')) return;
            const names = ['description', 'specifications'];
            const i = names.indexOf(el.getAttribute('data-spec-tab'));
            if (i === -1) return;
            let next = null;
            if (e.key === 'ArrowRight') next = names[(i + 1) % names.length];
            else if (e.key === 'ArrowLeft') next = names[(i - 1 + names.length) % names.length];
            else if (e.key === 'Home') next = names[0];
            else if (e.key === 'End') next = names[names.length - 1];
            if (!next) return;
            e.preventDefault();
            this.setDetailsTab(next);
            const target = this.$refs.detailsBody.querySelector('[data-spec-tab=' + next + ']');
            if (target) target.focus();
        },
        showToast(msg) {
            this.toastMsg = msg;
            this.toastShow = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => this.toastShow = false, 2600);
        },
        toggleAll(allIds) {
            if (this.selectAll) {
                this.selectedIds = [...allIds];
            } else {
                this.selectedIds = [];
            }
        }
    }"
    @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)"
    @bulk-actions-request.window="
        if (selectedIds.length === 0) {
            showToast('{{ __('messages.bulk_select_first_toast') }}');
            document.getElementById('product-table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            $nextTick(() => document.getElementById('bulk-actions-bar')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        }
    ">

    {{-- ============================================================
         COMPACT PAGE HEADER (34px - 38px)
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400 grid place-items-center text-base font-bold shadow-xs flex-shrink-0">
                📦
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.product_management') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    ကုန်ပစ္စည်းစာရင်း၊ စတော့လက်ကျန်နှင့် ဈေးနှုန်းများ စီမံခန့်ခွဲခြင်း
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-1 sm:gap-1.5 self-start sm:self-auto shrink-0">
            <a href="{{ route('store.admin.products.master-data', $baseParams) }}"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1 cursor-pointer">
                <svg class="w-3.5 h-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h7l2 2h9v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6Z"/>
                </svg>
                <span>{{ __('messages.master_data') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/admin/products/import') }}"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1 cursor-pointer">
                <svg class="w-3.5 h-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                </svg>
                <span>{{ __('messages.product_import') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}"
               class="h-7 px-3 rounded-md text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.add_product') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         SUMMARY STAT CARDS (Centered Row-based Alignment)
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1" role="list" aria-label="{{ __('messages.product_summary') }}">
        <a href="{{ $clearFiltersUrl }}" role="listitem"
           class="group bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 px-3 py-1.5 flex items-center justify-center gap-2.5 sm:gap-3 transition-all duration-200 hover:shadow-xs active:scale-[.99] {{ $statBorders['total'] }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center {{ $statAccents['total'] }} shadow-inner text-xs sm:text-sm font-bold">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit truncate">
                    {{ number_format($summary['total']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.products') }}
                </p>
            </div>
        </a>

        <a href="{{ route('store.admin.products.index', array_merge($baseParams, $currentSort, ['stock_status' => 'in_stock'])) }}" role="listitem"
           class="group bg-white dark:bg-slate-900 rounded-lg border border-emerald-200/70 dark:border-emerald-900/50 shadow-2xs bg-emerald-50/20 dark:bg-emerald-950/10 px-3 py-1.5 flex items-center justify-center gap-2.5 sm:gap-3 transition-all duration-200 hover:shadow-xs active:scale-[.99] {{ $statBorders['in_stock'] }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center {{ $statAccents['in_stock'] }} shadow-inner text-xs sm:text-sm font-bold">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit truncate">
                    {{ number_format($summary['in_stock']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.in_stock') }}
                </p>
            </div>
        </a>

        <a href="{{ route('store.admin.products.index', array_merge($baseParams, $currentSort, ['stock_status' => 'out_of_stock'])) }}" role="listitem"
           class="group bg-white dark:bg-slate-900 rounded-lg border border-rose-200/70 dark:border-rose-900/50 shadow-2xs bg-rose-50/20 dark:bg-rose-950/10 px-3 py-1.5 flex items-center justify-center gap-2.5 sm:gap-3 transition-all duration-200 hover:shadow-xs active:scale-[.99] {{ $statBorders['out_of_stock'] }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center {{ $statAccents['out_of_stock'] }} shadow-inner text-xs sm:text-sm font-bold">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-outfit truncate">
                    {{ number_format($summary['out_of_stock']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-rose-600/80 dark:text-rose-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.out_of_stock') }}
                </p>
            </div>
        </a>

        <a href="{{ route('store.admin.products.index', array_merge($baseParams, $currentSort)) }}" role="listitem"
           class="group bg-white dark:bg-slate-900 rounded-lg border border-amber-200/70 dark:border-amber-900/50 shadow-2xs bg-amber-50/20 dark:bg-amber-950/10 px-3 py-1.5 flex items-center justify-center gap-2.5 sm:gap-3 transition-all duration-200 hover:shadow-xs active:scale-[.99] {{ $statBorders['featured'] }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center {{ $statAccents['featured'] }} shadow-inner text-xs sm:text-sm font-bold">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-outfit truncate">
                    {{ number_format($summary['featured']) }}
                    <span class="text-[10px] sm:text-xs font-bold text-slate-400 dark:text-slate-500 ml-0.5 tabular-nums">
                        / {{ number_format($summary['online']) }}
                    </span>
                </div>
                <p class="text-[9px] sm:text-[10px] text-amber-600/80 dark:text-amber-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.featured') }}
                    <span class="text-slate-400 dark:text-slate-500">
                        / {{ __('messages.products_online') }}
                    </span>
                </p>
            </div>
        </a>
    </div>

    @if (session('success'))
        <div class="w-full p-2 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ============================================================
         Reusable Admin Toolbar Component
         ============================================================ --}}
    @php
        $exportUrl = route('store.admin.products.export', array_merge(['store_slug' => $store->slug], request()->except('page')));
    @endphp
    <x-admin.toolbar
        :search="request('search', '')"
        :searchPlaceholder="__('messages.search_by_name_sku_brand_category')"
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest'     => __('messages.sort_newest'),
            'oldest'     => __('messages.sort_oldest'),
            'name_asc'   => __('messages.sort_name_asc'),
            'name_desc'  => __('messages.sort_name_desc'),
            'price_asc'  => __('messages.sort_price_low_high'),
            'price_desc' => __('messages.sort_price_high_low'),
            'stock'      => __('messages.stock_status'),
        ]"
        :filters="[
            'stock_status' => [
                'label'   => __('messages.stock_status'),
                'options' => ['in_stock' => __('messages.in_stock'), 'out_of_stock' => __('messages.out_of_stock')]
            ],
            'product_type' => [
                'label'   => __('messages.product_form_product_type'),
                'options' => [
                    'standard'     => __('messages.product_type_standard'),
                    'serialized'   => __('messages.product_type_serialized'),
                    'variant'      => __('messages.product_type_variant'),
                    'service'      => __('messages.product_type_service'),
                    'digital'      => __('messages.product_type_digital'),
                    'weight_based' => __('messages.product_type_weight_based'),
                ]
            ],
            'is_ecommerce' => [
                'label'   => __('messages.filter_online_visibility'),
                'options' => ['online' => __('messages.online_only'), 'counter_only' => __('messages.counter_only')]
            ],
            'category_id' => [
                'label'   => __('messages.categories'),
                'options' => $categories,
                'groups'  => $categoryGroups,
            ],
            'brand_id' => [
                'label'   => __('messages.brands'),
                'options' => $brands
            ]
        ]"
        :showViewToggle="true"
        :importUrl="url('/store/' . $store->slug . '/admin/products/import')"
        :exportUrl="$exportUrl"
        :paginator="$products"
        :perPageOptions="[25 => '25', 50 => '50', 100 => '100', 200 => '200', 'all' => __('messages.all')]"
        :bulkActions="true"
    />

    {{-- ============================================================
         BULK ACTION BAR
         ============================================================ --}}
    <div id="bulk-actions-bar" x-show="selectedIds.length > 0" x-cloak class="w-full bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 p-3 sm:p-4 rounded-lg sm:rounded-xl shadow-lg text-sm border-2 border-violet-500/40 dark:border-violet-500/50 scroll-mt-24">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 min-w-0">
                <span class="w-3 h-3 rounded-full bg-violet-600 animate-pulse"></span>
                <div class="font-black text-slate-900 dark:text-white text-xs sm:text-sm whitespace-nowrap">
                    <span x-text="selectedIds.length" class="text-violet-600 dark:text-violet-400 text-sm sm:text-base"></span> {{ __('messages.items_selected') }}
                </div>
                <button type="button" @click="selectAll = false; selectedIds = []; priceFormOpen = false"
                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-200 rounded-lg text-[11px] font-bold shadow-xs transition">
                    {{ __('messages.cancel') }}
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                <button type="button" @click="selectAll = true; toggleAll({{ json_encode($products->pluck('id')) }})"
                    class="min-h-[38px] px-3 py-1.5 bg-violet-50 hover:bg-violet-100 dark:bg-violet-950/60 dark:hover:bg-violet-900/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800 rounded-lg text-xs font-black shadow-xs transition">
                    {{ __('messages.select_all') }}
                </button>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-stock') }}" class="inline">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="stock_status" value="in_stock" />
                    <button type="submit" class="min-h-[38px] inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-black shadow-xs transition active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                        <span>{{ __('messages.bulk_set_in_stock') }}</span>
                    </button>
                </form>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-stock') }}" class="inline">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="stock_status" value="out_of_stock" />
                    <button type="submit" class="min-h-[38px] inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-black shadow-xs transition active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>{{ __('messages.bulk_set_out_of_stock') }}</span>
                    </button>
                </form>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-ecommerce') }}" class="inline">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_ecommerce" value="1" />
                    <button type="submit" class="min-h-[38px] inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-black shadow-xs transition active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                        <span>{{ __('messages.bulk_sell_online') }}</span>
                    </button>
                </form>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-ecommerce') }}" class="inline">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_ecommerce" value="0" />
                    <button type="submit" class="min-h-[38px] inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white rounded-lg text-xs font-black shadow-xs transition active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                        <span>{{ __('messages.bulk_counter_only') }}</span>
                    </button>
                </form>

                <button type="button" @click="priceFormOpen = !priceFormOpen"
                    class="min-h-[38px] inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-black shadow-xs transition active:scale-95">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ __('messages.bulk_adjust_prices') }}</span>
                </button>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-delete') }}" class="inline"
                    onsubmit="return confirm('{{ __('messages.bulk_delete_confirm') }}')">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <button type="submit" class="min-h-[38px] inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-rose-700 text-white rounded-lg text-xs font-black shadow-xs transition active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>{{ __('messages.bulk_delete') }}</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Slide-down Bulk Price Adjustment Form --}}
        <div x-show="priceFormOpen" x-transition x-cloak class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-700">
            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-prices') }}" class="flex flex-wrap items-end gap-2.5">
                @csrf
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="ids[]" :value="id" />
                </template>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.bulk_price_apply_to') }}</label>
                    <select name="apply_to" class="text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 min-h-[38px] font-medium">
                        <option value="both">{{ __('messages.bulk_price_retail_wholesale') }}</option>
                        <option value="retail">{{ __('messages.bulk_price_retail_only') }}</option>
                        <option value="wholesale">{{ __('messages.bulk_price_wholesale_only') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.bulk_price_direction') }}</label>
                    <select name="direction" class="text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 min-h-[38px] font-medium">
                        <option value="increase">{{ __('messages.bulk_price_increase') }}</option>
                        <option value="decrease">{{ __('messages.bulk_price_decrease') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.bulk_price_mode') }}</label>
                    <select name="mode" class="text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 min-h-[38px] font-medium">
                        <option value="percent">{{ __('messages.bulk_price_percent') }}</option>
                        <option value="amount">{{ __('messages.bulk_price_amount') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.price_value') }}</label>
                    <input type="number" step="any" min="0" name="value" required placeholder="{{ __('messages.bulk_price_value_placeholder') }}"
                        class="text-xs sm:text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 min-h-[38px] w-32 sm:w-36 placeholder-slate-400" />
                </div>
                <button type="submit" class="min-h-[38px] inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white rounded-lg text-xs sm:text-sm font-black shadow-xs transition">
                    {{ __('messages.bulk_apply_prices') }}
                </button>
            </form>
        </div>
    </div>

    {{-- ============================================================
         View 1: GOOGLE SHEETS STYLE DATA GRID TABLE
         ============================================================ --}}
    <div id="product-table" x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto max-h-[75vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                {{-- Spreadsheet Grid Header (Sticky Top) --}}
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        <th class="py-2.5 px-2.5 w-12 text-center bg-slate-200/70 dark:bg-slate-800">
                            <div class="inline-flex items-center justify-center gap-1">
                                <input type="checkbox" x-model="selectAll" @change="toggleAll({{ json_encode($products->pluck('id')) }})"
                                    class="w-3.5 h-3.5 rounded border-slate-400 dark:border-slate-600 text-violet-600 focus:ring-violet-500 dark:bg-slate-900 cursor-pointer"
                                    title="{{ __('messages.select_all') }}" />
                            </div>
                        </th>
                        <th class="py-2.5 px-2.5 w-14 text-center">{{ __('messages.table_image') }}</th>
                        <th class="py-2.5 px-3 min-w-[130px]">{{ __('messages.sku') }}</th>
                        <th class="py-2.5 px-3 min-w-[200px]">{{ __('messages.table_name_sku') }}</th>
                        <th class="py-2.5 px-3 min-w-[140px] hidden md:table-cell">{{ __('messages.table_category_brand') }}</th>
                        <th class="py-2.5 px-3 text-right min-w-[110px]">{{ __('messages.retail_price') ?? __('messages.table_prices') }}</th>
                        <th class="py-2.5 px-3 text-right min-w-[110px] hidden sm:table-cell">{{ __('messages.wholesale_price') ?? 'Wholesale' }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[100px]">{{ __('messages.stock_status') }}</th>
                        <th class="py-2.5 px-2 text-center w-14 hidden lg:table-cell">{{ __('messages.filter_online_visibility') }}</th>
                        <th class="py-2.5 px-2 text-center w-12 hidden lg:table-cell">{{ __('messages.featured') }}</th>
                        <th class="py-2.5 px-3 text-center w-36">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/90 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse ($products as $product)
                        <tr class="hover:bg-violet-50/60 dark:hover:bg-violet-950/20 divide-x divide-slate-200/80 dark:divide-slate-800 transition-colors group"
                            :class="selectedIds.includes({{ $product->id }}) ? 'bg-violet-50/80 dark:bg-violet-950/40 font-medium' : ''">
                            {{-- Col 1: Row Number & Checkbox --}}
                            <td class="py-2 px-2 text-center bg-slate-50/60 dark:bg-slate-800/40 text-slate-400 dark:text-slate-500 font-mono text-[11px]">
                                <div class="flex items-center justify-center gap-1.5">
                                    <span class="w-4 text-right text-[10px] text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300">
                                        {{ $loop->iteration + (method_exists($products, 'currentPage') ? ($products->currentPage() - 1) * $products->perPage() : 0) }}
                                    </span>
                                    <input type="checkbox" :value="{{ $product->id }}" x-model="selectedIds"
                                        class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500 dark:bg-slate-800 cursor-pointer" />
                                </div>
                            </td>

                            {{-- Col 2: Image Thumbnail --}}
                            <td class="py-1.5 px-2 text-center">
                                @if ($product->image_path)
                                    <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}"
                                        class="h-9 w-9 object-cover rounded border border-slate-200 dark:border-slate-700 mx-auto shadow-2xs group-hover:scale-105 transition-transform" />
                                @else
                                    <div class="h-9 w-9 bg-slate-100 dark:bg-slate-800 rounded border border-slate-200/80 dark:border-slate-700 flex items-center justify-center text-[10px] text-slate-400 mx-auto shadow-2xs">
                                        <span>📷</span>
                                    </div>
                                @endif
                            </td>

                            {{-- Col 3: SKU & Barcode --}}
                            <td class="py-2 px-3 font-mono">
                                <span class="font-bold text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/60 px-1.5 py-0.5 rounded border border-violet-200/60 dark:border-violet-800/60 text-[11px]">
                                    {{ $product->sku }}
                                </span>
                                @if ($product->barcode)
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 tracking-tight font-mono">
                                        {{ $product->barcode }}
                                    </div>
                                @endif
                            </td>

                            {{-- Col 4: Name, Shelf & Type --}}
                            <td class="py-2 px-3">
                                <div class="font-bold text-slate-900 dark:text-slate-100 hover:text-violet-600 dark:hover:text-violet-400 transition cursor-pointer leading-snug"
                                     @click="openDetails('{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/details') }}')"
                                     title="{{ $product->name }}">
                                    {{ $product->name }}
                                </div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    @if ($product->shelf_location)
                                        <span class="text-[10px] font-bold text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 px-1.5 py-0.2 rounded border border-amber-200/60 dark:border-amber-800/60">
                                            📍 {{ $product->shelf_location }}
                                        </span>
                                    @endif
                                    @if ($product->product_type && $product->product_type !== 'standard')
                                        <span class="text-[10px] font-bold uppercase tracking-wider px-1 py-0.2 rounded bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                            {{ __("messages.product_type_" . $product->product_type) }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Col 5: Category & Brand --}}
                            <td class="py-2 px-3 hidden md:table-cell">
                                <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                    {{ $product->category->name ?? '—' }}
                                </div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500">
                                    {{ $product->brand->name ?? '—' }}
                                </div>
                            </td>

                            {{-- Col 6: Retail Price --}}
                            <td class="py-2 px-3 text-right">
                                <div class="font-bold font-mono text-slate-900 dark:text-white tabular-nums text-xs sm:text-sm">
                                    Ks {{ number_format($product->retail_price) }}
                                </div>
                            </td>

                            {{-- Col 7: Wholesale Price --}}
                            <td class="py-2 px-3 text-right hidden sm:table-cell">
                                @if ($product->wholesale_price > 0)
                                    <div class="font-mono text-emerald-600 dark:text-emerald-400 tabular-nums font-bold text-xs">
                                        Ks {{ number_format($product->wholesale_price) }}
                                    </div>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">—</span>
                                @endif
                            </td>

                            {{-- Col 8: Stock Status (services & digital items are non-inventory) --}}
                            <td class="py-2 px-3 text-center">
                                @if (in_array($product->product_type, ['service', 'digital'], true))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                        <span>—</span>
                                        <span>{{ in_array($product->product_type, ['service'], true) ? __('messages.product_type_service_short') : __('messages.product_type_digital_short') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black {{ $product->stock_status === 'in_stock' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800/80' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $product->stock_status === 'in_stock' ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                                        <span>{{ $product->stock_status === 'in_stock' ? __('messages.in_stock') : __('messages.out_of_stock') }}</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Col 9: Online Toggle --}}
                            <td class="py-2 px-2 text-center hidden lg:table-cell">
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/toggle-ecommerce') }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                        title="{{ $product->is_ecommerce ? __('messages.online_only') : __('messages.counter_only') }}"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded border transition {{ $product->is_ecommerce ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300 border-sky-300 dark:border-sky-800 hover:bg-sky-200' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                                    </button>
                                </form>
                            </td>

                            {{-- Col 10: Featured Toggle --}}
                            <td class="py-2 px-2 text-center hidden lg:table-cell">
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/toggle-featured') }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                        title="{{ __('messages.featured') }}"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded border transition {{ $product->is_featured ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border-amber-300 dark:border-amber-800 hover:bg-amber-200' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="{{ $product->is_featured ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    </button>
                                </form>
                            </td>

                            {{-- Col 11: Action Buttons --}}
                            <td class="py-2 px-2.5 text-center">
                                <div class="inline-flex items-center gap-1 justify-center">
                                    {{-- Quick View --}}
                                    <button type="button" @click="openDetails('{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/details') }}')"
                                        title="{{ __('messages.action_view') }}"
                                        class="w-7 h-7 rounded border border-teal-200 dark:border-teal-800/80 inline-flex items-center justify-center text-teal-700 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/50 hover:bg-teal-100 dark:hover:bg-teal-900/50 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    {{-- Edit --}}
                                    <a href="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/edit') }}"
                                        title="{{ __('messages.edit') }}"
                                        class="w-7 h-7 rounded border border-violet-200 dark:border-violet-800/80 inline-flex items-center justify-center text-violet-700 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/50 hover:bg-violet-100 dark:hover:bg-violet-900/50 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                                    </a>
                                    {{-- Duplicate --}}
                                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/duplicate') }}" class="inline">
                                        @csrf
                                        <button type="submit" title="{{ __('messages.duplicate_title') }}"
                                            class="w-7 h-7 rounded border border-sky-200 dark:border-sky-800/80 inline-flex items-center justify-center text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/50 hover:bg-sky-100 dark:hover:bg-sky-900/50 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                        </button>
                                    </form>
                                    {{-- Delete --}}
                                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id) }}" class="inline"
                                        onsubmit="return confirm('{{ __('messages.delete') }} ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="{{ __('messages.delete') }}"
                                            class="w-7 h-7 rounded border border-rose-200 dark:border-rose-800/80 inline-flex items-center justify-center text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/50 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="p-8 sm:p-12 text-center">
                                <div class="mx-auto max-w-sm">
                                    <div class="mx-auto mb-4 w-14 h-14 rounded-xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400 dark:text-slate-500 text-2xl shadow-inner">
                                        📦
                                    </div>
                                    <p class="font-black text-slate-800 dark:text-slate-200 text-sm sm:text-base">{{ __('messages.no_products_found') }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('messages.no_products_hint') }}</p>
                                    <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}"
                                       class="px-4 py-2 mt-4 inline-flex items-center gap-1.5 rounded-lg text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-xs transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        <span>{{ __('messages.add_product') }}</span>
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
         View 2: CARD VIEW (Mobile 2 Col, Tablet 3 Col, Desktop 5 Col)
         ============================================================ --}}
    <div x-show="viewMode === 'card'" class="w-full grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-2 sm:gap-3 lg:gap-4">
        @forelse ($products as $product)
            <div class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg sm:rounded-xl overflow-hidden transition shadow-xs flex flex-col justify-between group hover:border-violet-300 dark:hover:border-violet-700/70 hover:shadow-sm">
                <div class="p-2 sm:p-3">
                    {{-- Product Image & Floating Badges --}}
                    <div class="relative mb-2 aspect-square w-full rounded-md sm:rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700">
                        @if ($product->image_path)
                            <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}"
                                class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        @else
                            <div class="h-full w-full flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 gap-0.5">
                                <span class="text-2xl sm:text-3xl">📷</span>
                                <span class="text-[9px] font-black uppercase tracking-wider">{{ __('messages.no_image_short') }}</span>
                            </div>
                        @endif
                        
                        {{-- Select Checkbox --}}
                        <div class="absolute top-1.5 left-1.5 z-10">
                            <input type="checkbox" :value="{{ $product->id }}" x-model="selectedIds"
                                class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500 bg-white/90 dark:bg-slate-800/90 backdrop-blur-xs cursor-pointer shadow-2xs" />
                        </div>

                        {{-- Stock Status Pill --}}
                        <span class="absolute top-1.5 right-1.5 z-10 inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full font-black text-[9px] sm:text-[10px] backdrop-blur-md shadow-2xs {{ $product->isInStock() ? 'bg-emerald-500/95 text-white' : 'bg-rose-500/95 text-white' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                            <span>{{ $product->isInStock() ? __('messages.in_stock') : __('messages.out_of_stock') }}</span>
                        </span>

                        {{-- Featured Star Badge --}}
                        @if ($product->is_featured)
                            <span class="absolute bottom-1.5 left-1.5 z-10 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full font-black text-[9px] bg-amber-500/95 text-white shadow-2xs">
                                ⭐ {{ __('messages.featured') }}
                            </span>
                        @endif
                    </div>

                    {{-- Category & Brand Meta --}}
                    <div class="flex items-center gap-1 text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mb-0.5 truncate">
                        <span class="font-bold text-slate-700 dark:text-slate-300 truncate">{{ $product->category->name ?? '—' }}</span>
                        <span>·</span>
                        <span class="truncate">{{ $product->brand->name ?? '—' }}</span>
                    </div>

                    {{-- Product Name --}}
                    <div class="font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm leading-snug line-clamp-2 hover:text-violet-600 dark:hover:text-violet-400 transition cursor-pointer min-h-[32px]"
                         @click="openDetails('{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/details') }}')"
                         title="{{ $product->name }}">
                        {{ $product->name }}
                    </div>

                    {{-- SKU & Shelf Location --}}
                    <div class="flex flex-wrap items-center gap-1 mt-1.5">
                        <span class="font-mono text-[10px] font-bold text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/60 px-1.5 py-0.2 rounded border border-violet-100 dark:border-violet-900/60 truncate max-w-full">
                            {{ $product->sku }}
                        </span>
                        @if ($product->shelf_location)
                            <span class="text-[9px] font-bold text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 px-1 py-0.2 rounded border border-amber-200/60 dark:border-amber-900/60 truncate">
                                📍 {{ $product->shelf_location }}
                            </span>
                        @endif
                    </div>

                    {{-- Retail & Wholesale Prices --}}
                    <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-baseline justify-between gap-1">
                        <div>
                            <span class="text-[9px] font-bold uppercase text-slate-400 block leading-none">{{ __('messages.retail') }}</span>
                            <span class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 tabular-nums font-mono">Ks {{ number_format($product->retail_price) }}</span>
                        </div>
                        @if ($product->wholesale_price > 0)
                            <div class="text-right">
                                <span class="text-[9px] font-bold uppercase text-emerald-500 block leading-none">{{ __('messages.wholesale') }}</span>
                                <span class="text-[11px] sm:text-xs font-bold text-emerald-600 dark:text-emerald-400 tabular-nums font-mono">Ks {{ number_format($product->wholesale_price) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Card Action Footer (4 Quick Action Buttons) --}}
                <div class="grid grid-cols-4 gap-1 p-1 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800">
                    {{-- Quick View --}}
                    <button type="button" @click="openDetails('{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/details') }}')"
                        title="{{ __('messages.action_view') }}"
                        class="min-h-[30px] inline-flex items-center justify-center rounded text-xs font-bold text-teal-700 dark:text-teal-400 bg-white dark:bg-slate-700/80 hover:bg-teal-50 dark:hover:bg-teal-950/50 border border-slate-200/60 dark:border-slate-600 shadow-2xs transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    {{-- Edit --}}
                    <a href="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/edit') }}"
                        title="{{ __('messages.edit') }}"
                        class="min-h-[30px] inline-flex items-center justify-center rounded text-xs font-bold text-violet-700 dark:text-violet-400 bg-white dark:bg-slate-700/80 hover:bg-violet-50 dark:hover:bg-violet-950/50 border border-slate-200/60 dark:border-slate-600 shadow-2xs transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                    </a>
                    {{-- Duplicate --}}
                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/duplicate') }}" class="inline">
                        @csrf
                        <button type="submit" title="{{ __('messages.duplicate_title') }}"
                            class="min-h-[30px] w-full inline-flex items-center justify-center rounded text-xs font-bold text-sky-700 dark:text-sky-400 bg-white dark:bg-slate-700/80 hover:bg-sky-50 dark:hover:bg-sky-950/50 border border-slate-200/60 dark:border-slate-600 shadow-2xs transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                    </form>
                    {{-- Delete --}}
                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id) }}" class="inline"
                        onsubmit="return confirm('{{ __('messages.delete') }} ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="{{ __('messages.delete') }}"
                            class="min-h-[30px] w-full inline-flex items-center justify-center rounded text-xs font-bold text-rose-700 dark:text-rose-400 bg-white dark:bg-slate-700/80 hover:bg-rose-50 dark:hover:bg-rose-950/50 border border-slate-200/60 dark:border-slate-600 shadow-2xs transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg sm:rounded-xl p-8 sm:p-12 text-center w-full">
                <div class="mx-auto mb-4 w-14 h-14 rounded-xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400 dark:text-slate-500 text-2xl shadow-inner">
                    📦
                </div>
                <p class="font-black text-slate-800 dark:text-slate-200 text-sm sm:text-base">{{ __('messages.no_products_found') }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('messages.no_products_hint') }}</p>
                <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}"
                   class="px-4 py-2 mt-4 inline-flex items-center gap-1.5 rounded-lg text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span>{{ __('messages.add_product') }}</span>
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-4 sm:mt-6">{{ $products->links() }}</div>

    {{-- ============================================================
         Product Details Modal
         ============================================================ --}}
    <div x-show="detailsOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-label="{{ __('messages.product_details') }}" @keydown.escape.window="detailsOpen = false">
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-md" @click="detailsOpen = false"></div>
        <div class="relative flex min-h-full items-start justify-center p-3 sm:p-6">
            <div class="relative w-full max-w-2xl rounded-lg sm:rounded-2xl border border-slate-200/80 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900 overflow-hidden my-4 sm:my-8">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 px-4 sm:px-5 py-3.5 bg-slate-50/60 dark:bg-slate-800/60">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-violet-600 text-white grid place-items-center text-xs shadow-xs">📦</span>
                        <h2 class="text-sm font-black uppercase tracking-wide text-slate-800 dark:text-slate-100">{{ __('messages.product_details') }}</h2>
                    </div>
                    <button type="button" @click="detailsOpen = false" aria-label="{{ __('messages.close') }}"
                        class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 dark:hover:text-slate-200 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-4 sm:p-6" x-ref="detailsBody" @click="onDetailsClick($event)" @keydown="onDetailsKeydown($event)">
                    <div x-show="detailsLoading" class="py-12 text-center text-sm font-bold text-slate-500 dark:text-slate-400">
                        <svg class="w-6 h-6 animate-spin mx-auto mb-2 text-violet-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span>{{ __('messages.loading') }}</span>
                    </div>
                    <div x-show="!detailsLoading" x-html="detailsHtml"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast notification (bulk actions feedback) --}}
    <div x-show="toastShow" x-transition x-cloak
        class="fixed bottom-24 right-1/2 translate-x-1/2 z-50 px-4 py-2.5 rounded-2xl bg-slate-900/95 dark:bg-slate-800 text-white text-xs sm:text-sm font-bold shadow-2xl border border-slate-700/80 whitespace-nowrap backdrop-blur-md">
        <span x-text="toastMsg"></span>
    </div>

    {{-- Floating Action Button: Add New Product --}}
    <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}"
       title="{{ __('messages.add_new_product') }}"
       class="fixed bottom-[calc(env(safe-area-inset-bottom,0px)+1.5rem)] right-4 sm:right-6 z-40 flex items-center justify-center w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white shadow-xl shadow-violet-600/30 hover:shadow-violet-700/50 hover:scale-110 active:scale-95 transition-all duration-200 group">
        <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" aria-hidden="true">
            <path d="M12 4v16m8-8H4" />
        </svg>
        <span class="absolute right-full mr-3 px-3 py-1.5 bg-slate-900/90 dark:bg-slate-800 text-white text-xs font-bold rounded-xl whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none shadow-lg backdrop-blur-xs">{{ __('messages.add_new_product') }}</span>
    </a>
</div>
@endsection

