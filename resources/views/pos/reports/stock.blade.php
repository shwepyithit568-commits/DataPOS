@extends('layouts.admin.app')

@section('title', __('messages.sidebar_stock_balance') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5">

    {{-- 1. Compact Page Header --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
            <div class="min-w-0">
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/60 border border-amber-200/80 dark:border-amber-800 text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-amber-700 dark:text-amber-300">
                    <span>📦 {{ __('messages.sidebar_inventory') }}</span>
                    <span class="text-amber-400">·</span>
                    <span>{{ __('messages.sidebar_stock_balance') }}</span>
                </div>
                <h1 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-slate-100 font-outfit mt-0.5 truncate">
                    {{ __('messages.sidebar_stock_balance') }}
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 truncate">
                    {{ $store->name }} · {{ __('messages.reports_stock_subtitle') }}
                </p>
            </div>

            {{-- Fast Quick Action Links --}}
            <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                <a href="{{ route('pos.adjustments.index', ['store_slug' => $store->slug]) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 text-xs font-bold transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>{{ __('messages.sidebar_stock_adjustments') }}</span>
                </a>

                <a href="{{ route('store.admin.stock_ledger.index', ['store_slug' => $store->slug]) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 text-xs font-bold transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    <span>{{ __('messages.sidebar_stock_ledger') }}</span>
                </a>

                <a href="{{ route('store.admin.inventory_valuation.index', ['store_slug' => $store->slug]) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-100 dark:hover:bg-white dark:text-slate-900 text-xs font-bold transition shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>{{ __('messages.sidebar_inventory_valuation') }}</span>
                </a>
            </div>
        </div>
    </div>

    {{-- 2. KPI Summary Cards (4 Responsive Metrics) --}}
    @php
        $totalItemsCount = count($report['rows']);
        $zeroStockCount = $report['rows']->filter(fn($r) => (float)$r['quantity_on_hand'] <= 0)->count();
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
        {{-- Total SKUs --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider">{{ __('messages.reports_stock_total_skus') }}</span>
                <span class="p-1 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <p class="text-base sm:text-xl font-black font-mono mt-1 tabular-nums text-slate-900 dark:text-slate-100">
                {{ number_format($totalItemsCount) }}
            </p>
        </div>

        {{-- Total On-Hand Units --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider">{{ __('messages.reports_total_units') }}</span>
                <span class="p-1 rounded-md bg-emerald-50 dark:bg-emerald-950/80 text-emerald-600">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </span>
            </div>
            <p class="text-base sm:text-xl font-black font-mono mt-1 tabular-nums text-emerald-700 dark:text-emerald-300">
                {{ number_format((float) $report['total_units'], 3) }}
            </p>
        </div>

        {{-- Total Valuation --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="flex items-center justify-between text-sky-600 dark:text-sky-400">
                <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider">{{ __('messages.reports_stock_value') }}</span>
                <span class="p-1 rounded-md bg-sky-50 dark:bg-sky-950/80 text-sky-600">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p class="text-base sm:text-xl font-black font-mono mt-1 tabular-nums text-sky-700 dark:text-sky-300">
                Ks {{ number_format((float) $report['total_value']) }}
            </p>
        </div>

        {{-- Zero / Out of Stock Alert --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="flex items-center justify-between {{ $zeroStockCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500' }}">
                <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider">{{ __('messages.reports_stock_low_stock') }}</span>
                <span class="p-1 rounded-md {{ $zeroStockCount > 0 ? 'bg-amber-50 dark:bg-amber-950/80 text-amber-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <p class="text-base sm:text-xl font-black font-mono mt-1 tabular-nums {{ $zeroStockCount > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-slate-900 dark:text-slate-100' }}">
                {{ number_format($zeroStockCount) }}
            </p>
        </div>
    </div>

    {{-- 3. Search & Export Toolbar --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 shadow-2xs">
        <form method="GET" action="{{ url('/store/' . $store->slug . '/pos/reports/stock') }}"
              class="flex flex-wrap items-center justify-between gap-2">
            
            {{-- Search Bar --}}
            <div class="flex items-center gap-1.5 flex-1 min-w-[240px] max-w-md">
                <div class="relative w-full">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('messages.reports_search') }}"
                           class="w-full pl-8 pr-8 py-1.5 border border-slate-200 dark:border-slate-700 rounded-lg text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    @if (request('q'))
                        <a href="{{ url('/store/' . $store->slug . '/pos/reports/stock') }}"
                           class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold text-xs">×</a>
                    @endif
                </div>

                <button type="submit" class="rounded-lg px-3.5 py-1.5 text-xs font-bold bg-sky-600 hover:bg-sky-500 text-white transition shadow-2xs shrink-0">
                    {{ __('messages.reports_filter') }}
                </button>
            </div>

            {{-- Export & Print Actions --}}
            <div class="flex items-center gap-1.5 shrink-0 ml-auto">
                <a href="{{ route('pos.reports.stock.export', ['store_slug' => $store->slug, 'q' => request('q'), 'format' => 'xlsx']) }}"
                   class="rounded-lg px-2.5 py-1.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition inline-flex items-center gap-1.5"
                   title="Export Excel Spreadsheet (.xlsx)">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" /></svg>
                    <span>Excel</span>
                </a>

                <a href="{{ route('pos.reports.stock.export', ['store_slug' => $store->slug, 'q' => request('q'), 'format' => 'csv']) }}"
                   class="rounded-lg px-2.5 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-1.5"
                   title="Export CSV Data (.csv)">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    <span>CSV</span>
                </a>

                <button type="button" @click="window.print()"
                        class="rounded-lg px-2.5 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-1.5"
                        title="Print Stock Balance Report">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span class="hidden sm:inline">{{ __('messages.print') }}</span>
                </button>
            </div>
        </form>
    </div>

    {{-- 4. Dense Stock Ledger Spreadsheet Table --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden">
        {{-- Mobile Swipe Hint Bar --}}
        <div class="sm:hidden px-2.5 py-1 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200/60 dark:border-slate-800 text-[10px] text-slate-400 flex items-center justify-between">
            <span class="flex items-center gap-1">
                <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Swipe horizontally to view all columns</span>
            </span>
            <span class="font-mono text-[9px] uppercase tracking-wider text-slate-400">Scrollable</span>
        </div>

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
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 border-collapse min-w-[760px]">
                    <thead class="sticky top-0 bg-slate-50/90 dark:bg-slate-800/80 backdrop-blur-xs text-[10px] sm:text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-black border-b border-slate-200/80 dark:border-slate-800">
                        <tr>
                            <th class="px-3 py-2.5 w-12 text-center">#</th>
                            <th class="px-3 py-2.5">{{ __('messages.product') }}</th>
                            <th class="px-3 py-2.5">{{ __('messages.sku') }}</th>
                            <th class="px-3 py-2.5 text-center">{{ __('messages.status') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.on_hand_qty') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.average_cost') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.stock_value') }}</th>
                            <th class="px-3 py-2.5 text-center w-24">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach ($report['rows'] as $index => $row)
                            @php
                                $qty = (float) $row['quantity_on_hand'];
                                $cost = (float) $row['unit_cost_avg'];
                                $val = (float) $row['value'];
                                $product = $row['product'];
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                <td class="px-3 py-2 text-center text-slate-400 font-mono text-[11px]">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-3 py-2">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">
                                        {{ $product?->name ?? '—' }}
                                    </div>
                                    @if ($product?->category)
                                        <div class="text-[10px] text-slate-400 font-medium mt-0.5">
                                            {{ $product->category->name }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 font-mono text-slate-500">
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[11px]">
                                        {{ $product?->sku ?: '—' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @if ($qty > 5)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300">
                                            {{ __('messages.reports_stock_in_stock') }}
                                        </span>
                                    @elseif ($qty > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300">
                                            {{ __('messages.low_stock') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300">
                                            {{ __('messages.reports_stock_out_of_stock') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right font-mono font-bold tabular-nums {{ $qty <= 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' }}">
                                    {{ number_format($qty, 3) }}
                                </td>
                                <td class="px-3 py-2 text-right font-mono tabular-nums text-slate-600 dark:text-slate-400">
                                    Ks {{ number_format($cost) }}
                                </td>
                                <td class="px-3 py-2 text-right font-mono font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                    Ks {{ number_format($val) }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @if ($product)
                                        <a href="{{ route('store.admin.stock_ledger.index', ['store_slug' => $store->slug, 'product_id' => $product->id]) }}"
                                           class="inline-flex items-center gap-1 px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[11px] font-bold transition shadow-2xs"
                                           title="{{ __('messages.reports_stock_view_ledger') }}">
                                            <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            <span>{{ __('messages.reports_stock_view_ledger') }}</span>
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 dark:bg-slate-800/90 font-bold border-t-2 border-slate-200 dark:border-slate-700 text-xs">
                        <tr>
                            <td colspan="4" class="px-3 py-2.5 text-right font-black uppercase text-slate-700 dark:text-slate-300">
                                {{ __('messages.total') }}:
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-black text-emerald-700 dark:text-emerald-400 tabular-nums">
                                {{ number_format((float) $report['total_units'], 3) }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono text-slate-400 tabular-nums">
                                —
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-black text-sky-700 dark:text-sky-400 tabular-nums">
                                Ks {{ number_format((float) $report['total_value']) }}
                            </td>
                            <td class="px-3 py-2.5"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
