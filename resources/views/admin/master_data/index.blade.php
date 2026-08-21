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
            'products'   => 'bg-violet-100 text-violet-600 dark:bg-violet-950/60 dark:text-violet-400',
            'categories' => 'bg-orange-100 text-orange-600 dark:bg-orange-950/60 dark:text-orange-400',
            'brands'     => 'bg-blue-100 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400',
            'presets'    => 'bg-teal-100 text-teal-600 dark:bg-teal-950/60 dark:text-teal-400',
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
         SUMMARY STAT CARDS  (4-up — grid, mobile 2 × 2, desktop 1 × 4)
         Inspired by the Suppliers / PO list pages — consistent visual
         language across the admin suite.  Cards are clickable and
         jump to their corresponding tab (Products jumps to product list).
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3" role="list" aria-label="{{ __('messages.master_data_summary') }}">
        <a href="{{ url('/store/' . $store->slug . '/admin/products') }}" role="listitem"
           class="group bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3 transition hover:shadow-md hover:border-violet-200 dark:hover:border-violet-800/60 active:scale-[.99]">
            <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl grid place-items-center text-base sm:text-lg {{ $statAccentClasses['products'] }}">
                <svg class="w-[18px] h-[18px] sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums">
                    {{ number_format($summary['products']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-semibold uppercase tracking-wide">
                    {{ __('messages.products') }}
                </p>
            </div>
        </a>

        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'categories']) }}" role="listitem"
           class="group bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3 transition hover:shadow-md hover:border-orange-200 dark:hover:border-orange-800/60 active:scale-[.99]">
            <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl grid place-items-center {{ $statAccentClasses['categories'] }}">
                <svg class="w-[18px] h-[18px] sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h7l2 2h9v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6Z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums">
                    {{ number_format($summary['categories']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-semibold uppercase tracking-wide">
                    {{ __('messages.categories') }}
                </p>
            </div>
        </a>

        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'brands']) }}" role="listitem"
           class="group bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3 transition hover:shadow-md hover:border-blue-200 dark:hover:border-blue-800/60 active:scale-[.99]">
            <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl grid place-items-center {{ $statAccentClasses['brands'] }}">
                <svg class="w-[18px] h-[18px] sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13 11 4H4v7l9 9 7-7ZM7.5 7.5h.01" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums">
                    {{ number_format($summary['brands']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-semibold uppercase tracking-wide">
                    {{ __('messages.brands') }}
                </p>
            </div>
        </a>

        <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'variant-presets']) }}" role="listitem"
           class="group bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200/80 dark:border-slate-700 p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3 transition hover:shadow-md hover:border-teal-200 dark:hover:border-teal-800/60 active:scale-[.99]">
            <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl grid place-items-center {{ $statAccentClasses['presets'] }}">
                <svg class="w-[18px] h-[18px] sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h10M4 17h10M18 5v4M18 15v4M14 7h8M14 17h8" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums">
                    {{ number_format($summary['presets']) }}
                    <span class="text-[11px] sm:text-sm font-bold text-slate-400 dark:text-slate-500 ml-1 tabular-nums">
                        / {{ number_format($summary['presets_total_rows']) }}
                    </span>
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-semibold uppercase tracking-wide">
                    {{ __('messages.variant_settings') }}
                </p>
            </div>
        </a>
    </div>

    {{-- ============================================================
         SEGMENTED TAB SWITCHER  (professional POS/SaaS style)
         - Container: soft pill with inner padding
         - Mobile: overflow-x-auto so tabs never wrap (edge-to-edge feel)
         - Active tab: elevated white card with subtle shadow + violet ring
         - Each tab: icon + label + count badge
         - URL driven (?tab=...) — refresh / back / shareable safe
         ============================================================ --}}
    <nav class="bg-slate-100/90 dark:bg-slate-800/80 rounded-2xl p-1" role="tablist" aria-label="{{ __('messages.master_data') }}">
        <ul class="flex items-stretch gap-1 overflow-x-auto pb-0 scrollbar-thin" role="none">
            @foreach ($tabs as $key => $tab)
                @php
                    $isActive = $activeTab === $key;
                    $hrefQueryParams = ['store_slug' => $store->slug, 'tab' => $key];
                    if ($key === 'categories' && request()->filled('search')) {
                        $hrefQueryParams['search'] = request('search');
                    }
                    if ($key === 'brands') {
                        foreach (['search','sort','has_logo','per_page'] as $q) {
                            if (request()->filled($q)) $hrefQueryParams[$q] = request($q);
                        }
                    }
                @endphp
                <li class="flex-1 sm:flex-none min-w-0" role="none">
                    <a
                        href="{{ route('store.admin.products.master-data', $hrefQueryParams) }}"
                        role="tab"
                        @if ($isActive) aria-selected="true" @endif
                        class="group relative w-full inline-flex items-center justify-center sm:justify-start gap-2 sm:gap-2.5 rounded-xl px-2.5 sm:px-4 py-2.5 text-xs sm:text-sm font-black transition min-h-[44px]
                            @if($isActive)
                                bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 shadow-sm ring-1 ring-slate-900/5 dark:ring-white/10
                            @else
                                text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-700/50
                            @endif
                        "
                    >
                        <span class="shrink-0 inline-flex w-7 h-7 sm:w-8 sm:h-8 rounded-lg items-center justify-center {{ $isActive ? $tab['iconBgActive'] : $tab['iconBg'] }} transition">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" aria-hidden="true">
                                <path d="{{ $tab['icon'] }}"/>
                            </svg>
                        </span>
                        <span class="truncate">{{ $tab['label'] }}</span>
                        <span class="shrink-0 inline-flex items-center justify-center min-w-[1.5rem] h-[1.5rem] px-1.5 rounded-full text-[11px] font-black tabular-nums transition
                            @if($isActive)
                                bg-violet-100 dark:bg-violet-950/70 text-violet-700 dark:text-violet-300
                            @else
                                bg-white/70 dark:bg-slate-700/70 text-slate-500 dark:text-slate-400 group-hover:bg-white dark:group-hover:bg-slate-700
                            @endif
                        ">
                            {{ number_format($tab['count']) }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- ============================================================
         ACTIVE TAB CONTENT
         Embeds the SAME _content partial used by the standalone index
         pages, so CRUD / search / filter / sort / pagination behaviour
         stays perfectly in sync between the hub + standalone routes.
         Flashes, toolbars, and Alpine state live inside the partials.
         ============================================================ --}}
    <section aria-labelledby="md-tab-{{ $activeTab }}">
        <h2 id="md-tab-{{ $activeTab }}" class="sr-only">
            {{ $tabs[$activeTab]['label'] ?? __('messages.master_data') }}
        </h2>
        @if ($activeTab === 'brands')
            @include('admin.brands._content', ['embedded' => true])
        @elseif ($activeTab === 'variant-presets')
            @include('admin.variant_presets._content', ['embedded' => true])
        @else
            @include('admin.categories._content', ['embedded' => true])
        @endif
    </section>
</div>
@endsection
