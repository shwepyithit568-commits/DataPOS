@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-6">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Import History</h1>
            <p class="admin-page-sub">Review product and Glass Finder imports for {{ $store->name }}.</p>
        </div>
        <div class="flex flex-nowrap items-center gap-2">
            <a href="{{ route('store.admin.products.import', ['store_slug' => $store->slug]) }}"
                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-violet-600 px-4 py-2 text-sm font-black text-white shadow-sm transition hover:bg-violet-700">
                Product Import
            </a>
            <a href="{{ route('store.admin.glass-finder.index', ['store_slug' => $store->slug]) }}"
                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                Glass Finder
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3.5 sm:p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-start gap-2">
            <span class="text-base flex-shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="admin-hairline-grid grid-cols-2 lg:grid-cols-4">
        <div class="admin-hairline-cell">
            <div class="admin-stat-label">Total Imports</div>
            <div class="admin-stat-value">{{ $summary['total_imports'] }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-green-600 dark:text-green-400">Successful Rows</div>
            <div class="admin-stat-value">{{ $summary['successful_rows'] }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-red-600 dark:text-red-400">Failed Rows</div>
            <div class="admin-stat-value">{{ $summary['failed_rows'] }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label">Last Import Date</div>
            <div class="admin-stat-value text-sm">
                {{ $summary['last_import_date'] ? \Illuminate\Support\Carbon::parse($summary['last_import_date'])->format('Y-m-d H:i') : 'No imports yet' }}
            </div>
        </div>
    </div>

    <div class="admin-panel overflow-x-auto">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Previous Imports</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[920px] w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-slate-900/60 text-xs text-gray-600 dark:text-slate-300">
                    <tr>
                        <th class="p-3">Import Date</th>
                        <th class="p-3">User</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Filename</th>
                        <th class="p-3 text-right">Total</th>
                        <th class="p-3 text-right">Success</th>
                        <th class="p-3 text-right">Failed</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700 text-gray-700 dark:text-slate-300">
                    @forelse ($histories as $history)
                        <tr>
                            <td class="p-3 whitespace-nowrap">{{ $history->created_at->format('Y-m-d H:i') }}</td>
                            <td class="p-3 whitespace-nowrap">{{ $history->user?->name ?? 'System' }}</td>
                            <td class="p-3 whitespace-nowrap">{{ $history->displayType() }}</td>
                            <td class="p-3 max-w-[220px] truncate">{{ $history->filename }}</td>
                            <td class="p-3 text-right">{{ $history->total_rows }}</td>
                            <td class="p-3 text-right text-green-700 dark:text-green-400 font-semibold">{{ $history->success_rows }}</td>
                            <td class="p-3 text-right text-red-700 dark:text-red-400 font-semibold">{{ $history->failed_rows }}</td>
                            <td class="p-3 whitespace-nowrap">{{ $history->status() }}</td>
                            <td class="p-3">
                                <div class="flex justify-end items-center gap-2 whitespace-nowrap">
                                    <a href="{{ route('store.admin.import-history.show', ['store_slug' => $store->slug, 'history' => $history]) }}" class="px-2.5 py-1.5 text-xs font-semibold rounded bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 hover:bg-violet-100 dark:hover:bg-violet-900/50">View</a>
                                    @if ($history->error_file_path && $history->failed_rows > 0)
                                        <a href="{{ route('store.admin.import-history.errors', ['store_slug' => $store->slug, 'history' => $history]) }}" class="px-2.5 py-1.5 text-xs font-semibold rounded bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/50">Errors CSV</a>
                                    @endif
                                    <form method="POST" action="{{ route('store.admin.import-history.destroy', ['store_slug' => $store->slug, 'history' => $history]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1.5 text-xs font-semibold rounded bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 hover:bg-gray-200 dark:hover:bg-slate-600">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-6 text-center text-gray-500 dark:text-slate-400">No import history yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $histories->links() }}
        </div>
    </div>
</div>
@endsection
