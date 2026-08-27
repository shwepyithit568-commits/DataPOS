@extends('layouts.admin.app')

@section('title', __('messages.profit_loss_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5">

    {{-- Breadcrumbs & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 py-1">
        <div>
            <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition">
                    {{ __('messages.admin_dashboard') }}
                </a>
                <span>/</span>
                <span class="text-slate-500 dark:text-slate-400">{{ __('messages.finance') ?? 'Finance' }}</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">{{ __('messages.sidebar_profit_loss') }}</span>
            </div>
            <div class="flex items-center gap-2.5 mt-1">
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-slate-100 font-outfit tracking-tight">
                    {{ __('messages.profit_loss_title') }}
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-violet-100 text-violet-700 dark:bg-violet-950/80 dark:text-violet-300 border border-violet-200/80 dark:border-violet-800/80 shadow-2xs font-mono">
                    {{ $statement['period']['label'] }}
                </span>
            </div>
        </div>

        {{-- Top Right Actions (Print A4 & Export Modal) --}}
        <div class="flex items-center gap-2 shrink-0">
            {{-- Print A4 Statement --}}
            <a href="{{ route('store.admin.profit_loss.statement', array_merge(['store_slug' => $store->slug], request()->all())) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg border border-slate-200/90 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-2xs transition active:scale-95">
                <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>{{ __('messages.print_statement_a4') }}</span>
            </a>

            {{-- Dual Export Button (Excel & CSV) --}}
            @php
                $xlsxUrl = str_contains($exportUrl, '?') ? $exportUrl . '&format=xlsx' : $exportUrl . '?format=xlsx';
                $csvUrl = str_contains($exportUrl, '?') ? $exportUrl . '&format=csv' : $exportUrl . '?format=csv';
            @endphp
            <div class="relative inline-flex items-center" x-data="{ exportModalOpen: false }">
                <div class="inline-flex items-stretch rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs overflow-hidden border border-emerald-600 min-h-[32px]">
                    {{-- Direct Excel Download --}}
                    <a href="{{ $xlsxUrl }}" download title="{{ __('messages.export') }} Excel (.xlsx)"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-white hover:bg-emerald-700 text-xs font-bold transition active:scale-95">
                        <svg class="w-3.5 h-3.5 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 14l5-5 5 5M12 3v12" />
                        </svg>
                        <span>{{ __('messages.export') }}</span>
                        <span class="text-[10px] bg-white/25 text-white px-1 py-0.2 rounded font-mono uppercase font-black">Excel</span>
                    </a>
                    {{-- Selector Trigger Button --}}
                    <button type="button" @click="exportModalOpen = true"
                            class="inline-flex items-center justify-center w-7 px-1 bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white border-l border-emerald-500/80 transition cursor-pointer focus:outline-none"
                            title="Export Formats (Excel / CSV)" aria-label="Export Formats">
                        <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                {{-- Export Modal Teleport --}}
                <template x-teleport="body">
                    <div x-show="exportModalOpen" x-cloak
                         style="z-index: 99999;"
                         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
                         @click.self="exportModalOpen = false"
                         @keydown.escape.window="exportModalOpen = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95">

                        <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-5 space-y-4 text-left"
                             @click.stop>
                            {{-- Modal Header --}}
                            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 grid place-items-center text-sm shadow-inner font-bold">📤</span>
                                    <div>
                                        <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.export') }}</h3>
                                        <p class="text-[11px] text-slate-400">{{ __('messages.profit_loss_title') }}</p>
                                    </div>
                                </div>
                                <button type="button" @click="exportModalOpen = false"
                                        class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            {{-- Format Selection Options --}}
                            <div class="space-y-2.5">
                                {{-- Excel Option --}}
                                <a href="{{ $xlsxUrl }}" download @click="exportModalOpen = false"
                                   class="group flex items-center gap-3.5 p-3 rounded-xl border border-slate-200/90 dark:border-slate-700/80 hover:border-emerald-500/80 dark:hover:border-emerald-500/80 bg-white dark:bg-slate-800/80 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 transition-all shadow-xs hover:shadow-md active:scale-[0.99]">
                                    <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 grid place-items-center text-base font-black shrink-0 shadow-inner group-hover:scale-105 transition-transform">
                                        📊
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-black text-slate-900 dark:text-slate-100 group-hover:text-emerald-700 dark:group-hover:text-emerald-300">{{ __('messages.export_excel_format') }}</span>
                                            <span class="text-[9px] font-black uppercase px-1.5 py-0.2 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">Recommended</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                                            Formatted table, headers, auto column width & multi-section totals.
                                        </p>
                                    </div>
                                    <svg class="w-4 h-4 text-slate-400 group-hover:text-emerald-600 shrink-0 group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>

                                {{-- CSV Option --}}
                                <a href="{{ $csvUrl }}" download @click="exportModalOpen = false"
                                   class="group flex items-center gap-3.5 p-3 rounded-xl border border-slate-200/90 dark:border-slate-700/80 hover:border-sky-500/80 dark:hover:border-sky-500/80 bg-white dark:bg-slate-800/80 hover:bg-sky-50/50 dark:hover:bg-sky-950/20 transition-all shadow-xs hover:shadow-md active:scale-[0.99]">
                                    <div class="w-9 h-9 rounded-xl bg-sky-100 dark:bg-sky-950/80 text-sky-600 dark:text-sky-400 grid place-items-center text-base font-black shrink-0 shadow-inner group-hover:scale-105 transition-transform">
                                        📄
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-black text-slate-900 dark:text-slate-100 group-hover:text-sky-700 dark:group-hover:text-sky-300">{{ __('messages.export_csv_format') }}</span>
                                            <span class="text-[9px] font-black uppercase px-1.5 py-0.2 rounded-full bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300">UTF-8 BOM</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                                            Universal lightweight CSV compatible with Burmese fonts in Excel.
                                        </p>
                                    </div>
                                    <svg class="w-4 h-4 text-slate-400 group-hover:text-sky-600 shrink-0 group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Period & Branch Filter Toolbar --}}
    <div class="p-2.5 sm:p-3 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2.5 backdrop-blur-xs">
        {{-- Preset Pills --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 text-xs scrollbar-thin">
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
                <a href="{{ route('store.admin.profit_loss.index', array_merge(['store_slug' => $store->slug, 'preset' => $key], request()->filled('branch_id') ? ['branch_id' => request('branch_id')] : [])) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition shadow-2xs {{ $preset === $key ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Custom Date Form & Branch selector --}}
        <form method="GET" action="{{ route('store.admin.profit_loss.index', ['store_slug' => $store->slug]) }}" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="preset" value="custom">

            {{-- Branch Selector if multiple branches exist --}}
            @if(isset($branches) && $branches->count() > 0)
                <div class="relative inline-flex items-center">
                    <select name="branch_id"
                            class="appearance-none pl-2.5 pr-6 py-1 min-h-[32px] text-xs font-bold rounded-lg border border-slate-200/90 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-violet-500/40 focus:outline-none cursor-pointer">
                        <option value="">{{ __('messages.pl_all_branches') }}</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ (string) request('branch_id') === (string) $b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                    <svg class="w-3.5 h-3.5 text-slate-400 pointer-events-none absolute right-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            @endif

            {{-- Date Inputs --}}
            <div class="flex items-center gap-1.5">
                <input type="date"
                       name="from"
                       value="{{ $from->toDateString() }}"
                       class="px-2 py-1 min-h-[32px] text-xs font-mono font-bold rounded-lg border border-slate-200/90 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-violet-500/40 focus:outline-none shadow-2xs">
                <span class="text-xs text-slate-400 font-bold">—</span>
                <input type="date"
                       name="to"
                       value="{{ $to->toDateString() }}"
                       class="px-2 py-1 min-h-[32px] text-xs font-mono font-bold rounded-lg border border-slate-200/90 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-violet-500/40 focus:outline-none shadow-2xs">
            </div>

            <button type="submit" class="px-3 py-1 min-h-[32px] text-xs font-bold rounded-lg bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-700 dark:hover:bg-slate-600 transition shadow-2xs active:scale-95">
                {{ __('messages.filter') }}
            </button>

            @if(request()->filled('from') || request()->filled('to') || request()->filled('branch_id') || $preset !== 'this_month')
                <a href="{{ route('store.admin.profit_loss.index', ['store_slug' => $store->slug]) }}"
                   class="px-2.5 py-1 min-h-[32px] text-xs font-bold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition shadow-2xs inline-flex items-center gap-1"
                   title="{{ __('messages.clear') }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>{{ __('messages.clear') }}</span>
                </a>
            @endif
        </form>
    </div>

    {{-- Primary 4 Financial KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">

        {{-- 1. Net Sales Revenue --}}
        <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-sky-700 dark:text-sky-400 uppercase tracking-wider">{{ __('messages.pl_net_revenue') }}</span>
                <div class="w-7 h-7 rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-950/80 dark:text-sky-300 flex items-center justify-center font-bold text-xs shadow-2xs">
                    💵
                </div>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                    {{ number_format($statement['revenue']['net_sales'], 0) }} <span class="text-xs font-bold text-slate-500">{{ __('messages.currency_ks') }}</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center justify-between">
                    <span class="truncate">{{ __('messages.pl_gross_sales') }}: <strong class="text-slate-600 dark:text-slate-300 font-mono">{{ number_format($statement['revenue']['gross_sales'], 0) }}</strong></span>
                    @if($comparison['revenue_growth'] != 0)
                        <span class="font-bold shrink-0 ml-1 {{ $comparison['revenue_growth'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ $comparison['revenue_growth'] > 0 ? '+' : '' }}{{ $comparison['revenue_growth'] }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- 2. Cost of Goods Sold (COGS) --}}
        <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">{{ __('messages.pl_cost_of_goods_sold') }}</span>
                <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300 flex items-center justify-center font-bold text-xs shadow-2xs">
                    📦
                </div>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                    {{ number_format($statement['cogs']['net_cogs'], 0) }} <span class="text-xs font-bold text-slate-500">{{ __('messages.currency_ks') }}</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">
                    <span>{{ $statement['revenue']['net_sales'] > 0 ? round(($statement['cogs']['net_cogs'] / $statement['revenue']['net_sales']) * 100, 1) : 0 }}% {{ __('messages.pl_of_revenue') }}</span>
                </div>
            </div>
        </div>

        {{-- 3. Gross Profit & Margin --}}
        <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">{{ __('messages.pl_gross_profit') }}</span>
                <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300 flex items-center justify-center font-bold text-xs shadow-2xs">
                    📈
                </div>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                    {{ number_format($statement['gross_profit'], 0) }} <span class="text-xs font-bold text-slate-500">{{ __('messages.currency_ks') }}</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center justify-between">
                    <span>{{ __('messages.pl_gross_margin') }}:</span>
                    <span class="font-bold font-mono text-indigo-600 dark:text-indigo-400">{{ $statement['gross_margin'] }}%</span>
                </div>
            </div>
        </div>

        {{-- 4. Net Profit / Loss --}}
        @php
            $isProfitable = $statement['net_profit'] >= 0;
        @endphp
        <div class="p-3.5 sm:p-4 rounded-xl border shadow-xs space-y-2 {{ $isProfitable ? 'border-emerald-200/90 bg-emerald-50/40 dark:border-emerald-900/80 dark:bg-emerald-950/30' : 'border-rose-200/90 bg-rose-50/40 dark:border-rose-900/80 dark:bg-rose-950/30' }}">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider {{ $isProfitable ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                    {{ $isProfitable ? __('messages.pl_net_profit') : __('messages.pl_net_loss') }}
                </span>
                <div class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs shadow-2xs {{ $isProfitable ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300' }}">
                    {{ $isProfitable ? '🏆' : '⚠️' }}
                </div>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black font-outfit tabular-nums {{ $isProfitable ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ number_format($statement['net_profit'], 0) }} <span class="text-xs font-bold text-slate-500">{{ __('messages.currency_ks') }}</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center justify-between">
                    <span>{{ __('messages.pl_net_margin') }}: <strong class="{{ $isProfitable ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} font-mono">{{ $statement['net_margin'] }}%</strong></span>
                    @if($comparison['profit_growth'] != 0)
                        <span class="font-bold shrink-0 ml-1 {{ $comparison['profit_growth'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ $comparison['profit_growth'] > 0 ? '+' : '' }}{{ $comparison['profit_growth'] }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Main Statement Grid: Left (Comprehensive Breakdown), Right (Metrics, Expenses & Top Products) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 items-start">

        {{-- Left: Formal Income Statement Breakdown Table (7 cols) --}}
        <div class="lg:col-span-7 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs overflow-hidden">
            <div class="p-3 sm:p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.income_statement_breakdown') }}</h3>
                    <p class="text-xs text-slate-400 font-mono">{{ $statement['period']['label'] }}</p>
                </div>
                <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-xs font-mono text-slate-600 dark:text-slate-400 font-bold">MMK</span>
            </div>

            <div class="p-3 sm:p-4 space-y-4">

                {{-- 1. Revenue Section --}}
                <div class="space-y-1.5">
                    <div class="text-[11px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">1. {{ __('messages.pl_revenue') }}</div>
                    <div class="space-y-1 text-xs sm:text-sm">
                        <div class="flex justify-between text-slate-700 dark:text-slate-300 py-0.5">
                            <span>{{ __('messages.pl_gross_sales') }}</span>
                            <span class="font-mono tabular-nums font-semibold">{{ number_format($statement['revenue']['gross_sales'], 0) }}</span>
                        </div>
                        @if($statement['revenue']['discounts'] > 0)
                            <div class="flex justify-between text-slate-500 text-xs pl-3 py-0.5">
                                <span>— {{ __('messages.pl_discounts_given') }}</span>
                                <span class="font-mono tabular-nums text-rose-500">- {{ number_format($statement['revenue']['discounts'], 0) }}</span>
                            </div>
                        @endif
                        @if($statement['revenue']['returns'] > 0)
                            <div class="flex justify-between text-slate-500 text-xs pl-3 py-0.5">
                                <span>— {{ __('messages.pl_returns_refunds') }}</span>
                                <span class="font-mono tabular-nums text-rose-500">- {{ number_format($statement['revenue']['returns'], 0) }}</span>
                            </div>
                        @endif
                        @if(!empty($statement['services']['has_services']))
                            <div class="flex justify-between text-slate-500 text-xs pl-3 py-0.5">
                                <span>+ {{ __('messages.pl_service_repair_revenue') }} ({{ $statement['services']['jobs_count'] }})</span>
                                <span class="font-mono tabular-nums text-indigo-600 dark:text-indigo-400 font-semibold">+ {{ number_format($statement['services']['revenue'], 0) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-bold pt-1.5 border-t border-slate-100 dark:border-slate-800 text-slate-900 dark:text-slate-100">
                            <span>{{ !empty($statement['services']['has_services']) ? __('messages.pl_total_combined_revenue') : __('messages.pl_net_revenue') }}</span>
                            <span class="font-mono tabular-nums text-sky-600 dark:text-sky-400 font-black">{{ number_format($statement['revenue']['total_revenue'] ?? $statement['revenue']['net_sales'], 0) }} {{ __('messages.currency_ks') }}</span>
                        </div>
                    </div>
                </div>

                {{-- 2. Cost of Goods Sold Section --}}
                <div class="space-y-1.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <div class="text-[11px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">2. {{ __('messages.pl_cost_of_goods_sold') }} (COGS)</div>
                    <div class="space-y-1 text-xs sm:text-sm">
                        <div class="flex justify-between text-slate-700 dark:text-slate-300 py-0.5">
                            <span>{{ __('messages.pl_gross_cogs') }}</span>
                            <span class="font-mono tabular-nums font-semibold">{{ number_format($statement['cogs']['gross_cogs'], 0) }}</span>
                        </div>
                        @if($statement['cogs']['returns_cogs'] > 0)
                            <div class="flex justify-between text-slate-500 text-xs pl-3 py-0.5">
                                <span>— {{ __('messages.pl_returned_goods_cost') }}</span>
                                <span class="font-mono tabular-nums text-emerald-600">- {{ number_format($statement['cogs']['returns_cogs'], 0) }}</span>
                            </div>
                        @endif
                        @if(!empty($statement['services']['has_services']) && $statement['services']['parts_cost'] > 0)
                            <div class="flex justify-between text-slate-500 text-xs pl-3 py-0.5">
                                <span>+ {{ __('messages.pl_spare_parts_cost') }}</span>
                                <span class="font-mono tabular-nums text-amber-600 font-semibold">+ {{ number_format($statement['services']['parts_cost'], 0) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-bold pt-1.5 border-t border-slate-100 dark:border-slate-800 text-slate-900 dark:text-slate-100">
                            <span>{{ !empty($statement['services']['has_services']) ? __('messages.pl_total_combined_cogs') : __('messages.pl_net_cogs') }}</span>
                            <span class="font-mono tabular-nums text-amber-600 dark:text-amber-400 font-black">{{ number_format($statement['cogs']['total_cogs'] ?? $statement['cogs']['net_cogs'], 0) }} {{ __('messages.currency_ks') }}</span>
                        </div>
                    </div>
                </div>

                {{-- 3. Gross Profit Row --}}
                <div class="p-3 rounded-lg bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 flex items-center justify-between shadow-2xs">
                    <div>
                        <div class="text-xs font-black text-indigo-900 dark:text-indigo-300 uppercase tracking-wide">{{ __('messages.pl_gross_profit') }}</div>
                        <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-bold">
                            {{ __('messages.pl_gross_margin') }}: {{ $statement['gross_margin'] }}%
                            @if(!empty($statement['services']['has_services']) && $statement['services']['revenue'] > 0)
                                <span class="text-[10px] text-slate-400 font-normal ml-1">({{ __('messages.sidebar_service') }}: {{ $statement['services']['margin'] }}%)</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-base sm:text-lg font-black text-indigo-900 dark:text-indigo-200 font-mono tabular-nums">
                        {{ number_format($statement['gross_profit'], 0) }} {{ __('messages.currency_ks') }}
                    </div>
                </div>

                {{-- 4. Operating Expenses Section --}}
                <div class="space-y-1.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <div class="text-[11px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">4. {{ __('messages.pl_operating_expenses') }}</div>
                    <div class="space-y-1 text-xs sm:text-sm">
                        @forelse ($statement['expenses']['by_category'] as $cat)
                            <div class="flex items-center justify-between text-slate-700 dark:text-slate-300 py-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $cat['color'] }};"></span>
                                    <span>{{ $cat['name'] }}</span>
                                    <span class="text-xs text-slate-400 font-mono">({{ $cat['percent'] }}%)</span>
                                </div>
                                <span class="font-mono tabular-nums text-slate-600 dark:text-slate-400 font-semibold">{{ number_format($cat['amount'], 0) }}</span>
                            </div>
                        @empty
                            <div class="text-xs text-slate-400 italic py-1">{{ __('messages.no_expenses_in_period') }}</div>
                        @endforelse
                        <div class="flex justify-between font-bold pt-1.5 border-t border-slate-100 dark:border-slate-800 text-slate-900 dark:text-slate-100">
                            <span>{{ __('messages.pl_total_operating_expenses') }}</span>
                            <span class="font-mono tabular-nums text-rose-600 dark:text-rose-400 font-black">{{ number_format($statement['expenses']['total'], 0) }} {{ __('messages.currency_ks') }}</span>
                        </div>
                    </div>
                </div>

                {{-- 5. Final Net Profit / Loss Highlight Box --}}
                <div class="p-3.5 rounded-xl border flex items-center justify-between shadow-xs {{ $isProfitable ? 'bg-emerald-50 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800' : 'bg-rose-50 border-rose-200 dark:bg-rose-950/40 dark:border-rose-800' }}">
                    <div>
                        <div class="text-xs sm:text-sm font-black uppercase tracking-wide {{ $isProfitable ? 'text-emerald-900 dark:text-emerald-200' : 'text-rose-900 dark:text-rose-200' }}">
                            {{ $isProfitable ? __('messages.pl_net_profit') : __('messages.pl_net_loss') }}
                        </div>
                        <div class="text-[11px] font-bold {{ $isProfitable ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                            {{ __('messages.pl_net_margin') }}: <span class="font-mono">{{ $statement['net_margin'] }}%</span>
                        </div>
                    </div>
                    <div class="text-xl sm:text-2xl font-black font-outfit tabular-nums {{ $isProfitable ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                        {{ number_format($statement['net_profit'], 0) }} <span class="text-xs font-bold text-slate-500">{{ __('messages.currency_ks') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Expenses Visuals + Top Products + Operational Metrics (5 cols) --}}
        <div class="lg:col-span-5 space-y-2.5">

            {{-- Operational Transaction Metrics --}}
            <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-2.5">
                <h3 class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                    {{ __('messages.operational_metrics_title') }}
                </h3>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                        <div class="text-[10px] sm:text-[11px] text-slate-400 font-bold uppercase truncate">{{ __('messages.total_orders') }}</div>
                        <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 font-outfit mt-0.5 tabular-nums">{{ $statement['metrics']['order_count'] }}</div>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                        <div class="text-[10px] sm:text-[11px] text-slate-400 font-bold uppercase truncate">{{ __('messages.aov_metric') }}</div>
                        <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-outfit mt-0.5 tabular-nums truncate">{{ number_format($statement['metrics']['aov'], 0) }} <span class="text-[10px] font-normal text-slate-400">{{ __('messages.currency_ks') }}</span></div>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                        <div class="text-[10px] sm:text-[11px] text-slate-400 font-bold uppercase truncate">{{ __('messages.profit_per_order') }}</div>
                        <div class="text-xs sm:text-sm font-black font-outfit mt-0.5 tabular-nums truncate {{ $statement['metrics']['profit_per_order'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ number_format($statement['metrics']['profit_per_order'], 0) }} <span class="text-[10px] font-normal text-slate-400">{{ __('messages.currency_ks') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Service & Repair Revenue Block (if active) --}}
            @if(!empty($statement['services']['has_services']))
                <div class="p-3.5 sm:p-4 rounded-xl border border-indigo-200/80 bg-indigo-50/40 dark:border-indigo-900/60 dark:bg-indigo-950/30 shadow-xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="text-indigo-600 dark:text-indigo-400 font-bold text-xs">🔧</span>
                            <h3 class="text-xs font-black text-indigo-950 dark:text-indigo-200 uppercase tracking-wider font-outfit">
                                {{ __('messages.sidebar_service_revenue') }}
                            </h3>
                        </div>
                        <a href="{{ route('pos.reports.services', ['store_slug' => $store->slug]) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-500 transition inline-flex items-center gap-0.5">
                            <span>{{ __('messages.view_details') }}</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div class="p-2 rounded-lg bg-white dark:bg-slate-900/80 border border-indigo-100 dark:border-indigo-900/60">
                            <div class="text-[10px] text-slate-400 font-bold uppercase">{{ __('messages.pl_service_repair_revenue') }}</div>
                            <div class="text-sm font-black text-indigo-600 dark:text-indigo-400 font-outfit mt-0.5 tabular-nums">
                                {{ number_format($statement['services']['revenue'], 0) }} <span class="text-[10px] font-normal text-slate-400">{{ __('messages.currency_ks') }}</span>
                            </div>
                        </div>
                        <div class="p-2 rounded-lg bg-white dark:bg-slate-900/80 border border-indigo-100 dark:border-indigo-900/60">
                            <div class="text-[10px] text-slate-400 font-bold uppercase">{{ __('messages.pl_service_gross_profit') }}</div>
                            <div class="text-sm font-black text-emerald-600 dark:text-emerald-400 font-outfit mt-0.5 tabular-nums">
                                {{ number_format($statement['services']['gross_profit'], 0) }} <span class="text-[10px] font-normal text-slate-400">({{ $statement['services']['margin'] }}%)</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Expense Breakdown Progress Bars --}}
            <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                        {{ __('messages.pl_expense_breakdown_title') }}
                    </h3>
                    <a href="{{ route('store.admin.expenses.index', ['store_slug' => $store->slug]) }}" class="text-xs font-bold text-violet-600 hover:text-violet-500 transition inline-flex items-center gap-0.5">
                        <span>{{ __('messages.view_details') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <div class="space-y-2.5">
                    @forelse ($statement['expenses']['by_category'] as $cat)
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs font-bold text-slate-700 dark:text-slate-300">
                                <span>{{ $cat['name'] }}</span>
                                <span class="font-mono tabular-nums font-black">{{ number_format($cat['amount'], 0) }} {{ __('messages.currency_ks') }} <span class="text-slate-400 font-normal">({{ $cat['percent'] }}%)</span></span>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full transition-all" style="width: {{ $cat['percent'] }}%; background-color: {{ $cat['color'] }};"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-slate-400 py-3 text-center italic">{{ __('messages.no_expenses_in_period') }}</div>
                    @endforelse
                </div>
            </div>

            {{-- Top Profitable Products --}}
            <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-2.5">
                <h3 class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                    {{ __('messages.top_profitable_products_title') }}
                </h3>

                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($statement['top_products'] as $prod)
                        <div class="py-2 flex items-center justify-between text-xs gap-2">
                            <div class="max-w-[62%] min-w-0">
                                <div class="font-bold text-slate-900 dark:text-slate-100 truncate">{{ $prod['name'] }}</div>
                                <div class="text-[11px] text-slate-400">{{ __('messages.pl_sold_count', ['count' => (int) $prod['quantity']]) }} • {{ __('messages.pl_margin_percent', ['margin' => $prod['margin']]) }}</div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-black text-emerald-600 dark:text-emerald-400 font-outfit tabular-nums">+{{ number_format($prod['profit'], 0) }} {{ __('messages.currency_ks') }}</div>
                                <div class="text-[10px] text-slate-400 font-mono tabular-nums">{{ __('messages.pl_revenue_short') }}: {{ number_format($prod['revenue'], 0) }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-slate-400 py-3 text-center italic">{{ __('messages.no_sales_in_period') }}</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

