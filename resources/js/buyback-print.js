/**
 * Buy-back slip export (PDF download / JPG share).
 *
 * html2pdf.js is a normal local dependency (see package.json) and is bundled
 * here instead of being pulled from a CDN, for two reasons:
 *
 *  1. The admin/POS pages run under a CSP with `script-src 'self' 'nonce-…'`,
 *     so a plain <script src="https://cdnjs…"> is blocked outright. The
 *     Download PDF / Share JPG buttons on the slip were dead because of it.
 *  2. A shop with no internet cannot fetch a CDN copy at all — and the counter
 *     must keep working when the line is down.
 *
 * The slip's own inline handler reads window.html2pdf, so expose it there.
 */
import html2pdf from 'html2pdf.js';

window.html2pdf = html2pdf;
