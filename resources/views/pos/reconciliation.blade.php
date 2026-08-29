@extends('layouts.admin.app')

@section('title', __('messages.sidebar_stock_reconciliation') . ' - ' . $store->name)

@section('content')
    @php
        $isManager = auth()->user()?->hasStoreRole($store->id, 'store_manager') || auth()->user()?->hasStoreRole($store->id, 'store_owner');
        $storeRouteParams = ['store_slug' => $store->slug];
        
        $totalProducts = (int)($report['products'] ?? 0);
        $diffProducts = (int)($report['diff_products'] ?? 0);
        $cleanProducts = max(0, $totalProducts - $diffProducts);
        $totalDiff = (float)($report['total_diff'] ?? 0);
        $isClean = (bool)($report['clean'] ?? false);
    @endphp

    <div class="w-full space-y-2 sm:space-y-2.5" 
         x-data="{ 
             filterMode: 'all', // 'all', 'diff', 'clean'
             searchQuery: '',
             viewMode: localStorage.getItem('admin_view_mode') || 'table',
             reconcileModalOpen: false,
             historyOpen: false,
             submitting: false,

             matchesFilter(name, sku, hasDiff) {
                 const q = this.searchQuery.trim().toLowerCase();
                 const matchesSearch = !q || 
                     (name && name.toLowerCase().includes(q)) || 
                     (sku && sku.toLowerCase().includes(q));

                 const matchesType = this.filterMode === 'all' 
                     || (this.filterMode === 'diff' && hasDiff)
                     || (this.filterMode === 'clean' && !hasDiff);

                 return matchesSearch && matchesType;
             }
         }"
         @keydown.escape.window="reconcileModalOpen = false"
         @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

        {{-- 1. Top Header Banner --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-200 dark:border-sky-800 grid place-items-center text-sm font-black shrink-0">
                    ⚖️
                </span>
                <div class="min-w-0">
                    <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 truncate">
                        {{ __('messages.sidebar_stock_reconciliation') }}
                    </h1>
                    <p class="text-[11px] text-slate-400 font-mono truncate">
                        {{ $store->name }} — {{ __('messages.reconciliation_subtitle') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap shrink-0">
                @if ($isManager && !$isClean)
                    <button type="button" @click="reconcileModalOpen = true"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 text-white shadow-md shadow-sky-900/20 transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                        <span>⚖️</span>
                        <span>{{ __('messages.reconciliation_approve') }}</span>
                    </button>
                @endif
                <button type="button" onclick="window.print()"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    <span>Print</span>
                </button>
            </div>
        </div>

        {{-- Flash Alerts --}}
        @if (session('error'))
            <div class="p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-700 dark:text-rose-300 flex items-start gap-2">
                <span class="text-sm font-bold shrink-0">⚠️</span>
                <div>{{ session('error') }}</div>
            </div>
        @endif
        @if (session('success'))
            <div class="p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs text-emerald-700 dark:text-emerald-300 flex items-start gap-2">
                <span class="text-sm font-bold shrink-0">✓</span>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        {{-- 2. Compact KPI Summary Cards (4 Columns) --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
            {{-- Card 1: Total Audited Products --}}
            <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('messages.reconciliation_products') }}</span>
                    <span class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 grid place-items-center text-xs">📦</span>
                </div>
                <div class="mt-1">
                    <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 font-mono">{{ number_format($totalProducts) }}</p>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Total Audited Items</span>
                </div>
            </div>

            {{-- Card 2: Discrepancy Products --}}
            <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border shadow-2xs flex flex-col justify-between {{ $isClean ? 'border-emerald-200/80 dark:border-emerald-900/50' : 'border-amber-200/80 dark:border-amber-900/50' }}">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider {{ $isClean ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">{{ __('messages.reconciliation_diff_products') }}</span>
                    <span class="w-6 h-6 rounded {{ $isClean ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400' }} grid place-items-center text-xs">⚖️</span>
                </div>
                <div class="mt-1">
                    <p class="text-base sm:text-lg font-black font-mono {{ $isClean ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                        {{ number_format($diffProducts) }}
                    </p>
                    <span class="text-[10px] text-slate-400 block mt-0.5">{{ $isClean ? 'Clean (အားလုံး ကိုက်ညီပါသည်)' : 'Requires Adjustment' }}</span>
                </div>
            </div>

            {{-- Card 3: Net Stock Variance --}}
            <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('messages.reconciliation_total_diff') }}</span>
                    <span class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 grid place-items-center text-xs">📊</span>
                </div>
                <div class="mt-1">
                    <p class="text-base sm:text-lg font-black font-mono {{ $totalDiff != 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $totalDiff > 0 ? '+' : '' }}{{ number_format($totalDiff, 3) }}
                    </p>
                    <span class="text-[10px] text-slate-400 block mt-0.5">{{ __('messages.net_stock_variance') }}</span>
                </div>
            </div>

            {{-- Card 4: Audit Status --}}
            <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('messages.audit_status') }}</span>
                    <span class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 grid place-items-center text-xs">🛡️</span>
                </div>
                <div class="mt-1">
                    <p class="text-xs sm:text-sm font-black {{ $isClean ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }} truncate">
                        {{ $isClean ? '✨ Clean / ကိုက်ညီပါသည်' : '⚠️ ကွာဟချက် စစ်ဆေးရန်' }}
                    </p>
                    <span class="text-[10px] text-slate-400 block mt-0.5">{{ $cleanProducts }} / {{ $totalProducts }} Match</span>
                </div>
            </div>
        </div>

        {{-- 3. Toolbar Area --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2 flex-1">
                {{-- Search Box --}}
                <div class="relative w-full sm:w-64">
                    <input type="text" x-model="searchQuery" placeholder="Search product or SKU..."
                           class="w-full pl-8 pr-3 py-1.5 min-h-[36px] border border-slate-200 dark:border-slate-700 rounded-lg text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 outline-none focus:ring-2 focus:ring-sky-500/40" />
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                {{-- Filter Chips --}}
                <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 p-0.5 text-xs font-bold">
                    <button type="button" @click="filterMode = 'all'"
                            :class="filterMode === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="px-2.5 py-1 rounded-md transition">
                        အားလုံး ({{ $totalProducts }})
                    </button>
                    <button type="button" @click="filterMode = 'diff'"
                            :class="filterMode === 'diff' ? 'bg-amber-500 text-white shadow-2xs font-black' : 'text-amber-600 dark:text-amber-400 hover:text-amber-700'"
                            class="px-2.5 py-1 rounded-md transition flex items-center gap-1">
                        <span>⚠️ ကွာဟချက် ({{ $diffProducts }})</span>
                    </button>
                    <button type="button" @click="filterMode = 'clean'"
                            :class="filterMode === 'clean' ? 'bg-emerald-600 text-white shadow-2xs font-black' : 'text-emerald-600 dark:text-emerald-400 hover:text-emerald-700'"
                            class="px-2.5 py-1 rounded-md transition">
                        ✓ ကိုက်ညီ ({{ $cleanProducts }})
                    </button>
                </div>
            </div>

            {{-- View Toggle (Table / Card) --}}
            <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 p-0.5 shrink-0" role="group">
                <button type="button"
                    @click="viewMode = 'table'; localStorage.setItem('admin_view_mode', 'table'); $dispatch('view-changed', 'table')"
                    :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    class="px-2 py-1 text-xs rounded-md transition flex items-center gap-1"
                    title="Table View">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M3 6h18v12H3z"/></svg>
                    <span class="hidden sm:inline">Table</span>
                </button>
                <button type="button"
                    @click="viewMode = 'card'; localStorage.setItem('admin_view_mode', 'card'); $dispatch('view-changed', 'card')"
                    :class="(viewMode === 'card' || viewMode === 'cards') ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    class="px-2 py-1 text-xs rounded-md transition flex items-center gap-1"
                    title="Cards View">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                    <span class="hidden sm:inline">Cards</span>
                </button>
            </div>
        </div>

        {{-- 4. Google Sheets Style Spreadsheet Table View --}}
        <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
            <div class="overflow-x-auto max-h-[72vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
                <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                        <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                            <th class="py-2.5 px-3 min-w-[220px]">{{ __('messages.product') }}</th>
                            <th class="py-2.5 px-3 text-right min-w-[130px]">{{ __('messages.reconciliation_imported') }}</th>
                            <th class="py-2.5 px-3 text-right min-w-[130px]">{{ __('messages.reconciliation_recorded') }}</th>
                            <th class="py-2.5 px-3 text-right min-w-[140px]">{{ __('messages.reconciliation_diff') }}</th>
                            <th class="py-2.5 px-3 text-right min-w-[130px]">{{ __('messages.reconciliation_on_hand') }}</th>
                            <th class="py-2.5 px-3 text-center w-28">{{ __('messages.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @forelse ($report['rows'] ?? [] as $row)
                            @php 
                                $hasDiff = abs((float) ($row['diff'] ?? 0)) > 0.0001; 
                                $productName = $row['product_name'] ?? '—';
                                $productSku = $row['sku'] ?? '';
                                $diffVal = (float) ($row['diff'] ?? 0);
                            @endphp
                            <tr x-show="matchesFilter('{{ addslashes($productName) }}', '{{ addslashes($productSku) }}', {{ $hasDiff ? 'true' : 'false' }})"
                                class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition {{ $hasDiff ? 'bg-amber-50/30 dark:bg-amber-950/20' : '' }}">
                                
                                {{-- Product Name & SKU --}}
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 grid place-items-center text-xs font-black shrink-0">
                                            {{ $hasDiff ? '⚠️' : '📦' }}
                                        </span>
                                        <div class="min-w-0">
                                            <span class="font-black text-slate-900 dark:text-slate-100 text-xs sm:text-sm block truncate" title="{{ $productName }}">
                                                {{ $productName }}
                                            </span>
                                            @if (!empty($productSku))
                                                <span class="text-[10px] font-mono text-slate-400 block">SKU: {{ $productSku }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Imported Opening Stock --}}
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ number_format((float) ($row['imported_qty'] ?? 0), 3) }}
                                </td>

                                {{-- Ledger Recorded Qty --}}
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ number_format((float) ($row['recorded_qty'] ?? 0), 3) }}
                                </td>

                                {{-- Discrepancy / Variance --}}
                                <td class="py-2.5 px-3 text-right font-mono font-black {{ $hasDiff ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                                    @if ($hasDiff)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800">
                                            {{ $diffVal > 0 ? '+' : '' }}{{ number_format($diffVal, 3) }}
                                        </span>
                                    @else
                                        <span>0.000</span>
                                    @endif
                                </td>

                                {{-- Current On-Hand Stock --}}
                                <td class="py-2.5 px-3 text-right font-mono font-black text-slate-900 dark:text-slate-100">
                                    {{ number_format((float) ($row['on_hand'] ?? 0), 3) }}
                                </td>

                                {{-- Status --}}
                                <td class="py-2.5 px-3 text-center">
                                    @if ($hasDiff)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                            Mismatch
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            ✓ Clean
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    <div class="text-3xl mb-2 opacity-55">⚖️</div>
                                    <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.reconciliation_none') }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">ကွာဟချက် ညှိနှိုင်းရန် ကုန်ပစ္စည်း စာရင်းများ မရှိသေးပါ</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 5. Responsive Multi-Column Card Grid View --}}
        <div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
            @forelse ($report['rows'] ?? [] as $row)
                @php 
                    $hasDiff = abs((float) ($row['diff'] ?? 0)) > 0.0001; 
                    $productName = $row['product_name'] ?? '—';
                    $productSku = $row['sku'] ?? '';
                    $diffVal = (float) ($row['diff'] ?? 0);
                @endphp
                <div x-show="matchesFilter('{{ addslashes($productName) }}', '{{ addslashes($productSku) }}', {{ $hasDiff ? 'true' : 'false' }})"
                     class="bg-white dark:bg-slate-900 border rounded-xl overflow-hidden shadow-2xs hover:shadow-sm transition flex flex-col justify-between group {{ $hasDiff ? 'border-amber-200 dark:border-amber-800/80 hover:border-amber-400' : 'border-slate-200/80 dark:border-slate-800 hover:border-sky-300' }}">
                    
                    <div class="p-3 space-y-2.5">
                        {{-- Card Header: Icon + SKU + Status Pill --}}
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 grid place-items-center text-xs font-black shrink-0">
                                    {{ $hasDiff ? '⚠️' : '📦' }}
                                </span>
                                @if (!empty($productSku))
                                    <span class="px-1.5 py-0.5 rounded font-mono font-bold text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 truncate">
                                        SKU: {{ $productSku }}
                                    </span>
                                @endif
                            </div>

                            @if ($hasDiff)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shrink-0">
                                    Mismatch
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shrink-0">
                                    ✓ Match
                                </span>
                            @endif
                        </div>

                        {{-- Product Title --}}
                        <div>
                            <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 line-clamp-2" title="{{ $productName }}">
                                {{ $productName }}
                            </h4>
                        </div>

                        {{-- 2x2 Numeric Comparison Grid --}}
                        <div class="grid grid-cols-2 gap-2 bg-slate-50 dark:bg-slate-800/50 p-2 rounded-lg border border-slate-100 dark:border-slate-800 text-[11px]">
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase font-bold">{{ __('messages.reconciliation_imported') }}</span>
                                <span class="font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ number_format((float) ($row['imported_qty'] ?? 0), 2) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase font-bold">{{ __('messages.reconciliation_recorded') }}</span>
                                <span class="font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ number_format((float) ($row['recorded_qty'] ?? 0), 2) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase font-bold">{{ __('messages.reconciliation_diff') }}</span>
                                <span class="font-mono font-black {{ $hasDiff ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                                    {{ $diffVal > 0 ? '+' : '' }}{{ number_format($diffVal, 2) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase font-bold">{{ __('messages.reconciliation_on_hand') }}</span>
                                <span class="font-mono font-black text-slate-900 dark:text-slate-100">
                                    {{ number_format((float) ($row['on_hand'] ?? 0), 2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="p-2 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 text-[10px] text-slate-400 flex items-center justify-between">
                        <span>Status: {{ $hasDiff ? 'Action Required' : 'Synchronized' }}</span>
                        <span class="font-mono">{{ $hasDiff ? 'Diff: ' . number_format($diffVal, 2) : 'OK' }}</span>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 p-8 rounded-xl text-center text-slate-400 shadow-2xs">
                    <div class="text-3xl mb-2 opacity-55">⚖️</div>
                    <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.reconciliation_none') }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">ကွာဟချက် ညှိနှိုင်းရန် ကုန်ပစ္စည်း စာရင်းများ မရှိသေးပါ</div>
                </div>
            @endforelse
        </div>

        {{-- 6. Past Reconciliation Records (History Accordion / Section) --}}
        @if (!empty($history) && $history->isNotEmpty())
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-3 sm:p-4 shadow-2xs space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <span>📜</span> {{ __('messages.reconciliation_history') }}
                    </h3>
                    <span class="text-xs text-slate-400 font-mono">{{ $history->count() }} Records</span>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($history as $h)
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-black text-slate-900 dark:text-slate-100">{{ $h->reconciliation_number }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        ✓ Approved
                                    </span>
                                </div>
                                <span class="text-[11px] text-slate-400 block mt-0.5">
                                    Approved by: <strong class="text-slate-600 dark:text-slate-300">{{ $h->approver?->name ?? '—' }}</strong> · Date: {{ $h->created_at->format('d M Y, H:i') }}
                                    @if (!empty($h->review_notes))
                                        · Note: <em class="text-slate-500">{{ $h->review_notes }}</em>
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 7. Auto-Reconcile Modal Dialog --}}
        <div x-show="reconcileModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="reconcileModalOpen = false"></div>
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 space-y-4" @click.stop>
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            <span>⚖️</span> {{ __('messages.reconciliation_approve') }}
                        </h3>
                        <button type="button" @click="reconcileModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
                    </div>

                    <div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl text-xs text-amber-800 dark:text-amber-200 leading-relaxed space-y-1">
                        <p class="font-bold flex items-center gap-1.5">
                            <span>⚠️</span> {{ __('messages.reconciliation_approve_confirm') }}
                        </p>
                        <p class="text-[11px] text-amber-700/90 dark:text-amber-300/80">
                            {{ __('messages.reconciliation_approve_hint') }}
                        </p>
                        <p class="text-[11px] font-mono font-bold mt-1">
                            Discrepancy Items: {{ $diffProducts }} Products (Net Variance: {{ ($totalDiff > 0 ? '+' : '') . number_format($totalDiff, 3) }})
                        </p>
                    </div>

                    <form method="POST" action="{{ route('pos.reconciliation.approve', $storeRouteParams) }}"
                          @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }"
                          class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                                {{ __('messages.reconciliation_review_notes') }}
                            </label>
                            <input type="text" name="review_notes" placeholder="e.g. Monthly stock audit opening variance reconciliation..." maxlength="255"
                                   class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-sky-500" />
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="reconcileModalOpen = false"
                                    class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                                {{ __('messages.cancel') }}
                            </button>
                            <button type="submit" :disabled="submitting"
                                    class="px-5 py-2 rounded-lg bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 text-white text-xs font-black shadow-md shadow-sky-500/20 transition active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed">
                                <span x-show="!submitting" class="inline-flex items-center gap-1.5">
                                    <span>⚖️</span> {{ __('messages.reconciliation_approve') }}
                                </span>
                                <span x-show="submitting" class="inline-flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                    <span>Submitting...</span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection
