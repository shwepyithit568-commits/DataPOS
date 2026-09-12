{{-- ── Payment modal (multi-method split: cash / kpay / wave / credit) ── --}}
<div x-show="showPayment" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/25 dark:bg-black/35 p-0 sm:p-4 transition-opacity" @click.self="showPayment = false" @keydown.escape.window="showPayment = false">
    <div class="relative w-full max-w-full sm:max-w-lg rounded-t-3xl rounded-b-none sm:rounded-2xl bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 sm:border shadow-2xl overflow-hidden max-h-[92dvh] flex flex-col pb-[env(safe-area-inset-bottom,0px))]">

        {{-- Modal header --}}
        <div class="flex items-center justify-between gap-3 px-5 pt-4 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="text-base font-black">{{ __('messages.payments') }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ __('messages.total') }}:
                    <span class="font-extrabold text-blue-600 dark:text-blue-400" x-text="formatCurrency(cart.totals.total)"></span>
                </p>
            </div>
            <button type="button" @click="showPayment = false"
                    class="sf-btn-3d w-9 h-9 rounded-xl flex items-center justify-center text-slate-500 dark:text-slate-400 font-black cursor-pointer transition">✕</button>
        </div>

        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/post') }}"
              class="flex flex-col max-h-[85dvh] overflow-y-auto"
              @keydown.enter="if (exact) { $el.requestSubmit(); }">
            @csrf
            <input type="hidden" name="customer_id" :value="customer ? customer.id : ''">
            <input type="hidden" name="discount" :value="cart.totals.discount || '0'">
            <input type="hidden" name="web_order_id" :value="pendingWebOrderId || ''">
            {{-- Hidden payment inputs → server-side PosSaleController::post() --}}
            @foreach (['cash', 'kpay', 'wavepay', 'cb_pay', 'mmqr', 'credit'] as $i => $method)
                <input type="hidden" name="payments[{{ $i }}][method]" value="{{ $method }}">
                <input type="hidden" name="payments[{{ $i }}][amount]" x-model="{{ $method === 'cash' ? 'cash' : ($method === 'cb_pay' ? 'cbpay' : $method) }}">
            @endforeach

            <div class="px-5 py-4 space-y-4">

                {{-- ── Method tiles (toggle active; badge shows amount if > 0) ── --}}
                @php
                $methods = [
                    ['cash',    'payment_cash',    'M17 11H7M12 6v12',                                                                                                                                           'cash',   'emerald'],
                    ['kpay',    'payment_kpay',    'M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Zm0 0V5a2 2 0 0 1 2-2h5.5M21 9V5a2 2 0 0 0-2-2h-5.5',                              'kpay',   'purple'],
                    ['wavepay', 'payment_wavepay', 'M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Zm10 0a2 2 0 1 0 4 0 2 2 0 0 0-4 0',                                                                              'wavepay','blue'],
                    ['cb_pay',  'payment_cb_pay',  'M4 3h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm4 8h8m-4-4v8',                                                                      'cbpay',  'orange'],
                    ['mmqr',   'payment_mmqr',    'M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2',                                                           'mmqr',   'pink'],
                    ['credit', 'payment_credit',  'M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1ZM16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8',                                   'credit', 'amber'],
                ];
                @endphp

                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-2">{{ __('messages.payment_method') }}</p>
                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-1.5">
                        @foreach ($methods as [$mid, $mlabel, $micon, $mkey, $mcolor])
                        <button type="button"
                        @click="switchPaymentMethod('{{ $mid }}')"
                                :disabled="{{ $mid === 'credit' ? '!customer' : 'false' }}"
                                :class="activeMethod === '{{ $mid }}'
                                    ? 'sf-btn-3d-primary active text-white shadow-md ring-2 ring-blue-400'
                                    : (amtFor('{{ $mkey }}') > 0 ? 'sf-btn-3d text-slate-700 dark:text-slate-200 ring-2 ring-emerald-400' : 'sf-btn-3d text-slate-700 dark:text-slate-200')"
                                class="relative flex flex-col items-center gap-1 rounded-xl px-2 py-2.5 text-[10px] font-black transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                            {{-- Amount badge --}}
                            <span x-show="amtFor('{{ $mkey }}') > 0"
                                  class="absolute -top-1.5 -right-1.5 min-w-4 h-4 px-1 rounded-full bg-emerald-500 text-white text-[9px] font-black leading-4 text-center"
                                  x-text="formatCurrency(amtFor('{{ $mkey }}'))">
                            </span>
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $micon }}"/></svg>
                            <span>{{ __('messages.' . $mlabel) }}</span>
                        </button>
                        @endforeach
                    </div>

                    {{-- Credit warning --}}
                    <p x-show="activeMethod === 'credit' && !customer" x-cloak
                       class="mt-1.5 text-[11px] font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4m0 4h.01"/></svg>
                        {{ __('messages.credit_requires_customer') }}
                    </p>

                    {{-- Split payment info strip --}}
                    <div x-show="['cash','kpay','wavepay','cbpay','mmqr','credit'].filter(k => amtFor(k) > 0).length > 1"
                         class="mt-2 flex flex-wrap gap-1" x-cloak>
                        <template x-for="[mk, mlabel] in [['cash','{{ __('messages.payment_cash') }}'],['kpay','{{ __('messages.payment_kpay') }}'],['wavepay','{{ __('messages.payment_wavepay') }}'],['cbpay','{{ __('messages.payment_cb_pay') }}'],['mmqr','{{ __('messages.payment_mmqr') }}'],['credit','{{ __('messages.payment_credit') }}']]" :key="mk">
                            <span x-show="amtFor(mk) > 0"
                                  class="inline-flex items-center gap-1 rounded-lg bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 px-2 py-0.5 text-[10px] font-bold text-blue-700 dark:text-blue-300 cursor-pointer"
                                  @click="clearPaymentMethod(mk)" title="ဖယ်ရှားရန် click">
                                <span x-text="mlabel"></span>
                                <span class="font-black" x-text="formatCurrency(amtFor(mk))"></span>
                                <span class="opacity-60">✕</span>
                            </span>
                        </template>
                    </div>
                </div>

                {{-- ── Active method input area ── --}}
                <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 overflow-hidden">

                    {{-- Header row: label + current amount display --}}
                    <div class="px-4 pt-3 pb-2 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400"
                                  x-text="{
                                    cash: '{{ __('messages.payment_cash') }}',
                                    kpay: '{{ __('messages.payment_kpay') }}',
                                    wavepay: '{{ __('messages.payment_wavepay') }}',
                                    cb_pay: '{{ __('messages.payment_cb_pay') }}',
                                    mmqr: '{{ __('messages.payment_mmqr') }}',
                                    credit: '{{ __('messages.payment_credit') }}',
                                  }[activeMethod] || activeMethod"></span>
                            {{-- "Set remaining" quick button --}}
                            <button type="button" @click="setPaymentRemaining()"
                                    x-show="remaining > 0.5"
                                    class="inline-flex items-center gap-0.5 rounded-lg bg-emerald-100 dark:bg-emerald-950 border border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300 text-[10px] font-black px-2 py-0.5 cursor-pointer hover:bg-emerald-200 transition">
                                ← {{ __('messages.pos_remaining') }}
                                <span x-text="formatCurrency(remaining)"></span>
                            </button>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-base font-extrabold text-blue-600 dark:text-blue-400" x-text="window.__currencyConfig?.currency_symbol || 'Ks'"></span>
                            <input id="pos-active-method-input"
                                   type="number"
                                   min="0"
                                   step="any"
                                   x-effect="$el.value = getActiveAmount() === 0 ? '' : String(Math.round(getActiveAmount()))"
                                   @input="setActiveAmount($event.target.value)"
                                   @focus="$event.target.select()"
                                   @keydown.enter.prevent="if (exact) { $el.closest('form')?.requestSubmit(); }"
                                   :disabled="activeMethod === 'credit' && !customer"
                                   class="w-40 text-right text-2xl font-extrabold tabular-nums text-slate-800 dark:text-slate-100 bg-transparent border-b-2 border-blue-500/60 focus:border-blue-500 focus:outline-none px-1 py-0.5 disabled:opacity-40"
                                   placeholder="0">
                        </div>
                    </div>

                    {{-- Quick amount chips --}}
                    <div class="px-4 pb-3 flex flex-wrap gap-1.5">
                        @foreach ([1000, 2000, 5000, 10000, 20000, 50000, 100000] as $amt)
                        <button type="button" @click="setActiveAmount({{ $amt }})"
                                :class="getActiveAmount() == {{ $amt }} ? 'sf-btn-3d-primary active text-white' : 'sf-btn-3d text-slate-700 dark:text-slate-200'"
                                class="px-3 py-1.5 rounded-xl text-xs font-black tabular-nums transition cursor-pointer"
                                :disabled="activeMethod === 'credit' && !customer">
                            {{ number_format($amt) }}
                        </button>
                        @endforeach
                        {{-- Clear this method --}}
                        <button type="button" @click="clearPaymentMethod(activeKey)"
                                x-show="getActiveAmount() > 0"
                                class="sf-btn-3d-danger px-3 py-1.5 rounded-xl text-xs font-black text-white transition cursor-pointer">
                            ✕ {{ __('messages.clear') ?? 'ဖယ်ရှား' }}
                        </button>
                    </div>

                    {{-- Numpad (disabled but visible for credit when no customer) --}}
                    <div class="grid grid-cols-4 gap-1.5 p-2 bg-slate-100/70 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-700"
                         :class="activeMethod === 'credit' && !customer ? 'opacity-40 pointer-events-none' : ''">
                        @foreach (['7','8','9','←','4','5','6','C','1','2','3','000','0','00','',null] as $key)
                            @if ($key === null)
                                <span class="h-11"></span>
                            @elseif ($key === '')
                                <span class="h-11"></span>
                            @else
                                <button type="button"
                                        @click="padActive('{{ $key }}')"
                                        class="h-11 rounded-lg text-center font-black text-sm transition cursor-pointer flex items-center justify-center
                                               {{ $key === 'C' ? 'sf-btn-3d-danger text-white' : ($key === '←' ? 'sf-btn-3d-gold text-white' : 'sf-btn-3d text-slate-800 dark:text-slate-100') }}">
                                    {{ $key }}
                                </button>
                            @endif
                        @endforeach
                    </div>

                    {{-- Credit: customer info / warning note --}}
                    <div x-show="activeMethod === 'credit'" class="px-4 pb-3" x-cloak>
                        {{-- No customer attached yet --}}
                        <div x-show="!customer" class="flex items-start gap-2 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 px-3 py-2">
                            <svg class="w-4 h-4 text-rose-500 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4m0 4h.01"/></svg>
                            <div>
                                <p class="text-[11px] font-black text-rose-700 dark:text-rose-300">{{ __('messages.credit_requires_customer') }}</p>
                                <p class="text-[10px] text-rose-500 dark:text-rose-400 mt-0.5">{{ __('messages.credit_customer_required_hint') }}</p>
                            </div>
                        </div>
                        {{-- Customer attached --}}
                        <div x-show="customer" class="flex items-center gap-2 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 px-3 py-2">
                            <svg class="w-4 h-4 text-amber-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
                            <div class="min-w-0">
                                <p class="text-[11px] font-black text-amber-700 dark:text-amber-300 truncate" x-text="customer?.name || ''"></p>
                                <p class="text-[10px] text-amber-600 dark:text-amber-400" x-show="customer?.balance">
                                    {{ __('messages.balance_due') }}: <span class="font-black text-rose-600" x-text="formatCurrency(Math.abs(parseFloat(customer?.balance||0)))"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Summary box ── --}}
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 px-4 py-3 space-y-1.5 text-sm">
                    <p class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>{{ __('messages.subtotal') }}</span>
                        <span class="font-bold text-slate-700 dark:text-slate-200" x-text="formatCurrency(cart.totals.subtotal)"></span>
                    </p>
                    <p class="flex justify-between text-slate-500 dark:text-slate-400" x-show="cart.totals.tax_enabled && Number(cart.totals.tax) > 0 && cart.totals.tax_type === 'exclusive'" x-cloak>
                        <span>{{ __('messages.commercial_tax') }}:</span>
                        <span class="font-bold text-slate-700 dark:text-slate-200" x-text="'+ ' + formatCurrency(cart.totals.tax)"></span>
                    </p>
                    <p class="flex justify-between text-rose-600 dark:text-rose-400 font-semibold" x-show="Number(cart.totals.discount) > 0" x-cloak>
                        <span>{{ __('messages.discount') }}</span>
                        <span class="font-bold" x-text="'− ' + formatCurrency(cart.totals.discount)"></span>
                    </p>
                    <p class="flex justify-between" x-show="Number(cart.totals.retail_subtotal) > Number(cart.totals.total) && Number(cart.totals.discount) <= 0">
                        <span class="text-amber-600 dark:text-amber-400 inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><path d="M7 7h.01"/></svg>
                            {{ __('messages.pos_tier_total_savings') }}
                        </span>
                        <span class="font-black text-amber-600 dark:text-amber-400" x-text="'−' + formatCurrency(Number(cart.totals.retail_subtotal) - Number(cart.totals.total))"></span>
                    </p>
                    <div class="border-t border-dashed border-slate-200 dark:border-slate-700 my-1"></div>
                    <p class="flex justify-between font-bold">
                        <span class="text-slate-800 dark:text-slate-100">{{ __('messages.total') }}</span>
                        <span class="text-blue-600 dark:text-blue-400 font-black text-base" x-text="formatCurrency(cart.totals.total)"></span>
                    </p>
                    {{-- Breakdown of each active payment method --}}
                    <template x-for="[mk, mlabel] in [['cash','{{ __('messages.payment_cash') }}'],['kpay','{{ __('messages.payment_kpay') }}'],['wavepay','{{ __('messages.payment_wavepay') }}'],['cbpay','{{ __('messages.payment_cb_pay') }}'],['mmqr','{{ __('messages.payment_mmqr') }}']]" :key="mk">
                        <p class="flex justify-between text-slate-600 dark:text-slate-300" x-show="amtFor(mk) > 0">
                            <span class="flex items-center gap-1">
                                <svg class="w-3 h-3 opacity-60 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                <span x-text="mlabel"></span>
                            </span>
                            <span class="font-bold tabular-nums text-slate-900 dark:text-white" x-text="formatCurrency(amtFor(mk))"></span>
                        </p>
                    </template>
                    <div class="border-t border-dashed border-slate-200 dark:border-slate-700 my-1"></div>
                    {{-- Paid total --}}
                    <p class="flex justify-between font-bold">
                        <span class="text-slate-700 dark:text-slate-300">{{ __('messages.pos_paid_total') ?? 'ပေးငွေ စုစုပေါင်း' }}</span>
                        <span class="font-black tabular-nums"
                              :class="paid > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400'"
                              x-text="formatCurrency(paid)"></span>
                    </p>
                    <p class="flex justify-between" x-show="remaining > 0.005">
                        <span class="text-slate-700 dark:text-slate-300 font-semibold">{{ __('messages.pos_remaining') }}</span>
                        <span class="font-bold text-rose-600 dark:text-rose-400" x-text="formatCurrency(remaining)"></span>
                    </p>
                    <p class="flex justify-between" x-show="change > 0">
                        <span class="text-slate-700 dark:text-slate-300 font-semibold">{{ __('messages.change') }}</span>
                        <span class="font-extrabold text-emerald-600 dark:text-emerald-400 text-lg" x-text="formatCurrency(change)"></span>
                    </p>
                    <p class="flex justify-between" x-show="credit > 0">
                        <span class="text-amber-700 dark:text-amber-400 font-semibold flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1ZM16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/></svg>
                            {{ __('messages.payment_credit') }}
                        </span>
                        <span class="font-black text-amber-600 dark:text-amber-400" x-text="formatCurrency(credit)"></span>
                    </p>
                </div>

                {{-- ── Submit button ── --}}
                <div class="space-y-2">
                    {{-- State indicator --}}
                    <p x-show="!exact && remaining > 0.005"
                       class="text-center text-xs font-bold text-rose-600 dark:text-rose-400 flex items-center justify-center gap-1">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                        {{ __('messages.pos_remaining') }}: <span x-text="formatCurrency(remaining)"></span>
                    </p>
                    <p x-show="credit > 0 && !customer"
                       class="text-center text-xs font-bold text-rose-600 dark:text-rose-400">
                        {{ __('messages.credit_requires_customer') }}
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                        {{-- 1. Post sale only --}}
                        <button type="submit" name="print_receipt" value="0" :disabled="!exact"
                                :class="exact ? 'sf-btn-3d text-slate-800 dark:text-slate-100 shadow-md cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800' : 'sf-btn-3d opacity-45 cursor-not-allowed pointer-events-none'"
                                class="w-full rounded-xl px-3 py-3.5 sm:py-4 text-xs sm:text-sm font-black transition flex items-center justify-center gap-1.5 sm:gap-2">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600 dark:text-emerald-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span class="truncate" x-text="credit > 0
                                ? '{{ __('messages.post_sale') }} (' + formatCurrency(credit) + ')'
                                : '{{ __('messages.post_sale') }}'"></span>
                        </button>

                        {{-- 2. Post sale and print receipt --}}
                        <button type="submit" name="print_receipt" value="1" :disabled="!exact"
                                :class="exact ? 'sf-btn-3d-success text-white shadow-lg cursor-pointer' : 'sf-btn-3d opacity-45 cursor-not-allowed pointer-events-none'"
                                class="w-full rounded-xl px-3 py-3.5 sm:py-4 text-xs sm:text-sm font-black transition flex items-center justify-center gap-1.5 sm:gap-2">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                                <path d="M6 14h12v8H6z"/>
                            </svg>
                            <span class="truncate">{{ __('messages.post_and_print') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
