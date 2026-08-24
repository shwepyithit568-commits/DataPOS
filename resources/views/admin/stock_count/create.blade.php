@extends('layouts.admin.app')

@section('title', __('messages.stock_count_new_session') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ scope: 'all', selectedCategories: [] }">

    {{-- Breadcrumbs & Header --}}
    <div>
        <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
            <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.admin_dashboard') }}</a>
            <span>/</span>
            <a href="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.sidebar_stock_count') }}</a>
            <span>/</span>
            <span class="text-slate-700 dark:text-slate-200 font-semibold">{{ __('messages.stock_count_new_session') }}</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 font-outfit mt-1">
            {{ __('messages.stock_count_new_session') }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
            {{ __('messages.stock_count_sub') }}
        </p>
    </div>

    {{-- Session Creation Form --}}
    <form action="{{ route('store.admin.stock_count.store', ['store_slug' => $store->slug]) }}" method="POST" class="space-y-6">
        @csrf

        <div class="p-6 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-6">
            
            {{-- How It Works Infobox --}}
            <div class="p-4 rounded-xl bg-violet-50/60 border border-violet-100 dark:bg-violet-950/30 dark:border-violet-900/50 flex items-start gap-3.5">
                <div class="p-2 rounded-lg bg-violet-600 text-white shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-violet-950 dark:text-violet-200">{{ __('messages.stock_count_info_title') }}</h3>
                    <p class="text-xs text-violet-800 dark:text-violet-300/90 mt-0.5 leading-relaxed">
                        {{ __('messages.stock_count_info_desc') }}
                    </p>
                </div>
            </div>

            {{-- Scope Selection --}}
            <div class="space-y-3">
                <label class="block text-sm font-bold text-slate-900 dark:text-slate-100">
                    {{ __('messages.stock_count_scope') }} <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- All Products Card --}}
                    <label :class="scope === 'all' ? 'border-violet-600 bg-violet-50/30 dark:border-violet-500 dark:bg-violet-950/20 ring-2 ring-violet-500/20' : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 hover:border-slate-300'"
                           class="relative flex items-start p-4 rounded-xl border cursor-pointer transition">
                        <input type="radio" name="scope" value="all" x-model="scope" class="sr-only">
                        <div class="flex items-center h-5">
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center transition"
                                 :class="scope === 'all' ? 'border-violet-600 bg-violet-600' : 'border-slate-300 dark:border-slate-600'">
                                <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="scope === 'all'"></div>
                            </div>
                        </div>
                        <div class="ml-3 text-sm">
                            <span class="font-bold text-slate-900 dark:text-slate-100 block">{{ __('messages.stock_count_scope_all') }}</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 block">{{ __('messages.stock_count_scope_all_desc') }}</span>
                        </div>
                    </label>

                    {{-- By Category Card --}}
                    <label :class="scope === 'category' ? 'border-violet-600 bg-violet-50/30 dark:border-violet-500 dark:bg-violet-950/20 ring-2 ring-violet-500/20' : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 hover:border-slate-300'"
                           class="relative flex items-start p-4 rounded-xl border cursor-pointer transition">
                        <input type="radio" name="scope" value="category" x-model="scope" class="sr-only">
                        <div class="flex items-center h-5">
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center transition"
                                 :class="scope === 'category' ? 'border-violet-600 bg-violet-600' : 'border-slate-300 dark:border-slate-600'">
                                <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="scope === 'category'"></div>
                            </div>
                        </div>
                        <div class="ml-3 text-sm">
                            <span class="font-bold text-slate-900 dark:text-slate-100 block">{{ __('messages.stock_count_scope_category') }}</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 block">{{ __('messages.stock_count_scope_category_desc') }}</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Category Checkbox List (Shown only when scope === 'category') --}}
            <div x-show="scope === 'category'" x-transition class="space-y-3 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">
                        {{ __('messages.stock_count_select_categories') }}
                    </label>
                    <span class="text-xs text-slate-400">
                        {{ $categories->count() }} {{ __('messages.categories') }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-56 overflow-y-auto pr-1">
                    @forelse($categories as $category)
                        <label class="flex items-center gap-2.5 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer text-xs">
                            <input type="checkbox"
                                   name="category_ids[]"
                                   value="{{ $category->id }}"
                                   class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500 border-slate-300 dark:border-slate-600">
                            <span class="font-medium text-slate-800 dark:text-slate-200 truncate flex-1">{{ $category->name }}</span>
                            <span class="text-[11px] font-mono text-slate-400">({{ $category->products_count }})</span>
                        </label>
                    @empty
                        <p class="text-xs text-slate-400 italic col-span-3">{{ __('messages.no_categories') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Optional Branch / Warehouse Selection --}}
            @if($branches->count() > 1 || $warehouses->count() > 1)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if($branches->count() > 1)
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-1.5">
                                {{ __('messages.branch') }}
                            </label>
                            <select name="branch_id" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                <option value="">{{ __('messages.all_branches') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($warehouses->count() > 1)
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-1.5">
                                {{ __('messages.warehouse') }}
                            </label>
                            <select name="warehouse_id" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ $warehouse->is_default ? 'selected' : '' }}>
                                        {{ $warehouse->name }} {{ $warehouse->is_default ? '(Default)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Notes --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-1.5">
                    {{ __('messages.stock_count_notes') }}
                </label>
                <textarea name="notes"
                          rows="3"
                          placeholder="{{ __('messages.stock_count_notes_placeholder') }}"
                          class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
            </div>

        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug]) }}"
               class="px-5 py-2.5 text-sm font-semibold rounded-xl text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 transition">
                {{ __('messages.cancel') }}
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-extrabold rounded-xl bg-violet-600 hover:bg-violet-500 text-white shadow-lg shadow-violet-600/30 transition transform active:scale-95">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
                <span>{{ __('messages.stock_count_start_session') }}</span>
            </button>
        </div>

    </form>

</div>
@endsection
