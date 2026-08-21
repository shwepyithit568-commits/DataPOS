@extends('layouts.admin.app')

@section('content')
@php
    // Accent color tokens for the 4 stat cards + featured/online pill badges.
    // Matches the Master Data hub visual language for cross-page consistency.
    $statAccents = [
        'total'        => 'bg-violet-100 text-violet-600 dark:bg-violet-950/60 dark:text-violet-400',
        'in_stock'     => 'bg-green-100 text-green-600 dark:bg-green-950/60 dark:text-green-400',
        'out_of_stock' => 'bg-red-100 text-red-600 dark:bg-red-950/60 dark:text-red-400',
        'featured'     => 'bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400',
        'online'       => 'bg-sky-100 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400',
    ];

    $statBorders = [
        'total'        => 'hover:border-violet-200 dark:hover:border-violet-800/60',
        'in_stock'     => 'hover:border-green-200 dark:hover:border-green-800/60',
        'out_of_stock' => 'hover:border-red-200 dark:hover:border-red-800/60',
        'featured'     => 'hover:border-amber-200 dark:hover:border-amber-800/60',
    ];

    // Build clean filter-preserving URLs for stat-card clicks.
    $baseParams  = ['store_slug' => $store->slug];
    $currentSort = request()->only('sort', 'search');
    $clearFiltersUrl = route('store.admin.products.index', $baseParams);
