import './bootstrap';
import './csp-helpers';
import Alpine from 'alpinejs';
import {
    normalizeMyanmarPhoneNumber,
    buildViberChatUrl,
    buildProductInquiryMessage,
    buildOrderMessage,
    copyToClipboard,
    copyMessageAndOpenViber,
    registerViberModalStore,
} from './viber-order';

window.Alpine = Alpine;

// Register the reactive viberModal Alpine store before Alpine starts components.
registerViberModalStore();

/* ---- Viber order/inquiry helpers (mirror App\Support\ContactLinkBuilder) ----
   Exposed globally so the product-detail Alpine component builds identical
   messages/URLs to the server-rendered no-JS fallbacks. ---- */
window.alinnViber = {
    normalizeMyanmarPhoneNumber,
    buildViberChatUrl,
    buildProductInquiryMessage,
    buildOrderMessage,
    copyToClipboard,
    copyMessageAndOpenViber,
};

/* ---- brandHue: deterministic hue (0-359) for a brand name, used to paint
   placeholder tiles on the favorites page when an item has no photo. ---- */
window.brandHue = function (name) {
    const s = String(name || 'G');
    let sum = 0;
    for (let i = 0; i < s.length; i++) sum += (s.charCodeAt(i) * (i + 3)) % 360;
    return sum % 360;
};

/* ---- begin legacy storage migration ---- */
// One-time backward-compatible migration from the legacy DataPOS storage
// keys to the ACDC Mobile keys. Runs before the stores read their data so
// existing browser carts/favorites survive the rename.
(function migrateLegacyStorageKeys() {
    const pairs = [
        ['datapos_order_list', 'acdc_mobile_order_list'],
        ['datapos_fav_ids', 'acdc_mobile_fav_ids'],
        ['datapos_fav_items', 'acdc_mobile_fav_items'],
    ];

    pairs.forEach(function (pair) {
        const oldKey = pair[0];
        const newKey = pair[1];

        // Never overwrite data already written under the new key.
        if (localStorage.getItem(newKey) !== null) return;

        const raw = localStorage.getItem(oldKey);
        if (raw === null) return; // nothing to migrate

        try {
            const value = JSON.parse(raw);
            // Only valid JSON arrays are kept; anything else is corrupt.
            if (!Array.isArray(value)) {
                localStorage.removeItem(oldKey);
                return;
            }
            localStorage.setItem(newKey, raw);
            // Remove the old key only after the copy succeeded.
            localStorage.removeItem(oldKey);
        } catch (e) {
            // Invalid JSON: drop the broken payload so it cannot crash the app.
            localStorage.removeItem(oldKey);
        }
    });
})();
/* ---- end legacy storage migration ---- */

