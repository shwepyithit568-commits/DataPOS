@php
    $pos = $setting->pos_settings ?? [];
    $paperSize = $pos['paper_size'] ?? '80mm';
    $receiptHeader = $pos['receipt_header'] ?? ($setting->store_name ?? $store->name);
    $receiptSubtitle = $pos['receipt_subtitle'] ?? ($setting->tagline ?? '');
    $receiptFooter = $pos['receipt_footer'] ?? 'ဝယ်ယူအားပေးမှုကို အထူးပင်ကျေးဇူးတင်ရှိပါသည်။ ပစ္စည်းမှန်ကန်မှုအတွက် ပြေစာနှင့်တကွ ၃ ရက်အတွင်း လာရောက်လဲလှယ်နိုင်ပါသည်။';
    $autoPrint = $pos['auto_print'] ?? true;
    $showTaxId = $pos['show_tax_id'] ?? false;
    $taxIdNumber = $pos['tax_id_number'] ?? '';
    $showCashier = $pos['show_cashier'] ?? true;
    $showCustomerInfo = $pos['show_customer_info'] ?? true;
    $showQr = $pos['show_qr'] ?? true;
    $qrType = $pos['qr_type'] ?? 'storefront';
    $customQrUrl = $pos['custom_qr_url'] ?? '';

    $autoOpenDrawer = $pos['auto_open_drawer'] ?? true;
    $requireOpeningFloat = $pos['require_opening_float'] ?? false;
    $blindClosing = $pos['blind_closing'] ?? false;
    $holdExpiryHours = old('pos_hold_expiry_hours', $setting->pos_hold_expiry_hours ?? 24);

    $allowPriceEdit = $pos['allow_price_edit'] ?? false;
    $maxItemDiscount = $pos['max_item_discount_pct'] ?? 10;
    $maxCartDiscount = $pos['max_cart_discount_pct'] ?? 15;
    $pinThreshold = old('pos_override_pin_threshold', $setting->pos_override_pin_threshold ?? '');
    $requirePinVoid = $pos['require_pin_to_void'] ?? true;
    $requirePinReturn = $pos['require_pin_for_return'] ?? true;

    $enableTax = $pos['enable_tax'] ?? false;
    $defaultTaxRate = $pos['default_tax_rate'] ?? 5;
    $taxType = $pos['tax_type'] ?? 'exclusive';
    $cashRounding = $pos['cash_rounding'] ?? 'round_50';

    $barcodeAutoAdd = $pos['barcode_auto_add'] ?? true;
    $soundFx = $pos['enable_sound_fx'] ?? true;
    $allowNegativeStock = $pos['allow_negative_stock'] ?? false;
@endphp

