import './bootstrap';
import './csp-helpers';
import Alpine from 'alpinejs';
import { Html5Qrcode } from 'html5-qrcode';

window.Alpine = Alpine;
window.Html5Qrcode = Html5Qrcode;

/* ---- Expense retry identity (plain-form admin create) ----
   The admin expense form is a normal POST, so it has no fetch() layer to hold a
   key in memory. This pair hands the Blade form a stable per-session key and
   clears it once the server has confirmed a write, so a re-submitted page (lost
   response, refresh, double-click) replays the stored row instead of creating a
   second expense. Scoped per store + session, so a different cashier logging in
   on the same terminal never inherits the previous key. ---- */
window.dataposExpenseKey = function (scope) {
    const storageKey = 'datapos.expense.txid.' + scope;
    const makeKey = () => (window.crypto && typeof window.crypto.randomUUID === 'function')
        ? window.crypto.randomUUID()
        : 'exp-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);

    try {
        const existing = window.sessionStorage.getItem(storageKey);
        if (existing) return existing;
        const fresh = makeKey();
        window.sessionStorage.setItem(storageKey, fresh);
        return fresh;
    } catch (e) {
        return makeKey();
    }
};

window.dataposClearExpenseKey = function (scope) {
    try {
        window.sessionStorage.removeItem('datapos.expense.txid.' + scope);
    } catch (e) {
        // ignore
    }
};

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
    shiftsEnabled: opts.shiftsEnabled ?? true,
    staffPhone: opts.staffPhone || '',
    staffName: opts.staffName || '',

    // Product grid
    q: '',
    mobileSearchOpen: opts.mobileSearchOpen || false,
    categoryId: 0,
    brandId: 0,
    products: [],
    categories: [],
    brands: [],
    gridLoading: false,
    gridTimer: null,

    // Camera Barcode Scanner
    barcodeScannerOpen: false,
    barcodeContinuous: true,
    barcodeTorchOn: false,
    barcodeFacingMode: 'environment',
    barcodeLoading: false,
    barcodeCameraError: '',
    barcodeLastScanned: '',
    barcodeLastScannedName: '',
    barcodeScannerInstance: null,
    barcodeCooldown: false,

    // Cart + payments
    cart: { shift_open: false, lines: [], totals: { subtotal: '0', total: '0' }, held_count: 0, held: [], expiry: { threshold_hours: 24, oldest_held_at: null, soon_count: 0 } },
    cartBusy: false,
    variantProduct: null,
    showPayment: false,
    // Reporting modal (today / registers / debt / repairs)
    activeTab: 'today',
    reportingModalOpen: false,
    openReportingModal(tab = 'today') {
        this.activeTab = tab;
        this.reportingModalOpen = true;
    },
    closeReportingModal() {
        this.reportingModalOpen = false;
    },
    switchTab(name) {
        this.activeTab = name;
        this.reportingModalOpen = true;
    },
    // Web order import (fulfil an online order at the counter)
    webOrdersOpen: false,
    webOrderQ: '',
    webOrderResults: [],
    webOrderLoading: false,
    pendingWebOrderId: null,
    async openWebOrders() {
        this.webOrdersOpen = true;
        await this.loadWebOrders();
    },
    async loadWebOrders() {
        this.webOrderLoading = true;
        try {
            const data = await this.fetchJson('/web-orders?q=' + encodeURIComponent(this.webOrderQ));
            this.webOrderResults = data.orders || [];
        } catch (e) {
            this.flash(e.message, 'error');
        } finally {
            this.webOrderLoading = false;
        }
    },
    async importWebOrder(order) {
        this.webOrdersOpen = false;
        try {
            // The server builds the cart: it re-prices the order's lines and
            // aligns the total with what the shopper agreed online (coupon
            // included), so the counter charges the order's amount.
            const data = await this.fetchJson('/web-orders/' + order.id + '/import', {
                method: 'POST',
                body: new URLSearchParams({}),
            });
            this.applyCart(data);
            this.pendingWebOrderId = order.id;
            this.flash(data.success || this.labels.web_order_imported || 'Web order loaded into cart', 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        }
    },
    priceEditIndex: null,
    priceEditValue: '',
    pricePinIndex: null,   // line whose override needs a manager PIN
    pricePinValue: '',
    customer: null,
    cash: 0, kpay: 0, wavepay: 0, cbpay: 0, mmqr: 0, credit: 0,
    activeMethod: 'cash',   // which payment tile is focused in the modal
    cq: '', cresults: [], copen: false,
    quickAddOpen: false, quickBusy: false, qname: '', qphone: '', qtype: 'retail_customer',
    // Shop Expense Modal (quick cash-out / daily store expense)
    expenseModalOpen: false, expenseBusy: false, expenseTitle: '', expenseAmount: '',
    expenseCategoryId: '', expensePaymentMethod: 'cash', expensePaidTo: '', expenseNotes: '',
    // Retry identity for the expense currently in the modal. Survives a lost
    // response, a re-click and a same-tab reload; cleared only once the server
    // has confirmed the write, so the NEXT expense gets a fresh key.
    expenseClientTxId: '', expenseConflict: '',
    // Discount Modal
    discountModalOpen: false, discountBusy: false, discountType: 'fixed', discountValue: '',
    // Coupon (stored on the cart by the server; these drive the modal UI)
    couponCode: '', couponBusy: false, couponValid: false, couponMessage: '',
    // Loyalty points spent on this cart (server-side, like the coupon)
    pointsInput: '', pointsBusy: false,
    barcodeCameraInsecure: false,
    notice: '', noticeType: '', noticeTimer: null,

    /* ---- init ---- */
    async init() {
        this._barcodeVisibility = () => { if (document.hidden && this.barcodeScannerOpen) this.closeBarcodeScanner(); };
        document.addEventListener('visibilitychange', this._barcodeVisibility);
        await this.loadGrid();
        await this.refreshCart();
        window.addEventListener('keydown', (e) => this.shortcut(e));
        window.addEventListener('pos:reload', () => this.reloadPos());

        // Shield customer search from browser autofilling cashier/staff credentials or phone
        const clearStaffAutofill = () => {
            if (this.isStaffPhone(this.cq)) {
                this.cq = '';
                this.cresults = [];
                this.copen = false;
            }
            const el = document.getElementById('pos-customer-input');
            if (el && this.isStaffPhone(el.value)) {
                el.value = '';
                this.cq = '';
                this.cresults = [];
                this.copen = false;
            }
        };
        this.$nextTick(clearStaffAutofill);
        setTimeout(clearStaffAutofill, 100);
        setTimeout(clearStaffAutofill, 400);
        setTimeout(clearStaffAutofill, 1000);

        // Desktop: focus the search box so a cashier can type / scan immediately.
        if (window.innerWidth >= 1024) {
            this.$nextTick(() => {
                const searchInput = document.getElementById('pos-search-input') || (this.$refs && this.$refs.searchInput);
                if (searchInput) searchInput.focus();
            });
        }
    },

    isStaffPhone(val) {
        if (!this.staffPhone || !val) return false;
        const cleanStaff = String(this.staffPhone).replace(/\D/g, '');
        const cleanVal = String(val).replace(/\D/g, '');
        if (!cleanStaff || !cleanVal) return false;
        return cleanStaff === cleanVal
            || (cleanVal.length >= 7 && cleanStaff.endsWith(cleanVal))
            || (cleanStaff.length >= 7 && cleanVal.endsWith(cleanStaff));
    },

    url(path) {
        return this.baseUrl + path;
    },

    formatCurrency(val) {
        return (typeof window.formatCurrency === 'function') ? window.formatCurrency(val) : Number(val || 0).toLocaleString();
    },

    formatQuantity(val) {
        return (typeof window.formatQuantity === 'function') ? window.formatQuantity(val) : String(val ?? 0);
    },

    async fetchJson(path, options = {}) {
        const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': this.csrf };
        if (options.body) headers['Content-Type'] = 'application/x-www-form-urlencoded';
        const res = await fetch(this.url(path), { ...options, headers, credentials: 'same-origin' });
        let data = {};
        try { data = await res.json(); } catch (e) { /* empty body */ }
        if (!res.ok) {
            const err = new Error(data.error || ('HTTP ' + res.status));
            err.pinRequired = !!data.pin_required;
            err.status = res.status;
            err.conflict = !!data.conflict;
            throw err;
        }
        return data;
    },

    /* ---- product grid ---- */
    async loadGrid(autoAddIfSingle = false) {
        this.gridLoading = true;
        try {
            const params = new URLSearchParams();
            const trimmedQ = this.q.trim();
            if (trimmedQ) params.set('q', trimmedQ);
            if (this.categoryId) params.set('category_id', this.categoryId);
            if (this.brandId) params.set('brand_id', this.brandId);
            const data = await this.fetchJson('/products-grid?' + params.toString());
            this.products = data.products || [];
            this.categories = data.categories || [];
            this.brands = data.brands || [];

            // If triggered by Enter (e.g. barcode scanner) and exactly 1 match found:
            if (autoAddIfSingle && trimmedQ && this.products.length === 1) {
                const singleProd = this.products[0];
                await this.addProduct(singleProd);
                this.q = '';
                await this.loadGrid();
            }
        } catch (e) {
            this.flash(e.message, 'error');
        } finally {
            this.gridLoading = false;
        }
    },

    async reloadPos() {
        this.gridLoading = true;
        window.dispatchEvent(new CustomEvent('pos:loading', { detail: true }));
        try {
            await Promise.all([
                this.loadGrid(),
                this.refreshCart(),
            ]);
            this.flash(this.labels.pos_reloaded || 'အချက်အလက်များ ပြန်လည်ရယူပြီးပါပြီ', 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        } finally {
            this.gridLoading = false;
            window.dispatchEvent(new CustomEvent('pos:loading', { detail: false }));
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

    fmtQty(qty) {
        const n = parseFloat(qty) || 0;
        return n % 1 === 0 ? String(Math.round(n)) : String(parseFloat(n.toFixed(3)));
    },

    stockPillText(bal) {
        const b = parseFloat(bal) || 0;
        if (b <= 0) return '0 · ' + (this.labels.out_of_stock || 'ပစ္စည်းပြတ်');
        if (b <= 5) return this.fmtQty(b) + ' · ' + (this.labels.low_stock || 'လက်ကျန်နည်း');
        return this.fmtQty(b) + ' · ' + (this.labels.in_stock || 'ပစ္စည်းရှိ');
    },

    stockPillClass(bal) {
        const b = parseFloat(bal) || 0;
        if (b <= 0) return 'bg-rose-500';
        if (b <= 5) return 'bg-amber-500';
        return 'bg-emerald-500';
    },

    /* ---- cart mutations (AJAX) ---- */
    async addProduct(p) {
        if (p.variants && p.variants.length > 0) { this.variantProduct = p; return; }
        await this.mutate('/cart', { product_id: p.id, quantity: '1' }, {}, this.labels.added || 'ဈေးခြင်းထဲသို့ ထည့်သွင်းပြီးပါပြီ။');
    },

    async addVariant(v) {
        const p = this.variantProduct;
        this.variantProduct = null;
        if (!p) return;
        await this.mutate('/cart', { product_id: p.id, product_variant_id: v.id, quantity: '1' }, {}, this.labels.added || 'ဈေးခြင်းထဲသို့ ထည့်သွင်းပြီးပါပြီ။');
    },

    // Weight / length / volume products sell in fractions (2.5 kg, 1.2 m); every
    // other product stays whole-unit so a mistyped 0.5 cannot sell half a phone.
    isLooseWeight(line) {
        return !!(line && line.product && line.product.product_type === 'weight_based');
    },

    minQty(line) {
        return this.isLooseWeight(line) ? 0.001 : 1;
    },

    async changeQty(line, delta) {
        const current = parseFloat(line.quantity) || 0;
        // Sub-unit weights step in tenths so the buttons stay useful.
        const step = this.isLooseWeight(line) && current < 1 ? delta * 0.1 : delta;
        const qty = current + step;
        if (qty <= 0) { await this.removeLine(line); return; }
        await this.mutate('/cart/' + line.index, { quantity: String(Math.round(qty * 1000) / 1000) });
    },

    async setQty(line, qty) {
        const q = parseFloat(qty);
        if (isNaN(q) || q <= 0) { await this.removeLine(line); return; }
        const value = this.isLooseWeight(line) ? Math.round(q * 1000) / 1000 : Math.round(q);
        if (value <= 0) { await this.removeLine(line); return; }
        await this.mutate('/cart/' + line.index, { quantity: String(value) });
    },

    async removeLine(line) {
        await this.mutate('/cart/' + line.index, {}, { method: 'DELETE' }, this.labels.pos_item_removed || 'ဈေးခြင်းထဲမှ ပစ္စည်းကို ဖယ်ရှားပြီးပါပြီ။');
    },

    async clearCart() {
        if (!this.cart.lines.length) return;
        this.pendingWebOrderId = null; // a cleared cart is no longer fulfilling an order
        await this.mutate('/cart/clear', {}, {}, this.labels.cleared || 'ဈေးခြင်းထဲရှိ ပစ္စည်းများကို ရှင်းပြီးပါပြီ။');
    },

    // Per-line price override (negotiation): empty value clears the override
    // and the line returns to the customer-tier price.
    startPriceEdit(line) {
        this.priceEditIndex = line.index;
        this.priceEditValue = line.unit_price;
    },

    async saveLinePrice(line) {
        if (this.cartBusy) return;
        const raw = String(this.priceEditValue ?? '').trim();
        if (raw !== '' && (isNaN(parseFloat(raw)) || parseFloat(raw) < 0)) {
            this.flash(this.labels.pos_price_invalid || 'Invalid price', 'error');
            return;
        }
        const pinMode = this.pricePinIndex === line.index;
        const pin = pinMode ? String(this.pricePinValue ?? '').trim() : '';
        if (pinMode && !pin) {
            this.flash(this.labels.pos_price_pin_required || 'Manager PIN required', 'error');
            return;
        }
        this.priceEditIndex = null;
        this.cartBusy = true;
        try {
            const body = new URLSearchParams({ unit_price: raw });
            if (pin) body.set('manager_pin', pin);
            const data = await this.fetchJson('/cart/' + line.index + '/price', { method: 'POST', body });
            this.pricePinIndex = null;
            this.pricePinValue = '';
            const expired = this.applyCart(data);
            this.flash(expired > 0 ? this.expiredNotice(expired) : (data.success || (raw === '' ? (this.labels.pos_price_cleared || 'Price cleared') : (this.labels.pos_price_set || 'Price updated'))), expired > 0 ? 'error' : 'success');
        } catch (e) {
            if (e.pinRequired) {
                // Deep discount — switch the editor to manager-PIN mode.
                this.pricePinIndex = line.index;
                this.pricePinValue = '';
                this.priceEditIndex = line.index;
                this.flash(e.message, 'error');
            } else {
                this.pricePinIndex = null;
                this.pricePinValue = '';
                this.flash(e.message, 'error');
            }
        } finally {
            this.cartBusy = false;
        }
    },

    // Apply a cart snapshot from the server and return how many stale holds
    // were auto-expired on this read (so callers can surface a notice). The
    // server is the source of truth for the attached customer too (it drives
    // tiered pricing), so the local mirror is synced from cart.customer.
    applyCart(data) {
        if (data.cart) {
            this.cart = data.cart;
            this.customer = data.cart.customer || null;
            // Auto-resolved customer (logged-in storefront shopper): reflect
            // the name in the search box too, so the panel reads naturally.
            if (this.customer && !this.cq) this.cq = this.customer.name;
        }
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
        if (this.cartBusy) return false;
        this.cartBusy = true;
        try {
            const data = await this.fetchJson('/resume/' + id, { method: 'POST', body: new URLSearchParams({}) });
            const expired = this.applyCart(data);
            this.flash(expired > 0 ? this.expiredNotice(expired) : (this.labels.resumed || 'Sale resumed'), expired > 0 ? 'error' : 'success');
            return expired === 0;
        } catch (e) {
            this.flash(e.message, 'error');
            return false;
        } finally {
            this.cartBusy = false;
        }
    },

    async voidHeld(id) {
        if (this.cartBusy) return;
        if (!confirm(this.labels.confirm_void_sale || 'ဆိုင်းငံ့ထားသော ဤအရောင်းစာရင်းကို ပယ်ဖျက်ရန် သေချာပါသလား?')) return;
        this.cartBusy = true;
        try {
            const data = await this.fetchJson('/void/' + id, { method: 'POST', body: new URLSearchParams({}) });
            const expired = this.applyCart(data);
            this.flash(expired > 0 ? this.expiredNotice(expired) : (data.success || this.labels.voided || 'Sale voided'), expired > 0 ? 'error' : 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        } finally {
            this.cartBusy = false;
        }
    },

    async mutate(path, body, options = {}, defaultSuccessMsg = null) {
        if (this.cartBusy) return;
        this.cartBusy = true;
        try {
            const data = await this.fetchJson(path, { method: options.method || 'POST', body: new URLSearchParams(body) });
            const expired = this.applyCart(data);
            if (expired > 0) {
                this.flash(this.expiredNotice(expired), 'error');
            } else {
                const msg = (typeof data.success === 'string' && data.success.trim())
                    ? data.success
                    : defaultSuccessMsg;
                if (msg) {
                    this.flash(msg, 'success');
                }
            }
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
        if (this.shiftsEnabled && !this.shiftOpen) { this.flash(this.labels.shift_required || 'Open a shift first', 'error'); return; }
        if (!this.cart.lines.length) { this.flash(this.labels.pos_cart_empty || 'ဈေးခြင်းထဲတွင် ပစ္စည်းမရှိသေးပါ', 'error'); return; }
        this.mobileCartOpen = false;

        // Reset all payment fields — fresh slate each time the modal opens.
        // Cash = full total; all digital methods and credit start at 0.
        this.activeMethod = 'cash';
        this.cash    = parseFloat(this.cart.totals.total || 0);
        this.kpay    = 0;
        this.wavepay = 0;
        this.cbpay   = 0;
        this.mmqr    = 0;
        this.credit  = 0;

        this.showPayment = true;

        this.$nextTick(() => {
            const input = document.getElementById('pos-active-method-input');
            if (input) { input.focus(); input.select(); }
        });
    },


    /* ---- customer attach (credit/debt) ---- */
    async csearch(forceOpen = false) {
        if (this.isStaffPhone(this.cq)) {
            this.cq = '';
            this.cresults = [];
            this.copen = false;
            return;
        }
        const query = this.cq ? this.cq.trim() : '';
        if (query === '' && !forceOpen) {
            this.cresults = [];
            this.copen = false;
            return;
        }
        try {
            const data = await this.fetchJson('/customers?q=' + encodeURIComponent(query));
            this.cresults = data.customers || [];
            this.copen = true;
        } catch (e) {
            this.cresults = [];
        }
    },

    // Attach a customer server-side: the whole cart re-prices at their tier
    // (wholesale → wholesale prices in grid + cart) and the returned snapshot
    // holds the authoritative customer record + balance.
    async attach(c) {
        this.cresults = [];
        this.copen = false;
        try {
            const data = await this.fetchJson('/customers/' + c.id + '/attach', { method: 'POST', body: new URLSearchParams({}) });
            this.applyCart(data);
            this.cq = '';
            this.loadGrid(); // grid prices follow the attached tier
            this.flash(data.success || this.labels.pos_customer_attached || 'Customer attached', 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        }
    },

    async clearCustomer() {
        this.cq = '';
        this.credit = 0;
        try {
            const data = await this.fetchJson('/customers/detach', { method: 'POST', body: new URLSearchParams({}) });
            this.applyCart(data);
            this.loadGrid();
            this.flash(data.success || this.labels.pos_customer_detached || 'Customer removed', 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        }
    },

    async changeCustomer() {
        this.cq = '';
        await this.clearCustomer();
        this.$nextTick(() => {
            this.csearch(true);
            const ci = document.getElementById('pos-customer-input') || (this.$refs && this.$refs.customerInput);
            if (ci) {
                ci.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                ci.focus();
                ci.select();
            }
        });
    },

    openQuickAdd(query = '') {
        const trimmed = String(query || '').trim();
        if (this.isStaffPhone(trimmed)) {
            this.qname = '';
            this.qphone = '';
            this.quickAddOpen = true;
            this.$nextTick(() => this.$refs.quickName?.focus());
            return;
        }
        // Check if query is likely a phone number (digits only or starts with 09 / +95 / 9)
        const isPhone = /^(\+?95|0?9|\d{6,})/.test(trimmed.replace(/[\s\-]/g, '')) && /^[\d\s\-\+]+$/.test(trimmed);
        if (isPhone) {
            this.qphone = trimmed;
            this.qname = '';
            this.quickAddOpen = true;
            this.$nextTick(() => this.$refs.quickName?.focus());
        } else {
            this.qname = trimmed;
            this.qphone = '';
            this.quickAddOpen = true;
            this.$nextTick(() => (trimmed ? this.$refs.quickPhone?.focus() : this.$refs.quickName?.focus()));
        }
    },

    // Quick-add a customer: POST /pos/customers creates the user + this
    // store's retail/wholesale membership (shared users table, phone dedup and
    // staff-phone guard all live server-side). The server attaches the new
    // customer to the cart immediately, so the cart + grid re-price at their
    // tier right away.
    async quickAdd() {
        if (!this.qname.trim() || !this.qphone.trim() || this.quickBusy) return;
        this.quickBusy = true;
        try {
            const data = await this.fetchJson('/customers', { method: 'POST', body: new URLSearchParams({ name: this.qname.trim(), phone: this.qphone.trim(), type: this.qtype }) });
            this.quickAddOpen = false;
            this.cresults = [];
            this.copen = false;
            this.cq = '';
            this.applyCart(data);
            this.loadGrid();
            this.flash(data.success || this.labels.pos_customer_added || 'Customer added', 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        } finally {
            this.quickBusy = false;
        }
    },

    /* ---- POS Expense Modal ---- */
    openExpenseModal(presetTitle = '') {
        this.expenseTitle = presetTitle;
        this.expenseAmount = '';
        this.expenseCategoryId = '';
        this.expensePaymentMethod = 'cash';
        this.expensePaidTo = '';
        this.expenseNotes = '';
        this.expenseConflict = '';
        this.ensureExpenseKey();
        this.expenseModalOpen = true;
        this.$nextTick(() => {
            if (this.expenseTitle) {
                this.$refs.expenseAmountInput?.focus();
            } else {
                this.$refs.expenseTitleInput?.focus();
            }
        });
    },

    /* ---- Expense retry identity ----
     * A POST that times out may or may not have been written. The browser cannot
     * know, so it resends the SAME client_transaction_id and lets the server
     * replay the row it already stored. The key is held per store + session (so a
     * different cashier logging in on the same terminal never inherits it) and
     * survives a same-tab reload; it is cleared only after the server confirms
     * the write, which is what makes the NEXT expense a new one. */
    expenseKeyScope() {
        return 'datapos.expense.txid.' + this.baseUrl + '.' + this.csrf;
    },

    readStoredExpenseKey() {
        try {
            return window.sessionStorage.getItem(this.expenseKeyScope()) || '';
        } catch (e) {
            return '';
        }
    },

    storeExpenseKey(key) {
        try {
            window.sessionStorage.setItem(this.expenseKeyScope(), key);
        } catch (e) {
            // Private mode / storage disabled: the in-memory key still covers
            // retries within this page.
        }
    },

    clearExpenseKey() {
        try {
            window.sessionStorage.removeItem(this.expenseKeyScope());
        } catch (e) {
            // ignore
        }
    },

    newExpenseKey() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'exp-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);
    },

    ensureExpenseKey() {
        if (this.expenseClientTxId) return this.expenseClientTxId;

        let key = this.readStoredExpenseKey();
        if (!key) {
            key = this.newExpenseKey();
        }
        this.storeExpenseKey(key);
        this.expenseClientTxId = key;

        return key;
    },

    /**
     * Recovery from a 409: the operator changed the values after a submission
     * whose result they never saw. Nothing was written for the new values, so
     * they must consciously start a NEW expense instead of retrying.
     */
    startNewExpenseAfterConflict() {
        this.clearExpenseKey();
        this.expenseClientTxId = '';
        this.expenseConflict = '';
        this.ensureExpenseKey();
        this.flash(this.labels.expense_new_key_ready || 'Ready to record as a new expense', 'info');
    },

    setQuickExpense(title) {
        this.expenseTitle = title;
        this.$nextTick(() => this.$refs.expenseAmountInput?.focus());
    },

    setQuickExpenseAmount(amt) {
        this.expenseAmount = String(amt);
    },

    async submitExpense() {
        if (!this.expenseTitle.trim() || !this.expenseAmount || this.expenseBusy) return;
        const amt = parseFloat(this.expenseAmount);
        if (isNaN(amt) || amt <= 0) {
            this.flash(this.labels.pos_price_invalid || 'Invalid amount', 'error');
            return;
        }

        this.expenseBusy = true;
        try {
            const body = {
                title: this.expenseTitle.trim(),
                amount: String(amt),
                payment_method: this.expensePaymentMethod,
                client_transaction_id: this.ensureExpenseKey(),
            };
            if (this.expenseCategoryId) body.expense_category_id = String(this.expenseCategoryId);
            if (this.expensePaidTo.trim()) body.paid_to = this.expensePaidTo.trim();
            if (this.expenseNotes.trim()) body.notes = this.expenseNotes.trim();

            const data = await this.fetchJson('/expenses', {
                method: 'POST',
                body: new URLSearchParams(body),
            });

            // Confirmed by the server (fresh write or an idempotent replay of the
            // one we already made) — only now is this key spent.
            this.clearExpenseKey();
            this.expenseClientTxId = '';
            this.expenseConflict = '';
            this.expenseModalOpen = false;
            await this.refreshCart();
            this.flash(data.message || this.labels.expense_created_success || 'Expense recorded successfully', 'success');
        } catch (e) {
            if (e.conflict || e.status === 409) {
                this.expenseConflict = e.message;
            }
            this.flash(e.message, 'error');
        } finally {
            this.expenseBusy = false;
        }
    },

    /* ---- Discount Modal ---- */
    openDiscountModal() {
        if (!this.cart.lines || !this.cart.lines.length) {
            this.flash(this.labels.pos_cart_empty || 'ဈေးခြင်းထဲတွင် ပစ္စည်းမရှိသေးပါ', 'error');
            return;
        }
        this.discountType = 'fixed';
        // Prefill from the manual discount alone: the coupon and the points
        // already on the bill are not the cashier's discount to edit, and
        // folding them in here would apply them to the bill a second time.
        this.discountValue = Number(this.cart.totals.manual_discount || 0) > 0 ? String(this.cart.totals.manual_discount) : '';
        this.discountModalOpen = true;
        this.$nextTick(() => {
            const input = document.getElementById('pos-discount-input') || (this.$refs && this.$refs.discountInput);
            if (input) {
                input.focus();
                input.select();
            }
        });
    },

    setQuickDiscountPercent(pct) {
        this.discountType = 'percent';
        this.discountValue = String(pct);
        this.applyDiscount();
    },

    async applyDiscount() {
        if (this.discountBusy) return;
        let discountAmt = 0;
        const raw = parseFloat(this.discountValue || 0);
        if (isNaN(raw) || raw < 0) {
            this.flash(this.labels.pos_price_invalid || 'Invalid discount amount', 'error');
            return;
        }

        const subtotal = parseFloat(this.cart.totals.subtotal || 0);
        if (this.discountType === 'percent') {
            if (raw > 100) {
                this.flash(this.labels.pos_discount_pct_exceeded || 'လျှော့ဈေး ရာခိုင်နှုန်းသည် 100% ထက် မကျော်လွန်ရပါ။', 'error');
                return;
            }
            discountAmt = Math.round((subtotal * (raw / 100)) * 100) / 100;
        } else {
            discountAmt = raw;
        }

        this.discountBusy = true;
        try {
            const data = await this.fetchJson('/cart/discount', {
                method: 'POST',
                body: new URLSearchParams({ discount: String(discountAmt) }),
            });
            this.discountModalOpen = false;
            this.applyCart(data);
            this.flash(data.success || this.labels.pos_discount_applied || 'Discount applied', 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        } finally {
            this.discountBusy = false;
        }
    },

    async clearDiscount() {
        if (this.discountBusy) return;
        this.discountBusy = true;
        try {
            const data = await this.fetchJson('/cart/discount', {
                method: 'POST',
                body: new URLSearchParams({ discount: '0' }),
            });
            this.discountModalOpen = false;
            this.discountValue = '';
            this.applyCart(data);
            this.flash(data.success || this.labels.pos_discount_cleared || 'Discount cleared', 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        } finally {
            this.discountBusy = false;
        }
    },

    /**
     * Attach a coupon to the cart.
     *
     * The server validates the code against the current cart (active, in date,
     * limits, minimum order) and stores it with the cart, so the totals it
     * returns already include the discount — the same numbers the sale will be
     * posted with. A refusal carries the reason in the cashier's language.
     */
    async applyCoupon() {
        if (this.couponBusy) return;
        const code = String(this.couponCode || '').trim();
        if (!code) {
            this.couponMessage = this.labels.pos_coupon_placeholder || 'Enter the coupon code';
            this.couponValid = false;
            return;
        }

        this.couponBusy = true;
        try {
            const data = await this.fetchJson('/cart/coupon', {
                method: 'POST',
                body: new URLSearchParams({ code }),
            });
            this.applyCart(data);
            this.couponMessage = data.success || '';
            this.couponValid = true;
            this.flash(data.success || '', 'success');
        } catch (e) {
            this.couponMessage = e.message;
            this.couponValid = false;
            this.flash(e.message, 'error');
        } finally {
            this.couponBusy = false;
        }
    },

    /**
     * Spend loyalty points on this cart. The server clamps the request to the
     * customer's balance and to what is left on the bill, so the value shown is
     * always the value that will be charged.
     */
    async applyPoints(points) {
        if (this.pointsBusy) return;
        this.pointsBusy = true;
        try {
            const value = (points === null || points === undefined || points === '') ? null : parseInt(points, 10);
            const body = new URLSearchParams();
            if (value !== null && !isNaN(value)) body.set('points', String(value));
            const data = await this.fetchJson('/cart/points', { method: 'POST', body });
            this.applyCart(data);
            this.pointsInput = '';
            this.flash(data.success || '', 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        } finally {
            this.pointsBusy = false;
        }
    },

    async clearCoupon() {
        if (this.couponBusy) return;
        this.couponBusy = true;
        try {
            const data = await this.fetchJson('/cart/coupon', {
                method: 'POST',
                body: new URLSearchParams({ code: '' }),
            });
            this.applyCart(data);
            this.couponCode = '';
            this.couponMessage = data.success || '';
            this.couponValid = false;
            this.flash(data.success || '', 'success');
        } catch (e) {
            this.flash(e.message, 'error');
        } finally {
            this.couponBusy = false;
        }
    },

    /* ---- payment math ---- */
    get paid() {
        // Only actual payment methods received (cash + digital wallets); credit is debt / receivable
        return ['cash', 'kpay', 'wavepay', 'cbpay', 'mmqr'].reduce((s, k) => s + (parseFloat(this[k]) || 0), 0);
    },
    get totalSettled() {
        return this.paid + (parseFloat(this.credit) || 0);
    },
    get remaining() { return parseFloat(this.cart.totals.total || 0) - this.totalSettled; },
    get change() { return this.remaining < 0 ? -this.remaining : 0; },
    get shiftOpen() {
        if (!this.shiftsEnabled) return true;
        if (this.cart && typeof this.cart.shifts_enabled !== 'undefined' && !this.cart.shifts_enabled) return true;
        return !!this.cart.shift_open;
    },
    get exact() {
        if (this.credit > 0 && !this.customer) return false;
        return this.remaining <= 0.005;
    },

    /* ---- payment modal UI helpers (live in posApp scope to avoid child-scope shadowing) ---- */
    get activeKey() {
        // Map tile id → Alpine state key
        const map = { cash: 'cash', kpay: 'kpay', wavepay: 'wavepay', cb_pay: 'cbpay', mmqr: 'mmqr', credit: 'credit' };
        return map[this.activeMethod] || this.activeMethod;
    },

    getActiveAmount() {
        const k = this.activeKey;
        return parseFloat(this[k]) || 0;
    },

    setActiveAmount(val) {
        const k = this.activeKey;
        this[k] = Math.max(0, parseFloat(val) || 0);
    },

    // Numpad digit append for the currently active payment method
    padActive(val) {
        const k = this.activeKey;
        let s = String(Math.round(parseFloat(this[k]) || 0));
        if (val === 'C')   { this[k] = 0; return; }
        if (val === '←' || val === '\u2190') { s = s.slice(0, -1) || '0'; this[k] = parseInt(s, 10); return; }
        if (val === '00')  { this[k] = parseInt(s + '00', 10); return; }
        if (val === '000') { this[k] = parseInt(s + '000', 10); return; }
        this[k] = parseInt(s === '0' ? val : s + val, 10);
    },

    // Set the active method's amount to whatever is still owed
    setPaymentRemaining() {
        const k = this.activeKey;
        const others = ['cash', 'kpay', 'wavepay', 'cbpay', 'mmqr', 'credit'].filter(x => x !== k);
        const otherSum = others.reduce((s, x) => s + (parseFloat(this[x]) || 0), 0);
        const rem = Math.max(0, parseFloat(this.cart.totals.total || 0) - otherSum);
        this[k] = Math.round(rem);
    },

    // Zero out one payment method
    clearPaymentMethod(key) {
        const map = { cash: 'cash', kpay: 'kpay', wavepay: 'wavepay', cb_pay: 'cbpay', cbpay: 'cbpay', mmqr: 'mmqr', credit: 'credit' };
        const k = map[key] || key;
        this[k] = 0;
    },

    // Switch active payment tile
    switchPaymentMethod(mid) {
        if (mid === 'credit' && !this.customer) {
            this.flash(this.labels.credit_requires_customer || 'Customer required for credit', 'error');
            return;
        }
        const total = parseFloat(this.cart.totals.total || 0);
        const map = { cash: 'cash', kpay: 'kpay', wavepay: 'wavepay', cb_pay: 'cbpay', mmqr: 'mmqr', credit: 'credit' };
        const newKey = map[mid] || mid;

        // If currently only cash is filled with the full total (untouched default single-payment):
        // Automatically transfer the full amount to the newly selected method for 1-click single payment.
        const others = ['kpay', 'wavepay', 'cbpay', 'mmqr', 'credit'];
        const otherSum = others.reduce((s, x) => s + (parseFloat(this[x]) || 0), 0);
        if (this.activeMethod === 'cash' && parseFloat(this.cash) === total && otherSum === 0 && mid !== 'cash') {
            this.cash = 0;
            this[newKey] = total;
        }

        this.activeMethod = mid;
        this.$nextTick(() => {
            const input = document.getElementById('pos-active-method-input');
            if (input) { input.focus(); input.select(); }
        });
    },

    // Amount already entered for a given method key (for tile badges)
    amtFor(key) {
        const map = { cash: 'cash', kpay: 'kpay', wavepay: 'wavepay', cb_pay: 'cbpay', cbpay: 'cbpay', mmqr: 'mmqr', credit: 'credit' };
        const k = map[key] || key;
        return parseFloat(this[k]) || 0;
    },

    destroy() {
        document.removeEventListener('visibilitychange', this._barcodeVisibility);
        this.barcodeScannerOpen = false;
        this.stopCameraScanner();
    },

    /* ---- Camera Barcode Scanner ---- */
    playBeep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(1800, ctx.currentTime);
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.12);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.onended = () => { ctx.close().catch(() => {}); };
            osc.stop(ctx.currentTime + 0.12);
        } catch (e) {}
        if (navigator.vibrate) {
            try { navigator.vibrate(100); } catch (e) {}
        }
    },

    async openBarcodeScanner() {
        this.barcodeScannerOpen = true;
        this.barcodeCameraError = '';
        this.barcodeCameraInsecure = false;
        this.barcodeLoading = true;
        this.$nextTick(async () => {
            await this.startCameraScanner();
        });
    },

    async closeBarcodeScanner() {
        this.barcodeScannerOpen = false;
        await this.stopCameraScanner();
    },

    async startCameraScanner() {
        this.barcodeCameraError = '';
        this.barcodeCameraInsecure = false;
        this.barcodeLoading = true;

        // Stop any existing stream first
        const stopping = this.stopCameraScanner();
        const session = this._barcodeSession;
        await stopping;
        const active = () => this.barcodeScannerOpen && session === this._barcodeSession;
        if (!active()) return;
        this.barcodeLoading = true;

        const videoEl = document.getElementById('pos-barcode-video');
        if (!videoEl) {
            this.barcodeLoading = false;
            return;
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            this.barcodeLoading = false;
            this.barcodeCameraInsecure = true;
            this.barcodeCameraError = this.labels.pos_camera_insecure_http || 'ကင်မရာ API မရှိပါ (HTTPS လိုအပ်ပါသည်)';
            return;
        }

        try {
            // Request the camera stream directly — no third-party lib
            const constraints = {
                video: {
                    facingMode: { ideal: this.barcodeFacingMode },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            };

            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            if (!active()) { stream.getTracks().forEach(t => t.stop()); return; }
            this._cameraStream = stream;
            videoEl.srcObject = stream;
            await videoEl.play();
            if (!active()) return;

            this.barcodeLoading = false;

            // Start decoding loop
            this._scanLoop = true;
            this._decodingLoop(videoEl, session).catch(err => {
                if (active()) { this.barcodeCameraError = err.message; this.stopCameraScanner(); }
            });
        } catch (err) {
            if (!active()) return;
            if (this._cameraStream) { this._cameraStream.getTracks().forEach(t => t.stop()); this._cameraStream = null; videoEl.srcObject = null; }
            console.warn('[POS Camera] getUserMedia error:', err.name, err.message);
            this.barcodeLoading = false;
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                this.barcodeCameraError = this.labels.pos_camera_permission_denied || 'ကင်မရာ ခွင့်ပြုချက် မရရှိပါ — Browser Site Settings တွင် Camera ကို Allow ပြောင်းပေးပါ';
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                this.barcodeCameraError = 'ကင်မရာ ရှာမတွေ့ပါ (Camera မပါသည့် Device ဖြစ်နိုင်သည်)';
            } else if (err.name === 'OverconstrainedError') {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                    if (!active()) { stream.getTracks().forEach(t => t.stop()); return; }
                    this._cameraStream = stream;
                    videoEl.srcObject = stream;
                    await videoEl.play();
                    if (!active()) return;
                    this.barcodeLoading = false;
                    this._scanLoop = true;
                    this._decodingLoop(videoEl, session).catch(error => {
                        if (active()) { this.barcodeCameraError = error.message; this.stopCameraScanner(); }
                    });
                } catch (error) {
                    if (active()) {
                        await this.stopCameraScanner();
                        this.barcodeCameraError = error.message;
                    }
                }
            } else {
                this.barcodeCameraError = err.message || 'ကင်မရာ ဖွင့်မရပါ';
            }
        }
    },

    async _decodingLoop(videoEl, session) {
        const active = () => this._scanLoop && this.barcodeScannerOpen && session === this._barcodeSession;
        let detector = null;
        try {
            if (typeof BarcodeDetector !== 'undefined') {
                const formats = await BarcodeDetector.getSupportedFormats();
                // Native implementations may support QR only; use ZXing for retail barcodes.
                if (formats.includes('code_128') && formats.includes('ean_13')) {
                    detector = new BarcodeDetector({ formats });
                }
            }
        } catch (_) { /* Use the bundled decoder when native initialization fails. */ }
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        let scanner = null;
        let offEl = null;
        try {
            while (active()) {
                if (videoEl.readyState >= 2 && !videoEl.paused) {
                    let code = null;
                    if (detector) {
                        try { code = (await detector.detect(videoEl))[0]?.rawValue; }
                        catch (_) { detector = null; }
                    }
                    if (!detector && active() && ctx && videoEl.videoWidth && videoEl.videoHeight) {
                        if (!scanner) {
                            offEl = document.createElement('div');
                            offEl.id = 'pos-barcode-offscreen-' + session;
                            offEl.style.cssText = 'position:fixed;left:-9999px;width:1280px;height:720px;';
                            document.body.appendChild(offEl);
                            scanner = new window.Html5Qrcode(offEl.id, { verbose: false });
                        }
                        canvas.width = videoEl.videoWidth;
                        canvas.height = videoEl.videoHeight;
                        ctx.drawImage(videoEl, 0, 0);
                        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.9));
                        if (blob && active()) {
                            try { code = await scanner.scanFile(new File([blob], 'frame.jpg', { type: 'image/jpeg' }), false); }
                            catch (_) { /* No barcode in this frame. */ }
                        }
                    }
                    if (active() && code) await this.onBarcodeDetected(code, session);
                }
                // Await every decode and lookup; frames and cart requests cannot overlap.
                if (active()) await new Promise(resolve => setTimeout(resolve, detector ? 150 : 500));
            }
        } finally {
            try { scanner?.clear(); } catch (_) {}
            offEl?.remove();
        }
    },

    async stopCameraScanner() {
        this._barcodeSession = (this._barcodeSession || 0) + 1;
        this._scanLoop = false;

        if (this._cameraStream) {
            try {
                this._cameraStream.getTracks().forEach(t => t.stop());
            } catch (e) {}
            this._cameraStream = null;
        }

        const videoEl = document.getElementById('pos-barcode-video');
        if (videoEl) {
            videoEl.srcObject = null;
        }

        if (this._fallbackScanner) {
            try { this._fallbackScanner.clear(); } catch (e) {}
            this._fallbackScanner = null;
        }

        // Also clean up old Html5Qrcode instance if present
        if (this.barcodeScannerInstance) {
            try {
                if (this.barcodeScannerInstance.isScanning) {
                    await this.barcodeScannerInstance.stop();
                }
                this.barcodeScannerInstance.clear();
            } catch (e) {}
            this.barcodeScannerInstance = null;
        }

        this.barcodeTorchOn = false;
        this.barcodeLoading = false;
    },

    async toggleCameraFacing() {
        this.barcodeFacingMode = this.barcodeFacingMode === 'environment' ? 'user' : 'environment';
        this.barcodeLoading = true;
        await this.startCameraScanner();
    },

    async toggleTorch() {
        if (!this._cameraStream) return;
        try {
            const [track] = this._cameraStream.getVideoTracks();
            if (!track) return;
            if (!track.getCapabilities?.().torch) return;
            const enabled = !this.barcodeTorchOn;
            await track.applyConstraints({ advanced: [{ torch: enabled }] });
            this.barcodeTorchOn = enabled;
        } catch (e) {
            console.warn('[POS Torch] Not supported:', e);
            this.barcodeTorchOn = false;
        }
    },

    async onBarcodeDetected(code, session = this._barcodeSession) {
        const cleanCode = String(code || '').trim();
        if (!cleanCode || cleanCode.length > 120 || this.barcodeCooldown || this.cartBusy) return;
        const active = () => this.barcodeScannerOpen && session === this._barcodeSession;
        if (!active()) return;
        this.barcodeCooldown = true;
        try {
            const data = await this.fetchJson('/products-grid?exact_code=1&q=' + encodeURIComponent(cleanCode));
            if (!active()) return;
            const products = data.products || [];
            if (products.length !== 1) {
                this.flash((this.labels.pos_barcode_not_found || 'No unique product found for barcode') + ': ' + cleanCode, 'warning');
                return;
            }
            const matched = products[0];
            const variants = matched.variants || [];
            const exactVariant = variants.find(v => v.sku === cleanCode);
            if (variants.length && !exactVariant) {
                await this.closeBarcodeScanner();
                this.variantProduct = matched;
                return;
            }
            // addVariant depends on a modal selection; scanned variants have their own product context.
            const added = await this.mutate('/cart', {
                product_id: matched.id,
                ...(exactVariant ? { product_variant_id: exactVariant.id } : {}),
                quantity: '1',
            }, {}, this.labels.added);
            if (!added || !active()) return;
            this.barcodeLastScanned = cleanCode;
            this.barcodeLastScannedName = matched.name + (exactVariant ? ' (' + exactVariant.name + ')' : '');
            this.playBeep();
            if (!this.barcodeContinuous) await this.closeBarcodeScanner();
        } catch (err) {
            if (active()) this.flash(err.message, 'error');
        } finally {
            // Keep the lock throughout slow network requests, then debounce the next frame.
            await new Promise(resolve => setTimeout(resolve, 1800));
            this.barcodeCooldown = false;
        }
    },

    /* ---- feedback ---- */
    flash(msg, type) {
        this.notice = msg;
        this.noticeType = type;
        clearTimeout(this.noticeTimer);
        this.noticeTimer = setTimeout(() => { this.notice = ''; }, 3500);
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: type || 'info', message: msg } }));
    },

    /* ---- keyboard shortcuts ---- */
    shortcut(e) {
        const k = e.key ? e.key.toUpperCase() : '';

        // Global Escape handling
        if (k === 'ESCAPE') {
            if (this.barcodeScannerOpen) { this.closeBarcodeScanner(); e.preventDefault(); return; }
            if (this.discountModalOpen) { this.discountModalOpen = false; e.preventDefault(); return; }
            if (this.showPayment) { this.showPayment = false; e.preventDefault(); return; }
            if (this.variantProduct) { this.variantProduct = null; e.preventDefault(); return; }
            if (this.copen) { this.copen = false; e.preventDefault(); return; }
            if (this.quickAddOpen) { this.quickAddOpen = false; e.preventDefault(); return; }
            if (this.expenseModalOpen) { this.expenseModalOpen = false; e.preventDefault(); return; }
            if (this.webOrdersOpen) { this.webOrdersOpen = false; e.preventDefault(); return; }
            if (this.mobileCartOpen) { this.mobileCartOpen = false; e.preventDefault(); return; }
            if (this.mobileSearchOpen && !this.q) {
                this.mobileSearchOpen = false;
                e.preventDefault();
                return;
            }
            const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
            if (this.q && activeTag !== 'input' && activeTag !== 'textarea') {
                this.q = '';
                this.mobileSearchOpen = false;
                this.loadGrid();
                e.preventDefault();
                return;
            }
            return;
        }

        // Only handle function keys F1-F12
        if (!k.startsWith('F') || k.length < 2) return;

        const actions = {
            F1: () => {
                e.preventDefault();
                if (this.showPayment) this.showPayment = false;
                if (this.variantProduct) this.variantProduct = null;
                this.mobileSearchOpen = true;
                this.$nextTick(() => {
                    const desktopInput = document.getElementById('pos-search-input');
                    const mobileInput = document.getElementById('pos-mobile-search-input');
                    const input = (window.innerWidth >= 1024 && desktopInput && desktopInput.offsetParent !== null)
                        ? desktopInput
                        : (mobileInput && mobileInput.offsetParent !== null ? mobileInput : desktopInput);
                    if (input) {
                        input.focus();
                        input.select();
                    }
                });
            },
            F2: () => {
                e.preventDefault();
                this.openPayment();
            },
            F3: () => {
                e.preventDefault();
                if (window.innerWidth < 1024) {
                    this.mobileCartOpen = true;
                }
                if (this.customer) {
                    this.changeCustomer();
                } else {
                    this.csearch(true);
                    this.$nextTick(() => {
                        const ci = document.getElementById('pos-customer-input') || (this.$refs && this.$refs.customerInput);
                        if (ci) {
                            ci.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            ci.focus();
                            ci.select();
                        }
                    });
                }
            },
            F4: () => {
                e.preventDefault();
                if (!this.cart.lines || !this.cart.lines.length) {
                    this.flash(this.labels.pos_cart_empty || 'ဈေးခြင်းထဲတွင် ပစ္စည်းမရှိသေးပါ', 'error');
                    return;
                }
                if (confirm(this.labels.confirm_clear_cart || 'ဈေးခြင်းထဲရှိ ပစ္စည်းအားလုံးကို ရှင်းလင်းရန် သေချာပါသလား?')) {
                    this.clearCart();
                }
            },
            F5: () => {
                e.preventDefault();
                if (e.ctrlKey) {
                    // Ctrl+F5 = Hard cache-bust reload
                    if ('caches' in window) {
                        try { caches.keys().then(keys => Promise.all(keys.map(name => caches.delete(name)))); } catch (err) {}
                    }
                    const u = new URL(window.location.href);
                    u.searchParams.set('_r', Date.now().toString());
                    window.location.replace(u.toString());
                } else {
                    // Normal F5 = Fast in-place AJAX reload without page reload
                    this.reloadPos();
                }
            },
            F6: () => {
                e.preventDefault();
                if (!this.cart.lines || !this.cart.lines.length) {
                    this.flash(this.labels.pos_cart_empty || 'ဆိုင်းငံ့ရန် ဈေးခြင်းထဲတွင် ပစ္စည်းမရှိသေးပါ', 'error');
                    return;
                }
                this.hold();
            },
            F7: () => {
                e.preventDefault();
                if (!this.cart.held || !this.cart.held.length) {
                    this.flash(this.labels.no_held_sales || 'ဆိုင်းငံ့ထားသော အရောင်းများ မရှိသေးပါ', 'info');
                    return;
                }
                this.mobileCartOpen = false;
                this.$nextTick(() => {
                    const el = document.getElementById('pos-held-section');
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            },
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

/* ---- Drag-to-scroll for horizontal chip rows (mouse on desktop) ----
   The category/brand/module rows scroll sideways with a hidden scrollbar;
   on touch devices native touch-scroll works, but a desktop mouse has no
   way to move them — this makes the rows draggable (grab → drag) and
   swallows the stray click a drag can leave on a chip.

   IMPORTANT: no pointer capture. Capturing on pointerdown retargets the
   browser's click event to the row container, which breaks every chip
   click. Instead we observe with window-level listeners and only engage
   the drag (cursor, snap off, click-suppression) after the pointer has
   actually moved beyond a threshold — a plain click is untouched. */
Alpine.data('dragScroll', () => ({
    el: null,
    dragging: false,
    started: false,   // drag actually engaged (moved past threshold)
    startX: 0,
    startLeft: 0,
    targetLeft: null, // pending scroll position (written once per frame)
    raf: null,
    suppressClick: false,
    onMove: null,
    onUp: null,

    down(el, e) {
        if (e.pointerType !== 'mouse') return; // touch/pen scroll natively
        this.el = el;
        this.dragging = true;
        this.started = false;
        this.startX = e.clientX;
        this.startLeft = el.scrollLeft;
        this.onMove = (ev) => this.move(ev);
        this.onUp = (ev) => this.up(ev);
        window.addEventListener('pointermove', this.onMove);
        window.addEventListener('pointerup', this.onUp);
        window.addEventListener('pointercancel', this.onUp);
    },

    move(e) {
        if (!this.dragging || !this.el) return;
        const dx = e.clientX - this.startX;
        if (!this.started) {
            if (Math.abs(dx) <= 6) return;
            // Past the threshold: engage the drag. Kill scroll-snap AND the
            // row's `scroll-smooth` (scroll-behavior: smooth) — both fight
            // direct scrollLeft writes and make the drag feel jerky.
            this.started = true;
            const el = this.el;
            el.style.scrollSnapType = 'none';
            el.style.scrollBehavior = 'auto';
            el.style.cursor = 'grabbing';
            el.classList.add('select-none');
        }
        // Coalesce to one scrollLeft write per animation frame — mouse
        // pointermove can fire faster than the screen refreshes, and each
        // intermediate write just queues work.
        this.targetLeft = this.startLeft - dx;
        if (this.raf) return;
        this.raf = requestAnimationFrame(() => {
            this.raf = null;
            if (this.el && this.targetLeft !== null) {
                this.el.scrollLeft = this.targetLeft;
            }
        });
    },

    up() {
        if (!this.dragging) return;
        this.dragging = false;
        const el = this.el;
        this.el = null;
        if (this.raf) {
            cancelAnimationFrame(this.raf);
            this.raf = null;
        }
        window.removeEventListener('pointermove', this.onMove);
        window.removeEventListener('pointerup', this.onUp);
        window.removeEventListener('pointercancel', this.onUp);
        if (el && this.started) {
            el.style.scrollSnapType = '';
            el.style.scrollBehavior = '';
            el.style.cursor = '';
            el.classList.remove('select-none');
            // A drag is not a click — swallow the click fired on release so
            // the chip the mouse happened to land on is not activated.
            this.suppressClick = true;
            setTimeout(() => { this.suppressClick = false; }, 100);
        }
    },

    onClick(e) {
        if (this.suppressClick) {
            e.preventDefault();
            e.stopPropagation();
        }
    },
}));

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
