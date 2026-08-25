@extends('layouts.admin.app')

@section('title', 'Audit Log #' . $log->id . ' - ' . $store->name)

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $humanAction = \App\Http\Controllers\Admin\AuditLogController::humanizeAction($log->action);
    $categoryLabel = \App\Http\Controllers\Admin\AuditLogController::categoryOfAction($log->action);
    $metaArray = is_array($log->metadata) ? $log->metadata : json_decode($log->metadata ?? '[]', true);
@endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12">

    {{-- Top Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('store.admin.audit-logs.index', $storeRouteParams) }}"
               class="w-10 h-10 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 grid place-items-center text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-sm">
                ←
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <a href="{{ route('store.admin.audit-logs.index', $storeRouteParams) }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition">
                        System Audit Logs
                    </a>
                    <span>/</span>
                    <span class="text-rose-600 dark:text-rose-400">Log Detail</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span>#LOG-{{ str_pad($log->id, 5, '0', STR_PAD_LEFT) }}</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase whitespace-nowrap bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                        {{ $log->action }}
                    </span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $log->created_at?->format('M d, Y h:i:s A') }} · {{ $store->name }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <a href="{{ route('store.admin.audit-logs.index', $storeRouteParams) }}"
               class="px-4 py-2.5 rounded-2xl text-xs font-black bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-sm">
                ← မှတ်တမ်းများစာရင်းသို့
            </a>
        </div>
    </div>

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Left 2 Columns --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- Action Info --}}
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>⚡</span>
                    <span>လုပ်ဆောင်ချက် အသေးစိတ် (Event Description)</span>
                </h3>

                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-slate-100">{{ $humanAction }}</h2>
                    <div class="font-mono text-xs text-slate-400 mt-1">Action Code: {{ $log->action }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">အမျိုးအစား (Category)</div>
                        <div class="font-bold text-slate-900 dark:text-slate-100 text-sm">{{ $categoryLabel }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">ပစ်မှတ် (Target Entity)</div>
                        <div class="font-mono font-bold text-slate-900 dark:text-slate-100 text-sm">
                            {{ $log->entity_type ? ($log->entity_type . ' #' . $log->entity_id) : 'System / Global' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Metadata Card --}}
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>📝</span>
                    <span>အပြောင်းအလဲနှင့် အချက်အလက်များ (Payload & Changes)</span>
                </h3>

                @if (!empty($metaArray))
                    <div class="space-y-2">
                        <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 space-y-2 text-xs">
                            @foreach ($metaArray as $key => $val)
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-1 sm:gap-4 py-1.5 border-b border-slate-100 dark:border-slate-700/60 last:border-0">
                                    <span class="font-mono font-bold text-slate-500 dark:text-slate-400 uppercase text-[11px]">{{ $key }}</span>
                                    <span class="font-mono font-semibold text-slate-900 dark:text-slate-100 text-right break-all">
                                        {{ is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Raw JSON --}}
                        <div class="pt-2">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Raw JSON Payload</div>
                            <pre class="bg-slate-950 text-slate-200 rounded-2xl p-4 text-[11px] font-mono overflow-x-auto">{{ json_encode($metaArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                @else
                    <div class="bg-slate-50 dark:bg-slate-800/40 rounded-2xl p-6 text-center text-xs text-slate-400">
                        ဤမှတ်တမ်းတွင် အပိုဆောင်း Metadata အချက်အလက် မပါဝင်ပါ။
                    </div>
                @endif
            </div>
        </div>

        {{-- Right 1 Column: Actor & Metadata --}}
        <div class="space-y-4">
            {{-- Actor Info --}}
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 shadow-sm space-y-3">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>👤</span>
                    <span>လုပ်ဆောင်သူ (Actor)</span>
                </h3>

                <div class="space-y-2 text-xs">
                    <div>
                        <div class="font-bold text-slate-900 dark:text-slate-100 text-sm">
                            {{ $log->actor?->name ?? 'System / Automated' }}
                        </div>
                        @if ($log->actor?->phone)
                            <div class="font-mono text-slate-400 text-xs mt-0.5">📞 {{ $log->actor->phone }}</div>
                        @endif
                    </div>
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-slate-400 font-semibold">User ID</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100">#{{ $log->actor_id ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-semibold">IP Address</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ $log->ip_address ?? '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Audit Log Meta --}}
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 shadow-sm space-y-3">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>ℹ️</span>
                    <span>မှတ်တမ်းအချက်အလက် (Metadata)</span>
                </h3>

                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-semibold">Log ID</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100">#{{ $log->id }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-semibold">Store ID</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100">#{{ $log->store_id }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-semibold">Logged At</span>
                        <span class="text-slate-700 dark:text-slate-300">{{ $log->created_at?->format('d M Y, h:i:s A') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-semibold">Time Elapsed</span>
                        <span class="text-slate-700 dark:text-slate-300">{{ $log->created_at?->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
