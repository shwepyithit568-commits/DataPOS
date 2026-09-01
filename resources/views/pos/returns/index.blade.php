@extends('layouts.admin.app')

@section('title', __('messages.returns_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    $totalCount = $returns->total();
    $statusLabel = __('messages.posted');
@endphp
<div class="w-full space-y-0.5 pb-6"
     x-data="{
        viewMode: localStorage.getItem('pos_returns_view') || 'table',
        setView(mode) {
            this.viewMode = mode;
            localStorage.setItem('pos_returns_view', mode);
        }
     }">

    {{-- ============================================================
         1. COMPACT PAGE HEADER — title, store, and primary CTA
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 grid place-items-center text-base font-bold shadow-xs flex-shrink-0">
                ↩️
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.returns_title') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.returns_sub') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0">
            <a href="{{ route('pos.returns.create', $storeRouteParams) }}"
               class="h-7 px-3 rounded-md bg-sky-600 hover:bg-sky-500 text-white text-xs font-black shadow-2xs hover:shadow-sky-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5">
                    <path d="M12 4v16m8-8H4"/>
                </svg>
                <span>{{ __('messages.new_return') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. SUMMARY STAT CARDS (Row-based centered alignment)
         ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-0.5 sm:gap-1" role="list" aria-label="{{ __('messages.returns_summary') }}">
        {{-- Total Returns --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300 shadow-inner text-xs sm:text-sm font-bold">
                📦
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['total']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.returns_title') }}
                </p>
            </div>
        </div>

        {{-- Total Refunded Amount --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-rose-200/70 dark:border-rose-900/50 shadow-2xs bg-rose-50/20 dark:bg-rose-950/10 flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300 shadow-inner text-xs sm:text-sm font-bold">
                💸
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-outfit truncate">
                    Ks {{ number_format((float) $summary['refunded']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-rose-600/80 dark:text-rose-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.refund_total') }}
                </p>
            </div>
        </div>

        {{-- Today's Returns --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-amber-200/70 dark:border-amber-900/50 shadow-2xs bg-amber-50/20 dark:bg-amber-950/10 flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold">
                📅
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['today']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-amber-600/80 dark:text-amber-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.returns_today') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. INTERACTIVE SEARCH & FILTER TOOLBAR (Theme Governance Style)
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-1">
        {{-- Left: Search input & Filter pills --}}
        <div class="flex flex-wrap items-center gap-1.5 flex-1">
            {{-- Search Bar --}}
            <form method="GET" action="{{ route('pos.returns.index', $storeRouteParams) }}" class="relative min-w-[180px] sm:min-w-[260px] flex-1 max-w-sm">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="{{ __('messages.search_receipt_customer') }}"
                       class="w-full h-7 pl-8 pr-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-sky-500 focus:bg-white dark:focus:bg-slate-900 transition" />
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </form>

            {{-- Filter Pills --}}
            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
                <a href="{{ route('pos.returns.index', $storeRouteParams) }}"
                   class="px-2 py-0.5 rounded text-[11px] font-bold transition cursor-pointer {{ empty($search) ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                    {{ __('messages.all') ?? 'All' }} ({{ $totalCount }})
                </a>
            </div>
        </div>

        {{-- Right: Export Button & View mode switcher --}}
        <div class="flex items-center gap-1 self-end sm:self-auto">
            @if(!empty($exportUrl))
                <a href="{{ $exportUrl }}"
                   title="Export Excel (.xlsx)"
                   class="h-6 px-2 rounded text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                    <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Excel</span>
                </a>
            @endif

            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
                <button type="button"
                        @click="setView('table')"
                        class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                        :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                    <span>{{ __('messages.view_table') ?? 'Table' }}</span>
                </button>
                <button type="button"
                        @click="setView('card')"
                        class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                        :class="viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span>{{ __('messages.view_cards') ?? 'Cards' }}</span>
                </button>
            </div>
        </div>
    </div>

    @if ($returns->isEmpty())
        {{-- Empty state --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-6 sm:p-8 text-center shadow-2xs">
            <div class="mx-auto mb-3 w-12 h-12 rounded-lg bg-sky-50 dark:bg-sky-950/60 grid place-items-center text-sky-500 dark:text-sky-400 text-xl">
                ↩️
            </div>
            <p class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-200">{{ __('messages.returns_empty') }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 max-w-sm mx-auto">{{ __('messages.returns_empty_hint') }}</p>
            <a href="{{ route('pos.returns.create', $storeRouteParams) }}" class="h-7 px-3 rounded-md bg-sky-600 hover:bg-sky-500 text-white text-xs font-black shadow-2xs transition inline-flex items-center gap-1.5 mt-3 active:scale-95 cursor-pointer">
                <span>{{ __('messages.new_return') }}</span>
            </a>
        </div>
    @else
        {{-- 4. Desktop Table View Standard --}}
        <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="px-3 py-2 min-w-[150px]">{{ __('messages.return_number') }}</th>
                            <th class="px-3 py-2 min-w-[130px]">{{ __('messages.sale_receipt') }}</th>
                            <th class="px-3 py-2 min-w-[80px]">{{ __('messages.items') }}</th>
                            <th class="px-3 py-2 min-w-[120px] text-right">{{ __('messages.total') }}</th>
                            <th class="px-3 py-2 min-w-[140px]">{{ __('messages.refund_method') }}</th>
                            <th class="px-3 py-2 min-w-[130px]">{{ __('messages.date') }}</th>
                            <th class="px-3 py-2 min-w-[90px] text-right">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($returns as $return)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                <td class="px-3 py-2 align-middle">
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('pos.returns.show', [...$storeRouteParams, 'return' => $return->id]) }}"
                                           class="font-mono text-xs font-black text-sky-600 dark:text-sky-400 hover:underline">{{ $return->refund_number }}</a>
                                        <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60">{{ $statusLabel }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <span class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ $return->sale?->receipt_number ?? '—' }}</span>
                                </td>
                                <td class="px-3 py-2 align-middle text-slate-500 dark:text-slate-400 tabular-nums font-semibold">{{ $return->items->sum('quantity') }}</td>
                                <td class="px-3 py-2 align-middle text-right font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums">
                                    Ks {{ number_format((float) $return->total) }}
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($return->payments as $payment)
                                            <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold rounded-full {{ $payment->method === 'cash' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' }}">
                                                {{ $payment->method === 'cash' ? __('messages.cash_refund') : __('messages.credit_refund') }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-middle text-[11px] text-slate-500 dark:text-slate-400">{{ $return->posted_at?->format('d M Y H:i') }}</td>
                                <td class="px-3 py-2 align-middle text-right">
                                    <a href="{{ route('pos.returns.show', [...$storeRouteParams, 'return' => $return->id]) }}"
                                       title="{{ __('messages.view') }}"
                                       class="h-6 px-2 rounded text-[11px] font-bold text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/50 hover:bg-sky-100 dark:hover:bg-sky-900/50 border border-sky-200/60 dark:border-sky-800/60 transition inline-flex items-center gap-1 cursor-pointer">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <span>{{ __('messages.view') }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 5. Card View Grid (Responsive) --}}
        <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0.5 sm:gap-1">
            @foreach ($returns as $return)
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-2.5 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-1.5">
                            <span class="font-mono text-xs font-black text-sky-600 dark:text-sky-400">{{ $return->refund_number }}</span>
                            <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60">{{ $statusLabel }}</span>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between gap-1.5 text-xs">
                            <div class="min-w-0">
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wide font-bold">{{ __('messages.sale_receipt') }}</p>
                                <p class="font-mono text-xs text-slate-700 dark:text-slate-300 truncate">{{ $return->sale?->receipt_number ?? '—' }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wide font-bold">{{ __('messages.total') }}</p>
                                <p class="font-mono text-xs font-black text-slate-900 dark:text-slate-100 tabular-nums">Ks {{ number_format((float) $return->total) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 pt-1.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1.5">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($return->payments as $payment)
                                <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-bold rounded-full {{ $payment->method === 'cash' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' }}">
                                    {{ $payment->method === 'cash' ? __('messages.cash_refund') : __('messages.credit_refund') }}
                                </span>
                            @endforeach
                        </div>
                        <a href="{{ route('pos.returns.show', [...$storeRouteParams, 'return' => $return->id]) }}"
                           class="h-6 px-2 rounded text-[11px] font-bold text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/50 hover:bg-sky-100 dark:hover:bg-sky-900/50 border border-sky-200/60 dark:border-sky-800/60 transition inline-flex items-center gap-1 cursor-pointer">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <span>{{ __('messages.view') }}</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-1">{{ $returns->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
