
@php
    $presetIcons = ['📂', '📱', '🔧', '🔌', '🎧', '⚡', '📷', '🔋', '🗜️', '🧰', '💾', '🖥️', '📶', '🛡️', '🧲', '💡', '📦', '🗂️'];

    // The Add Main tab opens when the Main form has validation errors (and no Sub
    // form was the source) or when the store has no categories at all.
    $mainFormHasErrors = $errors->has('name') || $errors->has('icon') || $errors->has('image');
    $startTab = (($mainFormHasErrors && ! old('parent_id')) || $hasNoCategories) ? 'add' : 'list';
    $addSubForInit = old('parent_id') ? (int) old('parent_id') : null;
    $highlightCategory = session('highlight_category');

    $catUploaderLabels = [
        'current' => __('messages.category_current_image'),
        'keep_current' => __('messages.category_image_keep_current'),
        'remove' => __('messages.category_remove_image'),
        'replace' => __('messages.category_image_replace'),
        'optional' => __('messages.category_image_optional'),
        'no_logo' => __('messages.category_no_image'),
        'invalid_type' => __('messages.category_image_invalid_type'),
        'too_large' => __('messages.category_image_too_large', ['mb' => $imageMaxMb]),
        'recommended' => __('messages.category_image_recommended'),
        'remove_selected' => __('messages.category_image_remove_selected'),
    ];
