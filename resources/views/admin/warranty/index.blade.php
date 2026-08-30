@extends('layouts.admin.app')

@section('title', __('messages.sidebar_warranty') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div x-data="warrantyTracker()"
     @view-changed.window="viewMode = $event.detail"
     class="w-full space-y-2 sm:space-y-2.5">

    {{-- ============================================================
         1. COMPACT HERO PAGE HEADER
         ============================================================ --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
        <div class="min-w-0">
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span>🛡️ {{ __('messages.warranty_tracker_title') }}</span>
                <span class="text-xs font-mono font-bold text-slate-400">({{ number_format($warranties->total()) }})</span>
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 shrink-0">
            {{-- Register New Warranty --}}
            <a href="{{ route('store.admin.warranty.create', ['store_slug' => $store->slug]) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition flex items-center gap-1.5 active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('messages.register_new_warranty') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. KPI STATUS SUMMARY CARDS (5-UP COMPACT)
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 sm:gap-2.5">
        {{-- Total Warranties --}}
        <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'all']) }}"
           class="p-2.5 sm:p-3 rounded-lg border transition shadow-2xs {{ $status === 'all' ? 'border-violet-600 bg-violet-50/60 dark:border-violet-500 dark:bg-violet-950/40 ring-2 ring-violet-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300' }}">
            <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1">
                <span>📋</span>
                <span>{{ __('messages.warranty_stat_total') }}</span>
            </div>
            <div class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 font-mono mt-0.5">
                {{ number_format($stats['total']) }}
            </div>
            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">Total Registered</div>
        </a>

        {{-- Active Warranties --}}
        <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'active']) }}"
           class="p-2.5 sm:p-3 rounded-lg border transition shadow-2xs {{ $status === 'active' ? 'border-emerald-600 bg-emerald-50/60 dark:border-emerald-500 dark:bg-emerald-950/40 ring-2 ring-emerald-500/20' : 'border-emerald-200/80 bg-emerald-50/30 dark:border-emerald-900/60 dark:bg-emerald-950/20 hover:border-emerald-300' }}">
            <div class="text-[11px] font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider flex items-center gap-1">
                <span>✅</span>
                <span>{{ __('messages.warranty_stat_active') }}</span>
            </div>
            <div class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5">
                {{ number_format($stats['active']) }}
            </div>
            <div class="text-[10px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 truncate">Under Warranty</div>
        </a>

        {{-- Expiring Soon --}}
        <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'expiring_soon']) }}"
           class="p-2.5 sm:p-3 rounded-lg border transition shadow-2xs {{ $status === 'expiring_soon' ? 'border-amber-600 bg-amber-50/60 dark:border-amber-500 dark:bg-amber-950/40 ring-2 ring-amber-500/20' : 'border-amber-200/80 bg-amber-50/30 dark:border-amber-900/60 dark:bg-amber-950/20 hover:border-amber-300' }}">
            <div class="text-[11px] font-bold text-amber-700 dark:text-amber-300 uppercase tracking-wider flex items-center gap-1">
                <span>⏳</span>
                <span>{{ __('messages.warranty_stat_expiring_soon') }}</span>
            </div>
            <div class="text-base sm:text-lg font-black text-amber-600 dark:text-amber-400 font-mono mt-0.5">
                {{ number_format($stats['expiringSoon']) }}
            </div>
            <div class="text-[10px] text-amber-600/80 dark:text-amber-400/80 mt-0.5 truncate">&le; 30 Days Left</div>
        </a>

        {{-- Expired Warranties --}}
        <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'expired']) }}"
           class="p-2.5 sm:p-3 rounded-lg border transition shadow-2xs {{ $status === 'expired' ? 'border-rose-600 bg-rose-50/60 dark:border-rose-500 dark:bg-rose-950/40 ring-2 ring-rose-500/20' : 'border-rose-200/80 bg-rose-50/30 dark:border-rose-900/60 dark:bg-rose-950/20 hover:border-rose-300' }}">
            <div class="text-[11px] font-bold text-rose-700 dark:text-rose-300 uppercase tracking-wider flex items-center gap-1">
                <span>⚠️</span>
                <span>{{ __('messages.warranty_stat_expired') }}</span>
            </div>
            <div class="text-base sm:text-lg font-black text-rose-600 dark:text-rose-400 font-mono mt-0.5">
                {{ number_format($stats['expired']) }}
            </div>
            <div class="text-[10px] text-rose-600/80 dark:text-rose-400/80 mt-0.5 truncate">Past Validity</div>
        </a>

        {{-- Claimed / Repaired --}}
        <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'claimed']) }}"
           class="p-2.5 sm:p-3 rounded-lg border transition shadow-2xs {{ $status === 'claimed' ? 'border-indigo-600 bg-indigo-50/60 dark:border-indigo-500 dark:bg-indigo-950/40 ring-2 ring-indigo-500/20' : 'border-indigo-200/80 bg-indigo-50/30 dark:border-indigo-900/60 dark:bg-indigo-950/20 hover:border-indigo-300' }}">
            <div class="text-[11px] font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider flex items-center gap-1">
                <span>🛠️</span>
                <span>{{ __('messages.warranty_stat_claimed') }}</span>
            </div>
            <div class="text-base sm:text-lg font-black text-indigo-600 dark:text-indigo-400 font-mono mt-0.5">
                {{ number_format($stats['claimed']) }}
            </div>
            <div class="text-[10px] text-indigo-600/80 dark:text-indigo-400/80 mt-0.5 truncate">Repairs / Claims</div>
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
        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-xs shrink-0 overflow-x-auto">
            @foreach([
                'all' => __('messages.all'),
                'active' => __('messages.status_active'),
                'expiring_soon' => __('messages.status_expiring_soon'),
                'expired' => __('messages.status_expired'),
                'claimed' => __('messages.status_claimed'),
            ] as $stKey => $stLabel)
                <a href="{{ route('store.admin.warranty.index', array_merge(['store_slug' => $store->slug], request()->query(), ['status' => $stKey, 'page' => 1])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-bold transition whitespace-nowrap {{ ($status ?? 'all') === $stKey ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-2xs font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                    {{ $stLabel }}
                </a>
            @endforeach
        </div>

        {{-- Filter Slot for Quick Scanner Modal/Dropdown & Reset --}}
        <x-slot:filterSlot>
            <div class="space-y-3 p-1">
                {{-- Instant Barcode/IMEI Scanner Input --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1.5 flex items-center gap-1">
                        <span>⚡</span>
                        <span>{{ __('messages.warranty_scanner_placeholder') ?? 'Instant Barcode / IMEI / SN Scanner' }}</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                               x-model="scanInput"
                               @input.debounce.250ms="doQuickScan()"
                               @keydown.enter.prevent="doQuickScan()"
                               placeholder="Scan or type SN / IMEI..."
                               class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-violet-500">
                    </div>

                    {{-- Instant Dropdown Results --}}
                    <div x-show="scanResults.length > 0"
                         x-cloak
                         class="mt-2 max-h-56 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700 shadow-lg">
                        <template x-for="item in scanResults" :key="item.id">
                            <a :href="item.show_url"
                               class="p-2.5 hover:bg-violet-50 dark:hover:bg-violet-950/40 flex items-center justify-between transition block">
                                <div class="space-y-0.5">
                                    <div class="font-bold text-xs text-slate-900 dark:text-slate-100" x-text="item.product_name"></div>
                                    <div class="text-[10px] font-mono text-slate-500">
                                        <span x-text="'SN: ' + item.serial_number"></span>
                                        <span x-show="item.imei_primary" x-text="' • IMEI: ' + item.imei_primary"></span>
                                    </div>
                                    <div class="text-[10px] text-slate-400" x-text="item.customer_name ? 'Customer: ' + item.customer_name : 'Walk-in'"></div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold"
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
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                        <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug]) }}"
                           class="block w-full text-center px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/40 rounded-lg hover:bg-rose-100 transition">
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
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        <th class="py-2.5 px-3 min-w-[200px]">{{ __('messages.product') }}</th>
                        <th class="py-2.5 px-3 min-w-[170px]">Serial / IMEI</th>
                        <th class="py-2.5 px-3 min-w-[150px]">{{ __('messages.customer') }}</th>
                        <th class="py-2.5 px-3 min-w-[120px]">{{ __('messages.purchase_date') }}</th>
                        <th class="py-2.5 px-3 min-w-[130px]">{{ __('messages.warranty_expiry') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[110px]">{{ __('messages.warranty_status') }}</th>
                        <th class="py-2.5 px-3 text-center w-28">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                {{-- Table Body --}}
                <tbody class="divide-y divide-slate-200/90 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse ($warranties as $w)
                        @php
                            $compStatus = $w->computed_status;
                        @endphp
                        <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">

                            {{-- Product Name & Invoice --}}
                            <td class="py-2 px-3">
                                <a href="{{ route('store.admin.warranty.show', ['store_slug' => $store->slug, 'warranty' => $w->id]) }}"
                                   class="font-bold text-slate-900 dark:text-slate-100 hover:text-violet-600 transition leading-tight block">
                                    {{ $w->product_name }}
                                </a>
                                @if($w->invoice_number)
                                    <div class="text-[10px] font-mono text-slate-400 mt-0.5">Inv: #{{ $w->invoice_number }}</div>
                                @endif
                            </td>

                            {{-- Serial & IMEI --}}
                            <td class="py-2 px-3 font-mono">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                    SN: {{ $w->serial_number }}
                                </div>
                                @if($w->imei_primary)
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        IMEI: {{ $w->imei_primary }}
                                    </div>
                                @endif
                            </td>

                            {{-- Customer --}}
                            <td class="py-2 px-3">
                                <div class="font-bold text-slate-800 dark:text-slate-200 leading-tight">
                                    {{ $w->customer_name ?: 'Walk-in Customer' }}
                                </div>
                                <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                    {{ $w->customer_phone ?: '-' }}
                                </div>
                            </td>

                            {{-- Purchase Date & Duration --}}
                            <td class="py-2 px-3 font-mono whitespace-nowrap">
                                <div class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ $w->purchase_date->format('d/m/Y') }}
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5 font-sans">
                                    {{ $w->warranty_duration_months }} Months ({{ ucfirst(str_replace('_', ' ', $w->warranty_type)) }})
                                </div>
                            </td>

                            {{-- Expiry Date & Days Left --}}
                            <td class="py-2 px-3 font-mono whitespace-nowrap">
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
                            <td class="py-2 px-3 text-center whitespace-nowrap">
                                @if($compStatus === 'active')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <span>✅</span>
                                        <span>{{ __('messages.status_active') }}</span>
                                    </span>
                                @elseif($compStatus === 'expiring_soon')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                        <span>{{ __('messages.status_expiring_soon') }}</span>
                                    </span>
                                @elseif($compStatus === 'expired')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                        <span>⚠️</span>
                                        <span>{{ __('messages.status_expired') }}</span>
                                    </span>
                                @elseif($compStatus === 'claimed')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                        <span>🛠️</span>
                                        <span>{{ __('messages.status_claimed') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ ucfirst($compStatus) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-2 px-3 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    {{-- Certificate Print --}}
                                    <a href="{{ route('store.admin.warranty.certificate', ['store_slug' => $store->slug, 'warranty' => $w->id]) }}"
                                       target="_blank"
                                       title="{{ __('messages.print_certificate') }}"
                                       class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                        <span>🖨️</span>
                                    </a>
                                    {{-- View Details --}}
                                    <a href="{{ route('store.admin.warranty.show', ['store_slug' => $store->slug, 'warranty' => $w->id]) }}"
                                       class="px-2.5 py-1 text-xs font-bold rounded-lg bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-950/60 dark:text-violet-300 transition flex items-center gap-0.5">
                                        <span>{{ __('messages.view_details') }}</span>
                                        <span>&rarr;</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-3xl mb-2">🛡️</span>
                                    <p class="text-sm font-semibold">{{ __('messages.no_warranties_found') }}</p>
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
    <div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
        @forelse ($warranties as $w)
            @php
                $compStatus = $w->computed_status;
            @endphp
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:border-violet-300 dark:hover:border-violet-600/50 hover:shadow-sm transition flex flex-col justify-between group overflow-hidden">
                {{-- Top Card Content --}}
                <div class="p-3 sm:p-3.5 space-y-2.5">
                    {{-- Header: Product Name + Status Badge --}}
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
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
                            <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                <span>✅</span>
                                <span>{{ __('messages.status_active') }}</span>
                            </span>
                        @elseif($compStatus === 'expiring_soon')
                            <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                <span>{{ __('messages.status_expiring_soon') }}</span>
                            </span>
                        @elseif($compStatus === 'expired')
                            <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                <span>⚠️</span>
                                <span>{{ __('messages.status_expired') }}</span>
                            </span>
                        @elseif($compStatus === 'claimed')
                            <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                <span>🛠️</span>
                                <span>{{ __('messages.status_claimed') }}</span>
                            </span>
                        @else
                            <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ ucfirst($compStatus) }}
                            </span>
                        @endif
                    </div>

                    {{-- Serial & IMEI Pill Container --}}
                    <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-xs font-mono space-y-0.5">
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
                    <div class="p-2.5 rounded-lg border {{ $w->days_remaining > 0 ? 'bg-emerald-50/40 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-900/40' : 'bg-rose-50/40 dark:bg-rose-950/20 border-rose-100 dark:border-rose-900/40' }} space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                သက်တမ်း ကုန်ဆုံးရက်:
                            </span>
                            <span class="font-black font-mono text-xs {{ $w->days_remaining > 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $w->warranty_expiry_date->format('d/m/Y') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-200/60 dark:border-slate-700/60 font-mono">
                            <span class="text-[10px] text-slate-400 font-sans">
                                ဝယ်ယူ: {{ $w->purchase_date->format('d/m/Y') }} ({{ $w->warranty_duration_months }} လ)
                            </span>
                            @if($w->days_remaining > 0)
                                <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-100/60 dark:bg-emerald-900/50 px-1.5 py-0.2 rounded">
                                    {{ $w->days_remaining }} days left
                                </span>
                            @else
                                <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400 bg-rose-100/60 dark:bg-rose-900/50 px-1.5 py-0.2 rounded">
                                    Expired
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Customer Metadata --}}
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between pt-0.5">
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
                <div class="p-2.5 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <a href="{{ route('store.admin.warranty.certificate', ['store_slug' => $store->slug, 'warranty' => $w->id]) }}"
                       target="_blank"
                       title="{{ __('messages.print_certificate') }}"
                       class="p-1.5 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition text-xs font-bold flex items-center gap-1">
                        <span>🖨️</span>
                        <span>Certificate</span>
                    </a>

                    <a href="{{ route('store.admin.warranty.show', ['store_slug' => $store->slug, 'warranty' => $w->id]) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-bold bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-950/60 dark:text-violet-300 transition flex items-center gap-1 active:scale-95">
                        <span>{{ __('messages.view_details') }}</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full p-12 text-center text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 shadow-2xs">
                <span class="text-3xl mb-2 block">🛡️</span>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('messages.no_warranties_found') }}</p>
                <a href="{{ route('store.admin.warranty.create', ['store_slug' => $store->slug]) }}"
                   class="mt-3 inline-flex items-center gap-1 px-3.5 py-1.5 text-xs font-bold rounded-lg bg-violet-600 text-white hover:bg-violet-700 transition shadow-sm">
                    <span>+</span>
                    <span>{{ __('messages.register_new_warranty') }}</span>
                </a>
            </div>
        @endforelse
    </div>

    {{-- Bottom Pagination --}}
    @if($warranties->hasPages())
        <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
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
