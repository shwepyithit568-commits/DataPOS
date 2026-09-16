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
            (object)['code' => 'A-01', 'name' => __('messages.shelf_a1_example')],
            (object)['code' => 'A-02', 'name' => __('messages.shelf_a2_example')],
            (object)['code' => 'B-01', 'name' => __('messages.shelf_b1_example')],
            (object)['code' => 'B-02', 'name' => __('messages.shelf_b2_example')],
            (object)['code' => 'CTR-01', 'name' => __('messages.counter_glass_example')],
            (object)['code' => 'CAB-01', 'name' => __('messages.shelf_cab_example')],
            (object)['code' => 'RP-01', 'name' => __('messages.shelf_rp_example')],
            (object)['code' => 'WH-RACK', 'name' => __('messages.shelf_wh_example')],
        ]);

    $warrantyList = isset($masterPresets) && $masterPresets->where('type', 'warranty')->isNotEmpty()
        ? $masterPresets->where('type', 'warranty')->values()
        : collect([
            (object)['name' => '7 Days Testing Warranty', 'content' => __('messages.warranty_7_days_desc')],
            (object)['name' => '1 Month Service Warranty', 'content' => __('messages.warranty_1_month_desc')],
            (object)['name' => '3 Months Warranty', 'content' => __('messages.warranty_3_months_desc')],
            (object)['name' => '6 Months Warranty', 'content' => __('messages.warranty_6_months_desc')],
            (object)['name' => '1 Year Official Warranty', 'content' => __('messages.warranty_1_year_desc')],
            (object)['name' => 'No Warranty', 'content' => __('messages.warranty_no_warranty_desc')],
        ]);

    $returnPolicyList = isset($masterPresets) && $masterPresets->where('type', 'return_policy')->isNotEmpty()
        ? $masterPresets->where('type', 'return_policy')->values()
        : collect([
            (object)['name' => '7 Days Exchange', 'content' => __('messages.policy_7_days_desc')],
            (object)['name' => 'Screen & Touch LCD Policy', 'content' => __('messages.policy_screen_touch_desc')],
            (object)['name' => 'Accessories Return Policy', 'content' => __('messages.policy_accessories_desc')],
        ]);
@endphp

<style>
    @media (max-width: 1023.98px) {
        .product-form-layout {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 0.375rem !important;
        }
        .product-form-right-col {
            display: contents !important;
        }
        .product-form-smart-sku {
            order: -1 !important;
        }
        .product-form-left-col {
            order: 1 !important;
        }
        .product-form-other-cards {
            order: 2 !important;
        }
    }
