@extends('layouts.admin.app')

@section('title', __('messages.reports_commercial_tax') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6">

    @include('pos.reports._tabs', ['active' => 'tax'])

    {{-- 2. Interactive Filter & Export Toolbar --}}
    <div class="bg-white dark:bg-slate-900 rounded border border-slate-200/80 dark:border-slate-800 p-1.5 sm:p-2 shadow-2xs space-y-1">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-1.5">
            
            {{-- Quick Date Presets & TIN --}}
            <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5 lg:pb-0 text-xs">
                @php $tin = $store->setting?->getPosSetting('tax_id_number'); @endphp
                @if($tin)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-amber-50 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shrink-0 shadow-2xs">
                        TIN: {{ $tin }}
                    </span>
                @endif
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
                    @php $isCurrentPreset = (($preset ?? '') === $pKey); @endphp
                    <a href="{{ route('pos.reports.tax', ['store_slug' => $store->slug, 'preset' => $pKey, 'cashier_id' => request('cashier_id')]) }}"
                       class="px-2 py-0.5 rounded font-bold whitespace-nowrap text-[11px] sm:text-xs transition {{ $isCurrentPreset ? 'bg-sky-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                        {{ $pLabel }}
                    </a>
                @endforeach
            </div>

            {{-- Custom Form + Exports --}}
            <form method="GET" action="{{ route('pos.reports.tax', ['store_slug' => $store->slug]) }}"
                  class="flex flex-wrap items-center gap-1">
                <input type="hidden" name="preset" value="custom">

                {{-- Cashier Filter --}}
                <select name="cashier_id" onchange="this.form.submit()" class="h-7 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500 shadow-2xs">
                    <option value="">{{ __('messages.reports_all_cashiers') }}</option>
                    @foreach ($cashiers as $c)
                        <option value="{{ $c->id }}" @selected(request('cashier_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>

                {{-- Custom Date Inputs --}}
                <div class="flex items-center gap-1">
                    <input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ today()->toDateString() }}"
                           class="h-7 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-1.5 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500 shadow-2xs">
                    <span class="text-xs text-slate-400">—</span>
                    <input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ today()->toDateString() }}"
                           class="h-7 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-1.5 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500 shadow-2xs">
                </div>

                {{-- Filter Submit Button --}}
                <button type="submit" class="h-7 rounded px-2.5 text-xs font-bold bg-sky-600 hover:bg-sky-500 text-white transition shadow-2xs">
                    {{ __('messages.reports_filter') }}
                </button>

                {{-- Export Actions (Excel & CSV) & Print --}}
                <div class="flex items-center gap-1 shrink-0 ml-auto">
                    <a href="{{ route('pos.reports.tax.export', ['store_slug' => $store->slug, 'preset' => $preset ?? 'custom', 'cashier_id' => request('cashier_id'), 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'format' => 'xlsx']) }}"
                       class="h-7 rounded px-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition inline-flex items-center gap-1"
                       title="Export Excel (.xlsx)">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" /></svg>
                        <span>Excel</span>
                    </a>

                    <a href="{{ route('pos.reports.tax.export', ['store_slug' => $store->slug, 'preset' => $preset ?? 'custom', 'cashier_id' => request('cashier_id'), 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'format' => 'csv']) }}"
                       class="h-7 rounded px-2 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-1"
                       title="Export CSV (.csv)">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        <span>CSV</span>
                    </a>

                    <button type="button" @click="window.print()"
                            class="h-7 rounded px-2 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-1"
                            title="Print">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span class="hidden sm:inline">{{ __('messages.print') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 3. Centered Row-based Stat Cards (Ultra-Dense 2px Rhythm) --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-0.5 sm:gap-1">
        
        {{-- Total Gross Sales --}}
        <div class="bg-white dark:bg-slate-900 rounded border border-slate-200/80 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="w-8 h-8 rounded-full bg-sky-50 dark:bg-sky-950/80 text-sky-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div class="text-center">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('messages.total_sales') }}</div>
                <div class="text-xs sm:text-sm font-black text-sky-900 dark:text-sky-100 font-mono tabular-nums">{{ format_currency($report['total_sales'], $store) }}</div>
            </div>
        </div>

        {{-- Taxable Sales --}}
        <div class="bg-white dark:bg-slate-900 rounded border border-slate-200/80 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-950/80 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </div>
            <div class="text-center">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('messages.taxable_sales') }}</div>
                <div class="text-xs sm:text-sm font-black text-blue-900 dark:text-blue-100 font-mono tabular-nums">{{ format_currency($report['taxable_sales'], $store) }}</div>
            </div>
        </div>

        {{-- Tax-Exempt Sales --}}
        <div class="bg-white dark:bg-slate-900 rounded border border-slate-200/80 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
            </div>
            <div class="text-center">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('messages.exempt_sales') }}</div>
                <div class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-200 font-mono tabular-nums">{{ format_currency($report['exempt_sales'], $store) }}</div>
            </div>
        </div>

        {{-- Commercial Tax Collected (5%) --}}
        <div class="bg-white dark:bg-slate-900 rounded border border-slate-200/80 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="w-8 h-8 rounded-full bg-amber-50 dark:bg-amber-950/80 text-amber-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
            </div>
            <div class="text-center">
                <div class="text-[10px] font-black uppercase tracking-wider text-amber-700 dark:text-amber-400">{{ __('messages.commercial_tax') }}</div>
                <div class="text-xs sm:text-sm font-black text-amber-900 dark:text-amber-200 font-mono tabular-nums">{{ format_currency($report['total_tax'], $store) }}</div>
            </div>
        </div>

        {{-- Net Sales (Revenue excl Tax) --}}
        <div class="bg-white dark:bg-slate-900 rounded border border-slate-200/80 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 col-span-2 sm:col-span-1">
            <div class="w-8 h-8 rounded-full bg-emerald-50 dark:bg-emerald-950/80 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div class="text-center">
                <div class="text-[10px] font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-400">{{ __('messages.net_sales') }}</div>
                <div class="text-xs sm:text-sm font-black text-emerald-900 dark:text-emerald-100 font-mono tabular-nums">{{ format_currency($report['net_sales'], $store) }}</div>
            </div>
        </div>
    </div>

    {{-- 4. Itemized Sales Table --}}
    <div class="bg-white dark:bg-slate-900 rounded border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="p-2 sm:p-2.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 font-mono">
                    {{ __('messages.reports_sales') }} ({{ $report['count'] }})
                </h2>
                <span class="text-[10px] text-slate-400 font-medium">
                    {{ $from->toFormattedDateString() }} - {{ $to->toFormattedDateString() }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800 text-[10px] font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider">
                        <th class="py-2 px-2.5">{{ __('messages.receipt') }}</th>
                        <th class="py-2 px-2.5">{{ __('messages.reports_date') }}</th>
                        <th class="py-2 px-2.5">{{ __('messages.cashier') }}</th>
                        <th class="py-2 px-2.5">{{ __('messages.customer') }}</th>
                        <th class="py-2 px-2.5 text-center">{{ __('messages.tax_type') }}</th>
                        <th class="py-2 px-2.5 text-right">{{ __('messages.taxable_amount') }}</th>
                        <th class="py-2 px-2.5 text-right">{{ __('messages.exempt_amount') }}</th>
                        <th class="py-2 px-2.5 text-right font-bold text-amber-700 dark:text-amber-400">{{ __('messages.commercial_tax') }}</th>
                        <th class="py-2 px-2.5 text-right font-bold text-slate-900 dark:text-slate-100">{{ __('messages.total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-sans">
                    @forelse ($report['sales'] as $sale)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                            {{-- Receipt # --}}
                            <td class="py-1.5 px-2.5 font-mono font-bold text-sky-600 dark:text-sky-400 whitespace-nowrap">
                                <a href="{{ route('pos.receipt', ['store_slug' => $store->slug, 'sale' => $sale->id]) }}" target="_blank" class="hover:underline">
                                    {{ $sale->receipt_number ?: $sale->invoice_no }}
                                </a>
                            </td>

                            {{-- Date --}}
                            <td class="py-1.5 px-2.5 text-slate-500 dark:text-slate-400 whitespace-nowrap text-[11px]">
                                {{ $sale->posted_at?->format('Y-m-d H:i') }}
                            </td>

                            {{-- Cashier --}}
                            <td class="py-1.5 px-2.5 text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                {{ $sale->cashier?->name ?? $sale->creator?->name ?? '-' }}
                            </td>

                            {{-- Customer --}}
                            <td class="py-1.5 px-2.5 text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                {{ $sale->customer?->name ?? __('messages.reports_walk_in_customer') }}
                            </td>

                            {{-- Tax Type Badge --}}
                            <td class="py-1.5 px-2.5 text-center whitespace-nowrap">
                                @if(($sale->tax_type ?? 'inclusive') === 'exclusive')
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/70 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                        {{ __('messages.exclusive') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        {{ __('messages.inclusive') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Taxable Amount --}}
                            <td class="py-1.5 px-2.5 text-right font-mono tabular-nums text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                {{ format_currency($sale->taxable_amount ?? 0, $store) }}
                            </td>

                            {{-- Exempt Amount --}}
                            <td class="py-1.5 px-2.5 text-right font-mono tabular-nums text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                {{ format_currency($sale->exempt_amount ?? 0, $store) }}
                            </td>

                            {{-- Tax Amount --}}
                            <td class="py-1.5 px-2.5 text-right font-mono font-bold tabular-nums text-amber-700 dark:text-amber-400 whitespace-nowrap">
                                {{ format_currency($sale->tax ?? 0, $store) }}
                            </td>

                            {{-- Total --}}
                            <td class="py-1.5 px-2.5 text-right font-mono font-bold tabular-nums text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                {{ format_currency($sale->total, $store) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-6 text-center text-xs text-slate-400 italic">
                                {{ __('messages.reports_no_data') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($report['sales']) > 0)
                    <tfoot>
                        <tr class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-bold text-xs">
                            <td colspan="5" class="py-2 px-2.5 text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[10px]">
                                {{ __('messages.total') }}
                            </td>
                            <td class="py-2 px-2.5 text-right font-mono tabular-nums text-slate-800 dark:text-slate-200">
                                {{ format_currency($report['taxable_sales'], $store) }}
                            </td>
                            <td class="py-2 px-2.5 text-right font-mono tabular-nums text-slate-600 dark:text-slate-400">
                                {{ format_currency($report['exempt_sales'], $store) }}
                            </td>
                            <td class="py-2 px-2.5 text-right font-mono tabular-nums text-amber-700 dark:text-amber-400">
                                {{ format_currency($report['total_tax'], $store) }}
                            </td>
                            <td class="py-2 px-2.5 text-right font-mono tabular-nums text-slate-900 dark:text-slate-100">
                                {{ format_currency($report['total_sales'], $store) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
