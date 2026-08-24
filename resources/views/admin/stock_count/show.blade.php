@extends('layouts.admin.app')

@section('title', $session->session_number . ' - ' . __('messages.sidebar_stock_count'))

@section('content')
<div x-data="stockCountSheet()" class="space-y-6">

    {{-- Breadcrumbs & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.admin_dashboard') }}</a>
                <span>/</span>
                <a href="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.sidebar_stock_count') }}</a>
                <span>/</span>
                <span class="font-mono text-slate-700 dark:text-slate-200 font-semibold">{{ $session->session_number }}</span>
            </div>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 font-outfit">
                    {{ $session->session_number }}
                </h1>
                @if($session->isApproved())
                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                        {{ __('messages.stock_count_status_approved') }}
                    </span>
                @elseif($session->isCancelled())
                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-full bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                        {{ __('messages.stock_count_status_cancelled') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                        {{ __('messages.stock_count_status_in_progress') }}
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-400 mt-0.5">
                {{ __('messages.stock_count_created_by') }}: <span class="font-medium text-slate-600 dark:text-slate-300">{{ $session->createdBy?->name ?? 'System' }}</span> ({{ $session->created_at->format('d/m/Y H:i') }})
                @if($session->isApproved() && $session->approvedBy)
                    • {{ __('messages.stock_count_approved_by') }}: <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ $session->approvedBy->name }}</span> ({{ $session->approved_at?->format('d/m/Y H:i') }})
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('store.admin.stock_count.print', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>{{ __('messages.stock_count_print_sheet') }}</span>
            </a>

            @if($session->isInProgress())
                <form action="{{ route('store.admin.stock_count.cancel', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                      method="POST"
                      onsubmit="return confirm('{{ __('messages.stock_count_cancel_confirm') }}');">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 text-sm font-semibold rounded-xl text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40 border border-transparent hover:border-rose-200 dark:hover:border-rose-900 transition">
                        {{ __('messages.stock_count_cancel') }}
                    </button>
                </form>

                <form action="{{ route('store.admin.stock_count.approve', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                      method="POST"
                      onsubmit="return confirm('{{ __('messages.stock_count_approve_confirm') }}');">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 text-sm font-extrabold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-600/30 transition transform active:scale-95">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ __('messages.stock_count_approve') }}</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800/60 dark:text-emerald-300 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/40 dark:border-rose-800/60 dark:text-rose-300 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- 4 KPI Summary Cards --}}
    @php
        $progressPct = $session->total_items > 0 ? round(($session->counted_items / $session->total_items) * 100) : 0;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Products --}}
        <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.stock_count_total_products') }}</div>
            <div class="text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit mt-1">{{ number_format($session->total_items) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $session->scope === 'category' ? __('messages.stock_count_scope_category') : __('messages.stock_count_scope_all') }}</div>
        </div>

        {{-- Items Counted & Progress --}}
        <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                <span>{{ __('messages.stock_count_items_counted') }}</span>
                <span class="text-violet-600 dark:text-violet-400 font-mono">{{ $progressPct }}%</span>
            </div>
            <div class="text-2xl font-black text-violet-600 dark:text-violet-400 font-outfit mt-1">
                <span x-text="stats.counted_items">{{ $session->counted_items }}</span> / {{ $session->total_items }}
            </div>
            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="bg-violet-600 h-1.5 rounded-full transition-all duration-300" :style="'width: ' + progressPercentage + '%'"></div>
            </div>
        </div>

        {{-- Variance Items --}}
        <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm">
            <div class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">{{ __('messages.stock_count_variance_items') }}</div>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-outfit mt-1">
                <span x-text="stats.variance_items">{{ $session->variance_items }}</span>
            </div>
            <div class="text-xs text-slate-400 mt-1">
                {{ __('messages.stock_count_total_variance') }}: <span class="font-mono font-bold" x-text="formatQty(stats.total_variance_qty)">{{ number_format($session->total_variance_qty, 3) }}</span>
            </div>
        </div>

        {{-- Financial Impact --}}
        <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.variance') }} (Value)</div>
            <div class="text-2xl font-black font-outfit mt-1" :class="stats.total_variance_cost < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100'">
                <span x-text="formatMoney(stats.total_variance_cost)">{{ number_format($session->total_variance_cost, 2) }}</span>
                <span class="text-xs font-normal text-slate-400">MMK</span>
            </div>
            <div class="text-xs text-slate-400 mt-1">
                @if($session->isApproved())
                    <span class="text-emerald-600 font-semibold">{{ __('messages.stock_count_approved_label') }}</span>
                @else
                    <span>{{ __('messages.stock_count_approve_desc') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Live Barcode/SKU Scanner & Filter Toolbar --}}
    <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
        
        {{-- Scanner Input Bar --}}
        @if($session->isInProgress())
            <div class="relative">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5 text-violet-600 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-16v16M4 4v16m4-16v16m8-16v16" />
                        </svg>
                    </div>
                    <input type="text"
                           x-ref="scanInput"
                           x-model="scanQuery"
                           @input.debounce.250ms="doLiveScan()"
                           @keydown.enter.prevent="handleScanEnter()"
                           placeholder="{{ __('messages.stock_count_scan_placeholder') }}"
                           class="w-full pl-11 pr-24 py-3 text-sm rounded-xl border-2 border-violet-200 bg-violet-50/20 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-violet-600 focus:ring-4 focus:ring-violet-500/10 dark:border-violet-900/50 dark:bg-slate-800 dark:text-slate-100 font-mono transition">
                    
                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center gap-1.5">
                        <span class="px-2 py-1 text-[11px] font-bold rounded-lg bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300 font-mono">
                            SCANNER
                        </span>
                    </div>
                </div>

                {{-- Live Search / Scan Dropdown Suggestions --}}
                <div x-show="scanResults.length > 0"
                     @click.away="scanResults = []"
                     x-transition
                     class="absolute z-20 left-0 right-0 mt-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden divide-y divide-slate-100 dark:divide-slate-700 max-h-64 overflow-y-auto">
                    <template x-for="item in scanResults" :key="item.line_id">
                        <div @click="selectScannedProduct(item)"
                             class="p-3 hover:bg-violet-50 dark:hover:bg-slate-700/60 cursor-pointer flex items-center justify-between transition">
                            <div>
                                <div class="font-bold text-sm text-slate-900 dark:text-slate-100" x-text="item.product_name"></div>
                                <div class="text-xs text-slate-400 font-mono">
                                    SKU: <span x-text="item.sku"></span> | Barcode: <span x-text="item.barcode"></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-semibold px-2 py-1 rounded bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                    Sys: <span x-text="item.system_quantity"></span>
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        @endif

        {{-- Filter Tabs & Search Bar --}}
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 pt-2 border-t border-slate-100 dark:border-slate-800">
            
            {{-- Filter Tabs --}}
            <div class="flex items-center gap-1.5 p-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-semibold w-full md:w-auto overflow-x-auto">
                <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id, 'tab' => 'all', 'search' => $search, 'category_id' => $categoryId]) }}"
                   class="px-3 py-1.5 rounded-lg transition whitespace-nowrap {{ $tab === 'all' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                    {{ __('messages.all') }} ({{ $session->total_items }})
                </a>
                <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id, 'tab' => 'counted', 'search' => $search, 'category_id' => $categoryId]) }}"
                   class="px-3 py-1.5 rounded-lg transition whitespace-nowrap {{ $tab === 'counted' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                    {{ __('messages.stock_count_items_counted') }} ({{ $session->counted_items }})
                </a>
                <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id, 'tab' => 'variance', 'search' => $search, 'category_id' => $categoryId]) }}"
                   class="px-3 py-1.5 rounded-lg transition whitespace-nowrap {{ $tab === 'variance' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                    {{ __('messages.stock_count_has_variance') }} ({{ $session->variance_items }})
                </a>
                <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id, 'tab' => 'uncounted', 'search' => $search, 'category_id' => $categoryId]) }}"
                   class="px-3 py-1.5 rounded-lg transition whitespace-nowrap {{ $tab === 'uncounted' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900' }}">
                    {{ __('messages.stock_count_uncounted') }} ({{ max(0, $session->total_items - $session->counted_items) }})
                </a>
            </div>

            {{-- Category Filter & Search Form --}}
            <form method="GET" action="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}" class="flex items-center gap-2 w-full md:w-auto">
                <input type="hidden" name="tab" value="{{ $tab }}">
                
                @if($sessionCategories->count() > 1)
                    <select name="category_id" onchange="this.form.submit()" class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <option value="">{{ __('messages.all_categories') }}</option>
                        @foreach($sessionCategories as $cat)
                            <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                @endif

                <div class="relative flex-1 md:w-64">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="{{ __('messages.search') }}..."
                           class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-slate-800 text-white hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600">
                    {{ __('messages.filter') }}
                </button>
            </form>
        </div>

    </div>

    {{-- Stock Take Sheet Table --}}
    <form action="{{ route('store.admin.stock_count.bulk_update', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}" method="POST">
        @csrf
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div class="rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h2 class="font-bold text-slate-900 dark:text-slate-100 font-outfit text-base">
                    {{ __('messages.stock_count_take_sheet') }}
                </h2>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                        {{ $lines->total() }} {{ __('messages.stock_count_products') }}
                    </span>
                    @if($session->isInProgress())
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold rounded-xl bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white shadow-sm transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                            <span>{{ __('messages.save') }}</span>
                        </button>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/75 dark:bg-slate-800/50 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold">
                        <tr>
                            <th class="px-4 py-3">{{ __('messages.product') }}</th>
                            <th class="px-4 py-3">{{ __('messages.category') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('messages.stock_count_system_qty') }}</th>
                            <th class="px-4 py-3 text-center w-36">{{ __('messages.stock_count_counted_qty') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('messages.stock_count_variance') }}</th>
                            <th class="px-4 py-3">{{ __('messages.notes') }}</th>
                            <th class="px-4 py-3 text-center w-20">{{ __('messages.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($lines as $index => $line)
                            <tr id="line-row-{{ $line->id }}"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition {{ $line->is_counted ? ($line->variance_quantity != 0 ? 'bg-amber-50/20 dark:bg-amber-950/10' : '') : '' }}">
                                
                                <input type="hidden" name="lines[{{ $index }}][id]" value="{{ $line->id }}">
                                
                                {{-- Product Name & Barcode/SKU --}}
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">
                                        {{ $line->product?->name ?? 'Unknown Product' }}
                                    </div>
                                    <div class="text-xs font-mono text-slate-400 mt-0.5 flex items-center gap-2">
                                        @if($line->product?->barcode)
                                            <span class="bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-[11px]">{{ $line->product->barcode }}</span>
                                        @endif
                                        @if($line->product?->sku)
                                            <span class="text-slate-400 text-[11px]">SKU: {{ $line->product->sku }}</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Category --}}
                                <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ $line->category?->name ?? '-' }}
                                </td>

                                {{-- System Quantity --}}
                                <td class="px-4 py-3 text-right font-mono font-bold text-slate-700 dark:text-slate-200">
                                    {{ number_format($line->system_quantity, 3) }}
                                </td>

                                {{-- Counted Quantity Input --}}
                                <td class="px-4 py-3 text-center">
                                    @if($session->isInProgress())
                                        <div class="relative">
                                            <input type="number"
                                                   step="any"
                                                   min="0"
                                                   id="counted-input-{{ $line->id }}"
                                                   name="lines[{{ $index }}][counted_quantity]"
                                                   value="{{ $line->counted_quantity !== null ? (float) $line->counted_quantity : '' }}"
                                                   @change="saveLineCount({{ $line->id }}, $el.value, {{ (float) $line->system_quantity }})"
                                                   placeholder="0"
                                                   class="w-28 px-2.5 py-1.5 text-center font-mono font-bold text-sm rounded-xl border border-slate-300 bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 shadow-sm transition">
                                        </div>
                                    @else
                                        <span class="font-mono font-bold text-sm {{ $line->counted_quantity !== null ? 'text-slate-900 dark:text-slate-100' : 'text-slate-400' }}">
                                            {{ $line->counted_quantity !== null ? number_format($line->counted_quantity, 3) : '-' }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Variance Quantity Badge --}}
                                <td class="px-4 py-3 text-right">
                                    <div id="variance-cell-{{ $line->id }}">
                                        @if($line->is_counted)
                                            @if($line->variance_quantity > 0)
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-mono font-bold rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                                                    +{{ number_format($line->variance_quantity, 3) }}
                                                </span>
                                            @elseif($line->variance_quantity < 0)
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-mono font-bold rounded-md bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                                                    {{ number_format($line->variance_quantity, 3) }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-mono font-medium rounded-md bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                                    0.000
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-xs font-mono text-slate-400">-</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Notes Input --}}
                                <td class="px-4 py-3">
                                    @if($session->isInProgress())
                                        <input type="text"
                                               name="lines[{{ $index }}][notes]"
                                               value="{{ $line->notes }}"
                                               placeholder="Notes..."
                                               class="w-full px-2 py-1 text-xs rounded-lg border border-transparent hover:border-slate-200 focus:border-violet-400 bg-transparent focus:bg-white dark:focus:bg-slate-800 text-slate-800 dark:text-slate-200">
                                    @else
                                        <span class="text-xs text-slate-500">{{ $line->notes ?? '-' }}</span>
                                    @endif
                                </td>

                                {{-- Counted Status Icon --}}
                                <td class="px-4 py-3 text-center" id="status-cell-{{ $line->id }}">
                                    @if($line->is_counted)
                                        <span class="inline-flex items-center text-emerald-600 dark:text-emerald-400" title="{{ __('messages.stock_count_items_counted') }}">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    @else
                                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-slate-300 dark:bg-slate-600" title="{{ __('messages.stock_count_not_counted') }}"></span>
                                    @endif
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-400 text-sm">
                                    {{ __('messages.no_results_found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($lines->hasPages())
                <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    {{ $lines->links() }}
                </div>
            @endif
        </div>
    </form>

</div>

@push('scripts')
<script>
function stockCountSheet() {
    return {
        scanQuery: '',
        scanResults: [],
        stats: {
            counted_items: {{ $session->counted_items }},
            variance_items: {{ $session->variance_items }},
            total_variance_qty: {{ (float) $session->total_variance_qty }},
            total_variance_cost: {{ (float) $session->total_variance_cost }},
        },
        totalItems: {{ $session->total_items }},

        get progressPercentage() {
            return this.totalItems > 0 ? Math.round((this.stats.counted_items / this.totalItems) * 100) : 0;
        },

        doLiveScan() {
            if (this.scanQuery.trim().length === 0) {
                this.scanResults = [];
                return;
            }
            fetch(`{{ route('store.admin.stock_count.quick_scan', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}?q=` + encodeURIComponent(this.scanQuery))
                .then(r => r.json())
                .then(data => {
                    this.scanResults = data;
                });
        },

        handleScanEnter() {
            if (this.scanResults.length > 0) {
                this.selectScannedProduct(this.scanResults[0]);
            }
        },

        selectScannedProduct(item) {
            const input = document.getElementById('counted-input-' + item.line_id);
            const row = document.getElementById('line-row-' + item.line_id);

            if (input) {
                let currentVal = parseFloat(input.value) || 0;
                input.value = currentVal + 1;
                input.focus();
                input.select();
                this.saveLineCount(item.line_id, input.value, item.system_quantity);
                if (row) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.classList.add('bg-violet-100', 'dark:bg-violet-900/40');
                    setTimeout(() => row.classList.remove('bg-violet-100', 'dark:bg-violet-900/40'), 1500);
                }
            }
            this.scanQuery = '';
            this.scanResults = [];
            this.$refs.scanInput?.focus();
        },

        saveLineCount(lineId, countedVal, systemQty) {
            if (countedVal === '' || countedVal === null) return;
            const parsedCount = parseFloat(countedVal);
            if (isNaN(parsedCount)) return;

            const url = `{{ route('store.admin.stock_count.update_line', ['store_slug' => $store->slug, 'stock_count' => $session->id, 'line' => ':lineId']) }}`.replace(':lineId', lineId);

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ counted_quantity: parsedCount })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    this.stats = res.session;

                    // Update variance cell in DOM
                    const varianceCell = document.getElementById('variance-cell-' + lineId);
                    const statusCell = document.getElementById('status-cell-' + lineId);

                    if (varianceCell) {
                        const v = res.line.variance_quantity;
                        if (v > 0) {
                            varianceCell.innerHTML = `<span class="inline-flex items-center px-2 py-0.5 text-xs font-mono font-bold rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">+${v.toFixed(3)}</span>`;
                        } else if (v < 0) {
                            varianceCell.innerHTML = `<span class="inline-flex items-center px-2 py-0.5 text-xs font-mono font-bold rounded-md bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">${v.toFixed(3)}</span>`;
                        } else {
                            varianceCell.innerHTML = `<span class="inline-flex items-center px-2 py-0.5 text-xs font-mono font-medium rounded-md bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">0.000</span>`;
                        }
                    }

                    if (statusCell) {
                        statusCell.innerHTML = `<span class="inline-flex items-center text-emerald-600 dark:text-emerald-400"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg></span>`;
                    }
                }
            })
            .catch(err => console.error(err));
        },

        formatQty(val) {
            return (parseFloat(val) || 0).toFixed(3);
        },

        formatMoney(val) {
            return (parseFloat(val) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };
}
</script>
@endpush
@endsection
