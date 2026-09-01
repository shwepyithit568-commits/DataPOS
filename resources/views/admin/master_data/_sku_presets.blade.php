{{-- Master Data Hub — SKU Code Presets Tab (Models, Connectors, Colors) --}}
@php
    $skuTypeLabels = [
        'model' => 'Model Code (မော်ဒယ်)',
        'connector_spec' => 'Specs / Attributes (သတ်မှတ်ချက်များ)',
        'color' => 'Color Code (အရောင်)',
        'quality' => 'Quality (အရည်အသွေး)',
        'capacity' => 'Capacity (ပမာဏ/ဝပ်အား)',
    ];

    $skuTypeBadges = [
        'model' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
        'connector_spec' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        'color' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        'quality' => 'bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800',
        'capacity' => 'bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border-sky-200 dark:border-sky-800',
    ];
@endphp

<div x-data="{
    modalOpen: false,
    modalMode: 'create', // 'create' or 'edit'
    editId: null,
    formType: '{{ $selectedType ?: 'model' }}',
    formCode: '',
    formName: '',
    formColorHex: '#000000',
    formDescription: '',
    formSortOrder: 0,
    confirmDeleteOpen: false,
    deleteTarget: null,

    openCreate(type = 'model') {
        this.modalMode = 'create';
        this.editId = null;
        this.formType = type;
        this.formCode = '';
        this.formName = '';
        this.formColorHex = '#000000';
        this.formDescription = '';
        this.formSortOrder = 0;
        this.modalOpen = true;
    },

    openEdit(item) {
        this.modalMode = 'edit';
        this.editId = item.id;
        this.formType = item.type;
        this.formCode = item.code;
        this.formName = item.name;
        this.formColorHex = item.color_hex || '#000000';
        this.formDescription = item.description || '';
        this.formSortOrder = item.sort_order || 0;
        this.modalOpen = true;
    },

    openDelete(item) {
        this.deleteTarget = item;
        this.confirmDeleteOpen = true;
    }
}" class="space-y-4">

    {{-- Toolbar: Filter Chips, Search, Add CTA --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'sku-presets']) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ !$selectedType ? 'bg-violet-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
                All ({{ $typeCounts['all'] ?? 0 }})
            </a>
            <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'sku-presets', 'type' => 'model']) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $selectedType === 'model' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
                📱 Models ({{ $typeCounts['model'] ?? 0 }})
            </a>
            <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'sku-presets', 'type' => 'connector_spec']) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $selectedType === 'connector_spec' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
                ⚙️ Specs & Attributes ({{ $typeCounts['connector_spec'] ?? 0 }})
            </a>
            <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'sku-presets', 'type' => 'color']) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $selectedType === 'color' ? 'bg-amber-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
                🎨 Colors ({{ $typeCounts['color'] ?? 0 }})
            </a>
        </div>

        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug]) }}" class="relative">
                <input type="hidden" name="tab" value="sku-presets" />
                @if($selectedType)<input type="hidden" name="type" value="{{ $selectedType }}" />@endif
                <input type="search" name="search" value="{{ $search }}" placeholder="Search codes / names..."
                       class="pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500 w-44 sm:w-56" />
                <svg class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </form>

            <button type="button" @click="openCreate('{{ $selectedType ?: 'model' }}')"
                    class="px-3.5 py-1.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white font-bold text-xs shadow-sm transition flex items-center gap-1 shrink-0">
                <span>+</span>
                <span>{{ __('messages.add_code_preset') }}</span>
            </button>
        </div>
    </div>

    {{-- Presets Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="p-3.5">Type</th>
                        <th class="p-3.5">Short Code</th>
                        <th class="p-3.5">Name / Description</th>
                        <th class="p-3.5 text-center">Status</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse ($skuPresets as $item)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/40 transition">
                            <td class="p-3.5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $skuTypeBadges[$item->type] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    {{ $skuTypeLabels[$item->type] ?? ucfirst($item->type) }}
                                </span>
                            </td>
                            <td class="p-3.5 font-mono font-black text-slate-900 dark:text-slate-100 text-sm">
                                <span class="bg-slate-100 dark:bg-slate-900 px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700">
                                    {{ $item->code }}
                                </span>
                            </td>
                            <td class="p-3.5">
                                <div class="flex items-center gap-2 font-bold text-slate-900 dark:text-slate-100">
                                    @if ($item->type === 'color' && $item->color_hex)
                                        <span class="w-3.5 h-3.5 rounded-full border border-slate-300 shadow-2xs shrink-0" style="background-color: {{ $item->color_hex }}"></span>
                                    @endif
                                    <span>{{ $item->name }}</span>
                                </div>
                                @if ($item->description)
                                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $item->description }}</p>
                                @endif
                            </td>
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
                            <td colspan="5" class="p-8 text-center text-slate-400">
                                <div class="text-3xl mb-2 opacity-50">🏷️</div>
                                <p class="text-sm font-bold text-slate-600 dark:text-slate-300">No SKU code presets found.</p>
                                <p class="text-xs mt-1">Click "Add Code Preset" above to create one.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($skuPresets->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-700">
                {{ $skuPresets->links() }}
            </div>
        @endif
    </div>

    {{-- Create / Edit Modal --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs" @click="modalOpen = false"></div>
        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-2xl p-6 space-y-4" @click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white" x-text="modalMode === 'create' ? '➕ Add SKU Code Preset' : '✏️ Edit SKU Code Preset'"></h3>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                </div>

                <form method="POST" :action="modalMode === 'create' ? '{{ url('/store/' . $store->slug . '/admin/sku-code-presets') }}' : '{{ url('/store/' . $store->slug . '/admin/sku-code-presets') }}/' + editId" class="space-y-3.5">
                    @csrf
                    <template x-if="modalMode === 'edit'">
                        <input type="hidden" name="_method" value="PUT" />
                    </template>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Preset Type *</label>
                        <select name="type" x-model="formType" required class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="model">Model Code (မော်ဒယ်)</option>
                            <option value="connector_spec">Specs / Attributes (သတ်မှတ်ချက်များ)</option>
                            <option value="color">Color Code (အရောင်)</option>
                            <option value="quality">Quality Level (အရည်အသွေး)</option>
                            <option value="capacity">Capacity / Power (ပမာဏ/ဝပ်အား)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Short Code (အတိုကောက် ကုဒ်) *</label>
                        <input type="text" name="code" x-model="formCode" required placeholder="e.g. L009, TC, BLK" class="w-full uppercase font-mono rounded-2xl border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-black bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500" />
                        <p class="text-[10px] text-slate-400 mt-0.5">Used in SKU generation formula (e.g. 168-L009-CB-TC-BLK).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Display Name (ဖော်ပြလိုသောအမည်) *</label>
                        <input type="text" name="name" x-model="formName" required placeholder="e.g. L009 Cable, Type-C, Black" class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500" />
                    </div>

                    <div x-show="formType === 'color'" x-cloak>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Color Preview (အရောင်)</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="color_hex" x-model="formColorHex" class="w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer p-0.5 bg-white dark:bg-slate-800" />
                            <input type="text" x-model="formColorHex" placeholder="#000000" class="flex-1 font-mono rounded-2xl border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Description (မှတ်ချက်)</label>
                        <input type="text" name="description" x-model="formDescription" placeholder="Optional notes..." class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-xs font-black shadow-md shadow-violet-500/20 transition">
                            Save Preset
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
                    <h4 class="text-sm font-black text-slate-900 dark:text-white">Delete SKU Preset?</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Are you sure you want to delete <span class="font-bold text-slate-900 dark:text-white" x-text="deleteTarget?.code"></span> (<span x-text="deleteTarget?.name"></span>)?
                    </p>
                </div>

                <form method="POST" :action="'{{ url('/store/' . $store->slug . '/admin/sku-code-presets') }}/' + (deleteTarget ? deleteTarget.id : '')">
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
