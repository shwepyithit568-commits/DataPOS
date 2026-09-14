{{-- Reusable Price Adjustment Review Modal for Purchase Orders (Create & Edit) --}}
{{-- Follows Admin UI/UX Standard v4.1 (Ultra-dense rhythm, Tri-lingual, Zero hardcoded currency) --}}

<div x-show="priceModalOpen"
     x-cloak
     @keydown.escape.window="if (priceModalOpen && !submitting) priceModalOpen = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="price-modal-title"
     role="dialog"
     aria-modal="true">

    {{-- Backdrop --}}
    <div x-show="priceModalOpen"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
         @click="if (!submitting) priceModalOpen = false"></div>

    {{-- Dialog Box --}}
    <div class="flex min-h-full items-center justify-center p-2 sm:p-4 text-center">
        <div x-show="priceModalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.outside="if (!submitting) priceModalOpen = false"
             class="relative w-full max-w-4xl transform rounded-lg bg-white dark:bg-slate-900 text-left shadow-2xl transition-all border border-slate-200 dark:border-slate-800 flex flex-col max-h-[90vh]">

            {{-- Modal Header --}}
            <div class="p-2.5 sm:p-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2 shrink-0 bg-slate-50/50 dark:bg-slate-800/30">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-8 h-8 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-base font-black shrink-0 border border-amber-200/80 dark:border-amber-900/50">
                        ⚖️
                    </div>
                    <div class="min-w-0">
                        <h2 id="price-modal-title" class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate">
                            {{ __('messages.po_price_adjustment_title') }}
                        </h2>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">
                            {{ __('messages.po_price_adjustment_desc') }}
                        </p>
                    </div>
                </div>

                {{-- Select All / Clear All & Close --}}
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="button"
                            @click="selectAllPriceUpdates(true)"
                            class="h-6 px-2 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer">
                        {{ __('messages.po_select_all') }}
                    </button>
                    <button type="button"
                            @click="selectAllPriceUpdates(false)"
                            class="h-6 px-2 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer">
                        {{ __('messages.po_clear_all') }}
                    </button>
                    <button type="button"
                            :disabled="submitting"
                            @click="priceModalOpen = false"
                            class="w-6 h-6 rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm font-bold grid place-items-center cursor-pointer transition">
                        &times;
                    </button>
                </div>
            </div>

            {{-- Modal Body: List of Changed Items --}}
            <div class="p-2 sm:p-3 overflow-y-auto flex-1 space-y-2">
                {{-- Desktop Table View --}}
                <div class="hidden sm:block border border-slate-200/90 dark:border-slate-800 rounded overflow-hidden">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-100 dark:bg-slate-800/90 text-slate-700 dark:text-slate-300 font-black uppercase text-[10px]">
                            <tr class="divide-x divide-slate-200 dark:divide-slate-700">
                                <th class="py-1.5 px-2 text-center w-8">
                                    <input type="checkbox"
                                           :checked="areAllSelected()"
                                           @change="selectAllPriceUpdates($event.target.checked)"
                                           class="rounded border-slate-300 dark:border-slate-700 text-sky-600 focus:ring-sky-500 cursor-pointer">
                                </th>
                                <th class="py-1.5 px-2.5 min-w-[160px]">{{ __('messages.products') }}</th>
                                <th class="py-1.5 px-2 text-right min-w-[130px]">{{ __('messages.po_cost_baseline') }} → {{ __('messages.po_new_unit_cost') }}</th>
                                <th class="py-1.5 px-2 text-right min-w-[140px]">{{ __('messages.po_current_retail') }} → {{ __('messages.po_new_retail') }}</th>
                                <th class="py-1.5 px-2 text-right min-w-[140px]">{{ __('messages.po_current_wholesale') }} → {{ __('messages.po_new_wholesale') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-mono">
                            <template x-for="(item, idx) in changedItems" :key="item.key">
                                <tr class="divide-x divide-slate-200/60 dark:divide-slate-800 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    {{-- Checkbox --}}
                                    <td class="py-1.5 px-2 text-center">
                                        <input type="checkbox"
                                               x-model="item.update_prices"
                                               class="rounded border-slate-300 dark:border-slate-700 text-sky-600 focus:ring-sky-500 cursor-pointer">
                                    </td>

                                    {{-- Product Name & SKU --}}
                                    <td class="py-1.5 px-2.5 font-sans">
                                        <span class="block font-bold text-xs text-slate-900 dark:text-slate-100 truncate max-w-[200px]" x-text="item.name"></span>
                                        <span class="block text-[10px] text-slate-400 font-mono" x-text="item.sku"></span>
                                    </td>

                                    {{-- Cost Change Comparison --}}
                                    <td class="py-1.5 px-2 text-right">
                                        <div class="text-[11px] text-slate-400 line-through tabular-nums" x-text="fmt(item.baseline_cost)"></div>
                                        <div class="text-xs font-bold text-slate-900 dark:text-slate-100 tabular-nums flex items-center justify-end gap-1">
                                            <span x-text="fmt(item.new_unit_cost)"></span>
                                            <span class="px-1 py-0.2 rounded text-[9px] font-bold inline-block"
                                                  :class="item.cost_diff_pct >= 0 ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400'"
                                                  x-text="(item.cost_diff_pct >= 0 ? '+' : '') + item.cost_diff_pct + '%'">
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Retail Price Adjustment Input --}}
                                    <td class="py-1.5 px-2 text-right font-sans">
                                        <div class="flex items-center justify-between text-[10px] text-slate-400 mb-0.5">
                                            <span>Current: <b class="font-mono text-slate-600 dark:text-slate-300" x-text="fmt(item.expected_retail_price)"></b></span>
                                            <span class="text-sky-600 dark:text-sky-400 cursor-pointer hover:underline"
                                                  @click="item.retail_price = item.suggested_retail"
                                                  title="Use Suggested Price">
                                                Sugg: <span class="font-mono" x-text="fmt(item.suggested_retail)"></span>
                                            </span>
                                        </div>
                                        <input type="number"
                                               inputmode="decimal"
                                               min="0"
                                               step="any"
                                               x-model="item.retail_price"
                                               :disabled="!item.update_prices"
                                               class="w-full h-6 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-1.5 text-right font-mono font-bold text-xs text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-sky-500 disabled:opacity-40 disabled:cursor-not-allowed">
                                    </td>

                                    {{-- Wholesale Price Adjustment Input --}}
                                    <td class="py-1.5 px-2 text-right font-sans">
                                        <div class="flex items-center justify-between text-[10px] text-slate-400 mb-0.5">
                                            <span>Current: <b class="font-mono text-slate-600 dark:text-slate-300" x-text="fmt(item.expected_wholesale_price)"></b></span>
                                            <span class="text-sky-600 dark:text-sky-400 cursor-pointer hover:underline"
                                                  @click="item.wholesale_price = item.suggested_wholesale"
                                                  title="Use Suggested Price">
                                                Sugg: <span class="font-mono" x-text="fmt(item.suggested_wholesale)"></span>
                                            </span>
                                        </div>
                                        <input type="number"
                                               inputmode="decimal"
                                               min="0"
                                               step="any"
                                               x-model="item.wholesale_price"
                                               :disabled="!item.update_prices"
                                               class="w-full h-6 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-1.5 text-right font-mono font-bold text-xs text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-sky-500 disabled:opacity-40 disabled:cursor-not-allowed">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Card View --}}
                <div class="sm:hidden space-y-2">
                    <template x-for="(item, idx) in changedItems" :key="item.key">
                        <div class="p-2.5 rounded border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 space-y-2 text-xs shadow-2xs">
                            <div class="flex items-start justify-between gap-2">
                                <label class="flex items-center gap-2 cursor-pointer min-w-0">
                                    <input type="checkbox"
                                           x-model="item.update_prices"
                                           class="rounded border-slate-300 dark:border-slate-700 text-sky-600 focus:ring-sky-500 cursor-pointer shrink-0">
                                    <div class="min-w-0">
                                        <span class="block font-bold text-slate-900 dark:text-slate-100 truncate" x-text="item.name"></span>
                                        <span class="block text-[10px] text-slate-400 font-mono" x-text="item.sku"></span>
                                    </div>
                                </label>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold font-mono shrink-0"
                                      :class="item.cost_diff_pct >= 0 ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400'"
                                      x-text="(item.cost_diff_pct >= 0 ? '+' : '') + item.cost_diff_pct + '%'">
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-1.5 pt-1.5 border-t border-slate-100 dark:border-slate-800 text-[11px]">
                                <div>
                                    <span class="text-[10px] text-slate-400 block">{{ __('messages.po_cost_baseline') }}</span>
                                    <span class="font-mono text-slate-500 line-through" x-text="fmt(item.baseline_cost)"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-400 block">{{ __('messages.po_new_unit_cost') }}</span>
                                    <span class="font-mono font-bold text-slate-900 dark:text-slate-100" x-text="fmt(item.new_unit_cost)"></span>
                                </div>
                            </div>

                            {{-- Mobile Retail & Wholesale Inputs --}}
                            <div class="space-y-1.5 pt-1.5 border-t border-slate-100 dark:border-slate-800">
                                <div>
                                    <div class="flex items-center justify-between text-[10px] text-slate-500 mb-0.5">
                                        <span>{{ __('messages.po_new_retail') }} (Curr: <span class="font-mono" x-text="fmt(item.expected_retail_price)"></span>)</span>
                                        <button type="button" @click="item.retail_price = item.suggested_retail" class="text-sky-600 font-bold">
                                            Sugg: <span class="font-mono" x-text="fmt(item.suggested_retail)"></span>
                                        </button>
                                    </div>
                                    <input type="number"
                                           inputmode="decimal"
                                           x-model="item.retail_price"
                                           :disabled="!item.update_prices"
                                           class="w-full h-7 rounded border border-slate-300 dark:border-slate-700 px-2 text-right font-mono font-bold text-xs bg-slate-50 dark:bg-slate-800 outline-none">
                                </div>

                                <div>
                                    <div class="flex items-center justify-between text-[10px] text-slate-500 mb-0.5">
                                        <span>{{ __('messages.po_new_wholesale') }} (Curr: <span class="font-mono" x-text="fmt(item.expected_wholesale_price)"></span>)</span>
                                        <button type="button" @click="item.wholesale_price = item.suggested_wholesale" class="text-sky-600 font-bold">
                                            Sugg: <span class="font-mono" x-text="fmt(item.suggested_wholesale)"></span>
                                        </button>
                                    </div>
                                    <input type="number"
                                           inputmode="decimal"
                                           x-model="item.wholesale_price"
                                           :disabled="!item.update_prices"
                                           class="w-full h-7 rounded border border-slate-300 dark:border-slate-700 px-2 text-right font-mono font-bold text-xs bg-slate-50 dark:bg-slate-800 outline-none">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Hidden Inputs Container inside the main Form --}}
            {{-- Generated dynamically by submitWithPriceUpdates() before form submission --}}

            {{-- Modal Footer Actions --}}
            <div class="p-2.5 sm:p-3 border-t border-slate-200/90 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2 shrink-0 bg-slate-50/50 dark:bg-slate-800/30">
                <button type="button"
                        :disabled="submitting"
                        @click="priceModalOpen = false"
                        class="h-7 px-3 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer">
                    {{ __('messages.cancel') }}
                </button>

                <div class="flex items-center gap-1.5 flex-wrap justify-end">
                    {{-- Keep Current Prices & Save --}}
                    <button type="button"
                            :disabled="submitting"
                            @click="submitKeepCurrentPrices($refs.poForm)"
                            class="h-7 px-3 rounded text-xs font-bold bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-100 transition cursor-pointer inline-flex items-center gap-1">
                        <span>🛡️</span>
                        <span>{{ __('messages.po_keep_current_prices_and_save') }}</span>
                    </button>

                    {{-- Apply Selected Prices & Save --}}
                    <button type="button"
                            :disabled="submitting"
                            @click="submitApplySelectedPrices($refs.poForm)"
                            class="h-7 px-4 rounded text-xs font-black text-white bg-sky-600 hover:bg-sky-500 shadow-2xs hover:shadow-sky-500/20 transition active:scale-95 disabled:opacity-50 cursor-pointer inline-flex items-center gap-1">
                        <svg x-show="submitting" x-cloak class="animate-spin -ml-0.5 mr-1 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span>✅</span>
                        <span>{{ __('messages.po_apply_prices_and_save') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
