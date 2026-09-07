{{--
  Live search suggestions dropdown (categories · brands · products).
  Shared by the header slide-down search AND the inline desktop search bar.
  Requires the parent scope to provide the searchSuggestions Alpine component
  (open, query, categories, brands, products, trending, activeIndex, activeId, pickTrending, labels, loading, hasAny, highlight).
--}}
<div
    id="search-suggestions-panel"
    x-show="open"
    x-cloak
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0 -translate-y-1"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="absolute left-0 right-0 top-full z-50 mt-1.5 max-h-[72vh] overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
    role="listbox"
>
    {{-- Trending searches (chips, shown before the user types) --}}
    <template x-if="query.trim().length === 0 && trending.length > 0">
        <div class="border-b border-slate-100 dark:border-slate-800">
            <x-search-section-header>
                <div class="flex items-center gap-1.5">
                    <span>🔥</span>
                    <span x-text="labels.trending"></span>
                </div>
            </x-search-section-header>
            <div class="flex flex-wrap gap-1.5 px-3 py-2.5" role="group" :aria-label="labels.trending">
                <template x-for="t in trending" :key="t.type + '-' + t.label">
                    <button
                        type="button"
                        @click="pickTrending(t)"
                        class="sf-btn-3d inline-flex max-w-full items-center gap-1.5 px-3 py-1.5 text-xs font-bold leading-none cursor-pointer"
                    >
                        <span aria-hidden="true" x-text="t.type === 'category' ? '🗂️' : '🏷️'"></span>
                        <span class="max-w-[12rem] truncate" x-text="t.label"></span>
                    </button>
                </template>
            </div>
        </div>
    </template>

    {{-- Categories section --}}
    <template x-if="categories.length > 0">
        <div>
            <x-search-section-header>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span>🗂️</span>
                        <span x-text="labels.categories"></span>
                    </div>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-300" x-text="categories.length"></span>
                </div>
            </x-search-section-header>
            <template x-for="c in categories" :key="'c' + c.id">
                <a
                    :id="'sug-c-' + c.id"
                    :href="c.url"
                    @click="open = false"
                    @mouseenter="activeIndex = c._i"
                    :class="activeIndex === c._i ? 'bg-sky-50/90 dark:bg-slate-800 border-l-3 border-l-sky-500' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60'"
                    class="flex items-center gap-3 border-b border-slate-100 px-3 py-2.5 transition last:border-0 dark:border-slate-800"
                    role="option"
                    :aria-selected="activeIndex === c._i ? 'true' : 'false'"
                >
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 border border-slate-200 text-base dark:bg-slate-800 dark:border-slate-700" aria-hidden="true">
                        <span x-text="c.icon || '🗂️'"></span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-bold text-slate-800 dark:text-slate-100" x-html="highlight(c.name)"></span>
                        <span class="mt-0.5 block text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                            <span x-text="c.count"></span> <span>{{ __('messages.products') }}</span>
                        </span>
                    </span>
                    <svg class="h-4 w-4 shrink-0 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </template>
        </div>
    </template>

    {{-- Brands section --}}
    <template x-if="brands.length > 0">
        <div>
            <x-search-section-header>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span>🏷️</span>
                        <span x-text="labels.brands"></span>
                    </div>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-300" x-text="brands.length"></span>
                </div>
            </x-search-section-header>
            <template x-for="b in brands" :key="'b' + b.id">
                <a
                    :id="'sug-b-' + b.id"
                    :href="b.url"
                    @click="open = false"
                    @mouseenter="activeIndex = b._i"
                    :class="activeIndex === b._i ? 'bg-sky-50/90 dark:bg-slate-800 border-l-3 border-l-sky-500' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60'"
                    class="flex items-center gap-3 border-b border-slate-100 px-3 py-2.5 transition last:border-0 dark:border-slate-800"
                    role="option"
                    :aria-selected="activeIndex === b._i ? 'true' : 'false'"
                >
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 border border-slate-200 text-base dark:bg-slate-800 dark:border-slate-700" aria-hidden="true">🏷️</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-bold text-slate-800 dark:text-slate-100" x-html="highlight(b.name)"></span>
                        <span class="mt-0.5 block text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                            <span x-text="b.count"></span> <span>{{ __('messages.products') }}</span>
                        </span>
                    </span>
                    <svg class="h-4 w-4 shrink-0 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </template>
        </div>
    </template>

    {{-- Products section --}}
    <template x-if="products.length > 0">
        <div>
            <x-search-section-header>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span>📦</span>
                        <span x-text="labels.products"></span>
                    </div>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-300" x-text="products.length"></span>
                </div>
            </x-search-section-header>
            <template x-for="p in products" :key="'p' + p.id">
                <a
                    :id="'sug-p-' + p.id"
                    :href="p.url"
                    @click="open = false"
                    @mouseenter="activeIndex = p._i"
                    :class="activeIndex === p._i ? 'bg-sky-50/90 dark:bg-slate-800 border-l-3 border-l-sky-500' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60'"
                    class="flex items-center gap-3 border-b border-slate-100 px-3 py-2.5 transition last:border-0 dark:border-slate-800"
                    role="option"
                    :aria-selected="activeIndex === p._i ? 'true' : 'false'"
                >
                    <img :src="p.image" alt="" loading="lazy" decoding="async" class="h-11 w-11 shrink-0 rounded-lg bg-slate-100 border border-slate-200 object-cover dark:bg-slate-800 dark:border-slate-700" x-show="p.image">
                    <span x-show="!p.image" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 border border-slate-200 text-lg dark:bg-slate-800 dark:border-slate-700" aria-hidden="true">📦</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-bold text-slate-800 dark:text-slate-100" x-html="highlight(p.name)"></span>
                        <div class="mt-0.5 flex items-center gap-2">
                            <span class="text-xs font-black text-rose-600 dark:text-rose-400">
                                <span x-text="p.price"></span>
                                <span x-show="p.old_price" class="ml-1.5 align-middle text-[10px] font-semibold text-slate-400 line-through dark:text-slate-500" x-text="p.old_price"></span>
                            </span>
                            <span x-show="p.stock_status === 'in_stock'" class="inline-flex items-center gap-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                <span>●</span> <span>{{ __('messages.in_stock') }}</span>
                            </span>
                            <span x-show="p.stock_status && p.stock_status !== 'in_stock'" class="inline-flex items-center gap-0.5 text-[10px] font-bold text-rose-500 dark:text-rose-400">
                                <span>●</span> <span>{{ __('messages.out_of_stock') }}</span>
                            </span>
                        </div>
                    </span>
                    <svg class="h-4 w-4 shrink-0 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </template>
        </div>
    </template>

    <div x-show="loading" class="px-4 py-3.5 text-center text-xs font-bold text-slate-400 dark:text-slate-500">
        <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-sky-500 border-t-transparent align-middle"></span>
        <span class="ml-1.5 align-middle">{{ __('messages.loading') }}</span>
    </div>
    <div x-show="!loading && !hasAny() && query.trim().length > 0" class="px-4 py-4 text-center text-xs font-bold text-slate-400 dark:text-slate-500">
        <span class="block text-xl mb-1">🔍</span>
        <span>{{ __('messages.no_products_found') }}</span>
    </div>
</div>
