@extends('layouts.admin.app')

@section('title', __('messages.sidebar_stock_reconciliation') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
    @php
        $isManager = auth()->user()?->hasStoreRole($store->id, 'store_manager') || auth()->user()?->hasStoreRole($store->id, 'store_owner');
        $storeRouteParams = ['store_slug' => $store->slug];
        
        $totalProducts = (int)($report['products'] ?? 0);
        $diffProducts = (int)($report['diff_products'] ?? 0);
        $cleanProducts = max(0, $totalProducts - $diffProducts);
        $totalDiff = (float)($report['total_diff'] ?? 0);
        $isClean = (bool)($report['clean'] ?? false);

        $fmtQty = function($v) {
            $val = (float) $v;
            return $val == (int) $val ? number_format($val, 0) : rtrim(rtrim(number_format($val, 3), '0'), '.');
        };
    @endphp

    <div class="w-full space-y-0.5 pb-6" 
         x-data="{ 
             filterMode: 'all', // 'all', 'diff', 'clean'
             searchQuery: '',
             viewMode: localStorage.getItem('pos_reconciliation_view') || 'table',
             setView(mode) {
                 this.viewMode = mode;
                 localStorage.setItem('pos_reconciliation_view', mode);
             },
             reconcileModalOpen: false,
             historyOpen: false,
             submitting: false,

             matchesFilter(name, sku, hasDiff) {
                 const q = this.searchQuery.trim().toLowerCase();
                 const matchesSearch = !q || 
                     (name && name.toLowerCase().includes(q)) || 
                     (sku && sku.toLowerCase().includes(q));

                 const matchesType = this.filterMode === 'all' 
                     || (this.filterMode === 'diff' && hasDiff)
                     || (this.filterMode === 'clean' && !hasDiff);

                 return matchesSearch && matchesType;
             }
         }"
         @keydown.escape.window="reconcileModalOpen = false">

        {{-- ============================================================
             1. COMPACT PAGE HEADER (34px - 38px Standard Height)
             ============================================================ --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 grid place-items-center text-base font-bold shadow-xs flex-shrink-0">
                    ⚖️
                </span>
                <div class="min-w-0">
                    <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                        <span>{{ __('messages.sidebar_stock_reconciliation') }}</span>
                        <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                    </h1>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                        {{ __('messages.reconciliation_subtitle') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0">
                <a href="{{ url('/store/' . $store->slug . '/pos') }}"
                   class="h-7 px-2.5 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                    <span>🛒</span>
                    <span>{{ __('messages.back_to_pos') }}</span>
                </a>

                <button type="button" onclick="window.print()"
                        class="h-7 px-2.5 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    <span>Print</span>
                </button>

                @if ($isManager && !$isClean)
                    <button type="button" @click="reconcileModalOpen = true"
                            class="h-7 px-3 rounded-md bg-sky-600 hover:bg-sky-500 text-white text-xs font-black shadow-2xs hover:shadow-sky-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                        <span>⚖️</span>
                        <span>{{ __('messages.reconciliation_approve') }}</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Flash Alerts --}}
        @if (session('error'))
            <div class="px-3 py-1.5 rounded-lg border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 text-xs font-bold flex items-center gap-2 shadow-2xs">
                <span>⚠️</span>
                <div class="min-w-0 truncate">{{ session('error') }}</div>
            </div>
        @endif
        @if (session('success'))
            <div class="px-3 py-1.5 rounded-lg border border-emerald-200 dark:border-emerald-900/60 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center gap-2 shadow-2xs">
                <span>✅</span>
                <div class="min-w-0 truncate">{{ session('success') }}</div>
            </div>
        @endif

        {{-- ============================================================
             2. SUMMARY STAT CARDS (Row-Based Center Alignment)
             ============================================================ --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1" role="list" aria-label="Stock Reconciliation Metrics">
            {{-- Card 1: Total Audited Products --}}
            <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 shadow-inner text-xs sm:text-sm font-bold">
                    📦
                </div>
                <div class="min-w-0 text-left">
                    <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-mono">
                        {{ number_format($totalProducts) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.reconciliation_products') }}
                    </p>
                </div>
            </div>

            {{-- Card 2: Discrepancy Products --}}
            <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 {{ $isClean ? 'border-slate-200/80 dark:border-slate-800' : 'border-amber-400/80 dark:border-amber-600/80 bg-amber-50/20 dark:bg-amber-950/20' }}">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center {{ $isClean ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300' : 'bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300' }} shadow-inner text-xs sm:text-sm font-bold relative">
                    ⚖️
                    @if($diffProducts > 0)
                        <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                    @endif
                </div>
                <div class="min-w-0 text-left">
                    <div class="text-sm sm:text-base font-black font-mono leading-none tabular-nums {{ $isClean ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                        {{ number_format($diffProducts) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.reconciliation_diff_products') }}
                    </p>
                </div>
            </div>

            {{-- Card 3: Net Stock Variance --}}
            <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 shadow-inner text-xs sm:text-sm font-bold">
                    📊
                </div>
                <div class="min-w-0 text-left">
                    <div class="text-sm sm:text-base font-black font-mono leading-none tabular-nums {{ $totalDiff != 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $totalDiff > 0 ? '+' : '' }}{{ $fmtQty($totalDiff) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.reconciliation_total_diff') }}
                    </p>
                </div>
            </div>

            {{-- Card 4: Audit Status --}}
            <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center {{ $isClean ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300' : 'bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300' }} shadow-inner text-xs sm:text-sm font-bold">
                    🛡️
                </div>
                <div class="min-w-0 text-left">
                    <div class="text-xs sm:text-sm font-black truncate leading-none {{ $isClean ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                        {{ $isClean ? __('messages.reconciliation_clean_status') : __('messages.reconciliation_diff_status') }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider font-mono">
                        {{ $cleanProducts }} / {{ $totalProducts }} OK
                    </p>
                </div>
            </div>
        </div>

        {{-- ============================================================
             3. INTERACTIVE INLINE TOOLBAR (Search, Filter Pills, Export, View Toggle)
             ============================================================ --}}
        <div class="bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-1">
            {{-- Left: Search Bar & Filter Pills --}}
            <div class="flex flex-wrap items-center gap-1.5 flex-1 min-w-0">
                <div class="relative min-w-[180px] sm:min-w-[260px] flex-1 max-w-sm">
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="{{ __('messages.reconciliation_search_placeholder') }}"
                           class="w-full h-7 pl-8 pr-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-sky-500 focus:bg-white dark:focus:bg-slate-900 transition" />
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </div>

                {{-- Status Filter Tabs --}}
                <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700 overflow-x-auto max-w-full">
                    <button type="button" @click="filterMode = 'all'"
                            :class="filterMode === 'all' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                            class="px-2 py-0.5 rounded text-[11px] font-bold transition flex items-center gap-1 whitespace-nowrap cursor-pointer">
                        <span>{{ __('messages.all') }}</span>
                        <span class="text-[10px] font-mono opacity-80">({{ $totalProducts }})</span>
                    </button>
                    <button type="button" @click="filterMode = 'diff'"
                            :class="filterMode === 'diff' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-400 shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                            class="px-2 py-0.5 rounded text-[11px] font-bold transition flex items-center gap-1 whitespace-nowrap cursor-pointer">
                        <span>⚠️ {{ __('messages.reconciliation_diff_badge') }}</span>
                        <span class="text-[10px] font-mono opacity-80">({{ $diffProducts }})</span>
                    </button>
                    <button type="button" @click="filterMode = 'clean'"
                            :class="filterMode === 'clean' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                            class="px-2 py-0.5 rounded text-[11px] font-bold transition flex items-center gap-1 whitespace-nowrap cursor-pointer">
                        <span>✓ {{ __('messages.reconciliation_clean_badge') }}</span>
                        <span class="text-[10px] font-mono opacity-80">({{ $cleanProducts }})</span>
                    </button>
                </div>
            </div>

            {{-- Right: Export Button & View Switcher --}}
            <div class="flex items-center gap-1 self-end sm:self-auto shrink-0">
                @if(!empty($exportUrl))
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
                @endif

                <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
                    <button type="button"
                            @click="setView('table')"
                            class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                            :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                        <span>{{ __('messages.view_table') ?? 'Table' }}</span>
                    </button>
                    <button type="button"
                            @click="setView('card')"
                            class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                            :class="viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        <span>{{ __('messages.view_cards') ?? 'Cards' }}</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================
             4. RESPONSIVE CARDS VIEW GRID (CARD VIEW MODE / MOBILE OPTIMIZED)
             ============================================================ --}}
        <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0.5 sm:gap-1">
            @forelse ($report['rows'] ?? [] as $row)
                @php 
                    $hasDiff = abs((float) ($row['diff'] ?? 0)) > 0.0001; 
                    $productName = $row['product_name'] ?? '—';
                    $productSku = $row['sku'] ?? '';
                    $diffVal = (float) ($row['diff'] ?? 0);
                    $onHandVal = (float) ($row['on_hand'] ?? 0);
                @endphp
                <div x-show="matchesFilter('{{ addslashes($productName) }}', '{{ addslashes($productSku) }}', {{ $hasDiff ? 'true' : 'false' }})"
                     class="bg-white dark:bg-slate-900 border rounded-lg overflow-hidden shadow-2xs transition flex flex-col justify-between group {{ $hasDiff ? 'border-amber-300 dark:border-amber-700/80 bg-amber-50/10' : 'border-slate-200/80 dark:border-slate-800' }}">
                    
                    <div class="p-2.5 sm:p-3 space-y-2">
                        {{-- Card Header: SKU + Status Pill --}}
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 grid place-items-center text-xs font-black shrink-0">
                                    {{ $hasDiff ? '⚠️' : '📦' }}
                                </span>
                                @if (!empty($productSku))
                                    <span class="px-1.5 py-0.5 rounded font-mono font-bold text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 truncate">
                                        SKU: {{ $productSku }}
                                    </span>
                                @endif
                            </div>

                            @if ($hasDiff)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shrink-0">
                                    {{ __('messages.reconciliation_diff_badge') }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shrink-0">
                                    ✓ {{ __('messages.reconciliation_clean_badge') }}
                                </span>
                            @endif
                        </div>

                        {{-- Product Title --}}
                        <div>
                            <h4 class="font-black text-xs sm:text-sm text-slate-900 dark:text-slate-100 line-clamp-2" title="{{ $productName }}">
                                {{ $productName }}
                            </h4>
                        </div>

                        {{-- 2x2 Numeric Comparison Grid --}}
                        <div class="grid grid-cols-2 gap-1.5 bg-slate-50 dark:bg-slate-800/50 p-2 rounded-lg border border-slate-100 dark:border-slate-800 text-[11px]">
                            <div>
                                <span class="text-[9px] text-slate-400 block uppercase font-bold">{{ __('messages.reconciliation_imported') }}</span>
                                <span class="font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ $fmtQty($row['imported'] ?? $row['imported_qty'] ?? 0) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[9px] text-slate-400 block uppercase font-bold">{{ __('messages.reconciliation_recorded') }}</span>
                                <span class="font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ $fmtQty($row['recorded'] ?? $row['recorded_qty'] ?? 0) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[9px] text-slate-400 block uppercase font-bold">{{ __('messages.reconciliation_diff') }}</span>
                                <span class="font-mono font-black {{ $hasDiff ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                                    {{ $diffVal > 0 ? '+' : '' }}{{ $fmtQty($diffVal) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[9px] text-slate-400 block uppercase font-bold">{{ __('messages.reconciliation_on_hand') }}</span>
                                <span class="font-mono font-black text-slate-900 dark:text-slate-100">
                                    {{ $fmtQty($onHandVal) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="p-2 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 text-[10px] text-slate-400 flex items-center justify-between font-mono">
                        <span>{{ $hasDiff ? 'Action Required' : 'Synchronized' }}</span>
                        <span>{{ $hasDiff ? 'Diff: ' . ($diffVal > 0 ? '+' : '') . $fmtQty($diffVal) : 'OK' }}</span>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 p-8 rounded-lg text-center text-slate-400 shadow-2xs">
                    <div class="text-3xl mb-1.5 opacity-55">⚖️</div>
                    <div class="text-xs sm:text-sm font-black text-slate-700 dark:text-slate-300 mb-0.5">{{ __('messages.reconciliation_none') }}</div>
                    <div class="text-[11px] text-slate-400">{{ __('messages.reconciliation_empty_hint') }}</div>
                </div>
            @endforelse
        </div>

        {{-- ============================================================
             5. SPREADSHEET DATA GRID TABLE (TABLE VIEW MODE)
             ============================================================ --}}
        <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
            <div class="overflow-x-auto max-h-[72vh] overflow-y-auto">
                <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                        <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                            <th class="py-1.5 px-2.5 min-w-[200px]">{{ __('messages.product') ?? 'Product' }}</th>
                            <th class="py-1.5 px-2.5 text-right min-w-[120px]">{{ __('messages.reconciliation_imported') }}</th>
                            <th class="py-1.5 px-2.5 text-right min-w-[120px]">{{ __('messages.reconciliation_recorded') }}</th>
                            <th class="py-1.5 px-2.5 text-right min-w-[130px] bg-slate-200/50 dark:bg-slate-700/50 font-black text-slate-900 dark:text-white">
                                {{ __('messages.reconciliation_diff') }}
                            </th>
                            <th class="py-1.5 px-2.5 text-right min-w-[120px]">{{ __('messages.reconciliation_on_hand') }}</th>
                            <th class="py-1.5 px-2.5 text-center w-28">{{ __('messages.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @forelse ($report['rows'] ?? [] as $row)
                            @php 
                                $hasDiff = abs((float) ($row['diff'] ?? 0)) > 0.0001; 
                                $productName = $row['product_name'] ?? '—';
                                $productSku = $row['sku'] ?? '';
                                $diffVal = (float) ($row['diff'] ?? 0);
                            @endphp
                            <tr x-show="matchesFilter('{{ addslashes($productName) }}', '{{ addslashes($productSku) }}', {{ $hasDiff ? 'true' : 'false' }})"
                                class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition {{ $hasDiff ? 'bg-amber-50/30 dark:bg-amber-950/20' : '' }}">
                                
                                {{-- Product Name & SKU --}}
                                <td class="py-1.5 px-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 grid place-items-center text-xs font-black shrink-0">
                                            {{ $hasDiff ? '⚠️' : '📦' }}
                                        </span>
                                        <div class="min-w-0">
                                            <span class="font-black text-slate-900 dark:text-slate-100 text-xs block truncate" title="{{ $productName }}">
                                                {{ $productName }}
                                            </span>
                                            @if (!empty($productSku))
                                                <span class="text-[10px] font-mono text-slate-400 block">SKU: {{ $productSku }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Imported Opening Stock --}}
                                <td class="py-1.5 px-2.5 text-right font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ $fmtQty($row['imported'] ?? $row['imported_qty'] ?? 0) }}
                                </td>

                                {{-- Ledger Recorded Qty --}}
                                <td class="py-1.5 px-2.5 text-right font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ $fmtQty($row['recorded'] ?? $row['recorded_qty'] ?? 0) }}
                                </td>

                                {{-- Discrepancy / Variance --}}
                                <td class="py-1.5 px-2.5 text-right font-mono font-black tabular-nums bg-slate-50/50 dark:bg-slate-800/30 {{ $hasDiff ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                                    @if ($hasDiff)
                                        <span>{{ $diffVal > 0 ? '+' : '' }}{{ $fmtQty($diffVal) }}</span>
                                    @else
                                        <span>0</span>
                                    @endif
                                </td>

                                {{-- Current On-Hand Stock --}}
                                <td class="py-1.5 px-2.5 text-right font-mono font-black text-slate-900 dark:text-slate-100">
                                    {{ $fmtQty($row['on_hand'] ?? 0) }}
                                </td>

                                {{-- Status --}}
                                <td class="py-1.5 px-2.5 text-center whitespace-nowrap">
                                    @if ($hasDiff)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                            {{ __('messages.reconciliation_diff_badge') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            ✓ {{ __('messages.reconciliation_clean_badge') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    <div class="text-3xl mb-1.5 opacity-55">⚖️</div>
                                    <div class="text-xs sm:text-sm font-black text-slate-700 dark:text-slate-300 mb-0.5">{{ __('messages.reconciliation_none') }}</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.reconciliation_empty_hint') }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============================================================
             6. PAST RECONCILIATION RECORDS (HISTORY SECTION)
             ============================================================ --}}
        @if (!empty($history) && $history->isNotEmpty())
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-2.5 sm:p-3 shadow-2xs space-y-2">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                        <span>📜</span>
                        <span>{{ __('messages.reconciliation_history') }}</span>
                    </h3>
                    <span class="text-[11px] text-slate-400 font-mono font-bold">{{ $history->count() }} Records</span>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($history as $h)
                        <div class="py-2 flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 text-xs">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-black text-slate-900 dark:text-slate-100">{{ $h->reconciliation_number }}</span>
                                    <span class="px-2 py-0.2 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        ✓ Approved
                                    </span>
                                </div>
                                <span class="text-[10px] sm:text-[11px] text-slate-400 block mt-0.5">
                                    {{ __('messages.reviewed_by') ?? 'Approved by' }}: <strong class="text-slate-600 dark:text-slate-300">{{ $h->approver?->name ?? '—' }}</strong> · {{ $h->created_at->format('d/m/Y H:i') }}
                                    @if (!empty($h->review_notes))
                                        · <em>{{ $h->review_notes }}</em>
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ============================================================
             7. AUTO-RECONCILE MODAL DIALOG
             ============================================================ --}}
        <div x-show="reconcileModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="reconcileModalOpen = false"></div>
            <div class="min-h-full flex items-center justify-center p-3 sm:p-4">
                <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl p-4 sm:p-5 space-y-3" @click.stop>
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                            <span>⚖️</span>
                            <span>{{ __('messages.reconciliation_approve') }}</span>
                        </h3>
                        <button type="button" @click="reconcileModalOpen = false" class="w-6 h-6 rounded-md text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 grid place-items-center text-sm font-bold cursor-pointer">&times;</button>
                    </div>

                    <div class="p-2.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-lg text-xs text-amber-800 dark:text-amber-200 leading-relaxed space-y-1">
                        <p class="font-black flex items-center gap-1.5">
                            <span>⚠️</span> {{ __('messages.reconciliation_approve_confirm') }}
                        </p>
                        <p class="text-[11px] text-amber-700/90 dark:text-amber-300/80">
                            {{ __('messages.reconciliation_approve_hint') }}
                        </p>
                        <p class="text-[11px] font-mono font-bold mt-0.5">
                            {{ __('messages.reconciliation_diff_products') }}: {{ $diffProducts }} Products ({{ __('messages.net_stock_variance') ?? 'Net Variance' }}: {{ ($totalDiff > 0 ? '+' : '') . $fmtQty($totalDiff) }})
                        </p>
                    </div>

                    <form method="POST" action="{{ route('pos.reconciliation.approve', $storeRouteParams) }}"
                          @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }"
                          class="space-y-3">
                        @csrf
                        <div class="space-y-1">
                            <label class="block text-[10px] font-bold uppercase text-slate-600 dark:text-slate-400">
                                {{ __('messages.reconciliation_review_notes') }}
                            </label>
                            <input type="text" name="review_notes" placeholder="e.g. Monthly stock audit opening variance reconciliation..." maxlength="255"
                                   class="w-full h-7 rounded-md border border-slate-300 dark:border-slate-700 px-2.5 text-xs font-semibold bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-sky-500" />
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="reconcileModalOpen = false"
                                    class="h-7 px-3 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition cursor-pointer">
                                {{ __('messages.cancel') }}
                            </button>
                            <button type="submit" :disabled="submitting"
                                    class="h-7 px-4 rounded-md bg-sky-600 hover:bg-sky-500 text-white text-xs font-black shadow-2xs hover:shadow-sky-500/20 transition active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer">
                                <span x-show="!submitting" class="inline-flex items-center gap-1.5">
                                    <span>⚖️</span> {{ __('messages.reconciliation_approve') }}
                                </span>
                                <span x-show="submitting" class="inline-flex items-center gap-1.5">
                                    <span>Submitting...</span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection
