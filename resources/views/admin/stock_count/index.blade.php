@extends('layouts.admin.app')

@section('title', __('messages.sidebar_stock_count') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    $fmtQty = function($v) {
        $val = (float) $v;
        return $val == (int) $val ? number_format($val, 0) : rtrim(rtrim(number_format($val, 3), '0'), '.');
    };
    $exportUrl = route('store.admin.stock_count.export', array_merge(['store_slug' => $store->slug], request()->query()));
@endphp

<div class="w-full space-y-0.5 pb-6"
     x-data="{
        viewMode: localStorage.getItem('stock_count_view_mode') || 'table',
        setView(mode) {
            this.viewMode = mode;
            localStorage.setItem('stock_count_view_mode', mode);
        }
     }">

    {{-- ============================================================
         1. COMPACT PAGE HEADER (34px - 38px Standard Height)
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400 grid place-items-center text-base font-bold shadow-xs shrink-0">
                📋
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.stock_count_title') ?? 'Physical Stock Count' }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.stock_count_sub') ?? 'Physical inventory count sessions & ledger discrepancy reconciliation' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0 flex-wrap">
            <a href="{{ route('store.admin.stock_ledger.index', ['store_slug' => $store->slug]) }}"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5 shadow-2xs">
                <span>📑</span>
                <span>{{ __('messages.sidebar_stock_ledger') ?? 'Stock Ledger' }}</span>
            </a>

            <a href="{{ route('store.admin.stock_count.create', ['store_slug' => $store->slug]) }}"
               class="h-7 px-3 rounded-md bg-violet-600 hover:bg-violet-500 text-white text-xs font-black shadow-2xs hover:shadow-violet-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('messages.stock_count_new_session') ?? 'New Count Session' }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. CENTERED ROW-BASED SUMMARY STAT CARDS (4-UP FILTER PILLS)
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1" role="list" aria-label="Stock Count Status Metrics">
        {{-- Total Sessions --}}
        <a href="{{ route('store.admin.stock_count.index', array_merge(['store_slug' => $store->slug], request()->except('status', 'page'))) }}"
           class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border transition-all duration-200 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 {{ empty($status) ? 'border-violet-600 bg-violet-50/60 dark:border-violet-500 dark:bg-violet-950/40 ring-1 ring-violet-500/20' : 'border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 shadow-inner text-xs sm:text-sm font-bold">
                📦
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-mono">
                    {{ number_format($stats['total']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_count_stat_total') ?? 'Total Sessions' }}
                </p>
            </div>
        </a>

        {{-- In Progress (Active Count Sessions) --}}
        <a href="{{ route('store.admin.stock_count.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => 'in_progress'])) }}"
           class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border transition-all duration-200 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 {{ $status === 'in_progress' ? 'border-amber-600 bg-amber-50/60 dark:border-amber-500 dark:bg-amber-950/40 ring-1 ring-amber-500/20' : 'border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold relative">
                ⏳
                @if($stats['in_progress'] > 0)
                    <span class="absolute top-0.5 right-0.5 w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                @endif
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-mono">
                    {{ number_format($stats['in_progress']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-amber-700 dark:text-amber-300/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_count_stat_in_progress') ?? 'In Progress' }}
                </p>
            </div>
        </a>

        {{-- Approved / Reconciled --}}
        <a href="{{ route('store.admin.stock_count.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => 'approved'])) }}"
           class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border transition-all duration-200 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 {{ $status === 'approved' ? 'border-emerald-600 bg-emerald-50/60 dark:border-emerald-500 dark:bg-emerald-950/40 ring-1 ring-emerald-500/20' : 'border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                ✅
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-mono">
                    {{ number_format($stats['approved']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-emerald-700 dark:text-emerald-300/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_count_stat_approved') ?? 'Approved' }}
                </p>
            </div>
        </a>

        {{-- Cancelled --}}
        <a href="{{ route('store.admin.stock_count.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => 'cancelled'])) }}"
           class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border transition-all duration-200 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 {{ $status === 'cancelled' ? 'border-rose-600 bg-rose-50/60 dark:border-rose-500 dark:bg-rose-950/40 ring-1 ring-rose-500/20' : 'border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300 shadow-inner text-xs sm:text-sm font-bold">
                ✕
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-mono">
                    {{ number_format($stats['cancelled']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-rose-700 dark:text-rose-300/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_count_stat_cancelled') ?? 'Cancelled' }}
                </p>
            </div>
        </a>
    </div>

    {{-- ============================================================
         3. INTERACTIVE INLINE TOOLBAR (Guide v4.1 Standard)
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-1">
        {{-- Left: Search Bar & Filter Pills --}}
        <div class="flex flex-wrap items-center gap-1.5 flex-1">
            <form method="GET" action="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug]) }}" class="relative min-w-[180px] sm:min-w-[240px] flex-1 max-w-sm">
                @if($status) <input type="hidden" name="status" value="{{ $status }}"> @endif
                @if($scope) <input type="hidden" name="scope" value="{{ $scope }}"> @endif
                @if($sort) <input type="hidden" name="sort" value="{{ $sort }}"> @endif
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="{{ __('messages.search') }} Session No., location, notes..."
                       class="w-full h-7 pl-8 pr-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-violet-500 focus:bg-white dark:focus:bg-slate-900 transition" />
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </form>

            {{-- Quick Status Filter Pills --}}
            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700 text-xs shrink-0">
                @foreach([
                    '' => __('messages.stock_count_all_status') ?? 'All',
                    'in_progress' => __('messages.stock_count_status_in_progress') ?? 'In Progress',
                    'approved' => __('messages.stock_count_status_approved') ?? 'Approved',
                    'cancelled' => __('messages.stock_count_status_cancelled') ?? 'Cancelled',
                ] as $stVal => $stLabel)
                    <a href="{{ route('store.admin.stock_count.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => $stVal])) }}"
                       class="px-2 py-0.5 rounded text-[11px] font-bold transition cursor-pointer whitespace-nowrap {{ ($filters['status'] ?? '') === $stVal ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                        {{ $stLabel }}
                    </a>
                @endforeach
            </div>

            {{-- Scope Filter Dropdown --}}
            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700 text-xs shrink-0">
                @foreach([
                    '' => __('messages.all') ?? 'All',
                    'all' => __('messages.stock_count_scope_all') ?? 'Full Store',
                    'category' => __('messages.stock_count_scope_category') ?? 'Category',
                ] as $scVal => $scLabel)
                    <a href="{{ route('store.admin.stock_count.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['scope' => $scVal])) }}"
                       class="px-2 py-0.5 rounded text-[11px] font-bold transition cursor-pointer whitespace-nowrap {{ ($filters['scope'] ?? '') === $scVal ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                        {{ $scLabel }}
                    </a>
                @endforeach
            </div>

            @if($activeFiltersCount > 0)
                <a href="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug]) }}"
                   class="h-6 px-2 rounded text-[11px] font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 border border-rose-200 dark:border-rose-900/60 transition inline-flex items-center gap-1 cursor-pointer">
                    <span>✕</span>
                    <span>{{ __('messages.reset') ?? 'Reset' }}</span>
                </a>
            @endif
        </div>

        {{-- Right: Excel Export & View Mode Switcher --}}
        <div class="flex items-center gap-1 self-end sm:self-auto shrink-0">
            {{-- Excel Export Button --}}
            <a href="{{ $exportUrl }}"
               title="Export Excel (.xlsx)"
               class="h-6 px-2 rounded text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <span>Excel</span>
            </a>

            {{-- CSV Export Link --}}
            <a href="{{ $exportUrl }}&format=csv"
               title="Export CSV"
               class="h-6 px-1.5 rounded text-[11px] font-bold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-0.5 cursor-pointer">
                <span>CSV</span>
            </a>

            {{-- Table / Cards View Mode Switcher --}}
            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
                <button type="button"
                        @click="setView('table')"
                        class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                        :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                    <span>{{ __('messages.view_table') ?? 'Table' }}</span>
                </button>
                <button type="button"
                        @click="setView('card')"
                        class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                        :class="viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span>{{ __('messages.view_cards') ?? 'Cards' }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
         4. SPREADSHEET DATA GRID (TABLE VIEW)
         ============================================================ --}}
    <div x-show="viewMode === 'table'" class="rounded-lg border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto max-h-[72vh] overflow-y-auto">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800 border-b border-slate-300 dark:border-slate-700 shadow-xs select-none backdrop-blur-xs">
                    <tr class="text-[11px] font-black uppercase tracking-wider divide-x divide-slate-200 dark:divide-slate-700 text-slate-800 dark:text-slate-100">
                        <th class="py-2 px-3 min-w-[150px]">{{ __('messages.stock_count_session_number') ?? 'Session #' }}</th>
                        <th class="py-2 px-3 min-w-[130px]">{{ __('messages.stock_count_location') ?? 'Location' }}</th>
                        <th class="py-2 px-2.5 min-w-[110px]">{{ __('messages.stock_count_scope') ?? 'Scope' }}</th>
                        <th class="py-2 px-3 text-center min-w-[130px]">{{ __('messages.stock_count_progress') ?? 'Progress' }}</th>
                        <th class="py-2 px-2.5 text-center min-w-[110px] bg-slate-200/50 dark:bg-slate-700/50 font-black">{{ __('messages.stock_count_variance_items') ?? 'Variance Lines' }}</th>
                        <th class="py-2 px-2.5 text-right min-w-[120px]">{{ __('messages.stock_count_variance_cost') ?? 'Net Variance' }}</th>
                        <th class="py-2 px-2.5 text-center min-w-[100px]">{{ __('messages.stock_count_status') ?? 'Status' }}</th>
                        <th class="py-2 px-3 min-w-[120px]">{{ __('messages.stock_count_date') ?? 'Date' }}</th>
                        <th class="py-2 px-2 text-right min-w-[130px]">{{ __('messages.actions') ?? 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse($sessions as $session)
                        @php
                            $progressPct = $session->total_items > 0 ? round(($session->counted_items / $session->total_items) * 100) : 0;
                            $varCost = (float) $session->total_variance_cost;
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 divide-x divide-slate-200/80 dark:divide-slate-800 transition">
                            {{-- Session Number & Notes --}}
                            <td class="py-1.5 px-3">
                                <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                                   class="font-mono font-black text-violet-600 dark:text-violet-400 hover:underline text-xs">
                                    {{ $session->session_number }}
                                </a>
                                @if($session->notes)
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-xs mt-0.5">{{ $session->notes }}</p>
                                @endif
                            </td>

                            {{-- Warehouse / Branch --}}
                            <td class="py-1.5 px-3 whitespace-nowrap">
                                <div class="font-bold text-slate-900 dark:text-slate-100">
                                    {{ $session->warehouse?->name ?? $session->branch?->name ?? 'Default Warehouse' }}
                                </div>
                                @if($session->branch && $session->warehouse)
                                    <div class="text-[10px] text-slate-400">{{ $session->branch->name }}</div>
                                @endif
                            </td>

                            {{-- Scope --}}
                            <td class="py-1.5 px-2.5">
                                @if($session->scope === 'category')
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-bold rounded bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        <span>🏷️</span>
                                        <span>Category</span>
                                        @if(!empty($session->category_ids))
                                            <span class="font-mono">({{ count($session->category_ids) }})</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-bold rounded bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        <span>📦</span>
                                        <span>{{ __('messages.stock_count_scope_all') ?? 'All Products' }}</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Progress Indicator --}}
                            <td class="py-1.5 px-3 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="text-xs font-mono font-bold text-slate-800 dark:text-slate-200">
                                        {{ $fmtQty($session->counted_items) }} / {{ $fmtQty($session->total_items) }}
                                        <span class="text-[10px] text-slate-400">({{ $progressPct }}%)</span>
                                    </div>
                                    <div class="w-20 bg-slate-200 dark:bg-slate-700 rounded-full h-1 mt-1 overflow-hidden">
                                        <div class="h-1 rounded-full transition-all duration-300 {{ $progressPct === 100 ? 'bg-emerald-500' : 'bg-violet-600' }}" style="width: {{ $progressPct }}%"></div>
                                    </div>
                                </div>
                            </td>

                            {{-- Variance Lines --}}
                            <td class="py-1.5 px-2.5 text-center bg-slate-50/50 dark:bg-slate-800/30">
                                @if($session->variance_items > 0)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[11px] font-black font-mono rounded bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        <span>⚠️</span>
                                        <span>{{ $fmtQty($session->variance_items) }}</span>
                                    </span>
                                @else
                                    <span class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500">0</span>
                                @endif
                            </td>

                            {{-- Net Variance Cost --}}
                            <td class="py-1.5 px-2.5 text-right font-mono font-bold text-xs tabular-nums whitespace-nowrap {{ $varCost < 0 ? 'text-rose-600 dark:text-rose-400' : ($varCost > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500') }}">
                                {{ $varCost != 0 ? ($varCost > 0 ? '+' : '') . number_format($varCost) . ' Ks' : '-' }}
                            </td>

                            {{-- Status Badge --}}
                            <td class="py-1.5 px-2.5 text-center whitespace-nowrap">
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
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                        <span>{{ __('messages.stock_count_status_in_progress') ?? 'In Progress' }}</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Date & Creator --}}
                            <td class="py-1.5 px-3 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400 font-mono">
                                <div>{{ $session->created_at->format('d/m/Y H:i') }}</div>
                                <div class="text-[10px] text-slate-400 font-sans mt-0.5">{{ $session->createdBy?->name ?? 'System' }}</div>
                            </td>

                            {{-- Actions --}}
                            <td class="py-1.5 px-2 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- Print Sheet --}}
                                    <a href="{{ route('store.admin.stock_count.print', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                                       target="_blank"
                                       title="{{ __('messages.stock_count_print_sheet') }}"
                                       class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                    </a>

                                    {{-- Primary Action Link --}}
                                    <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-bold rounded transition shadow-2xs {{ $session->isInProgress() ? 'bg-violet-600 hover:bg-violet-700 text-white' : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200' }}">
                                        <span>{{ $session->isInProgress() ? __('messages.stock_count_continue_count') : __('messages.stock_count_view_audit') }}</span>
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 px-4 text-center text-slate-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                    <span class="text-3xl mb-1.5">📋</span>
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.stock_count_no_sessions') ?? 'No stock count sessions found.' }}</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">{{ __('messages.stock_count_create_first') ?? 'Click New Session to start.' }}</p>
                                    <a href="{{ route('store.admin.stock_count.create', ['store_slug' => $store->slug]) }}"
                                       class="mt-2.5 inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-md bg-violet-600 text-white hover:bg-violet-700 shadow-xs transition">
                                        <span>+</span>
                                        <span>{{ __('messages.stock_count_new_session') ?? 'New Count Session' }}</span>
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
         5. RESPONSIVE CARDS VIEW GRID (CARD VIEW MODE)
         ============================================================ --}}
    <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0.5 sm:gap-1">
        @forelse($sessions as $session)
            @php
                $progressPct = $session->total_items > 0 ? round(($session->counted_items / $session->total_items) * 100) : 0;
                $varCost = (float) $session->total_variance_cost;
            @endphp
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:border-violet-300 dark:hover:border-violet-600/50 transition flex flex-col justify-between overflow-hidden">
                {{-- Top Card Content --}}
                <div class="p-2.5 sm:p-3 space-y-2">
                    {{-- Header Row: Session Number + Status Pill --}}
                    <div class="flex items-center justify-between gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                        <div>
                            <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                               class="font-mono font-black text-xs sm:text-sm text-violet-600 dark:text-violet-400 hover:underline tracking-tight block">
                                {{ $session->session_number }}
                            </a>
                            <div class="text-[10px] text-slate-400 font-mono">
                                {{ $session->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>

                        {{-- Status Badge --}}
                        @if($session->isApproved())
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                <span>✓</span>
                                <span>{{ __('messages.stock_count_status_approved') }}</span>
                            </span>
                        @elseif($session->isCancelled())
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                <span>✕</span>
                                <span>{{ __('messages.stock_count_status_cancelled') }}</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                <span>{{ __('messages.stock_count_status_in_progress') }}</span>
                            </span>
                        @endif
                    </div>

                    {{-- Location & Scope Tags --}}
                    <div class="flex items-center justify-between gap-1.5 text-xs">
                        <div class="font-bold text-slate-800 dark:text-slate-200 truncate flex items-center gap-1 min-w-0">
                            <span>🏬</span>
                            <span class="truncate">{{ $session->warehouse?->name ?? $session->branch?->name ?? 'Default Warehouse' }}</span>
                        </div>
                        @if($session->scope === 'category')
                            <span class="shrink-0 inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-bold rounded bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                <span>🏷️</span>
                                <span>Category @if(!empty($session->category_ids))({{ count($session->category_ids) }})@endif</span>
                            </span>
                        @else
                            <span class="shrink-0 inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-bold rounded bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                <span>📦</span>
                                <span>All Products</span>
                            </span>
                        @endif
                    </div>

                    {{-- Progress Hero Metric Box --}}
                    <div class="p-2 rounded bg-slate-50/80 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 space-y-1">
                        <div class="flex items-center justify-between text-xs font-mono">
                            <span class="text-slate-500 dark:text-slate-400 font-bold">{{ __('messages.stock_count_progress') }}:</span>
                            <span class="font-black text-slate-900 dark:text-slate-100">
                                {{ $fmtQty($session->counted_items) }} / {{ $fmtQty($session->total_items) }}
                                <span class="text-[10px] text-violet-600 dark:text-violet-400 font-bold">({{ $progressPct }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full transition-all duration-300 {{ $progressPct === 100 ? 'bg-emerald-500' : 'bg-violet-600' }}" style="width: {{ $progressPct }}%"></div>
                        </div>

                        {{-- Variance Status Row --}}
                        <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ __('messages.stock_count_variance_items') }}:</span>
                            @if($session->variance_items > 0)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[10px] font-black font-mono text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800">
                                    <span>⚠️</span>
                                    <span>{{ $fmtQty($session->variance_items) }} items</span>
                                </span>
                            @else
                                <span class="text-[10px] font-mono font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-0.5">
                                    <span>✓</span> 0 Variance
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card Action Footer --}}
                <div class="p-2 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1.5">
                    <a href="{{ route('store.admin.stock_count.print', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                       target="_blank"
                       title="{{ __('messages.stock_count_print_sheet') }}"
                       class="h-6 px-2 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 rounded hover:bg-slate-200 dark:hover:bg-slate-700 transition text-[11px] font-bold inline-flex items-center gap-1">
                        <span>🖨️</span>
                        <span>Print</span>
                    </a>

                    <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                       class="h-6 px-2.5 rounded text-[11px] font-bold transition inline-flex items-center gap-1 shadow-2xs {{ $session->isInProgress() ? 'bg-violet-600 hover:bg-violet-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        <span>{{ $session->isInProgress() ? __('messages.stock_count_continue_count') : __('messages.stock_count_view_audit') }}</span>
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full py-10 px-4 text-center text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 rounded-lg border border-dashed border-slate-200 dark:border-slate-800 shadow-2xs">
                <span class="text-3xl mb-1.5 block">📋</span>
                <p class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.stock_count_no_sessions') ?? 'No stock count sessions found.' }}</p>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ __('messages.stock_count_create_first') ?? 'Click New Session to start.' }}</p>
            </div>
        @endforelse
    </div>

    {{-- Bottom Pagination --}}
    @if($sessions->hasPages())
        <div class="p-1.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            {{ $sessions->links() }}
        </div>
    @endif

</div>
@endsection

