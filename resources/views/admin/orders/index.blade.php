@extends('layouts.admin.app')

@section('title', __('messages.sidebar_orders', [], 'en') ? __('messages.sidebar_orders') . ' - ' . $store->name : 'Orders - ' . $store->name)
@section('main_padding', 'p-2')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $exportUrl = route('store.admin.orders.export', array_merge($storeRouteParams, request()->only(['search', 'sort', 'tab', 'status', 'pricing_type', 'contact_channel'])));

    // Accent color tokens for KPI stat cards
    $statAccents = [
        'pending'   => 'bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300',
        'confirmed' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300',
        'delivered' => 'bg-blue-100 text-blue-600 dark:bg-blue-950/70 dark:text-blue-300',
        'cancelled' => 'bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300',
        'revenue'   => 'bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300',
    ];
    $statBorders = [
        'pending'   => 'hover:border-amber-300 dark:hover:border-amber-700/80',
        'confirmed' => 'hover:border-emerald-300 dark:hover:border-emerald-700/80',
        'delivered' => 'hover:border-blue-300 dark:hover:border-blue-700/80',
        'cancelled' => 'hover:border-rose-300 dark:hover:border-rose-700/80',
        'revenue'   => 'hover:border-violet-300 dark:hover:border-violet-700/80',
    ];
