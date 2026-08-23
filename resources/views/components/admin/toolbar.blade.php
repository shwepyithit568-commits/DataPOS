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
        // Date-range filter: active when either bound is set ({key}_from/{key}_to).
        if (($filterConfig['type'] ?? 'select') === 'date') {
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
        if ($currentValue !== null && $currentValue !== '' && isset($filterConfig['options'][$currentValue])) {
            $activeFilters[$filterKey] = [
                'label' => $filterConfig['label'],
                'value' => $filterConfig['options'][$currentValue],
                'type' => 'select',
            ];
        }
    }

    $hasActiveSearch = trim((string) request('search', $search)) !== '';
    $hasActiveSort = request('sort', $sort) !== 'newest' && request('sort') !== null;
    $totalActiveFilters = count($activeFilters) + ($hasActiveSearch ? 1 : 0);
    $hasAnyActive = $totalActiveFilters > 0 || $hasActiveSort;

    $baseQueryStringParams = request()->except(['page']);

    // Pagination helpers (when a LengthAwarePaginator is passed)
    $showPerPageSelector = $paginator !== null && $showPagination;
    if ($showPerPageSelector) {
        $paginated = $paginator->appends(request()->except('page'));
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
    liveSearchSubmit(form) {
        if (!{{ $liveSearch ? 'true' : 'false' }}) return;
        this.searching = true;
        form.submit();
    }
}" class="rounded-2xl sm:rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm p-2.5 sm:p-3.5 mb-5 sm:mb-6 transition">

    {{-- Single Row: all controls in ONE smooth horizontal scroll row (mobile + tablet + desktop) --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 pt-0.5 -mx-1 px-1 scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-700">

        {{-- ===== 1. SEARCH ===== --}}
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
            <div class="hidden sm:relative sm:flex items-center sm:w-60 lg:w-72" x-ref="searchFormDesktop">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search', $search) }}"
                    placeholder="{{ $searchPlaceholder }}"
                    @input.debounce.450ms="liveSearchSubmit($refs.searchFormDesktop)"
                    class="w-full pl-9 pr-9 py-2.5 min-h-[42px] border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/70 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-inner"
                />
                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <svg x-show="searching" x-cloak class="w-4 h-4 text-blue-500 absolute right-3 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                @if ($hasActiveSearch)
                    <a href="{{ request()->url() . '?' . http_build_query(request()->except(['search', 'page'])) }}"
                       x-show="!searching"
                       class="absolute right-2.5 w-6 h-6 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-full hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                       title="Clear search" aria-label="Clear search">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>

            {{-- Mobile: compact search icon button --}}
            <button type="button" @click="searchOpen = !searchOpen"
                class="sm:hidden shrink-0 min-h-[42px] min-w-[42px] flex items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition relative shadow-sm {{ $hasActiveSearch ? 'ring-2 ring-blue-500 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60' : '' }}"
                :class="searchOpen ? 'ring-2 ring-blue-500 text-blue-600 dark:text-blue-400' : ''"
                aria-label="Toggle search">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                @if ($hasActiveSearch)
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-blue-500 rounded-full ring-2 ring-white dark:ring-slate-900 animate-pulse"></span>
                @endif
            </button>
        </form>

        {{-- Divider --}}
        <span class="hidden sm:inline-block w-px h-6 bg-slate-200 dark:bg-slate-700 shrink-0"></span>

        {{-- ===== 2. FILTERS DROPDOWN TOGGLE ===== --}}
        @if (count($filters) > 0)
            <button type="button" @click="showAdvanced = !showAdvanced"
                class="relative shrink-0 min-h-[42px] px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 whitespace-nowrap transition border border-slate-200/80 dark:border-slate-700/80 shadow-sm">
                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span>Filters</span>
                @if (count($activeFilters) > 0)
                    <span class="min-w-[18px] h-[18px] px-1 flex items-center justify-center text-[10px] font-black bg-blue-600 text-white rounded-full shadow">
                        {{ count($activeFilters) }}
                    </span>
                @endif
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="showAdvanced ? 'rotate-180 text-blue-600 dark:text-blue-400' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        @endif

        {{-- ===== 3. SORT SELECTOR (hidden on mobile; available in slide-down) ===== --}}
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
                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                <select name="sort" data-auto-submit
                    class="border border-slate-200 dark:border-slate-700 rounded-xl pl-9 pr-8 min-h-[42px] py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/70 text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer appearance-none shadow-sm transition">
                    @foreach ($sortOptions as $key => $label)
                        <option value="{{ $key }}" {{ request('sort', $sort) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </form>
        @endif

        {{-- ===== 4. VIEW MODE TOGGLE (Table vs Cards) ===== --}}
        @if ($showViewToggle)
            <div class="flex items-center bg-slate-100 dark:bg-slate-800/90 p-1 rounded-xl border border-slate-200/80 dark:border-slate-700 shrink-0 shadow-inner">
                <button type="button"
                    @click="$dispatch('view-changed', 'table'); currentView = 'table'; localStorage.setItem('admin_view_mode', 'table')"
                    :class="currentView === 'table' ? 'bg-white dark:bg-slate-700 shadow-sm text-blue-600 dark:text-blue-400 font-black' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5"
                    title="Table view" aria-label="Table view">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <span class="hidden md:inline">Table</span>
                </button>
                <button type="button"
                    @click="$dispatch('view-changed', 'card'); currentView = 'card'; localStorage.setItem('admin_view_mode', 'card')"
                    :class="currentView === 'card' ? 'bg-white dark:bg-slate-700 shadow-sm text-blue-600 dark:text-blue-400 font-black' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5"
                    title="Card view" aria-label="Card view">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span class="hidden md:inline">Cards</span>
                </button>
            </div>
        @endif

        {{-- ===== 5. BULK ACTIONS BUTTON ===== --}}
        @if ($bulkActions)
            <button type="button" @click="$dispatch('bulk-actions-request')"
                class="relative shrink-0 min-h-[42px] px-3.5 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 text-xs sm:text-sm font-bold flex items-center gap-1.5 whitespace-nowrap transition border border-slate-200/80 dark:border-slate-700 shadow-sm">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span>Bulk Actions</span>
            </button>
        @endif

        {{-- ===== 6. IMPORT / EXPORT BUTTONS ===== --}}
        @if ($showExportImport)
            @if ($importUrl)
                <a href="{{ $importUrl }}" class="shrink-0 min-h-[42px] px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs sm:text-sm font-bold shadow-sm flex items-center gap-1.5 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                    </svg>
                    <span>Import</span>
                </a>
            @endif
            @if ($exportUrl)
                <a href="{{ $exportUrl }}" class="shrink-0 min-h-[42px] px-3.5 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs sm:text-sm font-bold shadow-sm flex items-center gap-1.5 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 14l5-5 5 5M12 3v12" />
                    </svg>
                    <span>Export</span>
                </a>
            @endif
        @endif

        {{-- ===== 7. ITEMS PER PAGE SELECTOR ===== --}}
        @if ($showPerPageSelector)
            <form method="GET" class="shrink-0 flex items-center gap-1 bg-slate-100 dark:bg-slate-800/80 rounded-xl border border-slate-200/80 dark:border-slate-700 p-1 shadow-inner">
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
                    class="border border-slate-200 dark:border-slate-700 rounded-lg px-2 min-h-[32px] py-1 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer shadow-sm">
                    @foreach ($perPageOptions as $val => $label)
                        <option value="{{ $val }}" {{ (string) $currentPerPage === (string) $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 pr-1.5 hidden sm:inline">/ pg</span>
            </form>
        @endif

        {{-- ===== 8. RESULT COUNT ===== --}}
        @if ($totalCount !== null)
            <span class="shrink-0 ml-auto inline-flex items-center px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700 text-xs font-black text-slate-600 dark:text-slate-300 font-mono whitespace-nowrap shadow-inner">
                {{ number_format((int) $totalCount) }} {{ \Illuminate\Support\Str::plural('item', (int) $totalCount) }}
            </span>
        @endif
    </div>

    {{-- Mobile-only: expanding search overlay (when search icon is tapped) --}}
    <div x-show="searchOpen" x-transition x-cloak class="sm:hidden mt-2.5 pt-2.5 border-t border-slate-100 dark:border-slate-800">
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
                    class="w-full pl-9 pr-9 py-2.5 min-h-[42px] border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-inner"
                />
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                @if ($hasActiveSearch)
                    <a href="{{ request()->url() . '?' . http_build_query(request()->except(['search', 'page'])) }}"
                       class="absolute right-2.5 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-full hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                       aria-label="Clear search">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>
            <button type="submit" class="shrink-0 min-h-[42px] px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-black shadow active:scale-95 transition flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </form>
    </div>

    {{-- Active Filter Pills (horizontal scroll on mobile, wrap on desktop) --}}
    @if (count($activeFilters) > 0 || $hasActiveSearch)
        <div class="flex flex-nowrap items-center gap-1.5 mt-2.5 pt-2.5 border-t border-slate-100 dark:border-slate-800 overflow-x-auto pb-1 -mx-1 px-1 sm:flex-wrap sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0 scrollbar-thin">
            <span class="text-xs font-bold text-slate-400 mr-1 flex items-center gap-1">
                <span>🔎</span>
                <span>Active:</span>
            </span>
            @if ($hasActiveSearch)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 text-xs font-black text-blue-700 dark:text-blue-300 shadow-sm">
                    <span class="max-w-[140px] truncate">"{{ request('search') }}"</span>
                    <a href="{{ request()->url() . '?' . http_build_query(request()->except(['search', 'page'])) }}" class="hover:text-blue-900 dark:hover:text-white ml-0.5" title="Remove search">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                </span>
            @endif
            @foreach ($activeFilters as $filterKey => $filterInfo)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 shadow-sm">
                    <span class="text-slate-400">{{ $filterInfo['label'] }}:</span>
                    <span class="font-black">{{ $filterInfo['value'] }}</span>
                    @php
                        $pillExcept = ($filterInfo['type'] ?? 'select') === 'date'
                            ? [$filterKey . '_from', $filterKey . '_to', 'page']
                            : [$filterKey, 'page'];
                    @endphp
                    <a href="{{ request()->url() . '?' . http_build_query(request()->except($pillExcept)) }}" class="hover:text-rose-600 dark:hover:text-rose-400 ml-0.5 transition" title="Remove filter">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                </span>
            @endforeach
            <a href="{{ request()->url() }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-200/80 hover:bg-slate-300 dark:bg-slate-700/80 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold ml-1 transition shadow-sm">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>Clear all ({{ $totalActiveFilters }})</span>
            </a>
        </div>
    @endif

    {{-- Advanced Filter Panel (Slide-down drawer with smooth layout) --}}
    @if (count($filters) > 0 || count($sortOptions) > 0)
        <div x-show="showAdvanced" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="pt-3.5 mt-3 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-800">
            {{-- Mobile-only: Sort --}}
            @if (count($sortOptions) > 0)
                <div class="sm:hidden">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5 flex items-center gap-1">
                        <span>⇅</span>
                        <span>Sort</span>
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
                        <select name="sort" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs sm:text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm">
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
                                   class="flex-1 min-w-0 border border-slate-200 dark:border-slate-700 rounded-xl px-2.5 py-2 text-xs bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm" />
                            <span class="text-xs text-slate-400 shrink-0 font-bold">→</span>
                            <input type="date" name="{{ $filterKey }}_to" value="{{ request($filterKey . '_to') }}"
                                   class="flex-1 min-w-0 border border-slate-200 dark:border-slate-700 rounded-xl px-2.5 py-2 text-xs bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm" />
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
                        <select name="{{ $filterKey }}" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm">
                            <option value="">All {{ $filterConfig['label'] }}</option>
                            @if (! empty($filterConfig['groups']))
                                @foreach ($filterConfig['groups'] as $groupConfig)
                                    <optgroup label="{{ $groupConfig['label'] }}">
                                        @foreach (($groupConfig['options'] ?? []) as $val => $optLabel)
                                            <option value="{{ $val }}" {{ request($filterKey) == $val ? 'selected' : '' }}>{{ $optLabel }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            @else
                                @foreach (($filterConfig['options'] ?? []) as $val => $optLabel)
                                    <option value="{{ $val }}" {{ request($filterKey) == $val ? 'selected' : '' }}>{{ $optLabel }}</option>
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
