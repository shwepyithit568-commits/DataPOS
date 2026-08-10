@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-6">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Web Push Notifications</h1>
            <p class="admin-page-sub">{{ $store->name }} · Browser push management (VAPID / service worker)</p>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3.5 sm:p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-start gap-2">
            <span class="text-base flex-shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-md text-sm text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Subscriber count --}}
    <div class="admin-hairline-grid grid-cols-1 sm:grid-cols-3">
        <div class="admin-hairline-cell">
            <div class="admin-stat-label">Total subscribers</div>
            <div class="admin-stat-value">{{ number_format($subscriberCount) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label">VAPID keys</div>
            <div class="admin-stat-value text-sm">
                {{ config('webpush.vapid.public_key') ? 'Configured ✓' : 'Missing — run: php artisan vapid:generate' }}
            </div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label">Test endpoint</div>
            <div class="admin-stat-value text-sm">POST /api/push/test</div>
        </div>
    </div>

    {{-- Send test / custom notification --}}
    <div class="admin-panel">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Send Notification</h2>
            <span id="push-send-status" class="text-xs font-semibold"></span>
        </div>
        <div class="p-4 space-y-4">
            <form id="push-custom-form" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="push-title" class="block text-xs font-bold text-gray-600 dark:text-slate-300 mb-1.5">Title</label>
                        <input type="text" id="push-title" name="title" value="Test notification"
                               maxlength="255" autocomplete="off"
                               class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-violet-500 focus:ring-1 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label for="push-url" class="block text-xs font-bold text-gray-600 dark:text-slate-300 mb-1.5">Click-through URL</label>
                        <input type="text" id="push-url" name="url" value="{{ url('/') }}"
                               maxlength="500" autocomplete="off"
                               class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-violet-500 focus:ring-1 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    </div>
                </div>
                <div>
                    <label for="push-body" class="block text-xs font-bold text-gray-600 dark:text-slate-300 mb-1.5">Body</label>
                    <textarea id="push-body" name="body" rows="3" maxlength="1000"
                              class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-violet-500 focus:ring-1 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">This is a test push notification from your store.</textarea>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <button type="submit"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                        📣 Send to all subscribers
                    </button>
                    <button type="button" id="push-test-btn"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-violet-300 px-4 py-2.5 text-sm font-bold text-violet-700 transition hover:bg-violet-50 dark:border-violet-700 dark:text-violet-300 dark:hover:bg-violet-950/40">
                        Send default test
                    </button>
                </div>
            </form>
            @if ($subscriberCount === 0)
                <p class="text-xs font-semibold text-amber-600 dark:text-amber-400">
                    No subscribers yet — a browser must allow notifications and register a subscription (POST /api/push/subscribe) before pushes can be delivered.
                </p>
            @endif
        </div>
    </div>

    {{-- Recent notifications sent --}}
    <div class="admin-panel overflow-x-auto">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Recent notifications sent</h2>
            <span class="text-xs font-semibold text-gray-500 dark:text-slate-400">{{ count($recent) }} logged</span>
        </div>
        @if (count($recent) === 0)
            <p class="p-4 text-sm text-gray-500 dark:text-slate-400">Nothing sent yet — use the form above to send the first test notification.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-[720px] w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-slate-900/60 text-xs text-gray-600 dark:text-slate-300">
                        <tr>
                            <th class="p-3">Title</th>
                            <th class="p-3">Body</th>
                            <th class="p-3">Recipients</th>
                            <th class="p-3">Sent at</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                        @foreach ($recent as $entry)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-900/40">
                                <td class="p-3 font-semibold text-gray-900 dark:text-slate-100">{{ $entry['title'] ?? '' }}</td>
                                <td class="p-3 text-gray-600 dark:text-slate-300 max-w-[24rem] truncate" title="{{ $entry['body'] ?? '' }}">{{ $entry['body'] ?? '' }}</td>
                                <td class="p-3 text-gray-600 dark:text-slate-300">{{ number_format($entry['recipients'] ?? 0) }}</td>
                                <td class="p-3 text-gray-500 dark:text-slate-400 whitespace-nowrap">{{ $entry['sent_at'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<script nonce="{{ $cspNonce }}">
    (function () {
        var statusEl = document.getElementById('push-send-status');
        var form = document.getElementById('push-custom-form');
        var testBtn = document.getElementById('push-test-btn');
        var csrf = document.querySelector('meta[name="csrf-token"]');
        var busy = false;

        function setStatus(text, ok) {
            if (!statusEl) return;
            statusEl.textContent = text;
            statusEl.className = 'text-xs font-semibold ' + (ok ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400');
        }

        function sendPush(payload) {
            if (busy) return;
            busy = true;
            setStatus('Sending…', true);
            fetch('/api/push/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf ? csrf.content : '',
                },
                body: JSON.stringify(payload),
            })
                .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
                .then(function (r) {
                    setStatus(r.data && r.data.message ? r.data.message : (r.ok ? 'Sent.' : 'Failed.'), r.ok);
                    if (r.ok) setTimeout(function () { window.location.reload(); }, 900);
                })
                .catch(function () { setStatus('Request failed.', false); })
                .finally(function () { busy = false; });
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                sendPush({
                    title: document.getElementById('push-title').value,
                    body: document.getElementById('push-body').value,
                    url: document.getElementById('push-url').value,
                });
            });
        }

        if (testBtn) {
            testBtn.addEventListener('click', function () {
                sendPush({
                    title: 'Test notification',
                    body: 'This is a test push notification from your store.',
                    url: window.location.origin + '/',
                });
            });
        }
    })();
</script>
@endsection
