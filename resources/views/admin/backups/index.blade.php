@extends('layouts.admin.app')

@section('title', __('messages.backups') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2 sm:p-3 md:p-4')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-2.5 sm:space-y-3 pb-8"
     x-data="{
        activeTab: '{{ $tab ?? 'backups' }}',
        searchQuery: '',
        formatFilter: 'all',
        restoreModalOpen: false,
        restoreTargetFile: '',
        openRestoreModal(file) {
            this.restoreTargetFile = file;
            this.restoreModalOpen = true;
        },
        matchesFile(filename, format) {
            const matchesSearch = !this.searchQuery || filename.toLowerCase().includes(this.searchQuery.toLowerCase());
            const matchesFormat = this.formatFilter === 'all' || format === this.formatFilter;
            return matchesSearch && matchesFormat;
        }
     }">

    {{-- 1. Compact Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 sm:p-4 shadow-sm">
        <div class="flex items-center gap-3 min-w-0">
            <span class="w-10 h-10 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300 grid place-items-center text-lg font-bold shrink-0 shadow-sm">
                💾
            </span>
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 truncate">
                    {{ __('messages.backup_title') }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.backup_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{-- 1. Primary Action: Full Project Backup (Database + Uploaded Media ZIP) --}}
            <form method="POST" action="{{ route('store.admin.backups.store', $storeRouteParams) }}">
                @csrf
                <input type="hidden" name="format" value="zip">
                <button type="submit"
                        class="h-9 px-3.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition inline-flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <span>📦</span>
                    <span>{{ __('messages.backup_zip_btn') }}</span>
                </button>
            </form>

            {{-- 2. Secondary Action: Universal SQL Backup --}}
            <form method="POST" action="{{ route('store.admin.backups.store', $storeRouteParams) }}">
                @csrf
                <input type="hidden" name="format" value="sql">
                <button type="submit"
                        class="h-9 px-3 rounded-xl text-xs font-semibold bg-violet-600 hover:bg-violet-700 text-white shadow-sm transition inline-flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <span>⬇</span>
                    <span>{{ __('messages.backup_sql_btn') }}</span>
                </button>
            </form>

            @if ($driver === 'sqlite')
                {{-- 3. Optional Action: SQLite Snapshot --}}
                <form method="POST" action="{{ route('store.admin.backups.store', $storeRouteParams) }}">
                    @csrf
                    <input type="hidden" name="format" value="sqlite">
                    <button type="submit"
                            class="h-9 px-3 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5 cursor-pointer">
                        <span>💾</span>
                        <span>{{ __('messages.backup_sqlite_btn') }}</span>
                    </button>
                </form>
            @endif

            <a href="{{ route('store.admin.database.index', $storeRouteParams) }}"
               class="h-9 px-3 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5">
                <span>🗄️</span>
                <span>{{ __('messages.sidebar_database') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-medium text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 grid place-items-center text-xs font-bold">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs font-medium text-rose-800 dark:text-rose-200 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300 grid place-items-center text-xs font-bold">⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- 2. 4 Key Stat KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
        {{-- Total Backups --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-violet-600 dark:text-violet-400">{{ __('messages.backup_total_backups') }}</span>
                <span>📁</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-violet-600 dark:text-violet-400 tabular-nums">
                {{ count($files) }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ __('messages.backup_stored_snapshots') }}</p>
        </div>

        {{-- Total Size --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-indigo-600 dark:text-indigo-400">{{ __('messages.backup_total_size') }}</span>
                <span>💾</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-indigo-600 dark:text-indigo-400 tabular-nums">
                {{ $totalSize > 0 ? round($totalSize / 1024, 1) . ' KB' : '0 KB' }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ __('messages.backup_total_size') }}</p>
        </div>

        {{-- Last Backup --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-emerald-600 dark:text-emerald-400">{{ __('messages.backup_last_backup') }}</span>
                <span>⏰</span>
            </div>
            <div class="mt-1 text-sm sm:text-base font-bold font-mono text-emerald-600 dark:text-emerald-400 truncate">
                {{ $lastBackup ? $lastBackup->format('d M Y, h:i A') : __('messages.backup_never') }}
            </div>
            <p class="text-[11px] text-slate-400 font-mono truncate">
                {{ $lastBackup ? $lastBackup->diffForHumans() : '—' }}
            </p>
        </div>

        {{-- Engine Type --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-sky-600 dark:text-sky-400">{{ __('messages.backup_type') }}</span>
                <span>⚙️</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-sky-600 dark:text-sky-400">
                {{ $stats['driver'] }}
            </div>
            <p class="text-[11px] text-slate-400 font-mono truncate">{{ $stats['database_name'] }}</p>
        </div>
    </div>

    {{-- 3. Tab Filter Bar --}}
    <div class="flex items-center gap-1.5 border-b border-slate-200 dark:border-slate-800 pb-1 text-xs">
        <button type="button" @click="activeTab = 'backups'"
                class="px-3.5 py-2 rounded-xl font-semibold transition inline-flex items-center gap-2 shrink-0 cursor-pointer"
                :class="activeTab === 'backups' ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
            <span>📁 {{ __('messages.backup_archive_tab') }}</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                  :class="activeTab === 'backups' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'">
                {{ count($files) }}
            </span>
        </button>

        <button type="button" @click="activeTab = 'restore'"
                class="px-3.5 py-2 rounded-xl font-semibold transition inline-flex items-center gap-2 shrink-0 cursor-pointer"
                :class="activeTab === 'restore' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
            <span>🔄 {{ __('messages.backup_restore_tab') }}</span>
        </button>
    </div>

    {{-- 4. Tab 1: Backups Archive List --}}
    <div x-show="activeTab === 'backups'" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden space-y-0">
        <div class="p-3 sm:p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>📁 {{ __('messages.backup_stored_snapshots') }}</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ __('messages.backup_stored_snapshots_sub') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <input type="text" x-model="searchQuery" placeholder="{{ __('messages.backup_search_placeholder') }}"
                       class="text-xs border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500 w-44">

                <select x-model="formatFilter"
                        class="text-xs border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="all">{{ __('messages.backup_format_all') }}</option>
                    <option value="zip">{{ __('messages.backup_format_zip') }}</option>
                    <option value="sql">{{ __('messages.backup_format_sql') }}</option>
                    <option value="sqlite">{{ __('messages.backup_format_sqlite') }}</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-2.5 px-3.5">{{ __('messages.backup_file') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.backup_type') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.backup_date') }}</th>
                        <th class="py-2.5 px-3.5 text-right">{{ __('messages.backup_size') }}</th>
                        <th class="py-2.5 px-3.5 text-right">{{ __('messages.backup_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse ($files as $file)
                        <tr x-show="matchesFile('{{ $file['filename'] }}', '{{ $file['format'] }}')"
                            class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-2.5 px-3.5 font-mono font-semibold text-slate-900 dark:text-slate-100">
                                {{ $file['filename'] }}
                            </td>
                            <td class="py-2.5 px-3.5">
                                @if ($file['format'] === 'zip')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        📦 {{ __('messages.backup_format_full_archive') }}
                                    </span>
                                @elseif ($file['format'] === 'sqlite')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300">
                                        💾 {{ __('messages.backup_format_sqlite_binary') }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-violet-100 text-violet-800 dark:bg-violet-950/60 dark:text-violet-300">
                                        📄 {{ __('messages.backup_format_universal_sql') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3.5 font-mono text-slate-500 dark:text-slate-400">
                                {{ $file['created_at']->format('d M Y, h:i A') }}
                            </td>
                            <td class="py-2.5 px-3.5 text-right font-mono font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                {{ round($file['size'] / 1024, 1) }} KB
                            </td>
                            <td class="py-2.5 px-3.5 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    {{-- Download --}}
                                    <a href="{{ route('store.admin.backups.download', array_merge($storeRouteParams, ['file' => $file['filename']])) }}"
                                       class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-violet-50 hover:bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300 transition">
                                        ⬇ {{ __('messages.backup_download') }}
                                    </a>

                                    {{-- Restore from file button --}}
                                    <button type="button" @click="openRestoreModal('{{ $file['filename'] }}')"
                                             class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 transition cursor-pointer">
                                        🔄 {{ __('messages.backup_restore') }}
                                    </button>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('store.admin.backups.destroy', array_merge($storeRouteParams, ['file' => $file['filename']])) }}"
                                          onsubmit="return confirm('{{ __('messages.backup_delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1 rounded-lg text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 transition cursor-pointer">
                                            🗑️ {{ __('messages.backup_delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-slate-400 text-xs">
                                {{ __('messages.backup_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-2.5 border-t border-slate-200 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-800/30 flex items-center justify-between">
            <span>{{ __('messages.backup_hint', ['keep' => \App\Services\DatabaseBackupService::KEEP]) }}</span>
            <span class="font-mono text-slate-400">{{ __('messages.backup_retention_note', ['keep' => \App\Services\DatabaseBackupService::KEEP]) }}</span>
        </div>
    </div>

    {{-- 5. Tab 2: Restore from Uploaded File --}}
    <div x-show="activeTab === 'restore'" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-4">
        <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span class="text-base">🔄</span>
                    <span>{{ __('messages.backup_restore_title') }}</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ __('messages.backup_restore_sub') }}
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60 w-fit">
                <span>⚠️ {{ __('messages.backup_caution_overwrite') }}</span>
            </span>
        </div>

        {{-- Warning Box --}}
        <div class="p-3.5 bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/80 rounded-xl text-xs text-amber-900 dark:text-amber-200 space-y-1">
            <div class="font-bold flex items-center gap-1.5 text-amber-700 dark:text-amber-300">
                <span>⚠️</span>
                <span>{{ __('messages.backup_restore_warning') }}:</span>
            </div>
            <p class="leading-relaxed text-amber-800 dark:text-amber-300/90">
                {{ __('messages.backup_restore_warning_text') }}
            </p>
        </div>

        {{-- Upload Form --}}
        <form method="POST" action="{{ route('store.admin.backups.upload_restore', $storeRouteParams) }}" enctype="multipart/form-data"
              onsubmit="return confirm('{{ __('messages.backup_restore_warning_text') }}')"
              class="max-w-xl space-y-4 pt-1">
            @csrf

            <div>
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5 block">
                    {{ __('messages.backup_restore_file_label') }} <span class="text-rose-500">*</span>
                </label>
                <input type="file" name="backup_file" required accept=".zip,.sql,.sqlite,.db"
                       class="w-full text-xs font-mono border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-100 file:text-amber-800 dark:file:bg-amber-950 dark:file:text-amber-300 hover:file:bg-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-500">
                <p class="text-[10px] text-slate-400 mt-1">{{ __('messages.backup_restore_upload_hint') }}</p>
            </div>

            <button type="submit"
                    class="py-2.5 px-5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-md shadow-amber-500/15 transition flex items-center justify-center gap-2 active:scale-95 cursor-pointer">
                <span>🔄</span>
                <span>{{ __('messages.backup_restore_now_btn') }}</span>
            </button>
        </form>
    </div>

    {{-- Restore Confirmation Modal for Archive Files --}}
    <div x-show="restoreModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4">
        <div @click.away="restoreModalOpen = false"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300 grid place-items-center text-lg font-bold shrink-0">
                    ⚠️
                </span>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('messages.backup_restore_confirm_title') }}</h3>
                    <p class="text-xs text-slate-400">{{ __('messages.backup_restore_confirm_sub') }}</p>
                </div>
            </div>

            <div class="text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-200/80 dark:border-slate-700/80 space-y-1 font-mono">
                <div>{{ __('messages.backup_restore_selected_file') }}:</div>
                <div class="font-bold text-amber-600 dark:text-amber-400 truncate" x-text="restoreTargetFile"></div>
            </div>

            <p class="text-xs text-slate-500 leading-relaxed">
                {{ __('messages.backup_restore_confirm_text') }}
            </p>

            <form method="POST" action="{{ route('store.admin.backups.restore', $storeRouteParams) }}" class="flex items-center justify-end gap-2 pt-2">
                @csrf
                <input type="hidden" name="filename" :value="restoreTargetFile">
                <button type="button" @click="restoreModalOpen = false"
                        class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 transition">
                    {{ __('messages.backup_restore_cancel_btn') }}
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-bold rounded-xl bg-amber-600 hover:bg-amber-500 text-white shadow-sm transition">
                    {{ __('messages.backup_restore_confirm_btn') }}
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
