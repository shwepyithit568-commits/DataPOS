@extends('layouts.pos.app')

@section('content')
    @php
        /** @var array<int, array{customer_id: int, name: string, phone: string|null, balance: string|float, last_activity: string|null}> $outstanding */
        /** @var string|float $outstandingTotal */

        $posLabels = [
            'added' => __('messages.pos_item_added'),
            'removed' => __('messages.pos_item_removed'),
            'pos_item_removed' => __('messages.pos_item_removed'),
            'remove_item' => __('messages.pos_remove_item'),
            'cleared' => __('messages.pos_cart_cleared'),
            'held' => __('messages.sale_held'),
            'shift_required' => __('messages.pos_shift_required'),
            'select_variant' => __('messages.pos_select_variant'),
            'variant' => __('messages.pos_variant'),
            'add_to_cart' => __('messages.pos_add_to_cart'),
            'out_of_stock' => __('messages.out_of_stock'),
            'in_stock' => __('messages.in_stock'),
            'low_stock' => __('messages.low_stock'),
            'no_products' => __('messages.pos_no_products'),
            'clear_cart' => __('messages.pos_clear_cart'),
            'confirm_clear_cart' => __('messages.pos_cart_clear_confirm'),
            'confirm_void_sale' => __('messages.confirm_void_sale'),
            'pos_cart_empty' => __('messages.pos_cart_empty'),
            'no_held_sales' => __('messages.pos_no_held_sales'),
            'cart' => __('messages.cart'),
            'items' => __('messages.items'),
            'resumed' => __('messages.sale_resumed'),
            'voided' => __('messages.sale_voided'),
            'held_since' => __('messages.held_since'),
            'holds_expired' => __('messages.holds_expired'),
            'oldest_hold' => __('messages.oldest_hold'),
            'soon_to_expire' => __('messages.soon_to_expire'),
            'expiry_off' => __('messages.expiry_off'),
            'pos_customer_added' => __('messages.pos_customer_added'),
            'pos_customer_invalid_phone' => __('messages.pos_customer_invalid_phone'),
            'pos_customer_staff_phone' => __('messages.pos_customer_staff_phone'),
            'pos_customer_not_found_add' => __('messages.pos_customer_not_found_add'),
            'pos_customer_attached' => __('messages.pos_customer_attached'),
            'pos_customer_detached' => __('messages.pos_customer_detached'),
            'pos_customer_saving' => __('messages.pos_customer_saving'),
            'pos_price_edit' => __('messages.pos_price_edit'),
            'pos_price_invalid' => __('messages.pos_price_invalid'),
            'pos_price_set' => __('messages.pos_price_set'),
            'pos_price_cleared' => __('messages.pos_price_cleared'),
            'pos_price_pin_required' => __('messages.pos_price_pin_required'),
            'pos_price_pin_invalid' => __('messages.pos_price_pin_invalid'),
            'pos_price_pin_label' => __('messages.pos_price_pin_label'),
            'web_order_imported' => __('messages.web_order_imported'),
            'expense_created_success' => __('messages.expense_created_success'),
            'pos_expense_saving' => __('messages.pos_expense_saving'),
            'pos_discount_applied' => __('messages.pos_discount_applied'),
            'pos_discount_cleared' => __('messages.pos_discount_cleared'),
            'pos_discount_pct_exceeded' => __('messages.pos_discount_pct_exceeded'),
            'credit_requires_customer' => __('messages.credit_requires_customer'),
            'pos_reload' => __('messages.pos_reload'),
            'pos_reloaded' => __('messages.pos_reloaded'),
            'pos_camera_scanner_title' => __('messages.pos_camera_scanner_title'),
            'pos_align_barcode_hint' => __('messages.pos_align_barcode_hint'),
            'pos_continuous_scan' => __('messages.pos_continuous_scan'),
            'pos_camera_retry' => __('messages.pos_camera_retry'),
            'pos_barcode_not_found' => __('messages.pos_barcode_not_found'),
            'pos_camera_permission_denied' => __('messages.pos_camera_permission_denied'),
            'pos_camera_insecure_title' => __('messages.pos_camera_insecure_title'),
            'pos_camera_insecure_http' => __('messages.pos_camera_insecure_http'),
            'pos_camera_switch' => __('messages.pos_camera_switch'),
            'pos_torch_toggle' => __('messages.pos_torch_toggle'),
        ];

        // Module links row — authentic 3D tactile color-coded high-frequency buttons with inline SVG icons.
        $moduleLinks = [];

        if ($store->hasCapability(\App\Capabilities\Capability::SERVICE_REPAIR_JOBS)) {
            $moduleLinks[] = [
                'path' => 'admin/service-jobs/create',
                'label' => 'pos_mod_services',
                'btn_3d' => 'sf-btn-3d-orange',
                'icon' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
                'is_action' => false,
                'target_blank' => true,
            ];
        }

        $moduleLinks = array_merge($moduleLinks, [
            [
                'path' => 'admin/expenses',
                'label' => 'pos_mod_expenses',
                'btn_3d' => 'sf-btn-3d-gold',
                'icon' => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>',
                'is_action' => 'expense',
            ],
            [
                'path' => 'pos/reports/sales',
                'label' => 'pos_mod_sales',
                'btn_3d' => 'sf-btn-3d-success',
                'icon' => '<path d="M18 20V10M12 20V4M6 20v-6"/>',
                'is_action' => false,
            ],
            [
                'path' => 'pos/returns',
                'label' => 'pos_mod_returns',
                'btn_3d' => 'sf-btn-3d-danger',
                'icon' => '<path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/>',
                'is_action' => false,
            ],
            [
                'path' => 'admin/customers',
                'label' => 'pos_mod_customers',
                'btn_3d' => 'sf-btn-3d-primary',
                'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
                'is_action' => false,
            ],
            [
                'path' => 'pos/reports/cash',
                'label' => 'pos_mod_cash',
                'btn_3d' => 'sf-btn-3d-teal',
                'icon' => '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>',
                'is_action' => false,
            ],
            [
                'path' => 'pos/reports/stock',
                'label' => 'pos_mod_stock',
                'btn_3d' => 'sf-btn-3d-accent',
                'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
                'is_action' => false,
            ],
            [
                'path' => 'admin/products',
                'label' => 'pos_mod_products',
                'btn_3d' => 'sf-btn-3d-facebook',
                'icon' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>',
                'is_action' => false,
            ],
            [
                'path' => 'pos/purchases',
                'label' => 'pos_mod_purchases',
                'btn_3d' => 'sf-btn-3d-viber',
                'icon' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
                'is_action' => false,
            ],
            [
                'path' => 'pos/purchases/payables',
                'label' => 'pos_mod_payables',
                'btn_3d' => 'sf-btn-3d-fuchsia',
                'icon' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
                'is_action' => false,
            ],
            [
                'path' => 'admin/orders',
                'label' => 'pos_mod_orders',
                'btn_3d' => 'sf-btn-3d-telegram',
                'icon' => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
                'is_action' => false,
            ],
            [
                'path' => 'pos/opening-stock',
                'label' => 'pos_mod_opening_stock',
                'btn_3d' => 'sf-btn-3d-indigo',
                'icon' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><path d="M7 7h.01"/>',
                'is_action' => false,
            ],
        ]);
    @endphp

    <div class="space-y-1"
         x-data="posApp({
             baseUrl: '{{ url('/store/' . $store->slug . '/pos') }}',
             csrf: '{{ csrf_token() }}',
             labels: {{ \Illuminate\Support\Js::from($posLabels) }},
             shiftsEnabled: {{ $store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS) ? 'true' : 'false' }},
             staffPhone: '{{ auth()->user()?->phone ?? '' }}',
             staffName: '{{ addslashes(auth()->user()?->name ?? '') }}',
             mobileSearchOpen: false
         })"
         @pos:reload.window="reloadPos()"
         x-init="init()">

        {{-- Toast notice (AJAX feedback - highest top layer) --}}
        <div x-show="notice" x-cloak
             style="z-index: 99999 !important;"
             class="pos-toast-notice fixed top-6 sm:top-8 left-1/2 -translate-x-1/2 z-[99999] px-4 py-2.5 rounded-xl text-sm font-bold shadow-2xl border pointer-events-none max-w-[92vw] sm:max-w-md text-center transition-all duration-200"
             :class="noticeType === 'error' ? 'bg-rose-600 text-white border-rose-500 shadow-rose-950/50 ring-2 ring-rose-400/50' : (noticeType === 'warning' ? 'bg-amber-600 text-white border-amber-500 shadow-amber-950/50 ring-2 ring-amber-400/50' : 'bg-emerald-600 text-white border-emerald-500 shadow-emerald-950/50 ring-2 ring-emerald-400/50')"
             role="status">
            <div class="flex items-center justify-center gap-2">
                <span class="text-base" x-text="noticeType === 'error' ? '⚠️' : (noticeType === 'warning' ? '⚡' : '✓')"></span>
                <span x-text="notice"></span>
            </div>
        </div>

        {{-- ── Auxiliary Modals (Scanner, Quick Customer, Expense, Discount, Open Shift, Mobile Filter, Web Orders) ── --}}
        @include('pos.partials.modal-quick-tools')

        {{-- ── POS Reporting Modal (Today | Registers | Debt | Repairs) ────── --}}
        @include('pos.partials.modal-reporting')

        {{-- ── Top Toolbar (Sticky header: mobile search & tools | desktop shift & shortcuts) ── --}}
        <div class="sticky top-[64px] z-30">
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                
                {{-- Mobile toolbar (visible only on mobile/tablet < lg) --}}
                <div class="flex lg:hidden flex-nowrap items-center gap-2 px-4 py-3 overflow-x-auto whitespace-nowrap [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    {{-- Search Icon Trigger (Collapsed by default on mobile - Pink 3D) --}}
                    <button type="button"
                            x-show="!mobileSearchOpen && !q"
                            @click="mobileSearchOpen = true; $nextTick(() => { const el = document.getElementById('pos-mobile-search-input'); if (el) { el.focus(); el.select(); } })"
                            class="sf-btn-3d-pink shrink-0 w-11 h-11 rounded-xl transition grid place-items-center cursor-pointer text-white shadow-sm"
                            title="{{ __('messages.pos_search_placeholder') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </button>

                    {{-- Search Input Box (Expands when icon clicked or search active) --}}
                    <div x-show="mobileSearchOpen || q" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-x-2 scale-95"
                         x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                         class="relative flex-1 min-w-[200px] max-w-[280px] flex items-center gap-1.5">
                        <div class="relative flex-1">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-pink-500 dark:text-pink-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            </span>
                            <input id="pos-mobile-search-input"
                                   type="text"
                                   x-ref="mobileSearchInput"
                                   x-model="q"
                                   @input="onSearch()"
                                   @keydown.enter.prevent="loadGrid(true)"
                                   @keydown.escape.prevent="if (q) { q = ''; loadGrid(); } mobileSearchOpen = false;"
                                   placeholder="{{ __('messages.pos_search_placeholder') }}"
                                   class="w-full h-11 rounded-xl border border-pink-500/40 dark:border-pink-500/40 bg-slate-50 dark:bg-slate-800 pl-9 pr-8 text-xs font-bold focus:ring-2 focus:ring-pink-500 outline-none">
                            <button type="button" x-show="q" x-cloak @click="q = ''; loadGrid(); $refs.mobileSearchInput?.focus()"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-5 h-5 rounded-md bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-300 text-xs flex items-center justify-center font-bold hover:bg-slate-300">✕</button>
                        </div>
                        <button type="button" @click="if (q) { q = ''; loadGrid(); } mobileSearchOpen = false;"
                                class="sf-btn-3d-darkred shrink-0 w-11 h-11 rounded-xl text-white font-black transition flex items-center justify-center text-sm cursor-pointer shadow-sm"
                                title="{{ __('messages.close') }}">
                            ✕
                        </button>
                    </div>

                    {{-- Scan barcode --}}
                    <button type="button"
                            @click="openBarcodeScanner()"
                            class="sf-btn-3d-primary shrink-0 w-11 h-11 rounded-xl transition grid place-items-center cursor-pointer"
                            title="{{ __('messages.pos_scan_barcode') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/><path d="M7 8h10"/><path d="M7 16h10"/></svg>
                    </button>

                    @if ($store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS))
                    {{-- Shift status (Mobile) --}}
                    <button type="button" @click="if (shiftOpen) { switchTab('registers'); $nextTick(() => document.getElementById('pos-shift-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' })); } else { window.dispatchEvent(new CustomEvent('pos:open-register')); }"
                            class="shrink-0 inline-flex items-center justify-center min-h-11 px-3 gap-1.5 rounded-xl text-[11px] font-black uppercase tracking-wide cursor-pointer text-white shadow-xs"
                            :class="shiftOpen ? 'sf-btn-3d-success' : 'sf-btn-3d-gold'">
                        <span class="w-2 h-2 rounded-full shrink-0 bg-white" :class="shiftOpen ? 'animate-pulse' : ''"></span>
                        <span x-text="shiftOpen ? '{{ __('messages.pos_shift_active') }}' : '{{ __('messages.pos_shift_open_action') }}'"></span>
                    </button>

                    {{-- End shift --}}
                    <button type="button" x-show="shiftOpen" x-cloak
                            @click="switchTab('registers'); $nextTick(() => document.getElementById('pos-shift-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                            class="sf-btn-3d-gold shrink-0 w-11 h-11 rounded-xl transition grid place-items-center cursor-pointer"
                            title="{{ __('messages.pos_end_shift') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                    </button>
                    @endif

                    {{-- Mobile filters drawer button --}}
                    <button type="button" @click="window.dispatchEvent(new CustomEvent('pos:open-filters'))"
                            class="sf-btn-3d shrink-0 min-h-11 inline-flex items-center gap-1.5 px-3.5 rounded-xl text-xs font-bold transition cursor-pointer"
                            title="{{ __('messages.pos_filters') }}">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3Z"/></svg>
                        {{ __('messages.pos_filters') }}
                        <span x-show="categoryId > 0 || brandId > 0" x-cloak
                              class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-blue-600 text-white text-[10px] font-black"
                              x-text="(categoryId > 0 ? 1 : 0) + (brandId > 0 ? 1 : 0)"></span>
                    </button>

                    {{-- Mobile held sales --}}
                    <button type="button" @click="document.getElementById('pos-held-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                            class="sf-btn-3d-gold shrink-0 min-h-11 inline-flex items-center gap-1.5 px-3.5 rounded-xl text-xs font-bold transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/></svg>
                        {{ __('messages.held_sales') }} <span x-text="'(' + cart.held_count + ')'"></span>
                    </button>

                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 shrink-0"></div>

                    {{-- Reporting Modal Quick Triggers (Mobile) --}}
                    <button type="button" @click="openReportingModal('today')"
                            class="sf-btn-3d shrink-0 min-h-11 inline-flex items-center gap-1.5 px-3 rounded-xl text-xs font-bold transition cursor-pointer text-slate-700 dark:text-slate-200"
                            title="{{ __('messages.pos_tab_today') }}">
                        <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/></svg>
                        <span>{{ __('messages.pos_tab_today') }}</span>
                        @if ($todaySales->isNotEmpty())
                            <span class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-blue-600 text-white text-[10px] font-black">{{ $todaySales->count() }}</span>
                        @endif
                    </button>

                    @if ($store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS))
                    <button type="button" @click="openReportingModal('registers')"
                            class="sf-btn-3d shrink-0 min-h-11 inline-flex items-center gap-1.5 px-3 rounded-xl text-xs font-bold transition cursor-pointer text-slate-700 dark:text-slate-200"
                            title="{{ __('messages.pos_tab_registers') }}">
                        <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v5H4V5Zm2 5v9h12v-9M7 12h2m-2 4h2m5-4h3m-3 4h3"/></svg>
                        <span>{{ __('messages.pos_tab_registers') }}</span>
                    </button>
                    @endif

                    <button type="button" @click="openReportingModal('debt')"
                            class="sf-btn-3d shrink-0 min-h-11 inline-flex items-center gap-1.5 px-3 rounded-xl text-xs font-bold transition cursor-pointer text-slate-700 dark:text-slate-200"
                            title="{{ __('messages.pos_tab_debt') }}">
                        <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12H4L5 9Z"/></svg>
                        <span>{{ __('messages.pos_tab_debt') }}</span>
                        @if (!empty($outstanding) && count($outstanding) > 0)
                            <span class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-amber-500 text-white text-[10px] font-black">{{ count($outstanding) }}</span>
                        @endif
                    </button>

                    @if ($store->hasCapability(\App\Capabilities\Capability::SERVICE_REPAIR_JOBS))
                    <button type="button" @click="openReportingModal('repairs')"
                            class="sf-btn-3d shrink-0 min-h-11 inline-flex items-center gap-1.5 px-3 rounded-xl text-xs font-bold transition cursor-pointer text-slate-700 dark:text-slate-200"
                            title="{{ __('messages.pos_tab_repairs') }}">
                        <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        <span>{{ __('messages.pos_tab_repairs') }}</span>
                        @if (isset($activeRepairsCount) && $activeRepairsCount > 0)
                            <span class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-amber-500 text-white text-[10px] font-black">{{ $activeRepairsCount }}</span>
                        @endif
                    </button>
                    @endif
                </div>

                {{-- Desktop top toolbar (Shift status + End shift + Held sales + Daily closing + Keyboard shortcuts) --}}
                <div class="hidden lg:flex items-center justify-between gap-3 px-4 py-2.5 min-w-0">
                    <div class="flex items-center gap-2 shrink-0 min-w-0">
                        @if ($store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS))
                        {{-- Shift status pill --}}
                        <button type="button" @click="if (shiftOpen) { switchTab('registers'); $nextTick(() => document.getElementById('pos-shift-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' })); } else { window.dispatchEvent(new CustomEvent('pos:open-register')); }"
                                class="shrink-0 inline-flex items-center gap-2 h-9 px-3 rounded-xl text-xs font-black uppercase tracking-wide cursor-pointer whitespace-nowrap text-white shadow-xs"
                                :class="shiftOpen ? 'sf-btn-3d-success' : 'sf-btn-3d-gold'">
                            <span class="w-2 h-2 rounded-full shrink-0 bg-white" :class="shiftOpen ? 'animate-pulse' : ''"></span>
                            <span x-text="shiftOpen ? '{{ __('messages.pos_shift_active') }}' : '{{ __('messages.pos_shift_open_action') }}'"></span>
                        </button>

                        {{-- End shift button --}}
                        <button type="button" x-show="shiftOpen" x-cloak
                                @click="switchTab('registers'); $nextTick(() => document.getElementById('pos-shift-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                                class="sf-btn-3d-gold shrink-0 h-9 px-2.5 rounded-xl transition inline-flex items-center gap-1 text-xs font-bold cursor-pointer whitespace-nowrap"
                                title="{{ __('messages.pos_end_shift') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                            <span>{{ __('messages.pos_end_shift') }}</span>
                        </button>

                        <div class="h-5 w-px bg-slate-200 dark:bg-slate-700 shrink-0"></div>
                        @endif

                        {{-- Held sales toggle (ဆိုင်းငံ့ထားသော အရောင်းများ) --}}
                        <button type="button" id="pos-held-toggle"
                                class="sf-btn-3d-gold shrink-0 inline-flex items-center gap-1.5 h-9 px-3 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap"
                                @click="document.getElementById('pos-held-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/></svg>
                            <span>{{ __('messages.held_sales') }}</span>
                            <span class="px-1.5 py-0.2 rounded-md bg-amber-500/20 text-amber-800 dark:text-amber-200 text-[11px] font-black" x-text="cart.held_count"></span>
                        </button>

                        {{-- နေ့စဉ် အရောင်းပိတ် (Daily closing) --}}
                        <a href="{{ url('/store/' . $store->slug . '/pos/closing') }}"
                           class="sf-btn-3d-danger shrink-0 inline-flex items-center gap-1.5 h-9 px-3 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap text-white shadow-xs"
                           title="{{ __('messages.closing_title') }}">
                            <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4"/>
                            </svg>
                            <span>{{ __('messages.pos_tab_closing') }}</span>
                        </a>

                        <div class="h-5 w-px bg-slate-200 dark:bg-slate-700 shrink-0"></div>

                        {{-- Reporting Modal Quick Triggers (Desktop) --}}
                        <button type="button" @click="openReportingModal('today')"
                                class="sf-btn-3d shrink-0 inline-flex items-center gap-1.5 h-9 px-3 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap text-slate-700 dark:text-slate-200"
                                title="{{ __('messages.pos_tab_today') }}">
                            <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/></svg>
                            <span>{{ __('messages.pos_tab_today') }}</span>
                            @if ($todaySales->isNotEmpty())
                                <span class="px-1.5 py-0.2 rounded-md bg-blue-500/20 text-blue-800 dark:text-blue-200 text-[11px] font-black">{{ $todaySales->count() }}</span>
                            @endif
                        </button>

                        @if ($store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS))
                        <button type="button" @click="openReportingModal('registers')"
                                class="sf-btn-3d shrink-0 inline-flex items-center gap-1.5 h-9 px-3 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap text-slate-700 dark:text-slate-200"
                                title="{{ __('messages.pos_tab_registers') }}">
                            <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v5H4V5Zm2 5v9h12v-9M7 12h2m-2 4h2m5-4h3m-3 4h3"/></svg>
                            <span>{{ __('messages.pos_tab_registers') }}</span>
                        </button>
                        @endif

                        <button type="button" @click="openReportingModal('debt')"
                                class="sf-btn-3d shrink-0 inline-flex items-center gap-1.5 h-9 px-3 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap text-slate-700 dark:text-slate-200"
                                title="{{ __('messages.pos_tab_debt') }}">
                            <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12H4L5 9Z"/></svg>
                            <span>{{ __('messages.pos_tab_debt') }}</span>
                            @if (!empty($outstanding) && count($outstanding) > 0)
                                <span class="px-1.5 py-0.2 rounded-md bg-amber-500/20 text-amber-800 dark:text-amber-200 text-[11px] font-black">{{ count($outstanding) }}</span>
                            @endif
                        </button>

                        @if ($store->hasCapability(\App\Capabilities\Capability::SERVICE_REPAIR_JOBS))
                        <button type="button" @click="openReportingModal('repairs')"
                                class="sf-btn-3d shrink-0 inline-flex items-center gap-1.5 h-9 px-3 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap text-slate-700 dark:text-slate-200"
                                title="{{ __('messages.pos_tab_repairs') }}">
                            <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                            <span>{{ __('messages.pos_tab_repairs') }}</span>
                            @if (isset($activeRepairsCount) && $activeRepairsCount > 0)
                                <span class="px-1.5 py-0.2 rounded-md bg-amber-500/20 text-amber-800 dark:text-amber-200 text-[11px] font-black">{{ $activeRepairsCount }}</span>
                            @endif
                        </button>
                        @endif
                    </div>

                    {{-- Keyboard shortcuts --}}
                    <div class="hidden xl:flex items-center gap-1 shrink-0">
                        @foreach ([
                            ['F1', 'pos_hint_search'], ['F2', 'pos_hint_checkout'], ['F3', 'pos_hint_customer'],
                            ['F4', 'pos_hint_clear'], ['F5', 'pos_hint_reload'], ['F6', 'pos_hint_hold'], ['F7', 'pos_hint_held'],
                        ] as [$key, $hint])
                            <span class="inline-flex items-center gap-1 px-1.5 py-1 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <span class="px-1.5 py-0.5 rounded-md bg-blue-600/10 text-blue-600 dark:text-blue-400 text-[9px] font-black leading-none">{{ $key }}</span>
                                <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ __('messages.' . $hint) }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Desktop only: Navigation & Filters (More Modules + Search + Barcode + Web Order + Categories & Brands Dropdowns) ── --}}
        @include('pos.partials.catalog-filters')

        {{-- ── Two-panel: product grid (left) + cart (right) ─────────────── --}}
        <div class="grid gap-1 lg:grid-cols-[minmax(0,1fr)_460px] xl:grid-cols-[minmax(0,1fr)_500px] 2xl:grid-cols-[minmax(0,1fr)_540px] items-start">
            {{-- LEFT: product grid --}}
            @include('pos.partials.catalog-panel')

            {{-- RIGHT: cart panel --}}
            @include('pos.partials.cart-panel')
        </div>

        {{-- ── Variant picker modal ─────────────────────────────────────── --}}
        @include('pos.partials.modal-variant-picker')

        {{-- ── Payment modal (multi-method split: cash / kpay / wave / credit) ── --}}
        @include('pos.partials.modal-payment')

        {{-- ── Held sales (client-rendered from cart-state so a hold/resume
               refreshes the list live — no page reload needed) ─────────── --}}
        <section id="pos-held-section" x-show="cart.held.length > 0" x-cloak
                 class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm scroll-mt-24">
            <div class="flex flex-wrap items-center gap-2 mb-3">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.held_sales') }}</p>
                <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-bold">
                    <span class="inline-flex items-center gap-1 rounded-md bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 px-2 py-0.5"
                          x-show="cart.expiry?.oldest_held_at">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span x-text="labels.oldest_hold.replace(':age', ageLabel(cart.expiry.oldest_held_at))"></span>
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-md bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 px-2 py-0.5"
                          x-show="cart.expiry?.soon_count > 0">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4m0 4h.01"/></svg>
                        <span x-text="labels.soon_to_expire.replace(':count', cart.expiry.soon_count)"></span>
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 px-2 py-0.5"
                          x-show="cart.expiry?.threshold_hours === 0">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span x-text="labels.expiry_off"></span>
                    </span>
                </div>
            </div>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <template x-for="h in cart.held" :key="h.id">
                    <div class="rounded-xl border border-amber-200 dark:border-amber-900 bg-amber-50/50 dark:bg-amber-950/30 p-3 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-amber-700 dark:text-amber-300" x-text="'#' + h.id + ' · ' + h.items_count + ' ' + (labels.items || labels.cart)"></p>
                            <p class="mt-1 inline-flex items-center gap-1 rounded-md bg-amber-200/70 dark:bg-amber-900/60 px-1.5 py-0.5 text-[10px] font-black text-amber-800 dark:text-amber-200">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                <span x-text="labels.held_since.replace(':time', h.held_at)"></span>
                            </p>
                        </div>
                        <div class="flex gap-1.5">
                            <button type="button" @click="resumeHeld(h.id)" :disabled="cartBusy"
                                    class="sf-btn-3d-gold text-xs font-black px-2.5 py-1.5 rounded-lg cursor-pointer transition">{{ __('messages.resume') }}</button>
                            <button type="button" @click="voidHeld(h.id)" :disabled="cartBusy"
                                    class="sf-btn-3d-danger text-xs font-black px-2.5 py-1.5 rounded-lg cursor-pointer transition">{{ __('messages.void_sale') }}</button>
                        </div>
                    </div>
                </template>
            </div>
        </section>

    </div>
@endsection
