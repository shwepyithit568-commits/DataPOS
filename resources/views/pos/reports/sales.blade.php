@extends('layouts.pos.app')

@section('title', __('messages.reports_sales') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="mx-auto max-w-6xl px-4 py-6 space-y-6">

    {{-- Tabs Header --}}
    @include('pos.reports._tabs', ['active' => 'sales'])

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
                    <a href="{{ route('pos.reports.sales', ['store_slug' => $store->slug, 'preset' => $pKey, 'cashier_id' => request('cashier_id')]) }}"
                       class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap transition {{ ($preset ?? '') === $pKey ? 'bg-sky-600 text-white shadow-sm shadow-sky-500/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                        {{ $pLabel }}
                    </a>
                @endforeach
            </div>

            {{-- Custom Form + Actions --}}
            <form method="GET" action="{{ route('pos.reports.sales', ['store_slug' => $store->slug]) }}"
                  class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="preset" value="custom">

                {{-- Cashier Filter --}}
                <select name="cashier_id" onchange="this.form.submit()" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500">
                    <option value="">{{ __('messages.reports_all_cashiers') }}</option>
                    @foreach ($cashiers as $c)
                        <option value="{{ $c->id }}" @selected(request('cashier_id') == $c->id)>{{ $c->name }}</option>
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

                <a href="{{ route('pos.reports.sales.export', ['store_slug' => $store->slug, 'preset' => $preset ?? 'custom', 'cashier_id' => request('cashier_id'), 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                   class="rounded-xl px-3 py-1 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    <span>CSV</span>
                </a>
            </form>
        </div>
    </div>

    {{-- 4 Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        {{-- Total Sales Revenue --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                <span>{{ __('messages.reports_grand_total') }}</span>
                <span class="p-1.5 rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-sky-600 dark:text-sky-400 font-outfit tabular-nums">
                Ks {{ number_format((float) $report['total']) }}
            </p>
            <p class="text-[11px] text-slate-400">{{ __('messages.pl_net_revenue') }}</p>
        </div>

        {{-- Receipts Count --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                <span>{{ __('messages.reports_sale_count') }}</span>
                <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                {{ number_format($report['count']) }}
            </p>
            <p class="text-[11px] text-slate-400">{{ __('messages.receipts') }}</p>
        </div>

        {{-- Total Items Dispatched --}}
        @php
            $totalItemsSold = $report['sales']->sum(fn($s) => $s->items->sum('quantity'));
            $aov = $report['count'] > 0 ? round(((float) $report['total']) / $report['count'], 2) : 0;
        @endphp
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                <span>{{ __('messages.items_sold') }}</span>
                <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                {{ number_format($totalItemsSold) }}
            </p>
            <p class="text-[11px] text-slate-400">{{ __('messages.units_dispatched') }}</p>
        </div>

        {{-- Average Order Value (AOV) --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                <span>{{ __('messages.aov_metric') }}</span>
                <span class="p-1.5 rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                Ks {{ number_format($aov) }}
            </p>
            <p class="text-[11px] text-slate-400">{{ __('messages.avg_per_ticket') }}</p>
        </div>
    </div>

    {{-- Payment Methods Summary Cards --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            {{ __('messages.reports_method_totals') }}
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5">
            @forelse ($report['methods'] as $method => $amount)
                <div class="p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 space-y-1">
                    <div class="text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase">
                        {{ $method }}
                    </div>
                    <div class="text-sm font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                        Ks {{ number_format((float) $amount) }}
                    </div>
                </div>
            @empty
                <div class="col-span-full text-xs text-slate-400 py-2">
                    {{ __('messages.no_data_available') }}
                </div>
            @endforelse
        </div>
    </div>

    {{-- Sales Transactions Table --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">
                {{ __('messages.sales_trend_timeline') }}
            </h2>
            <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                {{ $report['sales']->count() }} {{ __('messages.transactions') }}
            </span>
        </div>

        @if ($report['sales']->isEmpty())
            <div class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm space-y-2">
                <div class="text-3xl">🧾</div>
                <p>{{ __('messages.reports_no_data') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-3">{{ __('messages.receipt') }}</th>
                            <th class="py-3 px-3">{{ __('messages.reports_date') }}</th>
                            <th class="py-3 px-3">{{ __('messages.cashier') }}</th>
                            <th class="py-3 px-3">{{ __('messages.customer') }}</th>
                            <th class="py-3 px-3 text-center">{{ __('messages.reports_items') }}</th>
                            <th class="py-3 px-3">{{ __('messages.payment_methods') }}</th>
                            <th class="py-3 px-3 text-right">{{ __('messages.total') }}</th>
                            <th class="py-3 px-3 text-center">{{ __('messages.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($report['sales'] as $sale)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-3">
                                    <a href="{{ url('/store/' . $store->slug . '/pos/sales/' . $sale->id . '/receipt') }}"
                                       target="_blank"
                                       class="font-mono font-bold text-sky-600 dark:text-sky-400 hover:underline inline-flex items-center gap-1">
                                        <span>{{ $sale->receipt_number }}</span>
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    </a>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap text-slate-600 dark:text-slate-300">
                                    {{ $sale->posted_at?->format('d M Y, H:i') }}
                                </td>
                                <td class="py-3 px-3 font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $sale->cashier?->name ?? '—' }}
                                </td>
                                <td class="py-3 px-3 text-slate-700 dark:text-slate-300">
                                    {{ $sale->customer?->name ?? 'Walk-in Customer' }}
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                    {{ $sale->items->sum('quantity') }}
                                </td>
                                <td class="py-3 px-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($sale->payments as $pm)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 uppercase">
                                                {{ $pm->method }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-right font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                                    Ks {{ number_format((float) $sale->total) }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    @if ($sale->status === 'posted')
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                            {{ __('messages.completed') }}
                                        </span>
                                    @elseif ($sale->status === 'refunded' || $sale->status === 'partially_refunded')
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                            {{ __('messages.refunded') }}
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            {{ $sale->status }}
                                        </span>
                                    @endif
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
