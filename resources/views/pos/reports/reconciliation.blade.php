@extends('layouts.admin.app')

@section('title', __('messages.business_reconciliation') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6">
    @include('pos.reports._tabs', ['active' => 'reconciliation'])

    {{-- Filter Toolbar --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-1.5 sm:p-2 shadow-2xs">
        <form method="GET" action="{{ route('pos.reports.reconciliation', ['store_slug' => $store->slug]) }}"
              class="flex flex-wrap items-center gap-1.5 sm:gap-2">
            <div class="flex items-center gap-1 text-xs font-bold text-slate-600 dark:text-slate-300">
                <span>{{ __('messages.reports_from') }}:</span>
                <input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ today()->toDateString() }}"
                       class="h-7 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500 shadow-2xs">
            </div>
            <div class="flex items-center gap-1 text-xs font-bold text-slate-600 dark:text-slate-300">
                <span>{{ __('messages.reports_to') }}:</span>
                <input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ today()->toDateString() }}"
                       class="h-7 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-sky-500 shadow-2xs">
            </div>
            <button type="submit" class="h-7 rounded-md px-3 text-xs font-bold bg-sky-600 hover:bg-sky-500 text-white transition shadow-2xs">
                {{ __('messages.reports_filter') }}
            </button>
            <button type="button" @click="window.print()"
                    class="h-7 rounded-md px-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-1 ml-auto"
                    title="Print Reconciliation Sheet">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span class="hidden sm:inline">{{ __('messages.print') }}</span>
            </button>
        </form>
    </div>

    {{-- Centered Row-based Reconciliation Overview Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-0.5 sm:gap-1">
        {{-- Stock Equation Status Card --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3 py-1">
                <div class="p-2 rounded-lg {{ $stock['is_clean'] ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-400' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/70 dark:text-rose-400' }}">
                    @if ($stock['is_clean'])
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    @endif
                </div>
                <div class="text-center">
                    <p class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('messages.stock_reconciliation_equation') }}</p>
                    <div class="flex items-center justify-center gap-2 mt-0.5">
                        <span class="text-xs sm:text-sm font-black px-2 py-0.5 rounded-md {{ $stock['is_clean'] ? 'bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200' : 'bg-rose-100 dark:bg-rose-900/60 text-rose-800 dark:text-rose-200' }}">
                            {{ $stock['is_clean'] ? __('messages.biz_reconciliation_clean') : __('messages.biz_reconciliation_variance') }}
                        </span>
                        <span class="font-mono text-xs font-bold {{ $stock['is_clean'] ? 'text-slate-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400' }}">
                            Δ {{ (float) $stock['discrepancy'] == 0 ? '0' : rtrim(rtrim(number_format((float) $stock['discrepancy'], 3, '.', ''), '0'), '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cash Equation Status Card --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3 py-1">
                <div class="p-2 rounded-lg {{ !$cash['has_variance'] ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-400' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/70 dark:text-rose-400' }}">
                    @if (!$cash['has_variance'])
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    @endif
                </div>
                <div class="text-center">
                    <p class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('messages.cash_reconciliation_equation') }}</p>
                    <div class="flex items-center justify-center gap-2 mt-0.5">
                        <span class="text-xs sm:text-sm font-black px-2 py-0.5 rounded-md {{ !$cash['has_variance'] ? 'bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200' : 'bg-rose-100 dark:bg-rose-900/60 text-rose-800 dark:text-rose-200' }}">
                            {{ !$cash['has_variance'] ? __('messages.biz_reconciliation_clean') : __('messages.biz_reconciliation_variance') }}
                        </span>
                        <span class="font-mono text-xs font-bold {{ !$cash['has_variance'] ? 'text-slate-600 dark:text-slate-400' : 'text-rose-600 dark:text-rose-400' }}">
                            Δ {{ format_currency((float) $cash['variance'], $store) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Sections --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-0.5 sm:gap-1">
        {{-- Stock Equation Detail Table --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs space-y-2">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-outfit">
                        📦 {{ __('messages.stock_reconciliation_equation') }}
                    </h2>
                    <p class="text-[10px] text-slate-400 font-mono">Opening + Inbound − Outbound = Calculated Closing</p>
                </div>
            </div>

            @php
                $fmtQty = fn($val) => (float)$val == 0 ? '0' : rtrim(rtrim(number_format((float)$val, 3, '.', ''), '0'), '.');
            @endphp

            <div class="space-y-1 text-xs">
                {{-- Opening Stock --}}
                <div class="flex items-center justify-between py-1 px-2 rounded-md bg-slate-50 dark:bg-slate-800/50">
                    <span class="text-slate-600 dark:text-slate-300 font-medium">{{ __('messages.opening_stock') }}</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ $fmtQty($stock['opening_stock']) }}</span>
                </div>

                {{-- Inbound Components --}}
                <div class="pl-2 border-l-2 border-emerald-400 space-y-0.5">
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">+ {{ __('messages.purchases') }} (GRN)</span>
                        <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">+{{ $fmtQty($stock['purchases_received']) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">+ {{ __('messages.sales_returns') }}</span>
                        <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">+{{ $fmtQty($stock['sales_returns']) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">+ {{ __('messages.positive_adjustments') }}</span>
                        <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">+{{ $fmtQty($stock['positive_adjustments']) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">+ {{ __('messages.transfers_in') }}</span>
                        <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">+{{ $fmtQty($stock['transfers_in']) }}</span>
                    </div>
                </div>

                {{-- Outbound Components --}}
                <div class="pl-2 border-l-2 border-rose-400 space-y-0.5">
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">− {{ __('messages.pos_sales') }}</span>
                        <span class="font-mono text-rose-600 dark:text-rose-400 font-semibold">−{{ $fmtQty($stock['pos_sales']) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">− {{ __('messages.online_sales') }}</span>
                        <span class="font-mono text-rose-600 dark:text-rose-400 font-semibold">−{{ $fmtQty($stock['online_sales']) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">− {{ __('messages.purchase_returns') }}</span>
                        <span class="font-mono text-rose-600 dark:text-rose-400 font-semibold">−{{ $fmtQty($stock['purchase_returns']) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">− {{ __('messages.negative_adjustments') }}</span>
                        <span class="font-mono text-rose-600 dark:text-rose-400 font-semibold">−{{ $fmtQty($stock['negative_adjustments']) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">− {{ __('messages.transfers_out') }}</span>
                        <span class="font-mono text-rose-600 dark:text-rose-400 font-semibold">−{{ $fmtQty($stock['transfers_out']) }}</span>
                    </div>
                </div>

                {{-- Calculated Closing vs Actual --}}
                <div class="pt-1.5 border-t border-slate-200 dark:border-slate-800 space-y-1">
                    <div class="flex items-center justify-between py-1 px-2 rounded-md bg-sky-50/80 dark:bg-sky-950/40 text-sky-900 dark:text-sky-200">
                        <span class="font-bold">{{ __('messages.calculated_closing_stock') }}</span>
                        <span class="font-mono font-black text-sm">{{ $fmtQty($stock['calculated_closing']) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1 px-2 rounded-md bg-slate-50 dark:bg-slate-800/60">
                        <span class="text-slate-600 dark:text-slate-400">{{ __('messages.actual_ledger_balance') }}</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ $fmtQty($stock['actual_ledger_balance']) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2 rounded-md {{ $stock['is_clean'] ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200' : 'bg-rose-50 text-rose-800 dark:bg-rose-950/60 dark:text-rose-200' }}">
                        <span class="font-black">{{ __('messages.discrepancy') }}</span>
                        <span class="font-mono font-black text-sm">{{ $fmtQty($stock['discrepancy']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cash Drawer Equation Detail Table --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs space-y-2">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-outfit">
                        💵 {{ __('messages.cash_reconciliation_equation') }}
                    </h2>
                    <p class="text-[10px] text-slate-400 font-mono">Opening + Inflows − Outflows = Expected Closing Cash</p>
                </div>
            </div>

            <div class="space-y-1 text-xs">
                {{-- Opening Cash --}}
                <div class="flex items-center justify-between py-1 px-2 rounded-md bg-slate-50 dark:bg-slate-800/50">
                    <span class="text-slate-600 dark:text-slate-300 font-medium">{{ __('messages.opening_cash') }}</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ format_currency($cash['opening_cash'], $store) }}</span>
                </div>

                {{-- Inflows --}}
                <div class="pl-2 border-l-2 border-emerald-400 space-y-0.5">
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">+ {{ __('messages.cash_sales') }}</span>
                        <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">+{{ format_currency($cash['cash_sales'], $store) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">+ {{ __('messages.debt_collections') }}</span>
                        <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">+{{ format_currency($cash['debt_collections'], $store) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">+ {{ __('messages.cash_in') }}</span>
                        <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">+{{ format_currency($cash['cash_in'], $store) }}</span>
                    </div>
                </div>

                {{-- Outflows --}}
                <div class="pl-2 border-l-2 border-rose-400 space-y-0.5">
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">− {{ __('messages.cash_refunds') }}</span>
                        <span class="font-mono text-rose-600 dark:text-rose-400 font-semibold">−{{ format_currency($cash['cash_refunds'], $store) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">− {{ __('messages.expenses') }}</span>
                        <span class="font-mono text-rose-600 dark:text-rose-400 font-semibold">−{{ format_currency($cash['expenses_paid'], $store) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">− {{ __('messages.supplier_payments') }}</span>
                        <span class="font-mono text-rose-600 dark:text-rose-400 font-semibold">−{{ format_currency($cash['supplier_payments'], $store) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px] py-0.5 px-1">
                        <span class="text-slate-500">− {{ __('messages.cash_out') }}</span>
                        <span class="font-mono text-rose-600 dark:text-rose-400 font-semibold">−{{ format_currency($cash['cash_out'], $store) }}</span>
                    </div>
                </div>

                {{-- Expected vs Counted Closing --}}
                <div class="pt-1.5 border-t border-slate-200 dark:border-slate-800 space-y-1">
                    <div class="flex items-center justify-between py-1 px-2 rounded-md bg-sky-50/80 dark:bg-sky-950/40 text-sky-900 dark:text-sky-200">
                        <span class="font-bold">{{ __('messages.expected_closing_cash') }}</span>
                        <span class="font-mono font-black text-sm">{{ format_currency($cash['expected_closing_cash'], $store) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1 px-2 rounded-md bg-slate-50 dark:bg-slate-800/60">
                        <span class="text-slate-600 dark:text-slate-400">{{ __('messages.counted_cash') }}</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ format_currency($cash['counted_cash'], $store) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2 rounded-md {{ !$cash['has_variance'] ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200' : 'bg-rose-50 text-rose-800 dark:bg-rose-950/60 dark:text-rose-200' }}">
                        <span class="font-black">{{ __('messages.variance') }}</span>
                        <span class="font-mono font-black text-sm">{{ ((float)$cash['variance'] < 0 ? '−' : '+') . format_currency(abs((float)$cash['variance']), $store) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
