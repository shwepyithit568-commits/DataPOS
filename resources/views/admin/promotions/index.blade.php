@extends('layouts.admin.app')

@section('title', __('messages.promotion_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection $promotions */
    $storeRouteParams = ['store_slug' => $store->slug];
    $baseParams = $storeRouteParams;
    $currentSort = request()->only('sort', 'search');
    $clearFiltersUrl = route('store.admin.promotions.index', $baseParams);

    // Accent color tokens for KPI stat cards
    $statAccents = [
        'total'     => 'bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300',
        'active'    => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300',
        'expired'   => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        'uses'      => 'bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300',
        'discount'  => 'bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300',
    ];

    $statBorders = [
        'total'     => 'hover:border-violet-300 dark:hover:border-violet-700/80',
        'active'    => 'hover:border-emerald-300 dark:hover:border-emerald-700/80',
        'expired'   => 'hover:border-slate-300 dark:hover:border-slate-600',
        'uses'      => 'hover:border-amber-300 dark:hover:border-amber-700/80',
        'discount'  => 'hover:border-rose-300 dark:hover:border-rose-700/80',
    ];
    $fmtTotalDiscount = format_currency($stats['total_discount'], $store);
@endphp

@section('content')
<div class="w-full space-y-0.5 pb-6"
     x-data="{
        viewMode: localStorage.getItem('admin_promotions_view_mode') || 'table',
        showCreateModal: false,
        showEditModal: false,
        showValidateModal: false,
        toastShow: false,
        toastMsg: '',

        editPromo: {
            id: null,
            name: '',
            code: '',
            type: 'percent_off',
            value: 0,
            min_order_amount: 0,
            category_id: '',
            product_id: '',
            total_uses_limit: '',
            per_customer_limit: '',
            starts_at: '',
            expires_at: '',
            is_active: 1,
            is_public: 0
        },

        validateCode: '',
        validateTotal: 50000,
        validateResult: null,
        validating: false,

        showToast(msg) {
            this.toastMsg = msg;
            this.toastShow = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => this.toastShow = false, 2600);
        },

        copyCode(code) {
            if (!code) return;
            navigator.clipboard.writeText(code);
            this.showToast('{{ __('messages.promotion_code_copied') }}');
        },

        openEdit(id, name, code, type, value, minOrder, catId, prodId, usesLimit, perLimit, startsAt, expiresAt, isActive, isPublic) {
            this.editPromo = {
                id: id,
                name: name,
                code: code || '',
                type: type,
                value: value,
                min_order_amount: minOrder || 0,
                category_id: catId || '',
                product_id: prodId || '',
                total_uses_limit: usesLimit || '',
                per_customer_limit: perLimit || '',
                starts_at: startsAt || '',
                expires_at: expiresAt || '',
                is_active: isActive ? 1 : 0,
                is_public: isPublic ? 1 : 0
            };
            this.showEditModal = true;
        },

        async checkCoupon() {
            if (!this.validateCode) return;
            this.validating = true;
            this.validateResult = null;
            try {
                const url = '{{ route('store.admin.promotions.validate_coupon', $storeRouteParams) }}'
                    + '?code=' + encodeURIComponent(this.validateCode)
                    + '&order_total=' + encodeURIComponent(this.validateTotal);
                const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                this.validateResult = await r.json();
            } catch (e) {
                this.validateResult = { valid: false, message: 'Failed to validate coupon.' };
            } finally {
                this.validating = false;
            }
        }
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_promotions_view_mode', $event.detail)">

    {{-- Floating Toast Notification --}}
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
    <header class="w-full flex flex-wrap items-center justify-between gap-1 bg-white dark:bg-slate-900 rounded-lg px-2 py-1.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition min-h-[42px]">
        <div class="flex items-center gap-2 min-w-0">
            <div class="shrink-0 w-7 h-7 rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 grid place-items-center shadow-inner">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            </div>
            <div class="min-w-0">
                <h1 class="text-sm font-black text-slate-900 dark:text-white tracking-tight truncate leading-none">{{ __('messages.promotion_title') }}</h1>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ $store->name }}</p>
            </div>
        </div>
        <div class="flex items-center gap-1 shrink-0">
            <button type="button" @click="showValidateModal = true" id="btn-test-coupon"
                    class="h-7 px-2.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs active:scale-95">
                <svg class="w-3 h-3 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="hidden sm:inline">{{ __('messages.promotion_test_coupon') }}</span>
            </button>
            <button type="button" @click="showCreateModal = true" id="btn-create-promotion"
                    class="h-7 px-2.5 rounded-lg text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                {{ __('messages.promotion_add') }}
            </button>
        </div>
    </header>

    {{-- Flash Notifications & Validation Errors --}}
    @if(session('success'))
        <div class="w-full p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="w-full p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-2xs">
            @foreach($errors->all() as $e)
                <div class="flex items-center gap-1.5 font-bold"><span>⚠️</span><span>{{ $e }}</span></div>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         KPI STAT CARDS — 5 responsive interactive cards
         ============================================================ --}}
    <div class="w-full grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-0.5 sm:gap-1" role="list" aria-label="{{ __('messages.promotion_title') }}">
        {{-- Total --}}
        <a href="{{ $clearFiltersUrl }}" role="listitem"
           class="group bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 px-2 py-2 flex items-center justify-center gap-2.5 sm:gap-3 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['total'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['total'] }} shadow-inner">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            </div>
            <div class="text-center">
                <p class="text-base sm:text-xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">{{ number_format($stats['total']) }}</p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-bold uppercase tracking-wider">{{ __('messages.promotion_total') }}</p>
            </div>
        </a>

        {{-- Active --}}
        <a href="{{ route('store.admin.promotions.index', array_merge($baseParams, $currentSort, ['status' => 'active'])) }}" role="listitem"
           class="group bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 px-2 py-2 flex items-center justify-center gap-2.5 sm:gap-3 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['active'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['active'] }} shadow-inner">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="text-center">
                <p class="text-base sm:text-xl font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit">{{ number_format($stats['active']) }}</p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-bold uppercase tracking-wider">{{ __('messages.promotion_active') }}</p>
            </div>
        </a>

        {{-- Expired / Inactive --}}
        <a href="{{ route('store.admin.promotions.index', array_merge($baseParams, $currentSort, ['status' => 'expired'])) }}" role="listitem"
           class="group bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 px-2 py-2 flex items-center justify-center gap-2.5 sm:gap-3 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['expired'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['expired'] }} shadow-inner">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="text-center">
                <p class="text-base sm:text-xl font-black text-slate-700 dark:text-slate-300 leading-none tabular-nums font-outfit">{{ number_format($stats['expired']) }}</p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-bold uppercase tracking-wider">{{ __('messages.promotion_expired') }}</p>
            </div>
        </a>

        {{-- Total Uses --}}
        <div role="listitem"
             class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 px-2 py-2 flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['uses'] }} shadow-inner">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
            </div>
            <div class="text-center">
                <p class="text-base sm:text-xl font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-outfit">{{ number_format($stats['total_uses']) }}</p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-bold uppercase tracking-wider">{{ __('messages.promotion_total_uses') }}</p>
            </div>
        </div>

        {{-- Total Discount Given --}}
        <div role="listitem"
             class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 px-2 py-2 flex items-center justify-center gap-2.5 sm:gap-3 col-span-2 sm:col-span-1">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['discount'] }} shadow-inner">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="text-center">
                <p class="text-base sm:text-xl font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-outfit">{{ $fmtTotalDiscount }}</p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-bold uppercase tracking-wider">{{ __('messages.promotion_total_discount') }}</p>
            </div>
        </div>
    </div>

    {{-- ============================================================
         REUSABLE ADMIN TOOLBAR COMPONENT
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', '')"
        :searchPlaceholder="__('messages.search_by_name_sku_brand_category')"
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest'     => __('messages.promotion_sort_newest'),
            'oldest'     => __('messages.promotion_sort_oldest'),
            'uses_desc'  => __('messages.promotion_sort_uses'),
            'value_desc' => __('messages.promotion_sort_value'),
            'name_asc'   => __('messages.promotion_sort_name'),
        ]"
        :filters="[
            'type' => [
                'label'   => __('messages.promotion_type'),
                'options' => [
                    'percent_off' => __('messages.promotion_type_percent_off'),
                    'flat_off'    => __('messages.promotion_type_flat_off'),
                    'bogo'        => __('messages.promotion_type_bogo'),
                ]
            ],
            'status' => [
                'label'   => __('messages.status'),
                'options' => [
                    'active'    => __('messages.promotion_status_active'),
                    'inactive'  => __('messages.promotion_status_inactive'),
                    'expired'   => __('messages.promotion_status_expired'),
                    'scheduled' => __('messages.promotion_status_scheduled'),
                ]
            ],
        ]"
        :showViewToggle="true"
        :showExportImport="false"
        :paginator="$promotions"
        :perPageOptions="[20 => '20', 50 => '50', 100 => '100', 'all' => __('messages.all')]"
    />

    {{-- ============================================================
         TABLE VIEW — spreadsheet grid with sticky header
         ============================================================ --}}
    <div id="promotions-table" x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        <th class="py-2.5 px-3 min-w-[200px]">{{ __('messages.promotion_name') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[120px]">{{ __('messages.promotion_type') }} / {{ __('messages.promotion_value') }}</th>
                        <th class="py-2.5 px-3 min-w-[140px]">{{ __('messages.promotion_code') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[110px]">{{ __('messages.promotion_total_uses') }}</th>
                        <th class="py-2.5 px-3 min-w-[160px]">{{ __('messages.promotion_validity') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[120px]">{{ __('messages.status') }}</th>
                        <th class="py-2.5 pl-3 pr-4 text-right w-24">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/90 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse($promotions as $promo)
                        @php
                            $status = $promo->statusLabel();
                            $statusCss = match($status) {
                                'active'    => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
                                'scheduled' => 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300',
                                'expired'   => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                                'exhausted' => 'bg-orange-100 text-orange-800 dark:bg-orange-950/60 dark:text-orange-300',
                                default     => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                            };
                            $typeCss = match($promo->type) {
                                'percent_off' => 'bg-violet-100 text-violet-800 dark:bg-violet-950/60 dark:text-violet-300',
                                'flat_off'    => 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300',
                                'bogo'        => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                                default       => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                            };
                        @endphp
                        <tr class="hover:bg-violet-50/50 dark:hover:bg-violet-950/20 divide-x divide-slate-200/80 dark:divide-slate-800 transition-colors group">
                            {{-- Name & Scope --}}
                            <td class="py-2.5 px-3">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                    {{ $promo->name }}
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    @if($promo->is_public)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-300 border border-violet-200/60 dark:border-violet-800">
                                            ★ {{ __('messages.promotion_auto_apply') }}
                                        </span>
                                    @endif
                                    @if($promo->category)
                                        <span class="truncate">📁 {{ $promo->category->name }}</span>
                                    @elseif($promo->product)
                                        <span class="truncate">📦 {{ $promo->product->name }}</span>
                                    @else
                                        <span>🌐 {{ __('messages.promotion_all_products') }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Type / Value --}}
                            <td class="py-2.5 px-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-black font-mono {{ $typeCss }}">
                                    @if($promo->type === 'percent_off')
                                        {{ $promo->value }}% Off
                                    @elseif($promo->type === 'flat_off')
                                        {{ format_currency($promo->value, $store) }} Off
                                    @else
                                        BOGO (1+1)
                                    @endif
                                </span>
                                @if($promo->min_order_amount > 0)
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                        Min: {{ format_currency($promo->min_order_amount, $store) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Coupon Code --}}
                            <td class="py-2.5 px-3">
                                @if($promo->code)
                                    <div class="flex items-center gap-1.5">
                                        <code class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-black font-mono text-slate-900 dark:text-slate-100 select-all">
                                            {{ $promo->code }}
                                        </code>
                                        <button type="button" @click="copyCode('{{ $promo->code }}')"
                                                class="p-1 text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 transition"
                                                title="{{ __('messages.promotion_copy_code') }}">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-slate-400 text-xs font-mono">—</span>
                                @endif
                            </td>

                            {{-- Uses --}}
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">
                                <span class="text-violet-600 dark:text-violet-400">{{ number_format($promo->used_count) }}</span>
                                @if($promo->total_uses_limit)
                                    <span class="text-slate-400 font-normal"> / {{ number_format($promo->total_uses_limit) }}</span>
                                @else
                                    <span class="text-[10px] text-slate-400 font-normal block font-sans">({{ __('messages.promotion_unlimited') }})</span>
                                @endif
                            </td>

                            {{-- Validity --}}
                            <td class="py-2.5 px-3 text-[11px] font-mono text-slate-600 dark:text-slate-400">
                                @if($promo->starts_at)
                                    <div>{{ $promo->starts_at->format('d M Y') }}</div>
                                @endif
                                @if($promo->expires_at)
                                    <div class="{{ $promo->isExpired() ? 'text-rose-500 font-bold' : '' }}">
                                        → {{ $promo->expires_at->format('d M Y') }}
                                    </div>
                                @else
                                    <div class="text-slate-400 font-sans text-[10px]">{{ __('messages.promotion_unlimited') }}</div>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="py-2.5 px-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statusCss }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="py-2.5 pl-3 pr-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- Edit --}}
                                    <button type="button"
                                            @click="openEdit(
                                                {{ $promo->id }},
                                                '{{ addslashes($promo->name) }}',
                                                '{{ $promo->code ?? '' }}',
                                                '{{ $promo->type }}',
                                                {{ $promo->value }},
                                                {{ $promo->min_order_amount }},
                                                '{{ $promo->category_id ?? '' }}',
                                                '{{ $promo->product_id ?? '' }}',
                                                '{{ $promo->total_uses_limit ?? '' }}',
                                                '{{ $promo->per_customer_limit ?? '' }}',
                                                '{{ $promo->starts_at?->format('Y-m-d') ?? '' }}',
                                                '{{ $promo->expires_at?->format('Y-m-d') ?? '' }}',
                                                {{ $promo->is_active ? 'true' : 'false' }},
                                                {{ $promo->is_public ? 'true' : 'false' }}
                                            )"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                            title="{{ __('messages.edit') }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>

                                    {{-- Toggle Active --}}
                                    <form method="POST" action="{{ route('store.admin.promotions.toggle', ['store_slug' => $store->slug, 'promotion' => $promo->id]) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="p-1.5 rounded-lg {{ $promo->is_active ? 'text-amber-500 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-950/40' : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40' }} transition"
                                                title="{{ $promo->is_active ? __('messages.promotion_status_inactive') : __('messages.promotion_status_active') }}">
                                            @if($promo->is_active)
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @endif
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('store.admin.promotions.destroy', ['store_slug' => $store->slug, 'promotion' => $promo->id]) }}"
                                          onsubmit="return confirm('{{ __('messages.promotion_confirm_delete') }}')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                                title="{{ __('messages.delete') }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="max-w-sm mx-auto space-y-2.5">
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    </div>
                                    <p class="font-bold text-xs text-slate-700 dark:text-slate-300">{{ __('messages.promotion_empty') }}</p>
                                    <p class="text-[11px] text-slate-400">{{ __('messages.promotion_empty_desc') }}</p>
                                    <a href="{{ $clearFiltersUrl }}" class="inline-block text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                                        {{ __('messages.clear_all') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($promotions->hasPages())
            <div class="px-2.5 py-2 border-t border-slate-200/80 dark:border-slate-800">
                {{ $promotions->links() }}
            </div>
        @endif
    </div>

    {{-- ============================================================
         CARD VIEW — responsive grid with coupon cards
         ============================================================ --}}
    <div id="promotions-cards" x-show="viewMode === 'card'" class="w-full">
        @if($promotions->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0.5 sm:gap-1">
                @foreach($promotions as $promo)
                    @php
                        $status = $promo->statusLabel();
                        $statusCss = match($status) {
                            'active'    => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
                            'scheduled' => 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300',
                            'expired'   => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                            'exhausted' => 'bg-orange-100 text-orange-800 dark:bg-orange-950/60 dark:text-orange-300',
                            default     => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                        };
                        $typeCss = match($promo->type) {
                            'percent_off' => 'bg-violet-100 text-violet-800 dark:bg-violet-950/60 dark:text-violet-300',
                            'flat_off'    => 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300',
                            'bogo'        => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                            default       => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                        };
                    @endphp
                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 shadow-2xs hover:shadow-sm transition flex flex-col justify-between space-y-2.5">
                        <div class="space-y-2">
                            {{-- Top row: status pill + actions --}}
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statusCss }}">
                                    {{ ucfirst($status) }}
                                </span>
                                <div class="flex items-center gap-1">
                                    <button type="button"
                                            @click="openEdit(
                                                {{ $promo->id }},
                                                '{{ addslashes($promo->name) }}',
                                                '{{ $promo->code ?? '' }}',
                                                '{{ $promo->type }}',
                                                {{ $promo->value }},
                                                {{ $promo->min_order_amount }},
                                                '{{ $promo->category_id ?? '' }}',
                                                '{{ $promo->product_id ?? '' }}',
                                                '{{ $promo->total_uses_limit ?? '' }}',
                                                '{{ $promo->per_customer_limit ?? '' }}',
                                                '{{ $promo->starts_at?->format('Y-m-d') ?? '' }}',
                                                '{{ $promo->expires_at?->format('Y-m-d') ?? '' }}',
                                                {{ $promo->is_active ? 'true' : 'false' }},
                                                {{ $promo->is_public ? 'true' : 'false' }}
                                            )"
                                            class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition" title="{{ __('messages.edit') }}">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    <form method="POST" action="{{ route('store.admin.promotions.destroy', ['store_slug' => $store->slug, 'promotion' => $promo->id]) }}"
                                          onsubmit="return confirm('{{ __('messages.promotion_confirm_delete') }}')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 transition" title="{{ __('messages.delete') }}">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Promo Name & Scope --}}
                            <div>
                                <h3 class="font-bold text-xs text-slate-900 dark:text-slate-100 truncate" title="{{ $promo->name }}">{{ $promo->name }}</h3>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">
                                    @if($promo->category)
                                        📁 {{ $promo->category->name }}
                                    @elseif($promo->product)
                                        📦 {{ $promo->product->name }}
                                    @else
                                        🌐 {{ __('messages.promotion_all_products') }}
                                    @endif
                                </p>
                            </div>

                            {{-- Value Ticket Display --}}
                            <div class="p-2.5 rounded-lg bg-violet-50/70 dark:bg-violet-950/40 border border-violet-200/60 dark:border-violet-800/60 flex items-center justify-between">
                                <div>
                                    <div class="text-[10px] uppercase font-bold text-violet-600 dark:text-violet-400">{{ __('messages.promotion_value') }}</div>
                                    <div class="text-sm font-black text-violet-900 dark:text-violet-100 font-mono">
                                        @if($promo->type === 'percent_off')
                                            {{ $promo->value }}% Off
                                        @elseif($promo->type === 'flat_off')
                                            {{ format_currency($promo->value, $store) }} Off
                                        @else
                                            BOGO (1+1)
                                        @endif
                                    </div>
                                </div>
                                @if($promo->min_order_amount > 0)
                                    <div class="text-right">
                                        <div class="text-[9px] uppercase font-bold text-slate-400">{{ __('messages.promotion_min_order') }}</div>
                                        <div class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">
                                            {{ format_currency($promo->min_order_amount, $store) }}
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Coupon code container --}}
                            @if($promo->code)
                                <div class="flex items-center justify-between p-1.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 text-xs">
                                    <code class="font-mono font-black text-slate-900 dark:text-slate-100 tracking-wider select-all">{{ $promo->code }}</code>
                                    <button type="button" @click="copyCode('{{ $promo->code }}')"
                                            class="text-[10px] font-bold text-violet-600 dark:text-violet-400 hover:underline">
                                        {{ __('messages.promotion_copy_code') }}
                                    </button>
                                </div>
                            @endif
                        </div>

                        {{-- Card Bottom Stats & Toggle --}}
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px]">
                            <div class="font-mono text-slate-500 dark:text-slate-400">
                                <span class="font-bold text-slate-900 dark:text-slate-100">{{ number_format($promo->used_count) }}</span> uses
                                @if($promo->total_uses_limit)
                                    / {{ number_format($promo->total_uses_limit) }}
                                @endif
                            </div>
                            <form method="POST" action="{{ route('store.admin.promotions.toggle', ['store_slug' => $store->slug, 'promotion' => $promo->id]) }}">
                                @csrf
                                <button type="submit" class="text-xs font-bold {{ $promo->is_active ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }} hover:underline">
                                    {{ $promo->is_active ? __('messages.promotion_status_inactive') : __('messages.promotion_status_active') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg p-12 text-center shadow-2xs">
                <div class="max-w-sm mx-auto space-y-2.5">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    </div>
                    <p class="font-bold text-xs text-slate-700 dark:text-slate-300">{{ __('messages.promotion_empty') }}</p>
                    <a href="{{ $clearFiltersUrl }}" class="inline-block text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                        {{ __('messages.clear_all') }}
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- ============================================================
         MODAL 1: CREATE PROMOTION
         ============================================================ --}}
    <div x-show="showCreateModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="showCreateModal = false"
             class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full p-4 sm:p-5 space-y-3.5 my-6">
            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300 grid place-items-center text-xs">✨</span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('messages.promotion_add') }}</h3>
                        <p class="text-[11px] text-slate-400">{{ __('messages.promotion_subtitle') }}</p>
                    </div>
                </div>
                <button type="button" @click="showCreateModal = false" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('store.admin.promotions.store', $storeRouteParams) }}" class="space-y-3 text-xs">
                @csrf

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_name') }} *</label>
                    <input type="text" name="name" required placeholder="e.g. Thingyan Festival 2026 Special"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_type') }} *</label>
                        <select name="type" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                            <option value="percent_off">{{ __('messages.promotion_type_percent_off') }}</option>
                            <option value="flat_off">{{ __('messages.promotion_type_flat_off') }}</option>
                            <option value="bogo">{{ __('messages.promotion_type_bogo') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_value') }} *</label>
                        <input type="number" name="value" step="0.01" min="0" required placeholder="e.g. 10"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_code') }}</label>
                        <input type="text" name="code" placeholder="e.g. SALE10"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 uppercase focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_min_order') }}</label>
                        <input type="number" name="min_order_amount" step="1000" min="0" value="0"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.categories') }}</label>
                        <select name="category_id" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                            <option value="">{{ __('messages.promotion_all_products') }}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.products') }}</label>
                        <select name="product_id" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                            <option value="">{{ __('messages.promotion_all_products') }}</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_starts_at') }}</label>
                        <input type="date" name="starts_at" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_expires_at') }}</label>
                        <input type="date" name="expires_at" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_uses_limit') }}</label>
                        <input type="number" name="total_uses_limit" min="1" placeholder="Unlimited"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_per_customer_limit') }}</label>
                        <input type="number" name="per_customer_limit" min="1" placeholder="Unlimited"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500">
                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.promotion_status_active') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_public" value="1" class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500">
                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.promotion_is_public') }}</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showCreateModal = false" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition active:scale-95">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition active:scale-95">
                        {{ __('messages.promotion_add') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL 2: EDIT PROMOTION
         ============================================================ --}}
    <div x-show="showEditModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="showEditModal = false"
             class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full p-4 sm:p-5 space-y-3.5 my-6">
            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300 grid place-items-center text-xs shrink-0">✏️</span>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 truncate">
                            {{ __('messages.promotion_edit') }}: <span x-text="editPromo.name" class="text-violet-600"></span>
                        </h3>
                    </div>
                </div>
                <button type="button" @click="showEditModal = false" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST"
                  :action="'{{ url('/store/' . $store->slug . '/admin/promotions') }}/' + editPromo.id"
                  class="space-y-3 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_name') }} *</label>
                    <input type="text" name="name" x-model="editPromo.name" required
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_type') }} *</label>
                        <select name="type" x-model="editPromo.type" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                            <option value="percent_off">{{ __('messages.promotion_type_percent_off') }}</option>
                            <option value="flat_off">{{ __('messages.promotion_type_flat_off') }}</option>
                            <option value="bogo">{{ __('messages.promotion_type_bogo') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_value') }} *</label>
                        <input type="number" name="value" step="0.01" min="0" x-model="editPromo.value" required
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_code') }}</label>
                        <input type="text" name="code" x-model="editPromo.code"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 uppercase focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_min_order') }}</label>
                        <input type="number" name="min_order_amount" step="1000" min="0" x-model="editPromo.min_order_amount"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.categories') }}</label>
                        <select name="category_id" x-model="editPromo.category_id" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                            <option value="">{{ __('messages.promotion_all_products') }}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.products') }}</label>
                        <select name="product_id" x-model="editPromo.product_id" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                            <option value="">{{ __('messages.promotion_all_products') }}</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_starts_at') }}</label>
                        <input type="date" name="starts_at" x-model="editPromo.starts_at" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_expires_at') }}</label>
                        <input type="date" name="expires_at" x-model="editPromo.expires_at" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_uses_limit') }}</label>
                        <input type="number" name="total_uses_limit" min="1" x-model="editPromo.total_uses_limit" placeholder="Unlimited"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_per_customer_limit') }}</label>
                        <input type="number" name="per_customer_limit" min="1" x-model="editPromo.per_customer_limit" placeholder="Unlimited"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" :checked="editPromo.is_active == 1" class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500">
                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.promotion_status_active') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_public" value="1" :checked="editPromo.is_public == 1" class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500">
                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.promotion_is_public') }}</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showEditModal = false" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition active:scale-95">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition active:scale-95">
                        {{ __('messages.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL 3: TEST COUPON VALIDATOR
         ============================================================ --}}
    <div x-show="showValidateModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="showValidateModal = false"
             class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-4 sm:p-5 space-y-3.5">
            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300 grid place-items-center text-xs">🎟</span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('messages.promotion_test_coupon') }}</h3>
                        <p class="text-[11px] text-slate-400">{{ __('messages.promotion_test_coupon_desc') }}</p>
                    </div>
                </div>
                <button type="button" @click="showValidateModal = false" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_code') }}</label>
                    <input type="text" x-model="validateCode" @keydown.enter="checkCoupon()" placeholder="e.g. SALE10"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 uppercase focus:ring-2 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.total') }}</label>
                    <input type="number" x-model="validateTotal" step="1000" min="0"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                </div>
                <button type="button" @click="checkCoupon()" :disabled="validating || !validateCode"
                        class="w-full px-4 py-2 rounded-lg bg-violet-600 text-white font-bold shadow-2xs hover:bg-violet-500 transition flex items-center justify-center gap-2 active:scale-95 disabled:opacity-50">
                    <template x-if="validating">
                        <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    </template>
                    <span>{{ __('messages.promotion_test_coupon') }}</span>
                </button>

                <template x-if="validateResult">
                    <div :class="validateResult.valid ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-200' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-200'"
                         class="p-3 rounded-lg border space-y-1">
                        <p class="font-bold text-xs" x-text="validateResult.message"></p>
                        <template x-if="validateResult.valid">
                            <p class="text-xs font-mono font-black" x-text="'Discount: ' + Number(validateResult.discount).toLocaleString() + ' Ks'"></p>
                        </template>
                    </div>
                </template>
            </div>

            <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 text-right">
                <button type="button" @click="showValidateModal = false" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition active:scale-95">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
