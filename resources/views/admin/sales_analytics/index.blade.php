@extends('layouts.admin.app')

@section('title', __('messages.sidebar_sales_analytics') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 font-outfit">
                    {{ __('messages.sales_analytics_title') }}
                </h1>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                    {{ $report['date_range']['from'] }} ~ {{ $report['date_range']['to'] }} ({{ $report['date_range']['days'] }} {{ __('messages.days') }})
                </span>
                @if($channel !== 'all')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                        {{ $channel === 'pos' ? __('messages.channel_pos_only') : __('messages.channel_online_only') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2">
            <button type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>{{ __('messages.print_slip') }}</span>
            </button>

            <a href="{{ route('store.admin.sales_analytics.export', ['store_slug' => $store->slug, 'preset' => $preset, 'channel' => $channel, 'from' => request('from'), 'to' => request('to')]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-600/20 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>{{ __('messages.export_csv') }}</span>
            </a>
        </div>
    </div>

    {{-- Filter Toolbar: Presets & Channels --}}
    <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-3">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            
            {{-- Preset Pills --}}
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 text-xs">
                @php
                    $presetsList = [
                        'today' => __('messages.period_today'),
                        'yesterday' => __('messages.period_yesterday'),
                        '7days' => __('messages.7days'),
                        '30days' => __('messages.30days'),
                        'this_month' => __('messages.period_this_month'),
                        'last_month' => __('messages.period_last_month'),
                        'this_year' => __('messages.period_this_year'),
                    ];
                @endphp
                @foreach ($presetsList as $key => $label)
                    <a href="{{ route('store.admin.sales_analytics.index', ['store_slug' => $store->slug, 'preset' => $key, 'channel' => $channel]) }}"
                       class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition {{ $preset === $key ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-500/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Channel Selector + Custom Date Form --}}
            <form method="GET" action="{{ route('store.admin.sales_analytics.index', ['store_slug' => $store->slug]) }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="preset" value="custom">

                {{-- Channel Dropdown --}}
                <select name="channel" onchange="this.form.submit()" class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    <option value="all" {{ $channel === 'all' ? 'selected' : '' }}>{{ __('messages.all_channels') }}</option>
                    <option value="pos" {{ $channel === 'pos' ? 'selected' : '' }}>{{ __('messages.channel_pos_counter') }}</option>
                    <option value="online" {{ $channel === 'online' ? 'selected' : '' }}>{{ __('messages.channel_online_web') }}</option>
                </select>

                <div class="flex items-center gap-1.5">
                    <input type="date"
                           name="from"
                           value="{{ $from->toDateString() }}"
                           class="px-2.5 py-1 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    <span class="text-xs text-slate-400">—</span>
                    <input type="date"
                           name="to"
                           value="{{ $to->toDateString() }}"
                           class="px-2.5 py-1 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                </div>
                <button type="submit" class="px-3.5 py-1 text-xs font-bold rounded-xl bg-slate-800 hover:bg-slate-700 text-white dark:bg-slate-700 dark:hover:bg-slate-600 transition">
                    {{ __('messages.filter') }}
                </button>
            </form>
        </div>
    </div>

    {{-- Core KPI Summary Cards (6 Cards) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">

        {{-- 1. Net Sales Revenue --}}
        <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.pl_net_revenue') }}</span>
                <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
            </div>
            <div>
                <div class="text-lg font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                    Ks {{ number_format($report['kpi']['net_sales']) }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px]">
                    @if ($comparison['revenue_growth'] >= 0)
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center">↑ {{ $comparison['revenue_growth'] }}%</span>
                    @else
                        <span class="font-bold text-rose-600 dark:text-rose-400 flex items-center">↓ {{ abs($comparison['revenue_growth']) }}%</span>
                    @endif
                    <span class="text-slate-400 dark:text-slate-500">{{ __('messages.vs_prev_period') }}</span>
                </div>
            </div>
        </div>

        {{-- 2. Total Orders / Invoices --}}
        <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.total_orders') }}</span>
                <span class="p-1.5 rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </span>
            </div>
            <div>
                <div class="text-lg font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                    {{ number_format($report['kpi']['total_orders']) }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px]">
                    @if ($comparison['orders_growth'] >= 0)
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">↑ {{ $comparison['orders_growth'] }}%</span>
                    @else
                        <span class="font-bold text-rose-600 dark:text-rose-400">↓ {{ abs($comparison['orders_growth']) }}%</span>
                    @endif
                    <span class="text-slate-400 dark:text-slate-500">{{ __('messages.receipts') }}</span>
                </div>
            </div>
        </div>

        {{-- 3. Items Sold Quantity --}}
        <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.items_sold') }}</span>
                <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                </span>
            </div>
            <div>
                <div class="text-lg font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                    {{ number_format($report['kpi']['total_items']) }}
                </div>
                <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                    {{ __('messages.units_dispatched') }}
                </div>
            </div>
        </div>

        {{-- 4. Average Order Value (AOV) --}}
        <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.aov_metric') }}</span>
                <span class="p-1.5 rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </span>
            </div>
            <div>
                <div class="text-lg font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                    Ks {{ number_format($report['kpi']['aov']) }}
                </div>
                <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                    {{ __('messages.avg_per_ticket') }}
                </div>
            </div>
        </div>

        {{-- 5. Gross Profit & Margin % --}}
        <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.pl_gross_profit') }}</span>
                <span class="p-1.5 rounded-lg bg-teal-50 text-teal-600 dark:bg-teal-950/60 dark:text-teal-300 font-black text-[10px]">
                    {{ $report['kpi']['gross_margin'] }}%
                </span>
            </div>
            <div>
                <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 font-outfit tabular-nums">
                    Ks {{ number_format($report['kpi']['gross_profit']) }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px]">
                    @if ($comparison['profit_growth'] >= 0)
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">↑ {{ $comparison['profit_growth'] }}%</span>
                    @else
                        <span class="font-bold text-rose-600 dark:text-rose-400">↓ {{ abs($comparison['profit_growth']) }}%</span>
                    @endif
                    <span class="text-slate-400 dark:text-slate-500">{{ __('messages.gross_margin') }}</span>
                </div>
            </div>
        </div>

        {{-- 6. Discounts Given --}}
        <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.pl_discounts_given') }}</span>
                <span class="p-1.5 rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                </span>
            </div>
            <div>
                <div class="text-lg font-black text-rose-600 dark:text-rose-400 font-outfit tabular-nums">
                    Ks {{ number_format($report['kpi']['discounts']) }}
                </div>
                <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                    {{ __('messages.promos_price_cuts') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Main Timeline Revenue Chart & Channel Mix --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Daily Timeline Chart (2 Cols) --}}
        <div class="lg:col-span-2 p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.sales_trend_timeline') }}</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('messages.daily_sales_revenue_orders') }}</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded bg-indigo-600"></span>
                        <span class="text-slate-600 dark:text-slate-300 font-medium">{{ __('messages.revenue') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded bg-sky-400"></span>
                        <span class="text-slate-600 dark:text-slate-300 font-medium">{{ __('messages.total_orders') }}</span>
                    </div>
                </div>
            </div>

            {{-- Timeline Bar Chart Visual --}}
            @php
                $timelineData = $report['timeline']['series'];
                $maxRev = $report['timeline']['max_revenue'];
            @endphp

            @if(count($timelineData) === 0 || $report['kpi']['net_sales'] == 0)
                <div class="py-16 text-center text-slate-400 dark:text-slate-500 text-sm">
                    {{ __('messages.no_sales_in_period') }}
                </div>
            @else
                <div class="relative pt-6">
                    <div class="h-56 flex items-end gap-1.5 sm:gap-2 overflow-x-auto pb-4 border-b border-slate-100 dark:border-slate-800">
                        @foreach ($timelineData as $point)
                            @php
                                $heightPercent = max(4, round(($point['revenue'] / $maxRev) * 100));
                            @endphp
                            <div class="flex-1 min-w-[20px] sm:min-w-[28px] group relative flex flex-col items-center justify-end h-full">
                                {{-- Tooltip --}}
                                <div class="absolute bottom-full mb-2 hidden group-hover:flex flex-col items-center z-20 pointer-events-none whitespace-nowrap">
                                    <div class="px-2.5 py-1.5 rounded-lg bg-slate-900 text-white text-[11px] font-medium shadow-xl border border-slate-700">
                                        <div class="font-bold text-slate-200">{{ $point['date'] }} ({{ $point['short_day'] }})</div>
                                        <div class="text-emerald-400 font-bold">Ks {{ number_format($point['revenue']) }}</div>
                                        <div class="text-sky-300">{{ $point['orders'] }} {{ __('messages.receipts') }}</div>
                                    </div>
                                    <div class="w-2 h-2 bg-slate-900 rotate-45 -mt-1"></div>
                                </div>

                                {{-- Bar Container --}}
                                <div class="w-full bg-indigo-500/15 dark:bg-indigo-500/10 rounded-t-md relative flex items-end justify-center transition hover:bg-indigo-500/25"
                                     style="height: {{ $heightPercent }}%;">
                                    <div class="w-full bg-gradient-to-t from-indigo-600 to-sky-500 rounded-t-md" style="height: 100%;"></div>
                                </div>
                                {{-- Date label --}}
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 truncate w-full text-center">
                                    {{ $point['short_day'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Channel Mix & Share (1 Col) --}}
        <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4 flex flex-col justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.channel_breakdown') }}</h2>
                <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('messages.pos_vs_online_sales') }}</p>
            </div>

            <div class="space-y-4 my-auto">
                {{-- POS Counter --}}
                <div class="p-3.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                            {{ __('messages.channel_pos_counter') }}
                        </span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $report['channels']['pos']['percent'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between text-xs">
                        <span class="text-slate-500 dark:text-slate-400">{{ $report['channels']['pos']['orders'] }} {{ __('messages.receipts') }}</span>
                        <span class="font-black text-slate-900 dark:text-slate-100 tabular-nums">Ks {{ number_format($report['channels']['pos']['revenue']) }}</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                        <div class="h-full bg-indigo-600 rounded-full" style="width: {{ $report['channels']['pos']['percent'] }}%;"></div>
                    </div>
                </div>

                {{-- Online Storefront --}}
                <div class="p-3.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                            {{ __('messages.channel_online_web') }}
                        </span>
                        <span class="font-bold text-sky-600 dark:text-sky-400">{{ $report['channels']['online']['percent'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between text-xs">
                        <span class="text-slate-500 dark:text-slate-400">{{ $report['channels']['online']['orders'] }} {{ __('messages.receipts') }}</span>
                        <span class="font-black text-slate-900 dark:text-slate-100 tabular-nums">Ks {{ number_format($report['channels']['online']['revenue']) }}</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                        <div class="h-full bg-sky-500 rounded-full" style="width: {{ $report['channels']['online']['percent'] }}%;"></div>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-400 dark:text-slate-500">
                💡 {{ __('messages.channel_mix_hint') }}
            </div>
        </div>
    </div>

    {{-- Peak Sales Hours (0-23h) & Day of Week Pattern --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- 24-Hour Peak Hourly Distribution (2 Cols) --}}
        <div class="lg:col-span-2 p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.peak_sales_hours') }}</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('messages.hourly_volume_heatmap') }}</p>
                </div>
                @if ($report['hourly']['peak_hour'] && $report['hourly']['peak_hour']['revenue'] > 0)
                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-xs font-bold text-amber-700 dark:text-amber-300">
                        ⚡ {{ __('messages.peak_hour') }}: {{ $report['hourly']['peak_hour']['display'] }} (Ks {{ number_format($report['hourly']['peak_hour']['revenue']) }})
                    </div>
                @endif
            </div>

            @php
                $hourlyData = $report['hourly']['hours'];
                $maxHourRev = $report['hourly']['max_revenue'];
            @endphp

            <div class="relative pt-4">
                <div class="h-44 flex items-end gap-1 overflow-x-auto pb-4 border-b border-slate-100 dark:border-slate-800">
                    @foreach ($hourlyData as $hPoint)
                        @php
                            $hHeight = max(4, round(($hPoint['revenue'] / $maxHourRev) * 100));
                            $isPeak = ($report['hourly']['peak_hour'] && $report['hourly']['peak_hour']['hour'] === $hPoint['hour'] && $hPoint['revenue'] > 0);
                        @endphp
                        <div class="flex-1 min-w-[16px] sm:min-w-[20px] group relative flex flex-col items-center justify-end h-full">
                            {{-- Tooltip --}}
                            <div class="absolute bottom-full mb-2 hidden group-hover:flex flex-col items-center z-20 pointer-events-none whitespace-nowrap">
                                <div class="px-2.5 py-1.5 rounded-lg bg-slate-900 text-white text-[11px] font-medium shadow-xl border border-slate-700">
                                    <div class="font-bold text-slate-200">{{ $hPoint['display'] }} ({{ $hPoint['label'] }})</div>
                                    <div class="text-emerald-400 font-bold">Ks {{ number_format($hPoint['revenue']) }}</div>
                                    <div class="text-sky-300">{{ $hPoint['orders'] }} {{ __('messages.receipts') }}</div>
                                </div>
                                <div class="w-2 h-2 bg-slate-900 rotate-45 -mt-1"></div>
                            </div>

                            {{-- Bar --}}
                            <div class="w-full rounded-t-md transition {{ $isPeak ? 'bg-amber-500' : 'bg-indigo-500/30 dark:bg-indigo-500/20 hover:bg-indigo-500/50' }}"
                                 style="height: {{ $hHeight }}%;"></div>
                            <span class="text-[9px] text-slate-400 dark:text-slate-500 mt-1">
                                {{ $hPoint['hour'] % 3 === 0 ? $hPoint['hour'] . 'h' : '' }}
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500 mt-2">
                    <span>12 AM (00:00)</span>
                    <span>12 PM (12:00)</span>
                    <span>11 PM (23:00)</span>
                </div>
            </div>
        </div>

        {{-- Busiest Day of Week Pattern (1 Col) --}}
        <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-3">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.busiest_days_of_week') }}</h2>
                <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('messages.traffic_by_day') }}</p>
            </div>

            @php
                $dowData = $report['day_of_week']['days'];
                $maxDowRev = $report['day_of_week']['max_revenue'];
            @endphp

            <div class="space-y-2.5 pt-2">
                @foreach ($dowData as $day)
                    @php
                        $dPercent = max(2, round(($day['revenue'] / $maxDowRev) * 100));
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $day['name'] }}</span>
                            <span class="font-black text-slate-900 dark:text-slate-100 tabular-nums">
                                Ks {{ number_format($day['revenue']) }}
                            </span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full" style="width: {{ $dPercent }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Top 10 Best Selling Products Leaderboard --}}
    <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.top_selling_products_title') }}</h2>
                <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('messages.top_products_by_revenue_margin') }}</p>
            </div>
            <span class="px-3 py-1 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300">
                Top {{ count($report['top_products']) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-3 w-12 text-center">#</th>
                        <th class="py-3 px-3">{{ __('messages.product') }}</th>
                        <th class="py-3 px-3">{{ __('messages.category') }}</th>
                        <th class="py-3 px-3 text-center">{{ __('messages.quantity') }}</th>
                        <th class="py-3 px-3 text-right">{{ __('messages.revenue') }}</th>
                        <th class="py-3 px-3 text-right">{{ __('messages.cost') }}</th>
                        <th class="py-3 px-3 text-right">{{ __('messages.profit') }}</th>
                        <th class="py-3 px-3 text-center">{{ __('messages.margin') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($report['top_products'] as $idx => $prod)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                            <td class="py-3 px-3 text-center">
                                @if ($idx === 0)
                                    <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300 inline-flex items-center justify-center font-black">🥇</span>
                                @elseif ($idx === 1)
                                    <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 inline-flex items-center justify-center font-black">🥈</span>
                                @elseif ($idx === 2)
                                    <span class="w-6 h-6 rounded-full bg-amber-50 text-amber-800 dark:bg-amber-900 dark:text-amber-200 inline-flex items-center justify-center font-black">🥉</span>
                                @else
                                    <span class="text-slate-400 font-bold">{{ $idx + 1 }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                <div class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ $prod['name'] }}
                                </div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500 font-mono">
                                    SKU: {{ $prod['sku'] ?: '-' }}
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <span class="px-2 py-0.5 rounded-lg text-[11px] font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $prod['category_name'] }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-center font-black text-slate-900 dark:text-slate-100 tabular-nums">
                                {{ number_format($prod['quantity']) }}
                            </td>
                            <td class="py-3 px-3 text-right font-black text-slate-900 dark:text-slate-100 tabular-nums">
                                Ks {{ number_format($prod['revenue']) }}
                            </td>
                            <td class="py-3 px-3 text-right text-slate-500 dark:text-slate-400 tabular-nums">
                                Ks {{ number_format($prod['cost']) }}
                            </td>
                            <td class="py-3 px-3 text-right font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                                Ks {{ number_format($prod['profit']) }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $prod['margin'] >= 25 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : ($prod['margin'] >= 10 ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300') }}">
                                    {{ $prod['margin'] }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                {{ __('messages.no_sales_in_period') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Category & Brand Revenue Share Breakdown (2 Cols) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Category Breakdown --}}
        <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-3">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.category_revenue_share') }}</h2>
            <div class="space-y-3">
                @forelse ($report['category_share'] as $c)
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $c['name'] }}</span>
                            <span class="font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                Ks {{ number_format($c['revenue']) }} <span class="text-slate-400 font-normal">({{ $c['percent'] }}%)</span>
                            </span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-indigo-600 rounded-full" style="width: {{ $c['percent'] }}%;"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-slate-400 dark:text-slate-500 text-xs">
                        {{ __('messages.no_data_available') }}
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Brand Breakdown --}}
        <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-3">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.brand_revenue_share') }}</h2>
            <div class="space-y-3">
                @forelse ($report['brand_share'] as $b)
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $b['name'] }}</span>
                            <span class="font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                Ks {{ number_format($b['revenue']) }} <span class="text-slate-400 font-normal">({{ $b['percent'] }}%)</span>
                            </span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-sky-500 rounded-full" style="width: {{ $b['percent'] }}%;"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-slate-400 dark:text-slate-500 text-xs">
                        {{ __('messages.no_data_available') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Cashier / Staff Performance & Payment Methods (2 Cols) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Cashier Performance (2 Cols) --}}
        <div class="lg:col-span-2 p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.cashier_performance_leaderboard') }}</h2>
                <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('messages.cashier_performance_subtitle') }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-3">{{ __('messages.cashier') }}</th>
                            <th class="py-2.5 px-3 text-center">{{ __('messages.total_orders') }}</th>
                            <th class="py-2.5 px-3 text-right">{{ __('messages.revenue') }}</th>
                            <th class="py-2.5 px-3 text-right">{{ __('messages.pl_discounts_given') }}</th>
                            <th class="py-2.5 px-3 text-right">{{ __('messages.aov_metric') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($report['cashier_performance'] as $cashier)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $cashier['name'] }}</div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ $cashier['email'] }}</div>
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                    {{ $cashier['orders_count'] }}
                                </td>
                                <td class="py-3 px-3 text-right font-black text-indigo-600 dark:text-indigo-400 tabular-nums">
                                    Ks {{ number_format($cashier['total_sales']) }}
                                </td>
                                <td class="py-3 px-3 text-right text-rose-600 dark:text-rose-400 tabular-nums">
                                    Ks {{ number_format($cashier['total_discounts']) }}
                                </td>
                                <td class="py-3 px-3 text-right font-bold text-slate-800 dark:text-slate-200 tabular-nums">
                                    Ks {{ number_format($cashier['aov']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400 dark:text-slate-500">
                                    {{ __('messages.no_data_available') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Payment Methods Breakdown (1 Col) --}}
        <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.payment_method_share') }}</h2>
                <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('messages.customer_payment_breakdown') }}</p>
            </div>

            <div class="space-y-3">
                @forelse ($report['payment_methods'] as $pm)
                    <div class="p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-800 dark:text-slate-200 uppercase">{{ $pm['method'] }}</span>
                            <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $pm['percent'] }}%</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                            <span>{{ $pm['count'] }} {{ __('messages.transactions') }}</span>
                            <span class="font-bold text-slate-900 dark:text-slate-100">Ks {{ number_format($pm['amount']) }}</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                            <div class="h-full bg-indigo-600 rounded-full" style="width: {{ $pm['percent'] }}%;"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-slate-400 dark:text-slate-500 text-xs">
                        {{ __('messages.no_data_available') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
