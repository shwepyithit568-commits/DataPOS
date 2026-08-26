{{-- Shared product form body. Requires Alpine state on create/edit wrappers. --}}
@php
    $input = 'w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition';
    $label = 'block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1';
    $hint  = 'mt-1 text-[11px] text-slate-400 dark:text-slate-500';
    $section = 'w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3';
    $fileInput = 'block w-full text-xs text-slate-600 dark:text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-violet-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-slate-800 dark:file:text-violet-300 rounded-lg border border-slate-200 dark:border-slate-700 p-1.5 bg-slate-50 dark:bg-slate-800/60';
@endphp

<div class="space-y-2 sm:space-y-2.5">
    {{-- 0. Product Type Switcher (Standard, Serialized, Variant, Service) --}}
    <section class="w-full rounded-lg bg-gradient-to-r from-violet-500/10 via-indigo-500/5 to-sky-500/10 dark:from-violet-950/40 dark:via-indigo-950/20 dark:to-sky-950/40 border border-violet-200/80 dark:border-violet-800/60 p-3 sm:p-4 shadow-2xs space-y-2.5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-violet-100 dark:border-violet-900/60 pb-2.5">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-violet-600 text-white grid place-items-center text-sm font-bold shadow-xs">
                    ⚡
                </span>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                        {{ __('messages.product_form_product_type') }}
                    </h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.product_type_hint') }}</p>
                </div>
            </div>
            <input type="hidden" name="product_type" :value="productType" />
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-0.5">
            <button type="button" @click="productType = 'standard'"
                :class="productType === 'standard' ? 'bg-violet-600 text-white shadow-md shadow-violet-500/25 border-violet-600' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-800 hover:border-violet-300'"
                class="flex flex-col items-center justify-center p-2.5 rounded-lg border text-center transition-all duration-150">
                <span class="text-lg mb-0.5">📦</span>
                <span class="text-xs font-black">{{ __('messages.product_type_standard') }}</span>
            </button>

            <button type="button" @click="productType = 'serialized'"
                :class="productType === 'serialized' ? 'bg-violet-600 text-white shadow-md shadow-violet-500/25 border-violet-600' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-800 hover:border-violet-300'"
                class="flex flex-col items-center justify-center p-2.5 rounded-lg border text-center transition-all duration-150">
                <span class="text-lg mb-0.5">📱</span>
                <span class="text-xs font-black">{{ __('messages.product_type_serialized') }}</span>
            </button>

            <button type="button" @click="productType = 'variant'"
                :class="productType === 'variant' ? 'bg-violet-600 text-white shadow-md shadow-violet-500/25 border-violet-600' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-800 hover:border-violet-300'"
                class="flex flex-col items-center justify-center p-2.5 rounded-lg border text-center transition-all duration-150">
                <span class="text-lg mb-0.5">🔀</span>
                <span class="text-xs font-black">{{ __('messages.product_type_variant') }}</span>
            </button>

            <button type="button" @click="productType = 'service'"
                :class="productType === 'service' ? 'bg-violet-600 text-white shadow-md shadow-violet-500/25 border-violet-600' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-800 hover:border-violet-300'"
                class="flex flex-col items-center justify-center p-2.5 rounded-lg border text-center transition-all duration-150">
                <span class="text-lg mb-0.5">🛠️</span>
                <span class="text-xs font-black">{{ __('messages.product_type_service') }}</span>
            </button>
        </div>
    </section>

    {{-- 1. Core Information & Smart Auto-SKU Card --}}
    <section class="{{ $section }}">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5 mb-1">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center text-sm font-bold">
                    📦
                </span>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                        {{ __('messages.product_form_core_section') }}
                    </h2>
                    <p class="text-[11px] text-slate-400">{{ __('messages.product_form_core_section_hint') }}</p>
                </div>
            </div>

            {{-- Auto-SKU Switch Toggle --}}
            <label class="inline-flex cursor-pointer select-none items-center gap-1.5 px-2.5 py-1 rounded-lg border border-violet-200 dark:border-violet-800 bg-violet-50/70 dark:bg-violet-950/40 text-xs font-black text-violet-700 dark:text-violet-300 transition hover:bg-violet-100">
                <input type="checkbox" name="auto_sku" value="1" x-model="autoSku" @change="recomputeSmartSkuAndName()" class="rounded border-violet-300 text-violet-600 focus:ring-violet-500" />
                <span>⚡ {{ __('messages.product_form_auto_sku') }}</span>
            </label>
        </div>

