{{-- Master Data Hub — Generic Preset Manager Partial --}}
@php
    $currentType = (string) ($presetType ?? 'connector_spec');

    $typeTitles = [
        'connector_spec' => __('messages.preset_type_connector'),
        'color' => __('messages.preset_type_color'),
        'shelf_location' => __('messages.preset_type_shelf'),
        'warranty' => __('messages.preset_type_warranty'),
        'return_policy' => __('messages.preset_type_return'),
    ];

    $typeIcons = [
        'connector_spec' => '🔌',
        'color' => '🎨',
        'shelf_location' => '🗄️',
        'warranty' => '🛡️',
        'return_policy' => '🔄',
    ];

    $title = $typeTitles[$currentType] ?? ucfirst(str_replace('_', ' ', $currentType));
    $icon = $typeIcons[$currentType] ?? '🏷️';
@endphp

<div x-data="{
    modalOpen: false,
    modalMode: 'create', // 'create' or 'edit'
    editId: null,
    formType: '{{ $presetType }}',
    formCode: '',
    formName: '',
    formColorHex: '#000000',
    formContent: '',
    formSortOrder: 0,
    confirmDeleteOpen: false,
    deleteTarget: null,

    openCreate() {
        this.modalMode = 'create';
        this.editId = null;
        this.formType = '{{ $presetType }}';
        this.formCode = '';
        this.formName = '';
        this.formColorHex = '#000000';
        this.formContent = '';
        this.formSortOrder = 0;
        this.modalOpen = true;
    },

    openEdit(item) {
        this.modalMode = 'edit';
        this.editId = item.id;
        this.formType = item.type;
        this.formCode = item.code || '';
        this.formName = item.name;
        this.formColorHex = item.color_hex || '#000000';
        this.formContent = item.content || '';
        this.formSortOrder = item.sort_order || 0;
        this.modalOpen = true;
    },

    openDelete(item) {
        this.deleteTarget = item;
        this.confirmDeleteOpen = true;
    }
}"
@open-preset-create.window="openCreate()"
class="space-y-2 sm:space-y-2.5">

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

    {{-- ============================================================
         1. TOOLBAR AREA: Search, View Mode Toggle (Standard Admin Toolbar)
         ============================================================ --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <x-admin.toolbar
            :search="request('search', $search)"
            searchPlaceholder="{{ __('messages.search') }} {{ $title }}..."
            :sortOptions="[]"
            :filters="[]"
            :showViewToggle="true"
            :showExportImport="true"
            :exportUrl="url('/store/' . $store->slug . '/admin/product-master-presets/export?type=' . $presetType)"
            :importUrl="url('/store/' . $store->slug . '/admin/product-master-presets/import?type=' . $presetType)"
            :totalCount="$presetList->total()"
            :paginator="$presetList"
        />
    </div>

    {{-- Floating Action Button for Mobile/Tablet Quick Add --}}
    <button type="button" @click="openCreate()"
            class="fixed bottom-5 right-5 z-40 sm:hidden w-12 h-12 rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-xl shadow-violet-900/40 flex items-center justify-center text-2xl font-bold active:scale-95 transition"
            title="{{ __('messages.preset_add_item') }}">
        +
    </button>

    {{-- ============================================================
         2. SPREADSHEET DATA GRID (TABLE VIEW)
         ============================================================ --}}
    <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto max-h-[72vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        @if ($presetType !== 'warranty' && $presetType !== 'return_policy')
                            <th class="py-2.5 px-3 min-w-[120px]">{{ __('messages.preset_col_code') }}</th>
                        @endif
                        <th class="py-2.5 px-3 min-w-[200px]">{{ __('messages.preset_col_name') }}</th>
                        @if ($presetType === 'color')
                            <th class="py-2.5 px-3 min-w-[140px]">{{ __('messages.preset_col_color') }}</th>
                        @endif
                        @if ($presetType === 'warranty' || $presetType === 'return_policy' || $presetType === 'shelf_location')
                            <th class="py-2.5 px-3 min-w-[220px]">{{ __('messages.preset_col_desc') }}</th>
                        @endif
                        <th class="py-2.5 px-3 text-center min-w-[100px]">{{ __('messages.status') }}</th>
                        <th class="py-2.5 px-3 text-right w-28">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse ($presetList as $item)
                        <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                            @if ($presetType !== 'warranty' && $presetType !== 'return_policy')
                                <td class="py-2 px-3 font-mono font-black text-slate-900 dark:text-slate-100 text-xs whitespace-nowrap">
                                    <span class="bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-xs border border-slate-200 dark:border-slate-700">
                                        {{ $item->code ?: '—' }}
                                    </span>
                                </td>
                            @endif

                            <td class="py-2 px-3">
                                <div class="flex items-center gap-2 font-bold text-slate-900 dark:text-slate-100">
                                    @if ($item->type === 'color' && $item->color_hex)
                                        <span class="w-3.5 h-3.5 rounded-full border border-slate-300 shadow-2xs shrink-0" style="background-color: {{ $item->color_hex }}"></span>
                                    @endif
                                    <span>{{ $item->name }}</span>
                                </div>
                            </td>

                            @if ($presetType === 'color')
                                <td class="py-2 px-3 whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 font-mono text-xs bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700">
                                        <span class="w-3 h-3 rounded-full border border-slate-300" style="background-color: {{ $item->color_hex ?? '#000' }}"></span>
                                        <span>{{ $item->color_hex ?? '#000000' }}</span>
                                    </div>
                                </td>
                            @endif

                            @if ($presetType === 'warranty' || $presetType === 'return_policy' || $presetType === 'shelf_location')
                                <td class="py-2 px-3">
                                    <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1 max-w-md">
                                        {{ $item->content ?: '—' }}
                                    </p>
                                </td>
                            @endif

                            <td class="py-2 px-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    <span>✓</span> {{ __('messages.active') }}
                                </span>
                            </td>

                            <td class="py-2 px-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" @click="openEdit({{ Js::from($item) }})"
                                            class="px-2 py-1 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                                        {{ __('messages.edit') }}
                                    </button>
                                    <button type="button" @click="openDelete({{ Js::from($item) }})"
                                            class="px-2 py-1 rounded text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 transition">
                                        {{ __('messages.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                <div class="text-3xl mb-2">{{ $icon }}</div>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('messages.preset_no_items', ['title' => $title]) }}</p>
                                <p class="text-xs text-slate-400 mt-1">{{ __('messages.preset_empty_hint') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================
         3. RESPONSIVE MULTI-COLUMN CARD GRID (CARDS VIEW)
         ============================================================ --}}
    <div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
        @forelse ($presetList as $item)
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:border-violet-300 dark:hover:border-violet-600/50 hover:shadow-sm transition flex flex-col justify-between group overflow-hidden">
                {{-- Card Content --}}
                <div class="p-3 space-y-2">
                    {{-- Card Header: Code + Active Badge --}}
                    <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                        @if ($item->code)
                            <span class="px-2 py-0.5 rounded font-mono font-black text-xs bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                {{ $item->code }}
                            </span>
                        @else
                            <span class="text-base">{{ $icon }}</span>
                        @endif

                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            <span>✓</span> {{ __('messages.active') }}
                        </span>
                    </div>

                    {{-- Item Name --}}
                    <div>
                        <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            @if ($item->type === 'color' && $item->color_hex)
                                <span class="w-4 h-4 rounded-full border border-slate-300 shadow-2xs shrink-0" style="background-color: {{ $item->color_hex }}"></span>
                            @endif
                            <span class="line-clamp-1">{{ $item->name }}</span>
                        </h4>
                        @if ($item->type === 'color' && $item->color_hex)
                            <div class="text-[10px] font-mono text-slate-400 mt-0.5">HEX: {{ $item->color_hex }}</div>
                        @endif
                    </div>

                    {{-- Description / Terms preview box --}}
                    @if ($item->content)
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/40 p-2 rounded border border-slate-100 dark:border-slate-800 line-clamp-2">
                            {{ $item->content }}
                        </div>
                    @endif
                </div>

                {{-- Card Action Footer --}}
                <div class="p-2.5 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-1.5">
                    <button type="button" @click="openEdit({{ Js::from($item) }})"
                            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
                        {{ __('messages.edit') }}
                    </button>
                    <button type="button" @click="openDelete({{ Js::from($item) }})"
                            class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 text-xs font-bold transition">
                        {{ __('messages.delete') }}
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full p-8 text-center text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="text-3xl mb-2">{{ $icon }}</div>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('messages.preset_no_items', ['title' => $title]) }}</p>
                <button type="button" @click="openCreate()"
                        class="mt-3 inline-flex items-center gap-1 px-3.5 py-1.5 text-xs font-bold rounded-lg bg-violet-600 text-white hover:bg-violet-700 transition">
                    <span>+</span>
                    <span>{{ __('messages.preset_add_item') }}</span>
                </button>
            </div>
        @endforelse
    </div>

    {{-- Bottom Pagination --}}
    @if ($presetList->hasPages())
        <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            {{ $presetList->links() }}
        </div>
    @endif

    {{-- ============================================================
         4. CREATE / EDIT MODAL
         ============================================================ --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="modalOpen = false"></div>
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 space-y-4" @click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100" x-text="modalMode === 'create' ? '➕ {{ __('messages.preset_add_item') }}' : '✏️ {{ __('messages.preset_edit_item') }}'"></h3>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
                </div>

                <form method="POST" :action="modalMode === 'create' ? '{{ url('/store/' . $store->slug . '/admin/product-master-presets') }}' : '{{ url('/store/' . $store->slug . '/admin/product-master-presets') }}/' + editId" class="space-y-3">
                    @csrf
                    <input type="hidden" name="type" value="{{ $presetType }}" />
                    <template x-if="modalMode === 'edit'">
                        <input type="hidden" name="_method" value="PUT" />
                    </template>

                    @if ($presetType !== 'warranty' && $presetType !== 'return_policy')
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.preset_short_code') }} *</label>
                            <input type="text" name="code" x-model="formCode" required placeholder="e.g. TC, BLK, A-01" class="w-full uppercase font-mono rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-black bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500" />
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.preset_display_name') }} *</label>
                        <input type="text" name="name" x-model="formName" required placeholder="e.g. Type-C, Black, Shelf A1, 1 Month Warranty" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500" />
                    </div>

                    @if ($presetType === 'color')
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.preset_color_picker') }}</label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="color_hex" x-model="formColorHex" class="w-9 h-9 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer p-0.5 bg-white dark:bg-slate-800" />
                                <input type="text" x-model="formColorHex" placeholder="#000000" class="flex-1 font-mono rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500" />
                            </div>
                        </div>
                    @endif

                    @if ($presetType === 'warranty' || $presetType === 'return_policy' || $presetType === 'shelf_location')
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.preset_content_terms') }}</label>
                            <textarea name="content" x-model="formContent" rows="3" placeholder="Enter terms, guidelines or description..." class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500"></textarea>
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                            {{ __('messages.cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-xs font-black shadow-md shadow-violet-500/20 transition active:scale-95">
                            {{ __('messages.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================
         5. DELETE CONFIRMATION MODAL
         ============================================================ --}}
    <div x-show="confirmDeleteOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs" @click="confirmDeleteOpen = false"></div>
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 space-y-4" @click.stop>
                <div class="text-center space-y-2">
                    <div class="w-12 h-12 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 grid place-items-center text-xl mx-auto">🗑️</div>
                    <h4 class="text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.preset_delete_title') }}</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.preset_delete_confirm') }}</p>
                    <p class="text-xs font-bold text-slate-900 dark:text-slate-100" x-text="deleteTarget?.name"></p>
                </div>

                <form method="POST" :action="'{{ url('/store/' . $store->slug . '/admin/product-master-presets') }}/' + (deleteTarget ? deleteTarget.id : '')">
                    @csrf
                    @method('DELETE')
                    <div class="flex items-center justify-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="confirmDeleteOpen = false" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                            {{ __('messages.cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-black shadow-md shadow-rose-500/20 transition active:scale-95">
                            {{ __('messages.confirm_delete') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
