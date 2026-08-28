@php
    $titles = [
        'sales' => ['label' => __('messages.reports_sales'), 'desc' => 'POS Invoices, Daily Totals & Payment Methods', 'icon' => '🧾'],
        'cash'  => ['label' => __('messages.reports_cash'), 'desc' => 'Cashier Shifts, Cash In/Out & Drawer Reconciliation', 'icon' => '💵'],
        'stock' => ['label' => __('messages.reports_stock'), 'desc' => 'On-Hand Inventory Balances, Average Costs & Valuation', 'icon' => '📦'],
    ];
    $current = $titles[$active] ?? ['label' => __('messages.reports_title'), 'desc' => '', 'icon' => '📊'];

    $tabLinks = [
        'sales' => ['name' => 'pos.reports.sales', 'label' => __('messages.reports_sales'), 'icon' => '🧾'],
        'cash'  => ['name' => 'pos.reports.cash', 'label' => __('messages.reports_cash'), 'icon' => '💵'],
        'stock' => ['name' => 'pos.reports.stock', 'label' => __('messages.reports_stock'), 'icon' => '📦'],
    ];
@endphp

<div class="space-y-2.5">
    {{-- Header --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
            <div class="min-w-0">
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-sky-50 dark:bg-sky-950/60 border border-sky-200/80 dark:border-sky-800 text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-sky-700 dark:text-sky-300">
                    <span>{{ $current['icon'] }} {{ __('messages.sidebar_reports') }}</span>
                    <span class="text-sky-400">·</span>
                    <span>{{ $current['label'] }}</span>
                </div>
                <h1 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-slate-100 font-outfit mt-0.5 truncate">
                    {{ $current['label'] }}
                </h1>
                @if (!empty($current['desc']))
                    <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 truncate">
                        {{ $store->name }} · {{ $current['desc'] }}
                    </p>
                @endif
            </div>

            {{-- Header Actions --}}
            <div class="flex items-center gap-1.5 shrink-0">
                <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 text-xs font-bold transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                    <span>{{ __('messages.admin_dashboard') }}</span>
                </a>

                <a href="{{ url('/store/' . $store->slug . '/pos') }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-100 dark:hover:bg-white dark:text-slate-900 text-xs font-bold transition shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>{{ __('messages.back_to_pos') }}</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation Bar --}}
    <div class="flex items-center gap-1 p-1 bg-slate-200/70 dark:bg-slate-900/80 rounded-lg border border-slate-200/80 dark:border-slate-800 overflow-x-auto shadow-2xs">
        @foreach ($tabLinks as $tKey => $tab)
            @php $isActiveTab = ($active === $tKey); @endphp
            <a href="{{ route($tab['name'], ['store_slug' => $store->slug]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-bold whitespace-nowrap transition {{ $isActiveTab ? 'bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-white/50 dark:hover:bg-slate-800/50' }}">
                <span>{{ $tab['icon'] }}</span>
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
