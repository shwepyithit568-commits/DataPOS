@extends('layouts.admin.app')

@section('title', __('messages.sidebar_barcode') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<script nonce="{{ $cspNonce }}">
window._barcodeInitialPool = [
    @foreach($recentProducts as $p)
        {
            id: 'p-{{ $p->id }}',
            product_id: {{ $p->id }},
            name: '{{ addslashes($p->name) }}',
            code: '{{ $p->barcode ?: ($p->sku ?: 'PRD-' . $p->id) }}',
            price: {{ (float) $p->retail_price }},
            quantity: 1
        },
    @endforeach
];

window.barcodeDesignerFactory = function () {
    var pool = window._barcodeInitialPool || [];
    var initialItems = pool.length > 0 ? [{ ...pool[0] }] : [];

    return {
        selectedPreset: 'thermal_50x30',
        codeType: 'barcode_128',
        showStoreName: true,
        showProductName: true,
        showPrice: true,
        showCodeText: true,
        searchQuery: '',
        searchResults: [],
        isSearching: false,
        previewIndex: 0,
        selectedItems: initialItems,
        recentPool: pool,

        get previewItem() {
            if (this.selectedItems.length === 0) return null;
            return this.selectedItems[this.previewIndex] || this.selectedItems[0];
        },

        get totalLabelsCount() {
            return this.selectedItems.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
        },

        formatNumber(val) {
            return new Intl.NumberFormat().format(val || 0);
        },

        async searchProducts() {
            if (!this.searchQuery || this.searchQuery.length < 1) {
                this.searchResults = [];
                return;
            }
            try {
                const res = await fetch(`{{ route('store.admin.barcode.search', ['store_slug' => $store->slug]) }}?q=${encodeURIComponent(this.searchQuery)}`);
                if (res.ok) {
                    this.searchResults = await res.json();
                    this.isSearching = true;
                }
            } catch (e) {
                console.error(e);
            }
        },

        addItem(item) {
            const existingIndex = this.selectedItems.findIndex(i => i.id === item.id);
            if (existingIndex >= 0) {
                this.selectedItems[existingIndex].quantity++;
                this.previewIndex = existingIndex;
            } else {
                this.selectedItems.push({
                    id: item.id,
                    product_id: item.product_id,
                    name: item.name,
                    code: item.code,
                    price: item.price,
                    quantity: 1
                });
                this.previewIndex = this.selectedItems.length - 1;
            }
            this.searchQuery = '';
            this.searchResults = [];
        },

        addAllRecent() {
            this.recentPool.forEach(rp => {
                if (!this.selectedItems.some(i => i.id === rp.id)) {
                    this.selectedItems.push({ ...rp, quantity: 1 });
                }
            });
            this.previewIndex = 0;
        },

        submitPrint() {
            if (this.selectedItems.length === 0) return;
            document.getElementById('items_json_field').value = JSON.stringify(this.selectedItems);
            document.getElementById('barcodePrintForm').submit();
        }
    };
};
</script>

<div x-data="barcodeDesignerFactory()" class="space-y-6">

    {{-- Header & Breadcrumbs --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.admin_dashboard') }}</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-200 font-semibold">{{ __('messages.sidebar_barcode') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 font-outfit mt-1">
                {{ __('messages.barcode_label_printing_title') }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                {{ __('messages.barcode_label_printing_sub') }}
            </p>
        </div>

        {{-- Print Action Button --}}
        <div class="flex items-center gap-3">
            <button type="button"
                    @click="submitPrint()"
                    :disabled="selectedItems.length === 0"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-extrabold rounded-xl bg-violet-600 hover:bg-violet-500 text-white shadow-lg shadow-violet-600/30 disabled:opacity-50 disabled:cursor-not-allowed transition transform active:scale-95">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>{{ __('messages.print_stickers_btn') }}</span>
                <span x-show="totalLabelsCount > 0" class="px-2 py-0.5 rounded-full bg-white/20 text-xs font-mono font-bold" x-text="totalLabelsCount"></span>
            </button>
        </div>
    </div>

    {{-- Main Layout Grid: Left (Designer Controls & Product Matrix), Right (Live Preview) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Left Column: Settings + Product Matrix (8 cols) --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- Label Config & Presets Card --}}
            <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-5">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                    {{ __('messages.barcode_settings_title') }}
                </h3>

                {{-- Preset Selector --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach ($presets as $key => $preset)
                        <label class="relative flex flex-col p-3.5 rounded-xl border cursor-pointer transition select-none"
                               :class="selectedPreset === '{{ $key }}' ? 'border-violet-600 bg-violet-50/50 dark:border-violet-500 dark:bg-violet-950/30 ring-2 ring-violet-500/20' : 'border-slate-200 hover:border-slate-300 dark:border-slate-800 dark:hover:border-slate-700 bg-white dark:bg-slate-900'">
                            <input type="radio" name="preset" value="{{ $key }}" x-model="selectedPreset" class="sr-only">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-xs text-slate-900 dark:text-slate-100">{{ $preset['name'] }}</span>
                                <span class="w-2.5 h-2.5 rounded-full border border-white"
                                      :class="selectedPreset === '{{ $key }}' ? 'bg-violet-600' : 'bg-slate-300 dark:bg-slate-700'"></span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2">{{ $preset['description'] }}</p>
                        </label>
                    @endforeach
                </div>

                {{-- Code Type & Element Toggles --}}
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Code Type --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('messages.barcode_type') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button"
                                    @click="codeType = 'barcode_128'"
                                    :class="codeType === 'barcode_128' ? 'bg-violet-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300'"
                                    class="px-3 py-2 text-xs rounded-xl transition flex items-center justify-center gap-1.5">
                                <span>||||</span>
                                <span>Code 128</span>
                            </button>
                            <button type="button"
                                    @click="codeType = 'qr_code'"
                                    :class="codeType === 'qr_code' ? 'bg-violet-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300'"
                                    class="px-3 py-2 text-xs rounded-xl transition flex items-center justify-center gap-1.5">
                                <span>🔳</span>
                                <span>QR Code</span>
                            </button>
                        </div>
                    </div>

                    {{-- Display Elements Toggles --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('messages.barcode_display_elements') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" x-model="showStoreName" class="rounded text-violet-600 focus:ring-violet-500">
                                <span>{{ __('messages.barcode_show_store_name') }}</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" x-model="showProductName" class="rounded text-violet-600 focus:ring-violet-500">
                                <span>{{ __('messages.barcode_show_product_name') }}</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" x-model="showPrice" class="rounded text-violet-600 focus:ring-violet-500">
                                <span>{{ __('messages.barcode_show_price') }}</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" x-model="showCodeText" class="rounded text-violet-600 focus:ring-violet-500">
                                <span>{{ __('messages.barcode_show_code_text') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Product Search & Selection Card --}}
            <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                        {{ __('messages.barcode_select_products_title') }}
                    </h3>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                @click="addAllRecent()"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition">
                            + {{ __('messages.barcode_add_all_in_stock') }}
                        </button>
                        <button type="button"
                                x-show="selectedItems.length > 0"
                                @click="selectedItems = []; previewIndex = 0;"
                                class="text-xs font-semibold px-2.5 py-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">
                            {{ __('messages.clear_all') }}
                        </button>
                    </div>
                </div>

                {{-- Search Bar with Autocomplete dropdown --}}
                <div class="relative">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text"
                               x-model="searchQuery"
                               @input.debounce.250ms="searchProducts()"
                               @focus="isSearching = true"
                               placeholder="{{ __('messages.barcode_search_placeholder') }}"
                               class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>

                    {{-- Search Results Dropdown --}}
                    <div x-show="searchResults.length > 0 && isSearching"
                         @click.away="isSearching = false"
                         x-cloak
                         class="absolute z-20 top-full left-0 right-0 mt-1.5 max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700">
                        <template x-for="item in searchResults" :key="item.id">
                            <div @click="addItem(item); isSearching = false;"
                                 class="p-3 hover:bg-violet-50 dark:hover:bg-violet-950/40 cursor-pointer flex items-center justify-between transition">
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-slate-100" x-text="item.name"></div>
                                    <div class="text-[11px] text-slate-500 font-mono" x-text="'Code: ' + item.code"></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs font-bold text-violet-600 dark:text-violet-400 font-outfit" x-text="formatNumber(item.price) + ' Ks'"></div>
                                    <div class="text-[10px] text-slate-400" x-text="'Stock: ' + item.stock"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Selected Items Table --}}
                <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 dark:bg-slate-800/60 dark:text-slate-400 uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">{{ __('messages.product') }}</th>
                                <th class="px-3 py-3">{{ __('messages.barcode') }}</th>
                                <th class="px-3 py-3">{{ __('messages.price') }} (Ks)</th>
                                <th class="px-3 py-3 text-center" style="width: 140px;">{{ __('messages.sticker_quantity') }}</th>
                                <th class="px-3 py-3 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-if="selectedItems.length === 0">
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                        {{ __('messages.barcode_no_items_selected_hint') }}
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(item, index) in selectedItems" :key="item.id">
                                <tr @click="previewIndex = index"
                                    :class="previewIndex === index ? 'bg-violet-50/60 dark:bg-violet-950/30' : ''"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 cursor-pointer transition">
                                    <td class="px-4 py-2.5">
                                        <div class="font-bold text-slate-900 dark:text-slate-100" x-text="item.name"></div>
                                    </td>
                                    <td class="px-3 py-2.5 font-mono text-slate-600 dark:text-slate-400" x-text="item.code"></td>
                                    <td class="px-3 py-2.5" @click.stop>
                                        <input type="number"
                                               x-model.number="item.price"
                                               class="w-24 px-2 py-1 text-xs rounded-lg border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-outfit">
                                    </td>
                                    <td class="px-3 py-2.5" @click.stop>
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button type="button"
                                                    @click="if (item.quantity > 1) item.quantity--"
                                                    class="w-6 h-6 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center">-</button>
                                            <input type="number"
                                                   x-model.number="item.quantity"
                                                   min="1"
                                                   class="w-14 text-center px-1.5 py-1 text-xs font-bold rounded-md border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-outfit">
                                            <button type="button"
                                                    @click="item.quantity++"
                                                    class="w-6 h-6 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center">+</button>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5 text-right" @click.stop>
                                        <button type="button"
                                                @click="selectedItems.splice(index, 1); if (previewIndex >= selectedItems.length) previewIndex = 0;"
                                                class="text-slate-400 hover:text-rose-600 transition p-1">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right Column: Live Sticky Sticker Preview (4 cols) --}}
        <div class="lg:col-span-4 sticky top-6 space-y-4">
            <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                        {{ __('messages.barcode_live_preview_title') }}
                    </h3>
                    <span class="px-2 py-0.5 rounded-md bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300 text-[10px] font-bold" x-text="selectedPreset"></span>
                </div>

                {{-- Scaled Visual Sticker Box --}}
                <div class="p-6 rounded-xl bg-slate-100 dark:bg-slate-950 flex items-center justify-center min-h-[220px]">
                    <div class="bg-white text-slate-900 p-3 rounded-lg shadow-md border border-slate-300 w-full max-w-[220px] text-center flex flex-col items-center justify-center space-y-1.5 transition-all">
                        {{-- Store Name --}}
                        <div x-show="showStoreName" class="text-[11px] font-extrabold tracking-tight text-slate-800 truncate w-full" x-text="'{{ $store->name }}'"></div>

                        {{-- Product Name --}}
                        <div x-show="showProductName" class="text-[10px] font-semibold text-slate-700 line-clamp-2 w-full leading-tight" x-text="previewItem ? previewItem.name : 'Sample Product Name'"></div>

                        {{-- Barcode / QR Graphic Simulation --}}
                        <div class="py-1 w-full flex items-center justify-center">
                            <template x-if="codeType === 'barcode_128'">
                                <div class="w-full flex flex-col items-center">
                                    <div class="h-9 w-4/5 bg-slate-900 flex items-center justify-center text-white text-[9px] font-mono tracking-widest" style="background-image: repeating-linear-gradient(90deg, #000 0px, #000 2px, #fff 2px, #fff 4px, #000 4px, #000 7px, #fff 7px, #fff 8px);"></div>
                                    <div x-show="showCodeText" class="text-[9px] font-mono font-bold mt-0.5 text-slate-800" x-text="previewItem ? previewItem.code : '885123456789'"></div>
                                </div>
                            </template>
                            <template x-if="codeType === 'qr_code'">
                                <div class="w-14 h-14 bg-slate-900 p-1 flex items-center justify-center text-white text-[8px] font-mono rounded">
                                    <span>[ QR ]</span>
                                </div>
                            </template>
                        </div>

                        {{-- Price (MMK) --}}
                        <div x-show="showPrice" class="text-xs font-black text-slate-950 font-outfit" x-text="previewItem ? formatNumber(previewItem.price) + ' Ks' : '15,000 Ks'"></div>
                    </div>
                </div>

                {{-- Summary Stats --}}
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>{{ __('messages.barcode_selected_products') }}:</span>
                        <span class="font-bold text-slate-900 dark:text-slate-100" x-text="selectedItems.length"></span>
                    </div>
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>{{ __('messages.barcode_total_labels') }}:</span>
                        <span class="font-black text-violet-600 dark:text-violet-400 text-sm font-outfit" x-text="totalLabelsCount"></span>
                    </div>
                </div>

                {{-- Large Print CTA Button --}}
                <button type="button"
                        @click="submitPrint()"
                        :disabled="selectedItems.length === 0"
                        class="w-full py-3 text-sm font-extrabold rounded-xl bg-violet-600 hover:bg-violet-500 text-white shadow-lg shadow-violet-600/30 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>{{ __('messages.print_stickers_btn') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Hidden Form to Submit to Print Window --}}
    <form id="barcodePrintForm"
          action="{{ route('store.admin.barcode.print', ['store_slug' => $store->slug]) }}"
          method="POST"
          target="_blank"
          class="hidden">
        @csrf
        <input type="hidden" name="preset" :value="selectedPreset">
        <input type="hidden" name="code_type" :value="codeType">
        <input type="hidden" name="show_store_name" :value="showStoreName ? '1' : '0'">
        <input type="hidden" name="show_product_name" :value="showProductName ? '1' : '0'">
        <input type="hidden" name="show_price" :value="showPrice ? '1' : '0'">
        <input type="hidden" name="show_code_text" :value="showCodeText ? '1' : '0'">
        <input type="hidden" name="items_json" id="items_json_field">
    </form>
</div>
@endsection
