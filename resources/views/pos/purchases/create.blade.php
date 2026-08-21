@extends('layouts.pos.app')

@section('content')
    <div class="mx-auto max-w-5xl px-3 sm:px-4 py-4 sm:py-6 space-y-4 sm:space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ __('messages.sidebar_purchases') }}</p>
                <h1 class="text-lg sm:text-xl font-black mt-0.5 truncate">{{ __('messages.po_create_title') }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 hidden sm:block">{{ __('messages.po_create_hint') }}</p>
            </div>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
               class="shrink-0 rounded-xl px-3 sm:px-4 py-2.5 text-sm font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                ← {{ __('messages.back') }}
            </a>
        </div>

        @if (session('error'))
            <div class="rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-300 px-4 py-3 text-sm font-semibold">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        <div x-data="{
                  rows: [],
                  q: '',
                  results: [],
                  open: false,
                  searching: false,
                  searched: false,
                  filterBrand: '',
                  filterCategory: '',
                  get canSearch() { return this.q.trim() !== '' || this.filterBrand !== '' || this.filterCategory !== ''; },
                  async search(open = true) {
                      if (!this.canSearch) { this.results = []; this.open = false; this.searched = false; return; }
                      this.searching = true;
                      try {
                          const params = new URLSearchParams();
                          if (this.q.trim() !== '') params.set('q', this.q.trim());
                          if (this.filterBrand) params.set('brand_id', this.filterBrand);
                          if (this.filterCategory) params.set('category_id', this.filterCategory);
                          const res = await fetch('{{ url("/store/" . $store->slug . "/pos/purchases/products") }}?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
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
                      // Barcode scanner / Enter key: pick first result when list is open.
                      if (this.open && this.results.length > 0) { this.addProduct(this.results[0]); }
                  },
                  addProduct(p) {
                      const variantId = p.type === 'variant' ? p.id : null;
                      const existing = this.rows.find(r => r.product_id === p.product_id && r.product_variant_id === variantId);
                      if (existing) {
                          existing.quantity = String((parseFloat(existing.quantity) || 0) + 1);
                      } else {
                          this.rows.push({ product_id: p.product_id, product_variant_id: variantId, name: p.name, sku: p.sku, quantity: '1', unit_cost: p.cost || '' });
                      }
                      this.q = ''; this.results = []; this.open = false; this.searched = false;
                      this.$nextTick(() => { this.$refs.searchInput && this.$refs.searchInput.focus(); });
                  },
                  removeRow(i) { this.rows.splice(i, 1); },
                  incQty(r) { r.quantity = String((parseFloat(r.quantity) || 0) + 1); },
                  decQty(r) { r.quantity = String(Math.max(0, (parseFloat(r.quantity) || 0) - 1)); },
                  lineTotal(r) { return (parseFloat(r.quantity) || 0) * (parseFloat(r.unit_cost) || 0); },
                  fmt(n) { return n.toLocaleString(undefined, { maximumFractionDigits: 2 }); },
                  get totalQty() { return this.rows.reduce((s, r) => s + (parseFloat(r.quantity) || 0), 0); },
                  get totalCost() { return this.rows.reduce((s, r) => s + this.lineTotal(r), 0); },
                  get valid() { return this.rows.some(r => r.product_id && (parseFloat(r.quantity) || 0) > 0 && (parseFloat(r.unit_cost) || 0) > 0); }
              }">
        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
              class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden space-y-0">
            @csrf

            {{-- ===== Section: Supplier / Reference / Notes ===== --}}
            <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="po-supplier" class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.po_supplier') }}</label>
                        <select id="po-supplier" name="supplier_id"
                                class="w-full h-11 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-base sm:text-sm font-semibold focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition">
                            <option value="">{{ __('messages.po_no_supplier') }}</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}{{ $supplier->phone ? " ({$supplier->phone})" : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="po-reference" class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.receiving_reference') }}</label>
                        <input id="po-reference" type="text" name="reference" maxlength="100" placeholder="{{ __('messages.receiving_reference_placeholder') }}"
                               class="w-full h-11 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-base sm:text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition">
                    </div>
                    <div>
                        <label for="po-notes" class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.notes') }}</label>
                        <input id="po-notes" type="text" name="notes" maxlength="1000" placeholder="{{ __('messages.notes_placeholder') }}"
                               class="w-full h-11 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-base sm:text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition">
                    </div>
                </div>
            </div>

            {{-- ===== Section: Product finder (search + filters) ===== --}}
            <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                <div class="flex flex-col sm:flex-row gap-2">
                    {{-- Search input --}}
                    <div class="relative flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input x-ref="searchInput" type="text" x-model="q" @input.debounce.250ms="search(true)"
                               @focus="open = results.length > 0"
                               @keydown.enter.prevent="enterPick()"
                               @keydown.escape.prevent="open = false"
                               placeholder="{{ __('messages.receiving_product_placeholder') }}"
                               autocomplete="off"
                               class="w-full h-12 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 pl-10 pr-10 text-base font-semibold focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"/>

                        {{-- Clear / spinner --}}
                        <button type="button" x-show="q && !searching" x-cloak @click="q = ''; results = []; open = false; searched = false; $refs.searchInput.focus()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 grid place-items-center rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-600 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <svg x-show="searching" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-sky-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>

                        {{-- Results dropdown --}}
                        <div x-show="open" x-cloak @click.outside="open = false"
                             class="absolute z-30 mt-1.5 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl overflow-hidden">
                            <div class="max-h-72 overflow-y-auto overscroll-contain">
                                <template x-for="p in results" :key="p.id">
                                    <button type="button" @click="addProduct(p)"
                                            class="w-full text-left px-3 py-3 active:bg-sky-50 dark:active:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 last:border-0 transition">
                                        <span class="min-w-0">
                                            <span class="block font-semibold text-sm truncate" x-text="p.name"></span>
                                            <span class="block text-[10px] font-mono text-slate-400 truncate" x-text="p.sku + ' · ' + p.balance + ' {{ __('messages.reports_units') }}'"></span>
                                        </span>
                                        <span class="shrink-0 text-xs font-bold text-sky-600" x-text="'Ks ' + Number(p.price).toLocaleString()"></span>
                                    </button>
                                </template>
                                <p x-show="searched && results.length === 0 && !searching" x-cloak
                                   class="px-3 py-4 text-center text-xs font-bold text-slate-400">{{ __('messages.no_results') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Filters --}}
                    <div class="grid grid-cols-2 sm:flex gap-2">
                        <div>
                            <select x-model="filterCategory" @change="onFilterChange()" aria-label="{{ __('messages.filter_category') }}"
                                    class="w-full sm:w-40 h-12 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 text-sm font-semibold focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition">
                                <option value="">{{ __('messages.filter_all_categories') }}</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select x-model="filterBrand" @change="onFilterChange()" aria-label="{{ __('messages.filter_brand') }}"
                                    class="w-full sm:w-40 h-12 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 text-sm font-semibold focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition">
                                <option value="">{{ __('messages.filter_all_brands') }}</option>
                                @foreach ($brands as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Section: Picked items ===== --}}
            <div class="p-4 sm:p-5 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('messages.products') }}</h2>
                    <span class="text-[11px] font-bold text-slate-400" x-show="rows.length > 0" x-text="rows.length + ' {{ __('messages.reports_units') }}'"></span>
                </div>

                {{-- Empty state --}}
                <div x-show="rows.length === 0"
                     class="rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700 py-8 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto w-8 h-8 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m-4.5-6.75l.375 6.75m8.625-6.75l-.375 6.75M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                    <p class="mt-2 text-xs font-bold text-slate-400">{{ __('messages.receiving_product_placeholder') }}</p>
                </div>

                {{-- Item cards --}}
                <div class="space-y-3">
                    <template x-for="(r, i) in rows" :key="r.product_id + '-' + (r.product_variant_id || 0)">
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/30 p-3 space-y-2">

                            {{-- Item header --}}
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-6 h-6 shrink-0 rounded-lg bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-400 grid place-items-center text-[11px] font-black"
                                          x-text="i + 1"></span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-bold truncate" x-text="r.name"></span>
                                        <span class="block text-[10px] font-mono font-bold text-slate-400 truncate" x-text="r.sku"></span>
                                    </span>
                                </div>
                                <button type="button" @click="removeRow(i)"
                                        :aria-label="'{{ __('messages.delete') }} ' + (i + 1)"
                                        class="shrink-0 w-9 h-9 grid place-items-center rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-600 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>

                            <input type="hidden" :name="'items[' + i + '][product_id]'" :value="r.product_id">
                            <input type="hidden" :name="'items[' + i + '][product_variant_id]'" :value="r.product_variant_id || ''">

                            {{-- Qty / unit cost / line total --}}
                            <div class="grid grid-cols-2 sm:grid-cols-[minmax(0,150px)_minmax(0,170px)_1fr] gap-2 sm:gap-3 items-end">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-0.5 uppercase tracking-wide">{{ __('messages.reports_qty') }}</label>
                                    <div class="flex h-11 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden focus-within:ring-2 focus-within:ring-sky-500 transition">
                                        <button type="button" @click="decQty(r)" tabindex="-1"
                                                class="w-10 shrink-0 grid place-items-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 active:bg-sky-50 dark:active:bg-slate-600 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                                            </svg>
                                        </button>
                                        <input :id="'po-qty-' + i" type="number" inputmode="decimal" :name="'items[' + i + '][quantity]'" min="0.001" step="any" x-model="r.quantity"
                                               class="w-full min-w-0 text-center text-base sm:text-sm font-bold bg-transparent outline-none"/>
                                        <button type="button" @click="incQty(r)" tabindex="-1"
                                                class="w-10 shrink-0 grid place-items-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 active:bg-sky-50 dark:active:bg-slate-600 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-0.5 uppercase tracking-wide">{{ __('messages.po_unit_cost') }} (Ks)</label>
                                    <input type="number" inputmode="decimal" :name="'items[' + i + '][unit_cost]'" min="0" step="any" x-model="r.unit_cost"
                                           class="w-full h-11 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-right text-base sm:text-sm font-bold focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"/>
                                </div>
                                <div class="col-span-2 sm:col-span-1">
                                    <label class="block text-[10px] font-bold text-slate-400 mb-0.5 uppercase tracking-wide">{{ __('messages.receiving_total') }}</label>
                                    <div class="h-11 px-3 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-100 dark:border-sky-900/50 flex items-center justify-end">
                                        <span class="text-sm sm:text-base font-black text-sky-700 dark:text-sky-400"
                                              x-text="'Ks ' + fmt(lineTotal(r))"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Totals --}}
                <div class="rounded-xl bg-slate-100 dark:bg-slate-800/60 px-4 py-3 text-sm flex items-center justify-between gap-3" x-show="rows.length > 0" x-cloak>
                    <span class="text-slate-500 dark:text-slate-400">{{ __('messages.receiving_total') }}
                        <b class="text-slate-700 dark:text-slate-200" x-text="totalQty.toLocaleString(undefined, { maximumFractionDigits: 3 })"></b>
                        {{ __('messages.reports_units') }}
                    </span>
                    <span class="text-base sm:text-lg font-black" x-text="'Ks ' + fmt(totalCost)"></span>
                </div>
            </div>

            {{-- ===== Sticky action bar ===== --}}
            <div class="sticky bottom-0 z-20 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-t border-slate-200 dark:border-slate-800 px-4 sm:px-5 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))]">
                <div class="flex items-center gap-3">
                    <div class="min-w-0 flex-1 sm:flex-none sm:w-40">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('messages.total') }}</p>
                        <p class="text-lg sm:text-xl font-black truncate" x-text="'Ks ' + fmt(totalCost)"></p>
                        <p class="text-[10px] text-slate-400" x-text="totalQty.toLocaleString(undefined, { maximumFractionDigits: 3 }) + ' {{ __('messages.reports_units') }}'"></p>
                    </div>
                    <button type="submit" :disabled="!valid"
                            :class="valid ? 'bg-sky-600 hover:bg-sky-500 active:bg-sky-700' : 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed'"
                            class="flex-1 sm:flex-none sm:min-w-56 rounded-xl px-6 h-12 text-sm font-black text-white transition">
                        📋 {{ __('messages.po_save_pending') }}
                    </button>
                </div>
            </div>
        </form>
        </div>
    </div>
@endsection
