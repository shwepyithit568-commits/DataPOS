import './bootstrap';
import './csp-helpers';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

/* ---- brandAssetUploader: isolated state for the three Storefront brand
   asset uploaders (Storefront logo / Admin logo / Favicon). Every card owns
   its own instance so file selection, preview, errors and remove flags never
   leak between fields. ---- */
Alpine.data('brandAssetUploader', (field, opts = {}) => ({
    field,
    accept: opts.accept || 'image/png,image/webp,image/jpeg',
    maxBytes: opts.maxBytes || 2 * 1024 * 1024,
    hasCurrent: !!opts.currentUrl,
    currentUrl: opts.currentUrl || null,
    fallbackNote: opts.fallbackNote || '',
    selectedFile: null,
    previewUrl: null,
    fileError: '',
    isValidFile: false,
    isSubmitting: false,
    markRemove: false,

    get fileName() {
        return this.selectedFile ? this.selectedFile.name : '';
    },
    get fileSizeLabel() {
        if (!this.selectedFile) return '';
        const kb = this.selectedFile.size / 1024;
        return kb >= 1024 ? (kb / 1024).toFixed(1) + ' MB' : Math.round(kb) + ' KB';
    },
    get previewSrc() {
        return this.previewUrl || this.currentUrl;
    },
    get showCurrent() {
        return !this.selectedFile && !this.markRemove && this.hasCurrent;
    },
    get showEmpty() {
        return !this.selectedFile && !this.markRemove && !this.hasCurrent;
    },
    get showMarkedForRemoval() {
        return this.markRemove && this.hasCurrent && !this.selectedFile;
    },
    get showRemoveAction() {
        return this.hasCurrent && !this.selectedFile && !this.markRemove;
    },

    handleFile(e) {
        const file = e.target.files && e.target.files[0];
        if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
        this.previewUrl = null;
        this.selectedFile = null;
        this.fileError = '';
        this.isValidFile = false;
        if (!file) return;

        const extOk = this.accept.split(',').some((t) => {
            const ext = t.trim().split('/').pop();
            return file.name.toLowerCase().endsWith('.' + ext);
        });
        const sizeOk = file.size <= this.maxBytes;

        this.selectedFile = file;
        this.previewUrl = URL.createObjectURL(file);
        this.isValidFile = extOk && sizeOk;

        if (!extOk) {
            this.fileError = 'Unsupported file type. Allowed: ' + this.accept.replace(/image\//g, '').replace(/,/g, ', ').toUpperCase();
            return;
        }
        if (!sizeOk) {
            this.fileError = 'File is too large. Maximum size is ' + Math.round(this.maxBytes / (1024 * 1024)) + ' MB.';
            return;
        }

        // Selecting a replacement cancels that field's remove state.
        this.markRemove = false;
    },
    clearSelection() {
        if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
        this.previewUrl = null;
        this.selectedFile = null;
        this.fileError = '';
        this.isValidFile = false;
    },
    markForRemove() {
        this.clearSelection();
        this.markRemove = true;
    },
    cancelRemove() {
        this.markRemove = false;
    },
    beginSubmit() {
        this.isSubmitting = true;
    },
}))

/* ---- posApp: POS home (two-panel cashier UI — reference: alinthit_pos)

   Left panel = searchable product grid (category/brand filters, tap to add,
   variant picker). Right panel = live cart (qty steppers, customer attach,
   hold, checkout). Cart mutations run as AJAX against JSON endpoints and the
   cart snapshot is refreshed from the response — no page reloads mid-sale.
   Keyboard shortcuts: F1 search, F2 checkout, F3 customer, F4 clear cart,
   F5 reload grid, F6 hold, F7 held orders. ---- */
Alpine.data('posApp', (opts = {}) => ({
    baseUrl: opts.baseUrl || '',
    csrf: opts.csrf || '',
    labels: opts.labels || {},

    // Product grid
    q: '',
    categoryId: 0,
    brandId: 0,
    products: [],
    categories: [],
    brands: [],
    gridLoading: false,
    gridTimer: null,

    // Cart + payments
    cart: { shift_open: false, lines: [], totals: { subtotal: '0', total: '0' }, held_count: 0, held: [], expiry: { threshold_hours: 24, oldest_held_at: null, soon_count: 0 } },
    cartBusy: false,
    variantProduct: null,
    showPayment: false,
    customer: null,
    cash: '0',
    kpay: 0, wavepay: 0, cbpay: 0, mmqr: 0, credit: 0,
    cq: '', cresults: [], copen: false,
    quickAddOpen: false, quickBusy: false, qname: '', qphone: '',
    notice: '', noticeType: '', noticeTimer: null,

    /* ---- init ---- */
    async init() {
        await this.loadGrid();
        await this.refreshCart();
        window.addEventListener('keydown', (e) => this.shortcut(e));
    },

    url(path) {
        return this.baseUrl + path;
    },

    async fetchJson(path, options = {}) {
        const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': this.csrf };
        if (options.body) headers['Content-Type'] = 'application/x-www-form-urlencoded';
        const res = await fetch(this.url(path), { ...options, headers, credentials: 'same-origin' });
        let data = {};
        try { data = await res.json(); } catch (e) { /* empty body */ }
        if (!res.ok) throw new Error(data.error || ('HTTP ' + res.status));
        return data;
    },

    /* ---- product grid ---- */
    async loadGrid() {
        this.gridLoading = true;
        try {
            const params = new URLSearchParams();
            if (this.q.trim()) params.set('q', this.q.trim());
            if (this.categoryId) params.set('category_id', this.categoryId);
            if (this.brandId) params.set('brand_id', this.brandId);
            const data = await this.fetchJson('/products-grid?' + params.toString());
            this.products = data.products || [];
            this.categories = data.categories || [];
            this.brands = data.brands || [];
        } catch (e) {
            this.flash(e.message, 'error');
        } finally {
            this.gridLoading = false;
        }
    },

    onSearch() {
        clearTimeout(this.gridTimer);
        this.gridTimer = setTimeout(() => this.loadGrid(), 250);
    },

    toggleCategory(id) {
        this.categoryId = this.categoryId === id ? 0 : id;
        this.loadGrid();
    },

    toggleBrand(id) {
        this.brandId = this.brandId === id ? 0 : id;
        this.loadGrid();
    },

    /* ---- cart mutations (AJAX) ---- */
    async addProduct(p) {
        if (p.variants && p.variants.length > 0) { this.variantProduct = p; return; }
        await this.mutate('/cart', { product_id: p.id, quantity: '1' });
    },

    async addVariant(v) {
        const p = this.variantProduct;
        this.variantProduct = null;
        if (!p) return;
        await this.mutate('/cart', { product_id: p.id, product_variant_id: v.id, quantity: '1' });
    },

    async changeQty(line, delta) {
        const qty = (parseFloat(line.quantity) || 0) + delta;
        if (qty <= 0) { await this.removeLine(line); return; }
        await this.mutate('/cart/' + line.index, { quantity: String(qty) });
    },

    async removeLine(line) {
        await this.mutate('/cart/' + line.index, {}, { method: 'DELETE' });
    },

    async clearCart() {
        if (!this.cart.lines.length) return;
        await this.mutate('/cart/clear', {});
    },

    // Apply a cart snapshot from the server and return how many stale holds
    // were auto-expired on this read (so callers can surface a notice).
    applyCart(data) {
        if (data.cart) this.cart = data.cart;
        return data.cart ? (data.cart.expired_count || 0) : 0;
    },

    expiredNotice(count) {
        return (this.labels.holds_expired || ':count stale held sale(s) auto-expired and voided.').replace(':count', count);
    },

    // Relative age of a held sale, e.g. '2h 15m' — for the expiry stats strip.
    ageLabel(iso) {
        if (!iso) return '';
        const mins = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 60000));
        const h = Math.floor(mins / 60), m = mins % 60;
        return (h > 0 ? h + 'h ' : '') + m + 'm';
    },

    async hold() {
        if (!this.cart.lines.length) return;
        try {
            const data = await this.fetchJson('/hold', { method: 'POST', body: new URLSearchParams({}) });
            const expired = this.applyCart(data);
            this.flash(expired > 0 ? this.expiredNotice(expired) : (this.labels.held || 'Sale held'), expired > 0 ? 'error' : 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        }
    },

    async resumeHeld(id) {
        if (this.cartBusy) return;
        this.cartBusy = true;
        try {
            const data = await this.fetchJson('/resume/' + id, { method: 'POST', body: new URLSearchParams({}) });
            const expired = this.applyCart(data);
            this.flash(expired > 0 ? this.expiredNotice(expired) : (this.labels.resumed || 'Sale resumed'), expired > 0 ? 'error' : 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        } finally {
            this.cartBusy = false;
        }
    },

    async voidHeld(id) {
        if (this.cartBusy) return;
        if (!confirm('Void this held sale?')) return;
        this.cartBusy = true;
        try {
            const data = await this.fetchJson('/void/' + id, { method: 'POST', body: new URLSearchParams({}) });
            const expired = this.applyCart(data);
            this.flash(expired > 0 ? this.expiredNotice(expired) : (this.labels.voided || 'Sale voided'), expired > 0 ? 'error' : 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        } finally {
            this.cartBusy = false;
        }
    },

    async mutate(path, body, options = {}) {
        if (this.cartBusy) return;
        this.cartBusy = true;
        try {
            const data = await this.fetchJson(path, { method: options.method || 'POST', body: new URLSearchParams(body) });
            const expired = this.applyCart(data);
            this.flash(expired > 0 ? this.expiredNotice(expired) : (this.labels.added || 'OK'), expired > 0 ? 'error' : 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        } finally {
            this.cartBusy = false;
        }
    },

    async refreshCart() {
        try {
            const data = await this.fetchJson('/cart-state');
            const expired = this.applyCart(data);
            if (expired > 0) this.flash(this.expiredNotice(expired), 'error');
        } catch (e) { /* cart refresh is best-effort */ }
    },

    openPayment() {
        if (!this.shiftOpen) { this.flash(this.labels.shift_required || 'Open a shift first', 'error'); return; }
        if (!this.cart.lines.length) return;
        // Pre-fill cash with the exact total unless the cashier already typed one.
        if (!this.cash || parseFloat(this.cash) === 0) this.cash = this.cart.totals.total;
        this.showPayment = true;
    },

    /* ---- customer attach (credit/debt) ---- */
    async csearch() {
        if (this.cq.trim() === '') { this.cresults = []; this.copen = false; return; }
        try {
            const data = await this.fetchJson('/customers?q=' + encodeURIComponent(this.cq));
            this.cresults = data.customers || [];
            this.copen = true;
        } catch (e) { this.cresults = []; }
    },

    attach(c) {
        this.customer = c;
        this.cq = c.name;
        this.cresults = [];
        this.copen = false;
        if (this.remaining > 0 && this.credit === 0) this.credit = Math.max(0, Math.round(this.remaining / 100) * 100);
    },

    clearCustomer() { this.customer = null; this.cq = ''; this.credit = 0; },

    openQuickAdd(name = '') {
        this.qname = name || '';
        this.qphone = '';
        this.quickAddOpen = true;
        this.$nextTick(() => this.$refs.quickName?.focus());
    },

    // Quick-add a customer: POST /pos/customers creates the user + this
    // store's retail_customer membership (shared users table, phone dedup and
    // staff-phone guard all live server-side). The returned customer is
    // attached to the cart like a search hit.
    async quickAdd() {
        if (!this.qname.trim() || !this.qphone.trim() || this.quickBusy) return;
        this.quickBusy = true;
        try {
            const data = await this.fetchJson('/customers', { method: 'POST', body: new URLSearchParams({ name: this.qname.trim(), phone: this.qphone.trim() }) });
            this.quickAddOpen = false;
            this.cresults = [];
            this.copen = false;
            this.cq = '';
            this.attach(data.customer);
            this.flash(data.success || this.labels.pos_customer_added, 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        } finally {
            this.quickBusy = false;
        }
    },

    /* ---- payment math ---- */
    get paid() {
        return ['cash', 'kpay', 'wavepay', 'cbpay', 'mmqr', 'credit'].reduce((s, k) => s + (parseFloat(this[k]) || 0), 0);
    },
    get remaining() { return parseFloat(this.cart.totals.total || 0) - this.paid; },
    get change() { return this.remaining < 0 ? -this.remaining : 0; },
    get shiftOpen() { return !!this.cart.shift_open; },
    get exact() {
        if (this.credit > 0 && !this.customer) return false;
        return this.remaining <= 0.005;
    },

    /* ---- feedback ---- */
    flash(msg, type) {
        this.notice = msg;
        this.noticeType = type;
        clearTimeout(this.noticeTimer);
        this.noticeTimer = setTimeout(() => { this.notice = ''; }, 3500);
    },

    /* ---- keyboard shortcuts ---- */
    shortcut(e) {
        if (!e.key || !e.key.toUpperCase().startsWith('F')) return;
        const k = e.key.toUpperCase();
        const actions = {
            F1: () => { e.preventDefault(); this.$refs.searchInput && this.$refs.searchInput.focus(); },
            F2: () => { e.preventDefault(); this.openPayment(); },
            F3: () => { e.preventDefault(); this.$refs.customerInput && this.$refs.customerInput.focus(); },
            F4: () => { e.preventDefault(); this.clearCart(); },
            F5: () => { e.preventDefault(); this.loadGrid(); },
            F6: () => { e.preventDefault(); this.hold(); },
            F7: () => { e.preventDefault(); const el = document.getElementById('pos-held-toggle'); if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' }); },
        };
        if (actions[k]) actions[k]();
    },
}))

/* ---- x-show resilience: don't depend on requestAnimationFrame ----
   Alpine's transitions plugin routes every x-show toggle (including the
   fallback for elements without x-transition) through rAF. In embedded
   webviews / background tabs rAF can be throttled or stall entirely, which
   silently keeps payment/variant modals hidden after their first toggle.
   Elements that declare x-transition keep the animated path; everything else
   toggles synchronously so modals/toasts always reveal. ---- */
if (Element.prototype._x_toggleAndCascadeWithTransitions) {
    const nativeToggle = Element.prototype._x_toggleAndCascadeWithTransitions;
    Element.prototype._x_toggleAndCascadeWithTransitions = function (el, value, show, hide) {
        if (el._x_transition) return nativeToggle.call(this, el, value, show, hide);
        value ? show() : hide();
    };
}

Alpine.start();

/* ---- Admin new-order / wholesale alerts (chime + browser notification) ---- */
// Activated only on store-scoped admin pages via the data-admin-alerts-url
// attribute rendered by layouts/admin/app.blade.php. Polls a small JSON
// endpoint and alerts when a fresh order or wholesale application arrives.
(function adminAlerts() {
    const root = document.querySelector('[data-admin-alerts-url]');
    if (!root || !root.dataset.adminAlertsUrl) return;

    const url = root.dataset.adminAlertsUrl;
    const intervalMs = parseInt(root.dataset.adminAlertsInterval || '30000', 10);

    function playChime() {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            const ctx = new Ctx();
            [880, 1174.66].forEach(function (freq, i) {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                const start = ctx.currentTime + i * 0.18;
                gain.gain.setValueAtTime(0.0001, start);
                gain.gain.exponentialRampToValueAtTime(0.12, start + 0.03);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.16);
                osc.connect(gain).connect(ctx.destination);
                osc.start(start);
                osc.stop(start + 0.2);
            });
            setTimeout(() => ctx.close(), 800);
        } catch (e) { /* audio is a nice-to-have */ }
    }

    function notify(title, body) {
        try {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification(title, { body: body, icon: '/favicon.ico' });
            }
        } catch (e) { /* notifications are optional */ }
    }

    function showToast(lines, href) {
        const existing = document.querySelector('[data-admin-alert-toast]');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.setAttribute('data-admin-alert-toast', '1');
        toast.setAttribute('role', 'status');
        toast.className = 'fixed bottom-24 right-1/2 translate-x-1/2 z-[100] max-w-[92vw] sm:max-w-md px-4 py-3 rounded-xl bg-slate-900/95 dark:bg-slate-700 text-white text-sm font-semibold shadow-xl border border-slate-700/50 cursor-pointer';
        const frag = document.createElement('div');
        lines.forEach((line) => {
            const p = document.createElement('p');
            p.textContent = line;
            frag.appendChild(p);
        });
        if (href) {
            toast.addEventListener('click', () => { window.location.href = href; });
            toast.setAttribute('aria-label', 'View details');
        }
        toast.appendChild(frag);
        document.body.appendChild(toast);
        setTimeout(function () { toast.remove(); }, 10000);
    }

    function updateStats(pendingOrders, pendingWholesale, todayOrders) {
        document.querySelectorAll('[data-pending-order-count]').forEach(function (el) {
            el.textContent = String(pendingOrders);
        });
        document.querySelectorAll('[data-pending-wholesale-count]').forEach(function (el) {
            el.textContent = String(pendingWholesale);
        });
        document.querySelectorAll('[data-today-orders-stat]').forEach(function (el) {
            el.textContent = String(todayOrders);
        });
    }

    async function poll() {
        let res;
        try {
            res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
        } catch (e) { return; }
        if (!res.ok) return;
        let data;
        try { data = await res.json(); } catch (e) { return; }

        const baseUrl = url.split('/admin/alerts')[0];
        const pendingOrders = Number(data.pending_orders || 0);
        const pendingWholesale = Number(data.pending_wholesale || 0);
        const todayOrders = Number(data.today_orders || 0);

        if (window.__adminAlertBaseline === undefined) {
            window.__adminAlertBaseline = { pendingOrders, pendingWholesale, todayOrders };
            updateStats(pendingOrders, pendingWholesale, todayOrders);
            return; // First poll establishes the baseline silently.
        }

        const baseline = window.__adminAlertBaseline;
        const freshOrder = pendingOrders > baseline.pendingOrders;
        const freshWholesale = pendingWholesale > baseline.pendingWholesale;

        updateStats(pendingOrders, pendingWholesale, todayOrders);
        baseline.pendingOrders = pendingOrders;
        baseline.pendingWholesale = pendingWholesale;
        baseline.todayOrders = todayOrders;

        if (!freshOrder && !freshWholesale) return;

        if (freshOrder) {
            playChime();
            notify('🛒 အမှာစာအသစ် ရောက်ပါပြီ', 'Pending orders: ' + pendingOrders);
            showToast(['🛒 အမှာစာအသစ် ရောက်ပါပြီ', 'Pending orders: ' + pendingOrders], baseUrl + '/orders');
        }
        if (freshWholesale) {
            playChime();
            notify('💼 လက်ကားလျှောက်လွှာအသစ် ရောက်ပါပြီ', 'Pending wholesale: ' + pendingWholesale);
            showToast(['လက်ကားလျှောက်လွှာအသစ်', 'Pending wholesale: ' + pendingWholesale], baseUrl + '/wholesale/applications');
        }
    }

    // First poll establishes the baseline silently; later polls alert on arrivals.
    poll();
    setInterval(poll, intervalMs);
})();
