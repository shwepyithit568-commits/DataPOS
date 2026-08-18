@extends('layouts.pos.app')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-6 space-y-6">

        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.sidebar_purchases') }}</p>
                <h1 class="text-xl font-black mt-0.5">{{ __('messages.po_create_title') }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('messages.po_create_hint') }}</p>
            </div>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
               class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                ← {{ __('messages.back') }}
            </a>
        </div>

        @if (session('error'))
            <div class="rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-300 px-4 py-3 text-sm font-semibold">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
              class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm space-y-5"
              x-data="{
                  rows: [{ q: '', results: [], open: false, product_id: '', product_variant_id: '', name: '', sku: '', quantity: '', unit_cost: '' }],
                  async search(r) {
                      if (r.q.trim() === '') { r.results = []; r.open = false; return; }
                      const res = await fetch('{{ url('/store/' . $store->slug . '/pos/products') }}?q=' + encodeURIComponent(r.q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                      const json = await res.json();
                      r.results = (json.results || []).slice(0, 8);
                      r.open = true;
                  },
                  pick(r, p) {
                      r.product_id = p.product_id;
                      r.product_variant_id = p.type === 'variant' ? p.id : null;
                      r.name = p.name;
                      r.sku = p.sku;
                      r.q = p.name;
                      r.quantity = '1';
                      r.unit_cost = p.cost || '';
                      r.results = []; r.open = false;
                  },
                  addRow() { this.rows.push({ q: '', results: [], open: false, product_id: '', product_variant_id: '', name: '', sku: '', quantity: '', unit_cost: '' }); },
                  removeRow(i) { if (this.rows.length > 1) this.rows.splice(i, 1); },
                  get totalQty() { return this.rows.reduce((s, r) => s + (parseFloat(r.quantity) || 0), 0); },
                  get totalCost() { return this.rows.reduce((s, r) => s + (parseFloat(r.quantity) || 0) * (parseFloat(r.unit_cost) || 0), 0); },
                  get valid() { return this.rows.some(r => r.product_id && (parseFloat(r.quantity) || 0) > 0 && (parseFloat(r.unit_cost) || 0) > 0); }
              }">
            @csrf

            {{-- Supplier + reference --}}
            <div class="flex items-center gap-2 flex-wrap">
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs font-bold text-slate-500 mb-1">{{ __('messages.po_supplier') }}</label>
                    <select name="supplier_id"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm font-semibold">
                        <option value="">{{ __('messages.po_no_supplier') }}</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}{{ $supplier->phone ? " ({$supplier->phone})" : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[140px]">
                    <label class="block text-xs font-bold text-slate-500 mb-1">{{ __('messages.receiving_reference') }}</label>
                    <input type="text" name="reference" maxlength="100" placeholder="{{ __('messages.receiving_reference_placeholder') }}"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                </div>
                <div class="flex-1 min-w-[140px]">
                    <label class="block text-xs font-bold text-slate-500 mb-1">{{ __('messages.notes') }}</label>
                    <input type="text" name="notes" maxlength="1000" placeholder="{{ __('messages.notes_placeholder') }}"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                </div>
            </div>

            {{-- PO lines --}}
            <div class="space-y-3">
                <template x-for="(r, i) in rows" :key="i">
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-3 space-y-2 relative">
                        <button type="button" @click="removeRow(i)" x-show="rows.length > 1"
                                class="absolute top-2 right-2 text-xs font-bold text-rose-500 hover:text-rose-700">✕</button>

                        {{-- Product search --}}
                        <div class="relative">
                            <input type="text" x-model="r.q" @input.debounce.250ms="search(r)" @focus="r.open = r.results.length > 0"
                                   placeholder="{{ __('messages.receiving_product_placeholder') }}"
                                   class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm font-semibold">
                            <div x-show="r.open" @click.outside="r.open = false"
                                 class="absolute z-20 mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-lg overflow-hidden">
                                <template x-for="p in r.results" :key="p.id">
                                    <button type="button" @click="pick(r, p)"
                                            class="w-full text-left px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 last:border-0">
                                        <span>
                                            <span class="block font-semibold text-sm" x-text="p.name"></span>
                                            <span class="block text-[10px] font-mono text-slate-400" x-text="p.sku + ' · on hand ' + p.balance"></span>
                                        </span>
                                        <span class="text-xs font-bold text-sky-600" x-text="'Ks ' + Number(p.price).toLocaleString()"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <input type="hidden" :name="'items[' + i + '][product_id]'" :value="r.product_id" :disabled="!r.product_id">
                        <input type="hidden" :name="'items[' + i + '][product_variant_id]'" :value="r.product_variant_id || ''" :disabled="!r.product_id">

                        <div class="flex items-center gap-2 flex-wrap" x-show="r.name">
                            <span class="text-xs font-bold text-slate-500" x-text="r.name"></span>
                            <label class="flex items-center gap-1 text-xs font-bold text-slate-500">
                                {{ __('messages.reports_qty') }}
                                <input type="number" :name="'items[' + i + '][quantity]'" min="0.001" step="any" x-model="r.quantity" :disabled="!r.product_id"
                                       class="w-24 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 py-1 text-right text-sm font-semibold">
                            </label>
                            <label class="flex items-center gap-1 text-xs font-bold text-slate-500">
                                {{ __('messages.po_unit_cost') }} (Ks)
                                <input type="number" :name="'items[' + i + '][unit_cost]'" min="0" step="any" x-model="r.unit_cost" :disabled="!r.product_id"
                                       class="w-32 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 py-1 text-right text-sm font-semibold">
                            </label>
                            <span class="ml-auto text-xs font-bold text-slate-400"
                                  x-text="r.quantity && r.unit_cost ? '= Ks ' + ((parseFloat(r.quantity) || 0) * (parseFloat(r.unit_cost) || 0)).toLocaleString(undefined, { maximumFractionDigits: 2 }) : ''"></span>
                        </div>
                    </div>
                </template>
            </div>

            <button type="button" @click="addRow"
                    class="w-full rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700 px-4 py-2.5 text-sm font-bold text-slate-500 hover:border-sky-400 hover:text-sky-600 transition">
                + {{ __('messages.receiving_add_line') }}
            </button>

            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 px-3 py-2.5 text-sm flex justify-between">
                <span class="text-slate-500">{{ __('messages.receiving_total') }}: <b x-text="totalQty.toLocaleString(undefined, { maximumFractionDigits: 3 })"></b> {{ __('messages.reports_units') }}</span>
                <span class="font-black" x-text="'Ks ' + totalCost.toLocaleString(undefined, { maximumFractionDigits: 2 })"></span>
            </div>

            <button type="submit" :disabled="!valid" :class="valid ? 'bg-sky-600 hover:bg-sky-500' : 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed'"
                    class="w-full rounded-xl px-4 py-3 text-sm font-black text-white transition">
                📋 {{ __('messages.po_save_pending') }}
            </button>
        </form>
    </div>
@endsection
