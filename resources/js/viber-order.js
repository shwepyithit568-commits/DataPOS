/* ---------------------------------------------------------------------------
 * viber-order.js
 *
 * Reusable, pure helpers for the storefront Viber flows. These mirror the
 * server-side App\Support\ContactLinkBuilder so the no-JS fallbacks and the
 * interactive modal produce identical messages and URLs:
 *
 *   normalizeMyanmarPhoneNumber(raw)   -> E.164 digits (e.g. "+959892499955")
 *   buildViberChatUrl(number, draft)   -> viber://chat?number=...&draft=...
 *   buildProductInquiryMessage(data)   -> enquiry message (Burmese)
 *   buildOrderMessage(data)            -> order message (Burmese)
 *   copyToClipboard(text)              -> clipboard with <textarea> fallback
 *   copyMessageAndOpenViber(data)      -> copy order message, then open chat
 *
 * Query parameters are encoded exactly once (encodeURIComponent) so Burmese
 * Unicode, newlines, &, +, /, #, parentheses and URLs survive Viber's draft
 * parameter without double-encoding.
 * ------------------------------------------------------------------------- */

// Normalize a Myanmar phone to E.164 ("09...", "959...", "+959...", "09 12 34 567").
export function normalizeMyanmarPhoneNumber(raw) {
    if (raw === null || raw === undefined) return null;
    const s = String(raw).trim();
    if (s === '') return null;
    const hasPlus = s.startsWith('+');
    const digits = s.replace(/\D+/g, '');
    if (digits === '') return null;
    if (hasPlus) return '+' + digits;
    if (digits.startsWith('09')) return '+95' + digits.slice(1);
    if (digits.startsWith('959')) return '+' + digits;
    return digits;
}

// Viber's mobile chat route expects E.164 digits without the leading plus.
export function buildViberChatUrl(number, draft) {
    const normalized = normalizeMyanmarPhoneNumber(number);
    if (!normalized) return null;
    let url = 'viber://chat?number=' + normalized.replace('+', '');
    if (draft !== null && draft !== undefined && String(draft) !== '') {
        url += '&draft=' + encodeURIComponent(String(draft));
    }
    return url;
}

// "ဒီပစ္စည်းကို Viber မှ မေးမြန်းရန်" — enquiry message.
export function buildProductInquiryMessage(data) {
    const lines = [
        'မင်္ဂလာပါ။',
        (data.store_name || window.__alinnStoreName || '') + ' မှာ ဒီပစ္စည်းကို မေးမြန်းချင်ပါတယ်။',
        '',
        'ပစ္စည်း: ' + (data.product_name || ''),
        'SKU: ' + (data.sku || '-'),
    ];
    if (data.variant_name) lines.push('ရွေးချယ်မှု: ' + data.variant_name);
    lines.push('အရေအတွက်: ' + (data.quantity || 1));
    if (data.price) lines.push('ဈေးနှုန်း: Ks ' + fmt(data.price));
    if (data.product_url) lines.push('လင့်ခ်: ' + data.product_url);
    return lines.join('\n');
}

// "Viber မှ အော်ဒါတင်ရန်" — order message (confirmed in the modal).
export function buildOrderMessage(data) {
    const lines = [
        'မင်္ဂလာပါ။',
        (data.store_name || window.__alinnStoreName || '') + ' မှာ အောက်ပါပစ္စည်းကို အော်ဒါတင်ချင်ပါတယ်။',
        '',
        'ပစ္စည်း: ' + (data.product_name || ''),
        'SKU: ' + (data.sku || '-'),
    ];
    if (data.variant_name) lines.push('ရွေးချယ်မှု: ' + data.variant_name);
    lines.push('အရေအတွက်: ' + (data.quantity || 1));
    if (data.unit_price) lines.push('တစ်ခုဈေး: Ks ' + fmt(data.unit_price));
    if (data.total_price) lines.push('စုစုပေါင်း: Ks ' + fmt(data.total_price));
    if (data.product_url) lines.push('ပစ္စည်းလင့်ခ်: ' + data.product_url);
    return lines.join('\n');
}

