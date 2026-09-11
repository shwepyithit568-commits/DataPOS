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
        },

        // Draggable floating cart button on mobile
        fabDragging: false,
        fabMoved: false,
        fabStartX: 0,
        fabStartY: 0,
        fabOffsetX: 0,
        fabOffsetY: 0,
        fabPosX: null,
        fabPosY: null,
        initFab() {
            const saved = localStorage.getItem('posMobileCartWidgetPos');
            if (saved && this.$refs.floatingFab) {
                try {
                    const p = JSON.parse(saved);
                    if (p.x !== null && p.y !== null) {
                        this.$nextTick(() => {
                            const el = this.$refs.floatingFab;
                            if (!el) return;
                            const rect = el.getBoundingClientRect();
                            const maxX = window.innerWidth - (rect.width || 120) - 8;
                            const maxY = window.innerHeight - (rect.height || 50) - 8;
                            const cx = Math.max(8, Math.min(maxX, p.x));
                            const cy = Math.max(8, Math.min(maxY, p.y));
                            this.fabPosX = cx;
                            this.fabPosY = cy;
                            el.style.left = cx + 'px';
                            el.style.top = cy + 'px';
                            el.style.right = 'auto';
                            el.style.bottom = 'auto';
                        });
                    }
                } catch(e) {}
            }
            window.addEventListener('resize', () => {
                if (this.fabPosX !== null && this.$refs.floatingFab) {
                    const el = this.$refs.floatingFab;
                    const rect = el.getBoundingClientRect();
                    const maxX = window.innerWidth - rect.width - 8;
                    const maxY = window.innerHeight - rect.height - 8;
                    if (this.fabPosX > maxX || this.fabPosY > maxY) {
                        const nx = Math.max(8, Math.min(maxX, this.fabPosX));
                        const ny = Math.max(8, Math.min(maxY, this.fabPosY));
                        el.style.left = nx + 'px';
                        el.style.top = ny + 'px';
                        this.fabPosX = nx;
                        this.fabPosY = ny;
                    }
                }
            });
        },
        onFabDown(e) {
            this.fabDragging = true;
            this.fabMoved = false;
            const t = e.touches ? e.touches[0] : e;
            this.fabStartX = t.clientX;
            this.fabStartY = t.clientY;
            const el = this.$refs.floatingFab;
            if (el) {
                const rect = el.getBoundingClientRect();
                this.fabOffsetX = t.clientX - rect.left;
                this.fabOffsetY = t.clientY - rect.top;
            }
        },
        onFabMove(e) {
            if (!this.fabDragging) return;
            const t = e.touches ? e.touches[0] : e;
            const dx = t.clientX - this.fabStartX;
            const dy = t.clientY - this.fabStartY;
            if (Math.abs(dx) > 6 || Math.abs(dy) > 6) {
                this.fabMoved = true;
            }
            if (!this.fabMoved) return;

            const el = this.$refs.floatingFab;
            if (!el) return;
            const rect = el.getBoundingClientRect();
            let nx = t.clientX - this.fabOffsetX;
            let ny = t.clientY - this.fabOffsetY;

            const maxX = window.innerWidth - rect.width - 8;
            const maxY = window.innerHeight - rect.height - 8;
            nx = Math.max(8, Math.min(maxX, nx));
            ny = Math.max(8, Math.min(maxY, ny));

            el.style.left = nx + 'px';
            el.style.top = ny + 'px';
            el.style.right = 'auto';
            el.style.bottom = 'auto';
            this.fabPosX = nx;
            this.fabPosY = ny;

            if (e.cancelable) {
                e.preventDefault();
            }
        },
        onFabUp() {
            if (!this.fabDragging) return;
            this.fabDragging = false;
            if (this.fabMoved && this.fabPosX !== null) {
                localStorage.setItem('posMobileCartWidgetPos', JSON.stringify({ x: this.fabPosX, y: this.fabPosY }));
            }
        }
    }">

    {{-- Mobile backdrop: tap to close (bottom sheet convention) --}}
    <div x-show="mobileCartOpen" x-cloak @click="mobileCartOpen = false"
         class="hidden max-lg:block fixed inset-0 z-50 bg-black/40"></div>

