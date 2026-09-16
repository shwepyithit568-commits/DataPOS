/* ---------------------------------------------------------------------------
 * csp-helpers.js
 *
 * Delegated replacements for the inline event-handler attributes that were
 * removed from the Blade views so the Content-Security-Policy can drop
 * 'unsafe-inline' from script-src (nonce-based instead). Every handler below
 * is registered once, globally, and matches on data-* attributes:
 *
 *   data-ios-href     <a> — on iOS tap, swap the href to the iOS deep link
 *   data-catalog-view <a> — remember the catalog grid/list preference
 *   data-auto-submit  <select|form> — submit the enclosing form on change
 *   data-confirm      <form|button> — window.confirm() before submit/click
 *   data-print        <button> — window.print()
 *   data-img-fallback <img> — image error fallback (hide / show placeholder)
 *   data-close-window <button> — window.close()
 *   data-back         <button> — history.back()
 *   data-href         <row> — click the row to navigate. Clicks that land on
 *                              an inner <a>/<button>/<input> are ignored so
 *                              row actions keep working and no
 *                              stopPropagation() is needed.
 *   data-navigate     <select> — navigate to data-navigate-prefix + value
 *   data-set-value    <button> — set data-set-target's value to data-set-value
 *   data-focus-scroll <el> — focus (when focusable) and scroll into view
 *   data-show/hide    <button> — remove / add the `hidden` class on the
 *                                element named by data-show / data-hide
 *                                (the codebase's Tailwind-class modal pattern)
 *   data-submit-form  <button> — submit the form whose id is in the attribute
 *   data-call         <button> — invoke window[data-call]() on click
 *   data-call-self    <el> — same, but only when the element itself is the
 *                             click target (modal backdrop dismiss)
 *
 * Imported by both the storefront (app.js) and admin (app-admin.js) bundles.
 * ------------------------------------------------------------------------- */

// Viber iOS deep-link swap. The Blades used to inline this as
// onclick="if (/iPad|iPhone|iPod/.test(navigator.userAgent)) this.href = this.dataset.iosHref;"
document.addEventListener('click', (e) => {
    const link = e.target.closest('a[data-ios-href]');
    if (link && /iPad|iPhone|iPod/.test(navigator.userAgent) && link.dataset.iosHref) {
        link.href = link.dataset.iosHref;
    }
}, true);

// Persist the grid/list preference before the link navigates.
document.addEventListener('click', (e) => {
    const link = e.target.closest('[data-catalog-view]');
    if (link && link.dataset.catalogView) {
        try { localStorage.setItem('catalog_view', link.dataset.catalogView); } catch (err) { /* storage unavailable */ }
    }
}, true);

// Auto-submit forms when a filter/sort select changes. The attribute may sit
// on the <select> itself or on the wrapping <form> (admin toolbar pattern).
document.addEventListener('change', (e) => {
    const el = e.target.closest('[data-auto-submit]');
    if (!el) return;
    const form = el.tagName === 'FORM' ? el : el.closest('form');
    if (form) form.submit();
}, true);

// Confirm destructive actions. A <form data-confirm> guards the submit event;
// a <button data-confirm> (submit button inside a form) guards the click.
document.addEventListener('submit', (e) => {
    const form = e.target.closest('form[data-confirm]');
    if (form && !window.confirm(form.dataset.confirm || 'Are you sure?')) {
        e.preventDefault();
        e.stopPropagation();
    }
}, true);

document.addEventListener('click', (e) => {
    const el = e.target.closest('button[data-confirm], a[data-confirm]');
    if (el && !window.confirm(el.dataset.confirm || 'Are you sure?')) {
        e.preventDefault();
        e.stopPropagation();
    }
}, true);

// Print button (invoice).
document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-print]');
    if (el) {
        e.preventDefault();
        window.print();
    }
}, true);

// Close the current window (print/preview tabs opened by the app).
document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-close-window]');
    if (el) {
        e.preventDefault();
        window.close();
    }
}, true);

