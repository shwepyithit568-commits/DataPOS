@extends('layouts.admin.app')

@section('title', __('messages.sidebar_profit_loss') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="space-y-6">

    {{-- Breadcrumbs & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.admin_dashboard') }}</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-200 font-semibold">{{ __('messages.sidebar_profit_loss') }}</span>
            </div>
            <div class="flex items-center gap-3 mt-1.5">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 font-outfit">
                    {{ __('messages.profit_loss_title') }}
                </h1>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                    {{ $statement['period']['label'] }}
                </span>
            </div>
        </div>

        {{-- Top Right Actions (Print & Export) --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('store.admin.profit_loss.statement', ['store_slug' => $store->slug, 'preset' => $preset, 'from' => request('from'), 'to' => request('to')]) }}"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>{{ __('messages.print_statement_a4') }}</span>
            </a>

            <a href="{{ route('store.admin.profit_loss.export', ['store_slug' => $store->slug, 'preset' => $preset, 'from' => request('from'), 'to' => request('to')]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-600/20 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>{{ __('messages.export_csv') }}</span>
            </a>
        </div>
    </div>

    {{-- Period Filter Toolbar --}}
    <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        {{-- Preset Pills --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 text-xs">
            @php
                $presetsList = [
                    'today' => __('messages.period_today'),
                    'yesterday' => __('messages.period_yesterday'),
                    'this_week' => __('messages.period_this_week'),
                    'this_month' => __('messages.period_this_month'),
                    'last_month' => __('messages.period_last_month'),
                    'this_year' => __('messages.period_this_year'),
                ];
            @endphp
            @foreach ($presetsList as $key => $label)
                <a href="{{ route('store.admin.profit_loss.index', ['store_slug' => $store->slug, 'preset' => $key]) }}"
                   class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition {{ $preset === $key ? 'bg-violet-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Custom Date Form --}}
        <form method="GET" action="{{ route('store.admin.profit_loss.index', ['store_slug' => $store->slug]) }}" class="flex items-center gap-2">
            <input type="hidden" name="preset" value="custom">
            <div class="flex items-center gap-1.5">
                <input type="date"
                       name="from"
                       value="{{ $from->toDateString() }}"
                       class="px-2.5 py-1 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500">
                <span class="text-xs text-slate-400">—</span>
                <input type="date"
                       name="to"
                       value="{{ $to->toDateString() }}"
                       class="px-2.5 py-1 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500">
            </div>
            <button type="submit" class="px-3 py-1 text-xs font-bold rounded-xl bg-slate-800 hover:bg-slate-700 text-white dark:bg-slate-700 dark:hover:bg-slate-600 transition">
                {{ __('messages.filter') }}
            </button>
        </form>
    </div>

    {{-- Primary 4 Financial KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- 1. Net Sales Revenue --}}
        <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-sky-700 dark:text-sky-400 uppercase tracking-wider">{{ __('messages.pl_net_revenue') }}</span>
                <div class="w-8 h-8 rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300 flex items-center justify-center font-bold">
                    💵
                </div>
            </div>
            <div>
                <div class="text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit">
                    {{ number_format($statement['revenue']['net_sales'], 0) }} <span class="text-sm font-semibold">Ks</span>
                </div>
                <div class="text-xs text-slate-400 mt-1 flex items-center justify-between">
                    <span>{{ __('messages.pl_gross_sales') }}: {{ number_format($statement['revenue']['gross_sales'], 0) }}</span>
                    @if($comparison['revenue_growth'] != 0)
                        <span class="font-bold {{ $comparison['revenue_growth'] > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $comparison['revenue_growth'] > 0 ? '+' : '' }}{{ $comparison['revenue_growth'] }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- 2. Cost of Goods Sold (COGS) --}}
        <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider">{{ __('messages.pl_cost_of_goods_sold') }}</span>
                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300 flex items-center justify-center font-bold">
                    📦
                </div>
            </div>
            <div>
                <div class="text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit">
                    {{ number_format($statement['cogs']['net_cogs'], 0) }} <span class="text-sm font-semibold">Ks</span>
                </div>
                <div class="text-xs text-slate-400 mt-1">
                    <span>{{ $statement['revenue']['net_sales'] > 0 ? round(($statement['cogs']['net_cogs'] / $statement['revenue']['net_sales']) * 100, 1) : 0 }}% of Revenue</span>
                </div>
            </div>
        </div>

        {{-- 3. Gross Profit & Margin --}}
        <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">{{ __('messages.pl_gross_profit') }}</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 flex items-center justify-center font-bold">
                    📈
                </div>
            </div>
            <div>
                <div class="text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit">
                    {{ number_format($statement['gross_profit'], 0) }} <span class="text-sm font-semibold">Ks</span>
                </div>
                <div class="text-xs text-slate-400 mt-1 flex items-center justify-between">
                    <span>{{ __('messages.pl_gross_margin') }}:</span>
                    <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $statement['gross_margin'] }}%</span>
                </div>
            </div>
        </div>

        {{-- 4. Net Profit / Loss --}}
        @php
            $isProfitable = $statement['net_profit'] >= 0;
        @endphp
        <div class="p-5 rounded-2xl border shadow-sm space-y-3 {{ $isProfitable ? 'border-emerald-200 bg-emerald-50/40 dark:border-emerald-900 dark:bg-emerald-950/20' : 'border-rose-200 bg-rose-50/40 dark:border-rose-900 dark:bg-rose-950/20' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider {{ $isProfitable ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                    {{ __('messages.pl_net_profit') }}
                </span>
                <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold {{ $isProfitable ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300' }}">
                    {{ $isProfitable ? '🏆' : '⚠️' }}
                </div>
            </div>
            <div>
                <div class="text-2xl font-black font-outfit {{ $isProfitable ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ number_format($statement['net_profit'], 0) }} <span class="text-sm font-semibold">Ks</span>
                </div>
                <div class="text-xs text-slate-400 mt-1 flex items-center justify-between">
                    <span>{{ __('messages.pl_net_margin') }}: <strong class="{{ $isProfitable ? 'text-emerald-600' : 'text-rose-600' }}">{{ $statement['net_margin'] }}%</strong></span>
                    @if($comparison['profit_growth'] != 0)
                        <span class="font-bold {{ $comparison['profit_growth'] > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $comparison['profit_growth'] > 0 ? '+' : '' }}{{ $comparison['profit_growth'] }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Main Statement Grid: Left (Full Income Statement Rows), Right (Expenses & Top Products) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Left: Formal Income Statement Table (7 cols) --}}
        <div class="lg:col-span-7 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.income_statement_breakdown') }}</h3>
                    <p class="text-xs text-slate-400">{{ $statement['period']['label'] }}</p>
                </div>
                <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-mono text-slate-600 dark:text-slate-400 font-semibold">MMK</span>
            </div>

            <div class="p-5 space-y-6">

                {{-- 1. Revenue Section --}}
                <div class="space-y-2">
                    <div class="text-xs font-extrabold uppercase text-slate-400 tracking-wider">1. {{ __('messages.pl_revenue') }}</div>
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between text-slate-700 dark:text-slate-300">
                            <span>{{ __('messages.pl_gross_sales') }}</span>
                            <span class="font-mono">{{ number_format($statement['revenue']['gross_sales'], 0) }}</span>
                        </div>
                        @if($statement['revenue']['discounts'] > 0)
                            <div class="flex justify-between text-slate-500 text-xs pl-3">
                                <span>— {{ __('messages.pl_discounts_given') }}</span>
                                <span class="font-mono text-rose-500">- {{ number_format($statement['revenue']['discounts'], 0) }}</span>
                            </div>
                        @endif
                        @if($statement['revenue']['returns'] > 0)
                            <div class="flex justify-between text-slate-500 text-xs pl-3">
                                <span>— {{ __('messages.pl_returns_refunds') }}</span>
                                <span class="font-mono text-rose-500">- {{ number_format($statement['revenue']['returns'], 0) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-bold pt-2 border-t border-slate-100 dark:border-slate-800 text-slate-900 dark:text-slate-100">
                            <span>{{ __('messages.pl_net_revenue') }}</span>
                            <span class="font-mono text-sky-600 dark:text-sky-400 font-black">{{ number_format($statement['revenue']['net_sales'], 0) }} Ks</span>
                        </div>
                    </div>
                </div>

                {{-- 2. Cost of Goods Sold Section --}}
                <div class="space-y-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div class="text-xs font-extrabold uppercase text-slate-400 tracking-wider">2. {{ __('messages.pl_cost_of_goods_sold') }} (COGS)</div>
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between text-slate-700 dark:text-slate-300">
                            <span>{{ __('messages.pl_gross_cogs') }}</span>
                            <span class="font-mono">{{ number_format($statement['cogs']['gross_cogs'], 0) }}</span>
                        </div>
                        @if($statement['cogs']['returns_cogs'] > 0)
                            <div class="flex justify-between text-slate-500 text-xs pl-3">
                                <span>— {{ __('messages.pl_returned_goods_cost') }}</span>
                                <span class="font-mono text-emerald-600">- {{ number_format($statement['cogs']['returns_cogs'], 0) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-bold pt-2 border-t border-slate-100 dark:border-slate-800 text-slate-900 dark:text-slate-100">
                            <span>{{ __('messages.pl_net_cogs') }}</span>
                            <span class="font-mono text-amber-600 dark:text-amber-400 font-black">{{ number_format($statement['cogs']['net_cogs'], 0) }} Ks</span>
                        </div>
                    </div>
                </div>

                {{-- 3. Gross Profit Row --}}
                <div class="p-3.5 rounded-xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/60 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-indigo-900 dark:text-indigo-300 uppercase tracking-wide">{{ __('messages.pl_gross_profit') }}</div>
                        <div class="text-[11px] text-indigo-600 dark:text-indigo-400">{{ __('messages.pl_gross_margin') }}: {{ $statement['gross_margin'] }}%</div>
                    </div>
                    <div class="text-lg font-black text-indigo-900 dark:text-indigo-200 font-mono">
                        {{ number_format($statement['gross_profit'], 0) }} Ks
                    </div>
                </div>

                {{-- 4. Operating Expenses Section --}}
                <div class="space-y-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div class="text-xs font-extrabold uppercase text-slate-400 tracking-wider">4. {{ __('messages.pl_operating_expenses') }}</div>
                    <div class="space-y-2 text-sm">
                        @forelse ($statement['expenses']['by_category'] as $cat)
                            <div class="flex items-center justify-between text-slate-700 dark:text-slate-300">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $cat['color'] }};"></span>
                                    <span>{{ $cat['name'] }}</span>
                                    <span class="text-xs text-slate-400 font-mono">({{ $cat['percent'] }}%)</span>
                                </div>
                                <span class="font-mono text-slate-600 dark:text-slate-400">{{ number_format($cat['amount'], 0) }}</span>
                            </div>
                        @empty
                            <div class="text-xs text-slate-400 italic py-1">{{ __('messages.no_expenses_in_period') }}</div>
                        @endforelse
                        <div class="flex justify-between font-bold pt-2 border-t border-slate-100 dark:border-slate-800 text-slate-900 dark:text-slate-100">
                            <span>{{ __('messages.pl_total_operating_expenses') }}</span>
                            <span class="font-mono text-rose-600 dark:text-rose-400 font-black">{{ number_format($statement['expenses']['total'], 0) }} Ks</span>
                        </div>
                    </div>
                </div>

                {{-- 5. Final Net Profit / Loss Highlight Box --}}
                <div class="p-4 rounded-xl border flex items-center justify-between {{ $isProfitable ? 'bg-emerald-50 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800' : 'bg-rose-50 border-rose-200 dark:bg-rose-950/40 dark:border-rose-800' }}">
                    <div>
                        <div class="text-sm font-black uppercase tracking-wide {{ $isProfitable ? 'text-emerald-900 dark:text-emerald-200' : 'text-rose-900 dark:text-rose-200' }}">
                            {{ $isProfitable ? __('messages.pl_net_profit') : __('messages.pl_net_loss') }}
                        </div>
                        <div class="text-xs font-semibold {{ $isProfitable ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                            {{ __('messages.pl_net_margin') }}: {{ $statement['net_margin'] }}%
                        </div>
                    </div>
                    <div class="text-2xl font-black font-outfit {{ $isProfitable ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                        {{ number_format($statement['net_profit'], 0) }} Ks
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Expenses Visuals + Top Products + Operational Metrics (5 cols) --}}
        <div class="lg:col-span-5 space-y-6">

            {{-- Operational Transaction Metrics --}}
            <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-3">
                <h3 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                    {{ __('messages.operational_metrics_title') }}
                </h3>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60">
                        <div class="text-[11px] text-slate-400 uppercase">{{ __('messages.total_orders') }}</div>
                        <div class="text-base font-black text-slate-900 dark:text-slate-100 font-outfit mt-1">{{ $statement['metrics']['order_count'] }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60">
                        <div class="text-[11px] text-slate-400 uppercase">{{ __('messages.aov_metric') }}</div>
                        <div class="text-sm font-black text-slate-900 dark:text-slate-100 font-outfit mt-1">{{ number_format($statement['metrics']['aov'], 0) }} Ks</div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60">
                        <div class="text-[11px] text-slate-400 uppercase">{{ __('messages.profit_per_order') }}</div>
                        <div class="text-sm font-black font-outfit mt-1 {{ $statement['metrics']['profit_per_order'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ number_format($statement['metrics']['profit_per_order'], 0) }} Ks
                        </div>
                    </div>
                </div>
            </div>

            {{-- Expense Breakdown Progress Bars --}}
            <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                        {{ __('messages.pl_expense_breakdown_title') }}
                    </h3>
                    <a href="{{ route('store.admin.expenses.index', ['store_slug' => $store->slug]) }}" class="text-xs font-semibold text-violet-600 hover:text-violet-500">
                        {{ __('messages.view_details') }} &rarr;
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse ($statement['expenses']['by_category'] as $cat)
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs font-semibold text-slate-700 dark:text-slate-300">
                                <span>{{ $cat['name'] }}</span>
                                <span class="font-mono font-bold">{{ number_format($cat['amount'], 0) }} Ks <span class="text-slate-400 font-normal">({{ $cat['percent'] }}%)</span></span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full transition-all" style="width: {{ $cat['percent'] }}%; background-color: {{ $cat['color'] }};"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-slate-400 py-3 text-center">{{ __('messages.no_expenses_in_period') }}</div>
                    @endforelse
                </div>
            </div>

            {{-- Top Profitable Products --}}
            <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-3">
                <h3 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                    {{ __('messages.top_profitable_products_title') }}
                </h3>

                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($statement['top_products'] as $prod)
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <div class="max-w-[65%]">
                                <div class="font-bold text-slate-900 dark:text-slate-100 truncate">{{ $prod['name'] }}</div>
                                <div class="text-[11px] text-slate-400">{{ (int) $prod['quantity'] }} sold • {{ $prod['margin'] }}% margin</div>
                            </div>
                            <div class="text-right">
                                <div class="font-black text-emerald-600 dark:text-emerald-400 font-outfit">+{{ number_format($prod['profit'], 0) }} Ks</div>
                                <div class="text-[10px] text-slate-400 font-mono">Rev: {{ number_format($prod['revenue'], 0) }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-slate-400 py-3 text-center">{{ __('messages.no_sales_in_period') }}</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
