@extends('layouts.admin.app')

@section('title', __('messages.new_buyback') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div x-data="buybackForm()" class="w-full space-y-0.5 pb-6">

    {{-- ============================================================
         1. COMPACT PAGE HEADER — back button & title
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <a href="{{ route('pos.buybacks.index', $storeRouteParams) }}"
               class="w-7 h-7 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 grid place-items-center text-xs font-bold transition shrink-0"
               title="{{ __('messages.back') }}">
                ←
            </a>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.new_buyback') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.buyback_sub') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0">
            <a href="{{ route('pos.buybacks.index', $storeRouteParams) }}"
               class="h-7 px-3 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-300 text-xs font-bold transition inline-flex items-center gap-1 cursor-pointer">
                <span>{{ __('messages.cancel') }}</span>
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-2 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 rounded-lg text-xs text-rose-700 dark:text-rose-300 shadow-2xs">
            <div class="flex items-center gap-1 font-bold">
                <span>⚠️</span>
                <span>{{ __('messages.validation_error') ?? 'Validation Error' }}</span>
            </div>
            <ul class="list-disc list-inside text-xs pl-2 space-y-0.5 mt-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('pos.buybacks.store', $storeRouteParams) }}" class="space-y-0.5" @submit="onFormSubmit($event)">
        @csrf

        {{-- Customer & Reason Section --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-3 shadow-2xs space-y-2">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <span>👤</span>
                <span>{{ __('messages.customer_info') }}</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">{{ __('messages.customer') }}</label>
                    <select name="customer_id"
                            class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-sky-500 focus:bg-white dark:focus:bg-slate-900 transition">
                        <option value="">{{ __('messages.walk_in_customer') }}</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }} ({{ $customer->phone ?? 'No phone' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">{{ __('messages.reason') }}</label>
                    <input type="text" name="reason" maxlength="500" x-model="reason"
                           class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-1 focus:ring-sky-500 focus:bg-white dark:focus:bg-slate-900 transition"
                           placeholder="{{ __('messages.buyback_reason_placeholder') }}">

                    {{-- Quick Trade-in Presets --}}
                    <div class="flex flex-wrap items-center gap-1 mt-1">
                        <span class="text-[10px] text-slate-400 font-semibold">{{ __('messages.buyback_quick_presets') }}:</span>
                        <button type="button" @click="setPreset('{{ __('messages.buyback_preset_phone') }}')"
                                class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 hover:bg-sky-100 transition cursor-pointer">
                            📱 {{ __('messages.buyback_preset_phone') }}
                        </button>
                        <button type="button" @click="setPreset('{{ __('messages.buyback_preset_laptop') }}')"
                                class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 transition cursor-pointer">
                            💻 {{ __('messages.buyback_preset_laptop') }}
                        </button>
                        <button type="button" @click="setPreset('{{ __('messages.buyback_preset_parts') }}')"
                                class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition cursor-pointer">
                            ⚙️ {{ __('messages.buyback_preset_parts') }}
                        </button>
                        <button type="button" @click="setPreset('{{ __('messages.buyback_preset_upgrade') }}')"
                                class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 transition cursor-pointer">
                            🚀 {{ __('messages.buyback_preset_upgrade') }}
                        </button>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">{{ __('messages.notes') }}</label>
                <input type="text" name="notes" maxlength="500" value="{{ old('notes') }}"
                       class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-1 focus:ring-sky-500 focus:bg-white dark:focus:bg-slate-900 transition"
                       placeholder="{{ __('messages.buyback_notes_placeholder') }}">
            </div>
        </div>

        {{-- Products / Items Section --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-3 shadow-2xs space-y-2.5">
            {{-- Toolbar with Barcode Search & Add Button --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <span>📦</span>
                        <span>{{ __('messages.products') }}</span>
                    </h2>
                    @if($warehouse)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700" title="Incoming stock destination">
                            📥 {{ $warehouse->name }}
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-1.5">
                    {{-- Quick Barcode Scanner Input --}}
                    <div class="relative min-w-[200px] sm:min-w-[240px]">
                        <input type="text" x-model="barcodeQuery" @keydown.enter.prevent="scanBarcode()"
                               placeholder="{{ __('messages.buyback_search_product') }}"
                               class="w-full h-7 pl-7 pr-7 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-sky-500 focus:bg-white dark:focus:bg-slate-900 transition" />
                        <span class="absolute left-2 top-1.5 text-xs opacity-50">🔍</span>
                        <button type="button" x-show="barcodeQuery" @click="scanBarcode()"
                                class="absolute right-1 top-1 px-1.5 h-5 rounded text-[10px] font-bold bg-sky-600 text-white cursor-pointer">
                            +
                        </button>
                    </div>

                    <button type="button" @click="addItem()"
                            class="h-7 px-2.5 text-xs font-bold text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-950/60 hover:bg-sky-100 dark:hover:bg-sky-900/60 rounded-md border border-sky-200 dark:border-sky-800 shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                        <span>+</span>
                        <span>{{ __('messages.add_item') ?? 'Add Item' }}</span>
                    </button>
                </div>
            </div>

            {{-- Product Items Rows --}}
            <div class="space-y-1.5">
                <template x-for="(item, index) in items" :key="index">
                    <div class="p-2 bg-slate-50/80 dark:bg-slate-800/60 rounded-lg border border-slate-200/80 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center gap-2 transition hover:border-slate-300 dark:hover:border-slate-600">
                        {{-- Product Search Combobox --}}
                        <div class="flex-1 min-w-[200px] relative" @click.outside="item.dropdownOpen = false">
                            <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.product_id" required>
                            <div class="flex items-center">
                                <input type="text" x-model="item.searchQuery"
                                       @focus="item.dropdownOpen = true"
                                       @input="item.dropdownOpen = true"
                                       placeholder="{{ __('messages.buyback_search_product') }}"
                                       class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-sky-500" />
                                <button type="button" @click="item.dropdownOpen = !item.dropdownOpen"
                                        class="h-8 px-2 border border-l-0 border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-700 text-slate-500 rounded-r-md text-xs cursor-pointer">
                                    ▾
                                </button>
                            </div>

                            {{-- Product Dropdown Popover --}}
                            <div x-show="item.dropdownOpen" x-cloak
                                 class="absolute left-0 right-0 top-full mt-1 z-30 max-h-52 overflow-y-auto bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-xl divide-y divide-slate-100 dark:divide-slate-700">
                                <template x-for="prod in filterProducts(item.searchQuery)" :key="prod.id">
                                    <div @click="selectProduct(item, prod)"
                                         class="p-2 hover:bg-sky-50 dark:hover:bg-sky-950/60 cursor-pointer transition flex items-center justify-between gap-2 text-xs">
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-800 dark:text-slate-100 truncate" x-text="prod.name"></p>
                                            <p class="font-mono text-[10px] text-slate-400" x-text="'SKU: ' + (prod.sku || '—') + (prod.barcode ? ' · ' + prod.barcode : '')"></p>
                                        </div>
                                        <div class="text-right shrink-0 text-[10px]">
                                            <span class="block font-bold text-slate-700 dark:text-slate-300 font-mono" x-text="formatCurrency(prod.price)"></span>
                                            <span class="block text-slate-400" x-text="'Cost: ' + formatCurrency(prod.cost)"></span>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="filterProducts(item.searchQuery).length === 0" class="p-3 text-center text-xs text-slate-400">
                                    {{ __('messages.no_products_found') ?? 'No matching products' }}
                                </div>
                            </div>
                        </div>

                        {{-- Quantity Input --}}
                        <div class="w-full sm:w-24 shrink-0">
                            <label class="block sm:hidden text-[10px] font-bold text-slate-500 mb-0.5">{{ __('messages.qty') }}</label>
                            <input type="number" :name="'items[' + index + '][quantity]'" x-model.number="item.quantity" min="0.001" step="0.001" required
                                   class="w-full h-8 px-2 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-1 focus:ring-sky-500"
                                   placeholder="{{ __('messages.qty') }}">
                        </div>

                        {{-- Unit Price Input --}}
                        <div class="w-full sm:w-32 shrink-0">
                            <label class="block sm:hidden text-[10px] font-bold text-slate-500 mb-0.5">{{ __('messages.price') }}</label>
                            <input type="number" :name="'items[' + index + '][unit_price]'" x-model.number="item.unit_price" min="0" step="1" required
                                   class="w-full h-8 px-2 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold text-slate-800 dark:text-slate-100 text-right focus:outline-none focus:ring-1 focus:ring-sky-500"
                                   placeholder="{{ __('messages.price') }}">
                        </div>

                        {{-- Row Subtotal --}}
                        <div class="w-full sm:w-28 shrink-0 flex items-center justify-between sm:justify-end gap-2 text-right">
                            <span class="sm:hidden text-xs text-slate-500 font-bold">{{ __('messages.total') }}:</span>
                            <span class="font-mono text-xs font-black text-slate-900 dark:text-slate-100 tabular-nums" x-text="formatRowSubtotal(item)"></span>
                            <button type="button" @click="removeItem(index)"
                                    class="w-7 h-7 rounded text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/60 grid place-items-center text-xs font-bold transition shrink-0 cursor-pointer"
                                    title="{{ __('messages.delete') }}">
                                ✕
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Total Summary Row --}}
            <div class="flex items-center justify-between pt-2.5 border-t border-slate-200 dark:border-slate-700">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('messages.total') }}:</span>
                <span class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 font-outfit tabular-nums" x-text="formatTotal()"></span>
            </div>
        </div>

        {{-- Bottom Action Buttons --}}
        <div class="flex justify-end gap-1.5 pt-1">
            <a href="{{ route('pos.buybacks.index', $storeRouteParams) }}"
               class="h-8 px-4 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 text-slate-700 dark:text-slate-300 text-xs font-bold transition inline-flex items-center cursor-pointer">
                {{ __('messages.cancel') }}
            </a>
            <button type="submit"
                    class="h-8 px-5 rounded-md bg-sky-600 hover:bg-sky-500 text-white text-xs font-black shadow-2xs hover:shadow-sky-500/20 transition inline-flex items-center gap-1.5 cursor-pointer active:scale-95">
                <span>✓ {{ __('messages.create_buyback') ?? 'Create Buy-Back' }}</span>
            </button>
        </div>
    </form>
</div>

<script nonce="{{ $cspNonce }}">
function buybackForm() {
    const rawProducts = @json($productArray ?? []);

    return {
        products: rawProducts,
        barcodeQuery: '',
        reason: '{{ old('reason') }}',
        items: [
            { product_id: '', searchQuery: '', quantity: 1, unit_price: 0, dropdownOpen: false }
        ],

        setPreset(presetText) {
            this.reason = presetText;
        },

        addItem() {
            this.items.push({ product_id: '', searchQuery: '', quantity: 1, unit_price: 0, dropdownOpen: false });
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },

        filterProducts(query) {
            if (!query) return this.products.slice(0, 30);
            const q = query.toLowerCase().trim();
            return this.products.filter(p =>
                p.name.toLowerCase().includes(q) ||
                (p.sku && p.sku.toLowerCase().includes(q)) ||
                (p.barcode && p.barcode.toLowerCase().includes(q))
            ).slice(0, 30);
        },

        selectProduct(item, prod) {
            item.product_id = prod.id;
            item.searchQuery = prod.name + (prod.sku ? ` (${prod.sku})` : '');
            item.dropdownOpen = false;
            // Default suggested price to cost or retail price if unit_price is 0
            if (!item.unit_price || item.unit_price === 0) {
                item.unit_price = prod.cost > 0 ? prod.cost : Math.round(prod.price * 0.7);
            }
        },

        scanBarcode() {
            const code = this.barcodeQuery.toLowerCase().trim();
            if (!code) return;
            const match = this.products.find(p =>
                (p.barcode && p.barcode.toLowerCase() === code) ||
                (p.sku && p.sku.toLowerCase() === code) ||
                p.name.toLowerCase().includes(code)
            );

            if (match) {
                // If the first row is empty, fill it; otherwise append
                const emptyRow = this.items.find(i => !i.product_id);
                if (emptyRow) {
                    this.selectProduct(emptyRow, match);
                } else {
                    const existing = this.items.find(i => i.product_id === match.id);
                    if (existing) {
                        existing.quantity += 1;
                    } else {
                        const newItem = { product_id: '', searchQuery: '', quantity: 1, unit_price: 0, dropdownOpen: false };
                        this.selectProduct(newItem, match);
                        this.items.push(newItem);
                    }
                }
                this.barcodeQuery = '';
            }
        },

        formatCurrency(amount) {
            if (typeof window.formatCurrency === 'function') {
                return window.formatCurrency(amount);
            }
            return Number(amount || 0).toLocaleString();
        },

        formatRowSubtotal(item) {
            const sub = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
            return this.formatCurrency(sub);
        },

        formatTotal() {
            const total = this.items.reduce((sum, item) => sum + ((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)), 0);
            return this.formatCurrency(total);
        },

        onFormSubmit(e) {
            const hasEmpty = this.items.some(i => !i.product_id);
            if (hasEmpty) {
                e.preventDefault();
                alert('Please select a product for all rows before submitting.');
            }
        }
    };
}
</script>
@endsection

