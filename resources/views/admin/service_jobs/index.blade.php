@extends('layouts.admin.app')

@section('content')
@php
    $statusColors = [
        'received'          => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
        'diagnosing'        => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
        'awaiting_approval' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
        'awaiting_parts'    => 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300',
        'in_repair'         => 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300',
        'ready'             => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
        'delivered'         => 'bg-gray-100 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400',
        'cancelled'         => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
        'unrepairable'      => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
    ];
    $statusDots = [
        'received'          => 'bg-blue-500',
        'diagnosing'        => 'bg-amber-500',
        'awaiting_approval' => 'bg-amber-500',
        'awaiting_parts'    => 'bg-purple-500',
        'in_repair'         => 'bg-orange-500',
        'ready'             => 'bg-emerald-500',
        'delivered'         => 'bg-gray-400',
        'cancelled'         => 'bg-red-500',
        'unrepairable'      => 'bg-red-500',
    ];
    $statusOptions = collect(\App\POS\Models\ServiceJob::STATUSES)
        ->map(fn ($s) => ['value' => $s, 'label' => __('messages.repair_status_' . $s)])
        ->values()
        ->toJson();
    $tabParams = request()->except(['tab', 'page']);
    $exportUrl = route('store.admin.service_jobs.export', [...$storeRouteParams, ...request()->except(['page'])]);
    $detailUrls = $jobs->mapWithKeys(fn ($job) => [
        $job->id => route('store.admin.service_jobs.show', [...$storeRouteParams, 'job' => $job->id]),
    ]);

    // Device icon helper
    $deviceIcon = function (string $type, string $model): string {
        $s = strtolower($type . ' ' . $model);
        return match (true) {
            str_contains($s, 'cctv'), str_contains($s, 'camera'), str_contains($s, 'nvr'), str_contains($s, 'dvr') => 'camera',
            str_contains($s, 'router'), str_contains($s, 'switch'), str_contains($s, 'access point'), str_contains($s, 'wifi'), str_contains($s, 'network') => 'router',
            str_contains($s, 'laptop'), str_contains($s, 'macbook') => 'laptop',
            str_contains($s, 'desktop'), str_contains($s, 'pc'), str_contains($s, 'computer') => 'desktop',
            str_contains($s, 'tablet'), str_contains($s, 'ipad') => 'tablet',
            str_contains($s, 'watch') => 'watch',
            str_contains($s, 'printer'), str_contains($s, 'scanner') => 'printer',
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
            this.statusUrl = '{{ route('store.admin.service_jobs.status', [...$storeRouteParams, 'job' => '__ID__']) }}'.replace('__ID__', id);
        },
        closeModal() { this.open = false; }
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

    {{-- Header --}}
    <div class="admin-page-header">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <div class="w-11 h-11 rounded-xl bg-teal-100 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center flex-shrink-0">
                    {{-- Wrench/cog icon for Service Jobs --}}
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="admin-page-title">{{ __('messages.sidebar_service_jobs') }}</h1>
                    <p class="admin-page-sub">{{ $store->name }} · {{ number_format($totalCount) }} {{ __('messages.repair_jobs') }}</p>
                </div>
            </div>
        </div>
        <a href="{{ route('store.admin.service_jobs.create', $storeRouteParams) }}"
           class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 rounded-lg shadow transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('messages.repair_new_job') }}
        </a>
    </div>

    {{-- Tabs: All / Processing / Ready / History --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-thin -mx-1 px-1">
        <div class="inline-flex items-center gap-1 p-1 bg-gray-100 dark:bg-slate-900 rounded-xl border dark:border-slate-700">
            @foreach ([
                'all'        => __('messages.repair_tab_all'),
                'processing' => __('messages.repair_tab_processing'),
                'ready'      => __('messages.repair_tab_ready'),
                'history'    => __('messages.repair_tab_history'),
            ] as $value => $label)
                @php
                    $isActive = $tab === $value;
                    $url = $value === 'all'
                        ? route('store.admin.service_jobs.index', [...$storeRouteParams, ...$tabParams])
                        : route('store.admin.service_jobs.index', [...$storeRouteParams, ...$tabParams, 'tab' => $value]);
                @endphp
                <a href="{{ $url }}"
                   class="inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition
                          {{ $isActive
                              ? 'bg-white dark:bg-slate-700 text-teal-700 dark:text-teal-300 shadow-sm'
                              : 'text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-100' }}">
                    {{ $label }}
                    <span class="text-xs font-bold px-1.5 py-0.5 rounded-full
                                 {{ $isActive ? 'bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300' : 'bg-gray-200 dark:bg-slate-700 text-gray-500 dark:text-slate-400' }}">
                        {{ number_format($tabCounts[$value] ?? 0) }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Stats --}}
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
            <div class="admin-stat-label text-teal-600 dark:text-teal-400">{{ __('messages.repair_stat_ready') }}</div>
            <div class="admin-stat-value">{{ number_format($stats['ready']) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-amber-600 dark:text-amber-400">{{ __('messages.repair_stat_debt') }}</div>
            <div class="admin-stat-value">{{ number_format($stats['debt'], 0) }}</div>
        </div>
    </div>

    {{-- Flash --}}
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

    {{-- Toolbar --}}
    <x-admin.toolbar
        :search="request('search', '')"
        :searchPlaceholder="__('messages.repair_search_placeholder')"
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest'   => __('messages.repair_sort_newest'),
            'oldest'   => __('messages.repair_sort_oldest'),
            'customer' => __('messages.repair_sort_customer'),
            'status'   => __('messages.repair_sort_status'),
        ]"
        :filters="[
            'status' => [
                'label'   => __('messages.status'),
                'options' => collect(\App\POS\Models\ServiceJob::STATUSES)
                    ->mapWithKeys(fn ($s) => [$s => __('messages.repair_status_' . $s)])
                    ->toArray()
            ],
            'date' => [
                'label' => __('messages.repair_date_range'),
                'type'  => 'date',
            ],
        ]"
        :viewMode="'card'"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$exportUrl"
        :totalCount="$totalCount"
        :paginator="$jobs"
    />

    {{-- Card grid (default) --}}
    <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse ($jobs as $job)
            <div data-href="{{ $detailUrls[$job->id] }}" onclick="window.location.href = this.dataset.href"
                 class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden cursor-pointer group">
                {{-- Visual top area --}}
                <div class="relative h-32 bg-gradient-to-br from-teal-50 to-teal-100 dark:from-teal-950/20 dark:to-slate-800/60 flex items-center justify-center">
                    @php $icon = $deviceIcon($job->device_type, $job->model ?? ''); @endphp
                    @if ($icon === 'camera')
                        <svg class="w-14 h-14 text-teal-300 dark:text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    @elseif ($icon === 'router')
                        <svg class="w-14 h-14 text-teal-300 dark:text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.07c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                    @elseif ($icon === 'desktop')
                        <svg class="w-14 h-14 text-teal-300 dark:text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    @elseif ($icon === 'laptop')
                        <svg class="w-14 h-14 text-teal-300 dark:text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    @elseif ($icon === 'printer')
                        <svg class="w-14 h-14 text-teal-300 dark:text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zM9 9h6V5H9v4z"/></svg>
                    @else
                        <svg class="w-14 h-14 text-teal-300 dark:text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    @endif
                    <span class="absolute top-3 left-3 text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-white/80 dark:bg-slate-800/80 text-gray-500 dark:text-slate-400 border dark:border-slate-600">{{ $job->voucher_no ?? $job->job_number }}</span>
                    <span class="absolute top-3 right-3 inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold rounded-full whitespace-nowrap {{ $statusColors[$job->status] ?? 'bg-gray-100 text-gray-500' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $statusDots[$job->status] ?? 'bg-gray-400' }}"></span>
                        {{ __('messages.repair_status_' . $job->status) }}
                    </span>
                </div>

                <div class="p-4 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-bold text-sm text-gray-900 dark:text-slate-100 truncate">{{ $job->contact_name ?: ($job->customer?->name ?? '—') }}</div>
                            <div class="text-xs text-gray-400 dark:text-slate-500 truncate">{{ $job->category ?? $job->device_type }}{{ $job->model ? ' · ' . $job->model : '' }}</div>
                        </div>
                        @if ($job->outstanding() > 0)
                            <span class="text-xs font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">{{ number_format($job->outstanding(), 0) }}</span>
                        @endif
                    </div>

                    <div class="pt-2.5 border-t dark:border-slate-700 flex items-center justify-between gap-2">
                        <span class="text-xs text-gray-400 dark:text-slate-500">{{ $job->created_at->format('M d, Y') }}</span>
                        <div class="flex items-center gap-1">
                            @if (! $job->isTerminal())
                                <button type="button" onclick="event.stopPropagation();"
                                        @click="openQuickStatus({{ $job->id }}, '{{ $job->voucher_no ?? $job->job_number }}', '{{ $job->status }}')"
                                        class="inline-flex items-center justify-center p-2 rounded-lg text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/60 hover:bg-teal-100 dark:hover:bg-teal-900/50 transition" title="{{ __('messages.repair_quick_status') }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                </button>
                                <a href="{{ route('store.admin.service_jobs.edit', [...$storeRouteParams, 'job' => $job->id]) }}" onclick="event.stopPropagation();"
                                   class="inline-flex items-center justify-center p-2 rounded-lg text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/60 hover:bg-sky-100 dark:hover:bg-sky-900/50 transition" title="{{ __('messages.repair_edit') }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </a>
                            @endif
                            <a href="{{ route('store.admin.service_jobs.print', [...$storeRouteParams, 'job' => $job->id]) }}" target="_blank" onclick="event.stopPropagation();"
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">{{ __('messages.repair_empty') }}</div>
                <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_empty_hint') }}</div>
            </div>
        @endforelse
    </div>

    {{-- Table view --}}
    <div x-show="viewMode === 'table'" x-cloak class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden">
        <div class="admin-panel overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm text-gray-600 dark:text-slate-300">
                <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-semibold text-gray-700 dark:text-slate-200">
                    <tr>
                        <th class="p-3 whitespace-nowrap">{{ __('messages.repair_job_number') }}</th>
                        <th class="p-3 whitespace-nowrap">Voucher #</th>
                        <th class="p-3 whitespace-nowrap">{{ __('messages.repair_device') }}</th>
                        <th class="p-3 whitespace-nowrap">{{ __('messages.repair_customer_label') }}</th>
                        <th class="p-3 whitespace-nowrap">{{ __('messages.repair_technician') }}</th>
                        <th class="p-3 whitespace-nowrap">{{ __('messages.status') }}</th>
                        <th class="p-3 whitespace-nowrap">{{ __('messages.repair_received_at') }}</th>
                        <th class="p-3 whitespace-nowrap text-right">{{ __('messages.repair_outstanding') }}</th>
                        <th class="p-3 whitespace-nowrap text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700">
                    @forelse ($jobs as $job)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-700/50 transition">
                            <td class="p-3">
                                <a href="{{ route('store.admin.service_jobs.show', [...$storeRouteParams, 'job' => $job->id]) }}" class="font-mono font-bold text-gray-900 dark:text-slate-100 hover:text-teal-600 dark:hover:text-teal-400">{{ $job->job_number }}</a>
                            </td>
                            <td class="p-3 text-xs text-gray-500 dark:text-slate-400 font-mono">{{ $job->voucher_no ?? '—' }}</td>
                            <td class="p-3">
                                <div class="font-medium text-gray-900 dark:text-slate-100">{{ $job->category ?? $job->device_type }}</div>
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
                                    <button type="button" @click="openQuickStatus({{ $job->id }}, '{{ $job->voucher_no ?? $job->job_number }}', '{{ $job->status }}')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-950/60 border border-teal-200 dark:border-teal-800 rounded-lg hover:bg-teal-100 dark:hover:bg-teal-900/50 transition">
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
                            <td colspan="9" class="p-8 text-center text-gray-400 dark:text-slate-500 text-sm">{{ __('messages.repair_empty') }}</td>
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

    {{-- Quick Status Modal --}}
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
                    <button type="button" @click="closeModal()" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-700 transition">
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
                                    ? 'bg-teal-600 text-white border-teal-600 shadow'
                                    : 'bg-white dark:bg-slate-900 text-gray-600 dark:text-slate-300 border-gray-200 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700'">
                                <span x-text="st.label"></span>
                            </button>
                        </template>
                    </div>

                    <input type="hidden" name="status" :value="selected">

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_status_note') }}</label>
                        <input type="text" name="note" x-model="note" maxlength="500"
                               placeholder="{{ __('messages.repair_status_note_placeholder') }}"
                               class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-teal-500" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-1">
                        <button type="button" @click="closeModal()"
                            class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-slate-300 hover:text-gray-800 dark:hover:text-white transition">
                            {{ __('messages.cancel') }}
                        </button>
                        <button type="submit" :disabled="!selected"
                            class="px-5 py-2.5 text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 rounded-lg shadow transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ __('messages.repair_apply') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
