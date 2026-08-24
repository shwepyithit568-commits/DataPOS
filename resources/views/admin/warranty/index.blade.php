@extends('layouts.admin.app')

@section('title', __('messages.sidebar_warranty') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div x-data="warrantyTracker()" class="space-y-6">

    {{-- Breadcrumbs & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.admin_dashboard') }}</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-200 font-semibold">{{ __('messages.sidebar_warranty') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 font-outfit mt-1">
                {{ __('messages.warranty_tracker_title') }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                {{ __('messages.warranty_tracker_sub') }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('store.admin.warranty.create', ['store_slug' => $store->slug]) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-extrabold rounded-xl bg-violet-600 hover:bg-violet-500 text-white shadow-lg shadow-violet-600/30 transition transform active:scale-95">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('messages.register_new_warranty') }}</span>
            </a>
        </div>
    </div>

    {{-- 4 KPI Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

        {{-- 1. Total Warranties --}}
        <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'all']) }}"
           class="p-4 rounded-2xl border transition {{ $status === 'all' ? 'border-violet-600 bg-violet-50/40 dark:border-violet-500 dark:bg-violet-950/20 ring-2 ring-violet-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300' }} shadow-sm">
            <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.warranty_stat_total') }}</div>
            <div class="text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit mt-1">{{ number_format($stats['total']) }}</div>
        </a>

        {{-- 2. Active Warranties --}}
        <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'active']) }}"
           class="p-4 rounded-2xl border transition {{ $status === 'active' ? 'border-emerald-600 bg-emerald-50/40 dark:border-emerald-500 dark:bg-emerald-950/20 ring-2 ring-emerald-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300' }} shadow-sm">
            <div class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">{{ __('messages.warranty_stat_active') }}</div>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-outfit mt-1">{{ number_format($stats['active']) }}</div>
        </a>

        {{-- 3. Expiring in 30 Days --}}
        <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'expiring_soon']) }}"
           class="p-4 rounded-2xl border transition {{ $status === 'expiring_soon' ? 'border-amber-600 bg-amber-50/40 dark:border-amber-500 dark:bg-amber-950/20 ring-2 ring-amber-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300' }} shadow-sm">
            <div class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">{{ __('messages.warranty_stat_expiring_soon') }}</div>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-outfit mt-1">{{ number_format($stats['expiringSoon']) }}</div>
        </a>

        {{-- 4. Expired Warranties --}}
        <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'expired']) }}"
           class="p-4 rounded-2xl border transition {{ $status === 'expired' ? 'border-rose-600 bg-rose-50/40 dark:border-rose-500 dark:bg-rose-950/20 ring-2 ring-rose-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300' }} shadow-sm">
            <div class="text-xs font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider">{{ __('messages.warranty_stat_expired') }}</div>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 font-outfit mt-1">{{ number_format($stats['expired']) }}</div>
        </a>

        {{-- 5. Claimed / Repaired --}}
        <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug, 'status' => 'claimed']) }}"
           class="p-4 rounded-2xl border transition {{ $status === 'claimed' ? 'border-indigo-600 bg-indigo-50/40 dark:border-indigo-500 dark:bg-indigo-950/20 ring-2 ring-indigo-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300' }} shadow-sm">
            <div class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">{{ __('messages.warranty_stat_claimed') }}</div>
            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 font-outfit mt-1">{{ number_format($stats['claimed']) }}</div>
        </a>
    </div>

    {{-- Quick Scanner & Search Toolbar --}}
    <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

            {{-- Instant Barcode/IMEI Scanner Bar --}}
            <div class="relative flex-1 max-w-xl">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        {{-- Barcode / Scanner Icon --}}
                        <svg class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-16v16M4 4v16m4-16v16m8-16v16" />
                        </svg>
                    </div>
                    <input type="text"
                           x-model="scanInput"
                           @input.debounce.250ms="doQuickScan()"
                           @keydown.enter.prevent="doQuickScan()"
                           placeholder="{{ __('messages.warranty_scanner_placeholder') }}"
                           class="w-full pl-10 pr-10 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-mono">
                    <button type="button"
                            x-show="scanInput.length > 0"
                            @click="scanInput = ''; scanResults = [];"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                        &times;
                    </button>
                </div>

                {{-- Quick Scan Dropdown Result Box --}}
                <div x-show="scanResults.length > 0"
                     x-cloak
                     @click.away="scanResults = []"
                     class="absolute z-20 top-full left-0 right-0 mt-2 max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700">
                    <template x-for="item in scanResults" :key="item.id">
                        <a :href="item.show_url"
                           class="p-3.5 hover:bg-violet-50 dark:hover:bg-violet-950/40 flex items-center justify-between transition group">
                            <div class="space-y-0.5">
                                <div class="font-bold text-xs text-slate-900 dark:text-slate-100 group-hover:text-violet-600" x-text="item.product_name"></div>
                                <div class="text-[11px] font-mono text-slate-500 space-x-2">
                                    <span x-text="'SN: ' + item.serial_number"></span>
                                    <span x-show="item.imei_primary" x-text="'• IMEI: ' + item.imei_primary"></span>
                                </div>
                                <div class="text-[11px] text-slate-400" x-text="'Customer: ' + (item.customer_name || 'N/A') + ' (' + (item.customer_phone || '-') + ')'"></div>
                            </div>
                            <div class="text-right space-y-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                      :class="{
                                          'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300': item.status === 'active',
                                          'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300': item.status === 'expiring_soon',
                                          'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300': item.status === 'expired',
                                          'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300': item.status === 'claimed'
                                      }"
                                      x-text="item.status"></span>
                                <div class="text-[10px] text-slate-400" x-text="item.days_remaining > 0 ? item.days_remaining + ' days left' : 'Expired'"></div>
                            </div>
                        </a>
                    </template>
                </div>
            </div>

            {{-- Normal Form Filter & Search --}}
            <form method="GET" action="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug]) }}" class="flex items-center gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="{{ __('messages.search_warranty_placeholder') }}"
                       class="px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-white text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                <button type="submit" class="px-3.5 py-2 text-xs font-bold rounded-xl bg-slate-800 hover:bg-slate-700 text-white dark:bg-slate-700 dark:hover:bg-slate-600 transition">
                    {{ __('messages.filter') }}
                </button>
                @if($search || $status !== 'all')
                    <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug]) }}" class="text-xs text-rose-600 hover:underline px-2">
                        {{ __('messages.clear_all') }}
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Main Warranty Records Table --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 dark:bg-slate-800/60 dark:text-slate-400 uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3.5">{{ __('messages.product') }}</th>
                        <th class="px-4 py-3.5">Serial / IMEI</th>
                        <th class="px-4 py-3.5">{{ __('messages.customer') }}</th>
                        <th class="px-4 py-3.5">{{ __('messages.purchase_date') }}</th>
                        <th class="px-4 py-3.5">{{ __('messages.warranty_expiry') }}</th>
                        <th class="px-4 py-3.5 text-center">{{ __('messages.warranty_status') }}</th>
                        <th class="px-4 py-3.5 text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($warranties as $w)
                        @php
                            $compStatus = $w->computed_status;
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                            <td class="px-4 py-3">
                                <a href="{{ route('store.admin.warranty.show', ['store_slug' => $store->slug, 'warranty' => $w->id]) }}" class="font-bold text-slate-900 dark:text-slate-100 hover:text-violet-600">
                                    {{ $w->product_name }}
                                </a>
                                @if($w->invoice_number)
                                    <div class="text-[11px] text-slate-400 font-mono">Inv: #{{ $w->invoice_number }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono space-y-0.5">
                                <div class="font-bold text-slate-800 dark:text-slate-200">SN: {{ $w->serial_number }}</div>
                                @if($w->imei_primary)
                                    <div class="text-[11px] text-slate-500">IMEI: {{ $w->imei_primary }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $w->customer_name ?: 'Walk-in Customer' }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $w->customer_phone ?: '-' }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-600 dark:text-slate-400">
                                {{ $w->purchase_date->format('d/m/Y') }}
                                <div class="text-[10px] text-slate-400">{{ $w->warranty_duration_months }} Months</div>
                            </td>
                            <td class="px-4 py-3 font-mono">
                                <div class="font-bold text-slate-800 dark:text-slate-200">{{ $w->warranty_expiry_date->format('d/m/Y') }}</div>
                                @if($w->days_remaining > 0)
                                    <div class="text-[10px] font-bold text-emerald-600">{{ $w->days_remaining }} days left</div>
                                @else
                                    <div class="text-[10px] font-bold text-rose-500">Expired</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($compStatus === 'active')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                        {{ __('messages.status_active') }}
                                    </span>
                                @elseif($compStatus === 'expiring_soon')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                        {{ __('messages.status_expiring_soon') }}
                                    </span>
                                @elseif($compStatus === 'expired')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                        {{ __('messages.status_expired') }}
                                    </span>
                                @elseif($compStatus === 'claimed')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                        {{ __('messages.status_claimed') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ ucfirst($compStatus) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('store.admin.warranty.certificate', ['store_slug' => $store->slug, 'warranty' => $w->id]) }}"
                                       target="_blank"
                                       title="{{ __('messages.print_certificate') }}"
                                       class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('store.admin.warranty.show', ['store_slug' => $store->slug, 'warranty' => $w->id]) }}"
                                       class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-950/60 dark:text-violet-300 transition">
                                        {{ __('messages.view_details') }} &rarr;
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                {{ __('messages.no_warranties_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($warranties->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $warranties->links() }}
            </div>
        @endif
    </div>
</div>

<script nonce="{{ $cspNonce }}">
window.warrantyTracker = function() {
    return {
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
