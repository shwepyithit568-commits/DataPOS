@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-6">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">{{ __('messages.backup_title') }}</h1>
            <p class="admin-page-sub">{{ __('messages.backup_subtitle') }}</p>
        </div>
        <form method="POST" action="{{ route('store.admin.backups.store', ['store_slug' => $store->slug]) }}">
            @csrf
            <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                ⬇ {{ __('messages.backup_now') }}
            </button>
        </form>
    </div>

    @if (session('success'))
        <div class="p-3.5 sm:p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-start gap-2">
            <span class="text-base flex-shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-md text-sm text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="admin-hairline-grid grid-cols-2 lg:grid-cols-4">
        <div class="admin-hairline-cell">
            <div class="admin-stat-label">{{ __('messages.backup_total_backups') }}</div>
            <div class="admin-stat-value">{{ count($files) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label">{{ __('messages.backup_total_size') }}</div>
            <div class="admin-stat-value">{{ $totalSize > 0 ? round($totalSize / 1024, 1) . ' KB' : '0 KB' }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label">{{ __('messages.backup_last_backup') }}</div>
            <div class="admin-stat-value text-sm">
                {{ $lastBackup ? $lastBackup->format('Y-m-d H:i') : __('messages.backup_never') }}
            </div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label">{{ __('messages.backup_type') }}</div>
            <div class="admin-stat-value text-sm">{{ config('database.default') === 'sqlite' ? 'SQLite' : 'MySQL' }}</div>
        </div>
    </div>

    <div class="admin-panel overflow-x-auto">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">{{ __('messages.backups') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[720px] w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-slate-900/60 text-xs text-gray-600 dark:text-slate-300">
                    <tr>
                        <th class="p-3">{{ __('messages.backup_file') }}</th>
                        <th class="p-3">{{ __('messages.backup_date') }}</th>
                        <th class="p-3 text-right">{{ __('messages.backup_size') }}</th>
                        <th class="p-3 text-right">{{ __('messages.backup_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700 text-gray-700 dark:text-slate-300">
                    @forelse ($files as $file)
                        <tr>
                            <td class="p-3 max-w-[320px] truncate font-mono text-xs">{{ $file['filename'] }}</td>
                            <td class="p-3 whitespace-nowrap">{{ $file['created_at']->format('Y-m-d H:i') }}</td>
                            <td class="p-3 text-right whitespace-nowrap">{{ round($file['size'] / 1024, 1) }} KB</td>
                            <td class="p-3">
                                <div class="flex justify-end items-center gap-2 whitespace-nowrap">
                                    <a href="{{ route('store.admin.backups.download', ['store_slug' => $store->slug, 'file' => $file['filename']]) }}"
                                        class="px-2.5 py-1.5 text-xs font-semibold rounded bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 hover:bg-violet-100 dark:hover:bg-violet-900/50">
                                        {{ __('messages.backup_download') }}
                                    </a>
                                    <form method="POST" action="{{ route('store.admin.backups.destroy', ['store_slug' => $store->slug, 'file' => $file['filename']]) }}"
                                        data-confirm="{{ __('messages.backup_delete_confirm') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1.5 text-xs font-semibold rounded bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/50">
                                            {{ __('messages.backup_delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-gray-500 dark:text-slate-400">{{ __('messages.backup_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-slate-700 text-xs text-gray-500 dark:text-slate-400">
            {{ __('messages.backup_hint', ['keep' => \App\Services\DatabaseBackupService::KEEP]) }}
        </div>
    </div>
</div>
@endsection