<aside id="pos-cart-panel" x-ref="drawer"
       @keydown.escape.window="mobileCartOpen = false"
       @touchstart="onTouchStart($event)" @touchmove="onTouchMove($event)" @touchend="onTouchEnd()" @touchcancel="onTouchCancel()"
       class="fixed inset-x-0 bottom-0 z-50 max-h-[88dvh] overflow-y-auto rounded-t-3xl rounded-b-none border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl pb-[calc(1rem+env(safe-area-inset-bottom,0px))] lg:static lg:inset-auto lg:z-auto lg:max-h-none lg:overflow-visible lg:rounded-2xl lg:border lg:shadow-sm lg:sticky lg:top-20 lg:pb-0"
       :class="mobileCartOpen ? '' : 'max-lg:translate-y-[120%]'">

    {{-- Mobile drawer handle + header (bottom sheet only) --}}
    <div class="hidden max-lg:flex items-center justify-center pt-2.5 px-4">
        <div class="w-10 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></div>
    </div>
    <div class="hidden max-lg:flex items-center justify-between gap-3 px-4 pb-3 pt-2 border-b border-slate-100 dark:border-slate-800">
        <p class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.pos_cart_title') }}</p>
        <div class="flex items-center gap-2">
            <span class="px-2.5 py-1 rounded-full text-xs font-black bg-blue-600/10 text-blue-600 dark:text-blue-400" x-text="cart.lines.length + ' · ' + formatCurrency(cart.totals.total)"></span>
            <button type="button" @click="mobileCartOpen = false" class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-300 font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition">✕</button>
        </div>
    </div>

    {{-- Customer selector header --}}
    <div class="px-4 pt-3.5 pb-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30">
        {{-- State 1: When a Customer IS Attached --}}
        <div x-show="customer" x-cloak class="rounded-2xl border border-blue-200/80 dark:border-blue-800/60 bg-blue-50/70 dark:bg-blue-950/40 p-3 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-10 h-10 shrink-0 rounded-xl bg-blue-600 text-white font-black text-sm grid place-items-center shadow-md shadow-blue-500/20 uppercase"
                         x-text="customer ? customer.name.charAt(0) : 'U'"></div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <p class="text-sm font-black text-slate-900 dark:text-slate-100 truncate" x-text="customer ? customer.name : ''"></p>
                            <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full"
                                  :class="customer && customer.role === 'wholesale_customer' ? 'bg-amber-500 text-white' : 'bg-blue-600 text-white'"
                                  x-text="customer && customer.role === 'wholesale_customer' ? '🏬 {{ __('messages.pos_customer_wholesale') }}' : '🛒 {{ __('messages.pos_customer_retail') }}'"></span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-mono mt-0.5" x-text="customer ? (customer.phone || '—') : ''"></p>
                    </div>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button type="button" @click="changeCustomer()"
                            class="px-2.5 py-1.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-bold transition shadow-sm"
                            title="{{ __('messages.pos_customer_change') }}">
                        🔄 <span class="hidden sm:inline">{{ __('messages.pos_customer_change') }}</span>
                    </button>
                    <button type="button" @click="clearCustomer()"
                            class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/60 transition grid place-items-center text-sm font-bold"
                            title="{{ __('messages.pos_customer_detached') }}">
                        ✕
                    </button>
                </div>
            </div>
            <template x-if="customer && parseFloat(customer.balance) > 0">
                <div class="mt-2.5 pt-2 border-t border-blue-200/60 dark:border-blue-900/60 flex items-center justify-between text-xs">
                    <span class="font-bold text-amber-700 dark:text-amber-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        {{ __('messages.outstanding_debt') }}:
                    </span>
                    <span class="font-black text-amber-700 dark:text-amber-400 font-mono" x-text="formatCurrency(customer.balance)"></span>
                </div>
            </template>
        </div>

        {{-- State 2: When NO Customer Attached (Walk-in / Search Mode) --}}
        <div x-show="!customer" x-cloak>
            <div class="flex items-center justify-between gap-2 mb-2">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-lg bg-slate-200 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 grid place-items-center text-xs">
                        👤
                    </div>
                    <div class="min-w-0">
                        <span class="text-xs font-black text-slate-700 dark:text-slate-300">{{ __('messages.walk_in_customer') }}</span>
                        <span class="text-[10px] text-slate-400 font-semibold block">{{ __('messages.pos_customer_search_hint') }}</span>
                    </div>
                </div>
                <button type="button" @click="openQuickAdd(cq.trim())"
                        class="px-2.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black text-xs transition inline-flex items-center gap-1 shadow-sm shrink-0">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>{{ __('messages.pos_quick_add_customer') }}</span>
                </button>
            </div>

            {{-- Customer search (F3) --}}
            <div class="relative" @click.outside="copen = false">
                <div class="relative flex items-center">
                    <span class="absolute left-3 text-slate-400 pointer-events-none text-xs">🔍</span>
                    <input id="pos-customer-input"
                           type="search"
                           name="pos_customer_search"
                           x-ref="customerInput"
                           x-model="cq"
                           @focus="csearch(true)"
                           @click="csearch(true)"
                           @input.debounce.200ms="if (isStaffPhone(cq)) { cq = ''; } else { csearch(); }"
                           @change="if (isStaffPhone(cq)) { cq = ''; }"
                           @keydown.escape.stop="copen = false"
                           @keydown.enter.prevent="if (cresults.length > 0) { attach(cresults[0]); } else if (cq.trim() && !isStaffPhone(cq)) { openQuickAdd(cq.trim()); }"
                           placeholder="{{ __('messages.customer_search_placeholder') }} (F3)"
                           autocomplete="off"
                           autocorrect="off"
                           autocapitalize="off"
                           spellcheck="false"
                           role="searchbox"
                           aria-autocomplete="list"
                           data-lpignore="true"
                           data-1p-ignore="true"
                           data-form-type="other"
                           class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 pl-8 pr-14 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 outline-none shadow-sm transition">
                    <div class="absolute right-2 flex items-center gap-1">
                        <button type="button" x-show="cq.trim() !== ''" @click="cq = ''; csearch(true); $refs.customerInput?.focus()"
                                class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-300 text-[10px] font-bold grid place-items-center hover:bg-slate-300">✕</button>
                        <span class="text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-400 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700 pointer-events-none">F3</span>
                    </div>
                </div>

                {{-- Search Dropdown Results --}}
                <div x-show="copen" x-cloak
                     class="absolute z-30 inset-x-0 top-full mt-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-2xl max-h-64 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                    {{-- Quick Add button inside dropdown --}}
                    <div class="p-1.5 bg-slate-50/80 dark:bg-slate-800/50">
                        <button type="button" @click="openQuickAdd(cq.trim())"
                                class="w-full text-left px-3 py-2 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-xs font-bold transition flex items-center justify-between gap-2">
                            <span class="flex items-center gap-1.5 truncate">
                                <span class="w-5 h-5 rounded-full bg-blue-600 text-white grid place-items-center text-xs font-black shrink-0">+</span>
                                <span x-text="cq.trim() ? labels.pos_customer_not_found_add.replace(':name', cq.trim()) : '+ ' + '{{ __('messages.pos_customer_quick_add_title') }}'"></span>
                            </span>
                            <span class="text-[10px] bg-blue-600/10 px-1.5 py-0.5 rounded font-mono font-bold shrink-0">Enter</span>
                        </button>
                    </div>

                    {{-- Results List --}}
                    <template x-for="c in cresults" :key="c.id">
                        <button type="button" @click="attach(c)"
                                class="w-full text-left px-3 py-2.5 hover:bg-blue-50/70 dark:hover:bg-slate-800/80 flex items-center justify-between gap-2 transition group">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs grid place-items-center shrink-0 uppercase group-hover:bg-blue-600 group-hover:text-white transition"
                                     x-text="c.name.charAt(0)"></div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate group-hover:text-blue-600 dark:group-hover:text-blue-400" x-text="c.name"></p>
                                    <p class="text-[11px] text-slate-400 font-mono" x-text="c.phone || '—'"></p>
                                </div>
                            </div>
                            <div class="shrink-0 flex items-center gap-1.5">
                                <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded"
                                      :class="c.role === 'wholesale_customer' ? 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                                      x-text="c.role === 'wholesale_customer' ? '{{ __('messages.pos_customer_wholesale') }}' : '{{ __('messages.pos_customer_retail') }}'"></span>
                                <span x-show="parseFloat(c.balance) > 0" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-50 dark:bg-rose-950 text-rose-600 dark:text-rose-400 font-mono"
                                      x-text="formatCurrency(c.balance)"></span>
                            </div>
                        </button>
                    </template>

                    {{-- Empty search state --}}
                    <div x-show="cq.trim() !== '' && !cresults.length" class="p-4 text-center">
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.no_customers_found') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <p x-show="credit > 0 && !customer" x-cloak class="mt-2 text-[11px] font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
            <svg class="inline w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4m0 4h.01"/></svg>
            <span>{{ __('messages.credit_requires_customer') }}</span>
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
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30 px-3 py-2 shadow-sm transition hover:border-slate-300 dark:hover:border-slate-700">
                <div class="flex items-center justify-between gap-2">
                    {{-- Product Name & Unit Price / Badges (Left) --}}
                    <div class="min-w-0 flex-1">
                        <p class="text-xs sm:text-sm font-bold leading-tight truncate" :title="line.name" x-text="line.name"></p>
                        <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                            {{-- Negotiated override: strike the tier price it replaced --}}
                            <span class="text-[10px] text-rose-500 font-bold line-through" x-show="line.original_unit_price !== null && parseFloat(line.original_unit_price) > parseFloat(line.unit_price)" x-text="formatCurrency(line.original_unit_price)"></span>
                            <span class="text-[11px] font-mono" :class="(line.original_unit_price !== null && parseFloat(line.original_unit_price) > parseFloat(line.unit_price)) || parseFloat(line.retail_unit_price) > parseFloat(line.unit_price) ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-400'" x-text="'@ ' + formatCurrency(line.unit_price)"></span>
                            {{-- Override savings (amber) takes precedence over the wholesale comparison --}}
                            <span class="text-[10px] font-black text-amber-600 dark:text-amber-400" x-show="line.original_unit_price !== null && parseFloat(line.original_unit_price) > parseFloat(line.unit_price)" x-text="'−' + formatCurrency(parseFloat(line.original_unit_price) - parseFloat(line.unit_price))"></span>
                            <span class="text-[10px] font-black text-amber-600 dark:text-amber-400" x-show="(line.original_unit_price === null || parseFloat(line.original_unit_price) <= parseFloat(line.unit_price)) && parseFloat(line.retail_unit_price) > parseFloat(line.unit_price)" x-text="'−' + formatCurrency(parseFloat(line.retail_unit_price) - parseFloat(line.unit_price))"></span>
                            {{-- Manager-approved deep override (audit badge) --}}
                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 inline-flex items-center gap-1" x-show="line.approved_by">
                                <svg class="inline w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <span x-text="line.approved_by_name || '{{ __('messages.pos_price_manager_approved') }}'"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Controls: Stepper + Price Edit + Total + Remove (Single Row, Right) --}}
                    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                        {{-- Qty stepper with inline edit on click --}}
                        <div class="inline-flex items-center gap-0.5 p-0.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100/60 dark:bg-slate-900/60 shadow-xs"
                             x-data="{ editing: false, editVal: '' }">
                            <button type="button" @click="changeQty(line, -1)" class="sf-btn-3d w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-blue-600 dark:text-blue-400 font-black flex items-center justify-center cursor-pointer transition text-xs sm:text-sm">−</button>
                            {{-- Click qty to type directly --}}
                            <template x-if="!editing">
                                <span class="w-6 sm:w-7 text-center text-xs sm:text-sm font-black cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-950/40 transition rounded"
                                      x-text="line.quantity"
                                      @click="editing = true; editVal = String(line.quantity); $nextTick(() => $refs['qtyInput_' + line.index]?.select())">
                                </span>
                            </template>
                            <template x-if="editing">
                                <input type="number" min="1" step="1"
                                       :x-ref="'qtyInput_' + line.index"
                                       x-model.number="editVal"
                                       class="w-10 sm:w-12 text-center text-xs sm:text-sm font-black border-x border-blue-400 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 outline-none py-0.5 tabular-nums rounded"
                                       @keydown.enter.prevent="if (editVal >= 1) { setQty(line, editVal); } editing = false"
                                       @keydown.escape.prevent="editing = false"
                                       @blur="if (editVal >= 1) { setQty(line, editVal); } editing = false"
                                       x-init="$nextTick(() => $el.focus())">
                            </template>
                            <button type="button" @click="changeQty(line, 1)" class="sf-btn-3d w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-blue-600 dark:text-blue-400 font-black flex items-center justify-center cursor-pointer transition text-xs sm:text-sm">+</button>
                        </div>

                        {{-- Price edit / inputs if editing --}}
                        <div class="flex items-center gap-1">
                            <input x-show="priceEditIndex === line.index" x-model="priceEditValue" type="number" min="0" step="100"
                                   @keydown.enter="saveLinePrice(line)" @keydown.escape="priceEditIndex = null"
                                   class="w-20 rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-900 px-1.5 py-0.5 text-right text-xs font-semibold focus:ring-2 focus:ring-amber-500 outline-none">
                            <input x-show="priceEditIndex === line.index && pricePinIndex === line.index"
                                   x-model="pricePinValue"
                                   type="password"
                                   name="manager_override_pin"
                                   autocomplete="new-password"
                                   inputmode="numeric"
                                   maxlength="6"
                                   data-lpignore="true"
                                   data-1p-ignore="true"
                                   data-form-type="other"
                                   @keydown.enter="saveLinePrice(line)"
                                   @keydown.escape="pricePinIndex = null"
                                   :placeholder="labels.pos_price_pin_label"
                                   class="w-16 rounded-lg border border-rose-300 dark:border-rose-700 bg-white dark:bg-slate-900 px-1.5 py-0.5 text-center text-xs font-bold tracking-widest focus:ring-2 focus:ring-rose-500 outline-none"
                                   :title="labels.pos_price_pin_label">
                            <button x-show="priceEditIndex === line.index" type="button" @click="saveLinePrice(line)"
                                    class="sf-btn-3d-success w-6 h-6 sm:w-7 sm:h-7 rounded-lg flex items-center justify-center text-xs font-black cursor-pointer transition">✓</button>
                            <button x-show="priceEditIndex === line.index" type="button" @click="priceEditIndex = null; pricePinIndex = null; pricePinValue = ''"
                                    class="sf-btn-3d-danger w-6 h-6 sm:w-7 sm:h-7 rounded-lg flex items-center justify-center text-xs font-black cursor-pointer transition">✕</button>
                            <button x-show="priceEditIndex !== line.index" type="button" @click="startPriceEdit(line)"
                                    class="sf-btn-3d w-6 h-6 sm:w-7 sm:h-7 rounded-lg flex items-center justify-center text-amber-600 dark:text-amber-400 cursor-pointer transition"
                                    :title="labels.pos_price_edit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                            </button>
                            <p class="text-xs sm:text-sm font-extrabold text-blue-600 dark:text-blue-400 min-w-[70px] sm:min-w-[85px] text-right font-mono" x-text="formatCurrency(line.line_total)"></p>
                        </div>

                        {{-- Remove line button --}}
                        <button type="button" @click="removeLine(line)"
                                class="sf-btn-3d-danger shrink-0 w-6 h-6 sm:w-7 sm:h-7 rounded-lg flex items-center justify-center cursor-pointer transition ml-0.5"
                                :title="labels.remove_item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
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
            <span class="font-bold text-slate-700 dark:text-slate-200" x-text="formatCurrency(cart.totals.subtotal)"></span>
        </p>
        <p class="flex justify-between text-sm text-slate-500 dark:text-slate-400 mb-1"
           x-show="cart.totals.tax_enabled && cart.totals.tax_type === 'exclusive'" x-cloak>
            <span>{{ __('messages.commercial_tax') }}:</span>
            <span class="font-bold text-slate-700 dark:text-slate-200" x-text="'+ ' + formatCurrency(cart.totals.tax)"></span>
        </p>
        {{-- Discount row --}}
        <div class="flex justify-between items-center text-sm mb-1">
            <button type="button" @click="openDiscountModal()" class="inline-flex items-center gap-1 font-semibold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                <span>{{ __('messages.discount') }}</span>
                <span x-show="Number(cart.totals.discount) > 0" class="text-[10px] bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 px-1.5 py-0.5 rounded-full font-bold ml-0.5">Edit</span>
            </button>
            <span class="font-bold cursor-pointer text-xs sm:text-sm" @click="openDiscountModal()"
                  :class="Number(cart.totals.discount) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400 dark:text-slate-500 hover:text-blue-600 dark:hover:text-blue-400'"
                  x-text="Number(cart.totals.discount) > 0 ? ('− ' + formatCurrency(cart.totals.discount)) : '+ {{ __('messages.add_discount') }}'"></span>
        </div>
        <p class="flex justify-between text-sm text-amber-600 dark:text-amber-400 mb-1" x-show="Number(cart.totals.retail_subtotal) > Number(cart.totals.total) && Number(cart.totals.discount) <= 0">
            <span class="inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><path d="M7 7h.01"/></svg>
                {{ __('messages.pos_tier_total_savings') }}
            </span>
            <span class="font-bold" x-text="'−' + formatCurrency(Number(cart.totals.retail_subtotal) - Number(cart.totals.total))"></span>
        </p>
        <div class="border-t border-dashed border-slate-200 dark:border-slate-700 my-2.5"></div>
        <p class="flex justify-between items-center mb-1">
            <span class="text-base font-black">{{ __('messages.total') }}</span>
            <span class="text-2xl font-extrabold text-blue-600 dark:text-blue-400" x-text="formatCurrency(cart.totals.total)"></span>
        </p>
        <p class="text-[11px] text-slate-400 dark:text-slate-500 text-right mb-4"
           x-show="cart.totals.tax_enabled && Number(cart.totals.tax) > 0 && cart.totals.tax_type === 'inclusive'" x-cloak>
            ({{ __('messages.commercial_tax') }} <span x-text="cart.totals.default_tax_rate"></span>% <span x-text="formatCurrency(cart.totals.tax)"></span> {{ __('messages.included') }})
        </p>

        <div class="flex items-stretch gap-2">
            <button type="button" @click="clearCart()" :disabled="!cart.lines.length"
                    class="sf-btn-3d-danger shrink-0 w-12 rounded-xl flex items-center justify-center transition cursor-pointer"
                    :title="labels.clear_cart">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
            <button type="button" @click="hold()" :disabled="!cart.lines.length"
                    class="sf-btn-3d-gold flex-1 rounded-xl px-3 py-3 text-xs font-black transition cursor-pointer flex items-center justify-center gap-1.5">
                <svg class="inline w-3.5 h-3.5 -mt-0.5" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>
                <span>{{ __('messages.hold_sale') }}</span>
            </button>
            <button type="button" x-show="cart.held_count > 0" x-cloak
                    @click="document.getElementById('pos-held-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                    class="sf-btn-3d shrink-0 w-12 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400 transition cursor-pointer"
                    :title="labels.held">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
            </button>
            <button type="button" id="pos-checkout-btn"
                    @click="if (shiftsEnabled && !shiftOpen) { window.dispatchEvent(new CustomEvent('pos:open-register')); mobileCartOpen = false; return; } if (!cart.lines.length) return; openPayment(); mobileCartOpen = false"
                    :disabled="!cart.lines.length || (shiftsEnabled && !shiftOpen)"
                    :aria-disabled="(!cart.lines.length || (shiftsEnabled && !shiftOpen)) ? 'true' : 'false'"
                    class="sf-btn-3d-success flex-[2] rounded-xl px-3 py-3 text-sm font-black text-white transition cursor-pointer flex items-center justify-center gap-1.5 shadow-lg">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                <span>{{ __('messages.post_sale') }}</span>
            </button>
        </div>
        <p x-show="shiftsEnabled && !shiftOpen" class="mt-2 text-[11px] font-bold text-amber-600 dark:text-amber-400" x-text="labels.shift_required"></p>
    </div>
