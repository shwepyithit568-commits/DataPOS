@extends('layouts.pos.app')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.sidebar_payables') }}</p>
                <h1 class="text-xl font-black mt-0.5">{{ $supplier->name }}</h1>
            </div>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases/payables') }}"
               class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                ← {{ __('messages.back_to_payables') }}
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-sm font-semibold">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-300 px-4 py-3 text-sm font-semibold">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        {{-- Supplier summary --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.payables_outstanding') }}</p>
                <p class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">
                    Ks {{ number_format((float) $supplier->remaining_balance) }}
                </p>
            </div>
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.payables_total_credit') }}</p>
                <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">
                    Ks {{ number_format((float) $supplier->total_credit) }}
                </p>
            </div>
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.payables_total_repaid') }}</p>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                    Ks {{ number_format((float) $supplier->total_repaid) }}
                </p>
            </div>
        </div>

        {{-- General payment form --}}
        @if ($supplier->has_outstanding_balance)
            <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                <p class="text-sm font-bold mb-3">{{ __('messages.payables_make_general_payment') }}</p>
                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases/payables/' . $supplier->id . '/pay') }}" class="flex items-end gap-3 flex-wrap">
                    @csrf
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-bold text-slate-500 mb-1">{{ __('messages.payables_amount') }}</label>
                        <input type="number" name="amount" step="0.01" min="0.01" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none"
                               placeholder="0.00">
                    </div>
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-bold text-slate-500 mb-1">{{ __('messages.receiving_reference') }}</label>
                        <input type="text" name="reference" maxlength="100"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none"
                               placeholder="Optional">
                    </div>
                    <button type="submit"
                            class="rounded-xl px-6 py-2 text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition">
                        💳 {{ __('messages.payables_pay') }}
                    </button>
                </form>
                <p class="text-xs text-slate-400 mt-2">{{ __('messages.payables_fifo_note') }}</p>
            </div>
        @endif

        {{-- Unpaid POs --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-3">{{ __('messages.payables_unpaid_orders') }}</p>
            @if ($unpaid->isEmpty())
                <p class="text-center text-sm text-slate-500 py-8">{{ __('messages.payables_no_unpaid') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="text-left px-3 py-2">{{ __('messages.po_number') }}</th>
                                <th class="text-left px-3 py-2">{{ __('messages.reports_date') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.po_total_cost') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.payables_paid') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.payables_balance') }}</th>
                                <th class="text-right px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($unpaid as $po)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="px-3 py-2.5 font-mono font-bold text-sky-600 dark:text-sky-400">
                                        {{ $po->po_number }}
                                    </td>
                                    <td class="px-3 py-2.5 whitespace-nowrap">{{ $po->received_at?->format('d M Y') ?? $po->created_at->format('d M Y') }}</td>
                                    <td class="px-3 py-2.5 text-right font-mono">Ks {{ number_format((float) $po->total_cost) }}</td>
                                    <td class="px-3 py-2.5 text-right font-mono text-emerald-600 dark:text-emerald-400">Ks {{ number_format((float) $po->paid_amount) }}</td>
                                    <td class="px-3 py-2.5 text-right font-mono font-bold text-rose-600 dark:text-rose-400">Ks {{ number_format((float) $po->remaining_balance) }}</td>
                                    <td class="px-3 py-2.5 text-right">
                                        <button type="button" x-data
                                                @click="$dispatch('open-pay-modal', { id: {{ $po->id }}, number: '{{ addslashes($po->po_number) }}', balance: {{ $po->remaining_balance }} })"
                                                class="text-xs font-bold text-sky-600 hover:underline">{{ __('messages.payables_pay_now') }}</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Payment history --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-3">{{ __('messages.payables_history') }}</p>
            @if ($history->isEmpty())
                <p class="text-center text-sm text-slate-500 py-8">{{ __('messages.payables_no_history') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="text-left px-3 py-2">{{ __('messages.reports_date') }}</th>
                                <th class="text-left px-3 py-2">{{ __('messages.po_number') }}</th>
                                <th class="text-left px-3 py-2">{{ __('messages.receiving_reference') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.payables_amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($history as $h)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="px-3 py-2.5 whitespace-nowrap">{{ $h->created_at->format('d M Y, H:i') }}</td>
                                    <td class="px-3 py-2.5 font-mono font-bold text-sky-600 dark:text-sky-400">
                                        {{ $h->metadata['po_number'] ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2.5">{{ $h->metadata['reference'] ?? '—' }}</td>
                                    <td class="px-3 py-2.5 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        Ks {{ number_format((float) ($h->metadata['amount'] ?? 0)) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Payment modal --}}
    <div x-data="{ open: false, id: null, number: '', balance: 0 }"
         @open-pay-modal.window="open = true; id = $event.detail.id; number = $event.detail.number; balance = $event.detail.balance"
         x-show="open"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
         x-cloak>
        <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-xl"
             @click.outside="open = false">
            <h3 class="text-lg font-black mb-4">{{ __('messages.payables_pay_specific') }}</h3>
            <p class="text-sm text-slate-500 mb-4 font-mono">{{ __('messages.po_number') }}: <span x-text="number"></span></p>
            <form :action="`/store/{{ $store->slug }}/pos/purchases/${id}/pay`" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">{{ __('messages.payables_amount') }}</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">{{ __('messages.receiving_reference') }}</label>
                    <input type="text" name="reference" maxlength="100"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none"
                           placeholder="Optional">
                </div>
                <div class="flex items-center gap-2 justify-end pt-2">
                    <button type="button" @click="open = false"
                            class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="rounded-xl px-6 py-2 text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition">
                        💳 {{ __('messages.payables_pay') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection