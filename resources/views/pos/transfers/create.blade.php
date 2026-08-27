@extends('layouts.admin.app')

@section('title', __('messages.new_transfer') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
         fromWarehouseId: '{{ old('from_warehouse_id', '') }}',
         toWarehouseId: '{{ old('to_warehouse_id', '') }}',
         fromWarehouseName: '',
         toWarehouseName: '',
         notes: '{{ old('notes', '') }}',
         rows: [],
         q: '',
         filterCategory: '',
         filterBrand: '',
         open: false,
         allProductsByWarehouse: {!! json_encode($products->mapWithKeys(fn($group, $whId) => [$whId => $group->map(fn($b) => [
             'product_id' => $b->product_id,
             'name' => $b->product->name ?? 'Unknown',
             'sku' => $b->product->sku ?: ($b->product->barcode ?: 'PRD-' . $b->product_id),
             'barcode' => $b->product->barcode ?? '',
             'category_id' => $b->product->category_id ?? '',
             'category_name' => $b->product->category?->name ?? '',
             'brand_id' => $b->product->brand_id ?? '',
             'brand_name' => $b->product->brand?->name ?? '',
             'balance' => (float) $b->quantity_on_hand,
             'cost' => (float) ($b->unit_cost_avg ?: ($b->product->cost_price ?: 0)),
         ])->values()->toArray()])) !!},
         availableProducts: [],

         init() {
             if (this.fromWarehouseId) {
                 this.onWarehouseChange();
             }
         },

         onWarehouseChange() {
             this.availableProducts = this.allProductsByWarehouse[this.fromWarehouseId] || [];
             this.rows = [];
             this.q = '';
             this.open = false;
         },

         get searchResults() {
             if (!this.fromWarehouseId || this.availableProducts.length === 0) return [];
             const query = this.q.trim().toLowerCase();
             const cat = this.filterCategory;
             const brand = this.filterBrand;

             if (!query && !cat && !brand) return [];

             return this.availableProducts.filter(p => {
                 const matchCat = !cat || String(p.category_id) === String(cat);
                 const matchBrand = !brand || String(p.brand_id) === String(brand);
                 const matchQuery = !query ||
                     p.name.toLowerCase().includes(query) ||
                     p.sku.toLowerCase().includes(query) ||
                     (p.barcode && p.barcode.toLowerCase().includes(query));
                 return matchCat && matchBrand && matchQuery;
             }).slice(0, 10);
         },

         addProduct(p) {
             const existing = this.rows.find(r => r.product_id === p.product_id);
             if (existing) {
                 const currentQty = parseFloat(existing.quantity) || 0;
                 if (currentQty < p.balance) {
                     existing.quantity = String(currentQty + 1);
                 }
             } else {
                 this.rows.push({
                     product_id: p.product_id,
                     name: p.name,
                     sku: p.sku,
                     category_name: p.category_name,
                     balance: p.balance,
                     quantity: '1',
                     unit_cost: p.cost
                 });
             }
             this.q = '';
             this.open = false;
             this.$nextTick(() => { this.$refs.searchInput && this.$refs.searchInput.focus(); });
         },

         enterPick() {
             if (this.searchResults.length > 0) {
                 this.addProduct(this.searchResults[0]);
             }
         },

         removeRow(index) {
             this.rows.splice(index, 1);
         },

         incQty(r) {
             const current = parseFloat(r.quantity) || 0;
             if (current < r.balance) {
                 r.quantity = String(current + 1);
             }
         },

         decQty(r) {
             const current = parseFloat(r.quantity) || 0;
             r.quantity = String(Math.max(0.001, current - 1));
         },

         setQtyMax(r) {
             r.quantity = String(r.balance);
         },

         lineTotal(r) {
             return (parseFloat(r.quantity) || 0) * (parseFloat(r.unit_cost) || 0);
         },

         fmt(n) {
             return Number(n).toLocaleString(undefined, { maximumFractionDigits: 2 });
         },

         get totalQty() {
             return this.rows.reduce((s, r) => s + (parseFloat(r.quantity) || 0), 0);
         },

         get totalValue() {
             return this.rows.reduce((s, r) => s + this.lineTotal(r), 0);
         },

         get canSubmit() {
             return this.fromWarehouseId &&
                 this.toWarehouseId &&
                 this.fromWarehouseId !== this.toWarehouseId &&
                 this.rows.length > 0 &&
                 this.rows.every(r => r.product_id && (parseFloat(r.quantity) || 0) > 0);
         }
     }">

    {{-- ============================================================
         1. COMPACT HERO PAGE HEADER (Admin UI Standard)
         ============================================================ --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
        <div class="min-w-0">
            <div class="flex items-center gap-1.5 mb-0.5">
                <a href="{{ route('pos.transfers.index', $storeRouteParams) }}"
                   class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    <span>{{ __('messages.back') }}</span>
                </a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                    <span>🔄</span>
                    <span>{{ __('messages.sidebar_transfers') }}</span>
                </span>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ $store->name }}</span>
            </div>
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span>{{ __('messages.new_transfer') }}</span>
            </h1>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ __('messages.transfer_empty_hint') }}</p>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
            <a href="{{ route('pos.transfers.index', $storeRouteParams) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1 shadow-2xs">
                <span>✕</span>
                <span>{{ __('messages.cancel') }}</span>
            </a>
        </div>
    </div>

    {{-- Error Notifications --}}
    @if ($errors->any())
        <div class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1">
            @foreach ($errors->all() as $error)
                <p class="flex items-center gap-1.5">
                    <span>⚠️</span>
                    <span>{{ $error }}</span>
                </p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         2. 4-COLUMN COMPACT KPI SUMMARY CARDS (Like Purchases Create)
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
        {{-- Card 1: Selected Products Count --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('messages.products') }}</span>
                <span class="w-6 h-6 rounded bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center text-xs">📦</span>
            </div>
            <div class="mt-1">
                <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 font-mono" x-text="rows.length + ' Items'"></p>
                <span class="text-[10px] text-slate-400 block mt-0.5" x-text="totalQty.toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' Total Units' "></span>
            </div>
        </div>

        {{-- Card 2: Estimated Transfer Value --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">Est. Total Value</span>
                <span class="w-6 h-6 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xs">💰</span>
            </div>
            <div class="mt-1">
                <p class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 font-mono truncate" x-text="'Ks ' + fmt(totalValue)"></p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Inventory Cost Value</span>
            </div>
        </div>

        {{-- Card 3: From Warehouse (Origin) --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('messages.from_warehouse') }}</span>
                <span class="w-6 h-6 rounded bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-xs">📤</span>
            </div>
            <div class="mt-1">
                <p class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-200 truncate"
                   x-text="fromWarehouseName ? fromWarehouseName : '{{ __('messages.select_warehouse') }}'"></p>
                <span class="text-[10px] text-slate-400 block mt-0.5" x-text="availableProducts.length + ' Products in Stock'"></span>
            </div>
        </div>

        {{-- Card 4: To Warehouse (Destination) --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('messages.to_warehouse') }}</span>
                <span class="w-6 h-6 rounded bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center text-xs">📥</span>
            </div>
            <div class="mt-1">
                <p class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-200 truncate"
                   x-text="toWarehouseName ? toWarehouseName : '{{ __('messages.select_warehouse') }}'"></p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Destination</span>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. MAIN FORM CARD
         ============================================================ --}}
    <form method="POST" action="{{ route('pos.transfers.store', $storeRouteParams) }}"
          class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden space-y-0">
        @csrf

        {{-- Section 1: Warehouse Route Selection & Notes --}}
        <div class="p-3 sm:p-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3">
                {{-- From Warehouse --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center gap-1">
                        <span>📤</span>
                        <span>{{ __('messages.from_warehouse') }}</span>
                        <span class="text-rose-500">*</span>
                    </label>
                    <select name="from_warehouse_id" x-model="fromWarehouseId"
                            @change="fromWarehouseName = $event.target.options[$event.target.selectedIndex].text; onWarehouseChange()" required
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">{{ __('messages.select_warehouse') }}</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ old('from_warehouse_id') == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }} ({{ $wh->code ?? '—' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- To Warehouse --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center gap-1">
                        <span>📥</span>
                        <span>{{ __('messages.to_warehouse') }}</span>
                        <span class="text-rose-500">*</span>
                    </label>
                    <select name="to_warehouse_id" x-model="toWarehouseId"
                            @change="toWarehouseName = $event.target.options[$event.target.selectedIndex].text" required
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">{{ __('messages.select_warehouse') }}</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" :disabled="fromWarehouseId == '{{ $wh->id }}'" {{ old('to_warehouse_id') == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }} ({{ $wh->code ?? '—' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center gap-1">
                        <span>📝</span>
                        <span>{{ __('messages.notes') }}</span>
                    </label>
                    <input type="text" name="notes" maxlength="500" x-model="notes"
                           placeholder="{{ __('messages.transfer_notes_placeholder') }}"
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500">
                </div>
            </div>
        </div>

        {{-- Section 2: Product Finder & Live Search (Purchases Entry Style) --}}
        <div class="p-3 sm:p-4 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900">
            <div class="flex flex-col sm:flex-row gap-2">
                {{-- Live Search Autocomplete Box --}}
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8" stroke-width="2"/><line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2"/></svg>
                    </span>
                    <input x-ref="searchInput" type="text" x-model="q"
                           @focus="open = searchResults.length > 0"
                           @input="open = searchResults.length > 0"
                           @keydown.enter.prevent="enterPick()"
                           @keydown.escape.prevent="open = false"
                           :disabled="!fromWarehouseId"
                           placeholder="{{ __('messages.receiving_product_placeholder') ?? 'Type barcode, SKU or product name...' }}"
                           autocomplete="off"
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 pl-9 pr-8 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500 disabled:opacity-50 disabled:cursor-not-allowed"/>

                    <button type="button" x-show="q" x-cloak
                            @click="q = ''; open = false; $refs.searchInput.focus()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-sm">
                        &times;
                    </button>

                    {{-- Autocomplete Results Dropdown --}}
                    <div x-show="open && searchResults.length > 0" x-cloak @click.outside="open = false"
                         class="absolute z-30 mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl overflow-hidden">
                        <div class="max-h-64 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="p in searchResults" :key="p.product_id">
                                <button type="button" @click="addProduct(p)"
                                        class="w-full text-left px-3 py-2.5 hover:bg-violet-50/60 dark:hover:bg-slate-800/80 flex items-center justify-between gap-2 transition cursor-pointer">
                                    <span class="min-w-0">
                                        <span class="block font-bold text-xs text-slate-900 dark:text-slate-100 truncate" x-text="p.name"></span>
                                        <span class="block text-[10px] font-mono text-slate-400 truncate" x-text="p.sku + ' · On Hand: ' + p.balance + ' pcs' + (p.category_name ? ' · ' + p.category_name : '')"></span>
                                    </span>
                                    <span class="shrink-0 flex items-center gap-2">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-mono" x-text="'Stock: ' + p.balance"></span>
                                        <span class="text-xs font-black text-violet-600 dark:text-violet-400 font-mono" x-text="'Ks ' + Number(p.cost).toLocaleString()"></span>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Category & Brand Filters --}}
                <div class="flex items-center gap-2">
                    <select x-model="filterCategory" :disabled="!fromWarehouseId"
                            class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 outline-none focus:ring-2 focus:ring-violet-500 disabled:opacity-50">
                        <option value="">{{ __('messages.filter_all_categories') ?? 'All Categories' }}</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>

                    <select x-model="filterBrand" :disabled="!fromWarehouseId"
                            class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 outline-none focus:ring-2 focus:ring-violet-500 disabled:opacity-50">
                        <option value="">{{ __('messages.filter_all_brands') ?? 'All Brands' }}</option>
                        @foreach ($brands as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Section 3: Selected Line Items Spreadsheet Table (Purchases Table Style) --}}
        <div class="p-3 sm:p-4 space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <span>📋</span>
                    <span>{{ __('messages.products') }}</span>
                </h3>
                <span class="text-xs font-mono font-bold text-slate-400" x-text="rows.length + ' lines selected'"></span>
            </div>

            {{-- Empty State (No Warehouse Selected) --}}
            <div x-show="!fromWarehouseId"
                 class="rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-800 py-8 text-center bg-slate-50/50 dark:bg-slate-900/30">
                <div class="text-2xl mb-1 opacity-50">📤</div>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400">ကျေးဇူးပြု၍ ပေးပို့မည့်ဂိုဒေါင် (From Warehouse) ကို အရင်ရွေးချယ်ပါ</p>
                <p class="text-[10px] text-slate-400 mt-0.5">ဂိုဒေါင်ရွေးချယ်ပြီးမှ ထိုဂိုဒေါင်ရှိ ကုန်ပစ္စည်းများကို ရှာဖွေထည့်သွင်းနိုင်ပါမည်။</p>
            </div>

            {{-- Empty State (Warehouse Selected but No Items Added) --}}
            <div x-show="fromWarehouseId && rows.length === 0"
                 class="rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-800 py-8 text-center bg-slate-50/50 dark:bg-slate-900/30">
                <div class="text-2xl mb-1 opacity-50">🛒</div>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.receiving_product_placeholder') ?? 'Search and add products to transfer' }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">Type barcode, SKU or product name above to add items to this transfer</p>
            </div>

            {{-- Line Items Table --}}
            <div x-show="rows.length > 0" class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-lg shadow-2xs">
                <table class="w-full text-left text-xs border-collapse font-sans">
                    <thead class="bg-slate-100 dark:bg-slate-800/90 text-slate-700 dark:text-slate-300 font-bold uppercase text-[11px]">
                        <tr class="divide-x divide-slate-200 dark:divide-slate-700">
                            <th class="p-2.5 text-center w-10">#</th>
                            <th class="p-2.5 min-w-[200px]">{{ __('messages.products') }}</th>
                            <th class="p-2.5 text-center min-w-[160px]">{{ __('messages.reports_qty') }}</th>
                            <th class="p-2.5 text-right min-w-[130px]">{{ __('messages.unit_cost') }} (Ks)</th>
                            <th class="p-2.5 text-right min-w-[130px]">{{ __('messages.receiving_total') ?? 'Subtotal' }}</th>
                            <th class="p-2.5 text-center w-14"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                        <template x-for="(r, i) in rows" :key="r.product_id">
                            <tr class="divide-x divide-slate-200/60 dark:divide-slate-800 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <td class="p-2.5 text-center font-mono font-bold text-slate-400" x-text="i + 1"></td>

                                {{-- Product Name & SKU --}}
                                <td class="p-2.5">
                                    <span class="block font-bold text-xs text-slate-900 dark:text-slate-100 truncate" x-text="r.name"></span>
                                    <span class="block font-mono text-[10px] text-slate-400" x-text="r.sku + ' · Available: ' + r.balance + ' pcs'"></span>
                                    <input type="hidden" :name="'items[' + i + '][product_id]'" :value="r.product_id">
                                </td>

                                {{-- Quantity Stepper & Max Button --}}
                                <td class="p-2.5">
                                    <div class="flex items-center justify-center gap-1 max-w-[170px] mx-auto">
                                        <div class="flex items-center h-8 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 overflow-hidden flex-1">
                                            <button type="button" @click="decQty(r)" class="w-7 h-full grid place-items-center text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 font-black text-sm cursor-pointer">-</button>
                                            <input type="number" inputmode="decimal" :name="'items[' + i + '][quantity]'" min="0.001" step="any" :max="r.balance" x-model="r.quantity"
                                                   class="w-full text-center font-mono font-bold text-xs bg-transparent outline-none"/>
                                            <button type="button" @click="incQty(r)" class="w-7 h-full grid place-items-center text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 font-black text-sm cursor-pointer">+</button>
                                        </div>
                                        <button type="button" @click="setQtyMax(r)"
                                                title="Set to max stock"
                                                class="h-8 px-2 rounded-lg text-[10px] font-mono font-bold uppercase bg-violet-50 hover:bg-violet-100 dark:bg-violet-950/60 dark:hover:bg-violet-900/80 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800 transition cursor-pointer">
                                            Max
                                        </button>
                                    </div>
                                </td>

                                {{-- Unit Cost --}}
                                <td class="p-2.5 text-right font-mono text-slate-600 dark:text-slate-400 text-xs">
                                    <span x-text="'Ks ' + fmt(r.unit_cost)"></span>
                                </td>

                                {{-- Line Total --}}
                                <td class="p-2.5 text-right font-mono font-black text-xs text-violet-600 dark:text-violet-400 whitespace-nowrap"
                                    x-text="'Ks ' + fmt(lineTotal(r))"></td>

                                {{-- Delete Line --}}
                                <td class="p-2.5 text-center">
                                    <button type="button" @click="removeRow(i)"
                                            class="w-7 h-7 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/50 hover:text-rose-600 transition inline-grid place-items-center cursor-pointer"
                                            title="Remove item">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-slate-50 dark:bg-slate-800/60 border-t-2 border-slate-200 dark:border-slate-700 font-bold">
                        <tr class="divide-x divide-slate-200 dark:divide-slate-700 text-slate-900 dark:text-slate-100">
                            <td colspan="2" class="p-2.5 text-right uppercase text-[11px] font-black">
                                {{ __('messages.total') ?? 'Total' }}:
                            </td>
                            <td class="p-2.5 text-center font-mono font-black text-xs text-slate-900 dark:text-slate-100">
                                <span x-text="totalQty.toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' pcs'"></span>
                            </td>
                            <td></td>
                            <td class="p-2.5 text-right font-mono font-black text-xs text-violet-600 dark:text-violet-400">
                                <span x-text="'Ks ' + fmt(totalValue)"></span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Section 4: Sticky Bottom Action Bar (Purchases Style) --}}
        <div class="sticky bottom-0 z-20 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xs border-t border-slate-200/90 dark:border-slate-800 p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-lg">
            <div class="flex items-center gap-4 flex-wrap">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">{{ __('messages.items') }}</span>
                    <span class="text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 font-mono" x-text="rows.length + ' Items (' + totalQty.toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' pcs)'"></span>
                </div>
                <div class="border-l border-slate-200 dark:border-slate-700 pl-3">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Estimated Value</span>
                    <span class="text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400 font-mono" x-text="'Ks ' + fmt(totalValue)"></span>
                </div>
            </div>

            <div class="flex items-center gap-2 justify-end">
                <a href="{{ route('pos.transfers.index', $storeRouteParams) }}"
                   class="px-4 py-2 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition">
                    {{ __('messages.cancel') }}
                </a>
                <button type="submit" :disabled="!canSubmit"
                        class="px-5 py-2 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition flex items-center gap-1.5 active:scale-95 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <span>🔄</span>
                    <span>{{ __('messages.create_transfer') }}</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