</style>

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
    <div class="product-form-layout grid grid-cols-1 lg:grid-cols-12 gap-1.5 sm:gap-2 items-start">

        {{-- LEFT COLUMN (lg:col-span-8): Primary Product & POS Data --}}
        <div class="product-form-left-col lg:col-span-8 space-y-1.5 sm:space-y-2">

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
                            <span x-text="productType === 'service' ? @js(__('messages.product_service_name')) : (productType === 'digital' ? @js(__('messages.product_digital_name')) : '{{ __('messages.product_form_name') }}')"></span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" x-model="productNameInput" @input="nameTouched = true" required class="{{ $input }}"
                            :placeholder="productType === 'service' ? @js(__('messages.product_service_name_placeholder')) : (productType === 'digital' ? @js(__('messages.product_digital_name_placeholder')) : (productType === 'serialized' ? @js(__('messages.product_serialized_name_placeholder')) : (productType === 'weight_based' ? @js(__('messages.product_weight_name_placeholder')) : '{{ __('messages.product_form_name_placeholder') }}')))" />
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
                        <div class="flex items-stretch gap-1.5" x-data="{
                            open: false,
                            search: '',
                            get filteredBrands() {
                                if (!this.search.trim()) return this.brands;
                                const q = this.search.toLowerCase().trim();
                                return this.brands.filter(b => 
                                    (b.name && b.name.toLowerCase().includes(q)) || 
                                    (b.code && b.code.toLowerCase().includes(q))
                                );
                            },
                            get selectedBrandObj() {
                                return this.brands.find(b => String(b.id) === String(selectedBrand));
                            },
                            select(id) {
                                selectedBrand = id;
                                this.open = false;
                                this.search = '';
                                recomputeSmartSkuAndName();
                            }
                        }" @click.outside="open = false" @keydown.escape="open = false">
                            <div class="relative flex-1 min-w-0">
                                <input type="hidden" name="brand_id" :value="selectedBrand" x-init="$nextTick(() => $el.value = selectedBrand)" />
                                
                                <button type="button" 
                                        @click="open = !open; if (open) $nextTick(() => $refs.brandSearch?.focus())"
                                        class="{{ $input }} flex items-center justify-between gap-1 text-left cursor-pointer select-none font-normal"
                                        :class="selectedBrand ? 'text-slate-900 dark:text-slate-100 font-semibold' : 'text-slate-400 dark:text-slate-500'">
                                    <span class="truncate" x-text="selectedBrandObj ? selectedBrandObj.name + (selectedBrandObj.code ? ' [' + selectedBrandObj.code + ']' : '') : '{{ __('messages.product_form_none') }}'"></span>
                                    <div class="flex items-center gap-1 shrink-0 ml-1">
                                        <template x-if="selectedBrand">
                                            <span @click.stop="select('')" class="text-slate-400 hover:text-rose-500 p-0.5 rounded cursor-pointer transition text-xs leading-none" title="Clear">✕</span>
                                        </template>
                                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                </button>

                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute z-50 mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-xl overflow-hidden"
                                     x-cloak>
                                    <div class="p-1.5 border-b border-slate-100 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-800/90">
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-slate-400 text-xs">🔍</span>
                                            <input type="text"
                                                   x-ref="brandSearch"
                                                   x-model="search"
                                                   placeholder="{{ __('messages.search') }}..."
                                                   class="w-full pl-7 pr-2 py-1 text-xs rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-violet-500"
                                                   @keydown.enter.prevent="if (filteredBrands.length > 0) select(filteredBrands[0].id)" />
                                        </div>
                                    </div>
                                    <div class="max-h-52 overflow-y-auto p-1 space-y-0.5 text-xs">
                                        <button type="button" 
                                                @click="select('')"
                                                class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                                :class="!selectedBrand ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-500 dark:text-slate-400'">
                                            <span>-- {{ __('messages.product_form_none') }} --</span>
                                            <span x-show="!selectedBrand">✓</span>
                                        </button>
                                        <template x-for="b in filteredBrands" :key="b.id">
                                            <button type="button"
                                                    @click="select(b.id)"
                                                    class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                                    :class="String(selectedBrand) === String(b.id) ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-800 dark:text-slate-200'">
                                                <span class="truncate" x-text="b.name"></span>
                                                <span class="flex items-center gap-1 shrink-0 ml-2">
                                                    <span x-show="b.code" class="text-[10px] font-mono px-1 py-0.2 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300" x-text="b.code"></span>
                                                    <span x-show="String(selectedBrand) === String(b.id)" class="text-violet-600 dark:text-violet-400">✓</span>
                                                </span>
                                            </button>
                                        </template>
                                        <div x-show="filteredBrands.length === 0" class="py-3 text-center text-xs text-slate-400">
                                            {{ __('messages.no_results') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

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
                        <div class="flex items-stretch gap-1.5" x-data="{
                            open: false,
                            search: '',
                            get filteredMainCategories() {
                                if (!this.search.trim()) return this.mainCategories;
                                const q = this.search.toLowerCase().trim();
                                return this.mainCategories.filter(c => 
                                    (c.name && c.name.toLowerCase().includes(q)) || 
                                    (c.code && c.code.toLowerCase().includes(q))
                                );
                            },
                            get selectedMainCatObj() {
                                return this.mainCategories.find(c => String(c.id) === String(selectedMainCategory));
                            },
                            select(id) {
                                selectedMainCategory = id;
                                onMainCategoryChange();
                                recomputeSmartSkuAndName();
                                this.open = false;
                                this.search = '';
                            }
                        }" @click.outside="open = false" @keydown.escape="open = false">
                            <div class="relative flex-1 min-w-0">
                                <button type="button" 
                                        @click="open = !open; if (open) $nextTick(() => $refs.mainCatSearch?.focus())"
                                        class="{{ $input }} flex items-center justify-between gap-1 text-left cursor-pointer select-none font-normal"
                                        :class="selectedMainCategory ? 'text-slate-900 dark:text-slate-100 font-semibold' : 'text-slate-400 dark:text-slate-500'">
                                    <span class="truncate" x-text="selectedMainCatObj ? selectedMainCatObj.name + (selectedMainCatObj.code ? ' [' + selectedMainCatObj.code + ']' : '') : '{{ __('messages.product_form_main_category_none') }}'"></span>
                                    <div class="flex items-center gap-1 shrink-0 ml-1">
                                        <template x-if="selectedMainCategory">
                                            <span @click.stop="select('')" class="text-slate-400 hover:text-rose-500 p-0.5 rounded cursor-pointer transition text-xs leading-none" title="Clear">✕</span>
                                        </template>
                                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                </button>

                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute z-50 mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-xl overflow-hidden"
                                     x-cloak>
                                    <div class="p-1.5 border-b border-slate-100 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-800/90">
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-slate-400 text-xs">🔍</span>
                                            <input type="text"
                                                   x-ref="mainCatSearch"
                                                   x-model="search"
                                                   placeholder="{{ __('messages.search') }}..."
                                                   class="w-full pl-7 pr-2 py-1 text-xs rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-violet-500"
                                                   @keydown.enter.prevent="if (filteredMainCategories.length > 0) select(filteredMainCategories[0].id)" />
                                        </div>
                                    </div>
                                    <div class="max-h-52 overflow-y-auto p-1 space-y-0.5 text-xs">
                                        <button type="button" 
                                                @click="select('')"
                                                class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                                :class="!selectedMainCategory ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-500 dark:text-slate-400'">
                                            <span>-- {{ __('messages.product_form_main_category_none') }} --</span>
                                            <span x-show="!selectedMainCategory">✓</span>
                                        </button>
                                        <template x-for="cat in filteredMainCategories" :key="cat.id">
                                            <button type="button"
                                                    @click="select(cat.id)"
                                                    class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                                    :class="String(selectedMainCategory) === String(cat.id) ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-800 dark:text-slate-200'">
                                                <span class="truncate" x-text="cat.name"></span>
                                                <span class="flex items-center gap-1 shrink-0 ml-2">
                                                    <span x-show="cat.code" class="text-[10px] font-mono px-1 py-0.2 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300" x-text="cat.code"></span>
                                                    <span x-show="String(selectedMainCategory) === String(cat.id)" class="text-violet-600 dark:text-violet-400">✓</span>
                                                </span>
                                            </button>
                                        </template>
                                        <div x-show="filteredMainCategories.length === 0" class="py-3 text-center text-xs text-slate-400">
                                            {{ __('messages.no_results') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" @click="newCategoryParent = ''; categoryModalOpen = true" title="{{ __('messages.product_form_quick_category') }}" aria-label="{{ __('messages.product_form_quick_category') }}" class="{{ $btn3dPlus }}">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            
                                <span class="text-[9px] sm:hidden font-black leading-none">{{ __('messages.quick_category_short') }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- Sub Category --}}
                    <div>
                        <label class="{{ $label }}">{{ __('messages.product_form_sub_category') }}</label>
                        <div class="flex items-stretch gap-1.5" x-data="{
                            open: false,
                            search: '',
                            get filteredSubCategories() {
                                if (!this.search.trim()) return this.subCategories;
                                const q = this.search.toLowerCase().trim();
                                return this.subCategories.filter(c => 
                                    (c.name && c.name.toLowerCase().includes(q)) || 
                                    (c.code && c.code.toLowerCase().includes(q))
                                );
                            },
                            get selectedSubCatObj() {
                                return this.subCategories.find(c => String(c.id) === String(selectedSubCategory));
                            },
                            select(id) {
                                selectedSubCategory = id;
                                recomputeSmartSkuAndName();
                                this.open = false;
                                this.search = '';
                            }
                        }" @click.outside="open = false" @keydown.escape="open = false">
                            <div class="relative flex-1 min-w-0">
                                <button type="button" 
                                        :disabled="!selectedMainCategory"
                                        @click="if (selectedMainCategory) { open = !open; if (open) $nextTick(() => $refs.subCatSearch?.focus()); }"
                                        class="{{ $input }} flex items-center justify-between gap-1 text-left cursor-pointer select-none font-normal disabled:cursor-not-allowed disabled:opacity-60"
                                        :class="selectedSubCategory ? 'text-slate-900 dark:text-slate-100 font-semibold' : 'text-slate-400 dark:text-slate-500'">
                                    <span class="truncate" x-text="!selectedMainCategory ? '{{ __('messages.product_form_sub_category_choose_first') }}' : (selectedSubCatObj ? selectedSubCatObj.name + (selectedSubCatObj.code ? ' [' + selectedSubCatObj.code + ']' : '') : (subCategories.length ? '{{ __('messages.product_form_sub_category_none') }}' : '{{ __('messages.product_form_no_sub_categories') }}'))"></span>
                                    <div class="flex items-center gap-1 shrink-0 ml-1">
                                        <template x-if="selectedSubCategory">
                                            <span @click.stop="select('')" class="text-slate-400 hover:text-rose-500 p-0.5 rounded cursor-pointer transition text-xs leading-none" title="Clear">✕</span>
                                        </template>
                                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                </button>

                                <div x-show="open && selectedMainCategory" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute z-50 mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-xl overflow-hidden"
                                     x-cloak>
                                    <div class="p-1.5 border-b border-slate-100 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-800/90">
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-slate-400 text-xs">🔍</span>
                                            <input type="text"
                                                   x-ref="subCatSearch"
                                                   x-model="search"
                                                   placeholder="{{ __('messages.search') }}..."
                                                   class="w-full pl-7 pr-2 py-1 text-xs rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-violet-500"
                                                   @keydown.enter.prevent="if (filteredSubCategories.length > 0) select(filteredSubCategories[0].id)" />
                                        </div>
                                    </div>
                                    <div class="max-h-52 overflow-y-auto p-1 space-y-0.5 text-xs">
                                        <button type="button" 
                                                @click="select('')"
                                                class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                                :class="!selectedSubCategory ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-500 dark:text-slate-400'">
                                            <span>-- {{ __('messages.product_form_sub_category_none') }} --</span>
                                            <span x-show="!selectedSubCategory">✓</span>
                                        </button>
                                        <template x-for="cat in filteredSubCategories" :key="cat.id">
                                            <button type="button"
                                                    @click="select(cat.id)"
                                                    class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                                    :class="String(selectedSubCategory) === String(cat.id) ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-800 dark:text-slate-200'">
                                                <span class="truncate" x-text="cat.name"></span>
                                                <span class="flex items-center gap-1 shrink-0 ml-2">
                                                    <span x-show="cat.code" class="text-[10px] font-mono px-1 py-0.2 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300" x-text="cat.code"></span>
                                                    <span x-show="String(selectedSubCategory) === String(cat.id)" class="text-violet-600 dark:text-violet-400">✓</span>
                                                </span>
                                            </button>
                                        </template>
                                        <div x-show="filteredSubCategories.length === 0" class="py-3 text-center text-xs text-slate-400">
                                            {{ __('messages.no_results') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

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
                    {{-- 1. Purchase Cost --}}
                    @if (store_can('products.view_cost', $store))
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="{{ $label }}">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-4 h-4 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-black grid place-items-center">1</span>
                                    <span>{{ __('messages.product_form_purchase_cost') }}</span>
                                </span>
                            </label>
                        </div>
                        <input type="number" step="0.01" min="0" name="purchase_cost" x-model="purchaseCost" @input="onPurchaseCostInput()" value="{{ old('purchase_cost', $product->purchase_cost) }}"
                               class="{{ $input }} font-bold" placeholder="10000" />
                        <p class="{{ $hint }}">{{ __('messages.purchase_cost_calc_hint') }}</p>
                        @error('purchase_cost')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    {{-- 2. Retail Price --}}
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="{{ $label }}">
                                <span class="inline-flex items-center gap-1.5">
                                    @if (store_can('products.view_cost', $store))
                                    <span class="w-4 h-4 rounded-full bg-violet-100 dark:bg-violet-900/60 text-violet-700 dark:text-violet-300 text-[10px] font-black grid place-items-center">2</span>
                                    @endif
                                    <span x-text="productType === 'service' ? '{{ __('messages.product_form_service_price_label') }}' : (productType === 'weight_based' ? '{{ __('messages.product_form_weight_price_label') }}' : '{{ __('messages.product_form_retail_price') }}')"></span>
                                    <span class="text-rose-500">*</span>
                                </span>
                            </label>
                        </div>
                        <div class="relative flex rounded-lg">
                            <input type="number" step="0.01" min="0" name="retail_price" x-model="marginRetail" @input="onRetailInput()" value="{{ old('retail_price', $product->retail_price) }}" required
                                   class="flex-1 min-w-0 rounded-l-lg border border-slate-200 dark:border-slate-700 px-3 py-1.5 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition font-bold {{ store_can('products.view_cost', $store) ? 'border-r-0 rounded-r-none' : 'rounded-r-lg' }}"
                                   placeholder="13000" />
                            @if (store_can('products.view_cost', $store))
                            {{-- Preset Mode: Clean Dropdown in right-side slot --}}
                            <div x-show="!retailCustom" class="relative flex items-center">
                                <select @change="onRetailMarkupSelect($event.target.value)"
                                        class="h-full rounded-r-lg border border-l-0 border-slate-200 dark:border-slate-700 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-bold text-xs px-2 sm:px-2.5 py-1.5 cursor-pointer outline-none focus:ring-2 focus:ring-emerald-500 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition"
                                        title="{{ __('messages.markup_percentage_hint') }}">
                                    <option value="" disabled :selected="!retailMarkup">-- % --</option>
                                    @foreach (['5','10','15','20','25','30','35','40','50','60','70','80','100'] as $pct)
                                    <option value="{{ $pct }}" :selected="retailMarkup == '{{ $pct }}'">+{{ $pct }}%</option>
                                    @endforeach
                                    <option value="custom">✏️ {{ __('messages.custom_markup_input') }}</option>
                                </select>
                            </div>

                            {{-- Custom Mode: Editable Input Box right in that dropdown position --}}
                            <div x-show="retailCustom" x-cloak class="relative flex items-center bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-r-lg px-1.5 py-0.5 gap-1">
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold text-xs select-none">+</span>
                                <input type="number" step="0.1" x-model="retailMarkup" @input="onRetailMarkupInput($event.target.value)"
                                       x-ref="retailMarkupInput"
                                       class="w-12 h-7 text-xs font-black text-center rounded bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 border border-slate-200 dark:border-slate-700 focus:ring-1 focus:ring-violet-500 outline-none px-0.5 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                       placeholder="%" title="Type custom markup %" />
                                <span class="text-slate-500 dark:text-slate-400 font-bold text-xs select-none">%</span>
                                <select @change="if($event.target.value !== '') { onRetailMarkupSelect($event.target.value); $event.target.value = ''; }"
                                        class="h-6 px-1 text-[11px] font-bold text-slate-500 dark:text-slate-400 bg-slate-200/70 dark:bg-slate-700/70 rounded border-0 outline-none cursor-pointer hover:bg-slate-300 dark:hover:bg-slate-600"
                                        title="Quick markup presets">
                                    <option value="">▾</option>
                                    @foreach (['5','10','15','20','25','30','35','40','50','60','70','80','100'] as $pct)
                                    <option value="{{ $pct }}">+{{ $pct }}%</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>
                        <p class="{{ $hint }}">{{ __('messages.product_retail_direct_hint') }}</p>
                        @error('retail_price')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- 3. Wholesale Price — optional: blank is saved as the retail price.
                         Hidden from staff without `products.view_cost`. --}}
                    @if (store_can('products.view_cost', $store))
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="{{ $label }}">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-4 h-4 rounded-full bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 text-[10px] font-black grid place-items-center">3</span>
                                    <span x-show="productType === 'service'" x-cloak>{{ __('messages.product_form_wholesale_service_label') }}</span>
                                    <span x-show="productType === 'weight_based'" x-cloak>{{ __('messages.product_form_wholesale_weight_label') }}</span>
                                    <span x-show="productType !== 'service' && productType !== 'weight_based'">{{ __('messages.product_form_wholesale_price') }}</span>
                                </span>
                            </label>
                        </div>
                        <div class="relative flex rounded-lg">
                            <input type="number" step="0.01" min="0" name="wholesale_price" x-model="marginWhole" @input="onWholesaleInput()" value="{{ old('wholesale_price', $product->wholesale_price) }}"
                                   class="flex-1 min-w-0 rounded-l-lg border border-r-0 border-slate-200 dark:border-slate-700 px-3 py-1.5 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition font-bold rounded-r-none"
                                   placeholder="{{ __('messages.product_form_wholesale_placeholder') }}" />
                            {{-- Preset Mode: Clean Dropdown in right-side slot --}}
                            <div x-show="!wholesaleCustom" class="relative flex items-center">
                                <select @change="onWholesaleMarkupSelect($event.target.value)"
                                        class="h-full rounded-r-lg border border-l-0 border-slate-200 dark:border-slate-700 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-bold text-xs px-2 sm:px-2.5 py-1.5 cursor-pointer outline-none focus:ring-2 focus:ring-blue-500 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition"
                                        title="{{ __('messages.markup_percentage_hint') }}">
                                    <option value="" disabled :selected="!wholesaleMarkup">-- % --</option>
                                    @foreach (['3','5','8','10','12','15','20','25','30'] as $pct)
                                    <option value="{{ $pct }}" :selected="wholesaleMarkup == '{{ $pct }}'">+{{ $pct }}%</option>
                                    @endforeach
                                    <option value="custom">✏️ {{ __('messages.custom_markup_input') }}</option>
                                </select>
                            </div>

                            {{-- Custom Mode: Editable Input Box right in that dropdown position --}}
                            <div x-show="wholesaleCustom" x-cloak class="relative flex items-center bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-r-lg px-1.5 py-0.5 gap-1">
                                <span class="text-blue-600 dark:text-blue-400 font-bold text-xs select-none">+</span>
                                <input type="number" step="0.1" x-model="wholesaleMarkup" @input="onWholesaleMarkupInput($event.target.value)"
                                       x-ref="wholesaleMarkupInput"
                                       class="w-12 h-7 text-xs font-black text-center rounded bg-white dark:bg-slate-900 text-blue-700 dark:text-blue-400 border border-slate-200 dark:border-slate-700 focus:ring-1 focus:ring-violet-500 outline-none px-0.5 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                       placeholder="%" title="Type custom wholesale markup %" />
                                <span class="text-slate-500 dark:text-slate-400 font-bold text-xs select-none">%</span>
                                <select @change="if($event.target.value !== '') { onWholesaleMarkupSelect($event.target.value); $event.target.value = ''; }"
                                        class="h-6 px-1 text-[11px] font-bold text-slate-500 dark:text-slate-400 bg-slate-200/70 dark:bg-slate-700/70 rounded border-0 outline-none cursor-pointer hover:bg-slate-300 dark:hover:bg-slate-600"
                                        title="Quick wholesale presets">
                                    <option value="">▾</option>
                                    @foreach (['3','5','8','10','12','15','20','25','30'] as $pct)
                                    <option value="{{ $pct }}">+{{ $pct }}%</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <p class="{{ $hint }}">{{ __('messages.product_form_wholesale_optional_hint') }}</p>
                        @error('wholesale_price')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
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
                        <input type="number" step="0.01" min="0" name="old_price" value="{{ old('old_price', $product->old_price) }}" class="{{ $input }}" placeholder="{{ __('messages.product_form_old_price_placeholder') }}" />
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
                                x-text="productType === 'service' ? @js(__('messages.product_type_service_details')) : (productType === 'digital' ? @js(__('messages.product_type_digital_details')) : @js(__('messages.product_type_weight_details')))">
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
                                    <option value="kg" @selected(($product->specs['unit'] ?? '') === 'kg')>{{ __('messages.unit_kg_label') }}</option>
                                    <option value="g" @selected(($product->specs['unit'] ?? '') === 'g')>{{ __('messages.unit_g_label') }}</option>
                                    <option value="viss" @selected(($product->specs['unit'] ?? '') === 'viss')>{{ __('messages.unit_viss_label') }}</option>
                                    <option value="kyat" @selected(($product->specs['unit'] ?? '') === 'kyat')>{{ __('messages.unit_kyat_label') }}</option>
                                    <option value="lb" @selected(($product->specs['unit'] ?? '') === 'lb')>{{ __('messages.unit_lb_label') }}</option>
                                    <option value="l" @selected(($product->specs['unit'] ?? '') === 'l')>{{ __('messages.unit_l_label') }}</option>
                                    <option value="ml" @selected(($product->specs['unit'] ?? '') === 'ml')>{{ __('messages.unit_ml_label') }}</option>
                                    <option value="pack" @selected(($product->specs['unit'] ?? '') === 'pack')>{{ __('messages.unit_pack_label') }}</option>
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
        <div class="product-form-right-col contents lg:block lg:col-span-4 lg:space-y-1.5 sm:space-y-2">

            {{-- Smart Auto-SKU & Name Generator Card.
                 On mobile (<lg), order: -1 brings this card to the very top of the page.
                 On desktop (lg), it sits at the top of the right column. --}}
            <div class="product-form-smart-sku w-full rounded-lg border border-indigo-200/80 dark:border-indigo-800/80 bg-indigo-50/40 dark:bg-slate-900 shadow-2xs overflow-hidden">
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
                    <div class="space-y-1 rounded-md bg-white dark:bg-slate-800/80 p-2 border border-slate-200/80 dark:border-slate-700/80"
                         x-data="{
                            open: false,
                            search: '',
                            get filteredBrands() {
                                if (!this.search.trim()) return this.brands;
                                const q = this.search.toLowerCase().trim();
                                return this.brands.filter(b => 
                                    (b.name && b.name.toLowerCase().includes(q)) || 
                                    (b.code && b.code.toLowerCase().includes(q))
                                );
                            },
                            select(id) {
                                selectedBrand = id;
                                recomputeSmartSkuAndName();
                                this.open = false;
                                this.search = '';
                            }
                         }" @click.outside="open = false" @keydown.escape="open = false">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.product_form_smart_brand_code') }}</span>
                            <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'brands']) }}" target="_blank" class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold inline-flex items-center gap-0.5">
                                {{ __('messages.product_form_smart_open_master_data') }} ↗
                            </a>
                        </div>

                        <div class="relative">
                            <button type="button"
                                    @click="open = !open; if (open) $nextTick(() => $refs.smartBrandSearch?.focus())"
                                    class="{{ $input }} flex items-center justify-between gap-1 text-left cursor-pointer select-none font-normal"
                                    :class="selectedBrand ? 'text-slate-900 dark:text-slate-100 font-semibold' : 'text-slate-400 dark:text-slate-500'">
                                <span class="truncate" x-text="brands.find(b => String(b.id) === String(selectedBrand)) ? (brands.find(b => String(b.id) === String(selectedBrand)).code ? brands.find(b => String(b.id) === String(selectedBrand)).code + ' — ' : '') + brands.find(b => String(b.id) === String(selectedBrand)).name : '{{ __('messages.product_form_smart_brand_code_pick') }}'"></span>
                                <div class="flex items-center gap-1 shrink-0 ml-1">
                                    <template x-if="selectedBrand">
                                        <span @click.stop="select('')" class="text-slate-400 hover:text-rose-500 p-0.5 rounded cursor-pointer transition text-xs leading-none" title="Clear">✕</span>
                                    </template>
                                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>

                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute z-50 mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-xl overflow-hidden"
                                 x-cloak>
                                <div class="p-1.5 border-b border-slate-100 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-800/90">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-slate-400 text-xs">🔍</span>
                                        <input type="text"
                                               x-ref="smartBrandSearch"
                                               x-model="search"
                                               placeholder="{{ __('messages.search') }}..."
                                               class="w-full pl-7 pr-2 py-1 text-xs rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-violet-500"
                                               @keydown.enter.prevent="if (filteredBrands.length > 0) select(filteredBrands[0].id)" />
                                    </div>
                                </div>
                                <div class="max-h-48 overflow-y-auto p-1 space-y-0.5 text-xs">
                                    <button type="button" 
                                            @click="select('')"
                                            class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                            :class="!selectedBrand ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-500 dark:text-slate-400'">
                                        <span>-- {{ __('messages.product_form_smart_brand_code_pick') }} --</span>
                                        <span x-show="!selectedBrand">✓</span>
                                    </button>
                                    <template x-for="b in filteredBrands" :key="b.id">
                                        <button type="button"
                                                @click="select(b.id)"
                                                class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                                :class="String(selectedBrand) === String(b.id) ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-800 dark:text-slate-200'">
                                            <span class="truncate" x-text="b.name"></span>
                                            <span class="flex items-center gap-1 shrink-0 ml-2">
                                                <span x-show="b.code" class="text-[10px] font-mono font-bold px-1 py-0.2 rounded bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800" x-text="b.code"></span>
                                                <span x-show="String(selectedBrand) === String(b.id)" class="text-violet-600 dark:text-violet-400">✓</span>
                                            </span>
                                        </button>
                                    </template>
                                    <div x-show="filteredBrands.length === 0" class="py-3 text-center text-xs text-slate-400">
                                        {{ __('messages.no_results') }}
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>

                    {{-- 2. Sub Category Code — dropdown fed by Master Data → Categories tab.
                         Main categories are listed first with their sub-categories beneath. --}}
                    <div class="space-y-1 rounded-md bg-white dark:bg-slate-800/80 p-2 border border-slate-200/80 dark:border-slate-700/80"
                         x-data="{
                            open: false,
                            search: '',
                            get filteredOptions() {
                                if (!this.search.trim()) return this.smartCategoryOptions;
                                const q = this.search.toLowerCase().trim();
                                return this.smartCategoryOptions.filter(opt => 
                                    opt.label && opt.label.toLowerCase().includes(q)
                                );
                            },
                            get selectedOptionObj() {
                                return this.smartCategoryOptions.find(opt => String(opt.id) === String(smartCategoryPick));
                            },
                            select(id) {
                                onSmartCategoryPick(id);
                                this.open = false;
                                this.search = '';
                            }
                         }" @click.outside="open = false" @keydown.escape="open = false">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.product_form_smart_subcat_code') }}</span>
                            <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'categories']) }}" target="_blank" class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold inline-flex items-center gap-0.5">
                                {{ __('messages.product_form_smart_open_master_data') }} ↗
                            </a>
                        </div>

                        <div class="relative">
                            <button type="button"
                                    @click="open = !open; if (open) $nextTick(() => $refs.smartCatSearch?.focus())"
                                    class="{{ $input }} flex items-center justify-between gap-1 text-left cursor-pointer select-none font-normal"
                                    :class="smartCategoryPick ? 'text-slate-900 dark:text-slate-100 font-semibold' : 'text-slate-400 dark:text-slate-500'">
                                <span class="truncate" x-text="selectedOptionObj ? (selectedOptionObj.depth ? '↳ ' : '') + selectedOptionObj.label : '{{ __('messages.product_form_smart_subcat_code_pick') }}'"></span>
                                <div class="flex items-center gap-1 shrink-0 ml-1">
                                    <template x-if="smartCategoryPick">
                                        <span @click.stop="select('')" class="text-slate-400 hover:text-rose-500 p-0.5 rounded cursor-pointer transition text-xs leading-none" title="Clear">✕</span>
                                    </template>
                                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>

                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute z-50 mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-xl overflow-hidden"
                                 x-cloak>
                                <div class="p-1.5 border-b border-slate-100 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-800/90">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-slate-400 text-xs">🔍</span>
                                        <input type="text"
                                               x-ref="smartCatSearch"
                                               x-model="search"
                                               placeholder="{{ __('messages.search') }}..."
                                               class="w-full pl-7 pr-2 py-1 text-xs rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-violet-500"
                                               @keydown.enter.prevent="if (filteredOptions.length > 0) select(filteredOptions[0].id)" />
                                    </div>
                                </div>
                                <div class="max-h-52 overflow-y-auto p-1 space-y-0.5 text-xs">
                                    <button type="button" 
                                            @click="select('')"
                                            class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                            :class="!smartCategoryPick ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-500 dark:text-slate-400'">
                                        <span>-- {{ __('messages.product_form_smart_subcat_code_pick') }} --</span>
                                        <span x-show="!smartCategoryPick">✓</span>
                                    </button>
                                    <template x-for="opt in filteredOptions" :key="opt.id">
                                        <button type="button"
                                                @click="select(opt.id)"
                                                class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                                :class="[
                                                    String(smartCategoryPick) === String(opt.id) ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-800 dark:text-slate-200',
                                                    opt.depth ? 'pl-5 text-slate-600 dark:text-slate-300 font-normal' : 'font-bold text-slate-900 dark:text-slate-100'
                                                ]">
                                            <span class="truncate" x-text="(opt.depth ? '↳ ' : '') + opt.label"></span>
                                            <span x-show="String(smartCategoryPick) === String(opt.id)" class="text-violet-600 dark:text-violet-400 shrink-0 ml-2">✓</span>
                                        </button>
                                    </template>
                                    <div x-show="filteredOptions.length === 0" class="py-3 text-center text-xs text-slate-400">
                                        {{ __('messages.no_results') }}
                                    </div>
                                </div>
                            </div>
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

                    {{-- 4. Compatible Models Input (Auto-added to Product Name) --}}
                    <div class="space-y-0.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">{{ __('messages.product_form_compatible_models') }}</label>
                            <span class="text-[9px] text-indigo-600 dark:text-indigo-400 font-bold uppercase tracking-wider">Auto Name</span>
                        </div>
                        <input type="text"
                               x-model="productCompatibleModels"
                               @input="recomputeSmartSkuAndName()"
                               data-test-label="Smart Compatible Models"
                               class="{{ $input }} text-xs"
                               placeholder="{{ __('messages.product_form_compatible_models_placeholder') }}" />
                        <p class="text-[9.5px] text-slate-400 leading-tight">
                            {{ __('messages.product_form_compatible_models_auto_name_note') }}
                        </p>
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

            {{-- Lower Right-Column Cards (Warehouse, Media, Warranty, Specs) --}}
            <div class="product-form-other-cards space-y-1.5 sm:space-y-2">
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
                        <div class="space-y-1" x-data="{
                            open: false,
                            search: '',
                            shelves: {{ json_encode($shelfList->map(fn($sh) => ['name' => $sh->name, 'code' => $sh->code ?? ''])->values()) }},
                            get filteredShelves() {
                                if (!this.search.trim()) return this.shelves;
                                const q = this.search.toLowerCase().trim();
                                return this.shelves.filter(s => 
                                    (s.name && s.name.toLowerCase().includes(q)) || 
                                    (s.code && s.code.toLowerCase().includes(q))
                                );
                            },
                            select(name) {
                                productShelfLocation = name;
                                this.open = false;
                                this.search = '';
                            }
                        }" @click.outside="open = false" @keydown.escape="open = false">
                            <input type="hidden" name="shelf_location" :value="productShelfLocation" :disabled="isStockless" />
                            <div class="relative">
                                <button type="button" 
                                        @click="open = !open; if (open) $nextTick(() => $refs.shelfSearch?.focus())"
                                        class="{{ $input }} flex items-center justify-between gap-1 text-left cursor-pointer select-none font-normal text-xs"
                                        :class="productShelfLocation ? 'text-slate-900 dark:text-slate-100 font-semibold' : 'text-slate-400 dark:text-slate-500'">
                                    <span class="truncate" x-text="productShelfLocation || '-- {{ __('messages.product_form_shelf_location_placeholder') }} --'"></span>
                                    <div class="flex items-center gap-1 shrink-0 ml-1">
                                        <template x-if="productShelfLocation">
                                            <span @click.stop="select('')" class="text-slate-400 hover:text-rose-500 p-0.5 rounded cursor-pointer transition text-xs leading-none" title="Clear">✕</span>
                                        </template>
                                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                </button>

                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute z-50 mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-xl overflow-hidden"
                                     x-cloak>
                                    <div class="p-1.5 border-b border-slate-100 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-800/90">
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-slate-400 text-xs">🔍</span>
                                            <input type="text"
                                                   x-ref="shelfSearch"
                                                   x-model="search"
                                                   placeholder="{{ __('messages.search') }}..."
                                                   class="w-full pl-7 pr-2 py-1 text-xs rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-violet-500"
                                                   @keydown.enter.prevent="if (filteredShelves.length > 0) { select(filteredShelves[0].name) } else if (search.trim()) { select(search.trim()) }" />
                                        </div>
                                    </div>
                                    <div class="max-h-48 overflow-y-auto p-1 space-y-0.5 text-xs">
                                        <button type="button" 
                                                @click="select('')"
                                                class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                                :class="!productShelfLocation ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-500 dark:text-slate-400'">
                                            <span>-- {{ __('messages.product_form_shelf_location_placeholder') }} --</span>
                                            <span x-show="!productShelfLocation">✓</span>
                                        </button>
                                        <template x-for="sh in filteredShelves" :key="sh.name">
                                            <button type="button"
                                                    @click="select(sh.name)"
                                                    class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                                    :class="productShelfLocation === sh.name ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-800 dark:text-slate-200'">
                                                <span class="truncate" x-text="(sh.code ? '[' + sh.code + '] ' : '') + sh.name"></span>
                                                <span x-show="productShelfLocation === sh.name" class="text-violet-600 dark:text-violet-400 shrink-0 ml-2">✓</span>
                                            </button>
                                        </template>
                                        <template x-if="search.trim() && !filteredShelves.some(sh => sh.name.toLowerCase() === search.toLowerCase().trim())">
                                            <button type="button"
                                                    @click="select(search.trim())"
                                                    class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 bg-blue-50/50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 hover:bg-blue-100/80 transition cursor-pointer font-semibold">
                                                <span class="truncate">Use custom: "<span x-text="search.trim()"></span>"</span>
                                                <span class="text-[10px] bg-blue-200 dark:bg-blue-800 px-1 py-0.5 rounded">↵ Enter</span>
                                            </button>
                                        </template>
                                        <div x-show="filteredShelves.length === 0 && !search.trim()" class="py-3 text-center text-xs text-slate-400">
                                            {{ __('messages.no_results') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                        <div class="flex items-stretch gap-1.5" x-data="{
                            open: false,
                            search: '',
                            get filteredSuppliers() {
                                if (!this.search.trim()) return this.suppliers;
                                const q = this.search.toLowerCase().trim();
                                return this.suppliers.filter(s => s.name && s.name.toLowerCase().includes(q));
                            },
                            get selectedSupplierObj() {
                                return this.suppliers.find(s => String(s.id) === String(selectedSupplier));
                            },
                            select(id) {
                                selectedSupplier = id;
                                this.open = false;
                                this.search = '';
                            }
                        }" @click.outside="open = false" @keydown.escape="open = false">
                            <div class="relative flex-1 min-w-0">
                                <input type="hidden" name="supplier_id" :value="selectedSupplier" />

                                <button type="button" 
                                        @click="open = !open; if (open) $nextTick(() => $refs.supplierSearch?.focus())"
                                        class="{{ $input }} flex items-center justify-between gap-1 text-left cursor-pointer select-none font-normal"
                                        :class="selectedSupplier ? 'text-slate-900 dark:text-slate-100 font-semibold' : 'text-slate-400 dark:text-slate-500'">
                                    <span class="truncate" x-text="selectedSupplierObj ? selectedSupplierObj.name : '{{ __('messages.product_form_none') }}'"></span>
                                    <div class="flex items-center gap-1 shrink-0 ml-1">
                                        <template x-if="selectedSupplier">
                                            <span @click.stop="select('')" class="text-slate-400 hover:text-rose-500 p-0.5 rounded cursor-pointer transition text-xs leading-none" title="Clear">✕</span>
                                        </template>
                                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                </button>

                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute z-50 mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-xl overflow-hidden"
                                     x-cloak>
                                    <div class="p-1.5 border-b border-slate-100 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-800/90">
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-slate-400 text-xs">🔍</span>
                                            <input type="text"
                                                   x-ref="supplierSearch"
                                                   x-model="search"
                                                   placeholder="{{ __('messages.search') }}..."
                                                   class="w-full pl-7 pr-2 py-1 text-xs rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-violet-500"
                                                   @keydown.enter.prevent="if (filteredSuppliers.length > 0) select(filteredSuppliers[0].id)" />
                                        </div>
                                    </div>
                                    <div class="max-h-52 overflow-y-auto p-1 space-y-0.5 text-xs">
                                        <button type="button" 
                                                @click="select('')"
                                                class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                                :class="!selectedSupplier ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-500 dark:text-slate-400'">
                                            <span>-- {{ __('messages.product_form_none') }} --</span>
                                            <span x-show="!selectedSupplier">✓</span>
                                        </button>
                                        <template x-for="s in filteredSuppliers" :key="s.id">
                                            <button type="button"
                                                    @click="select(s.id)"
                                                    class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                                    :class="String(selectedSupplier) === String(s.id) ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-800 dark:text-slate-200'">
                                                <span class="truncate" x-text="s.name"></span>
                                                <span x-show="String(selectedSupplier) === String(s.id)" class="text-violet-600 dark:text-violet-400 shrink-0 ml-2">✓</span>
                                            </button>
                                        </template>
                                        <div x-show="filteredSuppliers.length === 0" class="py-3 text-center text-xs text-slate-400">
                                            {{ __('messages.no_results') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

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

                <div class="grid grid-cols-2 gap-1.5 sm:gap-2 items-start">
                    {{-- Main Image --}}
                    <div class="min-w-0">
                        <label class="{{ $label }} truncate">{{ __('messages.product_form_product_image') }}</label>
                        <input type="file" name="image" accept="image/*" @change="previewMain($event)" class="{{ $fileInput }} text-[11px] file:mr-1.5 file:px-2 file:py-1 truncate" />
                        @error('image')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <img x-show="mainPreview" :src="mainPreview" class="h-16 w-16 rounded-lg object-cover border border-slate-200 dark:border-slate-700 shadow-2xs" />
                            @if (!$isEdit && !empty($product->image_path))
                                <img src="{{ asset('storage/' . $product->image_path) }}" class="h-16 w-16 rounded-lg object-cover border border-slate-200 dark:border-slate-700 shadow-2xs" />
                            @endif
                            @if ($isEdit && !empty($product->image_path))
                                <div class="relative h-16 w-16 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs">
                                    <img src="{{ asset('storage/' . $product->image_path) }}" class="h-16 w-16 object-cover" />
                                    <span class="absolute inset-x-0 bottom-0 bg-slate-900/80 py-0.5 text-center text-[9px] font-bold text-white">{{ __('messages.product_form_current_image') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Gallery Images --}}
                    <div class="min-w-0">
                        <label class="{{ $label }} truncate">{{ __('messages.product_form_gallery_images') }}</label>
                        <input type="file" name="gallery_images[]" multiple accept="image/*" @change="previewGallery($event)" class="{{ $fileInput }} text-[11px] file:mr-1.5 file:px-2 file:py-1 truncate" />
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
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

                <div class="grid grid-cols-2 gap-1.5 sm:gap-2">
                    <label class="flex items-center justify-between p-2 rounded-lg border border-slate-200/80 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/70 transition min-w-0">
                        <div class="flex items-center gap-1.5 min-w-0 pr-1">
                            <span class="shrink-0 text-sm">⭐</span>
                            <div class="min-w-0">
                                <div class="text-xs font-black text-slate-900 dark:text-white truncate">{{ __('messages.product_form_featured') }}</div>
                                <div class="text-[9.5px] text-slate-400 truncate leading-tight">Show on homepage</div>
                            </div>
                        </div>
                        <input type="checkbox" name="is_featured" value="1" id="is_featured" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }} class="h-4 w-4 shrink-0 rounded border-slate-300 text-violet-600 focus:ring-violet-500 cursor-pointer" />
                    </label>

                    <label class="flex items-center justify-between p-2 rounded-lg border border-slate-200/80 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/70 transition min-w-0">
                        <div class="flex items-center gap-1.5 min-w-0 pr-1">
                            <span class="shrink-0 text-sm">🌐</span>
                            <div class="min-w-0">
                                <div class="text-xs font-black text-slate-900 dark:text-white truncate">{{ __('messages.product_form_sell_online') }}</div>
                                <div class="text-[9.5px] text-slate-400 truncate leading-tight">{{ __('messages.product_form_sell_online_hint') }}</div>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <input type="hidden" name="is_ecommerce" value="0" />
                            <input type="checkbox" name="is_ecommerce" value="1" id="is_ecommerce" {{ old('is_ecommerce', $product->is_ecommerce ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer" />
                        </div>
                    </label>
                </div>
            </section>
        </div>
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
                    <div class="space-y-1" x-data="{
                        open: false,
                        search: '',
                        warranties: {{ json_encode($warrantyList->map(fn($w) => ['name' => $w->name, 'content' => $w->content ?? ''])->values()) }},
                        get filteredWarranties() {
                            if (!this.search.trim()) return this.warranties;
                            const q = this.search.toLowerCase().trim();
                            return this.warranties.filter(w => 
                                (w.name && w.name.toLowerCase().includes(q)) || 
                                (w.content && w.content.toLowerCase().includes(q))
                            );
                        },
                        select(name) {
                            productWarranty = name;
                            this.open = false;
                            this.search = '';
                        }
                    }" @click.outside="open = false" @keydown.escape="open = false">
                        <input type="hidden" name="warranty" :value="productWarranty" />
                        <div class="relative">
                            <button type="button" 
                                    @click="open = !open; if (open) $nextTick(() => $refs.warrantySearch?.focus())"
                                    class="{{ $input }} flex items-center justify-between gap-1 text-left cursor-pointer select-none font-normal text-xs"
                                    :class="productWarranty ? 'text-slate-900 dark:text-slate-100 font-semibold' : 'text-slate-400 dark:text-slate-500'">
                                <span class="truncate" x-text="productWarranty || '-- {{ __('messages.product_form_warranty_placeholder') }} --'"></span>
                                <div class="flex items-center gap-1 shrink-0 ml-1">
                                    <template x-if="productWarranty">
                                        <span @click.stop="select('')" class="text-slate-400 hover:text-rose-500 p-0.5 rounded cursor-pointer transition text-xs leading-none" title="Clear">✕</span>
                                    </template>
                                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>

                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute z-50 mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-xl overflow-hidden"
                                 x-cloak>
                                <div class="p-1.5 border-b border-slate-100 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-800/90">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-slate-400 text-xs">🔍</span>
                                        <input type="text"
                                               x-ref="warrantySearch"
                                               x-model="search"
                                               placeholder="{{ __('messages.search') }}..."
                                               class="w-full pl-7 pr-2 py-1 text-xs rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-violet-500"
                                               @keydown.enter.prevent="if (filteredWarranties.length > 0) { select(filteredWarranties[0].name) } else if (search.trim()) { select(search.trim()) }" />
                                    </div>
                                </div>
                                <div class="max-h-52 overflow-y-auto p-1 space-y-0.5 text-xs">
                                    <button type="button" 
                                            @click="select('')"
                                            class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                            :class="!productWarranty ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-500 dark:text-slate-400'">
                                        <span>-- {{ __('messages.product_form_warranty_placeholder') }} --</span>
                                        <span x-show="!productWarranty">✓</span>
                                    </button>
                                    <template x-for="w in filteredWarranties" :key="w.name">
                                        <button type="button"
                                                @click="select(w.name)"
                                                class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                                :class="productWarranty === w.name ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-800 dark:text-slate-200'">
                                            <div class="min-w-0 pr-1">
                                                <div class="truncate font-semibold" x-text="w.name"></div>
                                                <div x-show="w.content" class="text-[10px] text-slate-400 truncate" x-text="w.content"></div>
                                            </div>
                                            <span x-show="productWarranty === w.name" class="text-violet-600 dark:text-violet-400 shrink-0 ml-2">✓</span>
                                        </button>
                                    </template>
                                    <template x-if="search.trim() && !filteredWarranties.some(w => w.name.toLowerCase() === search.toLowerCase().trim())">
                                        <button type="button"
                                                @click="select(search.trim())"
                                                class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 bg-blue-50/50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 hover:bg-blue-100/80 transition cursor-pointer font-semibold">
                                            <span class="truncate">Use custom: "<span x-text="search.trim()"></span>"</span>
                                            <span class="text-[10px] bg-blue-200 dark:bg-blue-800 px-1 py-0.5 rounded">↵ Enter</span>
                                        </button>
                                    </template>
                                    <div x-show="filteredWarranties.length === 0 && !search.trim()" class="py-3 text-center text-xs text-slate-400">
                                        {{ __('messages.no_results') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @error('warranty')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                {{-- Return Policy --}}
                <div>
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <label class="{{ $label }} mb-0">{{ __('messages.product_form_return_policy') }}</label>
                        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'return-policies']) }}" target="_blank" class="text-[11px] font-bold text-rose-600 dark:text-rose-400 hover:underline">Presets</a>
                    </div>
                    <div class="space-y-1" x-data="{
                        open: false,
                        search: '',
                        policies: {{ json_encode($returnPolicyList->map(fn($rp) => ['name' => $rp->name, 'content' => $rp->content ?? ''])->values()) }},
                        get filteredPolicies() {
                            if (!this.search.trim()) return this.policies;
                            const q = this.search.toLowerCase().trim();
                            return this.policies.filter(p => 
                                (p.name && p.name.toLowerCase().includes(q)) || 
                                (p.content && p.content.toLowerCase().includes(q))
                            );
                        },
                        select(name) {
                            productReturnPolicy = name;
                            returnPolicyPreview = name;
                            this.open = false;
                            this.search = '';
                        }
                    }" @click.outside="open = false" @keydown.escape="open = false">
                        <input type="hidden" name="return_policy" :value="productReturnPolicy" />
                        <div class="relative">
                            <button type="button" 
                                    @click="open = !open; if (open) $nextTick(() => $refs.policySearch?.focus())"
                                    class="{{ $input }} flex items-center justify-between gap-1 text-left cursor-pointer select-none font-normal text-xs"
                                    :class="productReturnPolicy ? 'text-slate-900 dark:text-slate-100 font-semibold' : 'text-slate-400 dark:text-slate-500'">
                                <span class="truncate" x-text="productReturnPolicy || '-- {{ __('messages.product_form_return_policy_placeholder') }} --'"></span>
                                <div class="flex items-center gap-1 shrink-0 ml-1">
                                    <template x-if="productReturnPolicy">
                                        <span @click.stop="select('')" class="text-slate-400 hover:text-rose-500 p-0.5 rounded cursor-pointer transition text-xs leading-none" title="Clear">✕</span>
                                    </template>
                                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>

                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute z-50 mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-xl overflow-hidden"
                                 x-cloak>
                                <div class="p-1.5 border-b border-slate-100 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-800/90">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-slate-400 text-xs">🔍</span>
                                        <input type="text"
                                               x-ref="policySearch"
                                               x-model="search"
                                               placeholder="{{ __('messages.search') }}..."
                                               class="w-full pl-7 pr-2 py-1 text-xs rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-violet-500"
                                               @keydown.enter.prevent="if (filteredPolicies.length > 0) { select(filteredPolicies[0].name) } else if (search.trim()) { select(search.trim()) }" />
                                    </div>
                                </div>
                                <div class="max-h-52 overflow-y-auto p-1 space-y-0.5 text-xs">
                                    <button type="button" 
                                            @click="select('')"
                                            class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                            :class="!productReturnPolicy ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-500 dark:text-slate-400'">
                                        <span>-- {{ __('messages.product_form_return_policy_placeholder') }} --</span>
                                        <span x-show="!productReturnPolicy">✓</span>
                                    </button>
                                    <template x-for="p in filteredPolicies" :key="p.name">
                                        <button type="button"
                                                @click="select(p.name)"
                                                class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 hover:bg-blue-50/80 dark:hover:bg-blue-950/50 transition cursor-pointer"
                                                :class="productReturnPolicy === p.name ? 'bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-slate-800 dark:text-slate-200'">
                                            <div class="min-w-0 pr-1">
                                                <div class="truncate font-semibold" x-text="p.name"></div>
                                                <div x-show="p.content" class="text-[10px] text-slate-400 truncate" x-text="p.content"></div>
                                            </div>
                                            <span x-show="productReturnPolicy === p.name" class="text-violet-600 dark:text-violet-400 shrink-0 ml-2">✓</span>
                                        </button>
                                    </template>
                                    <template x-if="search.trim() && !filteredPolicies.some(p => p.name.toLowerCase() === search.toLowerCase().trim())">
                                        <button type="button"
                                                @click="select(search.trim())"
                                                class="w-full text-left px-2 py-1.5 rounded-sm flex items-center justify-between border-b border-blue-200/80 dark:border-blue-800/50 bg-blue-50/50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 hover:bg-blue-100/80 transition cursor-pointer font-semibold">
                                            <span class="truncate">Use custom: "<span x-text="search.trim()"></span>"</span>
                                            <span class="text-[10px] bg-blue-200 dark:bg-blue-800 px-1 py-0.5 rounded">↵ Enter</span>
                                        </button>
                                    </template>
                                    <div x-show="filteredPolicies.length === 0 && !search.trim()" class="py-3 text-center text-xs text-slate-400">
                                        {{ __('messages.no_results') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @error('return_policy')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
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
                   class="sf-btn-3d h-10 sm:h-8 px-3.5 flex-1 sm:flex-none justify-center rounded-md font-bold text-xs transition inline-flex items-center cursor-pointer">
                    {{ __('messages.cancel') }}
                </a>

                @if (!$isEdit)
                <button type="submit" name="action" value="save_and_new"
                        class="sf-btn-3d-accent h-10 sm:h-8 px-3.5 flex-1 sm:flex-none justify-center rounded-md font-bold text-xs transition inline-flex items-center gap-1 cursor-pointer"
                        title="{{ __('messages.save_and_continue_tip') }}">
                    <span>⚡</span>
                    <span>{{ __('messages.product_form_save_and_new') }}</span>
                </button>
                @endif

                <button type="submit" name="action" value="save"
                        class="sf-btn-3d-primary h-10 sm:h-8 px-4 flex-1 sm:flex-none justify-center rounded-md font-black text-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <span>💾</span>
                    <span>{{ $isEdit ? __('messages.product_form_update_product') : __('messages.product_form_save_product') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
