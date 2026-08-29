@extends('layouts.admin.app')

@section('content')
<div class="space-y-6 max-w-5xl">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit">{{ __('messages.brand_import_title') }}</h1>
        <div class="w-full sm:w-auto flex flex-nowrap items-center gap-2 overflow-x-auto pb-1 -mx-1 px-1 sm:flex-wrap sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0">
            <a href="{{ route('store.admin.brands.import.template', ['store_slug' => $store->slug]) }}" class="shrink-0 px-3 py-2 bg-green-600 text-white rounded-md text-xs font-semibold hover:bg-green-700 whitespace-nowrap">{{ __('messages.download_brand_template') }}</a>
            <a href="{{ route('store.admin.brands.index', ['store_slug' => $store->slug]) }}" class="shrink-0 text-xs text-violet-600 dark:text-violet-400 font-semibold hover:underline whitespace-nowrap">&larr; {{ __('messages.back_to_brands') }}</a>
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
                <h3 class="font-bold text-amber-900 dark:text-amber-200">{{ __('messages.brand_import_preview_ready', ['file' => $preview['filename']]) }}</h3>
                <p class="text-xs text-amber-800 dark:text-amber-300">{{ __('messages.brand_import_review_desc') }}</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm text-center">
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold">{{ $preview['total'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.brand_import_total') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-green-700 dark:text-green-400">{{ $preview['creatable'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.brand_import_new') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-violet-700 dark:text-violet-400">{{ $preview['updatable'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.brand_import_updates') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-amber-700 dark:text-amber-400">{{ $preview['skipped_duplicate'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.brand_import_skipped') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-red-700 dark:text-red-400">{{ $preview['failed'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.brand_import_failed') }}</div>
                </div>
            </div>

            @if (!empty($preview['preview_rows']))
                <div class="overflow-x-auto bg-white dark:bg-slate-800 rounded">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 dark:bg-slate-900/60">
                            <tr>
                                <th class="p-2">{{ __('messages.brand_import_row_col') }}</th>
                                <th class="p-2">{{ __('messages.brand_import_name_col') }}</th>
                                <th class="p-2">{{ __('messages.brand_import_slug_col') }}</th>
                                <th class="p-2">{{ __('messages.brand_import_action_col') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-slate-700">
                            @foreach ($preview['preview_rows'] as $row)
                                <tr>
                                    <td class="p-2">{{ $row['row'] }}</td>
                                    <td class="p-2">{{ $row['name'] }}</td>
                                    <td class="p-2 font-mono">{{ $row['slug'] }}</td>
                                    <td class="p-2">{{ $row['action'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if (!empty($preview['failed_rows']))
                <details>
                    <summary class="cursor-pointer text-xs font-semibold text-red-700 dark:text-red-400">{{ __('messages.brand_import_failed_rows', ['count' => count($preview['failed_rows'])]) }}</summary>
                    <div class="mt-2 space-y-1 text-xs bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded p-2 max-h-48 overflow-y-auto">
                        @foreach ($preview['failed_rows'] as $fr)
                            <div>{{ __('messages.brand_import_row_prefix', ['row' => $fr['row']]) }}{{ isset($fr['name']) && $fr['name'] !== '' ? ' [' . $fr['name'] . ']' : '' }}: {{ $fr['reason'] }}</div>
                        @endforeach
                    </div>
                </details>
            @endif

            <form method="POST" action="{{ route('store.admin.brands.import.confirm', ['store_slug' => $store->slug]) }}" class="flex flex-nowrap items-end gap-3 overflow-x-auto pb-1 -mx-1 px-1 sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0">
                @csrf
                <input type="hidden" name="token" value="{{ $preview['token'] }}">
                <div class="shrink-0">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.duplicate_brand_handling') }}</label>
                    <select name="duplicate_strategy" class="border dark:border-slate-600 rounded px-3 py-2 text-sm bg-white dark:bg-slate-900">
                        <option value="skip" {{ $preview['duplicate_strategy'] === 'skip' ? 'selected' : '' }}>{{ __('messages.skip_duplicate_brands') }}</option>
                        <option value="update" {{ $preview['duplicate_strategy'] === 'update' ? 'selected' : '' }}>{{ __('messages.update_existing_brands') }}</option>
                    </select>
                </div>
                <button type="submit" class="shrink-0 px-4 py-2 bg-green-600 text-white rounded font-semibold text-sm hover:bg-green-700 whitespace-nowrap">{{ __('messages.brand_import_confirm') }}</button>
            </form>
        </div>
    @endif

    @if (session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-lg p-4 space-y-3">
            <h3 class="font-bold text-green-800 dark:text-green-300">{{ __('messages.import_completed') }}</h3>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm text-center">
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold">{{ $result['total'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.brand_import_total_rows') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-green-700">{{ $result['imported'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.brand_import_created') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-violet-700">{{ $result['updated'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.brand_import_updated') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-amber-600">{{ $result['skipped_duplicate'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.brand_import_skipped') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-red-600">{{ $result['failed'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.brand_import_failed') }}</div>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm space-y-5 transition-colors duration-200">
        <form action="{{ route('store.admin.brands.import', ['store_slug' => $store->slug]) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.xlsx_or_csv_file') }}</label>
                <input type="file" name="file" accept=".csv,.txt,.xlsx" required
                    class="block w-full text-sm text-gray-600 dark:text-slate-300 border border-gray-300 dark:border-slate-600 rounded-lg p-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-violet-500 bg-white dark:bg-slate-900">
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">{{ __('messages.brand_import_file_hint') }}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.duplicate_brand_handling') }}</label>
                <select name="duplicate_strategy" class="border dark:border-slate-600 rounded px-3 py-2 text-sm bg-white dark:bg-slate-900">
                    <option value="skip">{{ __('messages.skip_duplicate_brands_default') }}</option>
                    <option value="update">{{ __('messages.update_existing_brands') }}</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-violet-600 text-white rounded font-semibold text-sm hover:bg-violet-700">
                {{ __('messages.upload_and_preview') }}
            </button>
        </form>

        <div class="border-t dark:border-slate-700 pt-4">
            <h4 class="text-sm font-bold text-gray-700 dark:text-slate-300 mb-2">{{ __('messages.brand_import_supported_columns') }}</h4>
            <div class="bg-gray-50 dark:bg-slate-900 rounded p-3 font-mono text-xs text-gray-600 dark:text-slate-400 overflow-x-auto">
                name,slug
            </div>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-2">
                {!! __('messages.brand_import_supported_desc', ['name' => '<code>name</code>', 'slug_opt' => '<code>slug</code>']) !!}
            </p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm space-y-3">
        <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100">{{ __('messages.recent_brand_imports') }}</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 dark:bg-slate-900/60 text-gray-600 dark:text-slate-300">
                    <tr>
                        <th class="p-2">{{ __('messages.brand_import_history_date') }}</th>
                        <th class="p-2">{{ __('messages.brand_import_history_file') }}</th>
                        <th class="p-2">{{ __('messages.brand_import_history_user') }}</th>
                        <th class="p-2">{{ __('messages.brand_import_history_total') }}</th>
                        <th class="p-2">{{ __('messages.brand_import_history_success') }}</th>
                        <th class="p-2">{{ __('messages.brand_import_history_failed') }}</th>
                        <th class="p-2">{{ __('messages.brand_import_history_actions') }}</th>
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
                                    <a href="{{ route('store.admin.import-history.errors', ['store_slug' => $store->slug, 'history' => $history]) }}" class="text-xs font-semibold text-red-600 dark:text-red-400 hover:underline">{{ __('messages.error_report') }}</a>
                                @else
                                    <span class="text-xs text-gray-400">{{ __('messages.no_errors') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-3 text-center text-gray-500">{{ __('messages.no_import_history') }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
