@extends('layouts.admin.app')

@section('title', $session->session_number . ' - ' . __('messages.sidebar_stock_count') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    $fmtQty = function($v) {
        $val = (float) $v;
        return $val == (int) $val ? number_format($val, 0) : rtrim(rtrim(number_format($val, 3), '0'), '.');
    };
    $exportUrl = route('store.admin.stock_count.export_session', ['store_slug' => $store->slug, 'stock_count' => $session->id]);
    $progressPct = $session->total_items > 0 ? round(($session->counted_items / $session->total_items) * 100) : 0;
@endphp

<div x-data="stockCountSheet()" class="w-full space-y-0.5 pb-6">

    {{-- ============================================================
         1. COMPACT PAGE HEADER (34px - 38px Standard Height)
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <a href="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug]) }}"
               class="h-7 px-2 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-xs font-bold inline-flex items-center gap-1 transition shadow-2xs shrink-0">
                <span>←</span>
                <span class="hidden sm:inline">{{ __('messages.back') }}</span>
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-2 truncate">
                    <h1 class="text-xs sm:text-sm font-black font-mono text-slate-900 dark:text-white truncate">
                        {{ $session->session_number }}
                    </h1>
                    @if($session->isApproved())
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            <span>✓</span>
                            <span>{{ __('messages.stock_count_status_approved') ?? 'Approved' }}</span>
                        </span>
                    @elseif($session->isCancelled())
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                            <span>✕</span>
                            <span>{{ __('messages.stock_count_status_cancelled') ?? 'Cancelled' }}</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                            <span>{{ __('messages.stock_count_status_in_progress') ?? 'In Progress' }}</span>
                        </span>
                    @endif
                </div>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">
                    {{ $session->warehouse?->name ?? $session->branch?->name ?? 'Warehouse' }} · {{ $session->created_at->format('d/m/Y H:i') }} ({{ $session->createdBy?->name ?? 'System' }})
                </p>
            </div>
        </div>

        {{-- Actions Bar --}}
        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0 flex-wrap">
            {{-- Print Sheet --}}
            <a href="{{ route('store.admin.stock_count.print', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
               target="_blank"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5 shadow-2xs">
                <span>🖨️</span>
                <span>{{ __('messages.stock_count_print_sheet') ?? 'Print Sheet' }}</span>
            </a>

            {{-- Excel Export --}}
            <a href="{{ $exportUrl }}"
               title="Export Excel (.xlsx)"
               class="h-7 px-2 rounded-md text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <span>Excel</span>
            </a>

            @if($session->isInProgress())
                {{-- Cancel Session --}}
                <form action="{{ route('store.admin.stock_count.cancel', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                      method="POST"
                      onsubmit="return confirm('{{ __('messages.stock_count_cancel_confirm') }}');"
                      class="inline">
                    @csrf
                    <button type="submit"
                            class="h-7 px-2.5 rounded-md text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800 transition inline-flex items-center gap-1 cursor-pointer">
                        <span>✕</span>
                        <span>{{ __('messages.stock_count_cancel') ?? 'Cancel' }}</span>
                    </button>
                </form>

                {{-- Reconcile & Approve Button --}}
                <form action="{{ route('store.admin.stock_count.approve', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                      method="POST"
                      onsubmit="return confirm('{{ __('messages.stock_count_approve_confirm') }}');"
                      class="inline">
                    @csrf
                    <button type="submit"
                            class="h-7 px-3 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-2xs hover:shadow-emerald-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ __('messages.stock_count_approve') ?? 'Reconcile & Approve' }}</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800/60 dark:text-emerald-300 text-xs flex items-center gap-2 shadow-2xs">
            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/40 dark:border-rose-800/60 dark:text-rose-300 text-xs flex items-center gap-2 shadow-2xs">
            <svg class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ============================================================
         2. CENTERED ROW-BASED SUMMARY STAT CARDS (4-UP METRICS)
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1" role="list" aria-label="Stock Count Session KPIs">
        {{-- Total Products --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 shadow-inner text-xs sm:text-sm font-bold">
                📦
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-mono">
                    {{ number_format($session->total_items) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_count_total_products') ?? 'Total Items' }}
                </p>
            </div>
        </div>

        {{-- Counted Lines & Progress --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-indigo-100 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-300 shadow-inner text-xs sm:text-sm font-bold">
                📊
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-indigo-600 dark:indigo-400 leading-none tabular-nums font-mono">
                    <span x-text="stats.counted_items">{{ $session->counted_items }}</span> / {{ $session->total_items }}
                    <span class="text-[10px] text-slate-400 font-normal">({{ $progressPct }}%)</span>
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_count_items_counted') ?? 'Counted Lines' }}
                </p>
            </div>
        </div>

        {{-- Variance Items --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold">
                ⚠️
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-mono">
                    <span x-text="stats.variance_items">{{ $session->variance_items }}</span>
                </div>
                <p class="text-[9px] sm:text-[10px] text-amber-700 dark:text-amber-300/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_count_variance_items') ?? 'Discrepancy Lines' }}
                </p>
            </div>
        </div>

        {{-- Financial Impact (MMK) --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                💰
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black leading-none tabular-nums font-mono"
                     :class="stats.total_variance_cost < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'">
                    <span x-text="formatMoney(stats.total_variance_cost)">{{ number_format($session->total_variance_cost) }}</span>
                    <span class="text-[10px] text-slate-400 font-normal">Ks</span>
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_count_variance_cost') ?? 'Net Variance' }}
                </p>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. LIVE BARCODE/SKU SCANNER & FILTER TOOLBAR
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-1">
        
        {{-- Scanner Input Bar (Active In-Progress Mode) --}}
        @if($session->isInProgress())
            <div class="relative">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5 text-violet-600 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-16v16M4 4v16m4-16v16m8-16v16" />
                        </svg>
                    </div>
                    <input type="text"
                           x-ref="scanInput"
                           x-model="scanQuery"
                           @input.debounce.250ms="doLiveScan()"
                           @keydown.enter.prevent="handleScanEnter()"
                           placeholder="{{ __('messages.stock_count_scan_placeholder') ?? 'Scan barcode or search SKU/product...' }}"
                           class="w-full h-8 pl-8 pr-20 text-xs rounded-md border border-violet-300 dark:border-violet-700 bg-violet-50/20 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-violet-600 focus:ring-1 focus:ring-violet-500 dark:bg-slate-800 dark:text-slate-100 font-mono transition shadow-2xs">
                    
                    <div class="absolute inset-y-0 right-0 pr-1.5 flex items-center gap-1">
                        <span class="px-1.5 py-0.5 text-[9px] font-black rounded bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300 font-mono uppercase">
                            ⚡ SCANNER
                        </span>
                    </div>
                </div>

                {{-- Live Search / Scan Dropdown Suggestions --}}
                <div x-show="scanResults.length > 0"
                     @click.away="scanResults = []"
                     x-transition
                     class="absolute z-20 left-0 right-0 mt-1 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 shadow-lg overflow-hidden divide-y divide-slate-100 dark:divide-slate-700 max-h-60 overflow-y-auto">
                    <template x-for="item in scanResults" :key="item.line_id">
                        <div @click="selectScannedProduct(item)"
                             class="p-2 hover:bg-violet-50 dark:hover:bg-slate-700/60 cursor-pointer flex items-center justify-between transition">
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-slate-900 dark:text-slate-100 truncate" x-text="item.product_name"></div>
                                <div class="text-[10px] text-slate-400 font-mono">
                                    SKU: <span x-text="item.sku"></span> | Barcode: <span x-text="item.barcode"></span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                    Sys: <span x-text="formatQty(item.system_quantity)"></span>
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        @endif

        {{-- Filter Tabs & Search Bar --}}
        <div class="flex flex-col md:flex-row items-center justify-between gap-1 pt-0.5">
            {{-- Filter Tabs --}}
            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700 text-xs w-full md:w-auto overflow-x-auto">
                <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id, 'tab' => 'all', 'search' => $search, 'category_id' => $categoryId]) }}"
                   class="px-2 py-0.5 rounded text-[11px] font-bold transition whitespace-nowrap {{ $tab === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                    {{ __('messages.all') }} ({{ $session->total_items }})
                </a>
                <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id, 'tab' => 'counted', 'search' => $search, 'category_id' => $categoryId]) }}"
                   class="px-2 py-0.5 rounded text-[11px] font-bold transition whitespace-nowrap {{ $tab === 'counted' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                    {{ __('messages.stock_count_items_counted') }} ({{ $session->counted_items }})
                </a>
                <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id, 'tab' => 'variance', 'search' => $search, 'category_id' => $categoryId]) }}"
                   class="px-2 py-0.5 rounded text-[11px] font-bold transition whitespace-nowrap {{ $tab === 'variance' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                    {{ __('messages.stock_count_has_variance') }} ({{ $session->variance_items }})
                </a>
                <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id, 'tab' => 'uncounted', 'search' => $search, 'category_id' => $categoryId]) }}"
                   class="px-2 py-0.5 rounded text-[11px] font-bold transition whitespace-nowrap {{ $tab === 'uncounted' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                    {{ __('messages.stock_count_not_counted') }} ({{ max(0, $session->total_items - $session->counted_items) }})
                </a>
            </div>

            {{-- Category Filter & Search Form --}}
            <form method="GET" action="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}" class="flex items-center gap-1 w-full md:w-auto">
                <input type="hidden" name="tab" value="{{ $tab }}">
                
                @if($sessionCategories->count() > 1)
                    <select name="category_id" onchange="this.form.submit()" class="h-7 px-2 text-[11px] font-bold rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 text-slate-800 dark:bg-slate-800 dark:text-slate-200 shadow-2xs">
                        <option value="">{{ __('messages.all_categories') ?? 'All Categories' }}</option>
                        @foreach($sessionCategories as $cat)
                            <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                @endif

                <div class="relative flex-1 md:w-48">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="{{ __('messages.search') }}..."
                           class="w-full h-7 pl-7 pr-2 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-violet-500">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <button type="submit" class="h-7 px-2.5 text-xs font-bold rounded-md bg-slate-800 text-white hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 transition">
                    {{ __('messages.filter') }}
                </button>
            </form>
        </div>
    </div>

    {{-- ============================================================
         4. STOCK TAKE SHEET SPREADSHEET TABLE
         ============================================================ --}}
    <form action="{{ route('store.admin.stock_count.bulk_update', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}" method="POST">
        @csrf
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div class="rounded-lg border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
            <div class="px-3 py-1.5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/50">
                <h2 class="font-black text-slate-900 dark:text-slate-100 text-xs flex items-center gap-1.5">
                    <span>📋 {{ __('messages.stock_count_take_sheet') ?? 'Physical Count Sheet' }}</span>
                    <span class="text-[10px] font-mono text-slate-400">({{ $lines->total() }} {{ __('messages.products') }})</span>
                </h2>
                @if($session->isInProgress())
                    <button type="submit" class="h-6 px-2.5 rounded text-[11px] font-bold bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        <span>{{ __('messages.save') }}</span>
                    </button>
                @endif
            </div>

            <div class="overflow-x-auto max-h-[68vh] overflow-y-auto">
                <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800 border-b border-slate-300 dark:border-slate-700 shadow-xs select-none backdrop-blur-xs">
                        <tr class="text-[11px] font-black uppercase tracking-wider divide-x divide-slate-200 dark:divide-slate-700 text-slate-800 dark:text-slate-100">
                            <th class="py-2 px-3 min-w-[180px]">{{ __('messages.product') }}</th>
                            <th class="py-2 px-2.5 min-w-[110px]">{{ __('messages.category') }}</th>
                            <th class="py-2 px-2.5 text-right min-w-[100px] bg-slate-200/50 dark:bg-slate-700/50 font-black">{{ __('messages.stock_count_system_qty') ?? 'Expected Qty' }}</th>
                            <th class="py-2 px-2.5 text-center min-w-[130px] font-black text-violet-700 dark:text-violet-300">{{ __('messages.stock_count_counted_qty') ?? 'Physical Count' }}</th>
                            <th class="py-2 px-2.5 text-right min-w-[100px]">{{ __('messages.stock_count_variance') ?? 'Variance' }}</th>
                            <th class="py-2 px-2.5 text-right min-w-[110px]">{{ __('messages.stock_count_variance_cost') ?? 'Cost Impact' }}</th>
                            <th class="py-2 px-2.5 min-w-[140px]">{{ __('messages.notes') }}</th>
                            <th class="py-2 px-2 text-center min-w-[70px]">{{ __('messages.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @forelse($lines as $index => $line)
                            <tr id="line-row-{{ $line->id }}"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 divide-x divide-slate-200/80 dark:divide-slate-800 transition {{ $line->is_counted ? ($line->variance_quantity != 0 ? 'bg-amber-50/30 dark:bg-amber-950/20' : '') : '' }}">
                                
                                <input type="hidden" name="lines[{{ $index }}][id]" value="{{ $line->id }}">
                                
                                {{-- Product Name & Barcode/SKU --}}
                                <td class="py-1.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">
                                        {{ $line->product?->name ?? 'Unknown Product' }}
                                    </div>
                                    <div class="text-[10px] font-mono text-slate-400 mt-0.5 flex items-center gap-1.5">
                                        @if($line->product?->barcode)
                                            <span class="bg-slate-100 dark:bg-slate-800 px-1 py-0.2 rounded">{{ $line->product->barcode }}</span>
                                        @endif
                                        @if($line->product?->sku)
                                            <span class="text-slate-400">SKU: {{ $line->product->sku }}</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Category --}}
                                <td class="py-1.5 px-2.5 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ $line->category?->name ?? '-' }}
                                </td>

                                {{-- System Quantity --}}
                                <td class="py-1.5 px-2.5 text-right font-mono font-bold text-slate-800 dark:text-slate-200 bg-slate-50/50 dark:bg-slate-800/30 tabular-nums">
                                    {{ $fmtQty($line->system_quantity) }}
                                </td>

                                {{-- Counted Quantity Input --}}
                                <td class="py-1 px-2 text-center">
                                    @if($session->isInProgress())
                                        <div class="relative inline-flex items-center gap-1">
                                            <input type="number"
                                                   step="any"
                                                   min="0"
                                                   id="counted-input-{{ $line->id }}"
                                                   name="lines[{{ $index }}][counted_quantity]"
                                                   value="{{ $line->counted_quantity !== null ? (float) $line->counted_quantity : '' }}"
                                                   @change="saveLineCount({{ $line->id }}, $el.value, {{ (float) $line->system_quantity }})"
                                                   placeholder="0"
                                                   class="w-20 h-6 px-1.5 text-center font-mono font-black text-xs rounded border border-slate-300 bg-white text-slate-900 focus:outline-none focus:ring-1 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 shadow-2xs transition">
                                        </div>
                                    @else
                                        <span class="font-mono font-black text-xs {{ $line->counted_quantity !== null ? 'text-slate-900 dark:text-slate-100' : 'text-slate-400' }}">
                                            {{ $line->counted_quantity !== null ? $fmtQty($line->counted_quantity) : '-' }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Variance Quantity Badge --}}
                                <td class="py-1.5 px-2.5 text-right font-mono font-bold tabular-nums">
                                    <div id="variance-cell-{{ $line->id }}">
                                        @if($line->is_counted)
                                            @if($line->variance_quantity > 0)
                                                <span class="inline-flex items-center px-1.5 py-0.2 text-[11px] font-black rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                    +{{ $fmtQty($line->variance_quantity) }}
                                                </span>
                                            @elseif($line->variance_quantity < 0)
                                                <span class="inline-flex items-center px-1.5 py-0.2 text-[11px] font-black rounded bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                                                    {{ $fmtQty($line->variance_quantity) }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.2 text-[11px] font-medium rounded bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                                    0
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-xs text-slate-400">-</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Variance Cost Impact (MMK) --}}
                                <td class="py-1.5 px-2.5 text-right font-mono font-bold text-[11px] tabular-nums whitespace-nowrap {{ $line->variance_cost < 0 ? 'text-rose-600 dark:text-rose-400' : ($line->variance_cost > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400') }}">
                                    {{ $line->is_counted && $line->variance_cost != 0 ? ($line->variance_cost > 0 ? '+' : '') . number_format($line->variance_cost) . ' Ks' : '-' }}
                                </td>

                                {{-- Notes Input --}}
                                <td class="py-1 px-2">
                                    @if($session->isInProgress())
                                        <input type="text"
                                               name="lines[{{ $index }}][notes]"
                                               value="{{ $line->notes }}"
                                               placeholder="Notes..."
                                               class="w-full h-6 px-1.5 text-[11px] rounded border border-transparent hover:border-slate-200 focus:border-violet-400 bg-transparent focus:bg-white dark:focus:bg-slate-800 text-slate-800 dark:text-slate-200">
                                    @else
                                        <span class="text-[11px] text-slate-500">{{ $line->notes ?? '-' }}</span>
                                    @endif
                                </td>

                                {{-- Counted Status Icon --}}
                                <td class="py-1.5 px-2 text-center" id="status-cell-{{ $line->id }}">
                                    @if($line->is_counted)
                                        <span class="inline-flex items-center text-emerald-600 dark:text-emerald-400" title="{{ __('messages.stock_count_items_counted') }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    @else
                                        <span class="inline-block w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600" title="{{ __('messages.stock_count_not_counted') }}"></span>
                                    @endif
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 px-4 text-center text-slate-400 text-xs">
                                    {{ __('messages.no_results_found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($lines->hasPages())
                <div class="p-1.5 border-t border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900">
                    {{ $lines->links() }}
                </div>
            @endif
        </div>
    </form>

</div>

@push('scripts')
<script>
function stockCountSheet() {
    return {
        scanQuery: '',
        scanResults: [],
        stats: {
            counted_items: {{ $session->counted_items }},
            variance_items: {{ $session->variance_items }},
            total_variance_qty: {{ (float) $session->total_variance_qty }},
            total_variance_cost: {{ (float) $session->total_variance_cost }},
        },
        totalItems: {{ $session->total_items }},

        doLiveScan() {
            if (this.scanQuery.trim().length === 0) {
                this.scanResults = [];
                return;
            }
            fetch(`{{ route('store.admin.stock_count.quick_scan', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}?q=` + encodeURIComponent(this.scanQuery))
                .then(r => r.json())
                .then(data => {
                    this.scanResults = data;
                });
        },

        handleScanEnter() {
            if (this.scanResults.length > 0) {
                this.selectScannedProduct(this.scanResults[0]);
            }
        },

        selectScannedProduct(item) {
            const input = document.getElementById('counted-input-' + item.line_id);
            const row = document.getElementById('line-row-' + item.line_id);

            if (input) {
                let currentVal = parseFloat(input.value) || 0;
                input.value = currentVal + 1;
                input.focus();
                input.select();
                this.saveLineCount(item.line_id, input.value, item.system_quantity);
                if (row) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.classList.add('bg-violet-100', 'dark:bg-violet-900/40');
                    setTimeout(() => row.classList.remove('bg-violet-100', 'dark:bg-violet-900/40'), 1500);
                }
            }
            this.scanQuery = '';
            this.scanResults = [];
            this.$refs.scanInput?.focus();
        },

        saveLineCount(lineId, countedVal, systemQty) {
            if (countedVal === '' || countedVal === null) return;
            const parsedCount = parseFloat(countedVal);
            if (isNaN(parsedCount)) return;

            const url = `{{ route('store.admin.stock_count.update_line', ['store_slug' => $store->slug, 'stock_count' => $session->id, 'line' => ':lineId']) }}`.replace(':lineId', lineId);

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ counted_quantity: parsedCount })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    this.stats = res.session;

                    const varianceCell = document.getElementById('variance-cell-' + lineId);
                    const statusCell = document.getElementById('status-cell-' + lineId);

                    if (varianceCell) {
                        const v = res.line.variance_quantity;
                        const vFmt = this.formatQty(v);
                        if (v > 0) {
                            varianceCell.innerHTML = `<span class="inline-flex items-center px-1.5 py-0.2 text-[11px] font-black rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">+${vFmt}</span>`;
                        } else if (v < 0) {
                            varianceCell.innerHTML = `<span class="inline-flex items-center px-1.5 py-0.2 text-[11px] font-black rounded bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">${vFmt}</span>`;
                        } else {
                            varianceCell.innerHTML = `<span class="inline-flex items-center px-1.5 py-0.2 text-[11px] font-medium rounded bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">0</span>`;
                        }
                    }

                    if (statusCell) {
                        statusCell.innerHTML = `<span class="inline-flex items-center text-emerald-600 dark:text-emerald-400"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg></span>`;
                    }
                }
            })
            .catch(err => console.error(err));
        },

        formatQty(val) {
            const num = parseFloat(val) || 0;
            return Number.isInteger(num) ? num.toString() : num.toFixed(3).replace(/\.?0+$/, '');
        },

        formatMoney(val) {
            return (parseFloat(val) || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }
    };
}
</script>
@endpush
@endsection