<div class="space-y-6"
     x-data="{
        receiptHeader: {{ json_encode($receiptHeader) }},
        receiptSubtitle: {{ json_encode($receiptSubtitle) }},
        receiptFooter: {{ json_encode($receiptFooter) }},
        paperSize: '{{ $paperSize }}',
        showCashier: {{ $showCashier ? 'true' : 'false' }},
        showCustomer: {{ $showCustomerInfo ? 'true' : 'false' }},
        showTax: {{ $showTaxId ? 'true' : 'false' }},
        taxId: {{ json_encode($taxIdNumber) }},
        showQr: {{ $showQr ? 'true' : 'false' }},
        enableTax: {{ $enableTax ? 'true' : 'false' }}
     }">

    {{-- Section Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 grid place-items-center text-xl font-bold shrink-0">
                🛒
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>{{ __('messages.settings_pos') }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 font-bold border border-blue-200/60 dark:border-blue-800">Advanced Counter</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Cashier terminal behavior, thermal receipts, discounts, security PINs, tax & cash drawer controls.</p>
            </div>
        </div>
    </div>

    {{-- Main Grid: Left Settings Forms & Right Thermal Receipt Live Preview --}}
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        
        {{-- Left Form Sections (7 Cols) --}}
        <div class="xl:col-span-7 space-y-6">

            {{-- 1. Receipt & Thermal Printing Setup --}}
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>🧾</span>
                        <span>Thermal Voucher & Receipt Template</span>
                    </h3>
                    <span class="text-[10px] font-bold text-slate-400">Print Customizer</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="{{ $labelClass }}">Paper Size (ပရင်တာ စက္ကူဆိုဒ်) *</label>
                        <select name="pos_settings[paper_size]" x-model="paperSize" class="{{ $inputClass }}">
                            <option value="80mm">80mm (Standard POS Thermal)</option>
                            <option value="58mm">58mm (Compact Mobile Bluetooth)</option>
                            <option value="a4">A4 / A5 (Full Page Invoice)</option>
                        </select>
                        <p class="{{ $helpClass }}">Choose width for thermal printer cut.</p>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Receipt Header Title (ဘောက်ချာခေါင်းစဉ်)</label>
                        <input type="text" name="pos_settings[receipt_header]" x-model="receiptHeader"
                               placeholder="e.g. {{ $store->name }}" class="{{ $inputClass }}">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">Receipt Subtitle / Slogan (ဆောင်ပုဒ် / ဆိုင်ခွဲ)</label>
                        <input type="text" name="pos_settings[receipt_subtitle]" x-model="receiptSubtitle"
                               placeholder="e.g. Mobile Phones, Accessories & Repair" class="{{ $inputClass }}">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">Voucher Footer Note (ကျေးဇူးတင်စကားနှင့် စည်းကမ်းချက်)</label>
                        <textarea name="pos_settings[receipt_footer]" x-model="receiptFooter" rows="2"
                                  class="{{ $inputClass }}"></textarea>
                    </div>
                </div>

                {{-- Thermal Receipt Toggles --}}
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-blue-300 transition">
                        <input type="checkbox" name="pos_settings[auto_print]" value="1" {{ $autoPrint ? 'checked' : '' }}
                               class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Auto Print on Checkout</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-blue-300 transition">
                        <input type="checkbox" name="pos_settings[show_cashier]" value="1" x-model="showCashier"
                               class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Show Cashier Name</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-blue-300 transition">
                        <input type="checkbox" name="pos_settings[show_customer_info]" value="1" x-model="showCustomer"
                               class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Show Customer & Points</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-blue-300 transition">
                        <input type="checkbox" name="pos_settings[show_qr]" value="1" x-model="showQr"
                               class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Print Store QR Code</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-blue-300 transition">
                        <input type="checkbox" name="pos_settings[show_tax_id]" value="1" x-model="showTax"
                               class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Show Commercial Tax / TIN</span>
                    </label>
                </div>

                <div x-show="showTax" class="pt-2">
                    <label class="{{ $labelClass }}">Tax Identification Number (TIN / အခွန်အမှတ်အသား)</label>
                    <input type="text" name="pos_settings[tax_id_number]" x-model="taxId"
                           placeholder="e.g. TIN-1029384756" class="{{ $inputClass }}">
                </div>
            </div>

            {{-- 2. Cash Register & Shift Handover Controls --}}
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>💵</span>
                        <span>Cash Register & Shift Controls</span>
                    </h3>
                    <span class="text-[10px] font-bold text-slate-400">Vault & Float</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label for="pos_hold_expiry_hours" class="{{ $labelClass }}">Held Sale Auto-Expiry (hours)</label>
                        <input id="pos_hold_expiry_hours" type="number" name="pos_hold_expiry_hours" min="0" max="720" step="1"
                               value="{{ $holdExpiryHours }}"
                               placeholder="24" class="{{ $inputClass }}" />
                        <p class="{{ $helpClass }}">
                            Hold လုပ်ထားသော အရောင်းများ အလိုအလျောက် သက်တမ်းကုန်ချိန် (0 = ပိတ်ထားမည်၊ 24 = default)။
                        </p>
                        @error('pos_hold_expiry_hours')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2 pt-1">
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-blue-300 transition">
                            <input type="checkbox" name="pos_settings[auto_open_drawer]" value="1" {{ $autoOpenDrawer ? 'checked' : '' }}
                                   class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Auto Open Drawer on Cash Sale</span>
                        </label>

                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-blue-300 transition">
                            <input type="checkbox" name="pos_settings[require_opening_float]" value="1" {{ $requireOpeningFloat ? 'checked' : '' }}
                                   class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Require Shift Opening Cash Float</span>
                        </label>

                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-blue-300 transition">
                            <input type="checkbox" name="pos_settings[blind_closing]" value="1" {{ $blindClosing ? 'checked' : '' }}
                                   class="rounded text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-200 block">Blind Shift Closing</span>
                                <span class="text-[10px] text-slate-400 block">Hide expected balance from cashier during shift count</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- 3. Pricing, Discounts & Security Manager PIN --}}
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>🔒</span>
                        <span>Pricing, Discounts & Security PIN</span>
                    </h3>
                    <span class="text-[10px] font-bold text-slate-400">Authorization</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label for="pos_override_pin_threshold" class="{{ $labelClass }}">Price Override Manager PIN Threshold (%)</label>
                        <input id="pos_override_pin_threshold" type="number" name="pos_override_pin_threshold" min="0" max="100" step="1"
                               value="{{ $pinThreshold }}"
                               placeholder="Disabled (No PIN required)" class="{{ $inputClass }}" />
                        <p class="{{ $helpClass }}">
                            Cashier က ဈေးနှုန်းကို သတ်မှတ်ထားထက် ဤ % ပိုလျှော့ပါက **Manager PIN** တောင်းပါမည်။
                        </p>
                        @error('pos_override_pin_threshold')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Max Cashier Item Discount (%)</label>
                        <input type="number" name="pos_settings[max_item_discount_pct]" min="0" max="100"
                               value="{{ $maxItemDiscount }}" class="{{ $inputClass }}" />
                        <p class="{{ $helpClass }}">Maximum item discount % allowed without manager PIN.</p>
                    </div>

                    <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-blue-300 transition">
                            <input type="checkbox" name="pos_settings[allow_price_edit]" value="1" {{ $allowPriceEdit ? 'checked' : '' }}
                                   class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Allow Counter Price Edit</span>
                        </label>

                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-blue-300 transition">
                            <input type="checkbox" name="pos_settings[require_pin_to_void]" value="1" {{ $requirePinVoid ? 'checked' : '' }}
                                   class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Require PIN to Void Invoice</span>
                        </label>

                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-blue-300 transition">
                            <input type="checkbox" name="pos_settings[require_pin_for_return]" value="1" {{ $requirePinReturn ? 'checked' : '' }}
                                   class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Require PIN for Returns</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- 4. Tax, Rounding & Barcode Scanner Speed --}}
            <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>⚖️</span>
                        <span>Tax, Cash Rounding & Scanner Speed</span>
                    </h3>
                    <span class="text-[10px] font-bold text-slate-400">Rules & Hardware</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="{{ $labelClass }}">Cash Rounding (မြန်မာကျပ်ငွေ အကြွေစေ့ ချိန်ညှိမှု)</label>
                        <select name="pos_settings[cash_rounding]" class="{{ $inputClass }}">
                            <option value="none" {{ $cashRounding === 'none' ? 'selected' : '' }}>No Rounding (Exact)</option>
                            <option value="round_10" {{ $cashRounding === 'round_10' ? 'selected' : '' }}>Round to nearest 10 Kyats</option>
                            <option value="round_50" {{ $cashRounding === 'round_50' ? 'selected' : '' }}>Round to nearest 50 Kyats (Recommended)</option>
                            <option value="round_100" {{ $cashRounding === 'round_100' ? 'selected' : '' }}>Round to nearest 100 Kyats</option>
                        </select>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Default Commercial Tax Rate (%)</label>
                        <input type="number" step="0.1" name="pos_settings[default_tax_rate]" value="{{ $defaultTaxRate }}"
                               placeholder="5.0" class="{{ $inputClass }}" />
                    </div>

                    <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-blue-300 transition">
                            <input type="checkbox" name="pos_settings[enable_tax]" value="1" x-model="enableTax"
                                   class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Enable Commercial Tax</span>
                        </label>

                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-blue-300 transition">
                            <input type="checkbox" name="pos_settings[barcode_auto_add]" value="1" {{ $barcodeAutoAdd ? 'checked' : '' }}
                                   class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Auto +1 on Barcode Scan</span>
                        </label>

                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/70 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:border-blue-300 transition">
                            <input type="checkbox" name="pos_settings[enable_sound_fx]" value="1" {{ $soundFx ? 'checked' : '' }}
                                   class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Scanner & Success Audio FX</span>
                        </label>
                    </div>
                </div>
            </div>

        </div>

        {{-- Right Side: Live Thermal Voucher Preview (5 Cols) --}}
        <div class="xl:col-span-5 sticky top-4 space-y-3">
            <div class="p-4 sm:p-5 rounded-2xl bg-slate-900 border border-slate-800 text-white shadow-xl space-y-3">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                        <span>🖨️</span>
                        <span>Live Thermal Voucher Preview</span>
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-blue-950 text-blue-300 border border-blue-800 uppercase" x-text="paperSize"></span>
                </div>

                {{-- Realistic Paper Receipt Box --}}
                <div class="bg-white text-slate-900 rounded-xl p-4 shadow-inner font-mono text-[11px] leading-relaxed mx-auto transition-all"
                     :class="paperSize === '58mm' ? 'max-w-[240px]' : (paperSize === 'a4' ? 'max-w-full' : 'max-w-[310px]')">
                    
                    {{-- Header --}}
                    <div class="text-center space-y-0.5 pb-2 border-b border-dashed border-slate-300">
                        <h4 class="font-black text-sm uppercase tracking-tight" x-text="receiptHeader || 'STORE NAME'"></h4>
                        <p class="text-[10px] text-slate-500" x-text="receiptSubtitle"></p>
                        <p class="text-[10px] text-slate-500 font-sans">{{ $store->setting->address ?? 'Shop Location & Branches' }}</p>
                        <p class="text-[10px] text-slate-500">Tel: {{ $store->setting->phone ?? '09xxxxxxxxx' }}</p>
                        
                        <template x-if="showTax && taxId">
                            <p class="text-[9px] font-bold text-slate-600" x-text="`Tax ID: ${taxId}`"></p>
                        </template>
                    </div>

                    {{-- Invoice Metadata --}}
                    <div class="py-2 border-b border-dashed border-slate-300 text-[10px] space-y-0.5">
                        <div class="flex justify-between">
                            <span>VOUCHER:</span>
                            <span class="font-bold">INV-20260828-0042</span>
                        </div>
                        <div class="flex justify-between">
                            <span>DATE:</span>
                            <span>{{ now()->format('d/m/Y h:i A') }}</span>
                        </div>
                        <template x-if="showCashier">
                            <div class="flex justify-between">
                                <span>CASHIER:</span>
                                <span>Ma Su Mon</span>
                            </div>
                        </template>
                        <template x-if="showCustomer">
                            <div class="flex justify-between text-blue-700">
                                <span>CUSTOMER:</span>
                                <span>U Kyaw Kyaw (VIP)</span>
                            </div>
                        </template>
                    </div>

                    {{-- Sample Items Table --}}
                    <div class="py-2 border-b border-dashed border-slate-300 space-y-1.5">
                        <div class="flex justify-between font-bold text-[10px] border-b border-slate-200 pb-0.5">
                            <span>ITEM</span>
                            <span>QTY x PRICE</span>
                            <span>AMT</span>
                        </div>
                        <div class="space-y-1 text-[10px]">
                            <div>
                                <span class="font-bold block truncate">iPhone 15 Case Pro Shock</span>
                                <div class="flex justify-between text-slate-600 pl-1">
                                    <span>2 x 15,000</span>
                                    <span>30,000</span>
                                </div>
                            </div>
                            <div>
                                <span class="font-bold block truncate">20W Fast Charger Adapter</span>
                                <div class="flex justify-between text-slate-600 pl-1">
                                    <span>1 x 25,000</span>
                                    <span>25,000</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Totals --}}
                    <div class="py-2 border-b border-dashed border-slate-300 text-[10px] space-y-1">
                        <div class="flex justify-between">
                            <span>SUBTOTAL:</span>
                            <span>55,000 Ks</span>
                        </div>
                        <div class="flex justify-between text-emerald-700">
                            <span>DISCOUNT (5%):</span>
                            <span>-2,750 Ks</span>
                        </div>
                        <template x-if="enableTax">
                            <div class="flex justify-between text-slate-600">
                                <span>TAX (5%):</span>
                                <span>+2,612 Ks</span>
                            </div>
                        </template>
                        <div class="flex justify-between font-black text-xs pt-1 border-t border-slate-200">
                            <span>NET TOTAL:</span>
                            <span>54,860 Ks</span>
                        </div>
                        <div class="flex justify-between text-slate-600 text-[10px] pt-0.5">
                            <span>PAID (CASH):</span>
                            <span>60,000 Ks</span>
                        </div>
                        <div class="flex justify-between text-slate-600 text-[10px]">
                            <span>CHANGE:</span>
                            <span>5,140 Ks</span>
                        </div>
                    </div>

                    {{-- QR Code & Footer --}}
                    <div class="text-center pt-3 space-y-2">
                        <template x-if="showQr">
                            <div class="w-16 h-16 bg-slate-100 border border-slate-300 mx-auto rounded grid place-items-center text-xs font-bold">
                                <span>📱 QR</span>
                            </div>
                        </template>
                        <p class="text-[9px] text-slate-500 font-sans leading-tight" x-text="receiptFooter"></p>
                        <p class="text-[8px] text-slate-400 font-mono tracking-widest pt-1">*** POWERED BY DATAPOS ***</p>
                    </div>
                </div>

                <p class="text-[10px] text-slate-400 text-center">
                    💡 This preview updates in real-time as you customize the receipt header, footer, tax, and paper size.
                </p>
            </div>
        </div>

    </div>
</div>
