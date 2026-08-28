@extends('layouts.admin.app')

@section('title', __('messages.reports_cash') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5">
    {{-- Page Header --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
            <div class="min-w-0">
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200/80 dark:border-emerald-800 text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-300">
                    <span>💵 {{ __('messages.sidebar_reports') }}</span>
                    <span class="text-emerald-400">·</span>
                    <span>{{ __('messages.reports_cash') }}</span>
                </div>
                <h1 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-slate-100 font-outfit mt-0.5 truncate">
                    {{ __('messages.reports_cash') }}
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 truncate">
                    {{ $store->name }} · {{ __('messages.reports_cash') }}
                </p>
            </div>

            {{-- Header Actions --}}
            <div class="flex items-center gap-1.5 shrink-0">
                <a href="{{ route('pos.reports.sales', ['store_slug' => $store->slug]) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 text-xs font-bold transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>{{ __('messages.reports_sales') }}</span>
                </a>

                <a href="{{ url('/store/' . $store->slug . '/pos') }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-100 dark:hover:bg-white dark:text-slate-900 text-xs font-bold transition shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>{{ __('messages.back_to_pos') }}</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 shadow-2xs">
        <form method="GET" action="{{ url('/store/' . $store->slug . '/pos/reports/cash') }}"
              class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300">
                <span>{{ __('messages.reports_from') }}:</span>
                <input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ today()->toDateString() }}"
                       class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500 shadow-2xs">
            </div>
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300">
                <span>{{ __('messages.reports_to') }}:</span>
                <input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ today()->toDateString() }}"
                       class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500 shadow-2xs">
            </div>
            <button type="submit" class="rounded-lg px-3.5 py-1 text-xs font-bold bg-sky-600 hover:bg-sky-500 text-white transition shadow-2xs">
                {{ __('messages.reports_filter') }}
            </button>

            {{-- Export Actions (Excel & CSV) & Print --}}
            <div class="flex items-center gap-1 ml-auto">
                <a href="{{ route('pos.reports.cash.export', ['store_slug' => $store->slug, 'preset' => $preset ?? 'custom', 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'format' => 'xlsx']) }}"
                   class="rounded-lg px-2.5 py-1 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition inline-flex items-center gap-1"
                   title="Export Excel (.xlsx)">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" /></svg>
                    <span>Excel</span>
                </a>
                <a href="{{ route('pos.reports.cash.export', ['store_slug' => $store->slug, 'preset' => $preset ?? 'custom', 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'format' => 'csv']) }}"
                   class="rounded-lg px-2.5 py-1 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-1"
                   title="Export CSV (.csv)">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    <span>CSV</span>
                </a>
                <button type="button" @click="window.print()"
                        class="rounded-lg px-2.5 py-1 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-1"
                        title="Print Report">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span class="hidden sm:inline">{{ __('messages.print') }}</span>
                </button>
            </div>
        </form>
    </div>

    {{-- 4 Metric Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-2.5">
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider">{{ __('messages.reports_shift_count') }}</span>
                <span class="p-1 rounded-md bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p class="text-base sm:text-xl font-black font-mono mt-1 tabular-nums text-slate-900 dark:text-slate-100">{{ $report['shift_count'] }}</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="flex items-center justify-between text-sky-600 dark:text-sky-400">
                <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider">{{ __('messages.expected_cash') }}</span>
                <span class="p-1 rounded-md bg-sky-50 dark:bg-sky-950/80 text-sky-600">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </span>
            </div>
            <p class="text-base sm:text-xl font-black font-mono mt-1 tabular-nums text-sky-700 dark:text-sky-300">Ks {{ number_format((float) $report['expected']) }}</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider">{{ __('messages.actual_cash') }}</span>
                <span class="p-1 rounded-md bg-emerald-50 dark:bg-emerald-950/80 text-emerald-600">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <p class="text-base sm:text-xl font-black font-mono mt-1 tabular-nums text-emerald-700 dark:text-emerald-300">Ks {{ number_format((float) $report['actual']) }}</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="flex items-center justify-between {{ (float) $report['difference'] < 0 ? 'text-rose-600 dark:text-rose-400' : ((float) $report['difference'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider">{{ __('messages.difference') }}</span>
                <span class="p-1 rounded-md {{ (float) $report['difference'] < 0 ? 'bg-rose-50 dark:bg-rose-950/80 text-rose-600' : ((float) $report['difference'] > 0 ? 'bg-amber-50 dark:bg-amber-950/80 text-amber-600' : 'bg-emerald-50 dark:bg-emerald-950/80 text-emerald-600') }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p class="text-base sm:text-xl font-black font-mono mt-1 tabular-nums {{ (float) $report['difference'] < 0 ? 'text-rose-600 dark:text-rose-400' : ((float) $report['difference'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                {{ (float) $report['difference'] > 0 ? '+' : '' }}{{ number_format((float) $report['difference']) }}
            </p>
        </div>
    </div>

    {{-- Per-shift table --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden">
        {{-- Mobile Swipe Hint Bar --}}
        <div class="sm:hidden px-2.5 py-1 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200/60 dark:border-slate-800 text-[10px] text-slate-400 flex items-center justify-between">
            <span class="flex items-center gap-1">
                <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Swipe horizontally to view all columns</span>
            </span>
            <span class="font-mono text-[9px] uppercase tracking-wider text-slate-400">Scrollable</span>
        </div>

        @if ($report['shifts']->isEmpty())
            <p class="text-center text-xs text-slate-500 py-8">{{ __('messages.reports_no_data') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 border-collapse min-w-[760px]">
                    <thead class="sticky top-0 bg-slate-50/90 dark:bg-slate-800/80 backdrop-blur-xs text-[10px] sm:text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-black border-b border-slate-200/80 dark:border-slate-800">
                        <tr>
                            <th class="px-3 py-2.5">{{ __('messages.register') }}</th>
                            <th class="px-3 py-2.5">{{ __('messages.cashier') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.opening_cash') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.cash_sales') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.cash_refunds') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.cash_in_out') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.expected_cash') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.actual') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.difference') }}</th>
                            <th class="px-3 py-2.5 text-right">{{ __('messages.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach ($report['shifts'] as $shift)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                <td class="px-3 py-2 font-semibold text-slate-900 dark:text-slate-100">{{ $shift->register_name }}</td>
                                <td class="px-3 py-2 text-slate-700 dark:text-slate-300">{{ $shift->cashier?->name ?? '—' }}</td>
                                <td class="px-3 py-2 text-right font-mono tabular-nums text-slate-800 dark:text-slate-200">Ks {{ number_format((float) $shift->opening_cash) }}</td>
                                <td class="px-3 py-2 text-right font-mono tabular-nums text-slate-800 dark:text-slate-200">Ks {{ number_format((float) $shift->cash_sales) }}</td>
                                <td class="px-3 py-2 text-right font-mono tabular-nums text-slate-800 dark:text-slate-200">Ks {{ number_format((float) $shift->cash_refunds) }}</td>
                                <td class="px-3 py-2 text-right font-mono tabular-nums text-slate-700 dark:text-slate-300">+{{ number_format((float) $shift->cash_in) }} / −{{ number_format((float) $shift->cash_out) }}</td>
                                <td class="px-3 py-2 text-right font-mono tabular-nums text-slate-800 dark:text-slate-200">{{ $shift->expected_closing_amount !== null ? 'Ks ' . number_format((float) $shift->expected_closing_amount) : '—' }}</td>
                                <td class="px-3 py-2 text-right font-mono tabular-nums text-slate-800 dark:text-slate-200">{{ $shift->actual_closing_amount !== null ? 'Ks ' . number_format((float) $shift->actual_closing_amount) : '—' }}</td>
                                <td class="px-3 py-2 text-right font-mono font-bold tabular-nums {{ (float) $shift->difference < 0 ? 'text-rose-600 dark:text-rose-400' : ((float) $shift->difference > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400 dark:text-slate-500') }}">
                                    {{ $shift->difference !== null ? ((float) $shift->difference > 0 ? '+' : '') . number_format((float) $shift->difference) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                                        {{ $shift->isOpen() ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }}">
                                        {{ ucfirst($shift->status) }}
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
