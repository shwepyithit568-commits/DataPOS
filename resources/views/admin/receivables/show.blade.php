@extends('layouts.admin.app')

@section('title', $customer->name . ' - ' . __('messages.sidebar_receivables') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div x-data="{
    previewModalOpen: false,
    previewUrl: '',
    isPdf: false,
    openSlip(url, filename) {
        this.previewUrl = url;
        this.isPdf = (filename || url).toLowerCase().endsWith('.pdf');
        this.previewModalOpen = true;
    }
}" class="w-full space-y-0.5 pb-6">

    {{-- ============================================================
         1. COMPACT PAGE HEADER
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 rounded-lg px-2.5 py-1.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0 flex items-center gap-2.5">
            <a href="{{ route('store.admin.receivables.index', ['store_slug' => $store->slug]) }}"
               class="w-7 h-7 sm:w-8 sm:h-8 rounded-md bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 grid place-items-center text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition shadow-2xs shrink-0 cursor-pointer"
               title="{{ __('messages.back') }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight truncate">
                        {{ $customer->name }}
                    </h1>
                    @php $bal = (float) $balance; @endphp
                    @if ($bal > 0)
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-black font-mono bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                            {{ format_currency($bal, $store) }}
                        </span>
                    @else
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            {{ __('messages.receivables_settled') }}
                        </span>
                    @endif
                </div>
                <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    📞 {{ $customer->phone ?: 'No phone' }} · {{ $store->name }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 shrink-0 self-start sm:self-auto">
            <a href="{{ route('store.admin.receivables.statement', ['store_slug' => $store->slug, 'customer' => $customer->id, 'format' => 'a4']) }}"
               target="_blank"
               class="h-7 px-2 sm:px-2.5 rounded-md text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1 shadow-2xs cursor-pointer">
                <span>📄</span>
                <span>A4</span>
            </a>

            <a href="{{ route('store.admin.receivables.statement', ['store_slug' => $store->slug, 'customer' => $customer->id, 'format' => 'thermal']) }}"
               target="_blank"
               class="h-7 px-2 sm:px-2.5 rounded-md text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1 shadow-2xs cursor-pointer">
                <span>🧾</span>
                <span>80mm</span>
            </a>
        </div>
    </header>

    {{-- Customer Profile & Collection Summary Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-0.5 sm:gap-1 items-start">

        {{-- Customer Info Card --}}
        <div class="p-2.5 sm:p-3 rounded-lg border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-2">
            <div class="flex items-center gap-2 pb-1.5 border-b border-slate-100 dark:border-slate-800">
                <div class="w-8 h-8 rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300 flex items-center justify-center font-bold text-sm shrink-0">
                    {{ mb_substr($customer->name, 0, 1) }}
                </div>
                <div class="min-w-0">
                    <h2 class="text-xs font-black text-slate-900 dark:text-slate-100 truncate">{{ $customer->name }}</h2>
                    <div class="text-[10px] text-slate-500 font-mono">{{ $customer->phone ?: 'No phone' }}</div>
                </div>
            </div>

            <div class="space-y-1 text-xs">
                @if(!empty($customer->address))
                    <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 font-bold text-[11px]">{{ __('messages.address') }}</span>
                        <span class="text-slate-700 dark:text-slate-300 font-medium text-[11px] truncate max-w-[170px]">{{ $customer->address }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold text-[11px]">{{ __('messages.email') }}</span>
                    <span class="text-slate-700 dark:text-slate-300 font-mono text-[11px] truncate max-w-[170px]">{{ $customer->email ?: '-' }}</span>
                </div>
            </div>

            <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-1.5">
                <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-800/60">
                    <span class="text-[10px] text-slate-400 block truncate">{{ __('messages.receivables_total_incurred') }}</span>
                    <span class="text-xs font-bold text-slate-900 dark:text-slate-100 font-mono">{{ format_currency((float) $totalIncurred, $store) }}</span>
                </div>
                <div class="p-2 rounded-md bg-emerald-50 dark:bg-emerald-950/30">
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 block truncate">{{ __('messages.receivables_total_paid') }}</span>
                    <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300 font-mono">{{ format_currency((float) $totalCollected, $store) }}</span>
                </div>
            </div>
        </div>

        {{-- Quick Debt Collection Form --}}
        <div class="lg:col-span-2 p-2.5 sm:p-3 rounded-lg border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800 mb-2.5">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                    <span>💰</span>
                    <span>{{ __('messages.receivables_collect_payment_modal_title') }}</span>
                </h2>
                <span class="text-[10px] text-slate-400">{{ __('messages.receivables_instant_ledger_update') }}</span>
            </div>

            @if ($bal > 0)
                <form action="{{ route('store.admin.receivables.collect', ['store_slug' => $store->slug, 'customer' => $customer->id]) }}" method="POST" enctype="multipart/form-data" class="space-y-2 text-xs">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        {{-- Amount --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('messages.receivables_amount_to_collect') }} <span class="text-rose-500">*</span>
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
                                       class="w-full h-8 px-2.5 text-xs font-bold font-mono rounded-lg border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                                <button type="button"
                                        onclick="document.getElementById('collect_amount_input').value = '{{ $balance }}'"
                                        class="absolute right-1 top-1 h-6 px-2 text-[10px] font-bold rounded bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 cursor-pointer">
                                    {{ __('messages.receivables_pay_full') }}
                                </button>
                            </div>
                        </div>

                        {{-- Payment Method --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('messages.payment_method') }}
                            </label>
                            <select name="payment_method"
                                    class="w-full h-8 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-emerald-500 text-xs focus:outline-none">
                                <option value="cash">{{ __('messages.payment_method_cash') }}</option>
                                <option value="kpay">KPay</option>
                                <option value="wave">WavePay</option>
                                <option value="bank">{{ __('messages.payment_method_bank') }}</option>
                                <option value="other">{{ __('messages.payment_method_other') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        {{-- Reference No --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('messages.reference_no') }}
                            </label>
                            <input type="text"
                                   name="reference_no"
                                   value="{{ old('reference_no') }}"
                                   placeholder="Txn ID"
                                   class="w-full h-8 px-2.5 text-xs rounded-lg border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                        </div>

                        {{-- Payment Slip Upload --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center justify-between">
                                <span>{{ __('messages.receivables_slip_image') }}</span>
                                <span class="text-[10px] font-normal text-slate-400">{{ __('messages.optional') ?? 'Optional' }}</span>
                            </label>
                            <input type="file"
                                   name="slip_image"
                                   accept="image/jpeg,image/png,image/webp,application/pdf"
                                   class="block w-full text-xs text-slate-500 dark:text-slate-400 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:font-bold file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-950/70 dark:file:text-emerald-300 hover:file:bg-emerald-100 cursor-pointer border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-800">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.notes') }}
                        </label>
                        <input type="text"
                               name="notes"
                               value="{{ old('notes') }}"
                               placeholder="{{ __('messages.receivables_collect_note_placeholder') }}"
                               class="w-full h-8 px-2.5 text-xs rounded-lg border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="submit" class="h-8 px-4 text-xs font-black rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition flex items-center gap-1 cursor-pointer active:scale-95">
                            <span>✓</span>
                            <span>{{ __('messages.receivables_confirm_collection') }}</span>
                        </button>
                    </div>
                </form>
            @else
                <div class="p-4 rounded-lg bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 text-center">
                    <div class="font-bold text-emerald-800 dark:text-emerald-300 text-xs">{{ __('messages.receivables_all_debts_settled') }}</div>
                    <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">{{ __('messages.receivables_no_outstanding_balance_notice') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Customer Ledger Timeline Table --}}
    <div class="rounded-lg border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs overflow-hidden">
        <div class="px-2.5 py-1.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">{{ __('messages.receivables_ledger_history_title') }}</h2>
                <p class="text-[10px] text-slate-400">{{ __('messages.receivables_ledger_history_sub') }}</p>
            </div>
            <span class="text-[11px] text-slate-500 font-mono">{{ $history->count() }} records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 min-w-[600px]">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-1.5 px-2.5">{{ __('messages.date') }}</th>
                        <th class="py-1.5 px-2.5">{{ __('messages.type') }}</th>
                        <th class="py-1.5 px-2.5">{{ __('messages.reference') }}</th>
                        <th class="py-1.5 px-2.5 text-right">{{ __('messages.amount') }}</th>
                        <th class="py-1.5 px-2.5">{{ __('messages.notes') }}</th>
                        <th class="py-1.5 px-2.5 text-center">{{ __('messages.receivables_slip_image') }}</th>
                        <th class="py-1.5 px-2.5 text-right">{{ __('messages.created_by') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($history as $entry)
                        @php
                            $isDebt = (float) $entry->amount > 0;
                            $amt = abs((float) $entry->amount);
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                            {{-- Date --}}
                            <td class="py-1.5 px-2.5 font-mono text-[11px] text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                <div>{{ $entry->occurred_at ? $entry->occurred_at->translatedFormat('d/m/Y, h:i A') : $entry->created_at->translatedFormat('d/m/Y, h:i A') }}</div>
                            </td>

                            {{-- Type Badge --}}
                            <td class="py-1.5 px-2.5">
                                @if ($entry->type === 'sale_debt')
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[10px] font-semibold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                        💳 {{ __('messages.receivables_type_sale_debt') }}
                                    </span>
                                @elseif ($entry->type === 'collection')
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                        💰 {{ __('messages.receivables_type_collection') }}
                                    </span>
                                @elseif ($entry->type === 'opening_balance')
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[10px] font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                        📂 {{ __('messages.receivables_type_opening_balance') }}
                                    </span>
                                @elseif ($entry->type === 'refund')
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[10px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                        ↩️ {{ __('messages.receivables_type_refund') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $entry->type }}
                                    </span>
                                @endif
                            </td>

                            {{-- Reference --}}
                            <td class="py-1.5 px-2.5 font-mono text-[11px] text-slate-600 dark:text-slate-300">
                                @if ($entry->source_type === 'pos_sale')
                                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Sale #{{ $entry->source_id }}</span>
                                @elseif ($entry->source_type === 'pos_return')
                                    <span class="text-amber-600 dark:text-amber-400 font-semibold">Return #{{ $entry->source_id }}</span>
                                @else
                                    {{ $entry->source_type ?: '-' }}
                                @endif
                            </td>

                            {{-- Amount --}}
                            <td class="py-1.5 px-2.5 text-right font-black font-mono text-xs whitespace-nowrap {{ $isDebt ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                {{ $isDebt ? '+' : '-' }} {{ format_currency($amt, $store) }}
                            </td>

                            {{-- Notes --}}
                            <td class="py-1.5 px-2.5 text-[11px] text-slate-500 dark:text-slate-400 max-w-xs truncate">
                                {{ $entry->notes ?: '-' }}
                            </td>

                            {{-- Slip Attachment --}}
                            <td class="py-1.5 px-2.5 text-center whitespace-nowrap">
                                @if ($entry->slip_image)
                                    <button type="button"
                                            @click="openSlip('{{ \Illuminate\Support\Facades\Storage::url($entry->slip_image) }}', '{{ $entry->slip_image }}')"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-violet-100 text-violet-700 hover:bg-violet-200 dark:bg-violet-950/70 dark:text-violet-300 dark:hover:bg-violet-900 border border-violet-200/80 dark:border-violet-800/60 shadow-2xs transition cursor-pointer">
                                        <span>🖼️</span>
                                        <span>{{ __('messages.receivables_view_slip') }}</span>
                                    </button>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 text-[11px]">-</span>
                                @endif
                            </td>

                            {{-- Created By --}}
                            <td class="py-1.5 px-2.5 text-right text-[11px] text-slate-400">
                                {{ $entry->actor?->name ?? 'System' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-400 text-xs">
                                {{ __('messages.no_history_records') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================
         SLIP PREVIEW MODAL
         ============================================================ --}}
    <div x-show="previewModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/70 backdrop-blur-xs overflow-y-auto"
         @keydown.escape.window="previewModalOpen = false"
         @click.self="previewModalOpen = false">
        <div class="relative w-full max-w-lg rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3.5 shadow-2xl space-y-3 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span>🧾</span>
                    <span>{{ __('messages.receivables_slip_image') }}</span>
                </h3>
                <div class="flex items-center gap-1">
                    <a :href="previewUrl" target="_blank" download class="h-6 px-2 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 flex items-center gap-1 transition">
                        <span>⬇️</span>
                        <span>Open / Download</span>
                    </a>
                    <button type="button" @click="previewModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 grid place-items-center text-xs font-bold transition cursor-pointer">✕</button>
                </div>
            </div>

            <div class="w-full max-h-[70vh] flex items-center justify-center bg-slate-50 dark:bg-slate-950/60 rounded-lg p-2 overflow-auto border border-slate-100 dark:border-slate-800">
                <template x-if="!isPdf">
                    <img :src="previewUrl" alt="Payment Slip" class="max-w-full max-h-[60vh] object-contain rounded-md shadow-xs">
                </template>
                <template x-if="isPdf">
                    <div class="p-6 text-center space-y-2">
                        <span class="text-3xl">📄</span>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300">PDF Document</p>
                        <a :href="previewUrl" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-500">
                            Open PDF in New Window
                        </a>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
