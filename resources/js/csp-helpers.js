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
