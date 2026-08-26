@props([
    'search' => '',
    'searchPlaceholder' => null,
    'sort' => 'newest',
    'sortOptions' => [
        'newest' => 'Newest',
        'oldest' => 'Oldest',
    ],
    'filters' => [],
    'viewMode' => 'table',
    'showViewToggle' => true,
    'showExportImport' => true,
    'exportUrl' => null,
    'importUrl' => null,
    'advancedSearch' => false,
    'totalCount' => null,
    'perPage' => null,
    'paginator' => null,
    'showPagination' => true,
    'bulkActions' => false,
    'liveSearch' => false,
    'perPageOptions' => [25 => '25', 50 => '50', 100 => '100', 'all' => 'All'],
])

@php
    $placeholder = $searchPlaceholder ?? __('messages.search_placeholder');

    // Compute active filters for pill display
    $activeFilters = [];
    foreach ($filters as $filterKey => $filterConfig) {
        // Date-range filter: active when either bound is set ({key}_from/{key}_to).
        if (($filterConfig['type'] ?? 'select') === 'date' || ($filterConfig['type'] ?? '') === 'date_range') {
            $from = request($filterKey . '_from');
            $to = request($filterKey . '_to');
            if ($from || $to) {
                $activeFilters[$filterKey] = [
                    'label' => $filterConfig['label'],
                    'value' => ($from ?: '…') . ' → ' . ($to ?: '…'),
                    'type' => 'date',
                ];
            }
            continue;
        }

        $currentValue = request($filterKey);
        if ($currentValue !== null && $currentValue !== '') {
            $labelVal = null;
            if (isset($filterConfig['options'][$currentValue])) {
                $labelVal = $filterConfig['options'][$currentValue];
            } elseif (!empty($filterConfig['groups'])) {
                foreach ($filterConfig['groups'] as $group) {
                    if (isset($group['options'][$currentValue])) {
                        $labelVal = $group['options'][$currentValue];
                        break;
                    }
                }
            }
            if ($labelVal !== null) {
                $activeFilters[$filterKey] = [
                    'label' => $filterConfig['label'],
                    'value' => $labelVal,
                    'type' => 'select',
                ];
            }
        }
    }

    $hasActiveSearch = trim((string) request('search', $search)) !== '';
    $hasActiveSort = request('sort', $sort) !== 'newest' && request('sort') !== null;
    $totalActiveFilters = count($activeFilters) + ($hasActiveSearch ? 1 : 0);
    $hasAnyActive = $totalActiveFilters > 0 || $hasActiveSort;

    // Pagination helpers (when a LengthAwarePaginator is passed)
    $showPerPageSelector = $paginator !== null && $showPagination;
    $hasPaginator = $paginator instanceof \Illuminate\Contracts\Pagination\Paginator || $paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    if ($showPerPageSelector) {
        $currentPerPage = request('per_page');
        if ($currentPerPage === null) {
            $currentPerPage = $paginator->perPage() >= 100000 ? 'all' : $paginator->perPage();
        }
    }
    if ($totalCount === null && $hasPaginator && method_exists($paginator, 'total')) {
        $totalCount = $paginator->total();
    }
@endphp

