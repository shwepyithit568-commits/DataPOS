@props(['store', 'btnClass' => ''])

@php
    $storeSlug = $store?->slug ?? request()->route('store_slug') ?? '';
    // Session-authenticated admin endpoints. The old /api/v1/.../sync/* URLs
    // are now machine-to-machine and require the store's sync API key.
    $statusUrl = $storeSlug ? route('store.admin.sync.status', ['store_slug' => $storeSlug]) : '';
    $triggerUrl = $storeSlug ? route('store.admin.sync.trigger', ['store_slug' => $storeSlug]) : '';
    $resolvedBtnClass = $btnClass ?: 'h-11 w-11 sm:h-10 sm:w-auto sm:px-3 rounded-xl';
@endphp

<div x-data="{
    online: navigator.onLine,
    syncing: false,
    pendingCount: 0,
    failedCount: 0,
    lastSynced: null,
    statusUrl: '{{ $statusUrl }}',
    triggerUrl: '{{ $triggerUrl }}',

    init() {
        window.addEventListener('online', () => {
            this.online = true;
            this.syncNow();
        });
        window.addEventListener('offline', () => {
            this.online = false;
        });

        if (this.statusUrl) {
            this.fetchStatus();
            setInterval(() => this.fetchStatus(), 20000);
        }
    },

    async fetchStatus() {
        if (!this.statusUrl || !navigator.onLine) return;
        try {
            const res = await fetch(this.statusUrl, { headers: { 'Accept': 'application/json' } });
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
        if (!this.triggerUrl || this.syncing) return;
        this.syncing = true;
        try {
            const res = await fetch(this.triggerUrl, {
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
}" class="inline-flex items-center flex-shrink-0">
    {{-- Status Button (Clickable to trigger sync) — matching 3D tactile header action buttons --}}
    <button type="button"
            @click="syncNow()"
            :disabled="syncing"
            class="{{ $resolvedBtnClass }} relative inline-flex items-center justify-center gap-1.5 text-xs font-bold tracking-tight shadow-xs transition-all duration-150 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-1 select-none disabled:cursor-not-allowed flex-shrink-0 text-white"
            :class="{
                'sf-btn-3d-success focus:ring-emerald-500': online && !syncing && pendingCount === 0 && failedCount === 0,
                'sf-btn-3d-gold focus:ring-amber-500': syncing || pendingCount > 0,
                'sf-btn-3d-danger focus:ring-rose-500': !online || failedCount > 0
            }"
            :title="online ? (syncing ? '{{ __('messages.sync_in_progress') }}' : (pendingCount > 0 ? pendingCount + ' {{ __('messages.sync_pending_records') }}' : '{{ __('messages.sync_online') }} — {{ __('messages.sync_now') }}')) : '{{ __('messages.sync_offline') }}'"
            aria-label="{{ __('messages.sync_status') }}">
        
        {{-- Status Symbol / Icon --}}
        {{-- 1. Spinning loader when syncing --}}
        <template x-if="syncing">
            <svg class="w-4 h-4 sm:w-3.5 sm:h-3.5 shrink-0 animate-spin text-white" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
        </template>

        {{-- 2. Online Healthy Icon (Cloud Check) --}}
        <template x-if="!syncing && online && pendingCount === 0 && failedCount === 0">
            <svg class="w-4 h-4 sm:w-3.5 sm:h-3.5 shrink-0 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                <polyline points="9 13 11 15 15 11"/>
            </svg>
        </template>

        {{-- 3. Pending Queue Icon (Cloud Upload / Sync) --}}
        <template x-if="!syncing && pendingCount > 0">
            <svg class="w-4 h-4 sm:w-3.5 sm:h-3.5 shrink-0 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242M12 12v9m-4-4 4 4 4-4"/>
            </svg>
        </template>

        {{-- 4. Offline / Error Icon (Cloud Disconnected) --}}
        <template x-if="!syncing && (!online || failedCount > 0) && pendingCount === 0">
            <svg class="w-4 h-4 sm:w-3.5 sm:h-3.5 shrink-0 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m2 2 20 20"/>
                <path d="M5 5a8 8 0 0 0-4 7h1.7a4.5 4.5 0 0 0 8.3 1.5"/>
                <path d="M22.61 16.95A5 5 0 0 0 18 10h-1.26a8 8 0 0 0-7.05-6"/>
            </svg>
        </template>

        {{-- Mobile Badge: Pending Count (Visible only on mobile when > 0) --}}
        <span x-show="pendingCount > 0"
              x-text="pendingCount > 9 ? '9+' : pendingCount"
              class="sm:hidden absolute -top-1 -right-1 px-1 min-w-[16px] h-4 rounded-full bg-amber-950 text-white text-[10px] font-black leading-none flex items-center justify-center ring-2 ring-white dark:ring-slate-900 shadow-xs">
        </span>

        {{-- Mobile Badge: Failed Count (Visible only on mobile when > 0) --}}
        <span x-show="failedCount > 0"
              x-text="failedCount > 9 ? '9+' : failedCount"
              class="sm:hidden absolute -top-1 -right-1 px-1 min-w-[16px] h-4 rounded-full bg-rose-950 text-white text-[10px] font-black leading-none flex items-center justify-center ring-2 ring-white dark:ring-slate-900 shadow-xs">
        </span>

        {{-- Text Labels: Hidden on phone (symbol-only), visible on sm+ screens --}}
        <span x-show="online && !syncing && pendingCount === 0 && failedCount === 0" class="hidden sm:inline whitespace-nowrap leading-none">
            {{ __('messages.sync_online') }}
        </span>
        <span x-show="syncing" class="hidden sm:inline whitespace-nowrap leading-none">
            {{ __('messages.sync_in_progress') }}
        </span>
        <span x-show="!syncing && pendingCount > 0" class="hidden sm:inline whitespace-nowrap leading-none" x-text="pendingCount + ' {{ __('messages.sync_pending_records') }}'"></span>
        <span x-show="!online && pendingCount === 0" class="hidden sm:inline whitespace-nowrap leading-none">
            {{ __('messages.sync_offline') }}
        </span>
        <span x-show="failedCount > 0" class="hidden sm:inline text-rose-100 font-bold ml-0.5 whitespace-nowrap leading-none" x-text="'(' + failedCount + ')'"></span>
    </button>
</div>
