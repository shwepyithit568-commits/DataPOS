@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-5 sm:space-y-6">
    @php
        $totalItems = $totalCount ?? 0;
    @endphp

    {{-- Header --}}
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Glass Finder Items</h1>
            <p class="admin-page-sub">{{ $store->name }} · {{ number_format($totalItems) }} items</p>
        </div>
    </div>

    {{-- Success Flash --}}
    @if (session('success'))
        <div class="p-3.5 sm:p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-start gap-2">
            <span class="text-base flex-shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Error Flash --}}
    @if ($errors->any())
        <div class="p-3.5 sm:p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300 space-y-1">
            <div class="flex items-center gap-2 font-bold"><span>⚠️</span><span>Errors:</span></div>
            @foreach ($errors->all() as $error)
                <div class="pl-6">• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Import Preview --}}
    @if (session('import_preview'))
        @php $preview = session('import_preview'); @endphp
        <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl p-4 space-y-4">
            <div class="flex items-start gap-2">
                <span class="text-xl flex-shrink-0">👀</span>
                <div>
                    <h3 class="font-bold text-amber-900 dark:text-amber-200">Preview Ready: {{ $preview['filename'] }}</h3>
                    <p class="text-xs text-amber-800 dark:text-amber-300">Review Glass Finder rows before saving changes.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm text-center">
                <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                    <div class="text-xl font-bold text-gray-900 dark:text-slate-100">{{ $preview['total'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">Total Rows</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                    <div class="text-xl font-bold text-green-700 dark:text-green-400">{{ $preview['valid_rows'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">Valid Rows</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                    <div class="text-xl font-bold text-amber-700 dark:text-amber-400">{{ $preview['duplicate_rows'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">Duplicate Rows</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                    <div class="text-xl font-bold text-red-700 dark:text-red-400">{{ $preview['failed'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">Failed Rows</div>
                </div>
            </div>

            @if (!empty($preview['preview_rows']))
                <div class="overflow-x-auto bg-white dark:bg-slate-800 rounded-lg">
                    <table class="min-w-[760px] w-full text-xs text-left">
                        <thead class="bg-gray-50 dark:bg-slate-900/60 text-gray-700 dark:text-slate-200">
                            <tr>
                                <th class="p-2 whitespace-nowrap">Row</th>
                                <th class="p-2 whitespace-nowrap">Brand</th>
                                <th class="p-2 whitespace-nowrap">Phone Model</th>
                                <th class="p-2 whitespace-nowrap">Glass Code</th>
                                <th class="p-2 whitespace-nowrap">Normalized</th>
                                <th class="p-2 whitespace-nowrap">Stock</th>
                                <th class="p-2 whitespace-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-slate-700 text-gray-600 dark:text-slate-300">
                            @foreach ($preview['preview_rows'] as $row)
                                <tr>
                                    <td class="p-2">{{ $row['row'] }}</td>
                                    <td class="p-2">{{ $row['brand'] }}</td>
                                    <td class="p-2">{{ $row['phone_model'] }}</td>
                                    <td class="p-2 font-mono">{{ $row['glass_code'] }}</td>
                                    <td class="p-2 font-mono">{{ $row['normalized_glass_code'] }}</td>
                                    <td class="p-2">{{ $row['stock_status'] }}</td>
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
                            <div>Row {{ $fr['row'] }} [{{ $fr['field'] ?? 'row' }}]: {{ $fr['reason'] }}</div>
                        @endforeach
                    </div>
                </details>
            @endif

            <form method="POST" action="{{ route('store.admin.glass-finder.import.confirm', ['store_slug' => $store->slug]) }}">
                @csrf
                <input type="hidden" name="token" value="{{ $preview['token'] }}">
                <button type="submit" class="w-full sm:w-auto px-4 py-2.5 bg-green-600 text-white rounded-lg font-semibold text-sm hover:bg-green-700 shadow flex items-center justify-center gap-2">
                    <span>✓</span><span>Confirm Import</span>
                </button>
            </form>
        </div>
    @endif

    {{-- Import Result --}}
    @if (session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl p-4">
            <h3 class="font-bold text-green-800 dark:text-green-300 mb-3 flex items-center gap-2"><span>✓</span><span>Glass Finder Import Completed</span></h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm text-center">
                <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                    <div class="text-xl font-bold text-gray-900 dark:text-slate-100">{{ $result['total'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">Total</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                    <div class="text-xl font-bold text-green-700 dark:text-green-400">{{ $result['imported'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">Imported</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                    <div class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $result['skipped_duplicate'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">Skipped</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                    <div class="text-xl font-bold text-red-600 dark:text-red-400">{{ $result['failed'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">Failed</div>
                </div>
            </div>
        </div>
    @endif

    {{-- Reusable Admin Toolbar --}}
    <x-admin.toolbar
        :search="request('search', '')"
        searchPlaceholder="Search phone model or glass code..."
        :sort="request('sort', 'newest')"
        :sortOptions="['newest' => 'Newest', 'oldest' => 'Oldest']"
        :filters="[
            'stock_status' => [
                'label' => 'Stock Status',
                'options' => ['in_stock' => 'In Stock', 'out_of_stock' => 'Out of Stock']
            ]
        ]"
        :showViewToggle="false"
        :showExportImport="true"
        :totalCount="$totalCount"
        :paginator="null"
    />

    {{-- Glass Finder Items / Add / Import (tabbed) --}}
    <div x-data="{ tab: 'items' }" class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden transition-colors duration-200">
        {{-- Tab bar --}}
        <div class="flex border-b dark:border-slate-700 bg-gray-50/60 dark:bg-slate-900/40" role="tablist">
            <button type="button" role="tab" :aria-selected="tab === 'items'" @click="tab = 'items'"
                class="flex-1 sm:flex-none sm:px-5 py-3 text-xs sm:text-sm sm:text-base font-semibold flex items-center justify-center gap-1.5 sm:gap-2 transition border-b-2 -mb-px min-w-0"
                :class="tab === 'items' ? 'border-fuchsia-600 text-fuchsia-700 dark:text-fuchsia-300 bg-white dark:bg-slate-800' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-800'">
                <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-fuchsia-100 dark:bg-fuchsia-900/40 text-fuchsia-600 dark:text-fuchsia-400 flex items-center justify-center text-xs sm:text-sm shrink-0">🔍</span>
                <span class="truncate">Glass Finder Items</span>
                <span class="shrink-0 px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-xs font-bold text-gray-600 dark:text-slate-300">{{ number_format($totalCount) }}</span>
            </button>
            <button type="button" role="tab" :aria-selected="tab === 'add'" @click="tab = 'add'"
                class="flex-1 sm:flex-none sm:px-5 py-3 text-xs sm:text-sm sm:text-base font-semibold flex items-center justify-center gap-1.5 sm:gap-2 transition border-b-2 -mb-px min-w-0"
                :class="tab === 'add' ? 'border-violet-600 text-violet-700 dark:text-violet-300 bg-white dark:bg-slate-800' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-800'">
                <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-violet-100 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 flex items-center justify-center text-xs sm:text-sm font-bold shrink-0">+</span>
                <span class="truncate">Add Glass Item</span>
            </button>
            <button type="button" role="tab" :aria-selected="tab === 'import'" @click="tab = 'import'"
                class="flex-1 sm:flex-none sm:px-5 py-3 text-xs sm:text-sm sm:text-base font-semibold flex items-center justify-center gap-1.5 sm:gap-2 transition border-b-2 -mb-px min-w-0"
                :class="tab === 'import' ? 'border-green-600 text-green-700 dark:text-green-300 bg-white dark:bg-slate-800' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-800'">
                <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 flex items-center justify-center text-xs sm:text-sm shrink-0">⬆</span>
                <span class="truncate">Import Excel / CSV</span>
            </button>
        </div>

        {{-- Items tab panel (glass code accordion) --}}
        <div x-show="tab === 'items'" x-cloak x-transition>
            @forelse ($items as $glassCode => $codeItems)
                <div x-data="{ open: {{ $autoOpen ? 'true' : 'false' }} }" class="border-b dark:border-slate-700 last:border-b-0">
                    {{-- Glass Code group header (click to expand/collapse) --}}
                    <div @click="open = !open" role="button" tabindex="0"
                        @keydown.enter.prevent="open = !open" @keydown.space.prevent="open = !open"
                        class="w-full flex items-center gap-3 px-4 sm:px-5 py-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-700/50 transition select-none">
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="font-mono font-bold text-sm sm:text-base text-gray-900 dark:text-slate-100 truncate">{{ $glassCode }}</span>
                        <span class="shrink-0 px-2 py-0.5 rounded-full bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 text-xs font-semibold">{{ $codeItems->count() }}</span>
                        @php
                            $brandsInGroup = $codeItems->pluck('brand')->unique()->values();
                            $inCount = $codeItems->filter(fn ($i) => $i->isInStock())->count();
                        @endphp
                        <span class="hidden sm:inline text-xs text-gray-500 dark:text-slate-400 truncate min-w-0">{{ $brandsInGroup->implode(', ') }}</span>
                        <span class="ml-auto flex items-center gap-1.5 text-xs font-semibold text-gray-400 dark:text-slate-500 shrink-0 whitespace-nowrap">
                            <span class="text-green-600 dark:text-green-400">{{ $inCount }} in</span>
                            <span>·</span>
                            <span class="text-red-500 dark:text-red-400">{{ $codeItems->count() - $inCount }} out</span>
                        </span>
                    </div>

                    {{-- Glass Code group body --}}
                    <div x-show="open" x-cloak x-transition class="divide-y divide-gray-100 dark:divide-slate-700/70 bg-gray-50/60 dark:bg-slate-900/40">
                        @foreach ($codeItems as $item)
                            <div class="flex items-center gap-3 px-4 sm:px-6 py-2.5 sm:py-3">
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-sm text-gray-900 dark:text-slate-100 truncate">{{ $item->phone_model }}</div>
                                    <div class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ $item->brand }}</div>
                                </div>
                                <span class="shrink-0 px-2.5 py-1 text-xs font-bold rounded-full uppercase whitespace-nowrap {{ $item->isInStock() ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300' }}">
                                    {{ $item->stock_status === 'in_stock' ? '✓ In Stock' : '✗ Out' }}
                                </span>
                                <div class="shrink-0 flex items-center gap-3">
                                    <a href="{{ url('/store/' . $store->slug . '/admin/glass-finder/' . $item->id . '/edit') }}" @click.stop class="inline-flex items-center gap-1 text-violet-600 dark:text-violet-400 hover:text-violet-800 dark:hover:text-violet-300 font-semibold text-xs">
                                        ✏ Edit
                                    </a>
                                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/glass-finder/' . $item->id) }}" @click.stop>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" data-confirm="Delete this glass item?" class="inline-flex items-center gap-1 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-semibold text-xs">
                                            🗑 Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-gray-500 dark:text-slate-400">
                    <div class="text-3xl mb-2">🔍</div>
                    <div>No glass finder items found.</div>
                </div>
            @endforelse
        </div>

        {{-- Add tab panel --}}
        <div x-show="tab === 'add'" x-cloak x-transition class="p-4 sm:p-5">
            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/glass-finder') }}" class="space-y-3.5">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Brand</label>
                        <input type="text" name="brand" required placeholder="iPhone, Samsung" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Phone Model</label>
                        <input type="text" name="phone_model" required placeholder="iPhone XR" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Glass Code</label>
                        <input type="text" name="glass_code" required placeholder="GX-001" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Stock Status</label>
                        <select name="stock_status" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                            <option value="in_stock">In Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="w-full sm:w-auto px-4 py-2.5 bg-violet-600 text-white rounded-lg hover:bg-violet-700 font-semibold text-sm shadow flex items-center justify-center gap-2 transition">
                    <span>+</span><span>Save Glass Item</span>
                </button>
            </form>
        </div>

        {{-- Import tab panel --}}
        <div x-show="tab === 'import'" x-cloak x-transition class="p-4 sm:p-5">
            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/glass-finder/import') }}" enctype="multipart/form-data" class="space-y-3.5">
                @csrf
                <a href="{{ route('store.admin.glass-finder.import.template', ['store_slug' => $store->slug]) }}" class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-300 rounded-lg text-xs font-semibold hover:bg-green-100 dark:hover:bg-green-900/40 border border-green-200 dark:border-green-800">
                    ⬇ Download Template
                </a>
                <p class="text-xs text-gray-500 dark:text-slate-400">Columns: <span class="font-mono bg-gray-100 dark:bg-slate-900 px-1.5 py-0.5 rounded">brand, phone_model, glass_code, stock_status</span></p>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Select File</label>
                    <input type="file" name="file" accept=".csv,.xlsx" required class="block w-full text-sm text-gray-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 dark:file:bg-slate-700 file:text-green-700 dark:file:text-green-300 hover:file:bg-green-100 cursor-pointer" />
                </div>
                <button type="submit" class="w-full sm:w-auto px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold text-sm shadow flex items-center justify-center gap-2 transition">
                    <span>⬆</span><span>Upload & Preview</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Recent Glass Finder Imports --}}
    <div class="admin-panel overflow-hidden">
        <div class="admin-quiet-divider px-4 py-3.5">
            <h2 class="admin-section-title flex items-center gap-2">
                <span aria-hidden="true">📋</span><span>Recent Imports</span>
            </h2>
        </div>

        {{-- Card view (mobile only) --}}
        <div class="sm:hidden divide-y dark:divide-slate-700">
            @forelse ($histories as $history)
                <div class="p-3 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono text-xs font-semibold text-gray-900 dark:text-slate-100 truncate">{{ $history->filename }}</span>
                        <span class="text-xs text-gray-400 dark:text-slate-500 whitespace-nowrap">{{ $history->created_at->format('M d, H:i') }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-gray-500 dark:text-slate-400">By {{ $history->user?->name ?? 'System' }}</span>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap text-xs">
                        <span class="px-2 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 font-semibold">Total: {{ $history->total_rows }}</span>
                        <span class="px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 font-semibold">✓ {{ $history->success_rows }}</span>
                        @if ($history->failed_rows > 0)
                            <span class="px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 font-semibold">✗ {{ $history->failed_rows }}</span>
                        @endif
                    </div>
                    @if ($history->error_file_path && $history->failed_rows > 0)
                        <a href="{{ route('store.admin.import-history.errors', ['store_slug' => $store->slug, 'history' => $history]) }}" class="inline-block text-xs font-semibold text-red-600 dark:text-red-400 hover:underline">⬇ Download Error Report</a>
                    @endif
                </div>
            @empty
                <div class="p-6 text-center text-sm text-gray-500 dark:text-slate-400">No import history yet.</div>
            @endforelse
        </div>

        {{-- Table view (sm+) --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600 dark:text-slate-300 min-w-[560px]">
                <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 text-gray-700 dark:text-slate-200">
                    <tr>
                        <th class="p-3 whitespace-nowrap">Date</th>
                        <th class="p-3 whitespace-nowrap">File</th>
                        <th class="p-3 whitespace-nowrap">User</th>
                        <th class="p-3 text-center whitespace-nowrap">Total</th>
                        <th class="p-3 text-center whitespace-nowrap">Success</th>
                        <th class="p-3 text-center whitespace-nowrap">Failed</th>
                        <th class="p-3 whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700">
                    @forelse ($histories as $history)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-700/50 transition">
                            <td class="p-3 whitespace-nowrap">{{ $history->created_at->format('Y-m-d H:i') }}</td>
                            <td class="p-3 font-medium text-gray-900 dark:text-slate-100">{{ $history->filename }}</td>
                            <td class="p-3">{{ $history->user?->name ?? 'System' }}</td>
                            <td class="p-3 text-center font-semibold">{{ $history->total_rows }}</td>
                            <td class="p-3 text-center font-semibold text-green-700 dark:text-green-400">{{ $history->success_rows }}</td>
                            <td class="p-3 text-center font-semibold {{ $history->failed_rows > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }}">{{ $history->failed_rows }}</td>
                            <td class="p-3 whitespace-nowrap">
                                @if ($history->error_file_path && $history->failed_rows > 0)
                                    <a href="{{ route('store.admin.import-history.errors', ['store_slug' => $store->slug, 'history' => $history]) }}" class="text-xs font-semibold text-red-600 dark:text-red-400 hover:underline">Errors</a>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500 dark:text-slate-400">No import history yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
