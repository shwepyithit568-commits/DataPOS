@extends('layouts.storefront.app')

@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $storeSetting = $store?->setting;
    $storeLogo = $storeSetting?->adminLogo();
    $storeLogoUrl = $storeLogo ? asset('storage/' . $storeLogo) : null;
    $ordersUrl = $storeSlug ? url('/account/orders?store_slug=' . $storeSlug) : url('/account/orders');
    $orderBuilderUrl = $storeSlug ? url('/order-builder?store_slug=' . $storeSlug) : url('/order-builder');
    $accountUrl = $storeSlug ? url('/account?store_slug=' . $storeSlug) : url('/account');

    $orderEffectiveTotal = $order->agreed_amount ?? $order->total_amount;
    $activePayments = $store?->paymentMethods()->active()->get() ?? collect();

    // Reorder payload for Alpine OrderBuilder
    $reorderPayload = $order->items->map(function ($it) {
        return [
            'id' => $it->product_id,
            'product_id' => $it->product_id,
            'product_variant_id' => $it->product_variant_id,
            'product_name' => $it->product_name,
            'variant_name' => $it->variant_name,
            'variant_sku' => $it->variant_sku,
            'unit_price' => (float) $it->unit_price,
            'quantity' => (int) $it->quantity,
            'image_path' => $it->product?->image_path ?? '',
        ];
    })->values()->all();

    // Store Hotline & Contact Clean Links
    $storePhone = $storeSetting?->phone ?? $store?->phone ?? '';
    $cleanPhone = preg_replace('/[^0-9]/', '', $storePhone);
    $storeViber = $storeSetting?->viber ?? $storePhone;
    $cleanViber = preg_replace('/[^0-9]/', '', $storeViber);
    $storeTelegram = $storeSetting?->telegram ?? '';
    $cleanTelegram = ltrim($storeTelegram, '@');

    $statusLabels = [
        'pending_contact' => __('messages.order_status_pending_contact'),
        'confirmed'       => __('messages.order_status_confirmed'),
        'delivered'       => __('messages.order_status_delivered'),
        'cancelled'       => __('messages.order_status_cancelled'),
    ];
    $currentStatusLabel = $statusLabels[$order->status] ?? $order->status;
@endphp

{{-- Thermal Print Styles --}}
<style>
    @media print {
        header, footer, nav, .no-print, .sf-sticky-controls, #offline-banner, .sf-bottom-nav {
            display: none !important;
        }
        body, html {
            background: #fff !important;
            color: #000 !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .printable-thermal-slip {
            display: block !important;
            width: 100% !important;
            max-width: 80mm !important;
            margin: 0 auto !important;
            padding: 4mm !important;
            font-family: 'Courier New', Courier, monospace !important;
            color: #000 !important;
            background: #fff !important;
            box-shadow: none !important;
            border: none !important;
        }
        .print-container {
            padding: 0 !important;
            margin: 0 !important;
            max-width: none !important;
        }
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>

