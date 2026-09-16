@extends('layouts.admin.app')

@section('title', __('messages.sync_manager') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-0.5 pb-6" x-data="{ payloadModal: false, selectedPayload: null }">
    {{-- 1. Top Ultra-Dense Header Banner (Standard v4.1) --}}
    <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none transition">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('store.admin.dashboard', $storeRouteParams) }}"
               class="h-6 w-6 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition active:scale-95 shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="w-6 h-6 rounded bg-violet-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs shrink-0">
                <span>🔄</span>
            </div>
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/60 px-1.5 py-0.5 rounded border border-violet-200/50 dark:border-violet-800/50 truncate max-w-[120px] sm:max-w-none">
                    {{ $store->name }}
                </span>
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight truncate">
                    {{ __('messages.sync_manager') }}
                </h1>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono hidden md:inline">
                    · {{ __('messages.sync_manager_desc') }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-1.5 shrink-0 self-end sm:self-auto">
            <form action="{{ route('store.admin.sync.retry_all', $storeRouteParams) }}" method="POST">
                @csrf
                <button type="submit" class="sf-btn-3d-primary h-7 px-2.5 rounded-md text-xs font-bold inline-flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>{{ __('messages.sync_all_now') }}</span>
                </button>
            </form>
        </div>
    </div>

    {{-- 1b. Terminal Sync Credential (machine-to-machine API key) --}}
    <div class="px-2 py-2 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs transition"
         x-data="{ copied: false, copy(text) { navigator.clipboard?.writeText(text).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); }); } }">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2">
            <div class="flex items-start gap-2 min-w-0">
                <div class="w-7 h-7 rounded-lg bg-slate-900 dark:bg-slate-700 text-white flex items-center justify-center text-sm shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs font-black text-slate-900 dark:text-white tracking-tight">{{ __('messages.sync_api_key') }}</span>
                        @if($store->hasSyncApiKey())
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded border bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-400 dark:border-emerald-800/60">
                                {{ __('messages.sync_key_active') }} · <span class="font-mono">••••{{ $store->sync_api_key_last4 }}</span>
                            </span>
                            @if($store->sync_api_key_rotated_at)
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">
                                    {{ $store->sync_api_key_rotated_at->format('Y-m-d H:i') }}
                                </span>
                            @endif
                        @else
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded border bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-400 dark:border-rose-800/60">
                                {{ __('messages.sync_key_none') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                        {{ __('messages.sync_api_key_desc') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
                <form action="{{ route('store.admin.sync.key.rotate', $storeRouteParams) }}" method="POST"
                      data-confirm="{{ __('messages.sync_key_generate_confirm') }}">
                    @csrf
                    <button type="submit" class="sf-btn-3d-primary h-7 px-2.5 rounded-md text-xs font-bold inline-flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>{{ $store->hasSyncApiKey() ? __('messages.sync_key_rotate') : __('messages.sync_key_generate') }}</span>
                    </button>
                </form>

                @if($store->hasSyncApiKey())
                    <form action="{{ route('store.admin.sync.key.revoke', $storeRouteParams) }}" method="POST"
                          data-confirm="{{ __('messages.sync_key_revoke_confirm') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="h-7 px-2.5 rounded-md text-xs font-bold inline-flex items-center gap-1.5 cursor-pointer border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-950 transition">
                            <span>{{ __('messages.sync_key_revoke') }}</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Plaintext key is shown exactly once, immediately after generation. --}}
        @if(session('sync_api_key_plaintext'))
            <div class="mt-2 rounded border border-amber-300 dark:border-amber-700/60 bg-amber-50 dark:bg-amber-950/40 p-2">
                <div class="flex items-center gap-1.5 mb-1">
                    <span class="text-[10px] font-black text-amber-800 dark:text-amber-400 uppercase tracking-wide">
                        {{ __('messages.sync_key_show_once') }}
                    </span>
                </div>
                <div class="flex items-center gap-1.5">
                    <code class="flex-1 min-w-0 text-[11px] font-mono break-all bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-800/60 rounded px-2 py-1 text-slate-900 dark:text-amber-200 select-all">{{ session('sync_api_key_plaintext') }}</code>
                    <button type="button" @click="copy(@js(session('sync_api_key_plaintext')))"
                            class="h-7 px-2.5 shrink-0 rounded-md text-xs font-bold border border-amber-300 dark:border-amber-700/60 bg-white dark:bg-slate-900 text-amber-800 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-950 transition cursor-pointer">
                        <span x-text="copied ? '{{ __('messages.copied') }}' : '{{ __('messages.copy') }}'"></span>
                    </button>
                </div>
                <p class="text-[10px] text-amber-700 dark:text-amber-500 mt-1 leading-snug">
                    {{ __('messages.sync_key_usage_hint') }}
                </p>
            </div>
        @endif
    </div>

    {{-- 2. Centered Row-based 4 Stat Cards (Standard v4.1) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1 select-none">
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.sync_status') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 border {{ $health['failed_count'] > 0 ? 'bg-rose-50 text-rose-600 border-rose-100 dark:bg-rose-950/60 dark:text-rose-400' : ($health['pending_count'] > 0 ? 'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-950/60 dark:text-amber-400' : 'bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-400') }}">
                {{ $health['failed_count'] > 0 ? '⚠️' : ($health['pending_count'] > 0 ? '⏳' : '✅') }}
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.sync_status') }}
                </div>
                <div class="text-xs sm:text-sm font-black {{ $health['failed_count'] > 0 ? 'text-rose-600 dark:text-rose-400' : ($health['pending_count'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }} tracking-tight">
                    {{ $health['failed_count'] > 0 ? __('messages.sync_issues_detected') : ($health['pending_count'] > 0 ? __('messages.sync_in_progress') : __('messages.sync_all_healthy')) }}
                </div>
            </div>
        </div>

        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.sync_pending_records') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 border bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-950/60 dark:text-amber-400">
                ⏳
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.sync_pending_records') }}
                </div>
                <div class="text-xs sm:text-sm font-black font-mono tracking-tight text-amber-600 dark:text-amber-400 tabular-nums">
                    {{ number_format($health['pending_count']) }}
                </div>
            </div>
        </div>

        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.sync_synced_count') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 border bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-400">
                ✓
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.sync_synced_count') }}
                </div>
                <div class="text-xs sm:text-sm font-black font-mono tracking-tight text-emerald-600 dark:text-emerald-400 tabular-nums">
                    {{ number_format($health['synced_count']) }}
                </div>
            </div>
        </div>

        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.sync_last_synced') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 border bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300">
                🕒
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.sync_last_synced') }}
                </div>
                <div class="text-xs sm:text-sm font-black text-slate-700 dark:text-slate-300">
                    {{ $health['last_synced_at'] ? \Carbon\Carbon::parse($health['last_synced_at'])->diffForHumans() : __('messages.sync_never') }}
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Filter Toolbar Tabs --}}
    <div class="px-2 py-1 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center gap-1 overflow-x-auto select-none">
        @php
            $tabs = [
                'all' => __('messages.sync_tab_all'),
                'pending' => __('messages.sync_tab_pending'),
                'synced' => __('messages.sync_tab_synced'),
                'failed' => __('messages.sync_tab_failed')
            ];
        @endphp
        @foreach($tabs as $tabKey => $tabLabel)
            <a href="{{ route('store.admin.sync.index', ['store_slug' => $store->slug, 'status' => $tabKey]) }}"
               class="px-2.5 py-1 rounded text-[11px] font-bold transition shrink-0 {{ $status === $tabKey ? 'bg-violet-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                {{ $tabLabel }}
            </a>
        @endforeach
    </div>

    {{-- 4. Records Table --}}
    <div class="rounded bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-1.5 px-2">{{ __('messages.sync_th_record_type') }}</th>
                        <th class="py-1.5 px-2">{{ __('messages.sync_th_client_tx_id') }}</th>
                        <th class="py-1.5 px-2">{{ __('messages.sync_th_created_offline') }}</th>
                        <th class="py-1.5 px-2">{{ __('messages.sync_th_synced_at') }}</th>
                        <th class="py-1.5 px-2">{{ __('messages.sync_th_status') }}</th>
                        <th class="py-1.5 px-2 text-right">{{ __('messages.sync_th_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-slate-700 dark:text-slate-200">
                    @forelse($records as $rec)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-1.5 px-2 font-bold">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ strtoupper(str_replace('_', ' ', $rec->record_type)) }}
                                </span>
                            </td>
                            <td class="py-1.5 px-2 font-mono text-xs">{{ $rec->client_transaction_id }}</td>
                            <td class="py-1.5 px-2 font-mono text-slate-500 dark:text-slate-400">{{ $rec->created_offline_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                            <td class="py-1.5 px-2 font-mono text-slate-500 dark:text-slate-400">{{ $rec->synced_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                            <td class="py-1.5 px-2">
                                @if($rec->status === 'synced')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        ✓ {{ __('messages.sync_badge_synced') }}
                                    </span>
                                @elseif($rec->status === 'failed')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300" title="{{ $rec->error_message }}">
                                        ✕ {{ __('messages.sync_badge_failed') }} ({{ $rec->retry_count }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                                        ⏳ {{ __('messages.sync_badge_pending') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-1.5 px-2 text-right">
                                <div class="inline-flex items-center justify-end gap-1">
                                    <button type="button" @click="selectedPayload = {{ json_encode($rec->payload) }}; payloadModal = true" class="sf-btn-3d h-6 px-2 text-[11px] font-semibold rounded-md inline-flex items-center gap-1 cursor-pointer">
                                        {{ __('messages.sync_btn_payload') }}
                                    </button>
                                    @if($rec->status !== 'synced')
                                        <form action="{{ route('store.admin.sync.retry', ['store_slug' => $store->slug, 'id' => $rec->id]) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="sf-btn-3d-primary h-6 px-2 text-[11px] font-semibold rounded-md inline-flex items-center gap-1 cursor-pointer">
                                                {{ __('messages.sync_retry') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400 dark:text-slate-500 text-xs">
                                {{ __('messages.sync_no_records') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="px-2 py-1.5 border-t border-slate-100 dark:border-slate-800">
                {{ $records->links() }}
            </div>
        @endif
    </div>

    {{-- Payload Modal --}}
    <div x-show="payloadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-5 shadow-xl space-y-4" @click.outside="payloadModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('messages.sync_modal_title') }}</h3>
                <button type="button" @click="payloadModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">✕</button>
            </div>
            <pre class="bg-slate-950 text-slate-200 p-3.5 rounded-xl text-xs font-mono overflow-x-auto max-h-60" x-text="JSON.stringify(selectedPayload, null, 2)"></pre>
            <div class="text-right">
                <button type="button" @click="payloadModal = false" class="sf-btn-3d px-4 py-2 text-xs font-bold rounded-md cursor-pointer">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
