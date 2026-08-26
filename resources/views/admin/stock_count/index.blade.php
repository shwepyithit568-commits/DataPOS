@extends('layouts.admin.app')

@section('title', __('messages.sidebar_stock_count') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5">

    {{-- ============================================================
         1. COMPACT HERO PAGE HEADER (Admin UI Standard)
         ============================================================ --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
        <div class="min-w-0">
            <div class="flex items-center gap-1.5 mb-0.5">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                    <span>📋</span>
                    <span>{{ __('messages.sidebar_stock_count') }}</span>
                </span>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ $store->name }}</span>
            </div>
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span>{{ __('messages.stock_count_title') }}</span>
                <span class="text-xs font-mono font-bold text-slate-400">({{ number_format($sessions->total()) }})</span>
            </h1>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ __('messages.stock_count_sub') }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 shrink-0">
            {{-- Quick Link to Stock Ledger --}}
            <a href="{{ route('store.admin.stock_ledger.index', ['store_slug' => $store->slug]) }}"
               class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                <span>📑</span>
                <span>{{ __('messages.sidebar_stock_ledger') ?? 'Stock Ledger' }}</span>
            </a>

            {{-- Primary Action: New Stock Count Session --}}
            <a href="{{ route('store.admin.stock_count.create', ['store_slug' => $store->slug]) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition flex items-center gap-1.5 active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('messages.stock_count_new_session') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. KPI SUMMARY METRIC CARDS (4-UP CLICK-TO-FILTER)
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5" role="list" aria-label="Stock Count Status Metrics">
        {{-- Total Sessions --}}
        <a href="{{ route('store.admin.stock_count.index', array_merge(['store_slug' => $store->slug], request()->except('status', 'page'))) }}"
           class="group p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 {{ empty($status) ? 'border-violet-600 bg-violet-50/60 dark:border-violet-500 dark:bg-violet-950/40 ring-2 ring-violet-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 shadow-inner">
                <span class="text-base sm:text-lg">📦</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-mono">
                    {{ number_format($stats['total']) }}
                </p>
                <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_count_stat_total') }}
                </p>
            </div>
        </a>

        {{-- In Progress (Active Count Sessions) --}}
        <a href="{{ route('store.admin.stock_count.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => 'in_progress'])) }}"
           class="group p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 {{ $status === 'in_progress' ? 'border-amber-600 bg-amber-50/60 dark:border-amber-500 dark:bg-amber-950/40 ring-2 ring-amber-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner relative">
                <span class="text-base sm:text-lg">⏳</span>
                @if($stats['in_progress'] > 0)
                    <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-mono">
                    {{ number_format($stats['in_progress']) }}
                </p>
                <p class="text-[10px] sm:text-[11px] text-amber-700 dark:text-amber-300/80 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_count_stat_in_progress') }}
                </p>
            </div>
        </a>

        {{-- Approved / Reconciled --}}
        <a href="{{ route('store.admin.stock_count.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => 'approved'])) }}"
           class="group p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 {{ $status === 'approved' ? 'border-emerald-600 bg-emerald-50/60 dark:border-emerald-500 dark:bg-emerald-950/40 ring-2 ring-emerald-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner">
                <span class="text-base sm:text-lg">✅</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-mono">
                    {{ number_format($stats['approved']) }}
                </p>
                <p class="text-[10px] sm:text-[11px] text-emerald-700 dark:text-emerald-300/80 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_count_stat_approved') }}
                </p>
            </div>
        </a>

        {{-- Cancelled --}}
        <a href="{{ route('store.admin.stock_count.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => 'cancelled'])) }}"
           class="group p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 {{ $status === 'cancelled' ? 'border-rose-600 bg-rose-50/60 dark:border-rose-500 dark:bg-rose-950/40 ring-2 ring-rose-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300 shadow-inner">
                <span class="text-base sm:text-lg">✕</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-mono">
                    {{ number_format($stats['cancelled']) }}
                </p>
                <p class="text-[10px] sm:text-[11px] text-rose-700 dark:text-rose-300/80 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.stock_count_stat_cancelled') }}
                </p>
            </div>
        </a>
    </div>

    {{-- ============================================================
         3. MASTER TOOLBAR COMPONENT (<x-admin.toolbar>)
         ============================================================ --}}
    <x-admin.toolbar
        :showSearch="true"
        :searchPlaceholder="__('messages.search') . ' Session No., warehouse, notes...'"
        :searchValue="$filters['search'] ?? ''"
        :filterCount="$activeFiltersCount ?? 0"
        :showViewToggle="true"
        :activeView="$activeView ?? 'table'"
        :showSort="true"
        :sort="$filters['sort'] ?? 'newest'"
        :sortOptions="[
            'newest'        => __('messages.stock_count_sort_newest'),
            'oldest'        => __('messages.stock_count_sort_oldest'),
            'progress_desc' => __('messages.stock_count_sort_progress'),
            'items_desc'    => __('messages.stock_count_sort_items'),
            'variance_desc' => __('messages.stock_count_sort_variance'),
        ]"
        :showPagination="true"
        :paginator="$sessions"
        :showPerPageSelector="true"
        :perPageOptions="[
            15    => '15',
            25    => '25',
            50    => '50',
            100   => '100',
            'all' => __('messages.all'),
        ]"
    >
        {{-- Quick Status Filter Tabs --}}
        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-xs shrink-0">
            @foreach([
                '' => __('messages.stock_count_all_status'),
                'in_progress' => __('messages.stock_count_status_in_progress'),
                'approved' => __('messages.stock_count_status_approved'),
                'cancelled' => __('messages.stock_count_status_cancelled'),
            ] as $stVal => $stLabel)
                <a href="{{ route('store.admin.stock_count.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => $stVal])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-bold transition whitespace-nowrap {{ ($filters['status'] ?? '') === $stVal ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                    {{ $stLabel }}
                </a>
            @endforeach
        </div>

        {{-- Filter Dropdown Slot --}}
        <x-slot:filterSlot>
            <div class="space-y-3 p-1 text-xs">
                {{-- Scope Filter --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1.5">
                        {{ __('messages.stock_count_scope') }}
                    </label>
                    <div class="grid grid-cols-2 gap-1">
                        @foreach([
                            '' => __('messages.stock_count_all_scopes'),
                            'all' => __('messages.stock_count_scope_all'),
                            'category' => __('messages.stock_count_scope_category'),
                        ] as $scVal => $scLabel)
                            <a href="{{ route('store.admin.stock_count.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['scope' => $scVal])) }}"
                               class="px-2 py-1 text-center text-xs font-bold rounded-md border transition {{ ($filters['scope'] ?? '') === $scVal ? 'bg-violet-600 text-white border-violet-600' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100' }}">
                                {{ $scLabel }}
                            </a>
                        @endforeach
                    </div>
                </div>

                @if($activeFiltersCount > 0)
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                        <a href="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug]) }}"
                           class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline">
                            {{ __('messages.reset') ?? 'Reset All Filters' }}
                        </a>
                    </div>
                @endif
            </div>
        </x-slot:filterSlot>
    </x-admin.toolbar>

    {{-- ============================================================
         4. SPREADSHEET DATA GRID (TABLE VIEW)
         ============================================================ --}}
    @if(($activeView ?? 'table') === 'table')
        <div class="rounded-lg border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto max-h-[68vh] overflow-y-auto">
                <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800 border-b-2 border-slate-300 dark:border-slate-700 shadow-xs select-none backdrop-blur-xs">
                        <tr class="text-[11px] font-black uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100">
                            <th class="py-2.5 px-3 min-w-[160px]">{{ __('messages.stock_count_session_number') }}</th>
                            <th class="py-2.5 px-3 min-w-[140px]">{{ __('messages.stock_count_location') }}</th>
                            <th class="py-2.5 px-3 min-w-[120px]">{{ __('messages.stock_count_scope') }}</th>
                            <th class="py-2.5 px-3 text-center min-w-[130px]">{{ __('messages.stock_count_progress') }}</th>
                            <th class="py-2.5 px-3 text-center min-w-[120px]">{{ __('messages.stock_count_variance_items') }}</th>
                            <th class="py-2.5 px-3 text-center min-w-[110px]">{{ __('messages.stock_count_status') }}</th>
                            <th class="py-2.5 px-3 min-w-[130px]">{{ __('messages.stock_count_date') }}</th>
                            <th class="py-2.5 px-2 text-right min-w-[130px]">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @forelse($sessions as $session)
                            @php
                                $progressPct = $session->total_items > 0 ? round(($session->counted_items / $session->total_items) * 100) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 divide-x divide-slate-200/80 dark:divide-slate-800 transition">
                                {{-- Session Number & Notes --}}
                                <td class="py-2 px-3">
                                    <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                                       class="font-mono font-black text-violet-600 dark:text-violet-400 hover:underline text-xs">
                                        {{ $session->session_number }}
                                    </a>
                                    @if($session->notes)
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-xs mt-0.5">{{ $session->notes }}</p>
                                    @endif
                                </td>

                                {{-- Warehouse / Branch --}}
                                <td class="py-2 px-3 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">
                                        {{ $session->warehouse?->name ?? $session->branch?->name ?? 'Default Warehouse' }}
                                    </div>
                                    @if($session->branch && $session->warehouse)
                                        <div class="text-[10px] text-slate-400">{{ $session->branch->name }}</div>
                                    @endif
                                </td>

                                {{-- Scope --}}
                                <td class="py-2 px-3">
                                    @if($session->scope === 'category')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-md bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                            <span>🏷️</span>
                                            <span>{{ __('messages.stock_count_scope_category') }}</span>
                                            @if(!empty($session->category_ids))
                                                <span class="font-mono">({{ count($session->category_ids) }})</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                            <span>📦</span>
                                            <span>{{ __('messages.stock_count_scope_all') }}</span>
                                        </span>
                                    @endif
                                </td>

                                {{-- Progress Indicator --}}
                                <td class="py-2 px-3 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="text-xs font-mono font-bold text-slate-800 dark:text-slate-200">
                                            {{ number_format($session->counted_items) }} / {{ number_format($session->total_items) }}
                                            <span class="text-[10px] text-slate-400">({{ $progressPct }}%)</span>
                                        </div>
                                        <div class="w-24 bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 mt-1 overflow-hidden">
                                            <div class="h-1.5 rounded-full transition-all duration-300 {{ $progressPct === 100 ? 'bg-emerald-500' : 'bg-violet-600' }}" style="width: {{ $progressPct }}%"></div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Variance Items --}}
                                <td class="py-2 px-3 text-center">
                                    @if($session->variance_items > 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold font-mono rounded-md bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                            <span>⚠️</span>
                                            <span>{{ number_format($session->variance_items) }} items</span>
                                        </span>
                                    @else
                                        <span class="text-xs font-mono text-slate-400 dark:text-slate-500">0</span>
                                    @endif
                                </td>

                                {{-- Status Badge --}}
                                <td class="py-2 px-3 text-center whitespace-nowrap">
                                    @if($session->isApproved())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <span>✓</span>
                                            <span>{{ __('messages.stock_count_status_approved') }}</span>
                                        </span>
                                    @elseif($session->isCancelled())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            <span>✕</span>
                                            <span>{{ __('messages.stock_count_status_cancelled') }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                            <span>{{ __('messages.stock_count_status_in_progress') }}</span>
                                        </span>
                                    @endif
                                </td>

                                {{-- Date & Creator --}}
                                <td class="py-2 px-3 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400 font-mono">
                                    <div>{{ $session->created_at->format('d/m/Y H:i') }}</div>
                                    <div class="text-[10px] text-slate-400 font-sans mt-0.5">{{ $session->createdBy?->name ?? 'System' }}</div>
                                </td>

                                {{-- Actions --}}
                                <td class="py-2 px-2 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{-- Print Sheet --}}
                                        <a href="{{ route('store.admin.stock_count.print', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                                           target="_blank"
                                           title="{{ __('messages.stock_count_print_sheet') }}"
                                           class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </a>

                                        {{-- Primary Action Link --}}
                                        <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-md transition shadow-2xs {{ $session->isInProgress() ? 'bg-violet-600 hover:bg-violet-700 text-white' : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200' }}">
                                            <span>{{ $session->isInProgress() ? __('messages.stock_count_continue_count') : __('messages.stock_count_view_audit') }}</span>
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 px-4 text-center text-slate-400 dark:text-slate-500">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <span class="text-4xl mb-2">📋</span>
                                        <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('messages.stock_count_no_sessions') }}</p>
                                        <p class="text-xs text-slate-400 mt-1">{{ __('messages.stock_count_create_first') }}</p>
                                        <a href="{{ route('store.admin.stock_count.create', ['store_slug' => $store->slug]) }}"
                                           class="mt-4 inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold rounded-lg bg-violet-600 text-white hover:bg-violet-700 shadow-md transition">
                                            <span>+</span>
                                            <span>{{ __('messages.stock_count_new_session') }}</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- ============================================================
             5. RESPONSIVE CARDS VIEW GRID (CARD VIEW MODE)
             ============================================================ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 sm:gap-3">
            @forelse($sessions as $session)
                @php
                    $progressPct = $session->total_items > 0 ? round(($session->counted_items / $session->total_items) * 100) : 0;
                @endphp
                <div class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between space-y-3 hover:border-slate-300 dark:hover:border-slate-700 transition group">
                    {{-- Header Row --}}
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                               class="font-mono font-black text-sm text-violet-600 dark:text-violet-400 hover:underline">
                                {{ $session->session_number }}
                            </a>

                            {{-- Status Badge --}}
                            @if($session->isApproved())
                                <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    {{ __('messages.stock_count_status_approved') }}
                                </span>
                            @elseif($session->isCancelled())
                                <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                    {{ __('messages.stock_count_status_cancelled') }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                    <span>{{ __('messages.stock_count_status_in_progress') }}</span>
                                </span>
                            @endif
                        </div>

                        {{-- Warehouse & Date --}}
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mt-1.5">
                            <span class="font-bold text-slate-700 dark:text-slate-300">
                                🏬 {{ $session->warehouse?->name ?? $session->branch?->name ?? 'Default Warehouse' }}
                            </span>
                            <span class="font-mono text-[11px]">{{ $session->created_at->format('d/m/Y') }}</span>
                        </div>

                        @if($session->notes)
                            <p class="text-[11px] text-slate-400 line-clamp-1 mt-1">{{ $session->notes }}</p>
                        @endif
                    </div>

                    {{-- Progress & Variance Metrics --}}
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-mono">
                            <span class="text-slate-500 dark:text-slate-400 font-bold">{{ __('messages.stock_count_progress') }}:</span>
                            <span class="font-black text-slate-900 dark:text-slate-100">
                                {{ number_format($session->counted_items) }} / {{ number_format($session->total_items) }} ({{ $progressPct }}%)
                            </span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full {{ $progressPct === 100 ? 'bg-emerald-500' : 'bg-violet-600' }}" style="width: {{ $progressPct }}%"></div>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-200/60 dark:border-slate-700">
                            <span class="text-slate-500 dark:text-slate-400">{{ __('messages.stock_count_variance_items') }}:</span>
                            @if($session->variance_items > 0)
                                <span class="font-bold font-mono text-amber-600 dark:text-amber-400">⚠️ {{ $session->variance_items }} items</span>
                            @else
                                <span class="font-mono text-slate-400">0</span>
                            @endif
                        </div>
                    </div>

                    {{-- Action Row --}}
                    <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                        <a href="{{ route('store.admin.stock_count.print', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                           target="_blank"
                           class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition text-xs font-bold flex items-center gap-1">
                            <span>🖨️</span>
                            <span>Print</span>
                        </a>

                        <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-2xs {{ $session->isInProgress() ? 'bg-violet-600 hover:bg-violet-700 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200' }}">
                            <span>{{ $session->isInProgress() ? __('messages.stock_count_continue_count') : __('messages.stock_count_view_audit') }}</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 px-4 text-center text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800">
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('messages.stock_count_no_sessions') }}</p>
                    <a href="{{ route('store.admin.stock_count.create', ['store_slug' => $store->slug]) }}"
                       class="mt-3 inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-lg bg-violet-600 text-white">
                        <span>+</span>
                        <span>{{ __('messages.stock_count_new_session') }}</span>
                    </a>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Bottom Pagination --}}
    @if($sessions->hasPages())
        <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            {{ $sessions->links() }}
        </div>
    @endif

</div>
@endsection
