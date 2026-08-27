@extends('layouts.admin.app')

@section('title', __('messages.sidebar_service_settings') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

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

<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
        createOpen: false,
        editOpen: false,
        importOpen: false,
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
     @open-import-modal.window="importOpen = true">

    {{-- ============================================================
         1. TOP PAGE HEADER — Eyebrow, Title, Context & Action
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="flex items-center gap-2.5 min-w-0">
            <a href="{{ route('store.admin.repairs.index', $storeRouteParams) }}"
               class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition grid place-items-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div class="min-w-0">
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-violet-100 dark:border-violet-900/60 mb-0.5">
                    <span>⚙️</span>
                    <span>{{ __('messages.sidebar_service_settings') }}</span>
                    <span class="text-slate-400 dark:text-slate-500">·</span>
                    <span class="font-normal normal-case text-slate-500 dark:text-slate-400">Master Data Configuration</span>
                </div>
                <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2 truncate">
                    <span>{{ __('messages.sidebar_service_settings') }}</span>
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                    {{ $store->name }} · {{ __('messages.service_settings_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button type="button" @click="importOpen = true"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition flex items-center gap-1 cursor-pointer">
                <span>📥</span>
                <span>{{ __('messages.import') ?? 'Import' }}</span>
            </button>
            <a href="{{ $exportUrl }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition flex items-center gap-1">
                <span>📤</span>
                <span>{{ __('messages.export') ?? 'Export' }}</span>
            </a>
            <button type="button" @click="createOpen = true"
                    class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <span class="text-sm leading-none">+</span>
                <span>{{ __('messages.add_new') }}</span>
            </button>
        </div>
    </header>

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
         2. SEGMENTED HORIZONTAL TABS (All 8 Master Data Categories)
         ============================================================ --}}
    <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5 scrollbar-thin">
        <div class="inline-flex items-center gap-1 p-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            @foreach ($types as $key => $typeTitle)
                @php $isActive = $tab === $key; @endphp
                <a href="{{ route('store.admin.service_settings.index', [...$storeRouteParams, 'tab' => $key]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-bold whitespace-nowrap transition cursor-pointer
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
         3. UNIFIED ADMIN TOOLBAR
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
        :showViewToggle="false"
        :showExportImport="true"
        :exportUrl="$exportUrl"
        :totalCount="count($grouped[$tab] ?? [])"
    />

    {{-- ============================================================
         4. ITEMS SPREADSHEET TABLE
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-100 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="p-2.5 w-14 text-center">#</th>
                        <th class="p-2.5">{{ __('messages.name') }}</th>
                        @if ($tab === 'model')
                            <th class="p-2.5">{{ __('messages.repair_brand') }}</th>
                        @else
                            <th class="p-2.5">{{ __('messages.code') ?? 'Code' }}</th>
                        @endif
                        <th class="p-2.5">{{ __('messages.description') }}</th>
                        <th class="p-2.5 text-center">{{ __('messages.status') }}</th>
                        <th class="p-2.5 text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($grouped[$tab] ?? [] as $index => $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition group">
                            <td class="p-2.5 text-center text-slate-400 font-mono text-xs">
                                {{ $item->sort_order ?: ($index + 1) }}
                            </td>
                            <td class="p-2.5">
                                <span class="font-bold text-slate-900 dark:text-slate-100 block">{{ $item->name }}</span>
                            </td>
                            <td class="p-2.5 text-slate-500 dark:text-slate-400 font-mono text-xs">
                                @if ($tab === 'model' && $item->parent)
                                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold">{{ $item->parent->name }}</span>
                                @else
                                    {{ $item->code ?: '—' }}
                                @endif
                            </td>
                            <td class="p-2.5 text-slate-500 dark:text-slate-400 text-xs truncate max-w-xs">
                                {{ $item->description ?: '—' }}
                            </td>
                            <td class="p-2.5 text-center">
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
                            <td class="p-2.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @click="openEdit({{ $item->toJson() }})"
                                            class="px-2 py-1 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 hover:bg-violet-50 hover:text-violet-600 dark:hover:bg-violet-950/60 dark:hover:text-violet-300 transition cursor-pointer">
                                        ✏️ {{ __('messages.edit') }}
                                    </button>
                                    <form method="POST" action="{{ route('store.admin.service_settings.destroy', [...$storeRouteParams, 'service_setting' => $item->id]) }}"
                                          onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2 py-1 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/60 dark:hover:text-rose-400 transition cursor-pointer">
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
         5. CREATE ITEM MODAL
         ============================================================ --}}
    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @keydown.escape.window="createOpen = false" @click.self="createOpen = false">
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-2xl space-y-3.5 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
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
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.code') ?? 'Code' }}</label>
                        <input type="text" name="code" maxlength="60"
                               placeholder="e.g. code or short identifier"
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
                    <button type="button" @click="createOpen = false" class="flex-1 py-2 rounded-lg font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="flex-1 py-2 rounded-lg font-black text-xs bg-violet-600 hover:bg-violet-500 text-white shadow-2xs transition cursor-pointer active:scale-95">+ {{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         6. EDIT ITEM MODAL
         ============================================================ --}}
    <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @keydown.escape.window="editOpen = false" @click.self="editOpen = false">
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-2xl space-y-3.5 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
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
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.code') ?? 'Code' }}</label>
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
                    <button type="button" @click="editOpen = false" class="flex-1 py-2 rounded-lg font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="flex-1 py-2 rounded-lg font-black text-xs bg-violet-600 hover:bg-violet-500 text-white shadow-2xs transition cursor-pointer active:scale-95">{{ __('messages.update') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         7. IMPORT CSV / EXCEL MODAL
         ============================================================ --}}
    <div x-show="importOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @keydown.escape.window="importOpen = false" @click.self="importOpen = false">
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-2xl space-y-4 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📥</span>
                    <span>{{ __('messages.import') ?? 'Import' }} {{ $tabLabels[$tab] ?? $types[$tab] }} (CSV)</span>
                </h3>
                <button type="button" @click="importOpen = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('store.admin.service_settings.import', $storeRouteParams) }}" enctype="multipart/form-data" class="space-y-3.5 text-xs">
                @csrf
                <input type="hidden" name="tab" value="{{ $tab }}">

                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/80 dark:border-slate-700/60 space-y-2">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                        CSV ဖိုင်ဖြင့် ကုန်ပစ္စည်း/တံဆိပ်/မော်ဒယ်/အရောင် Master Data များကို တစ်ပြိုင်နက် သွင်းယူနိုင်ပါသည်။
                    </p>
                    <a href="{{ route('store.admin.service_settings.template', [...$storeRouteParams, 'tab' => $tab]) }}"
                       class="inline-flex items-center gap-1.5 text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                        <span>📄</span>
                        <span>{{ __('messages.download_template') ?? 'Download CSV Template' }}</span>
                    </a>
                </div>

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1.5">CSV File <span class="text-rose-500">*</span></label>
                    <input type="file" name="file" required accept=".csv,.txt"
                           class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-violet-950/60 dark:file:text-violet-300 cursor-pointer">
                </div>

                <div class="flex gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="importOpen = false" class="flex-1 py-2 rounded-lg font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="flex-1 py-2 rounded-lg font-black text-xs bg-violet-600 hover:bg-violet-500 text-white shadow-2xs transition cursor-pointer active:scale-95">📥 {{ __('messages.import') ?? 'Import' }}</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
