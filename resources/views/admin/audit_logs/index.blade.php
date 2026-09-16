@extends('layouts.admin.app')

@section('title', __('messages.sidebar_audit_logs') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-0.5 pb-6"
     x-data="{
        viewMode: localStorage.getItem('admin_audit_logs_view_mode') || 'table',
        modalOpen: false,
        selectedLog: null,
        openModal(log) {
            this.selectedLog = log;
            this.modalOpen = true;
        },
        closeModal() {
            this.modalOpen = false;
            this.selectedLog = null;
        }
     }"
     @keydown.escape.window="closeModal()"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_audit_logs_view_mode', $event.detail)">

    {{-- ============================================================
         1. TOP ULTRA-DENSE HEADER BANNER (Standard v4.1)
         ============================================================ --}}
    <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none transition">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('store.admin.dashboard', $storeRouteParams) }}"
               class="h-6 w-6 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition active:scale-95 shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="w-6 h-6 rounded bg-rose-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs shrink-0">
                <span>🛡️</span>
            </div>
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60 px-1.5 py-0.5 rounded border border-rose-200/50 dark:border-rose-800/50 truncate max-w-[120px] sm:max-w-none">
                    {{ $store->name }}
                </span>
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight truncate">
                    {{ __('messages.audit_logs_title') }}
                </h1>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono hidden md:inline">
                    · {{ number_format($stats['total']) }} {{ __('messages.sidebar_audit_logs') }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-1.5 shrink-0 self-end sm:self-auto">
            <a href="{{ route('store.admin.roles.index', $storeRouteParams) }}"
               class="sf-btn-3d h-7 px-2 sm:px-2.5 rounded-md text-[11px] sm:text-xs font-bold inline-flex items-center gap-1 cursor-pointer">
                <span>🔑</span>
                <span class="hidden sm:inline">{{ __('messages.sidebar_roles') }}</span>
            </a>
            <a href="{{ $exportUrl }}"
               class="sf-btn-3d-success h-7 px-2 sm:px-2.5 rounded-md text-[11px] sm:text-xs font-bold inline-flex items-center gap-1 cursor-pointer">
                <span>📊</span>
                <span>{{ __('messages.export_csv_button') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="w-full p-2 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ============================================================
         2. 5 KEY KPI SUMMARY CARDS (Standard v4.1 Centered Row-based)
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-0.5 sm:gap-1 select-none">
        {{-- Total All --}}
        <a href="{{ route('store.admin.audit-logs.index', array_merge($storeRouteParams, ['category' => 'all'])) }}"
           class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition cursor-pointer active:scale-[0.98]
                  {{ $category === 'all'
                      ? 'bg-slate-100 dark:bg-slate-800 border-slate-400 dark:border-slate-500 ring-2 ring-slate-500/20'
                      : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 hover:bg-slate-50/30' }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border
                        {{ $category === 'all'
                            ? 'bg-slate-700 text-white border-slate-700 shadow-2xs'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700' }}">📋</div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate {{ $category === 'all' ? 'text-slate-900 dark:text-slate-200' : 'text-slate-500 dark:text-slate-400' }}">
                    {{ __('messages.audit_logs_total') }}
                </div>
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-white font-mono tracking-tight flex items-center gap-1">
                    <span>{{ number_format($stats['total']) }}</span>
                </div>
            </div>
        </a>

        {{-- Pricing & Sales --}}
        <a href="{{ route('store.admin.audit-logs.index', array_merge($storeRouteParams, ['category' => 'pricing_sales'])) }}"
           class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition cursor-pointer active:scale-[0.98]
                  {{ $category === 'pricing_sales'
                      ? 'bg-amber-50/80 dark:bg-amber-950/40 border-amber-400 dark:border-amber-600 ring-2 ring-amber-500/20'
                      : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-amber-300 dark:hover:border-amber-800 hover:bg-amber-50/30' }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border
                        {{ $category === 'pricing_sales'
                            ? 'bg-amber-500 text-white border-amber-500 shadow-2xs'
                            : 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border-amber-100 dark:border-amber-900/50' }}">💰</div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate {{ $category === 'pricing_sales' ? 'text-amber-900 dark:text-amber-200' : 'text-slate-500 dark:text-slate-400' }}">
                    {{ __('messages.audit_logs_pricing_sales') }}
                </div>
                <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ number_format($stats['pricing_sales']) }}</span>
                </div>
            </div>
        </a>

        {{-- Inventory --}}
        <a href="{{ route('store.admin.audit-logs.index', array_merge($storeRouteParams, ['category' => 'inventory'])) }}"
           class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition cursor-pointer active:scale-[0.98]
                  {{ $category === 'inventory'
                      ? 'bg-blue-50/80 dark:bg-blue-950/40 border-blue-400 dark:border-blue-600 ring-2 ring-blue-500/20'
                      : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-800 hover:bg-blue-50/30' }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border
                        {{ $category === 'inventory'
                            ? 'bg-blue-600 text-white border-blue-600 shadow-2xs'
                            : 'bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 border-blue-100 dark:border-blue-900/50' }}">📦</div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate {{ $category === 'inventory' ? 'text-blue-900 dark:text-blue-200' : 'text-slate-500 dark:text-slate-400' }}">
                    {{ __('messages.audit_logs_inventory') }}
                </div>
                <div class="text-sm sm:text-base font-black text-blue-600 dark:text-blue-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ number_format($stats['inventory']) }}</span>
                </div>
            </div>
        </a>

        {{-- Financial --}}
        <a href="{{ route('store.admin.audit-logs.index', array_merge($storeRouteParams, ['category' => 'financial'])) }}"
           class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition cursor-pointer active:scale-[0.98]
                  {{ $category === 'financial'
                      ? 'bg-emerald-50/80 dark:bg-emerald-950/40 border-emerald-400 dark:border-emerald-600 ring-2 ring-emerald-500/20'
                      : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-emerald-300 dark:hover:border-emerald-800 hover:bg-emerald-50/30' }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border
                        {{ $category === 'financial'
                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                            : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/50' }}">💵</div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate {{ $category === 'financial' ? 'text-emerald-900 dark:text-emerald-200' : 'text-slate-500 dark:text-slate-400' }}">
                    {{ __('messages.audit_logs_financial') }}
                </div>
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ number_format($stats['financial']) }}</span>
                </div>
            </div>
        </a>

        {{-- Security --}}
        <a href="{{ route('store.admin.audit-logs.index', array_merge($storeRouteParams, ['category' => 'security'])) }}"
           class="col-span-2 sm:col-span-1 rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition cursor-pointer active:scale-[0.98]
                  {{ $category === 'security'
                      ? 'bg-rose-50/80 dark:bg-rose-950/40 border-rose-400 dark:border-rose-600 ring-2 ring-rose-500/20'
                      : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 hover:border-rose-300 dark:hover:border-rose-800 hover:bg-rose-50/30' }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border
                        {{ $category === 'security'
                            ? 'bg-rose-600 text-white border-rose-600 shadow-2xs'
                            : 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border-rose-100 dark:border-rose-900/50' }}">🛡️</div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate {{ $category === 'security' ? 'text-rose-900 dark:text-rose-200' : 'text-slate-500 dark:text-slate-400' }}">
                    {{ __('messages.audit_logs_security') }}
                </div>
                <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ number_format($stats['security']) }}</span>
                </div>
            </div>
        </a>
    </div>

    {{-- 3. Unified Admin Toolbar --}}
    <x-admin.toolbar
        :search="request('search', '')"
        :searchPlaceholder="__('messages.audit_logs_search_placeholder')"
        :sort="request('sort', $sort)"
        :sortOptions="[
            'newest' => __('messages.audit_logs_sort_newest'),
            'oldest' => __('messages.audit_logs_sort_oldest'),
        ]"
        :filters="[
            'category' => [
                'label'   => __('messages.audit_logs_filter_category'),
                'options' => [
                    'all'               => __('messages.audit_logs_cat_all'),
                    'pricing_sales'     => __('messages.audit_logs_cat_pricing_sales'),
                    'inventory'         => __('messages.audit_logs_cat_inventory'),
                    'financial'         => __('messages.audit_logs_cat_financial'),
                    'security'          => __('messages.audit_logs_cat_security'),
                    'marketing_loyalty' => __('messages.audit_logs_cat_marketing_loyalty'),
                ],
            ],
        ]"
        :viewMode="'table'"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$exportUrl"
        :totalCount="$logs->total()"
        :perPage="$logs->perPage()"
        :paginator="$logs"
        :showPagination="true"
    />

    {{-- 4. Card View (Alpine Toggle) --}}
    <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($logs as $log)
            @php
                $humanAction = \App\Http\Controllers\Admin\AuditLogController::humanizeAction($log->action);
                $categoryLabel = \App\Http\Controllers\Admin\AuditLogController::categoryOfAction($log->action);
                $metaArray = is_array($log->metadata) ? $log->metadata : json_decode($log->metadata ?? '[]', true);
            @endphp
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4 group">
                <div class="space-y-3">
                    {{-- Card Header --}}
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <span class="text-xs font-mono font-bold text-slate-400 block">
                                #LOG-{{ str_pad($log->id, 5, '0', STR_PAD_LEFT) }}
                            </span>
                            <span class="text-[11px] text-slate-400 block mt-0.5">
                                {{ $log->created_at?->format('M d, Y h:i:s A') }}
                            </span>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase whitespace-nowrap bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ $log->created_at?->diffForHumans() }}
                        </span>
                    </div>

                    {{-- Action Label & Category --}}
                    <div>
                        <div class="font-black text-sm text-slate-900 dark:text-slate-100 leading-snug">
                            {{ $humanAction }}
                        </div>
                        <div class="text-[10px] font-mono text-slate-400 mt-1">
                            code: <span class="bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ $log->action }}</span>
                        </div>
                    </div>

                    {{-- Actor Info --}}
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-slate-900 dark:text-slate-200">
                                👤 {{ $log->actor?->name ?? 'System / Automated' }}
                            </div>
                            @if ($log->actor?->phone)
                                <div class="font-mono text-[11px] text-slate-400">📞 {{ $log->actor->phone }}</div>
                            @endif
                        </div>
                        @if ($log->ip_address)
                            <div class="font-mono text-[10px] text-slate-400 bg-slate-50 dark:bg-slate-800/80 px-2 py-1 rounded-lg">
                                🌐 {{ $log->ip_address }}
                            </div>
                        @endif
                    </div>

                    {{-- Metadata Snippet --}}
                    @if (!empty($metaArray))
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-3 text-[11px] font-mono text-slate-600 dark:text-slate-300 space-y-1">
                            @foreach (array_slice($metaArray, 0, 3) as $k => $v)
                                <div class="truncate">
                                    <span class="text-slate-400">{{ $k }}:</span>
                                    <span class="font-semibold">{{ is_array($v) ? json_encode($v) : $v }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Card Actions --}}
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <span class="text-[10px] font-semibold text-slate-400">
                        @if ($log->entity_type)
                            Target: <span class="font-mono">{{ $log->entity_type }} #{{ $log->entity_id }}</span>
                        @else
                            System Event
                        @endif
                    </span>

                    <button type="button"
                            @click.stop="openModal({{ json_encode([
                                'id'          => $log->id,
                                'action'      => $log->action,
                                'action_name' => $humanAction,
                                'category'    => $categoryLabel,
                                'actor'       => $log->actor ? ['name' => $log->actor->name, 'phone' => $log->actor->phone] : null,
                                'entity_type' => $log->entity_type,
                                'entity_id'   => $log->entity_id,
                                'metadata'    => $metaArray,
                                'ip_address'  => $log->ip_address,
                                'created_at'  => $log->created_at?->format('d M Y, h:i:s A'),
                                'time_ago'    => $log->created_at?->diffForHumans(),
                            ]) }})"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 transition flex items-center gap-1 cursor-pointer">
                        <span>🔍</span>
                        <span>{{ __('messages.details') }}</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                {{ __('messages.audit_logs_no_records') }}
            </div>
        @endforelse
    </div>

    {{-- 5. Table View (Alpine Toggle) --}}
    <div x-show="viewMode === 'table'" class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4">{{ __('messages.audit_logs_col_timestamp') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.audit_logs_col_actor') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.audit_logs_col_action') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.audit_logs_col_entity') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.audit_logs_col_ip') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.audit_logs_col_details') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($logs as $log)
                        @php
                            $humanAction = \App\Http\Controllers\Admin\AuditLogController::humanizeAction($log->action);
                            $categoryLabel = \App\Http\Controllers\Admin\AuditLogController::categoryOfAction($log->action);
                            $metaArray = is_array($log->metadata) ? $log->metadata : json_decode($log->metadata ?? '[]', true);
                        @endphp
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            {{-- Timestamp --}}
                            <td class="py-3.5 px-4">
                                <div class="font-mono font-bold text-slate-900 dark:text-slate-100 text-xs">
                                    {{ $log->created_at?->format('M d, Y') }}
                                </div>
                                <div class="font-mono text-[11px] text-slate-400">
                                    {{ $log->created_at?->format('h:i:s A') }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-semibold">
                                    {{ $log->created_at?->diffForHumans() }}
                                </div>
                            </td>

                            {{-- Actor --}}
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs flex items-center gap-1.5">
                                    <span>👤</span>
                                    <span>{{ $log->actor?->name ?? 'System / Automated' }}</span>
                                </div>
                                @if ($log->actor?->phone)
                                    <div class="font-mono text-[11px] text-slate-400 ml-5">
                                        {{ $log->actor->phone }}
                                    </div>
                                @endif
                            </td>

                            {{-- Action --}}
                            <td class="py-3.5 px-4 max-w-[280px]">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs leading-snug">
                                    {{ $humanAction }}
                                </div>
                                <div class="font-mono text-[10px] text-slate-400 mt-0.5">
                                    {{ $log->action }}
                                </div>
                            </td>

                            {{-- Entity --}}
                            <td class="py-3.5 px-4 font-mono text-[11px]">
                                @if ($log->entity_type)
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold">
                                        {{ $log->entity_type }}
                                    </span>
                                    @if ($log->entity_id)
                                        <span class="text-slate-400 font-bold">#{{ $log->entity_id }}</span>
                                    @endif
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">—</span>
                                @endif
                            </td>

                            {{-- IP Address --}}
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                {{ $log->ip_address ?? '—' }}
                            </td>

                            {{-- Details Button --}}
                            <td class="py-3.5 px-4 text-right">
                                <button type="button"
                                        @click.stop="openModal({{ json_encode([
                                            'id'          => $log->id,
                                            'action'      => $log->action,
                                            'action_name' => $humanAction,
                                            'category'    => $categoryLabel,
                                            'actor'       => $log->actor ? ['name' => $log->actor->name, 'phone' => $log->actor->phone] : null,
                                            'entity_type' => $log->entity_type,
                                            'entity_id'   => $log->entity_id,
                                            'metadata'    => $metaArray,
                                            'ip_address'  => $log->ip_address,
                                            'created_at'  => $log->created_at?->format('d M Y, h:i:s A'),
                                            'time_ago'    => $log->created_at?->diffForHumans(),
                                        ]) }})"
                                        class="sf-btn-3d px-3 py-1 rounded-md text-xs font-bold inline-flex items-center gap-1 cursor-pointer">
                                    <span>🔍</span>
                                    <span>{{ __('messages.details') }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                                {{ __('messages.audit_logs_no_records') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 6. Pagination --}}
    <div class="mt-4">
        {{ $logs->links() }}
    </div>

    {{-- 7. Detail Inspection Modal (Alpine.js) --}}
    <div x-show="modalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @click.self="closeModal()"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal()"></div>

        {{-- Modal Card --}}
        <div class="relative z-10 w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2">

            {{-- Modal Header --}}
            <div class="flex items-start justify-between p-6 border-b border-slate-100 dark:border-slate-800">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                            {{ __('messages.audit_logs_inspection') }}
                        </span>
                        <span class="font-mono text-xs text-slate-400" x-text="'#LOG-' + String(selectedLog?.id || '').padStart(5, '0')"></span>
                    </div>
                    <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 mt-1" x-text="selectedLog?.action_name"></h2>
                    <p class="text-xs text-slate-400 font-mono mt-0.5" x-text="selectedLog?.action"></p>
                </div>
                <button @click="closeModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                    ✕
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                {{-- Quick Summary Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-3">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">👤 {{ __('messages.audit_logs_actor') }}</div>
                        <div class="text-xs font-bold text-slate-900 dark:text-slate-100 mt-1 truncate" x-text="selectedLog?.actor?.name || 'System / Automated'"></div>
                        <div class="text-[11px] font-mono text-slate-400 truncate" x-text="selectedLog?.actor?.phone || '—'"></div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-3">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">📅 {{ __('messages.audit_logs_timestamp') }}</div>
                        <div class="text-xs font-bold text-slate-900 dark:text-slate-100 mt-1 truncate" x-text="selectedLog?.created_at"></div>
                        <div class="text-[11px] text-slate-400 truncate" x-text="selectedLog?.time_ago"></div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-3 col-span-2 sm:col-span-1">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">🌐 {{ __('messages.audit_logs_device_ip') }}</div>
                        <div class="text-xs font-mono font-bold text-slate-900 dark:text-slate-100 mt-1 truncate" x-text="selectedLog?.ip_address || '—'"></div>
                        <div class="text-[11px] font-mono text-slate-400 truncate" x-text="selectedLog?.entity_type ? (selectedLog.entity_type + ' #' + selectedLog.entity_id) : 'System'"></div>
                    </div>
                </div>

                {{-- Formatted Metadata --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                            <span>📝</span>
                            <span>{{ __('messages.audit_logs_metadata_title') }}</span>
                        </span>
                    </div>

                    <template x-if="selectedLog && selectedLog.metadata && Object.keys(selectedLog.metadata).length > 0">
                        <div class="space-y-2">
                            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 space-y-2 text-xs">
                                <template x-for="(val, key) in selectedLog.metadata" :key="key">
                                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-1 sm:gap-4 py-1 border-b border-slate-100 dark:border-slate-700/60 last:border-0">
                                        <span class="font-mono font-bold text-slate-500 dark:text-slate-400 uppercase text-[11px]" x-text="key"></span>
                                        <span class="font-mono font-semibold text-slate-900 dark:text-slate-100 text-right break-all"
                                              x-text="typeof val === 'object' ? JSON.stringify(val) : val"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Raw JSON View (Collapsible) --}}
                            <div x-data="{ showRaw: false }">
                                <button type="button" @click="showRaw = !showRaw" class="text-[11px] font-bold text-rose-600 dark:text-rose-400 hover:underline cursor-pointer">
                                    <span x-text="showRaw ? '▼ {{ __('messages.audit_logs_hide_raw_json') }}' : '► {{ __('messages.audit_logs_view_raw_json') }}'"></span>
                                </button>
                                <pre x-show="showRaw"
                                     class="mt-2 bg-slate-950 text-slate-200 rounded-2xl p-4 text-[11px] font-mono overflow-x-auto max-h-48"
                                     x-text="JSON.stringify(selectedLog.metadata, null, 2)"></pre>
                            </div>
                        </div>
                    </template>

                    <template x-if="!selectedLog || !selectedLog.metadata || Object.keys(selectedLog.metadata).length === 0">
                        <div class="bg-slate-50 dark:bg-slate-800/40 rounded-2xl p-6 text-center text-xs text-slate-400 font-medium">
                            {{ __('messages.audit_logs_no_metadata') }}
                        </div>
                    </template>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="p-5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                <button type="button" @click="closeModal()"
                        class="sf-btn-3d px-5 py-2 rounded-md text-xs font-bold cursor-pointer">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
