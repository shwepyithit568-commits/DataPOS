@extends('layouts.admin.app')

@section('title', __('messages.orders_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $exportUrl = route('store.admin.orders.export', array_merge($storeRouteParams, request()->only(['search', 'sort', 'tab', 'status', 'pricing_type', 'contact_channel'])));
@endphp

@section('content')
<div class="w-full space-y-0.5 pb-6"
     x-data="{
        viewMode: localStorage.getItem('admin_orders_view_mode') || 'table',
        setView(mode) {
            this.viewMode = mode;
            localStorage.setItem('admin_orders_view_mode', mode);
        },
        deleteConfirmId: null,
        deleteOrderNo: '',
        openDeleteConfirm(id, orderNo) {
            this.deleteConfirmId = id;
            this.deleteOrderNo = orderNo;
        },
        closeDeleteConfirm() {
            this.deleteConfirmId = null;
            this.deleteOrderNo = '';
        },
        submitDelete() {
            if (!this.deleteConfirmId) return;
            document.getElementById('delete-form-' + this.deleteConfirmId)?.submit();
        }
     }">

    {{-- ── 1. Compact Header Banner (34px - 38px) ───────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 grid place-items-center text-base font-bold shadow-xs shrink-0">
                🛒
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.orders_title') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.orders_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0 flex-wrap">
            {{-- Export Dropdown (Excel / CSV) --}}
            <div x-data="{ exportOpen: false }" @click.outside="exportOpen = false" class="relative">
                <button type="button" @click="exportOpen = !exportOpen"
                        class="h-7 px-2.5 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 14l5-5 5 5M12 3v12"/>
                    </svg>
                    <span>{{ __('messages.export_excel') }}</span>
                    <span class="text-[10px] text-slate-400">▾</span>
                </button>
                <div x-show="exportOpen" x-cloak x-transition
                     class="absolute right-0 mt-1 w-36 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-slate-200 dark:border-slate-700 py-1 z-30 text-xs font-bold">
                    <a href="{{ $exportUrl }}&format=xlsx" class="flex items-center gap-2 px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                        <span class="text-emerald-600 dark:text-emerald-400 font-mono font-black">.xlsx</span>
                        <span>Excel</span>
                    </a>
                    <a href="{{ $exportUrl }}&format=csv" class="flex items-center gap-2 px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                        <span class="text-sky-600 dark:text-sky-400 font-mono font-black">.csv</span>
                        <span>CSV</span>
                    </a>
                </div>
            </div>

            {{-- Open POS Counter Button --}}
            <a href="{{ route('pos.index', $storeRouteParams) }}" target="_blank"
               class="h-7 px-3 rounded-md bg-sky-600 hover:bg-sky-500 text-white text-xs font-black shadow-2xs hover:shadow-sky-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span>{{ __('messages.orders_open_pos') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 p-2 sm:p-2.5 flex items-center justify-between text-xs font-bold text-emerald-800 dark:text-emerald-200 shadow-2xs">
            <div class="flex items-center gap-2">
                <span class="text-sm">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300 font-bold px-1.5 py-0.5 cursor-pointer" aria-label="Close">&times;</button>
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 p-2 sm:p-2.5 text-xs font-bold text-rose-800 dark:text-rose-200 shadow-2xs space-y-0.5">
            <div class="flex items-center gap-1.5 font-black">
                <span>⚠️</span>
                <span>{{ __('messages.order_error_heading') }}</span>
            </div>
            <ul class="list-disc pl-5 font-normal">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── 2. Centered Row-Based Stat Cards (Ultra-Dense 2px Rhythm) ──── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-0.5 sm:gap-1" role="list">
        {{-- Card 1: Pending --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'pending'])) }}" role="listitem"
           class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition hover:border-amber-400 dark:hover:border-amber-600 {{ $tab === 'pending' ? 'ring-1 ring-amber-500 bg-amber-50/20 dark:bg-amber-950/20' : '' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold">
                ⏳
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-outfit truncate">
                    {{ number_format($stats['pending']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.orders_pending') }}
                </p>
            </div>
        </a>

        {{-- Card 2: Confirmed --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'confirmed'])) }}" role="listitem"
           class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition hover:border-emerald-400 dark:hover:border-emerald-600 {{ $tab === 'confirmed' ? 'ring-1 ring-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/20' : '' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                ✓
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit truncate">
                    {{ number_format($stats['confirmed']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.orders_confirmed') }}
                </p>
            </div>
        </a>

        {{-- Card 3: Delivered --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'delivered'])) }}" role="listitem"
           class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition hover:border-sky-400 dark:hover:border-sky-600 {{ $tab === 'delivered' ? 'ring-1 ring-sky-500 bg-sky-50/20 dark:bg-sky-950/20' : '' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300 shadow-inner text-xs sm:text-sm font-bold">
                🚚
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-sky-600 dark:text-sky-400 leading-none tabular-nums font-outfit truncate">
                    {{ number_format($stats['delivered']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.orders_delivered') }}
                </p>
            </div>
        </a>

        {{-- Card 4: Cancelled --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'cancelled'])) }}" role="listitem"
           class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition hover:border-rose-400 dark:hover:border-rose-600 {{ $tab === 'cancelled' ? 'ring-1 ring-rose-500 bg-rose-50/20 dark:bg-rose-950/20' : '' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300 shadow-inner text-xs sm:text-sm font-bold">
                ✕
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-outfit truncate">
                    {{ number_format($stats['cancelled']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.orders_cancelled') }}
                </p>
            </div>
        </a>

        {{-- Card 5: Confirmed Revenue --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'all'])) }}" role="listitem"
           class="col-span-2 sm:col-span-1 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition hover:border-violet-400 dark:hover:border-violet-600 {{ $tab === 'all' ? 'ring-1 ring-violet-500/50' : '' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 shadow-inner text-xs sm:text-sm font-bold">
                💰
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-violet-600 dark:text-violet-400 leading-none tabular-nums font-outfit truncate">
                    {{ format_currency($stats['revenue'], $store) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.orders_revenue') }}
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
                    $tabs = [
                        'all'       => ['label' => __('messages.orders_filter_all'), 'count' => $stats['total']],
                        'pending'   => ['label' => __('messages.orders_pending'), 'count' => $stats['pending']],
                        'confirmed' => ['label' => __('messages.orders_confirmed'), 'count' => $stats['confirmed']],
                        'delivered' => ['label' => __('messages.orders_delivered'), 'count' => $stats['delivered']],
                        'cancelled' => ['label' => __('messages.orders_cancelled'), 'count' => $stats['cancelled']],
                    ];
                @endphp
                @foreach ($tabs as $key => $meta)
                    <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, array_merge(request()->except(['tab', 'page']), ['tab' => $key]))) }}"
                       class="h-7 px-2.5 rounded text-xs font-bold transition inline-flex items-center gap-1.5 whitespace-nowrap {{ $tab === $key ? 'bg-sky-600 text-white shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                        <span>{{ $meta['label'] }}</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full font-mono font-black {{ $tab === $key ? 'bg-sky-700/80 text-white' : 'bg-white/80 dark:bg-slate-900 text-slate-600 dark:text-slate-300' }}">
                            {{ number_format($meta['count']) }}
                        </span>
                    </a>
                @endforeach
            </div>

            {{-- Table / Cards View Switcher --}}
            <div class="flex items-center gap-1 self-end lg:self-auto shrink-0">
                <span class="text-[11px] font-bold text-slate-400 font-mono hidden sm:inline mr-1">
                    {{ number_format($totalCount) }} {{ __('messages.reports_items') }}
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

        {{-- Search Input, Filters & Sorters Form --}}
        <form method="GET" action="{{ route('store.admin.orders.index', $storeRouteParams) }}"
              class="flex flex-col sm:flex-row items-stretch sm:items-center gap-1.5 pt-1 border-t border-slate-100 dark:border-slate-800">
            <input type="hidden" name="tab" value="{{ $tab }}">

            {{-- Search input --}}
            <div class="relative flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('messages.orders_search_placeholder') }}"
                       class="w-full h-7 pl-7 pr-3 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-sky-500 transition">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-2 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </div>

            {{-- Filter: Pricing Type --}}
            <select name="pricing_type" onchange="this.form.submit()"
                    class="h-7 px-2 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 outline-none focus:ring-1 focus:ring-sky-500">
                <option value="">{{ __('messages.orders_filter_pricing') }}: {{ __('messages.orders_filter_all') }}</option>
                <option value="retail" {{ request('pricing_type') === 'retail' ? 'selected' : '' }}>{{ __('messages.retail') }}</option>
                <option value="wholesale" {{ request('pricing_type') === 'wholesale' ? 'selected' : '' }}>{{ __('messages.wholesale') }}</option>
            </select>

            {{-- Filter: Channel --}}
            <select name="contact_channel" onchange="this.form.submit()"
                    class="h-7 px-2 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 outline-none focus:ring-1 focus:ring-sky-500">
                <option value="">{{ __('messages.orders_filter_channel') }}: {{ __('messages.orders_filter_all') }}</option>
                <option value="viber" {{ request('contact_channel') === 'viber' ? 'selected' : '' }}>Viber</option>
                <option value="telegram" {{ request('contact_channel') === 'telegram' ? 'selected' : '' }}>Telegram</option>
                <option value="phone" {{ request('contact_channel') === 'phone' ? 'selected' : '' }}>Phone</option>
            </select>

            {{-- Sort By --}}
            <select name="sort" onchange="this.form.submit()"
                    class="h-7 px-2 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 outline-none focus:ring-1 focus:ring-sky-500">
                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>{{ __('messages.sort_newest') }}</option>
                <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>{{ __('messages.sort_oldest') }}</option>
                <option value="amount_high" {{ $sort === 'amount_high' ? 'selected' : '' }}>{{ __('messages.sort_highest') }}</option>
                <option value="amount_low" {{ $sort === 'amount_low' ? 'selected' : '' }}>{{ __('messages.sort_lowest') }}</option>
            </select>

            @if (request()->hasAny(['search', 'pricing_type', 'contact_channel']) && (request('search') || request('pricing_type') || request('contact_channel')))
                <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => $tab])) }}"
                   class="h-7 px-2 rounded bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 hover:bg-rose-100 text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                    <span>✕</span>
                    <span>{{ __('messages.reset') }}</span>
                </a>
            @endif
        </form>
    </div>

    {{-- ── 4. Main Table View (Desktop & Tablet) ────────────────────────── --}}
    <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-10 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b border-slate-200 dark:border-slate-700 select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-200 dark:divide-slate-700">
                        <th class="py-2 px-2.5 min-w-[110px]">{{ __('messages.orders_table_order_no') }}</th>
                        <th class="py-2 px-2.5 min-w-[160px]">{{ __('messages.orders_table_customer') }}</th>
                        <th class="py-2 px-2.5 min-w-[120px] hidden md:table-cell">{{ __('messages.orders_table_channel_type') }}</th>
                        <th class="py-2 px-2.5 text-right min-w-[110px]">{{ __('messages.orders_table_total') }}</th>
                        <th class="py-2 px-2.5 min-w-[120px] hidden sm:table-cell">{{ __('messages.orders_table_date') }}</th>
                        <th class="py-2 px-2.5 text-center min-w-[130px]">{{ __('messages.orders_table_status') }}</th>
                        <th class="py-2 px-2 text-center w-36">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse ($orders as $order)
                        @php
                            $amount = $order->agreed_amount !== null ? (float) $order->agreed_amount : (float) $order->total_amount;
                            $isWholesale = $order->pricing_type === 'wholesale';
                            $channel = strtolower((string) $order->contact_channel);
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 divide-x divide-slate-100 dark:divide-slate-800/60 transition-colors group">
                            {{-- Order Number --}}
                            <td class="py-2 px-2.5 whitespace-nowrap">
                                <a href="{{ route('store.admin.orders.show', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                                   class="font-mono font-black text-sky-600 dark:text-sky-400 hover:underline inline-flex items-center gap-1">
                                    <span>#{{ $order->order_number }}</span>
                                </a>
                                @if ($order->admin_note)
                                    <span class="text-[10px] text-slate-400 block truncate max-w-[140px]" title="{{ $order->admin_note }}">📝 {{ Str::limit($order->admin_note, 18) }}</span>
                                @endif
                            </td>

                            {{-- Customer --}}
                            <td class="py-2 px-2.5">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs leading-tight truncate max-w-[180px]">{{ $order->customer_name }}</div>
                                <div class="font-mono text-[11px] text-slate-500 dark:text-slate-400">📞 {{ $order->customer_phone }}</div>
                                @if ($order->contact_identifier)
                                    <div class="text-[10px] text-violet-600 dark:text-violet-400 font-semibold truncate max-w-[160px]">{{ $order->contact_identifier }}</div>
                                @endif
                            </td>

                            {{-- Channel / Type --}}
                            <td class="py-2 px-2.5 hidden md:table-cell whitespace-nowrap">
                                <div class="flex items-center gap-1 flex-wrap">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700">
                                        @if ($channel === 'viber') Viber
                                        @elseif ($channel === 'telegram') Telegram
                                        @else Phone @endif
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $isWholesale ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800' }}">
                                        {{ $order->pricing_type }}
                                    </span>
                                </div>
                            </td>

                            {{-- Total Amount --}}
                            <td class="py-2 px-2.5 text-right whitespace-nowrap">
                                <div class="font-mono font-black text-slate-900 dark:text-white tabular-nums text-xs sm:text-sm">
                                    {{ format_currency($amount, $store) }}
                                </div>
                                @if ($order->agreed_amount !== null)
                                    <span class="text-[9px] font-bold text-violet-600 dark:text-violet-400 block">{{ __('messages.order_agreed_amount') }}</span>
                                @endif
                            </td>

                            {{-- Date --}}
                            <td class="py-2 px-2.5 text-slate-500 dark:text-slate-400 text-[11px] whitespace-nowrap hidden sm:table-cell tabular-nums font-mono">
                                {{ $order->created_at->format('d M Y, h:i A') }}
                            </td>

                            {{-- Status Badge --}}
                            <td class="py-2 px-2.5 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1 flex-wrap justify-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase
                                        {{ $order->status === 'confirmed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80' : '' }}
                                        {{ $order->status === 'pending_contact' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80' : '' }}
                                        {{ $order->status === 'delivered' ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300 border border-sky-200 dark:border-sky-800/80' : '' }}
                                        {{ $order->status === 'cancelled' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800/80' : '' }}">
                                        @if ($order->status === 'pending_contact')
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        @endif
                                        {{ $order->status === 'pending_contact' ? __('messages.orders_pending') : ($order->status === 'confirmed' ? __('messages.orders_confirmed') : ($order->status === 'delivered' ? __('messages.orders_delivered') : __('messages.orders_cancelled'))) }}
                                    </span>
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase {{ $order->payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700' }}">
                                        {{ $order->payment_status }}
                                    </span>
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="py-2 px-2 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1 justify-center">
                                    {{-- Quick Status Changer --}}
                                    <form method="POST" action="{{ route('store.admin.orders.update_status', array_merge($storeRouteParams, ['order' => $order->id])) }}" class="inline-flex items-center gap-0.5">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="text-[10px] font-bold border rounded px-1 py-0.5 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100 h-6 focus:outline-none focus:ring-1 focus:ring-sky-500">
                                            <option value="pending_contact" {{ $order->status === 'pending_contact' ? 'selected' : '' }}>{{ __('messages.orders_pending') }}</option>
                                            <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>{{ __('messages.orders_confirmed') }}</option>
                                            <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>{{ __('messages.orders_delivered') }}</option>
                                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>{{ __('messages.orders_cancelled') }}</option>
                                        </select>
                                        <button type="submit" title="{{ __('messages.save') }}" aria-label="{{ __('messages.save') }}"
                                                class="w-6 h-6 rounded border border-emerald-200 dark:border-emerald-800 inline-flex items-center justify-center text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition cursor-pointer">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>

                                    {{-- View Details --}}
                                    <a href="{{ route('store.admin.orders.show', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                                       title="{{ __('messages.view_details') }}"
                                       class="w-6 h-6 rounded border border-slate-200 dark:border-slate-700 inline-flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>

                                    {{-- Print Invoice --}}
                                    <a href="{{ route('store.admin.orders.invoice', array_merge($storeRouteParams, ['order' => $order->id])) }}" target="_blank"
                                       title="{{ __('messages.print_invoice') }}"
                                       class="w-6 h-6 rounded border border-sky-200 dark:border-sky-800/80 inline-flex items-center justify-center text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/50 hover:bg-sky-100 dark:hover:bg-sky-900/50 transition cursor-pointer">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>

                                    {{-- Delete Button (Platform Owner or Store Manager) --}}
                                    @if (auth()->user()->isPlatformOwner() || auth()->user()->hasStoreRole($store->id, ['store_manager']))
                                        <button type="button" @click.stop="openDeleteConfirm({{ $order->id }}, '{{ $order->order_number }}')"
                                                title="{{ __('messages.delete') }}"
                                                class="w-6 h-6 rounded border border-rose-200 dark:border-rose-800/80 inline-flex items-center justify-center text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/50 transition cursor-pointer">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>
                                        </button>
                                        <form id="delete-form-{{ $order->id }}" method="POST" action="{{ route('store.admin.orders.destroy', array_merge($storeRouteParams, ['order' => $order->id])) }}" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 sm:p-12 text-center">
                                <div class="mx-auto max-w-sm">
                                    <div class="mx-auto mb-3 w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400 dark:text-slate-500 text-2xl shadow-inner">
                                        🛒
                                    </div>
                                    <p class="font-black text-slate-800 dark:text-slate-200 text-xs sm:text-sm">{{ __('messages.orders_no_records') }}</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">No order requests found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── 5. Responsive Mobile Card View ────────────────────────────────── --}}
    <div x-show="viewMode === 'cards' || viewMode === 'card'" class="w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-0.5 sm:gap-1">
        @forelse ($orders as $order)
            @php
                $amount = $order->agreed_amount !== null ? (float) $order->agreed_amount : (float) $order->total_amount;
                $isWholesale = $order->pricing_type === 'wholesale';
                $channel = strtolower((string) $order->contact_channel);
            @endphp
            <div class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg p-2.5 shadow-2xs transition hover:shadow-xs flex flex-col justify-between space-y-2">
                <div class="space-y-1.5">
                    {{-- Card Header --}}
                    <div class="flex items-start justify-between gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                        <div class="min-w-0">
                            <a href="{{ route('store.admin.orders.show', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                               class="font-mono font-black text-sky-600 dark:text-sky-400 text-xs hover:underline inline-flex items-center gap-1">
                                #{{ $order->order_number }}
                            </a>
                            <span class="text-[10px] text-slate-400 block font-mono mt-0.5">{{ $order->created_at->format('d M Y, h:i A') }}</span>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-black uppercase
                                {{ $order->status === 'confirmed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80' : '' }}
                                {{ $order->status === 'pending_contact' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80' : '' }}
                                {{ $order->status === 'delivered' ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300 border border-sky-200 dark:border-sky-800/80' : '' }}
                                {{ $order->status === 'cancelled' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800/80' : '' }}">
                                @if ($order->status === 'pending_contact')
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                @endif
                                {{ $order->status === 'pending_contact' ? __('messages.orders_pending') : ($order->status === 'confirmed' ? __('messages.orders_confirmed') : ($order->status === 'delivered' ? __('messages.orders_delivered') : __('messages.orders_cancelled'))) }}
                            </span>
                        </div>
                    </div>

                    {{-- Customer Info --}}
                    <div>
                        <div class="font-bold text-xs text-slate-900 dark:text-slate-100 flex items-center gap-1">
                            <span class="truncate">{{ $order->customer_name }}</span>
                            @if ($order->admin_note)
                                <span title="{{ $order->admin_note }}">📝</span>
                            @endif
                        </div>
                        <div class="font-mono text-[11px] text-slate-500 dark:text-slate-400">📞 {{ $order->customer_phone }}</div>
                        @if ($order->contact_identifier)
                            <div class="text-[10px] text-violet-600 dark:text-violet-400 font-semibold truncate">{{ $order->contact_identifier }}</div>
                        @endif
                    </div>

                    {{-- Meta Badges & Amount --}}
                    <div class="grid grid-cols-2 gap-1 text-[11px]">
                        <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400 block text-[9px] font-bold uppercase">{{ __('messages.orders_table_channel_type') }}</span>
                            <div class="flex items-center gap-1 mt-0.5">
                                <span class="px-1 py-0.2 rounded text-[10px] font-bold uppercase bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                    {{ $channel }}
                                </span>
                                <span class="px-1 py-0.2 rounded text-[10px] font-bold uppercase {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' }}">
                                    {{ $order->pricing_type }}
                                </span>
                            </div>
                        </div>
                        <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400 block text-[9px] font-bold uppercase">{{ __('messages.orders_table_total') }}</span>
                            <span class="font-mono font-black text-xs text-slate-900 dark:text-white tabular-nums block mt-0.5">
                                {{ format_currency($amount, $store) }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Card Actions --}}
                <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1">
                    <form method="POST" action="{{ route('store.admin.orders.update_status', array_merge($storeRouteParams, ['order' => $order->id])) }}" class="inline-flex items-center gap-1">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="text-[10px] font-bold border rounded px-1 py-0.5 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100 h-6">
                            <option value="pending_contact" {{ $order->status === 'pending_contact' ? 'selected' : '' }}>{{ __('messages.orders_pending') }}</option>
                            <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>{{ __('messages.orders_confirmed') }}</option>
                            <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>{{ __('messages.orders_delivered') }}</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>{{ __('messages.orders_cancelled') }}</option>
                        </select>
                        <button type="submit" title="{{ __('messages.save') }}"
                                class="w-6 h-6 rounded border border-emerald-200 dark:border-emerald-800 inline-flex items-center justify-center text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 transition cursor-pointer">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </form>

                    <div class="flex items-center gap-1">
                        <a href="{{ route('store.admin.orders.show', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                           class="h-6 px-2 rounded text-[11px] font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 transition flex items-center gap-1">
                            <span>{{ __('messages.view_details') }}</span>
                        </a>
                        <a href="{{ route('store.admin.orders.invoice', array_merge($storeRouteParams, ['order' => $order->id])) }}" target="_blank"
                           title="{{ __('messages.print_invoice') }}"
                           class="w-6 h-6 rounded border border-sky-200 dark:border-sky-800/80 inline-flex items-center justify-center text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/50 hover:bg-sky-100 transition">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        </a>
                        @if (auth()->user()->isPlatformOwner() || auth()->user()->hasStoreRole($store->id, ['store_manager']))
                            <button type="button" @click.stop="openDeleteConfirm({{ $order->id }}, '{{ $order->order_number }}')"
                                    title="{{ __('messages.delete') }}"
                                    class="w-6 h-6 rounded border border-rose-200 dark:border-rose-800/80 inline-flex items-center justify-center text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 transition cursor-pointer">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-8 text-center bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800">
                <div class="mx-auto max-w-sm">
                    <div class="mx-auto mb-2 w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400 text-xl shadow-inner">
                        🛒
                    </div>
                    <p class="font-black text-slate-800 dark:text-slate-200 text-xs">{{ __('messages.orders_no_records') }}</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($orders->hasPages())
        <div class="p-2 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg text-xs">
            {{ $orders->links() }}
        </div>
    @endif

    {{-- ── 6. Alpine.js Delete Confirmation Modal ───────────────────────── --}}
    <div x-show="deleteConfirmId !== null" x-cloak
         @click.self="closeDeleteConfirm()"
         @keydown.escape.window="closeDeleteConfirm()"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-xs"
         role="dialog" aria-modal="true">
        <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 shadow-2xl space-y-3 border border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 grid place-items-center shrink-0 shadow-inner font-black">
                    🗑️
                </div>
                <div class="min-w-0">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white truncate">
                        <span>{{ __('messages.orders_delete_title') }}</span>
                        <span x-show="deleteOrderNo" x-text="' · #' + deleteOrderNo" class="font-mono text-rose-600 dark:text-rose-400"></span>
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                        {{ __('messages.orders_delete_confirm') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="closeDeleteConfirm()"
                        class="h-8 px-3 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition cursor-pointer">
                    {{ __('messages.cancel') }}
                </button>
                <button type="button" @click="submitDelete()"
                        class="h-8 px-3.5 rounded-lg text-xs font-black bg-rose-600 hover:bg-rose-500 text-white shadow-2xs transition active:scale-95 cursor-pointer">
                    {{ __('messages.delete') }}
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
