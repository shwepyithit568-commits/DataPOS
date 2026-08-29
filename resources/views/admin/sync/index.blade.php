@extends('layouts.admin.app')

@section('title', __('messages.sync_manager') ?? 'Offline Sync Manager')

@section('content')
<div class="space-y-6" x-data="{ payloadModal: false, selectedPayload: null }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>🔄</span> {{ __('messages.sync_manager') ?? 'Offline Sync Manager' }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ __('messages.sync_manager_desc') ?? 'Manage offline outbox queue, monitor central cloud synchronization, and retry failed events.' }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <form action="{{ route('store.admin.sync.retry_all', ['store_slug' => $store->slug]) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs sm:text-sm font-semibold rounded-xl shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>{{ __('messages.sync_all_now') ?? 'Sync All Pending' }}</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.status') }}</span>
            <div class="mt-2 flex items-center gap-2">
                <span class="w-3 h-3 rounded-full {{ $health['failed_count'] > 0 ? 'bg-rose-500' : ($health['pending_count'] > 0 ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500') }}"></span>
                <span class="text-base font-bold text-slate-900 dark:text-white">
                    {{ $health['failed_count'] > 0 ? 'Issues Detected' : ($health['pending_count'] > 0 ? 'Syncing...' : 'All Healthy') }}
                </span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.sync_pending_records') ?? 'Pending Outbox' }}</span>
            <div class="mt-2 text-2xl font-black text-amber-600 dark:text-amber-400">
                {{ number_format($health['pending_count']) }}
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.sync_synced_count') ?? 'Synced Records' }}</span>
            <div class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400">
                {{ number_format($health['synced_count']) }}
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.sync_last_synced') ?? 'Last Synced' }}</span>
            <div class="mt-2 text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300">
                {{ $health['last_synced_at'] ? \Carbon\Carbon::parse($health['last_synced_at'])->diffForHumans() : 'Never' }}
            </div>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        @foreach(['all' => 'All Records', 'pending' => 'Pending', 'synced' => 'Synced', 'failed' => 'Failed'] as $tabKey => $tabLabel)
            <a href="{{ route('store.admin.sync.index', ['store_slug' => $store->slug, 'status' => $tabKey]) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $status === $tabKey ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300' : 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800' }}">
                {{ $tabLabel }}
            </a>
        @endforeach
    </div>

    {{-- Records Table --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Record Type</th>
                        <th class="py-3 px-4">Client Tx ID</th>
                        <th class="py-3 px-4">Created (Offline)</th>
                        <th class="py-3 px-4">Synced At</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-200">
                    @forelse($records as $rec)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                            <td class="py-3 px-4 font-bold">
                                <span class="px-2 py-0.5 rounded-md text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ strtoupper(str_replace('_', ' ', $rec->record_type)) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono text-xs">{{ $rec->client_transaction_id }}</td>
                            <td class="py-3 px-4">{{ $rec->created_offline_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                            <td class="py-3 px-4">{{ $rec->synced_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                            <td class="py-3 px-4">
                                @if($rec->status === 'synced')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        ✓ Synced
                                    </span>
                                @elseif($rec->status === 'failed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300" title="{{ $rec->error_message }}">
                                        ✕ Failed ({{ $rec->retry_count }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                                        ⏳ Pending
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right flex items-center justify-end gap-2">
                                <button type="button" @click="selectedPayload = {{ json_encode($rec->payload) }}; payloadModal = true" class="px-2.5 py-1 text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg transition">
                                    Payload
                                </button>
                                @if($rec->status !== 'synced')
                                    <form action="{{ route('store.admin.sync.retry', ['store_slug' => $store->slug, 'id' => $rec->id]) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold bg-violet-50 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300 rounded-lg hover:bg-violet-100 transition">
                                            Retry
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                No sync records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $records->links() }}
            </div>
        @endif
    </div>

    {{-- Payload Modal --}}
    <div x-show="payloadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-5 shadow-xl space-y-4" @click.outside="payloadModal = false">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Record Payload (JSON)</h3>
                <button type="button" @click="payloadModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <pre class="bg-slate-950 text-slate-200 p-3 rounded-xl text-xs overflow-x-auto max-h-60" x-text="JSON.stringify(selectedPayload, null, 2)"></pre>
            <div class="text-right">
                <button type="button" @click="payloadModal = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
