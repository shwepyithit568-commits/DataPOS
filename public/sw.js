/* DataPOS — service worker (PWA app-shell)
 *
 * Strategy:
 *  - Navigations (HTML pages): stale-while-revalidate. Serve the cached copy
 *    instantly, refresh it from the network in the background, and when the
 *    network is unavailable fall back to the cached version (or the cached
 *    homepage as a last resort).
 *  - Same-origin static assets (CSS/JS/fonts/images): cache-first with a
 *    background refresh.
 *  - Never cached: POST/other non-GET requests, API/JSON endpoints, cart
 *    (order-builder), checkout/orders, account and admin pages, auth pages.
 *
 * Bump CACHE_VERSION after every deploy so browsers pick up a fresh shell
 * instead of fighting stale entries.
 */
'use strict';

var CACHE_VERSION = 'datapos-v3';
var SHELL_CACHE = CACHE_VERSION + '-shell';

/* Requests that must NEVER touch the cache (privacy + correctness). */
function isExcluded(url) {
    var path = url.pathname;

    if (url.origin !== self.location.origin) return true; // cross-origin: never intercept

    // API / JSON endpoints (search suggestions, glass-finder favorite sync, …)
    if (path.indexOf('/api/') === 0) return true;
    if (path.indexOf('/products/suggestions') !== -1) return true;
    if (path.indexOf('/glass-finder/favorite') !== -1) return true;

    // Cart / checkout / order submission — must always be fresh
    if (path.indexOf('order-builder') !== -1) return true;
    if (path.indexOf('/orders') !== -1) return true;
    if (path.indexOf('/checkout') !== -1) return true;

    // Private / authenticated areas
    if (path.indexOf('/account') !== -1) return true;
    if (path.indexOf('/admin') !== -1) return true;
    if (path.indexOf('/login') !== -1 || path.indexOf('/register') !== -1 || path.indexOf('/logout') !== -1) return true;

    // Forms / dynamic submission endpoints
    if (path.indexOf('/reviews') !== -1) return true;
    if (path.indexOf('/favorite') !== -1) return true;

    return false;
}

/* Cache an opaque success response (only GET HTML / static assets). */
function cachePut(request, response) {
    if (!response || response.status !== 200 || response.type === 'opaque') return;
    var url = new URL(request.url);
    if (isExcluded(url)) return;
    caches.open(SHELL_CACHE).then(function (cache) {
        cache.put(request, response.clone());
    });
}

self.addEventListener('install', function (event) {
    // Pre-cache the app shell so the first offline visit works.
    event.waitUntil(
        caches.open(SHELL_CACHE)
            .then(function (cache) {
                return cache.addAll([
                    '/',
                    '/manifest.webmanifest',
                    '/icons/icon-192.png',
                    '/icons/icon-512.png'
                ]);
            })
            .then(function () {
                return self.skipWaiting();
            })
    );
});

self.addEventListener('activate', function (event) {
    // Drop caches from older versions on activation.
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys
                    .filter(function (key) { return key.indexOf(CACHE_VERSION) !== 0; })
                    .map(function (key) { return caches.delete(key); })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', function (event) {
    var request = event.request;
    var url = new URL(request.url);

    if (request.method !== 'GET') return; // never intercept POST/PUT/…
    if (isExcluded(url)) return;          // API / cart / checkout / private pages

    // Static assets (hashed CSS/JS bundles, fonts, images): cache-first,
    // refresh in the background.
    if (request.destination === 'style' || request.destination === 'script' ||
        request.destination === 'font' || request.destination === 'image') {
        event.respondWith(
            caches.match(request).then(function (cached) {
                if (cached) {
                    // Refresh the cache entry in the background.
                    fetch(request).then(function (response) {
                        cachePut(request, response);
                    }).catch(function () {});
                    return cached;
                }
                return fetch(request).then(function (response) {
                    cachePut(request, response);
                    return response;
                }).catch(function () {
                    return Response.error();
                });
            })
        );
        return;
    }

    // Navigations (HTML pages): stale-while-revalidate with offline fallback.
    if (request.mode === 'navigate') {
        event.respondWith(
            caches.match(request).then(function (cached) {
                // Background refresh of the live page.
                var refresh = fetch(request).then(function (response) {
                    cachePut(request, response);
                    return response;
                }).catch(function () {
                    return null;
                });

                // Serve cache immediately, but hand the fresh network response
                // to the browser when it arrives.
                if (cached) {
                    refresh; // fire and forget — update the cache quietly
                    return cached;
                }
                return refresh.then(function (networkResponse) {
                    if (networkResponse) return networkResponse;
                    // Offline and nothing cached for this URL: try the homepage.
                    return caches.match('/').then(function (home) {
                        return home || Response.error();
                    });
                });
            })
        );
        return;
    }

    // Any other same-origin GET (e.g. <link rel="manifest">, favicons):
    // network-first with cache fallback.
    event.respondWith(
        fetch(request).then(function (response) {
            cachePut(request, response);
            return response;
        }).catch(function () {
            return caches.match(request);
        })
    );
});

