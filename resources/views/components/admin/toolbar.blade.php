@props([
    'search' => '',
    'searchPlaceholder' => 'Search records...',
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
    // Compute active filters for pill display
    $activeFilters = [];
    foreach ($filters as $filterKey => $filterConfig) {
        $currentValue = request($filterKey);
        if ($currentValue !== null && $currentValue !== '' && isset($filterConfig['options'][$currentValue])) {
            $activeFilters[$filterKey] = [
                'label' => $filterConfig['label'],
                'value' => $filterConfig['options'][$currentValue],
            ];
        }
    }

    $hasActiveSearch = trim((string) request('search', $search)) !== '';
    $hasActiveSort = request('sort', $sort) !== 'newest' && request('sort') !== null;
    $totalActiveFilters = count($activeFilters) + ($hasActiveSearch ? 1 : 0);
    $hasAnyActive = $totalActiveFilters > 0 || $hasActiveSort;

    $baseQueryStringParams = request()->except(['page']);

    // Pagination helpers (when a LengthAwarePaginator is passed). Pages that
    // render their own pagination (e.g. {{ $users->links() }}) can suppress the
    // per-page selector with an explicit showPagination="false" override.
    $showPerPageSelector = $paginator !== null && $showPagination;
    if ($showPerPageSelector) {
        $paginated = $paginator->appends(request()->except('page'));
        // Resolve current per-page for the selector (maps huge "all" value back to 'all')
        $currentPerPage = request('per_page');
        if ($currentPerPage === null) {
            $currentPerPage = $paginator->perPage() >= 100000 ? 'all' : $paginator->perPage();
        }
    }
@endphp

<div x-data="{
    showAdvanced: {{ $advancedSearch || count($activeFilters) > 0 ? 'true' : 'false' }},
    currentView: localStorage.getItem('admin_view_mode') || '{{ $viewMode }}',
    searchOpen: {{ $hasActiveSearch ? 'true' : 'false' }},
    searching: false,
    // Debounced live search (opt-in via the liveSearch prop): submits the
    // existing GET form after a pause, preserving every other query param.
    liveSearchSubmit(form) {
        if (!{{ $liveSearch ? 'true' : 'false' }}) return;
        this.searching = true;
        form.submit();
    }
}" class="admin-panel p-2.5 sm:p-3 mb-5 sm:mb-6">

    {{-- Single Row: all controls in ONE horizontal scroll row (mobile + desktop) --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 -mx-1 px-1 scrollbar-thin">

        {{-- ===== SEARCH ===== --}}
        {{-- Mobile: icon button that toggles an overlay search field --}}
        <form method="GET" class="flex items-center gap-2 shrink-0">
            @foreach (request()->except(['search', 'page']) as $key => $val)
                @if (is_array($val))
                    @foreach ($val as $subVal)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}" />
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}" />
                @endif
            @endforeach

            {{-- Desktop: inline search field (always visible) --}}
            <div class="hidden sm:relative sm:flex items-center sm:w-56 lg:w-64" x-ref="searchFormDesktop">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search', $search) }}"
                    placeholder="{{ $searchPlaceholder }}"
                    @input.debounce.450ms="liveSearchSubmit($refs.searchFormDesktop)"
                    class="w-full pl-9 pr-9 py-2 border dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors"
                />
                <svg class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <svg x-show="searching" x-cloak class="w-4 h-4 text-violet-500 absolute right-3 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                @if ($hasActiveSearch)
                    <a href="{{ request()->url() . '?' . http_build_query(request()->except(['search', 'page'])) }}"
                       x-show="!searching"
                       class="absolute right-2 w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 rounded-full hover:bg-gray-100 dark:hover:bg-slate-700 transition"
                       title="Clear search" aria-label="Clear search">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>

            {{-- Mobile: compact search icon button --}}
            <button type="button" @click="searchOpen = !searchOpen"
                class="sm:hidden shrink-0 w-11 h-11 flex items-center justify-center rounded-lg border dark:border-slate-600 bg-white dark:bg-slate-900 text-gray-500 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition {{ $hasActiveSearch ? 'ring-2 ring-violet-500 text-violet-600 dark:text-violet-400' : '' }}"
                :class="searchOpen ? 'ring-2 ring-violet-500 text-violet-600 dark:text-violet-400' : ''"
                aria-label="Toggle search">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                @if ($hasActiveSearch)
                    <span class="absolute -top-1 -right-1 w-2 h-2 bg-violet-500 rounded-full"></span>
                @endif
            </button>
        </form>

        {{-- Divider --}}
        <span class="hidden sm:inline-block w-px h-6 bg-gray-200 dark:bg-slate-700 shrink-0"></span>

        {{-- ===== FILTERS ===== --}}
        @if (count($filters) > 0)
            <button type="button" @click="showAdvanced = !showAdvanced"
                class="relative shrink-0 min-h-11 px-3 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 text-sm font-medium flex items-center gap-1.5 whitespace-nowrap transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span>Filters</span>
                @if (count($activeFilters) > 0)
                    <span class="absolute -top-1.5 -right-1.5 min-w-[20px] h-[20px] px-1 flex items-center justify-center text-xs font-bold bg-violet-600 text-white rounded-full shadow">
                        {{ count($activeFilters) }}
                    </span>
                @endif
                <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="showAdvanced ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        @endif

        {{-- ===== SORT (hidden on mobile; shown below Filters) — only when options exist ===== --}}
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
            <div class="relative">
                <svg class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                <select name="sort" data-auto-submit
                    class="border dark:border-slate-600 rounded-lg pl-8 pr-8 min-h-11 py-2 text-sm bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-violet-500 cursor-pointer appearance-none">
                    @foreach ($sortOptions as $key => $label)
                        <option value="{{ $key }}" {{ request('sort', $sort) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <svg class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </form>
        @endif

        {{-- ===== VIEW MODE TOGGLE ===== --}}
        @if ($showViewToggle)
            <div class="flex items-center bg-gray-100 dark:bg-slate-900 p-1 rounded-lg border dark:border-slate-700 shrink-0">
                <button type="button" @click="$dispatch('view-changed', 'table'); currentView = 'table'; localStorage.setItem('admin_view_mode', 'table')" :class="currentView === 'table' ? 'bg-white dark:bg-slate-700 shadow text-violet-600 dark:text-violet-400' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-100'"
                    class="px-2.5 py-1.5 rounded transition flex items-center justify-center" title="Table view" aria-label="Table view">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                </button>
                <button type="button" @click="$dispatch('view-changed', 'card'); currentView = 'card'; localStorage.setItem('admin_view_mode', 'card')" :class="currentView === 'card' ? 'bg-white dark:bg-slate-700 shadow text-violet-600 dark:text-violet-400' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-100'"
                    class="px-2.5 py-1.5 rounded transition flex items-center justify-center" title="Card view" aria-label="Card view">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z" />
                    </svg>
                </button>
            </div>
        @endif

        {{-- ===== BULK ACTIONS (scrolls page's bulk bar into view) ===== --}}
        @if ($bulkActions)
            <button type="button" @click="$dispatch('bulk-actions-request')"
                class="relative shrink-0 min-h-11 px-3 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 text-sm font-medium flex items-center gap-1.5 whitespace-nowrap transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span>Bulk Actions</span>
            </button>
        @endif

        {{-- ===== IMPORT/EXPORT ===== --}}
        @if ($showExportImport)
            @if ($importUrl)
                <a href="{{ $importUrl }}" class="shrink-0 min-h-11 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium shadow flex items-center gap-1.5 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                    </svg>
                    <span>Import</span>
                </a>
            @endif
            @if ($exportUrl)
                <a href="{{ $exportUrl }}" class="shrink-0 min-h-11 px-3 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 text-sm font-medium shadow flex items-center gap-1.5 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 14l5-5 5 5M12 3v12" />
                    </svg>
                    <span>Export</span>
                </a>
            @endif
        @endif

        {{-- ===== ITEMS PER PAGE SELECTOR (page numbers stay at bottom of page) ===== --}}
        @if ($showPerPageSelector)
            <form method="GET" class="shrink-0 flex items-center gap-1 bg-gray-100 dark:bg-slate-900 rounded-lg border dark:border-slate-700 p-1">
                @foreach (request()->except(['per_page', 'page']) as $key => $val)
                    @if (is_array($val))
                        @foreach ($val as $subVal)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}" />
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $val }}" />
                    @endif
                @endforeach
                <select name="per_page" data-auto-submit title="Items per page"
                    class="border dark:border-slate-600 rounded-md px-1.5 min-h-9 py-1.5 text-xs bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-violet-500 cursor-pointer">
                    @foreach ($perPageOptions as $val => $label)
                        <option value="{{ $val }}" {{ (string) $currentPerPage === (string) $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <span class="text-xs text-gray-400 dark:text-slate-500 ml-0.5 hidden sm:inline">/ pg</span>
            </form>
        @endif

        {{-- ===== RESULT COUNT (pushed to right) ===== --}}
        @if ($totalCount !== null)
            <span class="shrink-0 ml-auto inline-flex items-center px-2.5 py-1 rounded-md bg-gray-100 dark:bg-slate-700 text-xs font-semibold text-gray-600 dark:text-slate-300 whitespace-nowrap">
                {{ number_format($totalCount) }} {{ str()->plural('result', (int) $totalCount) }}
            </span>
        @endif
    </div>

    {{-- Mobile-only: expanding search overlay (when search icon is tapped) --}}            <div x-show="searchOpen" x-transition x-cloak class="sm:hidden mt-2 pt-2 border-t border-gray-100 dark:border-slate-700">
        <form method="GET" class="flex items-center gap-2" x-ref="searchFormMobile">
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
                    placeholder="{{ $searchPlaceholder }}"
                    x-ref="mobileSearch"
                    x-init="searchOpen && $nextTick(() => $refs.mobileSearch.focus())"
                    @input.debounce.450ms="liveSearchSubmit($refs.searchFormMobile)"
                    class="w-full pl-9 pr-9 py-2.5 border dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                />
                <svg class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3 top-3 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                @if ($hasActiveSearch)
                    <a href="{{ request()->url() . '?' . http_build_query(request()->except(['search', 'page'])) }}"
                       class="absolute right-2.5 top-2.5 w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 rounded-full hover:bg-gray-100 dark:hover:bg-slate-700 transition"
                       aria-label="Clear search">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>
            <button type="submit" class="shrink-0 min-h-11 px-4 py-2.5 bg-violet-600 text-white rounded-lg text-sm font-semibold shadow active:scale-95 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </form>
    </div>

    {{-- Mobile-only: Clear all button (only when filters/search active) --}}
    @if ($hasAnyActive)
        <div class="sm:hidden mt-2 pt-2 border-t border-gray-100 dark:border-slate-700">
            <a href="{{ request()->url() }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-600 text-xs font-semibold transition">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>Clear all ({{ $totalActiveFilters }})</span>
            </a>
        </div>
    @endif

    {{-- Active Filter Pills (horizontal scroll on mobile, wrap on desktop) --}}
    @if (count($activeFilters) > 0 || $hasActiveSearch)
        <div class="flex flex-nowrap items-center gap-1.5 mt-2 pt-2 border-t border-gray-100 dark:border-slate-700 overflow-x-auto pb-1 -mx-1 px-1 sm:flex-wrap sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0 scrollbar-thin">
            <span class="text-xs font-semibold text-gray-500 dark:text-slate-400 mr-1">Active:</span>
            @if ($hasActiveSearch)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-violet-50 dark:bg-violet-950/60 border border-violet-200 dark:border-violet-800 text-xs font-semibold text-violet-700 dark:text-violet-300">
                    <svg class="w-3 h-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span class="max-w-[120px] truncate">"{{ request('search') }}"</span>
                    <a href="{{ request()->url() . '?' . http_build_query(request()->except(['search', 'page'])) }}" class="hover:text-violet-900 dark:hover:text-white" title="Remove search">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                </span>
            @endif
            @foreach ($activeFilters as $filterKey => $filterInfo)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-violet-50 dark:bg-violet-950/60 border border-violet-200 dark:border-violet-800 text-xs font-semibold text-violet-700 dark:text-violet-300">
                    <svg class="w-3 h-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    <span>{{ $filterInfo['label'] }}:</span>
                    <span>{{ $filterInfo['value'] }}</span>
                    <a href="{{ request()->url() . '?' . http_build_query(request()->except([$filterKey, 'page'])) }}" class="hover:text-violet-900 dark:hover:text-white" title="Remove filter">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                </span>
            @endforeach
            <a href="{{ request()->url() }}" class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-600 text-xs font-semibold ml-1 transition">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>Clear all</span>
            </a>
        </div>
    @endif

    {{-- Advanced Filter Panel (also hosts mobile-only Sort) --}}
    @if (count($filters) > 0 || count($sortOptions) > 0)
        <div x-show="showAdvanced" x-transition class="pt-3 border-t border-gray-100 dark:border-slate-700 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            {{-- Mobile-only: Sort (desktop keeps it in the toolbar row) --}}
            @if (count($sortOptions) > 0)
                <div class="sm:hidden">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Sort</label>
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
                        <select name="sort" class="w-full border dark:border-slate-600 rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                            @foreach ($sortOptions as $key => $label)
                                <option value="{{ $key }}" {{ request('sort', $sort) === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif
            @foreach ($filters as $filterKey => $filterConfig)
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ $filterConfig['label'] }}</label>
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
                        <select name="{{ $filterKey }}" class="w-full border dark:border-slate-600 rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="">All {{ $filterConfig['label'] }}</option>
                            @if (! empty($filterConfig['groups']))
                                {{-- Grouped filter (e.g. Category Main → Sub) rendered as optgroups --}}
                                @foreach ($filterConfig['groups'] as $groupConfig)
                                    <optgroup label="{{ $groupConfig['label'] }}">
                                        @foreach ($groupConfig['options'] as $val => $optLabel)
                                            <option value="{{ $val }}" {{ request($filterKey) == $val ? 'selected' : '' }}>{{ $optLabel }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            @else
                                @foreach ($filterConfig['options'] as $val => $optLabel)
                                    <option value="{{ $val }}" {{ request($filterKey) == $val ? 'selected' : '' }}>{{ $optLabel }}</option>
                                @endforeach
                            @endif
                        </select>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>
