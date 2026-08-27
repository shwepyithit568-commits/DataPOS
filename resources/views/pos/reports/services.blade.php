@extends('layouts.admin.app')

@section('title', __('messages.sidebar_service_revenue_report') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $statusOptions = \App\POS\Models\ServiceJob::STATUSES;
    $collectionRate = $report['total_revenue'] > 0 ? round(($report['total_paid'] / $report['total_revenue']) * 100, 1) : 0;
    $profitMargin = $report['total_revenue'] > 0 ? round(($report['gross_service_profit'] / $report['total_revenue']) * 100, 1) : 0;
    $partsPercent = $report['total_revenue'] > 0 ? round(($report['total_parts_cost'] / $report['total_revenue']) * 100, 1) : 0;

    $jobStatusLabel = function (string $status): string {
        return match ($status) {
            'received' => __('messages.job_status_received'),
            'diagnosing' => __('messages.job_status_diagnosing'),
            'awaiting_approval' => __('messages.job_status_awaiting_approval'),
            'awaiting_parts' => __('messages.job_status_awaiting_parts'),
            'in_repair' => __('messages.job_status_in_repair'),
            'ready' => __('messages.job_status_ready'),
            'delivered' => __('messages.job_status_delivered'),
            'cancelled' => __('messages.job_status_cancelled'),
            'unrepairable' => __('messages.job_status_unrepairable'),
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    };

    $jobStatusBadgeClass = function (string $status): string {
        return match ($status) {
            'ready', 'delivered' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            'in_repair', 'diagnosing' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            'awaiting_parts', 'awaiting_approval' => 'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            'cancelled', 'unrepairable' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border-rose-200 dark:border-rose-800',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
        };
    };
@endphp

<div class="w-full space-y-2 sm:space-y-2.5" x-data="{ exportModalOpen: false }">

    {{-- 1. Top Action Bar & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-xl p-3 sm:p-4 border border-slate-200/90 dark:border-slate-800 shadow-xs">
        <div class="space-y-1">
            <div class="flex items-center gap-1.5 text-xs text-slate-400 font-medium">
                <a href="{{ route('store.admin.dashboard', $storeRouteParams) }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">
                    {{ __('messages.admin_dashboard') }}
                </a>
                <span>/</span>
                <a href="{{ route('store.admin.repairs.index', $storeRouteParams) }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">
                    {{ __('messages.sidebar_repair_center') }}
                </a>
                <span>/</span>
                <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ __('messages.sidebar_service_revenue_report') }}</span>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-slate-100 font-outfit tracking-tight">
                    {{ __('messages.sidebar_service_revenue_report') }}
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold font-mono bg-indigo-50 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200/70 dark:border-indigo-800/80">
                    {{ $from->translatedFormat('d M Y') }} — {{ $to->translatedFormat('d M Y') }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('store.admin.repairs.index', $storeRouteParams) }}"
               class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold transition inline-flex items-center gap-1.5 shadow-2xs">
                <span>&larr;</span>
                <span>{{ __('messages.sidebar_repair_center') }}</span>
            </a>

            {{-- Export Trigger Button --}}
            <button type="button" @click="exportModalOpen = true"
                    class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition inline-flex items-center gap-1.5 shadow-2xs active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>{{ __('messages.export') }}</span>
            </button>
        </div>
    </div>

    {{-- Export Modal --}}
    <div x-show="exportModalOpen" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div @click.away="exportModalOpen = false"
             class="w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.choose_export_format') }}</h3>
                <button @click="exportModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg">&times;</button>
            </div>
            
            <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ __('messages.select_export_file_type') }} ({{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }})
            </p>

            <div class="space-y-2">
                <a href="{{ $exportXlsxUrl }}" @click="exportModalOpen = false"
                   class="w-full flex items-center justify-between p-3 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/50 dark:bg-emerald-950/20 hover:bg-emerald-100/60 dark:hover:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300 transition text-xs font-bold">
                    <span class="flex items-center gap-2">
                        <span class="text-base">📊</span>
                        <span>{{ __('messages.export_excel_format') }}</span>
                    </span>
                    <span class="font-mono text-[11px] font-semibold px-2 py-0.5 bg-emerald-200/60 dark:bg-emerald-900/60 rounded">.xlsx</span>
                </a>

                <a href="{{ $exportCsvUrl }}" @click="exportModalOpen = false"
                   class="w-full flex items-center justify-between p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 transition text-xs font-bold">
                    <span class="flex items-center gap-2">
                        <span class="text-base">📄</span>
                        <span>{{ __('messages.export_csv_format') }}</span>
                    </span>
                    <span class="font-mono text-[11px] font-semibold px-2 py-0.5 bg-slate-200 dark:bg-slate-700 rounded">.csv</span>
                </a>
            </div>
        </div>
    </div>

    {{-- 2. Filter Toolbar --}}
    <div class="p-3 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-3">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            
            {{-- Quick Presets --}}
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 text-xs">
                @php
                    $presets = [
                        'today'      => __('messages.period_today'),
                        'yesterday'  => __('messages.period_yesterday'),
                        'this_week'  => __('messages.period_this_week'),
                        'this_month' => __('messages.period_this_month'),
                        'last_month' => __('messages.period_last_month'),
                        'this_year'  => __('messages.period_this_year'),
                    ];
                @endphp
                @foreach ($presets as $pKey => $pLabel)
                    <a href="{{ route('pos.reports.services', ['store_slug' => $store->slug, 'preset' => $pKey, 'technician_id' => request('technician_id'), 'status' => request('status')]) }}"
                       class="px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition cursor-pointer {{ ($preset ?? '') === $pKey ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                        {{ $pLabel }}
                    </a>
                @endforeach
            </div>

            {{-- Custom Form Filter --}}
            <form method="GET" action="{{ route('pos.reports.services', $storeRouteParams) }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="preset" value="custom">

                {{-- Status Filter --}}
                <select name="status" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    <option value="">{{ __('messages.report_all_statuses') }}</option>
                    @foreach ($statusOptions as $st)
                        <option value="{{ $st }}" @selected($status === $st)>{{ $jobStatusLabel($st) }}</option>
                    @endforeach
                </select>

                {{-- Technician Filter --}}
                <select name="technician_id" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    <option value="">{{ __('messages.report_all_technicians') }}</option>
                    @foreach ($technicians as $tech)
                        <option value="{{ $tech->id }}" @selected($technicianId == $tech->id)>{{ $tech->name }}</option>
                    @endforeach
                </select>

                <div class="flex items-center gap-1.5">
                    <input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ today()->toDateString() }}"
                           class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    <span class="text-xs text-slate-400">—</span>
                    <input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ today()->toDateString() }}"
                           class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                </div>

                <button type="submit" class="rounded-lg px-3.5 py-1.5 text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-2xs cursor-pointer">
                    {{ __('messages.filter') }}
                </button>

                @if($preset === 'custom' || request('technician_id') || request('status'))
                    <a href="{{ route('pos.reports.services', $storeRouteParams) }}"
                       class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                        {{ __('messages.reset') }}
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- 3. 4 High-Level Financial KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
        
        {{-- Total Service Revenue --}}
        <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.report_total_revenue') }}</span>
                <div class="w-7 h-7 rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-950/80 dark:text-sky-300 flex items-center justify-center font-bold text-xs shadow-2xs">
                    💰
                </div>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                    {{ number_format($report['total_revenue'], 0) }} <span class="text-xs font-bold text-slate-500">{{ __('messages.currency_ks') }}</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center justify-between">
                    <span>{{ __('messages.report_total_jobs') }}: <strong class="text-slate-700 dark:text-slate-300">{{ $report['count'] }}</strong></span>
                    <span class="font-mono text-emerald-600 dark:text-emerald-400">{{ $report['completed_count'] }} {{ __('messages.completed') }}</span>
                </div>
            </div>
        </div>

        {{-- Cash Collected / Paid --}}
        <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-teal-700 dark:text-teal-400 uppercase tracking-wider">{{ __('messages.report_paid') }}</span>
                <div class="w-7 h-7 rounded-lg bg-teal-100 text-teal-700 dark:bg-teal-950/80 dark:text-teal-300 flex items-center justify-center font-bold text-xs shadow-2xs">
                    💵
                </div>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-teal-600 dark:text-teal-400 font-outfit tabular-nums">
                    {{ number_format($report['total_paid'], 0) }} <span class="text-xs font-bold text-slate-500">{{ __('messages.currency_ks') }}</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center justify-between">
                    <span>{{ __('messages.collected_rate') }}:</span>
                    <span class="font-bold font-mono text-teal-600 dark:text-teal-400">{{ $collectionRate }}%</span>
                </div>
            </div>
        </div>

        {{-- Spare Parts Cost (COGS) --}}
        <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">{{ __('messages.report_total_parts_cost') }}</span>
                <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300 flex items-center justify-center font-bold text-xs shadow-2xs">
                    ⚙️
                </div>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                    {{ number_format($report['total_parts_cost'], 0) }} <span class="text-xs font-bold text-slate-500">{{ __('messages.currency_ks') }}</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center justify-between">
                    <span>{{ __('messages.pl_of_revenue') }}:</span>
                    <span class="font-bold font-mono text-amber-600 dark:text-amber-400">{{ $partsPercent }}%</span>
                </div>
            </div>
        </div>

        {{-- Gross Service Profit --}}
        <div class="p-3.5 sm:p-4 rounded-xl border border-indigo-200/90 bg-indigo-50/40 dark:border-indigo-900/80 dark:bg-indigo-950/30 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">{{ __('messages.report_gross_service_profit') }}</span>
                <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300 flex items-center justify-center font-bold text-xs shadow-2xs">
                    📈
                </div>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 font-outfit tabular-nums">
                    {{ number_format($report['gross_service_profit'], 0) }} <span class="text-xs font-bold text-slate-500">{{ __('messages.currency_ks') }}</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center justify-between">
                    <span>{{ __('messages.report_margin') }}:</span>
                    <span class="font-bold font-mono text-indigo-600 dark:text-indigo-400">{{ $profitMargin }}%</span>
                </div>
            </div>
        </div>

    </div>

    {{-- 4. Technician Breakdown & Status Summary Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5">
        
        {{-- Technician Performance Leaderboard (7 cols) --}}
        <div class="lg:col-span-7 p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                    👨‍🔧 {{ __('messages.service_technician_breakdown') }}
                </h3>
                <span class="text-[11px] text-slate-400 font-medium font-mono">{{ count($report['technicians']) }} Technicians</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @forelse ($report['technicians'] as $tech)
                    @php
                        $techProfit = max(0, $tech['revenue'] - $tech['parts_cost']);
                        $techMargin = $tech['revenue'] > 0 ? round(($techProfit / $tech['revenue']) * 100, 1) : 0;
                    @endphp
                    <div class="p-3 rounded-lg border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-slate-900 dark:text-slate-100 truncate">{{ $tech['name'] }}</span>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold font-mono bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                {{ $tech['completed'] }} / {{ $tech['jobs_count'] }} {{ __('messages.completed') }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                            <span class="text-slate-400">{{ __('messages.pl_revenue') }}:</span>
                            <span class="font-bold font-outfit text-slate-900 dark:text-slate-100 tabular-nums">{{ number_format($tech['revenue'], 0) }} {{ __('messages.currency_ks') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-400">{{ __('messages.report_profit') }}:</span>
                            <span class="font-bold font-outfit text-emerald-600 dark:text-emerald-400 tabular-nums">+{{ number_format($techProfit, 0) }} ({{ $techMargin }}%)</span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 text-center text-xs text-slate-400 py-3 italic">
                        {{ __('messages.no_data_available') }}
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Status Breakdown (5 cols) --}}
        <div class="lg:col-span-5 p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-3">
            <h3 class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                📊 {{ __('messages.service_status_distribution') }}
            </h3>

            <div class="space-y-1.5">
                @foreach ($statusOptions as $st)
                    @php
                        $count = $report['status_counts'][$st] ?? 0;
                        $pct = $report['count'] > 0 ? round(($count / $report['count']) * 100, 1) : 0;
                    @endphp
                    @if($count > 0 || in_array($st, ['received', 'in_repair', 'ready', 'delivered']))
                        <div class="flex items-center justify-between text-xs py-0.5">
                            <div class="flex items-center gap-1.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $jobStatusBadgeClass($st) }}">
                                    {{ $jobStatusLabel($st) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-800 dark:text-slate-200 font-mono">{{ $count }}</span>
                                <span class="text-[10px] text-slate-400 font-mono w-10 text-right">({{ $pct }}%)</span>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

    </div>

    {{-- 5. Detailed Service Jobs Table --}}
    <div class="rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs overflow-hidden">
        <div class="p-3 sm:p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.track_service_timeline') }}</h3>
                <p class="text-xs text-slate-400 font-mono">{{ $report['jobs']->count() }} {{ __('messages.repair_jobs') }}</p>
            </div>
            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-xs font-mono text-slate-600 dark:text-slate-400 font-bold">MMK</span>
        </div>

        @if ($report['jobs']->isEmpty())
            <div class="py-12 text-center text-slate-400 dark:text-slate-500 text-xs space-y-1.5">
                <div class="text-3xl">🛠️</div>
                <p class="font-bold text-slate-600 dark:text-slate-300">{{ __('messages.spare_parts_no_items') }}</p>
                <p class="text-[11px] text-slate-400">{{ __('messages.no_matching_records') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-3">Job / Voucher #</th>
                            <th class="py-2.5 px-3">{{ __('messages.reports_date') }}</th>
                            <th class="py-2.5 px-3">{{ __('messages.customer') }}</th>
                            <th class="py-2.5 px-3">{{ __('messages.track_service_device_info') }}</th>
                            <th class="py-2.5 px-3">{{ __('messages.report_technician') }}</th>
                            <th class="py-2.5 px-3 text-center">{{ __('messages.status') }}</th>
                            <th class="py-2.5 px-3 text-right">{{ __('messages.report_parts_cost') }}</th>
                            <th class="py-2.5 px-3 text-right">{{ __('messages.pl_revenue') }}</th>
                            <th class="py-2.5 px-3 text-right">{{ __('messages.report_paid') }}</th>
                            <th class="py-2.5 px-3 text-right">{{ __('messages.report_profit') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($report['jobs'] as $job)
                            @php
                                $finalAmt = (float) ($job->final_charge ?: $job->estimated_charge ?: 0);
                                $paidAmt = (float) $job->payments->sum('amount');
                                $partsCost = (float) $job->items->where('type', 'part')->sum('cost');
                                $profitAmt = max(0, $finalAmt - $partsCost);
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    <a href="{{ route('store.admin.repairs.show', ['store_slug' => $store->slug, 'repair' => $job->id]) }}"
                                       class="font-mono font-bold text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1">
                                        <span>{{ $job->job_number }}</span>
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                    @if ($job->voucher_no)
                                        <div class="text-[10px] text-slate-400 font-mono">#{{ $job->voucher_no }}</div>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap text-slate-600 dark:text-slate-300 font-mono text-[11px]">
                                    {{ $job->created_at?->format('d M Y, H:i') }}
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $job->contact_name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ $job->contact_phone }}</div>
                                </td>
                                <td class="py-2.5 px-3 text-slate-700 dark:text-slate-300">
                                    <div class="font-bold">{{ $job->device_type }} · {{ $job->brand }} {{ $job->model }}</div>
                                    <div class="text-[11px] text-slate-400 truncate max-w-xs">{{ $job->reported_problem }}</div>
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $job->technician?->name ?? '—' }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $jobStatusBadgeClass($job->status) }}">
                                        {{ $jobStatusLabel($job->status) }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono tabular-nums text-amber-600 dark:text-amber-400">
                                    {{ number_format($partsCost, 0) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono tabular-nums font-bold text-slate-900 dark:text-slate-100">
                                    {{ number_format($finalAmt, 0) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono tabular-nums text-teal-600 dark:text-teal-400">
                                    {{ number_format($paidAmt, 0) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono tabular-nums font-black text-emerald-600 dark:text-emerald-400">
                                    +{{ number_format($profitAmt, 0) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 font-bold">
                        <tr>
                            <td colspan="6" class="py-3 px-3 text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px]">
                                {{ __('messages.total') }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono tabular-nums text-amber-600 dark:text-amber-400">
                                {{ number_format($report['total_parts_cost'], 0) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono tabular-nums text-indigo-600 dark:text-indigo-400 text-sm font-black">
                                {{ number_format($report['total_revenue'], 0) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono tabular-nums text-teal-600 dark:text-teal-400">
                                {{ number_format($report['total_paid'], 0) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono tabular-nums text-emerald-600 dark:text-emerald-400 text-sm font-black">
                                +{{ number_format($report['gross_service_profit'], 0) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
