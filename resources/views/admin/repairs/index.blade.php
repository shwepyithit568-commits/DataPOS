@extends('layouts.admin.app')

@section('content')
@php
    $statusColors = [
        'received' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
        'diagnosing' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
        'awaiting_approval' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
        'awaiting_parts' => 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300',
        'in_repair' => 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300',
        'ready' => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
        'delivered' => 'bg-gray-100 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400',
        'cancelled' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
        'unrepairable' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
    ];
    $statusDots = [
        'received' => 'bg-blue-500',
        'diagnosing' => 'bg-amber-500',
        'awaiting_approval' => 'bg-amber-500',
        'awaiting_parts' => 'bg-purple-500',
        'in_repair' => 'bg-orange-500',
        'ready' => 'bg-emerald-500',
        'delivered' => 'bg-gray-400',
        'cancelled' => 'bg-red-500',
        'unrepairable' => 'bg-red-500',
    ];
    $statusOptions = collect(\App\POS\Models\ServiceJob::STATUSES)
        ->map(fn ($s) => ['value' => $s, 'label' => __('messages.repair_status_' . $s)])
        ->values()
        ->toJson();
    $tabParams = request()->except(['tab', 'page']);
    $exportUrl = route('store.admin.repairs.export', [...$storeRouteParams, ...request()->except(['page'])]);
    // Pre-computed detail links — keeps PHP route() out of inline JS attributes
    // so the editor's JS analyzer never sees PHP single-quoted strings.
    $detailUrls = $jobs->mapWithKeys(fn ($job) => [
        $job->id => route('store.admin.repairs.show', [...$storeRouteParams, 'repair' => $job->id]),
    ]);

    // Device icon helper (Repairs Center card parity).
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