@endphp

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
        viewMode: localStorage.getItem('admin_orders_view_mode') || 'table',
        deleteConfirmId: null,
        openDeleteConfirm(id) { this.deleteConfirmId = id; },
        closeDeleteConfirm() { this.deleteConfirmId = null; },
        submitDelete(id) { this.deleteConfirmId = null; document.getElementById('delete-form-' + id).submit(); }
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_orders_view_mode', $event.detail)">

    {{-- ============================================================
         HERO HEADER — eyebrow badge, title, subtitle, actions
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0">
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🛒 အော်ဒါ အမှာစာများ (Order Requests)</span>
            </h1>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 shrink-0">
            <a href="{{ $exportUrl }}"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs active:scale-95">
                <svg class="w-3.5 h-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 14l5-5 5 5M12 3v12"/>
                </svg>
                <span>{{ __('messages.export_csv_button') }}</span>
            </a>
            <a href="{{ route('pos.index', $storeRouteParams) }}" target="_blank"
               class="px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-lg text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span>POS ကောင်တာဖွင့်မည်</span>
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
         KPI STAT CARDS — compact horizontal layout (products pattern)
         ============================================================ --}}
    <div class="w-full grid grid-cols-2 lg:grid-cols-5 gap-2 sm:gap-2.5" role="list" aria-label="Order Summary">
        {{-- Pending --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'pending'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-3 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['pending'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center {{ $statAccents['pending'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-outfit">{{ number_format($stats['pending']) }}</p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">Pending</p>
            </div>
        </a>

        {{-- Confirmed --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'confirmed'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-3 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['confirmed'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center {{ $statAccents['confirmed'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit">{{ number_format($stats['confirmed']) }}</p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">Confirmed</p>
            </div>
        </a>

        {{-- Delivered --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'delivered'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-3 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['delivered'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center {{ $statAccents['delivered'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0z"/><path d="M19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10"/><path d="M21 16V9h-4l2-4h3v11z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-blue-600 dark:text-blue-400 leading-none tabular-nums font-outfit">{{ number_format($stats['delivered']) }}</p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">Delivered</p>
            </div>
        </a>

        {{-- Cancelled --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'cancelled'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-3 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['cancelled'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center {{ $statAccents['cancelled'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-outfit">{{ number_format($stats['cancelled']) }}</p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">Cancelled</p>
            </div>
        </a>

        {{-- Revenue --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'all'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-3 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['revenue'] }}"
           title="{{ __('messages.revenue_confirmed_only') }}">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center {{ $statAccents['revenue'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm sm:text-lg font-black text-violet-600 dark:text-violet-400 leading-none tabular-nums font-outfit">Ks {{ number_format($stats['revenue'], 0) }}</p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold">
                    {{ __('messages.pending_revenue') }}: Ks {{ number_format($stats['pendingRevenue'], 0) }}
                </p>
            </div>
        </a>
    </div>

    {{-- ============================================================
         UNIFIED ADMIN TOOLBAR
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', '')"
        searchPlaceholder="အော်ဒါနံပါတ်၊ ဖောက်သည်အမည် သို့မဟုတ် ဖုန်းနံပါတ် ရှာဖွေပါ..."
        :sort="request('sort', $sort)"
        :sortOptions="[
            'newest'      => 'အသစ်ဆုံး (Newest First)',
            'oldest'      => 'အဟောင်းဆုံး (Oldest First)',
            'amount_high' => 'ပမာဏ: များရာမှ နည်းရာ (High to Low)',
            'amount_low'  => 'ပမာဏ: နည်းရာမှ များရာ (Low to High)',
        ]"
        :filters="[
            'tab' => [
                'label' => 'Order Status',
                'options' => [
                    'all'       => 'အားလုံး (All Orders)',
                    'pending'   => 'Pending Contact (ဆက်သွယ်ရန်)',
                    'confirmed' => 'Confirmed (အတည်ပြုပြီး)',
                    'delivered' => 'Delivered (ပို့ဆောင်ပြီး)',
                    'cancelled' => 'Cancelled (ပယ်ဖျက်)',
                ],
            ],
            'pricing_type' => [
                'label' => 'Pricing Type',
                'options' => [
                    'retail'    => 'လက်လီ (Retail)',
                    'wholesale' => 'လက်ကား (Wholesale)',
                ],
            ],
            'contact_channel' => [
                'label' => 'Channel',
                'options' => [
                    'viber'    => 'Viber',
                    'telegram' => 'Telegram',
                    'phone'    => 'Direct Phone',
                ],
            ],
        ]"
        :viewMode="'table'"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$exportUrl"
        :totalCount="$orders->total()"
        :perPage="$orders->perPage()"
        :paginator="$orders"
        :showPagination="true"
    />

    {{-- ============================================================
         TABLE VIEW — spreadsheet grid with sticky header
         ============================================================ --}}
    <div id="order-table" x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        <th class="py-2.5 px-3 min-w-[120px]">{{ __('messages.table_order_no') ?: 'Order #' }}</th>
                        <th class="py-2.5 px-3 min-w-[160px]">{{ __('messages.table_customer') ?: 'Customer' }}</th>
                        <th class="py-2.5 px-3 min-w-[130px] hidden md:table-cell">{{ __('messages.channel') ?: 'Channel / Type' }}</th>
                        <th class="py-2.5 px-3 text-right min-w-[110px]">{{ __('messages.total_amount') ?: 'Total' }}</th>
                        <th class="py-2.5 px-3 min-w-[120px] hidden sm:table-cell">{{ __('messages.date') ?: 'Date' }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[140px]">{{ __('messages.status') ?: 'Status' }}</th>
                        <th class="py-2.5 px-3 text-center w-36">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/90 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse ($orders as $order)
                        @php
                            $amount = $order->agreed_amount !== null ? (float) $order->agreed_amount : (float) $order->total_amount;
                            $isWholesale = $order->pricing_type === 'wholesale';
                            $channel = strtolower($order->contact_channel);
                        @endphp
                        <tr class="hover:bg-violet-50/60 dark:hover:bg-violet-950/20 divide-x divide-slate-200/80 dark:divide-slate-800 transition-colors group">
                            {{-- Order Number --}}
                            <td class="py-2.5 px-3">
                                <a href="{{ route('store.admin.orders.show', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                                   class="font-mono font-bold text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/60 px-1.5 py-0.5 rounded border border-violet-200/60 dark:border-violet-800/60 text-[11px] hover:underline">
                                    #{{ $order->order_number }}
                                </a>
                                @if ($order->admin_note)
                                    <span class="text-[10px] text-slate-400 block mt-0.5 truncate" title="{{ $order->admin_note }}">📝 {{ Str::limit($order->admin_note, 20) }}</span>
                                @endif
                            </td>

                            {{-- Customer --}}
                            <td class="py-2.5 px-3">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs leading-snug">{{ $order->customer_name }}</div>
                                <div class="font-mono text-[11px] text-slate-400">📞 {{ $order->customer_phone }}</div>
                                @if ($order->contact_identifier)
                                    <div class="text-[10px] text-violet-500 font-semibold">{{ $order->contact_identifier }}</div>
                                @endif
                            </td>

                            {{-- Channel / Type --}}
                            <td class="py-2.5 px-3 hidden md:table-cell">
                                <div class="flex items-center gap-1 flex-wrap">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700/60">
                                        @if ($channel === 'viber') Viber
                                        @elseif ($channel === 'telegram') Telegram
                                        @else Phone @endif
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60' : 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/60' }}">
                                        {{ $order->pricing_type }}
                                    </span>
                                </div>
                            </td>

                            {{-- Amount --}}
                            <td class="py-2.5 px-3 text-right">
                                <div class="font-bold font-mono text-slate-900 dark:text-white tabular-nums text-xs sm:text-sm">
                                    Ks {{ number_format($amount, 0) }}
                                </div>
                                @if ($order->agreed_amount !== null)
                                    <span class="text-[9px] font-bold text-violet-500 block">{{ __('messages.order_agreed_amount') }}</span>
                                @endif
                            </td>

                            {{-- Date --}}
                            <td class="py-2.5 px-3 text-slate-400 text-[11px] whitespace-nowrap hidden sm:table-cell tabular-nums">
                                {{ $order->created_at->format('M d, Y h:i A') }}
                            </td>

                            {{-- Status --}}
                            <td class="py-2.5 px-3 text-center">
                                <div class="inline-flex items-center gap-1 flex-wrap justify-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase whitespace-nowrap
                                        {{ $order->status === 'confirmed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80' : '' }}
                                        {{ $order->status === 'pending_contact' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80' : '' }}
                                        {{ $order->status === 'delivered' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-200 dark:border-blue-800/80' : '' }}
                                        {{ $order->status === 'cancelled' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800/80' : '' }}">
                                        @if ($order->status === 'pending_contact')
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        @endif
                                        {{ $order->status === 'pending_contact' ? 'Pending' : ucfirst($order->status) }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase whitespace-nowrap {{ $order->payment_status === 'paid' ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border border-sky-200 dark:border-sky-800' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700' }}">
                                        {{ $order->payment_status }}
                                    </span>
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="py-2.5 px-2.5 text-center">
                                <div class="inline-flex items-center gap-1 justify-center">
                                    {{-- Quick Status Update --}}
                                    <form method="POST" action="{{ route('store.admin.orders.update_status', array_merge($storeRouteParams, ['order' => $order->id])) }}" class="inline-flex items-center gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="text-[10px] font-bold border rounded-lg px-1.5 py-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100 min-h-[28px] focus:outline-none focus:ring-2 focus:ring-violet-500/40">
                                            <option value="pending_contact" {{ $order->status === 'pending_contact' ? 'selected' : '' }}>Pending</option>
                                            <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirm</option>
                                            <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancel</option>
                                        </select>
                                        <button type="submit" title="Update Status" aria-label="Update"
                                                class="w-7 h-7 rounded border border-emerald-200 dark:border-emerald-800/80 inline-flex items-center justify-center text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            <span class="sr-only">Update</span>
                                        </button>
                                    </form>

                                    {{-- View Details --}}
                                    <a href="{{ route('store.admin.orders.show', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                                       title="{{ __('messages.view_details') }}"
                                       class="w-7 h-7 rounded border border-teal-200 dark:border-teal-800/80 inline-flex items-center justify-center text-teal-700 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/50 hover:bg-teal-100 dark:hover:bg-teal-900/50 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>

                                    {{-- Invoice --}}
                                    <a href="{{ route('store.admin.orders.invoice', array_merge($storeRouteParams, ['order' => $order->id])) }}" target="_blank"
                                       title="{{ __('messages.print_invoice') }}"
                                       class="w-7 h-7 rounded border border-sky-200 dark:border-sky-800/80 inline-flex items-center justify-center text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/50 hover:bg-sky-100 dark:hover:bg-sky-900/50 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>

                                    {{-- Delete (Alpine confirm) --}}
                                    @if (auth()->user()->isPlatformOwner() || auth()->user()->hasStoreRole($store->id, ['store_manager']))
                                        <button type="button" @click.stop="openDeleteConfirm({{ $order->id }})"
                                                title="Delete"
                                                class="w-7 h-7 rounded border border-rose-200 dark:border-rose-800/80 inline-flex items-center justify-center text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/50 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>
                                        </button>
                                        <form id="delete-form-{{ $order->id }}" method="POST" action="{{ route('store.admin.orders.destroy', array_merge($storeRouteParams, ['order' => $order->id])) }}" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 sm:p-12 text-center">
                                <div class="mx-auto max-w-sm">
                                    <div class="mx-auto mb-4 w-14 h-14 rounded-xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400 dark:text-slate-500 text-2xl shadow-inner">
                                        🛒
                                    </div>
                                    <p class="font-black text-slate-800 dark:text-slate-200 text-sm">အော်ဒါမှတ်တမ်း မရှိသေးပါ။</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">No order requests found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================
         CARD VIEW — mobile/tablet-friendly grid
         ============================================================ --}}
    <div x-show="viewMode === 'card'" class="w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
        @forelse ($orders as $order)
            @php
                $amount = $order->agreed_amount !== null ? (float) $order->agreed_amount : (float) $order->total_amount;
                $isWholesale = $order->pricing_type === 'wholesale';
                $channel = strtolower($order->contact_channel);
            @endphp
            <div class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg sm:rounded-xl p-4 shadow-xs transition hover:shadow-sm flex flex-col justify-between space-y-3 group hover:border-violet-300 dark:hover:border-violet-700/70">
                <div class="space-y-2.5">
                    {{-- Card Header --}}
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2.5">
                        <div class="min-w-0">
                            <a href="{{ route('store.admin.orders.show', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                               class="font-mono font-bold text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/60 px-1.5 py-0.5 rounded border border-violet-200/60 dark:border-violet-800/60 text-[11px] group-hover:underline">
                                #{{ $order->order_number }}
                            </a>
                            <span class="text-[11px] text-slate-400 block mt-0.5">{{ $order->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase
                                {{ $order->status === 'confirmed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80' : '' }}
                                {{ $order->status === 'pending_contact' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80' : '' }}
                                {{ $order->status === 'delivered' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-200 dark:border-blue-800/80' : '' }}
                                {{ $order->status === 'cancelled' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800/80' : '' }}">
                                {{ $order->status === 'pending_contact' ? 'Pending' : ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>

                    {{-- Customer Info --}}
                    <div>
                        <div class="font-bold text-sm text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                            <span>{{ $order->customer_name }}</span>
                            @if ($order->admin_note)
                                <span title="Admin Note: {{ $order->admin_note }}">📝</span>
                            @endif
                        </div>
                        <div class="font-mono text-xs text-slate-400">📞 {{ $order->customer_phone }}</div>
                        @if ($order->contact_identifier)
                            <div class="text-[11px] text-violet-500 font-semibold truncate">{{ $order->contact_identifier }}</div>
                        @endif
                    </div>

                    {{-- Meta Badges & Amount --}}
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Channel</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700/60">
                                @if ($channel === 'viber') Viber
                                @elseif ($channel === 'telegram') Telegram
                                @else Phone @endif
                            </span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">Type</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60' : 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/60' }}">
                                {{ $order->pricing_type }}
                            </span>
                        </div>
                    </div>

                    {{-- Amount --}}
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                        <span class="text-slate-400 block text-[10px] font-bold uppercase">Amount</span>
                        <span class="font-mono font-bold text-sm text-slate-900 dark:text-slate-100 tabular-nums block">
                            Ks {{ number_format($amount, 0) }}
                        </span>
                        @if ($order->agreed_amount !== null)
                            <span class="text-[9px] font-bold text-violet-500 block">{{ __('messages.order_agreed_amount') }}</span>
                        @endif
                    </div>
                </div>

                {{-- Card Actions --}}
                <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <form method="POST" action="{{ route('store.admin.orders.update_status', array_merge($storeRouteParams, ['order' => $order->id])) }}" class="inline-flex items-center gap-1">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="text-[10px] font-bold border rounded-lg px-1.5 py-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100 min-h-[28px] focus:outline-none focus:ring-2 focus:ring-violet-500/40">
                            <option value="pending_contact" {{ $order->status === 'pending_contact' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirm</option>
                            <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancel</option>
                        </select>
                        <button type="submit" title="Update"
                                class="w-7 h-7 rounded border border-emerald-200 dark:border-emerald-800/80 inline-flex items-center justify-center text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </form>

                    <div class="flex items-center gap-1.5">
                        <a href="{{ route('store.admin.orders.show', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                           class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 transition flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <span>အသေးစိတ်</span>
                        </a>
                        <a href="{{ route('store.admin.orders.invoice', array_merge($storeRouteParams, ['order' => $order->id])) }}" target="_blank"
                           title="{{ __('messages.print_invoice') }}"
                           class="w-7 h-7 rounded border border-sky-200 dark:border-sky-800/80 inline-flex items-center justify-center text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/50 hover:bg-sky-100 dark:hover:bg-sky-900/50 transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        </a>
                        @if (auth()->user()->isPlatformOwner() || auth()->user()->hasStoreRole($store->id, ['store_manager']))
                            <button type="button" @click.stop="openDeleteConfirm({{ $order->id }})"
                                    title="Delete"
                                    class="w-7 h-7 rounded border border-rose-200 dark:border-rose-800/80 inline-flex items-center justify-center text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/50 transition">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center">
                <div class="mx-auto max-w-sm">
                    <div class="mx-auto mb-4 w-14 h-14 rounded-xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-400 dark:text-slate-500 text-2xl shadow-inner">
                        🛒
                    </div>
                    <p class="font-black text-slate-800 dark:text-slate-200 text-sm">အော်ဒါမှတ်တမ်း မရှိသေးပါ။</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">No order requests found.</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- ============================================================
         DELETE CONFIRM MODAL (Alpine.js — no inline onsubmit)
         ============================================================ --}}
    <template x-if="deleteConfirmId !== null" x-teleport="body">
        <div x-show="deleteConfirmId !== null" x-cloak
             @click.self="closeDeleteConfirm()"
             @keydown.escape.window="closeDeleteConfirm()"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-2xl space-y-4 border border-slate-200 dark:border-slate-800"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 @click.stop>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 grid place-items-center shadow-inner">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">အော်ဒါ ဖျက်မည် (Delete Order)</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">ဒီလုပ်ဆောင်ချက်ကို ပြန်ပြင်၍ မရနိုင်ပါ။</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="closeDeleteConfirm()"
                            class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="button" @click="submitDelete(deleteConfirmId)"
                            class="px-4 py-2 rounded-lg text-xs font-black bg-rose-600 hover:bg-rose-700 text-white shadow-sm transition">
                        ဖျက်မည် (Delete)
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection
