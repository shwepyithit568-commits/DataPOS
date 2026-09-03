@extends('layouts.admin.app')

@section('title', $supplier->name . ' - ' . __('messages.sidebar_payables') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6"
     x-data="{
         payModalOpen: false,
         payPoId: null,
         payPoNumber: '',
         payPoBalance: 0,
         openPoPay(id, number, balance) {
             this.payPoId = id;
             this.payPoNumber = number;
             this.payPoBalance = balance;
             this.payModalOpen = true;
         }
     }"
     @keydown.escape.window="payModalOpen = false">

    {{-- 1. Compact Header Banner (34px - 38px) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-rose-500 to-amber-500 text-white grid place-items-center text-sm font-black shrink-0 select-none shadow-2xs">
                {{ mb_strtoupper(mb_substr(trim($supplier->name), 0, 1)) }}
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span>{{ $supplier->name }}</span>
                    @if ($supplier->phone)
                        <a href="tel:{{ preg_replace('/\s+/', '', $supplier->phone) }}" class="text-xs font-mono font-normal text-sky-600 dark:text-sky-400 hover:underline">
                            ({{ $supplier->phone }})
                        </a>
                    @endif
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ $store->name }} — {{ __('messages.payables_title') }}
                    @if ($supplier->contact_person) · {{ $supplier->contact_person }} @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0 flex-wrap">
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases/payables') }}"
               class="h-7 px-2.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-xs font-bold transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <span>←</span>
                <span>{{ __('messages.back_to_payables') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
               class="h-7 px-2.5 rounded-md bg-sky-50 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300 hover:bg-sky-100 dark:hover:bg-sky-900/60 border border-sky-200 dark:border-sky-800 text-xs font-bold transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <span>🛒</span>
                <span class="hidden sm:inline">{{ __('messages.po_list_title') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/pos') }}"
               class="h-7 px-2.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-xs font-bold transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <span>←</span>
                <span>{{ __('messages.back_to_pos') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs text-emerald-700 dark:text-emerald-300 flex items-start justify-between gap-2 shadow-2xs"
             x-data="{ show: true }" x-show="show">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold shrink-0">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="show = false" class="text-emerald-500 hover:text-emerald-700 font-black text-xs">✕</button>
        </div>
    @endif
    @if (session('error'))
        <div class="p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-700 dark:text-rose-300 flex items-start justify-between gap-2 shadow-2xs"
             x-data="{ show: true }" x-show="show">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold shrink-0">⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="show = false" class="text-rose-500 hover:text-rose-700 font-black text-xs">✕</button>
        </div>
    @endif

    {{-- 2. Row-Based Center-Aligned Summary Stat Cards (gap-0.5 sm:gap-1) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-0.5 sm:gap-1" role="list">
        {{-- Card 1: Outstanding Debt --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300 shadow-inner text-xs sm:text-sm font-bold">
                💰
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-outfit truncate">
                    {{ format_currency($supplier->remaining_balance, $store) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.payables_outstanding') }}
                </p>
            </div>
        </div>

        {{-- Card 2: Total Credit (Purchases) --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold">
                🧾
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit truncate">
                    {{ format_currency($supplier->total_credit, $store) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.payables_total_credit') }}
                </p>
            </div>
        </div>

        {{-- Card 3: Total Repaid --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                ✓
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit truncate">
                    {{ format_currency($supplier->total_repaid, $store) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.payables_total_repaid') }}
                </p>
            </div>
        </div>
    </div>

    {{-- 3. General Payment Quick Card (FIFO Repayment) --}}
    @if ($supplier->has_outstanding_balance)
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-2">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                    <span>💳</span>
                    <span>{{ __('messages.payables_make_general_payment') }}</span>
                </h3>
                <span class="text-[10px] text-slate-400 font-medium">{{ __('messages.payables_fifo_note') }}</span>
            </div>

            <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases/payables/' . $supplier->id . '/pay') }}"
                  class="flex flex-col sm:flex-row items-stretch sm:items-end gap-1.5">
                @csrf
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-0.5">{{ __('messages.payables_amount') }}</label>
                    <input type="number" name="amount" step="any" min="1" required max="{{ $supplier->remaining_balance }}"
                           value="{{ (float) $supplier->remaining_balance }}"
                           class="w-full h-7 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2.5 text-xs font-mono font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-rose-500">
                </div>
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-0.5">{{ __('messages.receiving_reference') }}</label>
                    <input type="text" name="reference" maxlength="100" placeholder="e.g. KPay, Bank Transfer, Cash"
                           class="w-full h-7 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2.5 text-xs text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-rose-500">
                </div>
                <div>
                    <button type="submit"
                            class="w-full sm:w-auto h-7 px-4 rounded-md text-xs font-black text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-2xs transition active:scale-95 cursor-pointer flex items-center justify-center gap-1.5">
                        <span>💳</span>
                        <span>{{ __('messages.payables_pay') }}</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- 4. Unpaid POs Table --}}
    <div class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="px-3 py-1.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                <span>📋</span>
                <span>{{ __('messages.payables_unpaid_orders') }}</span>
            </h3>
            <span class="text-xs font-mono font-bold text-slate-500">{{ $unpaid->count() }} Open POs</span>
        </div>

        @if ($unpaid->isEmpty())
            <div class="p-6 text-center text-xs font-bold text-slate-400">
                🎉 {{ __('messages.payables_no_unpaid') }}
            </div>
        @else
            <div class="overflow-x-auto max-h-[50vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
                <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                        <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                            <th class="py-2 px-2.5 text-center w-10">#</th>
                            <th class="py-2 px-2.5 min-w-[140px]">{{ __('messages.po_number') }}</th>
                            <th class="py-2 px-2.5 text-center min-w-[120px]">{{ __('messages.reports_date') }}</th>
                            <th class="py-2 px-2.5 text-right min-w-[130px]">{{ __('messages.po_total_cost') }}</th>
                            <th class="py-2 px-2.5 text-right min-w-[130px]">{{ __('messages.payables_paid') }}</th>
                            <th class="py-2 px-2.5 text-right min-w-[130px]">{{ __('messages.payables_balance') }}</th>
                            <th class="py-2 px-2.5 text-center w-24">{{ __('messages.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @foreach ($unpaid as $uIdx => $po)
                            <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                <td class="py-2 px-2.5 text-center font-mono font-bold text-slate-400">{{ $uIdx + 1 }}</td>
                                <td class="py-2 px-2.5">
                                    <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id) }}"
                                       class="font-mono font-bold text-xs text-sky-600 dark:text-sky-400 hover:underline">
                                        {{ $po->po_number }}
                                    </a>
                                </td>
                                <td class="py-2 px-2.5 text-center font-mono text-[11px] text-slate-500">
                                    {{ $po->received_at?->format('d M Y') ?? $po->created_at->format('d M Y') }}
                                </td>
                                <td class="py-2 px-2.5 text-right font-mono font-bold">
                                    {{ format_currency($po->total_cost, $store) }}
                                </td>
                                <td class="py-2 px-2.5 text-right font-mono text-emerald-600 dark:text-emerald-400 font-bold">
                                    {{ format_currency($po->paid_amount, $store) }}
                                </td>
                                <td class="py-2 px-2.5 text-right font-mono font-black text-rose-600 dark:text-rose-400 bg-rose-50/30 dark:bg-rose-950/20">
                                    {{ format_currency($po->remaining_balance, $store) }}
                                </td>
                                <td class="py-2 px-2.5 text-center whitespace-nowrap">
                                    <button type="button"
                                            @click="openPoPay({{ $po->id }}, '{{ addslashes($po->po_number) }}', {{ $po->remaining_balance }})"
                                            class="h-6 px-2 rounded text-[11px] font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                                        <span>💳</span>
                                        <span>{{ __('messages.payables_pay_now') }}</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- 5. Payment History Table --}}
    <div class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="px-3 py-1.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                <span>📜</span>
                <span>{{ __('messages.payables_history') }}</span>
            </h3>
            <span class="text-xs font-mono font-bold text-slate-500">{{ $history->count() }} Records</span>
        </div>

        @if ($history->isEmpty())
            <div class="p-6 text-center text-xs font-bold text-slate-400">
                {{ __('messages.payables_no_history') }}
            </div>
        @else
            <div class="overflow-x-auto max-h-[50vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
                <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                        <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                            <th class="py-2 px-2.5 text-center w-10">#</th>
                            <th class="py-2 px-2.5 text-center min-w-[130px]">{{ __('messages.reports_date') }}</th>
                            <th class="py-2 px-2.5 min-w-[140px]">{{ __('messages.po_number') }}</th>
                            <th class="py-2 px-2.5 min-w-[160px]">{{ __('messages.receiving_reference') }}</th>
                            <th class="py-2 px-2.5 text-right min-w-[140px]">{{ __('messages.payables_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @foreach ($history as $hIdx => $h)
                            <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                <td class="py-2 px-2.5 text-center font-mono font-bold text-slate-400">{{ $hIdx + 1 }}</td>
                                <td class="py-2 px-2.5 text-center font-mono text-[11px] text-slate-500">
                                    {{ $h->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="py-2 px-2.5 font-mono font-bold text-sky-600 dark:text-sky-400">
                                    {{ $h->metadata['po_number'] ?? '—' }}
                                </td>
                                <td class="py-2 px-2.5 text-slate-600 dark:text-slate-400 font-medium">
                                    {{ $h->metadata['reference'] ?? '—' }}
                                </td>
                                <td class="py-2 px-2.5 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                    {{ format_currency($h->metadata['amount'] ?? 0, $store) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Specific PO Pay Modal --}}
    <div x-show="payModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-xs"
         role="dialog" aria-modal="true">
        <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-2xl space-y-3"
             @click.outside="payModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-md bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xs font-black">💳</span>
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">
                        <span>{{ __('messages.payables_pay_specific') }}</span>
                    </h3>
                </div>
                <button type="button" @click="payModalOpen = false" class="w-6 h-6 rounded-md grid place-items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-xs font-black cursor-pointer">&times;</button>
            </div>

            <p class="text-xs text-slate-500 font-mono">PO: <span class="font-bold text-sky-600 dark:text-sky-400" x-text="payPoNumber"></span></p>

            <form :action="`/store/{{ $store->slug }}/pos/purchases/${payPoId}/pay`" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.payables_amount') }}</label>
                    <input type="number" name="amount" step="any" min="1" :max="payPoBalance" :value="payPoBalance" required
                           class="w-full h-8 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 text-xs font-mono font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.receiving_reference') }}</label>
                    <input type="text" name="reference" maxlength="100" placeholder="e.g. KPay, Bank Slip"
                           class="w-full h-8 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
                <div class="flex items-center justify-end gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="payModalOpen = false"
                            class="h-8 px-3 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 transition cursor-pointer">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="h-8 px-4 rounded-lg text-xs font-black text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-2xs transition cursor-pointer inline-flex items-center gap-1 active:scale-95">
                        <span>💳</span>
                        <span>{{ __('messages.payables_pay') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection