{{-- Shared product form body. Requires Alpine state on create/edit wrappers. --}}
@php
    $currencySettings = $store?->setting?->currency_settings ?? [];
    $currencySymbol = !empty($currencySettings['currency_symbol']) ? $currencySettings['currency_symbol'] : 'Ks';
    $input = 'w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-1.5 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition';
    $inputCurrency = 'w-full rounded-r-lg border border-slate-200 dark:border-slate-700 px-3 py-1.5 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition';
    $btn3dPlus = 'w-auto px-2 sm:px-0 sm:w-11 self-stretch shrink-0 inline-flex flex-col sm:flex-row items-center justify-center gap-0.5 min-h-[44px] sm:min-h-0 rounded-lg bg-gradient-to-b from-sky-400 via-sky-500 to-blue-600 hover:from-sky-500 hover:to-blue-700 active:from-sky-600 active:to-blue-800 text-white font-black border border-sky-300/80 border-b-[3px] border-b-blue-800 hover:brightness-105 active:translate-y-[1.5px] active:border-b active:shadow-none shadow-[0_2px_5px_rgba(2,132,199,0.35),inset_0_1px_0_rgba(255,255,255,0.45)] transition-all cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:pointer-events-none disabled:active:translate-y-0 disabled:border-b-[3px]';
    $label = 'block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1';
    $hint  = 'mt-0.5 text-[11px] text-slate-400 dark:text-slate-500';
    $section = 'w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs space-y-2';
    $fileInput = 'block w-full text-xs text-slate-600 dark:text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-violet-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-slate-800 dark:file:text-violet-300 rounded-lg border border-slate-200 dark:border-slate-700 p-1 bg-slate-50 dark:bg-slate-800/60';



    $shelfList = isset($masterPresets) && $masterPresets->where('type', 'shelf_location')->isNotEmpty()
        ? $masterPresets->where('type', 'shelf_location')->values()
        : collect([
            (object)['code' => 'A-01', 'name' => 'Shelf A1 (ရှေ့စင် အပေါ်ထပ်)'],
            (object)['code' => 'A-02', 'name' => 'Shelf A2 (ရှေ့စင် အလယ်ထပ်)'],
            (object)['code' => 'B-01', 'name' => 'Shelf B1 (ဘေးစင် အပေါ်ထပ်)'],
            (object)['code' => 'B-02', 'name' => 'Shelf B2 (ဘေးစင် အောက်ထပ်)'],
            (object)['code' => 'CTR-01', 'name' => 'Counter Glass (ကောင်တာ မှန်ပုံး)'],
            (object)['code' => 'CAB-01', 'name' => 'Back Cabinet (နောက်ဘက် ဗီရို)'],
            (object)['code' => 'RP-01', 'name' => 'Repair Bench (ပြင်ဆင်ရေး စင်)'],
            (object)['code' => 'WH-RACK', 'name' => 'Warehouse Rack (ဂိုဒေါင် စင်)'],
        ]);

    $warrantyList = isset($masterPresets) && $masterPresets->where('type', 'warranty')->isNotEmpty()
        ? $masterPresets->where('type', 'warranty')->values()
        : collect([
            (object)['name' => '7 Days Testing Warranty', 'content' => '၇ ရက်အတွင်း စက်ချို့ယွင်းချက်ရှိပါက အသစ်လဲပေးသည်'],
            (object)['name' => '1 Month Service Warranty', 'content' => '၁ လအတွင်း လက်ခအခမဲ့ ပြုပြင်ပေးသည်'],
            (object)['name' => '3 Months Warranty', 'content' => '၃ လ အာမခံ'],
            (object)['name' => '6 Months Warranty', 'content' => '၆ လ အာမခံ'],
            (object)['name' => '1 Year Official Warranty', 'content' => '၁ နှစ် တရားဝင် အာမခံ'],
            (object)['name' => 'No Warranty', 'content' => 'အာမခံမပါပါ'],
        ]);

    $returnPolicyList = isset($masterPresets) && $masterPresets->where('type', 'return_policy')->isNotEmpty()
        ? $masterPresets->where('type', 'return_policy')->values()
        : collect([
            (object)['name' => '7 Days Exchange', 'content' => 'ပစ္စည်းဘူးခွံ၊ ဘားကုဒ်နှင့် ဆက်စပ်ပစ္စည်းများ အကောင်းပကတိအတိုင်း ရှိပါက ဝယ်ယူပြီး ၇ ရက်အတွင်း တန်ဖိုးတူ အခြားပစ္စည်းနှင့် လဲလှယ်နိုင်ပါသည်။'],
            (object)['name' => 'Screen & Touch LCD Policy', 'content' => 'Touch LCD နှင့် မှန်ချပ်များကို ဖုန်းတွင် ကော်မကပ်မီ အပြင်မှ ကြိုးထိုး စမ်းသပ်ပေးရပါမည်။ တံဆိပ်တုံးပျက်စီးခြင်း၊ ကော်ကပ်ပြီးခြင်း၊ ဖလင်ခွာပြီးပါက ပြန်လဲမပေးပါ။'],
            (object)['name' => 'Accessories Return Policy', 'content' => 'ကြိုး၊ အားသွင်းခေါင်း စသည့် Accessories များ ပျက်စီးချို့ယွင်းပါက ဝယ်ယူသည့် ဘောက်ချာပြသ၍ အသစ်လဲလှယ်နိုင်ပါသည်။'],
        ]);
@endphp

<div class="space-y-1 sm:space-y-1.5">
    {{-- Product Type on the counter path is awareness-only: staff see which type
         they are creating and carry on. The real 6-way selector lives in
         "More Details" (see #product-type-selector), so an internal architecture
         question is never the first thing a cashier has to answer. --}}
    <div class="w-full rounded-lg border border-violet-200/80 dark:border-violet-800/60 bg-violet-50/50 dark:bg-slate-900 px-2.5 py-1.5 flex items-center justify-between gap-2 shadow-2xs">
        <div class="flex items-center gap-2 min-w-0">
            <span class="w-6 h-6 shrink-0 rounded-md bg-violet-600 text-white grid place-items-center text-xs font-bold shadow-xs">⚡</span>
            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 shrink-0">{{ __('messages.product_form_product_type') }}</span>
            <span class="text-xs font-black text-violet-700 dark:text-violet-300 truncate" x-text="productTypeLabel"></span>
        </div>
        <button type="button" @click="openProductTypeSelector()" data-test-label="Change Product Type"
                class="shrink-0 h-7 px-2.5 rounded-md text-[11px] font-black text-violet-700 dark:text-violet-300 bg-white dark:bg-slate-800 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition cursor-pointer">
            {{ __('messages.product_form_type_change') }}
        </button>
        <input type="hidden" name="product_type" :value="productType" />
    </div>

    {{-- Main 2-Column Responsive Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-1.5 sm:gap-2 items-start">

        {{-- LEFT COLUMN (lg:col-span-8): Primary Product & POS Data --}}
        <div class="lg:col-span-8 space-y-1.5 sm:space-y-2">

            {{-- 1. Core Information Card --}}
            <section class="{{ $section }}">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2 mb-1">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center text-xs font-bold">
                            📦
                        </span>
                        <div>
                            <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                                {{ __('messages.product_form_core_section') }}
                            </h2>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="hidden sm:inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-800/70">
                            📷 Barcode Scanner Ready
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:gap-2.5 md:grid-cols-2">
                    {{-- Product Name (Full Width) --}}
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">
                            <span x-text="productType === 'service' ? 'ဝန်ဆောင်မှု အမည်' : (productType === 'digital' ? 'ဒစ်ဂျစ်တယ် ပစ္စည်း အမည်' : '{{ __('messages.product_form_name') }}')"></span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" x-model="productNameInput" @input="nameTouched = true" required class="{{ $input }}"
                            :placeholder="productType === 'service' ? 'ဥပမာ - မှန်ကပ်ပေးခြင်း / iOS Update တင်ပေးခြင်း' : (productType === 'digital' ? 'ဥပမာ - iTunes $10 Gift Card / MLBB 500 Diamonds' : (productType === 'serialized' ? 'ဥပမာ - iPhone 15 Pro Max 256GB / Dell XPS 15' : (productType === 'weight_based' ? 'ဥပမာ - ကော်ကြိုး / ခဲကြိုး / အပူခံပိုက်' : '{{ __('messages.product_form_name_placeholder') }}')))" />
                        @error('name')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Barcode input (scanner gun friendly + phone camera scan) --}}
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('messages.product_form_barcode') }}</label>
                            <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400" x-show="productType === 'serialized'" x-cloak>📱 IMEI / Serial</span>
                        </div>
                        <div class="flex items-stretch gap-1.5">
                            <div class="relative flex-1 min-w-0">
                                <input type="text" name="barcode" x-model="productBarcode" value="{{ old('barcode', $product->barcode) }}"
                                    @keydown.enter.prevent="if($event.target.value.trim()){ document.querySelector('input[name=retail_price]')?.focus(); }"
                                    @input="scheduleCodeCheck('barcode')" @blur="checkCode('barcode')"
                                    class="{{ $input }} pl-8 font-mono" placeholder="{{ __('messages.product_form_barcode_placeholder') }}" />
                                <span class="absolute left-2.5 top-2 text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                </span>
                            </div>
                            {{-- Phone camera scan: opens the rear camera, decodes the photo locally. --}}
                            <button type="button" @click="$refs.barcodeScanInput.click()" :disabled="scanningBarcode"
                                title="{{ __('messages.product_form_barcode_scan') }}" aria-label="{{ __('messages.product_form_barcode_scan') }}"
                                class="shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-violet-300 dark:border-violet-700 bg-violet-50 dark:bg-violet-950/40 px-2.5 min-h-[40px] sm:min-h-0 sm:py-1.5 text-xs font-black text-violet-700 dark:text-violet-300 hover:bg-violet-100 dark:hover:bg-violet-900/40 disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer">
                                <span x-show="!scanningBarcode">📷</span>
                                <span x-show="scanningBarcode" x-cloak>⏳</span>
                                <span class="hidden sm:inline">{{ __('messages.product_form_barcode_scan') }}</span>
                            </button>
                            <input type="file" accept="image/*" capture="environment" x-ref="barcodeScanInput" class="hidden" @change="scanBarcodeFromCamera($event)" />
                        </div>
                        <p class="{{ $hint }}">📷 {{ __('messages.product_form_barcode_howto') }}</p>
                        <p x-show="barcodeScanError" x-cloak class="mt-1 text-[11px] font-semibold text-amber-600 dark:text-amber-400" x-text="barcodeScanError"></p>
                        <p x-show="scanningBarcode" x-cloak class="mt-1 text-[11px] font-semibold text-violet-600 dark:text-violet-400">{{ __('messages.product_form_scanning') }}</p>
                        <p x-show="codeChecks.barcode" x-cloak class="mt-1 text-[11px] font-bold text-amber-600 dark:text-amber-400" x-text="'{{ __('messages.product_form_code_taken') }}' + codeChecks.barcode"></p>
                        <p x-show="codeChecks.barcode === false" x-cloak class="mt-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">✓ {{ __('messages.product_form_code_free') }}</p>
                        @error('barcode')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- SKU Code — optional: blank means the system generates a unique code --}}
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">
                                {{ __('messages.product_form_sku') }}
                            </label>
                            <span x-show="autoSku" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400">⚡ {{ __('messages.product_stock_auto_managed') }}</span>
                        </div>
                        <input type="text" name="sku" x-model="productSku" :readonly="autoSku" @input="scheduleCodeCheck('sku')" @blur="checkCode('sku')" class="{{ $input }} font-mono" :class="autoSku ? 'cursor-not-allowed bg-slate-100 dark:bg-slate-800 text-indigo-700 dark:text-indigo-300 font-bold' : ''" placeholder="{{ __('messages.product_form_sku_placeholder') }}" />
                        <p class="{{ $hint }}">{{ __('messages.product_form_sku_optional_hint') }}</p>
                        <p x-show="codeChecks.sku" x-cloak class="mt-1 text-[11px] font-bold text-amber-600 dark:text-amber-400" x-text="'{{ __('messages.product_form_code_taken') }}' + codeChecks.sku"></p>
                        <p x-show="codeChecks.sku === false" x-cloak class="mt-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">✓ {{ __('messages.product_form_code_free') }}</p>
                        @error('sku')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Brand Selection --}}
                    <div>
                        <label class="{{ $label }}">{{ __('messages.product_form_brand') }}</label>
                        <div class="flex items-stretch gap-1.5">
                            <select name="brand_id" x-model="selectedBrand" @change="recomputeSmartSkuAndName()" x-init="$nextTick(() => $el.value = selectedBrand)" class="{{ $input }} flex-1 min-w-0 cursor-pointer">
                                <option value="">{{ __('messages.product_form_none') }}</option>
                                <template x-for="b in brands" :key="b.id">
                                    <option :value="b.id" x-text="b.name + (b.code ? ' [' + b.code + ']' : '')"></option>
                                </template>
                            </select>
                            <button type="button" @click="brandModalOpen = true" title="{{ __('messages.product_form_quick_brand') }}" aria-label="{{ __('messages.product_form_quick_brand') }}" class="{{ $btn3dPlus }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            
                                <span class="text-[9px] sm:hidden font-black leading-none">{{ __('messages.quick_brand_short') }}</span>
                            </button>
                        </div>
                        @error('brand_id')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Main Category --}}
                    <div>
                        <label class="{{ $label }}">{{ __('messages.product_form_main_category') }}</label>
                        <div class="flex items-stretch gap-1.5">
                            <select x-model="selectedMainCategory" @change="onMainCategoryChange(); recomputeSmartSkuAndName()" x-init="$nextTick(() => $el.value = selectedMainCategory)" class="{{ $input }} flex-1 min-w-0 cursor-pointer">
                                <option value="">{{ __('messages.product_form_main_category_none') }}</option>
                                <template x-for="cat in mainCategories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name + (cat.code ? ' [' + cat.code + ']' : '')"></option>
                                </template>
                            </select>
                            <button type="button" @click="newCategoryParent = ''; categoryModalOpen = true" title="{{ __('messages.product_form_quick_category') }}" aria-label="{{ __('messages.product_form_quick_category') }}" class="{{ $btn3dPlus }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            
                                <span class="text-[9px] sm:hidden font-black leading-none">{{ __('messages.quick_category_short') }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- Sub Category --}}
                    <div>
                        <label class="{{ $label }}">{{ __('messages.product_form_sub_category') }}</label>
                        <div class="flex items-stretch gap-1.5">
                            <select x-model="selectedSubCategory" @change="recomputeSmartSkuAndName()" :disabled="!selectedMainCategory" x-init="$nextTick(() => $el.value = selectedSubCategory)" class="{{ $input }} flex-1 min-w-0 cursor-pointer disabled:cursor-not-allowed disabled:opacity-60">
                                <option value="" x-text="!selectedMainCategory ? '{{ __('messages.product_form_sub_category_choose_first') }}' : (subCategories.length ? '{{ __('messages.product_form_sub_category_none') }}' : '{{ __('messages.product_form_no_sub_categories') }}')"></option>
                                <template x-for="cat in subCategories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name + (cat.code ? ' [' + cat.code + ']' : '')"></option>
                                </template>
                            </select>
                            <button type="button" @click="newCategoryParent = selectedMainCategory; categoryModalOpen = true" :disabled="!selectedMainCategory" title="{{ __('messages.product_form_quick_category') }}" aria-label="{{ __('messages.product_form_quick_category') }}" class="{{ $btn3dPlus }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            
                                <span class="text-[9px] sm:hidden font-black leading-none">{{ __('messages.quick_category_short') }}</span>
                            </button>
                        </div>
                        <input type="hidden" name="category_id" :value="selectedSubCategory || selectedMainCategory" />
                    </div>

                    {{-- Compatible Models (Auto-fills into name when autoSku is enabled) --}}
                    <div :class="autoSku ? 'md:col-span-1' : 'md:col-span-1'">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('messages.product_form_compatible_models') }}</label>
                            <span class="text-[10px] text-indigo-600 dark:text-indigo-400" x-show="autoSku">Auto Name</span>
                        </div>
                        <input type="text" x-model="productCompatibleModels" @input="recomputeSmartSkuAndName()" class="{{ $input }}" placeholder="{{ __('messages.product_form_compatible_models_placeholder') }}" />
                        @error('compatible_models')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                        <input type="hidden" name="compatible_models" :value="productCompatibleModels" />
                    </div>
                </div>
            </section>

            {{-- 2. Pricing, Cost & Profit Margin Card --}}
            <section class="{{ $section }}">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2 mb-1">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xs font-bold">
                            🏷️
                        </span>
                        <div>
                            <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                                {{ __('messages.product_form_pricing_section') }}
                            </h2>
                        </div>
                    </div>
                    <div class="rounded-md px-2 py-0.5 text-xs font-black border"
                         :class="marginPercent >= 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'">
                        {{ __('messages.product_form_margin') }}: <span x-text="marginPercent + '%'">0%</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:gap-2.5 md:grid-cols-3">
                    {{-- Retail Price --}}
                    <div>
                        <label class="{{ $label }}">
                            <span x-text="productType === 'service' ? '{{ __('messages.product_form_service_price_label') }}' : (productType === 'weight_based' ? '{{ __('messages.product_form_weight_price_label') }}' : '{{ __('messages.product_form_retail_price') }}')"></span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative flex rounded-lg">
                            <span class="inline-flex items-center px-2.5 rounded-l-lg border border-r-0 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300 select-none">
                                {{ $currencySymbol }}
                            </span>
                            <input type="number" step="0.01" min="0" name="retail_price" x-model="marginRetail" value="{{ old('retail_price', $product->retail_price) }}" required class="{{ $inputCurrency }}" placeholder="1990000" />
                        </div>
                        @error('retail_price')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Wholesale Price — optional: blank is saved as the retail price.
                         Hidden from staff without `products.view_cost`. --}}
                    @if (store_can('products.view_cost', $store))
                    <div>
                        <label class="{{ $label }}">
                            <span x-show="productType === 'service'" x-cloak>{{ __('messages.product_form_wholesale_service_label') }}</span>
                            <span x-show="productType === 'weight_based'" x-cloak>{{ __('messages.product_form_wholesale_weight_label') }}</span>
                            <span x-show="productType !== 'service' && productType !== 'weight_based'">{{ __('messages.product_form_wholesale_price') }}</span>
                        </label>
                        <div class="relative flex rounded-lg">
                            <span class="inline-flex items-center px-2.5 rounded-l-lg border border-r-0 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300 select-none">
                                {{ $currencySymbol }}
                            </span>
                            <input type="number" step="0.01" min="0" name="wholesale_price" x-model="marginWhole" value="{{ old('wholesale_price', $product->wholesale_price) }}" class="{{ $inputCurrency }}" placeholder="{{ __('messages.product_form_wholesale_placeholder') }}" />
                        </div>
                        <p class="{{ $hint }}">{{ __('messages.product_form_wholesale_optional_hint') }}</p>
                        @error('wholesale_price')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    {{-- Purchase Cost (Directly alongside pricing for immediate profit calculation) --}}
                    @if (store_can('products.view_cost', $store))
                    <div>
                        <label class="{{ $label }}">{{ __('messages.product_form_purchase_cost') }}</label>
                        <div class="relative flex rounded-lg">
                            <span class="inline-flex items-center px-2.5 rounded-l-lg border border-r-0 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300 select-none">
                                {{ $currencySymbol }}
                            </span>
                            <input type="number" step="0.01" min="0" name="purchase_cost" value="{{ old('purchase_cost', $product->purchase_cost) }}" class="{{ $inputCurrency }}" placeholder="1500000" />
                        </div>
                        @error('purchase_cost')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    @unless (store_can('products.view_cost', $store))
                        <div class="md:col-span-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 p-2 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                            🔒 {{ __('messages.product_form_cost_locked') }}
                        </div>
                    @endunless

                    {{-- Comparison Price (Old Price) --}}
                    <div>
                        <label class="{{ $label }}">{{ __('messages.product_form_old_price') }}</label>
                        <div class="relative flex rounded-lg">
                            <span class="inline-flex items-center px-2.5 rounded-l-lg border border-r-0 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300 select-none">
                                {{ $currencySymbol }}
                            </span>
                            <input type="number" step="0.01" min="0" name="old_price" value="{{ old('old_price', $product->old_price) }}" class="{{ $inputCurrency }}" placeholder="{{ __('messages.product_form_old_price_placeholder') }}" />
                        </div>
                        @error('old_price')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Sale Starts At --}}
                    <div>
                        <label class="{{ $label }}">{{ __('messages.product_form_sale_starts_at') }}</label>
                        <input type="datetime-local" name="sale_starts_at" value="{{ old('sale_starts_at', optional($product->sale_starts_at)->format('Y-m-d\TH:i')) }}" class="{{ $input }}" />
                        @error('sale_starts_at')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Sale Ends At --}}
                    <div>
                        <label class="{{ $label }}">{{ __('messages.product_form_sale_ends_at') }}</label>
                        <input type="datetime-local" name="sale_ends_at" value="{{ old('sale_ends_at', optional($product->sale_ends_at)->format('Y-m-d\TH:i')) }}" class="{{ $input }}" />
                        @error('sale_ends_at')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Commercial Tax Settings --}}
                    <div class="md:col-span-3 pt-2 border-t border-slate-100 dark:border-slate-800"
                         x-data="{ isTaxable: {{ old('is_taxable', $product->is_taxable ?? true) ? 'true' : 'false' }} }">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="hidden" name="is_taxable" value="0">
                                    <input type="checkbox" name="is_taxable" value="1" x-model="isTaxable"
                                           class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300 dark:border-slate-700 dark:bg-slate-800 transition">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        {{ __('messages.product_form_taxable_label') }}
                                    </span>
                                </label>
                                <p class="{{ $hint }}">
                                    <span x-show="isTaxable" x-cloak>{{ __('messages.product_form_taxable_hint') }}</span>
                                    <span x-show="!isTaxable" x-cloak class="text-amber-600 dark:text-amber-400 font-bold">⚠️ {{ __('messages.product_form_tax_exempt_hint') }}</span>
                                </p>
                            </div>

                            <div x-show="isTaxable" x-cloak class="w-full sm:w-56">
                                <div class="relative">
                                    <input type="number" step="0.1" min="0" max="100" name="tax_rate"
                                           value="{{ old('tax_rate', $product->tax_rate) }}"
                                           class="{{ $input }} pr-7"
                                           placeholder="{{ $store->setting?->getPosSetting('default_tax_rate', 5) }}%" />
                                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 2.5 Service / Digital / Weight Details Card --}}
            <section x-show="productType === 'service' || productType === 'digital' || productType === 'weight_based'" x-cloak class="{{ $section }}">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2 mb-1">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 grid place-items-center text-xs font-bold">
                            <span x-text="productType === 'service' ? '🛠️' : (productType === 'digital' ? '💻' : '⚖️')"></span>
                        </span>
                        <div>
                            <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white"
                                x-text="productType === 'service' ? '{{ __('messages.product_type_service') }} အသေးစိတ်' : (productType === 'digital' ? '{{ __('messages.product_type_digital') }} အသေးစိတ်' : '{{ __('messages.product_type_weight_based') }} အသေးစိတ်')">
                            </h2>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:gap-2.5 md:grid-cols-2">
                    <div x-show="productType === 'service'" x-cloak>
                        <label class="{{ $label }}">{{ __('messages.product_form_service_duration') }}</label>
                        <input type="text" name="service_duration" value="{{ old('service_duration', $product->service_duration) }}" maxlength="100" class="{{ $input }}" placeholder="{{ __('messages.product_form_service_duration_placeholder') }}" />
                        @error('service_duration')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div x-show="productType === 'digital'" x-cloak>
                        <label class="{{ $label }}">{{ __('messages.product_form_digital_delivery_method') }}</label>
                        <select name="digital_delivery_method" class="{{ $input }} cursor-pointer">
                            <option value="">{{ __('messages.product_form_digital_delivery_method_none') }}</option>
                            @foreach (['SMS', 'Email', 'Viber / Telegram', 'In-store Pickup', 'Physical Card'] as $method)
                                <option value="{{ $method }}" @selected(old('digital_delivery_method', $product->digital_delivery_method) === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                        @error('digital_delivery_method')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div x-show="productType === 'weight_based'" x-cloak class="md:col-span-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-2.5">
                            <div>
                                <label class="{{ $label }}">{{ __('messages.product_form_weight_unit') }}</label>
                                <select name="specs[unit]" class="{{ $input }} cursor-pointer font-bold">
                                    <option value="kg" @selected(($product->specs['unit'] ?? '') === 'kg')>kg (ကီလိုဂရမ် - Kilogram)</option>
                                    <option value="g" @selected(($product->specs['unit'] ?? '') === 'g')>g (ဂရမ် - Gram)</option>
                                    <option value="viss" @selected(($product->specs['unit'] ?? '') === 'viss')>viss (ပိဿာ - Viss)</option>
                                    <option value="kyat" @selected(($product->specs['unit'] ?? '') === 'kyat')>kyat-tha (ကျပ်သား - Kyat-tha)</option>
                                    <option value="lb" @selected(($product->specs['unit'] ?? '') === 'lb')>lb (ပေါင် - Pound)</option>
                                    <option value="l" @selected(($product->specs['unit'] ?? '') === 'l')>l (လီတာ - Liter)</option>
                                    <option value="ml" @selected(($product->specs['unit'] ?? '') === 'ml')>ml (မီလီလီတာ - Milliliter)</option>
                                    <option value="pack" @selected(($product->specs['unit'] ?? '') === 'pack')>pack (ထုပ် - Pack)</option>
                                </select>
                            </div>
                            <div class="rounded-lg bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 p-2 text-xs text-amber-800 dark:text-amber-300 flex items-center gap-2">
                                <span class="text-sm">⚖️</span>
                                <span class="text-[11px]">{{ __('messages.product_form_weight_note') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 6. Product Variants & Presets Card --}}
            <section class="{{ $section }}" :class="productType === 'variant' ? 'ring-2 ring-violet-500/50 dark:ring-violet-500/40 border-violet-300 dark:border-violet-700' : ''">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2 mb-1">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center text-xs font-bold">
                            🎨
                        </span>
                        <div>
                            <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <span>{{ __('messages.product_form_variants_section') }}</span>
                                <span x-show="productType === 'variant'" x-cloak class="rounded bg-violet-600 px-1.5 py-0.2 text-[10px] font-black text-white uppercase shadow-2xs">{{ __('messages.active_matrix') }}</span>
                                <span x-show="productType !== 'variant'" class="text-[11px] font-semibold text-slate-400">({{ __('messages.product_form_variants_optional') }})</span>
                            </h2>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 self-start sm:self-auto">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800"
                              x-text="variants.length"></span>
                        <button type="button" @click="variantsOpen = !variantsOpen" data-test-label="Toggle Variants"
                            class="px-2.5 py-3 sm:py-1 rounded-md text-xs font-black border transition flex items-center gap-1 cursor-pointer"
                            :class="variantsOpen ? 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700' : 'bg-violet-600 hover:bg-violet-500 text-white border-violet-600'">
                            <span x-show="!variantsOpen">+</span>
                            <span x-show="variantsOpen" x-cloak>▾</span>
                            <span x-text="variantsOpen ? '{{ __('messages.close') }}' : '{{ __('messages.product_form_variants_toggle') }}'"></span>
                        </button>
                    </div>
                </div>

                <p x-show="!variantsOpen" x-cloak class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.product_form_variants_hint') }}</p>

                @error('variants')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror

                <div x-show="variantsOpen" x-cloak class="space-y-2">
                {{-- Add-row button lives with the editor, not in the collapsed header. --}}
                <button type="button" @click="addVariant()" class="w-full sm:w-auto px-2.5 py-3 sm:py-1 rounded-md text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-xs transition flex items-center justify-center gap-1 cursor-pointer">
                    <span>+</span>
                    <span>{{ __('messages.product_form_add_variant') }}</span>
                </button>

                {{-- Preset Selector Box --}}
                <div class="rounded-lg border border-violet-100 dark:border-violet-900/60 bg-violet-50/50 dark:bg-violet-950/20 p-2.5 space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                        <h3 class="text-[11px] font-black uppercase tracking-wide text-violet-900 dark:text-violet-200 flex items-center gap-1.5">
                            <span>⚡</span>
                            <span>{{ __('messages.product_form_variant_preset_card') }}</span>
                        </h3>
                        <a href="{{ route('store.admin.variant-presets.index', ['store_slug' => $store->slug]) }}" class="text-[11px] font-bold text-violet-600 hover:underline dark:text-violet-400">{{ __('messages.product_form_open_variant_settings') }}</a>
                    </div>

                    @if (($variantPresets ?? collect())->isNotEmpty())
                        <div class="grid grid-cols-1 items-end gap-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto_auto]">
                            <div>
                                <label class="text-[10px] font-bold uppercase text-violet-700 dark:text-violet-300" data-test-label="Preset 1">{{ __('messages.product_form_preset_1') }}</label>
                                <select x-model="selectedVariantPresetId" class="mt-0.5 w-full cursor-pointer rounded-lg border border-violet-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-900 dark:border-violet-800 dark:bg-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500/40">
                                    <option value="">{{ __('messages.product_form_choose_preset') }}</option>
                                    <template x-for="preset in filteredVariantPresets" :key="preset.id">
                                        <option :value="preset.id" x-text="preset.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="text-[10px] font-bold uppercase text-violet-700 dark:text-violet-300" data-test-label="Preset 2 (optional)">{{ __('messages.product_form_preset_2') }}</label>
                                <select x-model="selectedVariantPresetIdTwo" class="mt-0.5 w-full cursor-pointer rounded-lg border border-violet-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-900 dark:border-violet-800 dark:bg-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500/40">
                                    <option value="">{{ __('messages.product_form_combine_with') }}</option>
                                    <template x-for="preset in filteredVariantPresets" :key="preset.id">
                                        <option :value="preset.id" x-text="preset.name"></option>
                                    </template>
                                </select>
                            </div>

                            <button type="button" @click="applyVariantPreset()" :disabled="!selectedVariantPresetId" data-test-label="Apply Preset" class="px-3 py-3 sm:py-1.5 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-xs font-black shadow-xs disabled:cursor-not-allowed disabled:opacity-50 transition">
                                {{ __('messages.product_form_apply_preset') }}
                            </button>

                            <button type="button" @click="applyVariantPresetCombination()" :disabled="!selectedVariantPresetId || !selectedVariantPresetIdTwo || selectedVariantPresetId === selectedVariantPresetIdTwo" data-test-label="Generate Combinations" class="px-3 py-3 sm:py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black shadow-xs disabled:cursor-not-allowed disabled:opacity-50 transition">
                                {{ __('messages.product_form_generate_combinations') }}
                            </button>
                        </div>
                    @else
                        <p class="text-xs font-semibold leading-5 text-slate-500 dark:text-slate-400">{{ __('messages.product_form_no_presets') }}</p>
                    @endif
                </div>

                {{-- Variant Items List --}}
                <div class="space-y-2">
                    <template x-for="(v, i) in variants" :key="i">
                        <div class="rounded-lg border border-slate-200 dark:border-slate-700/80 bg-slate-50/70 dark:bg-slate-800/40 p-2.5 space-y-2 transition">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="rounded-md bg-violet-600 px-2 py-0.5 text-[11px] font-black text-white shadow-2xs" x-text="'{{ __('messages.product_form_variant_label') }} ' + (i + 1)"></span>
                                    <template x-for="(attr, ai) in (v.attributes || [])" :key="ai">
                                        <span class="rounded-md border border-violet-200 bg-violet-50 px-2 py-0.5 text-[10px] font-bold text-violet-700 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-300" x-text="attr.label + ': ' + attr.value"></span>
                                    </template>
                                </div>
                                <button type="button" @click="removeVariant(i)" class="rounded-md px-2 py-0.5 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">{{ __('messages.product_form_remove_variant') }}</button>
                            </div>

                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <label class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400">{{ __('messages.product_form_variant_name') }} *</label>
                                    <input type="text" x-model="v.name" :name="'variants[' + i + '][name]'" required class="mt-0.5 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2.5 py-1 text-xs font-semibold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500/40" placeholder="{{ __('messages.product_form_variant_name_placeholder') }}" />
                                </div>

                                <div>
                                    <label class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400">{{ __('messages.product_form_sku') }}</label>
                                    <input type="text" x-model="v.sku" :name="'variants[' + i + '][sku]'" class="mt-0.5 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2.5 py-1 text-xs font-semibold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500/40" placeholder="{{ __('messages.product_form_variant_sku_placeholder') }}" />
                                </div>

                                <div>
                                    <label class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400">{{ __('messages.product_form_variant_quantity') }}</label>
                                    <div class="mt-0.5 flex items-center gap-1.5">
                                        <input type="number" step="0.001" min="0" x-model="v.quantity_on_hand" :name="'variants[' + i + '][quantity_on_hand]'"
                                            @input="v.stock_status = (parseFloat(v.quantity_on_hand) || 0) > 0 ? 'in_stock' : 'out_of_stock'"
                                            class="w-20 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1 text-xs font-semibold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500/40" placeholder="0" />
                                        <span class="inline-flex items-center gap-1 px-1.5 py-1 rounded-md border text-[9px] font-black"
                                            :class="v.stock_status === 'out_of_stock' ? 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800' : 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800'">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="v.stock_status === 'out_of_stock' ? 'bg-rose-500' : 'bg-emerald-500'"></span>
                                            <span x-text="v.stock_status === 'out_of_stock' ? '{{ __('messages.out_of_stock') }}' : '{{ __('messages.in_stock') }}'"></span>
                                        </span>
                                        <input type="hidden" :name="'variants[' + i + '][stock_status]'" :value="v.stock_status" />
                                    </div>
                                </div>

                                <div>
                                    <label class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400">{{ __('messages.product_form_variant_retail_price') }} *</label>
                                    <input type="number" step="0.01" min="0" x-model="v.retail_price" :name="'variants[' + i + '][retail_price]'" required class="mt-0.5 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2.5 py-1 text-xs font-semibold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500/40" />
                                </div>

                                <div>
                                    <label class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400">{{ __('messages.product_form_variant_wholesale_price') }}</label>
                                    <input type="number" step="0.01" min="0" x-model="v.wholesale_price" :name="'variants[' + i + '][wholesale_price]'" class="mt-0.5 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2.5 py-1 text-xs font-semibold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500/40" />
                                </div>

                                <div class="flex items-end pb-1">
                                    <label class="inline-flex cursor-pointer select-none items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-300">
                                        <input type="checkbox" x-model="v.is_default" :name="'variants[' + i + '][is_default]'" value="1" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500" />
                                        {{ __('messages.product_form_default_variant') }}
                                    </label>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 items-center gap-2 sm:grid-cols-2 pt-1 border-t border-slate-200/50 dark:border-slate-700/50">
                                <div>
                                    <label class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400">{{ __('messages.product_form_variant_image') }}</label>
                                    <input type="file" accept="image/*" @change="previewVariantImage($event, i)" :name="'variants[' + i + '][image]'" class="mt-0.5 block w-full text-xs text-slate-600 dark:text-slate-400 file:mr-2 file:rounded-md file:border-0 file:bg-violet-50 file:px-2 file:py-1 file:text-xs file:font-bold file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-slate-800 dark:file:text-violet-300 rounded-md border border-slate-200 dark:border-slate-700 p-0.5 bg-white dark:bg-slate-900" />
                                </div>

                                <div class="flex items-center gap-2">
                                    <img x-show="v.image_preview" :src="v.image_preview" class="h-10 w-10 rounded-md border border-slate-200 dark:border-slate-700 object-cover shadow-2xs" />
                                    <template x-if="v.image_path && !v.image_preview">
                                        <img :src="'/storage/' + v.image_path" class="h-10 w-10 rounded-md border border-slate-200 dark:border-slate-700 object-cover shadow-2xs" />
                                    </template>
                                    <label x-show="v.image_path && !v.image_preview" class="inline-flex cursor-pointer select-none items-center gap-1 text-[11px] font-bold text-rose-600">
                                        <input type="checkbox" x-model="v.remove_image" :name="'variants[' + i + '][remove_image]'" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500" />
                                        {{ __('messages.product_form_remove_image') }}
                                    </label>
                                </div>
                            </div>

                            <template x-for="(attr, ai) in (v.attributes || [])" :key="ai">
                                <span class="hidden">
                                    <input type="hidden" :name="'variants[' + i + '][attributes][' + ai + '][label]'" :value="attr.label" />
                                    <input type="hidden" :name="'variants[' + i + '][attributes][' + ai + '][value]'" :value="attr.value" />
                                </span>
                            </template>
                            <input type="hidden" x-model="v.id" :name="'variants[' + i + '][id]'" :value="v.id" />
                        </div>
                    </template>
                </div>
                </div>{{-- /variantsOpen --}}
            </section>
        </div>

        {{-- RIGHT COLUMN (lg:col-span-4): Inventory, Storage, Media & Smart Tools --}}
        <div class="lg:col-span-4 space-y-1.5 sm:space-y-2">

            {{-- Smart Auto-SKU & Name Generator Card.
                 Stays collapsed to a single row until "Auto-generate SKU" is
                 checked, so it costs no vertical space for hand-typed SKUs. --}}
            <div class="w-full rounded-lg border border-indigo-200/80 dark:border-indigo-800/80 bg-indigo-50/40 dark:bg-slate-900 shadow-2xs overflow-hidden">
                <div class="flex items-center justify-between gap-2 p-2.5 select-none"
                     :class="autoSku ? 'border-b border-indigo-100 dark:border-indigo-900/60' : ''">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="w-5 h-5 shrink-0 rounded bg-indigo-600 text-white grid place-items-center text-xs shadow-2xs">✨</span>
                        <h3 class="text-xs font-black uppercase tracking-wide text-indigo-950 dark:text-indigo-200 truncate">
                            {{ __('messages.product_form_smart_sku_toggle') }}
                        </h3>
                    </div>
                    <label class="inline-flex shrink-0 cursor-pointer select-none items-center gap-1.5 min-h-[44px] sm:min-h-0 text-[11px] font-black text-indigo-700 dark:text-indigo-300">
                        <input type="checkbox" name="auto_sku" value="1" x-model="autoSku" @change="recomputeSmartSkuAndName()" class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer" />
                        <span>{{ __('messages.product_form_auto_sku') }}</span>
                    </label>
                </div>

                {{-- Generator Body (Only expands when auto_sku is checked) --}}
                <div x-show="autoSku" x-transition class="space-y-2 p-2.5" x-cloak>
                    {{-- 1. Brand Code — dropdown fed by Master Data → Brands tab --}}
                    <div class="space-y-1 rounded-md bg-white dark:bg-slate-800/80 p-2 border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.product_form_smart_brand_code') }}</span>
                            <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'brands']) }}" target="_blank" class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold inline-flex items-center gap-0.5">
                                {{ __('messages.product_form_smart_open_master_data') }} ↗
                            </a>
                        </div>
                        <select x-model="selectedBrand" @change="recomputeSmartSkuAndName()" x-init="$nextTick(() => $el.value = selectedBrand)" data-test-label="Smart Brand Code" class="{{ $input }} cursor-pointer">
                            <option value="">{{ __('messages.product_form_smart_brand_code_pick') }}</option>
                            <template x-for="b in brands" :key="b.id">
                                <option :value="b.id" x-text="(b.code ? b.code + ' — ' : '') + b.name"></option>
                            </template>
                        </select>
                        <div class="flex items-center justify-between text-xs bg-slate-50 dark:bg-slate-900/60 px-2 py-1 rounded border border-slate-200 dark:border-slate-700">
                            <span class="font-mono font-black text-indigo-600 dark:text-indigo-400" x-text="brands.find(b => String(b.id) === String(selectedBrand))?.code || (selectedBrand ? '{{ __('messages.product_form_smart_no_code') }}' : '{{ __('messages.product_form_smart_select_brand_first') }}')"></span>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 truncate max-w-[120px]" x-text="brands.find(b => String(b.id) === String(selectedBrand))?.name || ''"></span>
                        </div>
                    </div>

                    {{-- 2. Sub Category Code — dropdown fed by Master Data → Categories tab.
                         Main categories are listed first with their sub-categories beneath. --}}
                    <div class="space-y-1 rounded-md bg-white dark:bg-slate-800/80 p-2 border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.product_form_smart_subcat_code') }}</span>
                            <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'categories']) }}" target="_blank" class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold inline-flex items-center gap-0.5">
                                {{ __('messages.product_form_smart_open_master_data') }} ↗
                            </a>
                        </div>
                        <select :value="smartCategoryPick" @change="onSmartCategoryPick($event.target.value)" data-test-label="Smart Sub Category Code"
                                x-init="$nextTick(() => $el.value = smartCategoryPick)"
                                class="{{ $input }} cursor-pointer">
                            <option value="">{{ __('messages.product_form_smart_subcat_code_pick') }}</option>
                            <template x-for="opt in smartCategoryOptions" :key="opt.id">
                                <option :value="opt.id" x-text="(opt.depth ? '↳ ' : '') + opt.label"></option>
                            </template>
                        </select>
                        <div class="flex items-center justify-between text-xs bg-slate-50 dark:bg-slate-900/60 px-2 py-1 rounded border border-slate-200 dark:border-slate-700">
                            <span class="font-mono font-black text-indigo-600 dark:text-indigo-400" x-text="smartCategoryPick ? (categories.find(c => String(c.id) === String(smartCategoryPick))?.code || '{{ __('messages.product_form_smart_no_code') }}') : '{{ __('messages.product_form_smart_select_category_first') }}'"></span>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 truncate max-w-[120px]" x-text="categories.find(c => String(c.id) === String(smartCategoryPick))?.name || ''"></span>
                        </div>
                    </div>

                    {{-- 3. Model Code Input --}}
                    <div class="space-y-0.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">{{ __('messages.product_form_smart_model_code') }}</label>
                            <span class="text-[9px] text-slate-400 font-medium">{{ __('messages.product_form_type_from_device') }}</span>
                        </div>
                        <input type="text" x-model="productModelCode" @input="recomputeSmartSkuAndName()" data-test-label="Smart Model Code" class="{{ $input }} uppercase text-xs font-mono font-bold" placeholder="{{ __('messages.product_form_smart_model_code_placeholder') }}" />
                    </div>

                    {{-- 4. Generated SKU & Name Previews --}}
                    <div class="pt-1 border-t border-indigo-100 dark:border-indigo-900/60 space-y-1.5">
                        {{-- SKU Preview --}}
                        <div class="bg-white dark:bg-slate-900 px-2.5 py-1.5 rounded-md border border-indigo-200/80 dark:border-indigo-800/80 shadow-2xs space-y-0.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">SKU Code:</span>
                                <span class="text-[9px] font-mono text-indigo-500 font-semibold">{{ __('messages.product_form_smart_formula_sku') }}</span>
                            </div>
                            <div class="font-mono font-black text-indigo-600 dark:text-indigo-400 text-xs sm:text-sm tracking-wider truncate" x-text="productSku || '—'"></div>
                        </div>

                        {{-- Name Preview --}}
                        <div class="bg-white dark:bg-slate-900 px-2.5 py-1.5 rounded-md border border-indigo-200/80 dark:border-indigo-800/80 shadow-2xs space-y-0.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('messages.product_form_name') }}:</span>
                                <button type="button" @click="nameTouched = false; recomputeSmartSkuAndName()" class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline cursor-pointer flex items-center gap-0.5" title="Re-sync name from formula">
                                    🔄 {{ __('messages.product_form_smart_resync') }}
                                </button>
                            </div>
                            <div class="font-bold text-slate-800 dark:text-slate-200 text-xs truncate" x-text="productNameInput || '—'"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Warehouse, Storage & Stock Card --}}
            <section class="{{ $section }}">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2 mb-1">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 grid place-items-center text-xs font-bold">
                            🏢
                        </span>
                        <div>
                            <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                                {{ __('messages.product_form_inventory_section') }}
                            </h2>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    {{-- Warehouse --}}
                    <div x-show="productType !== 'service' && productType !== 'digital'" x-cloak>
                        <label class="{{ $label }}">{{ __('messages.product_form_warehouse') }}</label>
                        <select name="warehouse_id" x-model="productWarehouseId" :disabled="isStockless" class="{{ $input }} cursor-pointer disabled:cursor-not-allowed disabled:opacity-60">
                            <option value="">{{ __('messages.product_form_warehouse_none') }}</option>
                            <template x-for="w in warehouses" :key="w.id">
                                <option :value="w.id" x-text="w.name"></option>
                            </template>
                        </select>
                        @error('warehouse_id')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Shelf Location --}}
                    <div x-show="productType !== 'service' && productType !== 'digital'" x-cloak>
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <label class="{{ $label }} mb-0">{{ __('messages.product_form_shelf_location') }}</label>
                            <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'shelves']) }}" target="_blank" class="text-[10px] font-bold text-purple-600 dark:text-purple-400 hover:underline">Presets</a>
                        </div>
                        <div class="space-y-1">
                            <select x-model="productShelfLocation" class="{{ $input }} cursor-pointer text-xs">
                                <option value="">-- {{ __('messages.product_form_shelf_location_placeholder') }} --</option>
                                @foreach ($shelfList as $sh)
                                    <option value="{{ $sh->name }}">{{ $sh->code ? '[' . $sh->code . '] ' : '' }}{{ $sh->name }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="shelf_location" x-model="productShelfLocation" :disabled="isStockless" class="{{ $input }} disabled:cursor-not-allowed disabled:opacity-60" placeholder="{{ __('messages.product_form_shelf_location_placeholder') }}" title="Custom shelf location" />
                        </div>
                        @error('shelf_location')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Initial Stock (on create) or Current Balance Note (on edit) --}}
                    @if (!$isEdit)
                    <div x-show="productType !== 'service' && productType !== 'digital'" x-cloak>
                        <label class="{{ $label }}">{{ __('messages.product_form_initial_stock') }}</label>
                        <input type="number" step="0.001" min="0" name="initial_stock" value="{{ old('initial_stock') }}" :disabled="isStockless" class="{{ $input }} disabled:cursor-not-allowed disabled:opacity-60" placeholder="0" />
                        <p class="{{ $hint }}">{{ __('messages.product_form_initial_stock_hint') }}</p>
                        <p x-show="isStockless" x-cloak class="mt-1 text-[11px] font-bold text-teal-600 dark:text-teal-400">ℹ️ {{ __('messages.product_form_stock_hidden_note') }}</p>
                        @error('initial_stock')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    @else
                    <div x-show="productType !== 'service' && productType !== 'digital'" x-cloak>
                        <label class="{{ $label }}">{{ __('messages.product_form_initial_stock') }}</label>
                        <p class="rounded-lg bg-slate-50 dark:bg-slate-800/60 p-2 text-xs leading-4 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">{{ __('messages.product_form_initial_stock_edit_note') }}</p>
                    </div>
                    @endif

                    {{-- Reorder Level --}}
                    @if (store_can('products.view_cost', $store))
                    <div x-show="productType !== 'service' && productType !== 'digital'" x-cloak>
                        <label class="{{ $label }}">{{ __('messages.product_form_reorder_level') }}</label>
                        <input type="number" step="0.001" min="0" name="reorder_level" value="{{ old('reorder_level', $product->reorder_level) }}" :disabled="isStockless" class="{{ $input }} disabled:cursor-not-allowed disabled:opacity-60" placeholder="10" />
                        <p class="{{ $hint }}">{{ __('messages.product_form_reorder_level_hint') }}</p>
                        @error('reorder_level')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    {{-- Supplier --}}
                    <div>
                        <label class="{{ $label }}">{{ __('messages.product_form_supplier') }}</label>
                        <div class="flex items-stretch gap-1.5">
                            <select name="supplier_id" x-model="selectedSupplier" x-init="$nextTick(() => $el.value = selectedSupplier)" class="{{ $input }} flex-1 min-w-0 cursor-pointer">
                                <option value="">{{ __('messages.product_form_none') }}</option>
                                <template x-for="s in suppliers" :key="s.id">
                                    <option :value="s.id" x-text="s.name"></option>
                                </template>
                            </select>
                            <button type="button" @click="supplierModalOpen = true" title="{{ __('messages.product_form_quick_supplier') }}" aria-label="{{ __('messages.product_form_quick_supplier') }}" class="{{ $btn3dPlus }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            
                                <span class="text-[9px] sm:hidden font-black leading-none">{{ __('messages.quick_supplier_short') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 4. Media & Product Gallery Card --}}
            <section class="{{ $section }}">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2 mb-1">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-xs font-bold">
                            🖼️
                        </span>
                        <div>
                            <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                                {{ __('messages.product_form_media_section') }}
                            </h2>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    {{-- Main Image --}}
                    <div>
                        <label class="{{ $label }}">{{ __('messages.product_form_product_image') }}</label>
                        <input type="file" name="image" accept="image/*" @change="previewMain($event)" class="{{ $fileInput }}" />
                        @error('image')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                        <div class="mt-2 flex flex-wrap gap-2">
                            <img x-show="mainPreview" :src="mainPreview" class="h-20 w-20 rounded-lg object-cover border border-slate-200 dark:border-slate-700 shadow-2xs" />
                            @if (!$isEdit && !empty($product->image_path))
                                <img src="{{ asset('storage/' . $product->image_path) }}" class="h-20 w-20 rounded-lg object-cover border border-slate-200 dark:border-slate-700 shadow-2xs" />
                            @endif
                            @if ($isEdit && !empty($product->image_path))
                                <div class="relative h-20 w-20 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs">
                                    <img src="{{ asset('storage/' . $product->image_path) }}" class="h-20 w-20 object-cover" />
                                    <span class="absolute inset-x-0 bottom-0 bg-slate-900/80 py-0.5 text-center text-[9px] font-bold text-white">{{ __('messages.product_form_current_image') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Gallery Images --}}
                    <div>
                        <label class="{{ $label }}">{{ __('messages.product_form_gallery_images') }}</label>
                        <input type="file" name="gallery_images[]" multiple accept="image/*" @change="previewGallery($event)" class="{{ $fileInput }}" />
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <template x-for="(g, gi) in galleryPreviews" :key="gi">
                                <img :src="g" class="h-14 w-14 rounded-md object-cover border border-slate-200 dark:border-slate-700 shadow-2xs" />
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 5. Visibility & Channel Settings Card --}}
            <section class="{{ $section }}">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2 mb-1">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 grid place-items-center text-xs font-bold">
                            🌐
                        </span>
                        <div>
                            <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                                Visibility & Channels
                            </h2>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="flex items-center justify-between p-2 rounded-lg border border-slate-200/80 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/70 transition">
                        <div class="flex items-center gap-2">
                            <span>⭐</span>
                            <div>
                                <div class="text-xs font-black text-slate-900 dark:text-white">{{ __('messages.product_form_featured') }}</div>
                                <div class="text-[10px] text-slate-400">Show on homepage featured deals</div>
                            </div>
                        </div>
                        <input type="checkbox" name="is_featured" value="1" id="is_featured" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500 cursor-pointer" />
                    </label>

                    <label class="flex items-center justify-between p-2 rounded-lg border border-slate-200/80 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/70 transition">
                        <div class="flex items-center gap-2">
                            <span>🌐</span>
                            <div>
                                <div class="text-xs font-black text-slate-900 dark:text-white">{{ __('messages.product_form_sell_online') }}</div>
                                <div class="text-[10px] text-slate-400">{{ __('messages.product_form_sell_online_hint') }}</div>
                            </div>
                        </div>
                        <div>
                            <input type="hidden" name="is_ecommerce" value="0" />
                            <input type="checkbox" name="is_ecommerce" value="1" id="is_ecommerce" {{ old('is_ecommerce', $product->is_ecommerce ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer" />
                        </div>
                    </label>
                </div>
            </section>
        </div>
    </div>

    {{-- Progressive Disclosure Accordion: Advanced Details (Description, Warranty, Policy, SEO, Previews) --}}
    <div x-data="{ expandedAdvanced: {{ ($isEdit && (!empty($product->description) || !empty($product->meta_description) || !empty($product->warranty) || !empty($product->return_policy))) || $errors->has('description') || $errors->has('warranty') || $errors->has('return_policy') || $errors->has('meta_description') ? 'true' : 'false' }} }"
         @expand-advanced.window="expandedAdvanced = true"
         class="w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden">
        <button type="button" @click="expandedAdvanced = !expandedAdvanced"
                class="w-full flex items-center justify-between px-3 py-2 bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800 border-b border-transparent transition cursor-pointer"
                :class="expandedAdvanced ? 'border-slate-200 dark:border-slate-700' : ''">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-md bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 grid place-items-center text-xs font-bold">
                    📝
                </span>
                <div class="text-left">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                        {{ __('messages.product_form_more_details') }}
                    </h3>
                    <p class="text-[10px] text-slate-400">Rich Description, Warranty, Return Policy, SEO Meta & Live Previews</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                <span x-text="expandedAdvanced ? 'Hide' : 'Expand'"></span>
                <span class="transition-transform duration-200" :class="expandedAdvanced ? 'rotate-180' : ''">▼</span>
            </div>
        </button>

        <div x-show="expandedAdvanced" x-transition class="p-2.5 sm:p-3 space-y-3">
            {{-- Product Type selector — off the counter path. Each button also
                 normalises the dependent fields, so switching type never leaves a
                 stale stock value to be posted for a service or digital item. --}}
            <div id="product-type-selector" class="rounded-lg border border-violet-200/80 dark:border-violet-800/60 bg-violet-50/40 dark:bg-slate-900/60 p-2.5 space-y-1.5">
                <div class="flex flex-wrap items-center justify-between gap-1.5">
                    <h4 class="text-xs font-black uppercase tracking-wide text-violet-900 dark:text-violet-200 flex items-center gap-1.5">
                        <span>⚡</span>
                        <span>{{ __('messages.product_form_product_type') }}</span>
                    </h4>
                    <span class="text-[10px] font-bold text-violet-700 dark:text-violet-300">{{ __('messages.product_form_type_help') }}</span>
                </div>

                <div class="grid grid-cols-3 sm:grid-cols-6 gap-1">
                    @foreach ([
                        ['standard', '📦', 'product_type_standard'],
                        ['serialized', '📱', 'product_type_serialized'],
                        ['variant', '🔀', 'product_type_variant'],
                        ['service', '🛠️', 'product_type_service'],
                        ['digital', '💻', 'product_type_digital'],
                        ['weight_based', '⚖️', 'product_type_weight_based'],
                    ] as [$typeValue, $typeIcon, $typeLabelKey])
                        <button type="button" @click="setProductType('{{ $typeValue }}')"
                            data-test-label="Product Type {{ $typeValue }}"
                            :class="productType === '{{ $typeValue }}' ? 'bg-violet-600 text-white shadow-md shadow-violet-500/25 border-violet-600' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-800 hover:border-violet-300'"
                            class="flex flex-col items-center justify-center p-1.5 rounded-lg border text-center transition-all duration-150 cursor-pointer">
                            <span class="text-sm mb-0.5">{{ $typeIcon }}</span>
                            <span class="text-[11px] font-black">{{ __('messages.' . $typeLabelKey) }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="px-2.5 py-1.5 rounded-md text-[11px] font-medium flex items-center gap-2 border transition-all duration-200"
                    :class="{
                        'bg-slate-50 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300': productType === 'standard',
                        'bg-blue-50 dark:bg-blue-950/40 border-blue-200 dark:border-blue-800/80 text-blue-900 dark:text-blue-200': productType === 'serialized',
                        'bg-violet-50 dark:bg-violet-950/40 border-violet-200 dark:border-violet-800/80 text-violet-900 dark:text-violet-200': productType === 'variant',
                        'bg-teal-50 dark:bg-teal-950/40 border-teal-200 dark:border-teal-800/80 text-teal-900 dark:text-teal-200': productType === 'service',
                        'bg-sky-50 dark:bg-sky-950/40 border-sky-200 dark:border-sky-800/80 text-sky-900 dark:text-sky-200': productType === 'digital',
                        'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800/80 text-amber-900 dark:text-amber-200': productType === 'weight_based',
                    }">
                    <span class="text-xs shrink-0" x-text="productTypeIcon"></span>
                    <span class="text-[11px]" x-text="productTypeDescription"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-2.5 sm:gap-3 md:grid-cols-2">
                {{-- Warranty --}}
                <div>
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <label class="{{ $label }} mb-0">{{ __('messages.product_form_warranty') }}</label>
                        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'warranties']) }}" target="_blank" class="text-[11px] font-bold text-sky-600 dark:text-sky-400 hover:underline">Presets</a>
                    </div>
                    <div class="space-y-1">
                        <select x-model="productWarranty" class="{{ $input }} cursor-pointer text-xs">
                            <option value="">-- {{ __('messages.product_form_warranty_placeholder') }} --</option>
                            @foreach ($warrantyList as $w)
                                <option value="{{ $w->name }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="warranty" x-model="productWarranty" class="{{ $input }}" placeholder="{{ __('messages.product_form_warranty_placeholder') }}" title="Custom warranty" />
                    </div>
                </div>

                {{-- Return Policy --}}
                <div>
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <label class="{{ $label }} mb-0">{{ __('messages.product_form_return_policy') }}</label>
                        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'return-policies']) }}" target="_blank" class="text-[11px] font-bold text-rose-600 dark:text-rose-400 hover:underline">Presets</a>
                    </div>
                    <select @change="if($event.target.value){ let el = document.getElementById('product_return_policy_input'); if(el){ el.value = $event.target.value; el.dispatchEvent(new Event('input')); } }" class="{{ $input }} cursor-pointer text-xs mb-1">
                        <option value="">-- {{ __('messages.product_form_fill_template') }} --</option>
                        @foreach ($returnPolicyList as $rp)
                            <option value="{{ addslashes($rp->content) }}">📋 {{ $rp->name }}</option>
                        @endforeach
                    </select>
                    <textarea name="return_policy" id="product_return_policy_input" rows="2" @input="refreshReturnPolicyPreview()" class="{{ $input }}" placeholder="{{ __('messages.product_form_return_policy_placeholder') }}">{{ old('return_policy', $product->return_policy) }}</textarea>
                </div>

                {{-- Long Description with Rich Text Editor --}}
                <div class="md:col-span-2">
                    <label class="{{ $label }}">{{ __('messages.product_form_description') }}</label>
                    <x-richtext-editor name="description" :value="old('description', $product->description)" :rows="140" placeholder="{{ __('messages.product_form_description_placeholder') }}" />
                    @error('description')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                {{-- SEO Meta Description --}}
                <div class="md:col-span-2">
                    <label class="{{ $label }}">{{ __('messages.product_form_meta_description') }}</label>
                    <textarea name="meta_description" rows="2" maxlength="1000" @input="metaDescLen = $el.value.length" class="{{ $input }}" placeholder="{{ __('messages.product_form_meta_placeholder') }}">{{ old('meta_description', $product->meta_description) }}</textarea>
                    <div class="mt-1 flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                        <p class="{{ $hint }}">{{ __('messages.product_form_meta_helper') }} {{ __('messages.product_form_meta_empty_fallback') }}</p>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500" :class="metaDescLen > 160 ? 'text-rose-500 dark:text-rose-400' : ''">
                                <span x-text="metaDescLen"></span>/160
                            </span>
                        </div>
                    </div>
                    @error('meta_description')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Live Previews Box --}}
            <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
                <h4 class="text-xs font-black uppercase text-slate-500 mb-2 flex items-center gap-1.5">
                    <span>👁️</span>
                    <span>{{ __('messages.product_form_preview_section') }}</span>
                </h4>
                <div class="grid grid-cols-1 gap-2 sm:gap-2.5 lg:grid-cols-2">
                    <div class="min-w-0">
                        <h5 class="mb-1 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">{{ __('messages.product_form_description_preview') }}</h5>
                        <div class="min-h-[5rem] max-h-40 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50/80 p-2 text-xs leading-relaxed text-slate-800 prose prose-sm max-w-none dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-100">
                            <template x-if="descriptionPreviewHtml">
                                <div x-html="descriptionPreviewHtml"></div>
                            </template>
                            <template x-if="!descriptionPreviewHtml">
                                <p class="text-xs text-slate-400">{{ __('messages.spec_description_empty') }}</p>
                            </template>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <h5 class="mb-1 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">{{ __('messages.product_form_specs_preview') }}</h5>
                        <div class="min-h-[5rem] max-h-40 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50/80 p-2 dark:border-slate-700 dark:bg-slate-900/60">
                            <template x-if="previewSpecs().length">
                                <dl class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-for="(row, i) in previewSpecs()" :key="i">
                                        <div class="grid grid-cols-1 gap-x-2 py-0.5 sm:grid-cols-[minmax(0,8rem)_minmax(0,1fr)]">
                                            <dt class="break-words text-[11px] font-bold text-slate-500 dark:text-slate-400" x-text="row.label"></dt>
                                            <dd class="min-w-0 break-words text-[11px] font-semibold text-slate-800 dark:text-slate-200" x-text="row.value"></dd>
                                        </div>
                                    </template>
                                </dl>
                            </template>
                            <template x-if="!previewSpecs().length">
                                <p class="text-xs text-slate-400">{{ __('messages.specs_empty') }}</p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden region html5-qrcode renders the captured photo into while decoding. --}}
    <div id="admin-barcode-scan-region" class="hidden" aria-hidden="true"></div>

    {{-- Sticky Bottom Action Bar --}}
    <div class="sticky bottom-0 z-20 w-full border border-slate-200/90 bg-white/95 px-3 py-1.5 sm:px-4 backdrop-blur-md shadow-[0_-4px_16px_rgba(15,23,42,0.06)] dark:border-slate-800/90 dark:bg-slate-900/95 rounded-lg">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="hidden md:inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-md border border-slate-200 dark:border-slate-700">
                    <span>⌨️</span>
                    <span>{{ __('messages.product_form_quick_entry_hint') }}</span>
                </span>
            </div>

            <div class="flex items-center gap-1.5 w-full sm:w-auto justify-end">
                <a href="{{ $returnTo ?? url('/store/' . $store->slug . '/admin/products') }}"
                   class="h-10 sm:h-8 px-3.5 flex-1 sm:flex-none justify-center rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition flex items-center cursor-pointer">
                    {{ __('messages.cancel') }}
                </a>

                @if (!$isEdit)
                <button type="submit" name="action" value="save_and_new"
                        class="h-10 sm:h-8 px-3.5 flex-1 sm:flex-none justify-center rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 font-bold text-xs transition flex items-center gap-1 cursor-pointer active:scale-95 shadow-2xs"
                        title="သိမ်းဆည်းပြီး အမျိုးအစားနှင့် Brand ဆက်လက်ထိန်းသိမ်းကာ နောက်ပစ္စည်း ဆက်ထည့်မည်">
                    <span>⚡</span>
                    <span>{{ __('messages.product_form_save_and_new') }}</span>
                </button>
                @endif

                <button type="submit" name="action" value="save"
                        class="h-10 sm:h-8 px-4 flex-1 sm:flex-none justify-center rounded-md bg-violet-600 hover:bg-violet-500 text-white font-black text-xs shadow-md shadow-violet-500/20 transition flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <span>💾</span>
                    <span>{{ $isEdit ? __('messages.product_form_update_product') : __('messages.product_form_save_product') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
