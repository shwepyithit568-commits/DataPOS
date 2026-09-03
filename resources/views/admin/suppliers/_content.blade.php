@php
    $highlightSupplier = session('highlight_supplier');
@endphp

<div class="w-full space-y-0.5 pb-6"
     x-data="{
         modalOpen: false,
         modalMode: 'create', // 'create' or 'edit'
         formId: null,
         formName: '',
         formPhone: '',
         formEmail: '',
         formContactPerson: '',
         formAddress: '',
         formNotes: '',
         saving: false,
         confirmTarget: null,
         deleting: false,
         viewMode: localStorage.getItem('admin_view_mode') || 'table',

         openCreate() {
             this.modalMode = 'create';
             this.formId = null;
             this.formName = '';
             this.formPhone = '';
             this.formEmail = '';
             this.formContactPerson = '';
             this.formAddress = '';
             this.formNotes = '';
             this.saving = false;
             this.modalOpen = true;
             this.$nextTick(() => this.$refs.supplierModalName?.focus());
         },

         openEdit(supplier) {
             this.modalMode = 'edit';
             this.formId = supplier.id;
             this.formName = supplier.name || '';
             this.formPhone = supplier.phone || '';
             this.formEmail = supplier.email || '';
             this.formContactPerson = supplier.contact_person || '';
             this.formAddress = supplier.address || '';
             this.formNotes = supplier.notes || '';
             this.saving = false;
             this.modalOpen = true;
             this.$nextTick(() => this.$refs.supplierModalName?.focus());
         },

         closeModal() {
             this.modalOpen = false;
         },

         openConfirm(supplier) {
             this.confirmTarget = supplier;
             this.deleting = false;
         },

         closeConfirm() {
             this.confirmTarget = null;
         }
     }"
     @open-add-supplier.window="openCreate()"
     @keydown.escape.window="if (modalOpen) closeModal(); else if (confirmTarget) closeConfirm();"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

    {{-- 1. Top Header Banner (Ultra-Dense 36px) --}}
    <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none">
        <div class="flex items-center gap-2 min-w-0">
            <span class="w-7 h-7 rounded bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 border border-violet-200 dark:border-violet-800 grid place-items-center text-sm font-black shrink-0">
                🏭
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate">
                        {{ __('messages.sidebar_suppliers') }}
                    </h1>
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        {{ $store->name }}
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 font-mono truncate hidden sm:block">
                    Manage suppliers, purchase orders & payables
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap shrink-0">
            <a href="{{ url('/store/' . $store->slug . '/admin/suppliers/aging') }}"
               class="h-7 px-2 rounded text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition inline-flex items-center gap-1 shadow-2xs">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span>{{ __('messages.supplier_col_outstanding') }}</span>
            </a>
            <button type="button" @click="openCreate()"
                    class="h-7 px-2.5 rounded text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-2xs hover:shadow-violet-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.supplier_add_title') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. Compact Centered Stat Cards (4 Columns) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1">
        {{-- Card 1: Total Suppliers --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 flex items-center justify-center text-sm font-black shrink-0">
                🏭
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none">
                    {{ __('messages.sidebar_suppliers') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-slate-900 dark:text-slate-100 tabular-nums mt-0.5">
                    {{ number_format($stats['total']) }}
                </div>
            </div>
        </div>

        {{-- Card 2: With Balance / Debt --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-amber-200/90 dark:border-amber-900/50 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm font-black shrink-0">
                ⚠️
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400 leading-none">
                    {{ __('messages.supplier_has_balance') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-amber-600 dark:text-amber-400 tabular-nums mt-0.5">
                    {{ number_format($stats['owing']) }}
                </div>
            </div>
        </div>

        {{-- Card 3: Total Outstanding Debt --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 flex items-center justify-center text-sm font-black shrink-0">
                💰
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none">
                    {{ __('messages.supplier_col_outstanding') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-amber-600 dark:text-amber-400 tabular-nums mt-0.5 truncate">
                    Ks {{ number_format((float) $stats['owing_amount'], 0) }}
                </div>
            </div>
        </div>

        {{-- Card 4: Clear Balance --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-emerald-200/90 dark:border-emerald-900/50 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm font-black shrink-0">
                ✓
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 leading-none">
                    Zero Balance
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-emerald-600 dark:text-emerald-400 tabular-nums mt-0.5">
                    {{ number_format(max(0, $stats['total'] - $stats['owing'])) }}
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Toolbar Area --}}
    <div class="p-1 sm:p-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs">
        <x-admin.toolbar
            :search="request('search', '')"
            searchPlaceholder="{{ __('messages.supplier_search_placeholder') }}"
            :sort="request('sort', 'newest')"
            :sortOptions="[
                'newest' => __('messages.supplier_sort_newest'),
                'oldest' => __('messages.supplier_sort_oldest'),
                'name_asc' => __('messages.supplier_sort_name_asc'),
                'name_desc' => __('messages.supplier_sort_name_desc'),
                'most_owed' => __('messages.supplier_sort_most_owed'),
            ]"
            :filters="[
                'has_balance' => [
                    'label' => __('messages.supplier_filter_balance'),
                    'options' => [
                        'yes' => __('messages.supplier_has_balance'),
                        'no' => __('messages.supplier_no_balance'),
                    ]
                ]
            ]"
            :showViewToggle="true"
            :showExportImport="true"
            :importUrl="url('/store/' . $store->slug . '/admin/suppliers/import')"
            :exportUrl="url('/store/' . $store->slug . '/admin/suppliers/export')"
            :liveSearch="true"
            :perPageOptions="[25 => '25', 50 => '50', 100 => '100']"
            :totalCount="$totalCount"
            :paginator="$suppliers"
        />
    </div>

    {{-- Floating Action Button for Mobile/Tablet Quick Add --}}
    <button type="button" @click="openCreate()"
            class="fixed bottom-5 right-5 z-40 sm:hidden w-12 h-12 rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-xl shadow-violet-900/40 flex items-center justify-center text-2xl font-bold active:scale-95 transition"
            title="{{ __('messages.supplier_add_title') }}">
        +
    </button>

    {{-- 4. Spreadsheet Table View --}}
    <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto max-h-[72vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        <th class="py-1.5 px-2.5 min-w-[200px]">{{ __('messages.supplier_col_name') }}</th>
                        <th class="py-1.5 px-2.5 min-w-[140px]">{{ __('messages.supplier_col_phone') }}</th>
                        <th class="py-1.5 px-2.5 hidden md:table-cell min-w-[140px]">{{ __('messages.supplier_col_contact') }}</th>
                        <th class="py-1.5 px-2.5 text-center w-20">PO</th>
                        <th class="py-1.5 px-2.5 text-right min-w-[140px]">{{ __('messages.supplier_col_outstanding') }}</th>
                        <th class="py-1.5 px-2.5 text-right w-36">{{ __('messages.supplier_col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse ($suppliers as $supplier)
                        <tr id="supplier-row-{{ $supplier->id }}"
                            class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition {{ $highlightSupplier && (int) $highlightSupplier === (int) $supplier->id ? 'bg-violet-50/60 dark:bg-violet-950/20 ring-2 ring-violet-400/70' : '' }}">
                            
                            {{-- Name & Avatar --}}
                            <td class="py-1.5 px-2.5">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-white grid place-items-center font-black text-xs select-none shadow-2xs"
                                         title="{{ $supplier->name }}">
                                        {{ mb_strtoupper(mb_substr(trim($supplier->name), 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 dark:text-slate-100 text-xs block truncate" title="{{ $supplier->name }}">
                                            {{ $supplier->name }}
                                        </span>
                                        @if ($supplier->email)
                                            <span class="text-[10px] text-slate-400 block truncate">{{ $supplier->email }}</span>
                                        @endif
                                        @if ($highlightSupplier && (int) $highlightSupplier === (int) $supplier->id)
                                            <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 text-[9px] font-bold">NEW</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Phone --}}
                            <td class="py-1.5 px-2.5 whitespace-nowrap">
                                @if ($supplier->phone)
                                    <a href="tel:{{ preg_replace('/\s+/', '', $supplier->phone) }}"
                                       class="font-mono text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">{{ $supplier->phone }}</a>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                                @if ($supplier->contact_person)
                                    <div class="md:hidden text-[10px] text-slate-400 truncate max-w-28">{{ $supplier->contact_person }}</div>
                                @endif
                            </td>

                            {{-- Contact Person --}}
                            <td class="py-1.5 px-2.5 hidden md:table-cell">
                                <span class="text-xs text-slate-700 dark:text-slate-300">{{ $supplier->contact_person ?? '—' }}</span>
                            </td>

                            {{-- PO Count --}}
                            <td class="py-1.5 px-2.5 text-center">
                                @if ($supplier->purchase_orders_count > 0)
                                    <span class="inline-block px-1.5 py-0.2 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold font-mono">
                                        {{ $supplier->purchase_orders_count }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-300 dark:text-slate-600 font-mono">0</span>
                                @endif
                            </td>

                            {{-- Outstanding Balance --}}
                            <td class="py-1.5 px-2.5 text-right whitespace-nowrap">
                                @if ($supplier->has_outstanding_balance)
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800 text-xs font-black font-mono">
                                        Ks {{ number_format($supplier->remaining_balance, 0) }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 font-mono">—</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-1.5 px-2.5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" @click="openEdit({{ Js::from($supplier) }})"
                                            class="px-2 py-1 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition flex items-center gap-1 cursor-pointer">
                                        <span>✏️</span> {{ __('messages.edit') }}
                                    </button>
                                    @if ($supplier->purchase_orders_count > 0)
                                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-400 text-xs font-bold cursor-not-allowed"
                                              title="{{ __('messages.supplier_delete_blocked', ['count' => $supplier->purchase_orders_count]) }}">
                                            🔒
                                        </span>
                                    @else
                                        <button type="button" @click="openConfirm({{ Js::from($supplier) }})"
                                                class="px-2 py-1 rounded text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 transition active:scale-95 cursor-pointer"
                                                title="{{ __('messages.delete') }}">
                                            🗑️
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                <div class="text-3xl mb-2 opacity-55">🏭</div>
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_none') }}</div>
                                <button type="button" @click="openCreate()"
                                        class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-bold text-white bg-violet-600 hover:bg-violet-700 transition shadow-xs cursor-pointer">
                                    + {{ __('messages.supplier_add_title') }}
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($suppliers->hasPages())
            <div class="p-2.5 border-t border-slate-100 dark:border-slate-800 text-xs">{{ $suppliers->links() }}</div>
        @endif
    </div>

    {{-- 5. Responsive Multi-Column Card Grid View --}}
    <div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-1.5 sm:gap-2">
        @forelse ($suppliers as $supplier)
            <div id="supplier-card-{{ $supplier->id }}"
                 class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg overflow-hidden shadow-2xs hover:border-violet-300 dark:hover:border-violet-600/50 hover:shadow-sm transition flex flex-col justify-between group {{ $highlightSupplier && (int) $highlightSupplier === (int) $supplier->id ? 'ring-2 ring-violet-400/70' : '' }}">
                
                <div class="p-2.5 space-y-2">
                    {{-- Card Header: Avatar + Name + Balance Pill --}}
                    <div class="flex items-start gap-2 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                        <div class="shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-white grid place-items-center font-black text-xs select-none shadow-2xs">
                            {{ mb_strtoupper(mb_substr(trim($supplier->name), 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 line-clamp-1" title="{{ $supplier->name }}">
                                {{ $supplier->name }}
                            </h4>
                            @if ($supplier->phone)
                                <a href="tel:{{ preg_replace('/\s+/', '', $supplier->phone) }}"
                                   class="block text-[11px] font-mono font-bold text-violet-600 dark:text-violet-400 hover:underline">
                                    {{ $supplier->phone }}
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Contact & Email --}}
                    <div class="text-[11px] space-y-0.5 text-slate-500 dark:text-slate-400">
                        @if ($supplier->contact_person)
                            <div class="truncate"><span class="text-slate-400">Contact:</span> {{ $supplier->contact_person }}</div>
                        @endif
                        @if ($supplier->email)
                            <div class="truncate font-mono"><span class="text-slate-400">Email:</span> {{ $supplier->email }}</div>
                        @endif
                    </div>

                    {{-- Financial Stats Box --}}
                    <div class="bg-slate-50 dark:bg-slate-800/60 p-1.5 rounded border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <div>
                            <span class="text-[10px] text-slate-400 block uppercase font-bold">Purchase Orders</span>
                            <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ $supplier->purchase_orders_count }} PO</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 block uppercase font-bold">Payables</span>
                            <span class="font-mono font-black {{ $supplier->has_outstanding_balance ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                                {{ $supplier->has_outstanding_balance ? 'Ks ' . number_format($supplier->remaining_balance, 0) : 'Clear' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Footer Actions --}}
                <div class="p-2 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1">
                    <button type="button" @click="openEdit({{ Js::from($supplier) }})"
                            class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                        <span>✏️</span> Edit
                    </button>

                    @if ($supplier->purchase_orders_count > 0)
                        <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-400 text-xs font-bold"
                              title="{{ __('messages.supplier_delete_blocked', ['count' => $supplier->purchase_orders_count]) }}">
                            🔒 In Use
                        </span>
                    @else
                        <button type="button" @click="openConfirm({{ Js::from($supplier) }})"
                                class="px-2 py-1 rounded bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 text-xs font-bold transition active:scale-95 cursor-pointer"
                                title="{{ __('messages.delete') }}">
                            🗑️ Delete
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 p-8 rounded text-center text-slate-400 shadow-2xs">
                <div class="text-3xl mb-2 opacity-55">🏭</div>
                <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_none') }}</div>
                <button type="button" @click="openCreate()"
                        class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-bold text-white bg-violet-600 hover:bg-violet-700 transition shadow-xs cursor-pointer">
                    + {{ __('messages.supplier_add_title') }}
                </button>
            </div>
        @endforelse
    </div>

    {{-- Pagination for Card view --}}
    @if ($suppliers->hasPages())
        <div x-show="viewMode === 'card' || viewMode === 'cards'" class="p-2.5 bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded text-xs">
            {{ $suppliers->links() }}
        </div>
    @endif

    {{-- 6. Unified Alpine Modal Dialog for Create & Edit --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="closeModal()"></div>
        <div class="min-h-full flex items-center justify-center p-3">
            <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-2xl p-4 space-y-3" @click.stop>
                
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                        <span x-text="modalMode === 'create' ? '➕ {{ __('messages.supplier_add_title') }}' : '✏️ {{ __('messages.edit') }}: ' + formName"></span>
                    </h3>
                    <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold cursor-pointer">&times;</button>
                </div>

                <form method="POST"
                      :action="modalMode === 'create' ? '{{ url('/store/' . $store->slug . '/admin/suppliers') }}' : ('{{ url('/store/' . $store->slug . '/admin/suppliers') }}/' + formId)"
                      @submit="if (saving) { $event.preventDefault(); } else { saving = true; }"
                      class="space-y-2.5">
                    @csrf
                    <template x-if="modalMode === 'edit'">
                        <input type="hidden" name="_method" value="PUT" />
                    </template>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                            {{ __('messages.supplier_name') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" x-ref="supplierModalName" name="name" x-model="formName" required maxlength="255"
                               placeholder="{{ __('messages.supplier_name_placeholder') }}"
                               class="w-full rounded border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-violet-500" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.supplier_phone') }}
                            </label>
                            <input type="tel" name="phone" x-model="formPhone" maxlength="50"
                                   placeholder="{{ __('messages.supplier_phone_placeholder') }}"
                                   class="w-full rounded border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 text-xs font-mono bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-violet-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.supplier_email') }}
                            </label>
                            <input type="email" name="email" x-model="formEmail" maxlength="100"
                                   placeholder="{{ __('messages.supplier_email_placeholder') }}"
                                   class="w-full rounded border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 text-xs font-mono bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-violet-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.supplier_contact_person') }}
                            </label>
                            <input type="text" name="contact_person" x-model="formContactPerson" maxlength="100"
                                   placeholder="{{ __('messages.supplier_contact_placeholder') }}"
                                   class="w-full rounded border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-violet-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.supplier_address') }}
                            </label>
                            <input type="text" name="address" x-model="formAddress" maxlength="255"
                                   placeholder="{{ __('messages.supplier_address_placeholder') }}"
                                   class="w-full rounded border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-violet-500" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                            {{ __('messages.supplier_notes') }}
                        </label>
                        <textarea name="notes" x-model="formNotes" rows="2" maxlength="1000"
                                  placeholder="{{ __('messages.supplier_notes_placeholder') }}"
                                  class="w-full rounded border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-violet-500 resize-none"></textarea>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex items-center justify-end gap-1.5 pt-2.5 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="closeModal()"
                                class="h-7 px-3 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition cursor-pointer">
                            {{ __('messages.cancel') }}
                        </button>
                        <button type="submit" :disabled="saving"
                                class="h-7 px-4 rounded bg-violet-600 hover:bg-violet-500 text-white text-xs font-black shadow-2xs hover:shadow-violet-500/20 transition active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer">
                            <span x-show="!saving" class="inline-flex items-center gap-1">
                                <span x-text="modalMode === 'create' ? '+ {{ __('messages.supplier_save') }}' : '✓ {{ __('messages.save_changes') }}'"></span>
                            </span>
                            <span x-show="saving" class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                <span>Saving...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- 7. Delete Confirmation Dialog --}}
    <div x-show="confirmTarget" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="closeConfirm()"></div>
        <div class="min-h-full flex items-center justify-center p-3">
            <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-2xl p-4 space-y-3" @click.stop>
                <div class="text-center space-y-1.5">
                    <div class="w-10 h-10 rounded-lg bg-rose-100 dark:bg-rose-950/60 text-rose-600 grid place-items-center text-lg mx-auto">🗑️</div>
                    <h4 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.supplier_delete_title') }}</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ __('messages.supplier_delete_confirm') }} <strong class="text-slate-900 dark:text-slate-100" x-text="confirmTarget?.name"></strong>?
                    </p>
                </div>

                <form method="POST"
                      :action="'/store/{{ $store->slug }}/admin/suppliers/' + (confirmTarget ? confirmTarget.id : '')">
                    @csrf
                    @method('DELETE')
                    <div class="flex items-center justify-center gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="closeConfirm()"
                                class="h-7 px-3 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition cursor-pointer">
                            {{ __('messages.cancel') }}
                        </button>
                        <button type="submit" :disabled="deleting"
                                class="h-7 px-4 rounded bg-rose-600 hover:bg-rose-500 text-white text-xs font-black shadow-2xs hover:shadow-rose-500/20 transition active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer">
                            <span x-show="!deleting">{{ __('messages.delete') }}</span>
                            <span x-show="deleting">Deleting...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
