@extends('layouts.admin.app')

@section('title', __('messages.sidebar_warranty') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div x-data="warrantyTracker()"
     @view-changed.window="viewMode = $event.detail"
     class="w-full space-y-0.5 pb-6">

    {{-- ============================================================
         1. COMPACT PAGE HEADER (34px - 38px Standard Height)
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400 grid place-items-center text-base font-bold shadow-xs shrink-0">
                🛡️
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.warranty_tracker_title') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                    <span class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                        {{ number_format($warranties->total()) }}
                    </span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.warranty_tracker_sub') }}
                </p>
            </div>
        </div>

        {{-- Header Actions: Excel Export + Register New Warranty --}}
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

            {{-- Register New Warranty --}}
            <a href="{{ route('store.admin.warranty.create', ['store_slug' => $store->slug]) }}"
               class="h-7 px-3 rounded-md text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-2xs hover:shadow-violet-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('messages.register_new_warranty') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. SUMMARY STAT CARDS (Row-based Center Alignment Standard)
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-0.5 sm:gap-1" role="list">
        {{-- Total Warranties --}}
        <a role="listitem"
           href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'all']) }}"
           class="px-3 py-1.5 rounded-lg border shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition {{ $status === 'all' ? 'border-violet-600 bg-violet-50/60 dark:border-violet-500 dark:bg-violet-950/40 ring-1 ring-violet-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 shadow-inner text-xs sm:text-sm font-bold">
                📋
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['total']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.warranty_stat_total') }}
                </p>
            </div>
        </a>

        {{-- Active Warranties --}}
        <a role="listitem"
           href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'active']) }}"
           class="px-3 py-1.5 rounded-lg border shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition {{ $status === 'active' ? 'border-emerald-600 bg-emerald-50/60 dark:border-emerald-500 dark:bg-emerald-950/40 ring-1 ring-emerald-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-emerald-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                ✅
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['active']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.warranty_stat_active') }}
                </p>
            </div>
        </a>

        {{-- Expiring Soon --}}
        <a role="listitem"
           href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'expiring_soon']) }}"
           class="px-3 py-1.5 rounded-lg border shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition {{ $status === 'expiring_soon' ? 'border-amber-600 bg-amber-50/60 dark:border-amber-500 dark:bg-amber-950/40 ring-1 ring-amber-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-amber-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold">
                ⏳
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['expiringSoon']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.warranty_stat_expiring_soon') }}
                </p>
            </div>
        </a>

        {{-- Expired Warranties --}}
        <a role="listitem"
           href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'expired']) }}"
           class="px-3 py-1.5 rounded-lg border shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition {{ $status === 'expired' ? 'border-rose-600 bg-rose-50/60 dark:border-rose-500 dark:bg-rose-950/40 ring-1 ring-rose-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-rose-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300 shadow-inner text-xs sm:text-sm font-bold">
                ⚠️
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['expired']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.warranty_stat_expired') }}
                </p>
            </div>
        </a>

        {{-- Claimed / Repaired --}}
        <a role="listitem"
           href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'claimed']) }}"
           class="px-3 py-1.5 rounded-lg border shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition {{ $status === 'claimed' ? 'border-indigo-600 bg-indigo-50/60 dark:border-indigo-500 dark:bg-indigo-950/40 ring-1 ring-indigo-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-indigo-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-indigo-100 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-300 shadow-inner text-xs sm:text-sm font-bold">
                🛠️
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-indigo-600 dark:text-indigo-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['claimed']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.warranty_stat_claimed') }}
                </p>
            </div>
        </a>
    </div>

    {{-- ============================================================
         3. STANDARD TOOLBAR WITH QUICK SCANNER & STATUS TABS
         ============================================================ --}}
    <x-admin.toolbar
        :showSearch="true"
        :searchPlaceholder="__('messages.search_warranty_placeholder') ?? 'Search product, SN, IMEI, customer...'"
        :searchValue="$search ?? ''"
        :filterCount="$activeFiltersCount ?? 0"
        :showViewToggle="true"
        :activeView="'table'"
        :showExcel="true"
        :excelUrl="$exportUrl ?? null"
        :showPagination="true"
        :paginator="$warranties"
        :showPerPageSelector="true"
        :perPageOptions="[
            25    => '25',
            50    => '50',
            100   => '100',
            200   => '200',
            'all' => __('messages.all'),
        ]"
    >
        {{-- Status Filter Tabs inside toolbar --}}
        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-0.5 rounded-lg border border-slate-200/80 dark:border-slate-700 text-xs shrink-0 overflow-x-auto">
            @foreach([
                'all' => __('messages.all'),
                'active' => __('messages.status_active'),
                'expiring_soon' => __('messages.status_expiring_soon'),
                'expired' => __('messages.status_expired'),
                'claimed' => __('messages.status_claimed'),
            ] as $stKey => $stLabel)
                <a href="{{ route('store.admin.warranty.index', array_merge(['store_slug' => $store->slug], request()->query(), ['status' => $stKey, 'page' => 1])) }}"
                   class="h-6 px-2.5 rounded-md text-xs font-bold transition whitespace-nowrap inline-flex items-center {{ ($status ?? 'all') === $stKey ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-2xs font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                    {{ $stLabel }}
                </a>
            @endforeach
        </div>

        {{-- Filter Slot for Quick Scanner Modal/Dropdown & Reset --}}
        <x-slot:filterSlot>
            <div class="space-y-2.5 p-1">
                {{-- Instant Barcode/IMEI Scanner Input --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1 flex items-center gap-1">
                        <span>⚡</span>
                        <span>{{ __('messages.warranty_scanner_placeholder') ?? 'Instant Barcode / IMEI / SN Scanner' }}</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                               x-model="scanInput"
                               @input.debounce.250ms="doQuickScan()"
                               @keydown.enter.prevent="doQuickScan()"
                               placeholder="Scan or type SN / IMEI..."
                               class="w-full h-7 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2.5 text-slate-900 dark:text-slate-100 font-mono font-bold focus:outline-none focus:ring-1 focus:ring-violet-500">
                    </div>

                    {{-- Instant Dropdown Results --}}
                    <div x-show="scanResults.length > 0"
                         x-cloak
                         class="mt-1.5 max-h-56 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700 shadow-lg">
                        <template x-for="item in scanResults" :key="item.id">
                            <a :href="item.show_url"
                               class="p-2 hover:bg-violet-50 dark:hover:bg-violet-950/40 flex items-center justify-between transition block">
                                <div class="space-y-0.5">
                                    <div class="font-bold text-xs text-slate-900 dark:text-slate-100" x-text="item.product_name"></div>
                                    <div class="text-[10px] font-mono text-slate-500">
                                        <span x-text="'SN: ' + item.serial_number"></span>
                                        <span x-show="item.imei_primary" x-text="' • IMEI: ' + item.imei_primary"></span>
                                    </div>
                                    <div class="text-[10px] text-slate-400" x-text="item.customer_name ? 'Customer: ' + item.customer_name : 'Walk-in'"></div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="inline-flex px-1.5 py-0.2 rounded text-[10px] font-black uppercase"
                                          :class="{
                                              'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300': item.status === 'active',
                                              'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300': item.status === 'expiring_soon',
                                              'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300': item.status === 'expired',
                                              'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300': item.status === 'claimed'
                                          }"
                                          x-text="item.status"></span>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>

                @if($activeFiltersCount > 0)
                    <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800">
                        <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug]) }}"
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
                        <th class="py-1.5 px-2.5 min-w-[200px]">{{ __('messages.product') }}</th>
                        <th class="py-1.5 px-2.5 min-w-[170px]">Serial / IMEI</th>
                        <th class="py-1.5 px-2.5 min-w-[150px]">{{ __('messages.customer') }}</th>
                        <th class="py-1.5 px-2.5 min-w-[120px]">{{ __('messages.purchase_date') }}</th>
                        <th class="py-1.5 px-2.5 min-w-[130px]">{{ __('messages.warranty_expiry') }}</th>
                        <th class="py-1.5 px-2.5 text-center min-w-[110px]">{{ __('messages.warranty_status') }}</th>
                        <th class="py-1.5 px-2 text-center w-28">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                {{-- Table Body --}}
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse ($warranties as $w)
                        @php
                            $compStatus = $w->computed_status;
                        @endphp
                        <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">

                            {{-- Product Name & Invoice --}}
                            <td class="py-1.5 px-2.5">
                                <a href="{{ route('store.admin.warranty.show', ['store_slug' => $store->slug, 'warranty' => $w->id]) }}"
                                   class="font-bold text-slate-900 dark:text-slate-100 hover:text-violet-600 dark:hover:text-violet-400 transition leading-tight block truncate max-w-[220px]">
                                    {{ $w->product_name }}
                                </a>
                                @if($w->invoice_number)
                                    <div class="text-[10px] font-mono text-slate-400 mt-0.5">Inv: #{{ $w->invoice_number }}</div>
                                @endif
                            </td>

                            {{-- Serial & IMEI --}}
                            <td class="py-1.5 px-2.5 font-mono">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs select-all">
                                    SN: {{ $w->serial_number }}
                                </div>
                                @if($w->imei_primary)
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 select-all">
                                        IMEI: {{ $w->imei_primary }}
                                    </div>
                                @endif
                            </td>

                            {{-- Customer --}}
                            <td class="py-1.5 px-2.5">
                                <div class="font-bold text-slate-800 dark:text-slate-200 leading-tight truncate max-w-[150px]">
                                    {{ $w->customer_name ?: 'Walk-in Customer' }}
                                </div>
                                <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                    {{ $w->customer_phone ?: '-' }}
                                </div>
                            </td>

                            {{-- Purchase Date & Duration --}}
                            <td class="py-1.5 px-2.5 font-mono whitespace-nowrap">
                                <div class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ $w->purchase_date->format('d/m/Y') }}
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5 font-sans">
                                    {{ $w->warranty_duration_months }} Months ({{ ucfirst(str_replace('_', ' ', $w->warranty_type)) }})
                                </div>
                            </td>

                            {{-- Expiry Date & Days Left --}}
                            <td class="py-1.5 px-2.5 font-mono whitespace-nowrap">
                                <div class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ $w->warranty_expiry_date->format('d/m/Y') }}
                                </div>
                                @if($w->days_remaining > 0)
                                    <div class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">
                                        {{ $w->days_remaining }} days left
                                    </div>
                                @else
                                    <div class="text-[10px] font-bold text-rose-500 mt-0.5">
                                        Expired
                                    </div>
                                @endif
                            </td>

                            {{-- Status Badge --}}
                            <td class="py-1.5 px-2.5 text-center whitespace-nowrap">
                                @if($compStatus === 'active')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <span>✅</span>
                                        <span>{{ __('messages.status_active') }}</span>
                                    </span>
                                @elseif($compStatus === 'expiring_soon')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                        <span>{{ __('messages.status_expiring_soon') }}</span>
                                    </span>
                                @elseif($compStatus === 'expired')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                        <span>⚠️</span>
                                        <span>{{ __('messages.status_expired') }}</span>
                                    </span>
                                @elseif($compStatus === 'claimed')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                        <span>🛠️</span>
                                        <span>{{ __('messages.status_claimed') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ ucfirst($compStatus) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-1.5 px-2 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    {{-- Certificate Print --}}
                                    <a href="{{ route('store.admin.warranty.certificate', ['store_slug' => $store->slug, 'warranty' => $w->id]) }}"
                                       target="_blank"
                                       title="{{ __('messages.print_certificate') }}"
                                       class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                        <span>🖨️</span>
                                    </a>
                                    {{-- View Details --}}
                                    <a href="{{ route('store.admin.warranty.show', ['store_slug' => $store->slug, 'warranty' => $w->id]) }}"
                                       class="h-6 px-2 text-xs font-bold rounded-md bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-950/60 dark:text-violet-300 transition inline-flex items-center gap-0.5 active:scale-95">
                                        <span>{{ __('messages.view_details') }}</span>
                                        <span>&rarr;</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-3xl mb-2">🛡️</span>
                                    <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('messages.no_warranties_found') }}</p>
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
        @forelse ($warranties as $w)
            @php
                $compStatus = $w->computed_status;
            @endphp
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:border-violet-300 dark:hover:border-violet-600/50 hover:shadow-xs transition flex flex-col justify-between group overflow-hidden">
                {{-- Top Card Content --}}
                <div class="p-2.5 sm:p-3 space-y-2">
                    {{-- Header: Product Name + Status Badge --}}
                    <div class="flex items-start justify-between gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('store.admin.warranty.show', ['store_slug' => $store->slug, 'warranty' => $w->id]) }}"
                               class="font-black text-xs sm:text-sm text-slate-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 line-clamp-1 block transition">
                                {{ $w->product_name }}
                            </a>
                            @if($w->invoice_number)
                                <span class="text-[10px] font-mono text-slate-400 block mt-0.5">Inv: #{{ $w->invoice_number }}</span>
                            @endif
                        </div>

                        {{-- Status Pill --}}
                        @if($compStatus === 'active')
                            <span class="shrink-0 inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                <span>✅</span>
                                <span>{{ __('messages.status_active') }}</span>
                            </span>
                        @elseif($compStatus === 'expiring_soon')
                            <span class="shrink-0 inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                <span>{{ __('messages.status_expiring_soon') }}</span>
                            </span>
                        @elseif($compStatus === 'expired')
                            <span class="shrink-0 inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                <span>⚠️</span>
                                <span>{{ __('messages.status_expired') }}</span>
                            </span>
                        @elseif($compStatus === 'claimed')
                            <span class="shrink-0 inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                <span>🛠️</span>
                                <span>{{ __('messages.status_claimed') }}</span>
                            </span>
                        @else
                            <span class="shrink-0 inline-flex items-center px-1.5 py-0.2 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ ucfirst($compStatus) }}
                            </span>
                        @endif
                    </div>

                    {{-- Serial & IMEI Pill Container --}}
                    <div class="p-1.5 rounded-md bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-xs font-mono space-y-0.5">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 text-[10px] uppercase font-bold">Serial No:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200 text-[11px] select-all">{{ $w->serial_number }}</span>
                        </div>
                        @if($w->imei_primary)
                            <div class="flex items-center justify-between pt-0.5 border-t border-slate-200/50 dark:border-slate-700/50">
                                <span class="text-slate-400 text-[10px] uppercase font-bold">IMEI:</span>
                                <span class="font-bold text-slate-600 dark:text-slate-300 text-[10px] select-all">{{ $w->imei_primary }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Validity & Expiry Timeline Box --}}
                    <div class="p-2 rounded-md border {{ $w->days_remaining > 0 ? 'bg-emerald-50/30 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-900/40' : 'bg-rose-50/30 dark:bg-rose-950/20 border-rose-100 dark:border-rose-900/40' }} space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                {{ __('messages.warranty_expiry') }}:
                            </span>
                            <span class="font-black font-mono text-xs {{ $w->days_remaining > 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $w->warranty_expiry_date->format('d/m/Y') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-200/50 dark:border-slate-700/50 font-mono">
                            <span class="text-[10px] text-slate-400 font-sans">
                                {{ $w->purchase_date->format('d/m/Y') }} ({{ $w->warranty_duration_months }} M)
                            </span>
                            @if($w->days_remaining > 0)
                                <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-100/60 dark:bg-emerald-900/50 px-1 py-0.2 rounded">
                                    {{ $w->days_remaining }}d left
                                </span>
                            @else
                                <span class="text-[9px] font-bold text-rose-600 dark:text-rose-400 bg-rose-100/60 dark:bg-rose-900/50 px-1 py-0.2 rounded">
                                    Expired
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Customer Metadata --}}
                    <div class="text-[10px] text-slate-500 dark:text-slate-400 flex items-center justify-between pt-0.5">
                        <div class="truncate flex items-center gap-1 min-w-0">
                            <span>👤</span>
                            <span class="font-bold text-slate-700 dark:text-slate-300 truncate">{{ $w->customer_name ?: 'Walk-in Customer' }}</span>
                        </div>
                        @if($w->customer_phone)
                            <span class="font-mono text-[10px] text-slate-400 shrink-0">{{ $w->customer_phone }}</span>
                        @endif
                    </div>
                </div>

                {{-- Card Action Footer --}}
                <div class="p-2 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1.5">
                    <a href="{{ route('store.admin.warranty.certificate', ['store_slug' => $store->slug, 'warranty' => $w->id]) }}"
                       target="_blank"
                       title="{{ __('messages.print_certificate') }}"
                       class="h-6 px-2 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 rounded hover:bg-slate-200 dark:hover:bg-slate-700 transition text-xs font-bold inline-flex items-center gap-1">
                        <span>🖨️</span>
                        <span>Certificate</span>
                    </a>

                    <a href="{{ route('store.admin.warranty.show', ['store_slug' => $store->slug, 'warranty' => $w->id]) }}"
                       class="h-6 px-2.5 rounded text-xs font-bold bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-950/60 dark:text-violet-300 transition inline-flex items-center gap-1 active:scale-95">
                        <span>{{ __('messages.view_details') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full p-8 text-center text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 rounded-lg border border-dashed border-slate-200 dark:border-slate-800 shadow-2xs">
                <span class="text-3xl mb-2 block">🛡️</span>
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('messages.no_warranties_found') }}</p>
                <a href="{{ route('store.admin.warranty.create', ['store_slug' => $store->slug]) }}"
                   class="mt-2.5 inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-md bg-violet-600 text-white hover:bg-violet-700 transition shadow-2xs">
                    <span>+</span>
                    <span>{{ __('messages.register_new_warranty') }}</span>
                </a>
            </div>
        @endforelse
    </div>

    {{-- Bottom Pagination --}}
    @if($warranties->hasPages())
        <div class="p-1.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            {{ $warranties->links() }}
        </div>
    @endif

</div>

<script nonce="{{ $cspNonce }}">
window.warrantyTracker = function() {
    return {
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
        scanInput: '',
        scanResults: [],
        async doQuickScan() {
            if (!this.scanInput || this.scanInput.length < 2) {
                this.scanResults = [];
                return;
            }
            try {
                const res = await fetch(`{{ route('store.admin.warranty.quick_scan', ['store_slug' => $store->slug]) }}?q=${encodeURIComponent(this.scanInput)}`);
                if (res.ok) {
                    this.scanResults = await res.json();
                }
            } catch (e) {
                console.error(e);
            }
        }
    };
};
</script>
@endsection
