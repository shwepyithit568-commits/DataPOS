@extends('layouts.admin.app')

@section('title', __('messages.reports_payments') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6">
    @include('pos.reports._tabs', ['active' => 'payments'])

    {{-- Filter Toolbar --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-1.5 sm:p-2 shadow-2xs">
        <form method="GET" action="{{ route('pos.reports.payments', ['store_slug' => $store->slug]) }}"
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

            {{-- Export Actions (Excel & CSV) & Print --}}
            <div class="flex items-center gap-1 ml-auto">
                <a href="{{ route('pos.reports.payments.export', ['store_slug' => $store->slug, 'preset' => $preset ?? 'custom', 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'format' => 'xlsx']) }}"
                   class="h-7 rounded-md px-2.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition inline-flex items-center gap-1"
                   title="Export Excel (.xlsx)">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" /></svg>
                    <span>Excel</span>
                </a>
                <a href="{{ route('pos.reports.payments.export', ['store_slug' => $store->slug, 'preset' => $preset ?? 'custom', 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'format' => 'csv']) }}"
                   class="h-7 rounded-md px-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-1"
                   title="Export CSV (.csv)">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>CSV</span>
                </a>
                <button type="button" @click="window.print()"
                        class="h-7 rounded-md px-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-1"
                        title="Print Report">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span class="hidden sm:inline">{{ __('messages.print') }}</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Centered Row-based Overview KPI Stat Cards (Admin UI v4.1 standard) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1">
        {{-- Total Collected --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3 py-1">
                <div class="p-2 rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-950/70 dark:text-sky-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="text-center">
                    <p class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('messages.reports_grand_total') }}</p>
                    <p class="text-sm sm:text-base font-black font-mono tracking-tight text-sky-700 dark:text-sky-300 tabular-nums">
                        {{ format_currency($report['total_collected'], $store) }}
                    </p>
                    <p class="text-[9px] text-slate-400 font-semibold">{{ $report['payment_count'] }} {{ __('messages.receipts') }}</p>
                </div>
            </div>
        </div>

        {{-- Digital Payments (KPay, Wave, CB, AYA, MMQR, Bank) --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3 py-1">
                <div class="p-2 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                </div>
                <div class="text-center">
                    <p class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('messages.digital_payments') }}</p>
                    <p class="text-sm sm:text-base font-black font-mono tracking-tight text-indigo-700 dark:text-indigo-300 tabular-nums">
                        {{ format_currency($report['digital_collected'], $store) }}
                    </p>
                    <p class="text-[9px] text-indigo-500 font-semibold">KBZPay / Wave / MMQR / Bank</p>
                </div>
            </div>
        </div>

        {{-- Cash In Drawer (Net) --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3 py-1">
                <div class="p-2 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
                <div class="text-center">
                    <p class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('messages.cash_collections') }}</p>
                    <p class="text-sm sm:text-base font-black font-mono tracking-tight text-emerald-700 dark:text-emerald-300 tabular-nums">
                        {{ format_currency($report['cash_collected'], $store) }}
                    </p>
                    <p class="text-[9px] text-emerald-500 font-semibold">{{ __('messages.reports_cash') }}</p>
                </div>
            </div>
        </div>

        {{-- Net Settlement (After Refunds) --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3 py-1">
                <div class="p-2 rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-950/70 dark:text-violet-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="text-center">
                    <p class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('messages.net_settlement') }}</p>
                    <p class="text-sm sm:text-base font-black font-mono tracking-tight text-violet-700 dark:text-violet-300 tabular-nums">
                        {{ format_currency($report['net_settlement'], $store) }}
                    </p>
                    <p class="text-[9px] text-slate-400 font-semibold">− {{ format_currency($report['total_refunded'], $store) }} {{ __('messages.refund_amount') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Methods Breakdown Table --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="p-2.5 sm:p-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-outfit">
                    💳 {{ __('messages.payment_method_breakdown') }}
                </h2>
                <p class="text-[10px] text-slate-400 font-mono">Reconcile digital settlements, cash collections and reference verifications</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 border-collapse min-w-[720px]">
                <thead class="sticky top-0 bg-slate-50/90 dark:bg-slate-800/80 backdrop-blur-xs text-[10px] sm:text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-black border-b border-slate-200/80 dark:border-slate-800">
                    <tr>
                        <th class="px-3 py-2.5">{{ __('messages.reports_payment_method') }}</th>
                        <th class="px-3 py-2.5 text-right">{{ __('messages.reports_sale_count') }}</th>
                        <th class="px-3 py-2.5 text-right">{{ __('messages.reports_grand_total') }}</th>
                        <th class="px-3 py-2.5 text-right">{{ __('messages.change') }}</th>
                        <th class="px-3 py-2.5 text-right">{{ __('messages.net_amount') }}</th>
                        <th class="px-3 py-2.5 text-right">{{ __('messages.refund_amount') }}</th>
                        <th class="px-3 py-2.5 text-right">{{ __('messages.reference_number') }}</th>
                        <th class="px-3 py-2.5 text-right">{{ __('messages.percentage') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse ($report['methods'] as $m)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="px-3 py-2 font-bold text-slate-900 dark:text-slate-100">
                                <div class="flex items-center gap-2">
                                    <x-payment-method-icon :iconValue="$m['method']" class="h-6 w-6" />
                                    <span>{{ $m['name'] }}</span>
                                    @if ($m['is_digital'])
                                        <span class="text-[9px] px-1.5 py-0.2 rounded bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300 font-black">Digital</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2 text-right font-mono tabular-nums text-slate-800 dark:text-slate-200">
                                {{ number_format($m['count']) }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono tabular-nums text-slate-900 dark:text-slate-100 font-bold">
                                {{ format_currency($m['total_amount'], $store) }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono tabular-nums text-slate-500">
                                {{ (float)$m['change_given'] > 0 ? format_currency($m['change_given'], $store) : '—' }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono font-black tabular-nums text-emerald-700 dark:text-emerald-300">
                                {{ format_currency($m['net_amount'], $store) }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono tabular-nums {{ (float)$m['refund_amount'] > 0 ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-400' }}">
                                {{ (float)$m['refund_amount'] > 0 ? ('−' . format_currency($m['refund_amount'], $store)) : '—' }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono tabular-nums">
                                @if ($m['is_digital'])
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $m['reference_count'] === $m['count'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300' }}">
                                        {{ $m['reference_count'] }} / {{ $m['count'] }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right font-mono tabular-nums text-slate-700 dark:text-slate-300 font-bold">
                                {{ $m['share_percentage'] }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-xs text-slate-400 py-8">
                                {{ __('messages.no_data_available') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if (!empty($report['methods']))
                    <tfoot class="bg-slate-100/75 dark:bg-slate-800/90 font-black text-slate-900 dark:text-slate-100 border-t-2 border-slate-200 dark:border-slate-700">
                        <tr>
                            <td class="px-3 py-2.5 uppercase tracking-wider text-[11px]">{{ __('messages.total') }}</td>
                            <td class="px-3 py-2.5 text-right font-mono tabular-nums">{{ number_format($report['payment_count']) }}</td>
                            <td class="px-3 py-2.5 text-right font-mono tabular-nums text-sky-700 dark:text-sky-300">{{ format_currency($report['total_collected'], $store) }}</td>
                            <td class="px-3 py-2.5 text-right font-mono tabular-nums text-slate-500">{{ format_currency($report['total_change'], $store) }}</td>
                            <td class="px-3 py-2.5 text-right font-mono tabular-nums text-emerald-700 dark:text-emerald-300">{{ format_currency($report['net_sales'], $store) }}</td>
                            <td class="px-3 py-2.5 text-right font-mono tabular-nums text-rose-600 dark:text-rose-400">−{{ format_currency($report['total_refunded'], $store) }}</td>
                            <td class="px-3 py-2.5 text-right font-mono tabular-nums text-slate-500">—</td>
                            <td class="px-3 py-2.5 text-right font-mono tabular-nums">100%</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Recent Payments Ledger --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="p-2.5 sm:p-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-outfit">
                📑 {{ __('messages.recent_payments') }}
            </h3>
            <span class="text-[10px] text-slate-400 font-mono">{{ count($report['payments']) }} transactions</span>
        </div>

        <div class="overflow-x-auto max-h-[380px] overflow-y-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 border-collapse min-w-[650px]">
                <thead class="sticky top-0 bg-slate-50/90 dark:bg-slate-800/80 backdrop-blur-xs text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-black border-b border-slate-200/80 dark:border-slate-800">
                    <tr>
                        <th class="px-3 py-2">{{ __('messages.receipt') }}</th>
                        <th class="px-3 py-2">{{ __('messages.reports_date') }}</th>
                        <th class="px-3 py-2">{{ __('messages.cashier') }}</th>
                        <th class="px-3 py-2">{{ __('messages.reports_payment_method') }}</th>
                        <th class="px-3 py-2">{{ __('messages.reference_number') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('messages.total') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('messages.change') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse ($report['payments'] as $p)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="px-3 py-1.5 font-mono font-bold text-sky-600 dark:text-sky-400">
                                {{ $p->sale?->receipt_number ?: ('#' . $p->pos_sale_id) }}
                            </td>
                            <td class="px-3 py-1.5 text-slate-500 text-[11px]">
                                {{ $p->created_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-3 py-1.5 text-slate-700 dark:text-slate-300 font-semibold">
                                {{ $p->sale?->cashier?->name ?? '—' }}
                            </td>
                            <td class="px-3 py-1.5">
                                <div class="inline-flex items-center gap-1.5">
                                    <x-payment-method-icon :iconValue="$p->method" class="h-4 w-4" />
                                    <span class="font-bold uppercase text-[11px]">{{ $p->method }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-1.5 font-mono text-[11px] text-slate-600 dark:text-slate-400">
                                {{ $p->reference ?: '—' }}
                            </td>
                            <td class="px-3 py-1.5 text-right font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums">
                                {{ format_currency($p->amount, $store) }}
                            </td>
                            <td class="px-3 py-1.5 text-right font-mono tabular-nums text-slate-500">
                                {{ (float)$p->change_given > 0 ? format_currency($p->change_given, $store) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-xs text-slate-400 py-6">
                                {{ __('messages.no_data_available') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
