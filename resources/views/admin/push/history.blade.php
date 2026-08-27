@extends('layouts.admin.app')

@php
    $routeParams = ['store_slug' => $store->slug];
    $counts = $counts ?? [
        'all' => $logs->count(),
        'order' => 0,
        'payment' => 0,
        'status' => 0,
        'system' => 0,
    ];
@endphp

@section('title', __('messages.push_history_btn') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5">
    
    {{-- ============================================================
         PAGE HEADER — Eyebrow, title, subtitle & Back CTA
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-indigo-100 dark:border-indigo-900/60 mb-0.5">
                <span>🔔</span>
                <span>{{ __('messages.sidebar_push_notifications') }}</span>
                <span class="text-slate-400 dark:text-slate-500">·</span>
                <span class="font-normal normal-case text-slate-500 dark:text-slate-400">Broadcast Audit Log</span>
            </div>
            <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                {{ __('messages.push_history_btn') }}
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                {{ $store->name }} · စတိုးမှ ပေးပို့ခဲ့သော နောက်ဆုံး အသိပေးချက် (၅၀) ၏ မှတ်တမ်းများ
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('store.admin.push.index', ['store_slug' => $store->slug]) }}"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1.5 active:scale-95 shadow-2xs">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                <span>{{ __('messages.back') }}</span>
            </a>
        </div>
    </header>

    {{-- ============================================================
         KPI STAT CARDS (4 Summary Cards)
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2">
        {{-- Total Logged --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">စုစုပေါင်း ပေးပို့မှု</span>
                <span class="text-xs">📋</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 mt-1 font-mono tracking-tight">
                {{ number_format($counts['all']) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">All dispatches logged</div>
        </div>

        {{-- Orders --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">အော်ဒါသစ်များ</span>
                <span class="text-xs">🆕</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-violet-600 dark:text-violet-400 mt-1 font-mono tracking-tight">
                {{ number_format($counts['order']) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">New order alerts</div>
        </div>

        {{-- Payments --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">ငွေပေးချေမှုများ</span>
                <span class="text-xs">💵</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono tracking-tight">
                {{ number_format($counts['payment']) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Payment receipts</div>
        </div>

        {{-- Broadcast Studio --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">သတင်းလွှာ/စမ်းသပ်</span>
                <span class="text-xs">📣</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1 font-mono tracking-tight">
                {{ number_format($counts['system']) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Broadcasts dispatched</div>
        </div>
    </div>

    {{-- ============================================================
         TYPE FILTER PILL BAR
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs flex flex-wrap items-center gap-1.5 text-xs">
        <a href="{{ route('store.admin.push.history', array_merge($routeParams, ['type' => null])) }}"
           class="px-3 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $type === null ? 'bg-violet-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
            <span>All Types</span>
            <span class="text-[10px] font-mono px-1.5 py-0.2 rounded-full {{ $type === null ? 'bg-violet-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('store.admin.push.history', array_merge($routeParams, ['type' => 'order'])) }}"
           class="px-3 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $type === 'order' ? 'bg-violet-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
            <span>🆕 New Order</span>
            <span class="text-[10px] font-mono px-1.5 py-0.2 rounded-full {{ $type === 'order' ? 'bg-violet-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $counts['order'] }}</span>
        </a>
        <a href="{{ route('store.admin.push.history', array_merge($routeParams, ['type' => 'payment'])) }}"
           class="px-3 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $type === 'payment' ? 'bg-violet-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
            <span>💵 Payment</span>
            <span class="text-[10px] font-mono px-1.5 py-0.2 rounded-full {{ $type === 'payment' ? 'bg-violet-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $counts['payment'] }}</span>
        </a>
        <a href="{{ route('store.admin.push.history', array_merge($routeParams, ['type' => 'status'])) }}"
           class="px-3 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $type === 'status' ? 'bg-violet-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
            <span>📦 Status</span>
            <span class="text-[10px] font-mono px-1.5 py-0.2 rounded-full {{ $type === 'status' ? 'bg-violet-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $counts['status'] }}</span>
        </a>
        <a href="{{ route('store.admin.push.history', array_merge($routeParams, ['type' => 'system'])) }}"
           class="px-3 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $type === 'system' ? 'bg-violet-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
            <span>📣 Broadcast</span>
            <span class="text-[10px] font-mono px-1.5 py-0.2 rounded-full {{ $type === 'system' ? 'bg-violet-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $counts['system'] }}</span>
        </a>
    </div>

    {{-- ============================================================
         HISTORY AUDIT LOG TABLE
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden transition">
        @if ($logs->isEmpty())
            <div class="p-8 text-center text-xs text-slate-400 font-bold space-y-1">
                <p>No notifications logged yet.</p>
                <p class="text-[11px] font-normal">Push notification dispatches appear here automatically when orders, payments or broadcasts occur.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 min-w-[720px]">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="p-2.5">Date & Time</th>
                            <th class="p-2.5">Type</th>
                            <th class="p-2.5">Title</th>
                            <th class="p-2.5">Message Body</th>
                            <th class="p-2.5 text-right">Recipients</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($logs as $log)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 align-top">
                                <td class="p-2.5 font-mono text-[11px] text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ $log->sent_at?->format('Y-m-d H:i') ?? $log->created_at?->format('Y-m-d H:i') }}
                                </td>
                                <td class="p-2.5">
                                    @php
                                        $badge = match ($log->type) {
                                            'order' => ['bg-violet-100 dark:bg-violet-950/70 text-violet-700 dark:text-violet-300', 'Order'],
                                            'payment' => ['bg-emerald-100 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300', 'Payment'],
                                            'status' => ['bg-sky-100 dark:bg-sky-950/70 text-sky-700 dark:text-sky-300', 'Status'],
                                            default => ['bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300', 'System'],
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $badge[0] }}">{{ $badge[1] }}</span>
                                </td>
                                <td class="p-2.5 font-bold text-slate-900 dark:text-slate-100">
                                    @if ($log->url)
                                        <a href="{{ $log->url }}" target="_blank" rel="noopener" class="text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1">
                                            <span>{{ $log->title }}</span>
                                            <span class="text-[10px]">↗</span>
                                        </a>
                                    @else
                                        <span>{{ $log->title }}</span>
                                    @endif
                                </td>
                                <td class="p-2.5 text-slate-600 dark:text-slate-400 max-w-[24rem]">
                                    <span class="line-clamp-2" title="{{ $log->body }}">{{ $log->body }}</span>
                                </td>
                                <td class="p-2.5 text-right font-mono font-bold text-violet-600 dark:text-violet-400 whitespace-nowrap">
                                    {{ number_format($log->recipient_count) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