</aside>

    {{-- Floating cart + checkout button (mobile only - draggable by touch/finger) --}}
    <div x-ref="floatingFab"
         x-init="initFab()"
         x-show="!mobileCartOpen"
         x-cloak
         @touchstart="onFabDown($event)"
         @touchmove="onFabMove($event)"
         @touchend="onFabUp()"
         @touchcancel="onFabUp()"
         @mousedown="onFabDown($event)"
         @mousemove.window="fabDragging && onFabMove($event)"
         @mouseup.window="onFabUp()"
         :class="fabDragging ? 'cursor-grabbing scale-105 shadow-2xl opacity-90 transition-none' : 'cursor-grab active:scale-95'"
         class="hidden max-lg:block fixed bottom-5 right-4 sm:right-5 z-40 select-none touch-none transition-transform duration-150">
        <button type="button"
                @click="if (!fabMoved) mobileCartOpen = true"
                class="sf-btn-3d-primary inline-flex items-center gap-2.5 rounded-2xl text-white pl-4 pr-5 py-3 shadow-2xl cursor-pointer select-none">
            <span class="relative shrink-0 pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                <span class="absolute -top-2 -right-2 min-w-5 h-5 px-1 rounded-full bg-rose-500 text-white text-[10px] font-black grid place-items-center" x-text="cart.lines.length"></span>
            </span>
            <span class="text-left leading-tight pointer-events-none">
                <span class="block text-[10px] font-bold uppercase tracking-wide opacity-80">{{ __('messages.pos_cart_title') }}</span>
                <span class="block text-sm font-black" x-text="typeof window.formatCurrency === 'function' ? window.formatCurrency(cart.totals.total) : Number(cart.totals.total).toLocaleString()"></span>
            </span>
        </button>
    </div>
</div>
