@extends('layouts.admin.app')

@section('title', __('messages.reports_sales') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5">

    {{-- 1. Compact Page Header --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-slate-100 font-outfit truncate">
                    {{ __('messages.reports_sales') }}
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 truncate">
                    {{ $store->name }} · {{ __('messages.reports_sales_subtitle') }}
                </p>
            </div>

            {{-- Header Actions --}}
            <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                <a href="{{ route('pos.index', ['store_slug' => $store->slug]) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 text-xs font-bold transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>{{ __('messages.pos_sale') }}</span>
                </a>

                <a href="{{ route('pos.reports.cash', ['store_slug' => $store->slug]) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 text-xs font-bold transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>{{ __('messages.reports_cash') }}</span>
                </a>

                <a href="{{ route('store.admin.sales_analytics.index', ['store_slug' => $store->slug]) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-100 dark:hover:bg-white dark:text-slate-900 text-xs font-bold transition shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>{{ __('messages.sidebar_sales_analytics') }}</span>
                </a>
            </div>
        </div>
    </div>

    {{-- 2. Filter & Action Toolbar --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 shadow-2xs space-y-2">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2">
            
            {{-- Quick Date Presets --}}
            <div class="flex items-center gap-1 overflow-x-auto pb-1 lg:pb-0 text-xs">
                @php
                    $presets = [
                        'today'      => __('messages.period_today'),
                        'yesterday'  => __('messages.period_yesterday'),
                        '7days'      => __('messages.7days'),
                        'this_month' => __('messages.period_this_month'),
                        'last_month' => __('messages.period_last_month'),
                    ];
                @endphp
                @foreach ($presets as $pKey => $pLabel)
                    @php $isCurrentPreset = (($preset ?? '') === $pKey); @endphp
                    <a href="{{ route('pos.reports.sales', ['store_slug' => $store->slug, 'preset' => $pKey, 'cashier_id' => request('cashier_id')]) }}"
                       class="px-2.5 py-1 rounded-md font-bold whitespace-nowrap text-[11px] sm:text-xs transition {{ $isCurrentPreset ? 'bg-sky-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                        {{ $pLabel }}
                    </a>
                @endforeach
            </div>

            {{-- Custom Form + Actions --}}
            <form method="GET" action="{{ route('pos.reports.sales', ['store_slug' => $store->slug]) }}"
                  class="flex flex-wrap items-center gap-1.5">
                <input type="hidden" name="preset" value="custom">

                {{-- Cashier Filter --}}
                <select name="cashier_id" onchange="this.form.submit()" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500 shadow-2xs">
                    <option value="">{{ __('messages.reports_all_cashiers') }}</option>
                    @foreach ($cashiers as $c)
                        <option value="{{ $c->id }}" @selected(request('cashier_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>

                {{-- Custom Date Inputs --}}
                <div class="flex items-center gap-1">
                    <input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ today()->toDateString() }}"
                           class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500 shadow-2xs">
                    <span class="text-xs text-slate-400">—</span>
                    <input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ today()->toDateString() }}"
                           class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500 shadow-2xs">
                </div>

                {{-- Filter Submit Button --}}
                <button type="submit" class="rounded-lg px-3 py-1 text-xs font-bold bg-sky-600 hover:bg-sky-500 text-white transition shadow-2xs">
                    {{ __('messages.reports_filter') }}
                </button>

                {{-- Export Actions (Excel & CSV) & Print --}}
                <div class="flex items-center gap-1 shrink-0 ml-auto">
                    <a href="{{ route('pos.reports.sales.export', ['store_slug' => $store->slug, 'preset' => $preset ?? 'custom', 'cashier_id' => request('cashier_id'), 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'format' => 'xlsx']) }}"
                       class="rounded-lg px-2.5 py-1 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition inline-flex items-center gap-1"
                       title="Export Excel (.xlsx)">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" /></svg>
                        <span>Excel</span>
                    </a>

                    <a href="{{ route('pos.reports.sales.export', ['store_slug' => $store->slug, 'preset' => $preset ?? 'custom', 'cashier_id' => request('cashier_id'), 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'format' => 'csv']) }}"
                       class="rounded-lg px-2.5 py-1 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-1"
                       title="Export CSV (.csv)">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        <span>CSV</span>
                    </a>

                    <button type="button" @click="window.print()"
                            class="rounded-lg px-2.5 py-1 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-1"
                            title="Print Sales Report">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span class="hidden sm:inline">{{ __('messages.print') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 3. KPI Metric Cards (4 Hairline Grid Cards) --}}
    @php
        $totalItemsSold = $report['sales']->sum(fn($s) => $s->items->sum('quantity'));
        $totalDiscount = $report['sales']->sum(fn($s) => (float) $s->discount);
        $totalTax = $report['sales']->sum(fn($s) => (float) $s->tax);
        $aov = $report['count'] > 0 ? round(((float) $report['total']) / $report['count'], 2) : 0;
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-2.5">
        
        {{-- Total Sales Revenue --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="flex items-center justify-between text-sky-600 dark:text-sky-400">
                <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider">{{ __('messages.reports_grand_total') }}</span>
                <span class="p-1 rounded-md bg-sky-50 dark:bg-sky-950/80 text-sky-600">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
            </div>
            <div class="text-base sm:text-xl font-black text-sky-900 dark:text-sky-100 font-mono tracking-tight mt-1 tabular-nums">
                Ks {{ number_format((float) $report['total']) }}
            </div>
            <div class="text-[10px] text-sky-600/80 dark:text-sky-400 font-semibold mt-0.5 truncate">{{ __('messages.pl_net_revenue') }}</div>
        </div>

        {{-- Receipts Count --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider">{{ __('messages.reports_sale_count') }}</span>
                <span class="p-1 rounded-md bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </span>
            </div>
            <div class="text-base sm:text-xl font-black text-slate-900 dark:text-slate-100 font-mono tracking-tight mt-1 tabular-nums">
                {{ number_format($report['count']) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5 truncate">{{ __('messages.receipts') }}</div>
        </div>

        {{-- Total Items Dispatched --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider">{{ __('messages.items_sold') }}</span>
                <span class="p-1 rounded-md bg-emerald-50 dark:bg-emerald-950/80 text-emerald-600">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                </span>
            </div>
            <div class="text-base sm:text-xl font-black text-emerald-900 dark:text-emerald-100 font-mono tracking-tight mt-1 tabular-nums">
                {{ number_format($totalItemsSold) }}
            </div>
            <div class="text-[10px] text-emerald-600/80 dark:text-emerald-400 mt-0.5 truncate">{{ __('messages.units_dispatched') }}</div>
        </div>

        {{-- Average Order Value (AOV) --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="flex items-center justify-between text-purple-600 dark:text-purple-400">
                <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider">{{ __('messages.aov_metric') }}</span>
                <span class="p-1 rounded-md bg-purple-50 dark:bg-purple-950/80 text-purple-600">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </span>
            </div>
            <div class="text-base sm:text-xl font-black text-slate-900 dark:text-slate-100 font-mono tracking-tight mt-1 tabular-nums">
                Ks {{ number_format($aov) }}
            </div>
            <div class="text-[10px] text-purple-600/80 dark:text-purple-400 mt-0.5 truncate">{{ __('messages.avg_per_ticket') }}</div>
        </div>
    </div>

    {{-- 4. Payment Methods Summary Cards --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs space-y-2">
        <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 font-mono">
            {{ __('messages.reports_method_totals') }}
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
            @forelse ($report['methods'] as $method => $amount)
                <div class="p-2.5 rounded-lg border border-slate-200/60 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-0.5 shadow-2xs">
                    <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        {{ $method }}
                    </div>
                    <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-mono tabular-nums">
                        Ks {{ number_format((float) $amount) }}
                    </div>
                </div>
            @empty
                <div class="col-span-full text-xs text-slate-400 py-1">
                    {{ __('messages.no_data_available') }}
                </div>
            @endforelse
        </div>
    </div>

    {{-- 5. Sales Transactions Ledger Table --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40">
            <h2 class="font-bold text-slate-900 dark:text-slate-100 font-outfit text-xs sm:text-sm flex items-center gap-2">
                <span>{{ __('messages.sales_trend_timeline') }}</span>
                <span class="px-2 py-0.5 rounded-full bg-slate-200/70 dark:bg-slate-700 text-[10px] font-mono text-slate-700 dark:text-slate-300">
                    {{ $report['sales']->count() }} {{ __('messages.transactions') }}
                </span>
            </h2>
        </div>

        {{-- Mobile Swipe Hint Bar --}}
        <div class="sm:hidden px-2.5 py-1 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200/60 dark:border-slate-800 text-[10px] text-slate-400 flex items-center justify-between">
            <span class="flex items-center gap-1">
                <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Swipe horizontally to view all columns</span>
            </span>
            <span class="font-mono text-[9px] uppercase tracking-wider text-slate-400">Scrollable</span>
        </div>

        @if ($report['sales']->isEmpty())
            <div class="py-12 text-center text-slate-400 dark:text-slate-500 text-xs space-y-2">
                <div class="text-3xl">🧾</div>
                <p class="font-semibold">{{ __('messages.reports_no_data') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 border-collapse min-w-[840px]">
                    <thead class="sticky top-0 bg-slate-50/90 dark:bg-slate-800/80 backdrop-blur-xs text-[10px] sm:text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-black border-b border-slate-200/80 dark:border-slate-800">
                        <tr>
                            <th class="px-3 py-2.5 w-12 text-center">#</th>
                            <th class="px-3 py-2.5">{{ __('messages.receipt') }}</th>
                            <th class="px-3 py-2.5">{{ __('messages.reports_date') }}</th>
                            <th class="px-3 py-2.5">{{ __('messages.cashier') }}</th>
                            <th class="px-3 py-2.5">{{ __('messages.customer') }}</th>
                            <th class="px-3 py-2.5 text-center">{{ __('messages.reports_items') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.subtotal') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.discount') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.tax') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.total') }}</th>
                            <th class="px-3 py-2.5">{{ __('messages.payment_methods') }}</th>
                            <th class="px-3 py-2.5 text-center">{{ __('messages.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach ($report['sales'] as $index => $sale)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                <td class="px-3 py-2 text-center text-slate-400 font-mono text-[11px]">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <a href="{{ url('/store/' . $store->slug . '/pos/sales/' . $sale->id . '/receipt') }}"
                                       target="_blank"
                                       class="font-mono font-bold text-sky-600 dark:text-sky-400 hover:underline inline-flex items-center gap-1">
                                        <span>{{ $sale->receipt_number ?: $sale->invoice_no }}</span>
                                        <svg class="w-3 h-3 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    </a>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-slate-600 dark:text-slate-300 font-mono text-[11px]">
                                    {{ $sale->posted_at?->format('d M Y, H:i') }}
                                </td>
                                <td class="px-3 py-2 font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $sale->cashier?->name ?? $sale->creator?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-slate-700 dark:text-slate-300">
                                    {{ $sale->customer?->name ?? __('messages.reports_walk_in_customer') }}
                                </td>
                                <td class="px-3 py-2 text-center font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                    {{ $sale->items->sum('quantity') }}
                                </td>
                                <td class="px-3 py-2 text-right font-mono tabular-nums text-slate-600 dark:text-slate-400">
                                    {{ number_format((float) $sale->subtotal) }}
                                </td>
                                <td class="px-3 py-2 text-right font-mono tabular-nums {{ (float) $sale->discount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                                    {{ (float) $sale->discount > 0 ? '-' . number_format((float) $sale->discount) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-right font-mono tabular-nums text-slate-400">
                                    {{ (float) $sale->tax > 0 ? number_format((float) $sale->tax) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-right font-black text-slate-900 dark:text-slate-100 font-mono text-xs sm:text-sm tabular-nums">
                                    Ks {{ number_format((float) $sale->total) }}
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse ($sale->payments as $pm)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 uppercase">
                                                {{ $pm->method }}
                                            </span>
                                        @empty
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 uppercase">
                                                {{ $sale->payment_method ?? 'Cash' }}
                                            </span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @if ($sale->status === 'posted')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                            {{ __('messages.completed') }}
                                        </span>
                                    @elseif ($sale->status === 'refunded' || $sale->status === 'partially_refunded')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                            {{ __('messages.refunded') }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            {{ ucfirst($sale->status) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 dark:bg-slate-800/90 font-bold border-t-2 border-slate-200 dark:border-slate-700 text-xs">
                        <tr>
                            <td colspan="5" class="px-3 py-2.5 text-right font-black uppercase text-slate-700 dark:text-slate-300">
                                {{ __('messages.total') }}:
                            </td>
                            <td class="px-3 py-2.5 text-center font-mono font-black text-emerald-700 dark:text-emerald-400 tabular-nums">
                                {{ number_format($totalItemsSold) }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono text-slate-600 dark:text-slate-300 tabular-nums">
                                {{ number_format((float) $report['sales']->sum('subtotal')) }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono text-amber-600 dark:text-amber-400 tabular-nums">
                                {{ $totalDiscount > 0 ? '-' . number_format($totalDiscount) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono text-slate-400 tabular-nums">
                                {{ $totalTax > 0 ? number_format($totalTax) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-black text-sky-700 dark:text-sky-400 tabular-nums">
                                Ks {{ number_format((float) $report['total']) }}
                            </td>
                            <td colspan="2" class="px-3 py-2.5"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