// 1. OrderBuilder Store
Alpine.store('orderBuilder', {
    items: JSON.parse(localStorage.getItem('acdc_mobile_order_list') || '[]'),

    save() {
        localStorage.setItem('acdc_mobile_order_list', JSON.stringify(this.items));
        this.items = [...this.items];
    },

    addItem(product) {
        if (!product) return;

        let productId = product.product_id || (typeof product.id === 'number' ? product.id : null);
        let variantId = product.product_variant_id || product.variant_id || null;
        let glassFinderItemId = product.glass_finder_item_id || null;
        let itemKey = glassFinderItemId
            ? 'glass_' + glassFinderItemId
            : (variantId ? 'product_' + productId + '_variant_' + variantId : 'product_' + productId);

        let existing = this.items.find(i => {
            if (glassFinderItemId && i.glass_finder_item_id === glassFinderItemId) return true;
            if (variantId && i.product_id === productId && (i.product_variant_id === variantId || i.variant_id === variantId)) return true;
            if (!variantId && productId && !i.product_variant_id && !i.variant_id && (i.product_id === productId || i.id === productId || i.id === itemKey)) return true;
            return false;
        });

        if (existing) {
            existing.quantity += 1;
        } else {
            this.items.push({
                id: itemKey || product.id,
                product_id: productId,
                product_variant_id: variantId,
                variant_id: variantId,
                glass_finder_item_id: glassFinderItemId,
                name: product.name,
                price: parseFloat(product.price || 0),
                quantity: 1,
                sku: product.sku || '',
                image_path: product.image_path || ''
            });
        }
        this.save();
    },

    addGlassCodeItem(code, modelsText, glassItemId) {
        if (!code) return;
        let idKey = 'code_' + code;
        let existing = this.items.find(i => i.id === idKey || i.sku === code);
        if (existing) {
            existing.quantity += 1;
        } else {
            this.items.push({
                id: idKey,
                product_id: null,
                glass_finder_item_id: glassItemId || null,
                // modelsText may be a bare model list (glass finder) or a full
                // name already prefixed with "Glass Code:" (favorites page)
                name: modelsText && modelsText.indexOf('Glass Code:') !== 0
                    ? 'Glass Code: ' + code + ' (' + modelsText + ')'
                    : (modelsText || 'Glass Code: ' + code),
                price: 0,
                quantity: 1,
                sku: code,
                image_path: ''
            });
        }
        this.save();
    },

    getCodeQty(code) {
        if (!code) return 0;
        let item = (this.items || []).find(i => i.sku === code || i.id === ('code_' + code));
        return item ? item.quantity : 0;
    },

    getItemQty(productId) {
        if (!productId) return 0;
        return (this.items || [])
            .filter(i => i.product_id === productId || i.id === productId || i.id === ('product_' + productId))
            .reduce((sum, i) => sum + (parseInt(i.quantity) || 0), 0);
    },

    getVariantQty(productId, variantId) {
        if (!productId || !variantId) return this.getItemQty(productId);
        let item = (this.items || []).find(i => i.product_id === productId && (i.product_variant_id === variantId || i.variant_id === variantId));
        return item ? item.quantity : 0;
    },

    getGlassItemQty(glassItemId) {
        if (!glassItemId) return 0;
        let item = (this.items || []).find(i => i.glass_finder_item_id === glassItemId);
        return item ? item.quantity : 0;
    },

    removeItem(targetId) {
        this.items = this.items.filter(i => i.product_id !== targetId && i.id !== targetId && i.glass_finder_item_id !== targetId && i.sku !== targetId);
        this.save();
    },

    updateQty(targetId, delta) {
        let item = this.items.find(i => i.product_id === targetId || i.id === targetId || i.glass_finder_item_id === targetId || i.sku === targetId);
        if (item) {
            item.quantity += delta;
            if (item.quantity <= 0) {
                this.removeItem(targetId);
            } else {
                this.save();
            }
        }
    },

    clear() {
        this.items = [];
        localStorage.removeItem('acdc_mobile_order_list');
    },

    get totalCount() {
        return (this.items || []).reduce((sum, i) => sum + (parseInt(i.quantity) || 0), 0);
    },

    get totalAmount() {
        return (this.items || []).reduce((sum, i) => sum + ((parseFloat(i.price) || 0) * (parseInt(i.quantity) || 0)), 0);
    }
});

// 2. Favorites Store
Alpine.store('favoritesStore', {
    ids: JSON.parse(localStorage.getItem('acdc_mobile_fav_ids') || '[]'),
    items: JSON.parse(localStorage.getItem('acdc_mobile_fav_items') || '[]'),

    save() {
        localStorage.setItem('acdc_mobile_fav_ids', JSON.stringify(this.ids));
        localStorage.setItem('acdc_mobile_fav_items', JSON.stringify(this.items));
        this.ids = [...this.ids];
        this.items = [...this.items];
    },

    isFav(targetId) {
        if (!targetId) return false;
        return this.ids.includes(targetId) || this.ids.includes(String(targetId));
    },

    async toggle(item) {
        if (!item || !item.id) return;

        let id = item.id;
        const wasFav = this.isFav(id);
        if (wasFav) {
            this.ids = this.ids.filter(i => i !== id && i !== String(id));
            this.items = this.items.filter(i => i.id !== id && i.id !== String(id));
        } else {
            this.ids.push(id);
            this.items.push({
                id: id,
                product_id: item.product_id || (typeof id === 'number' ? id : null),
                glass_finder_item_id: item.glass_finder_item_id || null,
                brand: item.brand || 'General',
                name: item.name || item.phone_model || 'Product',
                price: parseFloat(item.price || 0),
                sku: item.sku || item.glass_code || '',
                image_path: item.image_path || '',
                glass_code: item.glass_code || '',
                url: item.url || ''
            });
        }
        this.save();

        // Server-side persistence exists only for glass-finder items
        // (glass_favorites table). Plain product favorites are stored locally
        // in the browser; the glass endpoint validates against
        // glass_finder_items, so product ids must not be sent to it.
        if (item.glass_finder_item_id) {
            try {
                let csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                await fetch('/glass-finder/favorite', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        glass_finder_item_id: item.glass_finder_item_id,
                        // Send the intent explicitly so the server honors add vs
                        // remove. A blind toggle would let a guest's "unfavorite"
                        // (no server row yet) create a ghost favorite after login.
                        action: wasFav ? 'remove' : 'add'
                    })
                });
            } catch (e) {
                // The server remains the source of truth; local favorites are
                // intentionally kept when a background sync is unavailable.
            }
        }
    },

    removeItem(targetId, skipServerSync = false) {
        // Capture the item before it is removed so glass favorites can be
        // synced to the server (otherwise a server row would linger).
        const removed = this.items.find(i => i.id === targetId || i.id === String(targetId));
        this.ids = this.ids.filter(i => i !== targetId && i !== String(targetId));
        this.items = this.items.filter(i => i.id !== targetId && i.id !== String(targetId));
        this.save();

        if (!skipServerSync && removed && removed.glass_finder_item_id) {
            this.removeServerItem(removed.glass_finder_item_id);
        }
    },

    // Remove a server-side glass favorite (used by the account favorites
    // page for the cloud-synced section, and by removeItem above).
    async removeServerItem(glassItemId, el = null) {
        if (!glassItemId) return;
        try {
            let csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/glass-finder/favorite', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ glass_finder_item_id: glassItemId, action: 'remove' })
            });
            if (res.ok) {
                // Drop the local copy too; the server is already updated.
                this.removeItem('glass_' + glassItemId, true);
                if (el && el.closest) el.closest('.cloud-fav-row')?.remove();
            }
        } catch (e) {
            // Best-effort sync; leave the row so the user can retry.
        }
    },

    get count() {
        return (this.ids || []).length;
    }
});