<div class="print-container max-w-4xl mx-auto space-y-2 sm:space-y-3 select-none font-sans pb-16"
     x-data="{
         showReceiptModal: false,
         paperSize: '80mm',
         copiedOrderNo: false,
         printReceipt() {
             this.showReceiptModal = false;
             setTimeout(() => window.print(), 200);
         }
     }">

    {{-- 1. Top Breadcrumb & Store Header Bar --}}
    <div class="no-print flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-3 px-2 sm:px-3 py-1.5 sm:py-2 border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl shadow-2xs">
        <div class="flex items-center gap-2 sm:gap-2.5 min-w-0">
            @if ($storeLogoUrl)
                <img src="{{ $storeLogoUrl }}" alt="{{ $store->name }}" class="h-8 w-8 sm:h-9 sm:w-9 rounded-md object-contain bg-white dark:bg-slate-800 p-0.5 border border-slate-200 dark:border-slate-700 shadow-2xs shrink-0" />
            @else
                <div class="sf-btn-3d active flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-md text-white font-black text-xs sm:text-sm shrink-0 pointer-events-none">
                    {{ mb_substr($store?->name ?? 'D', 0, 1) }}
                </div>
            @endif
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                    <h1 class="text-xs sm:text-sm font-black font-mono text-sky-600 dark:text-sky-400">
                        #{{ $order->order_number }}
                    </h1>
                    <button type="button"
                            @click="navigator.clipboard.writeText('{{ $order->order_number }}'); copiedOrderNo = true; setTimeout(() => copiedOrderNo = false, 2000)"
                            class="sf-btn-3d active !flex-row px-1.5 py-0.2 text-[10px] font-bold leading-none shrink-0"
                            title="{{ __('messages.action_copy') }}">
                        <span x-show="!copiedOrderNo">📋 {{ __('messages.action_copy') }}</span>
                        <span x-show="copiedOrderNo" x-cloak class="text-emerald-500 font-black">✓ {{ __('messages.copied') }}</span>
                    </button>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 font-medium truncate">
                    {{ $order->created_at->format('Y-m-d H:i') }} · {{ $store?->name ?? 'Official Store' }}
                </p>
            </div>
        </div>

        {{-- Top Action Buttons --}}
        <div class="flex items-center gap-1.5 shrink-0 self-start sm:self-auto flex-wrap">
            <a href="{{ $ordersUrl }}"
               class="sf-btn-3d !inline-flex items-center gap-1 px-2.5 py-1 sm:py-1.5 rounded-md text-xs font-bold"
               title="{{ __('messages.order_history_title') }}">
                <span aria-hidden="true">←</span>
                <span>{{ __('messages.order_history_title') }}</span>
            </a>

            <button type="button"
                    @click="showReceiptModal = true"
                    class="sf-btn-3d-primary !inline-flex items-center gap-1 px-3 py-1 sm:py-1.5 rounded-md text-xs font-black cursor-pointer shadow-2xs"
                    title="{{ __('messages.order_print_slip') }}">
                <span aria-hidden="true">🖨️</span>
                <span>{{ __('messages.order_print_slip') }}</span>
            </button>

            <button type="button"
                    @click="reorderOrder(@js($reorderPayload))"
                    class="sf-btn-3d-gold !inline-flex items-center gap-1 px-3 py-1 sm:py-1.5 rounded-md text-xs font-black cursor-pointer shadow-2xs"
                    title="{{ __('messages.order_reorder_1tap') }}">
                <span aria-hidden="true">⚡</span>
                <span class="hidden xs:inline">{{ __('messages.order_reorder_1tap') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. Live Delivery Status Tracking (4-Stage Visual Stepper) --}}
    @if ($order->status === 'cancelled')
        <div class="no-print bg-rose-50 dark:bg-rose-950/40 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-rose-200 dark:border-rose-900/60 shadow-xs flex items-center gap-2.5 sm:gap-3">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-rose-100 dark:bg-rose-900/70 text-rose-600 dark:text-rose-300 flex items-center justify-center text-base sm:text-lg shrink-0">
                ✕
            </div>
            <div class="min-w-0 space-y-0.5">
                <h3 class="font-black text-xs sm:text-sm text-rose-700 dark:text-rose-300">
                    {{ __('messages.order_status_cancelled_notice') }}
                </h3>
                <p class="text-[11px] sm:text-xs text-rose-600/80 dark:text-rose-400/80 font-medium">
                    {{ $order->customer_note ?: __('messages.order_status_cancelled') }}
                </p>
            </div>
        </div>
    @else
        <div class="no-print bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-2">
                <h2 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span>🚚</span>
                    <span>{{ __('messages.order_live_tracking') }}</span>
                </h2>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] sm:text-xs font-black
                    {{ $order->status === 'delivered' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-300/60' : '' }}
                    {{ $order->status === 'confirmed' ? 'bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-300/60' : '' }}
                    {{ $order->status === 'pending_contact' ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-300/60' : '' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $order->status === 'pending_contact' ? 'bg-amber-500 animate-pulse' : ($order->status === 'confirmed' ? 'bg-sky-500' : 'bg-emerald-500') }}"></span>
                    <span>{{ $currentStatusLabel }}</span>
                </span>
            </div>

            {{-- 4-Stage Stepper Grid --}}
            <div class="grid grid-cols-4 gap-1 sm:gap-2 text-center pt-1 relative">
                {{-- Step 1: Order Placed --}}
                <div class="space-y-1">
                    <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs font-black mx-auto shadow-2xs">
                        ✓
                    </div>
                    <p class="text-[10px] sm:text-xs font-black text-emerald-600 dark:text-emerald-400 leading-tight">
                        {{ __('messages.order_status_stepper_placed') }}
                    </p>
                    <p class="text-[9px] text-slate-400 dark:text-slate-500 font-mono hidden sm:block">
                        {{ $order->created_at->format('h:i A') }}
                    </p>
                </div>

                {{-- Step 2: Under Review / Pending Contact --}}
                @php
                    $isStep2Done = in_array($order->status, ['confirmed', 'delivered'], true);
                    $isStep2Active = $order->status === 'pending_contact';
                @endphp
                <div class="space-y-1 {{ !$isStep2Done && !$isStep2Active ? 'opacity-40' : '' }}">
                    <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center text-xs font-black mx-auto shadow-2xs
                        {{ $isStep2Done ? 'bg-emerald-500 text-white' : ($isStep2Active ? 'bg-amber-500 text-white animate-pulse' : 'bg-slate-200 dark:bg-slate-800 text-slate-500') }}">
                        {{ $isStep2Done ? '✓' : '⏱️' }}
                    </div>
                    <p class="text-[10px] sm:text-xs font-bold {{ $isStep2Active ? 'text-amber-600 dark:text-amber-400 font-black' : ($isStep2Done ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400') }} leading-tight">
                        {{ __('messages.order_status_stepper_confirm') }}
                    </p>
                </div>

                {{-- Step 3: Payment & Packing / Confirmed --}}
                @php
                    $isStep3Done = $order->status === 'delivered';
                    $isStep3Active = $order->status === 'confirmed';
                @endphp
                <div class="space-y-1 {{ !$isStep3Done && !$isStep3Active ? 'opacity-40' : '' }}">
                    <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center text-xs font-black mx-auto shadow-2xs
                        {{ $isStep3Done ? 'bg-emerald-500 text-white' : ($isStep3Active ? 'bg-sky-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500') }}">
                        {{ $isStep3Done ? '✓' : '💳' }}
                    </div>
                    <p class="text-[10px] sm:text-xs font-bold {{ $isStep3Active ? 'text-sky-600 dark:text-sky-400 font-black' : ($isStep3Done ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400') }} leading-tight">
                        {{ __('messages.order_status_stepper_payment') }}
                    </p>
                </div>

                {{-- Step 4: Delivery / Completed --}}
                @php
                    $isStep4Done = $order->status === 'delivered';
                @endphp
                <div class="space-y-1 {{ !$isStep4Done ? 'opacity-40' : '' }}">
                    <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center text-xs font-black mx-auto shadow-2xs
                        {{ $isStep4Done ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500' }}">
                        🚚
                    </div>
                    <p class="text-[10px] sm:text-xs font-bold {{ $isStep4Done ? 'text-emerald-600 dark:text-emerald-400 font-black' : 'text-slate-500 dark:text-slate-400' }} leading-tight">
                        {{ __('messages.order_status_stepper_delivery') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- 3. Customer & Delivery Info Grid --}}
    <div class="no-print grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-2.5">
        {{-- Customer Info Card --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3 sm:p-3.5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-1.5">
            <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-1.5 border-b border-slate-100 dark:border-slate-800/80 pb-1.5">
                <span>👤</span>
                <span>{{ __('messages.order_customer_info') }}</span>
            </h3>
            <div class="space-y-1 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 dark:text-slate-400 font-medium">{{ __('messages.order_customer_name') }}:</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ $order->customer_name }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 dark:text-slate-400 font-medium">{{ __('messages.order_customer_phone') }}:</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $order->customer_phone }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 dark:text-slate-400 font-medium">{{ __('messages.order_contact_channel') }}:</span>
                    <span class="uppercase font-mono font-bold px-1.5 py-0.2 rounded text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                        {{ $order->contact_channel }}
                    </span>
                </div>
                @if ($order->contact_identifier)
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">{{ __('messages.order_contact_identifier') }}:</span>
                        <span class="font-mono text-slate-700 dark:text-slate-300">{{ $order->contact_identifier }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Delivery Address & Notes Card --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3 sm:p-3.5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-1.5">
            <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-1.5 border-b border-slate-100 dark:border-slate-800/80 pb-1.5">
                <span>📍</span>
                <span>{{ __('messages.order_address_label') }}</span>
            </h3>
            <div class="space-y-1 text-xs">
                <p class="text-slate-700 dark:text-slate-300 font-medium leading-relaxed">
                    {{ $order->customer_address ?: __('messages.order_no_address') }}
                </p>
                @if ($order->customer_note)
                    <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800/80">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">{{ __('messages.order_customer_note') }}:</span>
                        <p class="text-xs text-slate-600 dark:text-slate-400 italic">"{{ $order->customer_note }}"</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- 4. Order Items Table & Financial Breakdown --}}
    <div class="no-print bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-2">
            <h2 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                <span>📦</span>
                <span>{{ __('messages.order_items_heading') }}</span>
            </h2>
            <span class="text-[11px] font-black text-slate-500 dark:text-slate-400">
                {{ $order->items->count() }} {{ __('messages.order_items_unit') }}
            </span>
        </div>

        {{-- Items List --}}
        <div class="divide-y divide-slate-100 dark:divide-slate-800/80">
            @foreach ($order->items as $item)
                @php
                    $thumbImg = $item->product?->image_path;
                @endphp
                <div class="py-2 first:pt-0 last:pb-0 flex items-center justify-between gap-2.5 sm:gap-3">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        {{-- Thumbnail --}}
                        <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-md bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 shadow-2xs overflow-hidden flex items-center justify-center shrink-0">
                            @if ($thumbImg)
                                <img src="{{ asset('storage/' . $thumbImg) }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover pointer-events-none" loading="lazy" />
                            @else
                                <span class="text-xs text-slate-400 font-bold">📦</span>
                            @endif
                        </div>

                        {{-- Item Info --}}
                        <div class="min-w-0 space-y-0.5">
                            <h4 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white truncate">
                                {{ $item->product_name }}
                            </h4>
                            <div class="flex items-center gap-1.5 text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 flex-wrap">
                                @if ($item->variant_name)
                                    <span class="px-1 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium">
                                        {{ $item->variant_name }}
                                    </span>
                                @endif
                                @if ($item->variant_sku)
                                    <span class="font-mono">SKU: {{ $item->variant_sku }}</span>
                                @endif
                                <span>·</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ format_currency($item->unit_price, $store) }} × {{ format_quantity($item->quantity, $store) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Subtotal --}}
                    <div class="text-right shrink-0">
                        <span class="text-xs sm:text-sm font-black font-mono text-slate-900 dark:text-white">
                            {{ format_currency($item->subtotal, $store) }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Financial Calculation Box --}}
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 space-y-1.5 text-xs">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                <span>{{ __('messages.order_original_total') }}:</span>
                <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ format_currency($order->total_amount, $store) }}</span>
            </div>

            @if ($order->agreed_amount !== null && (float) $order->agreed_amount !== (float) $order->total_amount)
                <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400 font-bold">
                    <span>{{ __('messages.order_agreed_total') }}:</span>
                    <span class="font-mono font-black">{{ format_currency($order->agreed_amount, $store) }}</span>
                </div>
            @endif

            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <span>{{ __('messages.order_payment_label') }}:</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-black {{ $order->payment_status === 'paid' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                    {{ $order->payment_status === 'paid' ? __('messages.order_status_paid') : __('messages.order_status_unpaid') }}
                </span>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-dashed border-slate-200 dark:border-slate-700">
                <span class="font-black text-sm text-slate-900 dark:text-white">{{ __('messages.total_amount') }}:</span>
                <span class="font-mono font-black text-base sm:text-lg text-sky-600 dark:text-sky-400">
                    {{ format_currency($orderEffectiveTotal, $store) }}
                </span>
            </div>
        </div>
    </div>

    {{-- 5. Direct Store Contact Hotline (Brand Colors 3D Buttons) --}}
    <div class="no-print bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3 sm:p-3.5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2">
        <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
            <span>📞</span>
            <span>{{ __('messages.order_direct_hotline') }}</span>
        </h3>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
            အော်ဒါနှင့်ပတ်သက်၍ မေးမြန်းစုံစမ်းလိုပါက အောက်ပါ လိုင်းများမှတစ်ဆင့် ဆိုင်သို့ တိုက်ရိုက် ဆက်သွယ်နိုင်ပါသည်:
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5 sm:gap-2 pt-0.5">
            {{-- Viber CTA --}}
            @if ($cleanViber)
                <a href="viber://chat?number={{ $cleanViber }}"
                   class="sf-btn-3d-viber !flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-md text-xs font-black select-none">
                    <span>💬</span>
                    <span>{{ __('messages.order_chat_viber') }}</span>
                </a>
            @endif

            {{-- Telegram CTA --}}
            @if ($cleanTelegram)
                <a href="https://t.me/{{ $cleanTelegram }}" target="_blank" rel="noopener noreferrer"
                   class="sf-btn-3d-telegram !flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-md text-xs font-black select-none">
                    <span>✈️</span>
                    <span>{{ __('messages.order_chat_telegram') }}</span>
                </a>
            @endif

            {{-- Direct Phone Call CTA --}}
            @if ($cleanPhone)
                <a href="tel:{{ $cleanPhone }}"
                   class="sf-btn-3d-success !flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-md text-xs font-black select-none">
                    <span>📞</span>
                    <span>{{ __('messages.order_call_now') }}</span>
                </a>
            @endif
        </div>
    </div>

    {{-- 6. Payment Accounts & Transfer Guidance (If Unpaid / Pending) --}}
    @if ($order->payment_status !== 'paid' && $activePayments->isNotEmpty())
        <div class="no-print bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3 sm:p-3.5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2">
            <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                <span>💳</span>
                <span>{{ __('messages.order_payment_transfer_title') }}</span>
            </h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                {{ __('messages.order_payment_transfer_desc') }}:
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                @foreach ($activePayments as $pm)
                    <div class="p-2.5 rounded-md bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 space-y-1">
                        <div class="flex items-center gap-2">
                            <x-payment-method-icon :method="$pm" class="h-6 w-6 shrink-0" />
                            <div class="min-w-0">
                                <p class="text-xs font-black text-slate-900 dark:text-white truncate">{{ $pm->name }}</p>
                            </div>
                        </div>
                        @if ($pm->account_name || $pm->account_number)
                            <div class="text-[11px] font-mono text-slate-600 dark:text-slate-300 space-y-0.2 pt-0.5">
                                @if ($pm->account_name)
                                    <p class="truncate">{{ $pm->account_name }}</p>
                                @endif
                                @if ($pm->account_number)
                                    <p class="font-bold text-sky-600 dark:text-sky-400">{{ $pm->account_number }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- 7. THERMAL RECEIPT MODAL PREVIEW & DIRECT ESC/POS PRINT CONTAINER         --}}
    {{-- ========================================================================= --}}
    <div x-show="showReceiptModal"
         x-cloak
         class="no-print fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-950/70 backdrop-blur-xs"
         @keydown.escape.window="showReceiptModal = false">
        
        <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
             @click.outside="showReceiptModal = false">
            
            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-3 py-2 sm:px-4 sm:py-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/70">
                <div class="flex items-center gap-2">
                    <span class="text-base sm:text-lg">🖨️</span>
                    <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white">
                        {{ __('messages.order_print_slip') }}
                    </h3>
                </div>

                {{-- Paper Size Switcher --}}
                <div class="flex items-center gap-1 p-0.5 rounded-md bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-[10px]">
                    <button type="button"
                            @click="paperSize = '80mm'"
                            :class="paperSize === '80mm' ? 'bg-slate-900 dark:bg-slate-900 text-white font-black' : 'text-slate-600 dark:text-slate-300 font-bold'"
                            class="px-2 py-0.5 rounded cursor-pointer">
                        80mm
                    </button>
                    <button type="button"
                            @click="paperSize = '58mm'"
                            :class="paperSize === '58mm' ? 'bg-slate-900 dark:bg-slate-900 text-white font-black' : 'text-slate-600 dark:text-slate-300 font-bold'"
                            class="px-2 py-0.5 rounded cursor-pointer">
                        58mm
                    </button>
                </div>
            </div>

            {{-- Modal Body: Thermal Paper Simulation --}}
            <div class="p-3 sm:p-4 overflow-y-auto bg-slate-100 dark:bg-slate-950/80 flex justify-center">
                <div :class="paperSize === '58mm' ? 'max-w-[240px]' : 'max-w-[310px]'"
                     class="w-full bg-white text-slate-900 font-mono text-[11px] p-3 sm:p-4 rounded-md shadow-md border border-slate-300 space-y-2">
                    
                    {{-- Slip Header --}}
                    <div class="text-center space-y-0.5 border-b border-dashed border-slate-400 pb-2">
                        <h4 class="font-black text-xs sm:text-sm uppercase tracking-wide">{{ $store?->name ?? 'DataPOS' }}</h4>
                        @if ($storeSetting?->address)
                            <p class="text-[9.5px] leading-tight text-slate-600">{{ $storeSetting->address }}</p>
                        @endif
                        @if ($storePhone)
                            <p class="text-[10px] text-slate-700 font-bold">Tel: {{ $storePhone }}</p>
                        @endif
                        <p class="text-[11px] font-black tracking-widest pt-1">{{ __('messages.order_thermal_title') }}</p>
                    </div>

                    {{-- Meta Info --}}
                    <div class="space-y-0.5 text-[10px] border-b border-dashed border-slate-400 pb-1.5">
                        <div class="flex justify-between">
                            <span>Order:</span>
                            <span class="font-black">#{{ $order->order_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Date:</span>
                            <span>{{ $order->created_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Customer:</span>
                            <span class="font-bold truncate max-w-[150px]">{{ $order->customer_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Phone:</span>
                            <span>{{ $order->customer_phone }}</span>
                        </div>
                    </div>

                    {{-- Items List --}}
                    <div class="space-y-1 text-[10px] border-b border-dashed border-slate-400 pb-2">
                        @foreach ($order->items as $it)
                            <div>
                                <p class="font-bold truncate">{{ $it->product_name }}</p>
                                <div class="flex justify-between text-slate-600 text-[9.5px]">
                                    <span>{{ format_quantity($it->quantity, $store) }} × {{ format_currency($it->unit_price, $store) }}</span>
                                    <span class="font-bold text-slate-900">{{ format_currency($it->subtotal, $store) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Totals --}}
                    <div class="space-y-0.5 text-[10px] border-b border-dashed border-slate-400 pb-1.5">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span>{{ format_currency($order->total_amount, $store) }}</span>
                        </div>
                        @if ($order->agreed_amount !== null && (float) $order->agreed_amount !== (float) $order->total_amount)
                            <div class="flex justify-between font-bold">
                                <span>Agreed Total:</span>
                                <span>{{ format_currency($order->agreed_amount, $store) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-black text-xs pt-1 border-t border-slate-300">
                            <span>TOTAL:</span>
                            <span>{{ format_currency($orderEffectiveTotal, $store) }}</span>
                        </div>
                        <div class="flex justify-between pt-0.5 text-[9.5px]">
                            <span>Status:</span>
                            <span class="font-bold uppercase">{{ $order->status }} ({{ $order->payment_status }})</span>
                        </div>
                    </div>

                    {{-- Slip Footer --}}
                    <div class="text-center pt-1 space-y-0.5 text-[9px] text-slate-600">
                        <p class="font-bold">{{ __('messages.order_thank_you_note') }}</p>
                        <p class="text-[8px] text-slate-400">Powered by DataPOS</p>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-end gap-2 px-3 py-2 sm:px-4 sm:py-2.5 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/70">
                <button type="button"
                        @click="showReceiptModal = false"
                        class="sf-btn-3d px-3 py-1.5 rounded-md text-xs font-bold">
                    {{ __('messages.order_close_modal') }}
                </button>
                <button type="button"
                        @click="printReceipt()"
                        class="sf-btn-3d-primary px-4 py-1.5 rounded-md text-xs font-black shadow-xs flex items-center gap-1 cursor-pointer">
                    <span>🖨️</span>
                    <span>{{ __('messages.order_print_now') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 8. PURE PRINTABLE THERMAL SLIP CONTAINER (VISIBLE ONLY IN @media print)    --}}
    {{-- ========================================================================= --}}
    <div class="printable-thermal-slip hidden">
        <div style="text-align: center; margin-bottom: 8px;">
            <div style="font-size: 14px; font-weight: 900; text-transform: uppercase;">{{ $store?->name ?? 'DataPOS' }}</div>
            @if ($storeSetting?->address)
                <div style="font-size: 10px; color: #333; margin-top: 2px;">{{ $storeSetting->address }}</div>
            @endif
            @if ($storePhone)
                <div style="font-size: 10px; font-weight: bold; margin-top: 2px;">Tel: {{ $storePhone }}</div>
            @endif
            <div style="font-size: 12px; font-weight: bold; margin-top: 4px; letter-spacing: 2px;">{{ __('messages.order_thermal_title') }}</div>
        </div>

        <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 4px 0; margin-bottom: 6px; font-size: 10px;">
            <div style="display: flex; justify-content: space-between;"><span>Order:</span><span style="font-weight: bold;">#{{ $order->order_number }}</span></div>
            <div style="display: flex; justify-content: space-between;"><span>Date:</span><span>{{ $order->created_at->format('Y-m-d H:i') }}</span></div>
            <div style="display: flex; justify-content: space-between;"><span>Customer:</span><span style="font-weight: bold;">{{ $order->customer_name }}</span></div>
            <div style="display: flex; justify-content: space-between;"><span>Phone:</span><span>{{ $order->customer_phone }}</span></div>
            @if ($order->customer_address)
                <div style="display: flex; justify-content: space-between;"><span>Address:</span><span style="font-size: 9px; max-width: 60%; text-align: right;">{{ $order->customer_address }}</span></div>
            @endif
        </div>

        <div style="border-bottom: 1px dashed #000; padding-bottom: 6px; margin-bottom: 6px; font-size: 10px;">
            @foreach ($order->items as $it)
                <div style="margin-bottom: 4px;">
                    <div style="font-weight: bold;">{{ $it->product_name }}</div>
                    <div style="display: flex; justify-content: space-between; font-size: 9px; color: #222;">
                        <span>{{ format_quantity($it->quantity, $store) }} × {{ format_currency($it->unit_price, $store) }}</span>
                        <span style="font-weight: bold;">{{ format_currency($it->subtotal, $store) }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="border-bottom: 1px dashed #000; padding-bottom: 6px; margin-bottom: 6px; font-size: 10px;">
            <div style="display: flex; justify-content: space-between;"><span>Subtotal:</span><span>{{ format_currency($order->total_amount, $store) }}</span></div>
            @if ($order->agreed_amount !== null && (float) $order->agreed_amount !== (float) $order->total_amount)
                <div style="display: flex; justify-content: space-between; font-weight: bold;"><span>Agreed Price:</span><span>{{ format_currency($order->agreed_amount, $store) }}</span></div>
            @endif
            <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 900; margin-top: 4px; border-top: 1px solid #000; padding-top: 2px;">
                <span>TOTAL:</span>
                <span>{{ format_currency($orderEffectiveTotal, $store) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 9px; margin-top: 2px;">
                <span>Payment:</span>
                <span style="font-weight: bold; text-transform: uppercase;">{{ $order->payment_status }}</span>
            </div>
        </div>

        <div style="text-align: center; font-size: 9px; margin-top: 8px;">
            <p style="font-weight: bold;">{{ __('messages.order_thank_you_note') }}</p>
            <p style="font-size: 8px; color: #555; margin-top: 2px;">DataPOS Cloud ERP</p>
        </div>
    </div>
</div>

{{-- Reorder Script --}}
<script>
    function reorderOrder(items) {
        if (!items || !items.length) {
            window.location.href = @js($orderBuilderUrl);
            return;
        }

        const ob = window.Alpine?.store('orderBuilder');
        if (!ob) {
            window.location.href = @js($orderBuilderUrl);
            return;
        }

        items.forEach(function(item) {
            const qty = parseInt(item.quantity) || 1;
            for (let i = 0; i < qty; i++) {
                ob.addItem({
                    id: item.product_id || ('custom_' + item.id),
                    product_id: item.product_id,
                    product_variant_id: item.product_variant_id,
                    variant_id: item.product_variant_id,
                    name: item.product_name + (item.variant_name ? ' (' + item.variant_name + ')' : ''),
                    price: parseFloat(item.unit_price || 0),
                    sku: item.variant_sku || '',
                    image_path: item.image_path || ''
                });
            }
        });

        if (typeof window.showToast === 'function') {
            window.showToast(@js(__('messages.order_reorder_success_toast')), 'success');
        }

        setTimeout(function() {
            window.location.href = @js($orderBuilderUrl);
        }, 300);
    }
</script>
@endsection
