@extends('layouts.storefront.app')

@section('content')
@php
    $hideFloatingFabs = true;
@endphp
<div class="max-w-4xl mx-auto space-y-1 sm:space-y-1.5 lg:space-y-2">
    {{-- Header --}}
    <div class="text-center max-w-xl mx-auto space-y-2">
        <div class="inline-flex items-center space-x-2 px-3.5 py-1 rounded-full bg-sky-500/10 text-sky-700 dark:text-sky-300 text-xs font-extrabold border border-sky-400/30">
            <span>📋 Order Collection Assistant</span>
        </div>
        <h1 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white font-outfit">
            {{ __('messages.order_builder') }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-600 font-myanmar leading-relaxed">
            {{ __('messages.order_builder_subtitle') }}
        </p>
    </div>

    {{-- Main Order Builder Container --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- Selected Products List Column (7 cols) --}}
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200/60 dark:border-slate-800/60 pb-3">
                    <h2 class="font-black text-base text-slate-900 dark:text-white font-outfit flex items-center space-x-2">
                        <span>🛒</span>
                        <span>{{ __('messages.selected_products') }}</span>
                    </h2>
                    <span class="text-xs font-extrabold text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950 px-2.5 py-1 rounded-full border border-sky-200 dark:border-sky-800" x-text="($store.orderBuilder ? $store.orderBuilder.totalCount : 0) + ' Items'"></span>
                </div>

                {{-- Item List --}}
                <div class="space-y-3 divide-y divide-slate-100 dark:divide-slate-800/80">
                    <template x-if="!$store.orderBuilder || $store.orderBuilder.items.length === 0">
                        <div class="text-center py-12 space-y-3">
                            <div class="text-4xl">🛍️</div>
                            <h3 class="font-bold text-sm text-slate-700 dark:text-slate-300">{{ __('messages.order_list_empty') }}</h3>
                            <p class="text-xs text-slate-500 font-myanmar">{{ __('messages.order_list_empty_hint') }}</p>
                            <a href="{{ url('/products?store_slug=' . ($store?->slug ?? request('store_slug'))) }}" class="inline-block mt-2 px-5 py-2.5 rounded-xl bg-sky-600 text-white font-bold text-xs shadow-md hover:bg-sky-500 transition">
                                {{ __('messages.view_products') }} &rarr;
                            </a>
                        </div>
                    </template>

                    <template x-for="item in ($store.orderBuilder ? $store.orderBuilder.items : [])" :key="item.id">
                        <div class="pt-3 first:pt-0 flex items-center justify-between gap-3">
                            <div class="flex items-center space-x-3 flex-1 min-w-0">
                                <div class="w-14 h-14 rounded-xl bg-slate-100 dark:bg-slate-800 shrink-0 overflow-hidden border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                                    <template x-if="item.image_path">
                                        <img :src="'/storage/' + item.image_path" class="w-full h-full object-cover" />
                                    </template>
                                    <template x-if="!item.image_path">
                                        <span class="text-xs text-slate-600">No Pic</span>
                                    </template>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-extrabold text-xs sm:text-sm text-slate-900 dark:text-white truncate" x-text="item.name"></h4>
                                    <p class="text-xs font-mono text-slate-500 dark:text-slate-600" x-text="'SKU: ' + (item.sku || 'N/A')"></p>
                                    <p class="text-xs font-black text-sky-600 dark:text-sky-400 font-outfit" x-text="typeof window.formatCurrency === 'function' ? window.formatCurrency(item.price * item.quantity) : 'Ks ' + (item.price * item.quantity).toLocaleString()"></p>
                                </div>
                            </div>

                            {{-- Quantity controls & Delete --}}
                            <div class="flex items-center space-x-2 shrink-0">
                                <div class="flex items-center space-x-1 border border-slate-300 dark:border-slate-700 rounded-xl p-1 bg-white dark:bg-slate-800 shadow-sm">
                                    <button @click="$store.orderBuilder.updateQty(item.id, -1)" type="button" class="w-9 h-9 rounded-lg flex items-center justify-center font-black text-base text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition">-</button>
                                    <span class="px-2 font-black text-sm text-slate-900 dark:text-white" x-text="item.quantity"></span>
                                    <button @click="$store.orderBuilder.updateQty(item.id, 1)" type="button" class="w-9 h-9 rounded-lg flex items-center justify-center font-black text-base text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition">+</button>
                                </div>
                                <button @click="$store.orderBuilder.removeItem(item.id)" type="button" class="w-9 h-9 flex items-center justify-center rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/30 font-bold transition text-base" title="Remove">🗑️</button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Price Total Box --}}
                <div class="pt-4 border-t border-slate-200/60 dark:border-slate-800/60 flex items-center justify-between text-sm font-bold">
                    <span class="text-slate-700 dark:text-slate-300">{{ __('messages.total_amount') }}:</span>
                    <span class="text-xl font-black text-sky-600 dark:text-sky-400 font-outfit" x-text="typeof window.formatCurrency === 'function' ? window.formatCurrency($store.orderBuilder ? $store.orderBuilder.totalAmount : 0) : 'Ks ' + ($store.orderBuilder ? $store.orderBuilder.totalAmount.toLocaleString() : 0)"></span>
                </div>
            </div>
        </div>

        {{-- Customer Information & Direct Viber/Telegram Order Form (5 cols) --}}
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-4">
                <div class="border-b border-slate-200/60 dark:border-slate-800/60 pb-3">
                    <h2 class="font-black text-base text-slate-900 dark:text-white font-outfit flex items-center space-x-2">
                        <span>📝</span>
                        <span>{{ __('messages.customer_info') }}</span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-600 font-myanmar">{{ __('messages.customer_info_hint') }}</p>
                </div>

                <form method="POST" action="{{ url('/store/' . ($store?->slug ?? request('store_slug')) . '/orders') }}" class="space-y-4"
                    x-data="{
                        contactChannel: '{{ old('contact_channel', 'phone') }}',
                        contactHelp() {
                            return this.contactChannel === 'viber'
                                ? 'Optional. Admin will use this Viber phone number if it is different from the main phone.'
                                : 'Optional. Admin will use this Telegram username if you provide it.';
                        }
                    }">
                    @csrf
                    <input type="hidden" name="items_json" :value="JSON.stringify($store.orderBuilder ? $store.orderBuilder.items : [])" />

                    <div class="space-y-1">
                        <label class="block text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('messages.full_name') }} <span class="text-rose-500">*</span></label>
                        <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required placeholder="{{ __('messages.name_placeholder') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 shadow-sm" />
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('messages.phone_number') }} <span class="text-rose-500">*</span></label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', auth()->user()?->phone) }}" required placeholder="09xxxxxxxxx" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 shadow-sm" />
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('messages.address') }} <span class="text-rose-500">*</span></label>
                        <textarea name="customer_address" rows="2" required placeholder="{{ __('messages.address_placeholder') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 shadow-sm"></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('messages.note_label') }}</label>
                        <input type="text" name="customer_note" placeholder="{{ __('messages.note_placeholder') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 shadow-sm" />
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('messages.contact_channel') }}</label>
                        <input type="hidden" name="contact_channel" :value="contactChannel" />
                        <div class="flex items-center gap-2">
                            <button type="button" @click="contactChannel = 'viber'"
                                class="flex-1 flex items-center justify-center gap-1.5 py-3 rounded-xl border-2 text-sm font-bold transition-all duration-200"
                                :class="contactChannel === 'viber' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 shadow-md shadow-purple-500/20' : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:border-purple-300'">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M11.4 0C5.2 0 .1 4.8.1 10.8c0 3.2 1.6 6.1 4.1 8.1L3.2 24l5.3-2.8c1.7.5 3.5.8 5.4.8h.2c6.1-.1 11-4.9 11-11S17.5 0 11.4 0zm5.4 15.3c-.5 1.5-2.8 2.8-4.5 2.9-1.2.1-2.3.1-4.2-.8-2.8-1.2-5.3-4.2-5.5-4.4-.2-.2-1.5-2-1.5-3.9 0-1.9 1-2.8 1.3-3.2.3-.4.8-.5 1.1-.5h.8c.3 0 .6-.1.9.7.3.9.9 3 .9 3.2.1.3.1.5-.1.8-.2.2-.3.4-.5.6-.3.2-.5.5-.2.9.4.7 1.6 2.5 3.4 4 .6.5 1.2.8 1.6 1 .4.2.7.1.9-.2.3-.3.9-1.1 1.2-1.5.2-.4.5-.3.9-.2l3.4 1.6c.5.2.9.4 1 .6.1.3 0 1.6-.5 3.1z"/></svg>
                                Viber
                            </button>
                            <button type="button" @click="contactChannel = 'telegram'"
                                class="flex-1 flex items-center justify-center gap-1.5 py-3 rounded-xl border-2 text-sm font-bold transition-all duration-200"
                                :class="contactChannel === 'telegram' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 shadow-md shadow-blue-500/20' : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:border-blue-300'">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8l-1.57 7.4c-.12.53-.44.66-.88.41l-2.44-1.8-1.17 1.13c-.13.13-.24.24-.49.24l.17-2.49 4.54-4.1c.2-.18-.04-.27-.31-.11l-5.6 3.53-2.42-.75c-.53-.16-.54-.53.11-.79l9.48-3.65c.44-.16.83.1.69.79z"/></svg>
                                Telegram
                            </button>
                            <button type="button" @click="contactChannel = 'phone'"
                                class="flex-1 flex items-center justify-center gap-1.5 py-3 rounded-xl border-2 text-sm font-bold transition-all duration-200"
                                :class="contactChannel === 'phone' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 shadow-md shadow-emerald-500/20' : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:border-emerald-300'">
                                📞
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1" x-show="contactChannel === 'viber' || contactChannel === 'telegram'" x-transition>
                        <label class="block text-sm font-bold text-slate-800 dark:text-slate-200">
                            <span x-text="contactChannel === 'viber' ? 'Viber Phone Number' : 'Telegram Username'"></span>
                        </label>
                        <input
                            :type="contactChannel === 'viber' ? 'tel' : 'text'"
                            name="contact_identifier"
                            value="{{ old('contact_identifier') }}"
                            :placeholder="contactChannel === 'viber' ? '09xxxxxxxxx' : '@username'"
                            :inputmode="contactChannel === 'viber' ? 'tel' : 'text'"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 shadow-sm"
                            autocomplete="tel"
                        />
                        <p class="text-xs text-slate-500 dark:text-slate-600" x-text="contactHelp()"></p>
                    </div>

                    {{-- Submit Button --}}
                    <button
                        type="submit"
                        :disabled="!$store.orderBuilder || $store.orderBuilder.items.length === 0"
                        class="w-full min-h-[48px] py-3.5 px-4 bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 hover:from-violet-600 hover:to-rose-500 disabled:opacity-50 text-white font-black text-sm rounded-2xl shadow-xl shadow-sky-500/25 transition transform active:scale-95 flex items-center justify-center space-x-2"
                    >
                        <span x-text="contactChannel === 'viber' ? '{{ __('messages.send_order') }} (Viber)' : contactChannel === 'telegram' ? '{{ __('messages.send_order') }} (Telegram)' : '{{ __('messages.send_order') }}'"></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Delivery & Payment Info (full-width below the grid) --}}
    @php
        $obPayments = $store?->paymentMethods()->active()->get() ?? collect();
        $obDeliveries = $store?->deliveryMethods()->active()->get() ?? collect();
        $obHasPayment = $obPayments->isNotEmpty() || !empty($store?->setting?->payment_info);
        $obHasDelivery = $obDeliveries->isNotEmpty() || !empty($store?->setting?->delivery_info);
    @endphp
    @if ($obHasDelivery || $obHasPayment)
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @if ($obHasDelivery)
                    <div>
                        <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-outfit flex items-center gap-1.5">
                            <span>🚚</span>
                            <span>{{ __('messages.delivery') }}</span>
                        </h3>
                        @if ($obDeliveries->isNotEmpty())
                            <ul class="mt-1.5 space-y-1.5">
                                @foreach ($obDeliveries as $dm)
                                    <li class="rounded-lg bg-slate-50 dark:bg-slate-800/60 p-2">
                                        <p class="text-xs font-black text-slate-900 dark:text-white">{{ $dm->icon ?: '🚚' }} {{ $dm->name }}
                                            @if ($dm->estimated_time) <span class="font-bold text-sky-700 dark:text-sky-300">· {{ $dm->estimated_time }}</span> @endif
                                        </p>
                                        @if ($dm->service_area || $dm->fee_note || $dm->description)
                                            <p class="mt-0.5 text-[11px] leading-5 text-slate-600 dark:text-slate-400">{{ collect([$dm->service_area, $dm->fee_note, $dm->description])->filter()->implode(' · ') }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $store->setting->delivery_info }}</p>
                        @endif
                    </div>
                @endif
                @if ($obHasPayment)
                    <div>
                        <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-outfit flex items-center gap-1.5">
                            <span>💳</span>
                            <span>{{ __('messages.payment') }}</span>
                        </h3>
                        @if ($obPayments->isNotEmpty())
                            <ul class="mt-1.5 space-y-1.5">
                                @foreach ($obPayments as $pm)
                                    <li class="flex items-start gap-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 p-2">
                                        <x-payment-method-icon :method="$pm" class="h-8 w-8" />
                                        <div class="min-w-0">
                                            <p class="text-xs font-black text-slate-900 dark:text-white">{{ $pm->name }}</p>
                                            @if ($pm->show_account_details && ($pm->account_number || $pm->account_name))
                                                <p class="text-[11px] text-slate-600 dark:text-slate-400">
                                                    {{ $pm->account_name ? $pm->account_name . ' · ' : '' }}{{ $pm->account_number }}
                                                </p>
                                            @endif
                                            @if ($pm->instructions)
                                                <p class="mt-0.5 text-[11px] leading-5 text-slate-500 dark:text-slate-500">{{ $pm->instructions }}</p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $store->setting->payment_info }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
