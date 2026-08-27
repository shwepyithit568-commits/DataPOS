/* DataPOS — Web Push frontend
 *
 * Responsibilities:
 *  - Read the VAPID public key from <meta name="vapid-public-key">.
 *  - Request notification permission (via floating bell or modal),
 *    subscribe with Push API, and POST the subscription to /api/push/subscribe.
 *  - Unsubscribe via DELETE /api/push/unsubscribe.
 *  - Persist the subscription to localStorage as a backup.
 *  - Provide instant UI feedback (toast/modal) on permission grant/deny.
 */
(function () {
    'use strict';

    var LS_SUBSCRIPTION = 'alinn_push_subscription';
    var LS_DENIED = 'alinn_push_denied';
    var LS_VIEWS = 'alinn_push_views';
    var LS_ENABLED = 'alinn_push_enabled';

    var bell = document.getElementById('push-notification-bell');
    var bellBadge = document.getElementById('push-notification-badge');
    var modal = document.getElementById('push-notification-modal');
    var modalClose = document.getElementById('push-modal-close');
    var modalDismiss = document.getElementById('push-modal-dismiss-btn');
    var modalAction = document.getElementById('push-modal-action-btn');
    var modalStatusText = document.getElementById('push-modal-status-text');

    function isSecureContext() {
        return window.isSecureContext ||
            location.protocol === 'https:' ||
            location.hostname === 'localhost' ||
            location.hostname === '127.0.0.1';
    }

    function isSupported() {
        return isSecureContext() &&
            'serviceWorker' in navigator &&
            'PushManager' in window &&
            'Notification' in window &&
            !!getVapidPublicKey();
    }

    function getVapidPublicKey() {
        var meta = document.querySelector('meta[name="vapid-public-key"]');
        return meta ? (meta.getAttribute('content') || '').trim() : '';
    }

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    /* Convert a base64url-encoded VAPID key into a Uint8Array */
    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = window.atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    /* ---- localStorage backup helpers ---- */

    function getStoredSubscription() {
        try {
            var raw = localStorage.getItem(LS_SUBSCRIPTION);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function storeSubscription(sub) {
        try {
            localStorage.setItem(LS_SUBSCRIPTION, JSON.stringify(sub));
        } catch (e) { /* storage blocked */ }
    }

    function clearStoredSubscription() {
        try { localStorage.removeItem(LS_SUBSCRIPTION); } catch (e) {}
    }

    function isEnabled() {
        try { return localStorage.getItem(LS_ENABLED) === '1'; } catch (e) { return false; }
    }

    function isDenied() {
        try { return localStorage.getItem(LS_DENIED) === '1'; } catch (e) { return false; }
    }

    /* ---- subscription API ---- */

    function sendSubscriptionToServer(subscription, action) {
        var endpoint = subscription ? subscription.endpoint : '';
        var keys = null;

        if (subscription && typeof subscription.getKey === 'function') {
            keys = {
                p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh'))))
                    .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''),
                auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth'))))
                    .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''),
            };
        } else if (subscription && subscription.keys) {
            keys = subscription.keys;
        }

        var url = '/api/push/subscribe';
        var method = 'POST';

        if (action === 'unsubscribe') {
            url = '/api/push/unsubscribe';
            method = 'DELETE';
        }

        var body = keys
            ? { endpoint: endpoint, keys: keys }
            : { endpoint: endpoint };

        return fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });
    }

    async function getActiveServiceWorker() {
        if (!navigator.serviceWorker.controller) {
            await navigator.serviceWorker.register('/sw.js');
        }
        return navigator.serviceWorker.ready;
    }

    async function subscribeToPush() {
        var registration = await getActiveServiceWorker();

        var existing = await registration.pushManager.getSubscription();
        if (existing) return existing;

        return registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(getVapidPublicKey()),
        });
    }

    function showToast(msg) {
        var existing = document.getElementById('push-feedback-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.id = 'push-feedback-toast';
        toast.className = 'fixed bottom-20 left-1/2 -translate-x-1/2 z-50 px-4 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold shadow-2xl border border-slate-700 flex items-center gap-2 animate-bounce';
        toast.innerHTML = '<span>🔔</span><span>' + msg + '</span>';
        document.body.appendChild(toast);

        setTimeout(function () {
            if (toast && toast.parentNode) {
                toast.style.transition = 'opacity 0.3s';
                toast.style.opacity = '0';
                setTimeout(function () { toast.remove(); }, 300);
            }
        }, 3000);
    }

    async function enableNotifications() {
        if (!isSupported()) {
            alert('သင့် Browser သည် Web Push Notification ကို Support မလုပ်ပါ (HTTPS သို့မဟုတ် localhost လိုအပ်ပါသည်)။');
            return false;
        }

        var permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            try { localStorage.setItem(LS_DENIED, '1'); } catch (e) {}
            alert('Notification permission ကို Allow မပေးထားပါသဖြင့် အသိပေးချက် ဖွင့်မရပါ။ Browser settings မှ Notification ဖွင့်ပေးပါ။');
            return false;
        }

        var subscription = await subscribeToPush();
        if (!subscription) return false;

        storeSubscription(subscription);
        try { localStorage.setItem(LS_ENABLED, '1'); } catch (e) {}
        try { localStorage.removeItem(LS_DENIED); } catch (e) {}

        await sendSubscriptionToServer(subscription, 'subscribe').catch(function () {});

        updateAccountToggle(true);
        hideModal();
        showToast('အသိပေးချက်များကို အောင်မြင်စွာ ဖွင့်ပြီးပါပြီ!');
        return true;
    }

    async function disableNotifications() {
        var stored = getStoredSubscription();
        var registration = null;
        try { registration = await navigator.serviceWorker.ready; } catch (e) {}

        var subscription = null;
        if (registration) {
            subscription = await registration.pushManager.getSubscription();
        }

        if (subscription) {
            await subscription.unsubscribe().catch(function () {});
        }

        clearStoredSubscription();
        try { localStorage.removeItem(LS_ENABLED); } catch (e) {}

        if (stored && stored.endpoint) {
            await sendSubscriptionToServer({ endpoint: stored.endpoint }, 'unsubscribe').catch(function () {});
        }

        updateAccountToggle(false);
        hideModal();
        showToast('အသိပေးချက်များကို ပိတ်လိုက်ပါပြီ။');
        return true;
    }

    /* ---- Modal Management ---- */

    function showModal() {
        if (!modal) {
            // Fallback direct prompt if modal element is not in DOM
            enableNotifications();
            return;
        }

        var isAlreadyGranted = Notification.permission === 'granted';

        if (modalStatusText) {
            modalStatusText.textContent = isAlreadyGranted
                ? '✅ အသိပေးချက်များ ဖွင့်ထားပြီးဖြစ်ပါသည်'
                : 'အချိန်နှင့်တစ်ပြေးညီ သတင်းလွှာများ ရယူရန်';
        }

        if (modalAction) {
            if (isAlreadyGranted) {
                modalAction.innerHTML = '<span>🔕 အသိပေးချက်များ ပိတ်မည်</span>';
                modalAction.className = 'w-full py-2.5 px-4 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer';
                modalAction.onclick = function () { disableNotifications(); };
            } else {
                modalAction.innerHTML = '<span>🔔 အသိပေးချက်များ ဖွင့်မည်</span>';
                modalAction.className = 'w-full py-2.5 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 active:scale-95 text-white font-bold text-xs shadow-md transition flex items-center justify-center gap-1.5 cursor-pointer';
                modalAction.onclick = function () { enableNotifications(); };
            }
        }

        modal.classList.remove('hidden');
    }

    function hideModal() {
        if (modal) modal.classList.add('hidden');
    }

    /* ---- UI bindings ---- */

    function showBell() {
        if (!bell || isDenied()) return;
        bell.classList.remove('hidden');
    }

    function hideBell() {
        if (bell) bell.classList.add('hidden');
    }

    function setBadge(count) {
        if (!bellBadge) return;
        if (count > 0) {
            bellBadge.textContent = count > 9 ? '9+' : String(count);
            bellBadge.classList.remove('hidden');
        } else {
            bellBadge.classList.add('hidden');
        }
    }

    function updateAccountToggle(enabled) {
        var toggle = document.getElementById('push-prefs-toggle');
        var status = document.getElementById('push-prefs-status');
        if (!toggle) return;

        var on = enabled !== undefined
            ? enabled
            : (Notification.permission === 'granted' && !!getStoredSubscription()) || isEnabled();

        toggle.checked = on;
        toggle.setAttribute('aria-checked', on ? 'true' : 'false');

        if (status) {
            var labels = window.__pushLabels || {};
            status.textContent = on
                ? (labels.enabled || 'Notifications enabled')
                : (labels.disabled || 'Notifications disabled');
        }
    }

    function init() {
        if (!isSupported()) {
            hideBell();
            return;
        }

        showBell();
        updateAccountToggle();

        if (bell) {
            bell.addEventListener('click', function (e) {
                e.preventDefault();
                showModal();
            });
        }

        if (modalClose) modalClose.addEventListener('click', hideModal);
        if (modalDismiss) modalDismiss.addEventListener('click', hideModal);
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) hideModal();
            });
        }

        // Re-sync on reload if granted
        if (getStoredSubscription() && Notification.permission === 'granted') {
            sendSubscriptionToServer(getStoredSubscription(), 'subscribe').catch(function () {});
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
