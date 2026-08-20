@extends('layouts.admin.app')

@section('content')
<div class="space-y-6 max-w-5xl">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit">{{ __('messages.supplier_import_title') }}</h1>
        <div class="w-full sm:w-auto flex flex-nowrap items-center gap-2 overflow-x-auto pb-1 -mx-1 px-1 sm:flex-wrap sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0">
            <a href="{{ route('store.admin.suppliers.import.template', ['store_slug' => $store->slug]) }}" class="shrink-0 px-3 py-2 bg-green-600 text-white rounded-md text-xs font-semibold hover:bg-green-700 whitespace-nowrap">{{ __('messages.supplier_download_template') }}</a>
            <a href="{{ route('store.admin.suppliers.index', ['store_slug' => $store->slug]) }}" class="shrink-0 text-xs text-violet-600 dark:text-violet-400 font-semibold hover:underline whitespace-nowrap">&larr; {{ __('messages.supplier_back') }}</a>
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
                <h3 class="font-bold text-amber-900 dark:text-amber-200">{{ __('messages.supplier_import_preview') }}: {{ $preview['filename'] }}</h3>
                <p class="text-xs text-amber-800 dark:text-amber-300">{{ __('messages.supplier_import_review') }}</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm text-center">
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold">{{ $preview['total'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.supplier_import_total') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-green-700 dark:text-green-400">{{ $preview['creatable'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.supplier_import_new') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-violet-700 dark:text-violet-400">{{ $preview['updatable'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.supplier_import_updates') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-amber-700 dark:text-amber-400">{{ $preview['skipped_duplicate'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.supplier_import_skipped') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <div class="text-xl font-bold text-red-700 dark:text-red-400">{{ $preview['failed'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('messages.supplier_import_failed') }}</div>
                </div>
            </div>

            @if (!empty($preview['preview_rows']))
                <div class="overflow-x-auto bg-white dark:bg-slate-800 rounded">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 dark:bg-slate-900/60">
                            <tr>
                                <th class="p-2">#</th>
                                <th class="p-2">{{ __('messages.supplier_col_name') }}</th>
                                <th class="p-2">{{ __('messages.supplier_col_phone') }}</th>
                                <th class="p-2">{{ __('messages.supplier_import_action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-slate-700">
                            @foreach ($preview['preview_rows'] as $row)
                                <tr>
                                    <td class="p-2">{{ $row['row'] }}</td>
                                    <td class="p-2">{{ $row['name'] }}</td>
                                    <td class="p-2 font-mono">{{ $row['phone'] ?? '—' }}</td>
                                    <td class="p-2">{{ $row['action'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if (!empty($preview['failed_rows']))
                <details>
                    <summary class="cursor-pointer text-xs font-semibold text-red-700 dark:text-red-400">{{ __('messages.supplier_import_failed_rows') }} ({{ count($preview['failed_rows']) }})</summary>
                    <div class="mt-2 space-y-1 text-xs bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded p-2 max-h-48 overflow-y-auto">
                        @foreach ($preview['failed_rows'] as $fr)
                            <div>Row {{ $fr['row'] }}{{ isset($fr['name']) && $fr['name'] !== '' ? ' [' . $fr['name'] . ']' : '' }}: {{ $fr['reason'] }}</div>
                        @endforeach
                    </div>
                </details>
            @endif

            <form method="POST" action="{{ route('store.admin.suppliers.import.confirm', ['store_slug' => $store->slug]) }}" class="flex flex-nowrap items-end gap-3 overflow-x-auto pb-1 -mx-1 px-1 sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0">
                @csrf
                <input type="hidden" name="token" value="{{ $preview['token'] }}">
                <div class="shrink-0">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_import_dup_strategy') }}</label>
                    <select name="duplicate_strategy" class="border dark:border-slate-600 rounded px-3 py-2 text-sm bg-white dark:bg-slate-900">
                        <option value="skip" {{ $preview['duplicate_strategy'] === 'skip' ? 'selected' : '' }}>{{ __('messages.supplier_import_skip_dup') }}</option>
                        <option value="update" {{ $preview['duplicate_strategy'] === 'update' ? 'selected' : '' }}>{{ __('messages.supplier_import_update_dup') }}</option>
                    </select>
                </div>
                <button type="submit" class="shrink-0 px-4 py-2 bg-green-600 text-white rounded font-semibold text-sm hover:bg-green-700 whitespace-nowrap">{{ __('messages.supplier_import_confirm') }}</button>
            </form>
        </div>
    @endif

    @if (session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 rounded-lg p-4 text-sm text-green-700 dark:text-green-300 space-y-2">
            <div class="font-bold">{{ __('messages.supplier_import_complete') }}</div>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-center text-xs">
                <div>{{ __('messages.supplier_import_total') }}: {{ $result['total'] }}</div>
                <div class="text-green-700 dark:text-green-400">{{ __('messages.supplier_import_new') }}: {{ $result['imported'] }}</div>
                <div class="text-violet-700 dark:text-violet-400">{{ __('messages.supplier_import_updates') }}: {{ $result['updated'] }}</div>
                <div class="text-amber-700 dark:text-amber-400">{{ __('messages.supplier_import_skipped') }}: {{ $result['skipped_duplicate'] }}</div>
                <div class="text-red-700 dark:text-red-400">{{ __('messages.supplier_import_failed') }}: {{ $result['failed'] }}</div>
            </div>
        </div>
    @endif

    {{-- Upload form --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200/80 dark:border-slate-700 p-6">
        <form method="POST" action="{{ route('store.admin.suppliers.import.do', ['store_slug' => $store->slug]) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_import_file') }}</label>
                <input type="file" name="file" accept=".csv,.txt,.xlsx" required
                    class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                <p class="mt-1 text-xs text-gray-400">{{ __('messages.supplier_import_file_hint') }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_import_dup_strategy') }}</label>
                <select name="duplicate_strategy" class="border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none transition">
                    <option value="skip">{{ __('messages.supplier_import_skip_dup') }}</option>
                    <option value="update">{{ __('messages.supplier_import_update_dup') }}</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-violet-600 text-white rounded-lg font-semibold text-sm hover:bg-violet-700 transition shadow">
                {{ __('messages.supplier_import_upload') }}
            </button>
        </form>
    </div>

    {{-- Import history --}}
    @if ($histories->count())
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200/80 dark:border-slate-700 p-6">
            <h3 class="text-sm font-bold text-gray-700 dark:text-slate-300 mb-3">{{ __('messages.supplier_import_history') }}</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 dark:bg-slate-900/60">
                        <tr>
                            <th class="p-2">{{ __('messages.supplier_import_col_date') }}</th>
                            <th class="p-2">{{ __('messages.supplier_import_col_file') }}</th>
                            <th class="p-2">{{ __('messages.supplier_import_col_user') }}</th>
                            <th class="p-2 text-right">{{ __('messages.supplier_import_col_total') }}</th>
                            <th class="p-2 text-right">{{ __('messages.supplier_import_col_success') }}</th>
                            <th class="p-2 text-right">{{ __('messages.supplier_import_col_failed') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-slate-700">
                        @foreach ($histories as $history)
                            <tr>
                                <td class="p-2">{{ $history->created_at->format('d M Y, H:i') }}</td>
                                <td class="p-2 font-mono">{{ $history->filename }}</td>
                                <td class="p-2">{{ $history->user?->name ?? '—' }}</td>
                                <td class="p-2 text-right">{{ $history->total_rows }}</td>
                                <td class="p-2 text-right text-green-600 dark:text-green-400">{{ $history->success_rows }}</td>
                                <td class="p-2 text-right text-red-600 dark:text-red-400">{{ $history->failed_rows }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