/* Install-prompt bridge: the page sends { type: 'PWA_INSTALL_PROMPT' } when the
 * beforeinstallprompt event fires so the SW can log it / coordinate. The actual
 * prompt UI lives in the page (see the layout banner), which the SW cannot
 * trigger on its own. */
self.addEventListener('message', function (event) {
    if (event.data && event.data.type === 'PWA_INSTALL_PROMPT') {
        // Inform the page the SW is ready to serve the shell.
        if (event.source && event.source.postMessage) {
            event.source.postMessage({ type: 'PWA_READY' });
        }
    }
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

/* ------------------------------------------------------------------------
 * Web Push (browser notifications)
 * ----------------------------------------------------------------------*/

/* Read the notification payload out of a push event.
 * Backend notifications (NewOrderNotification / TestPushNotification) send
 * { title, body, icon, badge, data: { url } }; the icon/badge are absolute
 * URLs. Anything else falls back to a generic title so a malformed payload
 * can never crash the handler. */
function parsePushPayload(event) {
    var data = {};
    try {
        if (event.data) data = event.data.json() || {};
    } catch (e) {
        data = {};
    }
    return {
        title: data.title || 'DataPOS',
        body: data.body || '',
        icon: data.icon || '/icons/icon-192.png',
        badge: data.badge || '/icons/icon-192.png',
        url: (data.data && data.data.url) || '/'
    };
}

/* Track unread-notification count in a small dedicated cache so the page's
 * bell badge can read it (SW cannot touch localStorage). Keys:
 *   push-unread/current -> Response('3')
 */
function getUnreadCount() {
    return caches.open('push-unread')
        .then(function (cache) { return cache.match('/push-unread/current'); })
        .then(function (res) { return res ? parseInt(res.text(), 10) : 0; })
        .catch(function () { return 0; });
}

function setUnreadCount(count) {
    return caches.open('push-unread')
        .then(function (cache) {
            var text = String(count);
            return cache.put('/push-unread/current', new Response(text, { headers: { 'Content-Type': 'text/plain' } }));
        })
        .catch(function () {});
}

function clearUnreadCount() {
    return caches.open('push-unread')
        .then(function (cache) { return cache.delete('/push-unread/current'); })
        .catch(function () {});
}

/* Tell every open tab the unread count changed (the page's bell badge
 * listens for these messages). */
function broadcastUnread(count) {
    return self.clients.matchAll({ type: 'window', includeUncontrolled: true })
        .then(function (clients) {
            clients.forEach(function (client) {
                client.postMessage({ type: 'PUSH_UNREAD', count: count });
            });
        })
        .catch(function () {});
}

self.addEventListener('push', function (event) {
    var payload = parsePushPayload(event);

    event.waitUntil(
        getUnreadCount().then(function (count) {
            var next = count + 1;
            return setUnreadCount(next).then(function () { return broadcastUnread(next); });
        }).then(function () {
            return self.registration.showNotification(payload.title, {
                body: payload.body,
                icon: payload.icon,
                badge: payload.badge,
                data: { url: payload.url },
                vibrate: [100, 50, 100]
            });
        }).catch(function () {
            return self.registration.showNotification(payload.title, {
                body: payload.body,
                icon: payload.icon,
                badge: payload.badge,
                data: { url: payload.url }
            });
        })
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    var url = event.notification.data && event.notification.data.url;
    var target = url || '/';
    // Only allow same-origin navigation targets.
    try {
        var parsed = new URL(target, self.location.origin);
        if (parsed.origin !== self.location.origin) target = '/';
    } catch (e) {
        target = '/';
    }

    event.waitUntil(
        clearUnreadCount().then(function () {
            return self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        }).then(function (clientList) {
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url && new URL(client.url).origin === self.location.origin && 'focus' in client) {
                    return client.navigate(target).then(function () { return client.focus(); });
                }
            }
            return self.clients.openWindow(target);
        })
    );
});

/* Clean up the unread counter when the user dismisses a notification. */
self.addEventListener('notificationclose', function (event) {
    event.waitUntil(
        getUnreadCount().then(function (count) {
            return setUnreadCount(Math.max(0, count - 1));
        }).catch(function () {})
    );
});
