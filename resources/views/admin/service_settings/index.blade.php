@extends('layouts.admin.app')

@section('title', __('messages.sidebar_service_settings') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    /** @var string $tab */
    /** @var array<string, \Illuminate\Support\Collection> $grouped */
    $tabIcons = [
        'brand'     => '🏢',
        'category'  => '📁',
        'model'     => '📱',
        'color'     => '🎨',
        'storage'   => '💾',
        'defect'    => '⚡',
        'accessory' => '📦',
        'status'    => '🏷️',
    ];
    $tabLabels = [
        'brand'     => __('messages.repair_brands'),
        'category'  => __('messages.repair_categories'),
        'model'     => __('messages.repair_models'),
        'color'     => __('messages.repair_colors'),
        'storage'   => __('messages.repair_storage'),
        'defect'    => __('messages.repair_defects'),
        'accessory' => __('messages.repair_accessories_tab'),
        'status'    => __('messages.repair_statuses'),
    ];
@endphp

<div class="w-full space-y-0.5 pb-6"
     x-data="{
        createOpen: false,
        editOpen: false,
        importOpen: false,
        exportOpen: false,
        importScope: 'tab',
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
        editItem: { id: null, type: '', name: '', code: '', description: '', sort_order: 0, is_active: true, parent_id: null },
        openEdit(item) {
            this.editItem = {
                id: item.id,
                type: item.type,
                name: item.name,
                code: item.code || '',
                description: item.description || '',
                sort_order: item.sort_order || 0,
                is_active: !!item.is_active,
                parent_id: item.parent_id || null
            };
            this.editOpen = true;
        }
     }"
     @open-import-modal.window="importOpen = true"
     @open-export-modal.window="exportOpen = true"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)"
     @keydown.escape.window="if (createOpen) createOpen = false; else if (editOpen) editOpen = false; else if (importOpen) importOpen = false; else if (exportOpen) exportOpen = false;">

    {{-- ============================================================
         1. TOP PAGE HEADER — Ultra-Dense 36px Rhythm
         ============================================================ --}}
    <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none transition">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('store.admin.repairs.index', $storeRouteParams) }}"
               class="w-7 h-7 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition grid place-items-center shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <span class="w-7 h-7 rounded bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 border border-violet-200 dark:border-violet-800 grid place-items-center text-sm font-black shrink-0">
                ⚙️
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 tracking-tight truncate">
                        {{ __('messages.sidebar_service_settings') }}
                    </h1>
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        {{ $store->name }}
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 font-mono truncate hidden sm:block">
                    {{ __('messages.service_settings_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap shrink-0">
            <button type="button" @click="exportOpen = true"
                    class="h-7 px-2.5 rounded text-xs font-bold text-violet-700 dark:text-violet-300 bg-violet-50 hover:bg-violet-100 dark:bg-violet-950/60 dark:hover:bg-violet-900/60 border border-violet-200 dark:border-violet-800 transition inline-flex items-center gap-1 cursor-pointer shadow-2xs active:scale-95">
                <span>📤</span>
                <span>{{ __('messages.export') }}</span>
            </button>
            <button type="button" @click="importOpen = true"
                    class="h-7 px-2.5 rounded text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition inline-flex items-center gap-1 cursor-pointer shadow-2xs active:scale-95">
                <span>📥</span>
                <span>{{ __('messages.import') }}</span>
            </button>
            <button type="button" @click="createOpen = true"
                    class="h-7 px-2.5 rounded text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.add_new') }}</span>
            </button>
        </div>
    </div>

    {{-- Flash Notifications & Errors --}}
    @if (session('success'))
        <div class="w-full px-2.5 py-1.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="w-full px-2.5 py-1.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded text-xs text-rose-800 dark:text-rose-300 space-y-0.5 shadow-2xs">
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
         2. COMPACT CENTERED STAT CARDS (4 Columns)
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1 select-none">
        {{-- Card 1: Tab Total --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center text-sm font-black shrink-0">
                {{ $tabIcons[$tab] ?? '📋' }}
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none">
                    {{ $tabLabels[$tab] ?? $types[$tab] }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-slate-900 dark:text-slate-100 tabular-nums mt-0.5">
                    {{ number_format($stats['tab_total'] ?? 0) }}
                </div>
            </div>
        </div>

        {{-- Card 2: Active in Tab --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-emerald-200/90 dark:border-emerald-900/50 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm font-black shrink-0">
                🟢
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 leading-none">
                    {{ __('messages.service_settings_active_count') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-emerald-600 dark:text-emerald-400 tabular-nums mt-0.5">
                    {{ number_format($stats['tab_active'] ?? 0) }}
                </div>
            </div>
        </div>

        {{-- Card 3: Inactive in Tab --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 flex items-center justify-center text-sm font-black shrink-0">
                ⚪
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none">
                    {{ __('messages.service_settings_inactive_count') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-slate-700 dark:text-slate-300 tabular-nums mt-0.5">
                    {{ number_format($stats['tab_inactive'] ?? 0) }}
                </div>
            </div>
        </div>

        {{-- Card 4: All Masters Total --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 flex items-center justify-center text-sm font-black shrink-0">
                📚
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none">
                    {{ __('messages.service_settings_total_all') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-slate-900 dark:text-slate-100 tabular-nums mt-0.5">
                    {{ number_format($stats['all_total'] ?? 0) }}
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. SEGMENTED HORIZONTAL TABS (All 8 Master Data Categories)
         ============================================================ --}}
    <div class="w-full overflow-x-auto scrollbar-none sm:scrollbar-thin pb-0.5 select-none">
        <div class="inline-flex items-center gap-1 p-0.5 sm:p-1 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs">
            @foreach ($types as $key => $typeTitle)
                @php $isActive = $tab === $key; @endphp
                <a href="{{ route('store.admin.service_settings.index', [...$storeRouteParams, 'tab' => $key]) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1 sm:px-3 sm:py-1 rounded text-xs font-bold whitespace-nowrap transition cursor-pointer
                          {{ $isActive
                              ? 'bg-violet-600 text-white shadow-2xs'
                              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span>{{ $tabIcons[$key] ?? '•' }}</span>
                    <span>{{ $tabLabels[$key] ?? $typeTitle }}</span>
                    <span class="text-[10px] font-black px-1.5 py-0.2 rounded-full
                                 {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }}">
                        {{ count($grouped[$key] ?? []) }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ============================================================
         4. UNIFIED ADMIN TOOLBAR (Search, Filters, Sort, Excel, Table/Card)
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', $search)"
        :searchPlaceholder="__('messages.search') . ' ' . ($tabLabels[$tab] ?? '') . '...'"
        :sort="request('sort', $sort)"
        :sortOptions="[
            'newest' => __('messages.newest') ?? 'Newest',
            'oldest' => __('messages.oldest') ?? 'Oldest',
            'name_asc' => __('messages.name') . ' (A-Z)',
            'name_desc' => __('messages.name') . ' (Z-A)',
        ]"
        :filters="[
            'status' => [
                'label' => __('messages.status'),
                'options' => [
                    'active' => '✓ ' . __('messages.active'),
                    'inactive' => '✕ ' . __('messages.inactive'),
                ],
            ],
        ]"
        :viewMode="'table'"
        :showViewToggle="true"
        :showExportImport="false"
        :totalCount="count($grouped[$tab] ?? [])"
    />

    {{-- ============================================================
         5. SPREADSHEET TABLE VIEW
         ============================================================ --}}
    <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto max-h-[72vh] overflow-y-auto">
            <table class="w-full min-w-[700px] text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b border-slate-200 dark:border-slate-700 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">
                        <th class="py-2 px-2.5 w-14 text-center">#</th>
                        <th class="py-2 px-2.5">{{ __('messages.name') }}</th>
                        @if ($tab === 'model')
                            <th class="py-2 px-2.5">{{ __('messages.parent_brand') }}</th>
                        @else
                            <th class="py-2 px-2.5">{{ __('messages.code') }}</th>
                        @endif
                        <th class="py-2 px-2.5">{{ __('messages.description') }}</th>
                        <th class="py-2 px-2.5 text-center w-28">{{ __('messages.status') }}</th>
                        <th class="py-2 px-2.5 text-right w-32">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse ($grouped[$tab] ?? [] as $index => $item)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition group">
                            <td class="py-2 px-2.5 text-center text-slate-400 font-mono text-xs">
                                {{ $item->sort_order ?: ($index + 1) }}
                            </td>
                            <td class="py-2 px-2.5">
                                <span class="font-bold text-slate-900 dark:text-slate-100 block">{{ $item->name }}</span>
                            </td>
                            <td class="py-2 px-2.5 text-slate-500 dark:text-slate-400 font-mono text-xs">
                                @if ($tab === 'model' && $item->parent)
                                    <span class="px-2 py-0.5 rounded bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border border-violet-200/60 dark:border-violet-800 font-semibold text-[11px]">
                                        🏢 {{ $item->parent->name }}
                                    </span>
                                @else
                                    {{ $item->code ?: '—' }}
                                @endif
                            </td>
                            <td class="py-2 px-2.5 text-slate-500 dark:text-slate-400 text-xs truncate max-w-xs">
                                {{ $item->description ?: '—' }}
                            </td>
                            <td class="py-2 px-2.5 text-center whitespace-nowrap">
                                @if ($item->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-900">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> {{ __('messages.active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700">
                                        {{ __('messages.inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-2 px-2.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @click="openEdit({{ $item->toJson() }})"
                                            class="px-2 py-1 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-800 hover:bg-violet-50 hover:text-violet-600 dark:hover:bg-violet-950/60 dark:hover:text-violet-300 transition cursor-pointer">
                                        ✏️ {{ __('messages.edit') }}
                                    </button>
                                    <form method="POST" action="{{ route('store.admin.service_settings.destroy', [...$storeRouteParams, 'service_setting' => $item->id]) }}"
                                          onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2 py-1 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-800 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/60 dark:hover:text-rose-400 transition cursor-pointer"
                                                title="{{ __('messages.delete') }}">
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 grid place-items-center mx-auto mb-1.5 text-lg">
                                    {{ $tabIcons[$tab] ?? '📂' }}
                                </div>
                                <p class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('messages.no_records_found') }}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ __('messages.service_settings_add_prompt') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================
         6. RESPONSIVE MULTI-COLUMN CARDS VIEW (Fully Mobile-Friendly)
         ============================================================ --}}
    <div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-1 sm:gap-1.5">
        @forelse ($grouped[$tab] ?? [] as $index => $item)
            <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded p-2.5 shadow-2xs hover:border-violet-300 dark:hover:border-violet-700 hover:shadow-xs transition flex flex-col justify-between group">
                <div class="space-y-1.5">
                    {{-- Card Header: Icon + Name + Status Pill --}}
                    <div class="flex items-start justify-between gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="text-sm shrink-0">{{ $tabIcons[$tab] ?? '•' }}</span>
                            <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 line-clamp-1" title="{{ $item->name }}">
                                {{ $item->name }}
                            </h4>
                        </div>
                        <div class="shrink-0">
                            @if ($item->is_active)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[9px] font-black bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-900">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> {{ __('messages.active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700">
                                    {{ __('messages.inactive') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Code or Parent Brand --}}
                    <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400 flex-wrap">
                        @if ($tab === 'model' && $item->parent)
                            <span class="px-1.5 py-0.2 rounded bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border border-violet-200/60 dark:border-violet-800 text-[10px] font-semibold">
                                🏢 {{ $item->parent->name }}
                            </span>
                        @elseif ($item->code)
                            <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono text-[10px]">
                                #{{ $item->code }}
                            </span>
                        @endif

                        @if ($item->sort_order)
                            <span class="text-[10px] text-slate-400 font-mono">
                                {{ __('messages.sort_order') }}: {{ $item->sort_order }}
                            </span>
                        @endif
                    </div>

                    {{-- Description --}}
                    @if ($item->description)
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 leading-snug">
                            {{ $item->description }}
                        </p>
                    @endif
                </div>

                {{-- Card Actions Footer --}}
                <div class="mt-2 pt-1.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1">
                    <button type="button" @click="openEdit({{ $item->toJson() }})"
                            class="px-2 py-1 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-violet-50 hover:text-violet-600 dark:hover:bg-violet-950/60 dark:hover:text-violet-300 transition flex items-center gap-1 cursor-pointer">
                        <span>✏️</span>
                        <span>{{ __('messages.edit') }}</span>
                    </button>
                    <form method="POST" action="{{ route('store.admin.service_settings.destroy', [...$storeRouteParams, 'service_setting' => $item->id]) }}"
                          onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-2 py-1 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/60 dark:hover:text-rose-400 transition cursor-pointer"
                                title="{{ __('messages.delete') }}">
                            <span>✕</span>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full p-8 text-center bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded">
                <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 grid place-items-center mx-auto mb-1.5 text-lg">
                    {{ $tabIcons[$tab] ?? '📂' }}
                </div>
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('messages.no_records_found') }}</p>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ __('messages.service_settings_add_prompt') }}</p>
            </div>
        @endforelse
    </div>

    {{-- ============================================================
         7. CREATE ITEM MODAL
         ============================================================ --}}
    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @click.self="createOpen = false">
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-2xl space-y-3 text-slate-900 dark:text-slate-100">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>{{ $tabIcons[$tab] ?? '•' }}</span>
                    <span>+ {{ __('messages.add_new') }} ({{ $tabLabels[$tab] ?? $types[$tab] }})</span>
                </h3>
                <button type="button" @click="createOpen = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('store.admin.service_settings.store', $storeRouteParams) }}" class="space-y-3 text-xs">
                @csrf
                <input type="hidden" name="type" value="{{ $tab }}">

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required maxlength="120"
                           placeholder="e.g. {{ $tab === 'brand' ? 'Apple, Samsung, Dell, Dahua' : ($tab === 'color' ? 'Black, Gold, Sierra Blue' : ($tab === 'storage' ? '128 GB, 256 GB, 1 TB' : 'Name')) }}"
                           class="w-full px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>

                @if ($tab === 'model')
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.parent_brand') }}</label>
                        <select name="parent_id" class="w-full px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="">-- {{ __('messages.select_parent_brand') }} --</option>
                            @foreach ($brands as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.code') }}</label>
                        <input type="text" name="code" maxlength="60"
                               placeholder="e.g. short code"
                               class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.sort_order') }}</label>
                        <input type="number" name="sort_order" value="0" min="0" step="1"
                               class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div class="flex flex-col justify-end">
                        <label class="inline-flex items-center gap-2 cursor-pointer pb-2 select-none">
                            <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.active') }}</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.description') }}</label>
                    <textarea name="description" rows="2" maxlength="500"
                              class="w-full px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500 resize-none"></textarea>
                </div>

                <div class="flex gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="createOpen = false" class="flex-1 py-2 rounded-lg font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition cursor-pointer">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="flex-1 py-2 rounded-lg font-black text-xs bg-violet-600 hover:bg-violet-500 text-white shadow-2xs transition cursor-pointer active:scale-95">+ {{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         8. EDIT ITEM MODAL
         ============================================================ --}}
    <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @click.self="editOpen = false">
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-2xl space-y-3 text-slate-900 dark:text-slate-100">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>✏️</span>
                    <span>{{ __('messages.edit') }} ({{ $tabLabels[$tab] ?? $types[$tab] }})</span>
                </h3>
                <button type="button" @click="editOpen = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
            </div>

            <form method="POST" :action="'{{ url('/store/' . $store->slug . '/admin/service-settings') }}/' + editItem.id" class="space-y-3 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="editItem.name" required maxlength="120"
                           class="w-full px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>

                @if ($tab === 'model')
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.parent_brand') }}</label>
                        <select name="parent_id" x-model="editItem.parent_id" class="w-full px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="">-- {{ __('messages.select_parent_brand') }} --</option>
                            @foreach ($brands as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.code') }}</label>
                        <input type="text" name="code" x-model="editItem.code" maxlength="60"
                               class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.sort_order') }}</label>
                        <input type="number" name="sort_order" x-model="editItem.sort_order" min="0" step="1"
                               class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div class="flex flex-col justify-end">
                        <label class="inline-flex items-center gap-2 cursor-pointer pb-2 select-none">
                            <input type="checkbox" name="is_active" value="1" :checked="editItem.is_active" class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.active') }}</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.description') }}</label>
                    <textarea name="description" x-model="editItem.description" rows="2" maxlength="500"
                              class="w-full px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500 resize-none"></textarea>
                </div>

                <div class="flex gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="editOpen = false" class="flex-1 py-2 rounded-lg font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition cursor-pointer">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="flex-1 py-2 rounded-lg font-black text-xs bg-violet-600 hover:bg-violet-500 text-white shadow-2xs transition cursor-pointer active:scale-95">{{ __('messages.update') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         9. HYBRID EXPORT MODAL (Per-Tab vs Multi-Sheet All-in-One)
         ============================================================ --}}
    <div x-show="exportOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @click.self="exportOpen = false">
        <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-2xl space-y-4 text-slate-900 dark:text-slate-100">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-950/80 text-violet-600 dark:text-violet-400 grid place-items-center text-sm shadow-inner">📤</span>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">
                            {{ __('messages.service_settings_export_title') }}
                        </h3>
                        <p class="text-[11px] text-slate-400">
                            {{ $store->name }} · {{ __('messages.sidebar_service_settings') }}
                        </p>
                    </div>
                </div>
                <button type="button" @click="exportOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm font-bold cursor-pointer">✕</button>
            </div>

            <div class="space-y-3 text-xs">
                {{-- Option 1: Current Tab Only --}}
                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-800/50 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5 font-black text-slate-900 dark:text-slate-100">
                            <span>{{ $tabIcons[$tab] ?? '📋' }}</span>
                            <span>{{ __('messages.service_settings_export_scope_current') }}</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300 font-bold">
                                {{ $tabLabels[$tab] ?? $types[$tab] }} ({{ count($grouped[$tab] ?? []) }})
                            </span>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        {{ __('messages.service_settings_export_current_desc') }}
                    </p>
                    <div class="flex gap-2 pt-1">
                        <a href="{{ route('store.admin.service_settings.export', array_merge($storeRouteParams, ['tab' => $tab, 'scope' => 'tab', 'format' => 'xlsx', 'search' => $search])) }}"
                           @click="exportOpen = false"
                           class="flex-1 py-2 px-3 rounded-lg font-bold text-xs bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs text-center transition inline-flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer">
                            <span>📊</span>
                            <span>Excel (.xlsx)</span>
                        </a>
                        <a href="{{ route('store.admin.service_settings.export', array_merge($storeRouteParams, ['tab' => $tab, 'scope' => 'tab', 'format' => 'csv', 'search' => $search])) }}"
                           @click="exportOpen = false"
                           class="flex-1 py-2 px-3 rounded-lg font-bold text-xs bg-sky-600 hover:bg-sky-500 text-white shadow-2xs text-center transition inline-flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer">
                            <span>📄</span>
                            <span>CSV (.csv)</span>
                        </a>
                    </div>
                </div>

                {{-- Option 2: All 8 Categories (Multi-Sheet All-in-One) --}}
                <div class="p-3 rounded-xl border border-violet-300 dark:border-violet-700/80 bg-violet-50/40 dark:bg-violet-950/20 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5 font-black text-slate-900 dark:text-slate-100">
                            <span>📑</span>
                            <span>{{ __('messages.service_settings_export_scope_all') }}</span>
                        </div>
                        <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-violet-100 dark:bg-violet-900 text-violet-800 dark:text-violet-200">
                            ⭐ {{ __('messages.export_recommended') }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                        {{ __('messages.service_settings_export_all_desc') }} ({{ number_format($stats['all_total'] ?? 0) }} ခု)
                    </p>
                    <div class="flex gap-2 pt-1">
                        <a href="{{ route('store.admin.service_settings.export', array_merge($storeRouteParams, ['scope' => 'all', 'format' => 'xlsx', 'search' => $search])) }}"
                           @click="exportOpen = false"
                           class="flex-1 py-2 px-3 rounded-lg font-black text-xs bg-violet-600 hover:bg-violet-500 text-white shadow-2xs text-center transition inline-flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer">
                            <span>📊</span>
                            <span>Multi-Sheet Excel (.xlsx)</span>
                        </a>
                        <a href="{{ route('store.admin.service_settings.export', array_merge($storeRouteParams, ['scope' => 'all', 'format' => 'csv', 'search' => $search])) }}"
                           @click="exportOpen = false"
                           class="flex-1 py-2 px-3 rounded-lg font-bold text-xs bg-slate-700 hover:bg-slate-600 text-white shadow-2xs text-center transition inline-flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer">
                            <span>📄</span>
                            <span>Consolidated CSV (.csv)</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                <button type="button" @click="exportOpen = false"
                        class="px-4 py-1.5 rounded-lg font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition cursor-pointer">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
         10. HYBRID IMPORT MODAL (Current Tab vs Multi-Sheet / All Types)
         ============================================================ --}}
    <div x-show="importOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @click.self="importOpen = false">
        <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-2xl space-y-3.5 text-slate-900 dark:text-slate-100">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-950/80 text-violet-600 dark:text-violet-400 grid place-items-center text-sm shadow-inner">📥</span>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">
                            {{ __('messages.service_settings_import_title') }}
                        </h3>
                        <p class="text-[11px] text-slate-400">
                            {{ $store->name }} · {{ __('messages.sidebar_service_settings') }}
                        </p>
                    </div>
                </div>
                <button type="button" @click="importOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm font-bold cursor-pointer">✕</button>
            </div>

            <form method="POST" action="{{ route('store.admin.service_settings.import', $storeRouteParams) }}" enctype="multipart/form-data" class="space-y-3 text-xs">
                @csrf
                <input type="hidden" name="tab" value="{{ $tab }}">

                {{-- Scope Selection --}}
                <div class="space-y-1.5">
                    <label class="block font-bold text-slate-600 dark:text-slate-400">{{ __('messages.service_settings_import_scope') }}:</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label class="flex items-start gap-2 p-2.5 rounded-xl border cursor-pointer transition select-none"
                               :class="importScope === 'tab' ? 'border-violet-500 bg-violet-50/50 dark:bg-violet-950/30 font-bold' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60'">
                            <input type="radio" name="scope" value="tab" x-model="importScope" class="mt-0.5 text-violet-600">
                            <div class="min-w-0">
                                <div class="text-xs text-slate-900 dark:text-slate-100 flex items-center gap-1">
                                    <span>{{ $tabIcons[$tab] ?? '📋' }}</span>
                                    <span>{{ __('messages.service_settings_import_current_tab') }}</span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-0.5">({{ $tabLabels[$tab] ?? $types[$tab] }})</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-2 p-2.5 rounded-xl border cursor-pointer transition select-none"
                               :class="importScope === 'all' ? 'border-violet-500 bg-violet-50/50 dark:bg-violet-950/30 font-bold' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60'">
                            <input type="radio" name="scope" value="all" x-model="importScope" class="mt-0.5 text-violet-600">
                            <div class="min-w-0">
                                <div class="text-xs text-slate-900 dark:text-slate-100 flex items-center gap-1">
                                    <span>📑</span>
                                    <span>{{ __('messages.service_settings_import_all_tabs') }}</span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-0.5">Multi-Sheet / Type</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Download Template Helper Box --}}
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/80 dark:border-slate-700/60 space-y-2">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                        {{ __('messages.service_settings_import_help') }}
                    </p>
                    <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-[10px] font-bold text-slate-400">📥 {{ __('messages.download_template') }}:</span>
                        <a href="{{ route('store.admin.service_settings.template', [...$storeRouteParams, 'tab' => $tab, 'format' => 'csv']) }}"
                           class="inline-flex items-center gap-1 px-2 py-1 rounded bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-[11px] font-bold text-violet-600 dark:text-violet-300 hover:underline">
                            <span>📄</span>
                            <span>{{ __('messages.service_settings_template_current') }}</span>
                        </a>
                        <a href="{{ route('store.admin.service_settings.template', [...$storeRouteParams, 'scope' => 'all', 'format' => 'xlsx']) }}"
                           class="inline-flex items-center gap-1 px-2 py-1 rounded bg-violet-100 dark:bg-violet-900/60 border border-violet-200 dark:border-violet-700 text-[11px] font-bold text-violet-800 dark:text-violet-200 hover:underline">
                            <span>📊</span>
                            <span>{{ __('messages.service_settings_template_all_xlsx') }}</span>
                        </a>
                    </div>
                </div>

                {{-- File Input --}}
                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1.5">{{ __('messages.xlsx_or_csv_file') }} <span class="text-rose-500">*</span></label>
                    <input type="file" name="file" required accept=".xlsx,.xls,.csv,.txt"
                           class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-violet-950/60 dark:file:text-violet-300 cursor-pointer">
                    <p class="text-[10px] text-slate-400 mt-1">
                        💡 .xlsx (Excel) သို့မဟုတ် .csv ဖိုင်တင်နိုင်ပါသည်။ Multi-sheet Excel ဖြစ်ပါက Sheet အမည်အလိုက် အလိုအလျောက် သွင်းယူပေးပါမည်။
                    </p>
                </div>

                <div class="flex gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="importOpen = false" class="flex-1 py-2 rounded-lg font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition cursor-pointer">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="flex-1 py-2 rounded-lg font-black text-xs bg-violet-600 hover:bg-violet-500 text-white shadow-2xs transition cursor-pointer active:scale-95">📥 {{ __('messages.import') }}</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
