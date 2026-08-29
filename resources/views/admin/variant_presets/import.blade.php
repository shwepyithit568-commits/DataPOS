@extends('layouts.admin.app')

@section('title', __('messages.variant_preset_import_title') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="space-y-6 max-w-5xl">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit">{{ __('messages.variant_preset_import_title') }}</h1>
        <div class="w-full sm:w-auto flex flex-nowrap items-center gap-2 overflow-x-auto pb-1 -mx-1 px-1 sm:flex-wrap sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0">
            <a href="{{ route('store.admin.variant-presets.import.template', ['store_slug' => $store->slug]) }}" class="shrink-0 px-3 py-2 bg-green-600 text-white rounded-md text-xs font-semibold hover:bg-green-700 whitespace-nowrap">{{ __('messages.download_variant_preset_template') }}</a>
            <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'variant-presets']) }}" class="shrink-0 text-xs text-violet-600 dark:text-violet-400 font-semibold hover:underline whitespace-nowrap">&larr; {{ __('messages.back_to_master_data') }}</a>
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
                <h3 class="font-bold text-amber-900 dark:text-amber-200">{{ __('messages.import_preview_ready', ['file' => $preview['filename']]) }}</h3>
                <p class="text-xs text-amber-800 dark:text-amber-300">{{ __('messages.import_preview_desc') }}</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm text-center">
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold">{{ $preview['total'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.total_rows') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-green-700 dark:text-green-400">{{ $preview['creatable'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.new_records') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-violet-700 dark:text-violet-400">{{ $preview['updatable'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.updates') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-amber-700 dark:text-amber-400">{{ $preview['skipped_duplicate'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.skipped') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-red-700 dark:text-red-400">{{ $preview['failed'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.failed') }}</div>
                </div>
            </div>

            @if (!empty($preview['preview_rows']))
                <div class="overflow-x-auto bg-white dark:bg-slate-800 rounded">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 dark:bg-slate-900/60">
                            <tr>
                                <th class="p-2">{{ __('messages.row') }}</th>
                                <th class="p-2">{{ __('messages.name') }}</th>
                                <th class="p-2">{{ __('messages.category_family') }}</th>
                                <th class="p-2">{{ __('messages.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-slate-700">
                            @foreach ($preview['preview_rows'] as $row)
                                <tr>
                                    <td class="p-2">{{ $row['row'] }}</td>
                                    <td class="p-2 font-bold">{{ $row['name'] }}</td>
                                    <td class="p-2 font-mono">{{ $row['family'] }}</td>
                                    <td class="p-2">{{ $row['action'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if (!empty($preview['failed_rows']))
                <details>
                    <summary class="cursor-pointer text-xs font-semibold text-red-700 dark:text-red-400">{{ __('messages.failed_rows_count', ['count' => count($preview['failed_rows'])]) }}</summary>
                    <div class="mt-2 space-y-1 text-xs bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded p-2 max-h-48 overflow-y-auto">
                        @foreach ($preview['failed_rows'] as $fr)
                            <div>Row {{ $fr['row'] }}{{ isset($fr['name']) && $fr['name'] !== '' ? ' [' . $fr['name'] . ']' : '' }}: {{ $fr['reason'] }}</div>
                        @endforeach
                    </div>
                </details>
            @endif

            <form method="POST" action="{{ route('store.admin.variant-presets.import.confirm', ['store_slug' => $store->slug]) }}" class="flex flex-nowrap items-end gap-3 overflow-x-auto pb-1 -mx-1 px-1 sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0">
                @csrf
                <input type="hidden" name="token" value="{{ $preview['token'] }}">
                <div class="shrink-0">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.duplicate_handling') }}</label>
                    <select name="duplicate_strategy" class="border dark:border-slate-600 rounded px-3 py-2 text-sm bg-white dark:bg-slate-900">
                        <option value="skip" {{ $preview['duplicate_strategy'] === 'skip' ? 'selected' : '' }}>{{ __('messages.skip_duplicates') }}</option>
                        <option value="update" {{ $preview['duplicate_strategy'] === 'update' ? 'selected' : '' }}>{{ __('messages.update_existing') }}</option>
                    </select>
                </div>
                <button type="submit" class="shrink-0 px-4 py-2 bg-green-600 text-white rounded font-semibold text-sm hover:bg-green-700 whitespace-nowrap">{{ __('messages.confirm_import') }}</button>
            </form>
        </div>
    @endif

    <div class="bg-white dark:bg-slate-800 rounded-lg p-6 shadow-sm space-y-4">
        <h2 class="font-bold text-gray-900 dark:text-slate-100">{{ __('messages.upload_file') }}</h2>
        <form method="POST" action="{{ route('store.admin.variant-presets.import', ['store_slug' => $store->slug]) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.select_file_xlsx_csv') }}</label>
                <input type="file" name="file" accept=".csv,.xlsx,.txt" required class="block w-full text-sm text-gray-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100">
                <p class="text-xs text-gray-500 mt-1">{{ __('messages.import_file_types_supported') }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.duplicate_handling') }}</label>
                <select name="duplicate_strategy" class="border dark:border-slate-600 rounded px-3 py-2 text-sm bg-white dark:bg-slate-900 w-full sm:w-64">
                    <option value="skip">{{ __('messages.skip_duplicates') }}</option>
                    <option value="update">{{ __('messages.update_existing') }}</option>
                </select>
            </div>

            <button type="submit" class="px-4 py-2 bg-violet-600 text-white rounded font-semibold text-sm hover:bg-violet-700">{{ __('messages.preview_import') }}</button>
        </form>
    </div>

    @if ($histories->isNotEmpty())
        <div class="bg-white dark:bg-slate-800 rounded-lg p-6 shadow-sm space-y-3">
            <h2 class="font-bold text-gray-900 dark:text-slate-100">{{ __('messages.recent_imports') }}</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 dark:bg-slate-900/60">
                        <tr>
                            <th class="p-2">{{ __('messages.date') }}</th>
                            <th class="p-2">{{ __('messages.file') }}</th>
                            <th class="p-2">{{ __('messages.user') }}</th>
                            <th class="p-2">{{ __('messages.total') }}</th>
                            <th class="p-2">{{ __('messages.success') }}</th>
                            <th class="p-2">{{ __('messages.failed') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-slate-700">
                        @foreach ($histories as $h)
                            <tr>
                                <td class="p-2">{{ $h->created_at->format('Y-m-d H:i') }}</td>
                                <td class="p-2 font-mono">{{ $h->filename }}</td>
                                <td class="p-2">{{ $h->user?->name ?? 'System' }}</td>
                                <td class="p-2">{{ $h->total_rows }}</td>
                                <td class="p-2 text-green-700 dark:text-green-400">{{ $h->success_rows }}</td>
                                <td class="p-2 text-red-700 dark:text-red-400">{{ $h->failed_rows }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
