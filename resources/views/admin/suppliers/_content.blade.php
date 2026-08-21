@php
    $startTab = $errors->any() ? 'add' : 'list';
    $highlightSupplier = session('highlight_supplier');
@endphp
<div class="w-full space-y-4 sm:space-y-6">

    {{-- Header + quick actions --}}
    <div class="admin-page-header flex-col sm:flex-row sm:items-center gap-3">
        <div class="min-w-0">
            <h1 class="admin-page-title">{{ __('messages.sidebar_suppliers') }}</h1>
            <p class="admin-page-sub">{{ $store->name }} — {{ __('messages.supplier_index_sub') }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ url('/store/' . $store->slug . '/admin/suppliers/aging') }}"
               class="inline-flex items-center gap-1.5 min-h-11 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 hover:bg-amber-100 dark:hover:bg-amber-950/70 transition">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span class="hidden sm:inline">{{ __('messages.supplier_col_outstanding') }}</span>
                <span class="sm:hidden">{{ __('messages.supplier_col_outstanding') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/admin/suppliers') }}" x-data
               @click.prevent="$dispatch('open-add-supplier')"
               class="inline-flex items-center gap-1.5 min-h-11 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-violet-600 hover:bg-violet-700 shadow transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                {{ __('messages.supplier_add_title') }}
            </a>
        </div>
    </div>

    {{-- Summary stats --}}
    <div class="grid grid-cols-3 gap-2 sm:gap-3">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200/80 dark:border-slate-700 p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-orange-100 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 grid place-items-center text-base sm:text-lg">🏭</div>
            <div class="min-w-0">
                <p class="text-lg sm:text-2xl font-black text-gray-900 dark:text-slate-100 leading-none">{{ number_format($stats['total']) }}</p>
                <p class="text-[10px] sm:text-xs text-gray-500 dark:text-slate-400 mt-1 truncate">{{ __('messages.sidebar_suppliers') }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200/80 dark:border-slate-700 p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center">
                <svg class="w-4.5 h-4.5" style="width:18px;height:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-lg sm:text-2xl font-black text-amber-600 dark:text-amber-400 leading-none">{{ number_format($stats['owing']) }}</p>
                <p class="text-[10px] sm:text-xs text-gray-500 dark:text-slate-400 mt-1 truncate">{{ __('messages.supplier_has_balance') }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200/80 dark:border-slate-700 p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center">
                <svg class="w-4.5 h-4.5" style="width:18px;height:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 11v9a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1v-9M2 11l10-8 10 8M14 10a2 2 0 1 1-4 0"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-base sm:text-2xl font-black text-gray-900 dark:text-slate-100 leading-none truncate">Ks {{ number_format((float) $stats['owing_amount'], 0) }}</p>
                <p class="text-[10px] sm:text-xs text-gray-500 dark:text-slate-400 mt-1 truncate">{{ __('messages.supplier_col_outstanding') }}</p>
            </div>
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

    {{-- Suppliers / Add Supplier (tabbed) — toolbar lives inside so it can hide on the Add tab --}}
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
        @open-add-supplier.window="openAdd()"
        @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)"
        class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden transition-colors duration-200">

        {{-- Tab bar --}}
        <div class="flex border-b dark:border-slate-700 bg-gray-50/60 dark:bg-slate-900/40" role="tablist">
            <button type="button" role="tab" :aria-selected="tab === 'list'" @click="tab = 'list'"
                class="flex-1 sm:flex-none sm:px-6 py-3 text-xs sm:text-sm sm:text-base font-semibold flex items-center justify-center gap-1.5 sm:gap-2 transition border-b-2 -mb-px min-w-0"
                :class="tab === 'list' ? 'border-violet-600 text-violet-700 dark:text-violet-300 bg-white dark:bg-slate-800' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-800'">
                <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-orange-100 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 flex items-center justify-center text-xs sm:text-sm shrink-0">🏭</span>
                <span class="truncate">{{ __('messages.sidebar_suppliers') }}</span>
                <span class="shrink-0 px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-xs font-bold text-gray-600 dark:text-slate-300">{{ number_format($totalCount) }}</span>
            </button>
            <button type="button" role="tab" :aria-selected="tab === 'add'" @click="openAdd()"
                class="flex-1 sm:flex-none sm:px-6 py-3 text-xs sm:text-sm sm:text-base font-semibold flex items-center justify-center gap-1.5 sm:gap-2 transition border-b-2 -mb-px min-w-0"
                :class="tab === 'add' ? 'border-violet-600 text-violet-700 dark:text-violet-300 bg-white dark:bg-slate-800' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-800'">
                <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center text-xs sm:text-sm font-bold shrink-0">+</span>
                <span class="truncate">{{ __('messages.supplier_add_title') }}</span>
            </button>
        </div>

        {{-- Toolbar — list tab only (Add tab keeps the page focused on the form) --}}
        <div x-show="tab === 'list'" x-cloak x-transition.opacity.duration.150ms class="p-2.5 sm:p-3 sm:pb-0">
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

        {{-- Suppliers list tab panel --}}
        <div x-show="tab === 'list'" x-cloak x-transition
            x-init="@if ($highlightSupplier) $nextTick(() => { const v = localStorage.getItem('admin_view_mode') || 'table'; const el = document.getElementById(v === 'card' ? 'supplier-card-{{ $highlightSupplier }}' : 'supplier-row-{{ $highlightSupplier }}'); el?.scrollIntoView({ behavior: 'smooth', block: 'center' }); }) @endif">

            {{-- ===== View: Table ===== --}}
            <div x-show="viewMode === 'table'" x-cloak class="overflow-x-auto scrollbar-thin">
                <table class="w-full min-w-[640px] text-left text-sm text-gray-600 dark:text-slate-300">
                    <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-semibold text-gray-700 dark:text-slate-200">
                        <tr>
                            <th class="p-3">{{ __('messages.supplier_col_name') }}</th>
                            <th class="p-3">{{ __('messages.supplier_col_phone') }}</th>
                            <th class="p-3 hidden md:table-cell">{{ __('messages.supplier_col_contact') }}</th>
                            <th class="p-3 text-center">PO</th>
                            <th class="p-3 text-right">{{ __('messages.supplier_col_outstanding') }}</th>
                            <th class="p-3 text-right">{{ __('messages.supplier_col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse ($suppliers as $supplier)
                            <tr id="supplier-row-{{ $supplier->id }}"
                                class="hover:bg-gray-50/60 dark:hover:bg-slate-700/40 transition {{ $highlightSupplier && (int) $highlightSupplier === (int) $supplier->id ? 'bg-violet-50/60 dark:bg-violet-950/20 ring-2 ring-violet-400/70' : '' }}">
                                <td class="p-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-orange-400 to-amber-500 text-white grid place-items-center font-black text-sm select-none"
                                             title="{{ $supplier->name }}">{{ mb_strtoupper(mb_substr(trim($supplier->name), 0, 1)) }}</div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-gray-900 dark:text-slate-100 break-words">{{ $supplier->name }}</div>
                                            @if ($supplier->email)
                                                <div class="text-xs text-gray-400 dark:text-slate-500 mt-0.5 truncate">{{ $supplier->email }}</div>
                                            @endif
                                            @if ($highlightSupplier && (int) $highlightSupplier === (int) $supplier->id)
                                                <span class="inline-block mt-1 px-1.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 text-[11px] font-bold">NEW</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3 whitespace-nowrap">
                                    @if ($supplier->phone)
                                        <a href="tel:{{ preg_replace('/\s+/', '', $supplier->phone) }}"
                                           class="font-mono text-xs font-semibold text-violet-600 dark:text-violet-400 hover:underline">{{ $supplier->phone }}</a>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-slate-500">—</span>
                                    @endif
                                    @if ($supplier->contact_person)
                                        <div class="md:hidden text-[11px] text-gray-400 dark:text-slate-500 truncate max-w-28">{{ $supplier->contact_person }}</div>
                                    @endif
                                </td>
                                <td class="p-3 hidden md:table-cell">
                                    <span class="text-xs">{{ $supplier->contact_person ?? '—' }}</span>
                                </td>
                                <td class="p-3 text-center">
                                    @if ($supplier->purchase_orders_count > 0)
                                        <span class="inline-block px-2 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-xs font-bold"
                                              title="{{ $supplier->purchase_orders_count }} PO">{{ $supplier->purchase_orders_count }}</span>
                                    @else
                                        <span class="text-xs text-gray-300 dark:text-slate-600">0</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right whitespace-nowrap">
                                    @if ($supplier->has_outstanding_balance)
                                        <span class="inline-block px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-xs font-bold">
                                            Ks {{ number_format($supplier->remaining_balance, 0) }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                                        <a href="{{ url('/store/' . $store->slug . '/admin/suppliers/' . $supplier->id . '/edit') }}"
                                            class="min-h-11 inline-flex items-center gap-1 px-3 rounded-lg text-xs font-semibold text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition"
                                            title="{{ __('messages.edit') }}">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                                            <span class="hidden sm:inline">{{ __('messages.edit') }}</span>
                                        </a>
                                        @if ($supplier->purchase_orders_count > 0)
                                            <span class="min-h-11 inline-flex items-center gap-1.5 px-2.5 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 text-xs"
                                                title="{{ __('messages.supplier_delete_blocked', ['count' => $supplier->purchase_orders_count]) }}">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/></svg>
                                                <span class="hidden lg:inline">{{ __('messages.supplier_used_by', ['count' => $supplier->purchase_orders_count]) }}</span>
                                            </span>
                                        @else
                                            <button type="button" data-id="{{ $supplier->id }}" data-name="{{ $supplier->name }}"
                                                @click="openConfirm($el)"
                                                class="min-h-11 inline-flex items-center gap-1 px-3 rounded-lg text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition"
                                                aria-label="{{ __('messages.supplier_delete_title') }}">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.9 12.1A2 2 0 0 1 16.1 21H7.9a2 2 0 0 1-2-1.9L5 7m5-4h4a1 1 0 0 1 1 1v2H9V4a1 1 0 0 1 1-1ZM4 7h16"/></svg>
                                                <span class="hidden sm:inline">{{ __('messages.delete') }}</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-10 text-center text-gray-400 dark:text-slate-500">
                                    <div class="mx-auto w-14 h-14 rounded-2xl bg-orange-50 dark:bg-orange-950/40 grid place-items-center text-2xl mb-3">🏭</div>
                                    <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">{{ __('messages.supplier_none') }}</div>
                                    <button type="button" @click="openAdd()"
                                        class="mt-3 inline-flex items-center gap-1.5 min-h-11 px-4 py-2 rounded-xl text-xs font-bold text-white bg-violet-600 hover:bg-violet-700 transition">
                                        + {{ __('messages.supplier_add_title') }}
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ===== View: Card grid ===== --}}
            <div x-show="viewMode === 'card'" x-cloak class="p-3 sm:p-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-3">
                @forelse ($suppliers as $supplier)
                    <div id="supplier-card-{{ $supplier->id }}" data-supplier-row="{{ $supplier->id }}"
                        class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden transition-colors duration-200 hover:shadow-md {{ $highlightSupplier && (int) $highlightSupplier === (int) $supplier->id ? 'ring-2 ring-violet-400/70' : '' }}">
                        {{-- Card body --}}
                        <div class="p-3 sm:p-4">
                            <div class="flex items-start gap-2.5">
                                <div class="shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-amber-500 text-white grid place-items-center font-black text-base select-none">{{ mb_strtoupper(mb_substr(trim($supplier->name), 0, 1)) }}</div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-gray-900 dark:text-slate-100 text-sm break-words line-clamp-2" title="{{ $supplier->name }}">{{ $supplier->name }}</div>
                                    @if ($supplier->phone)
                                        <a href="tel:{{ preg_replace('/\s+/', '', $supplier->phone) }}"
                                           class="block text-[11px] font-mono font-semibold text-violet-600 dark:text-violet-400 hover:underline mt-0.5">{{ $supplier->phone }}</a>
                                    @endif
                                </div>
                            </div>
                            @if ($supplier->email)
                                <div class="text-[11px] text-gray-400 dark:text-slate-500 truncate mt-2">{{ $supplier->email }}</div>
                            @endif
                            @if ($supplier->contact_person)
                                <div class="text-[11px] text-gray-400 dark:text-slate-500 truncate">{{ $supplier->contact_person }}</div>
                            @endif
                            <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                                @if ($supplier->has_outstanding_balance)
                                    <span class="inline-block px-1.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-[11px] font-bold">
                                        Ks {{ number_format($supplier->remaining_balance, 0) }}
                                    </span>
                                @endif
                                @if ($supplier->purchase_orders_count > 0)
                                    <span class="inline-block px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-[11px] font-semibold">
                                        {{ $supplier->purchase_orders_count }} PO
                                    </span>
                                @endif
                                @if ($highlightSupplier && (int) $highlightSupplier === (int) $supplier->id)
                                    <span class="inline-block px-1.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 text-[11px] font-bold">NEW</span>
                                @endif
                            </div>
                        </div>
                        {{-- Card action row --}}
                        <div class="flex items-center gap-2 px-3 sm:px-4 py-2.5 border-t border-gray-100 dark:border-slate-700/60 bg-gray-50/50 dark:bg-slate-900/30">
                            <a href="{{ url('/store/' . $store->slug . '/admin/suppliers/' . $supplier->id . '/edit') }}"
                                class="min-h-11 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                                {{ __('messages.edit') }}
                            </a>
                            @if ($supplier->purchase_orders_count > 0)
                                <span class="ml-auto inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 text-xs"
                                    title="{{ __('messages.supplier_delete_blocked', ['count' => $supplier->purchase_orders_count]) }}">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/></svg>
                                    <span>{{ $supplier->purchase_orders_count }} PO</span>
                                </span>
                            @else
                                <button type="button" data-id="{{ $supplier->id }}" data-name="{{ $supplier->name }}"
                                    @click="openConfirm($el)"
                                    class="min-h-11 ml-auto inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition"
                                    aria-label="{{ __('messages.supplier_delete_title') }}">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.9 12.1A2 2 0 0 1 16.1 21H7.9a2 2 0 0 1-2-1.9L5 7m5-4h4a1 1 0 0 1 1 1v2H9V4a1 1 0 0 1 1-1ZM4 7h16"/></svg>
                                    {{ __('messages.delete') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white dark:bg-slate-800 p-10 rounded-xl text-center text-gray-500 dark:text-slate-400">
                        <div class="mx-auto w-14 h-14 rounded-2xl bg-orange-50 dark:bg-orange-950/40 grid place-items-center text-2xl mb-3">🏭</div>
                        <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">{{ __('messages.supplier_none') }}</div>
                        <button type="button" @click="openAdd()"
                            class="mt-3 inline-flex items-center gap-1.5 min-h-11 px-4 py-2 rounded-xl text-xs font-bold text-white bg-violet-600 hover:bg-violet-700 transition">
                            + {{ __('messages.supplier_add_title') }}
                        </button>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if ($suppliers->hasPages())
                <div class="p-4 border-t dark:border-slate-700 text-sm">{{ $suppliers->links() }}</div>
            @endif
        </div>

        {{-- Add Supplier tab panel --}}
        <div x-show="tab === 'add'" x-cloak x-transition>
            <form x-ref="createForm" method="POST" action="{{ url('/store/' . $store->slug . '/admin/suppliers') }}"
                @submit="if (saving) { $event.preventDefault(); } else { saving = true; }"
                class="p-4 sm:p-5 space-y-4">
                @csrf

                <div>
                    <label for="add-supplier-name" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_name') }} <span class="text-rose-500">*</span></label>
                    <input id="add-supplier-name" x-ref="addName" type="text" name="name" value="{{ old('name') }}"
                        placeholder="{{ __('messages.supplier_name_placeholder') }}"
                        class="w-full border dark:border-slate-600 rounded-xl px-3 py-2.5 min-h-11 text-base sm:text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition {{ $errors->has('name') ? 'border-red-400 dark:border-red-500' : '' }}" />
                    @error('name')
                        <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="add-supplier-phone" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_phone') }}</label>
                        <input id="add-supplier-phone" type="tel" name="phone" value="{{ old('phone') }}"
                            placeholder="{{ __('messages.supplier_phone_placeholder') }}"
                            class="w-full border dark:border-slate-600 rounded-xl px-3 py-2.5 min-h-11 text-base sm:text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
                    </div>
                    <div>
                        <label for="add-supplier-email" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_email') }}</label>
                        <input id="add-supplier-email" type="email" name="email" value="{{ old('email') }}"
                            placeholder="{{ __('messages.supplier_email_placeholder') }}"
                            class="w-full border dark:border-slate-600 rounded-xl px-3 py-2.5 min-h-11 text-base sm:text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="add-supplier-contact" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_contact_person') }}</label>
                        <input id="add-supplier-contact" type="text" name="contact_person" value="{{ old('contact_person') }}"
                            placeholder="{{ __('messages.supplier_contact_placeholder') }}"
                            class="w-full border dark:border-slate-600 rounded-xl px-3 py-2.5 min-h-11 text-base sm:text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
                    </div>
                    <div>
                        <label for="add-supplier-address" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_address') }}</label>
                        <input id="add-supplier-address" type="text" name="address" value="{{ old('address') }}"
                            placeholder="{{ __('messages.supplier_address_placeholder') }}"
                            class="w-full border dark:border-slate-600 rounded-xl px-3 py-2.5 min-h-11 text-base sm:text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
                    </div>
                </div>

                <div>
                    <label for="add-supplier-notes" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_notes') }}</label>
                    <textarea id="add-supplier-notes" name="notes" rows="2"
                        placeholder="{{ __('messages.supplier_notes_placeholder') }}"
                        class="w-full border dark:border-slate-600 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition resize-none">{{ old('notes') }}</textarea>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <button type="submit" :disabled="saving"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 min-h-11 px-5 py-2.5 bg-violet-600 text-white rounded-xl hover:bg-violet-700 disabled:opacity-60 disabled:cursor-not-allowed font-semibold text-sm shadow transition">
                        <span x-show="!saving" class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('messages.supplier_save') }}
                        </span>
                        <span x-show="saving" class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                            {{ __('messages.supplier_save') }}
                        </span>
                    </button>
                    <button type="button" @click="tab = 'list'"
                        class="w-full sm:w-auto inline-flex items-center justify-center min-h-11 px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 transition">
                        {{ __('messages.cancel') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Delete confirmation modal (accessible: focus trap, Escape, backdrop, focus return) --}}
        <div x-show="confirmTarget" x-cloak x-transition.opacity.duration.150ms class="fixed inset-0 z-50" role="dialog" aria-modal="true"
            aria-labelledby="supplier-delete-title">
        <div class="fixed inset-0 bg-black/40" @click="closeConfirm()" aria-hidden="true"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div x-ref="confirmPanel" @keydown.tab.prevent="trapFocus($event)" @click.stop
                class="pointer-events-auto w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 p-5 shadow-xl border border-gray-200 dark:border-slate-700">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-950/50 text-red-600 dark:text-red-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 id="supplier-delete-title" class="text-base font-bold text-gray-900 dark:text-slate-100">{{ __('messages.supplier_delete_title') }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-slate-300 break-words font-medium">{{ __('messages.supplier_delete_confirm') }} <strong x-text="confirmTarget ? confirmTarget.name : ''"></strong>?</p>
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" x-ref="confirmCancel" @click="closeConfirm()"
                        class="min-h-11 px-4 py-2 rounded-xl text-sm font-semibold text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <form x-ref="deleteForm" method="POST"
                        :action="confirmTarget ? url('/store/' + '{{ $store->slug }}' + '/admin/suppliers/' + confirmTarget.id) : ''"
                        class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" @click="submitDelete()" :disabled="deleting"
                            class="min-h-11 inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-red-600 hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed shadow transition">
                            <span x-show="!deleting" class="inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.9 12.1A2 2 0 0 1 16.1 21H7.9a2 2 0 0 1-2-1.9L5 7m5-4h4a1 1 0 0 1 1 1v2H9V4a1 1 0 0 1 1-1ZM4 7h16"/></svg>
                                {{ __('messages.delete') }}
                            </span>
                            <span x-show="deleting" class="inline-flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                {{ __('messages.supplier_deleting') }}
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>
