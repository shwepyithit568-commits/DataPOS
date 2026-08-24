@extends('layouts.admin.app')

@section('title', $product->name . ' - ' . __('messages.stock_ledger_bin_card') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="w-full space-y-5 sm:space-y-6">

    {{-- ============================================================
         PAGE HEADER — standard admin-page-header pattern
         ============================================================ --}}
    <div class="admin-page-header">
        <div class="min-w-0">
            {{-- Breadcrumbs --}}
            <nav class="flex items-center gap-1.5 text-[11px] text-slate-400 dark:text-slate-500 mb-1.5">
                <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}"
                   class="hover:text-violet-600 dark:hover:text-violet-400 transition">{{ __('messages.admin_dashboard') }}</a>
                <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('store.admin.stock_ledger.index', ['store_slug' => $store->slug]) }}"
                   class="hover:text-violet-600 dark:hover:text-violet-400 transition">{{ __('messages.sidebar_stock_ledger') }}</a>
                <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-600 dark:text-slate-300 font-semibold truncate max-w-[200px]">{{ $product->name }}</span>
            </nav>

            <h1 class="admin-page-title mt-0">
                {{ $product->name }}
            </h1>
            <p class="admin-page-sub mt-1">
                SKU: <span class="font-mono font-bold">{{ $product->sku }}</span>
                @if($product->category)
                    &nbsp;·&nbsp;{{ $product->category->name }}
                @endif
                @if($product->brand)
                    &nbsp;·&nbsp;{{ $product->brand->name }}
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{-- Print Bin Card --}}
            <a href="{{ route('store.admin.stock_ledger.print_bin_card', array_merge(['store_slug' => $store->slug, 'product' => $product->id], request()->all())) }}"
               target="_blank"
               class="admin-secondary-btn">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span class="hidden sm:inline">{{ __('messages.stock_ledger_print_bin_card') }}</span>
            </a>
            {{-- Back to Ledger --}}
            <a href="{{ route('store.admin.stock_ledger.index', ['store_slug' => $store->slug]) }}"
               class="admin-primary-btn">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span class="hidden sm:inline">{{ __('messages.stock_ledger_all_movements') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         Product Switcher + Date Preset Toolbar — standard toolbar style
         ============================================================ --}}
    <div class="rounded-2xl sm:rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm p-2.5 sm:p-3.5 transition">
        <form method="GET" action="{{ route('store.admin.stock_ledger.bin_card', ['store_slug' => $store->slug, 'product' => $product->id]) }}">
            <div class="flex items-center gap-2 overflow-x-auto pb-1 pt-0.5 -mx-1 px-1 scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-700">

                {{-- Product Switcher --}}
                <div class="relative shrink-0 min-w-[180px] max-w-xs">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <select name="product_id"
                            onchange="window.location.href='{{ route('store.admin.stock_ledger.bin_card', ['store_slug' => $store->slug]) }}/' + this.value"
                            class="w-full border border-slate-200 dark:border-slate-700 rounded-xl pl-9 pr-8 min-h-[42px] py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/70 text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-violet-500 cursor-pointer appearance-none shadow-sm transition">
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ $p->id === $product->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->sku }})
                            </option>
                        @endforeach
                    </select>
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                {{-- Vertical divider --}}
                <span class="hidden sm:inline-block w-px h-6 bg-slate-200 dark:bg-slate-700 shrink-0"></span>

                {{-- Date Preset Buttons --}}
                @php
                    $datePresets = [
                        'today' => __('messages.today'),
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

                {{-- Transaction count badge --}}
                <span class="shrink-0 ml-auto inline-flex items-center px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700 text-xs font-black text-slate-600 dark:text-slate-300 font-mono whitespace-nowrap shadow-inner">
                    {{ count($binCardData['timeline']) }} txn
                </span>
            </div>
        </form>
    </div>

    {{-- ============================================================
         4 KPI Cards — standard admin-hairline-grid pattern
         ============================================================ --}}
    <div class="admin-hairline-grid grid-cols-2 sm:grid-cols-4">
        {{-- Opening Balance --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label">{{ __('messages.stock_ledger_opening_balance') }}</div>
            <div class="admin-stat-value font-mono">{{ number_format($binCardData['opening_balance'], 3) }}</div>
            <div class="admin-stat-sub">At start of period</div>
        </div>
        {{-- Total In --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">{{ __('messages.stock_ledger_in_qty') }}</div>
            <div class="admin-stat-value text-emerald-600 dark:text-emerald-400 font-mono">+{{ number_format($binCardData['total_in'], 3) }}</div>
            <div class="admin-stat-sub">Purchases, Returns, Adj.</div>
        </div>
        {{-- Total Out --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-rose-600 dark:text-rose-400">{{ __('messages.stock_ledger_out_qty') }}</div>
            <div class="admin-stat-value text-rose-600 dark:text-rose-400 font-mono">-{{ number_format($binCardData['total_out'], 3) }}</div>
            <div class="admin-stat-sub">Sales, Transfers, Adj.</div>
        </div>
        {{-- Current On-Hand --}}
        <div class="admin-hairline-cell bg-violet-50/30 dark:bg-violet-950/10 border-violet-200 dark:border-violet-800/50 ring-1 ring-violet-500/20">
            <div class="admin-stat-label text-violet-700 dark:text-violet-300">{{ __('messages.stock_ledger_current_stock') }}</div>
            <div class="admin-stat-value text-violet-700 dark:text-violet-300 font-mono">{{ number_format($binCardData['current_on_hand'], 3) }}</div>
            <div class="admin-stat-sub">
                Value: <span class="font-mono font-bold">{{ number_format($binCardData['current_on_hand'] * (float) ($product->cost_price ?? $product->buy_price ?? 0), 2) }}</span> MMK
            </div>
        </div>
    </div>

    {{-- ============================================================
         Bin Card Running Balance Timeline Table
         ============================================================ --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="font-bold text-slate-900 dark:text-slate-100 font-outfit text-base">
                {{ __('messages.stock_ledger_bin_card_title') }}
            </h2>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                {{ count($binCardData['timeline']) }} Transactions
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/75 dark:bg-slate-800/50 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold">
                    <tr>
                        <th class="px-4 py-3">{{ __('messages.stock_ledger_date') }}</th>
                        <th class="px-4 py-3">{{ __('messages.stock_ledger_movement_type') }}</th>
                        <th class="px-4 py-3">{{ __('messages.stock_ledger_reference') }}</th>
                        <th class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400">{{ __('messages.stock_ledger_in_qty') }}</th>
                        <th class="px-4 py-3 text-right text-rose-600 dark:text-rose-400">{{ __('messages.stock_ledger_out_qty') }}</th>
                        <th class="px-4 py-3 text-right font-bold text-violet-600 dark:text-violet-400">{{ __('messages.stock_ledger_running_balance') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.stock_ledger_unit_cost') }}</th>
                        <th class="px-4 py-3">{{ __('messages.stock_ledger_posted_by') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono text-xs">
                    @forelse($binCardData['timeline'] as $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">

                            {{-- Date --}}
                            <td class="px-4 py-3 whitespace-nowrap text-slate-900 dark:text-slate-100 font-sans">
                                {{ $item['occurred_at'] ? $item['occurred_at']->format('d/m/Y H:i') : '-' }}
                            </td>

                            {{-- Movement Type Badge --}}
                            <td class="px-4 py-3 whitespace-nowrap font-sans">
                                @if($item['quantity_delta'] > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60">
                                        + {{ $item['movement_label'] }}
                                    </span>
                                @elseif($item['quantity_delta'] < 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60">
                                        - {{ $item['movement_label'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $item['movement_label'] }}
                                    </span>
                                @endif
                            </td>

                            {{-- Reference --}}
                            <td class="px-4 py-3 font-sans">
                                @if($item['source_type'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ ucfirst($item['source_type']) }} {{ $item['source_id'] ? '#' . $item['source_id'] : '' }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            {{-- In (+) --}}
                            <td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $item['in_qty'] > 0 ? '+' . number_format($item['in_qty'], 3) : '-' }}
                            </td>

                            {{-- Out (-) --}}
                            <td class="px-4 py-3 text-right font-bold text-rose-600 dark:text-rose-400">
                                {{ $item['out_qty'] > 0 ? '-' . number_format($item['out_qty'], 3) : '-' }}
                            </td>

                            {{-- Running Balance --}}
                            <td class="px-4 py-3 text-right font-bold text-sm text-violet-700 dark:text-violet-300 bg-violet-50/20 dark:bg-violet-950/10">
                                {{ number_format($item['running_balance'], 3) }}
                            </td>

                            {{-- Unit Cost --}}
                            <td class="px-4 py-3 text-right text-slate-500 dark:text-slate-400">
                                {{ number_format($item['unit_cost'], 2) }}
                            </td>

                            {{-- Posted By --}}
                            <td class="px-4 py-3 font-sans text-xs text-slate-500">
                                {{ $item['posted_by_name'] }}
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center text-slate-400 font-sans">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                    </svg>
                                    <p class="text-sm font-semibold">{{ __('messages.stock_ledger_no_movements') }}</p>
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
