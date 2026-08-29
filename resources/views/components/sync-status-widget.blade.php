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
}" class="inline-flex items-center">
    {{-- Status Pill (Clickable to trigger sync) --}}
    <button type="button"
            @click="syncNow()"
            :disabled="syncing"
            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold tracking-tight transition shadow-xs cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-emerald-500 disabled:cursor-not-allowed"
            :class="{
                'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-950/60': online && !syncing && pendingCount === 0 && failedCount === 0,
                'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800 animate-pulse': syncing || pendingCount > 0,
                'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-950/60': !online || failedCount > 0
            }"
            :title="online ? (syncing ? '{{ __('messages.sync_in_progress') ?? 'Syncing...' }}' : '{{ __('messages.sync_online') ?? 'Online — Click to sync' }}') : '{{ __('messages.sync_offline') ?? 'Offline — Transactions queued locally' }}'">
        
        {{-- Status Dot / Spinner --}}
        <template x-if="syncing">
            <svg class="w-2.5 h-2.5 animate-spin text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
        </template>
        <template x-if="!syncing">
            <span class="w-2 h-2 rounded-full"
                  :class="{
                      'bg-emerald-500 shadow-emerald-500/50 shadow-xs': online && pendingCount === 0 && failedCount === 0,
                      'bg-amber-500': pendingCount > 0,
                      'bg-rose-500': !online || failedCount > 0
                  }"></span>
        </template>

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
    </button>
</div>
