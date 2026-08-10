/* ---------------------------------------------------------------------------
 * viber-fallback.js
 *
 * Viber deep links (viber://...) are custom URL schemes: they work only when
 * the Viber app is installed AND the browser can hand the link to the OS.
 * In embedded browsers / in-app webviews, or when Viber is missing, clicking
 * such a link fails SILENTLY (no error, no navigation) — the visitor is left
 * wondering why nothing happened.
 *
 * This script arms a fallback: after any viber:// navigation attempt, if the
 * page does not lose focus within ~1.5s (meaning no external app opened), it
 * shows a small toast with the Viber number and a copy button, so the
 * visitor can still reach the store manually inside Viber.
 *
 * Exposed globals:
 *   window.__armViberFallback(url, orderMessageCopied)  — arm for a nav attempt
 *   window.__viberFallbackCopy(text)                    — clipboard helper
 * ------------------------------------------------------------------------- */
(function () {
    'use strict';

    var FALLBACK_DELAY = 1500;

    /* Extract the Viber phone number from a viber:// URL. */
    function extractNumber(url) {
        var m = /number=([0-9+]+)/.exec(url);
        return m ? m[1] : null;
    }

    /* 959892499955 -> 09 892 499 955 (Myanmar display format). */
    function displayNumber(num) {
        if (/^95[0-9]{9,10}$/.test(num)) return '0' + num.slice(2);
        return num;
    }

    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text).then(function () { return true; });
        }
        try {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            var ok = document.execCommand('copy');
            ta.remove();
            return Promise.resolve(ok);
        } catch (e) {
            return Promise.resolve(false);
        }
    }

    /* Show the fallback toast. */
    function showFallback(number, orderMessageCopied) {
        var old = document.getElementById('viber-fallback-toast');
        if (old) old.remove();

        var num = displayNumber(number);
        var toast = document.createElement('div');
        toast.id = 'viber-fallback-toast';
        toast.setAttribute('role', 'dialog');
        toast.setAttribute('aria-label', 'Viber fallback');
        toast.style.cssText =
            'position:fixed;left:16px;right:16px;bottom:96px;z-index:9999;' +
            'max-width:400px;margin:0 auto;background:#fff;' +
            'border:1px solid #e2e8f0;border-radius:16px;' +
            'box-shadow:0 20px 40px rgba(0,0,0,.25);padding:16px;' +
            'font-family:-apple-system,"Segoe UI",Roboto,sans-serif;';

        var title = document.createElement('p');
        title.style.cssText = 'margin:0 0 6px;font-size:14px;font-weight:800;color:#1e293b;';
        title.textContent = orderMessageCopied
            ? 'Message ကို ကော်ပီကူးပြီးပါပြီ'
            : 'Viber မဖွင့်နိုင်ပါ';

        var body = document.createElement('p');
        body.style.cssText = 'margin:0 0 12px;font-size:12.5px;line-height:1.55;color:#64748b;';
        body.textContent = orderMessageCopied
            ? 'Viber app ထဲမှာ နံပါတ် ' + num + ' ကို ရှာပြီး message ကို paste လုပ်ပြီး ပို့လိုက်ပါ။'
            : 'Viber app မဖွင့်နိုင်ပါ — Viber ထဲမှာ နံပါတ် ' + num + ' ကို ရှာပြီး စကားပြောနိုင်ပါတယ်။';

        var copyBtn = document.createElement('button');
        copyBtn.type = 'button';
        copyBtn.textContent = '📋 နံပါတ် ကော်ပီကူးမည်';
        copyBtn.style.cssText =
            'width:100%;background:#7c3aed;color:#fff;border:0;border-radius:12px;' +
            'padding:10px;font-size:13px;font-weight:700;cursor:pointer;';
        copyBtn.addEventListener('click', function () {
            copyText(num).then(function (ok) {
                copyBtn.textContent = ok ? '✅ ကော်ပီပြီးပါပြီ' : '❌ ကော်ပီမအောင်မြင်ပါ';
                setTimeout(function () {
                    copyBtn.textContent = '📋 နံပါတ် ကော်ပီကူးမည်';
                }, 2000);
            });
        });

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.textContent = 'ပိတ်ရန်';
        closeBtn.style.cssText =
            'width:100%;background:transparent;color:#64748b;border:0;border-radius:12px;' +
            'padding:8px;font-size:12px;font-weight:600;cursor:pointer;margin-top:4px;';
        closeBtn.addEventListener('click', function () { toast.remove(); });

        toast.appendChild(title);
        toast.appendChild(body);
        toast.appendChild(copyBtn);
        toast.appendChild(closeBtn);
        document.body.appendChild(toast);
    }

    /* Arm the fallback for a viber:// navigation attempt. */
    function arm(url, orderMessageCopied) {
        var number = extractNumber(url || '');
        if (!number) return;

        var appOpened = false;
        function markOpened() { appOpened = true; }

        window.addEventListener('blur', markOpened, { once: true });
        document.addEventListener('visibilitychange', function onVis() {
            if (document.hidden) markOpened();
            document.removeEventListener('visibilitychange', onVis);
        });

        setTimeout(function () {
            if (!appOpened) showFallback(number, !!orderMessageCopied);
        }, FALLBACK_DELAY);
    }

    /* Global click listener: any <a href="viber://..."> link (chat buttons,
     * footer, share "Viber" rows). Capture phase so it fires before the
     * default navigation. */
    document.addEventListener('click', function (e) {
        var link = e.target && e.target.closest
            ? e.target.closest('a[href^="viber://"]')
            : null;
        if (link) arm(link.href, false);
    }, true);

    window.__armViberFallback = arm;
    window.__viberFallbackCopy = copyText;
})();