@endphp
<div class="w-full space-y-5 sm:space-y-6">
    @unless($embedded ?? false)
        {{-- Header (hidden when embedded inside Master Data hub) --}}
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">{{ __('messages.categories') }}</h1>
                <p class="admin-page-sub">{{ $store->name }} — {{ __('messages.category_index_sub') }}</p>
            </div>
        </div>
    @endunless

    {{-- Success Flash --}}
    @if (session('success'))
        <div class="p-3.5 sm:p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-start gap-2">
            <span class="text-base flex-shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Error Flash --}}
    @if ($errors->any())
        <div class="p-3.5 sm:p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300 space-y-1">
            <div class="flex items-center gap-2 font-bold"><span>⚠️</span><span>Errors:</span></div>
            @foreach ($errors->all() as $error)
                <div class="pl-6">• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Single Alpine scope: tabs + view mode + tree + inline Add Sub + delete modal --}}
    <div x-data="{
        tab: '{{ $startTab }}',
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
        openSections: { @if ($highlightParentId) {{ (int) $highlightParentId }}: true @endif @if ($addSubForInit) @if ($highlightParentId), @endif {{ (int) $addSubForInit }}: true @endif },
        addSubFor: {{ $addSubForInit ?? 'null' }},
        savingMain: false,
        savingSub: false,
        confirmTarget: null,
        lastDeleteEl: null,
        deleting: false,
        isOpen(id) { return this.openSections[id] ?? {{ $autoOpen ? 'true' : 'false' }}; },
        toggle(id) { this.openSections[id] = !this.isOpen(id); },
        openAdd() {
            this.tab = 'add';
            this.$nextTick(() => this.$refs.addMainName?.focus());
        },
        openAddSub(id) {
            this.openSections[id] = true;
            this.addSubFor = id;
        },
        closeAddSub() { this.addSubFor = null; },
        openConfirm(el) {
            // Read id/name/counts straight from the clicked button so the
            // modal can never submit a stale row.
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
        },
        trapFocus(e) {
            const panel = this.$refs.confirmPanel;
            if (!panel) return;
            const focusables = [...panel.querySelectorAll('button, a[href], input, select, textarea, [tabindex]')].filter(el => !el.disabled && el.offsetParent !== null && el.getAttribute('tabindex') !== '-1');
            if (focusables.length === 0) return;
            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    }"
        @keydown.escape.window="if (confirmTarget) closeConfirm(); else if (addSubFor) closeAddSub(); else if (tab === 'add' && !savingMain && !($refs.addMainName && $refs.addMainName.value)) tab = 'list'"
        @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)"
        class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden transition-colors duration-200">

        {{-- Tab bar --}}
        <div class="flex border-b dark:border-slate-700 bg-gray-50/60 dark:bg-slate-900/40" role="tablist">
            <button type="button" role="tab" :aria-selected="tab === 'list'" @click="tab = 'list'"
                class="flex-1 sm:flex-none sm:px-6 py-3 text-xs sm:text-sm sm:text-base font-semibold flex items-center justify-center gap-1.5 sm:gap-2 transition border-b-2 -mb-px min-w-0"
                :class="tab === 'list' ? 'border-violet-600 text-violet-700 dark:text-violet-300 bg-white dark:bg-slate-800' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-800'">
                <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center text-xs sm:text-sm shrink-0">📂</span>
                <span class="truncate">{{ __('messages.categories') }}</span>
                <span class="shrink-0 px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-xs font-bold text-gray-600 dark:text-slate-300">{{ number_format($totalCount) }}</span>
            </button>
            <button type="button" role="tab" :aria-selected="tab === 'add'" @click="openAdd()"
                class="flex-1 sm:flex-none sm:px-6 py-3 text-xs sm:text-sm sm:text-base font-semibold flex items-center justify-center gap-1.5 sm:gap-2 transition border-b-2 -mb-px min-w-0"
                :class="tab === 'add' ? 'border-violet-600 text-violet-700 dark:text-violet-300 bg-white dark:bg-slate-800' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-800'">
                <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center text-xs sm:text-sm font-bold shrink-0">+</span>
                <span class="truncate">{{ __('messages.category_add_main_title') }}</span>
            </button>
        </div>

        {{-- Toolbar — list tab only (Add tab keeps the page focused on the form) --}}
        <div x-show="tab === 'list'" x-cloak x-transition.opacity.duration.150ms class="p-2.5 sm:p-3 sm:pb-0">
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

        {{-- Categories list tab panel --}}
        <div x-show="tab === 'list'" x-cloak x-transition
            x-init="@if ($highlightCategory) $nextTick(() => { const v = localStorage.getItem('admin_view_mode') || 'table'; const el = document.getElementById(v === 'card' ? 'cat-row-{{ $highlightCategory }}' : 'cat-row-t-{{ $highlightCategory }}'); el?.scrollIntoView({ behavior: 'smooth', block: 'center' }); }) @endif">

            {{-- Matching/context note while searching --}}
            @if ($matchingCount !== null)
                <p class="px-3.5 sm:px-4 py-2 text-xs text-gray-500 dark:text-slate-400">
                    {{ __('messages.category_results_matching', ['count' => number_format($matchingCount)]) }}
                    · {{ __('messages.category_includes_context') }}
                </p>
            @endif

            {{-- ===== View: Table (products-style — hairline dividers, horizontal
                 scroll on narrow screens; card grid is the mobile alternative) ===== --}}
            <div x-show="viewMode === 'table'" x-cloak class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm text-gray-600 dark:text-slate-300">
                    <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-semibold text-gray-700 dark:text-slate-200">
                        <tr>
                            <th class="p-3">{{ __('messages.category_col_icon') }}</th>
                            <th class="p-3">{{ __('messages.category_name') }}</th>
                            <th class="p-3 hidden sm:table-cell">{{ __('messages.category_type') }}</th>
                            <th class="p-3 text-center">{{ __('messages.category_col_subs') }}</th>
                            <th class="p-3 text-center">{{ __('messages.category_items') }}</th>
                            <th class="p-3 text-right">{{ __('messages.category_col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse ($parents as $parent)
                            @php
                                $parentChildren = $children[$parent->id] ?? collect();
                                $parentTotalItems = $parentProductTotals[$parent->id] ?? $parent->products_count;
                            @endphp

                            {{-- Parent row — click to expand/collapse sub-categories --}}
                            <tr id="cat-row-t-{{ $parent->id }}"
                                @click="toggle({{ $parent->id }})" role="button" tabindex="0"
                                @keydown.enter.prevent="toggle({{ $parent->id }})" @keydown.space.prevent="toggle({{ $parent->id }})"
                                :aria-expanded="isOpen({{ $parent->id }}) ? 'true' : 'false'"
                                class="cursor-pointer select-none hover:bg-gray-50/60 dark:hover:bg-slate-700/40 transition {{ $highlightCategory && (int) $highlightCategory === (int) $parent->id ? 'bg-violet-50/60 dark:bg-violet-950/20 ring-2 ring-violet-400/70' : '' }}">
                                <td class="p-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 shrink-0" :class="isOpen({{ $parent->id }}) ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                        <span class="w-9 h-9 shrink-0 rounded-lg bg-white dark:bg-slate-800 border border-violet-100 dark:border-slate-700 flex items-center justify-center text-base overflow-hidden">
                                            @if ($parent->image_path)
                                                <img src="{{ asset('storage/' . $parent->image_path) }}" alt="{{ $parent->name }}" loading="lazy" class="h-full w-full object-contain" />
                                            @else
                                                <span>{{ $parent->icon ?: '📂' }}</span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td class="p-3 max-w-[16rem]">
                                    <div class="font-bold text-gray-900 dark:text-slate-100 text-sm break-words">
                                        {{ $parent->name }}
                                        @if ($highlightCategory && (int) $highlightCategory === (int) $parent->id)
                                            <span class="inline-block ml-1 px-1.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 text-[11px] font-bold align-middle">{{ __('messages.category_new_badge') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-3 hidden sm:table-cell text-xs font-medium text-gray-400 dark:text-slate-500">{{ __('messages.category_type_parent') }}</td>
                                <td class="p-3 text-center tabular-nums">{{ $parent->children_count }}</td>
                                <td class="p-3 text-center tabular-nums">{{ number_format($parentTotalItems) }}</td>
                                <td class="p-3">
                                    <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                                        <button type="button" @click.stop="openAddSub({{ $parent->id }})"
                                            class="min-h-11 px-2.5 py-1.5 rounded-lg bg-violet-600 text-white hover:bg-violet-700 font-semibold text-xs shadow-sm transition whitespace-nowrap">
                                            + {{ __('messages.category_add_sub') }}
                                        </button>
                                        <a href="{{ url('/store/' . $store->slug . '/admin/categories/' . $parent->id . '/edit') }}" @click.stop
                                            class="min-h-11 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition whitespace-nowrap">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                                            <span class="hidden lg:inline">{{ __('messages.edit') }}</span>
                                        </a>
                                        @if ($parent->products_count > 0 || $parent->children_count > 0)
                                            {{-- Blocked: parent is in use — never offer deletion --}}
                                            <span class="min-h-11 inline-flex items-center gap-1.5 px-2.5 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 text-xs"
                                                title="{{ __('messages.category_delete_blocked_hint') }}">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/></svg>
                                                @if ($parent->products_count > 0)
                                                    <span>{{ __('messages.category_used_by_products', ['count' => $parent->products_count]) }}</span>
                                                    <a href="{{ url('/store/' . $store->slug . '/admin/products?category_id=' . $parent->id) }}"
                                                        class="min-h-11 inline-flex items-center gap-0.5 px-1 font-semibold text-violet-600 dark:text-violet-400 hover:underline"
                                                        title="{{ __('messages.category_view_products') }}">
                                                        {{ __('messages.category_view_products') }}
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                    </a>
                                                @endif
                                                @if ($parent->children_count > 0)
                                                    <span class="hidden lg:inline">{{ __('messages.category_contains_subs', ['count' => $parent->children_count]) }}</span>
                                                @endif
                                            </span>
                                        @else
                                            <button type="button" data-id="{{ $parent->id }}" data-name="{{ $parent->name }}"
                                                data-products="0" data-children="0"
                                                @click.stop="openConfirm($el)"
                                                class="min-h-11 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition"
                                                :aria-label="@js(__('messages.category_delete_modal_title'))">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.9 12.1A2 2 0 0 1 16.1 21H7.9a2 2 0 0 1-2-1.9L5 7m5-4h4a1 1 0 0 1 1 1v2H9V4a1 1 0 0 1 1-1ZM4 7h16"/></svg>
                                                <span class="hidden lg:inline">{{ __('messages.delete') }}</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            {{-- Inline Add Sub form — full-width row directly under the parent --}}
                            <template x-if="viewMode === 'table' && addSubFor === {{ $parent->id }}">
                                <tr>
                                    <td colspan="6" class="p-3.5 bg-violet-50/70 dark:bg-violet-950/20 border-t border-violet-100 dark:border-violet-900/50">
                                        @include('admin.categories._add_sub_form', ['parent' => $parent])
                                    </td>
                                </tr>
                            </template>

                            {{-- Sub-category rows (indented, hidden until parent expands) --}}
                            @foreach ($parentChildren as $child)
                                <tr id="cat-row-t-{{ $child->id }}" data-cat-row="{{ $child->id }}"
                                    x-show="isOpen({{ $parent->id }})" x-cloak
                                    class="hover:bg-gray-50/60 dark:hover:bg-slate-700/40 transition {{ $highlightCategory && (int) $highlightCategory === (int) $child->id ? 'bg-violet-50/60 dark:bg-violet-950/20 ring-2 ring-violet-400/70' : '' }}">
                                    <td class="p-3 pl-12">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <svg class="w-3.5 h-3.5 text-gray-300 dark:text-slate-600 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M10.59 6.59L15.17 11H3v2h12.17l-4.58 4.59L12 19l7-7-7-7z" />
                                            </svg>
                                            <span class="w-8 h-8 shrink-0 rounded-md bg-gray-100 dark:bg-slate-800 flex items-center justify-center text-sm overflow-hidden">
                                                @if ($child->image_path)
                                                    <img src="{{ asset('storage/' . $child->image_path) }}" alt="{{ $child->name }}" loading="lazy" class="h-full w-full object-contain" />
                                                @else
                                                    <span>{{ $child->icon ?: '🗂️' }}</span>
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-3 max-w-[16rem]">
                                        <span class="font-semibold text-gray-800 dark:text-slate-100 text-sm break-words">
                                            {{ $child->name }}
                                            @if ($highlightCategory && (int) $highlightCategory === (int) $child->id)
                                                <span class="inline-block ml-1 px-1.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 text-[11px] font-bold">{{ __('messages.category_new_badge') }}</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td class="p-3 hidden sm:table-cell text-xs font-medium text-gray-400 dark:text-slate-500">{{ __('messages.category_type_sub') }}</td>
                                    <td class="p-3 text-center text-gray-400">—</td>
                                    <td class="p-3 text-center tabular-nums">{{ number_format($child->products_count) }}</td>
                                    <td class="p-3">
                                        <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                                            <a href="{{ url('/store/' . $store->slug . '/admin/categories/' . $child->id . '/edit') }}"
                                                class="min-h-11 inline-flex items-center gap-1 px-2 py-1.5 rounded-md text-xs font-semibold text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                                                <span class="hidden lg:inline">{{ __('messages.edit') }}</span>
                                            </a>
                                            @if ($child->products_count > 0)
                                                {{-- Blocked: child has products --}}
                                                <a href="{{ url('/store/' . $store->slug . '/admin/products?category_id=' . $child->id) }}"
                                                    class="min-h-11 inline-flex items-center gap-1 px-2 py-1.5 rounded-md bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 font-semibold text-xs"
                                                    title="{{ __('messages.category_delete_blocked_products', ['count' => $child->products_count]) }}">
                                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/></svg>
                                                    <span class="hidden xl:inline">{{ __('messages.category_used_by_products', ['count' => $child->products_count]) }}</span>
                                                </a>
                                            @else
                                                <button type="button" data-id="{{ $child->id }}" data-name="{{ $child->name }}"
                                                    data-products="0" data-children="0"
                                                    @click="openConfirm($el)"
                                                    class="min-h-11 inline-flex items-center px-2 py-1.5 rounded-md text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition"
                                                    :aria-label="@js(__('messages.category_delete_modal_title'))">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.9 12.1A2 2 0 0 1 16.1 21H7.9a2 2 0 0 1-2-1.9L5 7m5-4h4a1 1 0 0 1 1 1v2H9V4a1 1 0 0 1 1-1ZM4 7h16"/></svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center">
                                    <div class="text-4xl mb-3 opacity-40">📂</div>
                                    <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">{{ __('messages.category_empty_title') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.category_empty_hint') }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ===== View: Card grid (icon/image + name + counts + actions) ===== --}}
            <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 items-start">
                @forelse ($parents as $parent)
                    @php
                        $parentChildren = $children[$parent->id] ?? collect();
                        $parentTotalItems = $parentProductTotals[$parent->id] ?? $parent->products_count;
                    @endphp

                    {{-- ===== Main Category card ===== --}}
                    <section id="cat-section-{{ $parent->id }}" aria-labelledby="cat-title-{{ $parent->id }}"
                        class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">

                        {{-- Parent header row — click to expand/collapse sub-categories --}}
                        <div id="cat-row-{{ $parent->id }}"
                            @click="toggle({{ $parent->id }})" role="button" tabindex="0"
                            @keydown.enter.prevent="toggle({{ $parent->id }})" @keydown.space.prevent="toggle({{ $parent->id }})"
                            :aria-expanded="isOpen({{ $parent->id }}) ? 'true' : 'false'"
                            aria-controls="cat-children-{{ $parent->id }}"
                            class="flex items-center gap-3 p-3.5 sm:p-4 bg-gray-50/60 dark:bg-slate-900/40 cursor-pointer select-none group {{ $highlightCategory && (int) $highlightCategory === (int) $parent->id ? 'ring-2 ring-violet-400/70' : '' }}">
                            <div class="w-10 h-10 sm:w-11 sm:h-11 shrink-0 rounded-lg bg-white dark:bg-slate-800 border border-violet-100 dark:border-slate-700 flex items-center justify-center text-lg overflow-hidden">
                                @if ($parent->image_path)
                                    <img src="{{ asset('storage/' . $parent->image_path) }}" alt="{{ $parent->name }}" loading="lazy" class="h-full w-full object-contain" />
                                @else
                                    <span>{{ $parent->icon ?: '📂' }}</span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div id="cat-title-{{ $parent->id }}" class="font-bold text-gray-900 dark:text-slate-100 text-sm sm:text-base break-words">
                                    {{ $parent->name }}
                                    @if ($highlightCategory && (int) $highlightCategory === (int) $parent->id)
                                        <span class="inline-block ml-1 px-1.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 text-[11px] font-bold align-middle">{{ __('messages.category_new_badge') }}</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 dark:text-slate-500">
                                    {{ __('messages.category_sub_count_items', ['count' => $parent->children_count, 'items' => number_format($parentTotalItems)]) }}
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="isOpen({{ $parent->id }}) ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>

                        {{-- Action buttons row (separate from header — always visible, not cramped) --}}
                        <div class="flex items-center gap-2 px-3.5 sm:px-4 py-2.5 border-t border-gray-100 dark:border-slate-700/60 bg-white dark:bg-slate-800">
                            <button type="button" @click.stop="openAddSub({{ $parent->id }})"
                                class="min-h-11 px-3 py-1.5 rounded-lg bg-violet-600 text-white hover:bg-violet-700 font-semibold text-xs shadow-sm transition">
                                + {{ __('messages.category_add_sub') }}
                            </button>
                            <a href="{{ url('/store/' . $store->slug . '/admin/categories/' . $parent->id . '/edit') }}" @click.stop
                                class="min-h-11 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                                {{ __('messages.edit') }}
                            </a>
                            @if ($parent->products_count > 0 || $parent->children_count > 0)
                                <span class="ml-auto inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 text-xs"
                                    title="{{ __('messages.category_delete_blocked_hint') }}">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/></svg>
                                    @if ($parent->products_count > 0)
                                        <a href="{{ url('/store/' . $store->slug . '/admin/products?category_id=' . $parent->id) }}"
                                            class="min-h-11 inline-flex items-center gap-0.5 px-1 font-semibold text-violet-600 dark:text-violet-400 hover:underline">
                                            {{ $parent->products_count }} {{ __('messages.category_items') }}
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    @endif
                                    @if ($parent->children_count > 0)
                                        <span>{{ $parent->children_count }} {{ __('messages.category_type_sub') }}</span>
                                    @endif
                                </span>
                            @else
                                <button type="button" data-id="{{ $parent->id }}" data-name="{{ $parent->name }}"
                                    data-products="0" data-children="0"
                                    @click.stop="openConfirm($el)"
                                    class="min-h-11 ml-auto inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition"
                                    :aria-label="@js(__('messages.category_delete_modal_title'))">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.9 12.1A2 2 0 0 1 16.1 21H7.9a2 2 0 0 1-2-1.9L5 7m5-4h4a1 1 0 0 1 1 1v2H9V4a1 1 0 0 1 1-1ZM4 7h16"/></svg>
                                    {{ __('messages.delete') }}
                                </button>
                            @endif
                        </div>

                        {{-- Inline Add Sub form — directly under the card header --}}
                        <template x-if="viewMode === 'card' && addSubFor === {{ $parent->id }}">
                            <div class="bg-violet-50/70 dark:bg-violet-950/20 border-b border-violet-200/70 dark:border-violet-900/50 p-3.5 sm:p-4">
                                @include('admin.categories._add_sub_form', ['parent' => $parent])
                            </div>
                        </template>

                        {{-- Sub-categories --}}
                        <div id="cat-children-{{ $parent->id }}" x-show="isOpen({{ $parent->id }})" x-cloak x-transition class="divide-y divide-gray-100 dark:divide-slate-800">
                            @forelse ($parentChildren as $child)
                                <div id="cat-row-{{ $child->id }}" data-cat-row="{{ $child->id }}"
                                    class="flex items-center gap-2.5 px-3.5 sm:px-4 py-2.5 sm:py-3 pl-8 sm:pl-10 group hover:bg-gray-50 dark:hover:bg-slate-700/40 transition {{ $highlightCategory && (int) $highlightCategory === (int) $child->id ? 'bg-violet-50/60 dark:bg-violet-950/20 ring-2 ring-violet-400/70' : '' }}">
                                    <svg class="w-3.5 h-3.5 shrink-0 text-gray-300 dark:text-slate-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M10.59 6.59L15.17 11H3v2h12.17l-4.58 4.59L12 19l7-7-7-7z" />
                                    </svg>
                                    <span class="w-8 h-8 shrink-0 rounded-md bg-gray-100 dark:bg-slate-800 flex items-center justify-center text-sm overflow-hidden">
                                        @if ($child->image_path)
                                            <img src="{{ asset('storage/' . $child->image_path) }}" alt="{{ $child->name }}" loading="lazy" class="h-full w-full object-contain" />
                                        @else
                                            <span>{{ $child->icon ?: '🗂️' }}</span>
                                        @endif
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <span class="font-semibold text-gray-800 dark:text-slate-100 text-xs sm:text-sm break-words">
                                            {{ $child->name }}
                                            @if ($highlightCategory && (int) $highlightCategory === (int) $child->id)
                                                <span class="inline-block ml-1 px-1.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 text-[11px] font-bold">{{ __('messages.category_new_badge') }}</span>
                                            @endif
                                        </span>
                                        <span class="ml-1.5 text-xs text-gray-400 dark:text-slate-500">{{ number_format($child->products_count) }} {{ __('messages.category_items') }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <a href="{{ url('/store/' . $store->slug . '/admin/categories/' . $child->id . '/edit') }}"
                                            class="min-h-11 inline-flex items-center gap-1 px-2 py-1.5 rounded-md text-xs font-semibold text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                                            <span class="hidden sm:inline">{{ __('messages.edit') }}</span>
                                        </a>
                                        @if ($child->products_count > 0)
                                            {{-- Blocked: child has products --}}
                                            <a href="{{ url('/store/' . $store->slug . '/admin/products?category_id=' . $child->id) }}"
                                                class="min-h-11 inline-flex items-center gap-1 px-2 py-1.5 rounded-md bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 font-semibold text-xs"
                                                title="{{ __('messages.category_delete_blocked_products', ['count' => $child->products_count]) }}">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/></svg>
                                                {{ __('messages.category_used_by_products', ['count' => $child->products_count]) }}
                                            </a>
                                        @else
                                            <button type="button" data-id="{{ $child->id }}" data-name="{{ $child->name }}"
                                                data-products="0" data-children="0"
                                                @click="openConfirm($el)"
                                                class="min-h-11 inline-flex items-center px-2 py-1.5 rounded-md text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition"
                                                :aria-label="@js(__('messages.category_delete_modal_title'))">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.9 12.1A2 2 0 0 1 16.1 21H7.9a2 2 0 0 1-2-1.9L5 7m5-4h4a1 1 0 0 1 1 1v2H9V4a1 1 0 0 1 1-1ZM4 7h16"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-3 pl-10 text-xs text-gray-400 dark:text-slate-500 italic">{{ __('messages.category_no_subs') }}</div>
                            @endforelse
                        </div>
                    </section>
                @empty
                    <div class="col-span-full p-8 text-center">
                        <div class="text-4xl mb-3 opacity-40">📂</div>
                        <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">{{ __('messages.category_empty_title') }}</div>
                        <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.category_empty_hint') }}</div>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Add Main Category tab panel --}}
        <div x-show="tab === 'add'" x-cloak x-transition>
            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/categories') }}" enctype="multipart/form-data"
                @submit="if (savingMain) { $event.preventDefault(); } else { savingMain = true; }"
                class="p-4 sm:p-5 space-y-4">
                @csrf
                <p class="text-xs text-gray-400 dark:text-slate-500">{{ __('messages.category_add_main_hint') }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-start">
                    <div>
                        <label for="add-main-name" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.category_main_name') }} <span class="text-rose-500">*</span></label>
                        <input id="add-main-name" x-ref="addMainName" type="text" name="name" required
                            value="{{ old('name') }}"
                            placeholder="e.g. Spare Part"
                            class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition {{ $errors->has('name') ? 'border-red-400 dark:border-red-500' : '' }}" />
                        @error('name')
                            <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div x-data="{ icon: @js(old('icon', '')) }">
                        <label for="add-main-icon" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.category_icon_optional') }}</label>
                        <input id="add-main-icon" type="text" name="icon" x-model="icon" maxlength="8" placeholder="📂"
                            class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach ($presetIcons as $presetIcon)
                                <button type="button" @click="icon = @js($presetIcon)"
                                    :aria-pressed="icon === @js($presetIcon)" :aria-label="@js($presetIcon)"
                                    :class="icon === @js($presetIcon) ? 'ring-2 ring-violet-500 bg-violet-100 dark:bg-violet-900/40 border-violet-500' : 'border-slate-200 dark:border-slate-600 hover:border-violet-500 hover:bg-violet-50 dark:hover:bg-slate-700'"
                                    class="h-9 w-9 min-h-9 rounded-lg border text-sm transition">
                                    {{ $presetIcon }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-1">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.category_image_optional') }}</label>
                        <x-admin.logo-uploader :maxMb="$imageMaxMb" :input-name="'image'" :labels="$catUploaderLabels" />
                        @error('image')
                            <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" :disabled="savingMain"
                        class="inline-flex items-center justify-center gap-2 min-h-11 px-5 py-2.5 bg-violet-600 text-white rounded-lg hover:bg-violet-700 disabled:opacity-60 disabled:cursor-not-allowed font-semibold text-sm shadow transition">
                        <span x-show="!savingMain" class="inline-flex items-center gap-1.5"><span class="text-base leading-none">+</span><span>{{ __('messages.category_save_main') }}</span></span>
                        <span x-show="savingMain" class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                            {{ __('messages.category_saving') }}
                        </span>
                    </button>
                    <button type="button" @click="tab = 'list'" :disabled="savingMain"
                        class="min-h-11 px-4 py-2.5 rounded-lg text-sm font-semibold text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 transition">
                        {{ __('messages.cancel') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Delete confirmation modal (accessible: focus trap, Escape, backdrop, focus return) --}}
        <div x-show="confirmTarget" x-cloak x-transition.opacity.duration.150ms class="fixed inset-0 z-50" role="dialog" aria-modal="true"
            aria-labelledby="category-delete-title">
            <div class="fixed inset-0 bg-black/40" @click="closeConfirm()" aria-hidden="true"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
                <div x-ref="confirmPanel" @keydown.tab.prevent="trapFocus($event)" @click.stop
                    class="pointer-events-auto w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 p-5 shadow-xl border border-gray-200 dark:border-slate-700">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-950/50 text-red-600 dark:text-red-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <h3 id="category-delete-title" class="text-base font-bold text-gray-900 dark:text-slate-100">{{ __('messages.category_delete_modal_title') }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-slate-300 break-words font-medium" x-text="confirmTarget ? confirmTarget.name : ''"></p>
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-slate-500">{{ __('messages.category_delete_modal_warning') }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-slate-400" x-text="confirmTarget ? @js(__('messages.category_delete_modal_counts', ['products' => ':p', 'children' => ':c'])).replace(':p', confirmTarget.products).replace(':c', confirmTarget.children) : ''"></p>
                        </div>
                    </div>
                    <div class="mt-5 flex items-center justify-end gap-2">
                        <button type="button" x-ref="confirmCancel" @click="closeConfirm()"
                            class="min-h-11 px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 transition">
                            {{ __('messages.cancel') }}
                        </button>
                        <form x-ref="deleteForm" method="POST"
                            :action="'/store/{{ $store->slug }}/admin/categories/' + (confirmTarget ? confirmTarget.id : '')"
                            class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" @click="submitDelete()" :disabled="deleting"
                                class="min-h-11 inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-red-600 hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed shadow transition">
                                <span x-show="!deleting" class="inline-flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.9 12.1A2 2 0 0 1 16.1 21H7.9a2 2 0 0 1-2-1.9L5 7m5-4h4a1 1 0 0 1 1 1v2H9V4a1 1 0 0 1 1-1ZM4 7h16"/></svg>
                                    {{ __('messages.delete') }}
                                </span>
                                <span x-show="deleting" class="inline-flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                    {{ __('messages.category_deleting') }}
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

