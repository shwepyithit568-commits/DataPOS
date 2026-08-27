@extends('layouts.pos.app')

@section('title', $supplier->name . ' - ' . __('messages.payables_title') . ' - ' . $store->name)

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
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

    {{-- 1. Top Header Banner --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-rose-500 to-amber-500 text-white grid place-items-center text-sm font-black shrink-0 select-none shadow-2xs">
                {{ mb_strtoupper(mb_substr(trim($supplier->name), 0, 1)) }}
            </span>
            <div class="min-w-0">
                <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 truncate flex items-center gap-2">
                    <span>{{ $supplier->name }}</span>
                    @if ($supplier->phone)
                        <a href="tel:{{ preg_replace('/\s+/', '', $supplier->phone) }}" class="text-xs font-mono font-normal text-sky-600 dark:text-sky-400 hover:underline">
                            ({{ $supplier->phone }})
                        </a>
                    @endif
                </h1>
                <p class="text-[11px] text-slate-400 font-mono truncate">
                    {{ $store->name }} — {{ __('messages.payables_title') }}
                    @if ($supplier->contact_person) · {{ $supplier->contact_person }} @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap shrink-0">
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases/payables') }}"
               class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1 shadow-2xs">
                <span>←</span>
                <span>{{ __('messages.back_to_payables') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
               class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 hover:bg-sky-100 transition flex items-center gap-1 shadow-2xs">
                <span>🛒</span>
                <span>{{ __('messages.po_list_title') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs text-emerald-700 dark:text-emerald-300 flex items-start gap-2 shadow-2xs">
            <span class="text-sm font-bold shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-700 dark:text-rose-300 flex items-start gap-2 shadow-2xs">
            <span class="text-sm font-bold shrink-0">⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- 2. 3-Column KPI Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-2.5">
        {{-- Card 1: Outstanding Debt --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-rose-200/80 dark:border-rose-900/50 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">{{ __('messages.payables_outstanding') }}</span>
                <span class="w-6 h-6 rounded bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 grid place-items-center text-xs">💰</span>
            </div>
            <div class="mt-1">
                <p class="text-base sm:text-lg font-black text-rose-600 dark:text-rose-400 font-mono truncate">
                    Ks {{ number_format((float) $supplier->remaining_balance) }}
                </p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Current Balance Due</span>
            </div>
        </div>

        {{-- Card 2: Total Credit (Purchases) --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">{{ __('messages.payables_total_credit') }}</span>
                <span class="w-6 h-6 rounded bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-xs">🧾</span>
            </div>
            <div class="mt-1">
                <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 font-mono truncate">
                    Ks {{ number_format((float) $supplier->total_credit) }}
                </p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Cumulative Purchases</span>
            </div>
        </div>

        {{-- Card 3: Total Repaid --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ __('messages.payables_total_repaid') }}</span>
                <span class="w-6 h-6 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xs">✓</span>
            </div>
            <div class="mt-1">
                <p class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 font-mono truncate">
                    Ks {{ number_format((float) $supplier->total_repaid) }}
                </p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Cumulative Paid Out</span>
            </div>
        </div>
    </div>

    {{-- 3. General Payment Quick Card (FIFO Repayment) --}}
    @if ($supplier->has_outstanding_balance)
        <div class="p-3 sm:p-4 bg-white dark:bg-slate-900 rounded-xl border border-slate-200/90 dark:border-slate-800 shadow-2xs space-y-2.5">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                    <span>💳</span>
                    <span>{{ __('messages.payables_make_general_payment') }}</span>
                </h3>
                <span class="text-[11px] text-slate-400 font-mono">{{ __('messages.payables_fifo_note') }}</span>
            </div>

            <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases/payables/' . $supplier->id . '/pay') }}"
                  class="flex flex-col sm:flex-row items-stretch sm:items-end gap-2.5">
                @csrf
                <div class="flex-1">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('messages.payables_amount') }} (Ks)</label>
                    <input type="number" name="amount" step="any" min="1" required max="{{ $supplier->remaining_balance }}"
                           value="{{ (float) $supplier->remaining_balance }}"
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-1.5 text-xs font-mono font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-rose-500">
                </div>
                <div class="flex-1">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ __('messages.receiving_reference') }}</label>
                    <input type="text" name="reference" maxlength="100" placeholder="e.g. KPay, Bank Transfer, Cash"
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-1.5 text-xs text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-rose-500">
                </div>
                <div>
                    <button type="submit"
                            class="w-full sm:w-auto px-5 py-1.5 rounded-lg text-xs font-black text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-2xs transition active:scale-95 cursor-pointer flex items-center justify-center gap-1.5">
                        <span>💳</span>
                        <span>{{ __('messages.payables_pay') }}</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- 4. Unpaid POs Table --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden space-y-0">
        <div class="p-2.5 sm:p-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                <span>📋</span>
                <span>{{ __('messages.payables_unpaid_orders') }}</span>
            </h3>
            <span class="text-xs font-mono font-bold text-slate-400">{{ $unpaid->count() }} Open POs</span>
        </div>

        @if ($unpaid->isEmpty())
            <div class="p-6 text-center text-xs font-bold text-slate-400">
                🎉 {{ __('messages.payables_no_unpaid') }}
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse font-sans">
                    <thead class="bg-slate-100 dark:bg-slate-800/90 text-slate-700 dark:text-slate-300 font-bold uppercase text-[11px] sticky top-0 border-b border-slate-200 dark:border-slate-700">
                        <tr class="divide-x divide-slate-200 dark:divide-slate-700">
                            <th class="p-2.5 text-center w-10">#</th>
                            <th class="p-2.5 min-w-[140px]">{{ __('messages.po_number') }}</th>
                            <th class="p-2.5 text-center min-w-[120px]">{{ __('messages.reports_date') }}</th>
                            <th class="p-2.5 text-right min-w-[130px]">{{ __('messages.po_total_cost') }}</th>
                            <th class="p-2.5 text-right min-w-[130px]">{{ __('messages.payables_paid') }}</th>
                            <th class="p-2.5 text-right min-w-[130px]">{{ __('messages.payables_balance') }}</th>
                            <th class="p-2.5 text-center w-28">{{ __('messages.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                        @foreach ($unpaid as $uIdx => $po)
                            <tr class="divide-x divide-slate-200/60 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                <td class="p-2.5 text-center font-mono font-bold text-slate-400">{{ $uIdx + 1 }}</td>
                                <td class="p-2.5">
                                    <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id) }}"
                                       class="font-mono font-bold text-xs text-sky-600 dark:text-sky-400 hover:underline">
                                        {{ $po->po_number }}
                                    </a>
                                </td>
                                <td class="p-2.5 text-center font-mono text-[11px] text-slate-500">
                                    {{ $po->received_at?->format('d M Y') ?? $po->created_at->format('d M Y') }}
                                </td>
                                <td class="p-2.5 text-right font-mono font-bold">
                                    Ks {{ number_format((float) $po->total_cost) }}
                                </td>
                                <td class="p-2.5 text-right font-mono text-emerald-600 dark:text-emerald-400">
                                    Ks {{ number_format((float) $po->paid_amount) }}
                                </td>
                                <td class="p-2.5 text-right font-mono font-black text-rose-600 dark:text-rose-400">
                                    Ks {{ number_format((float) $po->remaining_balance) }}
                                </td>
                                <td class="p-2.5 text-center">
                                    <button type="button"
                                            @click="openPoPay({{ $po->id }}, '{{ addslashes($po->po_number) }}', {{ $po->remaining_balance }})"
                                            class="px-2.5 py-1 rounded-lg text-xs font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-2xs transition">
                                        {{ __('messages.payables_pay_now') }}
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
    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden space-y-0">
        <div class="p-2.5 sm:p-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                <span>📜</span>
                <span>{{ __('messages.payables_history') }}</span>
            </h3>
            <span class="text-xs font-mono font-bold text-slate-400">{{ $history->count() }} Records</span>
        </div>

        @if ($history->isEmpty())
            <div class="p-6 text-center text-xs font-bold text-slate-400">
                {{ __('messages.payables_no_history') }}
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse font-sans">
                    <thead class="bg-slate-100 dark:bg-slate-800/90 text-slate-700 dark:text-slate-300 font-bold uppercase text-[11px] sticky top-0 border-b border-slate-200 dark:border-slate-700">
                        <tr class="divide-x divide-slate-200 dark:divide-slate-700">
                            <th class="p-2.5 text-center w-10">#</th>
                            <th class="p-2.5 text-center min-w-[130px]">{{ __('messages.reports_date') }}</th>
                            <th class="p-2.5 min-w-[140px]">{{ __('messages.po_number') }}</th>
                            <th class="p-2.5 min-w-[160px]">{{ __('messages.receiving_reference') }}</th>
                            <th class="p-2.5 text-right min-w-[140px]">{{ __('messages.payables_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                        @foreach ($history as $hIdx => $h)
                            <tr class="divide-x divide-slate-200/60 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                <td class="p-2.5 text-center font-mono font-bold text-slate-400">{{ $hIdx + 1 }}</td>
                                <td class="p-2.5 text-center font-mono text-[11px] text-slate-500">
                                    {{ $h->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="p-2.5 font-mono font-bold text-sky-600 dark:text-sky-400">
                                    {{ $h->metadata['po_number'] ?? '—' }}
                                </td>
                                <td class="p-2.5 text-slate-600 dark:text-slate-400 font-medium">
                                    {{ $h->metadata['reference'] ?? '—' }}
                                </td>
                                <td class="p-2.5 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                    Ks {{ number_format((float) ($h->metadata['amount'] ?? 0)) }}
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
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
         role="dialog" aria-modal="true">
        <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-2xl space-y-3"
             @click.outside="payModalOpen = false">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                    <span>💳</span>
                    <span>{{ __('messages.payables_pay_specific') }}</span>
                </h3>
                <button type="button" @click="payModalOpen = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
            </div>

            <p class="text-xs text-slate-500 font-mono">PO: <span class="font-bold text-sky-600 dark:text-sky-400" x-text="payPoNumber"></span></p>

            <form :action="`/store/{{ $store->slug }}/pos/purchases/${payPoId}/pay`" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.payables_amount') }} (Ks)</label>
                    <input type="number" name="amount" step="any" min="1" :max="payPoBalance" :value="payPoBalance" required
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs font-mono font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.receiving_reference') }}</label>
                    <input type="text" name="reference" maxlength="100" placeholder="e.g. KPay, Bank Slip"
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="payModalOpen = false"
                            class="px-4 py-2 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg text-xs font-black text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-2xs transition">
                        💳 {{ __('messages.payables_pay') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection