{{-- ── Desktop only: Navigation & Filters (More Modules + Search + Barcode + Web Order + Categories & Brands Dropdowns) ── --}}
<div class="hidden lg:block rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm"
     x-data="{
         catDropdownOpen: false,
         brandDropdownOpen: false,
         catSearch: '',
         brandSearch: '',
         get filteredCategories() {
             if (!this.catSearch.trim()) return categories;
             const query = this.catSearch.toLowerCase();
             return categories.filter(c => c.name.toLowerCase().includes(query));
         },
         get filteredBrands() {
             if (!this.brandSearch.trim()) return brands;
             const query = this.brandSearch.toLowerCase();
             return brands.filter(b => b.name.toLowerCase().includes(query));
         },
         get selectedCategoryName() {
             if (!categoryId) return '{{ __('messages.pos_all') }}';
             const c = categories.find(x => x.id === categoryId);
             return c ? c.name : '{{ __('messages.pos_all') }}';
         },
         get selectedBrandName() {
             if (!brandId) return '{{ __('messages.pos_all') }}';
             const b = brands.find(x => x.id === brandId);
             return b ? b.name : '{{ __('messages.pos_all') }}';
         }
     }">

    {{-- Row 1: More / Quick Module Links (horizontal chip-scroll) --}}
    <x-pos.chip-scroll :label="__('messages.pos_more')" variant="chips" class="bg-slate-50/60 dark:bg-slate-800/30 rounded-t-2xl">
        @foreach ($moduleLinks as $link)
            @if (!empty($link['is_action']))
                <button type="button" @click="openExpenseModal()"
                        class="{{ $link['btn_3d'] }} shrink-0 snap-start inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold cursor-pointer text-white shadow-xs">
                    <svg class="w-3.5 h-3.5 shrink-0 text-white/95" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $link['icon'] !!}</svg>
                    <span class="text-white">{{ __('messages.' . $link['label']) }}</span>
                </button>
            @else
                <a href="{{ url('/store/' . $store->slug . '/' . $link['path']) }}"
                   @if (!empty($link['target_blank'])) target="_blank" rel="noopener noreferrer" @endif
                   class="{{ $link['btn_3d'] }} shrink-0 snap-start inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold cursor-pointer text-white shadow-xs">
                    <svg class="w-3.5 h-3.5 shrink-0 text-white/95" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $link['icon'] !!}</svg>
                    <span class="text-white">{{ __('messages.' . $link['label']) }}</span>
                </a>
            @endif
        @endforeach
    </x-pos.chip-scroll>

    {{-- Row 2: Search, Barcode Scan, Web Orders + Categories & Brands Dropdown Filters --}}
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t border-slate-100 dark:border-slate-800 rounded-b-2xl bg-white dark:bg-slate-900">
        <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-0">
            
            {{-- Search (barcode / SKU / name — F1) --}}
            <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">
                    {{ __('messages.search') }}
                </label>
                <div class="relative w-60 xl:w-68">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-blue-600 dark:text-blue-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <input id="pos-search-input" type="text" x-ref="searchInput" x-model="q" @input="onSearch()" @keydown.enter.prevent="loadGrid(true)" @keydown.escape.prevent="if (q) { q = ''; loadGrid(); }"
                           placeholder="{{ __('messages.pos_search_placeholder') }}"
                           class="w-full h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/60 pl-9 pr-11 text-xs font-bold placeholder:font-semibold focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-slate-900 outline-none transition">
                    <span class="hidden sm:inline absolute right-2 top-1/2 -translate-y-1/2 px-1.5 py-0.5 rounded-md bg-blue-600/10 text-blue-600 dark:text-blue-400 text-[9px] font-black">F1</span>
                </div>
            </div>

            {{-- Scan barcode button --}}
            <div class="self-end">
                <button type="button" @click="openBarcodeScanner()"
                        class="sf-btn-3d-primary shrink-0 w-10 h-10 rounded-xl grid place-items-center cursor-pointer"
                        title="{{ __('messages.pos_scan_barcode') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/><path d="M7 8h10"/><path d="M7 16h10"/></svg>
                </button>
            </div>

            {{-- Import web order button --}}
            <div class="self-end">
                <button type="button" @click="openWebOrders()"
                        class="sf-btn-3d-primary shrink-0 w-10 h-10 rounded-xl grid place-items-center cursor-pointer"
                        title="{{ __('messages.pos_import_web_order') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                </button>
            </div>

            <div class="self-end mb-2 h-6 w-px bg-slate-200 dark:bg-slate-700 hidden sm:block"></div>

            {{-- Category Dropdown Filter --}}
            <div class="relative" @click.outside="catDropdownOpen = false">
                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">
                    {{ __('messages.categories') }}
                </label>
                <button type="button"
                        @click="catDropdownOpen = !catDropdownOpen; brandDropdownOpen = false; if (catDropdownOpen) $nextTick(() => $refs.catSearchInput?.focus())"
                        class="sf-btn-3d min-w-[190px] max-w-[240px] h-10 px-3 rounded-xl text-xs font-bold flex items-center justify-between gap-2 cursor-pointer"
                        :class="categoryId > 0 
                            ? 'active ring-2 ring-blue-500/20' 
                            : ''">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-3.5 h-3.5 shrink-0" :class="categoryId > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
                        <span class="truncate font-black" x-text="selectedCategoryName"></span>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <span x-show="categoryId > 0" @click.stop="toggleCategory(0)" class="w-4 h-4 rounded-full bg-blue-200 dark:bg-blue-800 text-blue-700 dark:text-blue-200 hover:bg-blue-300 grid place-items-center text-[10px] font-black" title="{{ __('messages.clear') }}">✕</span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="catDropdownOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </button>

                {{-- Dropdown Menu --}}
                <div x-show="catDropdownOpen" x-cloak x-transition
                     class="absolute left-0 top-full mt-1.5 w-72 max-h-80 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl z-50 p-2 flex flex-col">
                    {{-- Search in categories --}}
                    <div class="relative mb-2">
                        <svg class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <input type="text" x-ref="catSearchInput" x-model="catSearch" placeholder="{{ __('messages.pos_search_placeholder') }}"
                               class="w-full h-8 pl-8 pr-3 text-xs font-semibold rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="overflow-y-auto flex-1 space-y-0.5 max-h-60 pr-1 [scrollbar-width:thin]">
                        {{-- All option --}}
                        <button type="button" @click="toggleCategory(0); catDropdownOpen = false; catSearch = ''"
                                class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-bold flex items-center justify-between transition cursor-pointer"
                                :class="categoryId === 0 ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'">
                            <span>{{ __('messages.pos_all') }}</span>
                            <span x-show="categoryId === 0" class="font-black">✓</span>
                        </button>
                        <template x-for="c in filteredCategories" :key="'cat-dd-' + c.id">
                            <button type="button" @click="toggleCategory(c.id); catDropdownOpen = false; catSearch = ''"
                                    class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-bold flex items-center justify-between transition cursor-pointer"
                                    :class="categoryId === c.id ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'">
                                <span class="truncate" x-text="c.name"></span>
                                <span x-show="categoryId === c.id" class="font-black shrink-0 ml-1">✓</span>
                            </button>
                        </template>
                        <div x-show="filteredCategories.length === 0" class="p-3 text-center text-xs text-slate-400 font-medium">
                            {{ __('messages.no_results') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Brand Dropdown Filter --}}
            <div class="relative" @click.outside="brandDropdownOpen = false">
                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">
                    {{ __('messages.brands') }}
                </label>
                <button type="button"
                        @click="brandDropdownOpen = !brandDropdownOpen; catDropdownOpen = false; if (brandDropdownOpen) $nextTick(() => $refs.brandSearchInput?.focus())"
                        class="sf-btn-3d min-w-[190px] max-w-[240px] h-10 px-3 rounded-xl text-xs font-bold flex items-center justify-between gap-2 cursor-pointer"
                        :class="brandId > 0 
                            ? 'active ring-2 ring-blue-500/20' 
                            : ''">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-3.5 h-3.5 shrink-0" :class="brandId > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><path d="M7 7h.01"/></svg>
                        <span class="truncate font-black" x-text="selectedBrandName"></span>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <span x-show="brandId > 0" @click.stop="toggleBrand(0)" class="w-4 h-4 rounded-full bg-blue-200 dark:bg-blue-800 text-blue-700 dark:text-blue-200 hover:bg-blue-300 grid place-items-center text-[10px] font-black" title="{{ __('messages.clear') }}">✕</span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="brandDropdownOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </button>

                {{-- Dropdown Menu --}}
                <div x-show="brandDropdownOpen" x-cloak x-transition
                     class="absolute left-0 top-full mt-1.5 w-72 max-h-80 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl z-50 p-2 flex flex-col">
                    {{-- Search in brands --}}
                    <div class="relative mb-2">
                        <svg class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <input type="text" x-ref="brandSearchInput" x-model="brandSearch" placeholder="{{ __('messages.pos_search_placeholder') }}"
                               class="w-full h-8 pl-8 pr-3 text-xs font-semibold rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="overflow-y-auto flex-1 space-y-0.5 max-h-60 pr-1 [scrollbar-width:thin]">
                        {{-- All option --}}
                        <button type="button" @click="toggleBrand(0); brandDropdownOpen = false; brandSearch = ''"
                                class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-bold flex items-center justify-between transition cursor-pointer"
                                :class="brandId === 0 ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'">
                            <span>{{ __('messages.pos_all') }}</span>
                            <span x-show="brandId === 0" class="font-black">✓</span>
                        </button>
                        <template x-for="b in filteredBrands" :key="'brand-dd-' + b.id">
                            <button type="button" @click="toggleBrand(b.id); brandDropdownOpen = false; brandSearch = ''"
                                    class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-bold flex items-center justify-between transition cursor-pointer"
                                    :class="brandId === b.id ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'">
                                <span class="truncate" x-text="b.name"></span>
                                <span x-show="brandId === b.id" class="font-black shrink-0 ml-1">✓</span>
                            </button>
                        </template>
                        <div x-show="filteredBrands.length === 0" class="p-3 text-center text-xs text-slate-400 font-medium">
                            {{ __('messages.no_results') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Filters Reset Button --}}
            <div class="self-end pb-0.5" x-show="categoryId > 0 || brandId > 0 || q" x-cloak>
                <button type="button" @click="toggleCategory(0); toggleBrand(0); q = ''; loadGrid()"
                        class="h-10 px-3 rounded-xl border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 text-xs font-bold hover:bg-rose-100 dark:hover:bg-rose-900/60 transition inline-flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    <span>{{ __('messages.clear_filters') }}</span>
                </button>
            </div>
        </div>

        {{-- Active filter pill badges --}}
        <div class="flex items-center gap-2 self-end pb-0.5 text-xs">
            <span x-show="categoryId > 0" x-cloak class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800">
                <span class="text-[10px] text-blue-500 font-normal uppercase">{{ __('messages.categories') }}:</span>
                <span x-text="selectedCategoryName"></span>
                <button type="button" @click="toggleCategory(0)" class="hover:text-rose-600 font-black ml-0.5">✕</button>
            </span>
            <span x-show="brandId > 0" x-cloak class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800">
                <span class="text-[10px] text-blue-500 font-normal uppercase">{{ __('messages.brands') }}:</span>
                <span x-text="selectedBrandName"></span>
                <button type="button" @click="toggleBrand(0)" class="hover:text-rose-600 font-black ml-0.5">✕</button>
            </span>
        </div>
    </div>
</div>

