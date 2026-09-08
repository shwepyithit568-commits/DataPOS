@php
    $curr = $setting->currency_settings ?? [];
    $currCode = $curr['currency_code'] ?? 'MMK';
    $currName = $curr['currency_name'] ?? 'Myanmar Kyat';
    $currSymbol = $curr['currency_symbol'] ?? 'Ks';
    $symbolPosition = $curr['symbol_position'] ?? 'after_space';
    $decimalPlaces = isset($curr['decimal_places']) ? (int) $curr['decimal_places'] : 0;
    $decimalSep = $curr['decimal_separator'] ?? '.';
    $thousandSep = $curr['thousand_separator'] ?? ',';
    $negativeFormat = $curr['negative_format'] ?? 'minus';
    $showSymbol = $curr['show_symbol'] ?? true;
    $qtyDecimals = $curr['qty_decimal_places'] ?? 'auto';
    $qtyTrimZeros = isset($curr['qty_trim_zeros']) ? (bool) $curr['qty_trim_zeros'] : true;
@endphp

<div class="space-y-6"
     x-data="{
        code: {{ json_encode($currCode) }},
        name: {{ json_encode($currName) }},
        symbol: {{ json_encode($currSymbol) }},
        position: {{ json_encode($symbolPosition) }},
        decimals: {{ $decimalPlaces }},
        decSep: {{ json_encode($decimalSep) }},
        thSep: {{ json_encode($thousandSep) }},
        negFormat: {{ json_encode($negativeFormat) }},
        showSymbol: {{ $showSymbol ? 'true' : 'false' }},
        qtyDecimals: {{ json_encode($qtyDecimals) }},
        qtyTrimZeros: {{ $qtyTrimZeros ? 'true' : 'false' }},

        formatNumber(val) {
            const isNeg = val < 0;
            const abs = Math.abs(val);
            const fixed = abs.toFixed(this.decimals);
            const parts = fixed.split('.');
            
            let tSep = ',';
            if (this.thSep === 'dot' || this.thSep === '.') tSep = '.';
            else if (this.thSep === 'space' || this.thSep === ' ') tSep = ' ';
            else if (this.thSep === 'none' || this.thSep === '') tSep = '';

            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, tSep);
            
            let formatted = parts.join(this.decSep);
            let s = this.showSymbol ? (this.symbol || '') : '';
            
            let withSym = formatted;
            if (this.position === 'after_space') withSym = s ? formatted + ' ' + s : formatted;
            else if (this.position === 'after_tight') withSym = formatted + s;
            else if (this.position === 'before_space') withSym = s ? s + ' ' + formatted : formatted;
            else if (this.position === 'before_tight') withSym = s + formatted;

            if (!isNeg) return withSym;
            if (this.negFormat === 'parentheses') return '(' + withSym + ')';
            if (this.negFormat === 'dr_cr') return withSym + ' (DR)';
            return '-' + withSym;
        },

        formatQuantity(val) {
            let tSep = ',';
            if (this.thSep === 'dot' || this.thSep === '.') tSep = '.';
            else if (this.thSep === 'space' || this.thSep === ' ') tSep = ' ';
            else if (this.thSep === 'none' || this.thSep === '') tSep = '';

            const isInt = Number.isInteger(val);
            if (this.qtyDecimals === 'auto') {
                if (isInt) {
                    return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, tSep);
                }
                let fixed = val.toFixed(3);
                if (this.qtyTrimZeros) {
                    fixed = parseFloat(fixed).toString();
                }
                let parts = fixed.split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, tSep);
                return parts.join(this.decSep);
            }

            const d = parseInt(this.qtyDecimals, 10);
            let fixed = val.toFixed(d);
            let parts = fixed.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, tSep);
            let formatted = parts.join(this.decSep);
            if (this.qtyTrimZeros && d > 0 && formatted.includes(this.decSep)) {
                let split = formatted.split(this.decSep);
                let dec = split[1].replace(/0+$/, '');
                return dec ? split[0] + this.decSep + dec : split[0];
            }
            return formatted;
        }
     }">

    {{-- Section Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xl font-bold shrink-0">
                💱
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>{{ __('messages.settings_currency_title') }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-200/60 dark:border-emerald-800">{{ __('messages.settings_currency_systemwide') }}</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.settings_currency_desc') }}</p>
            </div>
        </div>
    </div>

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        
        {{-- Left Form Sections (7 Cols) --}}
        <div class="xl:col-span-7 space-y-6">

            {{-- 1. Currency Identity & Symbol --}}
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>💵</span>
                        <span>{{ __('messages.settings_currency_primary_symbol') }}</span>
                    </h3>
                    <span class="text-[10px] font-bold text-slate-400">{{ __('messages.settings_currency_base') }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('messages.settings_currency_code') }}</label>
                        <input type="text" name="currency_settings[currency_code]" x-model="code"
                               placeholder="MMK" class="{{ $inputClass }} uppercase font-mono font-bold" />
                        <p class="{{ $helpClass }}">e.g. MMK, USD, THB, SGD</p>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">{{ __('messages.settings_currency_name') }}</label>
                        <input type="text" name="currency_settings[currency_name]" x-model="name"
                               placeholder="Myanmar Kyat" class="{{ $inputClass }}" />
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">{{ __('messages.settings_currency_symbol') }}</label>
                        <input type="text" name="currency_settings[currency_symbol]" x-model="symbol"
                               placeholder="Ks" class="{{ $inputClass }} font-bold" />
                        <p class="{{ $helpClass }}">e.g. Ks, K, $, ฿, ¥, ကျပ်</p>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-emerald-300 transition">
                        <input type="checkbox" name="currency_settings[show_symbol]" value="1" x-model="showSymbol"
                               class="rounded text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 block">{{ __('messages.settings_currency_show_symbol') }}</span>
                            <span class="text-[10px] text-slate-400 block">{{ __('messages.settings_currency_show_symbol_help') }}</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- 2. Symbol Placement & Positioning --}}
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>🏷️</span>
                        <span>{{ __('messages.settings_currency_position') }}</span>
                    </h3>
                    <span class="text-[10px] font-bold text-slate-400">{{ __('messages.settings_currency_position_style') }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="position === 'after_space' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[symbol_position]" value="after_space" x-model="position"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-black text-slate-900 dark:text-white block">{{ __('messages.settings_currency_after_space') }}</span>
                            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 block mt-0.5">100,000 Ks</span>
                            <span class="text-[10px] text-slate-400 block">{{ __('messages.settings_currency_recommended_mmk') }}</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="position === 'before_space' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[symbol_position]" value="before_space" x-model="position"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-black text-slate-900 dark:text-white block">{{ __('messages.settings_currency_before_space') }}</span>
                            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 block mt-0.5">Ks 100,000</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="position === 'before_tight' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[symbol_position]" value="before_tight" x-model="position"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-black text-slate-900 dark:text-white block">{{ __('messages.settings_currency_before_tight') }}</span>
                            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 block mt-0.5">$100,000 / Ks100,000</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="position === 'after_tight' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[symbol_position]" value="after_tight" x-model="position"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-black text-slate-900 dark:text-white block">{{ __('messages.settings_currency_after_tight') }}</span>
                            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 block mt-0.5">100,000Ks</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- 3. Decimals & Separators --}}
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>🔢</span>
                        <span>{{ __('messages.settings_currency_decimals_title') }}</span>
                    </h3>
                    <span class="text-[10px] font-bold text-slate-400">{{ __('messages.settings_currency_format_rules') }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('messages.settings_currency_decimal_places') }}</label>
                        <select name="currency_settings[decimal_places]" x-model.number="decimals" class="{{ $inputClass }}">
                            <option value="0">0 (100,000) — MMK Default</option>
                            <option value="2">2 (100,000.00) — Standard</option>
                            <option value="3">3 (100,000.000)</option>
                            <option value="4">4 (100,000.0000)</option>
                        </select>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">{{ __('messages.settings_currency_thousand_sep') }}</label>
                        <select name="currency_settings[thousand_separator]" x-model="thSep" class="{{ $inputClass }}">
                            <option value=",">{{ __('messages.comma') }} (,) 100,000</option>
                            <option value=".">{{ __('messages.dot') }} (.) 100.000</option>
                            <option value="space">{{ __('messages.space') }} ( ) 100 000</option>
                            <option value="none">{{ __('messages.none') }} 100000</option>
                        </select>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">{{ __('messages.settings_currency_decimal_sep') }}</label>
                        <select name="currency_settings[decimal_separator]" x-model="decSep" class="{{ $inputClass }}">
                            <option value=".">{{ __('messages.dot') }} (.) 100.00</option>
                            <option value=",">{{ __('messages.comma') }} (,) 100,00</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- 4. Negative / Accounting Ledger Format --}}
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>📉</span>
                        <span>{{ __('messages.settings_currency_negative') }}</span>
                    </h3>
                    <span class="text-[10px] font-bold text-slate-400">{{ __('messages.settings_currency_accounting_style') }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition"
                           :class="negFormat === 'minus' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[negative_format]" value="minus" x-model="negFormat"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-bold text-slate-900 dark:text-white block">{{ __('messages.settings_currency_minus') }}</span>
                            <span class="text-xs font-mono font-bold text-rose-600 dark:text-rose-400 block mt-0.5" x-text="formatNumber(-50000)"></span>
                        </div>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition"
                           :class="negFormat === 'parentheses' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[negative_format]" value="parentheses" x-model="negFormat"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-bold text-slate-900 dark:text-white block">{{ __('messages.settings_currency_parentheses') }}</span>
                            <span class="text-xs font-mono font-bold text-rose-600 dark:text-rose-400 block mt-0.5" x-text="formatNumber(-50000)"></span>
                        </div>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition"
                           :class="negFormat === 'dr_cr' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[negative_format]" value="dr_cr" x-model="negFormat"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-bold text-slate-900 dark:text-white block">{{ __('messages.settings_currency_dr_cr') }}</span>
                            <span class="text-xs font-mono font-bold text-rose-600 dark:text-rose-400 block mt-0.5" x-text="formatNumber(-50000)"></span>
                        </div>
                    </label>
                </div>
            {{-- 5. Stock Quantity & Decimal Precision --}}
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>📦</span>
                        <span>{{ __('messages.settings_currency_stock_qty') }}</span>
                    </h3>
                    <span class="text-[10px] font-bold text-slate-400">{{ __('messages.settings_currency_inventory_standard') }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="qtyDecimals === 'auto' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[qty_decimal_places]" value="auto" x-model="qtyDecimals"
                               class="text-emerald-600 focus:ring-emerald-500 mt-0.5">
                        <div>
                            <span class="text-xs font-black text-slate-900 dark:text-white block">{{ __('messages.settings_currency_qty_auto') }}</span>
                            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 block mt-0.5">10, 25, 1.5, 2.25</span>
                            <span class="text-[10px] text-slate-400 block">(Recommended — .000 မပါဘဲ သန့်ရှင်းစွာ ပြသခြင်း)</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="qtyDecimals === '0' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[qty_decimal_places]" value="0" x-model="qtyDecimals"
                               class="text-emerald-600 focus:ring-emerald-500 mt-0.5">
                        <div>
                            <span class="text-xs font-black text-slate-900 dark:text-white block">{{ __('messages.settings_currency_qty_integers') }}</span>
                            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 block mt-0.5">10, 25, 100</span>
                            <span class="text-[10px] text-slate-400 block">(Mobile, Computer, Fashion & Electronics)</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="qtyDecimals === '2' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[qty_decimal_places]" value="2" x-model="qtyDecimals"
                               class="text-emerald-600 focus:ring-emerald-500 mt-0.5">
                        <div>
                            <span class="text-xs font-black text-slate-900 dark:text-white block">{{ __('messages.settings_currency_qty_2dec') }}</span>
                            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 block mt-0.5">10.00, 1.50</span>
                            <span class="text-[10px] text-slate-400 block">(Meter, Yards, Standard Weighed Items)</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="qtyDecimals === '3' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[qty_decimal_places]" value="3" x-model="qtyDecimals"
                               class="text-emerald-600 focus:ring-emerald-500 mt-0.5">
                        <div>
                            <span class="text-xs font-black text-slate-900 dark:text-white block">{{ __('messages.settings_currency_qty_3dec') }}</span>
                            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 block mt-0.5">10.000, 1.500</span>
                            <span class="text-[10px] text-slate-400 block">(Kilograms, Precision Weighing, Fuel)</span>
                        </div>
                    </label>
                </div>

                <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-emerald-300 transition">
                        <input type="checkbox" name="currency_settings[qty_trim_zeros]" value="1" x-model="qtyTrimZeros"
                               class="rounded text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 block">{{ __('messages.settings_currency_qty_trim') }}</span>
                            <span class="text-[10px] text-slate-400 block">စတော့အရေအတွက် ကိန်းပြည့်ဖြစ်နေပါက `.000` မပါဘဲ ဥပမာ- `10` အဖြစ် သန့်ရှင်းစွာ ပြသပေးပါမည်။</span>
                        </div>
                    </label>
                </div>
            </div>

        </div>

        {{-- Right Side: Live Visual Representation Box (5 Cols) --}}
        <div class="xl:col-span-5 sticky top-4 space-y-4">
            <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 text-white shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                        <span>📊</span>
                        <span>{{ __('messages.settings_currency_live_preview') }}</span>
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-300 border border-emerald-800 uppercase font-bold" x-text="code"></span>
                </div>

                {{-- Sample Currency Breakdown Display Cards --}}
                <div class="space-y-2.5">
                    <div class="p-2.5 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-between">
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold">{{ __('messages.settings_currency_retail_price') }}</p>
                            <p class="text-[10px] text-slate-500">{{ __('messages.settings_currency_single_item') }}</p>
                        </div>
                        <span class="text-sm font-black font-mono text-emerald-400" x-text="formatNumber(45000)"></span>
                    </div>

                    <div class="p-2.5 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-between">
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold">{{ __('messages.settings_currency_monthly_revenue') }}</p>
                            <p class="text-[10px] text-slate-500">{{ __('messages.settings_currency_large_volume') }}</p>
                        </div>
                        <span class="text-sm font-black font-mono text-white" x-text="formatNumber(12500000)"></span>
                    </div>

                    <div class="p-2.5 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-between">
                        <div>
                            <p class="text-[11px] text-rose-300 font-bold">{{ __('messages.settings_currency_refund') }}</p>
                            <p class="text-[10px] text-slate-500">{{ __('messages.settings_currency_negative_entry') }}</p>
                        </div>
                        <span class="text-sm font-black font-mono text-rose-400" x-text="formatNumber(-250000)"></span>
                    </div>
                </div>

                {{-- Live Stock Quantity Preview Card --}}
                <div class="p-3 rounded-xl bg-slate-800/90 border border-sky-800/60 space-y-2.5">
                    <div class="flex items-center justify-between border-b border-slate-700/80 pb-1.5">
                        <span class="text-[11px] font-black uppercase text-sky-400 flex items-center gap-1.5">
                            <span>📦</span>
                            <span>{{ __('messages.settings_currency_stock_display') }}</span>
                        </span>
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-sky-950 text-sky-300 font-bold" x-text="qtyDecimals === 'auto' ? @js(__('messages.settings_currency_auto_clean')) : qtyDecimals + ' ' + @js(__('messages.settings_currency_decimals_suffix'))"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2 rounded-lg bg-slate-900/80 border border-slate-800">
                            <p class="text-[10px] text-slate-400 font-bold">{{ __('messages.settings_currency_on_hand') }}</p>
                            <span class="text-sm font-black font-mono text-white" x-text="formatQuantity(30)"></span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-900/80 border border-slate-800">
                            <p class="text-[10px] text-slate-400 font-bold">{{ __('messages.settings_currency_fractional') }}</p>
                            <span class="text-sm font-black font-mono text-sky-400" x-text="formatQuantity(1.5)"></span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-900/80 border border-slate-800">
                            <p class="text-[10px] text-slate-400 font-bold">{{ __('messages.settings_currency_inbound') }}</p>
                            <span class="text-sm font-black font-mono text-emerald-400" x-text="'+' + formatQuantity(5)"></span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-900/80 border border-slate-800">
                            <p class="text-[10px] text-slate-400 font-bold">{{ __('messages.settings_currency_total_warehouse') }}</p>
                            <span class="text-sm font-black font-mono text-amber-300" x-text="formatQuantity(12500)"></span>
                        </div>
                    </div>
                </div>

                {{-- Mini POS Checkout Card Representation --}}
                <div class="p-3.5 rounded-xl bg-white text-slate-900 space-y-2 text-xs shadow-inner">
                    <div class="flex items-center justify-between font-bold border-b border-slate-200 pb-1.5">
                        <span>{{ __('messages.settings_currency_pos_voucher') }}</span>
                        <span class="text-[10px] font-mono text-slate-500">INV-8492</span>
                    </div>
                    <div class="flex justify-between text-slate-600 text-[11px]">
                        <span>{{ __('messages.settings_currency_subtotal') }} <span class="font-mono font-bold text-slate-900" x-text="formatQuantity(2)"></span>):</span>
                        <span class="font-mono font-bold text-slate-900" x-text="formatNumber(120000)"></span>
                    </div>
                    <div class="flex justify-between text-emerald-700 text-[11px]">
                        <span>{{ __('messages.settings_currency_discount') }}</span>
                        <span class="font-mono font-bold" x-text="formatNumber(-6000)"></span>
                    </div>
                    <div class="flex justify-between font-black text-sm pt-2 border-t border-slate-300">
                        <span>{{ __('messages.settings_currency_net_payable') }}</span>
                        <span class="font-mono text-emerald-600" x-text="formatNumber(114000)"></span>
                    </div>
                </div>

                <p class="text-[11px] text-slate-400 text-center">
                    {{ __('messages.settings_currency_realtime_hint') }}
                </p>
            </div>
        </div>

    </div>
</div>
