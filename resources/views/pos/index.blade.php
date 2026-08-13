@extends('layouts.pos.app')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 space-y-6">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-sm font-semibold">
                ✅ {{ session('success') }}
                @if (session('posted_receipt'))
                    <span class="block mt-1 text-xs font-mono">#{{ session('posted_receipt') }} · Change: Ks {{ number_format((float) session('posted_change')) }}</span>
                    @if (session('posted_debt'))
                        <span class="block mt-1 text-xs font-bold text-amber-600 dark:text-amber-400">💳 {{ __('messages.balance_due') }}: Ks {{ number_format((float) session('posted_debt')) }}</span>
                    @endif
                    @if (session('posted_sale_id'))
                        <a href="{{ url('/store/' . $store->slug . '/pos/sales/' . session('posted_sale_id') . '/receipt') }}" target="_blank"
                           class="inline-block mt-2 rounded-lg bg-emerald-600 text-white px-3 py-1.5 text-xs font-bold hover:bg-emerald-500 transition">
                            🖨️ {{ __('messages.print_receipt') }}
                        </a>
                    @endif
                @endif
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-300 px-4 py-3 text-sm font-semibold">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        {{-- ── Cart + sale posting ─────────────────────────────────────── --}}
        <section class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm"
                 x-data="{
            showPayment: false,
            customer: null,
            cash: '{{ $cartTotals['total'] }}',
            kpay: 0, wavepay: 0, cbpay: 0, mmqr: 0, credit: 0,
            cq: '', cresults: [], copen: false,
            async csearch() {
                if (this.cq.trim() === '') { this.cresults = []; this.copen = false; return; }
                const res = await fetch('{{ url('/store/' . $store->slug . '/pos/customers') }}?q=' + encodeURIComponent(this.cq), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json();
                this.cresults = json.customers || [];
                this.copen = true;
            },
            attach(c) {
                this.customer = c;
                this.cq = c.name;
                this.cresults = [];
                this.copen = false;
                if (this.remaining > 0 && this.credit === 0) this.credit = Math.max(0, Math.round(this.remaining / 100) * 100);
            },
            clearCustomer() { this.customer = null; this.cq = ''; this.credit = 0; },
            get paid() {
                return ['cash','kpay','wavepay','cbpay','mmqr','credit'].reduce((s, k) => s + (parseFloat(this[k]) || 0), 0);
            },
            get remaining() { return parseFloat('{{ $cartTotals['total'] }}') - this.paid; },
            get change() { return this.remaining < 0 ? -this.remaining : 0; },
            get exact() {
                if (this.credit > 0 && !this.customer) return false; // debt needs an attached customer
                return this.remaining <= 0.005; // overpay is fine — becomes change
            }
        }">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.cart') }}</p>
                    <h2 class="text-lg font-black mt-0.5">{{ __('messages.scan_or_search') }}</h2>
                </div>
                <div class="flex items-center gap-2">
                    @if ($openShift)
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                            ● {{ $openShift->register_name }}
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300">
                            {{ __('messages.sale_requires_shift') }}
                        </span>
                    @endif
                    <a href="{{ url('/store/' . $store->slug . '/pos/closing') }}"
                       class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        📋 {{ __('messages.closing_title') }}
                    </a>
                    <a href="{{ url('/store/' . $store->slug . '/pos/reports/sales') }}"
                       class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        📊 {{ __('messages.reports_title') }}
                    </a>
                    <a href="{{ url('/store/' . $store->slug . '/pos/receiving') }}"
                       class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        📦 {{ __('messages.receiving_title') }}
                    </a>
                    <a href="{{ url('/store/' . $store->slug . '/pos/opening-stock') }}"
                       class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        🏷️ {{ __('messages.opening_stock_title') }}
                    </a>
                    <a href="{{ url('/store/' . $store->slug . '/pos/adjustments') }}"
                       class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        🔧 {{ __('messages.adjustment_title') }}
                    </a>
                </div>
            </div>

            {{-- Product search (barcode / SKU / name) --}}
            <div class="relative mb-5" x-data="{ q: '', results: [], open: false, async search() {
                if (this.q.trim() === '') { this.results = []; this.open = false; return; }
                const res = await fetch('{{ url('/store/' . $store->slug . '/pos/products') }}?q=' + encodeURIComponent(this.q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json();
                this.results = json.results || [];
                this.open = true;
            } }">
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
                    <input type="text" x-model="q" @input.debounce.250ms="search()" @keydown.enter.prevent="search()"
                           placeholder="{{ __('messages.pos_search_placeholder') }}" autofocus
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 pl-10 pr-4 py-3 text-base font-semibold focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div x-show="open && results.length" x-cloak
                     class="absolute z-30 inset-x-0 top-full mt-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl max-h-80 overflow-y-auto">
                    <template x-for="r in results" :key="r.type + '-' + r.id">
                        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/cart') }}">
                            @csrf
                            <input type="hidden" name="product_id" :value="r.product_id">
                            <input type="hidden" name="product_variant_id" :value="r.type === 'variant' ? r.id : ''">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 last:border-0">
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold truncate" x-text="r.name"></span>
                                    <span class="block text-xs text-slate-500 font-mono" x-text="r.sku || ''"></span>
                                </span>
                                <span class="shrink-0 flex items-center gap-3">
                                    <span class="text-sm font-black" x-text="'Ks ' + Number(r.price).toLocaleString()"></span>
                                    <span class="text-xs px-1.5 py-0.5 rounded-md font-bold" :class="parseFloat(r.balance) > 0 ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' : 'bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-300'"
                                          x-text="'×' + r.balance"></span>
                                    <span class="text-xs font-bold text-sky-600 dark:text-sky-400">+</span>
                                </span>
                            </button>
                        </form>
                    </template>
                </div>
                <div x-show="open && q.trim() !== '' && !results.length" x-cloak
                     class="absolute z-30 inset-x-0 top-full mt-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl px-4 py-3 text-sm text-slate-500">
                    {{ __('messages.no_results') }}
                </div>
            </div>

            {{-- Customer attach (credit/debt — SoT §17) --}}
            <div class="mb-5">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.customer') }}</p>
                    <template x-if="customer">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300">
                            👤 <span x-text="customer.name"></span>
                            <span x-show="parseFloat(customer.balance) > 0" x-text="' · ' + {{ __('messages.debt') }} + ' Ks ' + Number(customer.balance).toLocaleString()"></span>
                            <button type="button" @click="clearCustomer()" class="ml-1 text-rose-500 font-black">✕</button>
                        </span>
                    </template>
                </div>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">👤</span>
                    <input type="text" x-model="cq" @input.debounce.250ms="csearch()" :disabled="customer !== null"
                           placeholder="{{ __('messages.customer_search_placeholder') }}"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 pl-10 pr-4 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-sky-500 outline-none disabled:opacity-50">
                </div>
                <div x-show="copen && cresults.length" x-cloak
                     class="relative z-20 mt-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl max-h-60 overflow-y-auto">
                    <template x-for="c in cresults" :key="c.id">
                        <button type="button" @click="attach(c)"
                                class="w-full text-left px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 last:border-0">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold truncate" x-text="c.name"></span>
                                <span class="block text-xs text-slate-500 font-mono" x-text="c.phone || ''"></span>
                            </span>
                            <span class="shrink-0 text-xs font-bold" :class="parseFloat(c.balance) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400'"
                                  x-text="parseFloat(c.balance) > 0 ? '{{ __('messages.debt') }} ' + Number(c.balance).toLocaleString() : ''"></span>
                        </button>
                    </template>
                </div>
                <div x-show="copen && cq.trim() !== '' && !cresults.length" x-cloak
                     class="relative z-20 mt-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl px-4 py-3 text-sm text-slate-500">
                    {{ __('messages.no_customers_found') }}
                </div>
                <p x-show="credit > 0 && !customer" x-cloak class="mt-2 text-xs font-bold text-rose-600 dark:text-rose-400">
                    ⚠️ {{ __('messages.credit_requires_customer') }}
                </p>
            </div>

            {{-- Cart lines --}}
            @if ($cart)
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="text-left px-3 py-2">{{ __('messages.cart') }}</th>
                                <th class="text-center px-3 py-2">{{ __('messages.qty') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.unit_price') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.line_total') }}</th>
                                <th class="text-center px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($cart as $line)
                                <tr>
                                    <td class="px-3 py-2.5">
                                        <p class="font-semibold">{{ $line['name'] }}</p>
                                        <p class="text-xs text-slate-500 font-mono">{{ $line['sku'] }} · {{ __('messages.stock_on_hand') }}: {{ $line['balance'] }}</p>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/cart/' . $line['index']) }}" class="inline-flex items-center gap-1">
                                            @csrf
                                            <input type="number" name="quantity" value="{{ rtrim(rtrim($line['quantity'], '0'), '.') }}" min="0.001" step="1" required
                                                   class="w-20 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-center text-sm font-semibold">
                                            <button type="submit" class="text-xs font-bold text-sky-600 dark:text-sky-400 px-1">✓</button>
                                        </form>
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-semibold">Ks {{ number_format((float) $line['unit_price']) }}</td>
                                    <td class="px-3 py-2.5 text-right font-black">Ks {{ number_format((float) $line['line_total']) }}</td>
                                    <td class="px-3 py-2.5 text-center">
                                        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/cart/' . $line['index']) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-500 hover:text-rose-700 font-bold px-1">✕</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="text-sm text-slate-500 dark:text-slate-400 space-y-0.5">
                        <p>{{ __('messages.subtotal') }}: <span class="font-bold text-slate-700 dark:text-slate-200">Ks {{ number_format((float) $cartTotals['subtotal']) }}</span></p>
                        <p class="text-base font-black text-slate-900 dark:text-white">{{ __('messages.total') }}: Ks {{ number_format((float) $cartTotals['total']) }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/hold') }}">
                            @csrf
                            <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                                ⏸ {{ __('messages.hold_sale') }}
                            </button>
                        </form>

                        {{-- Payment modal --}}
                        <div>
                            <button type="button" @click="showPayment = true" :disabled="!{{ $openShift ? 'true' : 'false' }}"
                                    class="rounded-xl px-5 py-2.5 text-sm font-black bg-sky-600 text-white hover:bg-sky-500 transition disabled:opacity-40 disabled:cursor-not-allowed">
                                💳 {{ __('messages.post_sale') }}
                            </button>

                            <div x-show="showPayment" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @keydown.escape.window="showPayment = false">
                                <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 p-5 shadow-2xl">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-black">{{ __('messages.payments') }}</h3>
                                        <button type="button" @click="showPayment = false" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
                                    </div>

                                    <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/post') }}" class="grid gap-3">
                                        @csrf
                                        <input type="hidden" name="customer_id" :value="customer ? customer.id : ''">
                                        @foreach (['cash', 'kpay', 'wavepay', 'cb_pay', 'mmqr', 'credit'] as $i => $method)
                                            <label class="flex items-center justify-between gap-3 rounded-xl border px-3 py-2.5"
                                                   :class="customer || $method !== 'credit' ? 'border-slate-200 dark:border-slate-700' : 'border-rose-300 dark:border-rose-800 bg-rose-50/50 dark:bg-rose-950/20'">
                                                <span class="text-sm font-bold">
                                                    {{ __('messages.payment_' . $method) }}
                                                    @if ($method === 'credit')
                                                        <span class="block text-[10px] font-semibold text-slate-400">{{ __('messages.credit_hint') }}</span>
                                                    @endif
                                                </span>
                                                <input type="hidden" name="payments[{{ $i }}][method]" value="{{ $method }}">
                                                <input type="number" name="payments[{{ $i }}][amount]" min="0" step="100" x-model="{{ $method === 'cash' ? 'cash' : $method }}"
                                                       :disabled="{{ $method === 'credit' ? '!customer' : 'false' }}"
                                                       class="w-36 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 py-1.5 text-right text-sm font-semibold disabled:opacity-40">
                                            </label>
                                        @endforeach

                                        <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 px-3 py-2.5 text-sm space-y-1">
                                            <p class="flex justify-between"><span class="text-slate-500">{{ __('messages.total') }}</span><span class="font-black">Ks {{ number_format((float) $cartTotals['total']) }}</span></p>
                                            <p class="flex justify-between" x-show="remaining !== 0">
                                                <span class="text-slate-500">{{ __('messages.subtotal') }} (ကျန်)</span>
                                                <span class="font-bold" :class="remaining < 0 ? 'text-rose-600' : 'text-amber-600'" x-text="'Ks ' + remaining.toLocaleString()"></span>
                                            </p>
                                            <p class="flex justify-between" x-show="change > 0">
                                                <span class="text-slate-500">{{ __('messages.change') }}</span>
                                                <span class="font-black text-emerald-600" x-text="'Ks ' + change.toLocaleString()"></span>
                                            </p>
                                            <p class="flex justify-between" x-show="credit > 0">
                                                <span class="text-slate-500">{{ __('messages.balance_due') }}</span>
                                                <span class="font-black text-amber-600 dark:text-amber-400" x-text="'Ks ' + credit.toLocaleString()"></span>
                                            </p>
                                        </div>

                                        <button type="submit" :disabled="!exact" :class="exact ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed'"
                                                class="rounded-xl px-4 py-3 text-sm font-black text-white transition">
                                            {{ __('messages.post_sale') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                    🛒 {{ __('messages.cart_empty') }} — {{ __('messages.scan_or_search') }}
                </div>
            @endif
        </section>

        {{-- ── Held sales ──────────────────────────────────────────────── --}}
        @if ($heldSales->isNotEmpty())
            <section class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-3">{{ __('messages.held_sales') }}</p>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($heldSales as $sale)
                        <div class="rounded-xl border border-amber-200 dark:border-amber-900 bg-amber-50/50 dark:bg-amber-950/30 p-3 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-amber-700 dark:text-amber-300">#{{ $sale->id }} · {{ $sale->items->count() }} {{ __('messages.cart') }}</p>
                                <p class="text-sm font-black">Ks {{ number_format((float) $sale->total) }}</p>
                            </div>
                            <div class="flex gap-1">
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/resume/' . $sale->id) }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold px-2 py-1 rounded-lg bg-amber-500 text-white hover:bg-amber-400">{{ __('messages.resume') }}</button>
                                </form>
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/void/' . $sale->id) }}"
                                      onsubmit="return confirm('Void this held sale?')">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold px-2 py-1 rounded-lg bg-rose-500 text-white hover:bg-rose-400">{{ __('messages.void_sale') }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ── Today's posted sales ────────────────────────────────────── --}}
        @if ($todaySales->isNotEmpty())
            <section class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-3">{{ __('messages.today_sales') }}</p>
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="text-left px-3 py-2">{{ __('messages.receipt_number') }}</th>
                                <th class="text-left px-3 py-2">{{ __('messages.cashier') }}</th>
                                <th class="text-left px-3 py-2">{{ __('messages.customer') }}</th>
                                <th class="text-left px-3 py-2">{{ __('messages.cart') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.payments') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.total') }}</th>
                                <th class="text-center px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($todaySales as $sale)
                                @php $saleDebt = $sale->payments->firstWhere('method', 'credit')?->amount ?? '0'; @endphp
                                <tr>
                                    <td class="px-3 py-2.5 font-mono font-bold text-sky-600 dark:text-sky-400">{{ $sale->receipt_number }}</td>
                                    <td class="px-3 py-2.5">{{ $sale->cashier?->name }}</td>
                                    <td class="px-3 py-2.5">
                                        @if ($sale->customer)
                                            <span class="font-semibold">{{ $sale->customer->name }}</span>
                                            @if ((float) $saleDebt > 0)
                                                <span class="block text-[10px] font-bold text-amber-600 dark:text-amber-400">{{ __('messages.debt') }} Ks {{ number_format((float) $saleDebt) }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-xs text-slate-500">
                                        {{ $sale->items->take(3)->pluck('product_name')->implode(', ') }}{{ $sale->items->count() > 3 ? '…' : '' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-xs">
                                        @foreach ($sale->payments as $payment)
                                            <span class="inline-block px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono mr-1">{{ $payment->method }} {{ number_format((float) $payment->amount) }}</span>
                                        @endforeach
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-black">Ks {{ number_format((float) $sale->total) }}</td>
                                    <td class="px-3 py-2.5 text-center whitespace-nowrap">
                                        @if ($sale->status !== 'refunded')
                                            <a href="{{ url('/store/' . $store->slug . '/pos/sales/' . $sale->id . '/refund') }}"
                                               class="inline-block px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-bold hover:bg-rose-100 dark:hover:bg-rose-900/40 hover:text-rose-600 transition"
                                               title="{{ __('messages.refund_sale') }}">
                                                ↩
                                            </a>
                                        @endif
                                        <a href="{{ url('/store/' . $store->slug . '/pos/sales/' . $sale->id . '/receipt') }}" target="_blank"
                                           class="inline-block px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-bold hover:bg-sky-100 dark:hover:bg-sky-900 transition">
                                            🖨️
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">

        {{-- ── Shift status / open ─────────────────────────────────────── --}}
        <section class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            @if ($openShift)
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.open_shift') }}</p>
                        <h2 class="text-lg font-black mt-0.5">{{ $openShift->register_name }}</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            {{ __('messages.cashier') }}: {{ $openShift->cashier?->name }} ·
                            {{ __('messages.opened_at') }}: {{ $openShift->opened_at->format('H:i') }}
                        </p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                        ● {{ __('messages.shift_open') }}
                    </span>
                </div>

                <dl class="grid grid-cols-2 gap-3 text-sm mb-5">
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                        <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.opening_cash') }}</dt>
                        <dd class="font-black mt-0.5">Ks {{ number_format((float) $openShift->opening_cash) }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                        <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.cash_in_out') }}</dt>
                        <dd class="font-black mt-0.5 text-sky-600 dark:text-sky-400">
                            +{{ number_format((float) $openShift->cash_in) }} / −{{ number_format((float) $openShift->cash_out) }}
                        </dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                        <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.cash_sales') }}</dt>
                        <dd class="font-black mt-0.5">Ks {{ number_format((float) $openShift->cash_sales) }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                        <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.cash_refunds') }}</dt>
                        <dd class="font-black mt-0.5">Ks {{ number_format((float) $openShift->cash_refunds) }}</dd>
                    </div>
                </dl>

                {{-- Cash in/out --}}
                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/shifts/' . $openShift->id . '/cash-events') }}"
                      class="grid grid-cols-[1fr_auto] gap-2 mb-5" x-data="{ type: 'cash_in' }">
                    @csrf
                    <div class="grid grid-cols-2 gap-2">
                        <select name="type" x-model="type" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                            <option value="cash_in">+ {{ __('messages.cash_in') }}</option>
                            <option value="cash_out">− {{ __('messages.cash_out') }}</option>
                        </select>
                        <input type="number" name="amount" min="1" step="100" required placeholder="Ks"
                               class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                    </div>
                    <input type="text" name="reason" maxlength="255" placeholder="{{ __('messages.reason') }}"
                           class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                    <button type="submit"
                            class="rounded-xl px-4 py-2 text-sm font-bold bg-sky-600 text-white hover:bg-sky-500 transition">
                        {{ __('messages.save') }}
                    </button>
                </form>

                {{-- Close shift --}}
                <div x-data="{ show: false }" class="border-t border-slate-200 dark:border-slate-800 pt-4">
                    <button type="button" @click="show = !show"
                            class="w-full rounded-xl px-4 py-3 text-sm font-bold bg-rose-600 text-white hover:bg-rose-500 transition">
                        {{ __('messages.close_shift') }}
                    </button>
                    <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/shifts/' . $openShift->id . '/close') }}"
                          x-show="show" x-cloak class="mt-3 grid gap-2">
                        @csrf
                        <input type="number" name="actual_closing_amount" min="0" step="100" required placeholder="{{ __('messages.actual_closing_amount') }} (Ks)"
                               class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                        <textarea name="notes" rows="2" maxlength="1000" placeholder="{{ __('messages.notes') }}"
                                  class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm"></textarea>
                        <button type="submit" class="rounded-xl px-4 py-2 text-sm font-bold bg-rose-600 text-white hover:bg-rose-500 transition">
                            {{ __('messages.confirm_close_shift') }}
                        </button>
                    </form>
                </div>
            @else
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-4">{{ __('messages.no_open_shift') }}</p>
                <h2 class="text-lg font-black mb-4">{{ __('messages.open_new_shift') }}</h2>
                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/shifts') }}" class="grid gap-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.register_name') }}</label>
                        <input type="text" name="register_name" required maxlength="100" value="{{ old('register_name') }}"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm"
                               placeholder="Register 1">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.opening_cash') }} (Ks)</label>
                        <input type="number" name="opening_cash" min="0" step="100" value="{{ old('opening_cash', 0) }}"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="rounded-xl px-4 py-3 text-sm font-bold bg-sky-600 text-white hover:bg-sky-500 transition">
                        {{ __('messages.open_shift') }}
                    </button>
                </form>
            @endif
        </section>

        {{-- ── Today's summary ─────────────────────────────────────────── --}}
        <section class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-1">{{ __('messages.today_summary') }}</p>
            <h2 class="text-lg font-black mb-4">{{ now()->format('d M Y') }}</h2>

            @if ($summary['shift_count'] > 0)
                <dl class="grid grid-cols-2 gap-3 text-sm mb-4">
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                        <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.closed_shifts') }}</dt>
                        <dd class="font-black mt-0.5">{{ $summary['shift_count'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                        <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.expected_cash') }}</dt>
                        <dd class="font-black mt-0.5">Ks {{ number_format((float) $summary['expected']) }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                        <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.actual_cash') }}</dt>
                        <dd class="font-black mt-0.5">Ks {{ number_format((float) $summary['actual']) }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                        <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.difference') }}</dt>
                        <dd class="font-black mt-0.5 {{ (float) $summary['difference'] < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                            {{ (float) $summary['difference'] < 0 ? '−' : '+' }}Ks {{ number_format(abs((float) $summary['difference'])) }}
                        </dd>
                    </div>
                </dl>

                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="text-left px-3 py-2">{{ __('messages.cashier') }}</th>
                                <th class="text-left px-3 py-2">{{ __('messages.register') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.opening_cash') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.actual') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.difference') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($summary['shifts'] as $shift)
                                <tr>
                                    <td class="px-3 py-2.5 font-semibold">{{ $shift->cashier?->name }}</td>
                                    <td class="px-3 py-2.5">{{ $shift->register_name }}</td>
                                    <td class="px-3 py-2.5 text-right">Ks {{ number_format((float) $shift->opening_cash) }}</td>
                                    <td class="px-3 py-2.5 text-right">Ks {{ number_format((float) $shift->actual_closing_amount) }}</td>
                                    <td class="px-3 py-2.5 text-right font-bold {{ (float) $shift->difference < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ (float) $shift->difference < 0 ? '−' : '+' }}Ks {{ number_format(abs((float) $shift->difference)) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                    {{ __('messages.no_closed_shifts_today') }}
                </div>
            @endif
        </section>

        </div>

        {{-- ── Customer balances (receivables — SoT §17) ───────────────── --}}
        <section class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.customer_balances') }}</p>
                    <h2 class="text-lg font-black mt-0.5">{{ __('messages.outstanding_debt') }}</h2>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300">
                    Ks {{ number_format((float) $outstandingTotal) }}
                </span>
            </div>

            @if ($outstanding)
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="text-left px-3 py-2">{{ __('messages.customer') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.outstanding_balance') }}</th>
                                <th class="text-left px-3 py-2">{{ __('messages.last_activity') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.collect') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($outstanding as $customer)
                                <tr>
                                    <td class="px-3 py-2.5">
                                        <p class="font-semibold">{{ $customer['name'] }}</p>
                                        <p class="text-xs text-slate-500 font-mono">{{ $customer['phone'] ?? '—' }}</p>
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-black text-amber-600 dark:text-amber-400">Ks {{ number_format((float) $customer['balance']) }}</td>
                                    <td class="px-3 py-2.5 text-xs text-slate-500">{{ $customer['last_activity'] ? \Illuminate\Support\Carbon::parse($customer['last_activity'])->diffForHumans() : '—' }}</td>
                                    <td class="px-3 py-2.5 text-right">
                                        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/customers/' . $customer['customer_id'] . '/collect') }}"
                                              class="inline-flex items-center gap-1" x-data="{ amount: '' }">
                                            @csrf
                                            <input type="number" name="amount" min="0.01" :max="{{ $customer['balance'] }}" step="any" required placeholder="Ks" x-model="amount"
                                                   class="w-28 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-right text-sm font-semibold">
                                            <button type="submit" :disabled="!amount || parseFloat(amount) <= 0"
                                                    class="text-xs font-bold px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 disabled:opacity-40 disabled:cursor-not-allowed transition">
                                                {{ __('messages.collect') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                    🎉 {{ __('messages.no_outstanding_debt') }}
                </div>
            @endif
        </section>
    </div>
@endsection
