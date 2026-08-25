@extends('layouts.admin.app')

@section('title', 'System Audit Logs - ' . $store->name)

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12"
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

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 grid place-items-center text-xl sm:text-2xl font-bold shadow-sm flex-shrink-0">
                🛡️
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <a href="{{ route('store.admin.dashboard', $storeRouteParams) }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition">
                        Dashboard
                    </a>
                    <span>/</span>
                    <span class="text-rose-600 dark:text-rose-400">Security & Audit</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span>လုပ်ဆောင်ချက် မှတ်တမ်းများ (System Audit Trail Logs)</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $store->name }} · စျေးနှုန်း၊ စတော့ဖြတ်တောက်မှု၊ ဘောက်ချာ၊ ငွေစာရင်းနှင့် ဝန်ထမ်းခွင့်ပြုချက် မှတ်တမ်းများ</p>
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <a href="{{ $exportUrl }}"
               class="px-3.5 py-2.5 rounded-2xl text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-2 shadow-sm">
                <span>📊</span>
                <span>Export CSV</span>
            </a>
            <a href="{{ route('store.admin.roles.index', $storeRouteParams) }}"
               class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-black bg-rose-600 hover:bg-rose-500 text-white shadow-lg shadow-rose-500/20 transition flex items-center gap-2 active:scale-95">
                <span>🔑</span>
                <span>ဝန်ထမ်းရာထူးများ စီမံမည်</span>
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-3xl text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <span class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900 grid place-items-center text-emerald-600 dark:text-emerald-300 font-black">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- 2. 5 Key KPI Summary Cards (Interactive Category Filter Tabs) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-3.5">
        {{-- Total All --}}
        <a href="{{ route('store.admin.audit-logs.index', array_merge($storeRouteParams, ['category' => 'all'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $category === 'all' ? 'border-slate-700 dark:border-slate-300 ring-2 ring-slate-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 truncate">Total Logs</span>
                <span class="text-base">📋</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono tracking-tight">{{ number_format($stats['total']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">စုစုပေါင်း မှတ်တမ်းအားလုံး</p>
        </a>

        {{-- Pricing & Sales --}}
        <a href="{{ route('store.admin.audit-logs.index', array_merge($storeRouteParams, ['category' => 'pricing_sales'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $category === 'pricing_sales' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-amber-600 dark:text-amber-400 truncate">Pricing & Sales</span>
                <span class="text-base">💰</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight">{{ number_format($stats['pricing_sales']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">စျေးနှုန်း/ဘောက်ချာ ပြင်ဆင်မှု</p>
        </a>

        {{-- Inventory --}}
        <a href="{{ route('store.admin.audit-logs.index', array_merge($storeRouteParams, ['category' => 'inventory'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $category === 'inventory' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-blue-600 dark:text-blue-400 truncate">Inventory & Stock</span>
                <span class="text-base">📦</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-blue-600 dark:text-blue-400 font-mono tracking-tight">{{ number_format($stats['inventory']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">စတော့ဖြတ်တောက် ချိန်ညှိမှု</p>
        </a>

        {{-- Financial --}}
        <a href="{{ route('store.admin.audit-logs.index', array_merge($storeRouteParams, ['category' => 'financial'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $category === 'financial' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 truncate">Cash & Finance</span>
                <span class="text-base">💵</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">{{ number_format($stats['financial']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">ငွေထုတ်/နေ့ချုပ် အတည်ပြုချက်</p>
        </a>

        {{-- Security --}}
        <a href="{{ route('store.admin.audit-logs.index', array_merge($storeRouteParams, ['category' => 'security'])) }}"
           class="col-span-2 sm:col-span-1 rounded-3xl bg-white dark:bg-slate-900 border {{ $category === 'security' ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-rose-600 dark:text-rose-400 truncate">Security & Roles</span>
                <span class="text-base">🛡️</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight">{{ number_format($stats['security']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">PIN/ရာထူး ခွင့်ပြုချက်များ</p>
        </a>
    </div>

    {{-- 3. Unified Admin Toolbar --}}
    <x-admin.toolbar
        :search="request('search', '')"
        searchPlaceholder="လုပ်ဆောင်သူ၊ Action၊ Entity ID သို့မဟုတ် IP လိပ်စာ ရှာဖွေပါ..."
        :sort="request('sort', $sort)"
        :sortOptions="[
            'newest' => 'အသစ်ဆုံး (Newest First)',
            'oldest' => 'အဟောင်းဆုံး (Oldest First)',
        ]"
        :filters="[
            'category' => [
                'label'   => 'Category',
                'options' => [
                    'all'               => 'အားလုံး (All Categories)',
                    'pricing_sales'     => '💰 Pricing & Sales (စျေးနှုန်း/အရောင်း)',
                    'inventory'         => '📦 Inventory & Stock (စတော့/ပစ္စည်း)',
                    'financial'         => '💵 Cash & Finance (ငွေစာရင်း/နေ့ချုပ်)',
                    'security'          => '🛡️ Security & Roles (လုံခြုံရေး/ရာထူး)',
                    'marketing_loyalty' => '🏷️ Promotions & Loyalty (ပရိုမိုးရှင်း)',
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
                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 transition flex items-center gap-1">
                        <span>🔍</span>
                        <span>အသေးစိတ်</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                လုပ်ဆောင်ချက် မှတ်တမ်း မရှိသေးပါ။ (No audit trail logs found.)
            </div>
        @endforelse
    </div>

    {{-- 5. Table View (Alpine Toggle) --}}
    <div x-show="viewMode === 'table'" class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4">အချိန် (Timestamp)</th>
                        <th class="py-3.5 px-4">လုပ်ဆောင်သူ (Actor)</th>
                        <th class="py-3.5 px-4">လုပ်ဆောင်ချက် (Action / Event)</th>
                        <th class="py-3.5 px-4">သက်ဆိုင်ရာ (Entity)</th>
                        <th class="py-3.5 px-4">IP လိပ်စာ (IP Address)</th>
                        <th class="py-3.5 px-4 text-right">အသေးစိတ် (Details)</th>
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
                                        class="px-3 py-1 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 transition inline-flex items-center gap-1">
                                    <span>🔍</span>
                                    <span>Details</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                                လုပ်ဆောင်ချက် မှတ်တမ်း မရှိသေးပါ။ (No audit trail logs found.)
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
                            Audit Trail Inspection
                        </span>
                        <span class="font-mono text-xs text-slate-400" x-text="'#LOG-' + String(selectedLog?.id || '').padStart(5, '0')"></span>
                    </div>
                    <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 mt-1" x-text="selectedLog?.action_name"></h2>
                    <p class="text-xs text-slate-400 font-mono mt-0.5" x-text="selectedLog?.action"></p>
                </div>
                <button @click="closeModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    ✕
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                {{-- Quick Summary Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-3">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">👤 လုပ်ဆောင်သူ (Actor)</div>
                        <div class="text-xs font-bold text-slate-900 dark:text-slate-100 mt-1 truncate" x-text="selectedLog?.actor?.name || 'System / Automated'"></div>
                        <div class="text-[11px] font-mono text-slate-400 truncate" x-text="selectedLog?.actor?.phone || '—'"></div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-3">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">📅 ရက်စွဲနှင့် အချိန်</div>
                        <div class="text-xs font-bold text-slate-900 dark:text-slate-100 mt-1 truncate" x-text="selectedLog?.created_at"></div>
                        <div class="text-[11px] text-slate-400 truncate" x-text="selectedLog?.time_ago"></div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-3 col-span-2 sm:col-span-1">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">🌐 IP & Device</div>
                        <div class="text-xs font-mono font-bold text-slate-900 dark:text-slate-100 mt-1 truncate" x-text="selectedLog?.ip_address || '—'"></div>
                        <div class="text-[11px] font-mono text-slate-400 truncate" x-text="selectedLog?.entity_type ? (selectedLog.entity_type + ' #' + selectedLog.entity_id) : 'System'"></div>
                    </div>
                </div>

                {{-- Formatted Metadata --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                            <span>📝</span>
                            <span>အသေးစိတ် အချက်အလက် (Event Metadata & Changes)</span>
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
                                <button type="button" @click="showRaw = !showRaw" class="text-[11px] font-bold text-rose-600 dark:text-rose-400 hover:underline">
                                    <span x-text="showRaw ? '▼ Hide Raw JSON' : '► View Raw JSON Payload'"></span>
                                </button>
                                <pre x-show="showRaw"
                                     class="mt-2 bg-slate-950 text-slate-200 rounded-2xl p-4 text-[11px] font-mono overflow-x-auto max-h-48"
                                     x-text="JSON.stringify(selectedLog.metadata, null, 2)"></pre>
                            </div>
                        </div>
                    </template>

                    <template x-if="!selectedLog || !selectedLog.metadata || Object.keys(selectedLog.metadata).length === 0">
                        <div class="bg-slate-50 dark:bg-slate-800/40 rounded-2xl p-6 text-center text-xs text-slate-400 font-medium">
                            ဤမှတ်တမ်းတွင် အပိုဆောင်း Metadata အချက်အလက် မပါဝင်ပါ။ (No extra payload attached.)
                        </div>
                    </template>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="p-5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                <button type="button" @click="closeModal()"
                        class="px-5 py-2 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
                    ပိတ်မည် (Close)
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
