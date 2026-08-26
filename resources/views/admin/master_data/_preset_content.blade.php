{{-- Master Data Hub — Generic Preset Manager Partial --}}
@php
    $currentType = (string) ($presetType ?? 'connector_spec');

    $typeTitles = [
        'connector_spec' => 'Connector / Spec Codes (ကြိုးခေါင်း/သတ်မှတ်ချက်များ)',
        'color' => 'Color Codes (အရောင်များ)',
        'shelf_location' => 'Shelf / Bin Locations (စင် / အကန့် နေရာများ)',
        'warranty' => 'Warranty Presets (အာမခံ သတ်မှတ်ချက်များ)',
        'return_policy' => 'Return Policy Presets (ပစ္စည်းပြန်လဲ/ပြန်အပ် မူဝါဒများ)',
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
}" class="space-y-4">

    {{-- Toolbar: Search, Add CTA --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="text-xl">{{ $icon }}</span>
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ $title }}</h3>
                <p class="text-[11px] text-slate-400">Total: {{ number_format($presetList->total()) }} items</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug]) }}" class="relative">
                <input type="hidden" name="tab" value="{{ $tabName }}" />
                <input type="search" name="search" value="{{ $search }}" placeholder="Search {{ $title }}..."
                       class="pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500 w-44 sm:w-64" />
                <svg class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </form>

            <button type="button" @click="openCreate()"
                    class="px-3.5 py-1.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-bold text-xs shadow-sm transition flex items-center gap-1 shrink-0">
                <span>+</span>
                <span>Add Item</span>
            </button>
        </div>
    </div>

    {{-- Presets Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        @if ($presetType !== 'warranty' && $presetType !== 'return_policy')
                            <th class="p-3.5">Short Code</th>
                        @endif
                        <th class="p-3.5">Name / Details</th>
                        @if ($presetType === 'color')
                            <th class="p-3.5">Color Preview</th>
                        @endif
                        @if ($presetType === 'warranty' || $presetType === 'return_policy')
                            <th class="p-3.5">Policy / Content Terms</th>
                        @endif
                        <th class="p-3.5 text-center">Status</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($presetList as $item)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/40 transition">
                            @if ($presetType !== 'warranty' && $presetType !== 'return_policy')
                                <td class="p-3.5 font-mono font-black text-slate-900 dark:text-slate-100 text-sm">
                                    <span class="bg-slate-100 dark:bg-slate-900 px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700">
                                        {{ $item->code ?: '—' }}
                                    </span>
                                </td>
                            @endif

                            <td class="p-3.5">
                                <div class="flex items-center gap-2 font-bold text-slate-900 dark:text-slate-100">
                                    @if ($item->type === 'color' && $item->color_hex)
                                        <span class="w-4 h-4 rounded-full border border-slate-300 shadow-2xs shrink-0" style="background-color: {{ $item->color_hex }}"></span>
                                    @endif
                                    <span>{{ $item->name }}</span>
                                </div>
                            </td>

                            @if ($presetType === 'color')
                                <td class="p-3.5">
                                    <div class="inline-flex items-center gap-1.5 font-mono text-[11px] bg-slate-100 dark:bg-slate-900 px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700">
                                        <span class="w-3 h-3 rounded-full border border-slate-300" style="background-color: {{ $item->color_hex ?? '#000' }}"></span>
                                        <span>{{ $item->color_hex ?? '#000000' }}</span>
                                    </div>
                                </td>
                            @endif

                            @if ($presetType === 'warranty' || $presetType === 'return_policy')
                                <td class="p-3.5">
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 max-w-md">
                                        {{ $item->content ?: '—' }}
                                    </p>
                                </td>
                            @endif

                            <td class="p-3.5 text-center">
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                </span>
                            </td>

                            <td class="p-3.5 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" @click="openEdit({{ Js::from($item) }})"
                                            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold transition">
                                        Edit
                                    </button>
                                    <button type="button" @click="openDelete({{ Js::from($item) }})"
                                            class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 font-bold transition">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                <div class="text-3xl mb-2 opacity-50">{{ $icon }}</div>
                                <p class="text-sm font-bold text-slate-600 dark:text-slate-300">No items found in {{ $title }}.</p>
                                <p class="text-xs mt-1">Click "Add Item" above to create one.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($presetList->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-700">
                {{ $presetList->links() }}
            </div>
        @endif
    </div>

    {{-- Create / Edit Modal --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs" @click="modalOpen = false"></div>
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-2xl p-6 space-y-4" @click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white" x-text="modalMode === 'create' ? '➕ Add ' + '{{ $title }}' : '✏️ Edit ' + '{{ $title }}'"></h3>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                </div>

                <form method="POST" :action="modalMode === 'create' ? '{{ url('/store/' . $store->slug . '/admin/product-master-presets') }}' : '{{ url('/store/' . $store->slug . '/admin/product-master-presets') }}/' + editId" class="space-y-3.5">
                    @csrf
                    <input type="hidden" name="type" value="{{ $presetType }}" />
                    <template x-if="modalMode === 'edit'">
                        <input type="hidden" name="_method" value="PUT" />
                    </template>

                    @if ($presetType !== 'warranty' && $presetType !== 'return_policy')
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Short Code (အတိုကောက် ကုဒ်) *</label>
                            <input type="text" name="code" x-model="formCode" required placeholder="e.g. TC, BLK, A-01" class="w-full uppercase font-mono rounded-2xl border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-black bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500" />
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Display Name (အမည်) *</label>
                        <input type="text" name="name" x-model="formName" required placeholder="e.g. Type-C, Black, Shelf A1, 1 Month Warranty" class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500" />
                    </div>

                    @if ($presetType === 'color')
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Color Picker (အရောင်ရွေးချယ်ရန်)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="color_hex" x-model="formColorHex" class="w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer p-0.5 bg-white dark:bg-slate-800" />
                                <input type="text" x-model="formColorHex" placeholder="#000000" class="flex-1 font-mono rounded-2xl border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none" />
                            </div>
                        </div>
                    @endif

                    @if ($presetType === 'warranty' || $presetType === 'return_policy' || $presetType === 'shelf_location')
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Content / Terms / Description</label>
                            <textarea name="content" x-model="formContent" rows="3" placeholder="Enter terms, guidelines or description..." class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500"></textarea>
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-xs font-black shadow-md shadow-violet-500/20 transition">
                            Save Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div x-show="confirmDeleteOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs" @click="confirmDeleteOpen = false"></div>
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-2xl p-5 space-y-4" @click.stop>
                <div class="text-center space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 grid place-items-center text-xl mx-auto">🗑️</div>
                    <h4 class="text-sm font-black text-slate-900 dark:text-white">Delete Item?</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Are you sure you want to delete <span class="font-bold text-slate-900 dark:text-white" x-text="deleteTarget?.name"></span>?
                    </p>
                </div>

                <form method="POST" :action="'{{ url('/store/' . $store->slug . '/admin/product-master-presets') }}/' + (deleteTarget ? deleteTarget.id : '')">
                    @csrf
                    @method('DELETE')
                    <div class="flex items-center justify-center gap-2 pt-2">
                        <button type="button" @click="confirmDeleteOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-black shadow-md shadow-rose-500/20 transition">
                            Confirm Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
