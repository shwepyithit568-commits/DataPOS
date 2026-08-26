@extends('layouts.admin.app')

@section('title', __('messages.master_data') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
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
                'label' => 'Connectors & Specs',
                'count' => $summary['connectors'] ?? 0,
                'icon' => '🔌',
            ],
            'colors' => [
                'label' => 'Color Codes',
                'count' => $summary['colors'] ?? 0,
                'icon' => '🎨',
            ],
            'shelves' => [
                'label' => 'Shelf / Locations',
                'count' => $summary['shelves'] ?? 0,
                'icon' => '🗄️',
            ],
            'warranties' => [
                'label' => 'Warranty Presets',
                'count' => $summary['warranties'] ?? 0,
                'icon' => '🛡️',
            ],
            'return-policies' => [
                'label' => 'Return Policies',
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
    @endphp

    {{-- ============================================================
         1. COMPACT HERO PAGE HEADER (Admin UI Standard)
         ============================================================ --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
        <div class="min-w-0">
            <div class="flex items-center gap-1.5 mb-0.5">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                    <span>⚙️</span>
                    <span>{{ __('messages.master_data') }}</span>
                </span>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ $store->name }}</span>
            </div>
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span>{{ __('messages.master_data') }}</span>
                <span class="text-xs font-mono font-bold text-slate-400">({{ number_format($totalMasterItems) }})</span>
            </h1>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ __('messages.master_data_sub') }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 shrink-0">
            {{-- Tab-specific quick action --}}
            @if ($activeTab === 'categories')
                <button type="button" @click="$dispatch('open-category-create')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition flex items-center gap-1.5 active:scale-95">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>{{ __('messages.category_add_main_title') }}</span>
                </button>
            @elseif ($activeTab === 'brands')
                <button type="button" @click="$dispatch('open-brand-create')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition flex items-center gap-1.5 active:scale-95">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>{{ __('messages.brand_add_title') }}</span>
                </button>
            @elseif ($activeTab === 'variant-presets')
                <button type="button" @click="$dispatch('open-variant-create')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition flex items-center gap-1.5 active:scale-95">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>{{ __('messages.variant_preset_add_title') }}</span>
                </button>
            @elseif (in_array($activeTab, ['connectors', 'colors', 'shelves', 'warranties', 'return-policies'], true))
                <button type="button" @click="$dispatch('open-preset-create')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition flex items-center gap-1.5 active:scale-95">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>+ Add {{ $tabs[$activeTab]['label'] ?? 'Item' }}</span>
                </button>
            @endif

            {{-- Quick Link to Products --}}
            <a href="{{ route('store.admin.products.index', ['store_slug' => $store->slug]) }}"
               class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                <span>📦</span>
                <span>{{ __('messages.products') }}</span>
            </a>

            {{-- Primary Action: Add Product --}}
            <a href="{{ route('store.admin.products.create', ['store_slug' => $store->slug]) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('messages.add_product') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. HORIZONTAL TAB NAVIGATION BAR
         ============================================================ --}}
    <nav class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-1.5 shadow-2xs overflow-x-auto no-scrollbar" aria-label="{{ __('messages.master_data_tabs') }}">
        <ul class="flex items-center gap-1 min-w-max" role="tablist">
            @foreach ($tabs as $key => $tab)
                @php $isActive = $activeTab === $key; @endphp
                <li role="presentation" class="shrink-0">
                    <a
                        href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => $key]) }}"
                        role="tab"
                        id="md-tab-btn-{{ $key }}"
                        aria-selected="{{ $isActive ? 'true' : 'false' }}"
                        class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-bold transition
                            @if($isActive)
                                bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800 shadow-2xs font-black
                            @else
                                text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800/60
                            @endif
                        "
                    >
                        <span>{{ $tab['icon'] }}</span>
                        <span>{{ $tab['label'] }}</span>
                        <span class="inline-flex items-center justify-center px-1.5 min-w-[1.25rem] h-4.5 rounded-md text-[10px] font-mono font-bold {{ $isActive ? 'bg-violet-200 dark:bg-violet-900 text-violet-900 dark:text-violet-200' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }}">
                            {{ $tab['count'] }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- ============================================================
         3. ACTIVE TAB CONTENT
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
