@extends('layouts.admin.app')

@section('title', __('messages.admin_dashboard') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-1 sm:p-1.5')

@section('content')
<div class="w-full space-y-0.5">

    {{-- ============================================================
         DAILY QUICK ACTIONS HUB: 12 EMBOSSED 3D PUSH BUTTONS
         Desktop: Single horizontal row (12 cols) | Mobile: 4 cols (4x3 grid)
         ============================================================ --}}
    <div class="dashboard-quick-actions">
        {{-- 1. POS Sale (Unified with Shift Indicator) --}}
        <a href="{{ route('pos.index', ['store_slug' => $store->slug]) }}"
           class="btn-3d-action group">
            @if ($openShift)
                <span class="absolute top-1 right-1 flex h-1.5 w-1.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                </span>
            @endif
            <span class="btn-3d-icon bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400">🧾</span>
            <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">{{ __('messages.dashboard_pos_counter') }}</span>
        </a>

        {{-- 2. Products List --}}
        <a href="{{ url('/store/' . $store->slug . '/admin/products') }}"
           class="btn-3d-action group">
            <span class="btn-3d-icon bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400">🏷️</span>
            <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">{{ __('messages.dashboard_products') }}</span>
        </a>

        {{-- 3. Add Product --}}
        <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}"
           class="btn-3d-action group">
            <span class="btn-3d-icon bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400">📦</span>
            <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">{{ __('messages.dashboard_add_product') }}</span>
        </a>

        {{-- 4. Purchase / Stock In --}}
        <a href="{{ url('/store/' . $store->slug . '/pos/purchases/create') }}"
           class="btn-3d-action group">
            <span class="btn-3d-icon bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">📥</span>
            <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">{{ __('messages.dashboard_purchase_stock') }}</span>
        </a>

        {{-- 5. Order Requests --}}
        <a href="{{ route('store.admin.orders.index', ['store_slug' => $store->slug]) }}"
           class="btn-3d-action group">
            <span class="btn-3d-icon bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400">📋</span>
            <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">{{ __('messages.dashboard_order_requests') }}</span>
        </a>

        {{-- 6. Service / Repair Jobs or Secondary --}}
        @if (store_can('service.repair_jobs', $store))
            <a href="{{ url('/store/' . $store->slug . '/admin/repairs') }}"
               class="btn-3d-action group">
                <span class="btn-3d-icon bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">🛠️</span>
                <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">{{ __('messages.dashboard_service_repairs') }}</span>
            </a>
        @elseif (store_can('storefront.glass_finder', $store))
            <a href="{{ route('store.admin.glass-finder.index', ['store_slug' => $store->slug]) }}"
               class="btn-3d-action group">
                <span class="btn-3d-icon bg-cyan-100 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400">🔍</span>
                <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">{{ __('messages.glass_finder') }}</span>
            </a>
        @else
            <a href="{{ url('/store/' . $store->slug . '/admin/stock-balance') }}"
               class="btn-3d-action group">
                <span class="btn-3d-icon bg-teal-100 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400">📊</span>
                <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">Stock</span>
            </a>
        @endif

        {{-- 7. Customer Receivables AR --}}
        <a href="{{ url('/store/' . $store->slug . '/pos/credit-sales') }}"
           class="btn-3d-action group">
            <span class="btn-3d-icon bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400">👥</span>
            <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">{{ __('messages.dashboard_credit_ar') }}</span>
        </a>

        {{-- 8. Supplier Payables AP --}}
        <a href="{{ url('/store/' . $store->slug . '/admin/payables') }}"
           class="btn-3d-action group">
            <span class="btn-3d-icon bg-orange-100 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400">🤝</span>
            <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">{{ __('messages.dashboard_payables') }}</span>
        </a>

        {{-- 9. Record Expense --}}
        <a href="{{ url('/store/' . $store->slug . '/admin/expenses') }}"
           class="btn-3d-action group">
            <span class="btn-3d-icon bg-fuchsia-100 dark:bg-fuchsia-950/60 text-fuchsia-600 dark:text-fuchsia-400">💸</span>
            <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">{{ __('messages.dashboard_record_expense') }}</span>
        </a>

        {{-- 10. Stock Movement Ledger --}}
        <a href="{{ url('/store/' . $store->slug . '/admin/stock-ledger') }}"
           class="btn-3d-action group">
            <span class="btn-3d-icon bg-teal-100 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400">📊</span>
            <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">{{ __('messages.dashboard_stock_ledger') }}</span>
        </a>

        {{-- 11. Warranty & IMEI Tracker / Customer Lookup --}}
        @if (store_can('service.warranty_tracking', $store))
            <a href="{{ url('/store/' . $store->slug . '/admin/warranty') }}"
               class="btn-3d-action group">
                <span class="btn-3d-icon bg-cyan-100 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400">🛡️</span>
                <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">{{ __('messages.dashboard_warranty') }}</span>
            </a>
        @else
            <a href="{{ url('/store/' . $store->slug . '/admin/customers') }}"
               class="btn-3d-action group">
                <span class="btn-3d-icon bg-cyan-100 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400">👥</span>
                <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">{{ __('messages.dashboard_customers') }}</span>
            </a>
        @endif

        {{-- 12. Daily Closing X/Z --}}
        <a href="{{ url('/store/' . $store->slug . '/pos/daily-closing') }}"
           class="btn-3d-action group">
            <span class="btn-3d-icon bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">🖨️</span>
            <span class="text-[9.5px] sm:text-[10px] md:text-[10.5px] font-bold text-slate-800 dark:text-slate-200 leading-tight truncate w-full block">{{ __('messages.dashboard_daily_closing') }}</span>
        </a>
    </div>

    {{-- ============================================================
         SECTION 1: TODAY'S OPERATIONS (CORE METRICS)
         ============================================================ --}}
    <section aria-label="Today's operations">
        <div class="admin-section-head">
            <h2 class="admin-section-title">{{ __('messages.dashboard_todays_operations') }}</h2>
            <span class="admin-section-sub">{{ __('messages.dashboard_todays_metrics_sub') }}</span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-0.5 sm:gap-1">
            {{-- 1. Today Orders --}}
            <div class="stat-card-3d flex items-center justify-center gap-2 sm:gap-2.5">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 shadow-inner text-xs sm:text-sm font-bold">
                    📦
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black text-violet-700 dark:text-violet-300 font-mono leading-none tabular-nums" data-today-orders-stat>
                        {{ number_format($todayOrders) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.dashboard_today_orders') }}
                    </p>
                    <p class="text-[8.5px] sm:text-[9px] text-slate-400 dark:text-slate-500 truncate mt-0.5">
                        {{ __('messages.revenue') }}: Ks {{ number_format($todayRevenue) }}
                    </p>
                </div>
            </div>

            {{-- 2. Today Revenue --}}
            <div class="stat-card-3d flex items-center justify-center gap-2 sm:gap-2.5">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                    💵
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 font-mono leading-none tabular-nums">
                        Ks {{ number_format($todayRevenue) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.dashboard_today_revenue') }}
                    </p>
                    <p class="text-[8.5px] sm:text-[9px] text-slate-400 dark:text-slate-500 truncate mt-0.5">
                        {{ number_format($todayOrders) }} {{ strtolower(__('messages.dashboard_today_orders')) }}
                    </p>
                </div>
            </div>

            {{-- 3. Today Expense --}}
            <div class="stat-card-3d flex items-center justify-center gap-2 sm:gap-2.5">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-fuchsia-100 text-fuchsia-600 dark:bg-fuchsia-950/70 dark:text-fuchsia-300 shadow-inner text-xs sm:text-sm font-bold">
                    💸
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black text-fuchsia-600 dark:text-fuchsia-400 font-mono leading-none tabular-nums">
                        Ks {{ number_format($todayExpense) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.dashboard_today_expense') }}
                    </p>
                    <p class="text-[8.5px] sm:text-[9px] text-slate-400 dark:text-slate-500 truncate mt-0.5">
                        {{ __('messages.dashboard_expense_recorded') }}
                    </p>
                </div>
            </div>

            {{-- 4. Pending Orders --}}
            <div class="stat-card-3d flex items-center justify-center gap-2 sm:gap-2.5">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold">
                    ⏳
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 font-mono leading-none tabular-nums" data-pending-orders-stat>
                        {{ number_format($pendingOrders) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.dashboard_pending_orders') }}
                    </p>
                    <p class="text-[8.5px] sm:text-[9px] text-slate-400 dark:text-slate-500 truncate mt-0.5">
                        {{ __('messages.dashboard_awaiting_contact') }}
                    </p>
                </div>
            </div>

            {{-- 5. Active Repairs (In Workshop) --}}
            <div class="stat-card-3d flex items-center justify-center gap-2 sm:gap-2.5">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-indigo-100 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-300 shadow-inner text-xs sm:text-sm font-bold">
                    🛠️
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black text-indigo-600 dark:text-indigo-400 font-mono leading-none tabular-nums">
                        {{ number_format($activeRepairs) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.dashboard_active_repairs') }}
                    </p>
                    <p class="text-[8.5px] sm:text-[9px] text-slate-400 dark:text-slate-500 truncate mt-0.5">
                        {{ __('messages.dashboard_ready_repairs') }}: {{ number_format($readyRepairs) }}
                    </p>
                </div>
            </div>

            {{-- 6. This Month Revenue --}}
            <div class="stat-card-3d flex items-center justify-center gap-2 sm:gap-2.5">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-blue-100 text-blue-600 dark:bg-blue-950/70 dark:text-blue-300 shadow-inner text-xs sm:text-sm font-bold">
                    📅
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black text-slate-800 dark:text-slate-200 font-mono leading-none tabular-nums">
                        Ks {{ number_format($monthRevenue) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.dashboard_month_revenue') }}
                    </p>
                    <p class="text-[8.5px] sm:text-[9px] text-slate-400 dark:text-slate-500 truncate mt-0.5">
                        {{ __('messages.dashboard_orders_this_month', ['count' => number_format($monthOrders)]) }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         SECTION 2: MULTI-DOMAIN BUSINESS HIGHLIGHTS (EXPENSE, SERVICE, ECOMMERCE, AR)
         ============================================================ --}}
    <section aria-label="Business domain highlights">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1">
            {{-- 1. Monthly Expense Card --}}
            <a href="{{ url('/store/' . $store->slug . '/admin/expenses') }}"
               class="stat-card-3d flex items-center justify-center gap-2.5 sm:gap-3 group">
                <div class="shrink-0 w-8 h-8 rounded-lg grid place-items-center bg-fuchsia-100 text-fuchsia-600 dark:bg-fuchsia-950/70 dark:text-fuchsia-300 shadow-inner text-sm font-bold">
                    💸
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black text-fuchsia-700 dark:text-fuchsia-300 font-mono leading-none tabular-nums">
                        Ks {{ number_format($monthExpense) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.dashboard_expense_overview') }}
                    </p>
                    <p class="text-[8.5px] sm:text-[9px] text-slate-400 dark:text-slate-500 truncate mt-0.5">
                        {{ __('messages.today') }}: Ks {{ number_format($todayExpense) }}
                    </p>
                </div>
            </a>

            @if (store_can('service.repair_jobs', $store))
            {{-- 2. Service & Repairs Card --}}
            <a href="{{ url('/store/' . $store->slug . '/admin/repairs') }}"
               class="stat-card-3d flex items-center justify-center gap-2.5 sm:gap-3 group">
                <div class="shrink-0 w-8 h-8 rounded-lg grid place-items-center bg-indigo-100 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-300 shadow-inner text-sm font-bold">
                    🛠️
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black text-indigo-700 dark:text-indigo-300 font-mono leading-none tabular-nums">
                        {{ number_format($activeRepairs) }} <span class="text-xs font-normal text-slate-400">{{ __('messages.dashboard_in_workshop') }}</span>
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.dashboard_service_overview') }}
                    </p>
                    <p class="text-[8.5px] sm:text-[9px] text-emerald-600 dark:text-emerald-400 font-semibold truncate mt-0.5">
                        {{ __('messages.dashboard_ready_repairs') }}: {{ number_format($readyRepairs) }}
                    </p>
                </div>
            </a>
            @else
            {{-- 2. Inventory & Stock Card (Retail fallback) --}}
            <a href="{{ url('/store/' . $store->slug . '/admin/products') }}"
               class="stat-card-3d flex items-center justify-center gap-2.5 sm:gap-3 group">
                <div class="shrink-0 w-8 h-8 rounded-lg grid place-items-center bg-indigo-100 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-300 shadow-inner text-sm font-bold">
                    📦
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black text-indigo-700 dark:text-indigo-300 font-mono leading-none tabular-nums">
                        {{ number_format($totalProducts) }} <span class="text-xs font-normal text-slate-400">{{ __('messages.items') }}</span>
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.dashboard_inventory_catalog') }}
                    </p>
                    <p class="text-[8.5px] sm:text-[9px] text-emerald-600 dark:text-emerald-400 font-semibold truncate mt-0.5">
                        {{ __('messages.in_stock') }}: {{ number_format($inStockProducts) }}
                    </p>
                </div>
            </a>
            @endif

            {{-- 3. Ecommerce Online Storefront Card --}}
            <a href="{{ url('/store/' . $store->slug . '/admin/web-products') }}"
               class="stat-card-3d flex items-center justify-center gap-2.5 sm:gap-3 group">
                <div class="shrink-0 w-8 h-8 rounded-lg grid place-items-center bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300 shadow-inner text-sm font-bold">
                    🌐
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black text-sky-700 dark:text-sky-300 font-mono leading-none tabular-nums">
                        {{ number_format($ecommerceProducts) }} <span class="text-xs font-normal text-slate-400">{{ __('messages.dashboard_ecommerce_products') }}</span>
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.dashboard_ecommerce_overview') }}
                    </p>
                    <p class="text-[8.5px] sm:text-[9px] text-slate-400 dark:text-slate-500 truncate mt-0.5">
                        {{ __('messages.dashboard_live_on_web') }} ({{ number_format($pendingOrders) }} {{ strtolower(__('messages.pending')) }})
                    </p>
                </div>
            </a>

            {{-- 4. Customer Receivables AR Card --}}
            <a href="{{ url('/store/' . $store->slug . '/pos/credit-sales') }}"
               class="stat-card-3d flex items-center justify-center gap-2.5 sm:gap-3 group">
                <div class="shrink-0 w-8 h-8 rounded-lg grid place-items-center bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300 shadow-inner text-sm font-bold">
                    👥
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 font-mono leading-none tabular-nums">
                        Ks {{ number_format($totalCustomerDebt) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.dashboard_customer_ar') }}
                    </p>
                    <p class="text-[8.5px] sm:text-[9px] text-slate-400 dark:text-slate-500 truncate mt-0.5">
                        {{ __('messages.dashboard_customer_debt_sub') }}
                    </p>
                </div>
            </a>
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
         OPERATIONAL ANALYTICS: 4 VISUAL EXECUTIVE CHARTS
         ============================================================ --}}
    <section aria-label="Operational analytics and charts">
        <div class="admin-section-head">
            <h2 class="admin-section-title flex items-center gap-1.5">
                <span>📊</span>
                <span>{{ __('messages.dashboard_4charts_section_title') }}</span>
            </h2>
            <span class="admin-section-sub">{{ __('messages.dashboard_4charts_section_sub') }}</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3.5">
            {{-- Chart 1: 7-Day Daily Sales Trend --}}
            @php
                $sevenDaysTotal = array_sum(array_column($last7DaysSeries, 'revenue'));
                $sevenDaysMax = max(array_column($last7DaysSeries, 'revenue')) ?: 1;
            @endphp
            <div class="card-panel-3d flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1">
                            <span>📊</span>
                            <span>{{ __('messages.dashboard_chart_7days_title') }}</span>
                        </span>
                        <span class="text-[11px] font-mono font-bold text-indigo-600 dark:text-indigo-400">Ks {{ number_format($sevenDaysTotal) }}</span>
                    </div>
                    <div class="h-28 flex items-end gap-1.5 pt-2 pb-1 border-b border-slate-100 dark:border-slate-800">
                        @foreach ($last7DaysSeries as $d)
                            @php
                                $barHeight = $sevenDaysMax > 0 ? max(6, round(($d['revenue'] / $sevenDaysMax) * 100)) : 6;
                            @endphp
                            <div class="flex-1 flex flex-col items-center h-full justify-end group cursor-pointer"
                                 title="{{ $d['date'] }} ({{ $d['day'] }}): Ks {{ number_format($d['revenue']) }} ({{ $d['orders'] }} orders)">
                                <div class="w-full rounded-t-md bg-gradient-to-t from-indigo-600 to-sky-400 transition-all duration-300 group-hover:from-indigo-500 group-hover:to-fuchsia-400 shadow-xs"
                                     style="height: {{ $barHeight }}%"></div>
                                <span class="text-[9px] font-mono font-bold text-slate-400 mt-1 leading-tight">{{ $d['day'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="pt-2 flex items-center justify-between text-[10px] text-slate-400">
                    <span>7-Day Volume</span>
                    <span class="font-mono font-semibold text-slate-600 dark:text-slate-300">{{ array_sum(array_column($last7DaysSeries, 'orders')) }} orders</span>
                </div>
            </div>

            {{-- Chart 2: Payment Channels Mix (Donut / Segmented Distribution) --}}
            <div class="card-panel-3d flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1">
                            <span>💳</span>
                            <span>{{ __('messages.dashboard_chart_payments_title') }}</span>
                        </span>
                        <span class="text-[10px] text-slate-400">30 days</span>
                    </div>
                    {{-- Stacked Progress Bar --}}
                    <div class="w-full h-3 rounded-full overflow-hidden flex bg-slate-100 dark:bg-slate-800 shadow-inner mb-2.5">
                        @foreach ($paymentBreakdown as $pay)
                            @if ($pay['percent'] > 0)
                                <div style="width: {{ $pay['percent'] }}%; background-color: {{ $pay['color'] }};"
                                     title="{{ $pay['name'] }}: {{ $pay['percent'] }}% (Ks {{ number_format($pay['amount']) }})"
                                     class="h-full transition-all duration-300 first:rounded-l-full last:rounded-r-full"></div>
                            @endif
                        @endforeach
                    </div>
                    {{-- Payment Badges Legend --}}
                    <div class="space-y-1.5">
                        @foreach (array_slice($paymentBreakdown, 0, 3) as $pay)
                            <div class="flex items-center justify-between text-[11px]">
                                <div class="flex items-center gap-1.5 truncate">
                                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $pay['color'] }};"></span>
                                    <span class="text-slate-700 dark:text-slate-300 truncate">{{ $pay['name'] }}</span>
                                </div>
                                <span class="font-mono font-bold text-slate-900 dark:text-slate-100 text-[10.5px]">{{ $pay['percent'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[10px] text-slate-400">
                    <span>Active Channels</span>
                    <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">{{ count(array_filter($paymentBreakdown, fn($p) => $p['amount'] > 0)) }} Active</span>
                </div>
            </div>

            {{-- Chart 3: Expense by Category Breakdown --}}
            @php
                $totalExpenseLogged = array_sum(array_column($expenseBreakdown, 'amount'));
            @endphp
            <div class="card-panel-3d flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1">
                            <span>💸</span>
                            <span>{{ __('messages.dashboard_chart_expense_title') }}</span>
                        </span>
                        <span class="text-[11px] font-mono font-bold text-fuchsia-600 dark:text-fuchsia-400">Ks {{ number_format($totalExpenseLogged) }}</span>
                    </div>
                    {{-- Category Progress Bars --}}
                    <div class="space-y-2">
                        @foreach ($expenseBreakdown as $exp)
                            <div>
                                <div class="flex items-center justify-between text-[10px] text-slate-600 dark:text-slate-300 mb-0.5">
                                    <span class="truncate pr-1">{{ $exp['name'] }}</span>
                                    <span class="font-mono font-bold shrink-0">{{ $exp['percent'] }}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-300"
                                         style="width: {{ max(4, $exp['percent']) }}%; background-color: {{ $exp['color'] }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[10px] text-slate-400">
                    <span>This Month Outflow</span>
                    <a href="{{ url('/store/' . $store->slug . '/admin/expenses') }}" class="text-fuchsia-600 dark:text-fuchsia-400 font-semibold hover:underline">Detail →</a>
                </div>
            </div>

            {{-- Chart 4: Service & Repair Pipeline Funnel --}}
            <div class="card-panel-3d flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1">
                            <span>🛠️</span>
                            <span>{{ __('messages.dashboard_chart_service_title') }}</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Workshop</span>
                    </div>
                    {{-- 4 Stage Pipeline Cards --}}
                    <div class="grid grid-cols-2 gap-1.5">
                        <div class="p-1.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60">
                            <span class="text-[10px] text-slate-400 block truncate">📥 Received</span>
                            <span class="text-xs font-bold font-mono text-slate-800 dark:text-slate-200">{{ $servicePipeline['received'] }}</span>
                        </div>
                        <div class="p-1.5 rounded-lg bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-900/40">
                            <span class="text-[10px] text-amber-600 dark:text-amber-400 block truncate">⚙️ In Repair</span>
                            <span class="text-xs font-bold font-mono text-amber-600 dark:text-amber-400">{{ $servicePipeline['in_progress'] }}</span>
                        </div>
                        <div class="p-1.5 rounded-lg bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/70 dark:border-emerald-900/40">
                            <span class="text-[10px] text-emerald-600 dark:text-emerald-400 block truncate">✅ Ready</span>
                            <span class="text-xs font-bold font-mono text-emerald-600 dark:text-emerald-400">{{ $servicePipeline['ready'] }}</span>
                        </div>
                        <div class="p-1.5 rounded-lg bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-200/70 dark:border-indigo-900/40">
                            <span class="text-[10px] text-indigo-600 dark:text-indigo-400 block truncate">🚚 Delivered</span>
                            <span class="text-xs font-bold font-mono text-indigo-600 dark:text-indigo-400">{{ $servicePipeline['delivered'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[10px] text-slate-400">
                    <span class="truncate">{{ __('messages.dashboard_chart_service_sub') }}</span>
                    @if (store_can('service.repair_jobs', $store))
                        <a href="{{ url('/store/' . $store->slug . '/admin/repairs') }}" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline shrink-0">Jobs →</a>
                    @else
                        <a href="{{ url('/store/' . $store->slug . '/admin/orders') }}" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline shrink-0">Orders →</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         SECTION 3: WEEKLY VOLUME & ORDER LIFECYCLE
         ============================================================ --}}
    <section aria-label="Order status">
        <div class="admin-section-head">
            <h2 class="admin-section-title">{{ __('messages.dashboard_weekly_volume') }}</h2>
            <span class="admin-section-sub">{{ __('messages.dashboard_since_monday') }}</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-2 sm:gap-2.5">
            <div class="stat-card-3d">
                <div class="admin-stat-label truncate">{{ __('messages.dashboard_week_orders') }}</div>
                <div class="admin-stat-value font-mono my-0.5">{{ number_format($weekOrders) }}</div>
                <div class="admin-stat-sub truncate">{{ __('messages.revenue') }}: Ks {{ number_format($weekRevenue) }}</div>
            </div>
            <div class="stat-card-3d">
                <div class="admin-stat-label truncate">{{ __('messages.dashboard_week_revenue') }}</div>
                <div class="admin-stat-value font-mono my-0.5">Ks {{ number_format($weekRevenue) }}</div>
                <div class="admin-stat-sub truncate">{{ __('messages.dashboard_since_monday') }}</div>
            </div>
            <div class="stat-card-3d">
                <div class="admin-stat-label truncate">{{ __('messages.dashboard_confirmed_orders') }}</div>
                <div class="admin-stat-value font-mono my-0.5">{{ number_format($confirmedOrders) }}</div>
                <div class="admin-stat-sub truncate">{{ __('messages.dashboard_all_time') }}</div>
            </div>
            <div class="stat-card-3d">
                <div class="admin-stat-label truncate">{{ __('messages.dashboard_delivered_orders') }}</div>
                <div class="admin-stat-value font-mono text-emerald-600 dark:text-emerald-400 my-0.5">{{ number_format($deliveredOrders) }}</div>
                <div class="admin-stat-sub truncate">{{ __('messages.dashboard_all_time') }}</div>
            </div>
            <div class="stat-card-3d">
                <div class="admin-stat-label truncate">{{ __('messages.dashboard_cancelled_orders') }}</div>
                <div class="admin-stat-value font-mono text-slate-400 my-0.5" data-cancelled-orders-stat>{{ number_format($cancelledOrders) }}</div>
                <div class="admin-stat-sub truncate">{{ __('messages.dashboard_all_time') }}</div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         SECTION 4: INVENTORY CATALOG & BUSINESS TRENDS
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 sm:gap-4">
        {{-- Inventory Catalog --}}
        <div class="lg:col-span-8 space-y-2">
            <div class="admin-section-head">
                <h2 class="admin-section-title">{{ __('messages.dashboard_inventory_catalog') }}</h2>
                <span class="admin-section-sub">{{ __('messages.dashboard_inventory_sub') }}</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 {{ store_can('storefront.glass_finder', $store) ? 'lg:grid-cols-4' : '' }} gap-2 sm:gap-2.5">
                <div class="stat-card-3d">
                    <div class="admin-stat-label truncate">{{ __('messages.dashboard_total_products') }}</div>
                    <div class="admin-stat-value font-mono my-0.5">{{ number_format($totalProducts) }}</div>
                    <div class="admin-stat-sub truncate">{{ __('messages.dashboard_whole_catalog') }}</div>
                </div>
                <div class="stat-card-3d">
                    <div class="admin-stat-label truncate">{{ __('messages.dashboard_in_stock') }}</div>
                    <div class="admin-stat-value font-mono text-emerald-600 dark:text-emerald-400 my-0.5">{{ number_format($inStockProducts) }}</div>
                    <div class="admin-stat-sub truncate">{{ __('messages.dashboard_available_to_sell') }}</div>
                </div>
                <div class="stat-card-3d">
                    <div class="admin-stat-label truncate">{{ __('messages.dashboard_out_of_stock') }}</div>
                    <div class="admin-stat-value font-mono text-rose-600 dark:text-rose-400 my-0.5">{{ number_format($outOfStockProducts) }}</div>
                    <div class="admin-stat-sub truncate">{{ __('messages.dashboard_needs_restocking') }}</div>
                </div>
                @if (store_can('storefront.glass_finder', $store))
                    <div class="stat-card-3d">
                        <div class="admin-stat-label truncate">{{ __('messages.dashboard_glass_finder_items') }}</div>
                        <div class="admin-stat-value font-mono text-sky-600 dark:text-sky-400 my-0.5">{{ number_format($glassFinderItems) }}</div>
                        <div class="admin-stat-sub truncate">{{ __('messages.dashboard_lookup_db') }}</div>
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
            <div class="grid {{ store_can('commerce.wholesale_pricing', $store) ? 'grid-cols-2' : 'grid-cols-1' }} gap-2 sm:gap-2.5">
                @if (store_can('commerce.wholesale_pricing', $store))
                    <div class="stat-card-3d">
                        <div class="admin-stat-label truncate">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                            {{ __('messages.dashboard_pending_wholesale') }}
                        </div>
                        <div class="admin-stat-value text-amber-600 dark:text-amber-400 font-mono my-0.5" data-pending-wholesale-stat>{{ number_format($pendingWholesale) }}</div>
                        <div class="admin-stat-sub truncate">{{ __('messages.dashboard_apps_to_review') }}</div>
                    </div>
                @endif
                <div class="stat-card-3d">
                    <div class="admin-stat-label truncate">{{ __('messages.dashboard_year_revenue') }}</div>
                    <div class="admin-stat-value text-violet-700 dark:text-violet-300 font-mono my-0.5">Ks {{ number_format($yearRevenue) }}</div>
                    <div class="admin-stat-sub truncate">{{ __('messages.dashboard_this_calendar_year') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         SECTION 4: MONTHLY REVENUE CHART & TOP SELLING PRODUCTS
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 sm:gap-4 items-start">
        {{-- 12-Month Sales Trend Chart (8 Cols) --}}
        <div class="lg:col-span-8 card-panel-3d space-y-3">
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
        <div class="lg:col-span-4 card-panel-3d space-y-3">
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-4 items-start">
        {{-- Recent Orders --}}
        <div class="card-panel-3d space-y-3">
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
        <div class="card-panel-3d space-y-3">
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
        <div class="card-panel-3d space-y-3">
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
