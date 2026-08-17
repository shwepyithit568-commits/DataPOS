
@php
    $startTab = $errors->any() ? 'add' : 'list';
    $highlightBrand = session('highlight_brand');
@endphp
<div class="w-full space-y-6">
    {{-- Header --}}
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">{{ __('messages.brands') }}</h1>
            <p class="admin-page-sub">{{ $store->name }} — {{ __('messages.brand_index_sub') }}</p>
        </div>
    </div>

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

    {{-- Brands / Add Brand (tabbed) — toolbar lives inside so it can hide on the Add tab --}}
    <div x-data="{
        tab: '{{ $startTab }}',
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
        saving: false,
        confirmTarget: null,
        lastDeleteEl: null,
        deleting: false,
        openAdd() {
            this.tab = 'add';
            this.$nextTick(() => this.$refs.addName?.focus());
        },
        submitCreate() {
            if (this.saving) return;
            this.saving = true;
            this.$refs.createForm.submit();
        },
        openConfirm(el) {
            // Read id/name straight from the clicked button so the modal can
            // never submit a stale row.
            this.confirmTarget = { id: el.dataset.id, name: el.dataset.name };
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
    }" @keydown.escape.window="if (confirmTarget) closeConfirm(); else if (tab === 'add' && $refs.addName && $refs.addName.value === '') tab = 'list'"
        @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)"
        class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden transition-colors duration-200">

        {{-- Tab bar --}}
        <div class="flex border-b dark:border-slate-700 bg-gray-50/60 dark:bg-slate-900/40" role="tablist">
            <button type="button" role="tab" :aria-selected="tab === 'list'" @click="tab = 'list'"
                class="flex-1 sm:flex-none sm:px-6 py-3 text-xs sm:text-sm sm:text-base font-semibold flex items-center justify-center gap-1.5 sm:gap-2 transition border-b-2 -mb-px min-w-0"
                :class="tab === 'list' ? 'border-violet-600 text-violet-700 dark:text-violet-300 bg-white dark:bg-slate-800' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-800'">
                <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center text-xs sm:text-sm shrink-0">🏷️</span>
                <span class="truncate">{{ __('messages.brands') }}</span>
                <span class="shrink-0 px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-xs font-bold text-gray-600 dark:text-slate-300">{{ number_format($totalCount) }}</span>
            </button>
            <button type="button" role="tab" :aria-selected="tab === 'add'" @click="openAdd()"
                class="flex-1 sm:flex-none sm:px-6 py-3 text-xs sm:text-sm sm:text-base font-semibold flex items-center justify-center gap-1.5 sm:gap-2 transition border-b-2 -mb-px min-w-0"
                :class="tab === 'add' ? 'border-violet-600 text-violet-700 dark:text-violet-300 bg-white dark:bg-slate-800' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-800'">
                <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center text-xs sm:text-sm font-bold shrink-0">+</span>
                <span class="truncate">{{ __('messages.brand_add_title') }}</span>
            </button>
        </div>

        {{-- Toolbar — list tab only (Add tab keeps the page focused on the form) --}}
        <div x-show="tab === 'list'" x-cloak x-transition.opacity.duration.150ms class="p-2.5 sm:p-3 sm:pb-0">
            <x-admin.toolbar
                :search="request('search', '')"
                searchPlaceholder="{{ __('messages.brand_search_placeholder') }}"
                :sort="request('sort', 'newest')"
                :sortOptions="[
                    'newest' => __('messages.brand_sort_newest'),
                    'oldest' => __('messages.brand_sort_oldest'),
                    'name_asc' => __('messages.brand_sort_name_asc'),
                    'name_desc' => __('messages.brand_sort_name_desc'),
                    'most_products' => __('messages.brand_sort_most_products')
                ]"
                :filters="[
                    'has_logo' => [
                        'label' => __('messages.brand_filter_logo'),
                        'options' => ['with' => __('messages.brand_with_logo'), 'without' => __('messages.brand_without_logo')]
                    ]
                ]"
                :showViewToggle="true"
                :showExportImport="true"
                :importUrl="url('/store/' . $store->slug . '/admin/brands/import')"
                :exportUrl="url('/store/' . $store->slug . '/admin/brands/export')"
                :liveSearch="true"
                :perPageOptions="[25 => '25', 50 => '50', 100 => '100']"
                :totalCount="$totalCount"
                :paginator="$brands"
            />
        </div>

        {{-- Brands list tab panel --}}
        <div x-show="tab === 'list'" x-cloak x-transition
            x-init="@if ($highlightBrand) $nextTick(() => { const v = localStorage.getItem('admin_view_mode') || 'table'; const el = document.getElementById(v === 'card' ? 'brand-card-{{ $highlightBrand }}' : 'brand-row-{{ $highlightBrand }}'); el?.scrollIntoView({ behavior: 'smooth', block: 'center' }); }) @endif">

            {{-- ===== View: Table (products-style — hairline dividers, horizontal
                 scroll on narrow screens; card grid is the mobile alternative) ===== --}}
            <div x-show="viewMode === 'table'" x-cloak class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm text-gray-600 dark:text-slate-300">
                    <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-semibold text-gray-700 dark:text-slate-200">
                        <tr>
                            <th class="p-3">{{ __('messages.brand_filter_logo') }}</th>
                            <th class="p-3">{{ __('messages.brand_name') }}</th>
                            <th class="p-3">{{ __('messages.brand_col_slug') }}</th>
                            <th class="p-3 text-center">{{ __('messages.products') }}</th>
                            <th class="p-3 text-right">{{ __('messages.brand_col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse ($brands as $brand)
                            <tr id="brand-row-{{ $brand->id }}"
                                class="hover:bg-gray-50/60 dark:hover:bg-slate-700/40 transition {{ $highlightBrand && (int) $highlightBrand === (int) $brand->id ? 'bg-violet-50/60 dark:bg-violet-950/20 ring-2 ring-violet-400/70' : '' }}">
                                <td class="p-3">
                                    @if ($brand->logo_path)
                                        <img src="{{ asset('storage/' . $brand->logo_path) }}" alt="{{ $brand->name }}" loading="lazy" class="h-10 w-10 object-contain rounded border dark:border-slate-600 bg-white" />
                                    @else
                                        <div class="h-10 w-10 rounded-lg bg-violet-50 dark:bg-slate-700 border border-violet-200 dark:border-slate-600 flex items-center justify-center text-xs font-black text-violet-500 dark:text-violet-300 uppercase">{{ strtoupper(Str::limit($brand->name, 2, '')) }}</div>
                                    @endif
                                </td>
                                <td class="p-3 max-w-[16rem]">
                                    <div class="font-bold text-gray-900 dark:text-slate-100 break-words">{{ $brand->name }}</div>
                                    @if ($highlightBrand && (int) $highlightBrand === (int) $brand->id)
                                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 text-[11px] font-bold">NEW</span>
                                    @endif
                                </td>
                                <td class="p-3 font-mono text-xs text-gray-400 dark:text-slate-400 break-all">{{ $brand->slug }}</td>
                                <td class="p-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 text-xs font-semibold">{{ $brand->products_count }} {{ __('messages.brand_items') }}</span>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                                        <a href="{{ url('/store/' . $store->slug . '/admin/brands/' . $brand->id . '/edit') }}"
                                            class="min-h-11 inline-flex items-center gap-1 px-3 rounded-lg text-xs font-semibold text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition"
                                            title="{{ __('messages.edit') }}">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                                            {{ __('messages.edit') }}
                                        </a>
                                        @if ($brand->products_count > 0)
                                            {{-- Blocked: brand is in use — show explanation + link to its products --}}
                                            <span class="min-h-11 inline-flex items-center gap-1.5 px-2.5 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 text-xs"
                                                title="{{ __('messages.brand_delete_blocked', ['count' => $brand->products_count]) }}">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/></svg>
                                                <span>{{ __('messages.brand_used_by', ['count' => $brand->products_count]) }}</span>
                                                <a href="{{ url('/store/' . $store->slug . '/admin/products?brand_id=' . $brand->id) }}"
                                                    class="min-h-11 inline-flex items-center gap-0.5 px-1 font-semibold text-violet-600 dark:text-violet-400 hover:underline"
                                                    title="{{ __('messages.brand_view_products') }}">
                                                    {{ __('messages.brand_view_products') }}
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                </a>
                                            </span>
                                        @else
                                            <button type="button" data-id="{{ $brand->id }}" data-name="{{ $brand->name }}"
                                                @click="openConfirm($el)"
                                                class="min-h-11 inline-flex items-center gap-1 px-3 rounded-lg text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition"
                                                aria-label="{{ __('messages.brand_delete_modal_title') }}">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.9 12.1A2 2 0 0 1 16.1 21H7.9a2 2 0 0 1-2-1.9L5 7m5-4h4a1 1 0 0 1 1 1v2H9V4a1 1 0 0 1 1-1ZM4 7h16"/></svg>
                                                {{ __('messages.delete') }}
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center">
                                    <div class="text-4xl mb-3 opacity-40">🏷️</div>
                                    <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">{{ __('messages.brand_empty_title') }}</div>
                                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.brand_empty_hint') }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ===== View: Card grid (localStorage 'admin_view_mode' = card) ===== --}}
            <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                @forelse ($brands as $brand)
                    <div id="brand-card-{{ $brand->id }}" data-brand-row="{{ $brand->id }}"
                        class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors duration-200 hover:shadow-md {{ $highlightBrand && (int) $highlightBrand === (int) $brand->id ? 'ring-2 ring-violet-400/70' : '' }}">
                        {{-- Card header: logo + name + slug + count --}}
                        <div class="p-3 sm:p-4">
                            <div class="h-20 sm:h-24 w-full rounded-md border dark:border-slate-600 bg-white mb-2 overflow-hidden">
                                @if ($brand->logo_path)
                                    <img src="{{ asset('storage/' . $brand->logo_path) }}" alt="{{ $brand->name }}" loading="lazy" class="h-full w-full object-contain p-1.5" />
                                @else
                                    <div class="h-full w-full bg-violet-50 dark:bg-slate-700 flex items-center justify-center text-base font-black text-violet-500 dark:text-violet-300 uppercase">{{ strtoupper(Str::limit($brand->name, 2, '')) }}</div>
                                @endif
                            </div>
                            <div class="font-bold text-gray-900 dark:text-slate-100 text-sm break-words" title="{{ $brand->name }}">{{ $brand->name }}</div>
                            <div class="text-[11px] text-gray-400 dark:text-slate-500 truncate font-mono">{{ $brand->slug }}</div>
                            <div class="mt-1.5 flex items-center gap-1.5">
                                <span class="inline-block px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-[11px] font-semibold">{{ $brand->products_count }} {{ __('messages.brand_items') }}</span>
                                @if ($highlightBrand && (int) $highlightBrand === (int) $brand->id)
                                    <span class="inline-block px-1.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 text-[11px] font-bold">NEW</span>
                                @endif
                            </div>
                        </div>
                        {{-- Card action row --}}
                        <div class="flex items-center gap-2 px-3 sm:px-4 py-2.5 border-t border-gray-100 dark:border-slate-700/60">
                            <a href="{{ url('/store/' . $store->slug . '/admin/brands/' . $brand->id . '/edit') }}"
                                class="min-h-11 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                                {{ __('messages.edit') }}
                            </a>
                            @if ($brand->products_count > 0)
                                <span class="ml-auto inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 text-xs">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/></svg>
                                    <a href="{{ url('/store/' . $store->slug . '/admin/products?brand_id=' . $brand->id) }}"
                                        class="min-h-11 inline-flex items-center gap-0.5 px-1 font-semibold text-violet-600 dark:text-violet-400 hover:underline">
                                        {{ $brand->products_count }} {{ __('messages.brand_items') }}
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </span>
                            @else
                                <button type="button" data-id="{{ $brand->id }}" data-name="{{ $brand->name }}"
                                    @click="openConfirm($el)"
                                    class="min-h-11 ml-auto inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition"
                                    aria-label="{{ __('messages.brand_delete_modal_title') }}">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.9 12.1A2 2 0 0 1 16.1 21H7.9a2 2 0 0 1-2-1.9L5 7m5-4h4a1 1 0 0 1 1 1v2H9V4a1 1 0 0 1 1-1ZM4 7h16"/></svg>
                                    {{ __('messages.delete') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white dark:bg-slate-800 p-6 rounded-lg text-center text-gray-500 dark:text-slate-400">
                        <div class="text-4xl mb-3 opacity-40">🏷️</div>
                        <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">{{ __('messages.brand_empty_title') }}</div>
                        <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.brand_empty_hint') }}</div>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if (method_exists($brands, 'links'))
                <div class="p-4 border-t dark:border-slate-700 text-sm">{{ $brands->links() }}</div>
            @endif
        </div>

        {{-- Add Brand tab panel --}}
        <div x-show="tab === 'add'" x-cloak x-transition>
            <form x-ref="createForm" method="POST" action="{{ url('/store/' . $store->slug . '/admin/brands') }}" enctype="multipart/form-data"
                @submit="if (saving) { $event.preventDefault(); } else { saving = true; }"
                class="p-4 sm:p-5 space-y-4">
                @csrf
                <p class="text-xs text-gray-400 dark:text-slate-500">{{ __('messages.brand_add_hint') }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                    <div>
                        <label for="add-brand-name" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.brand_name') }} <span class="text-rose-500">*</span></label>
                        <input id="add-brand-name" type="text" name="name" x-ref="addName"
                            value="{{ old('name') }}"
                            placeholder="e.g. Samsung, Apple"
                            class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition {{ $errors->has('name') ? 'border-red-400 dark:border-red-500' : '' }}" />
                        @error('name')
                            <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.brand_logo_optional') }}</label>
                        <x-admin.logo-uploader :maxMb="$imageMaxMb" />
                        @error('logo')
                            <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <button type="submit" :disabled="saving"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 min-h-11 px-5 py-2.5 bg-violet-600 text-white rounded-lg hover:bg-violet-700 disabled:opacity-60 disabled:cursor-not-allowed font-semibold text-sm shadow transition">
                    <span x-show="!saving" class="inline-flex items-center gap-2"><span class="text-base leading-none">+</span><span>{{ __('messages.brand_save') }}</span></span>
                    <span x-show="saving" class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        {{ __('messages.brand_saving') }}
                    </span>
                </button>
            </form>
        </div>

        {{-- Delete confirmation modal (accessible: focus trap, Escape, backdrop, focus return) --}}
        <div x-show="confirmTarget" x-cloak x-transition.opacity.duration.150ms class="fixed inset-0 z-50" role="dialog" aria-modal="true"
            aria-labelledby="brand-delete-title">
        <div class="fixed inset-0 bg-black/40" @click="closeConfirm()" aria-hidden="true"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div x-ref="confirmPanel" @keydown.tab.prevent="trapFocus($event)" @click.stop
                class="pointer-events-auto w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 p-5 shadow-xl border border-gray-200 dark:border-slate-700">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-950/50 text-red-600 dark:text-red-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 id="brand-delete-title" class="text-base font-bold text-gray-900 dark:text-slate-100">{{ __('messages.brand_delete_modal_title') }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-slate-300 break-words font-medium" x-text="confirmTarget ? confirmTarget.name : ''"></p>
                        <p class="mt-0.5 text-xs text-gray-400 dark:text-slate-500">{{ __('messages.brand_delete_modal_warning') }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                            {{ __('messages.products') }}: <span class="font-bold">0</span> · {{ __('messages.brand_items') }}: <span class="font-bold">0</span>
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" x-ref="confirmCancel" @click="closeConfirm()"
                        class="min-h-11 px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <form x-ref="deleteForm" method="POST"
                        :action="'/store/{{ $store->slug }}/admin/brands/' + (confirmTarget ? confirmTarget.id : '')"
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
                                {{ __('messages.brand_deleting') }}
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>