@endphp
<div class="w-full space-y-4 sm:space-y-5"
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
                t.classList.toggle('border-sky-500', active);
                t.classList.toggle('text-sky-600', active);
                t.classList.toggle('dark:text-sky-400', active);
                t.classList.toggle('border-transparent', !active);
                t.classList.toggle('text-gray-500', !active);
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
         HERO HEADER — eyebrow, title, subtitle, CTA row
         Pattern mirrors Master Data hub / Suppliers / PO list pages
         for cross-module visual consistency.
         ============================================================ --}}
    <header class="admin-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400">
                {{ __('messages.product_catalog') }}
            </p>
            <h1 class="admin-page-title mt-0.5">
                {{ __('messages.product_management') }}
            </h1>
            <p class="admin-page-sub mt-1">
                {{ $store->name }} · {{ __('messages.product_management_sub') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a href="{{ route('store.admin.products.master-data', $baseParams) }}"
               class="admin-secondary-btn">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                    <path d="M4 6h7l2 2h9v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6Z"/>
                </svg>
                <span class="hidden sm:inline">{{ __('messages.master_data') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/admin/products/import') }}"
               class="admin-secondary-btn">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                </svg>
                <span class="hidden sm:inline">{{ __('messages.product_import') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}"
               class="admin-primary-btn">
                <span>{{ __('messages.add_product') }}</span>
            </a>
        </div>
    </header>

    {{-- ============================================================
         SUMMARY STAT CARDS (4-up — mobile 2x2, desktop 1x4)
         Clickable: each card applies its own filter (or clears for
         Total).  Card #4 splits the count line to show BOTH Featured
         (primary) / Sell Online (sub) so we fit 5 vital counts in
         the 4-card POS grid without overflow.
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3" role="list" aria-label="{{ __('messages.product_summary') }}">
        <a href="{{ $clearFiltersUrl }}" role="listitem"
           class="group bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3 transition hover:shadow-md active:scale-[.99] {{ $statBorders['total'] }}">
            <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl grid place-items-center {{ $statAccents['total'] }}">
                <svg class="w-[18px] h-[18px] sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums">
                    {{ number_format($summary['total']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-semibold uppercase tracking-wide">
                    {{ __('messages.products') }}
                </p>
            </div>
        </a>

        <a href="{{ route('store.admin.products.index', array_merge($baseParams, $currentSort, ['stock_status' => 'in_stock'])) }}" role="listitem"
           class="group bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3 transition hover:shadow-md active:scale-[.99] {{ $statBorders['in_stock'] }}">
            <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl grid place-items-center {{ $statAccents['in_stock'] }}">
                <svg class="w-[18px] h-[18px] sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums">
                    {{ number_format($summary['in_stock']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-semibold uppercase tracking-wide">
                    {{ __('messages.in_stock') }}
                </p>
            </div>
        </a>

        <a href="{{ route('store.admin.products.index', array_merge($baseParams, $currentSort, ['stock_status' => 'out_of_stock'])) }}" role="listitem"
           class="group bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3 transition hover:shadow-md active:scale-[.99] {{ $statBorders['out_of_stock'] }}">
            <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl grid place-items-center {{ $statAccents['out_of_stock'] }}">
                <svg class="w-[18px] h-[18px] sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums">
                    {{ number_format($summary['out_of_stock']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-semibold uppercase tracking-wide">
                    {{ __('messages.out_of_stock') }}
                </p>
            </div>
        </a>

        <a href="{{ route('store.admin.products.index', array_merge($baseParams, $currentSort)) }}" role="listitem"
           class="group bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3 transition hover:shadow-md active:scale-[.99] {{ $statBorders['featured'] }}">
            <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl grid place-items-center {{ $statAccents['featured'] }}">
                <svg class="w-[18px] h-[18px] sm:w-5 sm:h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums">
                    {{ number_format($summary['featured']) }}
                    <span class="text-[11px] sm:text-sm font-bold text-slate-400 dark:text-slate-500 ml-1 tabular-nums">
                        / {{ number_format($summary['online']) }}
                    </span>
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-semibold uppercase tracking-wide">
                    {{ __('messages.featured') }}
                    <span class="text-slate-400 dark:text-slate-500">
                        / {{ __('messages.products_online') }}
                    </span>
                </p>
            </div>
        </a>
    </div>

    @if (session('success'))
        <div class="p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300">{{ session('success') }}</div>
    @endif

    {{-- ============================================================
         Reusable Admin Toolbar Component
         (search, filters, sort, view toggle, import/export,
         per-page, result count, active filter pills)
         All user-facing labels passed through i18n.
         ============================================================ --}}
    @php
        $exportUrl = url('/store/' . $store->slug . '/admin/products/export');
        if (request()->has('per_page')) {
            $exportUrl .= '?' . http_build_query(['per_page' => request('per_page')]);
        }
    @endphp
    <x-admin.toolbar
        :search="request('search', '')"
        :searchPlaceholder="__('messages.search_by_name_sku_brand_category')"
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest'     => __('messages.sort_newest'),
            'oldest'     => __('messages.sort_oldest'),
            'price_asc'  => __('messages.sort_price_low_high'),
            'price_desc' => __('messages.sort_price_high_low'),
            'stock'      => __('messages.stock_status'),
        ]"
        :filters="[
            'stock_status' => [
                'label'   => __('messages.stock_status'),
                'options' => ['in_stock' => __('messages.in_stock'), 'out_of_stock' => __('messages.out_of_stock')]
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
        :perPageOptions="[50 => '50', 100 => '100', 200 => '200', 'all' => __('messages.all')]"
        :bulkActions="true"
    />

    {{-- ============================================================
         BULK ACTION BAR
         - Elevated segmented-surface style (rounded-xl, not rounded-lg)
         - Replaced emoji badges with proper inline SVG icons
         - All text / form labels use i18n
         - All buttons use min-h-[44px] / min-h-11 for iOS comfort
         ============================================================ --}}
    <div id="bulk-actions-bar" x-show="selectedIds.length > 0" x-cloak class="bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 p-2.5 sm:p-3 rounded-2xl shadow-sm text-sm border border-slate-200 dark:border-slate-700 scroll-mt-24">
        <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <div class="flex items-center gap-2 min-w-0">
                <div class="font-black text-slate-800 dark:text-slate-100 whitespace-nowrap">
                    <span x-text="selectedIds.length"></span> {{ __('messages.items_selected') }}
                </div>
                <button type="button" @click="selectAll = false; selectedIds = []; priceFormOpen = false"
                    class="min-h-[40px] px-3 py-1.5 bg-slate-500 hover:bg-slate-600 text-white rounded-xl text-xs font-black shadow-sm transition">
                    {{ __('messages.cancel') }}
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 ml-auto">
                <button type="button" @click="selectAll = true; toggleAll({{ json_encode($products->pluck('id')) }})"
                    class="min-h-[40px] px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-black shadow-sm transition">
                    {{ __('messages.select_all') }}
                </button>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-stock') }}" class="flex items-center">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="stock_status" value="in_stock" />
                    <button type="submit" class="min-h-[40px] inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-xs font-black shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                        {{ __('messages.bulk_set_in_stock') }}
                    </button>
                </form>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-stock') }}" class="flex items-center">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="stock_status" value="out_of_stock" />
                    <button type="submit" class="min-h-[40px] inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-xl text-xs font-black shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18L18 6M6 6l12 12"/></svg>
                        {{ __('messages.bulk_set_out_of_stock') }}
                    </button>
                </form>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-ecommerce') }}" class="flex items-center">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_ecommerce" value="1" />
                    <button type="submit" class="min-h-[40px] inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-black shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                        {{ __('messages.bulk_sell_online') }}
                    </button>
                </form>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-ecommerce') }}" class="flex items-center">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_ecommerce" value="0" />
                    <button type="submit" class="min-h-[40px] inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-500 hover:bg-slate-600 text-white rounded-xl text-xs font-black shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 15h6"/></svg>
                        {{ __('messages.counter_only') }}
                    </button>
                </form>

                <button type="button" @click="priceFormOpen = !priceFormOpen"
                    class="min-h-[40px] inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-black shadow-sm transition"
                    :class="priceFormOpen ? 'ring-2 ring-amber-300 dark:ring-amber-700' : ''">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    {{ __('messages.bulk_adjust_prices') }}
                </button>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-delete') }}" data-confirm="{{ __('messages.bulk_delete_confirm') }}" class="flex items-center">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <button type="submit" class="min-h-[40px] inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-black shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                        {{ __('messages.bulk_delete') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Bulk Price Adjustment Form --}}
        <div x-show="priceFormOpen" x-transition x-cloak class="mt-3 pt-3 border-t border-slate-300/60 dark:border-slate-700">
            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-prices') }}" class="flex flex-wrap items-end gap-2 sm:gap-3">
                @csrf
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="ids[]" :value="id" />
                </template>
                <div>
                    <label class="block text-[10px] sm:text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-black">{{ __('messages.bulk_price_apply_to') }}</label>
                    <select name="apply_to" class="text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 min-h-[40px]">
                        <option value="both">{{ __('messages.bulk_price_retail_wholesale') }}</option>
                        <option value="retail">{{ __('messages.bulk_price_retail_only') }}</option>
                        <option value="wholesale">{{ __('messages.bulk_price_wholesale_only') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] sm:text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-black">{{ __('messages.bulk_price_direction') }}</label>
                    <select name="direction" class="text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 min-h-[40px]">
                        <option value="increase">{{ __('messages.bulk_price_increase') }}</option>
                        <option value="decrease">{{ __('messages.bulk_price_decrease') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] sm:text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-black">{{ __('messages.bulk_price_mode') }}</label>
                    <select name="mode" class="text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 min-h-[40px]">
                        <option value="percent">{{ __('messages.bulk_price_percent') }}</option>
                        <option value="amount">{{ __('messages.bulk_price_amount') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] sm:text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-black">{{ __('messages.price_value') }}</label>
                    <input type="number" name="value" required min="0" step="100" :placeholder="'{{ __('messages.bulk_price_value_placeholder') }}'"
                        class="text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 px-3 py-2 min-h-[40px] w-32 sm:w-36 placeholder-slate-400" />
                </div>
                <button type="submit" class="min-h-[40px] inline-flex items-center gap-1.5 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs sm:text-sm font-black shadow-sm transition">
                    {{ __('messages.bulk_apply_prices') }}
                </button>
            </form>
        </div>
    </div>

    {{-- ============================================================
         View 1: TABLE VIEW — compact POS density
         Replaced ⭐/🛒 emoji buttons with SVG-icon pills
         Column headers localized; touch targets ≥ 40px
         ============================================================ --}}
    <div id="product-table" x-show="viewMode === 'table'" class="admin-panel overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
            <thead class="bg-slate-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-black text-slate-700 dark:text-slate-200">
                <tr>
                    <th class="p-3 w-10">
                        <input type="checkbox" x-model="selectAll" @change="toggleAll({{ json_encode($products->pluck('id')) }})" class="rounded border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                    </th>
                    <th class="p-3">{{ __('messages.table_image') }}</th>
                    <th class="p-3">{{ __('messages.table_name_sku') }}</th>
                    <th class="p-3">{{ __('messages.table_category_brand') }}</th>
                    <th class="p-3">{{ __('messages.table_prices') }}</th>
                    <th class="p-3">{{ __('messages.stock_status') }}</th>
                    <th class="p-3">{{ __('messages.table_flags') }}</th>
                    <th class="p-3">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-slate-700">
                @forelse ($products as $product)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/50 transition">
                        <td class="p-3">
                            <input type="checkbox" :value="{{ $product->id }}" x-model="selectedIds" class="rounded border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        </td>
                        <td class="p-3">
                            @if ($product->image_path)
                                <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="h-10 w-10 object-cover rounded-lg border dark:border-slate-600" />
                            @else
                                <div class="h-10 w-10 bg-slate-100 dark:bg-slate-700 rounded-lg border dark:border-slate-600 flex items-center justify-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ __('messages.no_image_short') }}</div>
                            @endif
                        </td>
                        <td class="p-3">
                            <div class="font-black text-slate-900 dark:text-slate-100 leading-tight">{{ $product->name }}</div>
                            <div class="text-xs text-slate-400 dark:text-slate-500 font-mono mt-0.5">{{ __('messages.sku') }}: {{ $product->sku }}</div>
                        </td>
                        <td class="p-3">
                            <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ $product->category->name ?? '—' }}</div>
                            <div class="text-xs text-slate-400 dark:text-slate-500">{{ $product->brand->name ?? '—' }}</div>
                        </td>
                        <td class="p-3 tabular-nums">
                            <div class="font-semibold">{{ __('messages.retail') }}: Ks {{ number_format($product->retail_price) }}</div>
                            <div class="text-green-600 dark:text-green-400 font-black">
                                {{ __('messages.wholesale') }}: {{ $product->wholesale_price > 0 ? 'Ks ' . number_format($product->wholesale_price) : '—' }}
                            </div>
                        </td>
                        <td class="p-3">
                            <span class="inline-flex items-center px-2.5 py-1 text-xs rounded-xl font-black {{ $product->isInStock() ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300' }}">
                                {{ $product->stock_status === 'in_stock' ? __('messages.in_stock') : __('messages.out_of_stock') }}
                            </span>
                        </td>
                        <td class="p-3">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/toggle-featured') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded-xl font-black transition {{ $product->is_featured ? 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 hover:bg-amber-200' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-200' }}">
                                        @if ($product->is_featured)
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                        @else
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                        @endif
                                        {{ $product->is_featured ? __('messages.featured') : __('messages.toggle_feature') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/toggle-ecommerce') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded-xl font-black transition {{ $product->is_ecommerce ? 'bg-sky-100 dark:bg-sky-900/50 text-sky-700 dark:text-sky-300 hover:bg-sky-200' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-200' }}">
                                        @if ($product->is_ecommerce)
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                                            {{ __('messages.online_only') }}
                                        @else
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 15h6"/></svg>
                                            {{ __('messages.counter_only') }}
                                        @endif
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td class="p-3">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <button type="button" @click="openDetails('{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/details') }}')"
                                    class="min-h-[40px] inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-black text-teal-700 dark:text-teal-400 hover:bg-teal-50 dark:hover:bg-teal-950/40 transition">
                                    {{ __('messages.action_view') }}
                                </button>
                                <a href="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/edit') }}"
                                    class="min-h-[40px] inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-black text-violet-700 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                                    {{ __('messages.edit') }}
                                </a>
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/duplicate') }}" class="inline">
                                    @csrf
                                    <button type="submit" title="{{ __('messages.duplicate_title') }}"
                                        class="min-h-[40px] inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-black text-sky-700 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/40 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                        {{ __('messages.action_copy') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="min-h-[40px] inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-black text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                        {{ __('messages.delete') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-8 sm:p-12 text-center">
                            <div class="mx-auto max-w-sm">
                                <div class="mx-auto mb-4 w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400 dark:text-slate-500">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <p class="font-black text-slate-800 dark:text-slate-200">{{ __('messages.no_products_found') }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ __('messages.no_products_hint') }}</p>
                                <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}"
                                   class="admin-primary-btn mt-4 inline-flex">
                                    {{ __('messages.add_product') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============================================================
         View 2: CARD VIEW
         Mobile: 2 cols, Tablet: 3 cols, Desktop: 4 cols, 2XL: 5 cols
         Rounded-xl surface with subtle border, hover-shadow lift
         Action row buttons all ≥ min-h-11
         ============================================================ --}}
    <div x-show="viewMode === 'card'" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-2 sm:gap-3">
        @forelse ($products as $product)
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden transition-colors duration-200 hover:shadow-md hover:border-slate-300 dark:hover:border-slate-600">
                <div class="p-3 sm:p-4">
                    <div class="relative mb-2">
                        @if ($product->image_path)
                            <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="h-32 sm:h-40 w-full object-cover rounded-xl border dark:border-slate-700" />
                        @else
                            <div class="h-32 sm:h-40 w-full bg-slate-100 dark:bg-slate-700 rounded-xl border dark:border-slate-700 flex items-center justify-center text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ __('messages.no_image_short') }}</div>
                        @endif
                        <div class="absolute top-2 left-2 z-10">
                            <input type="checkbox" :value="{{ $product->id }}" x-model="selectedIds" class="rounded border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                        </div>
                        <span class="absolute top-2 right-2 z-10 inline-flex items-center px-2 py-1 rounded-xl font-black text-[11px] {{ $product->isInStock() ? 'bg-green-100 dark:bg-green-900/80 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/80 text-red-700 dark:text-red-300' }}">
                            {{ $product->isInStock() ? __('messages.in_stock') : __('messages.out_of_stock') }}
                        </span>
                    </div>
                    <div class="font-black text-slate-900 dark:text-slate-100 text-sm break-words leading-tight" title="{{ $product->name }}">{{ $product->name }}</div>
                    <div class="text-[11px] text-slate-400 dark:text-slate-500 truncate font-mono mt-0.5">{{ __('messages.sku') }}: {{ $product->sku }}</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">{{ $product->category->name ?? '—' }} · {{ $product->brand->name ?? '—' }}</div>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        <span class="text-sm font-black text-slate-900 dark:text-slate-100 tabular-nums">Ks {{ number_format($product->retail_price) }}</span>
                        @if ($product->wholesale_price > 0)
                            <span class="text-[11px] font-black text-green-600 dark:text-green-400 tabular-nums">{{ __('messages.wholesale') }}: Ks {{ number_format($product->wholesale_price) }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-1 px-3 sm:px-4 py-2.5 border-t border-slate-100 dark:border-slate-700/60">
                    <button type="button" @click="openDetails('{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/details') }}')"
                        class="min-h-11 flex-1 inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-xl text-xs font-black text-teal-700 dark:text-teal-400 hover:bg-teal-50 dark:hover:bg-teal-950/40 transition">
                        {{ __('messages.action_view') }}
                    </button>
                    <a href="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/edit') }}"
                        class="min-h-11 flex-1 inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-xl text-xs font-black text-violet-700 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition">
                        {{ __('messages.edit') }}
                    </a>
                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/duplicate') }}" class="inline flex-1">
                        @csrf
                        <button type="submit" title="{{ __('messages.duplicate_title') }}"
                            class="min-h-11 w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-xl text-xs font-black text-sky-700 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/40 transition">
                            {{ __('messages.action_copy') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id) }}" class="inline flex-1 ml-auto">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="min-h-11 w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-xl text-xs font-black text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition">
                            {{ __('messages.action_delete_short') }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-6 sm:p-10 text-center">
                <div class="mx-auto mb-4 w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-900/70 grid place-items-center text-slate-400 dark:text-slate-500">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <p class="font-black text-slate-800 dark:text-slate-200">{{ __('messages.no_products_found') }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ __('messages.no_products_hint') }}</p>
                <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}"
                   class="admin-primary-btn mt-4 inline-flex">
                    {{ __('messages.add_product') }}
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-2 sm:mt-4">{{ $products->links() }}</div>

    {{-- ============================================================
         Product Details Modal
         Uses backdrop-click pattern (NOT @click.outside) — per project
         convention because the x-show sync patch breaks the outside
         click guard.  All text localized.
         ============================================================ --}}
    <div x-show="detailsOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-label="{{ __('messages.product_details') }}" @keydown.escape.window="detailsOpen = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="detailsOpen = false"></div>
        <div class="relative flex min-h-full items-start justify-center p-4 sm:p-6">
            <div class="relative w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
                    <h2 class="text-sm font-black uppercase tracking-wide text-slate-700 dark:text-slate-200">{{ __('messages.product_details') }}</h2>
                    <button type="button" @click="detailsOpen = false" aria-label="{{ __('messages.close') }}"
                        class="rounded-xl p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-4 sm:p-5" x-ref="detailsBody" @click="onDetailsClick($event)" @keydown="onDetailsKeydown($event)">
                    <div x-show="detailsLoading" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('messages.loading') }}</div>
                    <div x-show="!detailsLoading" x-html="detailsHtml"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast notification (bulk actions feedback) --}}
    <div x-show="toastShow" x-transition x-cloak
        class="fixed bottom-24 right-1/2 translate-x-1/2 z-50 px-4 py-2.5 rounded-xl bg-slate-900/95 dark:bg-slate-700 text-white text-sm font-semibold shadow-xl border border-slate-700/50 whitespace-nowrap">
        <span x-text="toastMsg"></span>
    </div>

    {{-- Floating Action Button: Add New Product (only visible below content height) --}}
    <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}"
       title="{{ __('messages.add_new_product') }}"
       class="fixed bottom-[calc(env(safe-area-inset-bottom,0px)+1.5rem)] right-4 sm:right-6 z-40 flex items-center justify-center w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-violet-600 hover:bg-violet-700 text-white shadow-lg shadow-violet-600/30 hover:shadow-violet-700/40 hover:scale-110 active:scale-95 transition-all duration-200 group">
        <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" aria-hidden="true">
            <path d="M12 4v16m8-8H4" />
        </svg>
        <span class="absolute right-full mr-3 px-2.5 py-1 bg-slate-800 dark:bg-slate-700 text-white text-xs font-semibold rounded-xl whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none">{{ __('messages.add_new_product') }}</span>
    </a>
</div>
@endsection
