{{-- Warehouse Management Content (Admin UI Standard 3.0) --}}
@php
    $search = $search ?? request('search', '');
    $status = $status ?? request('status', '');
    $sort = $sort ?? request('sort', 'name');
    $branchId = $filters['branch_id'] ?? request('branch_id', '');
    $totalCount = $warehouses->count();
@endphp

<div x-data="{
         viewMode: localStorage.getItem('admin_view_mode') || 'table',
         showCreate: false,
         editWh: null,
         deleteWh: null
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)"
     @open-warehouse-create.window="showCreate = true"
     class="w-full space-y-2 sm:space-y-2.5">

    {{-- ============================================================
         1. COMPACT HERO PAGE HEADER (Admin UI Standard)
         ============================================================ --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
        <div class="min-w-0">
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span>🏬 {{ __('messages.warehouses_title') }}</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    {{ number_format($stats['total']) }}
                </span>
            </h1>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap shrink-0">
            {{-- Quick Link: Stock Transfers --}}
            @if(\Illuminate\Support\Facades\Route::has('pos.transfers.index'))
                <a href="{{ route('pos.transfers.index', $storeRouteParams) }}"
                   class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1 shadow-2xs">
                    <span>🔄</span>
                    <span class="hidden sm:inline">{{ __('messages.sidebar_transfers') }}</span>
                </a>
            @endif

            {{-- Quick Link: Stock Count --}}
            @if(\Illuminate\Support\Facades\Route::has('store.admin.stock_count.index'))
                <a href="{{ route('store.admin.stock_count.index', $storeRouteParams) }}"
                   class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1 shadow-2xs">
                    <span>📋</span>
                    <span class="hidden sm:inline">{{ __('messages.stock_count_title') ?? 'Stock Count' }}</span>
                </a>
            @endif

            {{-- Primary Action: Add Warehouse Modal --}}
            <button @click="showCreate = true" type="button"
                    class="px-3 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <span>+</span>
                <span>{{ __('messages.add_warehouse') }}</span>
            </button>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2">
                <span>✅</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1 shadow-2xs">
            @foreach ($errors->all() as $error)
                <p class="flex items-center gap-1.5">
                    <span>⚠️</span>
                    <span>{{ $error }}</span>
                </p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         2. KPI SUMMARY METRIC CARDS (4-Up Click-to-Filter)
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
        {{-- Card 1: Total Warehouses --}}
        <a href="{{ route('store.admin.warehouses.index', array_merge($storeRouteParams, ['status' => ''])) }}"
           class="p-2.5 sm:p-3 rounded-lg border transition duration-150 flex flex-col justify-between shadow-2xs cursor-pointer group {{ $status === '' ? 'bg-violet-50/70 border-violet-300 dark:bg-violet-950/30 dark:border-violet-800 ring-2 ring-violet-500/20' : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    {{ __('messages.warehouse_stat_total') }}
                </span>
                <span class="w-6 h-6 rounded-md bg-violet-100 dark:bg-violet-950 text-violet-600 dark:text-violet-400 grid place-items-center text-xs">
                    🏬
                </span>
            </div>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-base sm:text-lg font-black font-mono text-slate-900 dark:text-slate-100">
                    {{ number_format($stats['total']) }}
                </span>
                <span class="text-[10px] text-slate-400">{{ __('messages.all') }}</span>
            </div>
        </a>

        {{-- Card 2: Active Locations --}}
        <a href="{{ route('store.admin.warehouses.index', array_merge($storeRouteParams, ['status' => 'active'])) }}"
           class="p-2.5 sm:p-3 rounded-lg border transition duration-150 flex flex-col justify-between shadow-2xs cursor-pointer group {{ $status === 'active' || $status === '1' ? 'bg-emerald-50/70 border-emerald-300 dark:bg-emerald-950/30 dark:border-emerald-800 ring-2 ring-emerald-500/20' : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>{{ __('messages.warehouse_stat_active') }}</span>
                </span>
                <span class="w-6 h-6 rounded-md bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xs">
                    ✓
                </span>
            </div>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-base sm:text-lg font-black font-mono text-emerald-600 dark:text-emerald-400">
                    {{ number_format($stats['active']) }}
                </span>
                <span class="text-[10px] text-emerald-600/80 dark:text-emerald-400/80 font-bold">{{ __('messages.active') }}</span>
            </div>
        </a>

        {{-- Card 3: Inactive Locations --}}
        <a href="{{ route('store.admin.warehouses.index', array_merge($storeRouteParams, ['status' => 'inactive'])) }}"
           class="p-2.5 sm:p-3 rounded-lg border transition duration-150 flex flex-col justify-between shadow-2xs cursor-pointer group {{ $status === 'inactive' || $status === '0' ? 'bg-slate-100 border-slate-300 dark:bg-slate-800/80 dark:border-slate-700 ring-2 ring-slate-500/20' : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    {{ __('messages.inactive') }}
                </span>
                <span class="w-6 h-6 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 grid place-items-center text-xs">
                    ⏸
                </span>
            </div>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-base sm:text-lg font-black font-mono text-slate-700 dark:text-slate-300">
                    {{ number_format($stats['inactive']) }}
                </span>
                <span class="text-[10px] text-slate-400">{{ __('messages.inactive') }}</span>
            </div>
        </a>

        {{-- Card 4: Linked Branches & Default Warehouse --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    {{ __('messages.warehouse_stat_branches') }}
                </span>
                <span class="w-6 h-6 rounded-md bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400 grid place-items-center text-xs">
                    🏢
                </span>
            </div>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-base sm:text-lg font-black font-mono text-sky-600 dark:text-sky-400">
                    {{ number_format($stats['branches']) }}
                </span>
                <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 truncate max-w-[120px]" title="Default: {{ $stats['default_warehouse'] }}">
                    ★ {{ $stats['default_warehouse'] }}
                </span>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. MASTER TOOLBAR COMPONENT
         ============================================================ --}}
    <x-admin.toolbar
        :search="$search"
        :search-placeholder="__('messages.search_warehouse_placeholder')"
        :sort="$sort"
        :sort-options="[
            'name' => __('messages.warehouse_sort_name_asc'),
            'name_desc' => __('messages.warehouse_sort_name_desc'),
            'code_asc' => __('messages.warehouse_sort_code_asc'),
            'code_desc' => __('messages.warehouse_sort_code_desc'),
            'products_desc' => __('messages.warehouse_sort_products_desc'),
            'stock_desc' => __('messages.warehouse_sort_stock_desc'),
            'newest' => __('messages.sort_newest') ?? 'Newest',
            'oldest' => __('messages.sort_oldest') ?? 'Oldest',
        ]"
        :filters="[
            [
                'name' => 'branch_id',
                'label' => __('messages.branch'),
                'value' => $branchId,
                'options' => $branches->mapWithKeys(fn($b) => [$b->id => $b->name])->toArray(),
            ],
            [
                'name' => 'status',
                'label' => __('messages.status'),
                'value' => $status,
                'options' => [
                    'active' => __('messages.active'),
                    'inactive' => __('messages.inactive'),
                ],
            ],
        ]"
        :total-count="$totalCount"
        :show-view-toggle="true"
        :show-export-import="false"
        :view-mode="'table'"
        :show-pagination="false"
    >
        <x-slot:status-tabs>
            <div class="flex items-center gap-1 overflow-x-auto">
                <a href="{{ route('store.admin.warehouses.index', array_merge($storeRouteParams, ['status' => '', 'search' => $search, 'branch_id' => $branchId, 'sort' => $sort])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-bold whitespace-nowrap transition {{ $status === '' ? 'bg-violet-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    {{ __('messages.all') }} ({{ $stats['total'] }})
                </a>
                <a href="{{ route('store.admin.warehouses.index', array_merge($storeRouteParams, ['status' => 'active', 'search' => $search, 'branch_id' => $branchId, 'sort' => $sort])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-bold whitespace-nowrap transition {{ $status === 'active' || $status === '1' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    {{ __('messages.active') }} ({{ $stats['active'] }})
                </a>
                <a href="{{ route('store.admin.warehouses.index', array_merge($storeRouteParams, ['status' => 'inactive', 'search' => $search, 'branch_id' => $branchId, 'sort' => $sort])) }}"
                   class="px-2.5 py-1 rounded-md text-xs font-bold whitespace-nowrap transition {{ $status === 'inactive' || $status === '0' ? 'bg-slate-700 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    {{ __('messages.inactive') }} ({{ $stats['inactive'] }})
                </a>
            </div>
        </x-slot:status-tabs>
    </x-admin.toolbar>

    {{-- ============================================================
         4. DATA VIEWS (Spreadsheet Table vs Responsive Cards Grid)
         ============================================================ --}}
    @if($warehouses->isEmpty())
        <div class="p-8 sm:p-12 text-center bg-white dark:bg-slate-900 rounded-lg border border-dashed border-slate-300 dark:border-slate-800 shadow-2xs space-y-3">
            <div class="w-12 h-12 rounded-xl bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400 grid place-items-center text-xl mx-auto">
                🏬
            </div>
            <div>
                <p class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('messages.no_warehouses_found') }}</p>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">{{ __('messages.warehouse_empty_hint') }}</p>
            </div>
            <div class="flex items-center justify-center gap-2 pt-1">
                @if($search || $status !== '' || $branchId)
                    <a href="{{ route('store.admin.warehouses.index', $storeRouteParams) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 transition">
                        {{ __('messages.reset_filters') ?? 'Clear Filters' }}
                    </a>
                @endif
                <button @click="showCreate = true" type="button"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white transition shadow-2xs cursor-pointer">
                    + {{ __('messages.add_warehouse') }}
                </button>
            </div>
        </div>
    @else
        {{-- VIEW MODE A: SPREADSHEET TABLE VIEW --}}
        <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse font-sans">
                    <thead class="bg-slate-50 dark:bg-slate-800/75 text-slate-600 dark:text-slate-300 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200/80 dark:border-slate-800 select-none">
                        <tr>
                            <th class="p-2.5 text-center w-10">#</th>
                            <th class="p-2.5 min-w-[200px]">{{ __('messages.name') }}</th>
                            <th class="p-2.5 min-w-[100px]">{{ __('messages.code') }}</th>
                            <th class="p-2.5 min-w-[140px]">{{ __('messages.branch') }}</th>
                            <th class="p-2.5 min-w-[160px]">{{ __('messages.warehouse_in_stock') }}</th>
                            <th class="p-2.5 text-center min-w-[100px]">{{ __('messages.status') }}</th>
                            <th class="p-2.5 text-right min-w-[120px]">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300">
                        @foreach($warehouses as $index => $wh)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                <td class="p-2.5 text-center font-mono font-bold text-slate-400">{{ $index + 1 }}</td>

                                {{-- Name & Default Badge --}}
                                <td class="p-2.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-bold text-slate-900 dark:text-slate-100">{{ $wh->name }}</span>
                                        @if($wh->is_default)
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                <span>★</span>
                                                <span>{{ __('messages.default') }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Code --}}
                                <td class="p-2.5">
                                    <span class="font-mono text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">
                                        {{ $wh->code ?: '—' }}
                                    </span>
                                </td>

                                {{-- Branch --}}
                                <td class="p-2.5">
                                    @if($wh->branch)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                            <span>🏢</span>
                                            <span>{{ $wh->branch->name }}</span>
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-xs italic">{{ __('messages.no_branch') }}</span>
                                    @endif
                                </td>

                                {{-- Stock Stats --}}
                                <td class="p-2.5">
                                    <div class="flex items-center gap-1.5 text-xs font-mono">
                                        <span class="font-bold text-violet-700 dark:text-violet-300">
                                            {{ number_format($wh->active_products_count ?? 0) }} {{ __('messages.products') }}
                                        </span>
                                        <span class="text-slate-300 dark:text-slate-600">•</span>
                                        <span class="text-slate-600 dark:text-slate-400">
                                            {{ number_format($wh->total_stock_quantity ?? 0, 2) }} pcs
                                        </span>
                                    </div>
                                </td>

                                {{-- Status Pill --}}
                                <td class="p-2.5 text-center">
                                    @if($wh->is_active)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>{{ __('messages.active') }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                            <span>{{ __('messages.inactive') }}</span>
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="p-2.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button @click="editWh = {{ json_encode(['id' => $wh->id, 'name' => $wh->name, 'code' => $wh->code ?? '', 'branch_id' => $wh->branch_id ?? '', 'is_active' => (bool) $wh->is_active]) }}"
                                                type="button"
                                                class="w-7 h-7 rounded-lg text-slate-500 dark:text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-950/40 grid place-items-center transition cursor-pointer"
                                                title="{{ __('messages.edit_warehouse') }}">
                                            ✏️
                                        </button>
                                        @unless($wh->is_default)
                                            <button @click="deleteWh = {{ json_encode(['id' => $wh->id, 'name' => $wh->name]) }}"
                                                    type="button"
                                                    class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 grid place-items-center transition cursor-pointer"
                                                    title="{{ __('messages.delete_warehouse') }}">
                                                🗑️
                                            </button>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- VIEW MODE B: RESPONSIVE CARDS GRID VIEW --}}
        <div x-show="viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
            @foreach($warehouses as $wh)
                <div class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:border-violet-300 dark:hover:border-violet-800 transition flex flex-col justify-between space-y-2.5">
                    {{-- Header Row --}}
                    <div class="space-y-1.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="w-7 h-7 rounded-lg bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 border border-violet-200 dark:border-violet-800 grid place-items-center text-xs shrink-0">
                                    🏬
                                </span>
                                <div class="min-w-0">
                                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate flex items-center gap-1">
                                        <span>{{ $wh->name }}</span>
                                    </h3>
                                    @if($wh->code)
                                        <span class="font-mono text-[10px] text-slate-400 block">{{ $wh->code }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Status Pill & Star --}}
                            <div class="shrink-0 flex items-center gap-1">
                                @if($wh->is_default)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        ★
                                    </span>
                                @endif
                                <span class="inline-flex px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $wh->is_active ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700' }}">
                                    {{ $wh->is_active ? __('messages.active') : __('messages.inactive') }}
                                </span>
                            </div>
                        </div>

                        {{-- Branch Location --}}
                        <div class="pt-1 text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1">
                            <span>🏢</span>
                            <span class="truncate font-semibold">{{ $wh->branch->name ?? __('messages.no_branch') }}</span>
                        </div>
                    </div>

                    {{-- Stock Summary Pill --}}
                    <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between text-xs font-mono">
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">
                            {{ number_format($wh->active_products_count ?? 0) }} {{ __('messages.products') }}
                        </span>
                        <span class="font-bold text-violet-600 dark:text-violet-400">
                            {{ number_format($wh->total_stock_quantity ?? 0, 2) }} pcs
                        </span>
                    </div>

                    {{-- Card Footer Actions --}}
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-1.5">
                        <button @click="editWh = {{ json_encode(['id' => $wh->id, 'name' => $wh->name, 'code' => $wh->code ?? '', 'branch_id' => $wh->branch_id ?? '', 'is_active' => (bool) $wh->is_active]) }}"
                                type="button"
                                class="px-2.5 py-1 text-xs font-bold rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition flex items-center gap-1 cursor-pointer">
                            <span>✏️</span>
                            <span>{{ __('messages.edit') ?? 'Edit' }}</span>
                        </button>
                        @unless($wh->is_default)
                            <button @click="deleteWh = {{ json_encode(['id' => $wh->id, 'name' => $wh->name]) }}"
                                    type="button"
                                    class="px-2 py-1 text-xs font-bold rounded-md bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 transition flex items-center gap-1 cursor-pointer">
                                <span>🗑️</span>
                            </button>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         5. CREATE WAREHOUSE MODAL
         ============================================================ --}}
    <div x-show="showCreate" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs"
         @click.self="showCreate = false" @keydown.escape.window="showCreate = false">
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-4 sm:p-5 space-y-3.5 transition"
             @click.stop>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <h3 class="text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                    <span>🏬</span>
                    <span>{{ __('messages.add_warehouse') }}</span>
                </h3>
                <button type="button" @click="showCreate = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none cursor-pointer">
                    &times;
                </button>
            </div>

            <form method="POST" action="{{ route('store.admin.warehouses.store', $storeRouteParams) }}" class="space-y-3">
                @csrf
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        {{ __('messages.name') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required maxlength="100" placeholder="e.g. Main Warehouse, Yangon Hub"
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        {{ __('messages.code') }}
                    </label>
                    <input type="text" name="code" maxlength="32" placeholder="e.g. WH-MAIN, WH-YGN"
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-mono text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        {{ __('messages.branch') }}
                    </label>
                    <select name="branch_id"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">{{ __('messages.no_branch') }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showCreate = false"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="px-4 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition active:scale-95 cursor-pointer">
                        + {{ __('messages.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         6. EDIT WAREHOUSE MODAL
         ============================================================ --}}
    <div x-show="editWh" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs"
         @click.self="editWh = null" @keydown.escape.window="editWh = null">
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-4 sm:p-5 space-y-3.5 transition"
             @click.stop>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <h3 class="text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                    <span>✏️</span>
                    <span>{{ __('messages.edit_warehouse') }}</span>
                </h3>
                <button type="button" @click="editWh = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none cursor-pointer">
                    &times;
                </button>
            </div>

            <form :action="'{{ route('store.admin.warehouses.update', array_merge($storeRouteParams, ['warehouse' => 0])) }}'.replace('/0', '/' + editWh?.id)"
                  method="POST" class="space-y-3">
                @csrf
                @method('PUT')

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        {{ __('messages.name') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required maxlength="100" :value="editWh?.name"
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        {{ __('messages.code') }}
                    </label>
                    <input type="text" name="code" maxlength="32" :value="editWh?.code"
                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-mono text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        {{ __('messages.branch') }}
                    </label>
                    <select name="branch_id" x-model="editWh.branch_id"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">{{ __('messages.no_branch') }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="hidden" name="is_active" value="0">
                    <label class="relative inline-flex items-center cursor-pointer gap-2">
                        <input type="checkbox" name="is_active" value="1" :checked="editWh?.is_active"
                               class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500 border-slate-300 dark:border-slate-700">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.active') }}</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="editWh = null"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="px-4 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition active:scale-95 cursor-pointer">
                        {{ __('messages.save_changes') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         7. DELETE WAREHOUSE CONFIRMATION MODAL
         ============================================================ --}}
    <div x-show="deleteWh" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs"
         @click.self="deleteWh = null" @keydown.escape.window="deleteWh = null">
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-sm w-full p-4 sm:p-5 space-y-3 transition"
             @click.stop>
            <div class="w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 grid place-items-center text-lg mx-auto">
                ⚠️
            </div>
            <div class="text-center space-y-1">
                <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.delete_warehouse') }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ __('messages.warehouse_delete_confirm', ['name' => '']) }}<span class="font-bold text-slate-800 dark:text-slate-200" x-text="deleteWh?.name"></span>"?
                </p>
            </div>

            <form :action="'{{ route('store.admin.warehouses.destroy', array_merge($storeRouteParams, ['warehouse' => 0])) }}'.replace('/0', '/' + deleteWh?.id)"
                  method="POST">
                @csrf
                @method('DELETE')
                <div class="flex items-center justify-center gap-2 pt-2">
                    <button type="button" @click="deleteWh = null"
                            class="px-4 py-2 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 transition cursor-pointer shadow-2xs">
                        {{ __('messages.delete') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

