@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-4 sm:space-y-5">
    @php
        $tabs = [
            'categories' => [
                'label' => __('messages.categories'),
                'count' => $summary['categories'],
                'icon' => 'M3 6h7l2 2h9v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6Z',
                'iconBg' => 'bg-orange-100 text-orange-600 dark:bg-orange-950/60 dark:text-orange-400',
                'iconBgActive' => 'bg-orange-100 text-orange-700 dark:bg-orange-950/70 dark:text-orange-300',
            ],
            'brands' => [
                'label' => __('messages.brands'),
                'count' => $summary['brands'],
                'icon' => 'M20 13 11 4H4v7l9 9 7-7ZM7.5 7.5h.01',
                'iconBg' => 'bg-blue-100 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400',
                'iconBgActive' => 'bg-blue-100 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300',
            ],
            'connectors' => [
                'label' => '🔌 Connectors & Specs',
                'count' => $summary['connectors'] ?? 0,
                'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                'iconBg' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400',
                'iconBgActive' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300',
            ],
            'colors' => [
                'label' => '🎨 Color Codes',
                'count' => $summary['colors'] ?? 0,
                'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
                'iconBg' => 'bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400',
                'iconBgActive' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300',
            ],
            'shelves' => [
                'label' => '🗄️ Shelf / Locations',
                'count' => $summary['shelves'] ?? 0,
                'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
                'iconBg' => 'bg-purple-100 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400',
                'iconBgActive' => 'bg-purple-100 text-purple-700 dark:bg-purple-950/70 dark:text-purple-300',
            ],
            'warranties' => [
                'label' => '🛡️ Warranty Presets',
                'count' => $summary['warranties'] ?? 0,
                'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                'iconBg' => 'bg-sky-100 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400',
                'iconBgActive' => 'bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300',
            ],
            'return-policies' => [
                'label' => '🔄 Return Policies',
                'count' => $summary['return_policies'] ?? 0,
                'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
                'iconBg' => 'bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400',
                'iconBgActive' => 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300',
            ],
            'variant-presets' => [
                'label' => __('messages.variant_settings'),
                'count' => $summary['presets'],
                'subCount' => $summary['presets_total_rows'],
                'icon' => 'M4 7h10M4 17h10M18 5v4M18 15v4M14 7h8M14 17h8',
                'iconBg' => 'bg-teal-100 text-teal-600 dark:bg-teal-950/60 dark:text-teal-400',
                'iconBgActive' => 'bg-teal-100 text-teal-700 dark:bg-teal-950/70 dark:text-teal-300',
            ],
        ];

        $statAccentClasses = [
            'products'        => 'bg-violet-100 text-violet-600 dark:bg-violet-950/60 dark:text-violet-400',
            'categories'      => 'bg-orange-100 text-orange-600 dark:bg-orange-950/60 dark:text-orange-400',
            'brands'          => 'bg-blue-100 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400',
            'connectors'      => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400',
            'colors'          => 'bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400',
            'shelves'         => 'bg-purple-100 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400',
            'warranties'      => 'bg-sky-100 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400',
            'return_policies' => 'bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400',
            'presets'         => 'bg-teal-100 text-teal-600 dark:bg-teal-950/60 dark:text-teal-400',
        ];
    @endphp

    {{-- ============================================================
         HERO HEADER — eyebrow, title, subtitle, primary CTA
         ============================================================ --}}
    <header class="admin-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400">
                {{ __('messages.products') }}
            </p>
            <h1 class="admin-page-title mt-0.5">
                {{ __('messages.master_data') }}
            </h1>
            <p class="admin-page-sub mt-1">
                {{ $store->name }} · {{ __('messages.master_data_sub') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a href="{{ url('/store/' . $store->slug . '/admin/products') }}"
               class="admin-secondary-btn">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                <span class="hidden sm:inline">{{ __('messages.products') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}"
               class="admin-primary-btn">
                <span>{{ __('messages.add_product') }}</span>
            </a>
        </div>
    </header>

    {{-- ============================================================
         HORIZONTAL TAB BAR — scrollable on small screens
         ============================================================ --}}
    <nav class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-700 p-1.5 shadow-sm" aria-label="{{ __('messages.master_data_tabs') }}">
        <ul class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1" role="tablist">
            @foreach ($tabs as $key => $tab)
                @php $isActive = $activeTab === $key; @endphp
                <li role="presentation" class="shrink-0">
                    <a
                        href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => $key]) }}"
                        role="tab"
                        id="md-tab-btn-{{ $key }}"
                        aria-selected="{{ $isActive ? 'true' : 'false' }}"
                        aria-controls="md-tab-{{ $key }}"
                        class="group inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-black transition
                            @if($isActive)
                                bg-violet-50 dark:bg-violet-950/50 text-violet-700 dark:text-violet-300 ring-1 ring-violet-200 dark:ring-violet-800/80 shadow-xs
                            @else
                                text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50
                            @endif
                        "
                    >
                        <span class="truncate">{{ $tab['label'] }}</span>
                        <span class="inline-flex items-center justify-center px-1.5 min-w-[1.25rem] h-5 rounded-md text-[10px] font-bold {{ $isActive ? 'bg-violet-200 dark:bg-violet-900 text-violet-900 dark:text-violet-200' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                            {{ $tab['count'] }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- ============================================================
         ACTIVE TAB CONTENT
         ============================================================ --}}
    <section aria-labelledby="md-tab-{{ $activeTab }}">
        <h2 id="md-tab-{{ $activeTab }}" class="sr-only">
            {{ $tabs[$activeTab]['label'] ?? __('messages.master_data') }}
        </h2>
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
