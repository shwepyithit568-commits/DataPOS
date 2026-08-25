@php
    $titles = [
        'sales'    => ['label' => __('messages.reports_sales'), 'desc' => 'POS Invoices, Daily Totals & Payment Methods'],
        'cash'     => ['label' => __('messages.reports_cash'), 'desc' => 'Cashier Shifts, Cash In/Out & Drawer Reconciliation'],
        'stock'    => ['label' => __('messages.reports_stock'), 'desc' => 'On-Hand Inventory Balances, Average Costs & Valuation'],
        'services' => ['label' => __('messages.sidebar_report_services'), 'desc' => 'Device Repairs, Technician Performance & Service Revenue'],
    ];
    $current = $titles[$active] ?? ['label' => __('messages.reports_title'), 'desc' => ''];
@endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6 pb-4 border-b border-slate-200/80 dark:border-slate-800">
    <div class="space-y-0.5">
        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
            <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition">
                {{ __('messages.admin_dashboard') }}
            </a>
            <span>/</span>
            <span class="text-sky-600 dark:text-sky-400">{{ __('messages.sidebar_reports') }}</span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit">
            {{ $current['label'] }}
        </h1>
        @if (!empty($current['desc']))
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $current['desc'] }}</p>
        @endif
    </div>

    <div class="flex items-center gap-2 shrink-0">
        <a href="{{ url('/store/' . $store->slug . '/pos') }}"
           class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>{{ __('messages.back_to_pos') }}</span>
        </a>
    </div>
</div>
