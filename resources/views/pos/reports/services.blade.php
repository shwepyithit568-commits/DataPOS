@extends('layouts.pos.app')

@section('title', __('messages.sidebar_service_jobs') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="mx-auto max-w-6xl px-4 py-6 space-y-6">

    {{-- Tabs Header --}}
    @include('pos.reports._tabs', ['active' => 'services'])

    {{-- Filter Toolbar --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm space-y-3">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            
            {{-- Quick Presets --}}
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 text-xs">
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
                    <a href="{{ route('pos.reports.services', ['store_slug' => $store->slug, 'preset' => $pKey, 'technician_id' => request('technician_id'), 'status' => request('status')]) }}"
                       class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition {{ ($preset ?? '') === $pKey ? 'bg-sky-600 text-white shadow-sm shadow-sky-500/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                        {{ $pLabel }}
                    </a>
                @endforeach
            </div>

            {{-- Custom Form + Actions --}}
            <form method="GET" action="{{ route('pos.reports.services', ['store_slug' => $store->slug]) }}"
                  class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="preset" value="custom">

                {{-- Status Filter --}}
                <select name="status" onchange="this.form.submit()" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500">
                    <option value="">{{ __('messages.all_types') }}</option>
                    @foreach (\App\POS\Models\ServiceJob::STATUSES as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                    @endforeach
                </select>

                {{-- Technician Filter --}}
                <select name="technician_id" onchange="this.form.submit()" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500">
                    <option value="">{{ __('messages.reports_all_cashiers') }}</option>
                    @foreach ($technicians as $tech)
                        <option value="{{ $tech->id }}" @selected(request('technician_id') == $tech->id)>{{ $tech->name }}</option>
                    @endforeach
                </select>

                <div class="flex items-center gap-1.5">
                    <input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ today()->toDateString() }}"
                           class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500">
                    <span class="text-xs text-slate-400">—</span>
                    <input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ today()->toDateString() }}"
                           class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500">
                </div>

                <button type="submit" class="rounded-xl px-3.5 py-1 text-xs font-bold bg-slate-800 hover:bg-slate-700 text-white dark:bg-slate-700 dark:hover:bg-slate-600 transition">
                    {{ __('messages.filter') }}
                </button>

                <a href="{{ route('pos.reports.services.export', ['store_slug' => $store->slug, 'preset' => $preset ?? 'custom', 'technician_id' => request('technician_id'), 'status' => request('status'), 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                   class="rounded-xl px-3 py-1 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    <span>CSV</span>
                </a>
            </form>
        </div>
    </div>

    {{-- 4 Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        {{-- Total Service Revenue --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                <span>{{ __('messages.reports_grand_total') }}</span>
                <span class="p-1.5 rounded-lg bg-teal-50 text-teal-600 dark:bg-teal-950/60 dark:text-teal-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-teal-600 dark:text-teal-400 font-outfit tabular-nums">
                Ks {{ number_format($report['total_revenue']) }}
            </p>
            <p class="text-[11px] text-slate-400">{{ __('messages.pl_net_revenue') }}</p>
        </div>

        {{-- Total Service Jobs --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                <span>{{ __('messages.sidebar_service_jobs') }}</span>
                <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                {{ number_format($report['count']) }}
            </p>
            <p class="text-[11px] text-slate-400">{{ __('messages.total_orders') }}</p>
        </div>

        {{-- Completed / Delivered --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                <span>{{ __('messages.completed') }}</span>
                <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-outfit tabular-nums">
                {{ number_format($report['completed_count']) }}
            </p>
            <p class="text-[11px] text-slate-400">{{ __('messages.track_service_pickup_ready') }}</p>
        </div>

        {{-- Active / In-Repair --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                <span>{{ __('messages.pending') }} / In Progress</span>
                <span class="p-1.5 rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-amber-600 dark:text-amber-400 font-outfit tabular-nums">
                {{ number_format($report['pending_count']) }}
            </p>
            <p class="text-[11px] text-slate-400">{{ __('messages.track_service_in_progress') }}</p>
        </div>
    </div>

    {{-- Technician Breakdown --}}
    @if (!empty($report['technicians']))
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                {{ __('messages.cashier_performance_leaderboard') }} (Technicians)
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($report['technicians'] as $tech)
                    <div class="p-3.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-slate-800 dark:text-slate-200">{{ $tech['name'] }}</span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300">
                                {{ $tech['completed'] }} / {{ $tech['jobs_count'] }} {{ __('messages.completed') }}
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between text-xs pt-1 border-t border-slate-200/50 dark:border-slate-700/50">
                            <span class="text-slate-500">{{ __('messages.revenue') }}:</span>
                            <span class="font-black text-slate-900 dark:text-slate-100">Ks {{ number_format($tech['revenue']) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Service Jobs Table --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">
                {{ __('messages.track_service_timeline') }}
            </h2>
            <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                {{ $report['jobs']->count() }} {{ __('messages.transactions') }}
            </span>
        </div>

        @if ($report['jobs']->isEmpty())
            <div class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm space-y-2">
                <div class="text-3xl">🛠️</div>
                <p>{{ __('messages.spare_parts_no_items') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-3">Job / Voucher #</th>
                            <th class="py-3 px-3">{{ __('messages.reports_date') }}</th>
                            <th class="py-3 px-3">{{ __('messages.customer') }}</th>
                            <th class="py-3 px-3">{{ __('messages.track_service_device_info') }}</th>
                            <th class="py-3 px-3">{{ __('messages.cashier') }} (Tech)</th>
                            <th class="py-3 px-3 text-right">{{ __('messages.total') }}</th>
                            <th class="py-3 px-3 text-center">{{ __('messages.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($report['jobs'] as $job)
                            @php
                                $finalAmt = (float) ($job->final_charge ?: $job->estimated_charge ?: 0);
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-3">
                                    <a href="{{ route('store.admin.service_jobs.show', ['store_slug' => $store->slug, 'job' => $job->id]) }}"
                                       class="font-mono font-bold text-sky-600 dark:text-sky-400 hover:underline inline-flex items-center gap-1">
                                        <span>{{ $job->job_number }}</span>
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    </a>
                                    @if ($job->voucher_no)
                                        <div class="text-[10px] text-slate-400 font-mono">#{{ $job->voucher_no }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap text-slate-600 dark:text-slate-300">
                                    {{ $job->created_at?->format('d M Y, H:i') }}
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $job->contact_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $job->contact_phone }}</div>
                                </td>
                                <td class="py-3 px-3 text-slate-700 dark:text-slate-300">
                                    <div class="font-bold">{{ $job->device_type }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $job->brand }} {{ $job->model }}</div>
                                </td>
                                <td class="py-3 px-3 font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $job->technician?->name ?? '—' }}
                                </td>
                                <td class="py-3 px-3 text-right font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                                    Ks {{ number_format($finalAmt) }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold
                                        @if (in_array($job->status, ['ready', 'delivered'])) bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300
                                        @elseif (in_array($job->status, ['in_repair', 'diagnosing'])) bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300
                                        @elseif (in_array($job->status, ['cancelled', 'unrepairable'])) bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300
                                        @else bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
