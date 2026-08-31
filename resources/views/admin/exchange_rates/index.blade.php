@extends('layouts.admin.app')

@section('title', __('messages.exchange_title') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<script nonce="{{ $cspNonce }}">
window.exchangeHubData = function () {
    const ratesMap = {!! json_encode($currencies->pluck('exchange_rate', 'code')->toArray()) !!};

    return {
        // Converter state
        convAmount: 100,
        convFrom: 'USD',
        convTo: 'MMK',
        rates: ratesMap,

        // Pricing helper state
        calcCurr: 'USD',
        calcCost: 50,
        calcMarkup: 25,

        // Modals
        showAddModal: false,
        editModalOpen: false,
        editingCurr: { id: null, code: '', name: '', symbol: '', exchange_rate: 1, is_active: 1 },

        // Conversions
        get convertedResult() {
            const amt = parseFloat(this.convAmount) || 0;
            const fromRate = parseFloat(this.rates[this.convFrom]) || 1;
            const toRate = parseFloat(this.rates[this.convTo]) || 1;

            if (fromRate <= 0 || toRate <= 0) return 0;
            const inBase = amt * fromRate;
            return inBase / toRate;
        },

        swapCurrencies() {
            const temp = this.convFrom;
            this.convFrom = this.convTo;
            this.convTo = temp;
        },

        // Pricing calculations
        get landedCostMmk() {
            const cost = parseFloat(this.calcCost) || 0;
            const rate = parseFloat(this.rates[this.calcCurr]) || 1;
            return Math.round(cost * rate);
        },

        get suggestedPriceMmk() {
            const landed = this.landedCostMmk;
            const markup = parseFloat(this.calcMarkup) || 0;
            return Math.round(landed * (1 + (markup / 100)));
        },

        get profitMarginMmk() {
            return Math.max(0, this.suggestedPriceMmk - this.landedCostMmk);
        },

        openEdit(id, code, name, symbol, rate, active) {
            this.editingCurr = { id, code, name, symbol, exchange_rate: rate, is_active: active ? 1 : 0 };
            this.editModalOpen = true;
        }
    };
};
</script>

<div class="w-full space-y-5 sm:space-y-6" x-data="exchangeHubData()">

    {{-- ============================================================
         PAGE HEADER
         ============================================================ --}}
    <div class="admin-page-header">
        <div class="min-w-0">
            <h1 class="admin-page-title">
                {{ __('messages.exchange_title') }}
            </h1>
            <p class="admin-page-sub mt-1">
                {{ $store->name }} · {{ __('messages.exchange_subtitle') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{-- Add New Foreign Currency Modal Trigger --}}
            <button type="button"
                    @click="showAddModal = true"
                    class="admin-primary-btn bg-violet-600 hover:bg-violet-500">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('messages.exchange_add_new') }}</span>
            </button>
        </div>
    </div>

    {{-- Flash Notifications & Errors --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl text-sm text-emerald-800 dark:text-emerald-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-2xl text-sm text-rose-800 dark:text-rose-200">
            @foreach ($errors->all() as $err)
                <p>{{ $err }}</p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         SUMMARY STATS HAIRLINE GRID
         ============================================================ --}}
    <div class="admin-hairline-grid grid-cols-2 sm:grid-cols-4">
        {{-- 1. Base Currency --}}
        <div class="admin-hairline-cell bg-emerald-50/30 dark:bg-emerald-950/20">
            <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">{{ __('messages.exchange_base_currency') }}</div>
            <div class="admin-stat-value text-emerald-700 dark:text-emerald-300 font-mono">
                MMK (Ks)
            </div>
            <div class="admin-stat-sub text-slate-500">{{ __('messages.exchange_fixed_base_rate') }}</div>
        </div>

        {{-- 2. USD Rate --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-blue-600 dark:text-blue-400">{{ __('messages.exchange_usd_rate') }}</div>
            <div class="admin-stat-value text-blue-600 dark:text-blue-400 font-mono">
                {{ number_format($stats['usd_rate'], 0) }} Ks
            </div>
            <div class="admin-stat-sub text-slate-400">1 USD ($)</div>
        </div>

        {{-- 3. THB Rate --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-violet-600 dark:text-violet-400">{{ __('messages.exchange_thb_rate') }}</div>
            <div class="admin-stat-value text-violet-600 dark:text-violet-400 font-mono">
                {{ number_format($stats['thb_rate'], 2) }} Ks
            </div>
            <div class="admin-stat-sub text-slate-400">1 THB (฿)</div>
        </div>

        {{-- 4. CNY Rate --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-amber-600 dark:text-amber-400">{{ __('messages.exchange_cny_rate') }}</div>
            <div class="admin-stat-value text-amber-600 dark:text-amber-400 font-mono">
                {{ number_format($stats['cny_rate'], 2) }} Ks
            </div>
            <div class="admin-stat-sub text-slate-400">1 CNY (¥)</div>
        </div>
    </div>

    {{-- ============================================================
         TWO-COLUMN LAYOUT: RATES TABLE & LIVE CALCULATORS
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- LEFT COLUMN: DAILY EXCHANGE RATES BOARD (7 COLS) --}}
        <div class="lg:col-span-7 space-y-6">

            <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 font-outfit">
                            {{ __('messages.exchange_table_title') }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ __('messages.exchange_table_desc') }}
                        </p>
                    </div>
                </div>

                {{-- Bulk Rate Update Form --}}
                <form method="POST" action="{{ route('store.admin.exchange_rates.bulk_update', ['store_slug' => $store->slug]) }}" class="space-y-4">
                    @csrf

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-mono uppercase">
                                    <th class="pb-3">{{ __('messages.exchange_currency_code') }}</th>
                                    <th class="pb-3">{{ __('messages.exchange_currency_name') }}</th>
                                    <th class="pb-3 w-36">{{ __('messages.exchange_rate_to_mmk') }}</th>
                                    <th class="pb-3 text-center">{{ __('messages.status') }}</th>
                                    <th class="pb-3 text-right">{{ __('messages.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                @foreach($currencies as $curr)
                                    <tr class="{{ $curr->is_base ? 'bg-emerald-50/20 dark:bg-emerald-950/10' : '' }}">
                                        {{-- Code & Symbol --}}
                                        <td class="py-3 font-bold">
                                             <div class="flex items-center gap-1.5">
                                                 <span class="px-2.5 py-0.5 rounded-lg text-xs font-black font-mono {{ $curr->is_base ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700' }}">
                                                     {{ $curr->code }}
                                                 </span>
                                                 @if($curr->symbol)
                                                     <span class="text-slate-500 dark:text-slate-400 text-xs font-mono font-bold">({{ $curr->symbol }})</span>
                                                 @endif
                                             </div>
                                         </td>

                                         {{-- Name & Base Badge --}}
                                         <td class="py-3">
                                             <div class="font-bold text-slate-800 dark:text-slate-200">{{ $curr->name }}</div>
                                             @if($curr->is_base)
                                                 <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">★ {{ __('messages.exchange_base_currency_badge') }}</span>
                                             @elseif($curr->last_updated_at)
                                                 <span class="text-[10px] text-slate-400">{{ __('messages.updated') ?? 'Updated' }} {{ $curr->last_updated_at->diffForHumans() }}</span>
                                             @endif
                                         </td>

                                         {{-- Rate Input --}}
                                         <td class="py-3">
                                             @if($curr->is_base)
                                                 <span class="font-mono font-black text-slate-700 dark:text-slate-300 pl-2">1.0000 Ks</span>
                                             @else
                                                 <div class="relative">
                                                     <input type="number"
                                                            step="0.0001"
                                                            min="0.0001"
                                                            name="rates[{{ $curr->id }}]"
                                                            value="{{ $curr->exchange_rate }}"
                                                            required
                                                            class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-2.5 py-1.5 text-xs font-mono font-bold bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-sm">
                                                 </div>
                                             @endif
                                         </td>

                                         {{-- Status --}}
                                         <td class="py-3 text-center">
                                             <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold {{ $curr->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                                 {{ $curr->is_active ? __('messages.active') : __('messages.inactive') }}
                                             </span>
                                         </td>

                                         {{-- Actions --}}
                                         <td class="py-3 text-right">
                                             <div class="flex items-center justify-end gap-1">
                                                 @if(!$curr->is_base)
                                                     <button type="button"
                                                             @click="openEdit({{ $curr->id }}, '{{ $curr->code }}', '{{ addslashes($curr->name) }}', '{{ addslashes($curr->symbol) }}', {{ $curr->exchange_rate }}, {{ $curr->is_active ? 'true' : 'false' }})"
                                                             class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                                             title="{{ __('messages.edit') }}">
                                                         <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                     </button>

                                                     <button type="submit"
                                                             form="delete-curr-form-{{ $curr->id }}"
                                                             class="p-1.5 text-rose-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                                             title="{{ __('messages.delete') }}">
                                                         <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                     </button>
                                                 @endif
                                             </div>
                                         </td>
                                     </tr>
                                 @endforeach
                             </tbody>
                         </table>
                     </div>

                     {{-- Bulk Save Button --}}n --}}
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold shadow-md transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ __('messages.exchange_save_all_rates') }}</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>

        {{-- RIGHT COLUMN: REAL-TIME CONVERTER & PRICING HELPER (5 COLS) --}}
        <div class="lg:col-span-5 space-y-6">

            {{-- Card 1: Interactive Live Converter --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </span>
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 font-outfit">
                        {{ __('messages.exchange_converter_title') }}
                    </h3>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.exchange_convert_amount') }}</label>
                        <input type="number"
                               step="any"
                               x-model="convAmount"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>

                    <div class="grid grid-cols-5 gap-2 items-center">
                        <div class="col-span-2">
                            <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.exchange_from') }}</label>
                            <select x-model="convFrom" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-2.5 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                                @foreach($currencies as $c)
                                    <option value="{{ $c->code }}">{{ $c->code }} ({{ $c->symbol }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-span-1 text-center pt-5">
                            <button type="button"
                                    @click="swapCurrencies()"
                                    class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                    title="Swap">
                                ⇄
                            </button>
                        </div>

                        <div class="col-span-2">
                            <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.exchange_to') }}</label>
                            <select x-model="convTo" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-2.5 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                                @foreach($currencies as $c)
                                    <option value="{{ $c->code }}">{{ $c->code }} ({{ $c->symbol }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Converted Output Box --}}
                    <div class="p-4 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-2xl text-white shadow-md">
                        <div class="text-[11px] font-bold text-violet-200 uppercase tracking-wider">{{ __('messages.exchange_result') }}</div>
                        <div class="text-xl sm:text-2xl font-black font-mono mt-1">
                            <span x-text="Number(convertedResult).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })"></span>
                            <span class="text-xs font-normal text-violet-200 ml-1" x-text="convTo"></span>
                        </div>
                        <div class="text-[10px] text-violet-200 mt-1">
                            <span x-text="convAmount"></span> <span x-text="convFrom"></span> = <span x-text="Number(convertedResult).toLocaleString()"></span> <span x-text="convTo"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 2: Import Cost & Pricing Calculator --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </span>
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 font-outfit">
                        {{ __('messages.exchange_pricing_helper_title') }}
                    </h3>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.exchange_import_currency') }}</label>
                            <select x-model="calcCurr" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-2.5 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                                @foreach($currencies->where('is_base', false) as $c)
                                    <option value="{{ $c->code }}">{{ $c->code }} (Rate: {{ number_format($c->exchange_rate, 0) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.exchange_import_cost') }}</label>
                            <input type="number"
                                   step="any"
                                   x-model="calcCost"
                                   placeholder="e.g. 50"
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="font-bold text-slate-600 dark:text-slate-400">{{ __('messages.exchange_target_markup') }}</label>
                            <span class="font-mono font-black text-violet-600 dark:text-violet-400" x-text="calcMarkup + '%'"></span>
                        </div>
                        <input type="range"
                               min="5"
                               max="100"
                               step="1"
                               x-model="calcMarkup"
                               class="w-full accent-violet-600">
                    </div>

                    {{-- Output Grid --}}
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-2xl space-y-2">
                        <div class="flex justify-between items-center text-slate-600 dark:text-slate-400">
                            <span>{{ __('messages.exchange_landed_mmk') }}:</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-slate-100" x-text="landedCostMmk.toLocaleString() + ' Ks'"></span>
                        </div>
                        <div class="flex justify-between items-center text-slate-600 dark:text-slate-400">
                            <span>{{ __('messages.exchange_profit_margin') }}:</span>
                            <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="'+ ' + profitMarginMmk.toLocaleString() + ' Ks'"></span>
                        </div>
                        <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center">
                            <span class="font-bold text-slate-900 dark:text-slate-100">{{ __('messages.exchange_suggested_price') }}:</span>
                            <span class="font-mono text-base font-black text-emerald-600 dark:text-emerald-400" x-text="suggestedPriceMmk.toLocaleString() + ' Ks'"></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    {{-- ============================================================
         MODAL 1: ADD FOREIGN CURRENCY
         ============================================================ --}}
    <div x-show="showAddModal"
         x-transition.opacity
         class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="showAddModal = false"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-5">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-black text-slate-900 dark:text-slate-100 font-outfit">
                    {{ __('messages.exchange_add_new') }}
                </h3>
                <button type="button" @click="showAddModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form method="POST" action="{{ route('store.admin.exchange_rates.store', ['store_slug' => $store->slug]) }}" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.exchange_currency_code') }} *</label>
                    <input type="text"
                           name="code"
                           required
                           placeholder="e.g. EUR, MYR, JPY"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.exchange_currency_name') }} *</label>
                    <input type="text"
                           name="name"
                           required
                           placeholder="e.g. Euro, Malaysian Ringgit"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.exchange_symbol') }}</label>
                        <input type="text"
                               name="symbol"
                               placeholder="e.g. €, RM, ¥"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.exchange_rate_to_mmk') }} *</label>
                        <input type="number"
                               step="0.0001"
                               min="0.0001"
                               name="exchange_rate"
                               required
                               placeholder="e.g. 4800"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded text-violet-600">
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ __('messages.exchange_active_currency') }}</span>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showAddModal = false" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 font-bold text-slate-600 dark:text-slate-300">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-violet-600 text-white font-bold shadow-md">{{ __('messages.exchange_add_currency_btn') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL 2: EDIT CURRENCY DETAILS
         ============================================================ --}}
    <div x-show="editModalOpen"
         x-transition.opacity
         class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="editModalOpen = false"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-5">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-black text-slate-900 dark:text-slate-100 font-outfit">
                    {{ __('messages.exchange_edit_currency') }}: <span x-text="editingCurr.code" class="text-violet-600"></span>
                </h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form method="POST"
                  :action="'{{ url('/store/' . $store->slug . '/admin/exchange-rates') }}/' + editingCurr.id"
                  class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.exchange_currency_name') }} *</label>
                    <input type="text"
                           name="name"
                           x-model="editingCurr.name"
                           required
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.exchange_symbol') }}</label>
                        <input type="text"
                               name="symbol"
                               x-model="editingCurr.symbol"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.exchange_rate_to_mmk') }} *</label>
                        <input type="number"
                               step="0.0001"
                               min="0.0001"
                               name="exchange_rate"
                               x-model="editingCurr.exchange_rate"
                               required
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           :checked="editingCurr.is_active == 1"
                           class="w-4 h-4 rounded text-violet-600">
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ __('messages.exchange_active_currency') }}</span>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 font-bold text-slate-600 dark:text-slate-300">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-violet-600 text-white font-bold shadow-md">{{ __('messages.exchange_update_currency_btn') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- External Delete Forms to prevent HTML Form Nesting --}}
    @foreach($currencies as $c)
        @if(!$c->is_base)
            <form id="delete-curr-form-{{ $c->id }}"
                  method="POST"
                  action="{{ route('store.admin.exchange_rates.destroy', ['store_slug' => $store->slug, 'currency' => $c->id]) }}"
                  onsubmit="return confirm('{{ __('messages.exchange_confirm_delete') }}')"
                  class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endforeach

</div>
@endsection
