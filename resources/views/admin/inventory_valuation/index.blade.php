@extends('layouts.admin.app')

@section('title', __('messages.inv_val_title') . ' - ' . ($store->name ?? 'DataPOS'))

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12"
     x-data="{
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 grid place-items-center text-xl sm:text-2xl font-bold shadow-sm flex-shrink-0">
                💎
            </span>
            <div class="min-w-0">
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span class="truncate">{{ __('messages.inv_val_title') }}</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $store->name }} · {{ __('messages.inv_val_subtitle') }}</p>
            </div>
        </div>

        {{-- Top Right Actions (Print Valuation Sheet & CSV Export) --}}
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <a href="{{ route('store.admin.inventory_valuation.print', array_merge($storeRouteParams, request()->only(['search', 'category_id', 'brand_id', 'stock_status', 'sort']))) }}"
               target="_blank"
               class="px-3.5 py-2 rounded-2xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition inline-flex items-center gap-1.5 active:scale-95">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                <span>{{ __('messages.inv_val_print_statement') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. 4 Key Valuation KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        
        {{-- Total Cost Value --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.inv_val_total_cost') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight tabular-nums">
                    Ks {{ number_format($metrics['total_cost_value']) }}
                </h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ number_format($metrics['total_items_count']) }} SKUs · {{ __('messages.stock_valuation_cost') }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                🏷️
            </span>
        </div>

        {{-- Total Retail Value --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.inv_val_total_retail') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-sky-600 dark:text-sky-400 font-mono tracking-tight tabular-nums">
                    Ks {{ number_format($metrics['total_retail_value']) }}
                </h3>
                <p class="text-[11px] text-sky-500 font-semibold mt-0.5">Wholesale: Ks {{ number_format($metrics['total_wholesale_value']) }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                🛍️
            </span>
        </div>

        {{-- Potential Gross Profit & Margin % --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.inv_val_potential_profit') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight tabular-nums">
                    Ks {{ number_format($metrics['potential_profit']) }}
                </h3>
                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold mt-0.5">Margin: {{ $metrics['potential_margin'] }}%</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                📈
            </span>
        </div>

        {{-- Total Units On Hand --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.inv_val_units_on_hand') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 font-mono tracking-tight tabular-nums">
                    {{ number_format($metrics['total_units']) }}
                </h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ $metrics['in_stock_count'] }} In Stock · {{ $metrics['low_stock_count'] }} Low</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                📦
            </span>
        </div>
    </div>

    {{-- 3. Category Valuation Distribution Progress --}}
    @if (!empty($categoryBreakdown))
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center justify-between">
                <span>{{ __('messages.inv_val_category_breakdown') }}</span>
                <span>{{ count($categoryBreakdown) }} Categories</span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach (array_slice($categoryBreakdown, 0, 6) as $cat)
                    <div class="p-3 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ $cat['name'] }}</span>
                            <span class="font-black text-rose-600 dark:text-rose-400 font-mono">Ks {{ number_format($cat['cost_value']) }}</span>
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
                                {{ number_format($p->computed_qty) }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">{{ __('messages.reports_avg_cost') }}</span>
                            <span class="font-mono font-black text-slate-800 dark:text-slate-200 text-sm">
                                Ks {{ number_format($p->computed_cost) }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-rose-50/60 dark:bg-rose-950/30 col-span-2 flex items-center justify-between">
                            <span class="text-rose-600 dark:text-rose-400 text-xs font-bold">{{ __('messages.inv_val_total_cost') }}</span>
                            <span class="font-mono font-black text-rose-700 dark:text-rose-300 text-sm">
                                Ks {{ number_format($p->computed_cost_value) }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 col-span-2 flex items-center justify-between">
                            <span class="text-emerald-600 dark:text-emerald-400 text-xs font-bold">{{ __('messages.inv_val_potential_profit') }} ({{ $p->computed_margin }}%)</span>
                            <span class="font-mono font-black text-emerald-700 dark:text-emerald-300 text-sm">
                                Ks {{ number_format($p->computed_profit) }}
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
                        <th class="py-3.5 px-4">SKU / Item</th>
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
                                    {{ number_format($p->computed_qty) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-semibold text-slate-700 dark:text-slate-300 tabular-nums">
                                Ks {{ number_format($p->computed_cost) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-black text-rose-600 dark:text-rose-400 tabular-nums">
                                Ks {{ number_format($p->computed_cost_value) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300 tabular-nums">
                                Ks {{ number_format((float) $p->retail_price) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-sky-600 dark:text-sky-400 tabular-nums">
                                Ks {{ number_format($p->computed_retail_value) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                Ks {{ number_format($p->computed_profit) }}
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
