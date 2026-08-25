@extends('layouts.admin.app')

@section('title', 'System Alert Center - ' . $store->name)

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12"
     x-data="{
        activeTab: '{{ $tab === 'all' ? 'low_stock' : $tab }}'
     }">

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-xl sm:text-2xl font-bold shadow-sm flex-shrink-0">
                🔔
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <a href="{{ route('store.admin.dashboard', $storeRouteParams) }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition">
                        Dashboard
                    </a>
                    <span>/</span>
                    <span class="text-amber-600 dark:text-amber-400">Maintenance</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span>စနစ် သတိပေးချက် ဗဟိုဌာန (System Alert Center)</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $store->name }} · စတော့နည်းပါးမှု၊ ရက်လွန်အကြွေး၊ မစစ်ရသေးသော အမှာစာများနှင့် Telegram သတိပေးချက်များ</p>
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <form method="POST" action="{{ route('store.admin.alerts.daily_summary', $storeRouteParams) }}">
                @csrf
                <button type="submit"
                        class="px-3.5 py-2.5 rounded-2xl text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-2 shadow-sm">
                    <span>📊</span>
                    <span>နေ့စဉ် အကျဉ်းချုပ် ထုတ်မည်</span>
                </button>
            </form>

            <a href="{{ route('store.admin.database.index', $storeRouteParams) }}"
               class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-black bg-amber-600 hover:bg-amber-500 text-white shadow-lg shadow-amber-500/20 transition flex items-center gap-2 active:scale-95">
                <span>🗄️</span>
                <span>Database Tools</span>
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

    @if (session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-3xl text-xs font-bold text-rose-700 dark:text-rose-300 flex items-center gap-2.5 shadow-sm">
            <span class="w-6 h-6 rounded-full bg-rose-100 dark:bg-rose-900 grid place-items-center text-rose-600 dark:text-rose-300 font-black">⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- 2. 4 Key Alert KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-3.5">
        {{-- Low Stock --}}
        <button type="button" @click="activeTab = 'low_stock'"
                class="text-left rounded-3xl bg-white dark:bg-slate-900 border p-4 shadow-sm transition hover:shadow-md"
                :class="activeTab === 'low_stock' ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-slate-200/90 dark:border-slate-800'">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-rose-600 dark:text-rose-400 truncate">Low Stock Alert</span>
                <span class="text-base">📦</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight">{{ number_format($stats['low_stock_count']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">စတော့ဖြည့်ရန် လိုအပ်သောပစ္စည်း</p>
        </button>

        {{-- Pending Orders --}}
        <button type="button" @click="activeTab = 'pending_orders'"
                class="text-left rounded-3xl bg-white dark:bg-slate-900 border p-4 shadow-sm transition hover:shadow-md"
                :class="activeTab === 'pending_orders' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200/90 dark:border-slate-800'">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-amber-600 dark:text-amber-400 truncate">Pending Requests</span>
                <span class="text-base">⏳</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight">{{ number_format($stats['pending_orders'] + $stats['pending_wholesale']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">ဆက်သွယ်စိစစ်ရန် ကျန်အမှာစာ</p>
        </button>

        {{-- Overdue Debt --}}
        <button type="button" @click="activeTab = 'overdue_debt'"
                class="text-left rounded-3xl bg-white dark:bg-slate-900 border p-4 shadow-sm transition hover:shadow-md"
                :class="activeTab === 'overdue_debt' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200/90 dark:border-slate-800'">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-blue-600 dark:text-blue-400 truncate">Overdue Debts</span>
                <span class="text-base">⏰</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-blue-600 dark:text-blue-400 font-mono tracking-tight">{{ number_format($stats['overdue_debt_count']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">ရက် ၃၀ ကျော် အကြွေးစာရင်းများ</p>
        </button>

        {{-- Security Alerts --}}
        <button type="button" @click="activeTab = 'security'"
                class="text-left rounded-3xl bg-white dark:bg-slate-900 border p-4 shadow-sm transition hover:shadow-md"
                :class="activeTab === 'security' ? 'border-purple-500 ring-2 ring-purple-500/20' : 'border-slate-200/90 dark:border-slate-800'">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-purple-600 dark:text-purple-400 truncate">Security Events</span>
                <span class="text-base">🛡️</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-purple-600 dark:text-purple-400 font-mono tracking-tight">{{ number_format($stats['security_warnings']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">PIN အမှား / သတိပေးချက်များ</p>
        </button>
    </div>

    {{-- 3. Tab Content Sections --}}

    {{-- Low Stock Section --}}
    <div x-show="activeTab === 'low_stock'" class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden space-y-0">
        <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>📦</span>
                    <span>စတော့နည်းပါးနေသော ပစ္စည်းများ (Low Stock Alert Items)</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">လက်ကျန် စတော့ ၃ ခု သို့မဟုတ် သတ်မှတ်အနည်းဆုံးအရေအတွက်အောက် ရောက်ရှိနေသော ပစ္စည်းများ</p>
            </div>
            <a href="{{ route('store.admin.products.index', $storeRouteParams) }}" class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline">
                ပစ္စည်းများ စီမံမည် →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">ကုန်ပစ္စည်း (Product)</th>
                        <th class="py-3 px-4">SKU Code</th>
                        <th class="py-3 px-4 text-center">လက်ကျန် (Current Stock)</th>
                        <th class="py-3 px-4 text-right">ရောင်းစျေး (Price)</th>
                        <th class="py-3 px-4 text-right">လုပ်ဆောင်ချက် (Action)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($lowStockProducts as $p)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 dark:text-slate-100">{{ $p['name'] }}</div>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-400">{{ $p['sku'] ?? '—' }}</td>
                            <td class="py-3 px-4 text-center font-mono">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-black {{ $p['stock_quantity'] <= 0 ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                                    {{ $p['stock_quantity'] }} ခု
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ number_format((float) $p['retail_price']) }} Ks
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('store.admin.products.edit', array_merge($storeRouteParams, ['product' => $p['id']])) }}"
                                   class="px-3 py-1 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 transition">
                                    Restock / Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-sm">
                                စတော့နည်းပါးသော ပစ္စည်းမရှိပါ။ စတော့အားလုံး လုံလောက်မှုရှိနေပါသည်။
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pending Orders & Wholesale Section --}}
    <div x-show="activeTab === 'pending_orders'" class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden space-y-0">
        <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>⏳</span>
                    <span>ဆက်သွယ်ရန် ကျန်ရှိနေသော အမှာစာများနှင့် လျှောက်လွှာများ</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Viber, Telegram နှင့် Online မှတစ်ဆင့် မှာယူထားသော Pending Orders များ</p>
            </div>
            <a href="{{ route('store.admin.orders.index', $storeRouteParams) }}" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline">
                အော်ဒါများ အားလုံးကြည့်မည် →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">အော်ဒါနံပါတ် (Order #)</th>
                        <th class="py-3 px-4">ဖောက်သည် (Customer)</th>
                        <th class="py-3 px-4">Channel</th>
                        <th class="py-3 px-4 text-right">ကျသင့်ငွေ (Total)</th>
                        <th class="py-3 px-4">ရက်စွဲ (Date)</th>
                        <th class="py-3 px-4 text-right">လုပ်ဆောင်ချက် (Action)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($pendingOrders as $ord)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                #{{ $ord->order_number }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 dark:text-slate-100">{{ $ord->customer_name }}</div>
                                <div class="font-mono text-slate-400 text-[11px]">📞 {{ $ord->customer_phone }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $ord->contact_channel }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ number_format((float) ($ord->agreed_amount ?? $ord->total_amount)) }} Ks
                            </td>
                            <td class="py-3 px-4 text-slate-400 font-mono">{{ $ord->created_at->diffForHumans() }}</td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('store.admin.orders.show', array_merge($storeRouteParams, ['order' => $ord->id])) }}"
                                   class="px-3 py-1 rounded-xl text-xs font-bold bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 transition">
                                    ဆက်သွယ်အတည်ပြုမည်
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 text-sm">
                                ဆက်သွယ်ရန် ကျန်ရှိသော အမှာစာ မရှိသေးပါ။
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Overdue Debt Section --}}
    <div x-show="activeTab === 'overdue_debt'" class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden space-y-0">
        <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>⏰</span>
                    <span>ရက်လွန် အကြွေးစာရင်းများ (Overdue Debt Accounts)</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">ရက် ၃၀ ထက် ကျော်လွန်နေသော ကုန်ပေးသွင်းသူ / ဖောက်သည် အကြွေးစာရင်းများ</p>
            </div>
            <a href="{{ route('store.admin.debt_aging.index', $storeRouteParams) }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                Aging Report အပြည့်အစုံ →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">အမည် (Name)</th>
                        <th class="py-3 px-4">ဖုန်းနံပါတ်</th>
                        <th class="py-3 px-4">အညွှန်း (Ref PO/Bill)</th>
                        <th class="py-3 px-4 text-center">ရက်လွန်ကာလ (Age)</th>
                        <th class="py-3 px-4 text-right">ကျန်ငွေပမာဏ (Remaining)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($overdueDebts as $deb)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4 font-bold text-slate-900 dark:text-slate-100">{{ $deb['name'] }}</td>
                            <td class="py-3 px-4 font-mono text-slate-400">{{ $deb['phone'] ?? '—' }}</td>
                            <td class="py-3 px-4 font-mono text-slate-500">{{ $deb['ref'] }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                    {{ $deb['days_overdue'] }} ရက်ကျော်
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ number_format($deb['amount']) }} Ks
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-sm">
                                ရက်လွန်နေသော အကြွေးစာရင်း မရှိသေးပါ။
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Security Section --}}
    <div x-show="activeTab === 'security'" class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden space-y-0">
        <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>🛡️</span>
                    <span>လုံခြုံရေး သတိပေးချက်များ (Security & Access Logs)</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">မန်နေဂျာ PIN အမှားရိုက်ထည့်မှုနှင့် ခွင့်ပြုချက် အပြောင်းအလဲများ</p>
            </div>
            <a href="{{ route('store.admin.audit-logs.index', $storeRouteParams) }}" class="text-xs font-bold text-purple-600 dark:text-purple-400 hover:underline">
                Audit Logs အားလုံး →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">အချိန် (Timestamp)</th>
                        <th class="py-3 px-4">လုပ်ဆောင်သူ (Actor)</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">IP / Terminal</th>
                        <th class="py-3 px-4 text-right">အသေးစိတ် (Metadata)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($securityAlerts as $sec)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4 font-mono text-slate-400">{{ $sec->created_at?->format('d M Y, h:i A') }}</td>
                            <td class="py-3 px-4 font-bold text-slate-900 dark:text-slate-100">{{ $sec->actor?->name ?? 'POS Terminal / Guest' }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                    {{ $sec->action }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-400">{{ $sec->ip_address ?? '—' }}</td>
                            <td class="py-3 px-4 text-right font-mono text-[11px] text-slate-500">
                                {{ is_array($sec->metadata) ? json_encode($sec->metadata) : $sec->metadata }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-sm">
                                မကြာသေးမီက လုံခြုံရေး သတိပေးချက် မရှိပါ။
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 4. Telegram Notification & Daily Summary Configuration --}}
    <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>📱</span>
                    <span>Telegram Bot သတိပေးချက် ချိတ်ဆက်မှု (Telegram Alert Notification Channel)</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">နေ့စဉ် အရောင်းအကျဉ်းချုပ်နှင့် အရေးကြီး သတိပေးချက်များကို Telegram သို့ အလိုအလျောက် ပေးပို့နိုင်ပါသည်</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-2">
            {{-- Test Ping Form --}}
            <form method="POST" action="{{ route('store.admin.alerts.test_ping', $storeRouteParams) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5 block">Telegram Bot Token</label>
                    <input type="password" name="telegram_bot_token" placeholder="bot123456789:ABCdefGhIJKlmNoPQRstuVWXyz..."
                           class="w-full text-xs border border-slate-200 dark:border-slate-700 rounded-2xl px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div>
                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5 block">Telegram Chat / Channel ID</label>
                    <input type="text" name="telegram_chat_id" placeholder="-100123456789 or @channel_username"
                           class="w-full text-xs border border-slate-200 dark:border-slate-700 rounded-2xl px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <button type="submit"
                        class="w-full py-2.5 rounded-2xl bg-amber-600 hover:bg-amber-500 text-white font-black text-xs shadow-lg shadow-amber-500/20 transition active:scale-95 flex items-center justify-center gap-2">
                    <span>🔔</span>
                    <span>Test Telegram Notification Ping (စမ်းသပ်ပေးပို့မည်)</span>
                </button>
            </form>

            {{-- Daily Summary Live Preview --}}
            <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 space-y-2">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Telegram Message Preview</div>
                <div class="bg-white dark:bg-slate-900 rounded-xl p-3 text-xs font-mono text-slate-800 dark:text-slate-200 space-y-1 shadow-sm leading-relaxed border border-slate-100 dark:border-slate-800">
                    <div class="font-bold text-amber-600">📊 [DataPOS Daily Business Summary]</div>
                    <div>🏪 Store: {{ $store->name }}</div>
                    <div>📅 Date: {{ now()->format('d M Y') }}</div>
                    <div class="pt-1 border-t border-slate-100 dark:border-slate-800">
                        💰 Confirmed Sales: <span class="font-bold text-emerald-600">{{ number_format($stats['today_sales']) }} Ks</span>
                    </div>
                    <div>🛒 Total Orders: {{ $stats['today_orders_count'] }} orders</div>
                    <div>⏳ Pending Contact: {{ $stats['pending_orders'] }} orders</div>
                    <div>⚠️ Low Stock Items: {{ $stats['low_stock_count'] }} items</div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
