@extends('layouts.pos.app')

@section('content')
    @php
        $posLabels = [
            'added' => __('messages.pos_item_added'),
            'held' => __('messages.sale_held'),
            'shift_required' => __('messages.pos_shift_required'),
            'select_variant' => __('messages.pos_select_variant'),
            'variant' => __('messages.pos_variant'),
            'add_to_cart' => __('messages.pos_add_to_cart'),
            'out_of_stock' => __('messages.pos_out_of_stock'),
            'in_stock' => __('messages.pos_in_stock'),
            'no_products' => __('messages.pos_no_products'),
            'clear_cart' => __('messages.pos_clear_cart'),
            'cart' => __('messages.cart'),
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
            'pos_price_edit' => __('messages.pos_price_edit'),
            'pos_price_invalid' => __('messages.pos_price_invalid'),
            'pos_price_set' => __('messages.pos_price_set'),
            'pos_price_cleared' => __('messages.pos_price_cleared'),
            'pos_price_pin_required' => __('messages.pos_price_pin_required'),
            'pos_price_pin_invalid' => __('messages.pos_price_pin_invalid'),
            'pos_price_pin_label' => __('messages.pos_price_pin_label'),
            'web_order_imported' => __('messages.web_order_imported'),
        ];

        // Module links row — high-frequency daily operation links with inline SVG icons.
        $moduleLinks = [
            ['pos/closing', 'closing_title', '<path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4"/>'],
            ['pos/reports/sales', 'reports_sales', '<path d="M18 20V10M12 20V4M6 20v-6"/>'],
            ['pos/reports/cash', 'reports_cash', '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>'],
            ['pos/reports/stock', 'reports_stock', '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>'],
            ['pos/purchases', 'receiving_subtitle', '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>'],
            ['pos/purchases/payables', 'balance_due', '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>'],
            ['admin/products', 'products', '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>'],
            ['admin/orders', 'manage_orders', '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>'],
            ['pos/adjustments', 'adjustment_title', '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.4 2.4-3-3 2.4-2.4Z"/>'],
            ['pos/reconciliation', 'reconciliation_title', '<path d="M12 3v18M5 7h14M7.5 7 5 11a2.5 2.5 0 0 0 5 0L7.5 7Zm9 0L14 11a2.5 2.5 0 0 0 5 0l-2.5-4ZM8.5 21h7"/>'],
            ['pos/opening-stock', 'opening_stock_title', '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><path d="M7 7h.01"/>'],
            ['admin/dashboard', 'admin_panel', '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
        ];
    @endphp

    <div class="space-y-5"
         x-data="posApp({
             baseUrl: '{{ url('/store/' . $store->slug . '/pos') }}',
             csrf: '{{ csrf_token() }}',
             labels: {{ \Illuminate\Support\Js::from($posLabels) }}
         })"
         x-init="init()">

        {{-- Toast notice (AJAX feedback) --}}
        <div x-show="notice" x-cloak
             class="fixed top-20 left-1/2 -translate-x-1/2 z-[90] px-4 py-2.5 rounded-xl text-sm font-bold shadow-xl border"
             :class="noticeType === 'error' ? 'bg-rose-600 text-white border-rose-500' : 'bg-emerald-600 text-white border-emerald-500'"
             role="status">
            <span x-text="notice"></span>
        </div>

        {{-- Quick-add customer modal — creates a store-scoped retail customer
             (shared users table + store_user pivot, phone dedup server-side). --}}
        <div x-show="quickAddOpen" x-cloak class="fixed inset-0 z-[95] grid place-items-center p-4"
             @keydown.escape.window="quickAddOpen = false">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="quickAddOpen = false"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-2xl space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-black inline-flex items-center gap-2">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        {{ __('messages.pos_customer_quick_add_title') }}
                    </p>
                    <button type="button" @click="quickAddOpen = false"
                            class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition">✕</button>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.pos_customer_name') }}</label>
                    <input type="text" x-model="qname" x-ref="quickName"
                           class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm font-semibold focus:ring-2 focus:ring-blue-500 outline-none"
                           placeholder="{{ __('messages.pos_customer_name') }}">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.pos_customer_phone') }}</label>
                    <input type="tel" x-model="qphone" @keydown.enter="quickAdd()"
                           class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm font-semibold focus:ring-2 focus:ring-blue-500 outline-none"
                           placeholder="09 123 456 789">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.pos_customer_type') }}</label>
                    <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 dark:bg-slate-800 p-1">
                        <button type="button" @click="qtype = 'retail_customer'"
                                :class="qtype === 'retail_customer' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow' : 'text-slate-500 dark:text-slate-400'"
                                class="rounded-lg px-3 py-2 text-sm font-bold transition">{{ __('messages.pos_customer_retail') }}</button>
                        <button type="button" @click="qtype = 'wholesale_customer'"
                                :class="qtype === 'wholesale_customer' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 shadow' : 'text-slate-500 dark:text-slate-400'"
                                class="rounded-lg px-3 py-2 text-sm font-bold transition">{{ __('messages.pos_customer_wholesale') }}</button>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400" x-show="qtype === 'wholesale_customer'" x-cloak>
                        <svg class="inline w-3 h-3 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><path d="M7 7h.01"/></svg>
                        {{ __('messages.pos_wholesale_type_hint') }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="quickAddOpen = false"
                            class="flex-1 rounded-xl px-4 py-2.5 text-sm font-black bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">{{ __('messages.cancel') }}</button>
                    <button type="button" @click="quickAdd()" :disabled="quickBusy || !qname.trim() || !qphone.trim()"
                            class="flex-1 rounded-xl px-4 py-2.5 text-sm font-black bg-blue-600 text-white hover:bg-blue-500 disabled:opacity-50 transition">+ {{ __('messages.pos_customer_add') }}</button>
                </div>
            </div>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-sm font-semibold">
                <svg class="inline w-4 h-4 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ session('success') }}
                @if (session('posted_receipt'))
                    <span class="block mt-1 text-xs font-mono">#{{ session('posted_receipt') }} · {{ __('messages.change') }}: Ks {{ number_format((float) session('posted_change')) }}</span>
                    @if (session('posted_debt'))
                        <span class="block mt-1 text-xs font-bold text-amber-600 dark:text-amber-400">
                            <svg class="inline w-3.5 h-3.5 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                            {{ __('messages.balance_due') }}: Ks {{ number_format((float) session('posted_debt')) }}
                        </span>
                    @endif
                    @if (session('posted_sale_id'))
                        <a href="{{ url('/store/' . $store->slug . '/pos/sales/' . session('posted_sale_id') . '/receipt') }}" target="_blank"
                           class="inline-flex items-center gap-1.5 mt-2 rounded-lg bg-emerald-600 text-white px-3 py-1.5 text-xs font-bold hover:bg-emerald-500 transition">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                            {{ __('messages.print_receipt') }}
                        </a>
                    @endif
                @endif
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-300 px-4 py-3 text-sm font-semibold">
                <svg class="inline w-4 h-4 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4m0 4h.01"/></svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- ── No open shift: an "open register" modal greets the cashier on
               entry and reappears on demand (toolbar shift pill). Once the
               shift is opened the page is completely clean. ─────────────── --}}
        @if (!$openShift)
            <div x-data="{ open: true }" @pos:open-register.window="open = true">
                <div x-show="open" x-cloak x-transition.opacity
                     class="fixed inset-0 z-[95] grid place-items-center p-4"
                     role="dialog" aria-modal="true" aria-labelledby="pos-open-register-title"
                     @keydown.escape.window="open = false">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="open = false"></div>
                    <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-2xl space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="shrink-0 w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 grid place-items-center">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <h2 id="pos-open-register-title" class="text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.pos_open_register') }}</h2>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ __('messages.pos_open_register_hint') }}</p>
                                </div>
                            </div>
                            <button type="button" @click="open = false" aria-label="{{ __('messages.close') }}"
                                    class="shrink-0 w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition">✕</button>
                        </div>

                        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/shifts') }}" class="grid gap-3">
                            @csrf
                            @if ($errors->has('shift'))
                                <div class="rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-300 px-3 py-2.5 text-sm font-semibold">
                                    {{ $errors->first('shift') }}
                                </div>
                            @endif
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.register_name') }}</label>
                                <input type="text" name="register_name" required maxlength="100" value="{{ old('register_name', auth()->user()?->name ?? '') }}"
                                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm"
                                       placeholder="{{ __('messages.register_name_placeholder') }}">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.opening_cash') }} (Ks)</label>
                                <input type="number" name="opening_cash" min="0" step="100" value="{{ old('opening_cash', 0) }}"
                                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm">
                            </div>
                            <button type="submit" class="rounded-xl px-4 py-3 text-sm font-black bg-blue-600 text-white hover:bg-blue-500 transition">{{ __('messages.open_shift') }}</button>
                        </form>

                        @if ($occupiedRegisters->isNotEmpty())
                            <div class="rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-300 px-3 py-2.5 text-xs">
                                <p class="font-bold mb-1.5 inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    {{ __('messages.registers_in_use') }}
                                </p>
                                <ul class="space-y-1">
                                    @foreach ($occupiedRegisters as $busy)
                                        <li>
                                            <span class="font-bold">{{ $busy->register_name }}</span> —
                                            {{ __('messages.register_occupied_by', ['cashier' => $busy->cashier?->name ?? '—', 'time' => $busy->opened_at?->format('H:i') ?? '—']) }}
                                            <span class="block opacity-80">
                                                {{ __('messages.register_drawer_state', ['opening' => number_format((float) $busy->opening_cash), 'sales' => number_format((float) $busy->cash_sales)]) }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="mt-1.5 opacity-80">{{ __('messages.pick_another_register') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Mobile filter & more bottom sheet (More | Category | Brand tabs) ── --}}
        <div class="lg:hidden" x-data="{ fsOpen: false, fsTab: 'category' }"
             @pos:open-filters.window="fsOpen = true; fsTab = 'category'"
             @pos:open-more.window="fsOpen = true; fsTab = 'more'">
            <div x-show="fsOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[85] bg-black/40 backdrop-blur-xs" @click="fsOpen = false"></div>
            <div x-show="fsOpen" x-cloak x-transition
                 class="fixed inset-x-0 bottom-0 z-[90] lg:hidden rounded-t-3xl bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 shadow-2xl max-h-[80vh] flex flex-col pb-[env(safe-area-inset-bottom)]">
                <div class="flex items-center justify-between gap-3 px-5 pt-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-black uppercase tracking-wide text-slate-700 dark:text-slate-200"
                        x-text="fsTab === 'more' ? '{{ __('messages.pos_more') }}' : '{{ __('messages.pos_filters') }}'"></h3>
                    <button type="button" @click="fsOpen = false" aria-label="{{ __('messages.close') }}"
                            class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition">✕</button>
                </div>

                {{-- Tabs: More | Categories | Brands --}}
                <div class="grid grid-cols-3 gap-1 px-5 pt-3">
                    <button type="button" @click="fsTab = 'more'"
                            class="min-h-11 rounded-xl px-2 py-2 text-xs font-black transition"
                            :class="fsTab === 'more' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/25' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'">
                        {{ __('messages.pos_more') }}
                    </button>
                    <button type="button" @click="fsTab = 'category'"
                            class="min-h-11 rounded-xl px-2 py-2 text-xs font-black transition"
                            :class="fsTab === 'category' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/25' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'">
                        {{ __('messages.categories') }}
                        <span x-show="categoryId > 0" class="inline-block w-1.5 h-1.5 rounded-full bg-amber-400 ml-1"></span>
                    </button>
                    <button type="button" @click="fsTab = 'brand'"
                            class="min-h-11 rounded-xl px-2 py-2 text-xs font-black transition"
                            :class="fsTab === 'brand' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/25' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'">
                        {{ __('messages.brands') }}
                        <span x-show="brandId > 0" class="inline-block w-1.5 h-1.5 rounded-full bg-amber-400 ml-1"></span>
                    </button>
                </div>

                <div class="px-5 py-3 overflow-y-auto">
                    {{-- More / Module options --}}
                    <div x-show="fsTab === 'more'" class="space-y-2">
                        <button type="button" @click="fsOpen = false; document.getElementById('pos-held-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                                class="w-full text-left min-h-12 rounded-xl px-4 py-3 text-sm font-bold border transition flex items-center justify-between gap-3 bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800">
                            <span class="inline-flex items-center gap-2">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/></svg>
                                {{ __('messages.held_sales') }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full bg-amber-500 text-white text-xs font-black" x-text="cart.held_count"></span>
                        </button>
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            @foreach ($moduleLinks as [$path, $label, $icon])
                                <a href="{{ url('/store/' . $store->slug . '/' . $path) }}"
                                   class="min-h-12 rounded-xl px-3 py-2.5 text-xs font-bold border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 text-slate-700 dark:text-slate-200 flex items-center gap-2 hover:border-blue-400 hover:text-blue-600 transition">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
                                    <span class="truncate">{{ __('messages.' . $label) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Category options --}}
                    <div x-show="fsTab === 'category'" class="space-y-1">
                        <button type="button" @click="toggleCategory(0); fsOpen = false"
                                class="w-full text-left min-h-11 rounded-xl px-3 py-2.5 text-sm font-bold border transition flex items-center justify-between gap-2"
                                :class="categoryId === 0 ? 'border-blue-600 text-blue-600 bg-blue-600/5' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200'">
                            <span>{{ __('messages.pos_all') }}</span>
                            <span x-show="categoryId === 0" class="text-blue-600 font-black">✓</span>
                        </button>
                        <template x-for="c in categories" :key="'fs-cat-' + c.id">
                            <button type="button" @click="toggleCategory(c.id); fsOpen = false"
                                    class="w-full text-left min-h-11 rounded-xl px-3 py-2.5 text-sm font-bold border transition flex items-center justify-between gap-2"
                                    :class="categoryId === c.id ? 'border-blue-600 text-blue-600 bg-blue-600/5' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200'">
                                <span x-text="c.name"></span>
                                <span x-show="categoryId === c.id" class="text-blue-600 font-black">✓</span>
                            </button>
                        </template>
                    </div>

                    {{-- Brand options --}}
                    <div x-show="fsTab === 'brand'" class="space-y-1">
                        <button type="button" @click="toggleBrand(0); fsOpen = false"
                                class="w-full text-left min-h-11 rounded-xl px-3 py-2.5 text-sm font-bold border transition flex items-center justify-between gap-2"
                                :class="brandId === 0 ? 'border-blue-600 text-blue-600 bg-blue-600/5' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200'">
                            <span>{{ __('messages.pos_all') }}</span>
                            <span x-show="brandId === 0" class="text-blue-600 font-black">✓</span>
                        </button>
                        <template x-for="b in brands" :key="'fs-brand-' + b.id">
                            <button type="button" @click="toggleBrand(b.id); fsOpen = false"
                                    class="w-full text-left min-h-11 rounded-xl px-3 py-2.5 text-sm font-bold border transition flex items-center justify-between gap-2"
                                    :class="brandId === b.id ? 'border-blue-600 text-blue-600 bg-blue-600/5' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200'">
                                <span x-text="b.name"></span>
                                <span x-show="brandId === b.id" class="text-blue-600 font-black">✓</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Web Orders modal (import an online order into the cart) ────── --}}
        <div x-show="webOrdersOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[95] grid place-items-center p-4"
             @keydown.escape.window="webOrdersOpen = false">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="webOrdersOpen = false"></div>
            <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col max-h-[85vh]">
                <div class="flex items-center justify-between gap-3 px-5 pt-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="min-w-0">
                        <h3 class="text-sm font-black">{{ __('messages.pos_web_orders') }}</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ __('messages.pos_web_orders_hint') }}</p>
                    </div>
                    <button type="button" @click="webOrdersOpen = false" aria-label="{{ __('messages.close') }}"
                            class="shrink-0 w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition">✕</button>
                </div>

                <div class="px-5 pt-3">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <input type="text" x-model="webOrderQ" @input.debounce.300ms="loadWebOrders()"
                               :placeholder="'{{ __('messages.pos_search_placeholder') }}'"
                               class="w-full h-11 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 pl-9 pr-3 text-sm font-semibold focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>

                <div class="px-5 py-3 overflow-y-auto space-y-2.5">
                    <p x-show="webOrderLoading" x-cloak class="text-sm text-slate-500 dark:text-slate-400 text-center py-6">{{ __('messages.loading') }}</p>
                    <template x-for="order in webOrderResults" :key="order.id">
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/30 p-3.5">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-mono text-xs font-black text-blue-600 dark:text-blue-400" x-text="order.order_number"></p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black"
                                      :class="order.status === 'confirmed' ? 'bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300' : 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300'"
                                      x-text="order.status_label"></span>
                            </div>
                            <p class="mt-1 text-sm font-bold text-slate-800 dark:text-slate-200" x-text="order.customer_name"></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400" x-text="order.customer_phone || ''"></p>
                            <div class="mt-2 space-y-0.5">
                                <template x-for="(item, i) in order.items" :key="i">
                                    <p class="text-xs text-slate-600 dark:text-slate-300 flex justify-between gap-2">
                                        <span class="min-w-0 truncate" x-text="'×' + item.quantity + '  ' + item.name"></span>
                                        <span class="shrink-0 font-mono tabular-nums" x-text="'Ks ' + Number(item.unit_price).toLocaleString()"></span>
                                    </p>
                                </template>
                            </div>
                            <div class="mt-2.5 flex items-center justify-between gap-2">
                                <p class="text-sm font-black text-slate-900 dark:text-slate-100 tabular-nums" x-text="'Ks ' + Number(order.total).toLocaleString()"></p>
                                <button type="button" @click="importWebOrder(order)"
                                        class="min-h-11 inline-flex items-center gap-1.5 px-3.5 rounded-xl text-xs font-black text-white bg-blue-600 hover:bg-blue-500 shadow-sm transition">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                                    {{ __('messages.web_order_load_cart') }}
                                </button>
                            </div>
                        </div>
                    </template>
                    <div x-show="!webOrderLoading && !webOrderResults.length" x-cloak
                         class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                        <svg class="inline w-4 h-4 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/><path d="M14 3H9v6h5z"/></svg>
                        {{ __('messages.no_web_orders') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Toolbar (3-zone responsive: sticky search | filters | more) ── --}}
        {{-- Zone A — sticky search bar (all screens: search stays reachable) --}}
        <div class="sticky top-[64px] z-30">
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                {{-- One strip on mobile (horizontal scroll), static row on desktop --}}
                <div class="flex flex-nowrap items-center gap-2 px-4 py-3 max-lg:overflow-x-auto max-lg:whitespace-nowrap [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    {{-- Search (barcode / SKU / name — F1) — anchored left, stays comfortable --}}
                    <div class="relative flex-1 min-w-[200px] sm:min-w-[220px] max-w-md max-lg:max-w-[240px]">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-blue-600 dark:text-blue-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </span>
                        <input type="text" x-ref="searchInput" x-model="q" @input="onSearch()" @keydown.enter.prevent="loadGrid()"
                               placeholder="{{ __('messages.pos_search_placeholder') }}"
                               class="w-full h-12 rounded-2xl border border-blue-600/20 dark:border-blue-500/20 bg-slate-50 dark:bg-slate-800 pl-11 pr-14 text-sm font-bold placeholder:font-semibold focus:ring-2 focus:ring-blue-500 outline-none">
                        <span class="hidden sm:inline absolute right-2.5 top-1/2 -translate-y-1/2 px-2 py-1 rounded-lg bg-blue-600/10 text-blue-600 dark:text-blue-400 text-[10px] font-black">F1</span>
                    </div>

                    {{-- Scan barcode (USB scanners type into search + Enter) --}}
                    <button type="button" @click="$refs.searchInput.focus()"
                            class="shrink-0 w-12 h-12 rounded-2xl bg-blue-600/10 text-blue-600 dark:text-blue-400 hover:bg-blue-600/20 transition grid place-items-center"
                            title="{{ __('messages.pos_scan_barcode') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/><path d="M7 8h10"/><path d="M7 16h10"/></svg>
                    </button>

                    {{-- Import web order (desktop/sm only — compact on mobile) --}}
                    <button type="button" @click="openWebOrders()"
                            class="hidden sm:grid shrink-0 w-12 h-12 rounded-2xl bg-blue-600/10 text-blue-600 dark:text-blue-400 hover:bg-blue-600/20 transition place-items-center"
                            title="{{ __('messages.pos_import_web_order') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                    </button>

                    {{-- Shift status (dot on mobile, pill on sm+) --}}
                    <button type="button" @click="if (shiftOpen) { switchTab('registers'); $nextTick(() => document.getElementById('pos-shift-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' })); } else { window.dispatchEvent(new CustomEvent('pos:open-register')); }"
                            class="shrink-0 inline-flex items-center justify-center min-h-11 w-11 sm:w-auto sm:px-3 gap-1.5 rounded-xl text-[11px] font-black uppercase tracking-wide border transition"
                            :class="shiftOpen ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30'">
                        <span class="w-2 h-2 rounded-full shrink-0" :class="shiftOpen ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'"></span>
                        <template x-if="shiftOpen"><span class="hidden sm:inline">{{ __('messages.pos_shift_active') }}</span></template>
                        <template x-if="!shiftOpen"><span class="hidden sm:inline">{{ __('messages.sale_requires_shift') }}</span></template>
                    </button>

                    {{-- End shift --}}
                    <button type="button" x-show="shiftOpen" x-cloak
                            @click="switchTab('registers'); $nextTick(() => document.getElementById('pos-shift-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                            class="shrink-0 w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition grid place-items-center"
                            title="{{ __('messages.pos_end_shift') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                    </button>

                    {{-- Mobile/tablet quick action buttons: Filters + Held Sales --}}
                    <button type="button" @click="window.dispatchEvent(new CustomEvent('pos:open-filters'))"
                            class="lg:hidden shrink-0 min-h-11 inline-flex items-center gap-1.5 px-3.5 rounded-xl border text-xs font-bold bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 transition"
                            title="{{ __('messages.pos_filters') }}">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3Z"/></svg>
                        {{ __('messages.pos_filters') }}
                        <span x-show="categoryId > 0 || brandId > 0" x-cloak
                              class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-blue-600 text-white text-[10px] font-black"
                              x-text="(categoryId > 0 ? 1 : 0) + (brandId > 0 ? 1 : 0)"></span>
                    </button>
                    <button type="button" @click="document.getElementById('pos-held-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                            class="lg:hidden shrink-0 min-h-11 inline-flex items-center gap-1.5 px-3.5 rounded-xl border text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800 transition">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/></svg>
                        {{ __('messages.held_sales') }} <span x-text="'(' + cart.held_count + ')'"></span>
                    </button>

                    {{-- Keyboard shortcuts (desktop only) --}}
                    <div class="hidden xl:flex items-center gap-1.5">
                        @foreach ([
                            ['F1', 'pos_hint_search'], ['F2', 'pos_hint_checkout'], ['F3', 'pos_hint_customer'],
                            ['F4', 'pos_hint_clear'], ['F5', 'pos_hint_reload'], ['F6', 'pos_hint_hold'], ['F7', 'pos_hint_held'],
                        ] as [$key, $hint])
                            <span class="inline-flex items-center gap-1.5 px-2 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <span class="px-1.5 py-0.5 rounded-md bg-blue-600/10 text-blue-600 dark:text-blue-400 text-[9px] font-black">{{ $key }}</span>
                                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">{{ __('messages.' . $hint) }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Desktop only: Navigation & Filters: More (Modules) + Categories + Brands ── --}}
        <div class="hidden lg:block rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

            {{-- Row 1: More / Quick Module Links (horizontal chip-scroll) --}}
            <x-pos.chip-scroll :label="__('messages.pos_more')" variant="chips" class="bg-slate-50/60 dark:bg-slate-800/30">
                <button type="button" id="pos-held-toggle"
                        class="shrink-0 snap-start inline-flex items-center gap-1.5 px-3 py-1.5 rounded-2xl text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-900 border border-amber-300 dark:border-amber-800 transition"
                        @click="document.getElementById('pos-held-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/></svg>
                    {{ __('messages.held_sales') }} <span x-text="'(' + cart.held_count + ')'"></span>
                </button>
                @foreach ($moduleLinks as [$path, $label, $icon])
                    <a href="{{ url('/store/' . $store->slug . '/' . $path) }}"
                       class="shrink-0 snap-start inline-flex items-center gap-1.5 px-3 py-1.5 rounded-2xl text-xs font-bold bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-blue-400 hover:text-blue-600 dark:hover:text-blue-400 shadow-sm transition">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
                        {{ __('messages.' . $label) }}
                    </a>
                @endforeach
            </x-pos.chip-scroll>

            {{-- Row 2: Category chips --}}
            <x-pos.chip-scroll :label="__('messages.categories')">
                <button type="button" @click="toggleCategory(0)"
                        class="shrink-0 snap-start px-3.5 py-1.5 rounded-2xl text-[13px] font-black border transition"
                        :class="categoryId === 0 ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-600/25 ring-2 ring-blue-600/30' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-blue-400'">
                    <span x-show="categoryId === 0" class="inline-block mr-0.5">✓</span>{{ __('messages.pos_all') }}
                </button>
                <template x-for="c in categories" :key="'cat-' + c.id">
                    <button type="button" @click="toggleCategory(c.id)"
                            class="shrink-0 snap-start px-3.5 py-1.5 rounded-2xl text-[13px] font-black border transition"
                            :class="categoryId === c.id ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-600/25 ring-2 ring-blue-600/30' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-blue-400'">
                        <span x-show="categoryId === c.id" class="inline-block mr-0.5">✓</span><span x-text="c.name"></span>
                    </button>
                </template>
            </x-pos.chip-scroll>

            {{-- Row 3: Brand chips --}}
            <x-pos.chip-scroll :label="__('messages.brands')" class="rounded-b-2xl">
                <button type="button" @click="toggleBrand(0)"
                        class="shrink-0 snap-start px-3 py-1 rounded-2xl text-xs font-bold border transition"
                        :class="brandId === 0 ? 'bg-blue-600 text-white border-blue-600 ring-2 ring-blue-600/30' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-blue-400'">
                    <span x-show="brandId === 0" class="inline-block mr-0.5">✓</span>{{ __('messages.pos_all') }}
                </button>
                <template x-for="b in brands" :key="'brand-' + b.id">
                    <button type="button" @click="toggleBrand(b.id)"
                            class="shrink-0 snap-start px-3 py-1 rounded-2xl text-xs font-bold border transition"
                            :class="brandId === b.id ? 'bg-blue-600 text-white border-blue-600 ring-2 ring-blue-600/30' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-blue-400'">
                        <span x-show="brandId === b.id" class="inline-block mr-0.5">✓</span><span x-text="b.name"></span>
                    </button>
                </template>
            </x-pos.chip-scroll>
        </div>

        {{-- ── Mini sales summary strip (always visible, no tab needed) ──── --}}
        @if ($todaySales->isNotEmpty())
        @php
            $todayTotal = $todaySales->sum(fn($s) => (float) $s->total);
            $todayCount = $todaySales->count();
            $topMethod = $todaySales->flatMap->payments->groupBy('method')->map->sum('amount')->sortDesc()->keys()->first() ?? 'cash';
        @endphp
        <div class="flex items-center gap-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-4 py-2.5 shadow-sm text-xs font-bold overflow-x-auto whitespace-nowrap [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <span class="text-slate-400 uppercase tracking-wide shrink-0">{{ now()->format('d M') }}</span>
            <span class="w-px h-4 bg-slate-200 dark:bg-slate-700 shrink-0"></span>
            <span class="inline-flex items-center gap-1.5 shrink-0">
                <svg class="w-3.5 h-3.5 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span class="text-slate-700 dark:text-slate-200">{{ $todayCount }} {{ __('messages.sales') }}</span>
            </span>
            <span class="w-px h-4 bg-slate-200 dark:bg-slate-700 shrink-0"></span>
            <span class="inline-flex items-center gap-1.5 shrink-0">
                <svg class="w-3.5 h-3.5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                <span class="text-emerald-700 dark:text-emerald-400 font-extrabold">Ks {{ number_format($todayTotal) }}</span>
            </span>
            <span class="w-px h-4 bg-slate-200 dark:bg-slate-700 shrink-0"></span>
            <span class="inline-flex items-center gap-1.5 shrink-0 text-slate-500 dark:text-slate-400">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 11H7M12 6v12"/></svg>
                {{ __('messages.payment_' . $topMethod) }}
            </span>
        </div>
        @endif

        {{-- ── Two-panel: product grid (left) + cart (right) ─────────────── --}}
        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_400px] items-start">

            {{-- LEFT: product grid --}}
            <section class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-sm min-w-0"
                     x-data="{ gridMode: localStorage.getItem('pos_grid_mode') || 'normal' }"
                     x-init="$watch('gridMode', v => localStorage.setItem('pos_grid_mode', v))">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.pos_products') }}</p>
                        <h2 class="text-lg font-black mt-0.5">{{ __('messages.scan_or_search') }}</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-400" x-show="gridLoading">…</span>
                        {{-- Grid / List mode switcher (visible on all screens including mobile) --}}
                        <div class="flex items-center gap-1 rounded-xl bg-slate-100 dark:bg-slate-800 p-1">
                            <button type="button" @click="gridMode = 'grid'"
                                    :class="gridMode !== 'list' ? 'bg-white dark:bg-slate-700 shadow text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-400'"
                                    class="w-8 h-8 rounded-lg transition grid place-items-center"
                                    aria-label="Grid View"
                                    title="Grid View (၂ ကော်လံပုံစံ)">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                            </button>
                            <button type="button" @click="gridMode = 'list'"
                                    :class="gridMode === 'list' ? 'bg-white dark:bg-slate-700 shadow text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-400'"
                                    class="w-8 h-8 rounded-lg transition grid place-items-center"
                                    aria-label="List View"
                                    title="List View (စာရင်းပုံစံ)">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Product cards (grid / list mode) --}}
                {{-- GRID MODE (Mobile: 2 columns, Tablet: 3 columns, Desktop: 4 columns) --}}
                <div x-show="gridMode !== 'list'"
                     class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3.5 max-h-[58vh] overflow-y-auto pr-1 pb-1">
                    <template x-for="p in products" :key="p.id">
                        <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden transition hover:shadow-lg hover:-translate-y-0.5 active:scale-[.98]"
                             :class="parseFloat(p.balance) > 0 ? '' : 'opacity-55'">
                            {{-- Image section --}}
                            <div class="relative m-2 aspect-[4/3] rounded-xl bg-slate-100 dark:bg-slate-900/70 grid place-items-center overflow-hidden">
                                <template x-if="p.image">
                                    <img :src="p.image" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-contain p-3">
                                </template>
                                <template x-if="!p.image">
                                    <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                </template>

                                {{-- Stock status badge (top-right) --}}
                                <span class="absolute top-2 right-2 px-2 py-0.5 rounded-md text-[9px] font-black text-white shadow-sm"
                                      :class="parseFloat(p.balance) <= 0 ? 'bg-rose-500' : (parseFloat(p.balance) <= 5 ? 'bg-amber-500' : 'bg-emerald-500')"
                                      x-text="parseFloat(p.balance) <= 0 ? labels.out_of_stock : (parseFloat(p.balance) <= 5 ? ('×' + p.balance) : labels.in_stock)"></span>

                                {{-- Variants badge (top-left) --}}
                                <span x-show="p.variants && p.variants.length > 0" x-cloak
                                      class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-blue-600 text-white text-[9px] font-black shadow-sm"
                                      x-text="'↕ ' + p.variants.length + ' ' + labels.variant"></span>

                                {{-- Category badge (bottom-left) --}}
                                <span x-show="p.category" x-cloak
                                      class="absolute bottom-2 left-2 px-2 py-0.5 rounded-md bg-white/90 dark:bg-slate-900/90 text-[9px] font-black uppercase tracking-wider text-slate-500 border border-slate-200 dark:border-slate-700 shadow-sm"
                                      x-text="p.category"></span>
                            </div>

                            {{-- Info section --}}
                            <div class="px-3 pb-3 pt-1">
                                <p class="text-sm font-bold leading-snug line-clamp-2 min-h-[2.5em]" x-text="p.name"></p>
                                <div class="mt-2 flex items-end justify-between gap-2">
                                    <div class="min-w-0">
                                        {{-- Retail/walk-in: show the sale (old) price struck through --}}
                                        <p class="text-[11px] text-rose-500 font-bold line-through" x-show="p.tier !== 'wholesale' && p.old_price && parseFloat(p.old_price) > parseFloat(p.price)" x-text="'Ks ' + Number(p.old_price).toLocaleString()"></p>
                                        {{-- Wholesale tier: strike the retail price the shopper is NOT paying --}}
                                        <p class="text-[11px] text-rose-500 font-bold line-through" x-show="p.tier === 'wholesale' && parseFloat(p.retail_price) > parseFloat(p.price)" x-text="'Ks ' + Number(p.retail_price).toLocaleString()"></p>
                                        <p class="text-base font-extrabold text-blue-600 dark:text-blue-400 leading-none" x-text="'Ks ' + Number(p.price).toLocaleString()"></p>
                                        <p class="text-[10px] font-black text-amber-600 dark:text-amber-400"
                                           x-show="p.tier === 'wholesale' && parseFloat(p.retail_price) > parseFloat(p.price)"
                                           x-text="'−Ks ' + (parseFloat(p.retail_price) - parseFloat(p.price)).toLocaleString()"></p>
                                    </div>
                                    <button type="button" @click="addProduct(p)" :disabled="parseFloat(p.balance) <= 0"
                                            class="shrink-0 w-10 h-10 rounded-xl bg-blue-600 text-white grid place-items-center shadow-lg shadow-blue-600/30 hover:bg-blue-500 active:scale-90 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none"
                                            :title="p.variants && p.variants.length > 0 ? labels.select_variant : labels.add_to_cart">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- LIST MODE --}}
                <div x-show="gridMode === 'list'" class="max-h-[58vh] overflow-y-auto space-y-1.5 pr-1 pb-1">
                    <template x-for="p in products" :key="'list-' + p.id">
                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 hover:border-blue-400 hover:shadow-md transition"
                             :class="parseFloat(p.balance) > 0 ? '' : 'opacity-50'">
                            {{-- Thumbnail --}}
                            <div class="shrink-0 w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-900/70 grid place-items-center overflow-hidden">
                                <template x-if="p.image">
                                    <img :src="p.image" alt="" loading="lazy" class="w-full h-full object-contain p-0.5">
                                </template>
                                <template x-if="!p.image">
                                    <svg class="w-5 h-5 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                </template>
                            </div>
                            {{-- Name + SKU --}}
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold truncate" x-text="p.name"></p>
                                <p class="text-[11px] text-slate-400 font-mono" x-show="p.sku" x-text="p.sku || ''"></p>
                            </div>
                            {{-- Stock badge --}}
                            <span class="shrink-0 px-2 py-0.5 rounded-md text-[10px] font-black text-white"
                                  :class="parseFloat(p.balance) <= 0 ? 'bg-rose-500' : (parseFloat(p.balance) <= 5 ? 'bg-amber-500' : 'bg-emerald-500')"
                                  x-text="parseFloat(p.balance) <= 0 ? labels.out_of_stock : '×' + p.balance"></span>
                            {{-- Price --}}
                            <p class="shrink-0 text-sm font-extrabold text-blue-600 dark:text-blue-400 tabular-nums" x-text="'Ks ' + Number(p.price).toLocaleString()"></p>
                            {{-- Add button --}}
                            <button type="button" @click="addProduct(p)" :disabled="parseFloat(p.balance) <= 0"
                                    class="shrink-0 w-10 h-10 rounded-xl bg-blue-600 text-white grid place-items-center shadow-lg shadow-blue-600/30 hover:bg-blue-500 active:scale-90 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Empty state --}}
                <div x-show="!gridLoading && !products.length"
                     class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-10 text-center text-sm text-slate-500 dark:text-slate-400">
                    <svg class="inline w-4 h-4 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <span x-text="labels.no_products"></span>
                </div>
            </section>

            {{-- RIGHT: cart panel (reference: pos_cart_panel.dart) --}}
            {{-- Desktop (lg+): sticky side column (2-pane with the grid). Mobile: the
                 cart becomes a bottom-sheet drawer that slides up when the floating
                 cart button below is tapped — both share the same posApp cart state. --}}
            <div x-data="{
                    mobileCartOpen: false,
                    dragY: 0,
                    touchStartY: 0,
                    dragging: false,
                    scrollBlocked: false,
                    prevY: 0,
                    prevT: 0,
                    vel: 0,
                    onTouchStart(e) {
                        if (!this.mobileCartOpen || window.innerWidth >= 1024) return;
                        this.dragging = true;
                        this.scrollBlocked = false;
                        this.dragY = 0;
                        this.prevY = this.prevT = 0;
                        this.vel = 0;
                        this.touchStartY = e.touches[0].clientY;
                        // Only drag the sheet when the touched scroll container is at its top,
                        // so the cart list / customer results still scroll normally.
                        let el = e.target;
                        while (el && el !== this.$refs.drawer) {
                            if (el.scrollTop > 0) { this.scrollBlocked = true; break; }
                            el = el.parentElement;
                        }
                    },
                    onTouchMove(e) {
                        if (!this.dragging || window.innerWidth >= 1024) return;
                        const now = performance.now();
                        const y = e.touches[0].clientY;
                        if (this.prevT && now > this.prevT) {
                            this.vel = (y - this.prevY) / (now - this.prevT);
                        }
                        this.prevY = y; this.prevT = now;
                        const dy = y - this.touchStartY;
                        if (dy <= 0 || this.scrollBlocked || this.$refs.drawer.scrollTop > 0) { this.dragY = 0; return; }
                        // Follow the finger (resistive: only 60% of the distance feels native).
                        this.dragY = dy;
                        this.$refs.drawer.style.transform = 'translateY(' + Math.min(dy * 0.6, 320) + 'px)';
                        e.preventDefault();
                    },
                    onTouchEnd() {
                        if (!this.dragging) return;
                        this.dragging = false;
                        if (this.$refs.drawer) this.$refs.drawer.style.transform = '';
                        const fling = this.dragY > 30 && this.vel > 0.6;
                        if (this.dragY > 90 || fling) this.mobileCartOpen = false;
                        this.dragY = 0;
                    },
                    onTouchCancel() {
                        this.dragging = false;
                        if (this.$refs.drawer) this.$refs.drawer.style.transform = '';
                        this.dragY = 0;
                    }
                }">

                {{-- Mobile backdrop: tap to close (bottom sheet convention) --}}
                <div x-show="mobileCartOpen" x-cloak @click="mobileCartOpen = false"
                     class="hidden max-lg:block fixed inset-0 z-50 bg-black/40"></div>

            <aside id="pos-cart-panel" x-ref="drawer"
                   @keydown.escape.window="mobileCartOpen = false"
                   @touchstart="onTouchStart($event)" @touchmove="onTouchMove($event)" @touchend="onTouchEnd()" @touchcancel="onTouchCancel()"
                   class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 lg:shadow-sm lg:sticky lg:top-20 max-lg:fixed max-lg:inset-x-3 max-lg:bottom-3 max-lg:z-50 max-lg:max-h-[85dvh] max-lg:overflow-y-auto max-lg:rounded-3xl max-lg:shadow-2xl"
                   :class="mobileCartOpen ? '' : 'max-lg:translate-y-[120%]'">

                {{-- Mobile drawer handle + header (bottom sheet only) --}}
                <div class="hidden max-lg:flex items-center justify-center pt-2.5 px-4">
                    <div class="w-10 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></div>
                </div>
                <div class="hidden max-lg:flex items-center justify-between gap-3 px-4 pb-3 pt-2 border-b border-slate-100 dark:border-slate-800">
                    <p class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.pos_cart_title') }}</p>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-full text-xs font-black bg-blue-600/10 text-blue-600 dark:text-blue-400" x-text="cart.lines.length + ' · Ks ' + Number(cart.totals.total).toLocaleString()"></span>
                        <button type="button" @click="mobileCartOpen = false" class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-300 font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition">✕</button>
                    </div>
                </div>

                {{-- Customer selector header --}}
                <div class="px-4 pt-4 pb-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 shrink-0 rounded-full bg-blue-600/10 text-blue-600 dark:text-blue-400 grid place-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-black truncate" x-text="customer ? customer.name : '{{ __('messages.walk_in_customer') }}'"></p>
                            <p class="text-xs text-slate-400" x-text="customer ? '{{ __('messages.customer') }}' : '{{ __('messages.select_customer') }}'"></p>
                        </div>
                        <button type="button" @click="openQuickAdd()"
                                class="shrink-0 w-10 h-10 rounded-xl bg-blue-600/10 text-blue-600 dark:text-blue-400 hover:bg-blue-600/20 transition grid place-items-center"
                                title="{{ __('messages.pos_quick_add_customer') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                        </button>
                    </div>

                    {{-- Customer search (F3) --}}
                    <div class="relative mt-3">
                        <input type="text" x-ref="customerInput" x-model="cq" @input.debounce.250ms="csearch()" :disabled="customer !== null"
                               placeholder="{{ __('messages.customer_search_placeholder') }}"
                               class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm font-semibold focus:ring-2 focus:ring-blue-500 outline-none disabled:opacity-50">
                        <div x-show="copen && cresults.length" x-cloak
                             class="absolute z-30 inset-x-0 top-full mt-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl max-h-56 overflow-y-auto">
                            <template x-for="c in cresults" :key="c.id">
                                <button type="button" @click="attach(c)"
                                        class="w-full text-left px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 last:border-0">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold truncate" x-text="c.name"></span>
                                        <span class="block text-[11px] text-slate-500 font-mono" x-text="c.phone || ''"></span>
                                    </span>
                                    <span class="shrink-0 flex items-center gap-1.5">
                                        <span class="text-[11px] font-bold px-1.5 py-0.5 rounded"
                                              :class="c.role === 'wholesale_customer' ? 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300' : 'bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300'"
                                              x-text="c.role === 'wholesale_customer' ? '{{ __('messages.pos_customer_wholesale') }}' : '{{ __('messages.pos_customer_retail') }}'"></span>
                                        <span class="text-[11px] font-bold" :class="parseFloat(c.balance) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400'"
                                              x-text="parseFloat(c.balance) > 0 ? '{{ __('messages.debt') }} ' + Number(c.balance).toLocaleString() : ''"></span>
                                    </span>
                                </button>
                            </template>
                        </div>
                        <div x-show="copen && cq.trim() !== '' && !cresults.length" x-cloak
                             class="absolute z-30 inset-x-0 top-full mt-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl px-3 py-2 text-sm">
                            <p class="text-slate-500">{{ __('messages.no_customers_found') }}</p>
                            <button type="button" @click="openQuickAdd(cq.trim())"
                                    class="mt-1 w-full text-left px-3 py-2 rounded-lg bg-blue-600/10 text-blue-600 dark:text-blue-400 hover:bg-blue-600/20 text-sm font-bold transition">
                                <span x-text="labels.pos_customer_not_found_add.replace(':name', cq.trim())"></span>
                            </button>
                        </div>
                    </div>

                    <template x-if="customer">
                        <div class="mt-2 flex items-center justify-between gap-2">
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold"
                                  :class="customer.role === 'wholesale_customer' ? 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300' : 'bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300'">
                                <svg class="inline w-3.5 h-3.5 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <span x-text="customer.name"></span>
                                <span x-show="customer.role === 'wholesale_customer'" x-text="' · ' + '{{ __('messages.pos_customer_wholesale') }}'"></span>
                                <span x-show="parseFloat(customer.balance) > 0" x-text="' · ' + '{{ __('messages.debt') }}' + ' ' + Number(customer.balance).toLocaleString()"></span>
                            </span>
                            <button type="button" @click="clearCustomer()" class="text-rose-500 hover:text-rose-700 font-black text-xs">✕ {{ __('messages.customer') }}</button>
                        </div>
                    </template>
                    <p x-show="credit > 0 && !customer" x-cloak class="mt-1.5 text-[11px] font-bold text-rose-600 dark:text-rose-400">
                        <svg class="inline w-3.5 h-3.5 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4m0 4h.01"/></svg>
                        {{ __('messages.credit_requires_customer') }}
                    </p>
                </div>

                {{-- Cart header (desktop only — mobile uses the drawer header) --}}
                <div class="hidden lg:flex items-center justify-between gap-3 px-4 py-3">
                    <p class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.pos_cart_title') }}</p>
                    <span class="px-2.5 py-1 rounded-full text-xs font-black bg-blue-600/10 text-blue-600 dark:text-blue-400" x-text="cart.lines.length"></span>
                </div>

                {{-- Cart lines --}}
                <div class="space-y-2.5 px-4 max-h-[38vh] overflow-y-auto pr-2">
                    <template x-for="line in cart.lines" :key="line.index">
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30 px-3 py-2.5 shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold leading-snug truncate" x-text="line.name"></p>
                                    {{-- Negotiated override: strike the tier price it replaced --}}
                                    <p class="text-[10px] text-rose-500 font-bold line-through mt-0.5" x-show="line.original_unit_price !== null && parseFloat(line.original_unit_price) > parseFloat(line.unit_price)" x-text="'Ks ' + Number(line.original_unit_price).toLocaleString()"></p>
                                    <p class="text-xs font-mono mt-0.5" :class="(line.original_unit_price !== null && parseFloat(line.original_unit_price) > parseFloat(line.unit_price)) || parseFloat(line.retail_unit_price) > parseFloat(line.unit_price) ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-400'" x-text="'Ks ' + Number(line.unit_price).toLocaleString()"></p>
                                    {{-- Override savings (amber) takes precedence over the wholesale comparison --}}
                                    <p class="text-[10px] font-black text-amber-600 dark:text-amber-400" x-show="line.original_unit_price !== null && parseFloat(line.original_unit_price) > parseFloat(line.unit_price)" x-text="'−Ks ' + (parseFloat(line.original_unit_price) - parseFloat(line.unit_price)).toLocaleString()"></p>
                                    <p class="text-[10px] font-black text-amber-600 dark:text-amber-400" x-show="(line.original_unit_price === null || parseFloat(line.original_unit_price) <= parseFloat(line.unit_price)) && parseFloat(line.retail_unit_price) > parseFloat(line.unit_price)" x-text="'−Ks ' + (parseFloat(line.retail_unit_price) - parseFloat(line.unit_price)).toLocaleString()"></p>
                                    {{-- Manager-approved deep override (audit badge) --}}
                                    <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 mt-0.5 inline-flex items-center gap-1" x-show="line.approved_by">
                                        <svg class="inline w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        {{ __('messages.pos_price_manager_approved') }}<span x-show="line.approved_by_name" x-text="' · ' + line.approved_by_name"></span>
                                    </p>
                                </div>
                                <button type="button" @click="removeLine(line)"
                                        class="shrink-0 w-8 h-8 rounded-lg bg-rose-500/10 text-rose-500 hover:bg-rose-500/20 grid place-items-center transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </div>
                            <div class="flex items-center justify-between gap-2 mt-2">
                                {{-- Qty stepper with inline edit on click --}}
                                 <div class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm overflow-hidden"
                                      x-data="{ editing: false, editVal: '' }">
                                     <button type="button" @click="changeQty(line, -1)" class="w-9 h-9 text-blue-600 dark:text-blue-400 font-black hover:bg-slate-100 dark:hover:bg-slate-800 transition">−</button>
                                     {{-- Click qty to type directly --}}
                                     <template x-if="!editing">
                                         <span class="w-9 text-center text-sm font-black cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-950/40 transition rounded"
                                               x-text="line.quantity"
                                               @click="editing = true; editVal = String(line.quantity); $nextTick(() => $refs['qtyInput_' + line.index]?.select())">
                                         </span>
                                     </template>
                                     <template x-if="editing">
                                         <input type="number" min="1" step="1"
                                                :x-ref="'qtyInput_' + line.index"
                                                x-model.number="editVal"
                                                class="w-12 text-center text-sm font-black border-x border-blue-400 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 outline-none py-1 tabular-nums"
                                                @keydown.enter.prevent="if (editVal >= 1) { setQty(line, editVal); } editing = false"
                                                @keydown.escape.prevent="editing = false"
                                                @blur="if (editVal >= 1) { setQty(line, editVal); } editing = false"
                                                x-init="$nextTick(() => $el.focus())">
                                     </template>
                                     <button type="button" @click="changeQty(line, 1)" class="w-9 h-9 text-blue-600 dark:text-blue-400 font-black hover:bg-slate-100 dark:hover:bg-slate-800 transition">+</button>
                                 </div>
                                <div class="flex items-center gap-1.5">
                                    <input x-show="priceEditIndex === line.index" x-model="priceEditValue" type="number" min="0" step="100"
                                           @keydown.enter="saveLinePrice(line)" @keydown.escape="priceEditIndex = null"
                                           class="w-24 rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-900 px-2 py-1 text-right text-sm font-semibold focus:ring-2 focus:ring-amber-500 outline-none">
                                    <input x-show="priceEditIndex === line.index && pricePinIndex === line.index" x-model="pricePinValue" type="password" inputmode="numeric" maxlength="6"
                                           @keydown.enter="saveLinePrice(line)" @keydown.escape="pricePinIndex = null"
                                           :placeholder="labels.pos_price_pin_label"
                                           class="w-20 rounded-lg border border-rose-300 dark:border-rose-700 bg-white dark:bg-slate-900 px-2 py-1 text-center text-sm font-bold tracking-widest focus:ring-2 focus:ring-rose-500 outline-none"
                                           :title="labels.pos_price_pin_label">
                                    <button x-show="priceEditIndex === line.index" type="button" @click="saveLinePrice(line)"
                                            class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 grid place-items-center transition font-black">✓</button>
                                    <button x-show="priceEditIndex === line.index" type="button" @click="priceEditIndex = null; pricePinIndex = null; pricePinValue = ''"
                                            class="w-8 h-8 rounded-lg bg-slate-500/10 text-slate-500 hover:bg-slate-500/20 grid place-items-center transition font-black">✕</button>
                                    <button x-show="priceEditIndex !== line.index" type="button" @click="startPriceEdit(line)"
                                            class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 grid place-items-center transition"
                                            :title="labels.pos_price_edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                    </button>
                                    <p class="text-sm font-extrabold text-blue-600 dark:text-blue-400" x-text="'Ks ' + Number(line.line_total).toLocaleString()"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="!cart.lines.length" x-cloak class="px-4 py-10 text-center">
                    <div class="mx-auto mb-3 w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 grid place-items-center text-slate-300 dark:text-slate-600">
                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">{{ __('messages.pos_no_products_added') }}</p>
                </div>

                {{-- Summary + actions (reference summary section) --}}
                <div class="mt-3 border-t border-slate-100 dark:border-slate-800 px-4 pt-3 pb-4 bg-slate-50/60 dark:bg-slate-800/30 rounded-t-2xl">
                    <p class="flex justify-between text-sm text-slate-500 dark:text-slate-400 mb-1">
                        <span>{{ __('messages.subtotal') }}</span>
                        <span class="font-bold text-slate-700 dark:text-slate-200" x-text="'Ks ' + Number(cart.totals.subtotal).toLocaleString()"></span>
                    </p>
                    <p class="flex justify-between text-sm text-amber-600 dark:text-amber-400 mb-1" x-show="Number(cart.totals.retail_subtotal) > Number(cart.totals.total)">
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><path d="M7 7h.01"/></svg>
                            {{ __('messages.pos_tier_total_savings') }}
                        </span>
                        <span class="font-bold" x-text="'−Ks ' + (Number(cart.totals.retail_subtotal) - Number(cart.totals.total)).toLocaleString()"></span>
                    </p>
                    <div class="border-t border-dashed border-slate-200 dark:border-slate-700 my-2.5"></div>
                    <p class="flex justify-between items-center mb-4">
                        <span class="text-base font-black">{{ __('messages.total') }}</span>
                        <span class="text-2xl font-extrabold text-blue-600 dark:text-blue-400" x-text="'Ks ' + Number(cart.totals.total).toLocaleString()"></span>
                    </p>

                    <div class="flex items-stretch gap-2">
                        <button type="button" @click="clearCart()" :disabled="!cart.lines.length"
                                class="shrink-0 w-12 rounded-xl border-2 border-rose-500/20 text-rose-500 hover:bg-rose-500/10 grid place-items-center transition disabled:opacity-40 disabled:cursor-not-allowed"
                                :title="labels.clear_cart">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 12.5-5 5"/><path d="m9.5 12.5 5 5"/><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/></svg>
                        </button>
                        <button type="button" @click="hold()" :disabled="!cart.lines.length"
                                class="flex-1 rounded-xl px-3 py-3 text-xs font-black text-amber-600 dark:text-amber-400 bg-amber-500/10 hover:bg-amber-500/20 transition disabled:opacity-40 disabled:cursor-not-allowed">
                            <svg class="inline w-3.5 h-3.5 -mt-0.5" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>
                            {{ __('messages.hold_sale') }}
                        </button>
                        <button type="button" x-show="cart.held_count > 0" x-cloak
                                @click="document.getElementById('pos-held-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                                class="shrink-0 w-12 rounded-xl bg-blue-600/10 text-blue-600 dark:text-blue-400 hover:bg-blue-600/20 grid place-items-center transition"
                                :title="labels.held">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        </button>
                        <button type="button" id="pos-checkout-btn"
                                @click="if (!shiftOpen) { window.dispatchEvent(new CustomEvent('pos:open-register')); mobileCartOpen = false; return; } if (!cart.lines.length) return; openPayment(); mobileCartOpen = false"
                                :class="(!cart.lines.length || !shiftOpen) ? 'opacity-40 cursor-not-allowed' : ''"
                                :aria-disabled="(!cart.lines.length || !shiftOpen) ? 'true' : 'false'"
                                class="flex-[2] rounded-xl px-3 py-3 text-sm font-black text-white bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-600/30 transition">
                            {{ __('messages.post_sale') }}
                        </button>
                    </div>
                    <p x-show="!shiftOpen" class="mt-2 text-[11px] font-bold text-amber-600 dark:text-amber-400" x-text="labels.shift_required"></p>
                </div>
            </aside>

                {{-- Floating cart + checkout button (mobile only) --}}
                <button type="button" @click="mobileCartOpen = true"
                        class="hidden max-lg:inline-flex fixed bottom-5 right-5 z-40 items-center gap-2.5 rounded-2xl bg-blue-600 text-white pl-4 pr-5 py-3.5 shadow-xl shadow-blue-600/40 hover:bg-blue-500 active:scale-95 transition">
                    <span class="relative shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                        <span class="absolute -top-2 -right-2 min-w-5 h-5 px-1 rounded-full bg-rose-500 text-white text-[10px] font-black grid place-items-center" x-text="cart.lines.length"></span>
                    </span>
                    <span class="text-left leading-tight">
                        <span class="block text-[10px] font-bold uppercase tracking-wide opacity-80">{{ __('messages.pos_cart_title') }}</span>
                        <span class="block text-sm font-black" x-text="'Ks ' + Number(cart.totals.total).toLocaleString()"></span>
                    </span>
                </button>
            </div>
        </div>

        {{-- ── Variant picker modal ─────────────────────────────────────── --}}
        <div x-show="variantProduct" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @keydown.escape.window="variantProduct = null">
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 p-5 shadow-2xl">
                <div class="flex items-center justify-between mb-3">
                    <div class="min-w-0">
                        <h3 class="text-base font-black" x-text="labels.select_variant"></h3>
                        <p class="text-sm text-slate-500 truncate" x-text="variantProduct ? variantProduct.name : ''"></p>
                    </div>
                    <button type="button" @click="variantProduct = null" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
                </div>
                <div class="space-y-2 max-h-72 overflow-y-auto">
                    <template x-for="v in variantProduct ? variantProduct.variants : []" :key="v.id">
                        <button type="button" @click="addVariant(v)" :disabled="parseFloat(v.balance) <= 0"
                                class="w-full text-left rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2 transition disabled:opacity-40 disabled:cursor-not-allowed"
                                :class="parseFloat(v.balance) > 0 ? 'border-slate-200 dark:border-slate-700 hover:border-blue-400 hover:shadow-md' : 'border-slate-200 dark:border-slate-800 opacity-60'">
                            <span class="min-w-0">
                                <span class="block text-sm font-bold truncate" x-text="v.name"></span>
                                <span class="block text-[11px] text-slate-500 font-mono" x-text="v.sku || ''"></span>
                            </span>
                            <span class="shrink-0 text-right">
                                <span class="block text-[10px] text-rose-500 font-bold line-through" x-show="variantProduct.tier === 'wholesale' && parseFloat(v.retail_price) > parseFloat(v.price)" x-text="'Ks ' + Number(v.retail_price).toLocaleString()"></span>
                                <span class="block text-sm font-black text-blue-600 dark:text-blue-400" x-text="'Ks ' + Number(v.price).toLocaleString()"></span>
                                <span class="block text-[10px] font-bold" :class="parseFloat(v.balance) > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500'"
                                      x-text="parseFloat(v.balance) > 0 ? ('×' + v.balance) : labels.out_of_stock"></span>
                            </span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- ── Payment modal (enhanced: quick chips + numpad) ──────────────── --}}
        <div x-show="showPayment" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/50 backdrop-blur-sm p-3 sm:p-4" @keydown.escape.window="showPayment = false">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden"
                 x-data="{
                     activeMethod: 'cash',
                     padCash(val) {
                         // numpad appends digit to current cash value
                         let s = String(Math.round(this.cash || 0));
                         if (val === 'C') { this.cash = 0; return; }
                         if (val === '←') { s = s.slice(0,-1) || '0'; this.cash = parseInt(s, 10); return; }
                         if (val === '00') { this.cash = parseInt(s + '00', 10); return; }
                         if (val === '000') { this.cash = parseInt(s + '000', 10); return; }
                         this.cash = parseInt(s === '0' ? val : s + val, 10);
                     },
                     setExact() {
                         this.cash = Math.max(0, Number(cart.totals.total) - Number(this.kpay||0) - Number(this.wavepay||0) - Number(this.cbpay||0) - Number(this.mmqr||0) - Number(this.credit||0));
                     }
                 }">

                {{-- Modal header --}}
                <div class="flex items-center justify-between gap-3 px-5 pt-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="text-base font-black">{{ __('messages.payments') }}</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ __('messages.total') }}:
                            <span class="font-extrabold text-blue-600 dark:text-blue-400" x-text="'Ks ' + Number(cart.totals.total).toLocaleString()"></span>
                        </p>
                    </div>
                    <button type="button" @click="showPayment = false"
                            class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition">✕</button>
                </div>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/post') }}" class="flex flex-col max-h-[85dvh] overflow-y-auto">
                    @csrf
                    <input type="hidden" name="customer_id" :value="customer ? customer.id : ''">
                    <input type="hidden" name="web_order_id" :value="pendingWebOrderId || ''">
                    {{-- Hidden payment method inputs (unchanged — server-side reads these) --}}
                    @foreach (['cash', 'kpay', 'wavepay', 'cb_pay', 'mmqr', 'credit'] as $i => $method)
                        <input type="hidden" name="payments[{{ $i }}][method]" value="{{ $method }}">
                        <input type="hidden" name="payments[{{ $i }}][amount]" x-model="{{ $method === 'cash' ? 'cash' : ($method === 'cb_pay' ? 'cbpay' : $method) }}">
                    @endforeach

                    <div class="px-5 py-4 space-y-4">

                        {{-- Payment method tiles --}}
                        @php
                        $methods = [
                            ['cash',    'payment_cash',    'M17 11H7M12 6v12',                                      'bg-emerald-500', 'text-emerald-700', 'emerald'],
                            ['kpay',    'payment_kpay',    'M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Zm0 0V5a2 2 0 0 1 2-2h5.5M21 9V5a2 2 0 0 0-2-2h-5.5', 'bg-purple-500', 'text-purple-700',  'purple'],
                            ['wavepay', 'payment_wavepay', 'M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Zm10 0a2 2 0 1 0 4 0 2 2 0 0 0-4 0',                              'bg-blue-500',   'text-blue-700',   'blue'],
                            ['cb_pay',  'payment_cb_pay',  'M4 3h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm4 8h8m-4-4v8',                    'bg-orange-500', 'text-orange-700', 'orange'],
                            ['mmqr',   'payment_mmqr',    'M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2',         'bg-pink-500',   'text-pink-700',   'pink'],
                            ['credit',  'payment_credit',  'M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1ZM16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8', 'bg-amber-500', 'text-amber-700', 'amber'],
                        ];
                        @endphp
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-2">{{ __('messages.payment_method') }}</p>
                            <div class="grid grid-cols-3 sm:grid-cols-6 gap-1.5">
                                @foreach ($methods as [$mid, $mlabel, $micon, $mactiveBg, $mactiveText, $mcolor])
                                <button type="button"
                                        @click="activeMethod = '{{ $mid }}'; {{ $mid === 'credit' ? '' : '' }}"
                                        :disabled="{{ $mid === 'credit' ? '!customer' : 'false' }}"
                                        :class="activeMethod === '{{ $mid }}'
                                            ? '{{ $mactiveBg }} text-white shadow-lg ring-2 ring-{{ $mcolor }}-400/40'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
                                        class="flex flex-col items-center gap-1 rounded-xl px-2 py-2.5 text-[10px] font-black transition disabled:opacity-40 disabled:cursor-not-allowed">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $micon }}"/></svg>
                                    {{ __('messages.' . $mlabel) }}
                                </button>
                                @endforeach
                            </div>
                            {{-- Credit warning --}}
                            <p x-show="activeMethod === 'credit' && !customer" x-cloak
                               class="mt-1.5 text-[11px] font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4m0 4h.01"/></svg>
                                {{ __('messages.credit_requires_customer') }}
                            </p>
                        </div>

                        {{-- Cash: quick amount chips + numpad --}}
                        <div x-show="activeMethod === 'cash'" x-cloak class="space-y-3">
                            {{-- Quick amount row --}}
                            <div>
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">{{ __('messages.quick_amounts') }}</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ([1000, 2000, 5000, 10000, 20000, 50000, 100000] as $amt)
                                    <button type="button" @click="cash = {{ $amt }}"
                                            :class="cash == {{ $amt }} ? 'bg-blue-600 text-white ring-2 ring-blue-600/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-blue-100 dark:hover:bg-blue-950'"
                                            class="px-3 py-1.5 rounded-xl text-xs font-black tabular-nums transition">
                                        {{ number_format($amt) }}
                                    </button>
                                    @endforeach
                                    <button type="button" @click="setExact()"
                                            :class="change === 0 && cash > 0 ? 'bg-emerald-600 text-white ring-2 ring-emerald-600/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-emerald-100 dark:hover:bg-emerald-950'"
                                            class="px-3 py-1.5 rounded-xl text-xs font-black transition">
                                        ✓ {{ __('messages.exact') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Cash amount display + numpad --}}
                            <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 overflow-hidden">
                                {{-- Amount display --}}
                                <div class="px-4 pt-3 pb-2 flex items-center justify-between gap-2">
                                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.payment_cash') }}</p>
                                    <p class="text-2xl font-extrabold tabular-nums text-slate-800 dark:text-slate-100" x-text="'Ks ' + Number(cash||0).toLocaleString()"></p>
                                </div>
                                {{-- Numpad --}}
                                <div class="grid grid-cols-4 border-t border-slate-200 dark:border-slate-700">
                                    @foreach (['7','8','9','←','4','5','6','C','1','2','3','000','0','00','',null] as $key)
                                        @if ($key === null)
                                            {{-- empty cell --}}
                                            <span class="border-r border-b border-slate-200 dark:border-slate-700"></span>
                                        @elseif ($key === '')
                                            {{-- empty spacer --}}
                                            <span class="border-r border-b border-slate-200 dark:border-slate-700"></span>
                                        @else
                                            <button type="button"
                                                    @click="padCash('{{ $key }}')"
                                                    class="h-11 text-center font-black text-sm border-r border-b border-slate-200 dark:border-slate-700 transition
                                                           {{ $key === 'C' ? 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-100 dark:hover:bg-rose-950' : ($key === '←' ? 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30 hover:bg-amber-100 dark:hover:bg-amber-950' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800') }} active:scale-95">
                                                {{ $key }}
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            {{-- Hidden input for cash --}}
                            <input type="hidden" x-model="cash">
                        </div>

                        {{-- Other payment methods: simple input --}}
                        @foreach (['kpay', 'wavepay', 'cb_pay', 'mmqr', 'credit'] as $method)
                        <div x-show="activeMethod === '{{ $method }}'" x-cloak class="space-y-2">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wide">{{ __('messages.payment_' . $method) }} (Ks)</p>
                            @php $xmodel = $method === 'cb_pay' ? 'cbpay' : $method; @endphp
                            <div class="flex flex-wrap gap-1.5 mb-2">
                                @foreach ([1000, 2000, 5000, 10000, 20000, 50000, 100000] as $amt)
                                <button type="button" @click="{{ $xmodel }} = {{ $amt }}"
                                        :class="{{ $xmodel }} == {{ $amt }} ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-blue-100'"
                                        class="px-3 py-1.5 rounded-xl text-xs font-black tabular-nums transition">
                                    {{ number_format($amt) }}
                                </button>
                                @endforeach
                                <button type="button" @click="{{ $xmodel }} = Math.max(0, Number(cart.totals.total) - Number(cash||0) - {{ collect(['kpay','wavepay','cb_pay','mmqr','credit'])->reject(fn($m)=>$m===$method)->map(fn($m)=>$m==='cb_pay'?'cbpay':$m)->map(fn($m)=>"Number($m||0)")->implode(' - ') }})"
                                        class="px-3 py-1.5 rounded-xl text-xs font-black bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200 transition">
                                    ✓ {{ __('messages.exact') }}
                                </button>
                            </div>
                            <input type="number" min="0" step="100" x-model="{{ $xmodel }}"
                                   :disabled="{{ $method === 'credit' ? '!customer' : 'false' }}"
                                   class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-right text-xl font-extrabold tabular-nums focus:ring-2 focus:ring-blue-500 outline-none disabled:opacity-40">
                        </div>
                        @endforeach

                        {{-- Summary box --}}
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 px-4 py-3 space-y-1.5 text-sm">
                            <p class="flex justify-between" x-show="Number(cart.totals.retail_subtotal) > Number(cart.totals.total)">
                                <span class="text-amber-600 dark:text-amber-400 inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><path d="M7 7h.01"/></svg>
                                    {{ __('messages.pos_tier_total_savings') }}
                                </span>
                                <span class="font-black text-amber-600 dark:text-amber-400" x-text="'−Ks ' + (Number(cart.totals.retail_subtotal) - Number(cart.totals.total)).toLocaleString()"></span>
                            </p>
                            <p class="flex justify-between font-bold">
                                <span class="text-slate-500">{{ __('messages.total') }}</span>
                                <span x-text="'Ks ' + Number(cart.totals.total).toLocaleString()"></span>
                            </p>
                            <div class="border-t border-dashed border-slate-200 dark:border-slate-700 my-1"></div>
                            <p class="flex justify-between" x-show="remaining !== 0">
                                <span class="text-slate-500">{{ __('messages.pos_remaining') }}</span>
                                <span class="font-bold" :class="remaining < 0 ? 'text-rose-600' : 'text-amber-600'" x-text="'Ks ' + remaining.toLocaleString()"></span>
                            </p>
                            <p class="flex justify-between" x-show="change > 0">
                                <span class="text-slate-500">{{ __('messages.change') }}</span>
                                <span class="font-extrabold text-emerald-600 text-lg" x-text="'Ks ' + change.toLocaleString()"></span>
                            </p>
                            <p class="flex justify-between" x-show="credit > 0">
                                <span class="text-slate-500">{{ __('messages.balance_due') }}</span>
                                <span class="font-black text-amber-600 dark:text-amber-400" x-text="'Ks ' + credit.toLocaleString()"></span>
                            </p>
                        </div>

                        {{-- Submit --}}
                        <button type="submit" :disabled="!exact"
                                :class="exact ? 'bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-600/30' : 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed'"
                                class="w-full rounded-xl px-4 py-4 text-base font-black text-white transition">
                            {{ __('messages.post_sale') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Held sales (client-rendered from cart-state so a hold/resume
               refreshes the list live — no page reload needed) ─────────── --}}
        <section id="pos-held-section" x-show="cart.held.length > 0" x-cloak
                 class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm scroll-mt-24">
            <div class="flex flex-wrap items-center gap-2 mb-3">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.held_sales') }}</p>
                <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-bold">
                    <span class="inline-flex items-center gap-1 rounded-md bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 px-2 py-0.5"
                          x-show="cart.expiry?.oldest_held_at">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span x-text="labels.oldest_hold.replace(':age', ageLabel(cart.expiry.oldest_held_at))"></span>
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-md bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 px-2 py-0.5"
                          x-show="cart.expiry?.soon_count > 0">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4m0 4h.01"/></svg>
                        <span x-text="labels.soon_to_expire.replace(':count', cart.expiry.soon_count)"></span>
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 px-2 py-0.5"
                          x-show="cart.expiry?.threshold_hours === 0">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span x-text="labels.expiry_off"></span>
                    </span>
                </div>
            </div>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <template x-for="h in cart.held" :key="h.id">
                    <div class="rounded-xl border border-amber-200 dark:border-amber-900 bg-amber-50/50 dark:bg-amber-950/30 p-3 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-amber-700 dark:text-amber-300" x-text="'#' + h.id + ' · ' + h.items_count + ' ' + labels.cart"></p>
                            <p class="text-sm font-black" x-text="'Ks ' + Number(h.total).toLocaleString()"></p>
                            <p class="mt-1 inline-flex items-center gap-1 rounded-md bg-amber-200/70 dark:bg-amber-900/60 px-1.5 py-0.5 text-[10px] font-black text-amber-800 dark:text-amber-200">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                <span x-text="labels.held_since.replace(':time', h.held_at)"></span>
                            </p>
                        </div>
                        <div class="flex gap-1">
                            <button type="button" @click="resumeHeld(h.id)" :disabled="cartBusy"
                                    class="text-xs font-bold px-2 py-1 rounded-lg bg-amber-500 text-white hover:bg-amber-400 disabled:opacity-50">{{ __('messages.resume') }}</button>
                            <button type="button" @click="voidHeld(h.id)" :disabled="cartBusy"
                                    class="text-xs font-bold px-2 py-1 rounded-lg bg-rose-500 text-white hover:bg-rose-400 disabled:opacity-50">{{ __('messages.void_sale') }}</button>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        {{-- ── Reporting tabs: Today | Registers | Debt ─────────────────── --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <nav class="bg-slate-100/90 dark:bg-slate-800/80 p-1 rounded-t-2xl" role="tablist" aria-label="{{ __('messages.today_summary') }}">
                <div class="flex items-stretch gap-1 overflow-x-auto scrollbar-thin">
                    @foreach ([
                        ['today', 'pos_tab_today', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z'],
                        ['registers', 'pos_tab_registers', 'M4 5h16v5H4V5Zm2 5v9h12v-9M7 12h2m-2 4h2m5-4h3m-3 4h3'],
                        ['debt', 'pos_tab_debt', 'M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12H4L5 9Z'],
                    ] as [$key, $labelKey, $icon])
                        <button type="button" role="tab" @click="activeTab = '{{ $key }}'"
                                :aria-selected="activeTab === '{{ $key }}' ? 'true' : 'false'"
                                class="group relative flex-1 sm:flex-none min-w-0 inline-flex items-center justify-center sm:justify-start gap-2 rounded-xl px-3 sm:px-4 py-2.5 text-xs sm:text-sm font-black transition min-h-[44px]"
                                :class="activeTab === '{{ $key }}' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 shadow-sm ring-1 ring-slate-900/5 dark:ring-white/10' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-700/50'">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                            <span class="truncate">{{ __('messages.' . $labelKey) }}</span>
                        </button>
                    @endforeach
                </div>
            </nav>

            <div class="p-4 sm:p-5 space-y-5">
                {{-- TODAY: posted sales --}}
                <div x-show="activeTab === 'today'">
                    @if ($todaySales->isNotEmpty())
                        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                                    <tr>
                                        <th class="text-left px-3 py-2">{{ __('messages.receipt_number') }}</th>
                                        <th class="text-left px-3 py-2">{{ __('messages.cashier') }}</th>
                                        <th class="text-left px-3 py-2">{{ __('messages.customer') }}</th>
                                        <th class="text-left px-3 py-2">{{ __('messages.cart') }}</th>
                                        <th class="text-right px-3 py-2">{{ __('messages.payments') }}</th>
                                        <th class="text-right px-3 py-2">{{ __('messages.total') }}</th>
                                        <th class="text-center px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach ($todaySales as $sale)
                                        @php $saleDebt = $sale->payments->firstWhere('method', 'credit')?->amount ?? '0'; @endphp
                                        <tr>
                                            <td class="px-3 py-2.5 font-mono font-bold text-blue-600 dark:text-blue-400">{{ $sale->receipt_number }}</td>
                                            <td class="px-3 py-2.5">{{ $sale->cashier?->name }}</td>
                                            <td class="px-3 py-2.5">
                                                @if ($sale->customer)
                                                    <span class="font-semibold">{{ $sale->customer->name }}</span>
                                                    @if ((float) $saleDebt > 0)
                                                        <span class="block text-[10px] font-bold text-amber-600 dark:text-amber-400">{{ __('messages.debt') }} Ks {{ number_format((float) $saleDebt) }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2.5 text-xs text-slate-500">
                                                {{ $sale->items->take(3)->pluck('product_name')->implode(', ') }}{{ $sale->items->count() > 3 ? '…' : '' }}
                                            </td>
                                            <td class="px-3 py-2.5 text-xs">
                                                @foreach ($sale->payments as $payment)
                                                    <span class="inline-block px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono mr-1">{{ $payment->method }} {{ number_format((float) $payment->amount) }}</span>
                                                @endforeach
                                            </td>
                                            <td class="px-3 py-2.5 text-right font-black">Ks {{ number_format((float) $sale->total) }}</td>
                                            <td class="px-3 py-2.5 text-center whitespace-nowrap">
                                                @if ($sale->status !== 'refunded')
                                                    <a href="{{ url('/store/' . $store->slug . '/pos/sales/' . $sale->id . '/refund') }}"
                                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-rose-100 dark:hover:bg-rose-900/40 hover:text-rose-600 transition"
                                                       title="{{ __('messages.refund_sale') }}">
                                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>
                                                    </a>
                                                @endif
                                                <a href="{{ url('/store/' . $store->slug . '/pos/sales/' . $sale->id . '/receipt') }}" target="_blank"
                                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-blue-100 dark:hover:bg-blue-900 hover:text-blue-600 transition"
                                                   title="{{ __('messages.print_receipt') }}">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ __('messages.no_sales_today') }}
                        </div>
                    @endif
                </div>

                {{-- REGISTERS: shift card + today's closing summary --}}
                <div x-show="activeTab === 'registers'" class="space-y-5">
                    @if ($openShift)
                        <section id="pos-shift-card" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm scroll-mt-24">
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.open_shift') }}</p>
                                    <h2 class="text-lg font-black mt-0.5">{{ $openShift->register_name }}</h2>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        {{ __('messages.cashier') }}: {{ $openShift->cashier?->name }} ·
                                        {{ __('messages.opened_at') }}: {{ $openShift->opened_at->format('H:i') }}
                                    </p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">● {{ __('messages.shift_open') }}</span>
                            </div>

                            <dl class="grid grid-cols-2 gap-3 text-sm mb-5">
                                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                    <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.opening_cash') }}</dt>
                                    <dd class="font-black mt-0.5">Ks {{ number_format((float) $openShift->opening_cash) }}</dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                    <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.cash_in_out') }}</dt>
                                    <dd class="font-black mt-0.5 text-blue-600 dark:text-blue-400">+{{ number_format((float) $openShift->cash_in) }} / −{{ number_format((float) $openShift->cash_out) }}</dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                    <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.cash_sales') }}</dt>
                                    <dd class="font-black mt-0.5">Ks {{ number_format((float) $openShift->cash_sales) }}</dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                    <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.cash_refunds') }}</dt>
                                    <dd class="font-black mt-0.5">Ks {{ number_format((float) $openShift->cash_refunds) }}</dd>
                                </div>
                            </dl>

                            <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/shifts/' . $openShift->id . '/cash-events') }}"
                                  class="grid grid-cols-[1fr_auto] gap-2 mb-5" x-data="{ type: 'cash_in' }">
                                @csrf
                                <div class="grid grid-cols-2 gap-2">
                                    <select name="type" x-model="type" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                                        <option value="cash_in">+ {{ __('messages.cash_in') }}</option>
                                        <option value="cash_out">− {{ __('messages.cash_out') }}</option>
                                    </select>
                                    <input type="number" name="amount" min="1" step="100" required placeholder="Ks"
                                           class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                                </div>
                                <input type="text" name="reason" maxlength="255" placeholder="{{ __('messages.reason') }}"
                                       class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                                <button type="submit" class="rounded-xl px-4 py-2 text-sm font-bold bg-blue-600 text-white hover:bg-blue-500 transition">{{ __('messages.save') }}</button>
                            </form>

                            <div x-data="{ show: false }" class="border-t border-slate-200 dark:border-slate-800 pt-4">
                                <button type="button" @click="show = !show"
                                        class="w-full rounded-xl px-4 py-3 text-sm font-bold bg-rose-600 text-white hover:bg-rose-500 transition">{{ __('messages.close_shift') }}</button>
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/shifts/' . $openShift->id . '/close') }}"
                                      x-show="show" x-cloak class="mt-3 grid gap-2">
                                    @csrf
                                    <input type="number" name="actual_closing_amount" min="0" step="100" required placeholder="{{ __('messages.actual_closing_amount') }} (Ks)"
                                           class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                                    <textarea name="notes" rows="2" maxlength="1000" placeholder="{{ __('messages.notes') }}"
                                              class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm"></textarea>
                                    <button type="submit" class="rounded-xl px-4 py-2 text-sm font-bold bg-rose-600 text-white hover:bg-rose-500 transition">{{ __('messages.confirm_close_shift') }}</button>
                                </form>
                            </div>
                        </section>
                    @endif

                    <section class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-1">{{ __('messages.today_summary') }}</p>
                        <h2 class="text-lg font-black mb-4">{{ now()->format('d M Y') }}</h2>

                        @if ($summary['shift_count'] > 0)
                            <dl class="grid grid-cols-2 gap-3 text-sm mb-4">
                                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                    <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.closed_shifts') }}</dt>
                                    <dd class="font-black mt-0.5">{{ $summary['shift_count'] }}</dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                    <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.expected_cash') }}</dt>
                                    <dd class="font-black mt-0.5">Ks {{ number_format((float) $summary['expected']) }}</dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                    <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.actual_cash') }}</dt>
                                    <dd class="font-black mt-0.5">Ks {{ number_format((float) $summary['actual']) }}</dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                    <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.difference') }}</dt>
                                    <dd class="font-black mt-0.5 {{ (float) $summary['difference'] < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ (float) $summary['difference'] < 0 ? '−' : '+' }}Ks {{ number_format(abs((float) $summary['difference'])) }}
                                    </dd>
                                </div>
                            </dl>

                            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                                        <tr>
                                            <th class="text-left px-3 py-2">{{ __('messages.cashier') }}</th>
                                            <th class="text-left px-3 py-2">{{ __('messages.register') }}</th>
                                            <th class="text-right px-3 py-2">{{ __('messages.opening_cash') }}</th>
                                            <th class="text-right px-3 py-2">{{ __('messages.actual') }}</th>
                                            <th class="text-right px-3 py-2">{{ __('messages.difference') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        @foreach ($summary['shifts'] as $shift)
                                            <tr>
                                                <td class="px-3 py-2.5 font-semibold">{{ $shift->cashier?->name }}</td>
                                                <td class="px-3 py-2.5">{{ $shift->register_name }}</td>
                                                <td class="px-3 py-2.5 text-right">Ks {{ number_format((float) $shift->opening_cash) }}</td>
                                                <td class="px-3 py-2.5 text-right">Ks {{ number_format((float) $shift->actual_closing_amount) }}</td>
                                                <td class="px-3 py-2.5 text-right font-bold {{ (float) $shift->difference < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                                    {{ (float) $shift->difference < 0 ? '−' : '+' }}Ks {{ number_format(abs((float) $shift->difference)) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                {{ __('messages.no_closed_shifts_today') }}
                            </div>
                        @endif
                    </section>
                </div>

                {{-- DEBT: customer balances (receivables — SoT §17) --}}
                <div x-show="activeTab === 'debt'">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.customer_balances') }}</p>
                            <h2 class="text-lg font-black mt-0.5">{{ __('messages.outstanding_debt') }}</h2>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300">
                            Ks {{ number_format((float) $outstandingTotal) }}
                        </span>
                    </div>

                    @if ($outstanding)
                        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                                    <tr>
                                        <th class="text-left px-3 py-2">{{ __('messages.customer') }}</th>
                                        <th class="text-right px-3 py-2">{{ __('messages.outstanding_balance') }}</th>
                                        <th class="text-left px-3 py-2">{{ __('messages.last_activity') }}</th>
                                        <th class="text-right px-3 py-2">{{ __('messages.collect') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach ($outstanding as $customer)
                                        <tr>
                                            <td class="px-3 py-2.5">
                                                <p class="font-semibold">{{ $customer['name'] }}</p>
                                                <p class="text-xs text-slate-500 font-mono">{{ $customer['phone'] ?? '—' }}</p>
                                            </td>
                                            <td class="px-3 py-2.5 text-right font-black text-amber-600 dark:text-amber-400">Ks {{ number_format((float) $customer['balance']) }}</td>
                                            <td class="px-3 py-2.5 text-xs text-slate-500">{{ $customer['last_activity'] ? \Illuminate\Support\Carbon::parse($customer['last_activity'])->diffForHumans() : '—' }}</td>
                                            <td class="px-3 py-2.5 text-right">
                                                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/customers/' . $customer['customer_id'] . '/collect') }}"
                                                      class="inline-flex items-center gap-1" x-data="{ amount: '' }">
                                                    @csrf
                                                    <input type="number" name="amount" min="0.01" :max="{{ $customer['balance'] }}" step="any" required placeholder="Ks" x-model="amount"
                                                           class="w-28 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-right text-sm font-semibold">
                                                    <button type="submit" :disabled="!amount || parseFloat(amount) <= 0"
                                                            class="text-xs font-bold px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 disabled:opacity-40 disabled:cursor-not-allowed transition">
                                                        {{ __('messages.collect') }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                            <svg class="inline w-4 h-4 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            {{ __('messages.no_outstanding_debt') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
