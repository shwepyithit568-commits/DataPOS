@extends('layouts.admin.app')

@section('title', $customer->name . ' - ' . __('messages.sidebar_receivables') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="space-y-6">

    {{-- Breadcrumbs & Back Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.admin_dashboard') }}</a>
                <span>/</span>
                <a href="{{ route('store.admin.receivables.index', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.sidebar_receivables') }}</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-200 font-semibold">{{ $customer->name }}</span>
            </div>
            <div class="flex items-center gap-3 mt-1.5">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 font-outfit">
                    {{ $customer->name }}
                </h1>
                @php $bal = (float) $balance; @endphp
                @if ($bal > 0)
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                        {{ __('messages.receivables_current_debt') }}: {{ number_format($bal, 0) }} Ks
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        {{ __('messages.receivables_settled') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('store.admin.receivables.statement', ['store_slug' => $store->slug, 'customer' => $customer->id, 'format' => 'a4']) }}"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>{{ __('messages.print_statement_a4') }}</span>
            </a>

            <a href="{{ route('store.admin.receivables.statement', ['store_slug' => $store->slug, 'customer' => $customer->id, 'format' => 'thermal']) }}"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>{{ __('messages.print_statement_thermal') }} (80mm)</span>
            </a>
        </div>
    </div>

    {{-- Customer Profile & Collection Summary Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Customer Info Card --}}
        <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300 flex items-center justify-center font-bold text-lg font-outfit">
                    {{ mb_substr($customer->name, 0, 1) }}
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-slate-100">{{ $customer->name }}</h3>
                    <div class="text-xs text-slate-500 font-mono">{{ $customer->phone ?: 'No phone' }}</div>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2 text-xs">
                @if(!empty($customer->address))
                    <div>
                        <span class="text-slate-400 block">{{ __('messages.address') }}:</span>
                        <span class="text-slate-700 dark:text-slate-300 font-medium">{{ $customer->address }}</span>
                    </div>
                @endif
                <div>
                    <span class="text-slate-400 block">{{ __('messages.email') }}:</span>
                    <span class="text-slate-700 dark:text-slate-300 font-mono">{{ $customer->email ?: '-' }}</span>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-3">
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60">
                    <span class="text-[11px] text-slate-400 block">{{ __('messages.receivables_total_incurred') }}</span>
                    <span class="text-sm font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ number_format((float) $totalIncurred, 0) }} Ks</span>
                </div>
                <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/30">
                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 block">{{ __('messages.receivables_total_paid') }}</span>
                    <span class="text-sm font-bold text-emerald-700 dark:text-emerald-300 font-outfit">{{ number_format((float) $totalCollected, 0) }} Ks</span>
                </div>
            </div>
        </div>

        {{-- Quick Debt Collection Form --}}
        <div class="lg:col-span-2 p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit mb-3 flex items-center justify-between">
                <span>{{ __('messages.receivables_collect_payment_modal_title') }}</span>
                <span class="text-xs font-normal text-slate-400">{{ __('messages.receivables_instant_ledger_update') }}</span>
            </h3>

            @if ($bal > 0)
                <form action="{{ route('store.admin.receivables.collect', ['store_slug' => $store->slug, 'customer' => $customer->id]) }}" method="POST" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Amount --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('messages.receivables_amount_to_collect') }} (Ks) *
                            </label>
                            <div class="relative">
                                <input type="number"
                                       name="amount"
                                       id="collect_amount_input"
                                       value="{{ old('amount', $balance) }}"
                                       step="any"
                                       min="1"
                                       max="{{ $balance }}"
                                       required
                                       class="w-full px-3.5 py-2.5 text-base font-bold font-outfit rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                <button type="button"
                                        onclick="document.getElementById('collect_amount_input').value = '{{ $balance }}'"
                                        class="absolute right-2 top-2 px-2 py-1 text-[11px] font-bold rounded-md bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300">
                                    {{ __('messages.receivables_pay_full') }}
                                </button>
                            </div>
                        </div>

                        {{-- Payment Method --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('messages.payment_method') }}
                            </label>
                            <select name="payment_method"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 text-sm focus:outline-none">
                                <option value="cash">{{ __('messages.payment_method_cash') }} (ငွေသား)</option>
                                <option value="kpay">KPay (KBZPay)</option>
                                <option value="wave">WavePay</option>
                                <option value="bank">{{ __('messages.payment_method_bank') }} (ဘဏ်လွှဲ)</option>
                                <option value="other">{{ __('messages.payment_method_other') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Reference No --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('messages.reference_no') }} (Optional)
                            </label>
                            <input type="text"
                                   name="reference_no"
                                   value="{{ old('reference_no') }}"
                                   placeholder="Txn ID / စလစ်နံပါတ်"
                                   class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('messages.notes') }} (Optional)
                            </label>
                            <input type="text"
                                   name="notes"
                                   value="{{ old('notes') }}"
                                   placeholder="{{ __('messages.receivables_collect_note_placeholder') }}"
                                   class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-bold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-500/20 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ __('messages.receivables_confirm_collection') }}</span>
                        </button>
                    </div>
                </form>
            @else
                <div class="p-6 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 text-center">
                    <div class="w-10 h-10 mx-auto rounded-full bg-emerald-100 dark:bg-emerald-900 text-emerald-600 dark:text-emerald-300 flex items-center justify-center mb-2">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="font-bold text-emerald-800 dark:text-emerald-300 text-sm">{{ __('messages.receivables_all_debts_settled') }}</div>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">{{ __('messages.receivables_no_outstanding_balance_notice') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Customer Ledger Timeline Table --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.receivables_ledger_history_title') }}</h3>
                <p class="text-xs text-slate-400">{{ __('messages.receivables_ledger_history_sub') }}</p>
            </div>
            <span class="text-xs text-slate-500 font-mono">{{ $history->count() }} records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/80 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200/80 dark:bg-slate-800/60 dark:text-slate-400 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5">{{ __('messages.date') }}</th>
                        <th class="px-4 py-3.5">{{ __('messages.type') }}</th>
                        <th class="px-4 py-3.5">{{ __('messages.reference') }}</th>
                        <th class="px-4 py-3.5 text-right">{{ __('messages.amount') }}</th>
                        <th class="px-4 py-3.5">{{ __('messages.notes') }}</th>
                        <th class="px-5 py-3.5 text-right">{{ __('messages.created_by') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($history as $entry)
                        @php
                            $isDebt = (float) $entry->amount > 0;
                            $amt = abs((float) $entry->amount);
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            {{-- Date --}}
                            <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                <div>{{ $entry->occurred_at ? $entry->occurred_at->translatedFormat('d M Y, h:i A') : $entry->created_at->translatedFormat('d M Y, h:i A') }}</div>
                            </td>

                            {{-- Type Badge --}}
                            <td class="px-4 py-3.5">
                                @if ($entry->type === 'sale_debt')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                        <span>💳</span> {{ __('messages.receivables_type_sale_debt') }}
                                    </span>
                                @elseif ($entry->type === 'collection')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                        <span>💰</span> {{ __('messages.receivables_type_collection') }}
                                    </span>
                                @elseif ($entry->type === 'opening_balance')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                        <span>📂</span> {{ __('messages.receivables_type_opening_balance') }}
                                    </span>
                                @elseif ($entry->type === 'refund')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                        <span>↩️</span> {{ __('messages.receivables_type_refund') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $entry->type }}
                                    </span>
                                @endif
                            </td>

                            {{-- Reference --}}
                            <td class="px-4 py-3.5 text-xs text-slate-600 dark:text-slate-300 font-mono">
                                @if ($entry->source_type === 'pos_sale')
                                    <span class="text-violet-600 dark:text-violet-400 font-semibold">Sale #{{ $entry->source_id }}</span>
                                @elseif ($entry->source_type === 'pos_return')
                                    <span class="text-amber-600 dark:text-amber-400 font-semibold">Return #{{ $entry->source_id }}</span>
                                @else
                                    {{ $entry->source_type ?: '-' }}
                                @endif
                            </td>

                            {{-- Amount --}}
                            <td class="px-4 py-3.5 text-right font-black font-outfit text-sm whitespace-nowrap {{ $isDebt ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                {{ $isDebt ? '+' : '-' }} {{ number_format($amt, 0) }} Ks
                            </td>

                            {{-- Notes --}}
                            <td class="px-4 py-3.5 text-xs text-slate-500 dark:text-slate-400 max-w-xs truncate">
                                {{ $entry->notes ?: '-' }}
                            </td>

                            {{-- Created By --}}
                            <td class="px-5 py-3.5 text-right text-xs text-slate-400">
                                {{ $entry->actor?->name ?? 'System' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                                {{ __('messages.no_history_records') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
