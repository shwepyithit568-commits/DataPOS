@extends('layouts.admin.app')

@section('title', __('messages.sidebar_alerts') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2 sm:p-3 md:p-4')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-2.5 sm:space-y-3 pb-8"
     x-data="{
        activeTab: '{{ $tab }}'
     }">

    {{-- 1. Header (Compact standard) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 sm:p-4 shadow-sm">
        <div class="flex items-center gap-3 min-w-0">
            <span class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 grid place-items-center text-lg font-bold shrink-0 shadow-sm">
                🔔
            </span>
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 truncate">
                    {{ __('messages.sidebar_alerts') }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.alerts_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <form method="POST" action="{{ route('store.admin.alerts.daily_summary', $storeRouteParams) }}">
                @csrf
                <button type="submit"
                        class="h-9 px-3 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5 cursor-pointer">
                    <span>📊</span>
                    <span>{{ __('messages.alerts_daily_summary') }}</span>
                </button>
            </form>

            <a href="{{ route('store.admin.database.index', $storeRouteParams) }}"
               class="h-9 px-3 rounded-xl text-xs font-semibold bg-violet-600 hover:bg-violet-700 text-white shadow-sm transition inline-flex items-center gap-1.5">
                <span>🗄️</span>
                <span>{{ __('messages.sidebar_database') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-medium text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 grid place-items-center text-xs font-bold">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs font-medium text-rose-800 dark:text-rose-200 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300 grid place-items-center text-xs font-bold">⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- 2. 4 Key Alert KPI Metric Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
        {{-- Low Stock KPI --}}
        <button type="button" @click="activeTab = 'low_stock'"
                class="text-left rounded-xl bg-white dark:bg-slate-900 border p-3 shadow-sm transition hover:border-rose-400 focus:outline-none cursor-pointer"
                :class="activeTab === 'low_stock' ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/20 dark:bg-rose-950/10' : 'border-slate-200/90 dark:border-slate-800'">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-rose-600 dark:text-rose-400">{{ __('messages.alerts_kpi_low_stock') }}</span>
                <span>📦</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-rose-600 dark:text-rose-400 tabular-nums">
                {{ number_format($stats['low_stock_count']) }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ __('messages.alerts_kpi_low_stock_sub') }}</p>
        </button>

        {{-- Pending Requests KPI --}}
        <button type="button" @click="activeTab = 'pending_orders'"
                class="text-left rounded-xl bg-white dark:bg-slate-900 border p-3 shadow-sm transition hover:border-amber-400 focus:outline-none cursor-pointer"
                :class="activeTab === 'pending_orders' ? 'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/20 dark:bg-amber-950/10' : 'border-slate-200/90 dark:border-slate-800'">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-amber-600 dark:text-amber-400">{{ __('messages.alerts_kpi_pending') }}</span>
                <span>⏳</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-amber-600 dark:text-amber-400 tabular-nums">
                {{ number_format($stats['pending_orders'] + $stats['pending_wholesale']) }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ __('messages.alerts_kpi_pending_sub', ['orders' => $stats['pending_orders'], 'wholesale' => $stats['pending_wholesale']]) }}</p>
        </button>

        {{-- Overdue Debts KPI --}}
        <button type="button" @click="activeTab = 'overdue_debt'"
                class="text-left rounded-xl bg-white dark:bg-slate-900 border p-3 shadow-sm transition hover:border-blue-400 focus:outline-none cursor-pointer"
                :class="activeTab === 'overdue_debt' ? 'border-blue-500 ring-2 ring-blue-500/20 bg-blue-50/20 dark:bg-blue-950/10' : 'border-slate-200/90 dark:border-slate-800'">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-blue-600 dark:text-blue-400">{{ __('messages.alerts_kpi_overdue') }}</span>
                <span>⏰</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-blue-600 dark:text-blue-400 tabular-nums">
                {{ number_format($stats['overdue_debt_count']) }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ __('messages.alerts_kpi_overdue_sub') }}</p>
        </button>

        {{-- Security Events KPI --}}
        <button type="button" @click="activeTab = 'security'"
                class="text-left rounded-xl bg-white dark:bg-slate-900 border p-3 shadow-sm transition hover:border-purple-400 focus:outline-none cursor-pointer"
                :class="activeTab === 'security' ? 'border-purple-500 ring-2 ring-purple-500/20 bg-purple-50/20 dark:bg-purple-950/10' : 'border-slate-200/90 dark:border-slate-800'">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-purple-600 dark:text-purple-400">{{ __('messages.alerts_kpi_security') }}</span>
                <span>🛡️</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-purple-600 dark:text-purple-400 tabular-nums">
                {{ number_format($stats['security_warnings']) }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ __('messages.alerts_kpi_security_sub') }}</p>
        </button>
    </div>

    {{-- 3. Tab Filter Navigation --}}
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs border-b border-slate-200 dark:border-slate-800">
        <button type="button" @click="activeTab = 'low_stock'"
                class="px-3 py-2 rounded-xl font-semibold transition shrink-0 inline-flex items-center gap-1.5 cursor-pointer"
                :class="activeTab === 'low_stock' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
            <span>📦 {{ __('messages.alerts_tab_low_stock') }}</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                  :class="activeTab === 'low_stock' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'">
                {{ count($lowStockProducts) }}
            </span>
        </button>

        <button type="button" @click="activeTab = 'pending_orders'"
                class="px-3 py-2 rounded-xl font-semibold transition shrink-0 inline-flex items-center gap-1.5 cursor-pointer"
                :class="activeTab === 'pending_orders' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
            <span>⏳ {{ __('messages.alerts_tab_pending') }}</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                  :class="activeTab === 'pending_orders' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'">
                {{ count($pendingOrders) + count($pendingWholesale) }}
            </span>
        </button>

        <button type="button" @click="activeTab = 'overdue_debt'"
                class="px-3 py-2 rounded-xl font-semibold transition shrink-0 inline-flex items-center gap-1.5 cursor-pointer"
                :class="activeTab === 'overdue_debt' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
            <span>⏰ {{ __('messages.alerts_tab_overdue') }}</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                  :class="activeTab === 'overdue_debt' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'">
                {{ count($overdueDebts) }}
            </span>
        </button>

        <button type="button" @click="activeTab = 'security'"
                class="px-3 py-2 rounded-xl font-semibold transition shrink-0 inline-flex items-center gap-1.5 cursor-pointer"
                :class="activeTab === 'security' ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
            <span>🛡️ {{ __('messages.alerts_tab_security') }}</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                  :class="activeTab === 'security' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'">
                {{ count($securityAlerts) }}
            </span>
        </button>

        <button type="button" @click="activeTab = 'telegram'"
                class="px-3 py-2 rounded-xl font-semibold transition shrink-0 inline-flex items-center gap-1.5 cursor-pointer"
                :class="activeTab === 'telegram' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
            <span>📱 {{ __('messages.alerts_tab_telegram') }}</span>
        </button>
    </div>

    {{-- 4. Tab 1: Low Stock Alert Table --}}
    <div x-show="activeTab === 'low_stock'" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div class="min-w-0">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>📦 {{ __('messages.alerts_low_stock_title') }}</span>
                </h3>
                <p class="text-xs text-slate-400">{{ __('messages.alerts_low_stock_sub') }}</p>
            </div>
            <a href="{{ route('store.admin.products.index', $storeRouteParams) }}" class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline shrink-0">
                {{ __('messages.alerts_manage_products') }} →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_product_name') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_sku_category') }}</th>
                        <th class="py-2.5 px-3.5 text-center">{{ __('messages.alerts_th_current_stock') }}</th>
                        <th class="py-2.5 px-3.5 text-center">{{ __('messages.alerts_th_reorder_level') }}</th>
                        <th class="py-2.5 px-3.5 text-right">{{ __('messages.alerts_th_retail_price') }}</th>
                        <th class="py-2.5 px-3.5 text-right">{{ __('messages.alerts_th_action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse ($lowStockProducts as $p)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-2.5 px-3.5">
                                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $p['name'] }}</div>
                            </td>
                            <td class="py-2.5 px-3.5">
                                <div class="font-mono text-slate-500 dark:text-slate-400">{{ $p['sku'] ?: '—' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $p['category'] }}</div>
                            </td>
                            <td class="py-2.5 px-3.5 text-center font-mono">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $p['stock_quantity'] <= 0 ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300' }}">
                                    {{ $p['stock_quantity'] }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3.5 text-center font-mono text-slate-500 dark:text-slate-400">
                                {{ $p['reorder_level'] }}
                            </td>
                            <td class="py-2.5 px-3.5 text-right font-mono font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                {{ number_format((float) $p['retail_price']) }} Ks
                            </td>
                            <td class="py-2.5 px-3.5 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('pos.purchases.create', array_merge($storeRouteParams, ['product_id' => $p['id']])) }}"
                                       class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 transition">
                                        + {{ __('messages.alerts_btn_purchase') }}
                                    </a>
                                    <a href="{{ route('store.admin.products.edit', array_merge($storeRouteParams, ['product' => $p['id']])) }}"
                                       class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300 transition">
                                        {{ __('messages.edit') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-400 text-xs">
                                {{ __('messages.alerts_no_low_stock') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 5. Tab 2: Pending Orders & Wholesale Applications --}}
    <div x-show="activeTab === 'pending_orders'" class="space-y-3">
        {{-- Pending Online Orders --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div class="min-w-0">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <span>🛒 {{ __('messages.alerts_pending_orders_title') }}</span>
                    </h3>
                    <p class="text-xs text-slate-400">{{ __('messages.alerts_pending_orders_sub') }}</p>
                </div>
                <a href="{{ route('store.admin.orders.index', $storeRouteParams) }}" class="text-xs font-semibold text-amber-600 dark:text-amber-400 hover:underline shrink-0">
                    {{ __('messages.alerts_view_all_orders') }} →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                            <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_order_number') }}</th>
                            <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_customer') }}</th>
                            <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_channel') }}</th>
                            <th class="py-2.5 px-3.5 text-right">{{ __('messages.alerts_th_total_amount') }}</th>
                            <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_time') }}</th>
                            <th class="py-2.5 px-3.5 text-right">{{ __('messages.alerts_th_action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @forelse ($pendingOrders as $ord)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                <td class="py-2.5 px-3.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                    #{{ $ord->order_number }}
                                </td>
                                <td class="py-2.5 px-3.5">
                                    <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $ord->customer_name }}</div>
                                    <div class="font-mono text-slate-400 text-[11px]">📞 {{ $ord->customer_phone }}</div>
                                </td>
                                <td class="py-2.5 px-3.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $ord->contact_channel }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3.5 text-right font-mono font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                    {{ number_format((float) ($ord->agreed_amount ?? $ord->total_amount)) }} Ks
                                </td>
                                <td class="py-2.5 px-3.5 text-slate-400 font-mono">{{ $ord->created_at?->diffForHumans() }}</td>
                                <td class="py-2.5 px-3.5 text-right">
                                    <a href="{{ route('store.admin.orders.show', array_merge($storeRouteParams, ['order' => $ord->id])) }}"
                                       class="px-3 py-1 rounded-lg text-xs font-semibold bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 transition">
                                        {{ __('messages.alerts_btn_confirm_order') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                    {{ __('messages.alerts_no_pending_orders') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pending Wholesale Applications --}}
        @if (count($pendingWholesale) > 0)
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <span>🏷️ {{ __('messages.alerts_pending_wholesale_title') }}</span>
                    </h3>
                    <a href="{{ route('store.admin.wholesale.applications.index', $storeRouteParams) }}" class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                        {{ __('messages.alerts_view_all_applications') }} →
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                                <th class="py-2.5 px-3.5">{{ __('messages.alerts_wholesale_applicant') }}</th>
                                <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_phone') }}</th>
                                <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_city_address') }}</th>
                                <th class="py-2.5 px-3.5 text-right">{{ __('messages.alerts_th_action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @foreach ($pendingWholesale as $app)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-2.5 px-3.5 font-semibold text-slate-900 dark:text-slate-100">{{ $app->business_name ?? $app->contact_person }}</td>
                                    <td class="py-2.5 px-3.5 font-mono text-slate-500">{{ $app->phone }}</td>
                                    <td class="py-2.5 px-3.5 text-slate-500">{{ $app->city ?? '—' }}</td>
                                    <td class="py-2.5 px-3.5 text-right">
                                        <a href="{{ route('store.admin.wholesale.applications.show', array_merge($storeRouteParams, ['application' => $app->id])) }}"
                                           class="px-3 py-1 rounded-lg text-xs font-semibold bg-teal-50 hover:bg-teal-100 text-teal-700 dark:bg-teal-950/50 dark:text-teal-300 transition">
                                            {{ __('messages.alerts_btn_verify_wholesale') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- 6. Tab 3: Overdue Debts --}}
    <div x-show="activeTab === 'overdue_debt'" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div class="min-w-0">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>⏰ {{ __('messages.alerts_overdue_debts_title') }}</span>
                </h3>
                <p class="text-xs text-slate-400">{{ __('messages.alerts_overdue_debts_sub') }}</p>
            </div>
            <a href="{{ route('store.admin.debt_aging.index', $storeRouteParams) }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline shrink-0">
                {{ __('messages.sidebar_debt_aging') }} →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_type') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_name') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_phone') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_ref') }}</th>
                        <th class="py-2.5 px-3.5 text-center">{{ __('messages.alerts_overdue_period') }}</th>
                        <th class="py-2.5 px-3.5 text-right">{{ __('messages.alerts_th_remaining_amount') }}</th>
                        <th class="py-2.5 px-3.5 text-right">{{ __('messages.alerts_th_action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse ($overdueDebts as $deb)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-2.5 px-3.5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $deb['type'] === 'supplier' ? 'bg-orange-100 text-orange-800 dark:bg-orange-950/60 dark:text-orange-300' : 'bg-teal-100 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300' }}">
                                    {{ $deb['type_label'] }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3.5 font-semibold text-slate-900 dark:text-slate-100">{{ $deb['name'] }}</td>
                            <td class="py-2.5 px-3.5 font-mono text-slate-400">{{ $deb['phone'] ?: '—' }}</td>
                            <td class="py-2.5 px-3.5 font-mono text-slate-500">{{ $deb['ref'] }}</td>
                            <td class="py-2.5 px-3.5 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300">
                                    {{ __('messages.alerts_days_overdue', ['days' => $deb['days_overdue']]) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3.5 text-right font-mono font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                {{ number_format($deb['amount']) }} Ks
                            </td>
                            <td class="py-2.5 px-3.5 text-right">
                                <a href="{{ $deb['action_url'] }}"
                                   class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 transition">
                                    {{ __('messages.alerts_btn_view_ledger') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                {{ __('messages.alerts_no_overdue_debts') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 7. Tab 4: Security Events Logs --}}
    <div x-show="activeTab === 'security'" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div class="min-w-0">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>🛡️ {{ __('messages.alerts_security_title') }}</span>
                </h3>
                <p class="text-xs text-slate-400">{{ __('messages.alerts_security_sub') }}</p>
            </div>
            <a href="{{ route('store.admin.audit-logs.index', $storeRouteParams) }}" class="text-xs font-semibold text-purple-600 dark:text-purple-400 hover:underline shrink-0">
                {{ __('messages.sidebar_audit_logs') }} →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_time') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_actor') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_action') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.alerts_th_ip') }}</th>
                        <th class="py-2.5 px-3.5 text-right">{{ __('messages.alerts_th_metadata') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse ($securityAlerts as $sec)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-2.5 px-3.5 font-mono text-slate-400">{{ $sec->created_at?->format('d M Y, h:i A') }}</td>
                            <td class="py-2.5 px-3.5 font-semibold text-slate-900 dark:text-slate-100">{{ $sec->actor?->name ?? __('messages.alerts_pos_guest') }}</td>
                            <td class="py-2.5 px-3.5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300">
                                    {{ $sec->action }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3.5 font-mono text-slate-400">{{ $sec->ip_address ?: '127.0.0.1' }}</td>
                            <td class="py-2.5 px-3.5 text-right font-mono text-[11px] text-slate-500">
                                {{ is_array($sec->metadata) ? json_encode($sec->metadata) : ($sec->metadata ?: '—') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                                {{ __('messages.alerts_no_security') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 8. Tab 5: Telegram Bot Notification Channel Setup --}}
    <div x-show="activeTab === 'telegram'" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-4">
        <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span class="text-base">📱</span>
                    <span>{{ __('messages.alerts_tg_channel_title') }}</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('messages.alerts_tg_channel_sub') }}</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/60 w-fit">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span>
                <span>{{ __('messages.alerts_tg_bot_api') }}</span>
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 pt-1">
            {{-- Test Ping Form --}}
            <form method="POST" action="{{ route('store.admin.alerts.test_ping', $storeRouteParams) }}" class="space-y-3.5">
                @csrf
                <div>
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5 block">
                        {{ __('messages.alerts_tg_token') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" name="telegram_bot_token"
                           placeholder="bot123456789:ABCdefGhIJKlmNoPQRstuVWXyz..."
                           class="w-full text-xs font-mono border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 bg-slate-50/80 dark:bg-slate-800/90 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white dark:focus:bg-slate-800 transition">
                    <p class="text-[10px] text-slate-400 mt-1">{{ __('messages.alerts_tg_token_hint') }}</p>
                </div>

                <div>
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5 block">
                        {{ __('messages.alerts_tg_chat_id') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="telegram_chat_id"
                           placeholder="-100123456789 သို့မဟုတ် @channel_username"
                           class="w-full text-xs font-mono border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 bg-slate-50/80 dark:bg-slate-800/90 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white dark:focus:bg-slate-800 transition">
                    <p class="text-[10px] text-slate-400 mt-1">{{ __('messages.alerts_tg_chat_id_hint') }}</p>
                </div>

                <button type="submit"
                        class="w-full py-2.5 px-4 rounded-xl bg-amber-600 hover:bg-amber-500 dark:bg-amber-600 dark:hover:bg-amber-500 text-white font-bold text-xs shadow-md shadow-amber-500/15 transition flex items-center justify-center gap-2 active:scale-95 cursor-pointer">
                    <span>🔔</span>
                    <span>{{ __('messages.alerts_tg_test_ping') }}</span>
                </button>
            </form>

            {{-- Daily Summary Live Preview --}}
            <div class="bg-slate-50/90 dark:bg-slate-800/50 rounded-xl p-4 border border-slate-200 dark:border-slate-700/80 space-y-2.5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">
                        <span>{{ __('messages.alerts_tg_preview') }}</span>
                        <span class="text-slate-400 text-[10px] font-mono">{{ __('messages.alerts_tg_html') }}</span>
                    </div>
                    <div class="bg-white dark:bg-slate-950 rounded-xl p-3.5 text-xs font-mono text-slate-800 dark:text-slate-200 space-y-1.5 shadow-sm leading-relaxed border border-slate-200/80 dark:border-slate-800">
                        <div class="font-bold text-amber-600 dark:text-amber-400 text-[13px]">📊 [DataPOS Daily Business Summary]</div>
                        <div class="text-slate-600 dark:text-slate-300">🏪 <b>{{ __('messages.alerts_tg_store') }}</b> {{ $store->name }}</div>
                        <div class="text-slate-500 dark:text-slate-400 text-[11px]">📅 <b>{{ __('messages.alerts_tg_date') }}</b> {{ now()->format('d M Y, h:i A') }}</div>
                        <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800/80 space-y-1 text-[12px]">
                            <div>💰 <b>{{ __('messages.alerts_tg_confirmed_sales') }}</b> <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($stats['today_sales']) }} Ks</span></div>
                            <div>🛒 <b>{{ __('messages.alerts_tg_total_orders') }}</b> <span class="font-bold text-slate-900 dark:text-slate-100">{{ $stats['today_orders_count'] }}</span> orders</div>
                            <div>⏳ <b>{{ __('messages.alerts_tg_pending_contact') }}</b> <span class="font-bold text-amber-600 dark:text-amber-400">{{ $stats['pending_orders'] }}</span> orders</div>
                            <div>⚠️ <b>{{ __('messages.alerts_tg_low_stock_items') }}</b> <span class="font-bold text-rose-600 dark:text-rose-400">{{ $stats['low_stock_count'] }}</span> items</div>
                        </div>
                        <div class="pt-1 text-[10px] text-slate-400 italic">{{ __('messages.alerts_tg_generated') }}</div>
                    </div>
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/50 dark:border-amber-900/40 rounded-lg p-2.5 flex items-center gap-2">
                    <span class="text-amber-600 dark:text-amber-400 text-sm">💡</span>
                    <span>{{ __('messages.alerts_tg_preview_hint') }}</span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
