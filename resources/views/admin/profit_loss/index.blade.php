@extends('layouts.admin.app')

@section('title', __('messages.profit_loss_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    $isProfitable = $statement['net_profit'] >= 0;
    $xlsxUrl = str_contains($exportUrl, '?') ? $exportUrl . '&format=xlsx' : $exportUrl . '?format=xlsx';
    $csvUrl = str_contains($exportUrl, '?') ? $exportUrl . '&format=csv' : $exportUrl . '?format=csv';
@endphp

<div class="w-full space-y-0.5 pb-6" x-data="{ exportModalOpen: false }">

    {{-- ============================================================
         1. TOP ULTRA-DENSE HEADER BANNER (Standard v4.1)
         ============================================================ --}}
    <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none transition">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}"
               class="h-6 w-6 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition active:scale-95 shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="w-6 h-6 rounded bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs shrink-0">
                <span>📊</span>
            </div>
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/60 px-1.5 py-0.5 rounded border border-violet-200/50 dark:border-violet-800/50 truncate max-w-[120px] sm:max-w-none">
                    {{ $store->name }}
                </span>
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight truncate">
                    {{ __('messages.profit_loss_title') }}
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-violet-50 text-violet-700 dark:bg-violet-950/80 dark:text-violet-300 border border-violet-200/80 dark:border-violet-800/80 font-mono shrink-0">
                    {{ $statement['period']['label'] }}
                </span>
            </div>
        </div>

        {{-- Top Right Actions (Print A4 & Dual Export Button) --}}
        <div class="flex items-center gap-1 sm:gap-1.5 shrink-0">
            {{-- Print A4 Statement --}}
            <a href="{{ route('store.admin.profit_loss.statement', array_merge(['store_slug' => $store->slug], request()->all())) }}"
               target="_blank"
               class="h-7 px-2 sm:px-2.5 rounded text-[11px] sm:text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition inline-flex items-center gap-1 active:scale-95 shadow-2xs">
                <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>{{ __('messages.print_statement_a4') }}</span>
            </a>

            {{-- Dual Export Button (Excel & CSV) --}}
            <div class="relative inline-flex items-center">
                <div class="h-7 inline-flex items-stretch rounded bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs overflow-hidden border border-emerald-600">
                    <a href="{{ $xlsxUrl }}" download title="{{ __('messages.export') }} Excel (.xlsx)"
                       class="inline-flex items-center gap-1 px-2.5 text-white hover:bg-emerald-700 text-[11px] sm:text-xs font-bold transition active:scale-95">
                        <svg class="w-3.5 h-3.5 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 14l5-5 5 5M12 3v12" />
                        </svg>
                        <span>{{ __('messages.export') }}</span>
                        <span class="text-[9px] bg-white/25 text-white px-1 py-0.2 rounded font-mono uppercase font-black">XLSX</span>
                    </a>
                    <button type="button" @click="exportModalOpen = true"
                            class="inline-flex items-center justify-center w-6 px-1 bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white border-l border-emerald-500/80 transition cursor-pointer"
                            title="Export Options (Excel / CSV)" aria-label="Export Options">
                        <svg class="w-3 h-3 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Export Modal Teleport --}}
    <template x-teleport="body">
        <div x-show="exportModalOpen" x-cloak
             style="z-index: 99999;"
             class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-950/60 backdrop-blur-xs"
             @click.self="exportModalOpen = false"
             @keydown.escape.window="exportModalOpen = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl p-4 space-y-3 text-left"
                 @click.stop>
                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xs shadow-inner font-bold">📤</span>
                        <div>
                            <h3 class="text-xs font-black text-slate-900 dark:text-slate-100">{{ __('messages.export') }}</h3>
                            <p class="text-[10px] text-slate-400">{{ __('messages.profit_loss_title') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="exportModalOpen = false"
                            class="w-6 h-6 rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Format Selection Options --}}
                <div class="space-y-2">
                    {{-- Excel Option --}}
                    <a href="{{ $xlsxUrl }}" download @click="exportModalOpen = false"
                       class="group flex items-center gap-3 p-2.5 rounded-lg border border-slate-200/90 dark:border-slate-700/80 hover:border-emerald-500/80 dark:hover:border-emerald-500/80 bg-white dark:bg-slate-800/80 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 transition-all shadow-xs active:scale-[0.99]">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 grid place-items-center text-sm font-black shrink-0">
                            📊
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-black text-slate-900 dark:text-slate-100 group-hover:text-emerald-700 dark:group-hover:text-emerald-300">{{ __('messages.export_excel_format') }}</span>
                                <span class="text-[9px] font-black uppercase px-1 py-0.2 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">XLSX</span>
                            </div>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                                {{ __('messages.pl_export_excel_desc') }}
                            </p>
                        </div>
                    </a>

                    {{-- CSV Option --}}
                    <a href="{{ $csvUrl }}" download @click="exportModalOpen = false"
                       class="group flex items-center gap-3 p-2.5 rounded-lg border border-slate-200/90 dark:border-slate-700/80 hover:border-sky-500/80 dark:hover:border-sky-500/80 bg-white dark:bg-slate-800/80 hover:bg-sky-50/50 dark:hover:bg-sky-950/20 transition-all shadow-xs active:scale-[0.99]">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 dark:bg-sky-950/80 text-sky-600 dark:text-sky-400 grid place-items-center text-sm font-black shrink-0">
                            📄
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-black text-slate-900 dark:text-slate-100 group-hover:text-sky-700 dark:group-hover:text-sky-300">{{ __('messages.export_csv_format') }}</span>
                                <span class="text-[9px] font-black uppercase px-1 py-0.2 rounded bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300">CSV</span>
                            </div>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                                {{ __('messages.pl_export_csv_desc') }}
                            </p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </template>

    {{-- ============================================================
         2. PERIOD & BRANCH FILTER TOOLBAR (Standard v4.1)
         ============================================================ --}}
    <div class="px-2 py-1.5 rounded border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-1.5 transition">
        {{-- Quick Date Preset Pills --}}
        <div class="flex items-center gap-1 overflow-x-auto pb-0.5 lg:pb-0 text-xs scrollbar-thin">
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
                   class="h-7 px-2 sm:px-2.5 rounded text-[11px] font-bold whitespace-nowrap transition inline-flex items-center gap-1 shadow-2xs {{ $preset === $key ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    <span>{{ $label }}</span>
                    @if ($preset === $key)
                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Custom Date Range & Branch Form --}}
        <form method="GET" action="{{ route('store.admin.profit_loss.index', ['store_slug' => $store->slug]) }}" class="flex flex-wrap items-center gap-1">
            <input type="hidden" name="preset" value="custom">

            {{-- Branch Selector --}}
            @if(isset($branches) && $branches->count() > 0)
                <div class="relative inline-flex items-center">
                    <select name="branch_id"
                            class="h-7 pl-2 pr-6 text-xs font-bold rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 outline-none cursor-pointer">
                        <option value="">{{ __('messages.pl_all_branches') }}</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ (string) request('branch_id') === (string) $b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Date Inputs --}}
            <div class="flex items-center gap-1">
                <input type="date"
                       name="from"
                       value="{{ $from->toDateString() }}"
                       class="h-7 px-2 text-xs font-mono font-bold rounded border border-slate-200 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 dark:border-slate-700 outline-none shadow-2xs">
                <span class="text-xs text-slate-400 font-bold">—</span>
                <input type="date"
                       name="to"
                       value="{{ $to->toDateString() }}"
                       class="h-7 px-2 text-xs font-mono font-bold rounded border border-slate-200 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 dark:border-slate-700 outline-none shadow-2xs">
            </div>

            <button type="submit" class="h-7 px-2.5 text-xs font-bold rounded bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-700 dark:hover:bg-slate-600 transition shadow-2xs cursor-pointer active:scale-95">
                {{ __('messages.filter') }}
            </button>

            @if(request()->filled('from') || request()->filled('to') || request()->filled('branch_id') || $preset !== 'this_month')
                <a href="{{ route('store.admin.profit_loss.index', ['store_slug' => $store->slug]) }}"
                   class="h-7 px-2 text-xs font-bold rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition shadow-2xs inline-flex items-center gap-1"
                   title="{{ __('messages.clear') }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>{{ __('messages.clear') }}</span>
                </a>
            @endif
        </form>
    </div>

    {{-- ============================================================
         3. 4 KEY FINANCIAL KPI STAT CARDS (Centered Row-based Standard v4.1)
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1 select-none">

        {{-- Card 1: Net Sales Revenue --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border-sky-100 dark:border-sky-900/50">
                💵
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.pl_net_revenue') }}
                </div>
                <div class="text-sm sm:text-base font-black text-sky-600 dark:text-sky-400 font-mono tracking-tight">
                    {{ format_currency($statement['revenue']['net_sales'], $store) }}
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate flex items-center gap-1">
                    <span>{{ __('messages.pl_gross_sales') }}: <strong class="font-mono text-slate-600 dark:text-slate-300">{{ format_currency($statement['revenue']['gross_sales'], $store) }}</strong></span>
                    @if($comparison['revenue_growth'] != 0)
                        <span class="font-bold font-mono {{ $comparison['revenue_growth'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ $comparison['revenue_growth'] > 0 ? '+' : '' }}{{ $comparison['revenue_growth'] }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card 2: Cost of Goods Sold (COGS) --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border-amber-100 dark:border-amber-900/50">
                📦
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.pl_cost_of_goods_sold') }}
                </div>
                <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight">
                    {{ format_currency($statement['cogs']['net_cogs'], $store) }}
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate font-mono">
                    {{ $statement['revenue']['net_sales'] > 0 ? round(($statement['cogs']['net_cogs'] / $statement['revenue']['net_sales']) * 100, 1) : 0 }}% {{ __('messages.pl_of_revenue') }}
                </div>
            </div>
        </div>

        {{-- Card 3: Gross Profit & Margin --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border-indigo-100 dark:border-indigo-900/50">
                📈
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.pl_gross_profit') }}
                </div>
                <div class="text-sm sm:text-base font-black text-indigo-600 dark:text-indigo-400 font-mono tracking-tight">
                    {{ format_currency($statement['gross_profit'], $store) }}
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate">
                    <span>{{ __('messages.pl_gross_margin') }}:</span>
                    <span class="font-bold font-mono text-indigo-600 dark:text-indigo-400">{{ $statement['gross_margin'] }}%</span>
                </div>
            </div>
        </div>

        {{-- Card 4: Net Profit / Net Loss --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition {{ $isProfitable ? 'bg-emerald-50/50 dark:bg-emerald-950/30 border-emerald-300 dark:border-emerald-800' : 'bg-rose-50/50 dark:bg-rose-950/30 border-rose-300 dark:border-rose-800' }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border {{ $isProfitable ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-rose-600 text-white border-rose-600' }}">
                {{ $isProfitable ? '🏆' : '⚠️' }}
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate {{ $isProfitable ? 'text-emerald-900 dark:text-emerald-200' : 'text-rose-900 dark:text-rose-200' }}">
                    {{ $isProfitable ? __('messages.pl_net_profit') : __('messages.pl_net_loss') }}
                </div>
                <div class="text-sm sm:text-base font-black font-mono tracking-tight {{ $isProfitable ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ format_currency($statement['net_profit'], $store) }}
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate flex items-center gap-1">
                    <span>{{ __('messages.pl_net_margin') }}: <strong class="font-mono {{ $isProfitable ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $statement['net_margin'] }}%</strong></span>
                    @if($comparison['profit_growth'] != 0)
                        <span class="font-bold font-mono {{ $comparison['profit_growth'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ $comparison['profit_growth'] > 0 ? '+' : '' }}{{ $comparison['profit_growth'] }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- ============================================================
         4. MAIN FINANCIAL STATEMENTS (Left: Breakdown, Right: Analytics)
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-0.5 sm:gap-1 items-start">

        {{-- Left: Formal Income Statement Breakdown Table (7 cols) --}}
        <div class="lg:col-span-7 rounded border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs overflow-hidden">
            <div class="px-2.5 py-1.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.income_statement_breakdown') }}</h3>
                    <p class="text-[10px] text-slate-400 font-mono">{{ $statement['period']['label'] }}</p>
                </div>
                <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-mono text-slate-600 dark:text-slate-400 font-bold">
                    {{ $store->currency ?? 'MMK' }}
                </span>
            </div>

            <div class="p-2 sm:p-2.5 space-y-2">

                {{-- 1. Revenue Section --}}
                <div class="space-y-1">
                    <div class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">1. {{ __('messages.pl_revenue') }}</div>
                    <div class="space-y-0.5 text-xs">
                        <div class="flex justify-between text-slate-700 dark:text-slate-300 py-0.5">
                            <span>{{ __('messages.pl_gross_sales') }}</span>
                            <span class="font-mono tabular-nums font-semibold">{{ format_currency($statement['revenue']['gross_sales'], $store) }}</span>
                        </div>
                        @if($statement['revenue']['discounts'] > 0)
                            <div class="flex justify-between text-slate-500 text-xs pl-2.5 py-0.5">
                                <span>— {{ __('messages.pl_discounts_given') }}</span>
                                <span class="font-mono tabular-nums text-rose-500">- {{ format_currency($statement['revenue']['discounts'], $store) }}</span>
                            </div>
                        @endif
                        @if($statement['revenue']['returns'] > 0)
                            <div class="flex justify-between text-slate-500 text-xs pl-2.5 py-0.5">
                                <span>— {{ __('messages.pl_returns_refunds') }}</span>
                                <span class="font-mono tabular-nums text-rose-500">- {{ format_currency($statement['revenue']['returns'], $store) }}</span>
                            </div>
                        @endif
                        @if(!empty($statement['services']['has_services']))
                            <div class="flex justify-between text-slate-500 text-xs pl-2.5 py-0.5">
                                <span>+ {{ __('messages.pl_service_repair_revenue') }} ({{ $statement['services']['jobs_count'] }})</span>
                                <span class="font-mono tabular-nums text-indigo-600 dark:text-indigo-400 font-semibold">+ {{ format_currency($statement['services']['revenue'], $store) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-bold pt-1 border-t border-slate-100 dark:border-slate-800 text-slate-900 dark:text-slate-100">
                            <span>{{ !empty($statement['services']['has_services']) ? __('messages.pl_total_combined_revenue') : __('messages.pl_net_revenue') }}</span>
                            <span class="font-mono tabular-nums text-sky-600 dark:text-sky-400 font-black">{{ format_currency($statement['revenue']['total_revenue'] ?? $statement['revenue']['net_sales'], $store) }}</span>
                        </div>
                    </div>
                </div>

                {{-- 2. Cost of Goods Sold Section --}}
                <div class="space-y-1 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">2. {{ __('messages.pl_cost_of_goods_sold') }} (COGS)</div>
                    <div class="space-y-0.5 text-xs">
                        <div class="flex justify-between text-slate-700 dark:text-slate-300 py-0.5">
                            <span>{{ __('messages.pl_gross_cogs') }}</span>
                            <span class="font-mono tabular-nums font-semibold">{{ format_currency($statement['cogs']['gross_cogs'], $store) }}</span>
                        </div>
                        @if($statement['cogs']['returns_cogs'] > 0)
                            <div class="flex justify-between text-slate-500 text-xs pl-2.5 py-0.5">
                                <span>— {{ __('messages.pl_returned_goods_cost') }}</span>
                                <span class="font-mono tabular-nums text-emerald-600">- {{ format_currency($statement['cogs']['returns_cogs'], $store) }}</span>
                            </div>
                        @endif
                        @if(!empty($statement['services']['has_services']) && $statement['services']['parts_cost'] > 0)
                            <div class="flex justify-between text-slate-500 text-xs pl-2.5 py-0.5">
                                <span>+ {{ __('messages.pl_spare_parts_cost') }}</span>
                                <span class="font-mono tabular-nums text-amber-600 font-semibold">+ {{ format_currency($statement['services']['parts_cost'], $store) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-bold pt-1 border-t border-slate-100 dark:border-slate-800 text-slate-900 dark:text-slate-100">
                            <span>{{ !empty($statement['services']['has_services']) ? __('messages.pl_total_combined_cogs') : __('messages.pl_net_cogs') }}</span>
                            <span class="font-mono tabular-nums text-amber-600 dark:text-amber-400 font-black">{{ format_currency($statement['cogs']['total_cogs'] ?? $statement['cogs']['net_cogs'], $store) }}</span>
                        </div>
                    </div>
                </div>

                {{-- 3. Gross Profit Row --}}
                <div class="p-2 rounded bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 flex items-center justify-between shadow-2xs">
                    <div>
                        <div class="text-xs font-black text-indigo-900 dark:text-indigo-300 uppercase tracking-wide">{{ __('messages.pl_gross_profit') }}</div>
                        <div class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold">
                            {{ __('messages.pl_gross_margin') }}: {{ $statement['gross_margin'] }}%
                            @if(!empty($statement['services']['has_services']) && $statement['services']['revenue'] > 0)
                                <span class="text-slate-400 font-normal ml-1">({{ __('messages.sidebar_service') }}: {{ $statement['services']['margin'] }}%)</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-sm sm:text-base font-black text-indigo-900 dark:text-indigo-200 font-mono tabular-nums">
                        {{ format_currency($statement['gross_profit'], $store) }}
                    </div>
                </div>

                {{-- 4. Operating Expenses Section --}}
                <div class="space-y-1 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div class="text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">4. {{ __('messages.pl_operating_expenses') }}</div>
                    <div class="space-y-0.5 text-xs">
                        @forelse ($statement['expenses']['by_category'] as $cat)
                            <div class="flex items-center justify-between text-slate-700 dark:text-slate-300 py-0.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $cat['color'] }};"></span>
                                    <span>{{ $cat['name'] }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">({{ $cat['percent'] }}%)</span>
                                </div>
                                <span class="font-mono tabular-nums text-slate-600 dark:text-slate-400 font-semibold">{{ format_currency($cat['amount'], $store) }}</span>
                            </div>
                        @empty
                            <div class="text-xs text-slate-400 italic py-1">{{ __('messages.no_expenses_in_period') }}</div>
                        @endforelse
                        <div class="flex justify-between font-bold pt-1 border-t border-slate-100 dark:border-slate-800 text-slate-900 dark:text-slate-100">
                            <span>{{ __('messages.pl_total_operating_expenses') }}</span>
                            <span class="font-mono tabular-nums text-rose-600 dark:text-rose-400 font-black">{{ format_currency($statement['expenses']['total'], $store) }}</span>
                        </div>
                    </div>
                </div>

                {{-- 5. Final Net Profit / Loss Highlight Box --}}
                <div class="p-2.5 rounded border flex items-center justify-between shadow-2xs {{ $isProfitable ? 'bg-emerald-50 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800' : 'bg-rose-50 border-rose-200 dark:bg-rose-950/40 dark:border-rose-800' }}">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wide {{ $isProfitable ? 'text-emerald-900 dark:text-emerald-200' : 'text-rose-900 dark:text-rose-200' }}">
                            {{ $isProfitable ? __('messages.pl_net_profit') : __('messages.pl_net_loss') }}
                        </div>
                        <div class="text-[10px] font-bold {{ $isProfitable ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                            {{ __('messages.pl_net_margin') }}: <span class="font-mono">{{ $statement['net_margin'] }}%</span>
                        </div>
                    </div>
                    <div class="text-base sm:text-lg font-black font-mono tabular-nums {{ $isProfitable ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                        {{ format_currency($statement['net_profit'], $store) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Analytics, Metrics & Breakdown (5 cols) --}}
        <div class="lg:col-span-5 space-y-0.5 sm:space-y-1">

            {{-- Operational Transaction Metrics --}}
            <div class="p-2.5 rounded border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-1.5">
                <h3 class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider">
                    {{ __('messages.operational_metrics_title') }}
                </h3>
                <div class="grid grid-cols-3 gap-1 text-center">
                    <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                        <div class="text-[10px] text-slate-400 font-bold uppercase truncate">{{ __('messages.total_orders') }}</div>
                        <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-mono mt-0.5 tabular-nums">{{ format_quantity($statement['metrics']['order_count'], $store) }}</div>
                    </div>
                    <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                        <div class="text-[10px] text-slate-400 font-bold uppercase truncate">{{ __('messages.aov_metric') }}</div>
                        <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-mono mt-0.5 tabular-nums truncate">{{ format_currency($statement['metrics']['aov'], $store) }}</div>
                    </div>
                    <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                        <div class="text-[10px] text-slate-400 font-bold uppercase truncate">{{ __('messages.profit_per_order') }}</div>
                        <div class="text-xs sm:text-sm font-black font-mono mt-0.5 tabular-nums truncate {{ $statement['metrics']['profit_per_order'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ format_currency($statement['metrics']['profit_per_order'], $store) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Service & Repair Revenue Block (if active) --}}
            @if(!empty($statement['services']['has_services']))
                <div class="p-2.5 rounded border border-indigo-200/80 bg-indigo-50/40 dark:border-indigo-900/60 dark:bg-indigo-950/30 shadow-2xs space-y-1.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1">
                            <span class="text-indigo-600 dark:text-indigo-400 font-bold text-xs">🔧</span>
                            <h3 class="text-xs font-black text-indigo-950 dark:text-indigo-200 uppercase tracking-wider">
                                {{ __('messages.sidebar_service_revenue') }}
                            </h3>
                        </div>
                        <a href="{{ route('store.admin.repairs.index', ['store_slug' => $store->slug]) }}" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-500 transition inline-flex items-center gap-0.5">
                            <span>{{ __('messages.view_details') }}</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                    <div class="grid grid-cols-2 gap-1 text-center">
                        <div class="p-1.5 rounded bg-white dark:bg-slate-900/80 border border-indigo-100 dark:border-indigo-900/60">
                            <div class="text-[10px] text-slate-400 font-bold uppercase">{{ __('messages.pl_service_repair_revenue') }}</div>
                            <div class="text-xs sm:text-sm font-black text-indigo-600 dark:text-indigo-400 font-mono mt-0.5 tabular-nums">
                                {{ format_currency($statement['services']['revenue'], $store) }}
                            </div>
                        </div>
                        <div class="p-1.5 rounded bg-white dark:bg-slate-900/80 border border-indigo-100 dark:border-indigo-900/60">
                            <div class="text-[10px] text-slate-400 font-bold uppercase">{{ __('messages.pl_service_gross_profit') }}</div>
                            <div class="text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5 tabular-nums">
                                {{ format_currency($statement['services']['gross_profit'], $store) }} <span class="text-[9px] font-normal text-slate-400">({{ $statement['services']['margin'] }}%)</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Expense Breakdown Progress Bars --}}
            <div class="p-2.5 rounded border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider">
                        {{ __('messages.pl_expense_breakdown_title') }}
                    </h3>
                    <a href="{{ route('store.admin.expenses.index', ['store_slug' => $store->slug]) }}" class="text-[10px] font-bold text-violet-600 hover:text-violet-500 transition inline-flex items-center gap-0.5">
                        <span>{{ __('messages.view_details') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <div class="space-y-1.5">
                    @forelse ($statement['expenses']['by_category'] as $cat)
                        <div class="space-y-0.5">
                            <div class="flex justify-between text-xs font-bold text-slate-700 dark:text-slate-300">
                                <span>{{ $cat['name'] }}</span>
                                <span class="font-mono tabular-nums font-black">{{ format_currency($cat['amount'], $store) }} <span class="text-slate-400 font-normal">({{ $cat['percent'] }}%)</span></span>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full transition-all" style="width: {{ $cat['percent'] }}%; background-color: {{ $cat['color'] }};"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-slate-400 py-2 text-center italic">{{ __('messages.no_expenses_in_period') }}</div>
                    @endforelse
                </div>
            </div>

            {{-- Top Profitable Products --}}
            <div class="p-2.5 rounded border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-1.5">
                <h3 class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider">
                    {{ __('messages.top_profitable_products_title') }}
                </h3>

                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($statement['top_products'] as $prod)
                        <div class="py-1.5 flex items-center justify-between text-xs gap-2">
                            <div class="max-w-[62%] min-w-0">
                                <div class="font-bold text-slate-900 dark:text-slate-100 truncate">{{ $prod['name'] }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ __('messages.pl_sold_count', ['count' => (int) $prod['quantity']]) }} • {{ __('messages.pl_margin_percent', ['margin' => $prod['margin']]) }}</div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-black text-emerald-600 dark:text-emerald-400 font-mono tabular-nums">+{{ format_currency($prod['profit'], $store) }}</div>
                                <div class="text-[10px] text-slate-400 font-mono tabular-nums">{{ __('messages.pl_revenue_short') }}: {{ format_currency($prod['revenue'], $store) }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-slate-400 py-2 text-center italic">{{ __('messages.no_sales_in_period') }}</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
