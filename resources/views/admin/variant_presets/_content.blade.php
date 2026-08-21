
@php
    $editingPreset = $editingPreset ?? null;
    $initialOptions = old('options', $editingPreset?->options ?? [[
        'name' => '',
        'sku_suffix' => '',
        'retail_price_adjustment' => 0,
        'wholesale_price_adjustment' => 0,
        'stock_status' => 'in_stock',
    ]]);
    $totalRows = $presets->sum(fn ($preset) => count($preset->options ?? []));
    $stockRows = $presets->sum(fn ($preset) => collect($preset->options ?? [])->where('stock_status', 'in_stock')->count());
    $outRows = max(0, $totalRows - $stockRows);
    $familyOptions = [
        '' => __('messages.variant_preset_family_all'),
        'mobile' => __('messages.variant_preset_family_mobile'),
        'accessories' => __('messages.variant_preset_family_accessories'),
        'cctv' => __('messages.variant_preset_family_cctv'),
        'computer' => __('messages.variant_preset_family_computer'),
        'network' => __('messages.variant_preset_family_network'),
        'fashion' => __('messages.variant_preset_family_fashion'),
    ];
@endphp

<div class="w-full space-y-5 sm:space-y-6">
    @if($embedded ?? false)
        {{-- Embedded inside Master Data hub: only the action CTAs are visible (no duplicate title/stats) --}}
        <div class="flex justify-end items-center gap-2">
            <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}"
                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-violet-600 px-4 py-2 text-sm font-black text-white shadow-sm transition hover:bg-violet-700">
                {{ __('messages.variant_preset_product_create') }}
            </a>
            @if ($editingPreset)
                <a href="{{ route('store.admin.variant-presets.index', ['store_slug' => $store->slug]) }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    {{ __('messages.variant_preset_cancel_edit') }}
                </a>
            @endif
        </div>
    @else
        {{-- Standalone full page: title + CTAs + subtitle + stat grid --}}
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">{{ __('messages.variant_preset_title') }}</h1>
                <p class="admin-page-sub">{{ $store->name }} · {{ __('messages.variant_preset_subtitle') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-violet-600 px-4 py-2 text-sm font-black text-white shadow-sm transition hover:bg-violet-700">
                    {{ __('messages.variant_preset_product_create') }}
                </a>
                @if ($editingPreset)
                    <a href="{{ route('store.admin.variant-presets.index', ['store_slug' => $store->slug]) }}"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        {{ __('messages.variant_preset_cancel_edit') }}
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="p-3.5 sm:p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-start gap-2">
            <span class="text-base flex-shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-3.5 sm:p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300 space-y-1">
            <div class="font-bold">{{ __('messages.variant_preset_check_fields') }}</div>
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @unless($embedded ?? false)
        <div class="admin-hairline-grid grid-cols-2 lg:grid-cols-4">
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">{{ __('messages.variant_preset_presets') }}</div>
                <div class="admin-stat-value">{{ number_format($presets->count()) }}</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">{{ __('messages.variant_preset_total_rows') }}</div>
                <div class="admin-stat-value">{{ number_format($totalRows) }}</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">{{ __('messages.variant_preset_in_stock_rows') }}</div>
                <div class="admin-stat-value">{{ number_format($stockRows) }}</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label text-rose-600 dark:text-rose-400">{{ __('messages.variant_preset_out_rows') }}</div>
                <div class="admin-stat-value">{{ number_format($outRows) }}</div>
            </div>
        </div>
    @endunless

    <div
        x-data="{
            open: {{ $editingPreset || $errors->any() ? 'true' : 'false' }},
            options: @js(collect($initialOptions)->map(fn($option) => [
                'name' => $option['name'] ?? '',
                'sku_suffix' => $option['sku_suffix'] ?? '',
                'retail_price_adjustment' => $option['retail_price_adjustment'] ?? 0,
                'wholesale_price_adjustment' => $option['wholesale_price_adjustment'] ?? 0,
                'stock_status' => $option['stock_status'] ?? 'in_stock',
            ])->values()),
            addOption() {
                this.options.push({ name: '', sku_suffix: '', retail_price_adjustment: 0, wholesale_price_adjustment: 0, stock_status: 'in_stock' });
            },
            removeOption(index) {
                if (this.options.length > 1) this.options.splice(index, 1);
            }
        }"
        class="admin-panel overflow-hidden"
    >
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-3 p-4 sm:p-5 text-left hover:bg-gray-50 dark:hover:bg-slate-700/40 transition">
            <span class="flex items-center gap-3 min-w-0">
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300">+</span>
                <span class="min-w-0">
                    <span class="block text-sm sm:text-base font-black text-gray-900 dark:text-slate-100">{{ $editingPreset ? __('messages.variant_preset_edit_title') : __('messages.variant_preset_add_title') }}</span>
                    <span class="block text-xs text-gray-500 dark:text-slate-400 truncate">{{ __('messages.variant_preset_form_hint') }}</span>
                </span>
            </span>
            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <form x-show="open" x-transition method="POST" action="{{ $editingPreset ? route('store.admin.variant-presets.update', ['store_slug' => $store->slug, 'variantPreset' => $editingPreset]) : route('store.admin.variant-presets.store', ['store_slug' => $store->slug]) }}" class="border-t border-gray-100 p-4 sm:p-5 space-y-4 dark:border-slate-700">
            @csrf
            @if ($editingPreset)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_12rem_9rem] gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.variant_preset_name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $editingPreset?->name) }}" required maxlength="100" placeholder="{{ __('messages.variant_preset_name_placeholder') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white text-gray-900 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.variant_preset_category_family') }}</label>
                    <select name="category_family" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white text-gray-900 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                        @foreach ($familyOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('category_family', $editingPreset?->category_family) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.variant_preset_sort') }}</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $editingPreset?->sort_order ?? 0) }}" min="0" max="9999" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white text-gray-900 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                </div>
            </div>

            <div class="rounded-xl overflow-hidden">
                <div class="flex items-center justify-between gap-3 bg-gray-50 px-3 py-2.5 dark:bg-slate-900/60">
                    <div>
                        <h3 class="text-xs font-black uppercase text-gray-600 dark:text-slate-300">{{ __('messages.variant_preset_options') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.variant_preset_price_hint') }}</p>
                    </div>
                    <button type="button" @click="addOption()" class="shrink-0 rounded-lg border border-violet-300 bg-white px-3 py-1.5 text-xs font-bold text-violet-700 hover:bg-violet-50 dark:border-violet-700 dark:bg-slate-800 dark:text-violet-300 dark:hover:bg-violet-950/30">{{ __('messages.variant_preset_add_row') }}</button>
                </div>

                <div class="hidden lg:grid grid-cols-[1.4fr_0.8fr_0.8fr_0.8fr_0.9fr_4rem] gap-2 border-t border-gray-100 bg-white px-3 py-2 text-xs font-black uppercase text-gray-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500">
                    <span>{{ __('messages.variant_preset_option_name') }}</span>
                    <span>{{ __('messages.variant_preset_sku_suffix') }}</span>
                    <span>{{ __('messages.variant_preset_retail_adjustment') }}</span>
                    <span>{{ __('messages.variant_preset_wholesale_adjustment') }}</span>
                    <span>{{ __('messages.variant_preset_stock') }}</span>
                    <span></span>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-slate-700">
                    <template x-for="(option, index) in options" :key="index">
                        <div class="grid grid-cols-1 lg:grid-cols-[1.4fr_0.8fr_0.8fr_0.8fr_0.9fr_4rem] gap-2 p-3 bg-white dark:bg-slate-800">
                            <div>
                                <label class="lg:hidden text-xs font-bold text-gray-500 dark:text-slate-400 uppercase">{{ __('messages.variant_preset_option_name') }} *</label>
                                <input type="text" x-model="option.name" :name="'options[' + index + '][name]'" required maxlength="100" class="mt-1 lg:mt-0 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" placeholder="256GB" />
                            </div>
                            <div>
                                <label class="lg:hidden text-xs font-bold text-gray-500 dark:text-slate-400 uppercase">{{ __('messages.variant_preset_sku_suffix') }}</label>
                                <input type="text" x-model="option.sku_suffix" :name="'options[' + index + '][sku_suffix]'" maxlength="50" class="mt-1 lg:mt-0 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" placeholder="256" />
                            </div>
                            <div>
                                <label class="lg:hidden text-xs font-bold text-gray-500 dark:text-slate-400 uppercase">{{ __('messages.variant_preset_retail_adjustment') }}</label>
                                <input type="number" step="0.01" x-model="option.retail_price_adjustment" :name="'options[' + index + '][retail_price_adjustment]'" class="mt-1 lg:mt-0 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                            </div>
                            <div>
                                <label class="lg:hidden text-xs font-bold text-gray-500 dark:text-slate-400 uppercase">{{ __('messages.variant_preset_wholesale_adjustment') }}</label>
                                <input type="number" step="0.01" x-model="option.wholesale_price_adjustment" :name="'options[' + index + '][wholesale_price_adjustment]'" class="mt-1 lg:mt-0 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                            </div>
                            <div>
                                <label class="lg:hidden text-xs font-bold text-gray-500 dark:text-slate-400 uppercase">{{ __('messages.variant_preset_stock') }}</label>
                                <select x-model="option.stock_status" :name="'options[' + index + '][stock_status]'" class="mt-1 lg:mt-0 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                                    <option value="in_stock">{{ __('messages.in_stock') }}</option>
                                    <option value="out_of_stock">{{ __('messages.out_of_stock') }}</option>
                                </select>
                            </div>
                            <button type="button" @click="removeOption(index)" class="rounded-lg px-2 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-rose-950/40" :disabled="options.length === 1">
                                {{ __('messages.variant_preset_remove') }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-slate-700">
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.variant_preset_combine_hint') }}</p>
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-violet-600 px-5 py-2 text-sm font-black text-white shadow-sm hover:bg-violet-700">
                    {{ $editingPreset ? __('messages.variant_preset_update') : __('messages.variant_preset_save') }}
                </button>
            </div>
        </form>
    </div>

    <div
        x-data="{
            search: '',
            filter: 'all',
            viewMode: localStorage.getItem('admin_view_mode') || 'card',
            visibleCount: {{ $presets->count() }},
            matches(card) {
                const query = this.search.trim().toLowerCase();
                const matchesSearch = query === '' || card.dataset.search.includes(query);
                const matchesFilter = this.filter === 'all'
                    || (this.filter === 'adjusted' && card.dataset.adjusted === '1')
                    || (this.filter === 'plain' && card.dataset.adjusted === '0')
                    || (this.filter === 'out' && card.dataset.hasOut === '1')
                    || card.dataset.family === this.filter;

                return matchesSearch && matchesFilter;
            },
            refresh() {
                this.$nextTick(() => {
                    this.visibleCount = Array.from(this.$refs.cards.querySelectorAll('[data-preset-card]'))
                        .filter((card) => this.matches(card))
                        .length;
                });
            }
        }"
        x-init="$watch('search', () => refresh()); $watch('filter', () => refresh()); refresh();"
        class="space-y-3"
    >
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div class="min-w-0">
                <h2 class="text-base font-black text-gray-900 dark:text-slate-100">{{ __('messages.variant_preset_saved_title') }}</h2>
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.variant_preset_saved_hint') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="text-xs font-bold text-gray-500 dark:text-slate-400">
                    <span x-text="visibleCount"></span> {{ __('messages.variant_preset_shown') }}
                </div>
                <div class="flex rounded-lg border border-gray-200 dark:border-slate-600 overflow-hidden">
                    <button type="button" @click="viewMode = 'table'; localStorage.setItem('admin_view_mode', 'table')"
                        :class="viewMode === 'table' ? 'bg-violet-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700'"
                        class="p-2 transition" title="Table view">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M3 6h18M3 18h18"/></svg>
                    </button>
                    <button type="button" @click="viewMode = 'card'; localStorage.setItem('admin_view_mode', 'card')"
                        :class="viewMode === 'card' ? 'bg-violet-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700'"
                        class="p-2 transition" title="Card view">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto] gap-3">
                <div>
                    <label class="sr-only" for="variant-preset-search">{{ __('messages.variant_preset_search_label') }}</label>
                    <input id="variant-preset-search" type="search" x-model="search" placeholder="{{ __('messages.variant_preset_search_placeholder') }}" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                </div>
                <div class="grid grid-cols-2 sm:flex sm:items-center gap-2">
                    <button type="button" @click="filter = 'all'" :class="filter === 'all' ? 'bg-violet-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600'" class="rounded-lg px-3 py-2 text-xs font-black transition">{{ __('messages.variant_preset_filter_all') }}</button>
                    <button type="button" @click="filter = 'adjusted'" :class="filter === 'adjusted' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/50'" class="rounded-lg px-3 py-2 text-xs font-black transition">{{ __('messages.variant_preset_filter_adjusted') }}</button>
                    <button type="button" @click="filter = 'plain'" :class="filter === 'plain' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50'" class="rounded-lg px-3 py-2 text-xs font-black transition">{{ __('messages.variant_preset_filter_plain') }}</button>
                    <button type="button" @click="filter = 'out'" :class="filter === 'out' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-900/50'" class="rounded-lg px-3 py-2 text-xs font-black transition">{{ __('messages.variant_preset_filter_out') }}</button>
                    <button type="button" @click="filter = 'mobile'" :class="filter === 'mobile' ? 'bg-sky-600 text-white' : 'bg-sky-50 text-sky-700 hover:bg-sky-100 dark:bg-sky-950/40 dark:text-sky-300 dark:hover:bg-sky-900/50'" class="rounded-lg px-3 py-2 text-xs font-black transition">{{ __('messages.variant_preset_family_mobile') }}</button>
                    <button type="button" @click="filter = 'fashion'" :class="filter === 'fashion' ? 'bg-fuchsia-600 text-white' : 'bg-fuchsia-50 text-fuchsia-700 hover:bg-fuchsia-100 dark:bg-fuchsia-950/40 dark:text-fuchsia-300 dark:hover:bg-fuchsia-900/50'" class="rounded-lg px-3 py-2 text-xs font-black transition">{{ __('messages.variant_preset_family_fashion') }}</button>
                    <button type="button" @click="filter = 'network'" :class="filter === 'network' ? 'bg-teal-600 text-white' : 'bg-teal-50 text-teal-700 hover:bg-teal-100 dark:bg-teal-950/40 dark:text-teal-300 dark:hover:bg-teal-900/50'" class="rounded-lg px-3 py-2 text-xs font-black transition">{{ __('messages.variant_preset_family_network') }}</button>
                    <button type="button" @click="search = ''; filter = 'all'" class="rounded-lg bg-white px-3 py-2 text-xs font-black text-gray-600 transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-700">{{ __('messages.variant_preset_filter_clear') }}</button>
                </div>
            </div>
        </div>

        {{-- Table View --}}
        <div x-show="viewMode === 'table'" x-cloak class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm text-gray-600 dark:text-slate-300">
                <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-semibold text-gray-700 dark:text-slate-200">
                    <tr>
                        <th class="p-3">{{ __('messages.variant_preset_option_name') }}</th>
                        <th class="p-3 hidden sm:table-cell">{{ __('messages.variant_preset_category_family') }}</th>
                        <th class="p-3 text-center">{{ __('messages.variant_preset_rows') }}</th>
                        <th class="p-3 text-center hidden md:table-cell">{{ __('messages.variant_preset_sku_suffix') }}</th>
                        <th class="p-3 text-center">{{ __('messages.variant_preset_stock') }}</th>
                        <th class="p-3 text-right">{{ __('messages.variant_preset_col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse ($presets as $preset)
                        @php
                            $options = $preset->options ?? [];
                            $hasAdjustments = collect($options)->contains(fn ($option) => ((float) ($option['retail_price_adjustment'] ?? 0)) !== 0.0 || ((float) ($option['wholesale_price_adjustment'] ?? 0)) !== 0.0);
                            $hasOutRows = collect($options)->contains(fn ($option) => ($option['stock_status'] ?? 'in_stock') === 'out_of_stock');
                            $family = $preset->category_family ?? '';
                            $searchText = strtolower(collect([
                                $preset->name,
                                $familyOptions[$family] ?? $family,
                                $preset->sort_order,
                                collect($options)->pluck('name')->implode(' '),
                                collect($options)->pluck('sku_suffix')->implode(' '),
                            ])->filter()->implode(' '));
                        @endphp
                        <tr data-preset-card data-search="{{ e($searchText) }}" data-adjusted="{{ $hasAdjustments ? '1' : '0' }}" data-has-out="{{ $hasOutRows ? '1' : '0' }}" data-family="{{ $family }}"
                            x-show="matches($el)" x-transition.opacity
                            class="{{ $editingPreset?->id === $preset->id ? 'bg-violet-50/60 dark:bg-violet-950/20' : '' }}">
                            <td class="p-3">
                                <div class="font-bold text-gray-900 dark:text-slate-100 text-sm break-words">{{ $preset->name }}
                                    @if ($editingPreset?->id === $preset->id)
                                        <span class="ml-1 inline-block px-1.5 py-0.5 rounded-full bg-violet-600 text-white text-[10px] font-bold">{{ __('messages.variant_preset_editing') }}</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-gray-400 dark:text-slate-500 truncate">{{ collect($options)->pluck('name')->take(4)->implode(', ') }}</div>
                            </td>
                            <td class="p-3 hidden sm:table-cell text-xs font-medium">
                                <span class="rounded-full bg-sky-100 px-2 py-0.5 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">{{ $familyOptions[$family] ?? __('messages.variant_preset_family_all') }}</span>
                            </td>
                            <td class="p-3 text-center tabular-nums font-semibold">{{ count($options) }}</td>
                            <td class="p-3 text-center hidden md:table-cell text-xs font-mono text-gray-500 dark:text-slate-400">
                                {{ collect($options)->pluck('sku_suffix')->filter()->take(2)->implode(', ') ?: '—' }}
                            </td>
                            <td class="p-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $hasAdjustments ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' }}">
                                    {{ $hasAdjustments ? __('messages.variant_preset_filter_adjusted') : __('messages.variant_preset_no_price_adjustment') }}
                                </span>
                            </td>
                            <td class="p-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('store.admin.variant-presets.edit', ['store_slug' => $store->slug, 'variantPreset' => $preset]) }}"
                                        class="min-h-11 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                                        {{ __('messages.edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('store.admin.variant-presets.destroy', ['store_slug' => $store->slug, 'variantPreset' => $preset]) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" data-confirm="{{ __('messages.variant_preset_delete_confirm') }}"
                                            class="min-h-11 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.9 12.1A2 2 0 0 1 16.1 21H7.9a2 2 0 0 1-2-1.9L5 7m5-4h4a1 1 0 0 1 1 1v2H9V4a1 1 0 0 1 1-1ZM4 7h16"/></svg>
                                            {{ __('messages.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-sm text-gray-500 dark:text-slate-400">{{ __('messages.variant_preset_empty_title') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Card View --}}
        <div x-show="viewMode === 'card'" x-cloak x-ref="cards" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
            @forelse ($presets as $preset)
                @php
                    $options = $preset->options ?? [];
                    $previewOptions = array_slice($options, 0, 6);
                    $hasAdjustments = collect($options)->contains(fn ($option) => ((float) ($option['retail_price_adjustment'] ?? 0)) !== 0.0 || ((float) ($option['wholesale_price_adjustment'] ?? 0)) !== 0.0);
                    $hasOutRows = collect($options)->contains(fn ($option) => ($option['stock_status'] ?? 'in_stock') === 'out_of_stock');
                    $family = $preset->category_family ?? '';
                    $searchText = strtolower(collect([
                        $preset->name,
                        $familyOptions[$family] ?? $family,
                        $preset->sort_order,
                        collect($options)->pluck('name')->implode(' '),
                        collect($options)->pluck('sku_suffix')->implode(' '),
                    ])->filter()->implode(' '));
                @endphp
                <div
                    x-data="{ expanded: {{ $editingPreset?->id === $preset->id ? 'true' : 'false' }} }"
                    x-show="matches($el)"
                    x-transition.opacity
                    data-preset-card
                    data-search="{{ e($searchText) }}"
                    data-adjusted="{{ $hasAdjustments ? '1' : '0' }}"
                    data-has-out="{{ $hasOutRows ? '1' : '0' }}"
                    data-family="{{ $family }}"
                    class="rounded-xl border {{ $editingPreset?->id === $preset->id ? 'border-violet-400 ring-2 ring-violet-500/20' : 'border-gray-200 dark:border-slate-700' }} bg-white p-4 shadow-sm transition hover:shadow-md dark:bg-slate-800"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 min-w-0">
                                <h3 class="truncate text-sm font-black text-gray-900 dark:text-slate-100">{{ $preset->name }}</h3>
                                @if ($editingPreset?->id === $preset->id)
                                    <span class="shrink-0 rounded-full bg-violet-600 px-2 py-0.5 text-xs font-black text-white">{{ __('messages.variant_preset_editing') }}</span>
                                @endif
                            </div>
                            <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-black text-gray-600 dark:bg-slate-700 dark:text-slate-300">{{ count($options) }} {{ __('messages.variant_preset_rows') }}</span>
                                <span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-black text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">{{ $familyOptions[$family] ?? __('messages.variant_preset_family_all') }}</span>
                                <span class="rounded-full {{ $hasAdjustments ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' }} px-2 py-0.5 text-xs font-black">
                                    {{ $hasAdjustments ? __('messages.variant_preset_filter_adjusted') : __('messages.variant_preset_no_price_adjustment') }}
                                </span>
                            </div>
                        </div>
                        <span class="rounded-lg bg-violet-50 px-2 py-1 text-xs font-black text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">#{{ $preset->sort_order }}</span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-1.5 min-h-8">
                        @foreach ($previewOptions as $option)
                            <span class="rounded-full px-2 py-1 text-xs font-semibold text-gray-700 dark:border-slate-700 dark:text-slate-300">
                                {{ $option['name'] ?? '' }}
                            </span>
                        @endforeach
                        @if (count($options) > count($previewOptions))
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-black text-gray-500 dark:bg-slate-700 dark:text-slate-400">+{{ count($options) - count($previewOptions) }}</span>
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-slate-900/60">
                            <div class="font-bold text-gray-500 dark:text-slate-400">{{ __('messages.variant_preset_sku_samples') }}</div>
                            <div class="mt-0.5 truncate font-mono text-gray-800 dark:text-slate-200">
                                {{ collect($options)->pluck('sku_suffix')->filter()->take(2)->implode(', ') ?: __('messages.variant_preset_none') }}
                            </div>
                        </div>
                        <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-slate-900/60">
                            <div class="font-bold text-gray-500 dark:text-slate-400">{{ __('messages.variant_preset_stock') }}</div>
                            <div class="mt-0.5 font-black text-gray-800 dark:text-slate-200">
                                {{ collect($options)->where('stock_status', 'in_stock')->count() }}/{{ count($options) }}
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="expanded = !expanded" data-test-label="View Rows" class="mt-3 w-full rounded-lg px-3 py-2 text-xs font-black text-gray-600 hover:bg-gray-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700/60">
                        <span x-text="expanded ? @js(__('messages.variant_preset_hide_rows')) : @js(__('messages.variant_preset_view_rows'))"></span>
                    </button>

                    <div x-show="expanded" x-transition class="mt-3 overflow-hidden rounded-lg">
                        <div class="grid grid-cols-[1fr_4.5rem_4.5rem] gap-2 bg-gray-50 px-3 py-2 text-xs font-black uppercase text-gray-500 dark:bg-slate-900/60 dark:text-slate-400">
                            <span>{{ __('messages.variant_preset_option') }}</span>
                            <span>SKU</span>
                            <span>{{ __('messages.variant_preset_stock') }}</span>
                        </div>
                        <div class="max-h-56 overflow-y-auto divide-y divide-gray-100 dark:divide-slate-700">
                            @foreach ($options as $option)
                                <div class="grid grid-cols-[1fr_4.5rem_4.5rem] gap-2 px-3 py-2 text-xs">
                                    <span class="min-w-0 truncate font-semibold text-gray-800 dark:text-slate-100">{{ $option['name'] ?? '' }}</span>
                                    <span class="truncate font-mono text-gray-500 dark:text-slate-400">{{ $option['sku_suffix'] ?? '-' }}</span>
                                    <span class="font-bold {{ ($option['stock_status'] ?? 'in_stock') === 'in_stock' ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                                        {{ ($option['stock_status'] ?? 'in_stock') === 'in_stock' ? __('messages.variant_preset_in_short') : __('messages.variant_preset_out_short') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <a href="{{ route('store.admin.variant-presets.edit', ['store_slug' => $store->slug, 'variantPreset' => $preset]) }}" class="flex-1 text-center rounded-lg bg-violet-50 px-3 py-2 text-xs font-black text-violet-700 hover:bg-violet-100 dark:bg-violet-950/50 dark:text-violet-300 dark:hover:bg-violet-900/60">
                            {{ __('messages.variant_preset_edit') }}
                        </a>
                        <form method="POST" action="{{ route('store.admin.variant-presets.duplicate', ['store_slug' => $store->slug, 'variantPreset' => $preset]) }}">
                            @csrf
                            <button type="submit" data-test-label="Duplicate" class="w-full rounded-lg bg-sky-50 px-3 py-2 text-xs font-black text-sky-700 hover:bg-sky-100 dark:bg-sky-950/50 dark:text-sky-300 dark:hover:bg-sky-900/60">
                                {{ __('messages.variant_preset_duplicate') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('store.admin.variant-presets.move', ['store_slug' => $store->slug, 'variantPreset' => $preset]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="direction" value="up" />
                            <button type="submit" data-test-label="Move Up" class="w-full rounded-lg bg-gray-50 px-3 py-2 text-xs font-black text-gray-700 hover:bg-gray-100 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-700">
                                {{ __('messages.variant_preset_move_up') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('store.admin.variant-presets.move', ['store_slug' => $store->slug, 'variantPreset' => $preset]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="direction" value="down" />
                            <button type="submit" data-test-label="Move Down" class="w-full rounded-lg bg-gray-50 px-3 py-2 text-xs font-black text-gray-700 hover:bg-gray-100 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-700">
                                {{ __('messages.variant_preset_move_down') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('store.admin.variant-presets.destroy', ['store_slug' => $store->slug, 'variantPreset' => $preset]) }}" class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" data-confirm="{{ __('messages.variant_preset_delete_confirm') }}" class="w-full rounded-lg bg-rose-50 px-3 py-2 text-xs font-black text-rose-700 hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-300 dark:hover:bg-rose-900/60">
                                {{ __('messages.variant_preset_delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="md:col-span-2 xl:col-span-3 rounded-xl bg-white p-8 text-center dark:border-slate-700 dark:bg-slate-800">
                    <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">{{ __('messages.variant_preset_empty_title') }}</div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.variant_preset_empty_hint') }}</div>
                </div>
            @endforelse

            @if ($presets->isNotEmpty())
                <div x-show="visibleCount === 0" x-cloak class="md:col-span-2 xl:col-span-3 rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center dark:border-slate-700 dark:bg-slate-800">
                    <div class="text-sm font-black text-gray-800 dark:text-slate-100">{{ __('messages.variant_preset_no_match_title') }}</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-slate-400">{{ __('messages.variant_preset_no_match_hint') }}</div>
                </div>
            @endif
        </div>
    </div>
</div>

