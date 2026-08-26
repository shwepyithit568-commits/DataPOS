@extends('layouts.admin.app')

@section('title', __('messages.stock_count_new_session') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full max-w-4xl mx-auto space-y-2.5 sm:space-y-3"
     x-data="{
         scope: 'all',
         categorySearch: '',
         allCategories: {{ json_encode($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'products_count' => $c->products_count])) }},
         selectedCategories: [],
         selectAll() {
             this.selectedCategories = this.allCategories.map(c => c.id);
         },
         deselectAll() {
             this.selectedCategories = [];
         },
         filteredCategories() {
             if (!this.categorySearch.trim()) return this.allCategories;
             var q = this.categorySearch.toLowerCase();
             return this.allCategories.filter(c => c.name.toLowerCase().includes(q));
         }
     }">

    {{-- ============================================================
         1. COMPACT HERO FORM HEADER
         ============================================================ --}}
    <div class="p-2.5 sm:p-3.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
        <div class="min-w-0">
            <div class="flex items-center gap-1.5 mb-0.5">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                    <span>📋</span>
                    <span>{{ __('messages.sidebar_stock_count') }}</span>
                </span>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ $store->name }}</span>
            </div>
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span>{{ __('messages.stock_count_new_session') }}</span>
            </h1>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ __('messages.stock_count_sub') }}</p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug]) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                <span>←</span>
                <span>{{ __('messages.back') ?? 'Back to Sessions' }}</span>
            </a>
        </div>
    </div>

    {{-- ============================================================
         2. MAIN FORM CONTAINER
         ============================================================ --}}
    <form action="{{ route('store.admin.stock_count.store', ['store_slug' => $store->slug]) }}" method="POST" class="space-y-2.5 sm:space-y-3">
        @csrf

        {{-- How It Works Infobox --}}
        <div class="p-3 bg-violet-50/70 dark:bg-violet-950/30 rounded-lg border border-violet-200/90 dark:border-violet-900/60 flex items-start gap-3 shadow-2xs">
            <div class="p-1.5 rounded-md bg-violet-600 text-white shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="text-xs font-bold text-violet-950 dark:text-violet-200">{{ __('messages.stock_count_info_title') }}</h3>
                <p class="text-[11px] text-violet-800 dark:text-violet-300/90 mt-0.5 leading-relaxed">
                    {{ __('messages.stock_count_info_desc') }}
                </p>
            </div>
        </div>

        {{-- Section 1: Scope Selection --}}
        <div class="p-3 sm:p-4 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-3">
            <div class="flex items-center gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-2">
                <span class="w-5 h-5 rounded bg-violet-100 dark:bg-violet-950 text-violet-600 dark:text-violet-300 grid place-items-center text-xs font-black">1</span>
                <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">
                    {{ __('messages.stock_count_scope') }}
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                {{-- All Products Option Card --}}
                <label :class="scope === 'all' ? 'border-violet-600 bg-violet-50/60 dark:border-violet-500 dark:bg-violet-950/40 ring-2 ring-violet-500/20' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-850 hover:border-slate-300 dark:hover:border-slate-700'"
                       class="relative flex items-start p-3 rounded-lg border cursor-pointer transition select-none shadow-2xs">
                    <input type="radio" name="scope" value="all" x-model="scope" class="sr-only">
                    <div class="flex items-center h-5">
                        <div class="w-4 h-4 rounded-full border flex items-center justify-center transition"
                             :class="scope === 'all' ? 'border-violet-600 bg-violet-600' : 'border-slate-300 dark:border-slate-600'">
                            <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="scope === 'all'"></div>
                        </div>
                    </div>
                    <div class="ml-3 text-xs flex-1">
                        <span class="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                            <span>📦</span>
                            <span>{{ __('messages.stock_count_scope_all') }}</span>
                        </span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 block leading-normal">{{ __('messages.stock_count_scope_all_desc') }}</span>
                    </div>
                </label>

                {{-- By Category Option Card --}}
                <label :class="scope === 'category' ? 'border-violet-600 bg-violet-50/60 dark:border-violet-500 dark:bg-violet-950/40 ring-2 ring-violet-500/20' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-850 hover:border-slate-300 dark:hover:border-slate-700'"
                       class="relative flex items-start p-3 rounded-lg border cursor-pointer transition select-none shadow-2xs">
                    <input type="radio" name="scope" value="category" x-model="scope" class="sr-only">
                    <div class="flex items-center h-5">
                        <div class="w-4 h-4 rounded-full border flex items-center justify-center transition"
                             :class="scope === 'category' ? 'border-violet-600 bg-violet-600' : 'border-slate-300 dark:border-slate-600'">
                            <div class="w-1.5 h-1.5 rounded-full bg-white" x-show="scope === 'category'"></div>
                        </div>
                    </div>
                    <div class="ml-3 text-xs flex-1">
                        <span class="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                            <span>🏷️</span>
                            <span>{{ __('messages.stock_count_scope_category') }}</span>
                        </span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 block leading-normal">{{ __('messages.stock_count_scope_category_desc') }}</span>
                    </div>
                </label>
            </div>

            {{-- Category Selector Grid (Only when scope === 'category') --}}
            <div x-show="scope === 'category'"
                 x-collapse
                 class="space-y-2.5 p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-850 mt-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/80 dark:border-slate-700 pb-2">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider">
                            {{ __('messages.stock_count_select_categories') }}
                        </label>
                        <span class="text-[11px] font-mono font-bold px-1.5 py-0.5 rounded bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300"
                              x-text="selectedCategories.length + ' / ' + allCategories.length + ' selected'"></span>
                    </div>

                    {{-- Search & Quick Buttons inside Category Selector --}}
                    <div class="flex items-center gap-2">
                        <input type="text"
                               x-model="categorySearch"
                               placeholder="Search categories..."
                               class="px-2.5 py-1 text-xs rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                        <button type="button"
                                @click="selectAll()"
                                class="px-2 py-1 text-[11px] font-bold rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-300">
                            Select All
                        </button>
                        <button type="button"
                                @click="deselectAll()"
                                class="px-2 py-1 text-[11px] font-bold rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-300">
                            Clear
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-60 overflow-y-auto pr-1">
                    <template x-for="cat in filteredCategories()" :key="cat.id">
                        <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer text-xs transition"
                               :class="selectedCategories.includes(cat.id) ? 'border-violet-500 bg-violet-50/50 dark:bg-violet-950/30' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750'">
                            <input type="checkbox"
                                   name="category_ids[]"
                                   :value="cat.id"
                                   x-model="selectedCategories"
                                   class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500 border-slate-300 dark:border-slate-600">
                            <span class="font-bold text-slate-900 dark:text-slate-100 truncate flex-1" x-text="cat.name"></span>
                            <span class="text-[10px] font-mono text-slate-400" x-text="'(' + cat.products_count + ')'"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>

        {{-- Section 2: Storage Location (Branch / Warehouse) --}}
        <div class="p-3 sm:p-4 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-3">
            <div class="flex items-center gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-2">
                <span class="w-5 h-5 rounded bg-violet-100 dark:bg-violet-950 text-violet-600 dark:text-violet-300 grid place-items-center text-xs font-black">2</span>
                <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">
                    {{ __('messages.stock_count_location') }}
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                {{-- Warehouse Selector --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.warehouse') }} <span class="text-slate-400">(သိုလှောင်ရုံ)</span>
                    </label>
                    <select name="warehouse_id"
                            class="w-full px-3 py-2 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-2xs">
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ $warehouse->is_default ? 'selected' : '' }}>
                                {{ $warehouse->name }} {{ $warehouse->is_default ? '(Default)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Branch Selector --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.branch') }} <span class="text-slate-400">(ဆိုင်ခွဲ)</span>
                    </label>
                    <select name="branch_id"
                            class="w-full px-3 py-2 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-2xs">
                        <option value="">{{ __('messages.all_branches') ?? 'Main Store Branch' }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Section 3: Notes & Audit Remarks --}}
        <div class="p-3 sm:p-4 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-2">
            <div class="flex items-center gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-2">
                <span class="w-5 h-5 rounded bg-violet-100 dark:bg-violet-950 text-violet-600 dark:text-violet-300 grid place-items-center text-xs font-black">3</span>
                <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">
                    {{ __('messages.stock_count_notes') }}
                </h2>
            </div>

            <textarea name="notes"
                      rows="2"
                      placeholder="{{ __('messages.stock_count_notes_placeholder') }}"
                      class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-violet-500 font-sans"></textarea>
        </div>

        {{-- ============================================================
             3. STICKY BOTTOM ACTION BAR (Admin UI Standard lines 371-380)
             ============================================================ --}}
        <div class="sticky bottom-0 z-20 w-full border border-slate-200/90 bg-white/95 px-3 py-2.5 sm:px-4 backdrop-blur-md shadow-[0_-4px_16px_rgba(15,23,42,0.06)] dark:border-slate-800/90 dark:bg-slate-900/95 rounded-lg flex items-center justify-between gap-3">
            <a href="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug]) }}"
               class="px-3.5 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer">
                {{ __('messages.cancel') }}
            </a>

            <button type="submit"
                    class="px-5 py-2 rounded-lg bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-black text-xs shadow-md shadow-violet-500/20 transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                <span>⚡</span>
                <span>{{ __('messages.stock_count_start_session') }}</span>
            </button>
        </div>

    </form>

</div>
@endsection
