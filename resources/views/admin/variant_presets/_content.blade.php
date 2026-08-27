@php
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

    $familyFilterOptions = array_filter([
        'mobile' => __('messages.variant_preset_family_mobile'),
        'accessories' => __('messages.variant_preset_family_accessories'),
        'cctv' => __('messages.variant_preset_family_cctv'),
        'computer' => __('messages.variant_preset_family_computer'),
        'network' => __('messages.variant_preset_family_network'),
        'fashion' => __('messages.variant_preset_family_fashion'),
    ]);
@endphp

<div class="w-full space-y-2 sm:space-y-2.5">
    @unless($embedded ?? false)
        {{-- Header (hidden when embedded inside Master Data hub) --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
            <div>
                <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100">{{ __('messages.variant_preset_title') }}</h1>
                <p class="text-[11px] text-slate-400 font-mono">{{ $store->name }} — {{ __('messages.variant_preset_subtitle') }}</p>
            </div>
            <button type="button" @click="$dispatch('open-variant-create')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition flex items-center gap-1.5 active:scale-95 shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.variant_preset_add_title') }}</span>
            </button>
        </div>
    @endunless

    {{-- Success Flash --}}
    @if (session('success'))
        <div class="p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs text-emerald-700 dark:text-emerald-300 flex items-start gap-2">
            <span class="text-sm font-bold flex-shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Error Flash --}}
    @if ($errors->any())
        <div class="p-2.5 sm:p-3 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-lg text-xs text-red-700 dark:text-red-300 space-y-1">
            <div class="flex items-center gap-2 font-bold"><span>⚠️</span><span>Errors:</span></div>
            @foreach ($errors->all() as $error)
                <div class="pl-6">• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Single Alpine scope: Modal Form + View Mode + Preset Table / Cards + Delete Modal --}}
    <div x-data="{
        modalOpen: false,
        modalMode: 'create', // 'create' or 'edit'
        editId: null,
        formName: '',
        formCategoryFamily: '',
        formSortOrder: 0,
        formOptions: [{ name: '', sku_suffix: '', retail_price_adjustment: 0, wholesale_price_adjustment: 0, stock_status: 'in_stock' }],
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
        saving: false,
        confirmTarget: null,
        deleting: false,

        openCreate() {
            this.modalMode = 'create';
            this.editId = null;
            this.formName = '';
            this.formCategoryFamily = '';
            this.formSortOrder = 0;
            this.formOptions = [
                { name: '', sku_suffix: '', retail_price_adjustment: 0, wholesale_price_adjustment: 0, stock_status: 'in_stock' }
            ];
            this.saving = false;
            this.modalOpen = true;
            this.$nextTick(() => this.$refs.variantModalName?.focus());
        },

        openEdit(preset) {
            this.modalMode = 'edit';
            this.editId = preset.id;
            this.formName = preset.name || '';
            this.formCategoryFamily = preset.category_family || '';
            this.formSortOrder = preset.sort_order || 0;
            this.formOptions = Array.isArray(preset.options) && preset.options.length > 0
                ? JSON.parse(JSON.stringify(preset.options))
                : [{ name: '', sku_suffix: '', retail_price_adjustment: 0, wholesale_price_adjustment: 0, stock_status: 'in_stock' }];
            this.saving = false;
            this.modalOpen = true;
            this.$nextTick(() => this.$refs.variantModalName?.focus());
        },

        addOptionRow() {
            this.formOptions.push({
                name: '',
                sku_suffix: '',
                retail_price_adjustment: 0,
                wholesale_price_adjustment: 0,
                stock_status: 'in_stock'
            });
        },

        removeOptionRow(index) {
            if (this.formOptions.length > 1) {
                this.formOptions.splice(index, 1);
            }
        },

        closeModal() {
            this.modalOpen = false;
        },

        openConfirm(preset) {
            this.confirmTarget = preset;
            this.deleting = false;
        },

        closeConfirm() {
            this.confirmTarget = null;
        }
    }"
    @open-variant-create.window="openCreate()"
    @keydown.escape.window="if (modalOpen) closeModal(); else if (confirmTarget) closeConfirm();"
    @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)"
    class="w-full space-y-2 sm:space-y-2.5">

        {{-- ============================================================
             1. TOOLBAR AREA: Search, Family Filter, View Mode Toggle
             ============================================================ --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <x-admin.toolbar
                :search="request('search', '')"
                searchPlaceholder="{{ __('messages.variant_preset_search_placeholder') }}"
                :sortOptions="[]"
                :filters="[
                    'family' => [
                        'label' => __('messages.variant_preset_category_family'),
                        'options' => $familyFilterOptions
                    ]
                ]"
                :showViewToggle="true"
                :showExportImport="false"
                :totalCount="$presets->count()"
                :paginator="null"
            />
        </div>

        {{-- Floating Action Button for Mobile/Tablet Quick Add --}}
        <button type="button" @click="openCreate()"
                class="fixed bottom-5 right-5 z-40 sm:hidden w-12 h-12 rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-xl shadow-violet-900/40 flex items-center justify-center text-2xl font-bold active:scale-95 transition"
                title="{{ __('messages.variant_preset_add_title') }}">
            +
        </button>

        {{-- ============================================================
             2. SPREADSHEET TABLE VIEW
             ============================================================ --}}
        <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
            <div class="overflow-x-auto max-h-[72vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
                <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                        <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                            <th class="py-2.5 px-3 min-w-[180px]">{{ __('messages.variant_preset_name') }}</th>
                            <th class="py-2.5 px-3 min-w-[120px]">{{ __('messages.variant_preset_category_family') }}</th>
                            <th class="py-2.5 px-3 text-center w-20">{{ __('messages.variant_preset_rows') }}</th>
                            <th class="py-2.5 px-3 min-w-[280px]">Options Preview / Pricing</th>
                            <th class="py-2.5 px-3 text-center w-24">Reorder</th>
                            <th class="py-2.5 px-3 text-right w-36">{{ __('messages.variant_preset_col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @forelse ($presets as $preset)
                            @php
                                $optCount = count($preset->options ?? []);
                            @endphp
                            <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                
                                {{-- Name --}}
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded-lg bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 border border-violet-200 dark:border-violet-800 grid place-items-center text-xs font-black shrink-0">⚡</span>
                                        <span class="font-black text-slate-900 dark:text-slate-100 text-xs sm:text-sm">{{ $preset->name }}</span>
                                    </div>
                                </td>

                                {{-- Family --}}
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ $familyOptions[$preset->category_family] ?? ($preset->category_family ?: 'All Families') }}
                                    </span>
                                </td>

                                {{-- Options count --}}
                                <td class="py-2.5 px-3 text-center font-mono font-bold">{{ $optCount }}</td>

                                {{-- Options preview chips --}}
                                <td class="py-2.5 px-3">
                                    <div class="flex flex-wrap items-center gap-1 max-h-16 overflow-y-auto no-scrollbar">
                                        @foreach ($preset->options ?? [] as $opt)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                                <span>{{ $opt['name'] }}</span>
                                                @if (!empty($opt['retail_price_adjustment']))
                                                    <span class="text-violet-600 dark:text-violet-400 font-sans">({{ ($opt['retail_price_adjustment'] > 0 ? '+' : '') . number_format($opt['retail_price_adjustment']) }})</span>
                                                @endif
                                                @if (($opt['stock_status'] ?? '') === 'out_of_stock')
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500" title="Out of Stock"></span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </td>

                                {{-- Reorder Up/Down --}}
                                <td class="py-2.5 px-3 text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <form method="POST" action="{{ route('store.admin.variant-presets.move', ['store_slug' => $store->slug, 'variantPreset' => $preset]) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="up" />
                                            <button type="submit" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 text-xs" title="Move Up">▲</button>
                                        </form>
                                        <form method="POST" action="{{ route('store.admin.variant-presets.move', ['store_slug' => $store->slug, 'variantPreset' => $preset]) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="down" />
                                            <button type="submit" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 text-xs" title="Move Down">▼</button>
                                        </form>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" @click="openEdit({{ Js::from($preset) }})"
                                            class="px-2 py-1 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition flex items-center gap-1">
                                            <span>✏️</span> {{ __('messages.edit') }}
                                        </button>
                                        <form method="POST" action="{{ route('store.admin.variant-presets.duplicate', ['store_slug' => $store->slug, 'variantPreset' => $preset]) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2 py-1 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition" title="Duplicate">
                                                📑
                                            </button>
                                        </form>
                                        <button type="button" @click="openConfirm({{ Js::from($preset) }})"
                                            class="px-2 py-1 rounded text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 transition active:scale-95" title="Delete">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    <div class="text-3xl mb-2 opacity-55">⚡</div>
                                    <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.variant_preset_empty_title') }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.variant_preset_empty_hint') }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============================================================
             3. RESPONSIVE MULTI-COLUMN CARD GRID
             ============================================================ --}}
        <div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
            @forelse ($presets as $preset)
                @php
                    $optCount = count($preset->options ?? []);
                @endphp
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl overflow-hidden shadow-2xs hover:border-violet-300 dark:hover:border-violet-600/50 hover:shadow-sm transition flex flex-col justify-between group">
                    
                    <div class="p-3 space-y-2">
                        {{-- Card Header: Icon + Category Family Pill --}}
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="w-7 h-7 rounded-lg bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 border border-violet-200 dark:border-violet-800 grid place-items-center text-xs font-black shrink-0">⚡</span>
                                <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 truncate">
                                    {{ $familyOptions[$preset->category_family] ?? ($preset->category_family ?: 'All') }}
                                </span>
                            </div>

                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                                {{ $optCount }} Options
                            </span>
                        </div>

                        {{-- Preset Title --}}
                        <div>
                            <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 line-clamp-1" title="{{ $preset->name }}">
                                {{ $preset->name }}
                            </h4>
                        </div>

                        {{-- Options Pills in Card --}}
                        <div class="flex flex-wrap items-center gap-1 max-h-24 overflow-y-auto no-scrollbar pt-1">
                            @foreach ($preset->options ?? [] as $opt)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-50 dark:bg-slate-800/80 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                    <span>{{ $opt['name'] }}</span>
                                    @if (!empty($opt['retail_price_adjustment']))
                                        <span class="text-violet-600 dark:text-violet-400 font-sans">({{ ($opt['retail_price_adjustment'] > 0 ? '+' : '') . number_format($opt['retail_price_adjustment']) }})</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Footer Actions --}}
                    <div class="p-2.5 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1">
                        <div class="inline-flex items-center gap-1">
                            <button type="button" @click="openEdit({{ Js::from($preset) }})"
                                class="px-2 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1">
                                <span>✏️</span> Edit
                            </button>
                            <form method="POST" action="{{ route('store.admin.variant-presets.duplicate', ['store_slug' => $store->slug, 'variantPreset' => $preset]) }}" class="inline">
                                @csrf
                                <button type="submit" class="px-2 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition" title="Duplicate">
                                    📑
                                </button>
                            </form>
                        </div>

                        <div class="inline-flex items-center gap-1">
                            <form method="POST" action="{{ route('store.admin.variant-presets.move', ['store_slug' => $store->slug, 'variantPreset' => $preset]) }}" class="inline">
                                @csrf
                                <input type="hidden" name="direction" value="up" />
                                <button type="submit" class="p-1 rounded text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-xs" title="Move Up">▲</button>
                            </form>
                            <form method="POST" action="{{ route('store.admin.variant-presets.move', ['store_slug' => $store->slug, 'variantPreset' => $preset]) }}" class="inline">
                                @csrf
                                <input type="hidden" name="direction" value="down" />
                                <button type="submit" class="p-1 rounded text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-xs" title="Move Down">▼</button>
                            </form>
                            <button type="button" @click="openConfirm({{ Js::from($preset) }})"
                                class="px-2 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 text-xs font-bold transition active:scale-95" title="Delete">
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 p-8 rounded-xl text-center text-slate-400 shadow-2xs">
                    <div class="text-3xl mb-2 opacity-55">⚡</div>
                    <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.variant_preset_empty_title') }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.variant_preset_empty_hint') }}</div>
                </div>
            @endforelse
        </div>

        {{-- ============================================================
             4. CREATE / EDIT MODAL (Unified Alpine Dialog)
             ============================================================ --}}
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="closeModal()"></div>
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 space-y-4" @click.stop>
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            <span x-text="modalMode === 'create' ? '➕ {{ __('messages.variant_preset_add_title') }}' : '✏️ {{ __('messages.variant_preset_edit_title') }}: ' + formName"></span>
                        </h3>
                        <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
                    </div>

                    <form method="POST"
                        :action="modalMode === 'create' ? '{{ route('store.admin.variant-presets.store', ['store_slug' => $store->slug]) }}' : '{{ url('/store/' . $store->slug . '/admin/variant-presets') }}/' + editId"
                        @submit="if (saving) { $event.preventDefault(); } else { saving = true; }"
                        class="space-y-4">
                        @csrf
                        <template x-if="modalMode === 'edit'">
                            <input type="hidden" name="_method" value="PUT" />
                        </template>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.variant_preset_name') }} <span class="text-rose-500">*</span></label>
                                <input type="text" x-ref="variantModalName" name="name" x-model="formName" required maxlength="100"
                                    placeholder="e.g. Storage Capacity, Phone Colors"
                                    class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.variant_preset_category_family') }}</label>
                                <select name="category_family" x-model="formCategoryFamily"
                                    class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500">
                                    @foreach ($familyOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Dynamic Options Repeater --}}
                        <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
                            <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800">
                                <span class="text-xs font-black uppercase text-slate-700 dark:text-slate-300">{{ __('messages.variant_preset_options') }}</span>
                                <button type="button" @click="addOptionRow()"
                                    class="px-2.5 py-1 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold transition flex items-center gap-1">
                                    <span>+</span> {{ __('messages.variant_preset_add_row') }}
                                </button>
                            </div>

                            <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 p-2 space-y-2">
                                <template x-for="(opt, idx) in formOptions" :key="idx">
                                    <div class="grid grid-cols-1 sm:grid-cols-[1.5fr_1fr_1fr_1fr_1.2fr_auto] gap-2 items-center bg-slate-50/50 dark:bg-slate-850 p-2 rounded-lg border border-slate-200/60 dark:border-slate-800">
                                        <div>
                                            <label class="block sm:hidden text-[10px] font-bold text-slate-400">Option Name *</label>
                                            <input type="text" :name="'options[' + idx + '][name]'" x-model="opt.name" required placeholder="e.g. 256GB"
                                                class="w-full rounded border border-slate-200 dark:border-slate-700 px-2 py-1.5 text-xs font-bold bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-violet-500" />
                                        </div>
                                        <div>
                                            <label class="block sm:hidden text-[10px] font-bold text-slate-400">SKU Suffix</label>
                                            <input type="text" :name="'options[' + idx + '][sku_suffix]'" x-model="opt.sku_suffix" placeholder="256"
                                                class="w-full rounded border border-slate-200 dark:border-slate-700 px-2 py-1.5 text-xs font-mono bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-violet-500" />
                                        </div>
                                        <div>
                                            <label class="block sm:hidden text-[10px] font-bold text-slate-400">Retail Adj (+/-)</label>
                                            <input type="number" step="0.01" :name="'options[' + idx + '][retail_price_adjustment]'" x-model="opt.retail_price_adjustment"
                                                class="w-full rounded border border-slate-200 dark:border-slate-700 px-2 py-1.5 text-xs font-mono bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-violet-500" />
                                        </div>
                                        <div>
                                            <label class="block sm:hidden text-[10px] font-bold text-slate-400">Wholesale Adj</label>
                                            <input type="number" step="0.01" :name="'options[' + idx + '][wholesale_price_adjustment]'" x-model="opt.wholesale_price_adjustment"
                                                class="w-full rounded border border-slate-200 dark:border-slate-700 px-2 py-1.5 text-xs font-mono bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-violet-500" />
                                        </div>
                                        <div>
                                            <label class="block sm:hidden text-[10px] font-bold text-slate-400">Stock</label>
                                            <select :name="'options[' + idx + '][stock_status]'" x-model="opt.stock_status"
                                                class="w-full rounded border border-slate-200 dark:border-slate-700 px-2 py-1.5 text-xs font-bold bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-violet-500">
                                                <option value="in_stock">In Stock</option>
                                                <option value="out_of_stock">Out of Stock</option>
                                            </select>
                                        </div>
                                        <div>
                                            <button type="button" @click="removeOptionRow(idx)" :disabled="formOptions.length === 1"
                                                class="p-1.5 rounded text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 disabled:opacity-30 disabled:cursor-not-allowed">
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="closeModal()"
                                class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                                {{ __('messages.cancel') }}
                            </button>
                            <button type="submit" :disabled="saving"
                                class="px-5 py-2 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-xs font-black shadow-md shadow-violet-500/20 transition active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed">
                                <span x-show="!saving" class="inline-flex items-center gap-1.5">
                                    <span x-text="modalMode === 'create' ? '+ {{ __('messages.save') }}' : '✓ {{ __('messages.save_changes') }}'"></span>
                                </span>
                                <span x-show="saving" class="inline-flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                    <span>Saving...</span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================================================
             5. DELETE PRESET CONFIRMATION MODAL
             ============================================================ --}}
        <div x-show="confirmTarget" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs" @click="closeConfirm()"></div>
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 space-y-4" @click.stop>
                    <div class="text-center space-y-2">
                        <div class="w-12 h-12 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 grid place-items-center text-xl mx-auto">🗑️</div>
                        <h4 class="text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.variant_preset_delete') }}</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Are you sure you want to delete <strong class="text-slate-900 dark:text-slate-100" x-text="confirmTarget?.name"></strong>?
                        </p>
                    </div>

                    <form method="POST"
                        :action="'/store/{{ $store->slug }}/admin/variant-presets/' + (confirmTarget ? confirmTarget.id : '')">
                        @csrf
                        @method('DELETE')
                        <div class="flex items-center justify-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="closeConfirm()"
                                class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                                {{ __('messages.cancel') }}
                            </button>
                            <button type="submit" :disabled="deleting"
                                class="px-5 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-black shadow-md shadow-rose-500/20 transition active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed">
                                <span x-show="!deleting">{{ __('messages.delete') }}</span>
                                <span x-show="deleting">Deleting...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
