@extends('layouts.admin.app')

@section('title', __('messages.spare_parts_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

    {{-- ============================================================
         1. TOP PAGE HEADER — Eyebrow, Title, Context & Action
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="flex items-center gap-2.5 min-w-0">
            <a href="{{ route('store.admin.repairs.index', $storeRouteParams) }}"
               class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition grid place-items-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div class="min-w-0">
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-sky-100 dark:border-sky-900/60 mb-0.5">
                    <span>📦</span>
                    <span>{{ __('messages.spare_parts_title') }}</span>
                    <span class="text-slate-400 dark:text-slate-500">·</span>
                    <span class="font-normal normal-case text-slate-500 dark:text-slate-400">Service Consumption & Ledger</span>
                </div>
                <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2 truncate">
                    <span>{{ __('messages.spare_parts_title') }}</span>
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                    {{ $store->name }} · {{ __('messages.spare_parts_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('store.admin.repairs.create', $storeRouteParams) }}"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <span class="text-sm leading-none">+</span>
                <span>{{ __('messages.repair_new_job') }}</span>
            </a>
        </div>
    </header>

    {{-- Flash Notifications & Errors --}}
    @if (session('success'))
        <div class="w-full p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="w-full p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-2xs">
            <div class="font-black flex items-center gap-1.5">
                <span>⚠️</span>
                <span>{{ __('messages.validation_error') }}:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="ml-4">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         2. 4 KEY KPI STAT CARDS
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2">
        {{-- Total Parts Used --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.spare_parts_total_qty') }}</span>
                <span class="text-xs">📦</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono tracking-tight">{{ number_format($metrics['total_qty']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ number_format($metrics['total_count']) }} {{ __('messages.repair_items_section') }}</div>
        </div>

        {{-- Total Value --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-violet-600 dark:text-violet-400 truncate">{{ __('messages.spare_parts_total_value') }}</span>
                <span class="text-xs">💰</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-violet-600 dark:text-violet-400 mt-1 font-mono tracking-tight">{{ number_format($metrics['total_value']) }} <span class="text-xs font-normal">Ks</span></div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ $store->currency ?? 'MMK' }} Total</div>
        </div>

        {{-- Stock Deducted --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 truncate">{{ __('messages.spare_parts_deducted') }}</span>
                <span class="text-xs">✅</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono tracking-tight">{{ number_format($metrics['deducted_qty']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ number_format($metrics['deducted_value']) }} {{ $store->currency ?? 'MMK' }}</div>
        </div>

        {{-- Pending Stock Deduction --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 truncate">{{ __('messages.spare_parts_pending') }}</span>
                <span class="text-xs">⏳</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-amber-600 dark:text-amber-400 mt-1 font-mono tracking-tight">{{ number_format($metrics['pending_qty']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ number_format($metrics['pending_value']) }} {{ $store->currency ?? 'MMK' }}</div>
        </div>
    </div>

    {{-- ============================================================
         3. UNIFIED ADMIN TOOLBAR
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', $search)"
        :searchPlaceholder="__('messages.spare_parts_filter_search')"
        :sort="request('sort', $sort)"
        :sortOptions="[
            'newest' => __('messages.repair_sort_newest'),
            'oldest' => __('messages.repair_sort_oldest'),
            'subtotal_desc' => 'ကျသင့်ငွေ အများဆုံး (Highest Subtotal)',
            'subtotal_asc' => 'ကျသင့်ငွေ အနည်းဆုံး (Lowest Subtotal)',
            'price_desc' => 'ဈေးနှုန်း အများဆုံး (Highest Price)',
            'qty_desc' => 'အရေအတွက် အများဆုံး (Highest Quantity)',
        ]"
        :filters="[
            'deducted' => [
                'label' => __('messages.status'),
                'options' => [
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
    <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2 sm:gap-2.5">
        @forelse ($items as $item)
            @php
                $job = $item->job;
                $customerName = $job?->customer?->name ?? $job?->contact_name ?? 'Walk-in';
                $customerPhone = $job?->customer?->phone ?? $job?->contact_phone ?? '';
                $device = trim(($job?->brand ?? '') . ' ' . ($job?->model ?? $job?->device_type ?? ''));
            @endphp
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition flex flex-col justify-between space-y-2.5 group">
                
                <div class="space-y-2">
                    {{-- Card Top Row: Job # & Status Badge --}}
                    <div class="flex items-start justify-between gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-2">
                        <div>
                            <a href="{{ route('store.admin.repairs.show', array_merge($storeRouteParams, ['repair' => $job->id])) }}"
                               class="font-mono font-bold text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                {{ $job->job_number }}
                            </a>
                            <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $item->created_at?->format('d M Y, h:i A') }}</p>
                        </div>

                        {{-- Stock Status Badge --}}
                        <div>
                            @if ($item->is_deducted)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-black bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800">
                                    <span>✓</span>
                                    <span>{{ __('messages.spare_parts_deducted') }}</span>
                                </span>
                            @elseif ($item->product_id)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-black bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800">
                                    <span>⚠️</span>
                                    <span>{{ __('messages.spare_parts_pending') }}</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-400">
                                    <span>—</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Device & Customer Info --}}
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-1 text-xs font-bold text-slate-900 dark:text-white">
                            <span>📱</span>
                            <span class="truncate">{{ $device ?: 'Unknown Device' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                            <span class="truncate font-medium">👤 {{ $customerName }}</span>
                            @if ($customerPhone)
                                <span class="font-mono text-[10px] text-slate-400">{{ $customerPhone }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Spare Part Box --}}
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-1.5">
                        <div>
                            <div class="font-bold text-xs text-slate-900 dark:text-slate-100 leading-snug">
                                {{ $item->name }}
                            </div>
                            <div class="flex flex-wrap items-center gap-1 mt-1">
                                @if ($item->sku)
                                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-mono font-bold border border-slate-200 dark:border-slate-600">
                                        {{ $item->sku }}
                                    </span>
                                @endif
                                @if ($item->product)
                                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 font-semibold border border-violet-200/80 dark:border-violet-800">
                                        📁 {{ $item->product->category?->name ?? 'General' }}
                                    </span>
                                @else
                                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-200/70 dark:bg-slate-700 text-slate-600 dark:text-slate-400 font-medium">
                                        ✏️ {{ __('messages.spare_parts_custom_part') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Quantity & Price breakdown --}}
                        <div class="pt-1.5 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between text-xs">
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200">Qty: {{ $item->quantity }}</span>
                                <span class="text-slate-400">× {{ number_format((float) $item->unit_price) }}</span>
                            </div>
                            <div class="font-mono font-black text-xs text-slate-900 dark:text-white">
                                {{ number_format((float) $item->subtotal) }} <span class="text-[10px] text-slate-400 font-normal">{{ $store->currency ?? 'MMK' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card Bottom Actions --}}
                <div class="pt-1 flex items-center gap-1.5">
                    @if (! $item->is_deducted && $item->product_id && ! $job->isTerminal())
                        <form method="POST" action="{{ route('store.admin.spare_parts.deduct', array_merge($storeRouteParams, ['item' => $item->id])) }}"
                              onsubmit="return confirm('{{ __('messages.repair_deduct_confirm') }}');" class="flex-1">
                            @csrf
                            <button type="submit"
                                    class="w-full px-2.5 py-1.5 rounded-md text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition flex items-center justify-center gap-1 cursor-pointer">
                                <span>⚡</span>
                                <span>{{ __('messages.spare_parts_deduct_btn') }}</span>
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('store.admin.repairs.show', array_merge($storeRouteParams, ['repair' => $job->id])) }}"
                       class="px-3 py-1.5 rounded-md text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition text-center flex-1">
                        {{ __('messages.view') }}
                    </a>
                </div>

            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
                <span class="text-3xl">📦</span>
                <p class="text-xs font-black text-slate-700 dark:text-slate-200 mt-1">{{ __('messages.spare_parts_no_items') }}</p>
                <p class="text-[11px] text-slate-400 max-w-sm mx-auto mt-0.5">ဆာဗစ်လက်မှတ်များတွင် အပိုပစ္စည်းများ ထည့်သွင်းအသုံးပြုပါက ဤနေရာတွင် စာရင်းပေါ်လာပါမည်။</p>
            </div>
        @endforelse
    </div>

    {{-- ============================================================
         5. DUAL VIEWS: SPREADSHEET TABLE VIEW (Default)
         ============================================================ --}}
    <div x-show="viewMode === 'table'" x-cloak class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="p-2.5 whitespace-nowrap">{{ __('messages.spare_parts_date') }} & Job #</th>
                        <th class="p-2.5 whitespace-nowrap">{{ __('messages.spare_parts_device') }} & {{ __('messages.customer') }}</th>
                        <th class="p-2.5 whitespace-nowrap">{{ __('messages.repair_items_section') }} (Spare Part)</th>
                        <th class="p-2.5 whitespace-nowrap text-center">{{ __('messages.quantity') }}</th>
                        <th class="p-2.5 whitespace-nowrap text-right">{{ __('messages.price') }}</th>
                        <th class="p-2.5 whitespace-nowrap text-right">{{ __('messages.subtotal') }}</th>
                        <th class="p-2.5 whitespace-nowrap text-center">{{ __('messages.status') }}</th>
                        <th class="p-2.5 whitespace-nowrap text-right">{{ __('messages.actions') }}</th>
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
                            <td class="p-2.5 whitespace-nowrap">
                                <a href="{{ route('store.admin.repairs.show', array_merge($storeRouteParams, ['repair' => $job->id])) }}"
                                   class="font-mono font-bold text-xs text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                                    <span>{{ $job->job_number }}</span>
                                </a>
                                <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $item->created_at?->format('d M Y, h:i A') }}</p>
                            </td>

                            {{-- Device & Customer --}}
                            <td class="p-2.5">
                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1 truncate max-w-[190px]">
                                    <span>📱</span>
                                    <span class="truncate">{{ $device ?: 'Unknown Device' }}</span>
                                </div>
                                <div class="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5 truncate max-w-[190px]">
                                    <span class="truncate">👤 {{ $customerName }}</span>
                                    @if ($customerPhone)
                                        <span class="font-mono text-[10px] text-slate-400">· {{ $customerPhone }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Spare Part & SKU --}}
                            <td class="p-2.5">
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

                            {{-- Quantity --}}
                            <td class="p-2.5 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono font-black text-slate-800 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700">
                                    {{ $item->quantity }}
                                </span>
                            </td>

                            {{-- Unit Price --}}
                            <td class="p-2.5 text-right font-mono font-semibold whitespace-nowrap text-slate-600 dark:text-slate-300">
                                {{ number_format((float) $item->unit_price) }}
                            </td>

                            {{-- Subtotal --}}
                            <td class="p-2.5 text-right font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                {{ number_format((float) $item->subtotal) }} <span class="text-[10px] text-slate-400 font-normal">{{ $store->currency ?? 'MMK' }}</span>
                            </td>

                            {{-- Stock Deduction Status --}}
                            <td class="p-2.5 text-center whitespace-nowrap">
                                @if ($item->is_deducted)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-black bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>{{ __('messages.spare_parts_deducted') }}</span>
                                    </span>
                                @elseif ($item->product_id)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-black bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span>{{ __('messages.spare_parts_pending') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-400">
                                        <span>—</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Action --}}
                            <td class="p-2.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    @if (! $item->is_deducted && $item->product_id && ! $job->isTerminal())
                                        <form method="POST" action="{{ route('store.admin.spare_parts.deduct', array_merge($storeRouteParams, ['item' => $item->id])) }}"
                                              onsubmit="return confirm('{{ __('messages.repair_deduct_confirm') }}');">
                                            @csrf
                                            <button type="submit"
                                                    class="px-2 py-1 rounded text-[10px] font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition flex items-center gap-1 cursor-pointer">
                                                <span>⚡</span>
                                                <span>{{ __('messages.spare_parts_deduct_btn') }}</span>
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('store.admin.repairs.show', array_merge($storeRouteParams, ['repair' => $job->id])) }}"
                                       class="px-2 py-1 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition">
                                        {{ __('messages.view') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">
                                <div class="space-y-1">
                                    <span class="text-3xl">📦</span>
                                    <p class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('messages.spare_parts_no_items') }}</p>
                                    <p class="text-[11px] text-slate-400">ဆာဗစ်လက်မှတ်များတွင် အပိုပစ္စည်းများ ထည့်သွင်းအသုံးပြုပါက ဤနေရာတွင် စာရင်းပေါ်လာပါမည်။</p>
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
