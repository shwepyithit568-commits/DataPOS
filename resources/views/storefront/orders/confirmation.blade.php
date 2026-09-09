@extends('layouts.storefront.app', ['title' => __('messages.order_success')])

@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')

@section('content')
<style>
    @media print {
        header, footer, .no-print, nav, .sf-sticky-controls, #offline-banner {
            display: none !important;
        }
        body {
            background: #fff !important;
            color: #000 !important;
            padding: 0 !important;
        }
        .print-container {
            max-width: 100% !important;
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
        }
        .print-card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
            background: #fff !important;
        }
    }
</style>

@php
    $activeStoreSlug = request('store_slug') ?? $store?->slug;
    $obPayments = $store?->paymentMethods()->active()->get() ?? collect();
    $obDeliveries = $store?->deliveryMethods()->active()->get() ?? collect();
    $obHasPayment = $obPayments->isNotEmpty() || !empty($store?->setting?->payment_info);
    $statusKey = 'order_status_' . $order->status;
    $statusText = __("messages.{$statusKey}");
    if ($statusText === "messages.{$statusKey}") {
        $statusText = strtoupper($order->status);
    }
@endphp

<div class="print-container w-full max-w-3xl mx-auto space-y-1.5 sm:space-y-2 pb-16 sm:pb-12 select-none font-sans">

    {{-- 1. Success Hero Banner --}}
    <div class="print-card bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3.5 sm:p-5 text-center space-y-2.5 border {{ $order->status === 'cancelled' ? 'border-rose-500/30 bg-gradient-to-b from-rose-500/5 via-transparent to-transparent' : 'border-emerald-500/30 bg-gradient-to-b from-emerald-500/5 via-transparent to-transparent' }} shadow-xs relative overflow-hidden">
        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full {{ $order->status === 'cancelled' ? 'bg-rose-500 ring-rose-500/20' : 'bg-emerald-500 ring-emerald-500/20' }} text-white flex items-center justify-center text-2xl sm:text-3xl mx-auto shadow-md ring-4 font-black">
            {{ $order->status === 'cancelled' ? '✕' : '✓' }}
        </div>
        
        <div class="space-y-0.5">
            <h1 class="text-lg sm:text-2xl font-black {{ $order->status === 'cancelled' ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} font-outfit tracking-tight">
                {{ $order->status === 'cancelled' ? __('messages.order_status_cancelled') : __('messages.order_success') }}
            </h1>
            <p class="text-xs sm:text-sm font-bold text-slate-500 dark:text-slate-400 font-myanmar">
                {{ $store?->name ?? config('app.name') }}
            </p>
        </div>

        {{-- Order Code Pill with 1-Tap Copy --}}
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-slate-100 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 shadow-2xs"
             x-data="{ copied: false }">
            <div class="flex items-center gap-1.5">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.order_number') }}:</span>
                <span class="font-mono font-black text-xs sm:text-sm text-slate-900 dark:text-white">#{{ $order->order_number }}</span>
            </div>
            <button type="button"
                    @click="navigator.clipboard.writeText('{{ $order->order_number }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="sf-btn-3d active !flex-row px-2 py-0.5 text-[11px] font-bold leading-none shrink-0"
                    title="{{ __('messages.action_copy') }}">
                <span x-show="!copied">📋 {{ __('messages.action_copy') }}</span>
                <span x-show="copied" x-cloak class="text-emerald-600 dark:text-emerald-400 font-black">✓ {{ __('messages.copied') }}</span>
            </button>
        </div>

        @if ($storeSetting?->getPosSetting('show_tax_id') && $storeSetting?->getPosSetting('tax_id_number'))
            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-slate-50 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 text-xs font-mono font-bold text-slate-700 dark:text-slate-300 shadow-2xs">
                <span class="text-slate-400 dark:text-slate-500">TIN:</span>
                <span>{{ $storeSetting->getPosSetting('tax_id_number') }}</span>
            </div>
        @endif
    </div>

    {{-- 2. 4-Stage Visual Status Stepper / Live Tracking --}}
    @if ($order->status === 'cancelled')
        <div class="print-card bg-rose-50 dark:bg-rose-950/40 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-rose-200 dark:border-rose-900/60 shadow-xs flex items-center gap-2.5 sm:gap-3">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-rose-100 dark:bg-rose-900/70 text-rose-600 dark:text-rose-300 flex items-center justify-center text-base sm:text-lg shrink-0 font-black">
                ✕
            </div>
            <div class="min-w-0 space-y-0.5">
                <h3 class="font-black text-xs sm:text-sm text-rose-700 dark:text-rose-300 font-myanmar">
                    {{ __('messages.order_status_cancelled_notice') }}
                </h3>
                <p class="text-[11px] sm:text-xs text-rose-600/80 dark:text-rose-400/80 font-medium">
                    {{ $order->customer_note ?: __('messages.order_status_cancelled') }}
                </p>
            </div>
        </div>
    @else
        <div class="print-card bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-2">
                <h2 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-1.5 font-myanmar">
                    <span>🚚</span>
                    <span>{{ __('messages.order_live_tracking') }}</span>
                </h2>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] sm:text-xs font-black
                    {{ $order->status === 'delivered' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-300/60' : '' }}
                    {{ $order->status === 'confirmed' ? 'bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-300/60' : '' }}
                    {{ $order->status === 'pending_contact' ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-300/60' : '' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $order->status === 'pending_contact' ? 'bg-amber-500 animate-pulse' : ($order->status === 'confirmed' ? 'bg-sky-500' : 'bg-emerald-500') }}"></span>
                    <span>{{ $statusText }}</span>
                </span>
            </div>

            {{-- 4-Stage Stepper Grid --}}
            <div class="grid grid-cols-4 gap-1 sm:gap-2 text-center pt-1 relative">
                {{-- Step 1: Order Placed --}}
                <div class="space-y-1">
                    <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs font-black mx-auto shadow-2xs">
                        ✓
                    </div>
                    <p class="text-[10px] sm:text-xs font-black text-emerald-600 dark:text-emerald-400 font-myanmar leading-tight">
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
                    <p class="text-[10px] sm:text-xs font-bold {{ $isStep2Active ? 'text-amber-600 dark:text-amber-400 font-black' : ($isStep2Done ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400') }} font-myanmar leading-tight">
                        {{ __('messages.order_status_stepper_confirm') }}
                    </p>
                    @if ($isStep2Active)
                        <p class="text-[9px] text-amber-500/80 font-medium hidden sm:block">
                            {{ __('messages.order_status_pending_contact') }}
                        </p>
                    @endif
                </div>

                {{-- Step 3: Payment & Packing / Confirmed --}}
                @php
                    $isStep3Done = $order->status === 'delivered';
                    $isStep3Active = $order->status === 'confirmed';
                @endphp
                <div class="space-y-1 {{ !$isStep3Done && !$isStep3Active ? 'opacity-40' : '' }}">
                    <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center text-xs font-black mx-auto shadow-2xs
                        {{ $isStep3Done ? 'bg-emerald-500 text-white' : ($isStep3Active ? 'bg-sky-500 text-white animate-pulse' : 'bg-slate-200 dark:bg-slate-800 text-slate-500') }}">
                        {{ $isStep3Done ? '✓' : ($isStep3Active ? '💳' : '3') }}
                    </div>
                    <p class="text-[10px] sm:text-xs font-bold {{ $isStep3Active ? 'text-sky-600 dark:text-sky-400 font-black' : ($isStep3Done ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400') }} font-myanmar leading-tight">
                        {{ __('messages.order_status_stepper_payment') }}
                    </p>
                    @if ($isStep3Active)
                        <p class="text-[9px] text-sky-500/80 font-medium hidden sm:block">
                            {{ __('messages.order_status_confirmed') }}
                        </p>
                    @endif
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
                    <p class="text-[10px] sm:text-xs font-bold {{ $isStep4Done ? 'text-emerald-600 dark:text-emerald-400 font-black' : 'text-slate-500 dark:text-slate-400' }} font-myanmar leading-tight">
                        {{ __('messages.order_status_stepper_delivery') }}
                    </p>
                    @if ($isStep4Done)
                        <p class="text-[9px] text-emerald-500/80 font-medium hidden sm:block">
                            {{ __('messages.order_status_delivered') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- 3. Order Details & Customer Summary --}}
    <div class="print-card bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-3" data-order-status="{{ $order->status }}">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
            <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white font-myanmar flex items-center gap-1.5">
                <span>📋</span>
                <span>{{ __('messages.order_summary') }}</span>
            </h2>
            <span class="px-2 py-0.5 text-[10px] sm:text-xs font-black rounded-md
                {{ $order->status === 'delivered' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-300/60' : '' }}
                {{ $order->status === 'confirmed' ? 'bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-300/60' : '' }}
                {{ $order->status === 'pending_contact' ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-300/60' : '' }}
                {{ $order->status === 'cancelled' ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-300/60' : '' }}"
                data-status="{{ $order->status }}">
                {{ $statusText }}
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
            <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/60 space-y-0.5">
                <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500">{{ __('messages.full_name') }}</p>
                <p class="font-black text-slate-800 dark:text-slate-200">{{ $order->customer_name }}</p>
            </div>

            <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/60 space-y-0.5">
                <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500">{{ __('messages.phone_number') }}</p>
                <div class="flex items-center justify-between">
                    <p class="font-mono font-black text-slate-800 dark:text-slate-200">{{ $order->customer_phone }}</p>
                    <a href="tel:{{ $order->customer_phone }}" class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline no-print">
                        📞 {{ __('messages.call') ?? 'Call' }}
                    </a>
                </div>
            </div>

            @if ($order->contact_channel)
            <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/60 space-y-0.5">
                <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500">{{ __('messages.contact_channel') }}</p>
                <div class="flex items-center gap-1.5">
                    @if ($order->contact_channel === 'viber')
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 font-bold text-[10px]">
                            <x-brand-icon brand="viber" class="h-3.5 w-3.5 shrink-0"/> Viber
                        </span>
                    @elseif ($order->contact_channel === 'telegram')
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 font-bold text-[10px]">
                            <x-brand-icon brand="telegram" class="h-3.5 w-3.5 shrink-0"/> Telegram
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-[10px]">
                            📞 Phone
                        </span>
                    @endif
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $order->contact_identifier ?: $order->customer_phone }}</span>
                </div>
            </div>
            @endif

            <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/60 space-y-0.5 {{ empty($order->contact_channel) ? 'sm:col-span-2' : '' }}">
                <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500">{{ __('messages.address') }}</p>
                <p class="font-bold text-slate-800 dark:text-slate-200 leading-snug">{{ $order->customer_address ?: '—' }}</p>
            </div>

            @if ($order->customer_note)
            <div class="sm:col-span-2 p-2 rounded-md bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-900/40 space-y-0.5">
                <p class="text-[11px] font-bold text-amber-700 dark:text-amber-400">📝 {{ __('messages.customer_note') ?? 'Customer Note' }}</p>
                <p class="font-medium text-slate-700 dark:text-slate-300 text-xs">{{ $order->customer_note }}</p>
            </div>
            @endif
        </div>

        {{-- 4. Ordered Items Breakdown --}}
        @if ($order->items->count() > 0)
        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 space-y-1.5">
            <div class="flex items-center justify-between text-xs font-black text-slate-900 dark:text-white px-1">
                <span>{{ __('messages.items') }} ({{ $order->items->count() }})</span>
                <span>{{ __('messages.subtotal') ?? 'Subtotal' }}</span>
            </div>

            <div class="space-y-1 divide-y divide-slate-100 dark:divide-slate-800/60">
                @foreach ($order->items as $item)
                    <div class="pt-1.5 first:pt-0 flex items-start justify-between gap-3 text-xs">
                        <div class="min-w-0 space-y-0.5">
                            <div class="flex items-center gap-1.5">
                                <span class="font-black text-slate-900 dark:text-white">{{ $item->product_name }}</span>
                                <span class="shrink-0 px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 font-mono font-black text-slate-700 dark:text-slate-300 text-[11px]">
                                    x{{ format_quantity($item->quantity, $store) }}
                                </span>
                            </div>
                            @if ($item->variant_name && !str_contains((string) $item->product_name, (string) $item->variant_name))
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ $item->variant_name }}</p>
                            @endif
                            @if ($item->product && $item->product->product_type === 'service' && trim((string) $item->product->service_duration))
                                <p class="text-[10px] text-amber-600 dark:text-amber-400 font-bold">⏱️ {{ __('messages.product_form_service_duration') }}: {{ $item->product->service_duration }}</p>
                            @endif
                            @if ($item->product && $item->product->product_type === 'digital' && trim((string) $item->product->digital_delivery_method))
                                <p class="text-[10px] text-sky-600 dark:text-sky-400 font-bold">📲 {{ __('messages.product_form_digital_delivery_method') }}: {{ $item->product->digital_delivery_method }}</p>
                            @endif
                        </div>
                        <div class="text-right shrink-0">
                            <span class="font-black text-slate-900 dark:text-white font-mono text-xs sm:text-sm">
                                {{ format_currency($item->subtotal, $store ?? null) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Grand Total & Tax Breakdown --}}
            <div class="mt-2 pt-2 border-t border-dashed border-slate-200 dark:border-slate-700 space-y-1.5 px-1">
                @if ((float) $order->tax > 0 && $order->tax_type === 'exclusive')
                    @php $itemsSubtotal = $order->items->sum('subtotal'); @endphp
                    <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
                        <span>{{ __('messages.subtotal') }}</span>
                        <span class="font-mono font-bold">{{ format_currency($itemsSubtotal, $store ?? null) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
                        <span>{{ __('messages.tax') }} ({{ __('messages.commercial_tax') }})</span>
                        <span class="font-mono font-bold">+ {{ format_currency($order->tax, $store ?? null) }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between">
                    <span class="font-black text-xs sm:text-sm text-slate-900 dark:text-white">{{ __('messages.total_amount') }}</span>
                    <span class="font-black text-base sm:text-lg text-[color:var(--sf-primary)] font-outfit">
                        {{ format_currency($order->total_amount, $store ?? null) }}
                    </span>
                </div>
                @if ((float) $order->tax > 0 && $order->tax_type === 'inclusive')
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 text-right">
                        ({{ __('messages.tax_inclusive_notice', ['amount' => format_currency($order->tax, $store ?? null)]) }})
                    </p>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- 5. Direct Shop Chat Confirmation --}}
    <div class="print-card no-print bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3.5 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2.5">
        <div class="space-y-1">
            <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white font-myanmar flex items-center gap-1.5">
                <span>💬</span>
                <span>{{ __('messages.confirm_via_chat') }}</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar leading-relaxed">
                {{ __('messages.contact_shop_hint') }}
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1" x-data="{ textCopied: false }">
            @if ($viberUrl)
            <a href="{{ $viberUrl }}" data-ios-href="{{ $viberIosUrl ?? $viberUrl }}" target="_blank" rel="noopener noreferrer"
               class="sf-btn-3d-viber flex items-center justify-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-black rounded-md w-full">
                <x-brand-icon brand="viber" class="h-4 w-4 shrink-0"/>
                <span>Viber {{ __('messages.send_order') }}</span>
            </a>
            @endif

            @if ($telegramUrl)
            <a href="{{ $telegramUrl }}" target="_blank" rel="noopener noreferrer"
               class="sf-btn-3d-telegram flex items-center justify-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-black rounded-md w-full">
                <x-brand-icon brand="telegram" class="h-4 w-4 shrink-0"/>
                <span>Telegram {{ __('messages.send_order') }}</span>
            </a>
            @endif

            @if (!empty($store?->phone))
            <a href="tel:{{ $store->phone }}"
               class="sf-btn-3d-success flex items-center justify-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-black rounded-md w-full {{ (!$viberUrl && !$telegramUrl) ? 'sm:col-span-2' : '' }}">
                <span>📞</span>
                <span>{{ __('messages.order_call_shop') }}</span>
            </a>
            @endif

            {{-- 1-Tap Copy Full Order Message --}}
            <button type="button"
                    @click="
                        const msg = `မင်္ဂလာပါ။ Order Request (#{{ $order->order_number }})\nအမည်: {{ $order->customer_name }}\nဖုန်း: {{ $order->customer_phone }}\nလိပ်စာ: {{ $order->customer_address }}\nစုစုပေါင်း: {{ format_currency($order->total_amount, $store) }}`;
                        navigator.clipboard.writeText(msg);
                        textCopied = true;
                        setTimeout(() => textCopied = false, 2500);
                    "
                    class="sf-btn-3d flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold rounded-md w-full {{ (!$viberUrl || !$telegramUrl) ? '' : 'sm:col-span-2' }}">
                <span x-show="!textCopied">📋 {{ __('messages.order_copy_message_btn') }}</span>
                <span x-show="textCopied" x-cloak class="text-emerald-600 dark:text-emerald-400 font-black">✓ {{ __('messages.order_copy_message_success') }}</span>
            </button>
        </div>

        @if ($viberUrl)
            <p class="text-center text-[10px] text-slate-400 dark:text-slate-500 pt-1">
                {{ __('messages.viber_missing') }}
                <a href="https://www.viber.com/download/" target="_blank" rel="noopener noreferrer"
                   class="font-bold text-sky-600 transition hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300">{{ __('messages.viber_install') }} →</a>
            </p>
        @endif
    </div>

    {{-- 6. Payment Accounts & QR Codes --}}
    @if ($obHasPayment)
    <div class="print-card no-print bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3.5 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2.5">
        <div class="space-y-0.5">
            <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white font-myanmar flex items-center gap-1.5">
                <span>💳</span>
                <span>{{ __('messages.order_payment_transfer_title') }}</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar leading-relaxed">
                {{ __('messages.order_payment_transfer_desc') }}
            </p>
        </div>

        @if ($obPayments->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach ($obPayments as $pm)
                    <button type="button"
                        @click="$dispatch('open-payment-modal', {
                            name: @js($pm->name),
                            qr_url: @js($pm->qrUrl()),
                            account_name: @js($pm->show_account_details ? $pm->account_name : null),
                            account_number: @js($pm->show_account_details ? $pm->account_number : null),
                            instructions: @js($pm->instructions),
                        })"
                        class="w-full flex items-center justify-between gap-2 rounded-md bg-slate-50 dark:bg-slate-800/70 p-2.5 border border-slate-200/80 dark:border-slate-700/60 hover:border-slate-400 dark:hover:border-slate-500 transition group cursor-pointer text-left">
                        <div class="flex items-center gap-2 min-w-0">
                            <x-payment-method-icon :method="$pm" class="h-6 w-6 shrink-0 group-hover:scale-105 transition-transform" />
                            <div class="min-w-0">
                                <p class="text-xs font-black text-slate-900 dark:text-white truncate">{{ $pm->name }}</p>
                                @if ($pm->show_account_details && $pm->account_number)
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">{{ $pm->maskedAccountNumber() }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="shrink-0 px-2 py-1 rounded bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-[10px] font-bold group-hover:bg-slate-100 dark:group-hover:bg-slate-600 transition flex items-center gap-1">
                            <span>{{ $pm->hasQr() ? '📱 ' . __('messages.order_payment_qr_btn') : __('messages.order_payment_info_btn') }}</span>
                            <span>→</span>
                        </span>
                    </button>
                @endforeach
            </div>
        @elseif (!empty($store?->setting?->payment_info))
            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-md border border-slate-100 dark:border-slate-700/60 font-myanmar">
                {{ $store->setting->payment_info }}
            </p>
        @endif
    </div>
    @endif

    {{-- 7. Actions Toolbar (Print Slip & Continue Shopping) --}}
    <div class="no-print pt-2 flex flex-col sm:flex-row items-center justify-between gap-2.5">
        <button type="button" onclick="window.print()"
                class="sf-btn-3d flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs sm:text-sm font-black rounded-md w-full sm:w-auto">
            <span>🖨️</span>
            <span>{{ __('messages.order_print_slip') }}</span>
        </button>

        <a href="{{ url('/?store_slug=' . $activeStoreSlug) }}"
           class="sf-btn-3d-gold flex items-center justify-center gap-2 px-5 py-2.5 text-xs sm:text-sm font-black rounded-md w-full sm:w-auto">
            <span>{{ __('messages.order_continue_shopping') }}</span>
            <span>→</span>
        </a>
    </div>

</div>

{{-- Payment Details & QR Modal --}}
<x-payment-qr-modal />

@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
    window.addEventListener('DOMContentLoaded', () => {
        // Clear Alpine Order Builder state and local storage cart for security
        try {
            window.Alpine?.store('orderBuilder')?.clear();
            localStorage.removeItem('acdc_mobile_order_list');
            const storeSlug = @js($activeStoreSlug);
            if (storeSlug) {
                localStorage.removeItem('datapos_cart_' + storeSlug);
                localStorage.removeItem('datapos_order_selected_' + storeSlug);
            }
        } catch (e) {
            console.warn('Cart clean-up notice:', e);
        }
    });
</script>
@endpush
