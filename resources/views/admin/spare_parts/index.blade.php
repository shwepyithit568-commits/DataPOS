@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12"
     x-data="{
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('store.admin.repairs.index', $storeRouteParams) }}"
               class="w-10 h-10 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/80 transition grid place-items-center shadow-sm flex-shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div class="min-w-0">
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span>📦</span>
                    <span class="truncate">{{ __('messages.spare_parts_title') }}</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $store->name }} · {{ __('messages.spare_parts_subtitle') }}</p>
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2 self-end sm:self-auto flex-wrap">
            <a href="{{ route('store.admin.repairs.create', $storeRouteParams) }}"
               class="px-4 py-2.5 rounded-2xl text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-500/20 transition flex items-center gap-1.5">
                <span>+</span>
                <span>{{ __('messages.repair_new_job') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. Flash Messages --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-3xl text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <span class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900 grid place-items-center text-emerald-600 dark:text-emerald-300 font-black">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-3xl text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1 shadow-sm">
            @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- 3. 4 Stat KPI Metric Cards (Responsive 1-col on mobile, 2-col on sm/md, 4-col on lg/desktop) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        {{-- Total Parts Used --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.spare_parts_total_qty') }}</p>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white font-mono tracking-tight">{{ number_format($metrics['total_qty']) }}</h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ number_format($metrics['total_count']) }} {{ __('messages.repair_items_section') }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                📦
            </span>
        </div>

        {{-- Total Value --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.spare_parts_total_value') }}</p>
                <h3 class="text-2xl sm:text-3xl font-black text-violet-600 dark:text-violet-400 font-mono tracking-tight">{{ number_format($metrics['total_value']) }}</h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ $store->currency ?? 'MMK' }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                💰
            </span>
        </div>

        {{-- Stock Deducted --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mb-1 flex items-center gap-1 truncate">
                    <span>✓</span> <span class="truncate">{{ __('messages.spare_parts_deducted') }}</span>
                </p>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white font-mono tracking-tight">{{ number_format($metrics['deducted_qty']) }}</h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ number_format($metrics['deducted_value']) }} {{ $store->currency ?? 'MMK' }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                ✅
            </span>
        </div>

        {{-- Pending Stock Deduction --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-amber-600 dark:text-amber-400 mb-1 flex items-center gap-1 truncate">
                    <span>⚠️</span> <span class="truncate">{{ __('messages.spare_parts_pending') }}</span>
                </p>
                <h3 class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight">{{ number_format($metrics['pending_qty']) }}</h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ number_format($metrics['pending_value']) }} {{ $store->currency ?? 'MMK' }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                ⏳
            </span>
        </div>
    </div>

    {{-- 4. Unified Admin Toolbar (Matching admin/products with single horizontal scroll row) --}}
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

    {{-- 5. Card Grid View (Responsive Cards for Mobile, Tablet, Desktop) --}}
    <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse ($items as $item)
            @php
                $job = $item->job;
                $customerName = $job?->customer?->name ?? $job?->contact_name ?? 'Walk-in';
                $customerPhone = $job?->customer?->phone ?? $job?->contact_phone ?? '';
                $device = trim(($job?->brand ?? '') . ' ' . ($job?->model ?? $job?->device_type ?? ''));
            @endphp
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4 group">
                
                {{-- Card Top Row: Job # & Status Badge --}}
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <a href="{{ route('store.admin.repairs.show', array_merge($storeRouteParams, ['repair' => $job->id])) }}"
                               class="font-mono font-black text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                {{ $job->job_number }}
                            </a>
                            <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $item->created_at?->format('d M Y, h:i A') }}</p>
                        </div>

                        {{-- Stock Status Badge --}}
                        <div>
                            @if ($item->is_deducted)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800">
                                    <span>✓</span>
                                    <span>{{ __('messages.spare_parts_deducted') }}</span>
                                </span>
                            @elseif ($item->product_id)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800">
                                    <span>⚠️</span>
                                    <span>{{ __('messages.spare_parts_pending') }}</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-500">
                                    <span>—</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Device & Customer Info --}}
                    <div class="space-y-1">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
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
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-2">
                        <div class="space-y-1">
                            <div class="font-black text-xs text-slate-900 dark:text-slate-100 leading-snug">
                                {{ $item->name }}
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                @if ($item->sku)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-mono font-bold border border-slate-200 dark:border-slate-600">
                                        {{ $item->sku }}
                                    </span>
                                @endif
                                @if ($item->product)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 font-semibold border border-violet-200/80 dark:border-violet-800">
                                        📁 {{ $item->product->category?->name ?? 'General' }}
                                    </span>
                                @else
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-200/70 dark:bg-slate-700 text-slate-600 dark:text-slate-400 font-medium border border-slate-200 dark:border-slate-600">
                                        ✏️ {{ __('messages.spare_parts_custom_part') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Quantity & Price breakdown --}}
                        <div class="pt-2 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between text-xs">
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200">Qty: {{ $item->quantity }}</span>
                                <span class="text-slate-400">× {{ number_format((float) $item->unit_price) }}</span>
                            </div>
                            <div class="font-mono font-black text-sm text-slate-900 dark:text-white">
                                {{ number_format((float) $item->subtotal) }} <span class="text-[10px] text-slate-400 font-normal">{{ $store->currency ?? 'MMK' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card Bottom Actions --}}
                <div class="pt-2 flex items-center gap-2">
                    @if (! $item->is_deducted && $item->product_id && ! $job->isTerminal())
                        <form method="POST" action="{{ route('store.admin.spare_parts.deduct', array_merge($storeRouteParams, ['item' => $item->id])) }}"
                              onsubmit="return confirm('{{ __('messages.repair_deduct_confirm') }}');" class="flex-1">
                            @csrf
                            <button type="submit"
                                    class="w-full px-3 py-2 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition flex items-center justify-center gap-1.5">
                                <span>⚡</span>
                                <span>{{ __('messages.spare_parts_deduct_btn') }}</span>
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('store.admin.repairs.show', array_merge($storeRouteParams, ['repair' => $job->id])) }}"
                       class="px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition text-center flex-1">
                        {{ __('messages.view') }}
                    </a>
                </div>

            </div>
        @empty
            <div class="col-span-full py-16 text-center text-slate-400 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800">
                <span class="text-5xl">📦</span>
                <p class="text-base font-black text-slate-700 dark:text-slate-200 mt-2">{{ __('messages.spare_parts_no_items') }}</p>
                <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">ဆာဗစ်လက်မှတ်များတွင် အပိုပစ္စည်းများ ထည့်သွင်းအသုံးပြုပါက ဤနေရာတွင် စာရင်းပေါ်လာပါမည်။</p>
            </div>
        @endforelse
    </div>

    {{-- 6. Table View (Responsive Table for Desktop / Tablet with smooth horizontal scrolling on mobile) --}}
    <div x-show="viewMode === 'table'" x-cloak class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        
        {{-- Mobile Swipe Hint Bar (visible only on small mobile screens) --}}
        <div class="sm:hidden px-4 py-2.5 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] font-semibold text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1.5">
                <span class="animate-pulse">⟷</span>
                <span>ဘေးသို့ ဆွဲရွှေ့၍ ကြည့်နိုင်ပါသည် (Swipe table)</span>
            </span>
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-200/70 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-mono">{{ $items->total() }} items</span>
        </div>

        <div class="overflow-x-auto scrollbar-thin">
            <table class="w-full text-left border-collapse min-w-[980px]">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 text-[11px] font-black text-slate-500 uppercase tracking-wider">
                        <th class="px-5 py-4 w-[160px]">{{ __('messages.spare_parts_date') }} & Job #</th>
                        <th class="px-5 py-4 w-[200px]">{{ __('messages.spare_parts_device') }} & {{ __('messages.customer') }}</th>
                        <th class="px-5 py-4 w-[260px]">{{ __('messages.repair_items_section') }} (Spare Part)</th>
                        <th class="px-5 py-4 w-[90px] text-center">{{ __('messages.quantity') }}</th>
                        <th class="px-5 py-4 w-[110px] text-right">{{ __('messages.price') }}</th>
                        <th class="px-5 py-4 w-[130px] text-right">{{ __('messages.subtotal') }}</th>
                        <th class="px-5 py-4 w-[140px] text-center">{{ __('messages.status') }}</th>
                        <th class="px-5 py-4 w-[160px] text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs font-medium text-slate-700 dark:text-slate-300">
                    @forelse ($items as $item)
                        @php
                            $job = $item->job;
                            $customerName = $job?->customer?->name ?? $job?->contact_name ?? 'Walk-in';
                            $customerPhone = $job?->customer?->phone ?? $job?->contact_phone ?? '';
                            $device = trim(($job?->brand ?? '') . ' ' . ($job?->model ?? $job?->device_type ?? ''));
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition duration-150">
                            {{-- Date & Job # --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <a href="{{ route('store.admin.repairs.show', array_merge($storeRouteParams, ['repair' => $job->id])) }}"
                                   class="font-mono font-black text-sm text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                                    <span>{{ $job->job_number }}</span>
                                    <svg class="w-3 h-3 text-blue-400 opacity-0 hover:opacity-100 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $item->created_at?->format('d M Y, h:i A') }}</p>
                            </td>

                            {{-- Device & Customer --}}
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 truncate max-w-[190px]">
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
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-slate-900 dark:text-slate-100">{{ $item->name }}</span>
                                    @if ($item->sku)
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono font-bold border border-slate-200 dark:border-slate-700">
                                            {{ $item->sku }}
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    @if ($item->product)
                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 font-semibold border border-violet-200/80 dark:border-violet-800">
                                            📁 {{ $item->product->category?->name ?? 'General' }}
                                        </span>
                                    @else
                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 font-semibold border border-slate-200 dark:border-slate-700">
                                            ✏️ {{ __('messages.spare_parts_custom_part') }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Quantity --}}
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 font-mono font-black text-slate-800 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700/60 shadow-inner">
                                    {{ $item->quantity }}
                                </span>
                            </td>

                            {{-- Unit Price --}}
                            <td class="px-5 py-4 text-right font-mono font-semibold whitespace-nowrap text-slate-600 dark:text-slate-300">
                                {{ number_format((float) $item->unit_price) }}
                            </td>

                            {{-- Subtotal --}}
                            <td class="px-5 py-4 text-right font-mono font-black text-slate-900 dark:text-white whitespace-nowrap">
                                {{ number_format((float) $item->subtotal) }} <span class="text-[10px] text-slate-400 font-normal">{{ $store->currency ?? 'MMK' }}</span>
                            </td>

                            {{-- Stock Deduction Status --}}
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                @if ($item->is_deducted)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>{{ __('messages.spare_parts_deducted') }}</span>
                                    </span>
                                @elseif ($item->product_id)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span>{{ __('messages.spare_parts_pending') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-400">
                                        <span>—</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Action --}}
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if (! $item->is_deducted && $item->product_id && ! $job->isTerminal())
                                        <form method="POST" action="{{ route('store.admin.spare_parts.deduct', array_merge($storeRouteParams, ['item' => $item->id])) }}"
                                              onsubmit="return confirm('{{ __('messages.repair_deduct_confirm') }}');">
                                            @csrf
                                            <button type="submit"
                                                    class="px-2.5 py-1.5 rounded-xl text-[11px] font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition flex items-center gap-1">
                                                <span>⚡</span>
                                                <span>{{ __('messages.spare_parts_deduct_btn') }}</span>
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('store.admin.repairs.show', array_merge($storeRouteParams, ['repair' => $job->id])) }}"
                                       class="px-3 py-1.5 rounded-xl text-[11px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition">
                                        {{ __('messages.view') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center text-slate-400">
                                <div class="space-y-2">
                                    <span class="text-4xl">📦</span>
                                    <p class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ __('messages.spare_parts_no_items') }}</p>
                                    <p class="text-xs text-slate-400">ဆာဗစ်လက်မှတ်များတွင် အပိုပစ္စည်းများ ထည့်သွင်းအသုံးပြုပါက ဤနေရာတွင် စာရင်းပေါ်လာပါမည်။</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 7. Bottom Pagination --}}
    @if ($items->hasPages())
        <div class="p-4 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm">
            {{ $items->links() }}
        </div>
    @endif

</div>
@endsection