// Browser back (404 page).
document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-back]');
    if (el) {
        e.preventDefault();
        window.history.back();
    }
}, true);

// Row navigation for card/table views. Registered in the bubble phase so an
// inner <a>/<button> has already handled (and possibly prevented) the click.
document.addEventListener('click', (e) => {
    const row = e.target.closest('[data-href]');
    if (!row || !row.dataset.href || e.defaultPrevented) return;
    if (e.target.closest('a, button, input, select, textarea, label')) return;
    window.location.href = row.dataset.href;
});

// Navigate on select change (product/branch switchers).
document.addEventListener('change', (e) => {
    const el = e.target.closest('[data-navigate]');
    if (!el) return;
    window.location.href = (el.dataset.navigatePrefix || '') + el.value;
}, true);

// Copy a server-rendered value into an input (e.g. "collect full balance").
document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-set-value]');
    if (!el) return;
    e.preventDefault();
    const target = document.getElementById(el.dataset.setTarget || '');
    if (target) target.value = el.dataset.setValue;
}, true);

// Focus and scroll a field into view (glass finder shortcut buttons).
document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-focus-scroll]');
    if (!el) return;
    const target = document.getElementById(el.dataset.focusScroll);
    if (!target) return;
    if (typeof target.focus === 'function') target.focus();
    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
}, true);

// Show/hide a `hidden`-class modal container.
document.addEventListener('click', (e) => {
    const show = e.target.closest('[data-show]');
    if (show) {
        e.preventDefault();
        const target = document.getElementById(show.dataset.show);
        if (target) target.classList.remove('hidden');

        return;
    }

    const hide = e.target.closest('[data-hide]');
    if (hide) {
        e.preventDefault();
        const target = document.getElementById(hide.dataset.hide);
        if (target) target.classList.add('hidden');
    }
}, true);

// Submit an external form by id (buttons that live outside the form they post,
// e.g. a per-row "delete" button in a list). Honours a sibling data-confirm,
// which runs first and marks the event defaultPrevented when cancelled.
document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-submit-form]');
    if (!el || e.defaultPrevented) return;
    const form = document.getElementById(el.dataset.submitForm);
    if (form) form.submit();
}, true);

// Invoke a page-defined global function by name. Replaces onClick="someFn()"
// on pages whose functions are declared in their own nonce'd <script> block.
//   data-call="fnName"      → run fn() on click
//   data-call-self="fnName" → run fn() only when the element itself was
//                             clicked (modal backdrops)
document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-call]');
    if (el) {
        const fn = window[el.dataset.call];
        if (typeof fn === 'function') {
            e.preventDefault();
            fn(el);
        }

        return;
    }

    const self = e.target.closest('[data-call-self]');
    if (self && e.target === self) {
        const fn = window[self.dataset.callSelf];
        if (typeof fn === 'function') fn(self);
    }
}, true);

// Image error fallbacks. 'error' does not bubble, so listen in the capture
// phase and act on the target <img> carrying a data-img-fallback token:
//   hide       → hide the broken image itself
//   hide-next  → hide the image, show the placeholder <span> next to it
//   hide-parent→ hide the image's parent (used by hero banners)
//   fav        → hide the image, show the [data-fav-ph] placeholder inside the parent
document.addEventListener('error', (e) => {
    const img = e.target;
    if (!(img instanceof HTMLImageElement)) return;
    const mode = img.dataset.imgFallback;
    if (!mode) return;

    if (mode === 'hide') {
        img.style.display = 'none';
    } else if (mode === 'hide-next') {
        img.style.display = 'none';
        const placeholder = img.nextElementSibling;
        if (placeholder) placeholder.style.display = 'flex';
    } else if (mode === 'hide-parent') {
        const parent = img.parentElement;
        if (parent) parent.style.display = 'none';
    } else if (mode === 'fav') {
        img.style.display = 'none';
        const parent = img.parentElement;
        const placeholder = parent && parent.querySelector('[data-fav-ph]');
        if (placeholder) placeholder.style.display = 'flex';
    }
}, true);
