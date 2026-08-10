@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit">Import Details</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ $history->filename }}</p>
        </div>
        <div class="w-full sm:w-auto flex flex-nowrap items-center gap-2 overflow-x-auto pb-1 -mx-1 px-1 sm:flex-wrap sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0">
            <a href="{{ route('store.admin.import-history.index', ['store_slug' => $store->slug]) }}" class="shrink-0 text-xs text-violet-600 dark:text-violet-400 font-semibold hover:underline whitespace-nowrap">&larr; Back to Import History</a>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg p-5 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <div class="text-xs font-semibold text-gray-500 dark:text-slate-400">Import Date</div>
                <div class="mt-1 font-medium text-gray-900 dark:text-slate-100">{{ $history->created_at->format('Y-m-d H:i') }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold text-gray-500 dark:text-slate-400">User</div>
                <div class="mt-1 font-medium text-gray-900 dark:text-slate-100">{{ $history->user?->name ?? 'System' }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold text-gray-500 dark:text-slate-400">Import Type</div>
                <div class="mt-1 font-medium text-gray-900 dark:text-slate-100">{{ $history->displayType() }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold text-gray-500 dark:text-slate-400">Status</div>
                <div class="mt-1 font-medium text-gray-900 dark:text-slate-100">{{ $history->status() }}</div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3 text-center">
            <div class="rounded-lg p-4">
                <div class="text-2xl font-bold">{{ $history->total_rows }}</div>
                <div class="text-xs text-gray-500 dark:text-slate-400">Total Rows</div>
            </div>
            <div class="rounded-lg p-4">
                <div class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $history->success_rows }}</div>
                <div class="text-xs text-gray-500 dark:text-slate-400">Successful Rows</div>
            </div>
            <div class="rounded-lg p-4">
                <div class="text-2xl font-bold text-red-700 dark:text-red-400">{{ $history->failed_rows }}</div>
                <div class="text-xs text-gray-500 dark:text-slate-400">Failed Rows</div>
            </div>
        </div>

        <div class="border-t border-gray-200 dark:border-slate-700 pt-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <div class="text-sm font-bold text-gray-900 dark:text-slate-100">Failed Rows: {{ $history->failed_rows }}</div>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Download the CSV report to see row number, field, error message, and original data.</p>
                </div>
                <div class="w-full sm:w-auto flex flex-nowrap items-center gap-2 overflow-x-auto pb-1 -mx-1 px-1 sm:flex-wrap sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0">
                    @if ($history->error_file_path && $history->failed_rows > 0)
                        <a href="{{ route('store.admin.import-history.errors', ['store_slug' => $store->slug, 'history' => $history]) }}" class="shrink-0 px-3 py-2 bg-red-600 text-white rounded-md text-xs font-semibold hover:bg-red-700 whitespace-nowrap">Download Error Report</a>
                    @else
                        <span class="shrink-0 text-xs text-gray-500 dark:text-slate-400 whitespace-nowrap">No error report available.</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
