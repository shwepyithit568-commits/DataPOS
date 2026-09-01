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
                'icon' => '🔌',
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
                        class="h-7 px-3 rounded-md text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span>{{ __('messages.category_add_main_title') }}</span>
                </button>
            @elseif ($activeTab === 'brands')
                <button type="button" @click="$dispatch('open-brand-create')"
                        class="h-7 px-3 rounded-md text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span>{{ __('messages.brand_add_title') }}</span>
                </button>
            @elseif ($activeTab === 'variant-presets')
                <button type="button" @click="$dispatch('open-variant-create')"
                        class="h-7 px-3 rounded-md text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span>{{ __('messages.variant_preset_add_title') }}</span>
                </button>
            @elseif (in_array($activeTab, ['connectors', 'colors', 'shelves', 'warranties', 'return-policies'], true))
                <button type="button" @click="$dispatch('open-preset-create')"
                        class="h-7 px-3 rounded-md text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span>+ {{ __('messages.preset_add_item') }}</span>
                </button>
            @endif

            {{-- Quick Link to Products --}}
            <a href="{{ route('store.admin.products.index', ['store_slug' => $store->slug]) }}"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/70 dark:border-slate-700 transition flex items-center gap-1 cursor-pointer">
                <span>📦</span>
                <span>{{ __('messages.products') }}</span>
            </a>

            {{-- Primary Action: Add Product --}}
            <a href="{{ route('store.admin.products.create', ['store_slug' => $store->slug]) }}"
               class="h-7 px-3 rounded-md text-xs font-black bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-white shadow-2xs transition flex items-center gap-1 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                <span>{{ __('messages.add_product') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. KPI STAT CARDS (Row-based Centered Alignment)
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1" role="list" aria-label="Master Data Summary">
        {{-- Categories --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 shadow-inner text-xs sm:text-sm font-bold">
                📂
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['categories']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.categories') }}
                </p>
            </div>
        </div>

        {{-- Brands --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-sky-200/70 dark:border-sky-900/50 shadow-2xs bg-sky-50/20 dark:bg-sky-950/10 flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300 shadow-inner text-xs sm:text-sm font-bold">
                🏷️
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-sky-600 dark:text-sky-400 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['brands']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-sky-600/80 dark:text-sky-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.brands') }}
                </p>
            </div>
        </div>

        {{-- Hardware & Master Presets --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-emerald-200/70 dark:border-emerald-900/50 shadow-2xs bg-emerald-50/20 dark:bg-emerald-950/10 flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                🔌
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit">
                    {{ number_format($totalHardwarePresets) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    Preset စနစ်များ
                </p>
            </div>
        </div>

        {{-- Variant Settings --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-amber-200/70 dark:border-amber-900/50 shadow-2xs bg-amber-50/20 dark:bg-amber-950/10 flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold">
                ⚡
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['presets']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-amber-600/80 dark:text-amber-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.variant_settings') }}
                </p>
            </div>
        </div>
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
        @else
            @include('admin.categories._content', ['embedded' => true])
        @endif
    </section>

</div>
@endsection
