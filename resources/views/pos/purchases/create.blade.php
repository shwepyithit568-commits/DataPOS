@extends('layouts.admin.app')

@section('title', __('messages.po_create_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6"
     x-data="{
         rows: [],
         q: '',
         results: [],
         open: false,
         searching: false,
         searched: false,
         filterBrand: '',
         filterCategory: '',
         supplierId: '',
         supplierName: '',
         discountAmount: 0,
         deliveryFee: 0,
         paymentMode: 'credit',
         paidAmount: 0,
         voucherPreviews: [],
         get effectivePaid() {
             const total = this.netTotal;
             if (this.paymentMode !== 'cash') return 0;
             let amt = parseFloat(this.paidAmount) || 0;
             if (amt <= 0) amt = total;
             return Math.min(Math.max(0, amt), total);
         },
         get paidStatus() {
             if (this.paymentMode !== 'cash') return '';
             return this.effectivePaid >= this.netTotal ? 'paid' : 'partial';
         },
         setPaymentMode(mode) {
             this.paymentMode = mode;
             if (mode === 'cash' && (!this.paidAmount || this.paidAmount <= 0)) {
                 this.paidAmount = this.netTotal;
             }
         },
         get canSearch() { return this.q.trim() !== '' || this.filterBrand !== '' || this.filterCategory !== ''; },
         async search(open = true) {
             if (!this.canSearch) { this.results = []; this.open = false; this.searched = false; return; }
             this.searching = true;
             try {
                 const params = new URLSearchParams();
                 if (this.q.trim() !== '') params.set('q', this.q.trim());
                 if (this.filterBrand) params.set('brand_id', this.filterBrand);
                 if (this.filterCategory) params.set('category_id', this.filterCategory);
                 const res = await fetch('{{ url('/store/' . $store->slug . '/pos/purchases/products') }}?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                 const json = await res.json();
                 this.results = (json.results || []).slice(0, 10);
                 this.searched = true;
                 this.open = open;
             } finally {
                 this.searching = false;
             }
         },
         onFilterChange() { this.search(true); },
         enterPick() {
             if (this.open && this.results.length > 0) { this.addProduct(this.results[0]); }
         },
         addProduct(p) {
             const variantId = p.type === 'variant' ? p.id : null;
             const existing = this.rows.find(r => r.product_id === p.product_id && r.product_variant_id === variantId);
             if (existing) {
                 existing.quantity = String((parseFloat(existing.quantity) || 0) + 1);
             } else {
                 this.rows.push({
                     product_id: p.product_id,
                     product_variant_id: variantId,
                     name: p.name,
                     sku: p.sku,
                     balance: p.balance || 0,
                     quantity: '1',
                     unit_cost: p.cost || ''
                 });
             }
             this.q = '';
             this.results = [];
             this.open = false;
             this.searched = false;
             this.$nextTick(() => { this.$refs.searchInput && this.$refs.searchInput.focus(); });
         },
         removeRow(i) { this.rows.splice(i, 1); },
         incQty(r) { r.quantity = String((parseFloat(r.quantity) || 0) + 1); },
         decQty(r) { r.quantity = String(Math.max(1, (parseFloat(r.quantity) || 0) - 1)); },
         lineTotal(r) { return (parseFloat(r.quantity) || 0) * (parseFloat(r.unit_cost) || 0); },
         fmt(n) { return Number(n).toLocaleString(undefined, { maximumFractionDigits: 2 }); },
         get totalQty() { return this.rows.reduce((s, r) => s + (parseFloat(r.quantity) || 0), 0); },
         get subtotal() { return this.rows.reduce((s, r) => s + this.lineTotal(r), 0); },
         get netTotal() {
             const sub = this.subtotal;
             const disc = parseFloat(this.discountAmount) || 0;
             const deliv = parseFloat(this.deliveryFee) || 0;
             return Math.max(0, sub - disc + deliv);
         },
         get valid() { return this.rows.length > 0 && this.rows.every(r => r.product_id && (parseFloat(r.quantity) || 0) > 0 && (parseFloat(r.unit_cost) || 0) >= 0); },
         handleFiles(event) {
             const files = Array.from(event.target.files || []);
             this.voucherPreviews = [];
             files.forEach((file, idx) => {
                 if (file.type.startsWith('image/')) {
                     const reader = new FileReader();
                     reader.onload = (e) => {
                         this.voucherPreviews.push({ name: file.name, url: e.target.result, isPdf: false });
                     };
                     reader.readAsDataURL(file);
                 } else if (file.type === 'application/pdf') {
                     this.voucherPreviews.push({ name: file.name, url: '', isPdf: true });
                 }
             });
         }
     }">

    {{-- 1. Top Header Banner (Ultra-Dense 36px) --}}
    <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none">
        <div class="flex items-center gap-2 min-w-0">
            <span class="w-7 h-7 rounded bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-200 dark:border-sky-800 grid place-items-center text-sm font-black shrink-0">
                🛒
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate">
                        {{ __('messages.po_create_title') }}
                    </h1>
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        {{ $store->name }}
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 font-mono truncate hidden sm:block">
                    {{ __('messages.po_create_hint') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap shrink-0">
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
               class="h-7 px-2.5 rounded text-xs font-bold text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 hover:bg-sky-100 dark:hover:bg-sky-900/50 transition inline-flex items-center gap-1 shadow-2xs">
                <span>🛒</span>
                <span>{{ __('messages.po_list_title') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/pos') }}"
               class="h-7 px-2 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1 shadow-2xs">
                <span>←</span>
                <span>{{ __('messages.back_to_pos') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. 4-Column Compact Centered Stat Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1">
        {{-- Card 1: Selected Products Count --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-sm font-black shrink-0">
                📦
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none">
                    {{ __('messages.products') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-slate-900 dark:text-slate-100 tabular-nums mt-0.5" x-text="rows.length + ' Items (' + fmt(totalQty) + ' Qty)'">
                </div>
            </div>
        </div>

        {{-- Card 2: Items Subtotal --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 flex items-center justify-center text-sm font-black shrink-0">
                🧾
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none">
                    {{ __('messages.po_subtotal') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-slate-900 dark:text-slate-100 tabular-nums mt-0.5 truncate" x-text="'Ks ' + fmt(subtotal)">
                </div>
            </div>
        </div>

        {{-- Card 3: Net Total --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-emerald-200/90 dark:border-emerald-900/50 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm font-black shrink-0">
                💰
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 leading-none">
                    {{ __('messages.po_net_total') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-emerald-600 dark:text-emerald-400 tabular-nums mt-0.5 truncate" x-text="'Ks ' + fmt(netTotal)">
                </div>
            </div>
        </div>

        {{-- Card 4: Supplier Selection Status --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm font-black shrink-0">
                🏭
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none">
                    {{ __('messages.po_supplier') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-slate-800 dark:text-slate-200 tabular-nums mt-0.5 truncate"
                     x-text="supplierName ? supplierName : '{{ __('messages.po_no_supplier') }}'">
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Main Form Card --}}
    <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases') }}" enctype="multipart/form-data"
          class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded shadow-2xs overflow-hidden space-y-0">
        @csrf

        {{-- Section 1: Supplier, Reference, Discount, Delivery & Voucher Uploads --}}
        <div class="p-2 sm:p-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 space-y-2">
            {{-- Row 1: Supplier, Reference, Notes --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5 sm:gap-2">
                <div>
                    <label for="po-supplier" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                        {{ __('messages.po_supplier') }}
                    </label>
                    <select id="po-supplier" name="supplier_id" x-model="supplierId"
                            @change="supplierName = $event.target.options[$event.target.selectedIndex].text"
                            class="w-full h-7 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-xs font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-sky-500">
                        <option value="">{{ __('messages.po_no_supplier') }}</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}{{ $supplier->phone ? " ({$supplier->phone})" : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="po-reference" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                        {{ __('messages.receiving_reference') }}
                    </label>
                    <input id="po-reference" type="text" name="reference" maxlength="100"
                           placeholder="{{ __('messages.receiving_reference_placeholder') }}"
                           class="w-full h-7 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-xs font-mono text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-sky-500">
                </div>
                <div>
                    <label for="po-notes" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                        {{ __('messages.notes') }}
                    </label>
                    <input id="po-notes" type="text" name="notes" maxlength="1000"
                           placeholder="{{ __('messages.notes_placeholder') }}"
                           class="w-full h-7 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-xs text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-sky-500">
                </div>
            </div>

            {{-- Row 2: Trade Discount, Delivery Fee & Voucher Image Uploads --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5 sm:gap-2 pt-1.5 border-t border-slate-200/60 dark:border-slate-700/60">
                {{-- Wholesale Trade Discount --}}
                <div>
                    <label for="po-discount" class="block text-xs font-bold text-rose-600 dark:text-rose-400 mb-0.5 flex items-center justify-between">
                        <span>{{ __('messages.po_discount_amount') }} (Ks)</span>
                        <span class="text-[10px] font-normal text-slate-400">(-) နုတ်ပေးမည်</span>
                    </label>
                    <input id="po-discount" type="number" inputmode="decimal" name="discount_amount" min="0" step="any"
                           x-model.number="discountAmount" placeholder="0"
                           class="w-full h-7 rounded border border-rose-200 dark:border-rose-900/60 bg-white dark:bg-slate-800 px-2 py-1 text-xs font-mono font-bold text-rose-600 dark:text-rose-400 outline-none focus:ring-1 focus:ring-rose-500">
                </div>

                {{-- Delivery Fee / Shipping Cost --}}
                <div>
                    <label for="po-delivery" class="block text-xs font-bold text-amber-600 dark:text-amber-400 mb-0.5 flex items-center justify-between">
                        <span>{{ __('messages.po_delivery_fee') }} (Ks)</span>
                        <span class="text-[10px] font-normal text-slate-400">(+) ပေါင်းပေးမည်</span>
                    </label>
                    <input id="po-delivery" type="number" inputmode="decimal" name="delivery_fee" min="0" step="any"
                           x-model.number="deliveryFee" placeholder="0"
                           class="w-full h-7 rounded border border-amber-200 dark:border-amber-900/60 bg-white dark:bg-slate-800 px-2 py-1 text-xs font-mono font-bold text-amber-600 dark:text-amber-400 outline-none focus:ring-1 focus:ring-amber-500">
                </div>

                {{-- Multiple Voucher Photos Upload Box --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-0.5 flex items-center justify-between">
                        <span>📷 {{ __('messages.po_voucher_images') }}</span>
                        <span class="text-[10px] text-slate-400 font-mono" x-text="voucherPreviews.length + ' files'"></span>
                    </label>
                    <input type="file" name="voucher_images[]" multiple accept="image/*,.pdf" capture="environment"
                           @change="handleFiles($event)" id="po-voucher-files" class="sr-only">
                    <label for="po-voucher-files"
                           class="w-full h-7 rounded border border-dashed border-sky-300 dark:border-sky-700 bg-sky-50/50 dark:bg-sky-950/20 hover:bg-sky-100/60 dark:hover:bg-sky-900/30 px-2 py-1 text-xs font-bold text-sky-700 dark:text-sky-300 flex items-center justify-center gap-1.5 cursor-pointer transition">
                        <span class="text-sm">📎</span>
                        <span class="truncate text-[11px]">{{ __('messages.po_voucher_upload_hint') }}</span>
                    </label>
                </div>
            </div>

            {{-- Row 3: Payment Terms (Paid-now / Credit) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 sm:gap-2 pt-1.5 border-t border-slate-200/60 dark:border-slate-700/60">
                {{-- Payment Mode Toggle: Cash (paid now) vs Credit --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                        💳 {{ __('messages.po_payment_mode') }}
                    </label>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="setPaymentMode('cash')"
                                :class="paymentMode === 'cash' ? 'bg-emerald-600 text-white border-emerald-700' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-300 dark:border-slate-700'"
                                class="flex-1 h-7 px-2 rounded text-xs font-bold border transition flex items-center justify-center gap-1 cursor-pointer">
                            <span>💰</span>
                            <span>{{ __('messages.po_paid_now') }}</span>
                        </button>
                        <button type="button" @click="setPaymentMode('credit')"
                                :class="paymentMode === 'credit' ? 'bg-amber-500 text-white border-amber-600' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-300 dark:border-slate-700'"
                                class="flex-1 h-7 px-2 rounded text-xs font-bold border transition flex items-center justify-center gap-1 cursor-pointer">
                            <span>🕐</span>
                            <span>{{ __('messages.po_credit') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Paid Amount (only when cash/paid-now) --}}
                <div x-show="paymentMode === 'cash'" x-cloak>
                    <label for="po-paid-amount" class="block text-xs font-bold text-emerald-600 dark:text-emerald-400 mb-0.5 flex items-center justify-between">
                        <span>{{ __('messages.po_paid_amount') }} (Ks)</span>
                        <span class="text-[10px] font-normal text-slate-400">(≤ {{ __('messages.po_net_total') }})</span>
                    </label>
                    <input id="po-paid-amount" type="number" inputmode="decimal" min="0" step="any"
                           x-model.number="paidAmount" placeholder="0"
                           class="w-full h-7 rounded border border-emerald-200 dark:border-emerald-900/60 bg-white dark:bg-slate-800 px-2 py-1 text-xs font-mono font-bold text-emerald-700 dark:text-emerald-400 outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                {{-- Hidden payment fields submitted only when paid-now --}}
                <input type="hidden" name="payment_status" :value="paidStatus">
                <input type="hidden" name="paid_amount" :value="effectivePaid">
            </div>

            {{-- Voucher Previews Carousel / Grid (If files selected) --}}
            <div x-show="voucherPreviews.length > 0" x-cloak class="pt-1.5 border-t border-slate-200/60 dark:border-slate-700/60">
                <p class="text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1 flex items-center gap-1">
                    <span>🖼️ Selected Vouchers / Invoices:</span>
                    <span class="text-slate-400 font-normal" x-text="'(' + voucherPreviews.length + ' items)'"></span>
                </p>
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
                    <template x-for="(vp, idx) in voucherPreviews" :key="idx">
                        <div class="relative shrink-0 w-16 h-16 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden shadow-2xs flex flex-col items-center justify-center p-0.5 group">
                            <template x-if="!vp.isPdf">
                                <img :src="vp.url" class="w-full h-full object-cover rounded">
                            </template>
                            <template x-if="vp.isPdf">
                                <div class="text-center p-0.5">
                                    <span class="text-xl">📄</span>
                                    <span class="text-[8px] block text-slate-500 truncate max-w-[55px]" x-text="vp.name"></span>
                                </div>
                            </template>
                            <span class="absolute bottom-0.5 right-0.5 px-1 rounded bg-black/60 text-[8px] text-white font-mono" x-text="idx + 1"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Section 2: Product Finder & Live Search --}}
        <div class="p-2 sm:p-2.5 border-b border-slate-100 dark:border-slate-800">
            <div class="flex flex-col sm:flex-row gap-1.5">
                {{-- Live Search Autocomplete Box --}}
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8" stroke-width="2"/><line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2"/></svg>
                    </span>
                    <input x-ref="searchInput" type="text" x-model="q" @input.debounce.250ms="search(true)"
                           @focus="open = results.length > 0"
                           @keydown.enter.prevent="enterPick()"
                           @keydown.escape.prevent="open = false"
                           placeholder="{{ __('messages.receiving_product_placeholder') }}"
                           autocomplete="off"
                           class="w-full h-7 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 pl-8 pr-7 py-1 text-xs font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-sky-500"/>

                    <button type="button" x-show="q && !searching" x-cloak
                            @click="q = ''; results = []; open = false; searched = false; $refs.searchInput.focus()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-sm cursor-pointer">
                        &times;
                    </button>
                    <svg x-show="searching" x-cloak class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-sky-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>

                    {{-- Autocomplete Results Dropdown --}}
                    <div x-show="open" x-cloak @click.outside="open = false"
                         class="absolute z-30 mt-1 w-full rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl overflow-hidden">
                        <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="p in results" :key="p.id">
                                <button type="button" @click="addProduct(p)"
                                        class="w-full text-left px-2.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/80 flex items-center justify-between gap-2 transition cursor-pointer">
                                    <span class="min-w-0">
                                        <span class="block font-bold text-xs text-slate-900 dark:text-slate-100 truncate" x-text="p.name"></span>
                                        <span class="block text-[10px] font-mono text-slate-400 truncate" x-text="p.sku + ' · On Hand: ' + p.balance"></span>
                                    </span>
                                    <span class="shrink-0 text-xs font-black text-sky-600 dark:text-sky-400 font-mono" x-text="'Ks ' + Number(p.price).toLocaleString()"></span>
                                </button>
                            </template>
                            <p x-show="searched && results.length === 0 && !searching" x-cloak
                               class="px-2.5 py-2 text-center text-xs text-slate-400 font-bold">{{ __('messages.no_results') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Category & Brand Filters --}}
                <div class="flex items-center gap-1.5">
                    <select x-model="filterCategory" @change="onFilterChange()"
                            class="h-7 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200 outline-none focus:ring-1 focus:ring-sky-500">
                        <option value="">{{ __('messages.filter_all_categories') }}</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>

                    <select x-model="filterBrand" @change="onFilterChange()"
                            class="h-7 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-xs font-semibold text-slate-800 dark:text-slate-200 outline-none focus:ring-1 focus:ring-sky-500">
                        <option value="">{{ __('messages.filter_all_brands') }}</option>
                        @foreach ($brands as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Section 3: Selected Line Items Spreadsheet Table --}}
        <div class="p-2 sm:p-2.5 space-y-2">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <span>📋</span>
                    <span>{{ __('messages.products') }}</span>
                </h3>
                <span class="text-xs font-mono font-bold text-slate-400" x-text="rows.length + ' lines selected'"></span>
            </div>

            {{-- Empty State --}}
            <div x-show="rows.length === 0"
                 class="rounded border-2 border-dashed border-slate-200 dark:border-slate-800 py-6 text-center bg-slate-50/50 dark:bg-slate-900/30">
                <div class="text-2xl mb-1 opacity-50">🛒</div>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.receiving_product_placeholder') }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">{{ __('messages.po_scan_prompt') }}</p>
            </div>

            {{-- Line Items Table for Tablet & Desktop --}}
            <div x-show="rows.length > 0" class="overflow-x-auto border border-slate-200/90 dark:border-slate-800 rounded shadow-2xs">
                <table class="w-full text-left text-xs border-collapse font-sans">
                    <thead class="bg-slate-100 dark:bg-slate-800/90 text-slate-700 dark:text-slate-300 font-black uppercase text-[11px]">
                        <tr class="divide-x divide-slate-200 dark:divide-slate-700">
                            <th class="py-1.5 px-2 text-center w-8">#</th>
                            <th class="py-1.5 px-2.5 min-w-[180px]">{{ __('messages.products') }}</th>
                            <th class="py-1.5 px-2 text-center min-w-[130px]">{{ __('messages.reports_qty') }}</th>
                            <th class="py-1.5 px-2 text-right min-w-[120px]">{{ __('messages.po_unit_cost') }} (Ks)</th>
                            <th class="py-1.5 px-2.5 text-right min-w-[120px]">{{ __('messages.receiving_total') }}</th>
                            <th class="py-1.5 px-2 text-center w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                        <template x-for="(r, i) in rows" :key="r.product_id + '-' + (r.product_variant_id || 0)">
                            <tr class="divide-x divide-slate-200/60 dark:divide-slate-800 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <td class="py-1 px-2 text-center font-mono font-bold text-slate-400 text-[11px]" x-text="i + 1"></td>
                                
                                {{-- Product Name & SKU --}}
                                <td class="py-1 px-2.5">
                                    <span class="block font-bold text-xs text-slate-900 dark:text-slate-100 truncate" x-text="r.name"></span>
                                    <span class="block font-mono text-[10px] text-slate-400" x-text="r.sku + ' · Bal: ' + r.balance"></span>
                                    <input type="hidden" :name="'items[' + i + '][product_id]'" :value="r.product_id">
                                    <input type="hidden" :name="'items[' + i + '][product_variant_id]'" :value="r.product_variant_id || ''">
                                </td>

                                {{-- Quantity Stepper --}}
                                <td class="py-1 px-2">
                                    <div class="flex items-center justify-center h-6 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 overflow-hidden max-w-[110px] mx-auto">
                                        <button type="button" @click="decQty(r)" class="w-6 h-full grid place-items-center text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 font-black text-xs cursor-pointer">-</button>
                                        <input type="number" inputmode="decimal" :name="'items[' + i + '][quantity]'" min="0.001" step="any" x-model="r.quantity"
                                               class="w-full text-center font-mono font-bold text-xs bg-transparent outline-none"/>
                                        <button type="button" @click="incQty(r)" class="w-6 h-full grid place-items-center text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 font-black text-xs cursor-pointer">+</button>
                                    </div>
                                </td>

                                {{-- Unit Cost --}}
                                <td class="py-1 px-2 text-right">
                                    <input type="number" inputmode="decimal" :name="'items[' + i + '][unit_cost]'" min="0" step="any" x-model="r.unit_cost"
                                           class="w-full max-w-[110px] h-6 rounded border border-slate-200 dark:border-slate-700 px-1.5 py-0.5 text-right font-mono font-bold text-xs bg-slate-50 dark:bg-slate-800 outline-none focus:ring-1 focus:ring-sky-500 inline-block"/>
                                </td>

                                {{-- Line Total --}}
                                <td class="py-1 px-2.5 text-right font-mono font-black text-xs text-sky-600 dark:text-sky-400 whitespace-nowrap"
                                    x-text="'Ks ' + fmt(lineTotal(r))"></td>

                                {{-- Delete Line --}}
                                <td class="py-1 px-2 text-center">
                                    <button type="button" @click="removeRow(i)"
                                            class="w-6 h-6 rounded text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/50 hover:text-rose-600 transition inline-grid place-items-center cursor-pointer text-xs"
                                            title="Remove item">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Section 4: Sticky Bottom Action Bar with Detailed Financial Breakdown --}}
        <div class="sticky bottom-0 z-20 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xs border-t border-slate-200/90 dark:border-slate-800 p-2 sm:p-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 shadow-lg">
            <div class="flex items-center gap-3 flex-wrap">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block leading-none">{{ __('messages.po_subtotal') }}</span>
                    <span class="text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 font-mono mt-0.5 block" x-text="'Ks ' + fmt(subtotal)"></span>
                </div>
                <template x-if="discountAmount > 0">
                    <div class="border-l border-slate-200 dark:border-slate-700 pl-2.5">
                        <span class="text-[10px] uppercase font-bold text-rose-500 block leading-none">{{ __('messages.discount') }} (-)</span>
                        <span class="text-xs sm:text-sm font-bold text-rose-600 font-mono mt-0.5 block" x-text="'- Ks ' + fmt(discountAmount)"></span>
                    </div>
                </template>
                <template x-if="deliveryFee > 0">
                    <div class="border-l border-slate-200 dark:border-slate-700 pl-2.5">
                        <span class="text-[10px] uppercase font-bold text-amber-500 block leading-none">{{ __('messages.delivery') ?? 'ပို့ဆောင်ခ' }} (+)</span>
                        <span class="text-xs sm:text-sm font-bold text-amber-600 font-mono mt-0.5 block" x-text="'+ Ks ' + fmt(deliveryFee)"></span>
                    </div>
                </template>
                <div class="border-l border-slate-200 dark:border-slate-700 pl-2.5">
                    <span class="text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400 block leading-none">{{ __('messages.po_net_total') }}</span>
                    <span class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5 block" x-text="'Ks ' + fmt(netTotal)"></span>
                </div>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
                <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
                   class="h-7 px-3 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition inline-flex items-center justify-center cursor-pointer">
                    {{ __('messages.cancel') }}
                </a>
                <button type="submit" :disabled="!valid"
                        class="h-7 px-4 rounded text-xs font-black text-white bg-sky-600 hover:bg-sky-500 shadow-2xs hover:shadow-sky-500/20 transition active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer inline-flex items-center gap-1">
                    <span>📋</span>
                    <span>{{ __('messages.po_save_pending') }}</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
