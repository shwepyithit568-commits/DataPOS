@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-4 sm:space-y-5">

    {{-- ============================================================
         HERO HEADER — eyebrow, title, subtitle, back CTA
         ============================================================ --}}
    <header class="admin-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-wider text-sky-600 dark:text-sky-400">
                {{ __('messages.sidebar_returns') }}
            </p>
            <h1 class="admin-page-title mt-0.5">{{ __('messages.new_return') }}</h1>
            <p class="admin-page-sub mt-1">{{ __('messages.select_sale_sub') }}</p>
        </div>
        <a href="{{ route('pos.returns.index', $storeRouteParams) }}"
           class="admin-secondary-btn shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            <span class="hidden sm:inline">{{ __('messages.back') }}</span>
        </a>
    </header>

    {{-- ============================================================
         SEARCH — find the posted sale by receipt or customer
         ============================================================ --}}
    <form method="GET" action="{{ route('pos.returns.create', $storeRouteParams) }}" class="flex items-center gap-2">
        <div class="relative flex-1 min-w-0">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="{{ __('messages.search_receipt_customer') }}"
                class="w-full min-h-11 pl-9 pr-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500"
            />
        </div>
        <button type="submit" class="admin-primary-btn shrink-0">{{ __('messages.search') }}</button>
    </form>

    {{-- ============================================================
         POSTED SALES LIST — pick one to open the refund form
         ============================================================ --}}
    @if ($sales->isEmpty())
        <div class="bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 rounded-2xl p-8 sm:p-12 text-center">
            <div class="mx-auto mb-4 w-16 h-16 rounded-2xl bg-sky-50 dark:bg-sky-950/60 grid place-items-center text-sky-500 dark:text-sky-400">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
            </div>
            <p class="font-black text-slate-800 dark:text-slate-200">{{ __('messages.no_sales_found') }}</p>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ __('messages.select_sale_empty_hint') }}</p>
        </div>
    @else
        {{-- Desktop table --}}
        <div class="hidden md:block bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-black text-slate-700 dark:text-slate-200">
                        <tr>
                            <th class="px-4 py-3">{{ __('messages.receipt_number') }}</th>
                            <th class="px-4 py-3">{{ __('messages.customer') }}</th>
                            <th class="px-4 py-3">{{ __('messages.date') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('messages.total') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-slate-700">
                        @foreach ($sales as $sale)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/50 transition">
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs font-black text-slate-900 dark:text-slate-100">{{ $sale->receipt_number }}</span>
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 text-[10px] font-black rounded-full {{ $sale->status === 'posted' ? 'bg-slate-100 dark:bg-slate-700/60 text-slate-500 dark:text-slate-300' : 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300' }}">
                                        {{ $sale->status === 'posted' ? __('messages.posted') : __('messages.partially_refunded') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $sale->customer?->name ?? __('messages.walk_in_customer') }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">{{ $sale->posted_at?->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3 text-right font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums">Ks {{ number_format((float) $sale->total) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('pos.refund.create', [...$storeRouteParams, 'sale' => $sale->id]) }}"
                                       class="min-h-11 inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-black text-white bg-sky-600 hover:bg-sky-700 shadow-sm transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>
                                        {{ __('messages.refund_sale') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile cards --}}
        <div class="grid grid-cols-1 gap-2 md:hidden">
            @foreach ($sales as $sale)
                <div class="bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 rounded-xl p-3.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="min-w-0 inline-flex items-center gap-2">
                            <span class="font-mono text-xs font-black text-slate-900 dark:text-slate-100 truncate">{{ $sale->receipt_number }}</span>
                            <span class="shrink-0 inline-flex items-center px-2 py-0.5 text-[10px] font-black rounded-full {{ $sale->status === 'posted' ? 'bg-slate-100 dark:bg-slate-700/60 text-slate-500 dark:text-slate-300' : 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300' }}">
                                {{ $sale->status === 'posted' ? __('messages.posted') : __('messages.partially_refunded') }}
                            </span>
                        </span>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">{{ $sale->posted_at?->format('d M Y') }}</span>
                    </div>
                    <div class="mt-2 flex items-end justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 uppercase tracking-wide font-semibold">{{ __('messages.customer') }}</p>
                            <p class="text-sm text-slate-700 dark:text-slate-200 truncate">{{ $sale->customer?->name ?? __('messages.walk_in_customer') }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 uppercase tracking-wide font-semibold">{{ __('messages.total') }}</p>
                            <p class="font-mono text-sm font-black text-slate-900 dark:text-slate-100 tabular-nums">Ks {{ number_format((float) $sale->total) }}</p>
                        </div>
                    </div>
                    <a href="{{ route('pos.refund.create', [...$storeRouteParams, 'sale' => $sale->id]) }}"
                       class="mt-3 min-h-11 w-full inline-flex items-center justify-center gap-1.5 rounded-xl text-sm font-black text-white bg-sky-600 hover:bg-sky-700 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>
                        {{ __('messages.refund_sale') }}
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
