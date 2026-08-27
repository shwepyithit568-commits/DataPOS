@extends('layouts.admin.app')

@section('title', 'Order #' . $order->order_number . ' - ' . $store->name)
@section('main_padding', 'p-2')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $amount = $order->agreed_amount !== null ? (float) $order->agreed_amount : (float) $order->total_amount;
    $isWholesale = $order->pricing_type === 'wholesale';
    $channel = strtolower($order->contact_channel);
@endphp

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5">

    {{-- ============================================================
         HEADER — back button, breadcrumb, status badge
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('store.admin.orders.index', $storeRouteParams) }}"
               class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 grid place-items-center text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition shadow-2xs shrink-0"
               title="{{ __('messages.back_to_list') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="min-w-0">
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-amber-100 dark:border-amber-900/60 mb-0.5">
                    <span>🛒</span>
                    <a href="{{ route('store.admin.orders.index', $storeRouteParams) }}" class="hover:underline">
                        {{ __('messages.sidebar_orders') }}
                    </a>
                    <span>/</span>
                    <span>{{ $order->order_number }}</span>
                </div>
                <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <span class="font-mono">#{{ $order->order_number }}</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase
                        {{ $order->status === 'confirmed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80' : '' }}
                        {{ $order->status === 'pending_contact' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80' : '' }}
                        {{ $order->status === 'delivered' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-200 dark:border-blue-800/80' : '' }}
                        {{ $order->status === 'cancelled' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800/80' : '' }}">
                        @if ($order->status === 'pending_contact')
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        @endif
                        {{ $order->status === 'pending_contact' ? 'Pending' : ucfirst($order->status) }}
                    </span>
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 tabular-nums">
                    {{ $order->created_at->format('M d, Y h:i A') }} · {{ $store->name }}
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 shrink-0">
            <a href="{{ route('store.admin.orders.invoice', array_merge($storeRouteParams, ['order' => $order->id])) }}" target="_blank"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs active:scale-95">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>{{ __('messages.print_invoice') }}</span>
            </a>
        </div>
    </header>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="w-full p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1 shadow-2xs">
            <div class="font-black flex items-center gap-1.5">
                <span>⚠️</span>
                <span>{{ __('messages.order_error_heading') }}</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="ml-5">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         ORDER SUMMARY — customer info + status/payment
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 sm:gap-2.5">
        {{-- Customer & Channel Info --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-4 sm:p-5 shadow-2xs space-y-3">
            <h3 class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>{{ __('messages.order_customer_info') }}</span>
            </h3>

            <div class="space-y-0 text-xs divide-y divide-slate-100 dark:divide-slate-800">
                <div class="flex items-center justify-between py-2">
                    <span class="text-slate-500 font-bold">{{ __('messages.order_customer_name') }}</span>
                    <span class="font-black text-slate-900 dark:text-slate-100">{{ $order->customer_name }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-slate-500 font-bold">{{ __('messages.order_customer_phone') }}</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-slate-100">📞 {{ $order->customer_phone }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-slate-500 font-bold">{{ __('messages.order_contact_channel') }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700/60">
                        @if ($channel === 'viber') Viber
                        @elseif ($channel === 'telegram') Telegram
                        @else Phone @endif
                    </span>
                </div>
                @if ($order->contact_identifier)
                    <div class="flex items-center justify-between py-2">
                        <span class="text-slate-500 font-bold">{{ __('messages.order_contact_identifier') }}:</span>
                        <span class="font-bold text-violet-600 dark:text-violet-400">{{ $order->contact_identifier }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between py-2">
                    <span class="text-slate-500 font-bold">{{ __('messages.order_pricing_tier') }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60' : 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/60' }}">
                        {{ $order->pricing_type }}
                    </span>
                </div>
                <div class="py-2">
                    <span class="text-slate-500 font-bold block mb-1">{{ __('messages.order_address_label') }}</span>
                    <p class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/50 text-slate-700 dark:text-slate-300 text-xs border border-slate-200/60 dark:border-slate-700/60">
                        {{ $order->customer_address ?: __('messages.order_no_address') }}
                    </p>
                </div>
                @if ($order->customer_note)
                    <div class="py-2">
                        <span class="text-slate-500 font-bold block mb-1">{{ __('messages.order_customer_note') }}</span>
                        <p class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 text-xs border border-amber-200/60 dark:border-amber-800/60">
                            {{ $order->customer_note }}
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Order Status & Price/Payment Box --}}
        <div class="lg:col-span-2 space-y-2 sm:space-y-2.5">
            {{-- Status Bar --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-4 sm:p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1.5">{{ __('messages.order_current_status') }}</span>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase
                            {{ $order->status === 'confirmed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80' : '' }}
                            {{ $order->status === 'pending_contact' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80' : '' }}
                            {{ $order->status === 'delivered' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-200 dark:border-blue-800/80' : '' }}
                            {{ $order->status === 'cancelled' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800/80' : '' }}">
                            @if ($order->status === 'pending_contact')
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            @endif
                            {{ $order->status === 'pending_contact' ? __('messages.order_status_pending_contact') : ucfirst($order->status) }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $order->payment_status === 'paid' ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border border-sky-200 dark:border-sky-800' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700' }}">
                            {{ $order->payment_status === 'paid' ? __('messages.order_status_paid') : __('messages.order_status_unpaid') }}
                        </span>
                    </div>
                </div>

                {{-- Status Change Form --}}
                <form method="POST" action="{{ route('store.admin.orders.update_status', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                      class="flex items-center gap-1.5">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="text-xs font-bold border rounded-lg px-2.5 py-2 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100 min-h-[36px] focus:outline-none focus:ring-2 focus:ring-violet-500/40">
                        <option value="pending_contact" {{ $order->status === 'pending_contact' ? 'selected' : '' }}>{{ __('messages.order_status_pending_contact') }}</option>
                        <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>{{ __('messages.order_status_confirm') }}</option>
                        <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>{{ __('messages.order_status_delivered') }}</option>
                        <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>{{ __('messages.order_status_cancel') }}</option>
                    </select>
                    <button type="submit"
                            class="min-h-[36px] px-3.5 py-2 rounded-lg text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs transition active:scale-95 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ __('messages.update') }}</span>
                    </button>
                </form>
            </div>

            {{-- Agreed Price & Payment Status Editor --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-4 sm:p-5 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <h3 class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ __('messages.order_payment_price') }}</span>
                    </h3>
                </div>

                <form method="POST" action="{{ route('store.admin.orders.update_finances', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                      class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 items-end pt-1">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.order_agreed_amount') }}</label>
                        <input type="number" name="agreed_amount" min="0" step="100" value="{{ $order->agreed_amount ?? '' }}"
                               placeholder="e.g. 45000"
                               class="w-full px-3 py-2 rounded-lg text-xs font-mono font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500/40 min-h-[36px]">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.order_payment_label') }}</label>
                        <select name="payment_status"
                                class="w-full px-3 py-2 rounded-lg text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500/40 min-h-[36px]">
                            <option value="unpaid" {{ $order->payment_status === 'unpaid' ? 'selected' : '' }}>{{ __('messages.order_status_unpaid') }}</option>
                            <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>{{ __('messages.order_status_paid') }}</option>
                        </select>
                    </div>

                    <div>
                        <button type="submit"
                                class="w-full min-h-[36px] px-4 py-2 rounded-lg text-xs font-black bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition active:scale-95 flex items-center justify-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ __('messages.order_save_financials') }}</span>
                        </button>
                    </div>
                </form>

                <div class="text-[11px] text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between tabular-nums">
                    <span>{{ __('messages.order_original_total') }}: <strong class="text-slate-700 dark:text-slate-300 font-mono">Ks {{ number_format($order->total_amount) }}</strong></span>
                    @if ($order->agreed_amount !== null)
                        <span>{{ __('messages.order_agreed_total') }}: <strong class="text-violet-600 dark:text-violet-400 font-mono">Ks {{ number_format($order->agreed_amount) }}</strong></span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         ORDER ITEMS TABLE — spreadsheet grid with sticky header
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden">
        <div class="p-3.5 sm:p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>{{ __('messages.order_items_heading') }}</span>
            </h3>
            <span class="text-[11px] font-mono text-slate-400 dark:text-slate-500">{{ __('messages.order_items_count', ['count' => $order->items->count()]) }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        <th class="py-2.5 px-3 min-w-[200px]">{{ __('messages.order_table_product') }}</th>
                        <th class="py-2.5 px-3 text-right min-w-[110px]">{{ __('messages.unit_price') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[80px]">{{ __('messages.order_table_qty') }}</th>
                        <th class="py-2.5 px-3 text-right min-w-[110px]">{{ __('messages.subtotal') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/90 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @foreach ($order->items as $item)
                        <tr class="hover:bg-violet-50/60 dark:hover:bg-violet-950/20 divide-x divide-slate-200/80 dark:divide-slate-800 transition-colors">
                            <td class="py-2.5 px-3">
                                <div class="font-bold text-slate-900 dark:text-slate-100 leading-snug">{{ $item->product_name }}</div>
                                @if ($item->variant_name && !str_contains((string) $item->product_name, (string) $item->variant_name))
                                    <div class="text-[11px] text-slate-400 font-normal mt-0.5">{{ $item->variant_name }} @if ($item->variant_sku) · <span class="font-mono">{{ $item->variant_sku }}</span> @endif</div>
                                @elseif ($item->variant_sku)
                                    <div class="text-[11px] font-mono text-slate-400 mt-0.5">{{ $item->variant_sku }}</div>
                                @endif
                                @if ($item->product && $item->product->product_type === 'service' && trim((string) $item->product->service_duration))
                                    <div class="text-[11px] text-amber-600 dark:text-amber-400 font-bold mt-0.5">⏱️ {{ __('messages.product_form_service_duration') }}: {{ $item->product->service_duration }}</div>
                                @endif
                                @if ($item->product && $item->product->product_type === 'digital' && trim((string) $item->product->digital_delivery_method))
                                    <div class="text-[11px] text-sky-600 dark:text-sky-400 font-bold mt-0.5">📲 {{ __('messages.product_form_digital_delivery_method') }}: {{ $item->product->digital_delivery_method }}</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-700 dark:text-slate-300 tabular-nums">
                                Ks {{ number_format($item->unit_price, 0) }}
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                {{ $item->quantity }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums">
                                Ks {{ number_format($item->subtotal, 0) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-slate-300 dark:border-slate-600 bg-slate-50/80 dark:bg-slate-800/40">
                    <tr class="divide-x divide-slate-200/80 dark:divide-slate-800">
                        <td colspan="3" class="py-3 px-4 text-right font-black text-slate-700 dark:text-slate-300 text-xs uppercase">
                            {{ __('messages.total_amount') }}:
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-black text-sm text-violet-600 dark:text-violet-400 tabular-nums">
                            Ks {{ number_format($amount, 0) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ============================================================
         INTERNAL ADMIN NOTE
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-4 sm:p-5 shadow-2xs space-y-3"
         x-data="{ noteOpen: true }">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
            <h3 class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.4-9.4a2 2 0 112.8 2.8L11 14l-4 1 1-4 9.6-9.4z"/></svg>
                <span>{{ __('messages.order_internal_note') }}</span>
            </h3>
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full border border-slate-200/60 dark:border-slate-700/60">{{ __('messages.order_internal_only') }}</span>
            </div>
        </div>

        <p class="text-[11px] text-slate-500 dark:text-slate-400">
            {{ __('messages.order_internal_note_hint') }}
        </p>

        <form method="POST" action="{{ route('store.admin.orders.update_note', array_merge($storeRouteParams, ['order' => $order->id])) }}" class="space-y-2.5"
              x-data="{ saving: false }" @submit="saving = true">
            @csrf
            @method('PATCH')

            <textarea name="admin_note" rows="3"
                      placeholder="{{ __('messages.order_note_placeholder') }}"
                      class="w-full p-3 rounded-lg text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500/40 min-h-[80px]">{{ $order->admin_note }}</textarea>

            <div class="flex justify-end">
                <button type="submit" :disabled="saving"
                        class="min-h-[36px] px-4 py-2 rounded-lg text-xs font-black bg-slate-800 hover:bg-slate-700 text-white dark:bg-slate-700 dark:hover:bg-slate-600 shadow-2xs transition active:scale-95 flex items-center gap-1.5 disabled:opacity-60 disabled:cursor-not-allowed">
                    <template x-if="!saving">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ __('messages.order_save_note') }}</span>
                        </span>
                    </template>
                    <template x-if="saving">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                            <span>Saving...</span>
                        </span>
                    </template>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
