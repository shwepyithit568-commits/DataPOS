@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-6">
    {{-- Header --}}
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">{{ __('messages.aging_report_title') }}</h1>
            <p class="admin-page-sub">{{ $store->name }} — {{ __('messages.aging_report_sub') }}</p>
        </div>
        <a href="{{ url('/store/' . $store->slug . '/admin/suppliers') }}"
            class="min-h-11 inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ __('messages.back') }}
        </a>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-200 dark:border-slate-700">
            <div class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide">{{ __('messages.aging_total_outstanding') }}</div>
            <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">{{ number_format($summary['total_outstanding'], 0) }}</div>
            <div class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $summary['supplier_count'] }} {{ __('messages.aging_suppliers') }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-200 dark:border-slate-700">
            <div class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">{{ __('messages.aging_current') }}</div>
            <div class="text-xl font-extrabold text-emerald-700 dark:text-emerald-300 mt-1">{{ number_format($summary['total_current'], 0) }}</div>
            <div class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">0–30 {{ __('messages.aging_days') }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-200 dark:border-slate-700">
            <div class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wide">{{ __('messages.aging_31_60') }}</div>
            <div class="text-xl font-extrabold text-amber-700 dark:text-amber-300 mt-1">{{ number_format($summary['total_31_60'], 0) }}</div>
            <div class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">31–60 {{ __('messages.aging_days') }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-200 dark:border-slate-700">
            <div class="text-xs font-semibold text-orange-600 dark:text-orange-400 uppercase tracking-wide">{{ __('messages.aging_61_90') }}</div>
            <div class="text-xl font-extrabold text-orange-700 dark:text-orange-300 mt-1">{{ number_format($summary['total_61_90'], 0) }}</div>
            <div class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">61–90 {{ __('messages.aging_days') }}</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-200 dark:border-slate-700">
            <div class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wide">{{ __('messages.aging_over_90') }}</div>
            <div class="text-xl font-extrabold text-red-700 dark:text-red-300 mt-1">{{ number_format($summary['total_over_90'], 0) }}</div>
            <div class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">90+ {{ __('messages.aging_days') }}</div>
        </div>
    </div>

    {{-- Aging Table --}}
    @if (count($agingData) > 0)
        <div class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden transition-colors duration-200">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] text-left text-sm text-gray-600 dark:text-slate-300">
                    <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-semibold text-gray-700 dark:text-slate-200">
                        <tr>
                            <th class="p-3">{{ __('messages.supplier_col_name') }}</th>
                            <th class="p-3 text-center">{{ __('messages.aging_pos') }}</th>
                            <th class="p-3 text-right">{{ __('messages.aging_current') }}</th>
                            <th class="p-3 text-right">{{ __('messages.aging_31_60') }}</th>
                            <th class="p-3 text-right">{{ __('messages.aging_61_90') }}</th>
                            <th class="p-3 text-right">{{ __('messages.aging_over_90') }}</th>
                            <th class="p-3 text-right font-bold">{{ __('messages.aging_total_outstanding') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @foreach ($agingData as $row)
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-slate-700/40 transition">
                                <td class="p-3">
                                    <div class="font-bold text-gray-900 dark:text-slate-100">{{ $row['supplier']->name }}</div>
                                    @if ($row['supplier']->phone)
                                        <div class="text-xs text-gray-400 dark:text-slate-500 mt-0.5 font-mono">{{ $row['supplier']->phone }}</div>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 text-xs font-semibold">{{ $row['po_count'] }}</span>
                                </td>
                                <td class="p-3 text-right">
                                    @if ($row['buckets']['current'] > 0)
                                        <span class="text-emerald-700 dark:text-emerald-400 font-semibold">{{ number_format($row['buckets']['current'], 0) }}</span>
                                    @else
                                        <span class="text-gray-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right">
                                    @if ($row['buckets']['31_60'] > 0)
                                        <span class="text-amber-700 dark:text-amber-400 font-semibold">{{ number_format($row['buckets']['31_60'], 0) }}</span>
                                    @else
                                        <span class="text-gray-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right">
                                    @if ($row['buckets']['61_90'] > 0)
                                        <span class="text-orange-700 dark:text-orange-400 font-semibold">{{ number_format($row['buckets']['61_90'], 0) }}</span>
                                    @else
                                        <span class="text-gray-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right">
                                    @if ($row['buckets']['over_90'] > 0)
                                        <span class="text-red-700 dark:text-red-400 font-bold">{{ number_format($row['buckets']['over_90'], 0) }}</span>
                                    @else
                                        <span class="text-gray-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right">
                                    <span class="inline-block px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-xs font-bold">
                                        {{ number_format($row['total'], 0) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-slate-900/50 border-t-2 dark:border-slate-600 font-bold text-gray-900 dark:text-white">
                        <tr>
                            <td class="p-3">{{ __('messages.aging_totals') }}</td>
                            <td class="p-3 text-center">{{ array_sum(array_column($agingData, 'po_count')) }}</td>
                            <td class="p-3 text-right text-emerald-700 dark:text-emerald-400">{{ number_format($summary['total_current'], 0) }}</td>
                            <td class="p-3 text-right text-amber-700 dark:text-amber-400">{{ number_format($summary['total_31_60'], 0) }}</td>
                            <td class="p-3 text-right text-orange-700 dark:text-orange-400">{{ number_format($summary['total_61_90'], 0) }}</td>
                            <td class="p-3 text-right text-red-700 dark:text-red-400">{{ number_format($summary['total_over_90'], 0) }}</td>
                            <td class="p-3 text-right">
                                <span class="inline-block px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-xs font-bold">
                                    {{ number_format($summary['total_outstanding'], 0) }}
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
