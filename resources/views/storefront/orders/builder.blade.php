@extends('layouts.storefront.app')

@section('content')
@php
    $hideFloatingFabs = true;
    $storeSetting = $store?->setting;
    $storeLogo = $storeSetting?->storefrontLogo() ?? $storeSetting?->adminLogo();
    $storeLogoUrl = $storeLogo ? asset('storage/' . $storeLogo) : null;
@endphp

<div class="max-w-5xl mx-auto space-y-5 sm:space-y-6 pb-12">
    {{-- Top Breadcrumb & Store Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200/80 dark:border-slate-800">
        <div class="flex items-center gap-2.5">
            @if ($storeLogoUrl)
                <img src="{{ $storeLogoUrl }}" alt="{{ $store->name }}" class="h-9 w-9 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shadow-xs" />
            @else
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-red-600 text-white font-black text-sm shadow-xs">
                    {{ mb_substr($store?->name ?? 'D', 0, 1) }}
                </div>
            @endif
            <div>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white flex items-center gap-2 font-sans">
                    <span>{{ __('messages.order_builder') }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-black bg-orange-100 dark:bg-orange-950/60 text-[#f85606] dark:text-orange-400 border border-orange-200/60 dark:border-orange-800/60">
                        ⚡ Fast Checkout
                    </span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    {{ $store?->name ?? 'Official Store' }} · {{ __('messages.order_builder_subtitle') }}
                </p>
            </div>
        </div>

        <a href="{{ url('/products?store_slug=' . ($store?->slug ?? request('store_slug'))) }}"
           class="inline-flex items-center gap-1.5 self-start sm:self-auto text-xs font-bold text-slate-600 hover:text-[#f85606] dark:text-slate-300 dark:hover:text-orange-400 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-1.5 shadow-2xs transition">
            <span>←</span>
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
                           style="background: linear-gradient(135deg, #f85606 0%, #ea580c 100%) !important; color: #ffffff !important;"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-white font-black text-xs shadow-md shadow-orange-500/20 hover:brightness-110 transition border-0">
                            <span>{{ __('messages.view_products') }}</span>
                            <span>→</span>
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
                                <button @click="$store.orderBuilder.removeItem(item.id)" type="button" class="w-8 h-8 flex items-center justify-center rounded-xl text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 font-bold transition text-sm cursor-pointer" title="Remove">🗑️</button>
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

            {{-- Trust Badges Row --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
                <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
                    <span class="text-base">🛡️</span>
                    <p class="font-bold text-slate-700 dark:text-slate-300 text-[11px]">100% Authentic</p>
                </div>
                <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
                    <span class="text-base">💵</span>
                    <p class="font-bold text-slate-700 dark:text-slate-300 text-[11px]">Cash On Delivery</p>
                </div>
                <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
                    <span class="text-base">🚚</span>
                    <p class="font-bold text-slate-700 dark:text-slate-300 text-[11px]">Fast Delivery</p>
                </div>
                <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
                    <span class="text-base">⭐</span>
                    <p class="font-bold text-slate-700 dark:text-slate-300 text-[11px]">Service Guaranteed</p>
                </div>
            </div>
        </div>

        {{-- Customer Information & Order Form Column (5 cols) --}}
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4">
                <div class="border-b border-slate-100 dark:border-slate-800/80 pb-3">
                    <h2 class="font-black text-sm sm:text-base text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-sky-100 dark:bg-sky-950/60 text-sky-600 text-xs">📝</span>
                        <span>{{ __('messages.customer_info') }}</span>
                    </h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ __('messages.customer_info_hint') }}</p>
                </div>

                <form method="POST" action="{{ url('/store/' . ($store?->slug ?? request('store_slug')) . '/orders') }}" class="space-y-3.5"
                    x-data="{
                        contactChannel: '{{ old('contact_channel', 'phone') }}',
                        contactHelp() {
                            return this.contactChannel === 'viber'
                                ? 'Viber ဖြင့် ချိတ်ဆက်လိုပါက ဖုန်းနံပါတ် ဖြည့်ပေးပါရန်'
                                : 'Telegram ဖြင့် ချိတ်ဆက်လိုပါက Telegram @username ဖြည့်ပေးပါရန်';
                        }
                    }">
                    @csrf
                    <input type="hidden" name="items_json" :value="JSON.stringify($store.orderBuilder ? $store.orderBuilder.items : [])" />

                    {{-- Customer Name --}}
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.full_name') }} <span class="text-rose-500">*</span></label>
                        <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required placeholder="{{ __('messages.name_placeholder') }}"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-2.5 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-orange-500 focus:bg-white dark:focus:bg-slate-800 focus:outline-none shadow-2xs" />
                    </div>

                    {{-- Phone Number --}}
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.phone_number') }} <span class="text-rose-500">*</span></label>
                        <input type="tel" inputmode="tel" name="customer_phone" value="{{ old('customer_phone', auth()->user()?->phone) }}" required placeholder="09xxxxxxxxx"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-2.5 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-orange-500 focus:bg-white dark:focus:bg-slate-800 focus:outline-none shadow-2xs" />
                    </div>

                    {{-- Delivery Address --}}
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.address') }} <span class="text-rose-500">*</span></label>
                        <textarea name="customer_address" rows="2" required placeholder="{{ __('messages.address_placeholder') }}"
                                  class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-2.5 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-orange-500 focus:bg-white dark:focus:bg-slate-800 focus:outline-none shadow-2xs"></textarea>
                    </div>

                    {{-- Customer Note --}}
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.note_label') }}</label>
                        <input type="text" name="customer_note" placeholder="{{ __('messages.note_placeholder') }}"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-2 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:ring-2 focus:ring-orange-500 focus:bg-white dark:focus:bg-slate-800 focus:outline-none shadow-2xs" />
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
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-2 bg-slate-50/50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-orange-500 focus:bg-white dark:focus:bg-slate-800 focus:outline-none shadow-2xs"
                            autocomplete="tel"
                        />
                        <p class="text-[11px] text-slate-500 dark:text-slate-400" x-text="contactHelp()"></p>
                    </div>

                    {{-- 4 Action Buttons in 2-Column Grid (Viber, Telegram, Phone, Send Order) --}}
                    <div class="space-y-1.5 pt-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.contact_channel') }} & အော်ဒါပေးပို့ရန်</label>
                        <input type="hidden" name="contact_channel" :value="contactChannel" />
                        <div class="grid grid-cols-2 gap-2.5">
                            {{-- 1. Viber Channel Button --}}
                            <button type="button" @click="contactChannel = 'viber'"
                                style="background: linear-gradient(135deg, #7360F2 0%, #5f4de0 100%) !important; color: #ffffff !important;"
                                class="flex items-center justify-center gap-2 py-3 px-3 rounded-xl text-xs font-black transition transform active:scale-95 cursor-pointer select-none shadow-md shadow-purple-500/25 border-0"
                                :class="contactChannel === 'viber' ? 'ring-2 ring-purple-400 ring-offset-2 scale-[1.02] brightness-110' : 'opacity-85 hover:opacity-100'">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-white/20 shrink-0">
                                    <x-brand-icon brand="viber" class="h-3.5 w-3.5 shrink-0 fill-white text-white"/>
                                </span>
                                <span class="!text-white font-black">Viber</span>
                            </button>

                            {{-- 2. Telegram Channel Button --}}
                            <button type="button" @click="contactChannel = 'telegram'"
                                style="background: linear-gradient(135deg, #229ED9 0%, #0284c7 100%) !important; color: #ffffff !important;"
                                class="flex items-center justify-center gap-2 py-3 px-3 rounded-xl text-xs font-black transition transform active:scale-95 cursor-pointer select-none shadow-md shadow-sky-500/25 border-0"
                                :class="contactChannel === 'telegram' ? 'ring-2 ring-sky-400 ring-offset-2 scale-[1.02] brightness-110' : 'opacity-85 hover:opacity-100'">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-white/20 shrink-0">
                                    <x-brand-icon brand="telegram" class="h-3.5 w-3.5 shrink-0 fill-white text-white"/>
                                </span>
                                <span class="!text-white font-black">Telegram</span>
                            </button>

                            {{-- 3. Phone Channel Button --}}
                            <button type="button" @click="contactChannel = 'phone'"
                                style="background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; color: #ffffff !important;"
                                class="flex items-center justify-center gap-2 py-3 px-3 rounded-xl text-xs font-black transition transform active:scale-95 cursor-pointer select-none shadow-md shadow-emerald-500/25 border-0"
                                :class="contactChannel === 'phone' ? 'ring-2 ring-emerald-400 ring-offset-2 scale-[1.02] brightness-110' : 'opacity-85 hover:opacity-100'">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-white/20 shrink-0 text-xs">📞</span>
                                <span class="!text-white font-black">Phone</span>
                            </button>

                            {{-- 4. Submit Button --}}
                            <button
                                type="submit"
                                :disabled="!$store.orderBuilder || $store.orderBuilder.items.length === 0"
                                style="background: linear-gradient(135deg, #f85606 0%, #ea580c 100%) !important; color: #ffffff !important;"
                                class="flex items-center justify-center gap-1.5 py-3 px-3 rounded-xl text-xs font-black text-white shadow-md shadow-orange-500/25 hover:brightness-110 disabled:opacity-50 transition transform active:scale-95 cursor-pointer border-0 select-none"
                            >
                                <span class="text-sm">⚡</span>
                                <span class="font-black text-white whitespace-nowrap">{{ __('messages.send_order') }}</span>
                                <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
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
