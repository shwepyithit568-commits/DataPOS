@extends('layouts.storefront.app')

@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')

@section('content')
@php
    $hideFloatingFabs = true;
    $storeSetting = $store?->setting;
    $storeLogo = $storeSetting?->storefrontLogo() ?? $storeSetting?->adminLogo();
    $storeLogoUrl = $storeLogo ? asset('storage/' . $storeLogo) : null;
@endphp

<div class="w-full max-w-5xl mx-auto space-y-1 sm:space-y-2 pb-16 sm:pb-12 select-none">
    {{-- Top Breadcrumb & Store Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-3 px-1 py-1 sm:py-1.5 border-b border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-none shadow-2xs">
        <div class="flex items-center gap-2 sm:gap-2.5 min-w-0">
            @if ($storeLogoUrl)
                <img src="{{ $storeLogoUrl }}" alt="{{ $store->name }}" class="h-8 w-8 sm:h-9 sm:w-9 rounded-none object-cover border border-slate-200 dark:border-slate-700 shadow-2xs shrink-0" />
            @else
                <div class="sf-btn-3d active flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-none text-white font-black text-xs sm:text-sm shrink-0 pointer-events-none">
                    {{ mb_substr($store?->name ?? 'D', 0, 1) }}
                </div>
            @endif
            <div class="min-w-0">
                <h1 class="text-sm sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-1.5 sm:gap-2 flex-wrap font-sans">
                    <span class="truncate">{{ __('messages.order_builder') }}</span>
                    <span class="sf-btn-3d active inline-flex items-center px-1.5 py-0.2 rounded-none text-[10px] sm:text-[11px] font-black pointer-events-none whitespace-nowrap">
                        {{ __('messages.order_fast_checkout') }}
                    </span>
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 font-medium truncate">
                    {{ $store?->name ?? 'Official Store' }} · {{ __('messages.order_builder_subtitle') }}
                </p>
            </div>
        </div>

        <a href="{{ url('/products?store_slug=' . ($store?->slug ?? request('store_slug'))) }}"
           class="sf-btn-3d self-start sm:self-auto !inline-flex items-center gap-1.5 px-3 py-1.5 rounded-none text-xs font-bold shrink-0">
            <span aria-hidden="true">←</span>
            <span>{{ __('messages.view_products') }}</span>
        </a>
    </div>

    {{-- Main Checkout Layout (2 Columns on Desktop) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-1 sm:gap-1.5 lg:gap-2 items-start">
        {{-- Selected Products Column (7 cols) --}}
        <div class="lg:col-span-7 space-y-1 sm:space-y-1.5">
            <div class="bg-white dark:bg-slate-900 rounded-none p-2 sm:p-3.5 lg:p-4 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2 sm:space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-2">
                    <h2 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span class="inline-flex h-5 w-5 sm:h-6 sm:w-6 items-center justify-center rounded-none bg-slate-100 dark:bg-slate-800 text-xs">🛒</span>
                        <span>{{ __('messages.selected_products') }}</span>
                    </h2>
                    <span class="text-[10px] sm:text-xs font-black text-[color:var(--sf-primary)] dark:text-[color:var(--sf-primary-hover)] bg-slate-50 dark:bg-slate-800 px-2 py-0.5 rounded-none border border-slate-200/80 dark:border-slate-700/80"
                          x-text="($store.orderBuilder ? $store.orderBuilder.totalCount : 0) + ' ' + '{{ __('messages.order_items_unit') }}'"></span>
                </div>

                {{-- Empty State --}}
                <template x-if="!$store.orderBuilder || $store.orderBuilder.items.length === 0">
                    <div class="text-center py-8 sm:py-10 space-y-2.5 bg-slate-50/50 dark:bg-slate-800/30 rounded-none border border-dashed border-slate-200 dark:border-slate-700/80 p-4">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 mx-auto rounded-none bg-white dark:bg-slate-800 flex items-center justify-center text-2xl sm:text-3xl shadow-2xs border border-slate-200/80 dark:border-slate-700">🛍️</div>
                        <div class="space-y-0.5">
                            <h3 class="font-black text-xs sm:text-sm text-slate-800 dark:text-slate-200">{{ __('messages.order_list_empty') }}</h3>
                            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 font-medium">{{ __('messages.order_list_empty_hint') }}</p>
                        </div>
                        <a href="{{ url('/products?store_slug=' . ($store?->slug ?? request('store_slug'))) }}"
                           class="sf-btn-3d-primary !inline-flex items-center gap-1.5 px-4 py-2 rounded-none font-black text-xs shadow-xs">
                            <span>{{ __('messages.view_products') }}</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </template>

                {{-- Item List --}}
                <div class="space-y-1.5 sm:space-y-2 divide-y divide-slate-100 dark:divide-slate-800/80">
                    <template x-for="item in ($store.orderBuilder ? $store.orderBuilder.items : [])" :key="item.id">
                        <div class="pt-1.5 sm:pt-2 first:pt-0 flex items-center justify-between gap-2 sm:gap-3">
                            <div class="flex items-center space-x-2 sm:space-x-2.5 flex-1 min-w-0">
                                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-none bg-slate-100 dark:bg-slate-800 shrink-0 overflow-hidden border border-slate-200/80 dark:border-slate-700 flex items-center justify-center">
                                    <template x-if="item.image_path">
                                        <img :src="'/storage/' + item.image_path" class="w-full h-full object-cover pointer-events-none" loading="lazy" decoding="async" />
                                    </template>
                                    <template x-if="!item.image_path">
                                        <span class="text-[9px] sm:text-[10px] text-slate-400 font-bold">{{ __('messages.order_no_image') }}</span>
                                    </template>
                                </div>
                                <div class="min-w-0 space-y-0.5">
                                    <h4 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white truncate" x-text="item.name"></h4>
                                    <div class="flex items-center gap-2">
                                        <p class="text-[10px] sm:text-[11px] font-mono text-slate-500 dark:text-slate-400" x-show="item.sku" x-text="'SKU: ' + item.sku"></p>
                                    </div>
                                    <p class="text-xs sm:text-sm font-black text-[color:var(--sf-primary)] dark:text-[color:var(--sf-primary-hover)] font-sans"
                                       x-text="typeof window.formatCurrency === 'function' ? window.formatCurrency(item.price * item.quantity) : (item.price * item.quantity).toLocaleString()"></p>
                                </div>
                            </div>

                            {{-- Pure CSS 3D Quantity Steppers & 3D Delete --}}
                            <div class="flex items-center space-x-1 sm:space-x-1.5 shrink-0">
                                <div class="flex items-center border border-slate-200 dark:border-slate-700 rounded-none bg-slate-50 dark:bg-slate-800/80 p-0.5 shadow-2xs">
                                    <button @click="$store.orderBuilder.updateQty(item.id, -1)"
                                            type="button"
                                            class="sf-btn-3d w-7 h-7 sm:w-8 sm:h-8 rounded-none !flex items-center justify-center font-black text-xs sm:text-sm text-slate-700 dark:text-slate-200 transition cursor-pointer select-none"
                                            aria-label="Decrease quantity">-</button>
                                    <span class="px-1.5 sm:px-2 font-black text-xs sm:text-sm text-slate-900 dark:text-white min-w-[22px] text-center select-none" x-text="item.quantity"></span>
                                    <button @click="$store.orderBuilder.updateQty(item.id, 1)"
                                            type="button"
                                            class="sf-btn-3d w-7 h-7 sm:w-8 sm:h-8 rounded-none !flex items-center justify-center font-black text-xs sm:text-sm text-slate-700 dark:text-slate-200 transition cursor-pointer select-none"
                                            aria-label="Increase quantity">+</button>
                                </div>
                                <button @click="$store.orderBuilder.removeItem(item.id)"
                                        type="button"
                                        class="sf-btn-3d-danger w-7 h-7 sm:w-8 sm:h-8 rounded-none !flex items-center justify-center text-white transition cursor-pointer shadow-2xs"
                                        title="{{ __('messages.order_remove_item') }}"
                                        aria-label="{{ __('messages.order_remove_item') }}">
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Price Total Box --}}
                <div class="pt-2 sm:pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs sm:text-sm">
                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.total_amount') }}:</span>
                    <span class="text-base sm:text-xl font-black text-[color:var(--sf-primary)] dark:text-[color:var(--sf-primary-hover)] font-sans"
                          x-text="typeof window.formatCurrency === 'function' ? window.formatCurrency($store.orderBuilder ? $store.orderBuilder.totalAmount : 0) : ($store.orderBuilder ? $store.orderBuilder.totalAmount.toLocaleString() : 0)"></span>
                </div>
            </div>

            {{-- Trust badges (Sharp 3D tiles) --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-1 sm:gap-1.5 text-center text-xs">
                <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded-none border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col items-center justify-center">
                    <span class="text-sm sm:text-base block mb-0.5">⚡</span>
                    <span class="font-black text-[11px] sm:text-xs text-slate-800 dark:text-slate-200">{{ __('messages.fast_delivery') }}</span>
                </div>
                <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded-none border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col items-center justify-center">
                    <span class="text-sm sm:text-base block mb-0.5">🛡️</span>
                    <span class="font-black text-[11px] sm:text-xs text-slate-800 dark:text-slate-200">{{ __('messages.genuine_warranty') }}</span>
                </div>
                <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded-none border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col items-center justify-center">
                    <span class="text-sm sm:text-base block mb-0.5">💬</span>
                    <span class="font-black text-[11px] sm:text-xs text-slate-800 dark:text-slate-200">{{ __('messages.direct_support') }}</span>
                </div>
                <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded-none border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col items-center justify-center">
                    <span class="text-sm sm:text-base block mb-0.5">🚚</span>
                    <span class="font-black text-[11px] sm:text-xs text-slate-800 dark:text-slate-200">{{ __('messages.nationwide_shipping') }}</span>
                </div>
            </div>
        </div>

        {{-- Checkout Form Column (5 cols) --}}
        <div class="lg:col-span-5 space-y-1 sm:space-y-1.5">
            <div class="bg-white dark:bg-slate-900 rounded-none p-2 sm:p-3.5 lg:p-4 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2 sm:space-y-3 sticky top-16 sm:top-20"
                 x-data="{
                     contactChannel: '{{ old('contact_channel', 'phone') }}',
                     submitting: false,
                     submitOrder(e) {
                         if (this.submitting) return;
                         if (!window.Alpine.store('orderBuilder') || window.Alpine.store('orderBuilder').items.length === 0) {
                             e.preventDefault();
                             alert('{{ __('messages.order_select_items_first') }}');
                             return;
                         }
                         this.submitting = true;
                         this.$refs.itemsJsonInput.value = JSON.stringify(window.Alpine.store('orderBuilder').items);
                         this.$refs.orderForm.submit();
                     }
                 }">
                <div class="border-b border-slate-100 dark:border-slate-800/80 pb-2">
                    <h2 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span class="inline-flex h-5 w-5 sm:h-6 sm:w-6 items-center justify-center rounded-none bg-slate-100 dark:bg-slate-800 text-xs">📝</span>
                        <span>{{ __('messages.customer_info') }}</span>
                    </h2>
                </div>

                <form x-ref="orderForm" method="POST" action="{{ url('/store/' . ($store?->slug ?? request('store_slug')) . '/orders') }}" @submit.prevent="submitOrder($event)" class="space-y-2.5 sm:space-y-3">
                    @csrf
                    <input type="hidden" name="items_json" x-ref="itemsJsonInput" value="" />
                    <input type="hidden" name="contact_channel" :value="contactChannel" />

                    {{-- Customer Name --}}
                    <div>
                        <label class="block text-[11px] sm:text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.customer_name') }} <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="customer_name"
                            value="{{ old('customer_name', auth()->user()?->name) }}"
                            required
                            placeholder="{{ __('messages.order_customer_name_placeholder') }}"
                            class="w-full px-3 py-2 rounded-none border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white focus:ring-1 focus:ring-[color:var(--sf-primary)] focus:border-[color:var(--sf-primary)] outline-none transition"
                        />
                        @error('customer_name')
                            <p class="text-rose-500 text-[11px] font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Customer Phone --}}
                    <div>
                        <label class="block text-[11px] sm:text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.customer_phone') }} <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="tel"
                            name="customer_phone"
                            value="{{ old('customer_phone', auth()->user()?->phone) }}"
                            required
                            placeholder="{{ __('messages.order_customer_phone_placeholder') }}"
                            class="w-full px-3 py-2 rounded-none border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white focus:ring-1 focus:ring-[color:var(--sf-primary)] focus:border-[color:var(--sf-primary)] outline-none transition"
                        />
                        @error('customer_phone')
                            <p class="text-rose-500 text-[11px] font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Delivery Address --}}
                    <div>
                        <label class="block text-[11px] sm:text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.delivery_address') }} <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            name="customer_address"
                            rows="2"
                            required
                            placeholder="{{ __('messages.order_delivery_address_placeholder') }}"
                            class="w-full px-3 py-1.5 rounded-none border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white focus:ring-1 focus:ring-[color:var(--sf-primary)] focus:border-[color:var(--sf-primary)] outline-none transition"
                        >{{ old('customer_address') }}</textarea>
                        @error('customer_address')
                            <p class="text-rose-500 text-[11px] font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Customer Note --}}
                    <div>
                        <label class="block text-[11px] sm:text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.notes') }} ({{ __('messages.optional') }})
                        </label>
                        <input
                            type="text"
                            name="customer_note"
                            value="{{ old('customer_note') }}"
                            placeholder="{{ __('messages.order_customer_note_placeholder') }}"
                            class="w-full px-3 py-1.5 rounded-none border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white focus:ring-1 focus:ring-[color:var(--sf-primary)] focus:border-[color:var(--sf-primary)] outline-none transition"
                        />
                    </div>

                    {{-- Additional Identifier for Viber/Telegram --}}
                    <div class="space-y-1" x-show="contactChannel === 'viber' || contactChannel === 'telegram'" x-transition>
                        <label class="block text-[11px] sm:text-xs font-bold text-slate-700 dark:text-slate-300">
                            <span x-text="contactChannel === 'viber' ? 'Viber Phone Number' : 'Telegram Username'"></span>
                        </label>
                        <input
                            :type="contactChannel === 'viber' ? 'tel' : 'text'"
                            name="contact_identifier"
                            value="{{ old('contact_identifier') }}"
                            :placeholder="contactChannel === 'viber' ? '09xxxxxxxxx' : '@username'"
                            :inputmode="contactChannel === 'viber' ? 'tel' : 'text'"
                            class="w-full rounded-none border border-slate-300 dark:border-slate-700 px-3 py-1.5 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-1 focus:ring-[color:var(--sf-primary)] focus:border-[color:var(--sf-primary)] focus:outline-none shadow-2xs"
                            autocomplete="tel"
                        />
                    </div>

                    {{-- Preferred Contact Channel (Big 4-Grid Action Buttons) --}}
                    <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800/80">
                        <label class="block text-[11px] sm:text-xs font-black text-slate-900 dark:text-white mb-1.5 flex items-center justify-between">
                            <span>{{ __('messages.order_confirm_channel_prompt') }}</span>
                            <span class="text-[10px] text-[color:var(--sf-primary)] font-bold uppercase tracking-wider" x-text="contactChannel"></span>
                        </label>

                        <div class="grid grid-cols-2 gap-1.5 sm:gap-2">
                            {{-- 1. Viber Channel Button --}}
                            <button type="button" @click="contactChannel = 'viber'"
                                class="sf-btn-3d-viber !flex items-center justify-center gap-1.5 py-2.5 sm:py-3 px-2 rounded-none text-xs font-black select-none transition cursor-pointer"
                                :class="contactChannel === 'viber' ? 'ring-2 ring-purple-400 dark:ring-purple-300 shadow-md brightness-110' : 'opacity-85 hover:opacity-100'">
                                <span class="inline-flex items-center justify-center w-4 h-4 sm:w-5 sm:h-5 rounded-none bg-white/20 shrink-0">
                                    <x-brand-icon brand="viber" class="h-3.5 w-3.5 shrink-0 fill-white text-white"/>
                                </span>
                                <span class="font-black text-white">Viber</span>
                            </button>

                            {{-- 2. Telegram Channel Button --}}
                            <button type="button" @click="contactChannel = 'telegram'"
                                class="sf-btn-3d-telegram !flex items-center justify-center gap-1.5 py-2.5 sm:py-3 px-2 rounded-none text-xs font-black select-none transition cursor-pointer"
                                :class="contactChannel === 'telegram' ? 'ring-2 ring-sky-300 dark:ring-sky-200 shadow-md brightness-110' : 'opacity-85 hover:opacity-100'">
                                <span class="inline-flex items-center justify-center w-4 h-4 sm:w-5 sm:h-5 rounded-none bg-white/20 shrink-0">
                                    <x-brand-icon brand="telegram" class="h-3.5 w-3.5 shrink-0 fill-white text-white"/>
                                </span>
                                <span class="font-black text-white">Telegram</span>
                            </button>

                            {{-- 3. Phone Channel Button --}}
                            <button type="button" @click="contactChannel = 'phone'"
                                class="sf-btn-3d-success !flex items-center justify-center gap-1.5 py-2.5 sm:py-3 px-2 rounded-none text-xs font-black select-none transition cursor-pointer"
                                :class="contactChannel === 'phone' ? 'ring-2 ring-emerald-400 dark:ring-emerald-300 shadow-md brightness-110' : 'opacity-85 hover:opacity-100'">
                                <span class="inline-flex items-center justify-center w-4 h-4 sm:w-5 sm:h-5 rounded-none bg-white/20 shrink-0 text-xs" aria-hidden="true">📞</span>
                                <span class="font-black text-white">Phone</span>
                            </button>

                            {{-- 4. Submit (Send Order) — sf-btn-3d-primary --}}
                            <button
                                type="submit"
                                :disabled="!$store.orderBuilder || $store.orderBuilder.items.length === 0"
                                class="sf-btn-3d-primary !flex items-center justify-center gap-1.5 py-2.5 sm:py-3 px-2 rounded-none text-xs font-black disabled:opacity-50 disabled:pointer-events-none select-none cursor-pointer"
                            >
                                <span class="text-xs sm:text-sm" aria-hidden="true">⚡</span>
                                <span class="font-black whitespace-nowrap">{{ __('messages.send_order') }}</span>
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delivery & Payment Info (Sharp 3D Grid) --}}
    @php
        $obPayments = $store?->paymentMethods()->active()->get() ?? collect();
        $obDeliveries = $store?->deliveryMethods()->active()->get() ?? collect();
        $obHasPayment = $obPayments->isNotEmpty() || !empty($store?->setting?->payment_info);
        $obHasDelivery = $obDeliveries->isNotEmpty() || !empty($store?->setting?->delivery_info);
    @endphp
    @if ($obHasDelivery || $obHasPayment)
        <div class="bg-white dark:bg-slate-900 rounded-none p-2 sm:p-3.5 lg:p-4 border border-slate-200/90 dark:border-slate-800 shadow-xs">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-3">
                @if ($obHasDelivery)
                    <div class="space-y-1.5">
                        <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                            <span>🚚</span>
                            <span>{{ __('messages.delivery') }}</span>
                        </h3>
                        @if ($obDeliveries->isNotEmpty())
                            <ul class="space-y-1">
                                @foreach ($obDeliveries as $dm)
                                    <li class="rounded-none bg-slate-50 dark:bg-slate-800/60 p-2 border border-slate-100 dark:border-slate-700/60">
                                        <p class="text-xs font-black text-slate-900 dark:text-white">{{ $dm->icon ?: '🚚' }} {{ $dm->name }}
                                            @if ($dm->estimated_time) <span class="font-bold text-[color:var(--sf-primary)]">· {{ $dm->estimated_time }}</span> @endif
                                        </p>
                                        @if ($dm->service_area || $dm->fee_note || $dm->description)
                                            <p class="mt-0.5 text-[11px] leading-relaxed text-slate-600 dark:text-slate-400">{{ collect([$dm->service_area, $dm->fee_note, $dm->description])->filter()->implode(' · ') }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line bg-slate-50 dark:bg-slate-800/60 p-2 rounded-none">{{ $store->setting->delivery_info }}</p>
                        @endif
                    </div>
                @endif
                @if ($obHasPayment)
                    <div class="space-y-1.5">
                        <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                            <span>💳</span>
                            <span>{{ __('messages.payment') }}</span>
                        </h3>
                        @if ($obPayments->isNotEmpty())
                            <ul class="space-y-1">
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
                                            class="w-full flex items-center justify-between gap-2 rounded-none bg-white dark:bg-slate-800/70 p-2 border border-slate-200/80 dark:border-slate-700/60 hover:border-slate-400 dark:hover:border-slate-500 transition group cursor-pointer text-left">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <x-payment-method-icon :method="$pm" class="h-6 w-6 shrink-0 group-hover:scale-105 transition-transform" />
                                                <div class="min-w-0">
                                                    <p class="text-xs font-black text-slate-900 dark:text-white truncate">{{ $pm->name }}</p>
                                                </div>
                                            </div>
                                            <span class="shrink-0 px-1.5 py-0.5 rounded-none bg-slate-100 group-hover:bg-slate-200 dark:bg-slate-700 dark:group-hover:bg-slate-600 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-[10px] font-bold transition flex items-center gap-1">
                                                <span>{{ $pm->hasQr() ? '📱 ' . __('messages.order_payment_qr_btn') : __('messages.order_payment_info_btn') }}</span>
                                                <span>→</span>
                                            </span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line bg-slate-50 dark:bg-slate-800/60 p-2 rounded-none">{{ $store->setting->payment_info }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

