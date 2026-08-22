@extends('layouts.pos.app')

@section('content')
    @php
        $isManager = auth()->user()?->hasStoreRole($store->id, 'store_manager');
    @endphp

    <div class="mx-auto max-w-4xl px-4 py-6 space-y-6">

        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.adjustment_title') }}</p>
                <h1 class="text-xl font-black mt-0.5">{{ __('messages.adjustment_subtitle') }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('messages.adjustment_hint') }}</p>
            </div>
            <a href="{{ url('/store/' . $store->slug . '/pos') }}"
               class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                ← {{ __('messages.back_to_pos') }}
            </a>
        </div>

        {{-- Inventory Operations Navigation Tabs --}}
        <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700/60 overflow-x-auto scrollbar-none text-xs font-bold">
            <a href="{{ route('pos.adjustments.index', $storeRouteParams) }}"
               class="px-4 py-2 rounded-lg transition-all whitespace-nowrap {{ request()->routeIs('pos.adjustments.*') ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                ⚡ {{ __('messages.adjustment_title') }}
            </a>
            <a href="{{ route('pos.reconciliation.index', $storeRouteParams) }}"
               class="px-4 py-2 rounded-lg transition-all whitespace-nowrap {{ request()->routeIs('pos.reconciliation.*') ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                ⚖️ {{ __('messages.reconciliation') }}
            </a>
            <a href="{{ route('pos.opening-stock.index', $storeRouteParams) }}"
               class="px-4 py-2 rounded-lg transition-all whitespace-nowrap {{ request()->routeIs('pos.opening-stock.*') ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                📦 {{ __('messages.opening_stock_title') }}
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

        {{-- Submit form --}}
        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments') }}"
              class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm space-y-5"
              x-data="{
                  rows: [{ q: '', results: [], open: false, product_id: '', product_variant_id: '', name: '', sku: '', dir: 'in', quantity: '', reason: '' }],
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
                      r.results = []; r.open = false;
                  },
                  addRow() { this.rows.push({ q: '', results: [], open: false, product_id: '', product_variant_id: '', name: '', sku: '', dir: 'in', quantity: '', reason: '' }); },
                  removeRow(i) { if (this.rows.length > 1) this.rows.splice(i, 1); },
                  signed(r) { return (r.dir === 'out' ? -1 : 1) * (parseFloat(r.quantity) || 0); },
                  get totalQty() { return this.rows.reduce((s, r) => s + this.signed(r), 0); },
                  get valid() { return this.rows.some(r => r.product_id && (parseFloat(r.quantity) || 0) > 0 && r.reason.trim() !== ''); }
              }">
            @csrf

            <div class="space-y-3">
                <template x-for="(r, i) in rows" :key="i">
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-3 space-y-2 relative">
                        <button type="button" @click="removeRow(i)" x-show="rows.length > 1"
                                class="absolute top-2 right-2 text-xs font-bold text-rose-500 hover:text-rose-700">✕</button>

                        <div class="relative">
                            <input type="text" x-model="r.q" @input.debounce.250ms="search(r)" @focus="r.open = r.results.length > 0"
                                   placeholder="{{ __('messages.adjustment_product_placeholder') }}"
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
                            <span class="flex rounded-lg bg-slate-100 dark:bg-slate-800 p-0.5">
                                <button type="button" @click="r.dir = 'in'"
                                        class="px-2 py-1 rounded-md text-xs font-bold transition"
                                        :class="r.dir === 'in' ? 'bg-emerald-600 text-white' : 'text-slate-500'">+ {{ __('messages.adjustment_in') }}</button>
                                <button type="button" @click="r.dir = 'out'"
                                        class="px-2 py-1 rounded-md text-xs font-bold transition"
                                        :class="r.dir === 'out' ? 'bg-rose-600 text-white' : 'text-slate-500'">− {{ __('messages.adjustment_out') }}</button>
                            </span>
                            <input type="hidden" :name="'items[' + i + '][quantity]'" :value="signed(r)" :disabled="!r.product_id">
                            <input type="number" min="0.001" step="any" x-model="r.quantity" :disabled="!r.product_id"
                                   class="w-24 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 py-1 text-right text-sm font-semibold">
                            <input type="text" :name="'items[' + i + '][reason]'" x-model="r.reason" :disabled="!r.product_id" maxlength="255"
                                   placeholder="{{ __('messages.adjustment_reason_placeholder') }}"
                                   class="flex-1 min-w-40 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 py-1 text-sm">
                        </div>
                    </div>
                </template>
            </div>

            <button type="button" @click="addRow"
                    class="w-full rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700 px-4 py-2.5 text-sm font-bold text-slate-500 hover:border-sky-400 hover:text-sky-600 transition">
                + {{ __('messages.receiving_add_line') }}
            </button>

            <textarea name="notes" rows="2" maxlength="1000" placeholder="{{ __('messages.adjustment_notes_placeholder') }}"
                      class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm"></textarea>

            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 px-3 py-2.5 text-sm flex justify-between">
                <span class="text-slate-500">{{ __('messages.adjustment_net_change') }}:</span>
                <span class="font-black" :class="totalQty < 0 ? 'text-rose-600' : (totalQty > 0 ? 'text-emerald-600' : 'text-slate-400')" x-text="(totalQty > 0 ? '+' : '') + totalQty.toLocaleString(undefined, { maximumFractionDigits: 3 })"></span>
            </div>

            <button type="submit" :disabled="!valid" :class="valid ? 'bg-sky-600 hover:bg-sky-500' : 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed'"
                    class="w-full rounded-xl px-4 py-3 text-sm font-black text-white transition">
                📋 {{ __('messages.adjustment_submit') }}
            </button>
        </form>

        {{-- Requests list --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm space-y-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.adjustment_requests') }}</p>

            @if ($requests->isEmpty())
                <p class="text-center text-sm text-slate-500 py-6">{{ __('messages.adjustment_none') }}</p>
            @else
                @foreach ($requests as $req)
                    @php
                        $statusColors = [
                            'pending' => 'border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-300',
                            'approved' => 'border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300',
                            'rejected' => 'border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-300',
                        ][$req->status];
                        $statusLabel = ['pending' => __('messages.adjustment_pending'), 'approved' => __('messages.adjustment_approved'), 'rejected' => __('messages.adjustment_rejected')][$req->status];
                    @endphp
                    <div class="rounded-xl border {{ $statusColors }} p-4 space-y-3">
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <div>
                                <p class="font-mono font-bold">{{ $req->adjustment_number }} · <span class="text-sm">{{ $statusLabel }}</span></p>
                                <p class="text-xs opacity-80">
                                    {{ __('messages.adjustment_submitted_by') }}: {{ $req->submittedBy?->name ?? '—' }} · {{ $req->created_at->format('d M Y, H:i') }}
                                    @if ($req->reviewedBy) · {{ __('messages.adjustment_reviewed_by') }}: {{ $req->reviewedBy->name }} @endif
                                </p>
                                @if ($req->review_notes)
                                    <p class="text-xs italic opacity-80 mt-1">"{{ $req->review_notes }}"</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-xs opacity-80">{{ __('messages.adjustment_net_change') }}</p>
                                <p class="font-black {{ (float) $req->total_quantity < 0 ? 'text-rose-600' : ((float) $req->total_quantity > 0 ? 'text-emerald-600' : 'text-slate-400') }}">
                                    {{ (float) $req->total_quantity > 0 ? '+' : '' }}{{ number_format((float) $req->total_quantity, 3) }}
                                </p>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                                    <tr>
                                        <th class="text-left px-3 py-2">{{ __('messages.product') }}</th>
                                        <th class="text-right px-3 py-2">{{ __('messages.adjustment_on_hand') }}</th>
                                        <th class="text-right px-3 py-2">{{ __('messages.reports_qty') }}</th>
                                        <th class="text-left px-3 py-2">{{ __('messages.adjustment_reason') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach ($req->items as $item)
                                        <tr>
                                            <td class="px-3 py-2 font-semibold">{{ $item->product?->name ?? '—' }}</td>
                                            <td class="px-3 py-2 text-right font-mono">{{ number_format((float) ($item->on_hand ?? 0), 3) }}</td>
                                            <td class="px-3 py-2 text-right font-mono font-bold {{ (float) $item->quantity < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                                {{ (float) $item->quantity > 0 ? '+' : '' }}{{ number_format((float) $item->quantity, 3) }}
                                            </td>
                                            <td class="px-3 py-2 text-xs text-slate-500">{{ $item->reason }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($req->isPending() && $isManager)
                            <div class="flex gap-2 flex-wrap">
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments/' . $req->id . '/approve') }}" class="flex-1 flex gap-2">
                                    @csrf
                                    <input type="text" name="review_notes" maxlength="500" placeholder="{{ __('messages.adjustment_review_notes') }}"
                                           class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1.5 text-sm">
                                    <button class="rounded-lg px-4 py-2 text-sm font-bold bg-emerald-600 text-white hover:bg-emerald-500 transition">
                                        ✅ {{ __('messages.adjustment_approve') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments/' . $req->id . '/reject') }}">
                                    @csrf
                                    <button class="rounded-lg px-4 py-2 text-sm font-bold bg-rose-600 text-white hover:bg-rose-500 transition">
                                        ✕ {{ __('messages.adjustment_reject') }}
                                    </button>
                                </form>
                            </div>
                        @elseif ($req->isPending())
                            <p class="text-xs font-semibold text-slate-500">{{ __('messages.adjustment_waits') }}</p>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endsection
