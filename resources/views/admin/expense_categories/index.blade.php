@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12"
     x-data="{
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
        createModalOpen: false,
        editModalOpen: false,
        editingCategory: {
            id: null,
            name: '',
            code: '',
            description: '',
            color: '#6366f1',
            sort_order: 0,
            is_active: true
        },
        openEditModal(category) {
            this.editingCategory = {
                id: category.id,
                name: category.name,
                code: category.code || '',
                description: category.description || '',
                color: category.color || '#6366f1',
                sort_order: category.sort_order || 0,
                is_active: Boolean(category.is_active)
            };
            this.editModalOpen = true;
        },
        colorPresets: ['#6366f1', '#3b82f6', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#64748b']
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center text-xl sm:text-2xl font-bold shadow-sm flex-shrink-0">
                🏷️
            </span>
            <div class="min-w-0">
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span class="truncate">{{ __('messages.expense_categories_title') }}</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $store->name }} · {{ __('messages.expense_categories_subtitle') }}</p>
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <button type="button" @click="createModalOpen = true"
                    class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-black bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-500/20 transition flex items-center gap-2 active:scale-95">
                <span class="text-base leading-none">+</span>
                <span>{{ __('messages.expense_categories_new') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. Flash Messages --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-3xl text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <span class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900 grid place-items-center text-emerald-600 dark:text-emerald-300 font-black">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-3xl text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1 shadow-sm">
            @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- 3. 3 Stat KPI Metric Cards (Responsive 1-col on mobile, 3-col on sm/lg) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
        {{-- Total Categories --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.expense_categories_total') }}</p>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white font-mono tracking-tight">{{ number_format($metrics['total_count']) }}</h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ __('messages.categories') }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                📁
            </span>
        </div>

        {{-- Active Categories --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mb-1 flex items-center gap-1 truncate">
                    <span>✓</span> <span class="truncate">{{ __('messages.expense_categories_active_total') }}</span>
                </p>
                <h3 class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">{{ number_format($metrics['active_count']) }}</h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ __('messages.expense_category_active') }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                ✅
            </span>
        </div>

        {{-- Inactive Categories --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 mb-1 flex items-center gap-1 truncate">
                    <span>⏸️</span> <span class="truncate">{{ __('messages.expense_categories_inactive_total') }}</span>
                </p>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-500 dark:text-slate-400 font-mono tracking-tight">{{ number_format($metrics['inactive_count']) }}</h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ __('messages.expense_category_inactive') }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                ⏸️
            </span>
        </div>
    </div>

    {{-- 4. Unified Admin Toolbar --}}
    <x-admin.toolbar
        :search="request('search', $search)"
        :searchPlaceholder="__('messages.expense_category_filter_search')"
        :sort="request('sort', $sort)"
        :sortOptions="[
            'order_asc' => 'ပြသမှု အစီအစဉ် (Display Order)',
            'newest' => __('messages.repair_sort_newest'),
            'oldest' => __('messages.repair_sort_oldest'),
            'name_asc' => 'အမည် က-အ (Name A–Z)',
            'name_desc' => 'အမည် အ-က (Name Z–A)',
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
        :showExportImport="false"
        :totalCount="$metrics['total_count']"
        :paginator="$categories"
        :perPageOptions="[25 => '25', 50 => '50', 100 => '100', 'all' => 'All']"
    />

    {{-- 5. Card Grid View (Responsive Cards for Mobile, Tablet, Desktop) --}}
    <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse ($categories as $category)
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4 group">
                <div class="space-y-3">
                    {{-- Card Top: Color Badge & Status Toggle --}}
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full shadow-inner flex-shrink-0" style="background-color: {{ $category->color ?: '#6366f1' }};"></span>
                            @if ($category->code)
                                <span class="font-mono text-[11px] font-black px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    {{ $category->code }}
                                </span>
                            @endif
                        </div>

                        {{-- Active Status Pill / 1-click Toggle --}}
                        <form method="POST" action="{{ route('store.admin.expense_categories.toggle', array_merge($storeRouteParams, ['category' => $category->id])) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" title="Click to toggle status"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black transition {{ $category->is_active ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800 hover:bg-emerald-100' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 hover:bg-slate-200' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $category->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                <span>{{ $category->is_active ? __('messages.expense_category_active') : __('messages.expense_category_inactive') }}</span>
                            </button>
                        </form>
                    </div>

                    {{-- Category Name & Description --}}
                    <div>
                        <h3 class="font-black text-sm text-slate-900 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">
                            {{ $category->name }}
                        </h3>
                        @if ($category->description)
                            <p class="text-xs text-slate-400 dark:text-slate-400 mt-1 line-clamp-2">{{ $category->description }}</p>
                        @endif
                    </div>
                </div>

                {{-- Card Bottom: Order & Actions --}}
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-[11px] text-slate-400 font-mono">
                        Order: {{ $category->sort_order }}
                    </span>

                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="openEditModal({{ json_encode($category) }})"
                                class="px-3 py-1.5 rounded-xl font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                            {{ __('messages.edit') }}
                        </button>

                        <form method="POST" action="{{ route('store.admin.expense_categories.destroy', array_merge($storeRouteParams, ['category' => $category->id])) }}"
                              onsubmit="return confirm('{{ __('messages.expense_category_delete_confirm') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="p-1.5 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="Delete">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center text-slate-400 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800">
                <span class="text-5xl">🏷️</span>
                <p class="text-base font-black text-slate-700 dark:text-slate-200 mt-2">{{ __('messages.expense_category_no_items') }}</p>
                <button type="button" @click="createModalOpen = true"
                        class="mt-3 px-4 py-2 rounded-xl text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow transition inline-flex items-center gap-1.5">
                    <span>+</span>
                    <span>{{ __('messages.expense_categories_new') }}</span>
                </button>
            </div>
        @endforelse
    </div>

    {{-- 6. Table View (Responsive Table for Desktop / Tablet with smooth horizontal scrolling on mobile) --}}
    <div x-show="viewMode === 'table'" x-cloak class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        
        {{-- Mobile Swipe Hint Bar --}}
        <div class="sm:hidden px-4 py-2.5 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] font-semibold text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1.5">
                <span class="animate-pulse">⟷</span>
                <span>ဘေးသို့ ဆွဲရွှေ့၍ ကြည့်နိုင်ပါသည် (Swipe table)</span>
            </span>
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-200/70 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-mono">{{ $categories->total() }} items</span>
        </div>

        <div class="overflow-x-auto scrollbar-thin">
            <table class="w-full text-left border-collapse min-w-[850px]">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 text-[11px] font-black text-slate-500 uppercase tracking-wider">
                        <th class="px-5 py-4 w-[60px] text-center">Color</th>
                        <th class="px-5 py-4 w-[220px]">{{ __('messages.expense_category_name') }}</th>
                        <th class="px-5 py-4 w-[120px]">{{ __('messages.expense_category_code') }}</th>
                        <th class="px-5 py-4">{{ __('messages.description') }}</th>
                        <th class="px-5 py-4 w-[90px] text-center">{{ __('messages.expense_category_sort_order') }}</th>
                        <th class="px-5 py-4 w-[130px] text-center">{{ __('messages.status') }}</th>
                        <th class="px-5 py-4 w-[150px] text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs font-medium text-slate-700 dark:text-slate-300">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition duration-150">
                            {{-- Color Indicator --}}
                            <td class="px-5 py-4 text-center">
                                <span class="w-5 h-5 rounded-full shadow-inner inline-block" style="background-color: {{ $category->color ?: '#6366f1' }};"></span>
                            </td>

                            {{-- Name --}}
                            <td class="px-5 py-4">
                                <span class="font-black text-sm text-slate-900 dark:text-slate-100">{{ $category->name }}</span>
                            </td>

                            {{-- Code --}}
                            <td class="px-5 py-4">
                                @if ($category->code)
                                    <span class="font-mono text-[11px] font-black px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ $category->code }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- Description --}}
                            <td class="px-5 py-4">
                                <span class="text-slate-500 dark:text-slate-400 line-clamp-1">{{ $category->description ?: '—' }}</span>
                            </td>

                            {{-- Sort Order --}}
                            <td class="px-5 py-4 text-center font-mono font-bold text-slate-600 dark:text-slate-300">
                                {{ $category->sort_order }}
                            </td>

                            {{-- Status with 1-click Toggle --}}
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <form method="POST" action="{{ route('store.admin.expense_categories.toggle', array_merge($storeRouteParams, ['category' => $category->id])) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" title="Click to toggle status"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black transition {{ $category->is_active ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800 hover:bg-emerald-100' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 hover:bg-slate-200' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $category->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        <span>{{ $category->is_active ? __('messages.expense_category_active') : __('messages.expense_category_inactive') }}</span>
                                    </button>
                                </form>
                            </td>

                            {{-- Actions --}}
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" @click="openEditModal({{ json_encode($category) }})"
                                            class="px-3 py-1.5 rounded-xl text-[11px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition">
                                        {{ __('messages.edit') }}
                                    </button>

                                    <form method="POST" action="{{ route('store.admin.expense_categories.destroy', array_merge($storeRouteParams, ['category' => $category->id])) }}"
                                          onsubmit="return confirm('{{ __('messages.expense_category_delete_confirm') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="Delete">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center text-slate-400">
                                <div class="space-y-2">
                                    <span class="text-4xl">🏷️</span>
                                    <p class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ __('messages.expense_category_no_items') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 7. Bottom Pagination --}}
    @if ($categories->hasPages())
        <div class="p-4 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm">
            {{ $categories->links() }}
        </div>
    @endif

    {{-- ============================================================
         CREATE CATEGORY MODAL
         ============================================================ --}}
    <div x-show="createModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="createModalOpen = false"
             x-show="createModalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-lg rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-6 space-y-5 overflow-hidden">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🏷️</span>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">{{ __('messages.expense_categories_new') }}</h3>
                </div>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg">✕</button>
            </div>

            <form method="POST" action="{{ route('store.admin.expense_categories.store', $storeRouteParams) }}" class="space-y-4">
                @csrf

                {{-- Name --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.expense_category_name') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required placeholder="ဥပမာ - Shop Rent, Electricity, Staff Meals..."
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>

                {{-- Code & Sort Order --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_category_code') }}
                        </label>
                        <input type="text" name="code" placeholder="RENT, UTIL, MEALS..."
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-mono uppercase text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_category_sort_order') }}
                        </label>
                        <input type="number" name="sort_order" value="0" min="0"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-mono text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>

                {{-- Color Palette Selection --}}
                <div x-data="{ selectedColor: '#6366f1' }">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.expense_category_color') }}
                    </label>
                    <div class="flex items-center gap-2 flex-wrap">
                        <template x-for="color in colorPresets" :key="color">
                            <button type="button" @click="selectedColor = color"
                                    :class="selectedColor === color ? 'ring-2 ring-offset-2 ring-blue-500 scale-110' : 'opacity-70 hover:opacity-100'"
                                    :style="'background-color: ' + color"
                                    class="w-7 h-7 rounded-full transition shadow-sm"></button>
                        </template>
                        <input type="hidden" name="color" :value="selectedColor">
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.description') }}
                    </label>
                    <textarea name="description" rows="2" placeholder="ဖော်ပြချက် (Optional)..."
                              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                </div>

                {{-- Active Status --}}
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" id="create_is_active" value="1" checked
                           class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <label for="create_is_active" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                        {{ __('messages.expense_category_active') }}
                    </label>
                </div>

                {{-- Buttons --}}
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                    <button type="button" @click="createModalOpen = false"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow transition">
                        {{ __('messages.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         EDIT CATEGORY MODAL
         ============================================================ --}}
    <div x-show="editModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="editModalOpen = false"
             x-show="editModalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-lg rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-6 space-y-5 overflow-hidden">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">✏️</span>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">{{ __('messages.expense_categories_edit') }}</h3>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg">✕</button>
            </div>

            <form method="POST" :action="'{{ route('store.admin.expense_categories.index', $storeRouteParams) }}/' + editingCategory.id" class="space-y-4">
                @csrf
                @method('PUT')

                {{-- Name --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.expense_category_name') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" x-model="editingCategory.name" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>

                {{-- Code & Sort Order --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_category_code') }}
                        </label>
                        <input type="text" name="code" x-model="editingCategory.code"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-mono uppercase text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_category_sort_order') }}
                        </label>
                        <input type="number" name="sort_order" x-model="editingCategory.sort_order" min="0"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-mono text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>

                {{-- Color Palette Selection --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.expense_category_color') }}
                    </label>
                    <div class="flex items-center gap-2 flex-wrap">
                        <template x-for="color in colorPresets" :key="color">
                            <button type="button" @click="editingCategory.color = color"
                                    :class="editingCategory.color === color ? 'ring-2 ring-offset-2 ring-blue-500 scale-110' : 'opacity-70 hover:opacity-100'"
                                    :style="'background-color: ' + color"
                                    class="w-7 h-7 rounded-full transition shadow-sm"></button>
                        </template>
                        <input type="hidden" name="color" :value="editingCategory.color">
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.description') }}
                    </label>
                    <textarea name="description" x-model="editingCategory.description" rows="2"
                              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                </div>

                {{-- Active Status --}}
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1" x-model="editingCategory.is_active"
                           class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <label for="edit_is_active" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                        {{ __('messages.expense_category_active') }}
                    </label>
                </div>

                {{-- Buttons --}}
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                    <button type="button" @click="editModalOpen = false"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow transition">
                        {{ __('messages.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
