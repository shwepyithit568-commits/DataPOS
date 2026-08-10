@extends('layouts.admin.app')

@section('content')
<div class="space-y-6 max-w-5xl">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit">Import Categories</h1>
        <div class="w-full sm:w-auto flex flex-nowrap items-center gap-2 overflow-x-auto pb-1 -mx-1 px-1 sm:flex-wrap sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0">
            <a href="{{ route('store.admin.categories.import.template', ['store_slug' => $store->slug]) }}" class="shrink-0 px-3 py-2 bg-green-600 text-white rounded-md text-xs font-semibold hover:bg-green-700 whitespace-nowrap">Download Category Template</a>
            <a href="{{ route('store.admin.categories.index', ['store_slug' => $store->slug]) }}" class="shrink-0 text-xs text-violet-600 dark:text-violet-400 font-semibold hover:underline whitespace-nowrap">&larr; Back to Categories</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-lg p-4 text-sm text-red-700 dark:text-red-300 space-y-1">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if (session('import_preview'))
        @php $preview = session('import_preview'); @endphp
        <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg p-4 space-y-4">
            <div>
                <h3 class="font-bold text-amber-900 dark:text-amber-200">Preview Ready: {{ $preview['filename'] }}</h3>
                <p class="text-xs text-amber-800 dark:text-amber-300">Review the summary below before saving changes.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm text-center">
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold">{{ $preview['total'] }}</div>
                    <div class="text-xs text-gray-500">Total</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-green-700 dark:text-green-400">{{ $preview['creatable'] }}</div>
                    <div class="text-xs text-gray-500">New</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-violet-700 dark:text-violet-400">{{ $preview['updatable'] }}</div>
                    <div class="text-xs text-gray-500">Updates</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-amber-700 dark:text-amber-400">{{ $preview['skipped_duplicate'] }}</div>
                    <div class="text-xs text-gray-500">Skipped</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-red-700 dark:text-red-400">{{ $preview['failed'] }}</div>
                    <div class="text-xs text-gray-500">Failed</div>
                </div>
            </div>

            @if (!empty($preview['preview_rows']))
                <div class="overflow-x-auto bg-white dark:bg-slate-800 rounded">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 dark:bg-slate-900/60">
                            <tr>
                                <th class="p-2">Row</th>
                                <th class="p-2">Name</th>
                                <th class="p-2">Parent</th>
                                <th class="p-2">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-slate-700">
                            @foreach ($preview['preview_rows'] as $row)
                                <tr>
                                    <td class="p-2">{{ $row['row'] }}</td>
                                    <td class="p-2">{{ $row['name'] }}</td>
                                    <td class="p-2">{{ $row['parent'] }}</td>
                                    <td class="p-2">{{ $row['action'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if (!empty($preview['failed_rows']))
                <details>
                    <summary class="cursor-pointer text-xs font-semibold text-red-700 dark:text-red-400">Failed Rows ({{ count($preview['failed_rows']) }})</summary>
                    <div class="mt-2 space-y-1 text-xs bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded p-2 max-h-48 overflow-y-auto">
                        @foreach ($preview['failed_rows'] as $fr)
                            <div>Row {{ $fr['row'] }}{{ isset($fr['name']) && $fr['name'] !== '' ? ' [' . $fr['name'] . ']' : '' }}: {{ $fr['reason'] }}</div>
                        @endforeach
                    </div>
                </details>
            @endif

            <form method="POST" action="{{ route('store.admin.categories.import.confirm', ['store_slug' => $store->slug]) }}" class="flex flex-nowrap items-end gap-3 overflow-x-auto pb-1 -mx-1 px-1 sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0">
                @csrf
                <input type="hidden" name="token" value="{{ $preview['token'] }}">
                <div class="shrink-0">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Duplicate Category Handling</label>
                    <select name="duplicate_strategy" class="border dark:border-slate-600 rounded px-3 py-2 text-sm bg-white dark:bg-slate-900">
                        <option value="skip" {{ $preview['duplicate_strategy'] === 'skip' ? 'selected' : '' }}>Skip duplicate categories</option>
                        <option value="update" {{ $preview['duplicate_strategy'] === 'update' ? 'selected' : '' }}>Update existing categories</option>
                    </select>
                </div>
                <button type="submit" class="shrink-0 px-4 py-2 bg-green-600 text-white rounded font-semibold text-sm hover:bg-green-700 whitespace-nowrap">Confirm Import</button>
            </form>
        </div>
    @endif

    @if (session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-lg p-4 space-y-3">
            <h3 class="font-bold text-green-800 dark:text-green-300">Import Completed</h3>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm text-center">
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold">{{ $result['total'] }}</div>
                    <div class="text-xs text-gray-500">Total Rows</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-green-700">{{ $result['imported'] }}</div>
                    <div class="text-xs text-gray-500">Created</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-violet-700">{{ $result['updated'] }}</div>
                    <div class="text-xs text-gray-500">Updated</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-amber-600">{{ $result['skipped_duplicate'] }}</div>
                    <div class="text-xs text-gray-500">Skipped</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-red-600">{{ $result['failed'] }}</div>
                    <div class="text-xs text-gray-500">Failed</div>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm space-y-5 transition-colors duration-200">
        <form action="{{ route('store.admin.categories.import', ['store_slug' => $store->slug]) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-1">XLSX or CSV File</label>
                <input type="file" name="file" accept=".csv,.txt,.xlsx" required
                    class="block w-full text-sm text-gray-600 dark:text-slate-300 border border-gray-300 dark:border-slate-600 rounded-lg p-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-violet-500 bg-white dark:bg-slate-900">
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Max 5MB. Supports CSV and XLSX files.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-1">Duplicate Category Handling</label>
                <select name="duplicate_strategy" class="border dark:border-slate-600 rounded px-3 py-2 text-sm bg-white dark:bg-slate-900">
                    <option value="skip">Skip duplicate categories (default)</option>
                    <option value="update">Update existing categories</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-violet-600 text-white rounded font-semibold text-sm hover:bg-violet-700">
                Upload & Preview
            </button>
        </form>

        <div class="border-t dark:border-slate-700 pt-4">
            <h4 class="text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">Supported Columns</h4>
            <div class="bg-gray-50 dark:bg-slate-900 rounded p-3 font-mono text-xs text-gray-600 dark:text-slate-400 overflow-x-auto">
                name,slug,parent,description,icon
            </div>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-2">
                <code>name</code> is required. <code>parent</code> is the name or slug of a Main category
                (existing in this store or defined earlier in the same file) — blank means top-level.
                The tree stays two levels: Main categories and their Sub-categories. Categories are matched by
                slug first, then by name (case-insensitive). <code>icon</code> is up to 8 characters.
                The system always uses the current admin store — do not add a store_id column.
            </p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm space-y-3">
        <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100">Recent Category Imports</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 dark:bg-slate-900/60 text-gray-600 dark:text-slate-300">
                    <tr>
                        <th class="p-2">Date</th>
                        <th class="p-2">File</th>
                        <th class="p-2">User</th>
                        <th class="p-2">Total</th>
                        <th class="p-2">Success</th>
                        <th class="p-2">Failed</th>
                        <th class="p-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700">
                    @forelse ($histories as $history)
                        <tr>
                            <td class="p-2">{{ $history->created_at->format('Y-m-d H:i') }}</td>
                            <td class="p-2">{{ $history->filename }}</td>
                            <td class="p-2">{{ $history->user?->name ?? 'System' }}</td>
                            <td class="p-2">{{ $history->total_rows }}</td>
                            <td class="p-2">{{ $history->success_rows }}</td>
                            <td class="p-2">{{ $history->failed_rows }}</td>
                            <td class="p-2 whitespace-nowrap">
                                @if ($history->error_file_path && $history->failed_rows > 0)
                                    <a href="{{ route('store.admin.import-history.errors', ['store_slug' => $store->slug, 'history' => $history]) }}" class="text-xs font-semibold text-red-600 dark:text-red-400 hover:underline">Download Error Report</a>
                                @else
                                    <span class="text-xs text-gray-400">No errors</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-3 text-center text-gray-500">No import history yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
