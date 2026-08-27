@extends('layouts.storefront.app', ['title' => 'Order Confirmation'])

@section('content')
<div class="max-w-2xl mx-auto space-y-1 sm:space-y-1.5 lg:space-y-2">
    {{-- Success Banner --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 text-center space-y-3 border border-emerald-500/30 bg-gradient-to-b from-emerald-500/10 via-transparent to-transparent shadow-2xl">
        <div class="w-16 h-16 rounded-full bg-emerald-500 text-white flex items-center justify-center text-3xl mx-auto shadow-lg shadow-emerald-500/30">
            ✓
        </div>
        <h1 class="text-xl sm:text-3xl font-black text-slate-900 dark:text-white font-outfit">
            {{ __('messages.order_success') }}
        </h1>
        <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400 font-myanmar">
            {{ __('messages.order_number') }}: #{{ $order->order_number }}
        </p>
    </div>

    {{-- Order Summary Card --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-6 border border-white/50 dark:border-slate-800/80 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200/60 dark:border-slate-800/60 pb-3">
            <h2 class="text-base font-black text-slate-900 dark:text-white font-outfit">
                {{ __('messages.order_summary') }}
            </h2>
            <span class="px-3 py-1.5 text-sm font-bold rounded-full uppercase bg-amber-100 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 border border-amber-300/40">
                {{ $order->status }}
            </span>
        </div>

        <div class="space-y-2 text-sm">
            <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800/40">
                <span class="font-bold text-slate-500 dark:text-slate-400">{{ __('messages.order_number') }}</span>
                <span class="font-black text-violet-600 dark:text-violet-400 font-mono">{{ $order->order_number }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800/40">
                <span class="font-bold text-slate-500 dark:text-slate-400">{{ __('messages.full_name') }}</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $order->customer_name }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800/40">
                <span class="font-bold text-slate-500 dark:text-slate-400">{{ __('messages.phone_number') }}</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $order->customer_phone }}</span>
            </div>
            @if ($order->contact_identifier)
                <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800/40">
                    <span class="font-bold text-slate-500 dark:text-slate-400">{{ __('messages.contact_channel') }}</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200 text-right">{{ $order->contact_identifier }}</span>
                </div>
            @endif
            <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800/40">
                <span class="font-bold text-slate-500 dark:text-slate-400">{{ __('messages.address') }}</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 text-right max-w-xs">{{ $order->customer_address }}</span>
            </div>
            <div class="flex justify-between py-2 items-center">
                <span class="font-bold text-slate-600 dark:text-slate-300">{{ __('messages.total_amount') }}</span>
                <span class="font-black text-violet-600 dark:text-violet-400 font-outfit text-xl">Ks {{ number_format($order->total_amount) }}</span>
            </div>
        </div>

        {{-- Order Items --}}
        @if ($order->items->count() > 0)
                <div class="pt-3 border-t border-slate-200/60 dark:border-slate-800/60 space-y-1">
                <h3 class="font-bold text-sm text-slate-700 dark:text-slate-300 font-outfit">
                    {{ __('messages.items') }}
                </h3>
                @foreach ($order->items as $item)
                    <div class="-mx-5 sm:-mx-6 p-2.5 bg-slate-50 dark:bg-slate-800/60 border-y border-slate-200/50 dark:border-slate-700/50">
                        <div class="flex items-start justify-between gap-3">
                            <span class="font-bold text-sm text-slate-800 dark:text-slate-200">{{ $item->product_name }}</span>
                            <span class="font-black text-sm text-violet-600 dark:text-violet-400 shrink-0">x{{ $item->quantity }}</span>
                        </div>
                        @if ($item->variant_name && !str_contains((string) $item->product_name, (string) $item->variant_name))
                            <div class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ $item->variant_name }}</div>
                        @endif
                        @if ($item->product && $item->product->product_type === 'service' && trim((string) $item->product->service_duration))
                            <div class="mt-0.5 text-[11px] text-amber-600 dark:text-amber-400 font-bold">⏱️ {{ __('messages.product_form_service_duration') }}: {{ $item->product->service_duration }}</div>
                        @endif
                        @if ($item->product && $item->product->product_type === 'digital' && trim((string) $item->product->digital_delivery_method))
                            <div class="mt-0.5 text-[11px] text-sky-600 dark:text-sky-400 font-bold">📲 {{ __('messages.product_form_digital_delivery_method') }}: {{ $item->product->digital_delivery_method }}</div>
                        @endif
                        <div class="mt-1 text-right">
                            <span class="font-black text-base text-slate-900 dark:text-white">Ks {{ number_format($item->subtotal) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Contact Shop --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-6 border border-white/50 dark:border-slate-800/80 shadow-2xl space-y-3">
        <h2 class="text-base font-black text-slate-900 dark:text-white font-outfit">
            {{ __('messages.confirm_via_chat') }}
        </h2>
        <p class="text-sm font-bold text-slate-500 dark:text-slate-400 font-myanmar leading-relaxed">
            {{ __('messages.contact_shop_hint') }}
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
            @if ($viberUrl)
            <a href="{{ $viberUrl }}" data-ios-href="{{ $viberIosUrl ?? $viberUrl }}" target="_blank" rel="noopener noreferrer"
               class="flex items-center justify-center space-x-2 px-5 py-4 bg-purple-600 hover:bg-purple-500 text-white font-bold text-sm rounded-2xl shadow-lg shadow-purple-600/20 transition transform active:scale-95">
                <x-brand-icon brand="viber" class="h-5 w-5 shrink-0"/>
                <span>Viber {{ __('messages.send_order') }}</span>
            </a>
            @endif
            @if ($telegramUrl)
            <a href="{{ $telegramUrl }}" target="_blank" rel="noopener noreferrer"
               class="flex items-center justify-center space-x-2 px-5 py-4 bg-sky-500 hover:bg-sky-400 text-white font-bold text-sm rounded-2xl shadow-lg shadow-sky-500/20 transition transform active:scale-95">
                <x-brand-icon brand="telegram" class="h-5 w-5 shrink-0"/>
                <span>Telegram {{ __('messages.send_order') }}</span>
            </a>
            @endif
        </div>

        @if ($viberUrl)
            <p class="text-center text-[11px] text-slate-400 dark:text-slate-500">
                {{ __('messages.viber_missing') }}
                <a href="https://www.viber.com/download/" target="_blank" rel="noopener noreferrer"
                   class="font-bold text-sky-600 transition hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300">{{ __('messages.viber_install') }} →</a>
            </p>
        @endif

        @php
            $activeStoreSlug = request('store_slug') ?? $store?->slug;
        @endphp
        <div class="text-center pt-3">
            <a href="{{ url('/?store_slug=' . $activeStoreSlug) }}" class="text-sm font-bold text-violet-600 dark:text-violet-400 hover:underline inline-flex items-center space-x-1">
                <span>&larr;</span>
                <span>{{ __('messages.back_to_home') }}</span>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
    window.addEventListener('DOMContentLoaded', () => {
        window.Alpine?.store('orderBuilder')?.clear();
    });
</script>
@endpush