function fmt(n) {
    return Number(n).toLocaleString('en-US');
}

// Copy text to the clipboard; falls back to a temporary <textarea> + execCommand.
export async function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (e) {
            // fall through to the manual approach
        }
    }
    try {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        const ok = document.execCommand('copy');
        ta.remove();
        return ok;
    } catch (e) {
        return false;
    }
}

// Copy the order message to the clipboard, then open the Viber chat deep link.
// Never auto-sends; the shopper pastes and sends inside Viber. Returns the URL
// so callers can navigate to the Viber download page as a fallback.
export async function copyMessageAndOpenViber(data) {
    const message = buildOrderMessage(data);
    const url = buildViberChatUrl(data.number, message);
    await copyToClipboard(message);
    return url;
}

// ---------------------------------------------------------------------------
// Viber Modal State — Alpine.store singleton shared between the product detail
// Alpine x-data (which owns variant/price reactive state) and the body-level
// modal template (_viber_order_modal.blade.php). The store is reactive so the
// modal's $watch fires when show()/close() toggle `open`.
// ---------------------------------------------------------------------------

function createViberModalStore() {
    return {
        open: false,
        _data: null,
        _lastTrigger: 0,

        init(data) {
            this._data = data; // { qty, price, sku, variantName, message, url, needsVariant, fmt, copied, opening, incQty, decQty }
        },

        show() {
            this.open = true;
        },

        close() {
            this.open = false;
        },

        get needsVariant()  { return this._data?.needsVariant?.() ?? true; },
        get qty()           { return this._data?.qty?.() ?? 1; },
        get price()         { return this._data?.price?.() ?? 0; },
        get sku()           { return this._data?.sku?.() ?? '-'; },
        get variantName()   { return this._data?.variantName?.() ?? ''; },
        get message()       { return this._data?.message?.() ?? ''; },
        get url()           { return this._data?.url?.() ?? null; },
        get fmt()           { return this._data?.fmt ?? (n => Number(n).toLocaleString('en-US')); },
        get copied()        { return this._data?.copied ?? 'none'; },
        set copied(v)       { if (this._data) this._data.copied = v; },
        get opening()       { return this._data?.opening ?? false; },
        set opening(v)      { if (this._data) this._data.opening = v; },

        incQty()  { if (this._data?.incQty) this._data.incQty(); },
        decQty()  { if (this._data?.decQty) this._data.decQty(); },

        async copyMessage() {
            const msg = this._data?.message?.();
            if (!msg) return;
            if (this._data) this._data.copied = 'copying';
            const ok = await copyToClipboard(msg);
            if (this._data) this._data.copied = ok ? 'copied' : 'failed';
        },

        async copyAndOpen() {
            const now = Date.now();
            if (now - this._lastTrigger < 2000) return;
            this._lastTrigger = now;

            if (this.needsVariant || !this.url) return;
            if (this._data) this._data.opening = true;
            if (this._data) this._data.copied = 'copying';
            const msg = this._data?.message?.();
            const ok = msg ? await copyToClipboard(msg) : false;
            if (this._data) this._data.copied = ok ? 'copied' : 'failed';

            const viberUrl = this.url;
            if (viberUrl) window.location.href = viberUrl;
            if (this._data) this._data.opening = false;
        },
    };
}

// Register the store as soon as Alpine is on window. app.js calls this after
// setting window.Alpine; the guard makes repeated calls a no-op.
export function registerViberModalStore() {
    if (typeof window !== 'undefined' && typeof window.Alpine !== 'undefined' && !window.Alpine.store('viberModal')) {
        window.Alpine.store('viberModal', createViberModalStore());
        // Back-compat alias so Blade templates written before the store refactor still work.
        window.__viberModalState = window.Alpine.store('viberModal');
    }
}