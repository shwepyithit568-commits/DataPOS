@php
    $highlightBrand = session('highlight_brand');
@endphp
<div class="w-full space-y-2 sm:space-y-2.5">
    @unless($embedded ?? false)
        {{-- Header (hidden when embedded inside Master Data hub) --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
            <div>
                <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100">{{ __('messages.brands') }}</h1>
                <p class="text-[11px] text-slate-400 font-mono">{{ $store->name }} — {{ __('messages.brand_index_sub') }}</p>
            </div>
            <button type="button" @click="$dispatch('open-brand-create')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition flex items-center gap-1.5 active:scale-95 shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.brand_add_title') }}</span>
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

    {{-- Single Alpine scope: Modal Form + View Mode + Delete Modal --}}
    <div x-data="{
        modalOpen: false,
        modalMode: 'create', // 'create' or 'edit'
        editId: null,
        formName: '',
        formCode: '',
        formCurrentLogoUrl: null,
        removeLogo: false,
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
        saving: false,
        confirmTarget: null,
        lastDeleteEl: null,
        deleting: false,

        openCreate() {
            this.modalMode = 'create';
            this.editId = null;
            this.formName = '';
            this.formCode = '';
            this.formCurrentLogoUrl = null;
            this.removeLogo = false;
            this.saving = false;
            this.modalOpen = true;
            this.$nextTick(() => this.$refs.brandModalName?.focus());
        },

        openEdit(item) {
            this.modalMode = 'edit';
            this.editId = item.id;
            this.formName = item.name || '';
            this.formCode = item.code || '';
            this.formCurrentLogoUrl = item.logo_path ? ('{{ asset('storage') }}/' + item.logo_path) : null;
            this.removeLogo = false;
            this.saving = false;
            this.modalOpen = true;
            this.$nextTick(() => this.$refs.brandModalName?.focus());
        },

        closeModal() {
            this.modalOpen = false;
        },

        openConfirm(el) {
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
        }
    }"
    @open-brand-create.window="openCreate()"
    @keydown.escape.window="if (modalOpen) closeModal(); else if (confirmTarget) closeConfirm();"
    @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)"
    class="w-full space-y-2 sm:space-y-2.5">

        {{-- ============================================================
             1. TOOLBAR AREA: Search, Filters, Sort, View Toggle, Add CTA
             ============================================================ --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
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

        {{-- Floating Action Button for Mobile/Tablet Quick Add --}}
        <button type="button" @click="openCreate()"
                class="fixed bottom-5 right-5 z-40 sm:hidden w-12 h-12 rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-xl shadow-violet-900/40 flex items-center justify-center text-2xl font-bold active:scale-95 transition"
                title="{{ __('messages.brand_add_title') }}">
            +
        </button>

        {{-- Brands List Content --}}
        <div x-init="@if ($highlightBrand) $nextTick(() => { const v = localStorage.getItem('admin_view_mode') || 'table'; const el = document.getElementById(v === 'card' ? 'brand-card-{{ $highlightBrand }}' : 'brand-row-{{ $highlightBrand }}'); el?.scrollIntoView({ behavior: 'smooth', block: 'center' }); }) @endif">

            {{-- ===== View: Table (Google Sheets style spreadsheet view) ===== --}}
            <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
                <div class="overflow-x-auto max-h-[72vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
                    <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                        <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                            <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                                <th class="py-2.5 px-3 w-16">{{ __('messages.brand_filter_logo') }}</th>
                                <th class="py-2.5 px-3 min-w-[180px]">{{ __('messages.brand_name') }}</th>
                                <th class="py-2.5 px-3 min-w-[140px]">{{ __('messages.brand_col_slug') }}</th>
                                <th class="py-2.5 px-3 text-center min-w-[100px]">{{ __('messages.products') }}</th>
                                <th class="py-2.5 px-3 text-right w-36">{{ __('messages.brand_col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                            @forelse ($brands as $brand)
                                <tr id="brand-row-{{ $brand->id }}"
                                    class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition {{ $highlightBrand && (int) $highlightBrand === (int) $brand->id ? 'bg-violet-50/60 dark:bg-violet-950/20 ring-2 ring-violet-400/70' : '' }}">
                                    <td class="py-2 px-3">
                                        @if ($brand->logo_path)
                                            <img src="{{ asset('storage/' . $brand->logo_path) }}" alt="{{ $brand->name }}" loading="lazy" class="h-10 w-10 object-contain rounded border dark:border-slate-700 bg-white" />
                                        @else
                                            <div class="h-10 w-10 rounded-lg bg-violet-50 dark:bg-slate-800 border border-violet-200 dark:border-slate-700 flex items-center justify-center text-xs font-black text-violet-500 dark:text-violet-300 uppercase">{{ strtoupper(Str::limit($brand->name, 2, '')) }}</div>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3">
                                        <div class="flex items-center gap-1.5 font-bold text-slate-900 dark:text-slate-100 break-words">
                                            <span>{{ $brand->name }}</span>
                                            @if ($brand->code)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-black bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800">{{ $brand->code }}</span>
                                            @endif
                                        </div>
                                        @if ($highlightBrand && (int) $highlightBrand === (int) $brand->id)
                                            <span class="inline-block mt-1 px-1.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 text-[9px] font-black uppercase">NEW</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 font-mono text-xs text-slate-500 dark:text-slate-400 break-all">{{ $brand->slug }}</td>
                                    <td class="py-2 px-3 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold">{{ $brand->products_count }} {{ __('messages.brand_items') }}</span>
                                    </td>
                                    <td class="py-2 px-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" @click="openEdit({{ Js::from($brand) }})"
                                                class="px-2.5 py-1 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition flex items-center gap-1"
                                                title="{{ __('messages.edit') }}">
                                                <span>✏️</span> {{ __('messages.edit') }}
                                            </button>
                                            @if ($brand->products_count > 0)
                                                {{-- Blocked: brand is in use — show explanation + link to its products --}}
                                                <a href="{{ url('/store/' . $store->slug . '/admin/products?brand_id=' . $brand->id) }}"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-violet-600 dark:hover:text-violet-400 text-xs font-semibold hover:underline"
                                                    title="{{ __('messages.brand_delete_blocked', ['count' => $brand->products_count]) }}">
                                                    <span>🔒</span> {{ $brand->products_count }} {{ __('messages.products') }}
                                                </a>
                                            @else
                                                <button type="button" data-id="{{ $brand->id }}" data-name="{{ $brand->name }}"
                                                    @click="openConfirm($el)"
                                                    class="px-2.5 py-1 rounded text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 transition flex items-center gap-1 active:scale-95"
                                                    aria-label="{{ __('messages.brand_delete_modal_title') }}">
                                                    <span>🗑️</span> {{ __('messages.delete') }}
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-400">
                                        <div class="text-3xl mb-2 opacity-55">🏷️</div>
                                        <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.brand_empty_title') }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.brand_empty_hint') }}</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ===== View: Card grid (Modern multi-column responsive grid) ===== --}}
            <div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
                @forelse ($brands as $brand)
                    <div id="brand-card-{{ $brand->id }}" data-brand-row="{{ $brand->id }}"
                        class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl overflow-hidden shadow-2xs hover:border-violet-300 dark:hover:border-violet-600/50 hover:shadow-sm transition flex flex-col justify-between group {{ $highlightBrand && (int) $highlightBrand === (int) $brand->id ? 'ring-2 ring-violet-400/70 bg-violet-50/10 dark:bg-violet-950/10' : '' }}">
                        
                        <div class="p-3 space-y-2">
                            {{-- Card header: code badge / fallback + active status badge --}}
                            <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                                @if ($brand->code)
                                    <span class="px-2 py-0.5 rounded font-mono font-black text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                        {{ $brand->code }}
                                    </span>
                                @else
                                    <span class="text-base">🏷️</span>
                                @endif

                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    <span>✓</span> Active
                                </span>
                            </div>

                            {{-- Brand Logo image/box --}}
                            <div class="h-20 sm:h-24 w-full rounded-lg border border-slate-100 dark:border-slate-800 bg-white mb-2 overflow-hidden flex items-center justify-center">
                                @if ($brand->logo_path)
                                    <img src="{{ asset('storage/' . $brand->logo_path) }}" alt="{{ $brand->name }}" loading="lazy" class="h-full w-full object-contain p-1.5" />
                                @else
                                    <div class="h-full w-full bg-violet-50 dark:bg-slate-800 flex items-center justify-center text-sm font-black text-violet-500 dark:text-violet-300 uppercase">{{ strtoupper(Str::limit($brand->name, 2, '')) }}</div>
                                @endif
                            </div>

                            {{-- Brand Info --}}
                            <div>
                                <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 line-clamp-1" title="{{ $brand->name }}">
                                    {{ $brand->name }}
                                </h4>
                                <div class="text-[10px] font-mono text-slate-400 mt-0.5 truncate">{{ $brand->slug }}</div>
                            </div>

                            <div class="mt-1">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-bold">
                                    {{ $brand->products_count }} {{ __('messages.brand_items') }}
                                </span>
                                @if ($highlightBrand && (int) $highlightBrand === (int) $brand->id)
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 text-[10px] font-bold">NEW</span>
                                @endif
                            </div>
                        </div>

                        {{-- Card footer action row --}}
                        <div class="p-2.5 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1.5 mt-2">
                            <button type="button" @click="openEdit({{ Js::from($brand) }})"
                                class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1">
                                <span>✏️</span>
                                <span>{{ __('messages.edit') }}</span>
                            </button>
                            @if ($brand->products_count > 0)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 text-xs font-semibold" title="{{ __('messages.brand_delete_blocked', ['count' => $brand->products_count]) }}">
                                    <span>🔒</span>
                                    <span>Used</span>
                                </span>
                            @else
                                <button type="button" data-id="{{ $brand->id }}" data-name="{{ $brand->name }}"
                                    @click="openConfirm($el)"
                                    class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 text-xs font-bold transition flex items-center gap-1 active:scale-95">
                                    <span>🗑️</span>
                                    <span>{{ __('messages.delete') }}</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 p-8 rounded-xl text-center text-slate-400 dark:text-slate-500 shadow-2xs">
                        <div class="text-3xl mb-2 opacity-55">🏷️</div>
                        <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.brand_empty_title') }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.brand_empty_hint') }}</div>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if (method_exists($brands, 'links'))
                <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs mt-2.5 text-xs">
                    {{ $brands->links() }}
                </div>
            @endif
        </div>

        {{-- ============================================================
             2. CREATE / EDIT BRAND MODAL (Colors Tab Preset Style)
             ============================================================ --}}
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="closeModal()"></div>
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 space-y-4" @click.stop>
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            <span x-text="modalMode === 'create' ? '➕ {{ __('messages.brand_add_title') }}' : '✏️ {{ __('messages.edit') }} ' + formName"></span>
                        </h3>
                        <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
                    </div>

                    <form method="POST"
                        :action="modalMode === 'create' ? '{{ url('/store/' . $store->slug . '/admin/brands') }}' : '{{ url('/store/' . $store->slug . '/admin/brands') }}/' + editId"
                        enctype="multipart/form-data"
                        @submit="if (saving) { $event.preventDefault(); } else { saving = true; }"
                        class="space-y-3.5">
                        @csrf
                        <template x-if="modalMode === 'edit'">
                            <input type="hidden" name="_method" value="PUT" />
                        </template>

                        <div>
                            <label for="brand-modal-name" class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.brand_name') }} <span class="text-rose-500">*</span></label>
                            <input id="brand-modal-name" x-ref="brandModalName" type="text" name="name" x-model="formName" required
                                placeholder="e.g. Samsung, Apple, Remax"
                                class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500" />
                        </div>

                        <div>
                            <label for="brand-modal-code" class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.product_form_code') }}</label>
                            <input id="brand-modal-code" type="text" name="code" x-model="formCode"
                                placeholder="{{ __('messages.product_form_code_placeholder') }}"
                                class="w-full uppercase font-mono rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.brand_logo_optional') }}</label>
                            
                            {{-- Current logo preview if in edit mode --}}
                            <template x-if="modalMode === 'edit' && formCurrentLogoUrl && !removeLogo">
                                <div class="mb-2 flex items-center gap-3 p-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                    <img :src="formCurrentLogoUrl" class="h-10 w-10 object-contain rounded bg-white p-1 border border-slate-200 dark:border-slate-600" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ __('messages.brand_current_logo') }}</p>
                                        <button type="button" @click="removeLogo = true" class="text-[11px] font-bold text-rose-500 hover:underline">
                                            ✕ Remove Logo
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="removeLogo">
                                <input type="hidden" name="remove_logo" value="1" />
                            </template>

                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp"
                                class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-violet-950/60 dark:file:text-violet-300 cursor-pointer border border-slate-200 dark:border-slate-700 rounded-lg p-1 bg-slate-50 dark:bg-slate-800" />
                            <p class="text-[10px] text-slate-400 mt-1">PNG, JPG, WEBP up to 10MB</p>
                        </div>

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
             3. DELETE BRAND CONFIRMATION MODAL
             ============================================================ --}}
        <div x-show="confirmTarget" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs" @click="closeConfirm()"></div>
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 space-y-4" @click.stop>
                    <div class="text-center space-y-2">
                        <div class="w-12 h-12 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 grid place-items-center text-xl mx-auto">🗑️</div>
                        <h4 class="text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.brand_delete_modal_title') }}</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ __('messages.brand_delete_modal_warning') }} <strong class="text-slate-900 dark:text-slate-100" x-text="confirmTarget?.name"></strong>?
                        </p>
                    </div>

                    <form x-ref="deleteForm" method="POST"
                        :action="'/store/{{ $store->slug }}/admin/brands/' + (confirmTarget ? confirmTarget.id : '')">
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