<div x-data="{
    showAdvanced: {{ $advancedSearch || count($activeFilters) > 0 ? 'true' : 'false' }},
    currentView: localStorage.getItem('admin_view_mode') || '{{ $viewMode }}',
    searchOpen: {{ $hasActiveSearch ? 'true' : 'false' }},
    searching: false,
    liveSearchSubmit(form) {
        if (!{{ $liveSearch ? 'true' : 'false' }}) return;
        this.searching = true;
        form.submit();
    }
}" class="rounded-lg sm:rounded-xl bg-white/95 dark:bg-slate-900/95 border border-slate-200/90 dark:border-slate-800 shadow-xs p-2.5 sm:p-3.5 mb-3 sm:mb-5 backdrop-blur-md transition">

    {{-- Main Row: Controls in a smooth, responsive bar --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 pt-0.5 -mx-1 px-1 scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-700">

        {{-- ===== 1. SEARCH INPUT ===== --}}
        <form method="GET" class="flex items-center gap-2 shrink-0" role="search">
            @foreach (request()->except(['search', 'page']) as $key => $val)
                @if (is_array($val))
                    @foreach ($val as $subVal)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}" />
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}" />
                @endif
            @endforeach

            {{-- Desktop / Tablet: inline search field --}}
            <div class="hidden sm:relative sm:flex items-center sm:w-56 md:w-64 lg:w-72" x-ref="searchFormDesktop">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search', $search) }}"
                    placeholder="{{ $placeholder }}"
                    @input.debounce.400ms="liveSearchSubmit($refs.searchFormDesktop)"
                    class="w-full pl-8 pr-8 py-1.5 min-h-[36px] border border-slate-200 dark:border-slate-700 rounded-lg text-xs sm:text-sm bg-slate-50/80 dark:bg-slate-800/80 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-violet-500/40 focus:border-violet-500 transition shadow-2xs"
                />
                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <svg x-show="searching" x-cloak class="w-4 h-4 text-violet-600 absolute right-2.5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                @if ($hasActiveSearch)
                    <a href="{{ request()->url() . '?' . http_build_query(request()->except(['search', 'page'])) }}"
                       x-show="!searching"
                       class="absolute right-2 w-5 h-5 flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-full hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                       title="{{ __('messages.clear') }}" aria-label="{{ __('messages.clear') }}">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>

            {{-- Mobile: compact search icon button --}}
            <button type="button" @click="searchOpen = !searchOpen"
                class="sm:hidden shrink-0 min-h-[36px] min-w-[36px] flex items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition relative shadow-2xs {{ $hasActiveSearch ? 'ring-2 ring-violet-500 text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/60' : '' }}"
                :class="searchOpen ? 'ring-2 ring-violet-500 text-violet-600 dark:text-violet-400' : ''"
                aria-label="{{ __('messages.search') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                @if ($hasActiveSearch)
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-violet-600 rounded-full ring-2 ring-white dark:ring-slate-900 animate-pulse"></span>
                @endif
            </button>
        </form>

        {{-- Divider --}}
        <span class="hidden sm:inline-block w-px h-5 bg-slate-200 dark:bg-slate-700 shrink-0"></span>

        {{-- ===== 2. FILTERS TOGGLE BUTTON ===== --}}
        @if (count($filters) > 0)
            <button type="button" @click="showAdvanced = !showAdvanced"
                class="relative shrink-0 min-h-[36px] px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 whitespace-nowrap transition border shadow-2xs {{ count($activeFilters) > 0 ? 'bg-violet-50 text-violet-700 border-violet-200 dark:bg-violet-950/60 dark:text-violet-300 dark:border-violet-800' : 'bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border-slate-200/80 dark:border-slate-700/80' }}">
                <svg class="w-3.5 h-3.5 {{ count($activeFilters) > 0 ? 'text-violet-600 dark:text-violet-400' : 'text-slate-500 dark:text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span>{{ __('messages.filters') }}</span>
                @if (count($activeFilters) > 0)
                    <span class="min-w-[16px] h-[16px] px-1 flex items-center justify-center text-[10px] font-black bg-violet-600 text-white rounded-full shadow-2xs">
                        {{ count($activeFilters) }}
                    </span>
                @endif
                <svg class="w-3 h-3 text-slate-400 transition-transform duration-200" :class="showAdvanced ? 'rotate-180 text-violet-600 dark:text-violet-400' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        @endif

        {{-- ===== 3. SORT SELECTOR (Desktop/Tablet) ===== --}}
        @if (count($sortOptions) > 0)
        <form method="GET" class="hidden sm:block shrink-0">
            @foreach (request()->except(['sort', 'page']) as $key => $val)
                @if (is_array($val))
                    @foreach ($val as $subVal)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}" />
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}" />
                @endif
            @endforeach
            <div class="relative inline-flex items-center">
                <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 absolute left-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                <select name="sort" data-auto-submit
                    class="border border-slate-200 dark:border-slate-700 rounded-lg pl-8 pr-7 min-h-[36px] py-1 text-xs bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-violet-500/40 cursor-pointer appearance-none shadow-2xs transition">
                    @foreach ($sortOptions as $key => $label)
                        <option value="{{ $key }}" class="bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" {{ request('sort', $sort) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 absolute right-2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </form>
        @endif

        {{-- ===== 4. VIEW MODE TOGGLE (Table vs Cards) ===== --}}
        @if ($showViewToggle)
            <div class="flex items-center bg-slate-100 dark:bg-slate-800/90 p-0.5 rounded-lg border border-slate-200/80 dark:border-slate-700 shrink-0 min-h-[36px]">
                <button type="button"
                    @click="$dispatch('view-changed', 'table'); currentView = 'table'; localStorage.setItem('admin_view_mode', 'table')"
                    :class="currentView === 'table' ? 'bg-white dark:bg-slate-700 shadow-2xs text-violet-600 dark:text-violet-400 font-black' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                    class="px-2 py-1 rounded-md text-xs font-bold transition flex items-center gap-1"
                    title="{{ __('messages.view_table') }}" aria-label="{{ __('messages.view_table') }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <span class="hidden md:inline">{{ __('messages.view_table') }}</span>
                </button>
                <button type="button"
                    @click="$dispatch('view-changed', 'card'); currentView = 'card'; localStorage.setItem('admin_view_mode', 'card')"
                    :class="currentView === 'card' ? 'bg-white dark:bg-slate-700 shadow-2xs text-violet-600 dark:text-violet-400 font-black' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                    class="px-2 py-1 rounded-md text-xs font-bold transition flex items-center gap-1"
                    title="{{ __('messages.view_cards') }}" aria-label="{{ __('messages.view_cards') }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span class="hidden md:inline">{{ __('messages.view_cards') }}</span>
                </button>
            </div>
        @endif

        {{-- ===== 5. BULK ACTIONS BUTTON ===== --}}
        @if ($bulkActions)
            <button type="button" @click="$dispatch('bulk-actions-request')"
                class="relative shrink-0 min-h-[36px] px-3 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-bold flex items-center gap-1.5 whitespace-nowrap transition border border-slate-200/80 dark:border-slate-700 shadow-2xs">
                <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span>{{ __('messages.bulk_actions') }}</span>
            </button>
        @endif

        {{-- ===== 6. IMPORT / EXPORT BUTTONS ===== --}}
        @if ($showExportImport)
            @if ($importUrl)
                <a href="{{ $importUrl }}" class="shrink-0 min-h-[36px] px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow-2xs flex items-center gap-1.5 transition active:scale-95">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                    </svg>
                    <span>{{ __('messages.import') }}</span>
                </a>
            @endif
            @if ($exportUrl)
                @php
                    $xlsxUrl = str_contains($exportUrl, '?') ? $exportUrl . '&format=xlsx' : $exportUrl . '?format=xlsx';
                    $csvUrl = str_contains($exportUrl, '?') ? $exportUrl . '&format=csv' : $exportUrl . '?format=csv';
                @endphp
                <div class="relative shrink-0 inline-flex items-center" x-data="{ exportModalOpen: false }">
                    <div class="inline-flex items-stretch rounded-lg bg-violet-600 text-white shadow-xs overflow-hidden border border-violet-600 min-h-[36px]">
                        {{-- Direct Excel Download --}}
                        <a href="{{ $xlsxUrl }}" download="products.xlsx" title="{{ __('messages.export') }} Excel (.xlsx)"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-white hover:bg-violet-700 text-xs font-bold transition active:scale-95">
                            <svg class="w-3.5 h-3.5 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 14l5-5 5 5M12 3v12" />
                            </svg>
                            <span class="text-white">{{ __('messages.export') }}</span>
                            <span class="text-[10px] bg-white/25 text-white px-1 py-0.2 rounded font-mono uppercase font-black">Excel</span>
                        </a>
                        {{-- Floating Selector Trigger Button --}}
                        <button type="button" @click="exportModalOpen = true"
                                class="inline-flex items-center justify-center w-7 sm:w-8 px-1 bg-violet-700 hover:bg-violet-800 active:bg-violet-900 text-white border-l border-violet-500/80 transition cursor-pointer focus:outline-none"
                                title="Export Formats (Excel / CSV)" aria-label="Export Formats">
                            <svg class="w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>

                    {{-- Floating Action Card / Modal (Teleported to Body for top-most layer) --}}
                    <template x-teleport="body">
                        <div x-show="exportModalOpen" x-cloak
                             style="z-index: 99999;"
                             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm"
                             @click.self="exportModalOpen = false"
                             @keydown.escape.window="exportModalOpen = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95">
                            
                            <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-5 space-y-4 text-left"
                                 @click.stop>
                                {{-- Header --}}
                                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-950/80 text-violet-600 dark:text-violet-400 grid place-items-center text-sm shadow-inner">📤</span>
                                        <div>
                                            <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.export') }}</h3>
                                            <p class="text-[11px] text-slate-400">Select export file format</p>
                                        </div>
                                    </div>
                                    <button type="button" @click="exportModalOpen = false"
                                            class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                {{-- Format Cards --}}
                                <div class="space-y-2.5">
                                    {{-- Excel Option --}}
                                    <a href="{{ $xlsxUrl }}" download @click="exportModalOpen = false"
                                       class="group flex items-center gap-3.5 p-3.5 rounded-xl border border-slate-200/90 dark:border-slate-700/80 hover:border-emerald-500/80 dark:hover:border-emerald-500/80 bg-white dark:bg-slate-800/80 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 transition-all shadow-xs hover:shadow-md active:scale-[0.99]">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 grid place-items-center text-lg font-black shrink-0 shadow-inner group-hover:scale-105 transition-transform">
                                            📊
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-black text-slate-900 dark:text-slate-100 group-hover:text-emerald-700 dark:group-hover:text-emerald-300">Microsoft Excel (.xlsx)</span>
                                                <span class="text-[9px] font-black uppercase px-1.5 py-0.2 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">Recommended</span>
                                            </div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                                                Full styling, column auto-fit, header colors & ready for print/share.
                                            </p>
                                        </div>
                                        <svg class="w-4 h-4 text-slate-400 group-hover:text-emerald-600 shrink-0 group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>

                                    {{-- CSV Option --}}
                                    <a href="{{ $csvUrl }}" download @click="exportModalOpen = false"
                                       class="group flex items-center gap-3.5 p-3.5 rounded-xl border border-slate-200/90 dark:border-slate-700/80 hover:border-sky-500/80 dark:hover:border-sky-500/80 bg-white dark:bg-slate-800/80 hover:bg-sky-50/50 dark:hover:bg-sky-950/20 transition-all shadow-xs hover:shadow-md active:scale-[0.99]">
                                        <div class="w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-950/80 text-sky-600 dark:text-sky-400 grid place-items-center text-lg font-black shrink-0 shadow-inner group-hover:scale-105 transition-transform">
                                            📄
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-black text-slate-900 dark:text-slate-100 group-hover:text-sky-700 dark:group-hover:text-sky-300">CSV Document (.csv)</span>
                                                <span class="text-[9px] font-black uppercase px-1.5 py-0.2 rounded-full bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300">UTF-8</span>
                                            </div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                                                Universal lightweight format for POS import, bulk editing & migration.
                                            </p>
                                        </div>
                                        <svg class="w-4 h-4 text-slate-400 group-hover:text-sky-600 shrink-0 group-hover:translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            @endif
        @endif

        {{-- ===== 7. PAGINATION & ITEMS PER PAGE CONTROLS ===== --}}
        <div class="hidden sm:block h-5 w-px bg-slate-200 dark:bg-slate-700/80 mx-0.5 shrink-0 self-center"></div>
        <div class="shrink-0 flex items-center gap-1.5 sm:gap-2">
            {{-- Mini Paginator (Prev, Page selector, Next) --}}
            @if ($hasPaginator && $showPagination && method_exists($paginator, 'hasPages') && $paginator->hasPages())
                @php
                    $currentPage = $paginator->currentPage();
                    $lastPage = method_exists($paginator, 'lastPage') ? $paginator->lastPage() : 1;
                    $onFirst = $paginator->onFirstPage();
                    $hasMore = $paginator->hasMorePages();
                    $prevUrl = $paginator->previousPageUrl();
                    $nextUrl = $paginator->nextPageUrl();
                @endphp
                <div class="inline-flex items-center rounded-lg bg-slate-100 dark:bg-slate-800/90 border border-slate-200/90 dark:border-slate-700 p-0.5 shadow-2xs min-h-[36px]">
                    {{-- Previous Page --}}
                    @if ($onFirst)
                        <span class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-slate-300 dark:text-slate-600 cursor-not-allowed select-none rounded-md" aria-disabled="true">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        </span>
                    @else
                        <a href="{{ $prevUrl }}" class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-slate-700 hover:text-violet-600 dark:text-slate-200 dark:hover:text-violet-400 hover:bg-white dark:hover:bg-slate-700 rounded-md transition shadow-2xs" title="{{ __('messages.previous') }}">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        </a>
                    @endif

                    {{-- Page selector dropdown / indicator --}}
                    <div class="relative inline-flex items-center px-1">
                        @if ($lastPage <= 25)
                            <form method="GET" class="inline" data-auto-submit>
                                @foreach (request()->except(['page']) as $key => $val)
                                    @if (is_array($val))
                                        @foreach ($val as $subVal)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}" />
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $key }}" value="{{ $val }}" />
                                    @endif
                                @endforeach
                                <div class="relative inline-flex items-center">
                                    <select name="page" data-auto-submit title="{{ __('messages.page') }}"
                                            class="appearance-none bg-transparent pl-1 pr-4 py-0.5 text-xs font-mono font-black text-slate-800 dark:text-slate-200 cursor-pointer focus:outline-none text-center">
                                        @for ($p = 1; $p <= $lastPage; $p++)
                                            <option value="{{ $p }}" class="bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" {{ $p === $currentPage ? 'selected' : '' }}>
                                                {{ $p }} / {{ $lastPage }}
                                            </option>
                                        @endfor
                                    </select>
                                    <svg class="w-2.5 h-2.5 text-slate-400 pointer-events-none absolute right-0.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </form>
                        @else
                            <span class="text-xs font-mono font-black text-slate-700 dark:text-slate-200 px-1 select-none whitespace-nowrap">
                                {{ $currentPage }} / {{ $lastPage }}
                            </span>
                        @endif
                    </div>

                    {{-- Next Page --}}
                    @if ($hasMore)
                        <a href="{{ $nextUrl }}" class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-slate-700 hover:text-violet-600 dark:text-slate-200 dark:hover:text-violet-400 hover:bg-white dark:hover:bg-slate-700 rounded-md transition shadow-2xs" title="{{ __('messages.next') }}">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    @else
                        <span class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-slate-300 dark:text-slate-600 cursor-not-allowed select-none rounded-md" aria-disabled="true">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </span>
                    @endif
                </div>
            @endif

            {{-- Items Per Page Selector --}}
            @if ($showPerPageSelector)
                <form method="GET" class="shrink-0 inline-flex items-center" data-auto-submit>
                    @foreach (request()->except(['per_page', 'page']) as $key => $val)
                        @if (is_array($val))
                            @foreach ($val as $subVal)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}" />
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $val }}" />
                        @endif
                    @endforeach
                    <div class="relative inline-flex items-center">
                        <select name="per_page" data-auto-submit title="{{ __('messages.items_per_page') }}"
                            class="appearance-none bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700/80 border border-slate-200/90 dark:border-slate-700 rounded-lg pl-2.5 pr-6 py-1.5 min-h-[36px] text-xs font-bold text-slate-700 dark:text-slate-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-violet-500/40 transition shadow-2xs">
                            @foreach ($perPageOptions as $val => $label)
                                <option value="{{ $val }}" class="bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" {{ (string) $currentPerPage === (string) $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 pointer-events-none absolute right-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </form>
            @endif

            {{-- Result Count / Range Badge --}}
            @if ($hasPaginator && method_exists($paginator, 'total') && $paginator->total() > 0)
                <span class="hidden md:inline-flex items-center min-h-[36px] px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700 text-xs font-black text-slate-600 dark:text-slate-300 font-mono whitespace-nowrap shadow-inner">
                    @if ($paginator->firstItem() && $paginator->lastItem())
                        {{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }} / {{ number_format($paginator->total()) }}
                    @else
                        {{ number_format($paginator->total()) }} {{ __('messages.items') ?? 'items' }}
                    @endif
                </span>
            @elseif ($totalCount !== null)
                <span class="hidden md:inline-flex items-center min-h-[36px] px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700 text-xs font-black text-slate-600 dark:text-slate-300 font-mono whitespace-nowrap shadow-inner">
                    {{ number_format((int) $totalCount) }} {{ __('messages.items') ?? 'items' }}
                </span>
            @endif
        </div>
    </div>

    {{-- Mobile-only: expanding search bar --}}
    <div x-show="searchOpen" x-transition x-cloak class="sm:hidden mt-2.5 pt-2.5 border-t border-slate-100 dark:border-slate-800">
        <form method="GET" class="flex items-center gap-2" x-ref="searchFormMobile" role="search">
            @foreach (request()->except(['search', 'page']) as $key => $val)
                @if (is_array($val))
                    @foreach ($val as $subVal)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}" />
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}" />
                @endif
            @endforeach
            <div class="relative flex-1">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search', $search) }}"
                    placeholder="{{ $placeholder }}"
                    x-ref="mobileSearch"
                    x-init="searchOpen && $nextTick(() => $refs.mobileSearch.focus())"
                    @input.debounce.400ms="liveSearchSubmit($refs.searchFormMobile)"
                    class="w-full pl-9 pr-9 py-2 min-h-[40px] border border-slate-200 dark:border-slate-700 rounded-2xl text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-violet-500 shadow-inner"
                />
                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                @if ($hasActiveSearch)
                    <a href="{{ request()->url() . '?' . http_build_query(request()->except(['search', 'page'])) }}"
                       class="absolute right-2.5 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-full hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                       aria-label="{{ __('messages.clear') }}">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>
            <button type="submit" class="shrink-0 min-h-[40px] px-4 py-2 bg-violet-600 hover:bg-violet-500 text-white rounded-2xl text-xs font-black shadow-xs active:scale-95 transition flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </form>
    </div>

    {{-- Active Filter Pills (horizontal scroll on mobile, wraps neatly on desktop) --}}
    @if (count($activeFilters) > 0 || $hasActiveSearch)
        <div class="flex flex-nowrap items-center gap-1.5 mt-2.5 pt-2.5 border-t border-slate-100 dark:border-slate-800 overflow-x-auto pb-1 -mx-1 px-1 sm:flex-wrap sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0 scrollbar-thin">
            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 mr-1 flex items-center gap-1 shrink-0">
                <span>🔎</span>
                <span>{{ __('messages.active_filters') }}:</span>
            </span>
            @if ($hasActiveSearch)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-violet-50 dark:bg-violet-950/60 border border-violet-200 dark:border-violet-800 text-xs font-black text-violet-700 dark:text-violet-300 shadow-2xs shrink-0">
                    <span class="max-w-[140px] truncate">"{{ request('search') }}"</span>
                    <a href="{{ request()->url() . '?' . http_build_query(request()->except(['search', 'page'])) }}" class="hover:text-violet-900 dark:hover:text-white ml-0.5" title="{{ __('messages.clear') }}">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                </span>
            @endif
            @foreach ($activeFilters as $filterKey => $filterInfo)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 shadow-2xs shrink-0">
                    <span class="text-slate-400 dark:text-slate-500">{{ $filterInfo['label'] }}:</span>
                    <span class="font-black text-slate-900 dark:text-slate-100">{{ $filterInfo['value'] }}</span>
                    @php
                        $pillExcept = ($filterInfo['type'] ?? 'select') === 'date'
                            ? [$filterKey . '_from', $filterKey . '_to', 'page']
                            : [$filterKey, 'page'];
                    @endphp
                    <a href="{{ request()->url() . '?' . http_build_query(request()->except($pillExcept)) }}" class="hover:text-rose-600 dark:hover:text-rose-400 ml-0.5 transition" title="{{ __('messages.clear') }}">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                </span>
            @endforeach
            <a href="{{ request()->url() }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-slate-200/80 hover:bg-slate-300 dark:bg-slate-700/80 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold ml-1 transition shadow-2xs shrink-0">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>{{ __('messages.clear_all') }} ({{ $totalActiveFilters }})</span>
            </a>
        </div>
    @endif

    {{-- Advanced Filter Drawer (Grid layout with smooth slide-down) --}}
    @if (count($filters) > 0 || count($sortOptions) > 0)
        <div x-show="showAdvanced" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="pt-3.5 mt-3 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 p-3.5 sm:p-4 rounded-lg sm:rounded-xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80">
            {{-- Mobile-only: Sort --}}
            @if (count($sortOptions) > 0)
                <div class="sm:hidden">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5 flex items-center gap-1">
                        <span>⇅</span>
                        <span>{{ __('messages.sort_by') }}</span>
                    </label>
                    <form method="GET" data-auto-submit>
                        @foreach (request()->except(['sort', 'page']) as $key => $val)
                            @if (is_array($val))
                                @foreach ($val as $subVal)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}" />
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $val }}" />
                            @endif
                        @endforeach
                        <select name="sort" class="w-full border border-slate-200 dark:border-slate-700 rounded-2xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-violet-500 shadow-xs">
                            @foreach ($sortOptions as $key => $label)
                                <option value="{{ $key }}" class="bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" {{ request('sort', $sort) === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif

            @foreach ($filters as $filterKey => $filterConfig)
                @if (in_array(($filterConfig['type'] ?? 'select'), ['date', 'date_range'], true))
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5 flex items-center gap-1">
                            <span>📅</span>
                            <span>{{ $filterConfig['label'] }}</span>
                        </label>
                        <form method="GET" data-auto-submit class="flex items-center gap-1.5">
                            @foreach (request()->except([$filterKey . '_from', $filterKey . '_to', 'page']) as $key => $val)
                                @if (is_array($val))
                                    @foreach ($val as $subVal)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}" />
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $val }}" />
                                @endif
                            @endforeach
                            <input type="date" name="{{ $filterKey }}_from" value="{{ request($filterKey . '_from') }}"
                                   class="flex-1 min-w-0 border border-slate-200 dark:border-slate-700 rounded-2xl px-2.5 py-1.5 text-xs bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-medium focus:outline-none focus:ring-2 focus:ring-violet-500 shadow-xs [color-scheme:light] dark:[color-scheme:dark]" />
                            <span class="text-xs text-slate-400 dark:text-slate-500 shrink-0 font-bold">→</span>
                            <input type="date" name="{{ $filterKey }}_to" value="{{ request($filterKey . '_to') }}"
                                   class="flex-1 min-w-0 border border-slate-200 dark:border-slate-700 rounded-2xl px-2.5 py-1.5 text-xs bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-medium focus:outline-none focus:ring-2 focus:ring-violet-500 shadow-xs [color-scheme:light] dark:[color-scheme:dark]" />
                        </form>
                    </div>
                @else
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5 flex items-center gap-1">
                        <span>⚙️</span>
                        <span>{{ $filterConfig['label'] }}</span>
                    </label>
                    <form method="GET" data-auto-submit>
                        @foreach (request()->except([$filterKey, 'page']) as $key => $val)
                            @if (is_array($val))
                                @foreach ($val as $subVal)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}" />
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $val }}" />
                            @endif
                        @endforeach
                        <select name="{{ $filterKey }}" class="w-full border border-slate-200 dark:border-slate-700 rounded-2xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-violet-500 shadow-xs">
                            <option value="" class="bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100">{{ __('messages.all') }} {{ $filterConfig['label'] }}</option>
                            @if (! empty($filterConfig['groups']))
                                @foreach ($filterConfig['groups'] as $groupConfig)
                                    <optgroup label="{{ $groupConfig['label'] }}" class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold">
                                        @foreach (($groupConfig['options'] ?? []) as $val => $optLabel)
                                            <option value="{{ $val }}" class="bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-normal" {{ request($filterKey) == $val ? 'selected' : '' }}>{{ $optLabel }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            @else
                                @foreach (($filterConfig['options'] ?? []) as $val => $optLabel)
                                    <option value="{{ $val }}" class="bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" {{ request($filterKey) == $val ? 'selected' : '' }}>{{ $optLabel }}</option>
                                @endforeach
                            @endif
                        </select>
                    </form>
                </div>
                @endif
            @endforeach
        </div>
    @endif
</div>

