{{-- Stock Transfers Management View (Admin UI Standard 3.0) --}}
@php
    $currentStatus = request('status', $status ?? '');
    $activeFiltersCount = 0;
    if (!empty(request('search'))) $activeFiltersCount++;
    if (!empty($currentStatus)) $activeFiltersCount++;
    if (!empty(request('from_warehouse_id'))) $activeFiltersCount++;
    if (!empty(request('to_warehouse_id'))) $activeFiltersCount++;
    if (!empty(request('date_from')) || !empty(request('date_to'))) $activeFiltersCount++;
@endphp

<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{ viewMode: localStorage.getItem('admin_view_mode') || 'table' }"
     @view-changed.window="viewMode = $event.detail">

    {{-- ============================================================
         1. COMPACT HERO PAGE HEADER (Admin UI Standard)
         ============================================================ --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
        <div class="min-w-0">
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span>🔄 {{ __('messages.transfers_title') }}</span>
                <span class="text-xs font-mono font-bold text-slate-400">({{ number_format($stats['total'] ?? $transfers->total()) }})</span>
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 shrink-0">
            {{-- Quick Link to Warehouses --}}
            <a href="{{ route('store.admin.warehouses.index', ['store_slug' => $store->slug]) }}"
               class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                <span>🏬</span>
                <span>{{ __('messages.warehouses_title') }}</span>
            </a>

            {{-- Primary Action: New Transfer --}}
            <a href="{{ route('pos.transfers.create', $storeRouteParams) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition flex items-center gap-1.5 active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('messages.new_transfer') }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. KPI SUMMARY METRIC CARDS (4-UP CLICK-TO-FILTER)
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5" role="list" aria-label="Stock Transfer Status Metrics">
        {{-- Total Transfers --}}
        <a href="{{ route('pos.transfers.index', array_merge($storeRouteParams, request()->except('status', 'page'))) }}"
           class="group p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 {{ empty($currentStatus) ? 'border-violet-600 bg-violet-50/60 dark:border-violet-500 dark:bg-violet-950/40 ring-2 ring-violet-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 shadow-inner">
                <span class="text-base sm:text-lg">📦</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-mono">
                    {{ number_format($stats['total'] ?? 0) }}
                </p>
                <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.transfer_stat_total') }}
                </p>
            </div>
        </a>

        {{-- Pending --}}
        <a href="{{ route('pos.transfers.index', array_merge($storeRouteParams, request()->except('page'), ['status' => 'pending'])) }}"
           class="group p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 {{ $currentStatus === 'pending' ? 'border-amber-600 bg-amber-50/60 dark:border-amber-500 dark:bg-amber-950/40 ring-2 ring-amber-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner relative">
                <span class="text-base sm:text-lg">⏳</span>
                @if(($stats['pending'] ?? 0) > 0)
                    <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-mono">
                    {{ number_format($stats['pending'] ?? 0) }}
                </p>
                <p class="text-[10px] sm:text-[11px] text-amber-700 dark:text-amber-300/80 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.transfer_stat_pending') }}
                </p>
            </div>
        </a>

        {{-- In Transit --}}
        <a href="{{ route('pos.transfers.index', array_merge($storeRouteParams, request()->except('page'), ['status' => 'in_transit'])) }}"
           class="group p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 {{ $currentStatus === 'in_transit' ? 'border-sky-600 bg-sky-50/60 dark:border-sky-500 dark:bg-sky-950/40 ring-2 ring-sky-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300 shadow-inner relative">
                <span class="text-base sm:text-lg">🚚</span>
                @if(($stats['in_transit'] ?? 0) > 0)
                    <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-sky-500 animate-pulse"></span>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-black text-sky-600 dark:text-sky-400 leading-none tabular-nums font-mono">
                    {{ number_format($stats['in_transit'] ?? 0) }}
                </p>
                <p class="text-[10px] sm:text-[11px] text-sky-700 dark:text-sky-300/80 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.transfer_stat_in_transit') }}
                </p>
            </div>
        </a>

        {{-- Completed --}}
        <a href="{{ route('pos.transfers.index', array_merge($storeRouteParams, request()->except('page'), ['status' => 'completed'])) }}"
           class="group p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 {{ $currentStatus === 'completed' ? 'border-emerald-600 bg-emerald-50/60 dark:border-emerald-500 dark:bg-emerald-950/40 ring-2 ring-emerald-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner">
                <span class="text-base sm:text-lg">✅</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-mono">
                    {{ number_format($stats['completed'] ?? 0) }}
                </p>
                <p class="text-[10px] sm:text-[11px] text-emerald-700 dark:text-emerald-300/80 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.transfer_stat_completed') }}
                </p>
            </div>
        </a>
    </div>

    {{-- ============================================================
         3. MASTER TOOLBAR COMPONENT (<x-admin.toolbar>)
         ============================================================ --}}
    @php
        $warehouseOptions = $warehouses->pluck('name', 'id')->toArray();
        $toolbarFilters = [
            'from_warehouse_id' => [
                'label' => __('messages.from_warehouse'),
                'options' => $warehouseOptions,
            ],
            'to_warehouse_id' => [
                'label' => __('messages.to_warehouse'),
                'options' => $warehouseOptions,
            ],
            'created_at' => [
                'label' => __('messages.date'),
                'type' => 'date_range',
            ],
        ];
    @endphp

    <x-admin.toolbar
        :search="request('search', $search)"
        :search-placeholder="__('messages.search_transfer_placeholder')"
        :sort="request('sort', 'newest')"
        :sort-options="[
            'newest' => __('messages.transfer_sort_newest'),
            'oldest' => __('messages.transfer_sort_oldest'),
            'number_asc' => __('messages.transfer_sort_number_asc'),
            'number_desc' => __('messages.transfer_sort_number_desc'),
        ]"
        :filters="$toolbarFilters"
        :view-mode="request('view', 'table')"
        :show-view-toggle="true"
        :show-export-import="false"
        :paginator="$transfers"
        :show-pagination="true"
    />

    {{-- Quick Status Filter Tabs --}}
    <div class="flex items-center gap-1 bg-white dark:bg-slate-900 p-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 text-xs overflow-x-auto scrollbar-thin">
        @foreach([
            '' => __('messages.transfer_all_status') . ' (' . number_format($stats['total'] ?? 0) . ')',
            'pending' => __('messages.transfer_status_pending') . ' (' . number_format($stats['pending'] ?? 0) . ')',
            'in_transit' => __('messages.transfer_status_in_transit') . ' (' . number_format($stats['in_transit'] ?? 0) . ')',
            'completed' => __('messages.transfer_status_completed') . ' (' . number_format($stats['completed'] ?? 0) . ')',
            'cancelled' => __('messages.transfer_status_cancelled') . ' (' . number_format($stats['cancelled'] ?? 0) . ')',
        ] as $stVal => $stLabel)
            <a href="{{ route('pos.transfers.index', array_merge($storeRouteParams, request()->except('page'), ['status' => $stVal])) }}"
               class="px-2.5 py-1 rounded-md text-xs font-bold transition whitespace-nowrap {{ ($currentStatus === $stVal) ? 'bg-violet-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                {{ $stLabel }}
            </a>
        @endforeach
    </div>

    {{-- ============================================================
         4. SPREADSHEET DATA GRID (TABLE VIEW)
         ============================================================ --}}
    <div x-show="viewMode === 'table'" class="rounded-lg border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto max-h-[68vh] overflow-y-auto">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800 border-b-2 border-slate-300 dark:border-slate-700 shadow-xs select-none backdrop-blur-xs">
                    <tr class="text-[11px] font-black uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100">
                        <th class="py-2.5 px-3 min-w-[150px]">{{ __('messages.transfer_number') }}</th>
                        <th class="py-2.5 px-3 min-w-[150px]">{{ __('messages.from_warehouse') }}</th>
                        <th class="py-2.5 px-3 min-w-[150px]">{{ __('messages.to_warehouse') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[110px]">{{ __('messages.items') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[120px]">{{ __('messages.status') }}</th>
                        <th class="py-2.5 px-3 min-w-[140px]">{{ __('messages.date') }}</th>
                        <th class="py-2.5 px-2 text-right min-w-[140px]">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse($transfers as $transfer)
                        @php
                            $totalQty = $transfer->items->sum('quantity');
                            $itemsCount = $transfer->items->count();
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 divide-x divide-slate-200/80 dark:divide-slate-800 transition">
                            {{-- Transfer Number & Notes --}}
                            <td class="py-2 px-3">
                                <a href="{{ route('pos.transfers.show', [...$storeRouteParams, 'transfer' => $transfer->id]) }}"
                                   class="font-mono font-black text-violet-600 dark:text-violet-400 hover:underline text-xs block">
                                    {{ $transfer->transfer_number }}
                                </a>
                                @if($transfer->notes)
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-xs mt-0.5" title="{{ $transfer->notes }}">{{ $transfer->notes }}</p>
                                @endif
                            </td>

                            {{-- From Warehouse --}}
                            <td class="py-2 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5 font-bold text-slate-900 dark:text-slate-100">
                                    <span class="text-slate-400">📤</span>
                                    <span>{{ $transfer->fromWarehouse->name ?? '—' }}</span>
                                </div>
                                @if($transfer->fromWarehouse?->code)
                                    <span class="text-[10px] font-mono text-slate-400">({{ $transfer->fromWarehouse->code }})</span>
                                @endif
                            </td>

                            {{-- To Warehouse --}}
                            <td class="py-2 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5 font-bold text-slate-900 dark:text-slate-100">
                                    <span class="text-slate-400">📥</span>
                                    <span>{{ $transfer->toWarehouse->name ?? '—' }}</span>
                                </div>
                                @if($transfer->toWarehouse?->code)
                                    <span class="text-[10px] font-mono text-slate-400">({{ $transfer->toWarehouse->code }})</span>
                                @endif
                            </td>

                            {{-- Items & Total Quantity --}}
                            <td class="py-2 px-3 text-center whitespace-nowrap font-mono">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-bold rounded-md bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                    <span>{{ $itemsCount }} {{ $itemsCount === 1 ? 'item' : 'items' }}</span>
                                    <span class="text-slate-400">•</span>
                                    <span class="text-violet-600 dark:text-violet-400">{{ number_format($totalQty, $totalQty == round($totalQty) ? 0 : 2) }} pcs</span>
                                </span>
                            </td>

                            {{-- Status Badge --}}
                            <td class="py-2 px-3 text-center whitespace-nowrap">
                                @if($transfer->status === 'completed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <span>✓</span>
                                        <span>{{ __('messages.transfer_status_completed') }}</span>
                                    </span>
                                @elseif($transfer->status === 'in_transit')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span>
                                        <span>🚚 {{ __('messages.transfer_status_in_transit') }}</span>
                                    </span>
                                @elseif($transfer->status === 'pending')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                        <span>{{ __('messages.transfer_status_pending') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                        <span>✕</span>
                                        <span>{{ __('messages.transfer_status_cancelled') }}</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Date & Creator --}}
                            <td class="py-2 px-3 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400 font-mono">
                                <div>{{ $transfer->created_at->format('d/m/Y H:i') }}</div>
                                <div class="text-[10px] text-slate-400 font-sans mt-0.5">{{ $transfer->creator?->name ?? 'System' }}</div>
                            </td>

                            {{-- Actions --}}
                            <td class="py-2 px-2 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- Fast Ship Button --}}
                                    @if($transfer->status === 'pending')
                                        <form method="POST" action="{{ route('pos.transfers.ship', [...$storeRouteParams, 'transfer' => $transfer->id]) }}" class="inline"
                                              onsubmit="return confirm('{{ addslashes(__('messages.transfer_ship_confirm')) }}')">
                                            @csrf
                                            <button type="submit"
                                                    title="{{ __('messages.ship') }}"
                                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-bold rounded-md bg-amber-500 hover:bg-amber-600 text-white shadow-2xs transition active:scale-95 cursor-pointer">
                                                <span>🚚</span>
                                                <span>{{ __('messages.ship') }}</span>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Fast Receive Button --}}
                                    @if($transfer->status === 'in_transit')
                                        <form method="POST" action="{{ route('pos.transfers.receive', [...$storeRouteParams, 'transfer' => $transfer->id]) }}" class="inline"
                                              onsubmit="return confirm('{{ addslashes(__('messages.transfer_receive_confirm')) }}')">
                                            @csrf
                                            <button type="submit"
                                                    title="{{ __('messages.receive') }}"
                                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-bold rounded-md bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs transition active:scale-95 cursor-pointer">
                                                <span>✅</span>
                                                <span>{{ __('messages.receive') }}</span>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- View Details Link --}}
                                    <a href="{{ route('pos.transfers.show', [...$storeRouteParams, 'transfer' => $transfer->id]) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-md transition shadow-2xs bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200">
                                        <span>{{ __('messages.view_details') ?? 'View' }}</span>
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 px-4 text-center text-slate-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                    <span class="text-4xl mb-2">🔄</span>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('messages.no_transfers_found') }}</p>
                                    <p class="text-xs text-slate-400 mt-1">{{ __('messages.transfer_empty_hint') }}</p>
                                    <a href="{{ route('pos.transfers.create', $storeRouteParams) }}"
                                       class="mt-4 inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold rounded-lg bg-violet-600 text-white hover:bg-violet-700 shadow-md transition">
                                        <span>+</span>
                                        <span>{{ __('messages.new_transfer') }}</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================
         5. RESPONSIVE CARDS VIEW GRID (CARD VIEW MODE)
         ============================================================ --}}
    <div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
        @forelse($transfers as $transfer)
            @php
                $totalQty = $transfer->items->sum('quantity');
                $itemsCount = $transfer->items->count();
            @endphp
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:border-violet-300 dark:hover:border-violet-600/50 hover:shadow-sm transition flex flex-col justify-between group overflow-hidden">
                {{-- Top Card Content --}}
                <div class="p-3 sm:p-3.5 space-y-2.5">
                    {{-- Header Row: Transfer Number + Status Pill --}}
                    <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                        <div>
                            <a href="{{ route('pos.transfers.show', [...$storeRouteParams, 'transfer' => $transfer->id]) }}"
                               class="font-mono font-black text-xs sm:text-sm text-violet-600 dark:text-violet-400 hover:underline tracking-tight block">
                                {{ $transfer->transfer_number }}
                            </a>
                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                {{ $transfer->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>

                        {{-- Status Badge --}}
                        @if($transfer->status === 'completed')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                <span>✓</span>
                                <span>{{ __('messages.transfer_status_completed') }}</span>
                            </span>
                        @elseif($transfer->status === 'in_transit')
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span>
                                <span>🚚 {{ __('messages.transfer_status_in_transit') }}</span>
                            </span>
                        @elseif($transfer->status === 'pending')
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                <span>{{ __('messages.transfer_status_pending') }}</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                <span>✕</span>
                                <span>{{ __('messages.transfer_status_cancelled') }}</span>
                            </span>
                        @endif
                    </div>

                    {{-- Transfer Route Hero Box --}}
                    <div class="p-2.5 rounded-lg bg-slate-50/80 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 space-y-1.5">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            {{ __('messages.transfer_route') }}
                        </div>
                        <div class="flex items-center justify-between gap-1 text-xs">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 dark:text-slate-100 truncate flex items-center gap-1">
                                    <span>📤</span>
                                    <span class="truncate">{{ $transfer->fromWarehouse->name ?? '—' }}</span>
                                </div>
                            </div>
                            <span class="text-slate-400 font-bold px-1">➔</span>
                            <div class="min-w-0 flex-1 text-right">
                                <div class="font-bold text-slate-900 dark:text-slate-100 truncate flex items-center justify-end gap-1">
                                    <span class="truncate">{{ $transfer->toWarehouse->name ?? '—' }}</span>
                                    <span>📥</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Items & Summary Row --}}
                    <div class="flex items-center justify-between text-xs py-1 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">{{ __('messages.items') }}:</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200">
                            {{ $itemsCount }} items <span class="text-slate-400">•</span> <span class="text-violet-600 dark:text-violet-400">{{ number_format($totalQty, $totalQty == round($totalQty) ? 0 : 2) }} pcs</span>
                        </span>
                    </div>

                    {{-- Creator & Notes Metadata --}}
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between pt-0.5">
                        <span>{{ __('messages.created_by') }}: <strong class="text-slate-700 dark:text-slate-300">{{ $transfer->creator?->name ?? 'System' }}</strong></span>
                    </div>

                    @if($transfer->notes)
                        <div class="text-[10px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/40 p-1.5 rounded border border-slate-200/60 dark:border-slate-800 line-clamp-2">
                            <strong>{{ __('messages.notes') }}:</strong> {{ $transfer->notes }}
                        </div>
                    @endif
                </div>

                {{-- Card Action Footer --}}
                <div class="p-2.5 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    @if($transfer->status === 'pending')
                        <form method="POST" action="{{ route('pos.transfers.ship', [...$storeRouteParams, 'transfer' => $transfer->id]) }}"
                              onsubmit="return confirm('{{ addslashes(__('messages.transfer_ship_confirm')) }}')">
                            @csrf
                            <button type="submit"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-2xs transition active:scale-95 cursor-pointer flex items-center gap-1">
                                <span>🚚</span>
                                <span>{{ __('messages.ship') }}</span>
                            </button>
                        </form>
                    @elseif($transfer->status === 'in_transit')
                        <form method="POST" action="{{ route('pos.transfers.receive', [...$storeRouteParams, 'transfer' => $transfer->id]) }}"
                              onsubmit="return confirm('{{ addslashes(__('messages.transfer_receive_confirm')) }}')">
                            @csrf
                            <button type="submit"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs transition active:scale-95 cursor-pointer flex items-center gap-1">
                                <span>✅</span>
                                <span>{{ __('messages.receive') }}</span>
                            </button>
                        </form>
                    @else
                        <div></div>
                    @endif

                    <a href="{{ route('pos.transfers.show', [...$storeRouteParams, 'transfer' => $transfer->id]) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-2xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 active:scale-95">
                        <span>{{ __('messages.view_details') ?? 'View' }}</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 px-4 text-center text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 shadow-2xs">
                <span class="text-4xl mb-2 block">🔄</span>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('messages.no_transfers_found') }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ __('messages.transfer_empty_hint') }}</p>
                <a href="{{ route('pos.transfers.create', $storeRouteParams) }}"
                   class="mt-3 inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold rounded-lg bg-violet-600 text-white hover:bg-violet-700 shadow-md transition">
                    <span>+</span>
                    <span>{{ __('messages.new_transfer') }}</span>
                </a>
            </div>
        @endforelse
    </div>

    {{-- Bottom Pagination --}}
    @if($transfers->hasPages())
        <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            {{ $transfers->links() }}
        </div>
    @endif

</div>
