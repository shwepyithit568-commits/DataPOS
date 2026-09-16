@extends('layouts.admin.app')

@section('title', __('messages.aging_report_title') . ' · ' . $store->name)
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6">
    {{-- Top Ultra-Dense Header Banner (34px - 38px) --}}
    <div class="relative overflow-hidden rounded-lg bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 dark:from-slate-950 dark:via-indigo-950/80 dark:to-slate-950 p-2 sm:p-2.5 shadow-sm border border-indigo-900/40">
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-md bg-indigo-600/30 flex items-center justify-center border border-indigo-500/30 text-indigo-300 flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xs sm:text-sm font-bold text-white tracking-tight leading-none flex items-center gap-1.5">
                        {{ __('messages.aging_report_title') }}
                        <span class="text-[10px] font-normal px-1.5 py-0.2 rounded bg-indigo-500/20 text-indigo-200 border border-indigo-500/30 leading-tight">
                            {{ $summary['supplier_count'] }} {{ __('messages.aging_suppliers') }}
                        </span>
                    </h1>
                    <p class="text-[10px] text-indigo-200/80 leading-tight mt-0.5">{{ $store->name }} — {{ __('messages.aging_report_sub') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                <a href="{{ url('/store/' . $store->slug . '/admin/suppliers') }}"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-slate-800/80 hover:bg-slate-700 text-slate-200 text-xs font-medium border border-slate-700 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('messages.back') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Centered Row-based Stat Cards (Standard v4.1) --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-0.5 sm:gap-1">
        <div class="flex items-center justify-center gap-2.5 sm:gap-3 bg-white dark:bg-slate-800 rounded-lg p-2 sm:p-2.5 border border-slate-200 dark:border-slate-700 shadow-sm">
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-md bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] sm:text-xs font-medium text-slate-500 dark:text-slate-400 leading-tight">{{ __('messages.aging_total_outstanding') }}</p>
                <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white leading-tight mt-0.5">{{ format_currency($summary['total_outstanding'], $store) }}</p>
                <p class="text-[9px] text-slate-400 dark:text-slate-500 leading-none mt-0.5">{{ $summary['supplier_count'] }} {{ __('messages.aging_suppliers') }}</p>
            </div>
        </div>

        <div class="flex items-center justify-center gap-2.5 sm:gap-3 bg-white dark:bg-slate-800 rounded-lg p-2 sm:p-2.5 border border-slate-200 dark:border-slate-700 shadow-sm">
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-md bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] sm:text-xs font-medium text-emerald-600 dark:text-emerald-400 leading-tight">{{ __('messages.aging_current') }}</p>
                <p class="text-xs sm:text-sm font-bold text-emerald-700 dark:text-emerald-300 leading-tight mt-0.5">{{ format_currency($summary['total_current'], $store) }}</p>
                <p class="text-[9px] text-slate-400 dark:text-slate-500 leading-none mt-0.5">0–30 {{ __('messages.aging_days') }}</p>
            </div>
        </div>

        <div class="flex items-center justify-center gap-2.5 sm:gap-3 bg-white dark:bg-slate-800 rounded-lg p-2 sm:p-2.5 border border-slate-200 dark:border-slate-700 shadow-sm">
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-md bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] sm:text-xs font-medium text-amber-600 dark:text-amber-400 leading-tight">{{ __('messages.aging_31_60') }}</p>
                <p class="text-xs sm:text-sm font-bold text-amber-700 dark:text-amber-300 leading-tight mt-0.5">{{ format_currency($summary['total_31_60'], $store) }}</p>
                <p class="text-[9px] text-slate-400 dark:text-slate-500 leading-none mt-0.5">31–60 {{ __('messages.aging_days') }}</p>
            </div>
        </div>

        <div class="flex items-center justify-center gap-2.5 sm:gap-3 bg-white dark:bg-slate-800 rounded-lg p-2 sm:p-2.5 border border-slate-200 dark:border-slate-700 shadow-sm">
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-md bg-orange-50 dark:bg-orange-950/50 text-orange-600 dark:text-orange-400 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] sm:text-xs font-medium text-orange-600 dark:text-orange-400 leading-tight">{{ __('messages.aging_61_90') }}</p>
                <p class="text-xs sm:text-sm font-bold text-orange-700 dark:text-orange-300 leading-tight mt-0.5">{{ format_currency($summary['total_61_90'], $store) }}</p>
                <p class="text-[9px] text-slate-400 dark:text-slate-500 leading-none mt-0.5">61–90 {{ __('messages.aging_days') }}</p>
            </div>
        </div>

        <div class="col-span-2 sm:col-span-1 flex items-center justify-center gap-2.5 sm:gap-3 bg-white dark:bg-slate-800 rounded-lg p-2 sm:p-2.5 border border-slate-200 dark:border-slate-700 shadow-sm">
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-md bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] sm:text-xs font-medium text-rose-600 dark:text-rose-400 leading-tight">{{ __('messages.aging_over_90') }}</p>
                <p class="text-xs sm:text-sm font-bold text-rose-700 dark:text-rose-300 leading-tight mt-0.5">{{ format_currency($summary['total_over_90'], $store) }}</p>
                <p class="text-[9px] text-slate-400 dark:text-slate-500 leading-none mt-0.5">90+ {{ __('messages.aging_days') }}</p>
            </div>
        </div>
    </div>

    {{-- Aging Table --}}
    @if (count($agingData) > 0)
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden transition-colors duration-200">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] text-left text-xs text-gray-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 font-semibold text-slate-700 dark:text-slate-200">
                        <tr>
                            <th class="px-2.5 py-1.5">{{ __('messages.supplier_col_name') }}</th>
                            <th class="px-2.5 py-1.5 text-center">{{ __('messages.aging_pos') }}</th>
                            <th class="px-2.5 py-1.5 text-right">{{ __('messages.aging_current') }}</th>
                            <th class="px-2.5 py-1.5 text-right">{{ __('messages.aging_31_60') }}</th>
                            <th class="px-2.5 py-1.5 text-right">{{ __('messages.aging_61_90') }}</th>
                            <th class="px-2.5 py-1.5 text-right">{{ __('messages.aging_over_90') }}</th>
                            <th class="px-2.5 py-1.5 text-right font-bold">{{ __('messages.aging_total_outstanding') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @foreach ($agingData as $row)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/40 transition">
                                <td class="px-2.5 py-1.5">
                                    <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $row['supplier']->name }}</div>
                                    @if ($row['supplier']->phone)
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ $row['supplier']->phone }}</div>
                                    @endif
                                </td>
                                <td class="px-2.5 py-1.5 text-center">
                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-semibold">{{ $row['po_count'] }}</span>
                                </td>
                                <td class="px-2.5 py-1.5 text-right">
                                    @if ($row['buckets']['current'] > 0)
                                        <span class="text-emerald-700 dark:text-emerald-400 font-semibold">{{ format_currency($row['buckets']['current'], $store) }}</span>
                                    @else
                                        <span class="text-slate-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-2.5 py-1.5 text-right">
                                    @if ($row['buckets']['31_60'] > 0)
                                        <span class="text-amber-700 dark:text-amber-400 font-semibold">{{ format_currency($row['buckets']['31_60'], $store) }}</span>
                                    @else
                                        <span class="text-slate-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-2.5 py-1.5 text-right">
                                    @if ($row['buckets']['61_90'] > 0)
                                        <span class="text-orange-700 dark:text-orange-400 font-semibold">{{ format_currency($row['buckets']['61_90'], $store) }}</span>
                                    @else
                                        <span class="text-slate-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-2.5 py-1.5 text-right">
                                    @if ($row['buckets']['over_90'] > 0)
                                        <span class="text-rose-700 dark:text-rose-400 font-bold">{{ format_currency($row['buckets']['over_90'], $store) }}</span>
                                    @else
                                        <span class="text-slate-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-2.5 py-1.5 text-right">
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-xs font-bold">
                                        {{ format_currency($row['total'], $store) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 dark:bg-slate-900/60 border-t-2 border-slate-200 dark:border-slate-700 font-bold text-slate-900 dark:text-white text-xs">
                        <tr>
                            <td class="px-2.5 py-1.5">{{ __('messages.aging_totals') }}</td>
                            <td class="px-2.5 py-1.5 text-center">{{ array_sum(array_column($agingData, 'po_count')) }}</td>
                            <td class="px-2.5 py-1.5 text-right text-emerald-700 dark:text-emerald-400">{{ format_currency($summary['total_current'], $store) }}</td>
                            <td class="px-2.5 py-1.5 text-right text-amber-700 dark:text-amber-400">{{ format_currency($summary['total_31_60'], $store) }}</td>
                            <td class="px-2.5 py-1.5 text-right text-orange-700 dark:text-orange-400">{{ format_currency($summary['total_61_90'], $store) }}</td>
                            <td class="px-2.5 py-1.5 text-right text-rose-700 dark:text-rose-400">{{ format_currency($summary['total_over_90'], $store) }}</td>
                            <td class="px-2.5 py-1.5 text-right">
                                <span class="inline-block px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-xs font-bold">
                                    {{ format_currency($summary['total_outstanding'], $store) }}
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-slate-800 rounded-xl p-12 text-center">
            <div class="text-4xl mb-3 opacity-40">📊</div>
            <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">{{ __('messages.aging_no_data') }}</div>
            <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.aging_no_data_hint') }}</div>
        </div>
    @endif
</div>
@endsection
