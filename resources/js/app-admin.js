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
