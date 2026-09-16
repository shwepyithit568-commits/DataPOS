@extends('layouts.admin.app')

@section('title', __('messages.sidebar_sales_analytics') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6">

    {{-- Top Ultra-Dense Header Banner (Standard v4.1) --}}
    <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none transition">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}"
               class="h-6 w-6 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition active:scale-95 shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="w-6 h-6 rounded bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs shrink-0">
                <span>📈</span>
            </div>
            <div class="flex items-center gap-1.5 min-w-0 flex-wrap">
                <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-1.5 py-0.5 rounded border border-indigo-200/50 dark:border-indigo-800/50 truncate max-w-[120px] sm:max-w-none">
                    {{ $store->name }}
                </span>
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight truncate">
                    {{ __('messages.sales_analytics_title') }}
                </h1>
                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                    {{ $report['date_range']['from'] }} ~ {{ $report['date_range']['to'] }} ({{ $report['date_range']['days'] }} {{ __('messages.days') }})
                </span>
                @if($channel !== 'all')
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                        {{ $channel === 'pos' ? __('messages.channel_pos_only') : __('messages.channel_online_only') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-1.5 shrink-0 self-end sm:self-auto">
            <button type="button"
                    data-print
                    class="sf-btn-3d h-7 px-2.5 text-xs font-semibold rounded-md inline-flex items-center gap-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>{{ __('messages.print_slip') }}</span>
            </button>

            <a href="{{ route('store.admin.sales_analytics.export', ['store_slug' => $store->slug, 'preset' => $preset, 'channel' => $channel, 'from' => request('from'), 'to' => request('to')]) }}"
               class="sf-btn-3d-success h-7 px-2.5 text-xs font-bold rounded-md inline-flex items-center gap-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>{{ __('messages.export_csv') }}</span>
            </a>
        </div>
    </div>

    {{-- Filter Toolbar: Presets & Channels (Standard v4.1 Dense) --}}
    <div class="px-2 py-1 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-1 select-none">
        {{-- Preset Pills --}}
        <div class="flex items-center gap-1 overflow-x-auto pb-0.5 lg:pb-0 text-xs">
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
                   class="px-2 py-1 rounded-md text-[11px] font-bold whitespace-nowrap transition cursor-pointer {{ $preset === $key ? 'sf-btn-3d-primary' : 'sf-btn-3d' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Channel Selector + Custom Date Form --}}
        <form method="GET" action="{{ route('store.admin.sales_analytics.index', ['store_slug' => $store->slug]) }}" class="flex flex-wrap items-center gap-1">
            <input type="hidden" name="preset" value="custom">

            {{-- Channel Dropdown --}}
            <select name="channel" data-auto-submit class="h-7 px-2 text-xs rounded border border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                <option value="all" {{ $channel === 'all' ? 'selected' : '' }}>{{ __('messages.all_channels') }}</option>
                <option value="pos" {{ $channel === 'pos' ? 'selected' : '' }}>{{ __('messages.channel_pos_counter') }}</option>
                <option value="online" {{ $channel === 'online' ? 'selected' : '' }}>{{ __('messages.channel_online_web') }}</option>
            </select>

            <div class="flex items-center gap-1">
                <input type="date"
                       name="from"
                       value="{{ $from->toDateString() }}"
                       class="h-7 px-2 text-xs rounded border border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                <span class="text-xs text-slate-400">—</span>
                <input type="date"
                       name="to"
                       value="{{ $to->toDateString() }}"
                       class="h-7 px-2 text-xs rounded border border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
            </div>
            <button type="submit" class="sf-btn-3d-primary h-7 px-2.5 text-xs font-bold rounded-md inline-flex items-center gap-1 cursor-pointer">
                {{ __('messages.filter') }}
            </button>
        </form>
    </div>

    {{-- Core KPI Summary Cards (Standard v4.1 Centered Row-based 6 Cards) --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-0.5 sm:gap-1 select-none">

        {{-- 1. Net Sales Revenue --}}
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2 sm:gap-2.5 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.pl_net_revenue') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 border bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border-indigo-100 dark:border-indigo-900/50">
                💰
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.pl_net_revenue') }}
                </div>
                <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ format_currency($report['kpi']['net_sales'], $store) }}</span>
                </div>
                <div class="text-[10px] flex items-center gap-0.5">
                    @if ($comparison['revenue_growth'] >= 0)
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">↑ {{ $comparison['revenue_growth'] }}%</span>
                    @else
                        <span class="font-bold text-rose-600 dark:text-rose-400">↓ {{ abs($comparison['revenue_growth']) }}%</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- 2. Total Orders / Invoices --}}
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2 sm:gap-2.5 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.total_orders') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 border bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border-sky-100 dark:border-sky-900/50">
                🧾
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.total_orders') }}
                </div>
                <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ number_format($report['kpi']['total_orders']) }}</span>
                </div>
                <div class="text-[10px] flex items-center gap-0.5">
                    @if ($comparison['orders_growth'] >= 0)
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">↑ {{ $comparison['orders_growth'] }}%</span>
                    @else
                        <span class="font-bold text-rose-600 dark:text-rose-400">↓ {{ abs($comparison['orders_growth']) }}%</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- 3. Items Sold Quantity --}}
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2 sm:gap-2.5 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.items_sold') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 border bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/50">
                📦
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.items_sold') }}
                </div>
                <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ number_format($report['kpi']['total_items']) }}</span>
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500">
                    {{ __('messages.units_dispatched') }}
                </div>
            </div>
        </div>

        {{-- 4. Average Order Value (AOV) --}}
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2 sm:gap-2.5 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.aov_metric') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 border bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border-purple-100 dark:border-purple-900/50">
                🎯
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.aov_metric') }}
                </div>
                <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ format_currency($report['kpi']['aov'], $store) }}</span>
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500">
                    {{ __('messages.avg_per_ticket') }}
                </div>
            </div>
        </div>

        {{-- 5. Gross Profit & Margin % --}}
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2 sm:gap-2.5 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.pl_gross_profit') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 border bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 border-teal-100 dark:border-teal-900/50">
                💎
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.pl_gross_profit') }} ({{ $report['kpi']['gross_margin'] }}%)
                </div>
                <div class="text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ format_currency($report['kpi']['gross_profit'], $store) }}</span>
                </div>
                <div class="text-[10px] flex items-center gap-0.5">
                    @if ($comparison['profit_growth'] >= 0)
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">↑ {{ $comparison['profit_growth'] }}%</span>
                    @else
                        <span class="font-bold text-rose-600 dark:text-rose-400">↓ {{ abs($comparison['profit_growth']) }}%</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- 6. Discounts Given --}}
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2 sm:gap-2.5 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.pl_discounts_given') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 border bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border-rose-100 dark:border-rose-900/50">
                🏷️
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.pl_discounts_given') }}
                </div>
                <div class="text-xs sm:text-sm font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ format_currency($report['kpi']['discounts'], $store) }}</span>
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500">
                    {{ __('messages.promos_price_cuts') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Main Timeline Revenue Chart & Channel Mix --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-0.5 sm:gap-1">

        {{-- Daily Timeline Chart (2 Cols) --}}
        <div class="lg:col-span-2 p-3 sm:p-4 rounded border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-3">
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
                                        <div class="text-emerald-400 font-bold">{{ format_currency($point['revenue'], $store) }}</div>
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
        <div class="p-2 sm:p-2.5 rounded border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-2 flex flex-col justify-between">
            <div>
                <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.channel_breakdown') }}</h2>
                <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ __('messages.pos_vs_online_sales') }}</p>
            </div>

            <div class="space-y-2 my-auto">
                {{-- POS Counter --}}
                <div class="p-2 rounded border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            {{ __('messages.channel_pos_counter') }}
                        </span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $report['channels']['pos']['percent'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between text-[11px]">
                        <span class="text-slate-500 dark:text-slate-400">{{ $report['channels']['pos']['orders'] }} {{ __('messages.receipts') }}</span>
                        <span class="font-black text-slate-900 dark:text-slate-100 tabular-nums">{{ format_currency($report['channels']['pos']['revenue'], $store) }}</span>
                    </div>
                    <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                        <div class="h-full bg-indigo-600 rounded-full" style="width: {{ $report['channels']['pos']['percent'] }}%;"></div>
                    </div>
                </div>

                {{-- Online Storefront --}}
                <div class="p-2 rounded border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                            {{ __('messages.channel_online_web') }}
                        </span>
                        <span class="font-bold text-sky-600 dark:text-sky-400">{{ $report['channels']['online']['percent'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between text-[11px]">
                        <span class="text-slate-500 dark:text-slate-400">{{ $report['channels']['online']['orders'] }} {{ __('messages.receipts') }}</span>
                        <span class="font-black text-slate-900 dark:text-slate-100 tabular-nums">{{ format_currency($report['channels']['online']['revenue'], $store) }}</span>
                    </div>
                    <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                        <div class="h-full bg-sky-500 rounded-full" style="width: {{ $report['channels']['online']['percent'] }}%;"></div>
                    </div>
                </div>
            </div>

            <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800 text-[10px] text-slate-400 dark:text-slate-500">
                💡 {{ __('messages.channel_mix_hint') }}
            </div>
        </div>
    </div>

    {{-- Peak Sales Hours (0-23h) & Day of Week Pattern --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-0.5 sm:gap-1">

        {{-- 24-Hour Peak Hourly Distribution (2 Cols) --}}
        <div class="lg:col-span-2 p-2 sm:p-2.5 rounded border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-2">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.peak_sales_hours') }}</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('messages.hourly_volume_heatmap') }}</p>
                </div>
                @if ($report['hourly']['peak_hour'] && $report['hourly']['peak_hour']['revenue'] > 0)
                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-xs font-bold text-amber-700 dark:text-amber-300">
                        ⚡ {{ __('messages.peak_hour') }}: {{ $report['hourly']['peak_hour']['display'] }} ({{ format_currency($report['hourly']['peak_hour']['revenue'], $store) }})
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
                                    <div class="text-emerald-400 font-bold">{{ format_currency($hPoint['revenue'], $store) }}</div>
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
        <div class="p-2 sm:p-2.5 rounded border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-2">
            <div>
                <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.busiest_days_of_week') }}</h2>
                <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ __('messages.traffic_by_day') }}</p>
            </div>

            @php
                $dowData = $report['day_of_week']['days'];
                $maxDowRev = $report['day_of_week']['max_revenue'];
            @endphp

            <div class="space-y-1.5 pt-1">
                @foreach ($dowData as $day)
                    @php
                        $dPercent = max(2, round(($day['revenue'] / $maxDowRev) * 100));
                    @endphp
                    <div class="space-y-0.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $day['name'] }}</span>
                            <span class="font-black text-slate-900 dark:text-slate-100 tabular-nums">
                                {{ format_currency($day['revenue'], $store) }}
                            </span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full" style="width: {{ $dPercent }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Top 10 Best Selling Products Leaderboard --}}
    <div class="p-2 sm:p-2.5 rounded border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-2">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.top_selling_products_title') }}</h2>
                <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ __('messages.top_products_by_revenue_margin') }}</p>
            </div>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 border border-indigo-200/50">
                Top {{ count($report['top_products']) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                        <th class="py-1.5 px-2 w-10 text-center">#</th>
                        <th class="py-1.5 px-2">{{ __('messages.product') }}</th>
                        <th class="py-1.5 px-2">{{ __('messages.category') }}</th>
                        <th class="py-1.5 px-2 text-center">{{ __('messages.quantity') }}</th>
                        <th class="py-1.5 px-2 text-right">{{ __('messages.revenue') }}</th>
                        <th class="py-1.5 px-2 text-right">{{ __('messages.cost') }}</th>
                        <th class="py-1.5 px-2 text-right">{{ __('messages.profit') }}</th>
                        <th class="py-1.5 px-2 text-center">{{ __('messages.margin') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($report['top_products'] as $idx => $prod)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                            <td class="py-1 px-2 text-center">
                                @if ($idx === 0)
                                    <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300 inline-flex items-center justify-center font-black text-[10px]">🥇</span>
                                @elseif ($idx === 1)
                                    <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 inline-flex items-center justify-center font-black text-[10px]">🥈</span>
                                @elseif ($idx === 2)
                                    <span class="w-5 h-5 rounded-full bg-amber-50 text-amber-800 dark:bg-amber-900 dark:text-amber-200 inline-flex items-center justify-center font-black text-[10px]">🥉</span>
                                @else
                                    <span class="text-slate-400 font-bold text-[11px]">{{ $idx + 1 }}</span>
                                @endif
                            </td>
                            <td class="py-1 px-2">
                                <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                    {{ $prod['name'] }}
                                </div>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">
                                    SKU: {{ $prod['sku'] ?: '-' }}
                                </div>
                            </td>
                            <td class="py-1 px-2">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $prod['category_name'] }}
                                </span>
                            </td>
                            <td class="py-1 px-2 text-center font-black text-slate-900 dark:text-slate-100 tabular-nums bg-slate-50/50 dark:bg-slate-800/30">
                                {{ number_format($prod['quantity']) }}
                            </td>
                            <td class="py-1 px-2 text-right font-black text-slate-900 dark:text-slate-100 tabular-nums">
                                {{ format_currency($prod['revenue'], $store) }}
                            </td>
                            <td class="py-1 px-2 text-right text-slate-500 dark:text-slate-400 tabular-nums text-[11px]">
                                {{ format_currency($prod['cost'], $store) }}
                            </td>
                            <td class="py-1 px-2 text-right font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                                {{ format_currency($prod['profit'], $store) }}
                            </td>
                            <td class="py-1 px-2 text-center">
                                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $prod['margin'] >= 25 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : ($prod['margin'] >= 10 ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300') }}">
                                    {{ $prod['margin'] }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-slate-400 dark:text-slate-500">
                                {{ __('messages.no_sales_in_period') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Category & Brand Revenue Share Breakdown (2 Cols) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-0.5 sm:gap-1">

        {{-- Category Breakdown --}}
        <div class="p-2 sm:p-2.5 rounded border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-2">
            <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.category_revenue_share') }}</h2>
            <div class="space-y-2">
                @forelse ($report['category_share'] as $c)
                    <div class="space-y-0.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $c['name'] }}</span>
                            <span class="font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                {{ format_currency($c['revenue'], $store) }} <span class="text-slate-400 font-normal text-[10px]">({{ $c['percent'] }}%)</span>
                            </span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-indigo-600 rounded-full" style="width: {{ $c['percent'] }}%;"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-4 text-center text-slate-400 dark:text-slate-500 text-xs">
                        {{ __('messages.no_data_available') }}
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Brand Breakdown --}}
        <div class="p-2 sm:p-2.5 rounded border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-2">
            <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.brand_revenue_share') }}</h2>
            <div class="space-y-2">
                @forelse ($report['brand_share'] as $b)
                    <div class="space-y-0.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $b['name'] }}</span>
                            <span class="font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                {{ format_currency($b['revenue'], $store) }} <span class="text-slate-400 font-normal text-[10px]">({{ $b['percent'] }}%)</span>
                            </span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-sky-500 rounded-full" style="width: {{ $b['percent'] }}%;"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-4 text-center text-slate-400 dark:text-slate-500 text-xs">
                        {{ __('messages.no_data_available') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Cashier / Staff Performance & Payment Methods (2 Cols) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-0.5 sm:gap-1">

        {{-- Cashier Performance (2 Cols) --}}
        <div class="lg:col-span-2 p-2 sm:p-2.5 rounded border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-2">
            <div>
                <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.cashier_performance_leaderboard') }}</h2>
                <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ __('messages.cashier_performance_subtitle') }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                            <th class="py-1.5 px-2">{{ __('messages.cashier') }}</th>
                            <th class="py-1.5 px-2 text-center">{{ __('messages.total_orders') }}</th>
                            <th class="py-1.5 px-2 text-right">{{ __('messages.revenue') }}</th>
                            <th class="py-1.5 px-2 text-right">{{ __('messages.pl_discounts_given') }}</th>
                            <th class="py-1.5 px-2 text-right">{{ __('messages.aov_metric') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($report['cashier_performance'] as $cashier)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                                <td class="py-1 px-2">
                                    <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $cashier['name'] }}</div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500">{{ $cashier['email'] }}</div>
                                </td>
                                <td class="py-1 px-2 text-center font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                    {{ $cashier['orders_count'] }}
                                </td>
                                <td class="py-1 px-2 text-right font-black text-indigo-600 dark:text-indigo-400 tabular-nums">
                                    {{ format_currency($cashier['total_sales'], $store) }}
                                </td>
                                <td class="py-1 px-2 text-right text-rose-600 dark:text-rose-400 tabular-nums text-[11px]">
                                    {{ format_currency($cashier['total_discounts'], $store) }}
                                </td>
                                <td class="py-1 px-2 text-right font-bold text-slate-800 dark:text-slate-200 tabular-nums">
                                    {{ format_currency($cashier['aov'], $store) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-400 dark:text-slate-500">
                                    {{ __('messages.no_data_available') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Payment Methods Breakdown (1 Col) --}}
        <div class="p-2 sm:p-2.5 rounded border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-2">
            <div>
                <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.payment_method_share') }}</h2>
                <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ __('messages.customer_payment_breakdown') }}</p>
            </div>

            <div class="space-y-1.5">
                @forelse ($report['payment_methods'] as $pm)
                    <div class="p-2 rounded border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-800 dark:text-slate-200 uppercase">{{ $pm['method'] }}</span>
                            <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $pm['percent'] }}%</span>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400">
                            <span>{{ $pm['count'] }} {{ __('messages.transactions') }}</span>
                            <span class="font-bold text-slate-900 dark:text-slate-100">{{ format_currency($pm['amount'], $store) }}</span>
                        </div>
                        <div class="w-full h-1 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                            <div class="h-full bg-indigo-600 rounded-full" style="width: {{ $pm['percent'] }}%;"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-4 text-center text-slate-400 dark:text-slate-500 text-xs">
                        {{ __('messages.no_data_available') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
