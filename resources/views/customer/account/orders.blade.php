@extends('layouts.storefront.app')

@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $storeSetting = $store?->setting;
    $storeLogo = $storeSetting?->adminLogo();
    $storeLogoUrl = $storeLogo ? asset('storage/' . $storeLogo) : null;
    $accountUrl = $storeSlug ? url('/account?store_slug=' . $storeSlug) : url('/account');
    $orderBuilderUrl = $storeSlug ? url('/order-builder?store_slug=' . $storeSlug) : url('/order-builder');
    $catalogUrl = $storeSlug ? url('/products?store_slug=' . $storeSlug) : url('/products');

    $accountOrderUrl = fn ($order) => $storeSlug
        ? url('/account/orders/' . $order->id . '?store_slug=' . $storeSlug)
        : url('/account/orders/' . $order->id);

    $currentStatus = $status ?? 'all';
    $searchQuery = $search ?? '';
@endphp

<div class="max-w-5xl mx-auto space-y-1.5 sm:space-y-3 select-none font-sans pb-16"
     x-data="{
         viewMode: localStorage.getItem('datapos_orders_view') || 'cards',
         setViewMode(mode) {
             this.viewMode = mode;
             localStorage.setItem('datapos_orders_view', mode);
         }
     }">

    {{-- 1. Top Breadcrumb & Store Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-3 px-2 sm:px-3 py-1.5 sm:py-2 border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl shadow-2xs">
        <div class="flex items-center gap-2 sm:gap-2.5 min-w-0">
            @if ($storeLogoUrl)
                <img src="{{ $storeLogoUrl }}" alt="{{ $store->name }}" class="h-8 w-8 sm:h-9 sm:w-9 rounded-md object-contain bg-white dark:bg-slate-800 p-0.5 border border-slate-200 dark:border-slate-700 shadow-2xs shrink-0" />
            @else
                <div class="sf-btn-3d active flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-md text-white font-black text-xs sm:text-sm shrink-0 pointer-events-none">
                    {{ mb_substr($store?->name ?? 'D', 0, 1) }}
                </div>
            @endif
            <div class="min-w-0">
                <h1 class="text-sm sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-1.5 sm:gap-2 flex-wrap font-sans">
                    <span class="truncate">{{ __('messages.order_history_title') }}</span>
                    <span class="sf-btn-3d active inline-flex items-center px-1.5 py-0.2 rounded text-[10px] sm:text-[11px] font-black pointer-events-none whitespace-nowrap">
                        {{ $statusCounts['all'] ?? 0 }} {{ __('messages.order_items_unit') }}
                    </span>
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 font-medium truncate">
                    {{ __('messages.order_history_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 shrink-0 self-start sm:self-auto">
            <a href="{{ $accountUrl }}"
               class="sf-btn-3d !inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-bold">
                <span aria-hidden="true">←</span>
                <span>{{ __('messages.account') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. Interactive Toolbar (Status Tabs, Search, View Switcher) --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-2 sm:p-3 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2">
        {{-- Status Filter Tabs / Pills --}}
        <div class="flex items-center gap-1 sm:gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-none text-xs">
            @php
                $statusTabs = [
                    'all' => [
                        'label' => __('messages.order_status_all'),
                        'count' => $statusCounts['all'] ?? 0,
                    ],
                    'pending_contact' => [
                        'label' => __('messages.order_status_pending_contact'),
                        'count' => $statusCounts['pending_contact'] ?? 0,
                    ],
                    'confirmed' => [
                        'label' => __('messages.order_status_confirmed'),
                        'count' => $statusCounts['confirmed'] ?? 0,
                    ],
                    'delivered' => [
                        'label' => __('messages.order_status_delivered'),
                        'count' => $statusCounts['delivered'] ?? 0,
                    ],
                    'cancelled' => [
                        'label' => __('messages.order_status_cancelled'),
                        'count' => $statusCounts['cancelled'] ?? 0,
                    ],
                ];
            @endphp

            @foreach ($statusTabs as $tabKey => $tab)
                @php
                    $isActiveTab = $currentStatus === $tabKey;
                    $tabUrl = url('/account/orders?' . http_build_query(array_filter([
                        'store_slug' => $storeSlug,
                        'status'     => $tabKey !== 'all' ? $tabKey : null,
                        'q'          => $searchQuery !== '' ? $searchQuery : null,
                    ])));
                @endphp
                <a href="{{ $tabUrl }}"
                   class="sf-btn-3d shrink-0 !inline-flex items-center gap-1.5 px-2.5 py-1 sm:py-1.5 rounded-md text-[11px] sm:text-xs font-bold transition whitespace-nowrap {{ $isActiveTab ? 'active text-white' : '' }}">
                    <span>{{ $tab['label'] }}</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ $isActiveTab ? 'bg-white/25 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                        {{ $tab['count'] }}
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Search Input & View Switcher Row --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-1 border-t border-slate-100 dark:border-slate-800/80">
            {{-- Search Bar --}}
            <form method="GET" action="{{ url('/account/orders') }}" class="flex-1 max-w-md flex items-center gap-1.5">
                @if ($storeSlug)
                    <input type="hidden" name="store_slug" value="{{ $storeSlug }}">
                @endif
                @if ($currentStatus !== 'all')
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif

                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400 dark:text-slate-500 text-xs">
                        🔍
                    </span>
                    <input type="text"
                           name="q"
                           value="{{ $searchQuery }}"
                           placeholder="{{ __('messages.order_history_search_placeholder') }}"
                           class="w-full h-8 sm:h-9 pl-7 pr-7 text-xs rounded-md bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[color:var(--sf-primary)]" />
                    @if ($searchQuery !== '')
                        <a href="{{ url('/account/orders?' . http_build_query(array_filter(['store_slug' => $storeSlug, 'status' => $currentStatus !== 'all' ? $currentStatus : null]))) }}"
                           class="absolute inset-y-0 right-0 flex items-center pr-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs"
                           title="{{ __('messages.order_filter_reset') }}">
                            ✕
                        </a>
                    @endif
                </div>

                <button type="submit"
                        class="sf-btn-3d px-3 h-8 sm:h-9 rounded-md text-xs font-bold shrink-0">
                    {{ __('messages.search') }}
                </button>
            </form>

            {{-- View Switcher Buttons (Hidden on very small screens, visible sm+) --}}
            <div class="hidden sm:flex items-center gap-1 shrink-0 p-0.5 rounded-md bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                <button type="button"
                        @click="setViewMode('cards')"
                        :class="viewMode === 'cards' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs font-black' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 font-bold'"
                        class="px-2.5 py-1 rounded text-xs transition flex items-center gap-1 cursor-pointer">
                    <span>🗂️</span>
                    <span>{{ __('messages.order_view_cards') }}</span>
                </button>
                <button type="button"
                        @click="setViewMode('table')"
                        :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs font-black' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 font-bold'"
                        class="px-2.5 py-1 rounded text-xs transition flex items-center gap-1 cursor-pointer">
                    <span>📊</span>
                    <span>{{ __('messages.order_view_table') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- 3. Order Items Content --}}
    @if ($orders->isEmpty())
        {{-- Empty State --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-6 sm:p-10 border border-slate-200/90 dark:border-slate-800 shadow-xs text-center space-y-3">
            <div class="w-14 h-14 sm:w-16 sm:h-16 mx-auto rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-3xl sm:text-4xl shadow-2xs border border-slate-200/80 dark:border-slate-700">
                📦
            </div>
            <div class="space-y-1">
                <h3 class="font-black text-sm sm:text-base text-slate-900 dark:text-white">
                    {{ __('messages.order_no_history_title') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto font-medium">
                    {{ __('messages.order_no_history_subtitle') }}
                </p>
            </div>
            <div class="pt-2 flex items-center justify-center gap-2 flex-wrap">
                @if ($currentStatus !== 'all' || $searchQuery !== '')
                    <a href="{{ url('/account/orders?' . http_build_query(array_filter(['store_slug' => $storeSlug]))) }}"
                       class="sf-btn-3d !inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-md text-xs font-bold">
                        <span>✕</span>
                        <span>{{ __('messages.order_filter_reset') }}</span>
                    </a>
                @endif
                <a href="{{ $catalogUrl }}"
                   class="sf-btn-3d-primary !inline-flex items-center gap-1.5 px-4 py-1.5 rounded-md text-xs font-black">
                    <span>🛍️</span>
                    <span>{{ __('messages.order_browse_catalog') }}</span>
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    @else
        {{-- CARDS VIEW --}}
        <div x-show="viewMode === 'cards'" class="space-y-1.5 sm:space-y-2">
            @foreach ($orders as $order)
                @php
                    $itemCount = $order->items->count();
                    $firstItem = $order->items->first();
                    $orderEffectiveTotal = $order->agreed_amount ?? $order->total_amount;
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
                @endphp

                <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-2.5 sm:p-3.5 border border-slate-200/90 dark:border-slate-800 shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition space-y-2 sm:space-y-2.5">
                    {{-- Order Card Header Row --}}
                    <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800/80 pb-2">
                        <div class="min-w-0 flex items-center gap-2">
                            <span class="text-xs sm:text-sm font-black font-mono text-sky-600 dark:text-sky-400 truncate">
                                #{{ $order->order_number }}
                            </span>
                            <span class="text-[10px] sm:text-[11px] font-mono text-slate-400 dark:text-slate-500 whitespace-nowrap">
                                {{ $order->created_at->format('Y-m-d H:i') }}
                            </span>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            {{-- Status Badge --}}
                            @if ($order->status === 'pending_contact')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] sm:text-[11px] font-bold bg-amber-50 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    <span>{{ __('messages.order_status_pending_contact') }}</span>
                                </span>
                            @elseif ($order->status === 'confirmed')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] sm:text-[11px] font-bold bg-sky-50 dark:bg-sky-950/70 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60">
                                    <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                                    <span>{{ __('messages.order_status_confirmed') }}</span>
                                </span>
                            @elseif ($order->status === 'delivered')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] sm:text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    <span>{{ __('messages.order_status_delivered') }}</span>
                                </span>
                            @elseif ($order->status === 'cancelled')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] sm:text-[11px] font-bold bg-rose-50 dark:bg-rose-950/70 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    <span>{{ __('messages.order_status_cancelled') }}</span>
                                </span>
                            @endif

                            {{-- Payment Badge --}}
                            @if ($order->payment_status === 'paid')
                                <span class="hidden xs:inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-black bg-emerald-100/70 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                                    {{ __('messages.order_status_paid') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Order Items Preview Row --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                            {{-- Visual Thumbnails (up to 3) --}}
                            <div class="flex items-center -space-x-2 shrink-0 overflow-hidden py-0.5">
                                @foreach ($order->items->take(3) as $thumbItem)
                                    @php
                                        $thumbImg = $thumbItem->product?->image_path;
                                    @endphp
                                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-md bg-slate-100 dark:bg-slate-800 border border-white dark:border-slate-800 shadow-2xs overflow-hidden flex items-center justify-center shrink-0">
                                        @if ($thumbImg)
                                            <img src="{{ asset('storage/' . $thumbImg) }}" alt="{{ $thumbItem->product_name }}" class="w-full h-full object-cover" loading="lazy" />
                                        @else
                                            <span class="text-[10px] text-slate-400 font-bold">📦</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Text summary of items --}}
                            <div class="min-w-0 space-y-0.5">
                                <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">
                                    {{ $firstItem?->product_name ?? __('messages.order_items_heading') }}
                                    @if ($firstItem?->variant_name)
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-normal">({{ $firstItem->variant_name }})</span>
                                    @endif
                                </p>
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                    <span>{{ __('messages.order_items_total', ['count' => $itemCount]) }}</span>
                                    @if ($order->contact_channel)
                                        <span>·</span>
                                        <span class="uppercase font-semibold text-[10px] px-1 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                            {{ $order->contact_channel }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Price Info --}}
                        <div class="text-right shrink-0">
                            <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-white font-mono">
                                {{ format_currency($orderEffectiveTotal, $store) }}
                            </div>
                            @if ($order->agreed_amount !== null && (float) $order->agreed_amount !== (float) $order->total_amount)
                                <div class="text-[10px] text-slate-400 line-through font-mono">
                                    {{ format_currency($order->total_amount, $store) }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Actions Row --}}
                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100 dark:border-slate-800/80">
                        <div class="text-[11px] text-slate-400 dark:text-slate-500 truncate min-w-0">
                            @if ($order->customer_address)
                                <span class="truncate">📍 {{ Str::limit($order->customer_address, 40) }}</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            {{-- 1-Tap Reorder Button --}}
                            <button type="button"
                                    @click="reorderOrder(@js($reorderPayload))"
                                    class="sf-btn-3d-primary !inline-flex items-center gap-1 px-2.5 py-1 sm:py-1.5 rounded-md text-xs font-black cursor-pointer shadow-2xs"
                                    title="{{ __('messages.order_reorder_1tap') }}">
                                <span aria-hidden="true">⚡</span>
                                <span>{{ __('messages.order_reorder_1tap') }}</span>
                            </button>

                            {{-- View Details Button --}}
                            <a href="{{ $accountOrderUrl($order) }}"
                               class="sf-btn-3d !inline-flex items-center gap-1 px-2.5 py-1 sm:py-1.5 rounded-md text-xs font-bold"
                               title="{{ __('messages.order_view_detail') }}">
                                <span>{{ __('messages.order_view_detail') }}</span>
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- TABLE VIEW (Responsive Table) --}}
        <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl border border-slate-200/90 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/70 border-b border-slate-200/80 dark:border-slate-800 font-bold text-slate-800 dark:text-slate-200 uppercase text-[10px] tracking-wider">
                        <tr>
                            <th class="p-2.5 sm:p-3">{{ __('messages.order_number') }}</th>
                            <th class="p-2.5 sm:p-3">{{ __('messages.order_date') }}</th>
                            <th class="p-2.5 sm:p-3">{{ __('messages.order_items_heading') }}</th>
                            <th class="p-2.5 sm:p-3">{{ __('messages.total_amount') }}</th>
                            <th class="p-2.5 sm:p-3">{{ __('messages.status') }}</th>
                            <th class="p-2.5 sm:p-3 text-right">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                        @foreach ($orders as $order)
                            @php
                                $itemCount = $order->items->count();
                                $firstItem = $order->items->first();
                                $orderEffectiveTotal = $order->agreed_amount ?? $order->total_amount;
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
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <td class="p-2.5 sm:p-3 font-mono font-black text-sky-600 dark:text-sky-400 whitespace-nowrap">
                                    #{{ $order->order_number }}
                                </td>
                                <td class="p-2.5 sm:p-3 font-mono text-[11px] text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ $order->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="p-2.5 sm:p-3 max-w-[220px]">
                                    <div class="truncate font-bold text-slate-800 dark:text-slate-200">
                                        {{ $firstItem?->product_name ?? 'Item' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 truncate">
                                        {{ __('messages.order_items_total', ['count' => $itemCount]) }}
                                    </div>
                                </td>
                                <td class="p-2.5 sm:p-3 font-mono font-black text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ format_currency($orderEffectiveTotal, $store) }}
                                </td>
                                <td class="p-2.5 sm:p-3 whitespace-nowrap">
                                    @if ($order->status === 'pending_contact')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            <span>{{ __('messages.order_status_pending_contact') }}</span>
                                        </span>
                                    @elseif ($order->status === 'confirmed')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-sky-50 dark:bg-sky-950/70 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60">
                                            <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                                            <span>{{ __('messages.order_status_confirmed') }}</span>
                                        </span>
                                    @elseif ($order->status === 'delivered')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            <span>{{ __('messages.order_status_delivered') }}</span>
                                        </span>
                                    @elseif ($order->status === 'cancelled')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 dark:bg-rose-950/70 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                            <span>{{ __('messages.order_status_cancelled') }}</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="p-2.5 sm:p-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button"
                                                @click="reorderOrder(@js($reorderPayload))"
                                                class="sf-btn-3d-primary !inline-flex items-center gap-1 px-2 py-1 rounded text-[11px] font-black cursor-pointer shadow-2xs"
                                                title="{{ __('messages.order_reorder_1tap') }}">
                                            <span>⚡</span>
                                        </button>
                                        <a href="{{ $accountOrderUrl($order) }}"
                                           class="sf-btn-3d !inline-flex items-center gap-1 px-2 py-1 rounded text-[11px] font-bold"
                                           title="{{ __('messages.order_view_detail') }}">
                                            <span>{{ __('messages.order_view_detail') }}</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 4. Pagination Links --}}
        <div class="pt-2">
            {{ $orders->links() }}
        </div>
    @endif
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
