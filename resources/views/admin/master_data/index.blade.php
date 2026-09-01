@extends('layouts.admin.app')

@section('title', __('messages.master_data') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6"
     x-data="{ viewMode: localStorage.getItem('admin_view_mode') || 'table' }"
     @view-changed.window="viewMode = $event.detail">

    @php
        $tabs = [
            'categories' => [
                'label' => __('messages.categories'),
                'count' => $summary['categories'],
                'icon' => '📂',
            ],
            'brands' => [
                'label' => __('messages.brands'),
                'count' => $summary['brands'],
                'icon' => '🏷️',
            ],
            'connectors' => [
                'label' => __('messages.master_data_tab_connectors'),
                'count' => $summary['connectors'] ?? 0,
                'icon' => '⚙️',
            ],
            'colors' => [
                'label' => __('messages.master_data_tab_colors'),
                'count' => $summary['colors'] ?? 0,
                'icon' => '🎨',
            ],
            'shelves' => [
                'label' => __('messages.master_data_tab_shelves'),
                'count' => $summary['shelves'] ?? 0,
                'icon' => '🗄️',
            ],
            'warranties' => [
                'label' => __('messages.master_data_tab_warranties'),
                'count' => $summary['warranties'] ?? 0,
                'icon' => '🛡️',
            ],
            'return-policies' => [
                'label' => __('messages.master_data_tab_return_policies'),
                'count' => $summary['return_policies'] ?? 0,
                'icon' => '🔄',
            ],
            'variant-presets' => [
                'label' => __('messages.variant_settings'),
                'count' => $summary['presets'],
                'icon' => '⚡',
            ],
            'seed-data' => [
                'label' => 'Seed Data',
                'count' => '🌱',
                'icon' => '🌱',
            ],
        ];

        $totalMasterItems = array_sum(array_map(function($t) { return is_numeric($t['count']) ? $t['count'] : 0; }, $tabs));
        $totalHardwarePresets = ($summary['connectors'] ?? 0) + ($summary['colors'] ?? 0) + ($summary['shelves'] ?? 0) + ($summary['warranties'] ?? 0) + ($summary['return_policies'] ?? 0);
    @endphp

    {{-- ============================================================
         1. COMPACT PAGE HEADER (Admin UI Standard v4.1)
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400 grid place-items-center text-base font-bold shadow-xs flex-shrink-0">
                ⚙️
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.master_data') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    အမျိုးအစားများ၊ အမှတ်တံဆိပ်များ၊ ပစ္စည်းကြိုတင်သတ်မှတ်ချက်များနှင့် အရွယ်အစား/အရောင်စနစ်များ
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 self-start sm:self-auto shrink-0">
            {{-- Tab-specific quick action --}}
            @if ($activeTab === 'categories')
                <button type="button" @click="$dispatch('open-category-create')"
                        class="h-7 px-2.5 rounded-md text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span>{{ __('messages.master_data_btn_category') }}</span>
                </button>
            @elseif ($activeTab === 'brands')
                <button type="button" @click="$dispatch('open-brand-create')"
                        class="h-7 px-2.5 rounded-md text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span>{{ __('messages.master_data_btn_brand') }}</span>
                </button>
            @elseif ($activeTab === 'variant-presets')
                <button type="button" @click="$dispatch('open-variant-create')"
                        class="h-7 px-2.5 rounded-md text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span>{{ __('messages.master_data_btn_variant_preset') }}</span>
                </button>
            @elseif ($activeTab === 'connectors')
                <button type="button" @click="$dispatch('open-preset-create')"
                        class="h-7 px-2.5 rounded-md text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span>{{ __('messages.master_data_btn_connector') }}</span>
                </button>
            @elseif ($activeTab === 'colors')
                <button type="button" @click="$dispatch('open-preset-create')"
                        class="h-7 px-2.5 rounded-md text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span>{{ __('messages.master_data_btn_color') }}</span>
                </button>
            @elseif ($activeTab === 'shelves')
                <button type="button" @click="$dispatch('open-preset-create')"
                        class="h-7 px-2.5 rounded-md text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span>{{ __('messages.master_data_btn_shelf') }}</span>
                </button>
            @elseif ($activeTab === 'warranties')
                <button type="button" @click="$dispatch('open-preset-create')"
                        class="h-7 px-2.5 rounded-md text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span>{{ __('messages.master_data_btn_warranty') }}</span>
                </button>
            @elseif ($activeTab === 'return-policies')
                <button type="button" @click="$dispatch('open-preset-create')"
                        class="h-7 px-2.5 rounded-md text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span>{{ __('messages.master_data_btn_return_policy') }}</span>
                </button>
            @elseif ($activeTab === 'seed-data')
                <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'seed-data']) }}"
                   class="h-7 px-2.5 rounded-md text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition flex items-center gap-1 active:scale-95 cursor-pointer">
                    🌱 <span>Seed Data</span>
                </a>
            @endif

            {{-- Quick Link to Products --}}
            <a href="{{ route('store.admin.products.index', ['store_slug' => $store->slug]) }}"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/70 dark:border-slate-700 transition flex items-center gap-1 cursor-pointer">
                <span>📦</span>
                <span>{{ __('messages.master_data_btn_all_products') }}</span>
            </a>

            {{-- Primary Action: Add Product --}}
            <a href="{{ route('store.admin.products.create', ['store_slug' => $store->slug]) }}"
               class="h-7 px-2.5 rounded-md text-xs font-black bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-white shadow-2xs transition flex items-center gap-1 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                <span>{{ __('messages.master_data_btn_add_product') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. KPI STAT CARDS (Ultra-Compact: 1-Row Desktop & Horizontal Scroll Mobile)
         ============================================================ --}}
    <div class="flex items-center gap-1 overflow-x-auto no-scrollbar py-0.5 w-full select-none" role="list" aria-label="Master Data Summary">
        {{-- 1. Categories --}}
        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'categories']) }}"
           role="listitem"
           class="group flex-1 min-w-[110px] lg:min-w-0 shrink-0 lg:shrink bg-white dark:bg-slate-900 px-1.5 sm:px-2 py-1 rounded-lg border shadow-2xs flex items-center justify-center gap-1.5 transition hover:border-violet-300 dark:hover:border-violet-600 {{ $activeTab === 'categories' ? 'border-violet-500 ring-1.5 ring-violet-500/30 bg-violet-50/50 dark:bg-violet-950/40' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-md grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 text-[11px] sm:text-xs font-bold">
                📂
            </div>
            <div class="min-w-0 text-left">
                <div class="text-xs font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['categories']) }}
                </div>
                <p class="text-[8.5px] sm:text-[9px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.categories') }}
                </p>
            </div>
        </a>

        {{-- 2. Brands --}}
        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'brands']) }}"
           role="listitem"
           class="group flex-1 min-w-[110px] lg:min-w-0 shrink-0 lg:shrink bg-white dark:bg-slate-900 px-1.5 sm:px-2 py-1 rounded-lg border shadow-2xs flex items-center justify-center gap-1.5 transition hover:border-sky-300 dark:hover:border-sky-600 {{ $activeTab === 'brands' ? 'border-sky-500 ring-1.5 ring-sky-500/30 bg-sky-50/50 dark:bg-sky-950/40' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-md grid place-items-center bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300 text-[11px] sm:text-xs font-bold">
                🏷️
            </div>
            <div class="min-w-0 text-left">
                <div class="text-xs font-black text-sky-600 dark:text-sky-400 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['brands']) }}
                </div>
                <p class="text-[8.5px] sm:text-[9px] text-sky-600/80 dark:text-sky-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.brands') }}
                </p>
            </div>
        </a>

        {{-- 3. Specs / Attributes --}}
        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'connectors']) }}"
           role="listitem"
           class="group flex-1 min-w-[110px] lg:min-w-0 shrink-0 lg:shrink bg-white dark:bg-slate-900 px-1.5 sm:px-2 py-1 rounded-lg border shadow-2xs flex items-center justify-center gap-1.5 transition hover:border-indigo-300 dark:hover:border-indigo-600 {{ $activeTab === 'connectors' ? 'border-indigo-500 ring-1.5 ring-indigo-500/30 bg-indigo-50/50 dark:bg-indigo-950/40' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-md grid place-items-center bg-indigo-100 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-300 text-[11px] sm:text-xs font-bold">
                ⚙️
            </div>
            <div class="min-w-0 text-left">
                <div class="text-xs font-black text-indigo-600 dark:text-indigo-400 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['connectors'] ?? 0) }}
                </div>
                <p class="text-[8.5px] sm:text-[9px] text-indigo-600/80 dark:text-indigo-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.master_data_tab_connectors') }}
                </p>
            </div>
        </a>

        {{-- 4. Colors --}}
        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'colors']) }}"
           role="listitem"
           class="group flex-1 min-w-[110px] lg:min-w-0 shrink-0 lg:shrink bg-white dark:bg-slate-900 px-1.5 sm:px-2 py-1 rounded-lg border shadow-2xs flex items-center justify-center gap-1.5 transition hover:border-pink-300 dark:hover:border-pink-600 {{ $activeTab === 'colors' ? 'border-pink-500 ring-1.5 ring-pink-500/30 bg-pink-50/50 dark:bg-pink-950/40' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-md grid place-items-center bg-pink-100 text-pink-600 dark:bg-pink-950/70 dark:text-pink-300 text-[11px] sm:text-xs font-bold">
                🎨
            </div>
            <div class="min-w-0 text-left">
                <div class="text-xs font-black text-pink-600 dark:text-pink-400 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['colors'] ?? 0) }}
                </div>
                <p class="text-[8.5px] sm:text-[9px] text-pink-600/80 dark:text-pink-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.master_data_tab_colors') }}
                </p>
            </div>
        </a>

        {{-- 5. Shelves / Locations --}}
        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'shelves']) }}"
           role="listitem"
           class="group flex-1 min-w-[110px] lg:min-w-0 shrink-0 lg:shrink bg-white dark:bg-slate-900 px-1.5 sm:px-2 py-1 rounded-lg border shadow-2xs flex items-center justify-center gap-1.5 transition hover:border-teal-300 dark:hover:border-teal-600 {{ $activeTab === 'shelves' ? 'border-teal-500 ring-1.5 ring-teal-500/30 bg-teal-50/50 dark:bg-teal-950/40' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-md grid place-items-center bg-teal-100 text-teal-600 dark:bg-teal-950/70 dark:text-teal-300 text-[11px] sm:text-xs font-bold">
                🗄️
            </div>
            <div class="min-w-0 text-left">
                <div class="text-xs font-black text-teal-600 dark:text-teal-400 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['shelves'] ?? 0) }}
                </div>
                <p class="text-[8.5px] sm:text-[9px] text-teal-600/80 dark:text-teal-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.master_data_tab_shelves') }}
                </p>
            </div>
        </a>

        {{-- 6. Warranties --}}
        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'warranties']) }}"
           role="listitem"
           class="group flex-1 min-w-[110px] lg:min-w-0 shrink-0 lg:shrink bg-white dark:bg-slate-900 px-1.5 sm:px-2 py-1 rounded-lg border shadow-2xs flex items-center justify-center gap-1.5 transition hover:border-blue-300 dark:hover:border-blue-600 {{ $activeTab === 'warranties' ? 'border-blue-500 ring-1.5 ring-blue-500/30 bg-blue-50/50 dark:bg-blue-950/40' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-md grid place-items-center bg-blue-100 text-blue-600 dark:bg-blue-950/70 dark:text-blue-300 text-[11px] sm:text-xs font-bold">
                🛡️
            </div>
            <div class="min-w-0 text-left">
                <div class="text-xs font-black text-blue-600 dark:text-blue-400 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['warranties'] ?? 0) }}
                </div>
                <p class="text-[8.5px] sm:text-[9px] text-blue-600/80 dark:text-blue-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.master_data_tab_warranties') }}
                </p>
            </div>
        </a>

        {{-- 7. Return Policies --}}
        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'return-policies']) }}"
           role="listitem"
           class="group flex-1 min-w-[110px] lg:min-w-0 shrink-0 lg:shrink bg-white dark:bg-slate-900 px-1.5 sm:px-2 py-1 rounded-lg border shadow-2xs flex items-center justify-center gap-1.5 transition hover:border-rose-300 dark:hover:border-rose-600 {{ $activeTab === 'return-policies' ? 'border-rose-500 ring-1.5 ring-rose-500/30 bg-rose-50/50 dark:bg-rose-950/40' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-md grid place-items-center bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300 text-[11px] sm:text-xs font-bold">
                🔄
            </div>
            <div class="min-w-0 text-left">
                <div class="text-xs font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['return_policies'] ?? 0) }}
                </div>
                <p class="text-[8.5px] sm:text-[9px] text-rose-600/80 dark:text-rose-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.master_data_tab_return_policies') }}
                </p>
            </div>
        </a>

        {{-- 8. Variant Settings --}}
        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'variant-presets']) }}"
           role="listitem"
           class="group flex-1 min-w-[110px] lg:min-w-0 shrink-0 lg:shrink bg-white dark:bg-slate-900 px-1.5 sm:px-2 py-1 rounded-lg border shadow-2xs flex items-center justify-center gap-1.5 transition hover:border-amber-300 dark:hover:border-amber-600 {{ $activeTab === 'variant-presets' ? 'border-amber-500 ring-1.5 ring-amber-500/30 bg-amber-50/50 dark:bg-amber-950/40' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-md grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 text-[11px] sm:text-xs font-bold">
                ⚡
            </div>
            <div class="min-w-0 text-left">
                <div class="text-xs font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['presets']) }}
                </div>
                <p class="text-[8.5px] sm:text-[9px] text-amber-600/80 dark:text-amber-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.variant_settings') }}
                </p>
            </div>
        </a>
    </div>

    {{-- ============================================================
         3. HORIZONTAL TAB NAVIGATION BAR (Compact Rhythm)
         ============================================================ --}}
    <nav class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-1 shadow-2xs overflow-x-auto no-scrollbar" aria-label="{{ __('messages.master_data_tabs') }}">
        <ul class="flex items-center gap-0.5 min-w-max" role="tablist">
            @foreach ($tabs as $key => $tab)
                @php $isActive = $activeTab === $key; @endphp
                <li role="presentation" class="shrink-0">
                    <a
                        href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => $key]) }}"
                        role="tab"
                        id="md-tab-btn-{{ $key }}"
                        aria-selected="{{ $isActive ? 'true' : 'false' }}"
                        class="group inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold transition cursor-pointer
                            @if($isActive)
                                bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800 shadow-2xs font-black
                            @else
                                text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800/60
                            @endif
                        "
                    >
                        <span>{{ $tab['icon'] }}</span>
                        <span>{{ $tab['label'] }}</span>
                        <span class="inline-flex items-center justify-center px-1.5 min-w-[1.25rem] h-4 rounded text-[10px] font-mono font-bold {{ $isActive ? 'bg-violet-200 dark:bg-violet-900 text-violet-900 dark:text-violet-200' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }}">
                            {{ $tab['count'] }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- ============================================================
         4. ACTIVE TAB CONTENT
         ============================================================ --}}
    <section aria-labelledby="md-tab-{{ $activeTab }}" class="w-full">
        @if ($activeTab === 'brands')
            @include('admin.brands._content', ['embedded' => true])
        @elseif ($activeTab === 'variant-presets')
            @include('admin.variant_presets._content', ['embedded' => true])
        @elseif (in_array($activeTab, ['connectors', 'colors', 'shelves', 'warranties', 'return-policies'], true))
            @include('admin.master_data._preset_content', ['embedded' => true])
        @elseif ($activeTab === 'seed-data')
            @include('admin.master_data._seed_data', ['embedded' => true])
        @else
            @include('admin.categories._content', ['embedded' => true])
        @endif
    </section>

</div>
@endsection
