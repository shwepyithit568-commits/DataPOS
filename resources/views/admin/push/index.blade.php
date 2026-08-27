@extends('layouts.admin.app')

@php
    $isVapidConfigured = (bool) config('webpush.vapid.public_key');
    $historyUrl = route('store.admin.push.history', ['store_slug' => $store->slug]);
@endphp

@section('title', __('messages.push_admin_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="pushBroadcastStudio({
         subscriberCount: {{ (int) $subscriberCount }},
         defaultUrl: '{{ url('/?store_slug=' . $store->slug) }}',
         vapidKey: '{{ config('webpush.vapid.public_key') }}'
     })">

    {{-- ============================================================
         PAGE HEADER — eyebrow badge, title, subtitle, CTA row
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-indigo-100 dark:border-indigo-900/60 mb-0.5">
                <span>🔔</span>
                <span>{{ __('messages.sidebar_push_notifications') }}</span>
                <span class="text-slate-400 dark:text-slate-500">·</span>
                <span class="font-normal normal-case text-slate-500 dark:text-slate-400">Broadcast Studio & VAPID</span>
            </div>
            <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                {{ __('messages.push_admin_title') }}
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                {{ $store->name }} · {{ __('messages.push_admin_subtitle') }}
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            {{-- Quick Subscribe Button --}}
            <button type="button" @click="subscribeCurrentBrowser()" :disabled="subscribing"
                    class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 transition flex items-center gap-1.5 active:scale-95 shadow-2xs cursor-pointer">
                <span x-show="!subscribing">🔔</span>
                <span x-show="subscribing" class="animate-spin text-xs">⏳</span>
                <span>ဤ Browser မှ အသိပေးချက်ဖွင့်မည်</span>
            </button>

            <a href="{{ $historyUrl }}"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1.5 active:scale-95 shadow-2xs">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ __('messages.push_history_btn') }} ({{ count($recent) }})</span>
            </a>
        </div>
    </header>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="w-full p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="w-full p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-2xs">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ============================================================
         KPI STAT CARDS — 3 compact system status summary cards
         ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5 sm:gap-2">
        {{-- Total Subscribers --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.push_total_subscribers') }}</span>
                <span class="text-xs">👥</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 mt-1 font-mono tracking-tight" x-text="subscriberCount">
                {{ number_format($subscriberCount) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Active browser push endpoints</div>
        </div>

        {{-- VAPID Keys Status --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.push_server_status') }}</span>
                <span class="text-xs">🔐</span>
            </div>
            <div class="text-sm sm:text-base font-black {{ $isVapidConfigured ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }} mt-1 flex items-center gap-1.5">
                <span>{{ $isVapidConfigured ? '● VAPID Configured' : '○ Key Missing' }}</span>
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">RFC 8292 Encryption standard</div>
        </div>

        {{-- Dispatch Channel --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.push_dispatch_channel') }}</span>
                <span class="text-xs">⚡</span>
            </div>
            <div class="text-sm sm:text-base font-black text-indigo-600 dark:text-indigo-400 mt-1 font-mono">
                WebPush ServiceWorker
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Endpoint: POST /api/push/test</div>
        </div>
    </div>

    {{-- Subscriber Notice Banner if 0 --}}
    <div x-show="subscriberCount === 0" class="p-3 bg-gradient-to-r from-amber-50 to-indigo-50/40 dark:from-amber-950/40 dark:to-indigo-950/20 border border-amber-200 dark:border-amber-800/80 rounded-lg text-xs text-amber-900 dark:text-amber-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 shadow-2xs">
        <div class="flex items-center gap-2">
            <span class="text-base">💡</span>
            <div>
                <strong class="block">လက်ရှိတွင် အသိပေးချက် လက်ခံထားသော Browser မရှိသေးပါ (0 Subscribers)</strong>
                <span class="text-[11px] text-slate-600 dark:text-slate-400">စမ်းသပ်ရန်အတွက် သင့်လက်ရှိ Browser ကို အသိပေးချက်စာရင်းသွင်းလိုက်ပါက ချက်ချင်း Push စမ်းသပ်နိုင်ပါမည်။</span>
            </div>
        </div>
        <button type="button" @click="subscribeCurrentBrowser()" :disabled="subscribing"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg shadow-2xs transition active:scale-95 shrink-0">
            🔔 ဤ Browser မှ အသိပေးချက်ဖွင့်မည်
        </button>
    </div>

    {{-- ============================================================
         MAIN BROADCAST STUDIO — Compose (Left) & Real-Time Preview (Right)
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 sm:gap-2.5 items-start">
        
        {{-- Left 2 Columns: Notification Compose Form --}}
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="text-xs">📣</span>
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">Compose Push Broadcast</h2>
                </div>
                <span id="push-send-status" class="text-xs font-bold"></span>
            </div>

            {{-- Quick Templates / Chips --}}
            <div class="space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">⚡ Quick Message Presets:</span>
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" @click="applyPreset('promo')"
                            class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 hover:bg-violet-100 border border-violet-200 dark:border-violet-800 transition">
                        📢 Special Promotion
                    </button>
                    <button type="button" @click="applyPreset('new_stock')"
                            class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 hover:bg-sky-100 border border-sky-200 dark:border-sky-800 transition">
                        ✨ New Stock Arrival
                    </button>
                    <button type="button" @click="applyPreset('glass_finder')"
                            class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-fuchsia-50 text-fuchsia-700 dark:bg-fuchsia-950/60 dark:text-fuchsia-300 hover:bg-fuchsia-100 border border-fuchsia-200 dark:border-fuchsia-800 transition">
                        📱 Phone Glass Finder
                    </button>
                    <button type="button" @click="applyPreset('order_update')"
                            class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-100 border border-emerald-200 dark:border-emerald-800 transition">
                        📦 Delivery & Support
                    </button>
                </div>
            </div>

            <form id="push-custom-form" class="space-y-3 pt-1" @submit.prevent="submitBroadcast()">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- Title --}}
                    <div>
                        <label for="push-title" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            {{ __('messages.push_send_title') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="push-title" name="title" x-model="title" maxlength="255" required autocomplete="off"
                               class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none transition"
                               placeholder="e.g. 📢 မင်္ဂလာပါ! အထူးပရိုမိုးရှင်း စတင်ပါပြီ" />
                    </div>

                    {{-- URL --}}
                    <div>
                        <label for="push-url" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                            {{ __('messages.push_send_url') }}
                        </label>
                        <input type="text" id="push-url" name="url" x-model="url" maxlength="500" autocomplete="off"
                               class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs font-mono text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none transition"
                               placeholder="https://..." />
                    </div>
                </div>

                {{-- Body Message --}}
                <div>
                    <label for="push-body" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                        {{ __('messages.push_send_body') }} <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="push-body" name="body" x-model="body" rows="3" maxlength="1000" required
                              class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none transition"
                              placeholder="ဖောက်သည်များထံ ပေးပို့မည့် အသေးစိတ် အသိပေးစာသား..."></textarea>
                </div>

                {{-- Action Buttons (Always Clickable & Highlighted) --}}
                <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="submit" :disabled="busy"
                            class="px-4 py-2.5 bg-violet-600 hover:bg-violet-700 active:scale-95 text-white rounded-lg font-black text-xs shadow-md hover:shadow-lg flex items-center gap-1.5 transition cursor-pointer">
                        <span x-show="!busy">📣</span>
                        <span x-show="busy" class="animate-spin text-xs">⏳</span>
                        <span>{{ __('messages.push_send_btn') }} (<span x-text="subscriberCount"></span>)</span>
                    </button>

                    <button type="button" id="push-test-btn" @click="sendTestDefault()" :disabled="busy"
                            class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 active:scale-95 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-lg font-bold text-xs transition cursor-pointer">
                        <span>{{ __('messages.push_send_test_btn') }}</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Right 1 Column: Live Push Notification Mockup --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3 lg:sticky lg:top-2">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <span class="text-xs">📱</span>
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">Device Screen Preview</h3>
            </div>

            <p class="text-[11px] text-slate-400">This simulates how the push notification banner appears on the customer's phone or desktop.</p>

            {{-- Android / Desktop Notification Card Simulator --}}
            <div class="p-3 bg-slate-900 text-white rounded-xl shadow-lg border border-slate-700/80 space-y-2 font-sans select-none">
                {{-- Mock Header --}}
                <div class="flex items-center justify-between text-[10px] text-slate-400 pb-1.5 border-b border-slate-800">
                    <div class="flex items-center gap-1.5 font-bold">
                        <span class="w-4 h-4 rounded bg-violet-600 grid place-items-center text-[9px] text-white font-mono">D</span>
                        <span class="text-slate-200">{{ $store->name }}</span>
                        <span>· Web Push</span>
                    </div>
                    <span>Just now</span>
                </div>

                {{-- Mock Body --}}
                <div class="space-y-1">
                    <h4 class="text-xs font-bold text-white tracking-tight" x-text="title || 'Notification Title...'"></h4>
                    <p class="text-[11px] text-slate-300 line-clamp-3 leading-snug" x-text="body || 'Notification message body will render here as you type...'"></p>
                </div>

                {{-- Mock Destination --}}
                <div class="pt-1 flex items-center justify-between text-[10px] text-violet-400 font-mono">
                    <span class="truncate" x-text="url || '{{ url('/') }}'"></span>
                    <span class="text-slate-500 shrink-0">Tap to open ↗</span>
                </div>
            </div>
        </div>

    </div>

    {{-- ============================================================
         RECENT BROADCASTS LOG TABLE
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden transition">
        <div class="px-3 py-2.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xs">📋</span>
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">{{ __('messages.push_recent_sends') }}</h3>
            </div>
            <span class="text-[11px] text-slate-400 font-mono">{{ count($recent) }} logged</span>
        </div>

        @if (count($recent) === 0)
            <div class="p-8 text-center text-xs text-slate-400 font-bold">
                Nothing sent yet — use the form above to dispatch your first push notification.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 min-w-[600px]">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="p-2.5">Title</th>
                            <th class="p-2.5">Message Body</th>
                            <th class="p-2.5 text-center">Recipients</th>
                            <th class="p-2.5 text-right">Sent Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($recent as $entry)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="p-2.5 font-bold text-slate-900 dark:text-slate-100 max-w-[14rem] truncate">{{ $entry['title'] ?? 'Test Notification' }}</td>
                                <td class="p-2.5 text-slate-600 dark:text-slate-400 max-w-[20rem] truncate" title="{{ $entry['body'] ?? '' }}">{{ $entry['body'] ?? '' }}</td>
                                <td class="p-2.5 text-center font-mono font-bold text-violet-600 dark:text-violet-400">{{ number_format($entry['recipients'] ?? 0) }}</td>
                                <td class="p-2.5 text-right font-mono text-[11px] text-slate-400 whitespace-nowrap">{{ $entry['sent_at'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

<script nonce="{{ $cspNonce }}">
function pushBroadcastStudio(config) {
    return {
        title: '📢 အထူးသတင်းလွှာ · Special Promotion',
        body: 'ကျွန်ုပ်တို့၏ စတိုးဆိုင်ခွဲတွင် ပစ္စည်းအသစ်များနှင့် အထူးလျှော့စျေးများ စတင်နေပါပြီ!',
        url: config.defaultUrl || '',
        subscriberCount: config.subscriberCount || 0,
        busy: false,
        subscribing: false,

        applyPreset(type) {
            if (type === 'promo') {
                this.title = '🔥 အထူးလျှော့စျေး ပရိုမိုးရှင်း စတင်ပါပြီ';
                this.body = 'လူကြိုက်များသော ဖုန်းသုံးပစ္စည်းများကို ယခုလကုန်အထိ ၁၀% မှ ၃၀% အထိ လျှော့စျေးဖြင့် ဝယ်ယူနိုင်ပါပြီ!';
                this.url = config.defaultUrl + '&promo=flash_sale';
            } else if (type === 'new_stock') {
                this.title = '✨ ပစ္စည်းအသစ်များ ရောက်ရှိပါပြီ';
                this.body = 'နောက်ဆုံးပေါ် ဖုန်းကာဗာ၊ ဖုန်းမှန်ကပ်နှင့် အားသွင်းကြိုး အသစ်များကို စတိုးတွင် ကြည့်ရှုနိုင်ပါပြီ။';
                this.url = config.defaultUrl + '&category=accessories';
            } else if (type === 'glass_finder') {
                this.title = '🔍 ဖုန်းမှန် ရှာဖွေစနစ် အသစ်';
                this.body = 'မိမိဖုန်း မော်ဒယ်နှင့် ကိုက်ညီသော ဖုန်းမှန်ကပ်များကို စက္ကန့်ပိုင်းအတွင်း ရှာဖွေဝယ်ယူလိုက်ပါ။';
                this.url = window.location.origin + '/glass-finder?store_slug={{ $store->slug }}';
            } else if (type === 'order_update') {
                this.title = '📦 အော်ဒါနှင့် ပို့ဆောင်မှု သတင်းလွှာ';
                this.body = 'ကျွန်ုပ်တို့၏ စတိုးမှ အော်ဒါများကို အချိန်နှင့်တစ်ပြေးညီ မြန်ဆန်စွာ ပို့ဆောင်ပေးနေပါသည်။';
                this.url = config.defaultUrl;
            }
        },

        setStatus(text, ok) {
            const statusEl = document.getElementById('push-send-status');
            if (!statusEl) return;
            statusEl.textContent = text;
            statusEl.className = 'text-xs font-bold ' + (ok ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400');
        },

        urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        },

        async subscribeCurrentBrowser() {
            if (this.subscribing) return;
            this.subscribing = true;
            this.setStatus('Registering Web Push subscription…', true);

            try {
                if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                    alert('သင့် Browser သည် Web Push ကို Support မလုပ်ပါ (HTTPS သို့မဟုတ် localhost လိုအပ်ပါသည်)။');
                    this.setStatus('Web Push unsupported in this browser.', false);
                    return;
                }

                const perm = await Notification.requestPermission();
                if (perm !== 'granted') {
                    alert('Notification permission ကို Allow မပေးထားပါသဖြင့် အသိပေးချက် ဖွင့်မရပါ။ Browser settings မှ Notification ဖွင့်ပေးပါ။');
                    this.setStatus('Notification permission denied.', false);
                    return;
                }

                const reg = await navigator.serviceWorker.register('/sw.js');
                await navigator.serviceWorker.ready;

                const vapidKey = (config.vapidKey || '').trim() || (document.querySelector('meta[name="vapid-public-key"]')?.content || '').trim();

                if (!vapidKey) {
                    alert('VAPID Public Key မတွေ့ပါ။ Server configuration ကို စစ်ဆေးပါ။');
                    this.setStatus('VAPID key missing.', false);
                    return;
                }

                const convertedKey = this.urlBase64ToUint8Array(vapidKey);
                const sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedKey
                });

                const p256dh = btoa(String.fromCharCode.apply(null, new Uint8Array(sub.getKey('p256dh'))))
                    .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
                const auth = btoa(String.fromCharCode.apply(null, new Uint8Array(sub.getKey('auth'))))
                    .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');

                const csrf = document.querySelector('meta[name="csrf-token"]');
                const res = await fetch('/api/push/subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf ? csrf.content : '',
                    },
                    body: JSON.stringify({
                        endpoint: sub.endpoint,
                        keys: { p256dh, auth }
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.setStatus('Device subscribed successfully! You can now send push.', true);
                    this.subscriberCount = Math.max(1, this.subscriberCount + 1);
                    alert('✅ သင့် Browser ကို Web Push လက်ခံသူအဖြစ် အောင်မြင်စွာ စာရင်းသွင်းပြီးပါပြီ! ယခု စမ်းသပ် Push ပို့နိုင်ပါပြီ။');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.setStatus(data.message || 'Subscription failed.', false);
                }
            } catch (err) {
                console.error(err);
                this.setStatus('Subscription error: ' + err.message, false);
                alert('အသိပေးချက် ဖွင့်ရာတွင် အမှားဖြစ်ပေါ်ပါသည်: ' + err.message);
            } finally {
                this.subscribing = false;
            }
        },

        sendPushPayload(payload) {
            if (this.busy) return;

            if (this.subscriberCount === 0) {
                const proceed = confirm('လက်ရှိတွင် အသိပေးချက် စာရင်းသွင်းထားသော Browser မရှိသေးပါ (0 Subscribers)။ သင့် Browser မှ Notification ဖွင့်ပြီး စမ်းသပ်လိုပါသလား?');
                if (proceed) {
                    this.subscribeCurrentBrowser();
                }
                return;
            }

            this.busy = true;
            this.setStatus('Sending broadcast…', true);
            const csrf = document.querySelector('meta[name="csrf-token"]');

            fetch('/api/push/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf ? csrf.content : '',
                },
                body: JSON.stringify(payload),
            })
                .then(res => res.json().then(data => ({ ok: res.ok, data })))
                .then(r => {
                    this.setStatus(r.data && r.data.message ? r.data.message : (r.ok ? 'Sent successfully!' : 'Broadcast failed.'), r.ok);
                    if (r.ok) {
                        setTimeout(() => window.location.reload(), 1000);
                    }
                })
                .catch(() => this.setStatus('Request failed. Please check network connection.', false))
                .finally(() => this.busy = false);
        },

        submitBroadcast() {
            this.sendPushPayload({
                title: this.title,
                body: this.body,
                url: this.url,
            });
        },

        sendTestDefault() {
            this.sendPushPayload({
                title: 'Test Notification',
                body: 'This is a test web push notification from {{ $store->name }}.',
                url: this.url || window.location.origin + '/',
            });
        }
    };
}
</script>
@endsection
