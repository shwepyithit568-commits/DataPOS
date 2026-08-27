@extends('layouts.admin.app')

@php
    $totalItems = $totalCount ?? 0;
    $liveGlassFinderUrl = url('/glass-finder?store_slug=' . $store->slug);
    $brandFilterOptions = [];
    foreach ($brands as $b) {
        $brandFilterOptions[$b] = $b;
    }
@endphp

@section('title', __('messages.glass_finder_admin_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
         viewMode: localStorage.getItem('admin_glass_finder_view_mode') || 'card',
         addModalOpen: false,
         importModalOpen: false,
         copiedCode: null,
         copyCode(code) {
             navigator.clipboard.writeText(code);
             this.copiedCode = code;
             setTimeout(() => this.copiedCode = null, 2000);
         }
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_glass_finder_view_mode', $event.detail)">

    {{-- ============================================================
         PAGE HEADER — eyebrow badge, title, subtitle, CTA row
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-fuchsia-50 text-fuchsia-700 dark:bg-fuchsia-950/60 dark:text-fuchsia-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-fuchsia-100 dark:border-fuchsia-900/60 mb-0.5">
                <span>🔍</span>
                <span>{{ __('messages.sidebar_glass_finder') }}</span>
                <span class="text-slate-400 dark:text-slate-500">·</span>
                <span class="font-normal normal-case text-slate-500 dark:text-slate-400">Compatibility Matrix & Stock</span>
            </div>
            <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                {{ __('messages.glass_finder_admin_title') }}
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                {{ $store->name }} · {{ __('messages.glass_finder_admin_subtitle') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 shrink-0">
            {{-- Live Storefront Link --}}
            <a href="{{ $liveGlassFinderUrl }}" target="_blank" rel="noopener noreferrer"
               class="px-2.5 py-1.5 sm:px-3 sm:py-2 rounded-lg text-xs font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 hover:bg-sky-100 dark:hover:bg-sky-900/60 border border-sky-200 dark:border-sky-800 transition flex items-center gap-1.5 active:scale-95 shadow-2xs">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>Live Finder ↗</span>
            </a>

            {{-- Import Button --}}
            <button type="button" @click="importModalOpen = true"
                    class="px-2.5 py-1.5 sm:px-3 sm:py-2 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 transition flex items-center gap-1.5 active:scale-95 shadow-2xs">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span>{{ __('messages.glass_finder_import_btn') }}</span>
            </button>

            {{-- Add Item Button --}}
            <button type="button" @click="addModalOpen = true"
                    class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                <span>{{ __('messages.glass_finder_add_item') }}</span>
            </button>
        </div>
    </header>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="w-full p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="w-full p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-2xs">
            <div class="flex items-center gap-1.5 font-bold"><span>⚠️</span><span>Errors:</span></div>
            @foreach ($errors->all() as $error)
                <div class="pl-5 text-xs font-semibold">• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         KPI STAT CARDS — 4 compact interactive summary cards
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2">
        {{-- Total Items --}}
        <a href="{{ route('store.admin.glass-finder.index', ['store_slug' => $store->slug]) }}"
           class="group bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 shadow-2xs transition active:scale-98">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 group-hover:text-slate-800 dark:group-hover:text-slate-200 truncate">{{ __('messages.glass_finder_total_items') }}</span>
                <span class="text-xs">📱</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 mt-1 font-mono tracking-tight">{{ number_format($stats['total'] ?? 0) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ number_format($stats['brands_count'] ?? 0) }} brands</div>
        </a>

        {{-- Unique Glass Codes --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-violet-600 dark:text-violet-400 truncate">{{ __('messages.glass_finder_unique_codes') }}</span>
                <span class="text-xs">🏷️</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-violet-600 dark:text-violet-400 mt-1 font-mono tracking-tight">{{ number_format($stats['unique_codes'] ?? 0) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Matrix groups</div>
        </div>

        {{-- In Stock --}}
        <a href="{{ route('store.admin.glass-finder.index', ['store_slug' => $store->slug, 'stock_status' => 'in_stock']) }}"
           class="group bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border {{ request('stock_status') === 'in_stock' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/80 dark:border-slate-800' }} hover:border-emerald-300 shadow-2xs transition active:scale-98">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 truncate">{{ __('messages.glass_finder_in_stock') }}</span>
                <span class="text-xs">✅</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono tracking-tight">{{ number_format($stats['in_stock'] ?? 0) }}</div>
            <div class="text-[10px] text-emerald-600/70 dark:text-emerald-400/70 mt-0.5">Ready to install</div>
        </a>

        {{-- Out of Stock --}}
        <a href="{{ route('store.admin.glass-finder.index', ['store_slug' => $store->slug, 'stock_status' => 'out_of_stock']) }}"
           class="group bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border {{ request('stock_status') === 'out_of_stock' ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-slate-200/80 dark:border-slate-800' }} hover:border-rose-300 shadow-2xs transition active:scale-98">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-rose-600 dark:text-rose-400 truncate">{{ __('messages.glass_finder_out_of_stock') }}</span>
                <span class="text-xs">⚠️</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-rose-600 dark:text-rose-400 mt-1 font-mono tracking-tight">{{ number_format($stats['out_of_stock'] ?? 0) }}</div>
            <div class="text-[10px] text-rose-600/70 dark:text-rose-400/70 mt-0.5">Needs restock</div>
        </a>
    </div>

    {{-- Import Preview Alert & Confirmation --}}
    @if (session('import_preview'))
        @php $preview = session('import_preview'); @endphp
        <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg p-3 sm:p-4 space-y-3 shadow-2xs">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-base">👀</span>
                    <div>
                        <h3 class="font-bold text-xs sm:text-sm text-amber-900 dark:text-amber-200">Import Preview: {{ $preview['filename'] }}</h3>
                        <p class="text-[11px] text-amber-800 dark:text-amber-300">Please review the parsed Glass Finder items before final confirmation.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
                <div class="bg-white dark:bg-slate-800 rounded p-2 border border-amber-100 dark:border-slate-700">
                    <div class="text-base font-black text-slate-900 dark:text-slate-100 font-mono">{{ $preview['total'] }}</div>
                    <div class="text-[10px] text-slate-500">Total Rows</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-2 border border-amber-100 dark:border-slate-700">
                    <div class="text-base font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ $preview['valid_rows'] }}</div>
                    <div class="text-[10px] text-slate-500">Valid Rows</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-2 border border-amber-100 dark:border-slate-700">
                    <div class="text-base font-black text-amber-600 dark:text-amber-400 font-mono">{{ $preview['duplicate_rows'] }}</div>
                    <div class="text-[10px] text-slate-500">Duplicates</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-2 border border-amber-100 dark:border-slate-700">
                    <div class="text-base font-black text-rose-600 dark:text-rose-400 font-mono">{{ $preview['failed'] }}</div>
                    <div class="text-[10px] text-slate-500">Failed</div>
                </div>
            </div>

            @if (!empty($preview['preview_rows']))
                <div class="overflow-x-auto bg-white dark:bg-slate-800 rounded border border-amber-200/80 dark:border-slate-700 max-h-56">
                    <table class="w-full text-[11px] text-left">
                        <thead class="bg-slate-50 dark:bg-slate-900/80 sticky top-0 text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="p-2 whitespace-nowrap">Row</th>
                                <th class="p-2 whitespace-nowrap">Brand</th>
                                <th class="p-2 whitespace-nowrap">Phone Model</th>
                                <th class="p-2 whitespace-nowrap">Glass Code</th>
                                <th class="p-2 whitespace-nowrap">Stock</th>
                                <th class="p-2 whitespace-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-600 dark:text-slate-300">
                            @foreach ($preview['preview_rows'] as $row)
                                <tr>
                                    <td class="p-2 font-mono">{{ $row['row'] }}</td>
                                    <td class="p-2 font-bold">{{ $row['brand'] }}</td>
                                    <td class="p-2">{{ $row['phone_model'] }}</td>
                                    <td class="p-2 font-mono font-bold text-violet-600">{{ $row['glass_code'] }}</td>
                                    <td class="p-2">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $row['stock_status'] === 'in_stock' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                            {{ $row['stock_status'] }}
                                        </span>
                                    </td>
                                    <td class="p-2">{{ $row['action'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <form method="POST" action="{{ route('store.admin.glass-finder.import.confirm', ['store_slug' => $store->slug]) }}" class="flex items-center gap-2">
                @csrf
                <input type="hidden" name="token" value="{{ $preview['token'] }}">
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg font-bold text-xs hover:bg-emerald-700 shadow-2xs flex items-center gap-1.5 active:scale-95 transition">
                    <span>✓ Confirm & Save to Database</span>
                </button>
            </form>
        </div>
    @endif

    {{-- Import Result --}}
    @if (session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg p-3 sm:p-4 shadow-2xs">
            <h3 class="font-bold text-xs sm:text-sm text-emerald-900 dark:text-emerald-200 mb-2 flex items-center gap-1.5">
                <span>✅</span><span>Glass Finder Import Completed Successfully</span>
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
                <div class="bg-white dark:bg-slate-800 rounded p-2 border border-emerald-100 dark:border-slate-700">
                    <div class="text-base font-black text-slate-900 dark:text-slate-100 font-mono">{{ $result['total'] }}</div>
                    <div class="text-[10px] text-slate-500">Total</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-2 border border-emerald-100 dark:border-slate-700">
                    <div class="text-base font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ $result['imported'] }}</div>
                    <div class="text-[10px] text-slate-500">Imported</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-2 border border-emerald-100 dark:border-slate-700">
                    <div class="text-base font-black text-amber-600 dark:text-amber-400 font-mono">{{ $result['skipped_duplicate'] }}</div>
                    <div class="text-[10px] text-slate-500">Skipped</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-2 border border-emerald-100 dark:border-slate-700">
                    <div class="text-base font-black text-rose-600 dark:text-rose-400 font-mono">{{ $result['failed'] }}</div>
                    <div class="text-[10px] text-slate-500">Failed</div>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================
         UNIFIED ADMIN TOOLBAR
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', '')"
        searchPlaceholder="🔍 Search model, brand or glass code..."
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest' => 'Newest Added',
            'oldest' => 'Oldest Added',
            'code_asc' => 'Glass Code (A to Z)',
            'code_desc' => 'Glass Code (Z to A)',
        ]"
        :filters="[
            'stock_status' => [
                'label' => 'Stock Status',
                'options' => ['in_stock' => 'In Stock (လက်ကျန်ရှိ)', 'out_of_stock' => 'Out of Stock (ပြတ်လပ်)']
            ],
            'brand' => [
                'label' => 'Brand',
                'options' => $brandFilterOptions
            ]
        ]"
        :viewMode="'card'"
        :showViewToggle="true"
        :totalCount="$totalCount"
    />

    {{-- ============================================================
         VIEW 1: CARD GRID VIEW (3-COLUMN MATRIX CARDS)
         ============================================================ --}}
    <div x-show="viewMode === 'card'" x-cloak class="space-y-2">
        <div class="flex items-center justify-between px-1 text-[11px] text-slate-500 font-bold">
            <span>Glass Code Matrix ({{ $items->count() }} Groups)</span>
            <span class="text-slate-400">Card Grid View</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2.5 sm:gap-3 items-start">
            @forelse ($items as $glassCode => $codeItems)
                @php
                    $brandsInGroup = $codeItems->pluck('brand')->unique()->values();
                    $inCount = $codeItems->filter(fn ($i) => $i->isInStock())->count();
                    $outCount = $codeItems->count() - $inCount;
                @endphp
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:shadow-xs transition flex flex-col overflow-hidden">
                    
                    {{-- Card Top Header --}}
                    <div class="p-3 bg-gradient-to-r from-slate-50 to-violet-50/30 dark:from-slate-800/80 dark:to-violet-950/20 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            {{-- Glass Code Pill --}}
                            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-violet-600 text-white font-mono font-black text-xs shadow-2xs">
                                <span>🏷️</span>
                                <span>{{ $glassCode }}</span>
                            </div>
                            
                            {{-- Copy button --}}
                            <button type="button" @click="copyCode('{{ $glassCode }}')"
                                    class="p-1 rounded hover:bg-slate-200/60 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition text-[10px]"
                                    title="Copy Glass Code">
                                <span x-text="copiedCode === '{{ $glassCode }}' ? '✓ Copied' : '📋'"></span>
                            </button>
                        </div>

                        {{-- Stock Status Breakdown Pill --}}
                        <div class="flex items-center gap-1.5 font-mono text-[11px]">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-200/80 dark:border-emerald-800">
                                <span>✓</span>
                                <span>{{ $inCount }} In</span>
                            </span>
                            @if ($outCount > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 font-bold border border-rose-200/80 dark:border-rose-800">
                                    <span>✗</span>
                                    <span>{{ $outCount }}</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Brands Tags --}}
                    <div class="px-3 py-1.5 bg-slate-50/60 dark:bg-slate-900/60 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500">
                        <span class="truncate font-medium">Brands: <strong class="text-slate-700 dark:text-slate-300">{{ $brandsInGroup->implode(', ') }}</strong></span>
                        <span class="shrink-0 font-mono font-bold text-violet-600 dark:text-violet-400">{{ $codeItems->count() }} models</span>
                    </div>

                    {{-- Compatible Phone Models List --}}
                    <div class="p-2 space-y-1 max-h-56 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60">
                        @foreach ($codeItems as $item)
                            <div class="pt-1.5 first:pt-0 flex items-center justify-between gap-2 text-xs group hover:bg-slate-50 dark:hover:bg-slate-800/50 p-1.5 rounded-lg transition">
                                <div class="min-w-0 flex items-center gap-2">
                                    <span class="text-slate-400 text-[10px]">📱</span>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 truncate text-[11px] sm:text-xs">{{ $item->phone_model }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $item->brand }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase tracking-wider {{ $item->isInStock() ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300' }}">
                                        {{ $item->stock_status === 'in_stock' ? 'In Stock' : 'Out' }}
                                    </span>

                                    {{-- Edit Action --}}
                                    <a href="{{ url('/store/' . $store->slug . '/admin/glass-finder/' . $item->id . '/edit') }}"
                                       class="p-1 rounded text-slate-400 hover:text-violet-600 hover:bg-slate-200/60 dark:hover:bg-slate-700 transition" title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>

                                    {{-- Delete Action --}}
                                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/glass-finder/' . $item->id) }}" onsubmit="return confirm('{{ __('messages.glass_finder_delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="Delete">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-8 text-center space-y-2">
                    <div class="text-3xl">🔍</div>
                    <div class="font-bold text-sm text-slate-800 dark:text-slate-200">{{ __('messages.glass_finder_empty') }}</div>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">{{ __('messages.glass_finder_empty_desc') }}</p>
                    <button type="button" @click="addModalOpen = true" class="mt-2 px-3.5 py-2 bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs rounded-lg shadow transition">
                        + {{ __('messages.glass_finder_add_item') }}
                    </button>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ============================================================
         VIEW 2: SPREADSHEET TABLE VIEW
         ============================================================ --}}
    <div x-show="viewMode === 'table'" x-cloak class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 min-w-[640px]">
                <thead class="bg-slate-50/90 dark:bg-slate-800/90 border-b border-slate-200 dark:border-slate-700/80 sticky top-0 z-10 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 select-none">
                    <tr>
                        <th class="py-2.5 px-3">Brand</th>
                        <th class="py-2.5 px-3">Phone Model</th>
                        <th class="py-2.5 px-3">Glass Code</th>
                        <th class="py-2.5 px-3">Normalized</th>
                        <th class="py-2.5 px-3">Stock Status</th>
                        <th class="py-2.5 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse ($allItems as $item)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="py-2 px-3 font-bold text-slate-800 dark:text-slate-200">{{ $item->brand }}</td>
                            <td class="py-2 px-3 font-bold text-slate-900 dark:text-slate-100">{{ $item->phone_model }}</td>
                            <td class="py-2 px-3 font-mono font-bold text-violet-600 dark:text-violet-400">{{ $item->glass_code }}</td>
                            <td class="py-2 px-3 font-mono text-[10px] text-slate-400">{{ $item->normalized_glass_code }}</td>
                            <td class="py-2 px-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $item->isInStock() ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-900' }}">
                                    {{ $item->stock_status === 'in_stock' ? '✓ In Stock' : '✗ Out' }}
                                </span>
                            </td>
                            <td class="py-2 px-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ url('/store/' . $store->slug . '/admin/glass-finder/' . $item->id . '/edit') }}"
                                       class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-violet-600 transition" title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/glass-finder/' . $item->id) }}" onsubmit="return confirm('{{ __('messages.glass_finder_delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-400 hover:text-rose-600 transition" title="Delete">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 font-bold">{{ __('messages.glass_finder_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================
         RECENT IMPORT HISTORY PANEL
         ============================================================ --}}
    @if ($histories->isNotEmpty())
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden transition">
            <div class="px-3 py-2.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                    <span>📋</span><span>Recent Excel / CSV Imports</span>
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 min-w-[500px]">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="p-2.5">Date & Time</th>
                            <th class="p-2.5">Filename</th>
                            <th class="p-2.5">User</th>
                            <th class="p-2.5 text-center">Total</th>
                            <th class="p-2.5 text-center">Success</th>
                            <th class="p-2.5 text-center">Failed</th>
                            <th class="p-2.5 text-right">Report</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($histories as $history)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="p-2.5 font-mono text-[11px]">{{ $history->created_at->format('Y-m-d H:i') }}</td>
                                <td class="p-2.5 font-bold text-slate-800 dark:text-slate-200">{{ $history->filename }}</td>
                                <td class="p-2.5">{{ $history->user?->name ?? 'System' }}</td>
                                <td class="p-2.5 text-center font-mono font-bold">{{ $history->total_rows }}</td>
                                <td class="p-2.5 text-center font-mono font-bold text-emerald-600">{{ $history->success_rows }}</td>
                                <td class="p-2.5 text-center font-mono font-bold {{ $history->failed_rows > 0 ? 'text-rose-600' : 'text-slate-400' }}">{{ $history->failed_rows }}</td>
                                <td class="p-2.5 text-right">
                                    @if ($history->error_file_path && $history->failed_rows > 0)
                                        <a href="{{ route('store.admin.import-history.errors', ['store_slug' => $store->slug, 'history' => $history]) }}" class="text-xs font-bold text-rose-600 hover:underline">
                                            ⬇ Error File
                                        </a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ============================================================
         MODAL: ADD GLASS ITEM
         ============================================================ --}}
    <div x-cloak x-show="addModalOpen" x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/60 backdrop-blur-xs">
        <div @click.outside="addModalOpen = false"
             class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl p-4 sm:p-5 space-y-4">
            
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="text-base">📱</span>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ __('messages.glass_finder_add_item') }}</h3>
                </div>
                <button type="button" @click="addModalOpen = false" class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-700 grid place-items-center text-sm">✕</button>
            </div>

            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/glass-finder') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Brand <span class="text-rose-500">*</span></label>
                        <input type="text" name="brand" required placeholder="e.g. Apple, Samsung, Xiaomi" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-violet-500 outline-none" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Phone Model <span class="text-rose-500">*</span></label>
                        <input type="text" name="phone_model" required placeholder="e.g. iPhone 15 Pro Max" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-violet-500 outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Glass Code <span class="text-rose-500">*</span></label>
                        <input type="text" name="glass_code" required placeholder="e.g. GX-001, IP15PM-TG" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs font-mono font-bold focus:ring-2 focus:ring-violet-500 outline-none" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Stock Status</label>
                        <select name="stock_status" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-violet-500 outline-none cursor-pointer">
                            <option value="in_stock">In Stock (လက်ကျန်ရှိ)</option>
                            <option value="out_of_stock">Out of Stock (ပြတ်လပ်)</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="addModalOpen = false" class="px-3.5 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg font-bold text-xs shadow-2xs transition active:scale-95">
                        {{ __('messages.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL: IMPORT EXCEL / CSV
         ============================================================ --}}
    <div x-cloak x-show="importModalOpen" x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/60 backdrop-blur-xs">
        <div @click.outside="importModalOpen = false"
             class="w-full max-w-md bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl p-4 sm:p-5 space-y-4">
            
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="text-base">⬆</span>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ __('messages.glass_finder_import_btn') }}</h3>
                </div>
                <button type="button" @click="importModalOpen = false" class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-700 grid place-items-center text-sm">✕</button>
            </div>

            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/glass-finder/import') }}" enctype="multipart/form-data" class="space-y-3.5">
                @csrf
                
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-lg border border-slate-200/80 dark:border-slate-700/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Excel Sample Template</span>
                        <a href="{{ route('store.admin.glass-finder.import.template', ['store_slug' => $store->slug]) }}"
                           class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                            <span>⬇ Download (.xlsx)</span>
                        </a>
                    </div>
                    <p class="text-[10px] text-slate-400">Required columns: <code class="font-mono bg-white dark:bg-slate-900 px-1 py-0.5 rounded border border-slate-200 dark:border-slate-700">brand, phone_model, glass_code, stock_status</code></p>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Select Excel / CSV File</label>
                    <input type="file" name="file" accept=".csv,.xlsx" required
                           class="block w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 dark:file:bg-emerald-950/60 file:text-emerald-700 dark:file:text-emerald-300 hover:file:bg-emerald-100 cursor-pointer border border-slate-200 dark:border-slate-700 rounded-lg" />
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="importModalOpen = false" class="px-3.5 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs shadow-2xs transition active:scale-95">
                        Upload & Preview
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
