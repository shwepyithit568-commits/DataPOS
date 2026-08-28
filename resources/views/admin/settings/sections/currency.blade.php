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
                    <span>Currency & Accounting Number Format</span>
                    <span class="text-xs px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-200/60 dark:border-emerald-800">System-wide</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Manage currency symbols, symbol placement, thousand separators, decimals & accounting ledger presentation.</p>
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
                        <span>Primary Currency & Symbol</span>
                    </h3>
                    <span class="text-[10px] font-bold text-slate-400">Base Currency</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <label class="{{ $labelClass }}">Currency Code (ကုဒ်)</label>
                        <input type="text" name="currency_settings[currency_code]" x-model="code"
                               placeholder="MMK" class="{{ $inputClass }} uppercase font-mono font-bold" />
                        <p class="{{ $helpClass }}">e.g. MMK, USD, THB, SGD</p>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Currency Name (ငွေကြေးအမည်)</label>
                        <input type="text" name="currency_settings[currency_name]" x-model="name"
                               placeholder="Myanmar Kyat" class="{{ $inputClass }}" />
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Currency Symbol (သင်္ကေတ)</label>
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
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 block">Display Currency Symbol across all surfaces</span>
                            <span class="text-[10px] text-slate-400 block">Shows symbol next to prices in POS cashier, catalog, reports & receipts</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- 2. Symbol Placement & Positioning --}}
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>🏷️</span>
                        <span>Symbol Placement & Spacing (သင်္ကေတ နေရာ)</span>
                    </h3>
                    <span class="text-[10px] font-bold text-slate-400">Position Style</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="position === 'after_space' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[symbol_position]" value="after_space" x-model="position"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-black text-slate-900 dark:text-white block">After Amount with Space</span>
                            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 block mt-0.5">100,000 Ks</span>
                            <span class="text-[10px] text-slate-400 block">(Recommended for Myanmar Kyats)</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="position === 'before_space' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[symbol_position]" value="before_space" x-model="position"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-black text-slate-900 dark:text-white block">Before Amount with Space</span>
                            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 block mt-0.5">Ks 100,000</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="position === 'before_tight' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[symbol_position]" value="before_tight" x-model="position"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-black text-slate-900 dark:text-white block">Before Amount Tight</span>
                            <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 block mt-0.5">$100,000 / Ks100,000</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                           :class="position === 'after_tight' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[symbol_position]" value="after_tight" x-model="position"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-black text-slate-900 dark:text-white block">After Amount Tight</span>
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
                        <span>Decimals & Thousand Separator (ဒသမနှင့် ထောင်ဂဏန်း)</span>
                    </h3>
                    <span class="text-[10px] font-bold text-slate-400">Format Rules</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <label class="{{ $labelClass }}">Decimal Places (ဒသမနေရာ)</label>
                        <select name="currency_settings[decimal_places]" x-model.number="decimals" class="{{ $inputClass }}">
                            <option value="0">0 (100,000) — MMK Default</option>
                            <option value="2">2 (100,000.00) — Standard</option>
                            <option value="3">3 (100,000.000)</option>
                            <option value="4">4 (100,000.0000)</option>
                        </select>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Thousand Separator (ထောင်ဂဏန်း)</label>
                        <select name="currency_settings[thousand_separator]" x-model="thSep" class="{{ $inputClass }}">
                            <option value=",">Comma (,) 100,000</option>
                            <option value=".">Dot (.) 100.000</option>
                            <option value="space">Space ( ) 100 000</option>
                            <option value="none">None 100000</option>
                        </select>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Decimal Separator (ဒသမ ခွဲခြား)</label>
                        <select name="currency_settings[decimal_separator]" x-model="decSep" class="{{ $inputClass }}">
                            <option value=".">Dot (.) 100.00</option>
                            <option value=",">Comma (,) 100,00</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- 4. Negative / Accounting Ledger Format --}}
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>📉</span>
                        <span>Negative & Accounting Format (အနုတ်ပမာဏ ပြသမှု)</span>
                    </h3>
                    <span class="text-[10px] font-bold text-slate-400">Accounting Style</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition"
                           :class="negFormat === 'minus' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[negative_format]" value="minus" x-model="negFormat"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-bold text-slate-900 dark:text-white block">Minus Sign</span>
                            <span class="text-xs font-mono font-bold text-rose-600 dark:text-rose-400 block mt-0.5" x-text="formatNumber(-50000)"></span>
                        </div>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition"
                           :class="negFormat === 'parentheses' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[negative_format]" value="parentheses" x-model="negFormat"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-bold text-slate-900 dark:text-white block">Parentheses (Accounting)</span>
                            <span class="text-xs font-mono font-bold text-rose-600 dark:text-rose-400 block mt-0.5" x-text="formatNumber(-50000)"></span>
                        </div>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition"
                           :class="negFormat === 'dr_cr' ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                        <input type="radio" name="currency_settings[negative_format]" value="dr_cr" x-model="negFormat"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-bold text-slate-900 dark:text-white block">Debit / Credit (DR)</span>
                            <span class="text-xs font-mono font-bold text-rose-600 dark:text-rose-400 block mt-0.5" x-text="formatNumber(-50000)"></span>
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
                        <span>Live Accounting Format Preview</span>
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-300 border border-emerald-800 uppercase font-bold" x-text="code"></span>
                </div>

                {{-- Sample Breakdown Display Cards --}}
                <div class="space-y-3">
                    <div class="p-3 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-between">
                        <div>
                            <p class="text-[11px] text-slate-400">Regular Retail Price</p>
                            <p class="text-[10px] text-slate-500">Single Item Sale</p>
                        </div>
                        <span class="text-sm font-black font-mono text-emerald-400" x-text="formatNumber(45000)"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-between">
                        <div>
                            <p class="text-[11px] text-slate-400">Large Sales Volume</p>
                            <p class="text-[10px] text-slate-500">Monthly Revenue</p>
                        </div>
                        <span class="text-base font-black font-mono text-white" x-text="formatNumber(12500000)"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-between">
                        <div>
                            <p class="text-[11px] text-slate-400">Unit Price with Precision</p>
                            <p class="text-[10px] text-slate-500">Fuel, Agri, Weighing items</p>
                        </div>
                        <span class="text-sm font-black font-mono text-sky-400" x-text="formatNumber(18.75)"></span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-between">
                        <div>
                            <p class="text-[11px] text-rose-300">Customer Refund / Expense</p>
                            <p class="text-[10px] text-slate-500">Negative ledger entry</p>
                        </div>
                        <span class="text-sm font-black font-mono text-rose-400" x-text="formatNumber(-250000)"></span>
                    </div>
                </div>

                {{-- Mini POS Checkout Card Representation --}}
                <div class="p-4 rounded-xl bg-white text-slate-900 space-y-2 text-xs shadow-inner">
                    <div class="flex items-center justify-between font-bold border-b border-slate-200 pb-2">
                        <span>POS Checkout Voucher</span>
                        <span class="text-[10px] font-mono text-slate-500">INV-8492</span>
                    </div>
                    <div class="flex justify-between text-slate-600 text-[11px]">
                        <span>Subtotal:</span>
                        <span class="font-mono font-bold text-slate-900" x-text="formatNumber(120000)"></span>
                    </div>
                    <div class="flex justify-between text-emerald-700 text-[11px]">
                        <span>Discount (5%):</span>
                        <span class="font-mono font-bold" x-text="formatNumber(-6000)"></span>
                    </div>
                    <div class="flex justify-between text-slate-600 text-[11px]">
                        <span>Tax (5%):</span>
                        <span class="font-mono font-bold text-slate-900" x-text="formatNumber(5700)"></span>
                    </div>
                    <div class="flex justify-between font-black text-sm pt-2 border-t border-slate-300">
                        <span>NET PAYABLE:</span>
                        <span class="font-mono text-emerald-600" x-text="formatNumber(119700)"></span>
                    </div>
                </div>

                <p class="text-[11px] text-slate-400 text-center">
                    💡 Real-time format updates immediately as you customize symbols, thousand separators, decimals and negative accounting rules.
                </p>
            </div>
        </div>

    </div>
</div>
