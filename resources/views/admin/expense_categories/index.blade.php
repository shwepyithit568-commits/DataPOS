@extends('layouts.admin.app')

@section('title', __('messages.expense_categories_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $currentStatus = request('status', 'all');
    $currentSearch = request('search', $search ?? '');
    $currentSort = request('sort', $sort ?? 'order_asc');

    // Build filter URLs for KPI cards
    $kpiUrls = [
        'all' => route('store.admin.expense_categories.index', array_merge($storeRouteParams, array_filter([
            'search' => $currentSearch ?: null,
            'sort' => $currentSort !== 'order_asc' ? $currentSort : null,
        ]))),
        'active' => route('store.admin.expense_categories.index', array_merge($storeRouteParams, array_filter([
            'status' => 'active',
            'search' => $currentSearch ?: null,
            'sort' => $currentSort !== 'order_asc' ? $currentSort : null,
        ]))),
        'inactive' => route('store.admin.expense_categories.index', array_merge($storeRouteParams, array_filter([
            'status' => 'inactive',
            'search' => $currentSearch ?: null,
            'sort' => $currentSort !== 'order_asc' ? $currentSort : null,
        ]))),
    ];
@endphp

<div class="w-full space-y-0.5 pb-6"
     x-data="{
        viewMode: localStorage.getItem('admin_expense_categories_view_mode') || 'table',
        createModalOpen: false,
        editModalOpen: false,
        deleteConfirmOpen: false,
        categoryToDelete: null,
        editingCategory: {
            id: null,
            name: '',
            code: '',
            description: '',
            color: '#6366f1',
            sort_order: 0,
            is_active: true
        },
        colorPresets: ['#6366f1', '#3b82f6', '#0ea5e9', '#10b981', '#14b8a6', '#f59e0b', '#f97316', '#ef4444', '#ec4899', '#8b5cf6', '#64748b', '#0f172a'],
        openCreateModal() {
            this.createModalOpen = true;
            this.$nextTick(() => {
                this.$refs.createCategoryName?.focus();
            });
        },
        openEditModal(category) {
            this.editingCategory = {
                id: category.id,
                name: category.name || '',
                code: category.code || '',
                description: category.description || '',
                color: category.color || '#6366f1',
                sort_order: category.sort_order ?? 0,
                is_active: Boolean(category.is_active)
            };
            this.editModalOpen = true;
            this.$nextTick(() => {
                this.$refs.editCategoryName?.focus();
            });
        },
        confirmDelete(category) {
            this.categoryToDelete = category;
            this.deleteConfirmOpen = true;
        }
     }"
     @keydown.escape.window="if (createModalOpen) createModalOpen = false; if (editModalOpen) editModalOpen = false; if (deleteConfirmOpen) deleteConfirmOpen = false;"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_expense_categories_view_mode', $event.detail)">

    {{-- ============================================================
         1. TOP ULTRA-DENSE HEADER BANNER (Standard v4.1)
         ============================================================ --}}
    <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none transition">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('store.admin.expenses.index', $storeRouteParams) }}"
               class="h-6 w-6 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition active:scale-95 shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="w-6 h-6 rounded bg-violet-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs shrink-0">
                <span>🏷️</span>
            </div>
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/60 px-1.5 py-0.5 rounded border border-violet-200/50 dark:border-violet-800/50 truncate max-w-[120px] sm:max-w-none">
                    {{ $store->name }}
                </span>
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight truncate">
                    {{ __('messages.expense_categories_title') }}
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700/80 font-mono shrink-0">
                    {{ number_format($metrics['total_count']) }} {{ __('messages.categories') }}
                </span>
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-1 sm:gap-1.5 shrink-0 self-start sm:self-auto">
            {{-- Quick Link to Expenses Ledger --}}
            <a href="{{ route('store.admin.expenses.index', $storeRouteParams) }}"
               class="h-7 px-2 sm:px-2.5 rounded text-[11px] sm:text-xs font-bold bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 transition inline-flex items-center gap-1 active:scale-95 shadow-2xs">
                <span>💸</span>
                <span>{{ __('messages.expenses_title') }}</span>
            </a>

            {{-- Create Category CTA --}}
            <button type="button" @click="openCreateModal()"
                    class="h-7 px-2.5 sm:px-3 rounded text-[11px] sm:text-xs font-black bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.expense_categories_new') }}</span>
            </button>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="px-2.5 py-1.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span class="text-sm">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="px-2.5 py-1.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded text-xs font-bold text-rose-800 dark:text-rose-300 space-y-0.5 shadow-2xs">
            <div class="flex items-center gap-1.5">
                <span>⚠️</span>
                <span>{{ __('messages.validation_error') }}</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="pl-4 text-[11px] font-medium">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         2. KPI STAT CARDS (3 Centered Row-based Filter Pills - Standard v4.1)
         ============================================================ --}}
    <div class="grid grid-cols-3 gap-0.5 sm:gap-1 select-none" role="list" aria-label="{{ __('messages.expense_categories_title') }}">
        {{-- Total Categories --}}
        <a href="{{ $kpiUrls['all'] }}" role="listitem"
           class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 {{ $currentStatus === 'all' || !$currentStatus ? 'border-violet-500/80 dark:border-violet-500 ring-1 ring-violet-500/30' : 'border-slate-200/90 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 border-violet-100 dark:border-violet-900/50">
                📁
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.expense_categories_total') }}
                </div>
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ number_format($metrics['total_count']) }}</span>
                    @if($currentStatus === 'all' || !$currentStatus)
                        <span class="w-1.5 h-1.5 rounded-full bg-violet-500 animate-pulse"></span>
                    @endif
                </div>
                <div class="text-[10px] text-slate-400 font-mono truncate">{{ __('messages.categories') }}</div>
            </div>
        </a>

        {{-- Active Categories --}}
        <a href="{{ $kpiUrls['active'] }}" role="listitem"
           class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 {{ $currentStatus === 'active' ? 'border-emerald-500/80 dark:border-emerald-500 ring-1 ring-emerald-500/30' : 'border-slate-200/90 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/50">
                ✅
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-emerald-600 dark:text-emerald-400">
                    {{ __('messages.expense_categories_active_total') }}
                </div>
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ number_format($metrics['active_count']) }}</span>
                    @if($currentStatus === 'active')
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    @endif
                </div>
                <div class="text-[10px] text-slate-400 font-mono truncate">{{ __('messages.expense_category_active') }}</div>
            </div>
        </a>

        {{-- Inactive Categories --}}
        <a href="{{ $kpiUrls['inactive'] }}" role="listitem"
           class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 {{ $currentStatus === 'inactive' ? 'border-amber-500/80 dark:border-amber-500 ring-1 ring-amber-500/30' : 'border-slate-200/90 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border-amber-100 dark:border-amber-900/50">
                ⏸️
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.expense_categories_inactive_total') }}
                </div>
                <div class="text-sm sm:text-base font-black text-slate-500 dark:text-slate-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ number_format($metrics['inactive_count']) }}</span>
                    @if($currentStatus === 'inactive')
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    @endif
                </div>
                <div class="text-[10px] text-slate-400 font-mono truncate">{{ __('messages.expense_category_inactive') }}</div>
            </div>
        </a>
    </div>

    {{-- ============================================================
         3. TOOLBAR AREA: Search, Status Filter, Sort, View Toggle, Export
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', $search)"
        :searchPlaceholder="__('messages.expense_category_filter_search')"
        :sort="request('sort', $sort)"
        :sortOptions="[
            'order_asc' => __('messages.expense_category_sort_order'),
            'newest' => __('messages.sort_newest'),
            'oldest' => __('messages.sort_oldest'),
            'name_asc' => __('messages.sort_name_asc'),
            'name_desc' => __('messages.sort_name_desc'),
        ]"
        :filters="[
            'status' => [
                'label' => __('messages.status'),
                'options' => [
                    'active' => '✓ ' . __('messages.expense_category_active'),
                    'inactive' => '⏸️ ' . __('messages.expense_category_inactive'),
                ],
            ],
        ]"
        :viewMode="'table'"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$exportUrl ?? null"
        :totalCount="$metrics['total_count']"
        :paginator="$categories"
    />

    {{-- Floating Action Button for Mobile Quick Add --}}
    <button type="button" @click="openCreateModal()"
            class="fixed bottom-5 right-5 z-40 sm:hidden w-12 h-12 rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-xl shadow-violet-900/40 flex items-center justify-center text-2xl font-bold active:scale-95 transition cursor-pointer"
            title="{{ __('messages.expense_categories_new') }}">
        +
    </button>

    {{-- ============================================================
         4. SPREADSHEET TABLE VIEW (Desktop & Tablet standard)
         ============================================================ --}}
    <div x-show="viewMode === 'table'" x-cloak class="w-full bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden transition">

        {{-- Mobile Swipe Hint Bar --}}
        <div class="sm:hidden px-2.5 py-1 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between text-[10px] font-semibold text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1">
                <span>⟷</span>
                <span>{{ __('messages.swipe_hint') ?? 'Swipe table' }}</span>
            </span>
            <span class="font-mono text-[10px] px-1.5 py-0.2 bg-slate-200/70 dark:bg-slate-700 rounded">{{ $categories->total() }} {{ __('messages.categories') }}</span>
        </div>

        <div class="overflow-x-auto scrollbar-thin">
            <table class="w-full text-left border-collapse min-w-[720px]">
                <thead>
                    <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider">
                        <th class="px-2.5 py-2 w-[50px] text-center">{{ __('messages.expense_category_color') }}</th>
                        <th class="px-2.5 py-2 min-w-[180px]">{{ __('messages.expense_category_name') }}</th>
                        <th class="px-2.5 py-2 w-[110px]">{{ __('messages.expense_category_code') }}</th>
                        <th class="px-2.5 py-2">{{ __('messages.description') }}</th>
                        <th class="px-2.5 py-2 w-[90px] text-center">{{ __('messages.expense_category_sort_order') }}</th>
                        <th class="px-2.5 py-2 w-[120px] text-center">{{ __('messages.status') }}</th>
                        <th class="px-2.5 py-2 w-[110px] text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs font-medium text-slate-700 dark:text-slate-300">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition duration-150">
                            {{-- Color Indicator Swatch --}}
                            <td class="px-2.5 py-2 text-center">
                                <span class="w-4 h-4 rounded-full shadow-inner inline-block align-middle ring-1 ring-black/10 dark:ring-white/20"
                                      style="background-color: {{ $category->color ?: '#6366f1' }};"
                                      title="{{ $category->color ?: '#6366f1' }}"></span>
                            </td>

                            {{-- Category Name --}}
                            <td class="px-2.5 py-2">
                                <span class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100">
                                    {{ $category->name }}
                                </span>
                            </td>

                            {{-- Category Code Badge --}}
                            <td class="px-2.5 py-2">
                                @if ($category->code)
                                    <span class="font-mono text-[10px] sm:text-[11px] font-bold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700/80">
                                        {{ $category->code }}
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>

                            {{-- Description --}}
                            <td class="px-2.5 py-2">
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1" title="{{ $category->description }}">
                                    {{ $category->description ?: '—' }}
                                </span>
                            </td>

                            {{-- Sort Order (Highlighted) --}}
                            <td class="px-2.5 py-2 text-center font-mono font-bold">
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px]">
                                    {{ $category->sort_order }}
                                </span>
                            </td>

                            {{-- Status with 1-click Toggle --}}
                            <td class="px-2.5 py-2 text-center whitespace-nowrap">
                                <form method="POST" action="{{ route('store.admin.expense_categories.toggle', array_merge($storeRouteParams, ['category' => $category->id])) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" title="Click to toggle status"
                                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-black transition cursor-pointer active:scale-95 {{ $category->is_active ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800 hover:bg-emerald-100' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 hover:bg-slate-200 border border-slate-200/60 dark:border-slate-700' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $category->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        <span>{{ $category->is_active ? __('messages.expense_category_active') : __('messages.expense_category_inactive') }}</span>
                                    </button>
                                </form>
                            </td>

                            {{-- Actions --}}
                            <td class="px-2.5 py-2 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @click="openEditModal({{ json_encode($category) }})"
                                            class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer"
                                            title="{{ __('messages.edit') }}">
                                        {{ __('messages.edit') }}
                                    </button>

                                    <button type="button" @click="confirmDelete({{ json_encode($category) }})"
                                            class="p-1 rounded text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                                            title="{{ __('messages.delete') }}">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-10 text-center text-slate-400">
                                <div class="space-y-1.5 max-w-sm mx-auto">
                                    <span class="text-3xl block">🏷️</span>
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.expense_category_no_items') }}</p>
                                    <button type="button" @click="openCreateModal()"
                                            class="mt-1.5 px-3 py-1 rounded text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                                        <span>+</span>
                                        <span>{{ __('messages.expense_categories_new') }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================
         5. RESPONSIVE CARD GRID VIEW (Mobile & Tablet friendly)
         ============================================================ --}}
    <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0.5 sm:gap-1">
        @forelse ($categories as $category)
            <div class="rounded bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-2.5 shadow-2xs hover:shadow-xs transition flex flex-col justify-between space-y-2 group">
                <div class="space-y-1.5">
                    {{-- Card Top: Color Badge & Status Toggle --}}
                    <div class="flex items-center justify-between gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3.5 h-3.5 rounded-full shadow-inner ring-1 ring-black/10 dark:ring-white/20 shrink-0"
                                  style="background-color: {{ $category->color ?: '#6366f1' }};"></span>
                            @if ($category->code)
                                <span class="font-mono text-[10px] font-bold px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700">
                                    {{ $category->code }}
                                </span>
                            @endif
                        </div>

                        {{-- Active Status Pill / 1-click Toggle --}}
                        <form method="POST" action="{{ route('store.admin.expense_categories.toggle', array_merge($storeRouteParams, ['category' => $category->id])) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" title="Click to toggle status"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black transition cursor-pointer {{ $category->is_active ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $category->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                <span>{{ $category->is_active ? __('messages.expense_category_active') : __('messages.expense_category_inactive') }}</span>
                            </button>
                        </form>
                    </div>

                    {{-- Category Name & Description --}}
                    <div>
                        <h3 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition">
                            {{ $category->name }}
                        </h3>
                        @if ($category->description)
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2 leading-tight">{{ $category->description }}</p>
                        @endif
                    </div>
                </div>

                {{-- Card Bottom: Order & Actions --}}
                <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-[10px] text-slate-400 font-mono font-bold">
                        {{ __('messages.expense_category_sort_order') }}: {{ $category->sort_order }}
                    </span>

                    <div class="flex items-center gap-1">
                        <button type="button" @click="openEditModal({{ json_encode($category) }})"
                                class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition cursor-pointer">
                            {{ __('messages.edit') }}
                        </button>

                        <button type="button" @click="confirmDelete({{ json_encode($category) }})"
                                class="p-1 rounded text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer" title="{{ __('messages.delete') }}">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-10 text-center text-slate-400 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800">
                <span class="text-3xl">🏷️</span>
                <p class="text-xs font-bold text-slate-700 dark:text-slate-300 mt-1">{{ __('messages.expense_category_no_items') }}</p>
                <button type="button" @click="openCreateModal()"
                        class="mt-1.5 px-3 py-1 rounded text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                    <span>+</span>
                    <span>{{ __('messages.expense_categories_new') }}</span>
                </button>
            </div>
        @endforelse
    </div>

    {{-- 6. Pagination --}}
    @if ($categories->hasPages())
        <div class="p-2 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs">
            {{ $categories->links() }}
        </div>
    @endif

    {{-- ============================================================
         CREATE CATEGORY MODAL (Teleport)
         ============================================================ --}}
    <template x-teleport="body">
        <div x-show="createModalOpen" x-cloak
             style="z-index: 99999;"
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/60 backdrop-blur-xs"
             @click.self="createModalOpen = false"
             @keydown.escape.window="createModalOpen = false">
            <div x-show="createModalOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="w-full max-w-md rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden text-left"
                 @click.stop>
                
                <div class="px-3.5 py-2.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-violet-100 dark:bg-violet-950/80 text-violet-600 dark:text-violet-400 grid place-items-center text-xs font-bold shadow-inner">🏷️</span>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">{{ __('messages.expense_categories_new') }}</h3>
                    </div>
                    <button type="button" @click="createModalOpen = false" class="w-6 h-6 rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center justify-center text-sm font-bold transition">✕</button>
                </div>

                <form method="POST" action="{{ route('store.admin.expense_categories.store', $storeRouteParams) }}"
                      x-data="{ colorHex: '#6366f1' }"
                      class="p-3.5 space-y-2.5">
                    @csrf

                    {{-- Name --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                            {{ __('messages.expense_category_name') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" x-ref="createCategoryName" required placeholder="Shop Rent, Utilities, Staff Meals..."
                               class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                    </div>

                    {{-- Code & Sort Order in 2 cols --}}
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.expense_category_code') }}
                            </label>
                            <input type="text" name="code" placeholder="RENT, UTIL..."
                                   class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-mono uppercase text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.expense_category_sort_order') }}
                            </label>
                            <input type="number" name="sort_order" value="0" min="0"
                                   class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-mono text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                        </div>
                    </div>

                    {{-- Color Swatches & Hex Input --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_category_color') }}
                        </label>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <template x-for="c in colorPresets" :key="c">
                                <button type="button" @click="colorHex = c"
                                        :class="colorHex.toLowerCase() === c.toLowerCase() ? 'ring-2 ring-violet-500 ring-offset-1 scale-110' : 'opacity-80 hover:opacity-100'"
                                        :style="'background-color: ' + c"
                                        class="w-5 h-5 rounded-full transition shadow-2xs cursor-pointer"></button>
                            </template>
                            <input type="color" x-model="colorHex" class="w-6 h-6 rounded border-0 p-0 cursor-pointer bg-transparent">
                            <input type="text" name="color" x-model="colorHex" maxlength="20"
                                   class="w-20 h-7 px-1.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-mono text-[11px] text-slate-900 dark:text-white uppercase outline-none">
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                            {{ __('messages.description') }}
                        </label>
                        <textarea name="description" rows="2" placeholder="{{ __('messages.optional') }}..."
                                  class="w-full px-2.5 py-1.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition"></textarea>
                    </div>

                    {{-- Active Status --}}
                    <div class="flex items-center gap-2 pt-0.5">
                        <input type="checkbox" name="is_active" id="create_is_active" value="1" checked
                               class="w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                        <label for="create_is_active" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            {{ __('messages.expense_category_active') }}
                        </label>
                    </div>

                    {{-- Footer Action Buttons --}}
                    <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-1.5">
                        <button type="button" @click="createModalOpen = false"
                                class="h-7 px-3 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer">
                            {{ __('messages.cancel') }}
                        </button>
                        <button type="submit"
                                class="h-7 px-3.5 rounded text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-2xs transition cursor-pointer">
                            {{ __('messages.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- ============================================================
         EDIT CATEGORY MODAL (Teleport)
         ============================================================ --}}
    <template x-teleport="body">
        <div x-show="editModalOpen" x-cloak
             style="z-index: 99999;"
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/60 backdrop-blur-xs"
             @click.self="editModalOpen = false"
             @keydown.escape.window="editModalOpen = false">
            <div x-show="editModalOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="w-full max-w-md rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden text-left"
                 @click.stop>
                
                <div class="px-3.5 py-2.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-violet-100 dark:bg-violet-950/80 text-violet-600 dark:text-violet-400 grid place-items-center text-xs font-bold shadow-inner">✏️</span>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">{{ __('messages.expense_categories_edit') }}</h3>
                    </div>
                    <button type="button" @click="editModalOpen = false" class="w-6 h-6 rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center justify-center text-sm font-bold transition">✕</button>
                </div>

                <form method="POST" :action="'{{ route('store.admin.expense_categories.index', $storeRouteParams) }}/' + editingCategory.id"
                      class="p-3.5 space-y-2.5">
                    @csrf
                    @method('PUT')

                    {{-- Name --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                            {{ __('messages.expense_category_name') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" x-ref="editCategoryName" x-model="editingCategory.name" required
                               class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                    </div>

                    {{-- Code & Sort Order in 2 cols --}}
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.expense_category_code') }}
                            </label>
                            <input type="text" name="code" x-model="editingCategory.code"
                                   class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-mono uppercase text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.expense_category_sort_order') }}
                            </label>
                            <input type="number" name="sort_order" x-model="editingCategory.sort_order" min="0"
                                   class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-mono text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                        </div>
                    </div>

                    {{-- Color Swatches & Hex Input --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_category_color') }}
                        </label>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <template x-for="c in colorPresets" :key="c">
                                <button type="button" @click="editingCategory.color = c"
                                        :class="editingCategory.color && editingCategory.color.toLowerCase() === c.toLowerCase() ? 'ring-2 ring-violet-500 ring-offset-1 scale-110' : 'opacity-80 hover:opacity-100'"
                                        :style="'background-color: ' + c"
                                        class="w-5 h-5 rounded-full transition shadow-2xs cursor-pointer"></button>
                            </template>
                            <input type="color" x-model="editingCategory.color" class="w-6 h-6 rounded border-0 p-0 cursor-pointer bg-transparent">
                            <input type="text" name="color" x-model="editingCategory.color" maxlength="20"
                                   class="w-20 h-7 px-1.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-mono text-[11px] text-slate-900 dark:text-white uppercase outline-none">
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                            {{ __('messages.description') }}
                        </label>
                        <textarea name="description" x-model="editingCategory.description" rows="2"
                                  class="w-full px-2.5 py-1.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition"></textarea>
                    </div>

                    {{-- Active Status --}}
                    <div class="flex items-center gap-2 pt-0.5">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1" x-model="editingCategory.is_active"
                               class="w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                        <label for="edit_is_active" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            {{ __('messages.expense_category_active') }}
                        </label>
                    </div>

                    {{-- Footer Action Buttons --}}
                    <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-1.5">
                        <button type="button" @click="editModalOpen = false"
                                class="h-7 px-3 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer">
                            {{ __('messages.cancel') }}
                        </button>
                        <button type="submit"
                                class="h-7 px-3.5 rounded text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-2xs transition cursor-pointer">
                            {{ __('messages.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- ============================================================
         DELETE CONFIRMATION MODAL (Teleport)
         ============================================================ --}}
    <template x-teleport="body">
        <div x-show="deleteConfirmOpen" x-cloak
             style="z-index: 99999;"
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/60 backdrop-blur-xs"
             @click.self="deleteConfirmOpen = false"
             @keydown.escape.window="deleteConfirmOpen = false">
            <div x-show="deleteConfirmOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="w-full max-w-sm rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-4 space-y-3 text-center"
                 @click.stop>
                
                <div class="w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400 grid place-items-center mx-auto text-xl shadow-inner">
                    ⚠️
                </div>

                <div>
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">{{ __('messages.expense_category_delete_confirm') }}</h3>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-1" x-text="categoryToDelete ? categoryToDelete.name : ''"></p>
                </div>

                <form method="POST" :action="'{{ route('store.admin.expense_categories.index', $storeRouteParams) }}/' + (categoryToDelete ? categoryToDelete.id : '')"
                      class="flex items-center justify-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="deleteConfirmOpen = false"
                            class="h-7 px-3.5 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="h-7 px-4 rounded text-xs font-black bg-rose-600 hover:bg-rose-700 text-white shadow-2xs transition cursor-pointer">
                        {{ __('messages.delete') }}
                    </button>
                </form>
            </div>
        </div>
    </template>

</div>
@endsection