/* ---- Flash-sale countdown: ticks every second to the given UTC epoch (ms) ---- */
Alpine.data('flashTimer', (targetMs) => ({
    days: 0,
    hours: 0,
    minutes: 0,
    seconds: 0,
    expired: false,
    interval: null,

    init() {
        this.tick();
        this.interval = setInterval(() => this.tick(), 1000);
    },

    tick() {
        const diff = Math.max(0, targetMs - Date.now());
        if (diff === 0) {
            this.expired = true;
            clearInterval(this.interval);
            // One soft reload when the moment passes so the server re-renders
            // the deals with their new state (sale ended / started).
            if (! window.__flashReloaded) {
                window.__flashReloaded = true;
                setTimeout(() => window.location.reload(), 1500);
            }
            return;
        }
        this.days = Math.floor(diff / 86400000);
        this.hours = Math.floor(diff / 3600000) % 24;
        this.minutes = Math.floor(diff / 60000) % 60;
        this.seconds = Math.floor(diff / 1000) % 60;
    },

    destroy() {
        clearInterval(this.interval);
    },
}));

/* ---- Live search suggestions (mobile search bar + desktop search overlay) ---- */
Alpine.data('searchSuggestions', (storeSlug, endpoint, labels) => ({
    query: '',
    categories: [],
    brands: [],
    products: [],
    trending: [],
    open: false,
    loading: false,
    activeIndex: -1,
    total: 0,
    timer: null,
    controller: null,
    storeSlug,
    endpoint,
    labels,

    init() {
        // Preload trending chips so they render instantly when the search box
        // is focused (the dropdown itself stays closed until focus/typing).
        this.fetchTrending(false);
    },

    async fetchSuggestions() {
        if (this.controller) this.controller.abort();
        this.controller = new AbortController();
        this.loading = true;
        try {
            const params = new URLSearchParams({ search: this.query.trim() });
            if (this.storeSlug) params.set('store_slug', this.storeSlug);
            const res = await fetch(this.endpoint + '?' + params.toString(), { signal: this.controller.signal, headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.categories = Array.isArray(data.categories) ? data.categories : [];
            this.brands = Array.isArray(data.brands) ? data.brands : [];
            this.products = Array.isArray(data.products) ? data.products : [];
            this.trending = Array.isArray(data.trending) ? data.trending : [];
            // Flat keyboard-navigation indexes across all three sections.
            let n = 0;
            this.categories.forEach(c => { c._i = n++; });
            this.brands.forEach(b => { b._i = n++; });
            this.products.forEach(p => { p._i = n++; });
            this.total = n;
            this.activeIndex = this.total ? 0 : -1;
            this.open = true;
        } catch (e) {
            if (e.name !== 'AbortError') { this.categories = []; this.brands = []; this.products = []; this.total = 0; this.open = false; }
        } finally {
            this.loading = false;
        }
    },

    onInput() {
        clearTimeout(this.timer);
        this.categories = [];
        this.brands = [];
        this.products = [];
        this.activeIndex = -1;
        this.total = 0;
        if (this.query.trim().length < 1) {
            // Search box cleared → show the trending chips again.
            this.open = true;
            this.loading = false;
            if (this.trending.length === 0) this.fetchTrending(true);
            return;
        }
        this.open = true;
        this.loading = true;
        this.timer = setTimeout(() => this.fetchSuggestions(), 250);
    },

    onFocus() {
        if (this.query.trim() === '') {
            this.open = true;
            if (this.trending.length === 0) this.fetchTrending(true);
        }
    },

    async fetchTrending(openAfter = true) {
        if (this.controller) this.controller.abort();
        this.controller = new AbortController();
        try {
            const params = new URLSearchParams({ search: '' });
            if (this.storeSlug) params.set('store_slug', this.storeSlug);
            const res = await fetch(this.endpoint + '?' + params.toString(), { signal: this.controller.signal, headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.trending = Array.isArray(data.trending) ? data.trending : [];
            if (openAfter && this.query.trim() === '') this.open = true;
        } catch (e) {
            if (e.name !== 'AbortError') this.trending = [];
        }
    },

    pickTrending(item) {
        // Tapping a chip fills the search box and immediately runs the search.
        clearTimeout(this.timer);
        this.query = item.label;
        this.categories = [];
        this.brands = [];
        this.products = [];
        this.activeIndex = -1;
        this.total = 0;
        this.fetchSuggestions();
        this.$nextTick(() => this.$refs.searchInput && this.$refs.searchInput.focus());
    },

    onKeydown(e) {
        if (e.key === 'Escape') { this.open = false; return; }
        if (!this.open || !this.total) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); this.activeIndex = (this.activeIndex + 1) % this.total; }
        else if (e.key === 'ArrowUp') { e.preventDefault(); this.activeIndex = (this.activeIndex - 1 + this.total) % this.total; }
        else if (e.key === 'Enter' && this.activeItem()) {
            e.preventDefault();
            window.location.href = this.activeItem().url;
        }
    },

    activeItem() {
        if (this.activeIndex < 0) return null;
        for (const c of this.categories) if (c._i === this.activeIndex) return c;
        for (const b of this.brands) if (b._i === this.activeIndex) return b;
        for (const p of this.products) if (p._i === this.activeIndex) return p;
        return null;
    },

    activeId() {
        if (this.activeIndex < 0) return null;
        for (const c of this.categories) if (c._i === this.activeIndex) return 'sug-c-' + c.id;
        for (const b of this.brands) if (b._i === this.activeIndex) return 'sug-b-' + b.id;
        for (const p of this.products) if (p._i === this.activeIndex) return 'sug-p-' + p.id;
        return null;
    },

    hasAny() {
        return this.categories.length > 0 || this.brands.length > 0 || this.products.length > 0;
    },
}));

/* ---- Share store link: native Web Share API with per-app fallback ---- */
// Used by <x-share-button> (footer contact column). Tries navigator.share
// first (opens the phone's share sheet with Viber/Telegram/Facebook/etc.);
// when unsupported or cancelled, reveals a small menu with direct per-app
// share links (Viber forward / Telegram / Facebook) plus copy-link.
Alpine.data('shareAction', () => ({
    shareOpen: false,
    copied: false,

    get url() { return this.$el.dataset.shareUrl || window.location.href; },
    get title() { return this.$el.dataset.shareTitle || document.title; },
    get copyLabel() { return this.$el.dataset.copyLabel || 'Copy link'; },
    get copiedLabel() { return this.$el.dataset.copiedLabel || 'Copied!'; },

    share() {
        if (navigator.share) {
            navigator.share({ title: this.title, text: this.title, url: this.url })
                .catch(() => { this.shareOpen = true; });
        } else {
            this.shareOpen = true;
        }
    },

    copy() {
        const url = this.url;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).catch(() => {});
        } else {
            const ta = document.createElement('textarea');
            ta.value = url;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            ta.remove();
        }
        this.copied = true;
        setTimeout(() => { this.copied = false; this.shareOpen = false; }, 1500);
    },
}));


