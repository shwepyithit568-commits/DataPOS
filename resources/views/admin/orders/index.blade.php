@extends('layouts.admin.app')

@section('title', __('messages.sidebar_orders', [], 'en') ? __('messages.sidebar_orders') . ' - ' . $store->name : 'Orders - ' . $store->name)

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $exportUrl = route('store.admin.orders.export', array_merge($storeRouteParams, request()->only(['search', 'sort', 'tab', 'status', 'pricing_type', 'contact_channel'])));
@endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12"
     x-data="{
        viewMode: localStorage.getItem('admin_orders_view_mode') || 'table',
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_orders_view_mode', $event.detail)">

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-xl sm:text-2xl font-bold shadow-sm flex-shrink-0">
                🛒
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <a href="{{ route('store.admin.dashboard', $storeRouteParams) }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition">
                        {{ __('messages.admin_dashboard') }}
                    </a>
                    <span>/</span>
                    <span class="text-amber-600 dark:text-amber-400">Online & Contact Orders</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span>အော်ဒါ အမှာစာများ (Order Requests)</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $store->name }} · Viber, Telegram နှင့် ဖုန်းဖြင့် မှာယူထားသော အော်ဒါများ</p>
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <a href="{{ $exportUrl }}"
               class="px-3.5 py-2.5 rounded-2xl text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-2 shadow-sm">
                <span>📊</span>
                <span>Export CSV</span>
            </a>
            <a href="{{ route('pos.index', $storeRouteParams) }}" target="_blank"
               class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-black bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-500/20 transition flex items-center gap-2 active:scale-95">
                <span>⚡</span>
                <span>POS ကောင်တာဖွင့်မည်</span>
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-3xl text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <span class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900 grid place-items-center text-emerald-600 dark:text-emerald-300 font-black">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-3xl text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1 shadow-sm">
            <div class="font-black flex items-center gap-1.5">
                <span>⚠️</span>
                <span>အမှားအယွင်း ရှိနေပါသည်:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="ml-5">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- 2. 5 Key Order KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-3.5">
        {{-- Pending --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'pending'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $tab === 'pending' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-amber-600 dark:text-amber-400 truncate">Pending</span>
                <span class="text-base">⏳</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight">{{ number_format($stats['pending']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">ဆက်သွယ်ရန် ကျန်ရှိ</p>
        </a>

        {{-- Confirmed --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'confirmed'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $tab === 'confirmed' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 truncate">Confirmed</span>
                <span class="text-base">✅</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">{{ number_format($stats['confirmed']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">အတည်ပြုပြီး အော်ဒါ</p>
        </a>

        {{-- Delivered --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'delivered'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $tab === 'delivered' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-blue-600 dark:text-blue-400 truncate">Delivered</span>
                <span class="text-base">🚚</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-blue-600 dark:text-blue-400 font-mono tracking-tight">{{ number_format($stats['delivered']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">ပို့ဆောင်ပြီးစီး</p>
        </a>

        {{-- Cancelled --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'cancelled'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $tab === 'cancelled' ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-rose-600 dark:text-rose-400 truncate">Cancelled</span>
                <span class="text-base">❌</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight">{{ number_format($stats['cancelled']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">ပယ်ဖျက်ထားသည်</p>
        </a>

        {{-- Revenue --}}
        <a href="{{ route('store.admin.orders.index', array_merge($storeRouteParams, ['tab' => 'all'])) }}"
           class="col-span-2 sm:col-span-1 rounded-3xl bg-white dark:bg-slate-900 border {{ $tab === 'all' ? 'border-violet-500 ring-2 ring-violet-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 shadow-sm transition hover:shadow-md"
           title="{{ __('messages.revenue_confirmed_only') }}">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-violet-600 dark:text-violet-400 truncate">Revenue</span>
                <span class="text-base">💰</span>
            </div>
            <h3 class="text-lg sm:text-xl font-black text-violet-600 dark:text-violet-400 font-mono tracking-tight">Ks {{ number_format($stats['revenue'], 0) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5 truncate">{{ __('messages.pending_revenue') }}: Ks {{ number_format($stats['pendingRevenue'], 0) }}</p>
        </a>
    </div>

    {{-- 3. Unified Admin Toolbar --}}
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
                    'viber'    => 'Viber 🟣',
                    'telegram' => 'Telegram 🔵',
                    'phone'    => 'Direct Phone 🟢',
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

    {{-- 4. Card View (Alpine Toggle) --}}
    <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($orders as $order)
            @php
                $amount = $order->agreed_amount !== null ? (float) $order->agreed_amount : (float) $order->total_amount;
                $isWholesale = $order->pricing_type === 'wholesale';
                $channel = strtolower($order->contact_channel);
            @endphp
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4 group">
                <div class="space-y-3">
                    {{-- Card Header --}}
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <a href="{{ route('store.admin.orders.show', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                               class="font-mono font-black text-sm text-violet-600 dark:text-violet-400 group-hover:underline">
                                #{{ $order->order_number }}
                            </a>
                            <span class="text-[11px] text-slate-400 block">{{ $order->created_at->format('M d, Y h:i A') }}</span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase
                                {{ $order->status === 'confirmed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : '' }}
                                {{ $order->status === 'pending_contact' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}
                                {{ $order->status === 'delivered' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : '' }}
                                {{ $order->status === 'cancelled' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : '' }}">
                                {{ $order->status === 'pending_contact' ? 'Pending' : $order->status }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $order->payment_status === 'paid' ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                {{ $order->payment_status }}
                            </span>
                        </div>
                    </div>

                    {{-- Customer Info --}}
                    <div>
                        <div class="font-black text-sm text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
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
                    <div class="flex items-center justify-between text-xs pt-1">
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                @if ($channel === 'viber') 🟣 Viber
                                @elseif ($channel === 'telegram') 🔵 Telegram
                                @else 🟢 Phone @endif
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' }}">
                                {{ $order->pricing_type }}
                            </span>
                        </div>

                        <div class="text-right">
                            <span class="font-mono font-black text-sm text-slate-900 dark:text-slate-100 block">
                                {{ number_format($amount, 0) }} Ks
                            </span>
                            @if ($order->agreed_amount !== null)
                                <span class="text-[9px] font-bold text-violet-500 block">Agreed Price</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card Actions --}}
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <form method="POST" action="{{ route('store.admin.orders.update_status', array_merge($storeRouteParams, ['order' => $order->id])) }}" class="inline-flex items-center gap-1">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="text-[11px] font-bold border rounded-xl px-2 py-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100">
                            <option value="pending_contact" {{ $order->status === 'pending_contact' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirm</option>
                            <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancel</option>
                        </select>
                        <button type="submit" class="px-2 py-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] shadow-sm">
                            ✓
                        </button>
                    </form>

                    <div class="flex items-center gap-1.5">
                        <a href="{{ route('store.admin.orders.show', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                           class="px-2.5 py-1 rounded-xl text-xs font-bold bg-violet-50 hover:bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 transition">
                            View
                        </a>
                        <a href="{{ route('store.admin.orders.invoice', array_merge($storeRouteParams, ['order' => $order->id])) }}" target="_blank"
                           class="px-2 py-1 rounded-xl text-xs font-bold bg-sky-50 hover:bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 transition" title="Print Invoice">
                            🧾
                        </a>
                        @if (auth()->user()->isPlatformOwner() || auth()->user()->hasStoreRole($store->id, ['store_manager']))
                            <form method="POST" action="{{ route('store.admin.orders.destroy', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                                  onsubmit="return confirm('Delete this order?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2 py-1 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/60 transition" title="Delete">
                                    🗑
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                အော်ဒါမှတ်တမ်း မရှိသေးပါ။ (No order requests found.)
            </div>
        @endforelse
    </div>

    {{-- 5. Table View (Alpine Toggle) --}}
    <div x-show="viewMode === 'table'" class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4">အော်ဒါနံပါတ် (Order #)</th>
                        <th class="py-3.5 px-4">ဖောက်သည် (Customer)</th>
                        <th class="py-3.5 px-4">Channel / Type</th>
                        <th class="py-3.5 px-4 text-right">ကျသင့်ငွေ (Total)</th>
                        <th class="py-3.5 px-4">ရက်စွဲ (Date)</th>
                        <th class="py-3.5 px-4 text-center">အခြေအနေ (Status)</th>
                        <th class="py-3.5 px-4 text-right">လုပ်ဆောင်ချက် (Actions)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($orders as $order)
                        @php
                            $amount = $order->agreed_amount !== null ? (float) $order->agreed_amount : (float) $order->total_amount;
                            $isWholesale = $order->pricing_type === 'wholesale';
                            $channel = strtolower($order->contact_channel);
                        @endphp
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4">
                                <a href="{{ route('store.admin.orders.show', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                                   class="font-mono font-black text-violet-600 dark:text-violet-400 hover:underline block text-xs">
                                    #{{ $order->order_number }}
                                </a>
                                @if ($order->admin_note)
                                    <span class="text-[10px] text-slate-400 truncate block" title="{{ $order->admin_note }}">📝 {{ Str::limit($order->admin_note, 20) }}</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">{{ $order->customer_name }}</div>
                                <div class="font-mono text-[11px] text-slate-400">📞 {{ $order->customer_phone }}</div>
                                @if ($order->contact_identifier)
                                    <div class="text-[10px] text-violet-500 font-semibold">{{ $order->contact_identifier }}</div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1 flex-wrap">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        @if ($channel === 'viber') 🟣 Viber
                                        @elseif ($channel === 'telegram') 🔵 TG
                                        @else 🟢 Phone @endif
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' }}">
                                        {{ $order->pricing_type }}
                                    </span>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono">
                                <span class="font-black text-slate-900 dark:text-slate-100 text-xs block">
                                    {{ number_format($amount, 0) }} Ks
                                </span>
                                @if ($order->agreed_amount !== null)
                                    <span class="text-[9px] font-bold text-violet-500 block">Agreed</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-slate-400 text-[11px] whitespace-nowrap">
                                {{ $order->created_at->format('M d, Y h:i A') }}
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase whitespace-nowrap
                                        {{ $order->status === 'confirmed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : '' }}
                                        {{ $order->status === 'pending_contact' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}
                                        {{ $order->status === 'delivered' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : '' }}
                                        {{ $order->status === 'cancelled' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : '' }}">
                                        {{ $order->status === 'pending_contact' ? 'Pending' : $order->status }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase whitespace-nowrap {{ $order->payment_status === 'paid' ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                        {{ $order->payment_status }}
                                    </span>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <form method="POST" action="{{ route('store.admin.orders.update_status', array_merge($storeRouteParams, ['order' => $order->id])) }}" class="inline-flex items-center gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="text-[10px] font-bold border rounded-xl px-2 py-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100">
                                            <option value="pending_contact" {{ $order->status === 'pending_contact' ? 'selected' : '' }}>Pending</option>
                                            <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirm</option>
                                            <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancel</option>
                                        </select>
                                        <button type="submit" class="px-2 py-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] shadow-sm">
                                            Update
                                        </button>
                                    </form>

                                    <a href="{{ route('store.admin.orders.show', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                                       class="px-2.5 py-1 rounded-xl text-xs font-bold bg-violet-50 hover:bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 transition">
                                        View
                                    </a>

                                    <a href="{{ route('store.admin.orders.invoice', array_merge($storeRouteParams, ['order' => $order->id])) }}" target="_blank"
                                       class="px-2 py-1 rounded-xl text-xs font-bold bg-sky-50 hover:bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 transition" title="Print Invoice">
                                        🧾
                                    </a>

                                    @if (auth()->user()->isPlatformOwner() || auth()->user()->hasStoreRole($store->id, ['store_manager']))
                                        <form method="POST" action="{{ route('store.admin.orders.destroy', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                                              onsubmit="return confirm('Delete this order?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/60 transition" title="Delete">
                                                🗑
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                                အော်ဒါမှတ်တမ်း မရှိသေးပါ။ (No order requests found.)
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $orders->links() }}
    </div>

</div>
@endsection
