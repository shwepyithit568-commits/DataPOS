@extends('layouts.admin.app')

@section('title', __('messages.inv_val_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-0.5 pb-6"
     x-data="{
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

    {{-- ============================================================
         1. TOP ULTRA-DENSE HEADER BANNER (Standard v4.1)
         ============================================================ --}}
    <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none transition">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('store.admin.dashboard', $storeRouteParams) }}"
               class="h-6 w-6 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition active:scale-95 shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="w-6 h-6 rounded bg-cyan-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs shrink-0">
                <span>💎</span>
            </div>
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-950/60 px-1.5 py-0.5 rounded border border-cyan-200/50 dark:border-cyan-800/50 truncate max-w-[120px] sm:max-w-none">
                    {{ $store->name }}
                </span>
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight truncate">
                    {{ __('messages.inv_val_title') }}
                </h1>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono hidden md:inline">
                    · {{ number_format($metrics['total_items_count']) }} SKUs · {{ number_format($metrics['total_units']) }} Units
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-1.5 shrink-0 self-end sm:self-auto">
            <a href="{{ route('store.admin.stock_ledger.index', $storeRouteParams) }}"
               class="sf-btn-3d h-7 px-2 sm:px-2.5 rounded-md text-[11px] sm:text-xs font-bold inline-flex items-center gap-1 cursor-pointer">
                <span>📜</span>
                <span class="hidden sm:inline">{{ __('messages.stock_ledger_title') ?? 'Stock Ledger' }}</span>
            </a>
            <a href="{{ route('store.admin.inventory_valuation.print', array_merge($storeRouteParams, request()->only(['search', 'category_id', 'brand_id', 'stock_status', 'sort']))) }}"
               target="_blank"
               class="sf-btn-3d-primary h-7 px-2 sm:px-2.5 rounded-md text-[11px] sm:text-xs font-bold inline-flex items-center gap-1 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                <span>{{ __('messages.inv_val_print_statement') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. 4 KEY VALUATION KPI CARDS (Standard v4.1 Centered Row-based)
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1 select-none">
        {{-- Total Cost Value --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.inv_val_total_cost') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border-rose-100 dark:border-rose-900/50">🏷️</div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.inv_val_total_cost') }}
                </div>
                <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ format_currency($metrics['total_cost_value'], $store) }}</span>
                </div>
            </div>
        </div>

        {{-- Total Retail Value --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.inv_val_total_retail') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border-sky-100 dark:border-sky-900/50">🛍️</div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.inv_val_total_retail') }}
                </div>
                <div class="text-sm sm:text-base font-black text-sky-600 dark:text-sky-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ format_currency($metrics['total_retail_value'], $store) }}</span>
                </div>
            </div>
        </div>

        {{-- Potential Gross Profit --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.inv_val_potential_profit') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/50">📈</div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.inv_val_potential_profit') }}
                </div>
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ format_currency($metrics['potential_profit'], $store) }}</span>
                </div>
            </div>
        </div>

        {{-- Total Units On Hand --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.inv_val_units_on_hand') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border-indigo-100 dark:border-indigo-900/50">📦</div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.inv_val_units_on_hand') }}
                </div>
                <div class="text-sm sm:text-base font-black text-indigo-600 dark:text-indigo-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ number_format($metrics['total_units']) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Category Valuation Distribution Progress --}}
    @if (!empty($categoryBreakdown))
        <div class="rounded-lg bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs space-y-1.5">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center justify-between">
                <span>{{ __('messages.inv_val_category_breakdown') }}</span>
                <span>{{ count($categoryBreakdown) }} Categories</span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach (array_slice($categoryBreakdown, 0, 6) as $cat)
                    <div class="p-3 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ $cat['name'] }}</span>
                            <span class="font-black text-rose-600 dark:text-rose-400 font-mono">{{ format_currency($cat['cost_value'], $store) }}</span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-cyan-500 h-full rounded-full" style="width: {{ min(100, max(5, $cat['percent'])) }}%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400">
                            <span>{{ $cat['items_count'] }} items ({{ number_format($cat['total_qty']) }} units)</span>
                            <span class="font-bold text-slate-600 dark:text-slate-300">{{ $cat['percent'] }}% of stock</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 4. Unified Admin Toolbar --}}
    @php
        $categoryFilterOptions = [];
        foreach ($categories as $c) {
            $categoryFilterOptions[$c->id] = $c->name;
        }

        $brandFilterOptions = [];
        foreach ($brands as $b) {
            $brandFilterOptions[$b->id] = $b->name;
        }

        $stockStatusFilterOptions = [
            'in_stock'     => __('messages.inv_val_in_stock'),
            'out_of_stock' => __('messages.inv_val_out_of_stock'),
            'zero_cost'    => __('messages.inv_val_zero_cost'),
        ];

        $exportUrl = route('store.admin.inventory_valuation.export', array_merge($storeRouteParams, request()->only(['search', 'sort', 'category_id', 'brand_id', 'stock_status'])));
    @endphp

    <x-admin.toolbar
        :search="request('search', $filters['search'] ?? '')"
        :searchPlaceholder="__('messages.inv_val_filter_search')"
        :sort="request('sort', $filters['sort'] ?? 'cost_value_desc')"
        :sortOptions="[
            'cost_value_desc'   => __('messages.inv_val_sort_cost_desc'),
            'cost_value_asc'    => __('messages.inv_val_sort_cost_asc'),
            'retail_value_desc' => __('messages.inv_val_sort_retail_desc'),
            'retail_value_asc'  => __('messages.inv_val_sort_retail_asc'),
            'qty_desc'          => __('messages.inv_val_sort_qty_desc'),
            'qty_asc'           => __('messages.inv_val_sort_qty_asc'),
            'margin_desc'       => __('messages.inv_val_sort_margin_desc'),
            'name_asc'          => __('messages.inv_val_sort_name_asc'),
        ]"
        :filters="[
            'category_id' => [
                'label' => __('messages.categories'),
                'options' => $categoryFilterOptions,
            ],
            'brand_id' => [
                'label' => __('messages.brand'),
                'options' => $brandFilterOptions,
            ],
            'stock_status' => [
                'label' => __('messages.inv_val_stock_status'),
                'options' => $stockStatusFilterOptions,
            ],
        ]"
        :viewMode="'table'"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$exportUrl"
        :totalCount="$products instanceof \Illuminate\Pagination\LengthAwarePaginator ? $products->total() : $products->count()"
        :paginator="$products instanceof \Illuminate\Pagination\LengthAwarePaginator ? $products : null"
        :perPageOptions="[25 => '25', 50 => '50', 100 => '100', 'all' => 'All']"
    />

    {{-- 5. Card Grid View --}}
    <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($products as $p)
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4 group">
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <span class="font-mono text-xs font-black text-slate-800 dark:text-slate-200">
                                {{ $p->sku ?? 'NO-SKU' }}
                            </span>
                            <h3 class="font-black text-sm text-slate-900 dark:text-slate-100 group-hover:text-cyan-600 transition mt-0.5">
                                {{ $p->name }}
                            </h3>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ $p->category?->name ?? 'General' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">{{ __('messages.reports_qty') }}</span>
                            <span class="font-mono font-black text-slate-800 dark:text-slate-200 text-sm">
                                {{ format_quantity($p->computed_qty, $store) }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">{{ __('messages.reports_avg_cost') }}</span>
                            <span class="font-mono font-black text-slate-800 dark:text-slate-200 text-sm">
                                {{ format_currency($p->computed_cost, $store) }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-rose-50/60 dark:bg-rose-950/30 col-span-2 flex items-center justify-between">
                            <span class="text-rose-600 dark:text-rose-400 text-xs font-bold">{{ __('messages.inv_val_total_cost') }}</span>
                            <span class="font-mono font-black text-rose-700 dark:text-rose-300 text-sm">
                                {{ format_currency($p->computed_cost_value, $store) }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 col-span-2 flex items-center justify-between">
                            <span class="text-emerald-600 dark:text-emerald-400 text-xs font-bold">{{ __('messages.inv_val_potential_profit') }} ({{ $p->computed_margin }}%)</span>
                            <span class="font-mono font-black text-emerald-700 dark:text-emerald-300 text-sm">
                                {{ format_currency($p->computed_profit, $store) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                {{ __('messages.inv_val_no_products') }}
            </div>
        @endforelse
    </div>

    {{-- 6. Table View --}}
    <div x-show="viewMode === 'table'" class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4">{{ __('messages.sku_item') }}</th>
                        <th class="py-3.5 px-4">Category & Brand</th>
                        <th class="py-3.5 px-4 text-center">{{ __('messages.reports_qty') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.cost') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.inv_val_total_cost') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.retail_price') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.inv_val_total_retail') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.inv_val_potential_profit') }}</th>
                        <th class="py-3.5 px-4 text-center">{{ __('messages.margin') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($products as $p)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4">
                                <div class="font-mono text-xs font-bold text-slate-500 dark:text-slate-400">
                                    {{ $p->sku ?? '-' }}
                                </div>
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-sm">
                                    {{ $p->name }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $p->category?->name ?? 'General' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $p->brand?->name ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-center font-black text-slate-900 dark:text-slate-100 tabular-nums">
                                <span class="px-2.5 py-1 rounded-xl text-xs {{ $p->computed_qty > 0 ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300' }}">
                                    {{ format_quantity($p->computed_qty, $store) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-semibold text-slate-700 dark:text-slate-300 tabular-nums">
                                {{ format_currency($p->computed_cost, $store) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-black text-rose-600 dark:text-rose-400 tabular-nums">
                                {{ format_currency($p->computed_cost_value, $store) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300 tabular-nums">
                                {{ format_currency((float) $p->retail_price, $store) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-sky-600 dark:text-sky-400 tabular-nums">
                                {{ format_currency($p->computed_retail_value, $store) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                {{ format_currency($p->computed_profit, $store) }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $p->computed_margin >= 30 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : ($p->computed_margin > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400') }}">
                                    {{ $p->computed_margin }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                                {{ __('messages.inv_val_no_products') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