@php
    $skuExtraList = isset($masterPresets) && $masterPresets->where('type', 'connector_spec')->isNotEmpty()
        ? $masterPresets->where('type', 'connector_spec')->values()
        : collect([
            (object)['code' => 'TC', 'name' => 'Type-C'],
            (object)['code' => 'MC', 'name' => 'Micro USB'],
            (object)['code' => 'IP', 'name' => 'Lightning / iPhone'],
            (object)['code' => '3.5MM', 'name' => '3.5mm Aux'],
            (object)['code' => '3IN1', 'name' => '3-in-1 Combo'],
            (object)['code' => 'OTG', 'name' => 'OTG Adapter'],
            (object)['code' => '10000MAH', 'name' => '10000mAh'],
            (object)['code' => '20000MAH', 'name' => '20000mAh'],
            (object)['code' => '20W', 'name' => '20W Fast Charge'],
            (object)['code' => '65W', 'name' => '65W GaN Fast'],
            (object)['code' => '100W', 'name' => '100W PD Ultra'],
            (object)['code' => 'ORG', 'name' => 'Original (မူရင်းအစစ်)'],
            (object)['code' => 'AAA', 'name' => 'AAA Quality (အဆင့်မြင့်)'],
            (object)['code' => 'OCA', 'name' => 'OCA Glass'],
            (object)['code' => 'SIL', 'name' => 'Silicone Case'],
            (object)['code' => 'CLR', 'name' => 'Clear Case'],
        ]);

    $skuColorList = isset($masterPresets) && $masterPresets->where('type', 'color')->isNotEmpty()
        ? $masterPresets->where('type', 'color')->values()
        : collect([
            (object)['code' => 'BLK', 'name' => 'Black (အနက်)', 'color_hex' => '#000000'],
            (object)['code' => 'WHT', 'name' => 'White (အဖြူ)', 'color_hex' => '#FFFFFF'],
            (object)['code' => 'BLU', 'name' => 'Blue (အပြာ)', 'color_hex' => '#2563EB'],
            (object)['code' => 'RED', 'name' => 'Red (အနီ)', 'color_hex' => '#DC2626'],
            (object)['code' => 'GLD', 'name' => 'Gold (ရွှေရောင်)', 'color_hex' => '#F59E0B'],
            (object)['code' => 'SLV', 'name' => 'Silver (ငွေရောင်)', 'color_hex' => '#94A3B8'],
            (object)['code' => 'GRY', 'name' => 'Gray (မီးခိုး)', 'color_hex' => '#64748B'],
            (object)['code' => 'PUR', 'name' => 'Purple (ခရမ်း)', 'color_hex' => '#9333EA'],
            (object)['code' => 'GRN', 'name' => 'Green (အစိမ်း)', 'color_hex' => '#16A34A'],
            (object)['code' => 'PNK', 'name' => 'Pink (ပန်းရောင်)', 'color_hex' => '#F472B6'],
        ]);

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

        {{-- Smart Generator Real-time Builder (Visible when autoSku is enabled) --}}
        <div x-show="autoSku" x-cloak class="rounded-lg border border-indigo-200/80 dark:border-indigo-800/80 bg-indigo-50/50 dark:bg-slate-800/90 p-3 sm:p-3.5 space-y-2.5 shadow-2xs backdrop-blur-xs transition-colors">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-indigo-100 dark:border-indigo-900/60 pb-2">
                <h3 class="text-xs font-black uppercase tracking-wide text-indigo-950 dark:text-indigo-200 flex items-center gap-1.5">
                    <span class="w-5 h-5 rounded bg-indigo-600 text-white grid place-items-center text-xs shadow-2xs">✨</span>
                    <span>{{ __('messages.product_form_smart_sku_engine') }} <span class="text-[10px] font-normal text-slate-500 dark:text-slate-400">({{ __('messages.product_form_formula_hint') }})</span></span>
                </h3>
                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-100/70 dark:bg-indigo-950/70 px-2 py-0.5 rounded-lg border border-indigo-200 dark:border-indigo-800/70">
                    ⚡ {{ __('messages.product_form_fast_presets') }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5 sm:gap-3">
                {{-- 1. Model Code (Manual typing per product as requested) --}}
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.product_form_smart_model_code') }}</label>
                        <span class="text-[10px] text-slate-400 font-medium">{{ __('messages.product_form_type_from_device') }}</span>
                    </div>
                    <input type="text" x-model="productModelCode" @input="recomputeSmartSkuAndName()" class="{{ $input }} uppercase text-xs font-mono font-bold" placeholder="{{ __('messages.product_form_smart_model_code_placeholder') }}" />
                    <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ __('messages.product_form_model_code_examples') }}</p>
                </div>

                {{-- 2. Connector / Spec Code with Compact Dropdown & Manual Input --}}
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.product_form_smart_extra_code') }}</label>
                        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'connectors']) }}" target="_blank" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold hover:underline">{{ __('messages.product_form_manage_presets') }}</a>
                    </div>
                    <div class="flex gap-1.5">
                        <select x-model="productExtraCode" @change="recomputeSmartSkuAndName()" class="{{ $input }} cursor-pointer text-xs flex-1">
                            <option value="">-- {{ __('messages.product_form_smart_extra_code_placeholder') }} --</option>
                            @foreach ($skuExtraList as $ex)
                                <option value="{{ $ex->code }}">{{ $ex->code }} ({{ $ex->name }})</option>
                            @endforeach
                        </select>
                        <input type="text" x-model="productExtraCode" @input="recomputeSmartSkuAndName()" class="w-20 sm:w-24 rounded-lg border border-slate-200 dark:border-slate-700 px-2.5 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-mono font-bold uppercase focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition" placeholder="Manual" title="Custom code" />
                    </div>
                </div>

                {{-- 3. Color Code with Compact Dropdown & Manual Input --}}
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.product_form_smart_color_code') }}</label>
                        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'colors']) }}" target="_blank" class="text-[11px] text-amber-600 dark:text-amber-400 font-bold hover:underline">{{ __('messages.product_form_manage_presets') }}</a>
                    </div>
                    <div class="flex gap-1.5">
                        <select x-model="productColorCode" @change="recomputeSmartSkuAndName()" class="{{ $input }} cursor-pointer text-xs flex-1">
                            <option value="">-- {{ __('messages.product_form_smart_color_code_placeholder') }} --</option>
                            @foreach ($skuColorList as $c)
                                <option value="{{ $c->code }}">{{ $c->code }} - {{ $c->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" x-model="productColorCode" @input="recomputeSmartSkuAndName()" class="w-20 sm:w-24 rounded-lg border border-slate-200 dark:border-slate-700 px-2.5 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-mono font-bold uppercase focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition" placeholder="Manual" title="Custom code" />
                    </div>
                </div>

                {{-- 4. Compatible Models (အလားတူမော်ဒယ်များ - Auto-fills in Name, NOT SKU) --}}
                <div class="space-y-1 md:col-span-3 pt-2 border-t border-indigo-100/70 dark:border-indigo-900/40">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                            <span>📱</span>
                            <span>{{ __('messages.product_form_compatible_models') }}</span>
                        </label>
                        <span class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400">
                            ✨ {{ __('messages.product_form_compatible_models_auto_name_note') }}
                        </span>
                    </div>
                    <input type="text" x-model="productCompatibleModels" @input="recomputeSmartSkuAndName()" class="{{ $input }}" placeholder="{{ __('messages.product_form_compatible_models_placeholder') }}" />
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ __('messages.product_form_compatible_models_hint') }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-indigo-100 dark:border-indigo-900/60 text-xs">
                <div class="flex items-center gap-2 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-indigo-200/80 dark:border-indigo-800/80 shadow-2xs">
                    <span class="text-slate-500 dark:text-slate-400 font-bold text-xs">{{ __('messages.product_form_generated_sku_preview') }}:</span>
                    <span class="font-mono font-black text-indigo-600 dark:text-indigo-400 text-xs sm:text-sm tracking-wider" x-text="productSku || 'SKU-PENDING'"></span>
                </div>
                <div class="flex items-center gap-2 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-indigo-200/80 dark:border-indigo-800/80 shadow-2xs">
                    <span class="text-slate-500 dark:text-slate-400 font-bold text-xs">{{ __('messages.product_form_generated_name_preview') }}:</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm" x-text="productNameInput || 'Product Name'"></span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-2.5 sm:gap-3 md:grid-cols-2">
            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_name') }} <span class="text-rose-500">*</span></label>
                <input type="text" name="name" x-model="productNameInput" required class="{{ $input }}" placeholder="{{ __('messages.product_form_name_placeholder') }}" />
                @error('name')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <div class="flex items-center justify-between gap-3 mb-1">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('messages.product_form_sku') }} <span class="text-rose-500" x-show="!autoSku">*</span></label>
                    <span x-show="autoSku" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400">{{ __('messages.product_stock_auto_managed') }}</span>
                </div>
                <input type="text" name="sku" x-model="productSku" :disabled="autoSku" :required="!autoSku" class="{{ $input }} font-mono disabled:cursor-not-allowed disabled:bg-slate-100 dark:disabled:bg-slate-800 disabled:text-slate-500 dark:disabled:text-slate-400" placeholder="{{ __('messages.product_form_sku_placeholder') }}" />
                <p class="{{ $hint }}" x-show="autoSku" x-cloak>{{ __('messages.product_form_auto_sku_hint') }}</p>
                @error('sku')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <div class="flex items-center justify-between gap-3 mb-1">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('messages.product_form_barcode') }}</label>
                    <span class="text-[10px] text-slate-400 font-medium">{{ __('messages.product_form_barcode_supported') }}</span>
                </div>
                <div class="relative">
                    <input type="text" name="barcode" x-model="productBarcode" value="{{ old('barcode', $product->barcode) }}" class="{{ $input }} pl-9 font-mono" placeholder="{{ __('messages.product_form_barcode_placeholder') }}" />
                    <span class="absolute left-3 top-2.5 text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </span>
                </div>
                @error('barcode')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <div class="flex items-center justify-between gap-3 mb-1">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('messages.product_form_brand') }}</label>
                    <button type="button" @click="brandModalOpen = true" class="text-xs font-bold text-violet-600 hover:underline dark:text-violet-400">+ {{ __('messages.product_form_quick_brand') }}</button>
                </div>
                <select name="brand_id" x-model="selectedBrand" @change="recomputeSmartSkuAndName()" x-init="$nextTick(() => $el.value = selectedBrand)" class="{{ $input }} cursor-pointer">
                    <option value="">{{ __('messages.product_form_none') }}</option>
                    <template x-for="b in brands" :key="b.id">
                        <option :value="b.id" x-text="b.name + (b.code ? ' [' + b.code + ']' : '')"></option>
                    </template>
                </select>
                @error('brand_id')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <div class="flex items-center justify-between gap-3 mb-1">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('messages.product_form_main_category') }}</label>
                    <button type="button" @click="newCategoryParent = ''; categoryModalOpen = true" class="text-xs font-bold text-violet-600 hover:underline dark:text-violet-400">+ {{ __('messages.product_form_quick_category') }}</button>
                </div>
                <select x-model="selectedMainCategory" @change="onMainCategoryChange(); recomputeSmartSkuAndName()" x-init="$nextTick(() => $el.value = selectedMainCategory)" class="{{ $input }} cursor-pointer">
                    <option value="">{{ __('messages.product_form_main_category_none') }}</option>
                    <template x-for="cat in mainCategories" :key="cat.id">
                        <option :value="cat.id" x-text="cat.name + (cat.code ? ' [' + cat.code + ']' : '')"></option>
                    </template>
                </select>
            </div>

            <div>
                <div class="flex items-center justify-between gap-3 mb-1">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('messages.product_form_sub_category') }}</label>
                    <button type="button" @click="newCategoryParent = selectedMainCategory; categoryModalOpen = true" :disabled="!selectedMainCategory" class="text-xs font-bold text-violet-600 hover:underline disabled:cursor-not-allowed disabled:opacity-40 dark:text-violet-400">+ {{ __('messages.product_form_quick_category') }}</button>
                </div>
                <select x-model="selectedSubCategory" @change="recomputeSmartSkuAndName()" :disabled="!selectedMainCategory" x-init="$nextTick(() => $el.value = selectedSubCategory)" class="{{ $input }} cursor-pointer disabled:cursor-not-allowed disabled:opacity-60">
                    <option value="" x-text="!selectedMainCategory ? '{{ __('messages.product_form_sub_category_choose_first') }}' : (subCategories.length ? '{{ __('messages.product_form_sub_category_none') }}' : '{{ __('messages.product_form_no_sub_categories') }}')"></option>
                    <template x-for="cat in subCategories" :key="cat.id">
                        <option :value="cat.id" x-text="cat.name + (cat.code ? ' [' + cat.code + ']' : '')"></option>
                    </template>
                </select>
                <input type="hidden" name="category_id" :value="selectedSubCategory || selectedMainCategory" />
            </div>

            <div x-show="!autoSku" class="md:col-span-2">
                <label class="{{ $label }}">{{ __('messages.product_form_compatible_models') }}</label>
                <input type="text" x-model="productCompatibleModels" @input="recomputeSmartSkuAndName()" class="{{ $input }}" placeholder="{{ __('messages.product_form_compatible_models_placeholder') }}" />
                <p class="{{ $hint }}">{{ __('messages.product_form_compatible_models_placeholder') }}</p>
                @error('compatible_models')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            {{-- Hidden input to ensure compatible_models value is always submitted in form payload --}}
            <input type="hidden" name="compatible_models" :value="productCompatibleModels" />
        </div>
    </section>

    {{-- 2. Pricing & Promotion Card --}}
    <section class="{{ $section }}">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5 mb-1">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-sm font-bold">
                    🏷️
                </span>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                        {{ __('messages.product_form_pricing_section') }}
                    </h2>
                    <p class="text-[11px] text-slate-400">{{ __('messages.product_form_pricing_section_hint') }}</p>
                </div>
            </div>
            <div class="rounded-lg px-2.5 py-1 text-xs font-black border"
                 :class="marginPercent >= 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'">
                {{ __('messages.product_form_margin') }}: <span x-text="marginPercent + '%'">0%</span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-2.5 sm:gap-3 md:grid-cols-2">
            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_retail_price') }} <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" min="0" name="retail_price" x-model="marginRetail" value="{{ old('retail_price', $product->retail_price) }}" required class="{{ $input }}" placeholder="1990000" />
                @error('retail_price')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_wholesale_price') }} <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" min="0" name="wholesale_price" x-model="marginWhole" value="{{ old('wholesale_price', $product->wholesale_price) }}" required class="{{ $input }}" placeholder="{{ __('messages.product_form_wholesale_placeholder') }}" />
                @error('wholesale_price')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_old_price') }}</label>
                <input type="number" step="0.01" min="0" name="old_price" value="{{ old('old_price', $product->old_price) }}" class="{{ $input }}" placeholder="{{ __('messages.product_form_old_price_placeholder') }}" />
                <p class="{{ $hint }}">{{ __('messages.product_form_old_price_hint') }}</p>
                @error('old_price')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 gap-2.5 sm:gap-3 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}">{{ __('messages.product_form_sale_starts_at') }}</label>
                    <input type="datetime-local" name="sale_starts_at" value="{{ old('sale_starts_at', optional($product->sale_starts_at)->format('Y-m-d\TH:i')) }}" class="{{ $input }}" />
                    <p class="{{ $hint }}">{{ __('messages.product_form_sale_starts_hint') }}</p>
                    @error('sale_starts_at')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('messages.product_form_sale_ends_at') }}</label>
                    <input type="datetime-local" name="sale_ends_at" value="{{ old('sale_ends_at', optional($product->sale_ends_at)->format('Y-m-d\TH:i')) }}" class="{{ $input }}" />
                    <p class="{{ $hint }}">{{ __('messages.product_form_sale_ends_hint') }}</p>
                    @error('sale_ends_at')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Inventory, Warehouse & Shelf Location Card --}}
    <section class="{{ $section }}">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5 mb-1">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 grid place-items-center text-sm font-bold">
                    🏢
                </span>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                        {{ __('messages.product_form_inventory_section') }}
                    </h2>
                    <p class="text-[11px] text-slate-400">{{ __('messages.product_form_inventory_section_hint') }}</p>
                </div>
            </div>
        </div>

        <div x-show="productType === 'service'" x-cloak class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-lg text-xs font-bold text-amber-800 dark:text-amber-300">
            {{ __('messages.product_form_service_item_notice') }}
        </div>

        <div class="grid grid-cols-1 gap-2.5 sm:gap-3 md:grid-cols-2">
            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_warehouse') }}</label>
                <select name="warehouse_id" x-model="productWarehouseId" class="{{ $input }} cursor-pointer">
                    <option value="">{{ __('messages.product_form_warehouse_none') }}</option>
                    <template x-for="w in warehouses" :key="w.id">
                        <option :value="w.id" x-text="w.name"></option>
                    </template>
                </select>
                @error('warehouse_id')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <div class="flex items-center justify-between gap-3 mb-1">
                    <label class="{{ $label }} mb-0">{{ __('messages.product_form_shelf_location') }}</label>
                    <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'shelves']) }}" target="_blank" class="text-[11px] font-bold text-purple-600 dark:text-purple-400 hover:underline">{{ __('messages.product_form_manage_presets') }}</a>
                </div>
                <div class="flex gap-1.5">
                    <select x-model="productShelfLocation" class="{{ $input }} cursor-pointer text-xs flex-1">
                        <option value="">-- {{ __('messages.product_form_shelf_location_placeholder') }} --</option>
                        @foreach ($shelfList as $sh)
                            <option value="{{ $sh->name }}">{{ $sh->code ? '[' . $sh->code . '] ' : '' }}{{ $sh->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="shelf_location" x-model="productShelfLocation" class="w-24 sm:w-28 rounded-lg border border-slate-200 dark:border-slate-700 px-2.5 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition" placeholder="Manual" title="Custom shelf location" />
                </div>
                @error('shelf_location')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_reorder_level') }}</label>
                <input type="number" step="0.001" min="0" name="reorder_level" value="{{ old('reorder_level', $product->reorder_level) }}" class="{{ $input }}" placeholder="10" />
                <p class="{{ $hint }}">{{ __('messages.product_form_reorder_level_hint') }}</p>
                @error('reorder_level')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            @if (!$isEdit)
            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_initial_stock') }}</label>
                <input type="number" step="0.001" min="0" name="initial_stock" value="{{ old('initial_stock') }}" class="{{ $input }}" placeholder="0" />
                <p class="{{ $hint }}">{{ __('messages.product_form_initial_stock_hint') }}</p>
                @error('initial_stock')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
            @else
            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_initial_stock') }}</label>
                <p class="mt-1 rounded-lg bg-slate-50 dark:bg-slate-800/60 p-2.5 text-xs leading-5 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">{{ __('messages.product_form_initial_stock_edit_note') }}</p>
            </div>
            @endif

            <div>
                <div class="flex items-center justify-between gap-3 mb-1">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('messages.product_form_supplier') }}</label>
                    <button type="button" @click="supplierModalOpen = true" class="text-xs font-bold text-violet-600 hover:underline dark:text-violet-400">{{ __('messages.product_form_quick_supplier') }}</button>
                </div>
                <select name="supplier_id" x-model="selectedSupplier" x-init="$nextTick(() => $el.value = selectedSupplier)" class="{{ $input }} cursor-pointer">
                    <option value="">{{ __('messages.product_form_none') }}</option>
                    <template x-for="s in suppliers" :key="s.id">
                        <option :value="s.id" x-text="s.name"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_purchase_cost') }}</label>
                <input type="number" step="0.01" min="0" name="purchase_cost" value="{{ old('purchase_cost', $product->purchase_cost) }}" class="{{ $input }}" placeholder="1500000" />
                <p class="{{ $hint }}">{{ __('messages.product_form_purchase_cost_hint') }}</p>
                @error('purchase_cost')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    {{-- 4. Media & Gallery Card --}}
    <section class="{{ $section }}">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5 mb-1">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-sm font-bold">
                    🖼️
                </span>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                        {{ __('messages.product_form_media_section') }}
                    </h2>
                    <p class="text-[11px] text-slate-400">{{ __('messages.product_form_media_section_hint') }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-2.5 sm:gap-3 md:grid-cols-2">
            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_product_image') }}</label>
                <input type="file" name="image" accept="image/*" @change="previewMain($event)" class="{{ $fileInput }}" />
                <p class="{{ $hint }}">{{ __('messages.product_form_product_image_hint', ['size' => 10]) }}</p>
                @error('image')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                <div class="mt-2.5 flex flex-wrap gap-2">
                    <img x-show="mainPreview" :src="mainPreview" class="h-24 w-24 rounded-lg object-cover border border-slate-200 dark:border-slate-700 shadow-2xs" />
                    @if (!$isEdit && !empty($product->image_path))
                        <img src="{{ asset('storage/' . $product->image_path) }}" class="h-24 w-24 rounded-lg object-cover border border-slate-200 dark:border-slate-700 shadow-2xs" />
                    @endif
                    @if ($isEdit && !empty($product->image_path))
                        <div class="relative h-24 w-24 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <img src="{{ asset('storage/' . $product->image_path) }}" class="h-24 w-24 object-cover" />
                            <span class="absolute inset-x-0 bottom-0 bg-slate-900/80 py-0.5 text-center text-[9px] font-bold text-white">{{ __('messages.product_form_current_image') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_gallery_images') }}</label>
                <input type="file" name="gallery_images[]" multiple accept="image/*" @change="previewGallery($event)" class="{{ $fileInput }}" />
                <p class="{{ $hint }}">{{ __('messages.product_form_gallery_hint', ['count' => 4, 'size' => 10]) }}</p>
                <div class="mt-2.5 flex flex-wrap gap-2">
                    <template x-for="(g, gi) in galleryPreviews" :key="gi">
                        <img :src="g" class="h-24 w-24 rounded-lg object-cover border border-slate-200 dark:border-slate-700 shadow-2xs" />
                    </template>
                </div>
            </div>
        </div>
    </section>

    {{-- 5. Warranty & Policy Card --}}
    <section class="{{ $section }}">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5 mb-1">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 grid place-items-center text-sm font-bold">
                    🛡️
                </span>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                        {{ __('messages.product_form_policy_section') }}
                    </h2>
                    <p class="text-[11px] text-slate-400">{{ __('messages.product_form_policy_section_hint') }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-2.5 sm:gap-3 md:grid-cols-2">
            <div>
                <div class="flex items-center justify-between gap-3 mb-1">
                    <label class="{{ $label }} mb-0">{{ __('messages.product_form_warranty') }}</label>
                    <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'warranties']) }}" target="_blank" class="text-[11px] font-bold text-sky-600 dark:text-sky-400 hover:underline">{{ __('messages.product_form_manage_presets') }}</a>
                </div>
                <div class="flex gap-1.5">
                    <select x-model="productWarranty" class="{{ $input }} cursor-pointer text-xs flex-1">
                        <option value="">-- {{ __('messages.product_form_warranty_placeholder') }} --</option>
                        @foreach ($warrantyList as $w)
                            <option value="{{ $w->name }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="warranty" x-model="productWarranty" class="w-24 sm:w-28 rounded-lg border border-slate-200 dark:border-slate-700 px-2.5 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition" placeholder="Manual" title="Custom warranty" />
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between gap-3 mb-1">
                    <label class="{{ $label }} mb-0">{{ __('messages.product_form_return_policy') }}</label>
                    <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'return-policies']) }}" target="_blank" class="text-[11px] font-bold text-rose-600 dark:text-rose-400 hover:underline">{{ __('messages.product_form_manage_presets') }}</a>
                </div>
                <select @change="if($event.target.value){ let el = document.getElementById('product_return_policy_input'); if(el){ el.value = $event.target.value; el.dispatchEvent(new Event('input')); } }" class="{{ $input }} cursor-pointer text-xs mb-1.5">
                    <option value="">-- {{ __('messages.product_form_fill_template') }} --</option>
                    @foreach ($returnPolicyList as $rp)
                        <option value="{{ addslashes($rp->content) }}">📋 {{ $rp->name }}</option>
                    @endforeach
                </select>
                <textarea name="return_policy" id="product_return_policy_input" rows="2" @input="refreshReturnPolicyPreview()" class="{{ $input }}" placeholder="{{ __('messages.product_form_return_policy_placeholder') }}">{{ old('return_policy', $product->return_policy) }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label class="{{ $label }}">{{ __('messages.product_form_description') }}</label>
                <x-richtext-editor name="description" :value="old('description', $product->description)" :rows="160" placeholder="{{ __('messages.product_form_description_placeholder') }}" />
                <p class="{{ $hint }}">{{ __('messages.product_form_description_hint') }}</p>
                @error('description')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="{{ $label }}">{{ __('messages.product_form_meta_description') }}</label>
                <textarea name="meta_description" rows="2" maxlength="1000" @input="metaDescLen = $el.value.length" class="{{ $input }}" placeholder="{{ __('messages.product_form_meta_placeholder') }}">{{ old('meta_description', $product->meta_description) }}</textarea>
                <div class="mt-1 flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                    <p class="{{ $hint }}">{{ __('messages.product_form_meta_helper') }}</p>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500" :class="metaDescLen > 160 ? 'text-rose-500 dark:text-rose-400' : ''">
                            <span x-text="metaDescLen"></span>/160
                        </span>
                    </div>
                </div>
                <p class="{{ $hint }}">{{ __('messages.product_form_meta_empty_fallback') }}</p>
                @error('meta_description')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    {{-- 6. Product Variants & Presets Card --}}
    <section class="{{ $section }}">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 border-b border-slate-100 dark:border-slate-800 pb-2.5 mb-1">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center text-sm font-bold">
                    🎨
                </span>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>{{ __('messages.product_form_variants_section') }}</span>
                        <span class="text-[11px] font-semibold text-slate-400">({{ __('messages.product_form_variants_optional') }})</span>
                    </h2>
                    <p class="text-[11px] text-slate-400">{{ __('messages.product_form_variants_hint') }}</p>
                </div>
            </div>

            <button type="button" @click="addVariant()" class="px-3 py-1.5 rounded-lg text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-sm transition flex items-center gap-1 self-start sm:self-auto">
                <span>+</span>
                <span>{{ __('messages.product_form_add_variant') }}</span>
            </button>
        </div>

        @error('variants')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror

        {{-- Preset Selector Box --}}
        <div class="rounded-lg border border-violet-100 dark:border-violet-900/60 bg-violet-50/50 dark:bg-violet-950/20 p-3 space-y-2.5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h3 class="text-xs font-black uppercase tracking-wide text-violet-900 dark:text-violet-200 flex items-center gap-1.5">
                    <span>⚡</span>
                    <span>{{ __('messages.product_form_variant_preset_card') }}</span>
                </h3>
                <a href="{{ route('store.admin.variant-presets.index', ['store_slug' => $store->slug]) }}" class="text-xs font-bold text-violet-600 hover:underline dark:text-violet-400">{{ __('messages.product_form_open_variant_settings') }}</a>
            </div>

            @if (($variantPresets ?? collect())->isNotEmpty())
                <div class="grid grid-cols-1 items-end gap-2.5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto_auto]">
                    <div>
                        <label class="text-[11px] font-bold uppercase text-violet-700 dark:text-violet-300" data-test-label="Preset 1">{{ __('messages.product_form_preset_1') }}</label>
                        <select x-model="selectedVariantPresetId" class="mt-1 w-full cursor-pointer rounded-lg border border-violet-200 bg-white px-3 py-1.5 text-xs sm:text-sm font-semibold text-slate-900 dark:border-violet-800 dark:bg-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500/40">
                            <option value="">{{ __('messages.product_form_choose_preset') }}</option>
                            <template x-for="preset in filteredVariantPresets" :key="preset.id">
                                <option :value="preset.id" x-text="preset.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="text-[11px] font-bold uppercase text-violet-700 dark:text-violet-300" data-test-label="Preset 2 (optional)">{{ __('messages.product_form_preset_2') }}</label>
                        <select x-model="selectedVariantPresetIdTwo" class="mt-1 w-full cursor-pointer rounded-lg border border-violet-200 bg-white px-3 py-1.5 text-xs sm:text-sm font-semibold text-slate-900 dark:border-violet-800 dark:bg-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500/40">
                            <option value="">{{ __('messages.product_form_combine_with') }}</option>
                            <template x-for="preset in filteredVariantPresets" :key="preset.id">
                                <option :value="preset.id" x-text="preset.name"></option>
                            </template>
                        </select>
                    </div>

                    <button type="button" @click="applyVariantPreset()" :disabled="!selectedVariantPresetId" data-test-label="Apply Preset" class="px-3.5 py-2 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-xs font-black shadow-sm disabled:cursor-not-allowed disabled:opacity-50 transition">
                        {{ __('messages.product_form_apply_preset') }}
                    </button>

                    <button type="button" @click="applyVariantPresetCombination()" :disabled="!selectedVariantPresetId || !selectedVariantPresetIdTwo || selectedVariantPresetId === selectedVariantPresetIdTwo" data-test-label="Generate Combinations" class="px-3.5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black shadow-sm disabled:cursor-not-allowed disabled:opacity-50 transition">
                        {{ __('messages.product_form_generate_combinations') }}
                    </button>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500 dark:text-slate-400">
                    <span x-show="selectedCategoryName">
                        {{ __('messages.product_form_category_preset_hint') }}
                        <span class="font-bold text-violet-700 dark:text-violet-300" x-text="filteredVariantPresets.length"></span>
                    </span>
                    <span>• {{ __('messages.product_form_preset_hint') }}</span>
                </div>
            @else
                <p class="text-xs font-semibold leading-5 text-slate-500 dark:text-slate-400">{{ __('messages.product_form_no_presets') }}</p>
            @endif
        </div>

        {{-- Variant Items List --}}
        <div class="space-y-2.5">
            <template x-for="(v, i) in variants" :key="i">
                <div class="rounded-lg border border-slate-200 dark:border-slate-700/80 bg-slate-50/70 dark:bg-slate-800/40 p-3 space-y-2.5 transition">
                    <div class="flex items-center justify-between gap-2.5">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="rounded-md bg-violet-600 px-2.5 py-0.5 text-xs font-black text-white shadow-2xs" x-text="'{{ __('messages.product_form_variant_label') }} ' + (i + 1)"></span>
                            <template x-for="(attr, ai) in (v.attributes || [])" :key="ai">
                                <span class="rounded-md border border-violet-200 bg-violet-50 px-2 py-0.5 text-xs font-bold text-violet-700 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-300" x-text="attr.label + ': ' + attr.value"></span>
                            </template>
                        </div>
                        <button type="button" @click="removeVariant(i)" class="rounded-lg px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">{{ __('messages.product_form_remove_variant') }}</button>
                    </div>

                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">{{ __('messages.product_form_variant_name') }} *</label>
                            <input type="text" x-model="v.name" :name="'variants[' + i + '][name]'" required class="mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-1.5 text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500/40" placeholder="{{ __('messages.product_form_variant_name_placeholder') }}" />
                        </div>

                        <div>
                            <label class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">{{ __('messages.product_form_sku') }}</label>
                            <input type="text" x-model="v.sku" :name="'variants[' + i + '][sku]'" class="mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-1.5 text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500/40" placeholder="{{ __('messages.product_form_variant_sku_placeholder') }}" />
                        </div>

                        <div>
                            <label class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">{{ __('messages.product_form_variant_stock') }}</label>
                            <div class="mt-1 flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-100/70 dark:bg-slate-800 text-xs">
                                <span class="w-2 h-2 rounded-full" :class="v.stock_status === 'out_of_stock' ? 'bg-rose-500' : 'bg-emerald-500'"></span>
                                <span class="font-bold text-slate-700 dark:text-slate-300" x-text="v.stock_status === 'out_of_stock' ? '{{ __('messages.out_of_stock') }}' : '{{ __('messages.in_stock') }}'"></span>
                                <span class="text-[10px] text-slate-400">({{ __('messages.product_stock_auto_managed') }})</span>
                            </div>
                        </div>

                        <div>
                            <label class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">{{ __('messages.product_form_variant_retail_price') }} *</label>
                            <input type="number" step="0.01" min="0" x-model="v.retail_price" :name="'variants[' + i + '][retail_price]'" required class="mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-1.5 text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500/40" />
                        </div>

                        <div>
                            <label class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">{{ __('messages.product_form_variant_wholesale_price') }}</label>
                            <input type="number" step="0.01" min="0" x-model="v.wholesale_price" :name="'variants[' + i + '][wholesale_price]'" class="mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-1.5 text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500/40" />
                        </div>

                        <div class="flex items-end pb-1">
                            <label class="inline-flex cursor-pointer select-none items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300">
                                <input type="checkbox" x-model="v.is_default" :name="'variants[' + i + '][is_default]'" value="1" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500" />
                                {{ __('messages.product_form_default_variant') }}
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 items-end gap-2.5 sm:grid-cols-2 pt-1 border-t border-slate-200/50 dark:border-slate-700/50">
                        <div>
                            <label class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">{{ __('messages.product_form_variant_image') }}</label>
                            <input type="file" accept="image/*" @change="previewVariantImage($event, i)" :name="'variants[' + i + '][image]'" class="mt-1 block w-full text-xs text-slate-600 dark:text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-violet-50 file:px-2.5 file:py-1 file:text-xs file:font-bold file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-slate-800 dark:file:text-violet-300 rounded-lg border border-slate-200 dark:border-slate-700 p-1 bg-white dark:bg-slate-900" />
                            <p class="{{ $hint }}">{{ __('messages.product_form_variant_image_hint') }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <img x-show="v.image_preview" :src="v.image_preview" class="h-12 w-12 rounded-lg border border-slate-200 dark:border-slate-700 object-cover shadow-2xs" />
                            <template x-if="v.image_path && !v.image_preview">
                                <img :src="'/storage/' + v.image_path" class="h-12 w-12 rounded-lg border border-slate-200 dark:border-slate-700 object-cover shadow-2xs" />
                            </template>
                            <label x-show="v.image_path && !v.image_preview" class="inline-flex cursor-pointer select-none items-center gap-1 text-xs font-bold text-rose-600">
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
    </section>

    {{-- 7. Storefront Live Previews Card --}}
    <section class="{{ $section }}">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5 mb-1">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 grid place-items-center text-sm font-bold">
                    👁️
                </span>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                        {{ __('messages.product_form_preview_section') }}
                    </h2>
                    <p class="text-[11px] text-slate-400">{{ __('messages.product_form_specs_preview_hint') }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-2.5 sm:gap-3 lg:grid-cols-2">
            <div class="min-w-0">
                <h3 class="mb-1.5 text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.product_form_description_preview') }}</h3>
                <div class="min-h-[7rem] rounded-lg border border-slate-200 bg-slate-50/80 p-3 text-xs sm:text-sm leading-relaxed text-slate-800 prose prose-sm max-w-none dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-100">
                    <template x-if="descriptionPreviewHtml">
                        <div x-html="descriptionPreviewHtml"></div>
                    </template>
                    <template x-if="!descriptionPreviewHtml">
                        <p class="text-xs text-slate-400">{{ __('messages.spec_description_empty') }}</p>
                    </template>
                </div>
            </div>

            <div class="min-w-0">
                <h3 class="mb-1.5 text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.product_form_specs_preview') }}</h3>
                <div class="min-h-[7rem] rounded-lg border border-slate-200 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-900/60">
                    <template x-if="previewSpecs().length">
                        <dl class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="(row, i) in previewSpecs()" :key="i">
                                <div class="grid grid-cols-1 gap-x-3 gap-y-0.5 py-1 sm:grid-cols-[minmax(0,9rem)_minmax(0,1fr)] sm:items-start">
                                    <dt class="break-words text-xs font-bold text-slate-500 dark:text-slate-400" x-text="row.label"></dt>
                                    <dd class="min-w-0 break-words text-xs font-semibold text-slate-800 dark:text-slate-200" x-text="row.value"></dd>
                                </div>
                            </template>
                        </dl>
                    </template>
                    <template x-if="!previewSpecs().length">
                        <p class="text-xs text-slate-400">{{ __('messages.specs_empty') }}</p>
                    </template>
                </div>
            </div>

            {{-- Return Policy preview --}}
            <div class="min-w-0 lg:col-span-2">
                <h3 class="mb-1.5 text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.return_policy') }}</h3>
                <div class="min-h-[3rem] rounded-lg border border-slate-200 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-900/60">
                    <p class="text-xs text-slate-700 dark:text-slate-300 whitespace-pre-line" x-show="returnPolicyPreview === null">{{ old('return_policy', $product->return_policy) ?: __('messages.specs_empty') }}</p>
                    <p class="text-xs text-slate-700 dark:text-slate-300 whitespace-pre-line" x-show="returnPolicyPreview !== null" x-text="returnPolicyPreview"></p>
                </div>
            </div>
        </div>
    </section>

    {{-- Sticky Bottom Action Bar --}}
    <div class="sticky bottom-0 z-20 w-full border border-slate-200/90 bg-white/95 px-3 py-2.5 sm:px-4 backdrop-blur-md shadow-[0_-4px_16px_rgba(15,23,42,0.06)] dark:border-slate-800/90 dark:bg-slate-900/95 rounded-lg">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3 sm:gap-5">
                <label class="inline-flex cursor-pointer select-none items-center gap-2">
                    <input type="checkbox" name="is_featured" value="1" id="is_featured" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500" />
                    <span class="text-xs font-black text-slate-800 dark:text-slate-200 flex items-center gap-1">
                        <span>⭐</span>
                        <span>{{ __('messages.product_form_featured') }}</span>
                    </span>
                </label>

                <label class="inline-flex cursor-pointer select-none items-center gap-2" title="{{ __('messages.product_form_sell_online_hint') }}">
                    <input type="hidden" name="is_ecommerce" value="0" />
                    <input type="checkbox" name="is_ecommerce" value="1" id="is_ecommerce" {{ old('is_ecommerce', $product->is_ecommerce ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                    <span class="text-xs font-black text-slate-800 dark:text-slate-200 flex items-center gap-1">
                        <span>🌐</span>
                        <span>{{ __('messages.product_form_sell_online') }}</span>
                    </span>
                </label>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $returnTo ?? url('/store/' . $store->slug . '/admin/products') }}"
                   class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition">
                    {{ __('messages.cancel') }}
                </a>
                <button type="submit" class="px-5 py-2 rounded-lg bg-violet-600 hover:bg-violet-500 text-white font-black text-xs shadow-md shadow-violet-500/20 transition flex items-center gap-1.5">
                    <span>💾</span>
                    <span>{{ $isEdit ? __('messages.product_form_update_product') : __('messages.product_form_save_product') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>

