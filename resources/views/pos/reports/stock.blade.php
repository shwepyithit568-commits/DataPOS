@extends('layouts.admin.app')

@section('title', __('messages.sidebar_stock_balance') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    $fmtQty = function($v) {
        $val = (float) $v;
        return $val == (int) $val ? number_format($val, 0) : rtrim(rtrim(number_format($val, 3), '0'), '.');
    };
    $totalItemsCount = count($report['rows']);
    $zeroStockCount = $report['rows']->filter(fn($r) => (float)$r['quantity_on_hand'] <= 0)->count();
    $lowStockCount = $report['rows']->filter(fn($r) => (float)$r['quantity_on_hand'] > 0 && (float)$r['quantity_on_hand'] <= 5)->count();
@endphp

<div class="w-full space-y-0.5 pb-6"
     x-data="{ viewMode: localStorage.getItem('admin_view_mode') || 'table', statusFilter: 'all' }"
     @view-changed.window="viewMode = $event.detail">

    {{-- ============================================================
         1. COMPACT PAGE HEADER (34px - 38px Standard Height)
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 grid place-items-center text-base font-bold shadow-xs shrink-0">
                📊
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.sidebar_stock_balance') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                    <span class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                        {{ number_format($totalItemsCount) }}
                    </span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.reports_stock_subtitle') }}
                </p>
            </div>
        </div>

        {{-- Header Actions: Excel, CSV, Print & Quick Nav --}}
        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0 flex-wrap">
            {{-- Excel Export Button --}}
            <a href="{{ route('pos.reports.stock.export', ['store_slug' => $store->slug, 'q' => request('q'), 'format' => 'xlsx']) }}"
               title="Export Excel Spreadsheet (.xlsx)"
               class="h-7 px-2.5 rounded-md text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <span>Excel</span>
            </a>

            {{-- CSV Export Button --}}
            <a href="{{ route('pos.reports.stock.export', ['store_slug' => $store->slug, 'q' => request('q'), 'format' => 'csv']) }}"
               title="Export CSV Data (.csv)"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                <span>CSV</span>
            </a>

            {{-- Print Button --}}
            <button type="button" @click="window.print()"
                    title="Print Stock Balance Report"
                    class="h-7 px-2.5 rounded-md text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>{{ __('messages.print') }}</span>
            </button>

            {{-- Quick Nav: Stock Ledger --}}
            <a href="{{ route('store.admin.stock_ledger.index', ['store_slug' => $store->slug]) }}"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 dark:hover:bg-violet-900/60 shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <span>📑</span>
                <span>{{ __('messages.sidebar_stock_ledger') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. SUMMARY STAT CARDS (Row-based Center Alignment Standard)
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-0.5 sm:gap-1" role="list">
        {{-- Total SKUs --}}
        <div role="listitem"
             class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 shadow-inner text-xs sm:text-sm font-bold">
                🏷️
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ number_format($totalItemsCount) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.reports_stock_total_skus') }}
                </p>
            </div>
        </div>

        {{-- Total On-Hand Units --}}
        <div role="listitem"
             class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-emerald-200/80 dark:border-emerald-900/60 bg-emerald-50/20 dark:bg-emerald-950/20 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                📦
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit">
                    {{ $fmtQty($report['total_units']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.reports_total_units') }}
                </p>
            </div>
        </div>

        {{-- Total Valuation --}}
        <div role="listitem"
             class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-sky-200/80 dark:border-sky-900/60 bg-sky-50/20 dark:bg-sky-950/20 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300 shadow-inner text-xs sm:text-sm font-bold">
                💰
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-sky-600 dark:text-sky-400 leading-none tabular-nums font-outfit">
                    Ks {{ number_format((float) $report['total_value']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.reports_stock_value') }}
                </p>
            </div>
        </div>

        {{-- Zero / Out of Stock Alert --}}
        <div role="listitem"
             class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border {{ $zeroStockCount > 0 ? 'border-rose-200/80 dark:border-rose-900/60 bg-rose-50/20 dark:bg-rose-950/20' : 'border-slate-200/80 dark:border-slate-800' }} shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center {{ $zeroStockCount > 0 ? 'bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }} shadow-inner text-xs sm:text-sm font-bold">
                ⚠️
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black {{ $zeroStockCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' }} leading-none tabular-nums font-outfit">
                    {{ number_format($zeroStockCount) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.reports_stock_low_stock') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. STANDARD TOOLBAR WITH SEARCH & STATUS PILLS
         ============================================================ --}}
    <x-admin.toolbar
        :showSearch="true"
        :searchPlaceholder="__('messages.reports_search') ?? 'Search product, SKU...'"
        :searchValue="request('q') ?? request('search') ?? ''"
        :showViewToggle="true"
        :activeView="'table'"
        :showExcel="true"
        :excelUrl="route('pos.reports.stock.export', ['store_slug' => $store->slug, 'q' => request('q'), 'format' => 'xlsx'])"
        :showCsv="true"
        :csvUrl="route('pos.reports.stock.export', ['store_slug' => $store->slug, 'q' => request('q'), 'format' => 'csv'])"
    >
        {{-- Quick Stock Status Tabs --}}
        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-0.5 rounded-lg border border-slate-200/80 dark:border-slate-700 text-xs shrink-0">
            <button type="button"
                    @click="statusFilter = 'all'"
                    class="h-6 px-2.5 rounded-md text-xs font-bold transition whitespace-nowrap inline-flex items-center"
                    :class="statusFilter === 'all' ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-2xs font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
                {{ __('messages.all') }}
            </button>
            <button type="button"
                    @click="statusFilter = 'in_stock'"
                    class="h-6 px-2.5 rounded-md text-xs font-bold transition whitespace-nowrap inline-flex items-center"
                    :class="statusFilter === 'in_stock' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-300 shadow-2xs font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
                {{ __('messages.reports_stock_in_stock') }}
            </button>
            <button type="button"
                    @click="statusFilter = 'low_stock'"
                    class="h-6 px-2.5 rounded-md text-xs font-bold transition whitespace-nowrap inline-flex items-center"
                    :class="statusFilter === 'low_stock' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-300 shadow-2xs font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
                {{ __('messages.low_stock') }}
            </button>
            <button type="button"
                    @click="statusFilter = 'out_of_stock'"
                    class="h-6 px-2.5 rounded-md text-xs font-bold transition whitespace-nowrap inline-flex items-center"
                    :class="statusFilter === 'out_of_stock' ? 'bg-white dark:bg-slate-700 text-rose-600 dark:text-rose-300 shadow-2xs font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
                {{ __('messages.reports_stock_out_of_stock') }}
            </button>
        </div>
    </x-admin.toolbar>

    {{-- ============================================================
         4. SPREADSHEET DATA GRID TABLE (TABLE VIEW)
         ============================================================ --}}
    <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        @if ($report['rows']->isEmpty())
            <div class="py-12 px-4 text-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('messages.reports_stock_no_data') }}</p>
                @if (request('q'))
                    <a href="{{ url('/store/' . $store->slug . '/pos/reports/stock') }}"
                       class="inline-block mt-2 text-xs font-bold text-sky-600 hover:text-sky-500 underline">
                        {{ __('messages.reset_filters') }}
                    </a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto max-h-[75vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
                <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200 min-w-[760px]">
                    {{-- Sticky Header --}}
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b border-slate-200 dark:border-slate-700 shadow-2xs select-none">
                        <tr class="text-[10px] sm:text-[11px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-wider divide-x divide-slate-200 dark:divide-slate-700">
                            <th class="py-1.5 px-2.5 w-12 text-center">#</th>
                            <th class="py-1.5 px-2.5 min-w-[200px]">{{ __('messages.product') }}</th>
                            <th class="py-1.5 px-2.5 min-w-[130px]">{{ __('messages.sku') }}</th>
                            <th class="py-1.5 px-2.5 text-center min-w-[110px]">{{ __('messages.status') }}</th>
                            <th class="py-1.5 px-2.5 text-right min-w-[110px] bg-slate-200/50 dark:bg-slate-700/50 font-black text-slate-900 dark:text-white">{{ __('messages.on_hand_qty') }}</th>
                            <th class="py-1.5 px-2.5 text-right min-w-[110px]">{{ __('messages.average_cost') }}</th>
                            <th class="py-1.5 px-2.5 text-right min-w-[130px]">{{ __('messages.stock_value') }}</th>
                            <th class="py-1.5 px-2 text-center w-28">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    {{-- Table Body --}}
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @foreach ($report['rows'] as $index => $row)
                            @php
                                $qty = (float) $row['quantity_on_hand'];
                                $cost = (float) $row['unit_cost_avg'];
                                $val = (float) $row['value'];
                                $product = $row['product'];
                                $itemStatusKey = $qty > 5 ? 'in_stock' : ($qty > 0 ? 'low_stock' : 'out_of_stock');
                            @endphp
                            <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition"
                                x-show="statusFilter === 'all' || statusFilter === '{{ $itemStatusKey }}'">
                                <td class="py-1.5 px-2.5 text-center text-slate-400 font-mono text-[11px]">
                                    {{ $index + 1 }}
                                </td>
                                <td class="py-1.5 px-2.5">
                                    <div class="font-bold text-slate-900 dark:text-slate-100 leading-tight truncate max-w-[220px]">
                                        {{ $product?->name ?? '—' }}
                                    </div>
                                    @if ($product?->category)
                                        <div class="text-[10px] text-slate-400 font-medium mt-0.5">
                                            {{ $product->category->name }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-1.5 px-2.5 font-mono text-slate-500 dark:text-slate-400">
                                    <span class="inline-block px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-[11px]">
                                        {{ $product?->sku ?: '—' }}
                                    </span>
                                </td>
                                <td class="py-1.5 px-2.5 text-center whitespace-nowrap">
                                    @if ($qty > 5)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <span>✅</span>
                                            <span>{{ __('messages.reports_stock_in_stock') }}</span>
                                        </span>
                                    @elseif ($qty > 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                            <span>⚠️</span>
                                            <span>{{ __('messages.low_stock') }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            <span>❌</span>
                                            <span>{{ __('messages.reports_stock_out_of_stock') }}</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-1.5 px-2.5 text-right font-mono font-black text-xs sm:text-sm tabular-nums bg-slate-50/50 dark:bg-slate-800/30 {{ $qty <= 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' }}">
                                    {{ $fmtQty($qty) }}
                                </td>
                                <td class="py-1.5 px-2.5 text-right font-mono tabular-nums text-slate-600 dark:text-slate-400">
                                    Ks {{ number_format($cost) }}
                                </td>
                                <td class="py-1.5 px-2.5 text-right font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums">
                                    Ks {{ number_format($val) }}
                                </td>
                                <td class="py-1.5 px-2 text-center whitespace-nowrap">
                                    @if ($product)
                                        <a href="{{ route('store.admin.stock_ledger.bin_card', ['store_slug' => $store->slug, 'product' => $product->id]) }}"
                                           class="h-6 px-2 text-xs font-bold rounded-md text-violet-600 hover:bg-violet-50 dark:text-violet-400 dark:hover:bg-violet-950/40 transition inline-flex items-center gap-1 active:scale-95"
                                           title="{{ __('messages.reports_stock_view_ledger') }}">
                                            <span>📑</span>
                                            <span>Bin Card</span>
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    {{-- Sticky Summary Footer --}}
                    <tfoot class="sticky bottom-0 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs font-bold border-t-2 border-slate-300 dark:border-slate-600 text-xs shadow-inner">
                        <tr class="divide-x divide-slate-200 dark:divide-slate-700">
                            <td colspan="4" class="py-1.5 px-2.5 text-right font-black uppercase text-slate-700 dark:text-slate-300">
                                {{ __('messages.total') }}:
                            </td>
                            <td class="py-1.5 px-2.5 text-right font-mono font-black text-emerald-700 dark:text-emerald-400 tabular-nums bg-emerald-50/50 dark:bg-emerald-950/40">
                                {{ $fmtQty($report['total_units']) }}
                            </td>
                            <td class="py-1.5 px-2.5 text-right font-mono text-slate-400 tabular-nums">
                                —
                            </td>
                            <td class="py-1.5 px-2.5 text-right font-mono font-black text-sky-700 dark:text-sky-400 tabular-nums">
                                Ks {{ number_format((float) $report['total_value']) }}
                            </td>
                            <td class="py-1.5 px-2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    {{-- ============================================================
         5. RESPONSIVE CARDS VIEW GRID (CARD VIEW MODE)
         ============================================================ --}}
    <div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-1 sm:gap-1.5">
        @forelse ($report['rows'] as $item)
            @php
                $qty = (float) $item['quantity_on_hand'];
                $cost = (float) $item['unit_cost_avg'];
                $val = (float) $item['value'];
                $product = $item['product'];
                $itemStatusKey = $qty > 5 ? 'in_stock' : ($qty > 0 ? 'low_stock' : 'out_of_stock');
            @endphp
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:border-amber-300 dark:hover:border-amber-600/50 hover:shadow-xs transition flex flex-col justify-between group overflow-hidden"
                 x-show="statusFilter === 'all' || statusFilter === '{{ $itemStatusKey }}'">
                {{-- Top Card Content --}}
                <div class="p-2.5 sm:p-3 space-y-2">
                    {{-- Header: Product Name + Status Badge --}}
                    <div class="flex items-start justify-between gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                        <div class="min-w-0 flex-1">
                            <div class="font-black text-xs sm:text-sm text-slate-900 dark:text-white line-clamp-1">
                                {{ $product?->name ?? '—' }}
                            </div>
                            @if ($product?->category)
                                <span class="text-[10px] text-slate-400 block mt-0.5">{{ $product->category->name }}</span>
                            @endif
                        </div>

                        {{-- Status Pill --}}
                        @if ($qty > 5)
                            <span class="shrink-0 inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                <span>✅</span>
                                <span>{{ __('messages.reports_stock_in_stock') }}</span>
                            </span>
                        @elseif ($qty > 0)
                            <span class="shrink-0 inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                <span>⚠️</span>
                                <span>{{ __('messages.low_stock') }}</span>
                            </span>
                        @else
                            <span class="shrink-0 inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                <span>❌</span>
                                <span>{{ __('messages.reports_stock_out_of_stock') }}</span>
                            </span>
                        @endif
                    </div>

                    {{-- SKU Pill --}}
                    <div class="flex items-center justify-between text-[10px] font-mono text-slate-500 dark:text-slate-400">
                        <span class="font-bold uppercase text-slate-400">SKU:</span>
                        <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold select-all">{{ $product?->sku ?: '—' }}</span>
                    </div>

                    {{-- Quantity & Valuation Hero Box --}}
                    <div class="p-2 rounded-md border {{ $qty > 0 ? 'bg-emerald-50/30 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-900/40' : 'bg-rose-50/30 dark:bg-rose-950/20 border-rose-100 dark:border-rose-900/40' }} space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                {{ __('messages.on_hand_qty') }}:
                            </span>
                            <span class="font-black font-mono text-xs sm:text-sm {{ $qty > 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $fmtQty($qty) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-200/50 dark:border-slate-700/50 font-mono">
                            <span class="text-[10px] text-slate-400 font-sans">
                                {{ __('messages.stock_value') }} (@ {{ number_format($cost) }} Ks)
                            </span>
                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                Ks {{ number_format($val) }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Card Action Footer --}}
                <div class="p-2 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                    @if ($product)
                        <a href="{{ route('store.admin.stock_ledger.bin_card', ['store_slug' => $store->slug, 'product' => $product->id]) }}"
                           class="w-full text-center px-2.5 py-1 rounded text-xs font-bold bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-950/60 dark:text-violet-300 transition inline-flex items-center justify-center gap-1 active:scale-95 shadow-2xs">
                            <span>📑</span>
                            <span>{{ __('messages.reports_stock_view_ledger') }}</span>
                            <span>&rarr;</span>
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full p-8 text-center text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 rounded-lg border border-dashed border-slate-200 dark:border-slate-800 shadow-2xs">
                <span class="text-3xl mb-2 block">📦</span>
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('messages.reports_stock_no_data') }}</p>
            </div>
        @endforelse
    </div>

</div>
@endsection
