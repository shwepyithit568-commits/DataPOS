/* DataPOS — Web Push frontend
 *
 * Responsibilities:
 *  - Read the VAPID public key from <meta name="vapid-public-key">.
 *  - Request notification permission (via the floating bell or the account
 *    preferences toggle), subscribe with the Push API, and POST the
 *    subscription to /api/push/subscribe.
 *  - Unsubscribe via DELETE /api/push/unsubscribe.
 *  - Persist the subscription to localStorage as a backup so we never
 *    re-prompt or re-subscribe on every page load.
 *  - Show the bell only after the visitor has browsed 5+ pages, hide it
 *    permanently once notifications are granted or denied.
 *
 * Loaded with `defer` from the storefront layout. No dependencies.
 */
(function () {
    'use strict';

    var LS_SUBSCRIPTION = 'alinn_push_subscription';
    var LS_DENIED = 'alinn_push_denied';
    var LS_VIEWS = 'alinn_push_views';
    var LS_ENABLED = 'alinn_push_enabled';

    var MIN_VIEWS = 5;

    var bell = document.getElementById('push-notification-bell');
    var bellBadge = document.getElementById('push-notification-badge');

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

    /* Convert a base64url-encoded VAPID key into a Uint8Array (the format the
     * Push API's applicationServerKey expects). */
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
        } catch (e) { /* storage full / blocked — non-fatal */ }
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

    function countPageView() {
        var views = 0;
        try {
            views = parseInt(localStorage.getItem(LS_VIEWS) || '0', 10);
            if (isNaN(views)) views = 0;
            views += 1;
            localStorage.setItem(LS_VIEWS, String(views));
        } catch (e) { views = MIN_VIEWS + 1; } // storage blocked → treat as browsed enough
        return views;
    }

    /* ---- subscription API ---- */

    function sendSubscriptionToServer(subscription, action) {
        var endpoint = subscription ? subscription.endpoint : '';
        var keys = subscription && subscription.getKey
            ? {
                p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh'))))
                    .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''),
                auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth'))))
                    .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''),
            }
            : null;

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

    async function enableNotifications() {
        if (!isSupported()) return false;

        var permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            try { localStorage.setItem(LS_DENIED, '1'); } catch (e) {}
            hideBell();
            return false;
        }

        var subscription = await subscribeToPush();
        if (!subscription) return false;

        storeSubscription(subscription);
        try { localStorage.setItem(LS_ENABLED, '1'); } catch (e) {}
        try { localStorage.removeItem(LS_DENIED); } catch (e) {}

        await sendSubscriptionToServer(subscription, 'subscribe').catch(function () {
            // The localStorage backup still exists; the server can be
            // re-synced on the next page load.
        });

        hideBell();
        updateAccountToggle(true);
        return true;
    }

    async function disableNotifications() {
        // Capture the stored backup FIRST — it is our only record of the
        // endpoint if the live PushSubscription is already gone, and we must
        // not clear it before reading it.
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

        // Report the removal even if the live PushSubscription was gone —
        // the stored backup tells us the endpoint to remove server-side.
        if (stored && stored.endpoint) {
            await sendSubscriptionToServer({ endpoint: stored.endpoint }, 'unsubscribe').catch(function () {});
        }

        updateAccountToggle(false);
        return true;
    }

    /* ---- bell UI ---- */

    function showBell() {
        if (!bell || isDenied()) return;
        if (Notification.permission === 'granted') return; // already enabled
        if (Notification.permission === 'denied') {
            try { localStorage.setItem(LS_DENIED, '1'); } catch (e) {}
            return;
        }
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

    /* Read the unread count the service worker keeps in its push-unread cache. */
    function refreshBadgeFromSw() {
        if (!('caches' in window)) return;
        caches.open('push-unread').then(function (cache) {
            return cache.match('/push-unread/current');
        }).then(function (res) {
            if (!res) { setBadge(0); return; }
            return res.text();
        }).then(function (text) {
            if (text !== undefined) setBadge(parseInt(text, 10) || 0);
        }).catch(function () { setBadge(0); });
    }

    /* ---- account preferences toggle ---- */

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

    function wireAccountToggle() {
        var toggle = document.getElementById('push-prefs-toggle');
        if (!toggle) return;

        toggle.addEventListener('change', function () {
            var btn = toggle.closest('button');
            if (btn) btn.disabled = true;
            (toggle.checked ? enableNotifications() : disableNotifications())
                .then(function () {
                    updateAccountToggle(toggle.checked);
                })
                .catch(function () {
                    updateAccountToggle(!toggle.checked);
                })
                .finally(function () {
                    if (btn) btn.disabled = false;
                });
        });
    }

    /* ---- init ---- */

    function init() {
        if (!isSupported()) {
            hideBell();
            return;
        }

        // Track page views to decide when the bell may appear.
        var views = countPageView();

        // Wire the account preferences toggle (page may or may not have it).
        wireAccountToggle();
        updateAccountToggle();

        // Listen for unread-count updates broadcast by the service worker.
        if (navigator.serviceWorker) {
            navigator.serviceWorker.addEventListener('message', function (event) {
                if (event.data && event.data.type === 'PUSH_UNREAD') {
                    setBadge(event.data.count || 0);
                }
            });
        }

        refreshBadgeFromSw();

        if (bell) {
            bell.addEventListener('click', function () {
                enableNotifications();
            });
        }

        // The bell appears only after 5+ browsed pages and only while
        // notifications are neither granted nor denied.
        if (views >= MIN_VIEWS) {
            showBell();
        }

        // Re-sync the stored subscription backup with the server once per
        // session (covers the case where the first POST failed offline).
        if (getStoredSubscription() && Notification.permission === 'granted') {
            sendSubscriptionToServer(getStoredSubscription(), 'subscribe').catch(function () {});
        }

        // Sync the account toggle once subscription state is known.
        setTimeout(function () { updateAccountToggle(); }, 300);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
