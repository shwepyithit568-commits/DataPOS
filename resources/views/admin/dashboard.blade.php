@extends('layouts.admin.app')

@section('title', __('messages.admin_dashboard') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2 sm:p-4')

@section('content')
<div class="w-full space-y-4 sm:space-y-5">

    {{-- ============================================================
         PAGE HEADER: CLEAN & COMPACT
         ============================================================ --}}
    <div class="admin-page-header">
        <div class="min-w-0">
            <h1 class="admin-page-title flex items-center gap-2">
                <span>{{ __('messages.admin_dashboard') }}</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 font-mono">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    ONLINE
                </span>
            </h1>
            <p class="admin-page-sub mt-0.5">
                {{ $store->name }} · {{ __('messages.dashboard_overview_sub') }}
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0 text-xs font-mono text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-200/90 dark:border-slate-800 shadow-sm">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>{{ now()->format('D, d M Y') }}</span>
        </div>
    </div>

    {{-- ============================================================
         POS LIVE SHIFT STRIP: HERO BAR
         ============================================================ --}}
    @if ($canAccessStaffTools)
        <div class="rounded-2xl overflow-hidden border border-sky-200 dark:border-sky-900 bg-gradient-to-r from-sky-500 to-sky-600 dark:from-sky-950/90 dark:to-slate-900 text-white shadow-md shadow-sky-500/10">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 px-5 py-3.5">
                <div class="flex items-center gap-3.5 min-w-0">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white/20 text-lg font-black" aria-hidden="true">
                        🧾
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-sky-100/90">{{ __('messages.pos') }} {{ __('messages.counter') }}</span>
                            @if ($openShift)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-400/30 text-emerald-100 text-[10px] font-bold">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                                    {{ __('messages.shift_open') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-400/30 text-amber-100 text-[10px] font-bold">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                                    {{ __('messages.no_open_shift') }}
                                </span>
                            @endif
                        </div>
                        @if ($openShift)
                            <p class="text-xs text-sky-100/90 truncate mt-0.5">
                                <span class="font-bold text-white">{{ $openShift->register_name }}</span> · {{ $openShift->cashier?->name }} ·
                                {{ __('messages.opened_at') }} {{ $openShift->opened_at->format('H:i') }} ·
                                {{ __('messages.cash_sales') }}: <span class="font-mono font-bold text-white">Ks {{ number_format((float) $openShift->cash_sales) }}</span>
                            </p>
                        @else
                            <p class="text-xs text-sky-100/90 mt-0.5">{{ __('messages.pos_open_shift_hint') }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('pos.index', ['store_slug' => $store->slug]) }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-white text-sky-700 hover:bg-sky-50 px-4 py-2.5 text-xs font-black shadow-sm active:scale-[0.98] transition">
                        <span>🧾 {{ __('messages.pos_sale') }}</span>
                        <span>→</span>
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================
         DAILY HIGH-FREQUENCY QUICK ACTIONS HUB (8 ESSENTIAL BUTTONS)
         ============================================================ --}}
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 font-mono">
                ⚡ {{ __('messages.dashboard_quick_actions') }}
            </h2>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2.5">
            {{-- 1. POS Sale --}}
            <a href="{{ route('pos.index', ['store_slug' => $store->slug]) }}"
               class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 hover:border-violet-400 dark:hover:border-violet-600 hover:shadow-md transition text-center group">
                <span class="w-9 h-9 rounded-xl bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-base mb-1.5 group-hover:scale-110 transition">🧾</span>
                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">{{ __('messages.dashboard_pos_counter') }}</span>
            </a>

            {{-- 2. Add Product --}}
            <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}"
               class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 hover:border-violet-400 dark:hover:border-violet-600 hover:shadow-md transition text-center group">
                <span class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center text-base mb-1.5 group-hover:scale-110 transition">📦</span>
                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">{{ __('messages.dashboard_add_product') }}</span>
            </a>

            {{-- 3. Stock In / Purchase --}}
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases/create') }}"
               class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 hover:border-violet-400 dark:hover:border-violet-600 hover:shadow-md transition text-center group">
                <span class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-base mb-1.5 group-hover:scale-110 transition">📥</span>
                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">{{ __('messages.dashboard_purchase_stock') }}</span>
            </a>

            {{-- 4. Order Requests --}}
            <a href="{{ route('store.admin.orders.index', ['store_slug' => $store->slug]) }}"
               class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 hover:border-violet-400 dark:hover:border-violet-600 hover:shadow-md transition text-center group">
                <span class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-base mb-1.5 group-hover:scale-110 transition">📋</span>
                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">{{ __('messages.dashboard_order_requests') }}</span>
            </a>

            {{-- 5. Service / Repair Jobs or Secondary --}}
            @if (store_can('service.repair_jobs', $store))
                <a href="{{ url('/store/' . $store->slug . '/admin/repairs') }}"
                   class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 hover:border-violet-400 dark:hover:border-violet-600 hover:shadow-md transition text-center group">
                    <span class="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-base mb-1.5 group-hover:scale-110 transition">🛠️</span>
                    <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">{{ __('messages.dashboard_service_repairs') }}</span>
                </a>
            @elseif (store_can('storefront.glass_finder', $store))
                <a href="{{ route('store.admin.glass-finder.index', ['store_slug' => $store->slug]) }}"
                   class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 hover:border-violet-400 dark:hover:border-violet-600 hover:shadow-md transition text-center group">
                    <span class="w-9 h-9 rounded-xl bg-cyan-100 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 flex items-center justify-center text-base mb-1.5 group-hover:scale-110 transition">🔍</span>
                    <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">{{ __('messages.glass_finder') }}</span>
                </a>
            @else
                <a href="{{ url('/store/' . $store->slug . '/admin/stock-balance') }}"
                   class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 hover:border-violet-400 dark:hover:border-violet-600 hover:shadow-md transition text-center group">
                    <span class="w-9 h-9 rounded-xl bg-teal-100 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center text-base mb-1.5 group-hover:scale-110 transition">📊</span>
                    <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">Stock Balance</span>
                </a>
            @endif

            {{-- 6. Customer Receivables AR --}}
            <a href="{{ url('/store/' . $store->slug . '/pos/credit-sales') }}"
               class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 hover:border-violet-400 dark:hover:border-violet-600 hover:shadow-md transition text-center group">
                <span class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center text-base mb-1.5 group-hover:scale-110 transition">👥</span>
                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">{{ __('messages.dashboard_credit_ar') }}</span>
            </a>

            {{-- 7. Record Expense --}}
            <a href="{{ url('/store/' . $store->slug . '/admin/expenses') }}"
               class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 hover:border-violet-400 dark:hover:border-violet-600 hover:shadow-md transition text-center group">
                <span class="w-9 h-9 rounded-xl bg-fuchsia-100 dark:bg-fuchsia-950/60 text-fuchsia-600 dark:text-fuchsia-400 flex items-center justify-center text-base mb-1.5 group-hover:scale-110 transition">💸</span>
                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">{{ __('messages.dashboard_record_expense') }}</span>
            </a>

            {{-- 8. Daily Closing X/Z --}}
            <a href="{{ url('/store/' . $store->slug . '/pos/daily-closing') }}"
               class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 hover:border-violet-400 dark:hover:border-violet-600 hover:shadow-md transition text-center group">
                <span class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center text-base mb-1.5 group-hover:scale-110 transition">🖨️</span>
                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">{{ __('messages.dashboard_daily_closing') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         SECTION 1: TODAY'S OPERATIONS (CORE METRICS)
         ============================================================ --}}
    <section aria-label="Today's operations">
        <div class="admin-section-head">
            <h2 class="admin-section-title">{{ __('messages.dashboard_todays_operations') }}</h2>
            <span class="admin-section-sub">{{ __('messages.dashboard_todays_metrics_sub') }}</span>
        </div>
        <div class="admin-hairline-grid grid-cols-2 lg:grid-cols-4">
            {{-- 1. Today Orders --}}
            <div class="admin-hairline-cell bg-violet-50/20 dark:bg-violet-950/10">
                <div class="admin-stat-label">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-violet-500" aria-hidden="true"></span>
                    {{ __('messages.dashboard_today_orders') }}
                </div>
                <div class="admin-stat-value text-violet-700 dark:text-violet-300 font-mono" data-today-orders-stat>{{ number_format($todayOrders) }}</div>
                <div class="admin-stat-sub">{{ __('messages.revenue') }}: Ks {{ number_format($todayRevenue) }}</div>
            </div>

            {{-- 2. Today Revenue --}}
            <div class="admin-hairline-cell bg-emerald-50/20 dark:bg-emerald-950/10">
                <div class="admin-stat-label">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                    {{ __('messages.dashboard_today_revenue') }}
                </div>
                <div class="admin-stat-value text-emerald-600 dark:text-emerald-400 font-mono">Ks {{ number_format($todayRevenue) }}</div>
                <div class="admin-stat-sub">{{ number_format($todayOrders) }} {{ strtolower(__('messages.dashboard_today_orders')) }}</div>
            </div>

            {{-- 3. Pending Orders --}}
            <div class="admin-hairline-cell bg-amber-50/20 dark:bg-amber-950/10">
                <div class="admin-stat-label">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                    {{ __('messages.dashboard_pending_orders') }}
                </div>
                <div class="admin-stat-value text-amber-600 dark:text-amber-400 font-mono" data-pending-orders-stat>{{ number_format($pendingOrders) }}</div>
                <div class="admin-stat-sub">{{ __('messages.dashboard_awaiting_contact') }}</div>
            </div>

            {{-- 4. This Month Revenue --}}
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-blue-500" aria-hidden="true"></span>
                    {{ __('messages.dashboard_month_revenue') }}
                </div>
                <div class="admin-stat-value font-mono">Ks {{ number_format($monthRevenue) }}</div>
                <div class="admin-stat-sub">{{ __('messages.dashboard_orders_this_month', ['count' => number_format($monthOrders)]) }}</div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         CRITICAL OVERDUE SUPPLIER PAYABLES ALERT
         ============================================================ --}}
    @if ($overdueData['overdue_count'] > 0)
        <section aria-label="Overdue payables">
            <div class="admin-section-head">
                <h2 class="admin-section-title flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 text-xs font-bold">{{ $overdueData['overdue_count'] }}</span>
                    {{ __('messages.overdue_payables_title') }}
                </h2>
                <a href="{{ url('/store/' . $store->slug . '/admin/suppliers/aging') }}" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1">
                    <span>{{ __('messages.aging_report_title') }}</span>
                    <span>→</span>
                </a>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-2xl overflow-hidden border border-rose-200/90 dark:border-rose-900/40 shadow-sm">
                <div class="px-4 py-3 bg-rose-50/50 dark:bg-rose-950/30 border-b border-rose-200/80 dark:border-rose-900/40 flex items-center justify-between">
                    <span class="text-xs font-bold text-rose-800 dark:text-rose-300">{{ __('messages.overdue_total') }}: Ks {{ number_format($overdueData['total_overdue'], 0) }}</span>
                    <span class="text-[11px] font-semibold text-rose-600 dark:text-rose-400">{{ __('messages.overdue_30_days') }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 text-slate-500 font-mono uppercase">
                            <tr>
                                <th class="px-4 py-2.5 font-semibold">{{ __('messages.supplier_col_name') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-right">{{ __('messages.aging_total_outstanding') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-right">{{ __('messages.aging_days') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-right">{{ __('messages.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($overdueData['overdue_suppliers'] as $row)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900 dark:text-slate-100">{{ $row['name'] }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $row['po_count'] }} {{ __('messages.aging_pos') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-block px-2 py-0.5 rounded-lg bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 font-mono font-bold">Ks {{ number_format($row['amount'], 0) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-mono font-bold text-rose-600 dark:text-rose-400">{{ $row['age_days'] }} {{ __('messages.aging_days') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ url('/store/' . $store->slug . '/pos/purchases/payables/' . $row['id']) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 transition shadow-sm">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            <span>{{ __('messages.overdue_pay_now') }}</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    {{-- ============================================================
         SECTION 2: WEEKLY VOLUME & ORDER LIFECYCLE
         ============================================================ --}}
    <section aria-label="Order status">
        <div class="admin-section-head">
            <h2 class="admin-section-title">{{ __('messages.dashboard_weekly_volume') }}</h2>
            <span class="admin-section-sub">{{ __('messages.dashboard_since_monday') }}</span>
        </div>
        <div class="admin-hairline-grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5">
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">{{ __('messages.dashboard_week_orders') }}</div>
                <div class="admin-stat-value font-mono">{{ number_format($weekOrders) }}</div>
                <div class="admin-stat-sub">{{ __('messages.revenue') }}: Ks {{ number_format($weekRevenue) }}</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">{{ __('messages.dashboard_week_revenue') }}</div>
                <div class="admin-stat-value font-mono">Ks {{ number_format($weekRevenue) }}</div>
                <div class="admin-stat-sub">{{ __('messages.dashboard_since_monday') }}</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">{{ __('messages.dashboard_confirmed_orders') }}</div>
                <div class="admin-stat-value font-mono">{{ number_format($confirmedOrders) }}</div>
                <div class="admin-stat-sub">{{ __('messages.dashboard_all_time') }}</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">{{ __('messages.dashboard_delivered_orders') }}</div>
                <div class="admin-stat-value font-mono text-emerald-600 dark:text-emerald-400">{{ number_format($deliveredOrders) }}</div>
                <div class="admin-stat-sub">{{ __('messages.dashboard_all_time') }}</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">{{ __('messages.dashboard_cancelled_orders') }}</div>
                <div class="admin-stat-value font-mono text-slate-400" data-cancelled-orders-stat>{{ number_format($cancelledOrders) }}</div>
                <div class="admin-stat-sub">{{ __('messages.dashboard_all_time') }}</div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         SECTION 3: INVENTORY CATALOG & BUSINESS TRENDS
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5">
        {{-- Inventory Catalog --}}
        <div class="lg:col-span-8 space-y-2">
            <div class="admin-section-head">
                <h2 class="admin-section-title">{{ __('messages.dashboard_inventory_catalog') }}</h2>
                <span class="admin-section-sub">{{ __('messages.dashboard_inventory_sub') }}</span>
            </div>
            <div class="admin-hairline-grid grid-cols-2 sm:grid-cols-3 {{ store_can('storefront.glass_finder', $store) ? 'lg:grid-cols-4' : '' }}">
                <div class="admin-hairline-cell">
                    <div class="admin-stat-label">{{ __('messages.dashboard_total_products') }}</div>
                    <div class="admin-stat-value font-mono">{{ number_format($totalProducts) }}</div>
                    <div class="admin-stat-sub">{{ __('messages.dashboard_whole_catalog') }}</div>
                </div>
                <div class="admin-hairline-cell">
                    <div class="admin-stat-label">{{ __('messages.dashboard_in_stock') }}</div>
                    <div class="admin-stat-value font-mono text-emerald-600 dark:text-emerald-400">{{ number_format($inStockProducts) }}</div>
                    <div class="admin-stat-sub">{{ __('messages.dashboard_available_to_sell') }}</div>
                </div>
                <div class="admin-hairline-cell">
                    <div class="admin-stat-label">{{ __('messages.dashboard_out_of_stock') }}</div>
                    <div class="admin-stat-value font-mono text-rose-600 dark:text-rose-400">{{ number_format($outOfStockProducts) }}</div>
                    <div class="admin-stat-sub">{{ __('messages.dashboard_needs_restocking') }}</div>
                </div>
                @if (store_can('storefront.glass_finder', $store))
                    <div class="admin-hairline-cell">
                        <div class="admin-stat-label">{{ __('messages.dashboard_glass_finder_items') }}</div>
                        <div class="admin-stat-value font-mono text-sky-600 dark:text-sky-400">{{ number_format($glassFinderItems) }}</div>
                        <div class="admin-stat-sub">{{ __('messages.dashboard_lookup_db') }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Business Trends --}}
        <div class="lg:col-span-4 space-y-2">
            <div class="admin-section-head">
                <h2 class="admin-section-title">{{ __('messages.dashboard_business_pipeline') }}</h2>
                <span class="admin-section-sub">{{ __('messages.dashboard_business_sub') }}</span>
            </div>
            <div class="admin-hairline-grid {{ store_can('commerce.wholesale_pricing', $store) ? 'grid-cols-2' : 'grid-cols-1' }}">
                @if (store_can('commerce.wholesale_pricing', $store))
                    <div class="admin-hairline-cell bg-amber-50/20 dark:bg-amber-950/10">
                        <div class="admin-stat-label">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                            {{ __('messages.dashboard_pending_wholesale') }}
                        </div>
                        <div class="admin-stat-value text-amber-600 dark:text-amber-400 font-mono" data-pending-wholesale-stat>{{ number_format($pendingWholesale) }}</div>
                        <div class="admin-stat-sub">{{ __('messages.dashboard_apps_to_review') }}</div>
                    </div>
                @endif
                <div class="admin-hairline-cell bg-violet-50/20 dark:bg-violet-950/10">
                    <div class="admin-stat-label">{{ __('messages.dashboard_year_revenue') }}</div>
                    <div class="admin-stat-value text-violet-700 dark:text-violet-300 font-mono">Ks {{ number_format($yearRevenue) }}</div>
                    <div class="admin-stat-sub">{{ __('messages.dashboard_this_calendar_year') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         SECTION 4: MONTHLY REVENUE CHART & TOP SELLING PRODUCTS
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 items-start">
        {{-- 12-Month Sales Trend Chart (8 Cols) --}}
        <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-slate-100 font-mono flex items-center gap-2">
                    <span>📈 {{ __('messages.dashboard_monthly_report_title') }}</span>
                    <span class="text-[11px] font-normal text-slate-400">{{ __('messages.dashboard_last_12_months') }}</span>
                </h3>
            </div>
            @php
                $chartTotal = array_sum(array_column($monthlySeries, 'revenue'));
                $chartMax = max(array_column($monthlySeries, 'revenue')) ?: 1;
            @endphp

            @if ($chartTotal == 0)
                <div class="py-12 text-center text-slate-400 text-xs">
                    <p class="font-bold text-slate-600 dark:text-slate-300">{{ __('messages.dashboard_no_sales_data') }}</p>
                    <p class="text-[11px] text-slate-400 mt-1">{{ __('messages.dashboard_sales_appear_hint') }}</p>
                </div>
            @else
                <div class="h-44 sm:h-48 pt-4">
                    <div class="flex gap-1.5 sm:gap-2 h-full items-end">
                        @foreach ($monthlySeries as $i => $m)
                            <div class="flex-1 flex flex-col min-w-0 h-full justify-end group cursor-pointer" title="{{ $m['label'] }}: Ks {{ number_format($m['revenue']) }}">
                                <div class="w-full rounded-t-lg bg-gradient-to-t from-violet-600 to-indigo-500 transition-all duration-300 group-hover:from-violet-500 group-hover:to-fuchsia-400 shadow-sm"
                                     style="height: {{ max(4, round(($m['revenue'] / $chartMax) * 100)) }}%"></div>
                                <span class="text-[10px] font-mono font-bold text-slate-400 leading-tight w-full text-center truncate mt-2">{{ $m['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center text-xs">
                    <span class="font-mono text-slate-500">{{ __('messages.dashboard_total_12_mo', ['amount' => number_format($chartTotal)]) }}</span>
                </div>
            @endif
        </div>

        {{-- Top Selling Products (4 Cols) --}}
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-slate-100 font-mono flex items-center gap-1.5">
                    <span>🏆 {{ __('messages.dashboard_top_products_title') }}</span>
                    <span class="text-[11px] font-normal text-slate-400">{{ __('messages.dashboard_by_qty') }}</span>
                </h3>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                @forelse ($topProducts as $i => $tp)
                    <div class="flex items-center justify-between gap-2 py-2.5">
                        <div class="min-w-0 flex items-center gap-2.5">
                            <span class="shrink-0 w-5 h-5 rounded-full text-[10px] font-black text-white flex items-center justify-center {{ $i === 0 ? 'bg-amber-500' : ($i === 1 ? 'bg-slate-400' : ($i === 2 ? 'bg-amber-700' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300')) }}">
                                {{ $i + 1 }}
                            </span>
                            <span class="font-bold text-slate-800 dark:text-slate-200 truncate" title="{{ $tp->name }}">{{ $tp->name }}</span>
                        </div>
                        <div class="shrink-0 text-right font-mono">
                            <span class="font-bold text-violet-600 dark:text-violet-400">{{ number_format($tp->qty) }} pcs</span>
                            <span class="block text-[10px] text-slate-400">Ks {{ number_format($tp->sales) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400 text-xs">
                        <p class="font-bold text-slate-600 dark:text-slate-300">{{ __('messages.dashboard_no_best_sellers') }}</p>
                        <p class="text-[11px] text-slate-400 mt-1">{{ __('messages.dashboard_best_sellers_hint') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ============================================================
         SECTION 5: RECENT OPERATIONAL ACTIVITY FEEDS
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5 items-start">
        {{-- Recent Orders --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-slate-100 font-mono">
                    🛒 {{ __('messages.dashboard_recent_orders_title') }}
                </h3>
                <a href="{{ route('store.admin.orders.index', ['store_slug' => $store->slug]) }}" class="text-[11px] font-bold text-violet-600 dark:text-violet-400 hover:underline">
                    {{ __('messages.view_all') }} →
                </a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                @forelse ($recentOrders as $order)
                    <div class="flex items-center justify-between gap-2 py-2.5">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 min-w-0 font-mono">
                                <span class="font-bold text-violet-600 dark:text-violet-400 truncate">{{ $order->order_number }}</span>
                                <span class="text-slate-300 dark:text-slate-600 shrink-0">•</span>
                                <span class="text-slate-700 dark:text-slate-300 truncate">{{ $order->customer_name }}</span>
                            </div>
                            @if ($order->status === 'pending_contact' && $order->created_at->lt(now()->subHours(2)))
                                <div class="mt-1">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 text-[10px] font-bold">
                                        {{ __('messages.dashboard_uncontacted_warning') }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        <span class="shrink-0 font-mono font-bold text-slate-800 dark:text-slate-200" title="Ks {{ number_format($order->total_amount) }}">
                            Ks {{ number_format($order->total_amount) }}
                        </span>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400 text-xs">
                        <p class="font-bold text-slate-600 dark:text-slate-300">{{ __('messages.dashboard_no_recent_orders') }}</p>
                        <p class="text-[11px] text-slate-400 mt-1">{{ __('messages.dashboard_recent_orders_hint') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Wholesale Applications --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-slate-100 font-mono">
                    📝 {{ __('messages.dashboard_recent_wholesale_title') }}
                </h3>
                @if (store_can('commerce.wholesale_pricing', $store))
                    <a href="{{ route('store.admin.wholesale.applications.index', ['store_slug' => $store->slug]) }}" class="text-[11px] font-bold text-violet-600 dark:text-violet-400 hover:underline">
                        {{ __('messages.view_all') }} →
                    </a>
                @endif
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                @forelse ($recentWholesale as $app)
                    <div class="flex items-center justify-between gap-2 py-2.5">
                        <div class="min-w-0">
                            <div class="truncate font-bold text-slate-900 dark:text-slate-100" title="{{ $app->business_name }}">{{ $app->business_name }}</div>
                            <div class="truncate text-slate-500 dark:text-slate-400 text-[11px] font-mono mt-0.5">{{ $app->phone }}</div>
                        </div>
                        <span class="shrink-0 uppercase font-mono font-bold text-[10px] px-2 py-0.5 rounded-full {{ $app->status === 'approved' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' }}">
                            {{ $app->status }}
                        </span>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400 text-xs">
                        <p class="font-bold text-slate-600 dark:text-slate-300">{{ __('messages.dashboard_no_recent_wholesale') }}</p>
                        <p class="text-[11px] text-slate-400 mt-1">{{ __('messages.dashboard_recent_wholesale_hint') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recently Added Products --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-slate-100 font-mono">
                    📦 {{ __('messages.dashboard_recent_products_title') }}
                </h3>
                <a href="{{ url('/store/' . $store->slug . '/admin/products') }}" class="text-[11px] font-bold text-violet-600 dark:text-violet-400 hover:underline">
                    {{ __('messages.view_all') }} →
                </a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                @forelse ($recentProducts as $prod)
                    <div class="flex items-center justify-between gap-2 py-2.5">
                        <div class="min-w-0">
                            <div class="truncate font-bold text-slate-900 dark:text-slate-100" title="{{ $prod->name }}">{{ $prod->name }}</div>
                            <div class="truncate text-slate-400 font-mono text-[10px] mt-0.5">SKU: {{ $prod->sku }}</div>
                        </div>
                        <span class="shrink-0 font-mono font-bold text-violet-600 dark:text-violet-400" title="Ks {{ number_format($prod->retail_price) }}">
                            Ks {{ number_format($prod->retail_price) }}
                        </span>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400 text-xs">
                        <p class="font-bold text-slate-600 dark:text-slate-300">{{ __('messages.dashboard_no_recent_products') }}</p>
                        <p class="text-[11px] text-slate-400 mt-1">{{ __('messages.dashboard_recent_products_hint') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