<div class="w-full space-y-5 sm:space-y-6"
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

    {{-- Header --}}
    <div class="admin-page-header">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <div class="w-11 h-11 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                    </svg>
                </div>
                <div>
                    <h1 class="admin-page-title">{{ __('messages.sidebar_repair_center') }}</h1>
                    <p class="admin-page-sub">{{ $store->name }} · {{ number_format($totalCount) }} {{ __('messages.repair_jobs') }}</p>
                </div>
            </div>
        </div>
        <a href="{{ route('store.admin.repairs.create', $storeRouteParams) }}"
           class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-white bg-violet-600 hover:bg-violet-700 rounded-lg shadow transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('messages.repair_new_job') }}
        </a>
    </div>

    {{-- Segmented tabs: All / Processing / Ready / History (Repairs Center parity) --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-thin -mx-1 px-1">
        <div class="inline-flex items-center gap-1 p-1 bg-gray-100 dark:bg-slate-900 rounded-xl border dark:border-slate-700">
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
                   class="inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition
                          {{ $isActive
                              ? 'bg-white dark:bg-slate-700 text-violet-700 dark:text-violet-300 shadow-sm'
                              : 'text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-100' }}">
                    {{ $label }}
                    <span class="text-xs font-bold px-1.5 py-0.5 rounded-full
                                 {{ $isActive ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300' : 'bg-gray-200 dark:bg-slate-700 text-gray-500 dark:text-slate-400' }}">
                        {{ number_format($tabCounts[$value] ?? 0) }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="admin-hairline-grid grid-cols-2 sm:grid-cols-4">
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-blue-600 dark:text-blue-400">{{ __('messages.repair_stat_total') }}</div>
            <div class="admin-stat-value">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">{{ __('messages.repair_stat_active') }}</div>
            <div class="admin-stat-value">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-violet-600 dark:text-violet-400">{{ __('messages.repair_stat_ready') }}</div>
            <div class="admin-stat-value">{{ number_format($stats['ready']) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-amber-600 dark:text-amber-400">{{ __('messages.repair_stat_debt') }}</div>
            <div class="admin-stat-value">{{ number_format($stats['debt'], 0) }}</div>
        </div>
    </div>

    {{-- Success Flash --}}
    @if (session('success'))
        <div class="p-3.5 sm:p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-start gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300">
            @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Toolbar (view toggle included — cards default, matching Repairs Center) --}}
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

    {{-- Card grid view (default — Repairs Center parity) --}}
    <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse ($jobs as $job)
            <div data-href="{{ $detailUrls[$job->id] }}" onclick="window.location.href = this.dataset.href"
                 class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden cursor-pointer group">
                {{-- Device visual + status pill --}}
                <div class="relative h-32 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-900 dark:to-slate-800/60 flex items-center justify-center">
                    @php $icon = $deviceIcon($job->device_type, $job->model ?? ''); @endphp
                    @if ($icon === 'phone')
                        <svg class="w-14 h-14 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    @elseif ($icon === 'tablet')
                        <svg class="w-14 h-14 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v10m0 0H8m4 0h4m-8 3h8a2 2 0 002-2V6a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    @elseif ($icon === 'laptop')
                        <svg class="w-14 h-14 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    @elseif ($icon === 'watch')
                        <svg class="w-14 h-14 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @elseif ($icon === 'tv')
                        <svg class="w-14 h-14 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    @elseif ($icon === 'router')
                        <svg class="w-14 h-14 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.07c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                    @elseif ($icon === 'camera')
                        <svg class="w-14 h-14 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    @endif
                    <span class="absolute top-3 left-3 text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-white/80 dark:bg-slate-800/80 text-gray-500 dark:text-slate-400 border dark:border-slate-600">{{ $job->job_number }}</span>
                    <span class="absolute top-3 right-3 inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold rounded-full whitespace-nowrap {{ $statusColors[$job->status] ?? 'bg-gray-100 text-gray-500' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $statusDots[$job->status] ?? 'bg-gray-400' }}"></span>
                        {{ __('messages.repair_status_' . $job->status) }}
                    </span>
                </div>

                {{-- Info --}}
                <div class="p-4 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-bold text-sm text-gray-900 dark:text-slate-100 truncate">{{ $job->contact_name ?: ($job->customer?->name ?? '—') }}</div>
                            <div class="text-xs text-gray-400 dark:text-slate-500 truncate">{{ $job->device_type }} {{ $job->model ? '· ' . $job->model : '' }}</div>
                        </div>
                        @if ($job->outstanding() > 0)
                            <span class="text-xs font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">{{ number_format($job->outstanding(), 0) }}</span>
                        @endif
                    </div>

                    <div class="pt-2.5 border-t dark:border-slate-700 flex items-center justify-between gap-2">
                        <span class="text-xs text-gray-400 dark:text-slate-500">{{ $job->created_at->format('M d, Y') }}</span>
                        <div class="flex items-center gap-1">
                            @if (! $job->isTerminal())
                                <button type="button"
                                        onclick="event.stopPropagation();"
                                        @click="openQuickStatus({{ $job->id }}, '{{ $job->job_number }}', '{{ $job->status }}')"
                                        class="inline-flex items-center justify-center p-2 rounded-lg text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/60 hover:bg-violet-100 dark:hover:bg-violet-900/50 transition" title="{{ __('messages.repair_quick_status') }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                </button>
                                <a href="{{ route('store.admin.repairs.edit', [...$storeRouteParams, 'repair' => $job->id]) }}" onclick="event.stopPropagation();"
                                   class="inline-flex items-center justify-center p-2 rounded-lg text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/60 hover:bg-sky-100 dark:hover:bg-sky-900/50 transition" title="{{ __('messages.repair_edit') }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </a>
                            @endif
                            <a href="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $job->id]) }}" target="_blank" onclick="event.stopPropagation();"
                               class="inline-flex items-center justify-center p-2 rounded-lg text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition" title="{{ __('messages.repair_print_ticket') }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2z" /></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-800 p-8 rounded-xl text-center">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                </svg>
                <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">{{ __('messages.repair_empty') }}</div>
                <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_empty_hint') }}</div>
            </div>
        @endforelse
    </div>

    {{-- Desktop table view (opt-in via view toggle) --}}
    <div x-show="viewMode === 'table'" x-cloak class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden transition-colors duration-200">
        <div class="admin-panel overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm text-gray-600 dark:text-slate-300">
                <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-semibold text-gray-700 dark:text-slate-200">
                    <tr>
                        <th class="p-3 whitespace-nowrap">{{ __('messages.repair_job_number') }}</th>
                        <th class="p-3 whitespace-nowrap">{{ __('messages.repair_device') }}</th>
                        <th class="p-3 whitespace-nowrap">{{ __('messages.repair_customer_label') }}</th>
                        <th class="p-3 whitespace-nowrap">{{ __('messages.repair_technician') }}</th>
                        <th class="p-3 whitespace-nowrap">{{ __('messages.status') }}</th>
                        <th class="p-3 whitespace-nowrap">{{ __('messages.repair_received_at') }}</th>
                        <th class="p-3 whitespace-nowrap text-right">{{ __('messages.repair_outstanding') }}</th>
                        <th class="p-3 whitespace-nowrap text-right">{{ __('messages.repair_quick_status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700">
                    @forelse ($jobs as $job)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-700/50 transition">
                            <td class="p-3">
                                <a href="{{ route('store.admin.repairs.show', [...$storeRouteParams, 'repair' => $job->id]) }}" class="font-mono font-bold text-gray-900 dark:text-slate-100 hover:text-violet-600 dark:hover:text-violet-400">{{ $job->job_number }}</a>
                            </td>
                            <td class="p-3">
                                <div class="font-medium text-gray-900 dark:text-slate-100">{{ $job->device_type }}</div>
                                @if ($job->model)
                                    <div class="text-xs text-gray-400 dark:text-slate-500">{{ $job->model }}</div>
                                @endif
                            </td>
                            <td class="p-3">
                                <div class="text-gray-700 dark:text-slate-200">{{ $job->contact_name ?: ($job->customer?->name ?? '—') }}</div>
                                @if ($job->contact_phone)
                                    <div class="text-xs text-gray-400 dark:text-slate-500">{{ $job->contact_phone }}</div>
                                @endif
                            </td>
                            <td class="p-3 text-xs text-gray-500 dark:text-slate-400">{{ $job->technician?->name ?? '—' }}</td>
                            <td class="p-3">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full whitespace-nowrap {{ $statusColors[$job->status] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ __('messages.repair_status_' . $job->status) }}
                                </span>
                            </td>
                            <td class="p-3 text-xs text-gray-500 dark:text-slate-400 whitespace-nowrap">{{ $job->created_at->format('M d, Y') }}</td>
                            <td class="p-3 text-right whitespace-nowrap">
                                @if ($job->outstanding() > 0)
                                    <span class="text-amber-600 dark:text-amber-400 font-semibold">{{ number_format($job->outstanding(), 0) }} MMK</span>
                                @else
                                    <span class="text-gray-300 dark:text-slate-600">—</span>
                                @endif
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                @if (! $job->isTerminal())
                                    <button type="button" @click="openQuickStatus({{ $job->id }}, '{{ $job->job_number }}', '{{ $job->status }}')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/60 border border-violet-200 dark:border-violet-800 rounded-lg hover:bg-violet-100 dark:hover:bg-violet-900/50 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        {{ __('messages.repair_change_status') }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                                </svg>
                                <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">{{ __('messages.repair_empty') }}</div>
                                <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_empty_hint') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if (method_exists($jobs, 'links'))
        <div class="text-sm">{{ $jobs->links() }}</div>
    @endif

    {{-- Quick status modal (Repairs Center status picker parity) --}}
    <div x-cloak x-show="open" x-transition.opacity
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm p-0 sm:p-4"
         @keydown.escape.window="closeModal()" @click.self="closeModal()">
        <div class="w-full sm:max-w-lg bg-white dark:bg-slate-800 rounded-t-2xl sm:rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3 mb-1">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ __('messages.repair_quick_status') }}</h2>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                            <span class="font-mono font-semibold" x-text="jobNumber"></span> · <span x-text="currentStatus"></span>
                        </p>
                    </div>
                    <button type="button" @click="closeModal()" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-700 transition" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-4">{{ __('messages.repair_quick_status_hint') }}</p>

                <form method="POST" :action="statusUrl" class="space-y-4">
                    @csrf
                    <div class="flex flex-wrap gap-2">
                        <template x-for="st in statuses" :key="st.value">
                            <button type="button"
                                @click="selected = st.value"
                                :disabled="st.value === currentStatus"
                                class="px-3 py-1.5 text-xs font-semibold rounded-full border transition disabled:opacity-40 disabled:cursor-not-allowed"
                                :class="selected === st.value
                                    ? 'bg-violet-600 text-white border-violet-600 shadow'
                                    : 'bg-white dark:bg-slate-900 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700'">
                                <span x-text="st.label"></span>
                            </button>
                        </template>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_status_note') }}</label>
                        <input type="text" name="note" x-model="note" maxlength="500"
                               placeholder="{{ __('messages.repair_status_note_placeholder') }}"
                               class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-1">
                        <button type="button" @click="closeModal()"
                            class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-slate-300 hover:text-gray-800 dark:hover:text-white transition">
                            {{ __('messages.cancel') }}
                        </button>
                        <button type="submit" :disabled="!selected"
                            class="px-5 py-2.5 text-sm font-semibold text-white bg-violet-600 hover:bg-violet-700 rounded-lg shadow transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ __('messages.repair_apply') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
