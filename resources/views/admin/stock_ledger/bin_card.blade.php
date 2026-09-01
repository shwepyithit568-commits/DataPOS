@extends('layouts.admin.app')

@section('title', $product->name . ' - ' . __('messages.stock_ledger_bin_card') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    $fmtQty = function($v) {
        $val = (float) $v;
        return $val == (int) $val ? number_format($val, 0) : rtrim(rtrim(number_format($val, 3), '0'), '.');
    };
@endphp
<div class="w-full space-y-0.5 pb-6">

    {{-- ============================================================
         1. COMPACT PAGE HEADER (34px - 38px Standard Height)
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="min-w-0 flex-1">
            {{-- Breadcrumbs --}}
            <nav class="flex items-center gap-1.5 text-[10px] text-slate-400 dark:text-slate-500 mb-0.5">
                <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}"
                   class="hover:text-violet-600 dark:hover:text-violet-400 transition">{{ __('messages.admin_dashboard') }}</a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <a href="{{ route('store.admin.stock_ledger.index', ['store_slug' => $store->slug]) }}"
                   class="hover:text-violet-600 dark:hover:text-violet-400 transition">{{ __('messages.sidebar_stock_ledger') }}</a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="text-slate-600 dark:text-slate-300 font-bold truncate max-w-[200px]">{{ $product->name }}</span>
            </nav>

            <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex flex-wrap items-center gap-1.5 truncate">
                <span>{{ $product->name }}</span>
                <span class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                    SKU: {{ $product->sku }}
                </span>
                @if($product->category)
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        📁 {{ $product->category->name }}
                    </span>
                @endif
                @if($product->brand)
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        🏷️ {{ $product->brand->name }}
                    </span>
                @endif
            </h1>
        </div>

        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0 flex-wrap">
            {{-- Print Bin Card --}}
            <a href="{{ route('store.admin.stock_ledger.print_bin_card', array_merge(['store_slug' => $store->slug, 'product' => $product->id], request()->all())) }}"
               target="_blank"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition inline-flex items-center gap-1 shadow-2xs active:scale-95 cursor-pointer">
                <span>🖨️</span>
                <span>{{ __('messages.stock_ledger_print_bin_card') }}</span>
            </a>
            {{-- Back to Ledger --}}
            <a href="{{ route('store.admin.stock_ledger.index', ['store_slug' => $store->slug]) }}"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 dark:hover:bg-violet-900/60 transition inline-flex items-center gap-1 shadow-2xs active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>{{ __('messages.stock_ledger_all_movements') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. PRODUCT SWITCHER & DATE FILTER TOOLBAR
         ============================================================ --}}
    <div class="p-1.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <form method="GET" action="{{ route('store.admin.stock_ledger.bin_card', ['store_slug' => $store->slug, 'product' => $product->id]) }}">
            <div class="flex flex-wrap items-center gap-1.5">

                {{-- Product Selector Dropdown --}}
                <div class="relative min-w-[180px] flex-1 sm:max-w-xs">
                    <select name="product_id"
                            onchange="window.location.href='{{ route('store.admin.stock_ledger.bin_card', ['store_slug' => $store->slug]) }}/' + this.value"
                            class="w-full h-7 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 text-slate-900 dark:text-slate-100 font-bold focus:outline-none focus:ring-1 focus:ring-violet-500 cursor-pointer">
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ $p->id === $product->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Warehouse Selector if exists --}}
                @if($warehouses->count() > 1)
                    <div class="relative min-w-[130px]">
                        <select name="warehouse_id" data-auto-submit
                                class="w-full h-7 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 text-slate-900 dark:text-slate-100 font-bold focus:outline-none focus:ring-1 focus:ring-violet-500 cursor-pointer">
                            <option value="">-- {{ __('messages.all_warehouses') }} --</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ ($warehouseId ?? '') == $wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Date Presets Buttons --}}
                <div class="flex flex-wrap items-center gap-1">
                    @foreach([
                        'today' => __('messages.today'),
                        '7days' => __('messages.7days'),
                        'this_month' => __('messages.this_month'),
                        'last_month' => __('messages.last_month'),
                        'all' => __('messages.all'),
                    ] as $key => $label)
                        <button type="submit" name="preset" value="{{ $key }}"
                                class="h-7 px-2 rounded-md text-xs font-bold border transition {{ ($preset ?? 'this_month') === $key ? 'bg-violet-600 text-white border-violet-600 shadow-2xs' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Txn count badge --}}
                <span class="ml-auto inline-flex items-center h-7 px-2.5 rounded-md bg-slate-100 dark:bg-slate-800 text-xs font-mono font-black text-slate-600 dark:text-slate-300">
                    {{ count($binCardData['timeline']) }} Txn
                </span>
            </div>
        </form>
    </div>

    {{-- ============================================================
         3. SUMMARY STAT CARDS (Row-based Center Alignment Standard)
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-0.5 sm:gap-1" role="list">
        {{-- Opening Balance --}}
        <div role="listitem"
             class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 shadow-inner text-xs sm:text-sm font-bold">
                ⏱️
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ $fmtQty($binCardData['opening_balance']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_ledger_opening_balance') }}
                </p>
            </div>
        </div>

        {{-- Total In --}}
        <div role="listitem"
             class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-emerald-200/80 dark:border-emerald-900/60 bg-emerald-50/20 dark:bg-emerald-950/20 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                📥
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit">
                    +{{ $fmtQty($binCardData['total_in']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_ledger_in_qty') }}
                </p>
            </div>
        </div>

        {{-- Total Out --}}
        <div role="listitem"
             class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-rose-200/80 dark:border-rose-900/60 bg-rose-50/20 dark:bg-rose-950/20 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300 shadow-inner text-xs sm:text-sm font-bold">
                📤
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-outfit">
                    -{{ $fmtQty($binCardData['total_out']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_ledger_out_qty') }}
                </p>
            </div>
        </div>

        {{-- Current On-Hand Stock & Valuation --}}
        <div role="listitem"
             class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-violet-300 dark:border-violet-800 bg-violet-50/20 dark:bg-violet-950/20 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-violet-600 text-white shadow-inner text-xs sm:text-sm font-bold">
                🏢
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-violet-700 dark:text-violet-300 leading-none tabular-nums font-outfit">
                    {{ $fmtQty($binCardData['current_on_hand']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-violet-900 dark:text-violet-300 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_ledger_current_stock') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ============================================================
         4. SPREADSHEET DATA GRID TABLE (BIN CARD RUNNING TIMELINE)
         ============================================================ --}}
    <div id="data-table" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto max-h-[75vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                {{-- Sticky Header --}}
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b border-slate-200 dark:border-slate-700 shadow-2xs select-none">
                    <tr class="text-[10px] sm:text-[11px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-wider divide-x divide-slate-200 dark:divide-slate-700">
                        <th class="py-1.5 px-2.5 min-w-[130px]">{{ __('messages.stock_ledger_date') }}</th>
                        <th class="py-1.5 px-2.5 min-w-[150px]">{{ __('messages.stock_ledger_movement_type') }}</th>
                        <th class="py-1.5 px-2.5 min-w-[130px]">{{ __('messages.stock_ledger_reference') }}</th>
                        <th class="py-1.5 px-2.5 text-right text-emerald-700 dark:text-emerald-400 min-w-[90px]">{{ __('messages.stock_ledger_in_qty') }}</th>
                        <th class="py-1.5 px-2.5 text-right text-rose-700 dark:text-rose-400 min-w-[90px]">{{ __('messages.stock_ledger_out_qty') }}</th>
                        <th class="py-1.5 px-2.5 text-right font-black text-violet-700 dark:text-violet-300 min-w-[110px] bg-violet-50/70 dark:bg-violet-950/40">{{ __('messages.stock_ledger_running_balance') }}</th>
                        <th class="py-1.5 px-2.5 text-right min-w-[100px]">{{ __('messages.stock_ledger_unit_cost') }}</th>
                        <th class="py-1.5 px-2.5 min-w-[120px]">{{ __('messages.stock_ledger_posted_by') }}</th>
                    </tr>
                </thead>
                {{-- Table Body --}}
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse($binCardData['timeline'] as $item)
                        <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">

                            {{-- Date --}}
                            <td class="py-1.5 px-2.5 whitespace-nowrap font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ $item['occurred_at'] ? $item['occurred_at']->format('d/m/Y H:i') : '-' }}
                            </td>

                            {{-- Movement Type Badge --}}
                            <td class="py-1.5 px-2.5 whitespace-nowrap">
                                @if($item['quantity_delta'] > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.2 text-[10px] font-black uppercase tracking-wider rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <span>📥</span>
                                        <span>{{ __('messages.movement_type_' . $item['movement_type']) }}</span>
                                    </span>
                                @elseif($item['quantity_delta'] < 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.2 text-[10px] font-black uppercase tracking-wider rounded-full bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                        <span>📤</span>
                                        <span>{{ __('messages.movement_type_' . $item['movement_type']) }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.2 text-[10px] font-black uppercase tracking-wider rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ __('messages.movement_type_' . $item['movement_type']) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Reference --}}
                            <td class="py-1.5 px-2.5 whitespace-nowrap">
                                @if($item['source_type'])
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ ucfirst($item['source_type']) }} {{ $item['source_id'] ? '#' . $item['source_id'] : '' }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            {{-- In (+) --}}
                            <td class="py-1.5 px-2.5 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                {{ $item['in_qty'] > 0 ? '+' . $fmtQty($item['in_qty']) : '-' }}
                            </td>

                            {{-- Out (-) --}}
                            <td class="py-1.5 px-2.5 text-right font-mono font-black text-rose-600 dark:text-rose-400">
                                {{ $item['out_qty'] > 0 ? '-' . $fmtQty($item['out_qty']) : '-' }}
                            </td>

                            {{-- Running Balance --}}
                            <td class="py-1.5 px-2.5 text-right font-mono font-black text-xs text-violet-700 dark:text-violet-300 bg-violet-50/40 dark:bg-violet-950/20">
                                {{ $fmtQty($item['running_balance']) }}
                            </td>

                            {{-- Unit Cost --}}
                            <td class="py-1.5 px-2.5 text-right font-mono font-semibold text-slate-700 dark:text-slate-300">
                                {{ number_format($item['unit_cost']) }}
                            </td>

                            {{-- Posted By --}}
                            <td class="py-1.5 px-2.5 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                👤 {{ $item['posted_by_name'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-3xl mb-2">📑</span>
                                    <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('messages.stock_ledger_no_movements') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
