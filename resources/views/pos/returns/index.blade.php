@extends('layouts.admin.app')

@section('content')
@php
    $totalCount = $returns->total();
    $statusLabel = __('messages.posted');
@endphp
<div class="w-full space-y-4 sm:space-y-5">

    {{-- ============================================================
         HERO HEADER — eyebrow, title, subtitle, primary CTA
         ============================================================ --}}
    <header class="admin-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-wider text-sky-600 dark:text-sky-400">
                {{ __('messages.sidebar_returns') }}
            </p>
            <h1 class="admin-page-title mt-0.5">{{ __('messages.returns_title') }}</h1>
            <p class="admin-page-sub mt-1">{{ $store->name }} · {{ __('messages.returns_sub') }}</p>
        </div>
        <a href="{{ route('pos.returns.create', $storeRouteParams) }}"
           class="admin-primary-btn shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5">
                <path d="M12 4v16m8-8H4"/>
            </svg>
            <span>{{ __('messages.new_return') }}</span>
        </a>
    </header>

    {{-- ============================================================
         SUMMARY STAT CARDS (3-up — compact POS grid)
         ============================================================ --}}
    <div class="grid grid-cols-3 gap-2 sm:gap-3" role="list" aria-label="{{ __('messages.returns_summary') }}">
        <div role="listitem" class="bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4">
            <p class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums">
                {{ number_format($summary['total']) }}
            </p>
            <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-semibold uppercase tracking-wide">
                {{ __('messages.returns_title') }}
            </p>
        </div>
        <div role="listitem" class="bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4">
            <p class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums truncate">
                Ks {{ number_format((float) $summary['refunded']) }}
            </p>
            <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-semibold uppercase tracking-wide">
                {{ __('messages.refund_total') }}
            </p>
        </div>
        <div role="listitem" class="bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4">
            <p class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums">
                {{ number_format($summary['today']) }}
            </p>
            <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-semibold uppercase tracking-wide">
                {{ __('messages.returns_today') }}
            </p>
        </div>
    </div>

    {{-- ============================================================
         TOOLBAR (search / sort / per-page / result count)
         ============================================================ --}}
    <x-admin.toolbar
        :search="$search"
        :search-placeholder="__('messages.search_receipt_customer')"
        :sort="request('sort', 'posted_at')"
        :sort-options="[
            'posted_at' => __('messages.sort_by_date'),
            'refund_number' => __('messages.sort_by_number'),
        ]"
        :filters="[]"
        :total-count="$totalCount"
        :per-page="(int) request('per_page', 25)"
        :show-view-toggle="false"
        :show-export-import="false"
        :show-pagination="false"
    />

    @if ($returns->isEmpty())
        {{-- Empty state --}}
        <div class="bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 rounded-2xl p-8 sm:p-12 text-center">
            <div class="mx-auto mb-4 w-16 h-16 rounded-2xl bg-sky-50 dark:bg-sky-950/60 grid place-items-center text-sky-500 dark:text-sky-400">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>
            </div>
            <p class="font-black text-slate-800 dark:text-slate-200">{{ __('messages.returns_empty') }}</p>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">{{ __('messages.returns_empty_hint') }}</p>
            <a href="{{ route('pos.returns.create', $storeRouteParams) }}" class="admin-primary-btn mt-4 inline-flex">
                {{ __('messages.new_return') }}
            </a>
        </div>
    @else
        {{-- Desktop table --}}
        <div class="hidden md:block bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-black text-slate-700 dark:text-slate-200">
                        <tr>
                            <th class="px-4 py-3">{{ __('messages.return_number') }}</th>
                            <th class="px-4 py-3">{{ __('messages.sale_receipt') }}</th>
                            <th class="px-4 py-3">{{ __('messages.items') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('messages.total') }}</th>
                            <th class="px-4 py-3">{{ __('messages.refund_method') }}</th>
                            <th class="px-4 py-3">{{ __('messages.date') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-slate-700">
                        @foreach ($returns as $return)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/50 transition">
                                <td class="px-4 py-3">
                                    <a href="{{ route('pos.returns.show', [...$storeRouteParams, 'return' => $return->id]) }}"
                                       class="font-mono text-xs font-black text-sky-700 dark:text-sky-400 hover:underline">{{ $return->refund_number }}</a>
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 text-[10px] font-black rounded-full bg-sky-100 dark:bg-sky-900/50 text-sky-700 dark:text-sky-300">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ $return->sale?->receipt_number ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 tabular-nums">{{ $return->items->sum('quantity') }}</td>
                                <td class="px-4 py-3 text-right font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums">
                                    Ks {{ number_format((float) $return->total) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($return->payments as $payment)
                                            <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-black rounded-full {{ $payment->method === 'cash' ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300' : 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300' }}">
                                                {{ $payment->method === 'cash' ? __('messages.cash_refund') : __('messages.credit_refund') }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">{{ $return->posted_at?->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('pos.returns.show', [...$storeRouteParams, 'return' => $return->id]) }}"
                                       title="{{ __('messages.view') }}"
                                       class="min-h-11 inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-black text-sky-700 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/40 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        {{ __('messages.view') }}
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
            @foreach ($returns as $return)
                <a href="{{ route('pos.returns.show', [...$storeRouteParams, 'return' => $return->id]) }}"
                   class="bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 rounded-xl p-3.5 active:scale-[.99] transition">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono text-xs font-black text-sky-700 dark:text-sky-400">{{ $return->refund_number }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-black rounded-full bg-sky-100 dark:bg-sky-900/50 text-sky-700 dark:text-sky-300">{{ $statusLabel }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 uppercase tracking-wide font-semibold">{{ __('messages.sale_receipt') }}</p>
                            <p class="font-mono text-xs text-slate-600 dark:text-slate-300 truncate">{{ $return->sale?->receipt_number ?? '—' }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 uppercase tracking-wide font-semibold">{{ __('messages.total') }}</p>
                            <p class="font-mono text-sm font-black text-slate-900 dark:text-slate-100 tabular-nums">Ks {{ number_format((float) $return->total) }}</p>
                        </div>
                    </div>
                    <div class="mt-2.5 flex items-center justify-between gap-2">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($return->payments as $payment)
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-black rounded-full {{ $payment->method === 'cash' ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300' : 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300' }}">
                                    {{ $payment->method === 'cash' ? __('messages.cash_refund') : __('messages.credit_refund') }}
                                </span>
                            @endforeach
                        </div>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">{{ $return->posted_at?->format('d M Y') }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-2 sm:mt-4">{{ $returns->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
