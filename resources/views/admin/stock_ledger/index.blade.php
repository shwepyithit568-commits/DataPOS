@extends('layouts.admin.app')

@section('title', __('messages.sidebar_stock_ledger') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    $fmtQty = function($v) {
        $val = (float) $v;
        return $val == (int) $val ? number_format($val, 0) : rtrim(rtrim(number_format($val, 3), '0'), '.');
    };
@endphp
<div class="w-full space-y-0.5 pb-6"
     x-data="{ viewMode: localStorage.getItem('admin_view_mode') || 'table' }"
     @view-changed.window="viewMode = $event.detail">

    {{-- ============================================================
         1. COMPACT PAGE HEADER (34px - 38px Standard Height)
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400 grid place-items-center text-base font-bold shadow-xs shrink-0">
                📑
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.stock_ledger_title') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                    <span class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                        {{ number_format($movements->total()) }}
                    </span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.stock_ledger_sub') }}
                </p>
            </div>
        </div>

        {{-- Header Actions: Excel Export + Bin Card Switcher --}}
        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0 flex-wrap">
            {{-- Excel Export Button --}}
            @if(!empty($exportUrl))
            <a href="{{ $exportUrl }}"
               title="Export Excel (.xlsx)"
               class="h-7 px-2.5 rounded-md text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <span>Excel</span>
            </a>
            @endif

            {{-- Switch to Bin Card --}}
            <a href="{{ route('store.admin.stock_ledger.bin_card', ['store_slug' => $store->slug]) }}"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 dark:hover:bg-violet-900/60 shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <span>📑</span>
                <span>{{ __('messages.stock_ledger_bin_card') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. SUMMARY STAT CARDS (Row-based Center Alignment Standard)
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-0.5 sm:gap-1" role="list">
        {{-- Total Records --}}
        <a role="listitem"
           href="{{ route('store.admin.stock_ledger.index', array_merge(['store_slug' => $store->slug], request()->except('page', 'flow'))) }}"
           class="px-3 py-1.5 rounded-lg border shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition {{ empty($filters['flow']) ? 'border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-900' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 shadow-inner text-xs sm:text-sm font-bold">
                📊
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ number_format($metrics['total_records']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_ledger_stat_total') }}
                </p>
            </div>
        </a>

        {{-- Total Inbound --}}
        <a role="listitem"
           href="{{ route('store.admin.stock_ledger.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['flow' => 'inflow'])) }}"
           class="px-3 py-1.5 rounded-lg border shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition {{ ($filters['flow'] ?? '') === 'inflow' ? 'border-emerald-600 bg-emerald-50/60 dark:border-emerald-500 dark:bg-emerald-950/40 ring-1 ring-emerald-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-emerald-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                📥
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit">
                    +{{ $fmtQty($metrics['total_inflow']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_ledger_stat_inflow') }}
                </p>
            </div>
        </a>

        {{-- Total Outbound --}}
        <a role="listitem"
           href="{{ route('store.admin.stock_ledger.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['flow' => 'outflow'])) }}"
           class="px-3 py-1.5 rounded-lg border shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition {{ ($filters['flow'] ?? '') === 'outflow' ? 'border-rose-600 bg-rose-50/60 dark:border-rose-500 dark:bg-rose-950/40 ring-1 ring-rose-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-rose-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300 shadow-inner text-xs sm:text-sm font-bold">
                📤
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-outfit">
                    -{{ $fmtQty($metrics['total_outflow']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_ledger_stat_outflow') }}
                </p>
            </div>
        </a>

        {{-- Net Delta --}}
        <div role="listitem"
             class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-violet-200/80 dark:border-violet-900/60 bg-violet-50/20 dark:bg-violet-950/20 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-violet-600 text-white shadow-inner text-xs sm:text-sm font-bold">
                ⚡
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black leading-none tabular-nums font-outfit {{ $metrics['net_delta'] >= 0 ? 'text-violet-600 dark:text-violet-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ $metrics['net_delta'] >= 0 ? '+' : '' }}{{ $fmtQty($metrics['net_delta']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-violet-900 dark:text-violet-300 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_ledger_stat_net') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. STANDARD TOOLBAR
         ============================================================ --}}
    <x-admin.toolbar
        :showSearch="true"
        :searchPlaceholder="__('messages.search') . ' product, SKU, ref...'"
        :searchValue="$filters['search'] ?? ''"
        :filterCount="$activeFiltersCount ?? 0"
        :showViewToggle="true"
        :activeView="'table'"
        :showExcel="true"
        :excelUrl="$exportUrl ?? null"
        :showPagination="true"
        :paginator="$movements"
        :showPerPageSelector="true"
        :perPageOptions="[
            25    => '25',
            50    => '50',
            100   => '100',
            200   => '200',
            'all' => __('messages.all'),
        ]"
    >
        {{-- Quick Flow Filter Tabs inside toolbar --}}
        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-0.5 rounded-lg border border-slate-200/80 dark:border-slate-700 text-xs shrink-0">
            @foreach([
                'all' => __('messages.stock_ledger_all_movements'),
                'inflow' => __('messages.stock_ledger_inflow_tab'),
                'outflow' => __('messages.stock_ledger_outflow_tab'),
            ] as $flowVal => $flowLabel)
                <a href="{{ route('store.admin.stock_ledger.index', array_merge(['store_slug' => $store->slug], request()->query(), ['flow' => $flowVal, 'page' => 1])) }}"
                   class="h-6 px-2.5 rounded-md text-xs font-bold transition whitespace-nowrap inline-flex items-center {{ ($filters['flow'] ?? 'all') === $flowVal ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-2xs font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                    {{ $flowLabel }}
                </a>
            @endforeach
        </div>

        {{-- Filter Dropdown Slot --}}
        <x-slot:filterSlot>
            <div class="space-y-2.5 p-1">
                {{-- Date Presets --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        {{ __('messages.date') }}
                    </label>
                    <div class="grid grid-cols-3 gap-1">
                        @foreach([
                            'today' => __('messages.today'),
                            'yesterday' => __('messages.yesterday'),
                            '7days' => __('messages.7days'),
                            'this_month' => __('messages.this_month'),
                            'last_month' => __('messages.last_month'),
                            'all' => __('messages.all'),
                        ] as $key => $label)
                            <a href="{{ route('store.admin.stock_ledger.index', array_merge(['store_slug' => $store->slug], request()->query(), ['preset' => $key, 'page' => 1])) }}"
                               class="h-6 px-1.5 inline-flex items-center justify-center text-center text-xs font-bold rounded border transition {{ ($preset ?? 'this_month') === $key ? 'bg-violet-600 text-white border-violet-600 shadow-2xs' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Movement Type Filter --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        {{ __('messages.stock_ledger_movement_type') }}
                    </label>
                    <select name="movement_type" data-auto-submit class="w-full h-7 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 text-slate-900 dark:text-slate-100 font-semibold focus:outline-none focus:ring-1 focus:ring-violet-500">
                        <option value="">-- {{ __('messages.stock_ledger_all_types') }} --</option>
                        @foreach($movementTypes as $type)
                            <option value="{{ $type->value }}" {{ ($filters['movement_type'] ?? '') === $type->value ? 'selected' : '' }}>
                                {{ __('messages.movement_type_' . $type->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Warehouse Filter --}}
                @if($warehouses->count() > 1)
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                            {{ __('messages.warehouse') ?? 'Warehouse' }}
                        </label>
                        <select name="warehouse_id" data-auto-submit class="w-full h-7 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 text-slate-900 dark:text-slate-100 font-semibold focus:outline-none focus:ring-1 focus:ring-violet-500">
                            <option value="">-- {{ __('messages.all_warehouses') }} --</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ ($filters['warehouse_id'] ?? '') == $wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($activeFiltersCount > 0)
                    <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800">
                        <a href="{{ route('store.admin.stock_ledger.index', ['store_slug' => $store->slug]) }}"
                           class="block w-full text-center px-2.5 py-1 text-xs font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/40 rounded-md hover:bg-rose-100 transition">
                            {{ __('messages.reset') }}
                        </a>
                    </div>
                @endif
            </div>
        </x-slot:filterSlot>
    </x-admin.toolbar>

    {{-- ============================================================
         4. SPREADSHEET DATA GRID TABLE (TABLE VIEW)
         ============================================================ --}}
    <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto max-h-[75vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                {{-- Sticky Header --}}
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b border-slate-200 dark:border-slate-700 shadow-2xs select-none">
                    <tr class="text-[10px] sm:text-[11px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-wider divide-x divide-slate-200 dark:divide-slate-700">
                        <th class="py-1.5 px-2.5 min-w-[130px]">{{ __('messages.stock_ledger_date') }}</th>
                        <th class="py-1.5 px-2.5 min-w-[200px]">{{ __('messages.product') }}</th>
                        <th class="py-1.5 px-2.5 min-w-[140px]">{{ __('messages.stock_ledger_movement_type') }}</th>
                        <th class="py-1.5 px-2.5 text-right min-w-[100px]">{{ __('messages.stock_ledger_delta_qty') }}</th>
                        <th class="py-1.5 px-2.5 text-right min-w-[110px]">{{ __('messages.stock_ledger_unit_cost') }}</th>
                        <th class="py-1.5 px-2.5 text-right min-w-[120px]">{{ __('messages.stock_ledger_total_value') }}</th>
                        <th class="py-1.5 px-2.5 min-w-[130px]">{{ __('messages.stock_ledger_reference') }}</th>
                        <th class="py-1.5 px-2 text-center w-28">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                {{-- Table Body --}}
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse($movements as $m)
                        @php
                            $delta = (float) $m->quantity_delta;
                            $cost  = (float) $m->unit_cost;
                            $totalVal = round(abs($delta) * $cost, 2);
                            $typeEnum = $m->type();
                        @endphp
                        <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">

                            {{-- Date & Posted User --}}
                            <td class="py-1.5 px-2.5 whitespace-nowrap">
                                <div class="font-mono text-slate-900 dark:text-slate-100 font-bold">
                                    {{ $m->occurred_at ? $m->occurred_at->format('d/m/Y H:i') : '-' }}
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    👤 {{ $m->postedBy?->name ?? 'System' }}
                                </div>
                            </td>

                            {{-- Product Name & SKU --}}
                            <td class="py-1.5 px-2.5">
                                <div class="font-bold text-slate-900 dark:text-slate-100 leading-tight truncate max-w-[220px]">
                                    {{ $m->product?->name ?? 'Product #' . $m->product_id }}
                                </div>
                                <div class="text-[10px] font-mono text-slate-400 mt-0.5 flex items-center gap-1.5">
                                    <span>SKU: {{ $m->product?->sku ?? '-' }}</span>
                                    @if($m->productVariant)
                                        <span class="px-1.5 py-0.2 rounded bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-sans font-bold">
                                            {{ $m->productVariant->name }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Movement Type Badge --}}
                            <td class="py-1.5 px-2.5 whitespace-nowrap">
                                @if($delta > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.2 text-[10px] font-black uppercase tracking-wider rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <span>📥</span>
                                        <span>{{ __('messages.movement_type_' . $m->movement_type) }}</span>
                                    </span>
                                @elseif($delta < 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.2 text-[10px] font-black uppercase tracking-wider rounded-full bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                        <span>📤</span>
                                        <span>{{ __('messages.movement_type_' . $m->movement_type) }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.2 text-[10px] font-black uppercase tracking-wider rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ __('messages.movement_type_' . $m->movement_type) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Quantity Delta --}}
                            <td class="py-1.5 px-2.5 text-right whitespace-nowrap font-mono font-black text-xs {{ $delta > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($delta < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400') }}">
                                {{ $delta > 0 ? '+' : '' }}{{ $fmtQty($delta) }}
                            </td>

                            {{-- Unit Cost --}}
                            <td class="py-1.5 px-2.5 text-right whitespace-nowrap font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">
                                {{ number_format($cost) }}
                            </td>

                            {{-- Total Value --}}
                            <td class="py-1.5 px-2.5 text-right whitespace-nowrap font-mono font-black text-xs text-slate-900 dark:text-slate-100">
                                {{ number_format($totalVal) }} <span class="text-[10px] font-normal text-slate-400">Ks</span>
                            </td>

                            {{-- Reference --}}
                            <td class="py-1.5 px-2.5 whitespace-nowrap text-xs">
                                @if($m->source_type)
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ ucfirst($m->source_type) }} {{ $m->source_id ? '#' . $m->source_id : '' }}
                                    </span>
                                @elseif($m->client_transaction_id)
                                    <span class="text-xs font-mono text-slate-400 truncate max-w-[130px] block" title="{{ $m->client_transaction_id }}">
                                        {{ Str::limit($m->client_transaction_id, 14) }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            {{-- Action — View Bin Card --}}
                            <td class="py-1.5 px-2 text-center whitespace-nowrap">
                                @if($m->product_id)
                                    <a href="{{ route('store.admin.stock_ledger.bin_card', ['store_slug' => $store->slug, 'product' => $m->product_id]) }}"
                                       class="h-6 px-2 text-xs font-bold rounded-md text-violet-600 hover:bg-violet-50 dark:text-violet-400 dark:hover:bg-violet-950/40 transition inline-flex items-center gap-1 active:scale-95">
                                        <span>📑</span>
                                        <span>{{ __('messages.stock_ledger_view_bin_card') ?? 'Bin Card' }}</span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-3xl mb-2">📦</span>
                                    <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('messages.stock_ledger_no_movements') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================
         5. RESPONSIVE CARDS VIEW GRID (CARD VIEW MODE)
         ============================================================ --}}
    <div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-1 sm:gap-1.5">
        @forelse($movements as $m)
            @php
                $delta = (float) $m->quantity_delta;
                $cost  = (float) $m->unit_cost;
                $totalVal = round(abs($delta) * $cost, 2);
                $typeEnum = $m->type();
            @endphp
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:border-violet-300 dark:hover:border-violet-600/50 hover:shadow-xs transition flex flex-col justify-between group overflow-hidden">
                {{-- Top Card Content --}}
                <div class="p-2.5 sm:p-3 space-y-2">
                    {{-- Header: Movement Type + Date/Time --}}
                    <div class="flex items-center justify-between gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                        @if($delta > 0)
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 text-[10px] font-black uppercase tracking-wider rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                <span>📥</span>
                                <span>{{ $typeEnum->label() }}</span>
                            </span>
                        @elseif($delta < 0)
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 text-[10px] font-black uppercase tracking-wider rounded-full bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                <span>📤</span>
                                <span>{{ $typeEnum->label() }}</span>
                            </span>
                        @else
                            <span class="inline-flex items-center px-1.5 py-0.2 text-[10px] font-black uppercase tracking-wider rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ $typeEnum->label() }}
                            </span>
                        @endif

                        <span class="text-[10px] font-mono text-slate-400">
                            {{ $m->occurred_at ? $m->occurred_at->format('d/m/Y H:i') : '-' }}
                        </span>
                    </div>

                    {{-- Product Info --}}
                    <div>
                        <div class="font-black text-xs sm:text-sm text-slate-900 dark:text-slate-100 line-clamp-1">
                            {{ $m->product?->name ?? 'Product #' . $m->product_id }}
                        </div>
                        <div class="text-[10px] font-mono text-slate-400 mt-0.5 flex items-center gap-1.5 flex-wrap">
                            <span>SKU: {{ $m->product?->sku ?? '-' }}</span>
                            @if($m->productVariant)
                                <span class="px-1.5 py-0.2 rounded bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 font-sans font-bold">
                                    {{ $m->productVariant->name }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Movement Hero Box (Delta + Value) --}}
                    <div class="p-2 rounded-md border {{ $delta > 0 ? 'bg-emerald-50/30 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-900/40' : ($delta < 0 ? 'bg-rose-50/30 dark:bg-rose-950/20 border-rose-100 dark:border-rose-900/40' : 'bg-slate-50 dark:bg-slate-800/50 border-slate-100 dark:border-slate-800') }} space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                {{ __('messages.stock_ledger_delta_qty') }}:
                            </span>
                            <span class="font-black font-mono text-xs sm:text-sm {{ $delta > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($delta < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400') }}">
                                {{ $delta > 0 ? '+' : '' }}{{ $fmtQty($delta) }} Qty
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-200/50 dark:border-slate-700/50 font-mono">
                            <span class="text-[10px] text-slate-400 font-sans">
                                {{ __('messages.stock_ledger_total_value') }} (@ {{ number_format($cost) }} Ks)
                            </span>
                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                {{ number_format($totalVal) }} Ks
                            </span>
                        </div>
                    </div>

                    {{-- Metadata Row: Source Ref & Posted User --}}
                    <div class="text-[10px] text-slate-500 dark:text-slate-400 space-y-0.5 pt-0.5">
                        <div class="flex items-center justify-between">
                            <span>Reference:</span>
                            @if($m->source_type)
                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    {{ ucfirst($m->source_type) }} {{ $m->source_id ? '#' . $m->source_id : '' }}
                                </span>
                            @elseif($m->client_transaction_id)
                                <span class="text-[10px] font-mono text-slate-400 truncate max-w-[120px]" title="{{ $m->client_transaction_id }}">
                                    {{ Str::limit($m->client_transaction_id, 12) }}
                                </span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between">
                            <span>{{ __('messages.stock_ledger_posted_by') }}:</span>
                            <span class="font-bold text-slate-700 dark:text-slate-300 truncate">👤 {{ $m->postedBy?->name ?? 'System' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Card Footer Action --}}
                <div class="p-2 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                    @if($m->product_id)
                        <a href="{{ route('store.admin.stock_ledger.bin_card', ['store_slug' => $store->slug, 'product' => $m->product_id]) }}"
                           class="w-full text-center px-2.5 py-1 rounded text-xs font-bold bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-950/60 dark:text-violet-300 transition inline-flex items-center justify-center gap-1 active:scale-95 shadow-2xs">
                            <span>📑</span>
                            <span>{{ __('messages.stock_ledger_view_bin_card') ?? 'Bin Card' }}</span>
                            <span>&rarr;</span>
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full p-8 text-center text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 rounded-lg border border-dashed border-slate-200 dark:border-slate-800 shadow-2xs">
                <span class="text-3xl mb-2 block">📦</span>
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('messages.stock_ledger_no_movements') }}</p>
            </div>
        @endforelse
    </div>

    {{-- Bottom Pagination --}}
    @if($movements->hasPages())
        <div class="p-1.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            {{ $movements->links() }}
        </div>
    @endif

</div>
@endsection