/* ---- Product detail Description | Specifications tabs ---- */
// Used on the storefront product page. Both tab panels are server-rendered in
// the DOM (so content survives if JS never loads); Alpine just switches which
// one is visible. Hash deep-linking (#description / #specifications) works on
// load and on back/forward via hashchange.
Alpine.data('productTabs', () => ({
    tab: 'description',

    init() {
        this.tab = this.tabFromHash() || 'description';
        window.addEventListener('hashchange', () => {
            this.tab = this.tabFromHash() || 'description';
        });
    },

    tabFromHash() {
        const h = window.location.hash;
        return h === '#specifications' || h === '#description' ? h.slice(1) : null;
    },

    activate(name) {
        this.tab = name;
        // replaceState avoids the jump-to-anchor scroll.
        history.replaceState(null, '', '#' + name);
    },

    onTabKeydown(e, current) {
        const names = ['description', 'specifications'];
        const i = names.indexOf(current);
        if (i === -1) return;
        let next = null;
        if (e.key === 'ArrowRight') next = names[(i + 1) % names.length];
        else if (e.key === 'ArrowLeft') next = names[(i - 1 + names.length) % names.length];
        else if (e.key === 'Home') next = names[0];
        else if (e.key === 'End') next = names[names.length - 1];
        if (!next) return;
        e.preventDefault();
        const btn = document.getElementById('tab-' + next);
        if (btn) btn.focus();
        this.activate(next);
    },
}));


Alpine.start();
