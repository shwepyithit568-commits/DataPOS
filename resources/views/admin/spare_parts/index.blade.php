@extends('layouts.admin.app')

@section('title', __('messages.spare_parts_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6"
     x-data="{
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

    {{-- ============================================================
         1. TOP ULTRA-DENSE HEADER BANNER (Standard v4.1)
         ============================================================ --}}
    <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none transition">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('store.admin.repairs.index', $storeRouteParams) }}"
               class="h-6 w-6 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition active:scale-95 shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="w-6 h-6 rounded bg-violet-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs shrink-0">
                <span>⚙️</span>
            </div>
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/60 px-1.5 py-0.5 rounded border border-violet-200/50 dark:border-violet-800/50 truncate max-w-[120px] sm:max-w-none">
                    {{ $store->name }}
                </span>
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight truncate">
                    {{ __('messages.spare_parts_title') }}
                </h1>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono hidden md:inline">
                    · {{ number_format($metrics['total_count']) }} {{ __('messages.repair_items_section') }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-1.5 shrink-0">
            <a href="{{ route('store.admin.repairs.index', $storeRouteParams) }}"
               class="h-7 px-2 sm:px-2.5 rounded text-[11px] sm:text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition inline-flex items-center gap-1 active:scale-95">
                <span>🔧</span>
                <span class="hidden sm:inline">{{ __('messages.sidebar_repair_center') }}</span>
            </a>

            <a href="{{ route('store.admin.repairs.create', $storeRouteParams) }}"
               class="h-7 px-2.5 sm:px-3 rounded text-[11px] sm:text-xs font-black bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <span class="text-sm leading-none">+</span>
                <span>{{ __('messages.repair_new_job') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications & Errors --}}
    @if (session('success'))
        <div class="w-full px-2 py-1.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="w-full px-2 py-1.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded text-xs text-rose-800 dark:text-rose-300 space-y-0.5 shadow-2xs">
            <div class="font-black flex items-center gap-1">
                <span>⚠️</span>
                <span>{{ __('messages.validation_error') }}:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="ml-4">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         2. 4 KEY KPI STAT CARDS (Centered Row-based Interactive Filter Pills)
         ============================================================ --}}
    @php
        $baseFilterParams = request()->except(['deducted', 'page']);
        $urlAll = route('store.admin.spare_parts.index', array_merge($storeRouteParams, $baseFilterParams, ['deducted' => 'all']));
        $urlDeducted = route('store.admin.spare_parts.index', array_merge($storeRouteParams, $baseFilterParams, ['deducted' => 'deducted']));
        $urlPending = route('store.admin.spare_parts.index', array_merge($storeRouteParams, $baseFilterParams, ['deducted' => 'pending']));
        $currentDeducted = $deductedFilter ?: 'all';
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1 select-none">
        {{-- Card 1: Total Parts Used (All) --}}
        <a href="{{ $urlAll }}"
           class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition cursor-pointer active:scale-[0.98]
                  {{ $currentDeducted === 'all'
                      ? 'bg-blue-50/80 dark:bg-blue-950/40 border-blue-400 dark:border-blue-600 ring-2 ring-blue-500/20'
                      : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-800 hover:bg-blue-50/30' }}"
           title="{{ __('messages.spare_parts_total_qty') }} ({{ __('messages.spare_parts_all_status') }})">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border
                        {{ $currentDeducted === 'all'
                            ? 'bg-blue-600 text-white border-blue-600 shadow-2xs'
                            : 'bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 border-blue-100 dark:border-blue-900/50' }}">
                📦
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate {{ $currentDeducted === 'all' ? 'text-blue-900 dark:text-blue-200' : 'text-slate-500 dark:text-slate-400' }}">
                    {{ __('messages.spare_parts_total_qty') }}
                </div>
                <div class="text-sm sm:text-base font-black text-blue-600 dark:text-blue-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ format_quantity($metrics['total_qty'], $store) }}</span>
                    @if ($currentDeducted === 'all')
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                    @endif
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate">
                    {{ number_format($metrics['total_count']) }} {{ __('messages.repair_items_section') }}
                </div>
            </div>
        </a>

        {{-- Card 2: Total Value --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 border-violet-100 dark:border-violet-900/50">
                💰
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.spare_parts_total_value') }}
                </div>
                <div class="text-sm sm:text-base font-black text-violet-600 dark:text-violet-400 font-mono tracking-tight">
                    {{ format_currency($metrics['total_value'], $store) }}
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate">
                    {{ $store->currency ?? 'MMK' }} Total
                </div>
            </div>
        </div>

        {{-- Card 3: Stock Deducted --}}
        <a href="{{ $urlDeducted }}"
           class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition cursor-pointer active:scale-[0.98]
                  {{ $currentDeducted === 'deducted'
                      ? 'bg-emerald-50/80 dark:bg-emerald-950/40 border-emerald-400 dark:border-emerald-600 ring-2 ring-emerald-500/20'
                      : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-emerald-300 dark:hover:border-emerald-800 hover:bg-emerald-50/30' }}"
           title="{{ __('messages.spare_parts_deducted') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border
                        {{ $currentDeducted === 'deducted'
                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                            : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/50' }}">
                ✅
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate {{ $currentDeducted === 'deducted' ? 'text-emerald-900 dark:text-emerald-200' : 'text-slate-500 dark:text-slate-400' }}">
                    {{ __('messages.spare_parts_deducted') }}
                </div>
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ format_quantity($metrics['deducted_qty'], $store) }}</span>
                    @if ($currentDeducted === 'deducted')
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    @endif
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate font-mono">
                    {{ format_currency($metrics['deducted_value'], $store) }}
                </div>
            </div>
        </a>

        {{-- Card 4: Pending Stock Deduction --}}
        <a href="{{ $urlPending }}"
           class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition cursor-pointer active:scale-[0.98]
                  {{ $currentDeducted === 'pending'
                      ? 'bg-amber-50/80 dark:bg-amber-950/40 border-amber-400 dark:border-amber-600 ring-2 ring-amber-500/20'
                      : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-amber-300 dark:hover:border-amber-800 hover:bg-amber-50/30' }}"
           title="{{ __('messages.spare_parts_pending') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border
                        {{ $currentDeducted === 'pending'
                            ? 'bg-amber-500 text-white border-amber-500 shadow-2xs'
                            : 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border-amber-100 dark:border-amber-900/50' }}">
                ⏳
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate {{ $currentDeducted === 'pending' ? 'text-amber-900 dark:text-amber-200' : 'text-slate-500 dark:text-slate-400' }}">
                    {{ __('messages.spare_parts_pending') }}
                </div>
                <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ format_quantity($metrics['pending_qty'], $store) }}</span>
                    @if ($currentDeducted === 'pending')
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    @endif
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate font-mono">
                    {{ format_currency($metrics['pending_value'], $store) }}
                </div>
            </div>
        </a>
    </div>

    {{-- ============================================================
         3. UNIFIED ADMIN TOOLBAR (Standard v4.1)
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', $search)"
        :searchPlaceholder="__('messages.spare_parts_filter_search')"
        :sort="request('sort', $sort)"
        :sortOptions="[
            'newest' => __('messages.repair_sort_newest'),
            'oldest' => __('messages.repair_sort_oldest'),
            'subtotal_desc' => __('messages.spare_parts_sort_subtotal_desc'),
            'subtotal_asc' => __('messages.spare_parts_sort_subtotal_asc'),
            'price_desc' => __('messages.spare_parts_sort_price_desc'),
            'price_asc' => __('messages.spare_parts_sort_price_asc'),
            'qty_desc' => __('messages.spare_parts_sort_qty_desc'),
        ]"
        :filters="[
            'deducted' => [
                'label' => __('messages.status'),
                'options' => [
                    'all' => '📋 ' . __('messages.spare_parts_all_status'),
                    'deducted' => '✓ ' . __('messages.spare_parts_deducted'),
                    'pending' => '⚠️ ' . __('messages.spare_parts_pending'),
                ],
            ],
            'category_id' => [
                'label' => __('messages.category'),
                'options' => $categories,
            ],
            'date' => [
                'label' => __('messages.repair_date_range'),
                'type' => 'date',
            ],
        ]"
        :viewMode="'table'"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$exportUrl"
        :totalCount="$metrics['total_count']"
        :paginator="$items"
        :perPageOptions="[25 => '25', 50 => '50', 100 => '100', 'all' => 'All']"
    />

    {{-- ============================================================
         4. DUAL VIEWS: CARD GRID VIEW
         ============================================================ --}}
    <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0.5 sm:gap-1">
        @forelse ($items as $item)
            @php
                $job = $item->job;
                $customerName = $job?->customer?->name ?? $job?->contact_name ?? 'Walk-in';
                $customerPhone = $job?->customer?->phone ?? $job?->contact_phone ?? '';
                $device = trim(($job?->brand ?? '') . ' ' . ($job?->model ?? $job?->device_type ?? ''));
            @endphp
            <div class="bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition flex flex-col justify-between space-y-2 group">
                
                <div class="space-y-1.5">
                    {{-- Card Top Row: Job # & Status Badge --}}
                    <div class="flex items-start justify-between gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                        <div class="min-w-0">
                            <a href="{{ route('store.admin.repairs.show', array_merge($storeRouteParams, ['repair' => $job->id])) }}"
                               class="font-mono font-bold text-xs text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1 truncate">
                                <span>{{ $job->job_number }}</span>
                            </a>
                            <p class="text-[10px] text-slate-400 font-mono">{{ $item->created_at?->format('d M Y, h:i A') }}</p>
                        </div>

                        {{-- Stock Status Badge --}}
                        <div class="shrink-0">
                            @if ($item->is_deducted)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-black bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800">
                                    <span>✓</span>
                                    <span>{{ __('messages.spare_parts_deducted') }}</span>
                                </span>
                            @elseif ($item->product_id)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-black bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    <span>{{ __('messages.spare_parts_pending') }}</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-400">
                                    <span>—</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Device & Customer Info --}}
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-1 text-xs font-bold text-slate-900 dark:text-white truncate">
                            <span class="text-amber-500">📱</span>
                            <span class="truncate">{{ $device ?: 'Unknown Device' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                            <span class="truncate font-medium">👤 {{ $customerName }}</span>
                            @if ($customerPhone)
                                <span class="font-mono text-[10px] text-slate-400 shrink-0">{{ $customerPhone }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Spare Part Box --}}
                    <div class="p-2 rounded bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-1">
                        <div>
                            <div class="font-bold text-xs text-slate-900 dark:text-slate-100 leading-snug">
                                {{ $item->name }}
                            </div>
                            <div class="flex flex-wrap items-center gap-1 mt-0.5">
                                @if ($item->sku)
                                    <span class="text-[10px] px-1 py-0.2 rounded bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-mono font-bold border border-slate-200 dark:border-slate-600">
                                        {{ $item->sku }}
                                    </span>
                                @endif
                                @if ($item->product)
                                    <span class="text-[10px] px-1 py-0.2 rounded bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 font-semibold border border-violet-200/80 dark:border-violet-800">
                                        📁 {{ $item->product->category?->name ?? 'General' }}
                                    </span>
                                @else
                                    <span class="text-[10px] px-1 py-0.2 rounded bg-slate-200/70 dark:bg-slate-700 text-slate-600 dark:text-slate-400 font-medium">
                                        ✏️ {{ __('messages.spare_parts_custom_part') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Quantity & Price breakdown --}}
                        <div class="pt-1 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between text-xs">
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200">Qty: {{ format_quantity($item->quantity, $store) }}</span>
                                <span class="text-slate-400 font-mono">× {{ format_currency($item->unit_price, $store) }}</span>
                            </div>
                            <div class="font-mono font-black text-xs text-slate-900 dark:text-white">
                                {{ format_currency($item->subtotal, $store) }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card Bottom Actions --}}
                <div class="pt-0.5 flex items-center gap-1">
                    @if (! $item->is_deducted && $item->product_id && ! $job->isTerminal())
                        <form method="POST" action="{{ route('store.admin.spare_parts.deduct', array_merge($storeRouteParams, ['item' => $item->id])) }}"
                              onsubmit="return confirm('{{ __('messages.repair_deduct_confirm') }}');" class="flex-1">
                            @csrf
                            <button type="submit"
                                    class="w-full h-6 px-2 rounded text-[10px] font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition flex items-center justify-center gap-1 cursor-pointer">
                                <span>⚡</span>
                                <span>{{ __('messages.spare_parts_deduct_btn') }}</span>
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('store.admin.repairs.show', array_merge($storeRouteParams, ['repair' => $job->id])) }}"
                       class="h-6 px-2.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition text-center flex-1 flex items-center justify-center gap-1">
                        <span>👁️</span>
                        <span>{{ __('messages.view') }}</span>
                    </a>
                </div>

            </div>
        @empty
            <div class="col-span-full py-10 text-center text-slate-400 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs">
                <span class="text-2xl">📦</span>
                <p class="text-xs font-black text-slate-700 dark:text-slate-200 mt-1">{{ __('messages.spare_parts_no_items') }}</p>
                <p class="text-[11px] text-slate-400 max-w-sm mx-auto mt-0.5">{{ __('messages.spare_parts_no_items_hint') }}</p>
            </div>
        @endforelse
    </div>

    {{-- ============================================================
         5. DUAL VIEWS: SPREADSHEET TABLE VIEW (Default)
         ============================================================ --}}
    <div x-show="viewMode === 'table'" x-cloak class="bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-1.5 px-2 whitespace-nowrap">{{ __('messages.spare_parts_date') }} & Job #</th>
                        <th class="py-1.5 px-2 whitespace-nowrap">{{ __('messages.spare_parts_device') }} & {{ __('messages.customer') }}</th>
                        <th class="py-1.5 px-2 whitespace-nowrap">{{ __('messages.repair_items_section') }} (Spare Part)</th>
                        <th class="py-1.5 px-2 whitespace-nowrap text-center">{{ __('messages.quantity') }}</th>
                        <th class="py-1.5 px-2 whitespace-nowrap text-right">{{ __('messages.price') }}</th>
                        <th class="py-1.5 px-2 whitespace-nowrap text-right">{{ __('messages.subtotal') }}</th>
                        <th class="py-1.5 px-2 whitespace-nowrap text-center">{{ __('messages.status') }}</th>
                        <th class="py-1.5 px-2 whitespace-nowrap text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($items as $item)
                        @php
                            $job = $item->job;
                            $customerName = $job?->customer?->name ?? $job?->contact_name ?? 'Walk-in';
                            $customerPhone = $job?->customer?->phone ?? $job?->contact_phone ?? '';
                            $device = trim(($job?->brand ?? '') . ' ' . ($job?->model ?? $job?->device_type ?? ''));
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                            {{-- Date & Job # --}}
                            <td class="py-1.5 px-2 whitespace-nowrap">
                                <a href="{{ route('store.admin.repairs.show', array_merge($storeRouteParams, ['repair' => $job->id])) }}"
                                   class="font-mono font-bold text-xs text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                                    <span>{{ $job->job_number }}</span>
                                </a>
                                <p class="text-[10px] text-slate-400 font-mono">{{ $item->created_at?->format('d M Y, h:i A') }}</p>
                            </td>

                            {{-- Device & Customer --}}
                            <td class="py-1.5 px-2">
                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1 truncate max-w-[190px]">
                                    <span class="text-amber-500">📱</span>
                                    <span class="truncate">{{ $device ?: 'Unknown Device' }}</span>
                                </div>
                                <div class="text-[11px] text-slate-400 flex items-center gap-1 truncate max-w-[190px]">
                                    <span class="truncate">👤 {{ $customerName }}</span>
                                    @if ($customerPhone)
                                        <span class="font-mono text-[10px] text-slate-400">· {{ $customerPhone }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Spare Part & SKU --}}
                            <td class="py-1.5 px-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-slate-900 dark:text-slate-100">{{ $item->name }}</span>
                                    @if ($item->sku)
                                        <span class="text-[10px] px-1 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono font-bold border border-slate-200 dark:border-slate-700">
                                            {{ $item->sku }}
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1 mt-0.5">
                                    @if ($item->product)
                                        <span class="text-[10px] px-1.5 py-0.2 rounded bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 font-semibold border border-violet-200/80 dark:border-violet-800">
                                            📁 {{ $item->product->category?->name ?? 'General' }}
                                        </span>
                                    @else
                                        <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 font-semibold">
                                            ✏️ {{ __('messages.spare_parts_custom_part') }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Quantity (Highlighted & Bold as per standard) --}}
                            <td class="py-1.5 px-2 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono font-black text-slate-900 dark:text-white border border-slate-200/70 dark:border-slate-700 shadow-2xs">
                                    {{ format_quantity($item->quantity, $store) }}
                                </span>
                            </td>

                            {{-- Unit Price --}}
                            <td class="py-1.5 px-2 text-right font-mono font-semibold whitespace-nowrap text-slate-600 dark:text-slate-300">
                                {{ format_currency($item->unit_price, $store) }}
                            </td>

                            {{-- Subtotal --}}
                            <td class="py-1.5 px-2 text-right font-mono font-black text-slate-900 dark:text-white whitespace-nowrap">
                                {{ format_currency($item->subtotal, $store) }}
                            </td>

                            {{-- Stock Deduction Status --}}
                            <td class="py-1.5 px-2 text-center whitespace-nowrap">
                                @if ($item->is_deducted)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-black bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>{{ __('messages.spare_parts_deducted') }}</span>
                                    </span>
                                @elseif ($item->product_id)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-black bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span>{{ __('messages.spare_parts_pending') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-400">
                                        <span>—</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-1.5 px-2 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    @if (! $item->is_deducted && $item->product_id && ! $job->isTerminal())
                                        <form method="POST" action="{{ route('store.admin.spare_parts.deduct', array_merge($storeRouteParams, ['item' => $item->id])) }}"
                                              onsubmit="return confirm('{{ __('messages.repair_deduct_confirm') }}');">
                                            @csrf
                                            <button type="submit"
                                                    class="h-6 px-2 rounded text-[10px] font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition flex items-center gap-1 cursor-pointer">
                                                <span>⚡</span>
                                                <span>{{ __('messages.spare_parts_deduct_btn') }}</span>
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('store.admin.repairs.show', array_merge($storeRouteParams, ['repair' => $job->id])) }}"
                                       class="h-6 px-2 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition flex items-center gap-1">
                                        <span>👁️</span>
                                        <span>{{ __('messages.view') }}</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">
                                <div class="space-y-1">
                                    <span class="text-2xl">📦</span>
                                    <p class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('messages.spare_parts_no_items') }}</p>
                                    <p class="text-[11px] text-slate-400">{{ __('messages.spare_parts_no_items_hint') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bottom Pagination --}}
    @if (method_exists($items, 'links'))
        <div class="pt-1">{{ $items->links() }}</div>
    @endif

</div>
@endsection
