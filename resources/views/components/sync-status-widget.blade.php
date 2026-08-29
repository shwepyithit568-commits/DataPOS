@props(['store'])

@php
    $storeSlug = $store?->slug ?? request()->route('store_slug') ?? '';
@endphp

<div x-data="{
    online: navigator.onLine,
    syncing: false,
    pendingCount: 0,
    failedCount: 0,
    lastSynced: null,
    storeSlug: '{{ $storeSlug }}',

    init() {
        window.addEventListener('online', () => {
            this.online = true;
            this.syncNow();
        });
        window.addEventListener('offline', () => {
            this.online = false;
        });

        if (this.storeSlug) {
            this.fetchStatus();
            setInterval(() => this.fetchStatus(), 20000);
        }
    },

    async fetchStatus() {
        if (!this.storeSlug || !navigator.onLine) return;
        try {
            const res = await fetch(`/api/v1/store/${this.storeSlug}/sync/status`);
            if (res.ok) {
                const data = await res.json();
                this.pendingCount = data.health?.pending_count || 0;
                this.failedCount = data.health?.failed_count || 0;
                this.lastSynced = data.health?.last_synced_at;
                this.online = true;

                if (this.pendingCount > 0 && !this.syncing) {
                    this.syncNow();
                }
            }
        } catch (e) {
            this.online = false;
        }
    },

    async syncNow() {
        if (!this.storeSlug || this.syncing) return;
        this.syncing = true;
        try {
            const res = await fetch(`/api/v1/store/${this.storeSlug}/sync/trigger`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    'Accept': 'application/json'
                }
            });
            if (res.ok) {
                const data = await res.json();
                this.pendingCount = data.health?.pending_count || 0;
                this.failedCount = data.health?.failed_count || 0;
                this.lastSynced = data.health?.last_synced_at;
            }
        } catch (e) {
            console.warn('Sync trigger error:', e);
        } finally {
            this.syncing = false;
        }
    }
}" class="inline-flex items-center gap-2">
    {{-- Status Pill --}}
    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold tracking-tight transition shadow-xs"
         :class="{
             'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800': online && !syncing && pendingCount === 0 && failedCount === 0,
             'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800 animate-pulse': syncing || pendingCount > 0,
             'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800': !online || failedCount > 0
         }"
         :title="online ? '{{ __('messages.sync_online') ?? 'Online — System is synced' }}' : '{{ __('messages.sync_offline') ?? 'Offline — Transactions queued locally' }}'">
        
        {{-- Status Dot --}}
        <span class="w-2 h-2 rounded-full"
              :class="{
                  'bg-emerald-500 shadow-emerald-500/50 shadow-xs': online && !syncing && pendingCount === 0 && failedCount === 0,
                  'bg-amber-500': syncing || pendingCount > 0,
                  'bg-rose-500': !online || failedCount > 0
              }"></span>

        <span x-show="online && !syncing && pendingCount === 0 && failedCount === 0" class="hidden sm:inline">
            {{ __('messages.sync_online') ?? 'Online' }}
        </span>
        <span x-show="syncing">
            {{ __('messages.sync_in_progress') ?? 'Syncing...' }}
        </span>
        <span x-show="!syncing && pendingCount > 0" x-text="pendingCount + ' {{ __('messages.sync_pending_records') ?? 'pending' }}'"></span>
        <span x-show="!online && pendingCount === 0">
            {{ __('messages.sync_offline') ?? 'Offline' }}
        </span>
        <span x-show="failedCount > 0" class="text-rose-600 font-bold" x-text="'(' + failedCount + ' failed)'"></span>
    </div>

    {{-- Manual Sync Trigger Button --}}
    <button type="button"
            @click="syncNow()"
            :disabled="syncing"
            title="{{ __('messages.sync_now') ?? 'Sync Now' }}"
            class="inline-flex items-center justify-center h-7 w-7 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition disabled:opacity-50"
            :class="{ 'animate-spin': syncing }">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
    </button>
</div>
