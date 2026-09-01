@extends('layouts.admin.app')

@section('title', __('messages.new_return') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6">

    {{-- ============================================================
         1. COMPACT PAGE HEADER — title, subtitle, back CTA
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 grid place-items-center text-base font-bold shadow-xs flex-shrink-0">
                🔍
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.new_return') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.select_sale_sub') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0">
            <a href="{{ route('pos.returns.index', $storeRouteParams) }}"
               class="h-7 px-2.5 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition inline-flex items-center gap-1 border border-slate-200/60 dark:border-slate-700 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                <span>{{ __('messages.back') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. SEARCH TOOLBAR — find the posted sale by receipt or customer
         ============================================================ --}}
    <form method="GET" action="{{ route('pos.returns.create', $storeRouteParams) }}" class="bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center gap-1.5">
        <div class="relative flex-1 min-w-0">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="{{ __('messages.search_receipt_customer') }}"
                class="w-full h-7 pl-8 pr-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-sky-500 focus:bg-white dark:focus:bg-slate-900 transition"
            />
        </div>
        <button type="submit" class="h-7 px-3 rounded-md bg-sky-600 hover:bg-sky-500 text-white text-xs font-black shadow-2xs transition inline-flex items-center gap-1 shrink-0 active:scale-95 cursor-pointer">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <span>{{ __('messages.search') }}</span>
        </button>
    </form>

    {{-- ============================================================
         3. POSTED SALES LIST — pick one to open the refund form
         ============================================================ --}}
    @if ($sales->isEmpty())
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-6 sm:p-8 text-center shadow-2xs">
            <div class="mx-auto mb-3 w-12 h-12 rounded-lg bg-sky-50 dark:bg-sky-950/60 grid place-items-center text-sky-500 dark:text-sky-400 text-xl">
                ⚠️
            </div>
            <p class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-200">{{ __('messages.no_sales_found') }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ __('messages.select_sale_empty_hint') }}</p>
        </div>
    @else
        {{-- Desktop table --}}
        <div class="hidden md:block bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="px-3 py-2 min-w-[150px]">{{ __('messages.receipt_number') }}</th>
                            <th class="px-3 py-2 min-w-[160px]">{{ __('messages.customer') }}</th>
                            <th class="px-3 py-2 min-w-[130px]">{{ __('messages.date') }}</th>
                            <th class="px-3 py-2 min-w-[120px] text-right">{{ __('messages.total') }}</th>
                            <th class="px-3 py-2 min-w-[110px] text-right">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($sales as $sale)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                <td class="px-3 py-2 align-middle">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-xs font-black text-slate-900 dark:text-slate-100">{{ $sale->receipt_number }}</span>
                                        <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-bold rounded-full {{ $sale->status === 'posted' ? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' }}">
                                            {{ $sale->status === 'posted' ? __('messages.posted') : __('messages.partially_refunded') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-middle font-semibold text-slate-800 dark:text-slate-200">{{ $sale->customer?->name ?? __('messages.walk_in_customer') }}</td>
                                <td class="px-3 py-2 align-middle text-[11px] text-slate-500 dark:text-slate-400">{{ $sale->posted_at?->format('d M Y H:i') }}</td>
                                <td class="px-3 py-2 align-middle text-right font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums">Ks {{ number_format((float) $sale->total) }}</td>
                                <td class="px-3 py-2 align-middle text-right">
                                    <a href="{{ route('pos.refund.create', [...$storeRouteParams, 'sale' => $sale->id]) }}"
                                       class="h-6 px-2.5 rounded text-[11px] font-bold text-white bg-sky-600 hover:bg-sky-500 shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>
                                        <span>{{ __('messages.refund_sale') }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile cards --}}
        <div class="grid grid-cols-1 gap-1 md:hidden">
            @foreach ($sales as $sale)
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-2.5 shadow-2xs">
                    <div class="flex items-center justify-between gap-1.5">
                        <span class="font-mono text-xs font-black text-slate-900 dark:text-slate-100 truncate">{{ $sale->receipt_number }}</span>
                        <span class="shrink-0 inline-flex items-center px-1.5 py-0.5 text-[9px] font-bold rounded-full {{ $sale->status === 'posted' ? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' }}">
                            {{ $sale->status === 'posted' ? __('messages.posted') : __('messages.partially_refunded') }}
                        </span>
                    </div>
                    <div class="mt-1.5 flex items-center justify-between gap-1.5">
                        <div class="min-w-0">
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wide font-bold">{{ __('messages.customer') }}</p>
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-300 truncate">{{ $sale->customer?->name ?? __('messages.walk_in_customer') }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wide font-bold">{{ __('messages.total') }}</p>
                            <p class="font-mono text-xs font-black text-slate-900 dark:text-slate-100 tabular-nums">Ks {{ number_format((float) $sale->total) }}</p>
                        </div>
                    </div>
                    <div class="mt-2 pt-1.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1.5">
                        <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ $sale->posted_at?->format('d M Y') }}</span>
                        <a href="{{ route('pos.refund.create', [...$storeRouteParams, 'sale' => $sale->id]) }}"
                           class="h-6 px-2.5 rounded text-[11px] font-bold text-white bg-sky-600 hover:bg-sky-500 shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>
                            <span>{{ __('messages.refund_sale') }}</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
