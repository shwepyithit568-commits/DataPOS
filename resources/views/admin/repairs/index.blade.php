@extends('layouts.admin.app')

@section('title', __('messages.sidebar_repair_center') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
@php
    $statusColors = [
        'received' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
        'diagnosing' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
        'awaiting_approval' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
        'awaiting_parts' => 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300',
        'in_repair' => 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300',
        'ready' => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
        'delivered' => 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400',
        'cancelled' => 'bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300',
        'unrepairable' => 'bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300',
    ];
    $statusDots = [
        'received' => 'bg-blue-500',
        'diagnosing' => 'bg-amber-500',
        'awaiting_approval' => 'bg-amber-500',
        'awaiting_parts' => 'bg-purple-500',
        'in_repair' => 'bg-orange-500',
        'ready' => 'bg-emerald-500',
        'delivered' => 'bg-slate-400',
        'cancelled' => 'bg-rose-500',
        'unrepairable' => 'bg-rose-500',
    ];
    $statusOptions = collect(\App\POS\Models\ServiceJob::STATUSES)
        ->map(fn ($s) => ['value' => $s, 'label' => __('messages.repair_status_' . $s)])
        ->values()
        ->toJson();
    $tabParams = request()->except(['tab', 'page']);
    $exportUrl = route('store.admin.repairs.export', [...$storeRouteParams, ...request()->except(['page'])]);

    $detailUrls = $jobs->mapWithKeys(fn ($job) => [
        $job->id => route('store.admin.repairs.show', [...$storeRouteParams, 'repair' => $job->id]),
    ]);

    // Device icon helper
    $deviceIcon = function (string $type, string $model): string {
        $s = strtolower($type . ' ' . $model);
        return match (true) {
            str_contains($s, 'laptop'), str_contains($s, 'macbook'), str_contains($s, 'desktop'), str_contains($s, 'pc') => 'laptop',
            str_contains($s, 'tablet'), str_contains($s, 'ipad') => 'tablet',
            str_contains($s, 'watch') => 'watch',
            str_contains($s, 'tv') => 'tv',
            str_contains($s, 'router'), str_contains($s, 'wifi') => 'router',
            str_contains($s, 'camera'), str_contains($s, 'cctv') => 'camera',
            default => 'phone',
        };
    };
@endphp

<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
        viewMode: localStorage.getItem('admin_view_mode') || 'card',
        open: false,
        jobNumber: '',
        currentStatus: '',
        statusUrl: '',
        selected: '',
        note: '',
        statuses: {{ $statusOptions }},
        openQuickStatus(id, jobNumber, currentStatus) {
            this.open = true;
            this.jobNumber = jobNumber;
            this.currentStatus = currentStatus;
            this.selected = '';
            this.note = '';
            this.statusUrl = '{{ route('store.admin.repairs.status', [...$storeRouteParams, 'repair' => '__ID__']) }}'.replace('__ID__', id);
        },
        closeModal() {
            this.open = false;
        }
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

    {{-- ============================================================
         1. TOP PAGE HEADER — Eyebrow, Title, Context & New Job Action
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-violet-100 dark:border-violet-900/60 mb-0.5">
                <span>🔧</span>
                <span>{{ __('messages.sidebar_repair_center') }}</span>
                <span class="text-slate-400 dark:text-slate-500">·</span>
                <span class="font-normal normal-case text-slate-500 dark:text-slate-400">Service Center & Repair Hub</span>
            </div>
            <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>{{ __('messages.sidebar_repair_center') }}</span>
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                {{ $store->name }} · {{ number_format($totalCount) }} {{ __('messages.repair_jobs') }}
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('pos.reports.services', $storeRouteParams) }}"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700 shadow-2xs transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <span>📊</span>
                <span>{{ __('messages.sidebar_service_revenue') }}</span>
            </a>
            <a href="{{ route('store.admin.repairs.create', $storeRouteParams) }}"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-black bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <span class="text-sm leading-none">+</span>
                <span>{{ __('messages.repair_new_job') }}</span>
            </a>
        </div>
    </header>

    {{-- ============================================================
         2. SEGMENTED TABS: All / Processing / Ready / History
         ============================================================ --}}
    <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5 scrollbar-thin">
        <div class="inline-flex items-center gap-1 p-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            @foreach ([
                'all' => __('messages.repair_tab_all'),
                'processing' => __('messages.repair_tab_processing'),
                'ready' => __('messages.repair_tab_ready'),
                'history' => __('messages.repair_tab_history'),
            ] as $value => $label)
                @php
                    $isActive = $tab === $value;
                    $url = $value === 'all'
                        ? route('store.admin.repairs.index', [...$storeRouteParams, ...$tabParams])
                        : route('store.admin.repairs.index', [...$storeRouteParams, ...$tabParams, 'tab' => $value]);
                @endphp
                <a href="{{ $url }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-bold whitespace-nowrap transition cursor-pointer
                          {{ $isActive
                              ? 'bg-violet-600 text-white shadow-2xs'
                              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span>{{ $label }}</span>
                    <span class="text-[10px] font-black px-1.5 py-0.2 rounded-full
                                 {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }}">
                        {{ number_format($tabCounts[$value] ?? 0) }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ============================================================
         3. 4 KEY KPI STAT CARDS
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2">
        {{-- Total Jobs --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-blue-600 dark:text-blue-400 truncate">{{ __('messages.repair_stat_total') }}</span>
                <span class="text-xs">📋</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-blue-600 dark:text-blue-400 mt-1 font-mono tracking-tight">{{ number_format($stats['total']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Total Registered Jobs</div>
        </div>

        {{-- Active / In Progress --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 truncate">{{ __('messages.repair_stat_active') }}</span>
                <span class="text-xs">⚡</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono tracking-tight">{{ number_format($stats['active']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Diagnosing & In Repair</div>
        </div>

        {{-- Ready for Pickup --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-violet-600 dark:text-violet-400 truncate">{{ __('messages.repair_stat_ready') }}</span>
                <span class="text-xs">✅</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-violet-700 dark:text-violet-300 mt-1 font-mono tracking-tight">{{ number_format($stats['ready']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Ready for Collection</div>
        </div>

        {{-- Outstanding Balance --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 truncate">{{ __('messages.repair_stat_debt') }}</span>
                <span class="text-xs">💰</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-amber-600 dark:text-amber-400 mt-1 font-mono tracking-tight">{{ number_format($stats['debt'], 0) }} <span class="text-xs font-normal">Ks</span></div>
            <div class="text-[10px] text-slate-400 mt-0.5">Unpaid Job Invoices</div>
        </div>
    </div>

    {{-- Flash Notifications & Errors --}}
    @if (session('success'))
        <div class="w-full p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="w-full p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-2xs">
            <div class="font-black flex items-center gap-1.5">
                <span>⚠️</span>
                <span>{{ __('messages.validation_error') }}:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="ml-4">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         4. UNIFIED TOOLBAR
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', '')"
        :searchPlaceholder="__('messages.repair_search_placeholder')"
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest' => __('messages.repair_sort_newest'),
            'oldest' => __('messages.repair_sort_oldest'),
            'customer' => __('messages.repair_sort_customer'),
            'status' => __('messages.repair_sort_status'),
        ]"
        :filters="[
            'status' => [
                'label' => __('messages.status'),
                'options' => collect(\App\POS\Models\ServiceJob::STATUSES)
                    ->mapWithKeys(fn ($s) => [$s => __('messages.repair_status_' . $s)])
                    ->toArray()
            ],
            'date' => [
                'label' => __('messages.repair_date_range'),
                'type' => 'date',
            ],
        ]"
        :viewMode="'card'"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$exportUrl"
        :totalCount="$totalCount"
        :paginator="$jobs"
    />

    {{-- ============================================================
         5. DUAL VIEWS: CARD GRID VIEW (Default)
         ============================================================ --}}
    <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2 sm:gap-2.5">
        @forelse ($jobs as $job)
            <div data-href="{{ $detailUrls[$job->id] }}" onclick="window.location.href = this.dataset.href"
                 class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition overflow-hidden cursor-pointer group flex flex-col justify-between">
                
                <div>
                    {{-- Device visual + status pill --}}
                    <div class="relative h-28 bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800/60 dark:to-slate-900 flex items-center justify-center border-b border-slate-100 dark:border-slate-800">
                        @php $icon = $deviceIcon($job->device_type, $job->model ?? ''); @endphp
                        @if ($icon === 'phone')
                            <svg class="w-12 h-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        @elseif ($icon === 'tablet')
                            <svg class="w-12 h-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v10m0 0H8m4 0h4m-8 3h8a2 2 0 002-2V6a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        @elseif ($icon === 'laptop')
                            <svg class="w-12 h-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        @elseif ($icon === 'watch')
                            <svg class="w-12 h-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @elseif ($icon === 'tv')
                            <svg class="w-12 h-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        @elseif ($icon === 'router')
                            <svg class="w-12 h-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.07c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                        @elseif ($icon === 'camera')
                            <svg class="w-12 h-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        @endif
                        <span class="absolute top-2 left-2 text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-white/90 dark:bg-slate-900/90 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shadow-2xs">{{ $job->job_number }}</span>
                        <span class="absolute top-2 right-2 inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-md whitespace-nowrap {{ $statusColors[$job->status] ?? 'bg-slate-100 text-slate-500' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $statusDots[$job->status] ?? 'bg-slate-400' }}"></span>
                            <span>{{ __('messages.repair_status_' . $job->status) }}</span>
                        </span>
                    </div>

                    {{-- Customer & Device Details --}}
                    <div class="p-3 space-y-2">
                        <div class="flex items-start justify-between gap-1.5">
                            <div class="min-w-0">
                                <div class="font-black text-xs text-slate-900 dark:text-slate-100 truncate">{{ $job->contact_name ?: ($job->customer?->name ?? '—') }}</div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">{{ $job->device_type }} {{ $job->model ? '· ' . $job->model : '' }}</div>
                            </div>
                            @if ($job->outstanding() > 0)
                                <span class="text-xs font-black text-amber-600 dark:text-amber-400 font-mono whitespace-nowrap">{{ number_format($job->outstanding(), 0) }} Ks</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card Footer & Actions --}}
                <div class="p-3 pt-0">
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2 text-[11px]">
                        <span class="text-slate-400 font-mono">{{ $job->created_at->format('M d, Y') }}</span>
                        <div class="flex items-center gap-1">
                            @if (! $job->isTerminal())
                                <button type="button"
                                        onclick="event.stopPropagation();"
                                        @click="openQuickStatus({{ $job->id }}, '{{ $job->job_number }}', '{{ $job->status }}')"
                                        class="px-2 py-1 rounded-md text-[10px] font-bold text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/60 hover:bg-violet-100 dark:hover:bg-violet-900/50 transition cursor-pointer" title="{{ __('messages.repair_quick_status') }}">
                                    Status
                                </button>
                                <a href="{{ route('store.admin.repairs.edit', [...$storeRouteParams, 'repair' => $job->id]) }}" onclick="event.stopPropagation();"
                                   class="px-2 py-1 rounded-md text-[10px] font-bold text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-950/60 hover:bg-sky-100 dark:hover:bg-sky-900/50 transition cursor-pointer" title="{{ __('messages.repair_edit') }}">
                                    Edit
                                </a>
                            @endif
                            <a href="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $job->id]) }}" target="_blank" onclick="event.stopPropagation();"
                               class="px-2 py-1 rounded-md text-[10px] font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition cursor-pointer" title="{{ __('messages.repair_print_ticket') }}">
                                Slip
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-900 p-8 rounded-lg text-center border border-slate-200/80 dark:border-slate-800 shadow-2xs">
                <div class="text-2xl mb-1">🔧</div>
                <div class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ __('messages.repair_empty') }}</div>
                <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">{{ __('messages.repair_empty_hint') }}</div>
            </div>
        @endforelse
    </div>

    {{-- ============================================================
         6. DUAL VIEWS: SPREADSHEET TABLE VIEW (Opt-in)
         ============================================================ --}}
    <div x-show="viewMode === 'table'" x-cloak class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="p-2.5 whitespace-nowrap">{{ __('messages.repair_job_number') }}</th>
                        <th class="p-2.5 whitespace-nowrap">{{ __('messages.repair_device') }}</th>
                        <th class="p-2.5 whitespace-nowrap">{{ __('messages.repair_customer_label') }}</th>
                        <th class="p-2.5 whitespace-nowrap">{{ __('messages.repair_technician') }}</th>
                        <th class="p-2.5 whitespace-nowrap">{{ __('messages.status') }}</th>
                        <th class="p-2.5 whitespace-nowrap">{{ __('messages.repair_received_at') }}</th>
                        <th class="p-2.5 whitespace-nowrap text-right">{{ __('messages.repair_outstanding') }}</th>
                        <th class="p-2.5 whitespace-nowrap text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($jobs as $job)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                            <td class="p-2.5">
                                <a href="{{ route('store.admin.repairs.show', [...$storeRouteParams, 'repair' => $job->id]) }}" class="font-mono font-bold text-slate-900 dark:text-slate-100 hover:text-violet-600 dark:hover:text-violet-400">{{ $job->job_number }}</a>
                            </td>
                            <td class="p-2.5">
                                <div class="font-medium text-slate-900 dark:text-slate-100">{{ $job->device_type }}</div>
                                @if ($job->model)
                                    <div class="text-[10px] text-slate-400">{{ $job->model }}</div>
                                @endif
                            </td>
                            <td class="p-2.5">
                                <div class="font-bold text-slate-800 dark:text-slate-200">{{ $job->contact_name ?: ($job->customer?->name ?? '—') }}</div>
                                @if ($job->contact_phone)
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $job->contact_phone }}</div>
                                @endif
                            </td>
                            <td class="p-2.5 text-[11px] text-slate-500">{{ $job->technician?->name ?? '—' }}</td>
                            <td class="p-2.5">
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md whitespace-nowrap {{ $statusColors[$job->status] ?? 'bg-slate-100 text-slate-500' }}">
                                    {{ __('messages.repair_status_' . $job->status) }}
                                </span>
                            </td>
                            <td class="p-2.5 text-[11px] text-slate-400 whitespace-nowrap font-mono">{{ $job->created_at->format('M d, Y') }}</td>
                            <td class="p-2.5 text-right whitespace-nowrap">
                                @if ($job->outstanding() > 0)
                                    <span class="text-amber-600 dark:text-amber-400 font-mono font-bold">{{ number_format($job->outstanding(), 0) }} Ks</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 font-mono">—</span>
                                @endif
                            </td>
                            <td class="p-2.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    @if (! $job->isTerminal())
                                        <button type="button" @click="openQuickStatus({{ $job->id }}, '{{ $job->job_number }}', '{{ $job->status }}')"
                                            class="px-2 py-1 text-[10px] font-bold text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/60 border border-violet-200 dark:border-violet-800 rounded-md hover:bg-violet-100 dark:hover:bg-violet-900/50 transition cursor-pointer">
                                            Status
                                        </button>
                                        <a href="{{ route('store.admin.repairs.edit', [...$storeRouteParams, 'repair' => $job->id]) }}"
                                           class="px-2 py-1 text-[10px] font-bold text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-950/60 border border-sky-200 dark:border-sky-800 rounded-md hover:bg-sky-100 transition cursor-pointer">
                                            Edit
                                        </a>
                                    @endif
                                    <a href="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $job->id]) }}" target="_blank"
                                       class="px-2 py-1 text-[10px] font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 rounded-md hover:bg-blue-100 transition cursor-pointer">
                                        Slip
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400 dark:text-slate-500 text-xs font-bold">
                                {{ __('messages.repair_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if (method_exists($jobs, 'links'))
        <div class="pt-1">{{ $jobs->links() }}</div>
    @endif

    {{-- ============================================================
         7. QUICK STATUS MODAL
         ============================================================ --}}
    <div x-cloak x-show="open" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto"
         @keydown.escape.window="closeModal()" @click.self="closeModal()">
        <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-2xl space-y-3.5 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span>🔄</span>
                        <span>{{ __('messages.repair_quick_status') }}</span>
                    </h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        <span class="font-mono font-bold" x-text="jobNumber"></span> · Current: <span x-text="currentStatus" class="font-bold text-violet-600 dark:text-violet-400"></span>
                    </p>
                </div>
                <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-600 text-sm font-bold" aria-label="Close">✕</button>
            </div>

            <form method="POST" :action="statusUrl" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1.5">Select New Status *</label>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="st in statuses" :key="st.value">
                            <button type="button"
                                @click="selected = st.value"
                                :disabled="st.value === currentStatus"
                                class="px-2.5 py-1 text-[11px] font-bold rounded-md border transition disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                                :class="selected === st.value
                                    ? 'bg-violet-600 text-white border-violet-600 shadow-2xs'
                                    : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100'">
                                <span x-text="st.label"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.repair_status_note') }}</label>
                    <input type="text" name="note" x-model="note" maxlength="500"
                           placeholder="{{ __('messages.repair_status_note_placeholder') }}"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>

                <div class="flex items-center justify-end gap-2 pt-2.5 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="closeModal()"
                        class="px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 rounded-lg">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" :disabled="!selected"
                        class="px-4 py-2 text-xs font-bold text-white bg-violet-600 hover:bg-violet-700 rounded-lg shadow-2xs transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed active:scale-95">
                        {{ __('messages.repair_apply') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
