@extends('layouts.admin.app')

@section('title', __('messages.sync_manager') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2 sm:p-3 md:p-4')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-2.5 sm:space-y-3 pb-8" x-data="{ payloadModal: false, selectedPayload: null }">
    {{-- 1. Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 sm:p-4 shadow-sm">
        <div class="flex items-center gap-3 min-w-0">
            <span class="w-10 h-10 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300 grid place-items-center text-lg font-bold shrink-0 shadow-sm">
                🔄
            </span>
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 truncate">
                    {{ __('messages.sync_manager') }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.sync_manager_desc') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <form action="{{ route('store.admin.sync.retry_all', $storeRouteParams) }}" method="POST">
                @csrf
                <button type="submit" class="h-9 px-3.5 rounded-xl text-xs font-semibold bg-violet-600 hover:bg-violet-500 text-white shadow-sm transition inline-flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>{{ __('messages.sync_all_now') }}</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-xl p-3 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.sync_status') }}</span>
            <div class="mt-1 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full {{ $health['failed_count'] > 0 ? 'bg-rose-500' : ($health['pending_count'] > 0 ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500') }}"></span>
                <span class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
                    {{ $health['failed_count'] > 0 ? __('messages.sync_issues_detected') : ($health['pending_count'] > 0 ? __('messages.sync_in_progress') : __('messages.sync_all_healthy')) }}
                </span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-xl p-3 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.sync_pending_records') }}</span>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-amber-600 dark:text-amber-400 tabular-nums">
                {{ number_format($health['pending_count']) }}
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-xl p-3 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.sync_synced_count') }}</span>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-emerald-600 dark:text-emerald-400 tabular-nums">
                {{ number_format($health['synced_count']) }}
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-xl p-3 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.sync_last_synced') }}</span>
            <div class="mt-1 text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300">
                {{ $health['last_synced_at'] ? \Carbon\Carbon::parse($health['last_synced_at'])->diffForHumans() : __('messages.sync_never') }}
            </div>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="flex items-center gap-1.5 overflow-x-auto border-b border-slate-200 dark:border-slate-800 pb-2 text-xs">
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
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition shrink-0 {{ $status === $tabKey ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300' : 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800' }}">
                {{ $tabLabel }}
            </a>
        @endforeach
    </div>

    {{-- Records Table --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-2.5 px-3.5">{{ __('messages.sync_th_record_type') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.sync_th_client_tx_id') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.sync_th_created_offline') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.sync_th_synced_at') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.sync_th_status') }}</th>
                        <th class="py-2.5 px-3.5 text-right">{{ __('messages.sync_th_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-slate-700 dark:text-slate-200">
                    @forelse($records as $rec)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-2.5 px-3.5 font-bold">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ strtoupper(str_replace('_', ' ', $rec->record_type)) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3.5 font-mono text-xs">{{ $rec->client_transaction_id }}</td>
                            <td class="py-2.5 px-3.5 font-mono text-slate-500 dark:text-slate-400">{{ $rec->created_offline_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                            <td class="py-2.5 px-3.5 font-mono text-slate-500 dark:text-slate-400">{{ $rec->synced_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                            <td class="py-2.5 px-3.5">
                                @if($rec->status === 'synced')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        ✓ {{ __('messages.sync_badge_synced') }}
                                    </span>
                                @elseif($rec->status === 'failed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300" title="{{ $rec->error_message }}">
                                        ✕ {{ __('messages.sync_badge_failed') }} ({{ $rec->retry_count }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                                        ⏳ {{ __('messages.sync_badge_pending') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3.5 text-right">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <button type="button" @click="selectedPayload = {{ json_encode($rec->payload) }}; payloadModal = true" class="px-2.5 py-1 text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg transition cursor-pointer">
                                        {{ __('messages.sync_btn_payload') }}
                                    </button>
                                    @if($rec->status !== 'synced')
                                        <form action="{{ route('store.admin.sync.retry', ['store_slug' => $store->slug, 'id' => $rec->id]) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold bg-violet-50 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300 rounded-lg hover:bg-violet-100 transition cursor-pointer">
                                                {{ __('messages.sync_retry') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500 text-xs">
                                {{ __('messages.sync_no_records') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-3 border-t border-slate-100 dark:border-slate-800">
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
                <button type="button" @click="payloadModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition cursor-pointer">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
