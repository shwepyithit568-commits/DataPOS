@php
    $presetIcons = ['📂', '📱', '🔧', '🔌', '🎧', '⚡', '📷', '🔋', '🗜️', '🧰', '💾', '🖥️', '📶', '🛡️', '🧲', '💡', '📦', '🗂️'];
    $highlightCategory = session('highlight_category');
@endphp

<div class="w-full space-y-2 sm:space-y-2.5">
    @unless($embedded ?? false)
        {{-- Header (hidden when embedded inside Master Data hub) --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
            <div>
                <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100">{{ __('messages.categories') }}</h1>
                <p class="text-[11px] text-slate-400 font-mono">{{ $store->name }} — {{ __('messages.category_index_sub') }}</p>
            </div>
            <button type="button" @click="$dispatch('open-category-create')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition flex items-center gap-1.5 active:scale-95 shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.category_add_main_title') }}</span>
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

    {{-- Single Alpine scope: Modal Form + View Mode + Category Tree + Delete Modal --}}
    <div x-data="{
        modalOpen: false,
        modalMode: 'create', // 'create' or 'edit'
        editId: null,
        formName: '',
        formCode: '',
        formParentId: '',
        formIcon: '📂',
        formDescription: '',
        formCurrentImageUrl: null,
        removeImage: false,
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
        openSections: { @if ($highlightParentId) {{ (int) $highlightParentId }}: true @endif },
        saving: false,
        confirmTarget: null,
        lastDeleteEl: null,
        deleting: false,

        isOpen(id) { return this.openSections[id] ?? {{ $autoOpen ? 'true' : 'false' }}; },
        toggle(id) { this.openSections[id] = !this.isOpen(id); },

        openCreate(parentId = '') {
            this.modalMode = 'create';
            this.editId = null;
            this.formName = '';
            this.formCode = '';
            this.formParentId = parentId ? String(parentId) : '';
            this.formIcon = '📂';
            this.formDescription = '';
            this.formCurrentImageUrl = null;
            this.removeImage = false;
            this.saving = false;
            this.modalOpen = true;
            this.$nextTick(() => this.$refs.catModalName?.focus());
        },

        openEdit(item) {
            this.modalMode = 'edit';
            this.editId = item.id;
            this.formName = item.name || '';
            this.formCode = item.code || '';
            this.formParentId = item.parent_id ? String(item.parent_id) : '';
            this.formIcon = item.icon || '📂';
            this.formDescription = item.description || '';
            this.formCurrentImageUrl = item.image_path ? ('{{ asset('storage') }}/' + item.image_path) : null;
            this.removeImage = false;
            this.saving = false;
            this.modalOpen = true;
            this.$nextTick(() => this.$refs.catModalName?.focus());
        },

        closeModal() {
            this.modalOpen = false;
        },

        openConfirm(el) {
            this.confirmTarget = {
                id: el.dataset.id,
                name: el.dataset.name,
                products: el.dataset.products || '0',
                children: el.dataset.children || '0'
            };
            this.deleting = false;
            this.lastDeleteEl = el;
            this.$nextTick(() => this.$refs.confirmCancel?.focus());
        },

        closeConfirm() {
            this.confirmTarget = null;
            this.$nextTick(() => this.lastDeleteEl?.focus());
        },

        submitDelete() {
            if (this.deleting) return;
            this.deleting = true;
            this.$refs.deleteForm.submit();
        }
    }"
    @open-category-create.window="openCreate()"
    @keydown.escape.window="if (modalOpen) closeModal(); else if (confirmTarget) closeConfirm();"
    @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)"
    class="w-full space-y-2 sm:space-y-2.5">

        {{-- ============================================================
             1. TOOLBAR AREA: Search, Filters, View Toggle, Export/Import
             ============================================================ --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <x-admin.toolbar
                :search="request('search', '')"
                searchPlaceholder="{{ __('messages.category_search_placeholder') }}"
                :sortOptions="[]"
                :filters="[
                    'has_image' => [
                        'label' => __('messages.category_image_filter'),
                        'options' => ['with' => __('messages.category_with_image'), 'without' => __('messages.category_without_image')]
                    ]
                ]"
                :showViewToggle="true"
                :showExportImport="true"
                :importUrl="url('/store/' . $store->slug . '/admin/categories/import')"
                :exportUrl="url('/store/' . $store->slug . '/admin/categories/export')"
                :totalCount="$totalCount"
                :paginator="null"
            />
        </div>

        {{-- Floating Action Button for Mobile/Tablet Quick Add --}}
        <button type="button" @click="openCreate()"
                class="fixed bottom-5 right-5 z-40 sm:hidden w-12 h-12 rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-xl shadow-violet-900/40 flex items-center justify-center text-2xl font-bold active:scale-95 transition"
                title="{{ __('messages.category_add_main_title') }}">
            +
        </button>

        {{-- Matching/context note while searching --}}
        @if ($matchingCount !== null)
            <div class="px-2 py-1 text-xs text-slate-500 dark:text-slate-400">
                {{ __('messages.category_results_matching', ['count' => number_format($matchingCount)]) }} · {{ __('messages.category_includes_context') }}
            </div>
        @endif

        {{-- Categories Content Container --}}
        <div x-init="@if ($highlightCategory) $nextTick(() => { const v = localStorage.getItem('admin_view_mode') || 'table'; const el = document.getElementById(v === 'card' ? 'cat-row-{{ $highlightCategory }}' : 'cat-row-t-{{ $highlightCategory }}'); el?.scrollIntoView({ behavior: 'smooth', block: 'center' }); }) @endif">

            {{-- ============================================================
                 2. SPREADSHEET TREE TABLE VIEW
                 ============================================================ --}}
            <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
                <div class="overflow-x-auto max-h-[72vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
                    <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                        <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                            <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                                <th class="py-2.5 px-3 w-16 text-center">{{ __('messages.category_col_icon') }}</th>
                                <th class="py-2.5 px-3 min-w-[200px]">{{ __('messages.category_name') }}</th>
                                <th class="py-2.5 px-3 hidden sm:table-cell min-w-[120px]">{{ __('messages.category_type') }}</th>
                                <th class="py-2.5 px-3 text-center min-w-[90px]">{{ __('messages.category_col_subs') }}</th>
                                <th class="py-2.5 px-3 text-center min-w-[90px]">{{ __('messages.category_items') }}</th>
                                <th class="py-2.5 px-3 text-right w-44">{{ __('messages.category_col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                            @forelse ($parents as $parent)
                                @php
                                    $parentChildren = $children[$parent->id] ?? collect();
                                    $parentTotalItems = $parentProductTotals[$parent->id] ?? $parent->products_count;
                                @endphp

                                {{-- Main / Parent Row --}}
                                <tr id="cat-row-t-{{ $parent->id }}"
                                    @click="toggle({{ $parent->id }})" role="button" tabindex="0"
                                    @keydown.enter.prevent="toggle({{ $parent->id }})" @keydown.space.prevent="toggle({{ $parent->id }})"
                                    :aria-expanded="isOpen({{ $parent->id }}) ? 'true' : 'false'"
                                    class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 cursor-pointer transition select-none {{ $highlightCategory && (int) $highlightCategory === (int) $parent->id ? 'bg-violet-50/60 dark:bg-violet-950/20 ring-2 ring-violet-400/70' : '' }}">
                                    
                                    {{-- Icon / Expand Caret --}}
                                    <td class="py-2 px-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 shrink-0" :class="isOpen({{ $parent->id }}) ? 'rotate-90 text-violet-600' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                            </svg>
                                            <span class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-sm overflow-hidden shrink-0">
                                                @if ($parent->image_path)
                                                    <img src="{{ asset('storage/' . $parent->image_path) }}" alt="{{ $parent->name }}" loading="lazy" class="h-full w-full object-contain p-0.5" />
                                                @else
                                                    <span>{{ $parent->icon ?: '📂' }}</span>
                                                @endif
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Name & Code --}}
                                    <td class="py-2 px-3">
                                        <div class="flex items-center gap-1.5 font-black text-slate-900 dark:text-slate-100 text-xs sm:text-sm break-words">
                                            <span>{{ $parent->name }}</span>
                                            @if ($parent->code)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800">{{ $parent->code }}</span>
                                            @endif
                                            @if ($highlightCategory && (int) $highlightCategory === (int) $parent->id)
                                                <span class="px-1.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 text-[9px] font-black uppercase">{{ __('messages.category_new_badge') }}</span>
                                            @endif
                                        </div>
                                        @if ($parent->description)
                                            <p class="text-[10px] text-slate-400 line-clamp-1 mt-0.5">{{ $parent->description }}</p>
                                        @endif
                                    </td>

                                    {{-- Type --}}
                                    <td class="py-2 px-3 hidden sm:table-cell text-xs font-bold text-slate-500 dark:text-slate-400">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                            {{ __('messages.category_type_parent') }}
                                        </span>
                                    </td>

                                    {{-- Sub count --}}
                                    <td class="py-2 px-3 text-center font-mono font-bold">{{ $parent->children_count }}</td>

                                    {{-- Items total count --}}
                                    <td class="py-2 px-3 text-center font-mono font-bold">{{ number_format($parentTotalItems) }}</td>

                                    {{-- Action Buttons --}}
                                    <td class="py-2 px-3 text-right whitespace-nowrap" @click.stop>
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" @click="openCreate({{ $parent->id }})"
                                                class="px-2 py-1 rounded text-xs font-bold bg-violet-50 hover:bg-violet-100 dark:bg-violet-950/50 dark:hover:bg-violet-900/60 text-violet-700 dark:text-violet-300 transition flex items-center gap-1">
                                                <span>+</span> {{ __('messages.category_add_sub') }}
                                            </button>
                                            <button type="button" @click="openEdit({{ Js::from($parent) }})"
                                                class="px-2 py-1 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition flex items-center gap-1">
                                                <span>✏️</span> {{ __('messages.edit') }}
                                            </button>
                                            @if ($parent->products_count > 0 || $parent->children_count > 0)
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 text-xs font-semibold"
                                                    title="{{ __('messages.category_delete_blocked_hint') }}">
                                                    <span>🔒</span> Used
                                                </span>
                                            @else
                                                <button type="button" data-id="{{ $parent->id }}" data-name="{{ $parent->name }}"
                                                    data-products="0" data-children="0"
                                                    @click="openConfirm($el)"
                                                    class="px-2 py-1 rounded text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 transition flex items-center gap-1 active:scale-95">
                                                    <span>🗑️</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- Sub-category Rows (Nested inside parent) --}}
                                @foreach ($parentChildren as $child)
                                    <tr id="cat-row-t-{{ $child->id }}" data-cat-row="{{ $child->id }}"
                                        x-show="isOpen({{ $parent->id }})" x-cloak
                                        class="divide-x divide-slate-200/60 dark:divide-slate-800/80 bg-slate-50/60 dark:bg-slate-850/40 hover:bg-slate-100/80 dark:hover:bg-slate-800/60 transition {{ $highlightCategory && (int) $highlightCategory === (int) $child->id ? 'bg-violet-50/60 dark:bg-violet-950/20 ring-2 ring-violet-400/70' : '' }}">
                                        
                                        {{-- Indented Icon --}}
                                        <td class="py-2 px-3 text-center pl-6">
                                            <div class="flex items-center justify-center gap-1">
                                                <span class="text-slate-300 dark:text-slate-600">↳</span>
                                                <span class="w-7 h-7 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-xs overflow-hidden">
                                                    @if ($child->image_path)
                                                        <img src="{{ asset('storage/' . $child->image_path) }}" alt="{{ $child->name }}" loading="lazy" class="h-full w-full object-contain" />
                                                    @else
                                                        <span>{{ $child->icon ?: '🗂️' }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        </td>

                                        {{-- Sub Name & Code --}}
                                        <td class="py-2 px-3 pl-4">
                                            <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-slate-200 text-xs break-words">
                                                <span>{{ $child->name }}</span>
                                                @if ($child->code)
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">{{ $child->code }}</span>
                                                @endif
                                                @if ($highlightCategory && (int) $highlightCategory === (int) $child->id)
                                                    <span class="px-1.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 text-[9px] font-black uppercase">{{ __('messages.category_new_badge') }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Type --}}
                                        <td class="py-2 px-3 hidden sm:table-cell text-xs text-slate-400 dark:text-slate-500">
                                            <span>{{ __('messages.category_type_sub') }}</span>
                                        </td>

                                        {{-- Sub count --}}
                                        <td class="py-2 px-3 text-center text-slate-300 dark:text-slate-600">—</td>

                                        {{-- Items count --}}
                                        <td class="py-2 px-3 text-center font-mono text-xs font-bold">{{ number_format($child->products_count) }}</td>

                                        {{-- Actions --}}
                                        <td class="py-2 px-3 text-right whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1">
                                                <button type="button" @click="openEdit({{ Js::from($child) }})"
                                                    class="px-2 py-1 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition flex items-center gap-1">
                                                    <span>✏️</span> {{ __('messages.edit') }}
                                                </button>
                                                @if ($child->products_count > 0)
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 text-xs font-semibold"
                                                        title="{{ __('messages.category_delete_blocked_products', ['count' => $child->products_count]) }}">
                                                        <span>🔒</span> Used
                                                    </span>
                                                @else
                                                    <button type="button" data-id="{{ $child->id }}" data-name="{{ $child->name }}"
                                                        data-products="0" data-children="0"
                                                        @click="openConfirm($el)"
                                                        class="px-2 py-1 rounded text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 transition flex items-center gap-1 active:scale-95">
                                                        <span>🗑️</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-slate-400">
                                        <div class="text-3xl mb-2 opacity-55">📂</div>
                                        <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.category_empty_title') }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.category_empty_hint') }}</div>
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
                @forelse ($parents as $parent)
                    @php
                        $parentChildren = $children[$parent->id] ?? collect();
                        $parentTotalItems = $parentProductTotals[$parent->id] ?? $parent->products_count;
                    @endphp

                    <div id="cat-card-{{ $parent->id }}"
                        class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl overflow-hidden shadow-2xs hover:border-violet-300 dark:hover:border-violet-600/50 hover:shadow-sm transition flex flex-col justify-between group {{ $highlightCategory && (int) $highlightCategory === (int) $parent->id ? 'ring-2 ring-violet-400/70 bg-violet-50/10 dark:bg-violet-950/10' : '' }}">
                        
                        <div class="p-3 space-y-2">
                            {{-- Card Header: Icon + Code + Active Pill --}}
                            <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-base overflow-hidden shrink-0">
                                        @if ($parent->image_path)
                                            <img src="{{ asset('storage/' . $parent->image_path) }}" alt="{{ $parent->name }}" loading="lazy" class="h-full w-full object-contain p-0.5" />
                                        @else
                                            <span>{{ $parent->icon ?: '📂' }}</span>
                                        @endif
                                    </span>
                                    @if ($parent->code)
                                        <span class="px-2 py-0.5 rounded font-mono font-black text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 truncate">
                                            {{ $parent->code }}
                                        </span>
                                    @endif
                                </div>

                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    <span>✓</span> Active
                                </span>
                            </div>

                            {{-- Category Title & Description --}}
                            <div>
                                <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 line-clamp-1" title="{{ $parent->name }}">
                                    {{ $parent->name }}
                                </h4>
                                @if ($parent->description)
                                    <p class="text-[10px] text-slate-400 line-clamp-1 mt-0.5">{{ $parent->description }}</p>
                                @endif
                            </div>

                            {{-- Stats Pills --}}
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-violet-50 dark:bg-violet-950/50 text-violet-700 dark:text-violet-300 text-[10px] font-bold border border-violet-200 dark:border-violet-800/80">
                                    {{ $parent->children_count }} {{ __('messages.category_col_subs') }}
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-bold border border-slate-200 dark:border-slate-700">
                                    {{ number_format($parentTotalItems) }} {{ __('messages.category_items') }}
                                </span>
                            </div>

                            {{-- Sub-categories preview drawer inside card --}}
                            @if ($parentChildren->isNotEmpty())
                                <div class="pt-2 border-t border-slate-100 dark:border-slate-800/80 space-y-1">
                                    <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 flex items-center justify-between">
                                        <span>Sub-categories</span>
                                        <button type="button" @click="toggle({{ $parent->id }})" class="text-violet-600 hover:underline">
                                            <span x-text="isOpen({{ $parent->id }}) ? 'Hide' : 'Show ({{ $parentChildren->count() }})'"></span>
                                        </button>
                                    </div>
                                    <div x-show="isOpen({{ $parent->id }})" x-cloak class="space-y-1 max-h-36 overflow-y-auto no-scrollbar pt-1">
                                        @foreach ($parentChildren as $child)
                                            <div class="flex items-center justify-between gap-1 p-1.5 rounded bg-slate-50 dark:bg-slate-800/50 text-xs">
                                                <span class="truncate font-semibold text-slate-700 dark:text-slate-200 text-[11px]">{{ $child->icon ?: '•' }} {{ $child->name }}</span>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <button type="button" @click="openEdit({{ Js::from($child) }})" class="text-slate-400 hover:text-violet-600 text-[11px]">✏️</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Card Footer Action Row --}}
                        <div class="p-2.5 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1 mt-2">
                            <div class="inline-flex items-center gap-1">
                                <button type="button" @click="openCreate({{ $parent->id }})"
                                    class="px-2 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold transition flex items-center gap-1 shadow-2xs">
                                    <span>+ Sub</span>
                                </button>
                                <button type="button" @click="openEdit({{ Js::from($parent) }})"
                                    class="px-2 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1">
                                    <span>✏️</span>
                                </button>
                            </div>

                            @if ($parent->products_count > 0 || $parent->children_count > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-1.5 rounded-lg bg-gray-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 text-xs font-semibold" title="{{ __('messages.category_delete_blocked_hint') }}">
                                    <span>🔒</span> Used
                                </span>
                            @else
                                <button type="button" data-id="{{ $parent->id }}" data-name="{{ $parent->name }}"
                                    data-products="0" data-children="0"
                                    @click="openConfirm($el)"
                                    class="px-2 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 text-xs font-bold transition flex items-center gap-1 active:scale-95">
                                    <span>🗑️</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 p-8 rounded-xl text-center text-slate-400 dark:text-slate-500 shadow-2xs">
                        <div class="text-3xl mb-2 opacity-55">📂</div>
                        <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.category_empty_title') }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.category_empty_hint') }}</div>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ============================================================
             4. CREATE / EDIT CATEGORY MODAL (Unified Dialog Form)
             ============================================================ --}}
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="closeModal()"></div>
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 space-y-4" @click.stop>
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            <span x-text="modalMode === 'create' ? (formParentId ? '➕ Add Sub-category' : '➕ {{ __('messages.category_add_main_title') }}') : '✏️ {{ __('messages.edit') }} ' + formName"></span>
                        </h3>
                        <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
                    </div>

                    <form method="POST"
                        :action="modalMode === 'create' ? '{{ url('/store/' . $store->slug . '/admin/categories') }}' : '{{ url('/store/' . $store->slug . '/admin/categories') }}/' + editId"
                        enctype="multipart/form-data"
                        @submit="if (saving) { $event.preventDefault(); } else { saving = true; }"
                        class="space-y-3.5">
                        @csrf
                        <template x-if="modalMode === 'edit'">
                            <input type="hidden" name="_method" value="PUT" />
                        </template>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="cat-modal-name" class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.category_name') }} <span class="text-rose-500">*</span></label>
                                <input id="cat-modal-name" x-ref="catModalName" type="text" name="name" x-model="formName" required
                                    placeholder="e.g. Mobile Phones, Adapters"
                                    class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500" />
                            </div>

                            <div>
                                <label for="cat-modal-code" class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.product_form_code') }}</label>
                                <input id="cat-modal-code" type="text" name="code" x-model="formCode"
                                    placeholder="{{ __('messages.product_form_code_placeholder') }}"
                                    class="w-full uppercase font-mono rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500" />
                            </div>
                        </div>

                        {{-- Parent Category Selector --}}
                        <div>
                            <label for="cat-modal-parent" class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Parent Hierarchy (အဓိက အုပ်စု သတ်မှတ်ခြင်း)</label>
                            <select id="cat-modal-parent" name="parent_id" x-model="formParentId"
                                class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500">
                                <option value="">— None (Main Category / ပင်မ အုပ်စု) —</option>
                                @foreach ($parents as $p)
                                    <option value="{{ $p->id }}">{{ $p->icon ?: '📂' }} {{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Icon Picker with presets --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.category_icon_optional') }}</label>
                            <div class="flex items-center gap-2 mb-2">
                                <input type="text" name="icon" x-model="formIcon" maxlength="8" placeholder="📂"
                                    class="w-20 text-center font-bold text-base rounded-lg border border-slate-200 dark:border-slate-700 px-2 py-1.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500" />
                                <span class="text-[11px] text-slate-400">Choose an icon below or type emoji</span>
                            </div>
                            <div class="flex flex-wrap gap-1 max-h-20 overflow-y-auto no-scrollbar p-1 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                @foreach ($presetIcons as $pIcon)
                                    <button type="button" @click="formIcon = '{{ $pIcon }}'"
                                        :class="formIcon === '{{ $pIcon }}' ? 'bg-violet-600 text-white ring-2 ring-violet-400' : 'bg-white dark:bg-slate-700 hover:bg-slate-200 text-slate-700 dark:text-slate-200'"
                                        class="w-7 h-7 rounded-md text-xs font-bold transition flex items-center justify-center">
                                        {{ $pIcon }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Image Uploader --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.category_image_optional') }}</label>
                            
                            {{-- Current image preview if editing --}}
                            <template x-if="modalMode === 'edit' && formCurrentImageUrl && !removeImage">
                                <div class="mb-2 flex items-center gap-3 p-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                    <img :src="formCurrentImageUrl" class="h-10 w-10 object-contain rounded bg-white p-1 border border-slate-200 dark:border-slate-600" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ __('messages.category_current_image') }}</p>
                                        <button type="button" @click="removeImage = true" class="text-[11px] font-bold text-rose-500 hover:underline">
                                            ✕ Remove Image
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="removeImage">
                                <input type="hidden" name="remove_image" value="1" />
                            </template>

                            <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                                class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-violet-950/60 dark:file:text-violet-300 cursor-pointer border border-slate-200 dark:border-slate-700 rounded-lg p-1 bg-slate-50 dark:bg-slate-800" />
                            <p class="text-[10px] text-slate-400 mt-1">PNG, JPG, WEBP up to 10MB</p>
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Description / Notes</label>
                            <textarea name="description" x-model="formDescription" rows="2" placeholder="Enter category details..."
                                class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-medium bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500"></textarea>
                        </div>

                        {{-- Footer CTA --}}
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
             5. DELETE CATEGORY CONFIRMATION MODAL
             ============================================================ --}}
        <div x-show="confirmTarget" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs" @click="closeConfirm()"></div>
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 space-y-4" @click.stop>
                    <div class="text-center space-y-2">
                        <div class="w-12 h-12 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 grid place-items-center text-xl mx-auto">🗑️</div>
                        <h4 class="text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.category_delete_modal_title') }}</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ __('messages.category_delete_modal_warning') }} <strong class="text-slate-900 dark:text-slate-100" x-text="confirmTarget?.name"></strong>?
                        </p>
                        <p class="text-[11px] text-slate-400" x-text="@js(__('messages.category_delete_modal_counts', ['products' => ':p', 'children' => ':c'])).replace(':p', confirmTarget?.products || '0').replace(':c', confirmTarget?.children || '0')"></p>
                    </div>

                    <form x-ref="deleteForm" method="POST"
                        :action="'/store/{{ $store->slug }}/admin/categories/' + (confirmTarget ? confirmTarget.id : '')">
                        @csrf
                        @method('DELETE')
                        <div class="flex items-center justify-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" x-ref="confirmCancel" @click="closeConfirm()"
                                class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                                {{ __('messages.cancel') }}
                            </button>
                            <button type="submit" @click="submitDelete()" :disabled="deleting"
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
