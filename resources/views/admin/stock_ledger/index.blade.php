@extends('layouts.admin.app')

@section('title', __('messages.sidebar_stock_ledger') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="w-full space-y-5 sm:space-y-6">

    {{-- ============================================================
         PAGE HEADER — standard admin-page-header pattern
         ============================================================ --}}
    <div class="admin-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400">
                {{ __('messages.sidebar_inventory') ?? 'Inventory' }}
            </p>
            <h1 class="admin-page-title mt-0.5">
                {{ __('messages.stock_ledger_title') }}
            </h1>
            <p class="admin-page-sub mt-1">
                {{ $store->name }} · {{ __('messages.stock_ledger_sub') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{-- Switch to Bin Card --}}
            <a href="{{ route('store.admin.stock_ledger.bin_card', ['store_slug' => $store->slug]) }}"
               class="admin-secondary-btn">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span class="hidden sm:inline">{{ __('messages.stock_ledger_bin_card') }}</span>
            </a>
            {{-- Export CSV --}}
            <a href="{{ route('store.admin.stock_ledger.export', array_merge(['store_slug' => $store->slug], request()->all())) }}"
               class="admin-primary-btn">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span class="hidden sm:inline">{{ __('messages.stock_ledger_export_csv') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         KPI Summary Hairline Grid — standard admin-hairline-grid pattern
         ============================================================ --}}
    <div class="admin-hairline-grid grid-cols-2 sm:grid-cols-4">
        {{-- Total Movements --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label">{{ __('messages.stock_ledger_stat_total') }}</div>
            <div class="admin-stat-value">{{ number_format($metrics['total_records']) }}</div>
            <div class="admin-stat-sub">{{ number_format($metrics['unique_products']) }} {{ __('messages.stock_ledger_stat_products') }}</div>
        </div>
        {{-- Total Inbound --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">{{ __('messages.stock_ledger_stat_inflow') }}</div>
            <div class="admin-stat-value text-emerald-600 dark:text-emerald-400">+{{ number_format($metrics['total_inflow'], 3) }}</div>
            <div class="admin-stat-sub">Purchases, Returns, Adj.</div>
        </div>
        {{-- Total Outbound --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-rose-600 dark:text-rose-400">{{ __('messages.stock_ledger_stat_outflow') }}</div>
            <div class="admin-stat-value text-rose-600 dark:text-rose-400">-{{ number_format($metrics['total_outflow'], 3) }}</div>
            <div class="admin-stat-sub">Sales, Transfers, Adj.</div>
        </div>
        {{-- Net Delta --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label {{ $metrics['net_delta'] >= 0 ? 'text-violet-600 dark:text-violet-400' : 'text-rose-600 dark:text-rose-400' }}">
                {{ __('messages.stock_ledger_stat_net') }}
            </div>
            <div class="admin-stat-value {{ $metrics['net_delta'] >= 0 ? 'text-violet-600 dark:text-violet-400' : 'text-rose-600 dark:text-rose-400' }}">
                {{ $metrics['net_delta'] >= 0 ? '+' : '' }}{{ number_format($metrics['net_delta'], 3) }}
            </div>
            <div class="admin-stat-sub">Net On-Hand Movement</div>
        </div>
    </div>

    {{-- ============================================================
         STANDARD Toolbar — matching x-admin.toolbar component style
         ============================================================ --}}
    <div class="rounded-2xl sm:rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm p-2.5 sm:p-3.5 mb-5 sm:mb-6 transition">
        <form method="GET" action="{{ route('store.admin.stock_ledger.index', ['store_slug' => $store->slug]) }}">

            {{-- Top Row: Flow Tabs + Date Presets + Filter submit --}}
            <div class="flex items-center gap-2 overflow-x-auto pb-1 pt-0.5 -mx-1 px-1 scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-700">

                {{-- Flow Tabs (All / Inbound / Outbound) --}}
                <div class="flex items-center bg-slate-100 dark:bg-slate-800/90 p-1 rounded-xl border border-slate-200/80 dark:border-slate-700 shrink-0 shadow-inner">
                    @foreach([
                        'all' => __('messages.stock_ledger_all_movements'),
                        'inflow' => __('messages.stock_ledger_inflow_tab'),
                        'outflow' => __('messages.stock_ledger_outflow_tab'),
                    ] as $flowVal => $flowLabel)
                        <button type="submit" name="flow" value="{{ $flowVal }}"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap
                                    @if(($filters['flow'] ?? 'all') === $flowVal)
                                        bg-white dark:bg-slate-700 shadow-sm
                                        {{ $flowVal === 'inflow' ? 'text-emerald-600 dark:text-emerald-400' : ($flowVal === 'outflow' ? 'text-rose-600 dark:text-rose-400' : 'text-blue-600 dark:text-blue-400') }}
                                    @else
                                        text-slate-500 hover:text-slate-900 dark:hover:text-white
                                    @endif">
                            {{ $flowLabel }}
                        </button>
                    @endforeach
                </div>

                {{-- Vertical divider --}}
                <span class="hidden sm:inline-block w-px h-6 bg-slate-200 dark:bg-slate-700 shrink-0"></span>

                {{-- Date Preset Buttons --}}
                @php
                    $datePresets = [
                        'today' => __('messages.today'),
                        'yesterday' => __('messages.yesterday'),
                        '7days' => __('messages.7days'),
                        'this_month' => __('messages.this_month'),
                        'last_month' => __('messages.last_month'),
                        'all' => __('messages.all'),
                    ];
                @endphp
                @foreach($datePresets as $key => $label)
                    <button type="submit" name="preset" value="{{ $key }}"
                            class="shrink-0 min-h-[42px] px-3.5 py-2 rounded-xl border text-xs font-bold transition whitespace-nowrap shadow-sm
                                {{ $preset === $key
                                    ? 'bg-violet-600 text-white border-violet-600'
                                    : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                        {{ $label }}
                    </button>
                @endforeach

                {{-- Spacer --}}
                <span class="hidden sm:inline-block w-px h-6 bg-slate-200 dark:bg-slate-700 shrink-0"></span>

                {{-- Search Box --}}
                <div class="hidden sm:relative sm:flex items-center sm:w-60 lg:w-72">
                    <input type="text"
                           name="search"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="{{ __('messages.search') }} product, SKU..."
                           class="w-full pl-9 pr-3.5 py-2.5 min-h-[42px] border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/70 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors shadow-inner">
                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                {{-- Movement Type Filter --}}
                <div class="relative shrink-0">
                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <select name="movement_type"
                            class="border border-slate-200 dark:border-slate-700 rounded-xl pl-9 pr-8 min-h-[42px] py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/70 text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-violet-500 cursor-pointer appearance-none shadow-sm transition">
                        <option value="">{{ __('messages.stock_ledger_all_types') }}</option>
                        @foreach($movementTypes as $type)
                            <option value="{{ $type->value }}" {{ ($filters['movement_type'] ?? '') === $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                    <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                {{-- Warehouse Filter --}}
                @if($warehouses->count() > 1)
                    <div class="relative shrink-0">
                        <select name="warehouse_id"
                                class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 min-h-[42px] py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/70 text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-violet-500 cursor-pointer appearance-none shadow-sm transition">
                            <option value="">{{ __('messages.all_warehouses') }}</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ ($filters['warehouse_id'] ?? '') == $wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Filter / Reset Buttons --}}
                <div class="flex items-center gap-1 ml-auto shrink-0">
                    @if(!empty($filters['search']) || !empty($filters['movement_type']) || !empty($filters['warehouse_id']) || ($filters['flow'] ?? 'all') !== 'all' || $preset !== 'this_month')
                        <a href="{{ route('store.admin.stock_ledger.index', ['store_slug' => $store->slug]) }}"
                           class="shrink-0 min-h-[42px] px-3.5 py-2 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition shadow-sm">
                            {{ __('messages.reset') }}
                        </a>
                    @endif
                    <button type="submit"
                            class="shrink-0 min-h-[42px] px-4 py-2 bg-violet-600 hover:bg-violet-500 text-white rounded-xl text-xs sm:text-sm font-bold shadow-sm flex items-center gap-1.5 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>{{ __('messages.filter') }}</span>
                    </button>
                </div>

                {{-- Result Count --}}
                <span class="shrink-0 ml-1 inline-flex items-center px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700 text-xs font-black text-slate-600 dark:text-slate-300 font-mono whitespace-nowrap shadow-inner">
                    {{ number_format($movements->total()) }} items
                </span>
            </div>
        </form>
    </div>

    {{-- ============================================================
         Stock Ledger Table
         ============================================================ --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="font-bold text-slate-900 dark:text-slate-100 font-outfit text-base">
                {{ __('messages.stock_ledger_all_movements') }}
            </h2>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                {{ $movements->total() }} {{ __('messages.stock_ledger_stat_total') }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/75 dark:bg-slate-800/50 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold">
                    <tr>
                        <th class="px-4 py-3">{{ __('messages.stock_ledger_date') }}</th>
                        <th class="px-4 py-3">{{ __('messages.product') }}</th>
                        <th class="px-4 py-3">{{ __('messages.stock_ledger_movement_type') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.stock_ledger_delta_qty') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.stock_ledger_unit_cost') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.stock_ledger_total_value') }}</th>
                        <th class="px-4 py-3">{{ __('messages.stock_ledger_reference') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($movements as $m)
                        @php
                            $delta = (float) $m->quantity_delta;
                            $cost  = (float) $m->unit_cost;
                            $totalVal = round(abs($delta) * $cost, 2);
                            $typeEnum = $m->type();
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">

                            {{-- Date & User --}}
                            <td class="px-4 py-3 whitespace-nowrap text-xs">
                                <div class="font-mono text-slate-900 dark:text-slate-100 font-medium">
                                    {{ $m->occurred_at ? $m->occurred_at->format('d/m/Y H:i') : '-' }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    {{ $m->postedBy?->name ?? 'System' }}
                                </div>
                            </td>

                            {{-- Product --}}
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-sm">
                                    {{ $m->product?->name ?? 'Product #' . $m->product_id }}
                                </div>
                                <div class="text-xs font-mono text-slate-400 mt-0.5">
                                    SKU: {{ $m->product?->sku ?? '-' }}
                                </div>
                            </td>

                            {{-- Movement Type Badge --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($delta > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                        {{ $typeEnum->label() }}
                                    </span>
                                @elseif($delta < 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                        {{ $typeEnum->label() }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $typeEnum->label() }}
                                    </span>
                                @endif
                            </td>

                            {{-- Quantity Delta --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap font-mono font-bold text-sm {{ $delta > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($delta < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400') }}">
                                {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 3) }}
                            </td>

                            {{-- Unit Cost --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap font-mono text-xs text-slate-600 dark:text-slate-400">
                                {{ number_format($cost, 2) }}
                            </td>

                            {{-- Total Value --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap font-mono font-bold text-xs text-slate-900 dark:text-slate-100">
                                {{ number_format($totalVal, 2) }} <span class="text-[10px] text-slate-400">MMK</span>
                            </td>

                            {{-- Reference --}}
                            <td class="px-4 py-3 text-xs">
                                @if($m->source_type)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ ucfirst($m->source_type) }} {{ $m->source_id ? '#' . $m->source_id : '' }}
                                    </span>
                                @elseif($m->client_transaction_id)
                                    <span class="text-xs font-mono text-slate-400 truncate max-w-[140px] block" title="{{ $m->client_transaction_id }}">
                                        {{ Str::limit($m->client_transaction_id, 16) }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            {{-- Action —  View Bin Card --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if($m->product_id)
                                    <a href="{{ route('store.admin.stock_ledger.bin_card', ['store_slug' => $store->slug, 'product' => $m->product_id]) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg text-violet-600 hover:bg-violet-50 dark:text-violet-400 dark:hover:bg-violet-950/40 transition">
                                        <span>{{ __('messages.stock_ledger_bin_card') }}</span>
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <p class="text-sm font-semibold">{{ __('messages.stock_ledger_no_movements') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $movements->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
