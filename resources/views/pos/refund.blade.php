@extends('layouts.pos.app')

@section('content')
    @php
        $saleCredit = (string) ($sale->payments->firstWhere('method', 'credit')?->amount ?? '0');
    @endphp

    <div class="mx-auto max-w-3xl px-4 py-6 space-y-6">

        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.refund_sale') }}</p>
                <h1 class="text-xl font-black mt-0.5">#{{ $sale->receipt_number }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    {{ $sale->posted_at?->format('d M Y, H:i') }} ·
                    {{ $sale->cashier?->name }}
                    @if ($sale->customer) · {{ $sale->customer->name }} @endif
                </p>
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

        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/sales/' . $sale->id . '/refunds') }}"
              class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm space-y-5"
              x-data="{
                  rows: @js($sale->items->map(fn ($item) => [
                      'id' => $item->id,
                      'name' => $item->product_name,
                      'price' => (float) $item->unit_price,
                      'already' => (float) ($refunded[$item->id] ?? '0'),
                      'refundable' => (float) (bcsub((string) $item->quantity, $refunded[$item->id] ?? '0', 3)),
                      'qty' => (float) (bcsub((string) $item->quantity, $refunded[$item->id] ?? '0', 3)),
                  ])->values()),
                  cash: 0,
                  credit: 0,
                  init() {
                      this.cash = this.returnTotal;
                  },
                  get returnTotal() {
                      return this.rows.reduce((s, r) => s + r.price * r.qty, 0);
                  },
                  get refundPaid() { return (this.cash || 0) + (this.credit || 0); },
                  get diff() { return Math.round((this.returnTotal - this.refundPaid) * 100) / 100; },
                  get valid() { return this.returnTotal > 0 && Math.abs(this.diff) < 0.01; }
              }">
            @csrf

            {{-- Items to return --}}
            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="text-left px-3 py-2">{{ __('messages.cart') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.unit_price') }}</th>
                            <th class="text-center px-3 py-2">{{ __('messages.already_refunded') }}</th>
                            <th class="text-center px-3 py-2">{{ __('messages.refundable') }}</th>
                            <th class="text-center px-3 py-2">{{ __('messages.qty') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-for="(r, i) in rows" :key="r.id">
                            <tr>
                                <td class="px-3 py-2.5">
                                    <p class="font-semibold" x-text="r.name"></p>
                                    <p class="text-xs text-slate-500 font-mono" x-text="'Ks ' + r.price.toLocaleString() + ' × ' + r.qty"></p>
                                </td>
                                <td class="px-3 py-2.5 text-right font-semibold" x-text="'Ks ' + r.price.toLocaleString()"></td>
                                <td class="px-3 py-2.5 text-center text-slate-500" x-text="r.already"></td>
                                <td class="px-3 py-2.5 text-center font-bold text-sky-600 dark:text-sky-400" x-text="r.refundable"></td>
                                <td class="px-3 py-2.5 text-center">
                                    <input type="hidden" :name="'items[' + i + '][pos_sale_item_id]'" :value="r.id">
                                    <input type="number" :name="'items[' + i + '][quantity]'" min="0" :max="r.refundable" step="0.001" required
                                           x-model.number="r.qty"
                                           class="w-24 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-center text-sm font-semibold">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Refund methods --}}
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 space-y-3">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.refund_method') }}</p>

                <label class="flex items-center justify-between gap-3">
                    <span class="text-sm font-bold">{{ __('messages.payment_cash') }}</span>
                    <span class="flex items-center gap-2">
                        <input type="hidden" name="refunds[0][method]" value="cash">
                        <input type="number" name="refunds[0][amount]" min="0" step="100" required x-model.number="cash"
                               class="w-36 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 py-1.5 text-right text-sm font-semibold">
                    </span>
                </label>

                @if ((float) $creditLeft > 0)
                    <label class="flex items-center justify-between gap-3">
                        <span class="text-sm font-bold">{{ __('messages.payment_credit') }}
                            <span class="block text-[10px] font-semibold text-slate-400">{{ __('messages.credit_refund_hint') }}
                                · {{ __('messages.credit_refund_remaining', ['amount' => number_format((float) $creditLeft)]) }}
                            </span>
                        </span>
                        <span class="flex items-center gap-2">
                            <input type="hidden" name="refunds[1][method]" value="credit">
                            <input type="number" name="refunds[1][amount]" min="0" :max="{{ (float) $creditLeft }}" step="100" x-model.number="credit"
                                   class="w-36 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 py-1.5 text-right text-sm font-semibold">
                        </span>
                    </label>
                @endif

                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 px-3 py-2.5 text-sm space-y-1">
                    <p class="flex justify-between"><span class="text-slate-500">{{ __('messages.return_value') }}</span><span class="font-black" x-text="'Ks ' + returnTotal.toLocaleString()"></span></p>
                    <p class="flex justify-between" x-show="diff !== 0">
                        <span class="text-slate-500">{{ __('messages.difference') }}</span>
                        <span class="font-bold" :class="diff < 0 ? 'text-rose-600' : 'text-amber-600'" x-text="'Ks ' + diff.toLocaleString()"></span>
                    </p>
                </div>
            </div>

            <textarea name="notes" rows="2" maxlength="1000" placeholder="{{ __('messages.notes') }}"
                      class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm"></textarea>

            <button type="submit" :disabled="!valid" :class="valid ? 'bg-rose-600 hover:bg-rose-500' : 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed'"
                    class="w-full rounded-xl px-4 py-3 text-sm font-black text-white transition">
                ↩ {{ __('messages.post_refund') }}
            </button>
        </form>
    </div>
@endsection
