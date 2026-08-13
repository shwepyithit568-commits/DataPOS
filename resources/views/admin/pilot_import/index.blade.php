@extends('layouts.admin.app')

@section('content')
<div class="space-y-6 max-w-6xl">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit">Pilot Data Import</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">AlinnThit pilot — upload → review (dry-run, nothing written) → confirm. Every import is recorded in Import History with downloadable error reports.</p>
        </div>
        <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="shrink-0 text-xs text-violet-600 dark:text-violet-400 font-semibold hover:underline whitespace-nowrap">&larr; Back to Dashboard</a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-lg p-4 text-sm text-red-700 dark:text-red-300 space-y-1">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="flex flex-nowrap gap-2 overflow-x-auto border-b border-gray-200 dark:border-slate-700 pb-px">
        @foreach (['products', 'customers', 'suppliers'] as $t)
            <a href="{{ route('store.admin.pilot-import.index', ['store_slug' => $store->slug, 'tab' => $t]) }}"
               class="shrink-0 px-4 py-2 rounded-t-lg text-sm font-semibold whitespace-nowrap transition-colors {{ $tab === $t ? 'bg-white dark:bg-slate-800 text-violet-700 dark:text-violet-300 border border-b-0 border-gray-200 dark:border-slate-600' : 'text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200' }}">
                {{ ucfirst($t) }}
            </a>
        @endforeach
    </div>

    @php
        $importRoute = route('store.admin.pilot-import.import', ['store_slug' => $store->slug, 'tab' => $tab]);
        $confirmRoute = route('store.admin.pilot-import.confirm', ['store_slug' => $store->slug, 'tab' => $tab]);
        $templateRoute = route('store.admin.pilot-import.template', ['store_slug' => $store->slug, 'tab' => $tab]);
        $columns = match ($tab) {
            'customers' => ['name', 'phone', 'email', 'role'],
            'suppliers' => ['name', 'phone', 'email', 'contact_person', 'address', 'notes'],
            default => ['name', 'sku', 'brand', 'category', 'retail_price', 'stock_status'],
        };
        $required = $tab === 'products' ? 'name, sku, retail_price, wholesale_price, stock_status' : ($tab === 'customers' ? 'name, phone' : 'name');
        $dupRule = $tab === 'products'
            ? 'Products are matched by SKU (case-insensitive). Matching SKUs are skipped or updated depending on the strategy.'
            : ($tab === 'customers'
                ? 'Customers are matched by phone (normalized, e.g. "09 123 456 789" → 9123456789). A phone already attached to this store is skipped or updated; a phone belonging to another store is attached here as a new customer.'
                : 'Suppliers are matched by phone first, then by name (case-insensitive), within this store only.');
    @endphp

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm text-center">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3 shadow-sm">
            <div class="text-xl font-bold">{{ $summary['total_imports'] }}</div>
            <div class="text-xs text-gray-500 dark:text-slate-400">Imports ({{ ucfirst($tab) }})</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3 shadow-sm">
            <div class="text-xl font-bold text-green-700 dark:text-green-400">{{ $summary['successful_rows'] }}</div>
            <div class="text-xs text-gray-500 dark:text-slate-400">Successful Rows</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3 shadow-sm">
            <div class="text-xl font-bold text-red-700 dark:text-red-400">{{ $summary['failed_rows'] }}</div>
            <div class="text-xs text-gray-500 dark:text-slate-400">Failed Rows</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-3 shadow-sm">
            <div class="text-xl font-bold text-amber-700 dark:text-amber-400">{{ $summary['total_imports'] > 0 ? round(($summary['successful_rows'] / max(1, $summary['successful_rows'] + $summary['failed_rows'])) * 100) . '%' : '—' }}</div>
            <div class="text-xs text-gray-500 dark:text-slate-400">Success Rate</div>
        </div>
    </div>

    @if (session('import_preview'))
        @php $preview = session('import_preview'); @endphp
        <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg p-4 space-y-4">
            <div>
                <h3 class="font-bold text-amber-900 dark:text-amber-200">Preview Ready: {{ $preview['filename'] }}</h3>
                <p class="text-xs text-amber-800 dark:text-amber-300">Dry-run only — nothing has been written yet. Review the summary below before saving changes.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 text-sm text-center">
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
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-sky-700 dark:text-sky-400">{{ $preview['attached'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500">Attached</div>
                </div>
            </div>

            @if (!empty($preview['preview_rows']))
                <div class="overflow-x-auto bg-white dark:bg-slate-800 rounded">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 dark:bg-slate-900/60">
                            <tr>
                                <th class="p-2">Row</th>
                                <th class="p-2">Name</th>
                                <th class="p-2">{{ $tab === 'customers' ? 'Phone' : ($tab === 'suppliers' ? 'Phone' : 'SKU') }}</th>
                                @if ($tab === 'products')
                                    <th class="p-2">Brand</th>
                                    <th class="p-2">Category</th>
                                    <th class="p-2">Retail Price</th>
                                @elseif ($tab === 'customers')
                                    <th class="p-2">Email</th>
                                    <th class="p-2">Role</th>
                                @else
                                    <th class="p-2">Contact</th>
                                @endif
                                <th class="p-2">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-slate-700">
                            @foreach ($preview['preview_rows'] as $row)
                                <tr>
                                    <td class="p-2">{{ $row['row'] }}</td>
                                    <td class="p-2">{{ $row['name'] }}</td>
                                    <td class="p-2 font-mono">{{ $tab === 'products' ? ($row['sku'] ?? '') : ($row['phone'] ?? '') }}</td>
                                    @if ($tab === 'products')
                                        <td class="p-2">{{ $row['brand'] ?? '' }}</td>
                                        <td class="p-2">{{ $row['category'] ?? '' }}</td>
                                        <td class="p-2">{{ $row['retail_price'] ?? '' }}</td>
                                    @elseif ($tab === 'customers')
                                        <td class="p-2">{{ $row['email'] ?? '' }}</td>
                                        <td class="p-2">{{ $row['role'] ?? '' }}</td>
                                    @else
                                        <td class="p-2">{{ $row['contact_person'] ?? '' }}</td>
                                    @endif
                                    <td class="p-2">
                                        @php $action = $row['action'] ?? 'create'; @endphp
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $action === 'create' || $action === 'attach' ? 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300' : ($action === 'update' ? 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300') }}">{{ $action }}</span>
                                    </td>
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

            <form method="POST" action="{{ $confirmRoute }}" class="flex flex-nowrap items-end gap-3 overflow-x-auto pb-1 -mx-1 px-1 sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0">
                @csrf
                <input type="hidden" name="token" value="{{ $preview['token'] }}">
                <div class="shrink-0">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Duplicate Handling</label>
                    <select name="duplicate_strategy" class="border dark:border-slate-600 rounded px-3 py-2 text-sm bg-white dark:bg-slate-900">
                        <option value="skip" {{ $preview['duplicate_strategy'] === 'skip' ? 'selected' : '' }}>Skip duplicates</option>
                        <option value="update" {{ $preview['duplicate_strategy'] === 'update' ? 'selected' : '' }}>Update existing</option>
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
            <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 text-sm text-center">
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
                    <div class="text-xl font-bold text-sky-700">{{ $result['attached'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500">Attached</div>
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
            @if ($result['failed'] > 0)
                <p class="text-xs text-red-600 dark:text-red-400">Failed rows are recorded in Import History — download the error report there to fix and re-import them.</p>
            @endif
        </div>
    @endif

    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm space-y-5 transition-colors duration-200">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100">Upload {{ ucfirst($tab) }} File</h2>
            <a href="{{ $templateRoute }}" class="shrink-0 px-3 py-2 bg-green-600 text-white rounded-md text-xs font-semibold hover:bg-green-700 whitespace-nowrap">Download {{ ucfirst($tab) }} Template</a>
        </div>
        <form action="{{ $importRoute }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-1">CSV or XLSX File</label>
                <input type="file" name="file" accept=".csv,.txt,.xlsx" required
                    class="block w-full text-sm text-gray-600 dark:text-slate-300 border border-gray-300 dark:border-slate-600 rounded-lg p-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-violet-500 bg-white dark:bg-slate-900">
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Max 5MB. Supports CSV and XLSX files. The system always uses the current admin store — do not add a store_id column.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-1">Duplicate Handling</label>
                <select name="duplicate_strategy" class="border dark:border-slate-600 rounded px-3 py-2 text-sm bg-white dark:bg-slate-900">
                    <option value="skip">Skip duplicates (default)</option>
                    <option value="update">Update existing records</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-violet-600 text-white rounded font-semibold text-sm hover:bg-violet-700">
                Upload & Preview (dry-run)
            </button>
        </form>

        <div class="border-t dark:border-slate-700 pt-4">
            <h4 class="text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">Supported Columns</h4>
            <div class="bg-gray-50 dark:bg-slate-900 rounded p-3 font-mono text-xs text-gray-600 dark:text-slate-400 overflow-x-auto">
                {{ implode(',', $columns) }}
            </div>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-2">
                <code>{{ $required }}</code> {{ $tab === 'customers' ? 'are' : 'is' }} required. {{ $dupRule }}
            </p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm space-y-3">
        <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100">Recent {{ ucfirst($tab) }} Imports</h2>
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
                            <td colspan="7" class="p-3 text-center text-gray-500">No {{ $tab }} imports yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
