@extends('layouts.storefront.app')

@section('content')
@php
    $hideFloatingFabs = true;
    $storeSetting = $store?->setting;
    $storeLogo = $storeSetting?->storefrontLogo() ?? $storeSetting?->adminLogo();
    $storeLogoUrl = $storeLogo ? asset('storage/' . $storeLogo) : null;
@endphp

<div class="max-w-5xl mx-auto space-y-0.5 sm:space-y-1 pb-12">
    {{-- Top Breadcrumb & Store Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200/80 dark:border-slate-800">
        <div class="flex items-center gap-2.5">
            @if ($storeLogoUrl)
                <img src="{{ $storeLogoUrl }}" alt="{{ $store->name }}" class="h-9 w-9 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shadow-xs" />
            @else
                <div class="sf-btn-3d active flex h-9 w-9 items-center justify-center rounded-xl text-white font-black text-sm pointer-events-none">
                    {{ mb_substr($store?->name ?? 'D', 0, 1) }}
                </div>
            @endif
            <div>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white flex items-center gap-2 font-sans">
                    <span>{{ __('messages.order_builder') }}</span>
                    <span class="sf-btn-3d active inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-black pointer-events-none">
                        ⚡ Fast Checkout
                    </span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    {{ $store?->name ?? 'Official Store' }} · {{ __('messages.order_builder_subtitle') }}
                </p>
            </div>
        </div>

        <a href="{{ url('/products?store_slug=' . ($store?->slug ?? request('store_slug'))) }}"
           class="sf-btn-3d self-start sm:self-auto !inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold">
            <span aria-hidden="true">←</span>
            <span>{{ __('messages.view_products') }}</span>
        </a>
    </div>

    {{-- Main Checkout Layout (2 Columns on Desktop) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6 items-start">
        {{-- Selected Products Column (7 cols) --}}
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-3">
                    <h2 class="font-black text-sm sm:text-base text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-950/60 text-[#f85606] text-xs">🛒</span>
                        <span>{{ __('messages.selected_products') }}</span>
                    </h2>
                    <span class="text-xs font-black text-[#f85606] dark:text-orange-400 bg-orange-50 dark:bg-orange-950/40 px-2.5 py-0.5 rounded-full border border-orange-200/60 dark:border-orange-900/60" x-text="($store.orderBuilder ? $store.orderBuilder.totalCount : 0) + ' ပစ္စည်း'"></span>
                </div>

                {{-- Empty State --}}
                <template x-if="!$store.orderBuilder || $store.orderBuilder.items.length === 0">
                    <div class="text-center py-10 space-y-3">
                        <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-3xl shadow-inner">🛍️</div>
                        <div class="space-y-1">
                            <h3 class="font-bold text-sm text-slate-800 dark:text-slate-200">{{ __('messages.order_list_empty') }}</h3>
                            <p class="text-xs text-slate-500 font-medium">{{ __('messages.order_list_empty_hint') }}</p>
                        </div>
                        <a href="{{ url('/products?store_slug=' . ($store?->slug ?? request('store_slug'))) }}"
                           class="sf-btn-3d-primary inline-flex items-center gap-1.5 px-4 py-2 rounded-xl font-black text-xs">
                            <span>{{ __('messages.view_products') }}</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </template>

                {{-- Item List --}}
                <div class="space-y-3 divide-y divide-slate-100 dark:divide-slate-800/80">
                    <template x-for="item in ($store.orderBuilder ? $store.orderBuilder.items : [])" :key="item.id">
                        <div class="pt-3 first:pt-0 flex items-center justify-between gap-3">
                            <div class="flex items-center space-x-3 flex-1 min-w-0">
                                <div class="w-14 h-14 rounded-xl bg-slate-100 dark:bg-slate-800 shrink-0 overflow-hidden border border-slate-200/80 dark:border-slate-700 flex items-center justify-center">
                                    <template x-if="item.image_path">
                                        <img :src="'/storage/' + item.image_path" class="w-full h-full object-cover" />
                                    </template>
                                    <template x-if="!item.image_path">
                                        <span class="text-[10px] text-slate-400 font-bold">No Pic</span>
                                    </template>
                                </div>
                                <div class="min-w-0 space-y-0.5">
                                    <h4 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white truncate" x-text="item.name"></h4>
                                    <div class="flex items-center gap-2">
                                        <p class="text-[11px] font-mono text-slate-500 dark:text-slate-400" x-show="item.sku" x-text="'SKU: ' + item.sku"></p>
                                    </div>
                                    <p class="text-xs font-black text-[#f85606] dark:text-orange-400 font-sans" x-text="typeof window.formatCurrency === 'function' ? window.formatCurrency(item.price * item.quantity) : 'Ks ' + (item.price * item.quantity).toLocaleString()"></p>
                                </div>
                            </div>

                            {{-- Quantity controls & Delete --}}
                            <div class="flex items-center space-x-2 shrink-0">
                                <div class="flex items-center border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 p-0.5 shadow-2xs">
                                    <button @click="$store.orderBuilder.updateQty(item.id, -1)" type="button" class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-sm text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 transition cursor-pointer select-none">-</button>
                                    <span class="px-2 font-black text-xs sm:text-sm text-slate-900 dark:text-white min-w-[24px] text-center" x-text="item.quantity"></span>
                                    <button @click="$store.orderBuilder.updateQty(item.id, 1)" type="button" class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-sm text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 transition cursor-pointer select-none">+</button>
                                </div>
                                <button @click="$store.orderBuilder.removeItem(item.id)" type="button" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/60 transition cursor-pointer" title="Remove">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Price Total Box --}}
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-sm">
                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.total_amount') }}:</span>
                    <span class="text-lg sm:text-xl font-black text-[#f85606] dark:text-orange-400 font-sans" x-text="typeof window.formatCurrency === 'function' ? window.formatCurrency($store.orderBuilder ? $store.orderBuilder.totalAmount : 0) : 'Ks ' + ($store.orderBuilder ? $store.orderBuilder.totalAmount.toLocaleString() : 0)"></span>
                </div>
            </div>

            {{-- Trust badges --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
                <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800">
                    <span class="text-base block mb-1">⚡</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ __('messages.fast_delivery') }}</span>
                </div>
                <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800">
                    <span class="text-base block mb-1">🛡️</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ __('messages.genuine_warranty') }}</span>
                </div>
                <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800">
                    <span class="text-base block mb-1">💬</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ __('messages.direct_support') }}</span>
                </div>
                <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800">
                    <span class="text-base block mb-1">🚚</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ __('messages.nationwide_shipping') }}</span>
                </div>
            </div>
        </div>

        {{-- Checkout Form Column (5 cols) --}}
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4 sticky top-24"
                 x-data="{
                     contactChannel: '{{ old('contact_channel', 'phone') }}',
                     submitting: false,
                     submitOrder(e) {
                         if (this.submitting) return;
                         if (!window.Alpine.store('orderBuilder') || window.Alpine.store('orderBuilder').items.length === 0) {
                             e.preventDefault();
                             alert('ကျေးဇူးပြု၍ မှာယူလိုသည့် ပစ္စည်းအရင်ရွေးချယ်ပါ');
                             return;
                         }
                         this.submitting = true;
                         this.$refs.itemsJsonInput.value = JSON.stringify(window.Alpine.store('orderBuilder').items);
                         this.$refs.orderForm.submit();
                     }
                 }">
                <div class="border-b border-slate-100 dark:border-slate-800/80 pb-3">
                    <h2 class="font-black text-sm sm:text-base text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-950/60 text-[#f85606] text-xs">📝</span>
                        <span>{{ __('messages.customer_info') }}</span>
                    </h2>
                </div>

                <form x-ref="orderForm" method="POST" action="{{ url('/store/' . ($store?->slug ?? request('store_slug')) . '/orders') }}" @submit.prevent="submitOrder($event)" class="space-y-3.5">
                    @csrf
                    <input type="hidden" name="items_json" x-ref="itemsJsonInput" value="" />
                    <input type="hidden" name="contact_channel" :value="contactChannel" />

                    {{-- Customer Name --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.customer_name') }} <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="customer_name"
                            value="{{ old('customer_name', auth()->user()?->name) }}"
                            required
                            placeholder="ဥပမာ - မောင်မောင်"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-[#f85606] outline-none transition"
                        />
                        @error('customer_name')
                            <p class="text-rose-500 text-[11px] font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Customer Phone --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.customer_phone') }} <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="tel"
                            name="customer_phone"
                            value="{{ old('customer_phone', auth()->user()?->phone) }}"
                            required
                            placeholder="ဥပမာ - 09xxxxxxxxx"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-[#f85606] outline-none transition"
                        />
                        @error('customer_phone')
                            <p class="text-rose-500 text-[11px] font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Delivery Address --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.delivery_address') }} <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            name="customer_address"
                            rows="2"
                            required
                            placeholder="ပို့ဆောင်ပေးရမည့် လိပ်စာ (မြို့နယ်/လမ်း/အိမ်နံပါတ်)"
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-[#f85606] outline-none transition"
                        >{{ old('customer_address') }}</textarea>
                        @error('customer_address')
                            <p class="text-rose-500 text-[11px] font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Customer Note --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.notes') }} ({{ __('messages.optional') }})
                        </label>
                        <input
                            type="text"
                            name="customer_note"
                            value="{{ old('customer_note') }}"
                            placeholder="မှာကြားလိုသည့် အချက်အလက်များ"
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-[#f85606] outline-none transition"
                        />
                    </div>

                    {{-- Additional Identifier for Viber/Telegram --}}
                    <div class="space-y-1" x-show="contactChannel === 'viber' || contactChannel === 'telegram'" x-transition>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            <span x-text="contactChannel === 'viber' ? 'Viber Phone Number' : 'Telegram Username'"></span>
                        </label>
                        <input
                            :type="contactChannel === 'viber' ? 'tel' : 'text'"
                            name="contact_identifier"
                            value="{{ old('contact_identifier') }}"
                            :placeholder="contactChannel === 'viber' ? '09xxxxxxxxx' : '@username'"
                            :inputmode="contactChannel === 'viber' ? 'tel' : 'text'"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-2 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-[#f85606] focus:outline-none shadow-2xs"
                            autocomplete="tel"
                        />
                    </div>

                    {{-- Preferred Contact Channel (Big 4-Grid Action Buttons) --}}
                    <div class="pt-2">
                        <label class="block text-xs font-black text-slate-900 dark:text-white mb-2 flex items-center justify-between">
                            <span>အော်ဒါအတည်ပြုမည့် နည်းလမ်း ရွေးချယ်ပါ:</span>
                            <span class="text-[10px] text-orange-500 font-bold" x-text="contactChannel.toUpperCase()"></span>
                        </label>

                        <div class="grid grid-cols-2 gap-2.5">
                            {{-- 1. Viber Channel Button — sf-btn-3d neutral + active state driven by token --}}
                            <button type="button" @click="contactChannel = 'viber'"
                                class="sf-btn-3d !flex items-center justify-center gap-2 py-3 px-3 rounded-xl text-xs font-black select-none transition"
                                :class="contactChannel === 'viber' ? 'active ring-2 ring-[color:var(--sf-primary)]/40 ring-offset-1' : 'opacity-85 hover:opacity-100'">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-current/10 shrink-0" :class="contactChannel === 'viber' ? 'bg-white/20' : ''">
                                    <x-brand-icon brand="viber" class="h-3.5 w-3.5 shrink-0"/>
                                </span>
                                <span class="font-black">Viber</span>
                            </button>

                            {{-- 2. Telegram Channel Button --}}
                            <button type="button" @click="contactChannel = 'telegram'"
                                class="sf-btn-3d !flex items-center justify-center gap-2 py-3 px-3 rounded-xl text-xs font-black select-none transition"
                                :class="contactChannel === 'telegram' ? 'active ring-2 ring-[color:var(--sf-primary)]/40 ring-offset-1' : 'opacity-85 hover:opacity-100'">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-current/10 shrink-0" :class="contactChannel === 'telegram' ? 'bg-white/20' : ''">
                                    <x-brand-icon brand="telegram" class="h-3.5 w-3.5 shrink-0"/>
                                </span>
                                <span class="font-black">Telegram</span>
                            </button>

                            {{-- 3. Phone Channel Button --}}
                            <button type="button" @click="contactChannel = 'phone'"
                                class="sf-btn-3d-success !flex items-center justify-center gap-2 py-3 px-3 rounded-xl text-xs font-black select-none transition"
                                :class="contactChannel === 'phone' ? 'ring-2 ring-emerald-400/50 ring-offset-1' : 'opacity-85 hover:opacity-100'">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-white/20 shrink-0 text-xs" aria-hidden="true">📞</span>
                                <span class="font-black text-white">Phone</span>
                            </button>

                            {{-- 4. Submit (Send Order) — sf-btn-3d-primary --}}
                            <button
                                type="submit"
                                :disabled="!$store.orderBuilder || $store.orderBuilder.items.length === 0"
                                class="sf-btn-3d-primary !flex items-center justify-center gap-1.5 py-3 px-3 rounded-xl text-xs font-black disabled:opacity-50 disabled:pointer-events-none select-none cursor-pointer"
                            >
                                <span class="text-sm" aria-hidden="true">⚡</span>
                                <span class="font-black whitespace-nowrap">{{ __('messages.send_order') }}</span>
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delivery & Payment Info (Collapsible / Clean Grid) --}}
    @php
        $obPayments = $store?->paymentMethods()->active()->get() ?? collect();
        $obDeliveries = $store?->deliveryMethods()->active()->get() ?? collect();
        $obHasPayment = $obPayments->isNotEmpty() || !empty($store?->setting?->payment_info);
        $obHasDelivery = $obDeliveries->isNotEmpty() || !empty($store?->setting?->delivery_info);
    @endphp
    @if ($obHasDelivery || $obHasPayment)
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @if ($obHasDelivery)
                    <div class="space-y-2">
                        <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                            <span>🚚</span>
                            <span>{{ __('messages.delivery') }}</span>
                        </h3>
                        @if ($obDeliveries->isNotEmpty())
                            <ul class="space-y-1.5">
                                @foreach ($obDeliveries as $dm)
                                    <li class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-2.5 border border-slate-100 dark:border-slate-700/60">
                                        <p class="text-xs font-black text-slate-900 dark:text-white">{{ $dm->icon ?: '🚚' }} {{ $dm->name }}
                                            @if ($dm->estimated_time) <span class="font-bold text-[#f85606] dark:text-orange-400">· {{ $dm->estimated_time }}</span> @endif
                                        </p>
                                        @if ($dm->service_area || $dm->fee_note || $dm->description)
                                            <p class="mt-0.5 text-[11px] leading-relaxed text-slate-600 dark:text-slate-400">{{ collect([$dm->service_area, $dm->fee_note, $dm->description])->filter()->implode(' · ') }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl">{{ $store->setting->delivery_info }}</p>
                        @endif
                    </div>
                @endif
                @if ($obHasPayment)
                    <div class="space-y-2">
                        <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                            <span>💳</span>
                            <span>{{ __('messages.payment') }}</span>
                        </h3>
                        @if ($obPayments->isNotEmpty())
                            <ul class="space-y-1.5">
                                @foreach ($obPayments as $pm)
                                    <li>
                                        <button type="button"
                                            @click="$dispatch('open-payment-modal', {
                                                name: @js($pm->name),
                                                qr_url: @js($pm->qrUrl()),
                                                account_name: @js($pm->show_account_details ? $pm->account_name : null),
                                                account_number: @js($pm->show_account_details ? $pm->account_number : null),
                                                instructions: @js($pm->instructions),
                                            })"
                                            class="w-full flex items-center justify-between gap-2.5 rounded-xl bg-white dark:bg-slate-800/70 p-2 sm:p-2.5 border border-slate-200/80 dark:border-slate-700/60 hover:border-violet-300 dark:hover:border-violet-600 transition group cursor-pointer text-left">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <x-payment-method-icon :method="$pm" class="h-7 w-7 shrink-0 group-hover:scale-105 transition-transform" />
                                                <div class="min-w-0">
                                                    <p class="text-xs font-black text-slate-900 dark:text-white truncate">{{ $pm->name }}</p>
                                                </div>
                                            </div>
                                            <span class="shrink-0 px-2 py-0.5 rounded-lg bg-violet-50 group-hover:bg-violet-100 dark:bg-violet-950/40 dark:group-hover:bg-violet-900/60 border border-violet-200 dark:border-violet-800 text-violet-700 dark:text-violet-300 text-[11px] font-bold transition flex items-center gap-1">
                                                <span>{{ $pm->hasQr() ? '📱 QR' : 'အချက်အလက်' }}</span>
                                                <span>→</span>
                                            </span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl">{{ $store->setting->payment_info }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
