@extends('layouts.pos.app')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-6 space-y-6">

        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.receiving_title') }}</p>
                <h1 class="text-xl font-black mt-0.5">{{ __('messages.receiving_subtitle') }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('messages.receiving_hint') }}</p>
            </div>
            <a href="{{ url('/store/' . $store->slug . '/pos') }}"
               class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                ← {{ __('messages.back_to_pos') }}
            </a>
        </div>

        @if (session('error'))
            <div class="rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-300 px-4 py-3 text-sm font-semibold">
                ⚠️ {{ session('error') }}
            </div>
        @endif
        @if (session('success'))
            <div class="rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-sm font-semibold">
                ✅ {{ session('success') }}
            </div>
        @endif

        {{-- Receiving form --}}
        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/receiving') }}"
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
                      r.unit_cost = '';
                      r.results = []; r.open = false;
                  },
                  addRow() { this.rows.push({ q: '', results: [], open: false, product_id: '', product_variant_id: '', name: '', sku: '', quantity: '', unit_cost: '' }); },
                  removeRow(i) { if (this.rows.length > 1) this.rows.splice(i, 1); },
                  get totalQty() { return this.rows.reduce((s, r) => s + (parseFloat(r.quantity) || 0), 0); },
                  get totalCost() { return this.rows.reduce((s, r) => s + (parseFloat(r.quantity) || 0) * (parseFloat(r.unit_cost) || 0), 0); },
                  get valid() { return this.rows.some(r => r.product_id && (parseFloat(r.quantity) || 0) > 0); }
              }">
            @csrf
            <input type="hidden" name="client_transaction_id" value="{{ \Illuminate\Support\Str::uuid() }}">

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
                                {{ __('messages.receiving_unit_cost') }} (Ks)
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

            <div class="flex items-center gap-2">
                <input type="text" name="reference" maxlength="100" placeholder="{{ __('messages.receiving_reference') }}"
                       class="flex-1 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                <input type="text" name="notes" maxlength="1000" placeholder="{{ __('messages.notes') }}"
                       class="flex-1 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
            </div>

            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 px-3 py-2.5 text-sm flex justify-between">
                <span class="text-slate-500">{{ __('messages.receiving_total') }}: <b x-text="totalQty.toLocaleString(undefined, { maximumFractionDigits: 3 })"></b> {{ __('messages.reports_units') }}</span>
                <span class="font-black" x-text="'Ks ' + totalCost.toLocaleString(undefined, { maximumFractionDigits: 2 })"></span>
            </div>

            <button type="submit" :disabled="!valid" :class="valid ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed'"
                    class="w-full rounded-xl px-4 py-3 text-sm font-black text-white transition">
                📦 {{ __('messages.receiving_post') }}
            </button>
        </form>

        {{-- Recent receipts --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-3">{{ __('messages.receiving_recent') }}</p>
            @if ($recent->isEmpty())
                <p class="text-center text-sm text-slate-500 py-6">{{ __('messages.receiving_none') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="text-left px-3 py-2">{{ __('messages.receipt') }}</th>
                                <th class="text-left px-3 py-2">{{ __('messages.reports_date') }}</th>
                                <th class="text-left px-3 py-2">{{ __('messages.receiving_reference') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.reports_items') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.reports_qty') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.reports_value') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($recent as $receipt)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="px-3 py-2.5 font-mono font-bold text-sky-600 dark:text-sky-400">{{ $receipt->receipt_number }}</td>
                                    <td class="px-3 py-2.5 whitespace-nowrap">{{ $receipt->posted_at?->format('d M Y, H:i') }}</td>
                                    <td class="px-3 py-2.5">{{ $receipt->reference ?? '—' }}</td>
                                    <td class="px-3 py-2.5 text-right">{{ $receipt->items->count() }}</td>
                                    <td class="px-3 py-2.5 text-right font-mono">{{ number_format((float) $receipt->total_quantity, 3) }}</td>
                                    <td class="px-3 py-2.5 text-right font-mono font-bold">Ks {{ number_format((float) $receipt->total_cost) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
