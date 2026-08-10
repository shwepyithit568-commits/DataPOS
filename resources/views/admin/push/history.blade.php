@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-6">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Push Notification History</h1>
            <p class="admin-page-sub">{{ $store->name }} · Last 50 dispatched notifications</p>
        </div>
    </div>

    {{-- Type filter --}}
    @php $routeParams = ['store_slug' => $store->slug]; @endphp
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('store.admin.push.history', array_merge($routeParams, ['type' => null])) }}"
           class="rounded-full px-3.5 py-1.5 text-xs font-bold transition {{ $type === null ? 'bg-violet-600 text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-800' }}">
            All
        </a>
        @foreach (['order' => '🆕 Order', 'payment' => '💵 Payment', 'status' => '📦 Status', 'system' => '📣 System'] as $value => $label)
            <a href="{{ route('store.admin.push.history', array_merge($routeParams, ['type' => $value])) }}"
               class="rounded-full px-3.5 py-1.5 text-xs font-bold transition {{ $type === $value ? 'bg-violet-600 text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-800' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- History table --}}
    <div class="admin-panel overflow-x-auto">
        @if ($logs->isEmpty())
            <p class="p-6 text-sm text-gray-500 dark:text-slate-400">
                No notifications logged yet.
                @if ($type === null)
                    They appear here automatically when new orders, status changes, payments or test sends are dispatched.
                @else
                    Nothing of this type has been sent yet.
                @endif
            </p>
        @else
            <table class="min-w-[820px] w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-slate-900/60 text-xs text-gray-600 dark:text-slate-300">
                    <tr>
                        <th class="p-3">Time</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Title</th>
                        <th class="p-3">Body</th>
                        <th class="p-3 text-right">Recipients</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                    @foreach ($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-900/40 align-top">
                            <td class="p-3 text-gray-500 dark:text-slate-400 whitespace-nowrap">
                                {{ $log->sent_at?->format('Y-m-d H:i') ?? $log->created_at?->format('Y-m-d H:i') }}
                            </td>
                            <td class="p-3">
                                @php
                                    $badge = match ($log->type) {
                                        'order' => ['bg-violet-100 dark:bg-violet-950/70 text-violet-700 dark:text-violet-300', 'Order'],
                                        'payment' => ['bg-emerald-100 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300', 'Payment'],
                                        'status' => ['bg-sky-100 dark:bg-sky-950/70 text-sky-700 dark:text-sky-300', 'Status'],
                                        default => ['bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300', 'System'],
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-bold {{ $badge[0] }}">{{ $badge[1] }}</span>
                            </td>
                            <td class="p-3 font-semibold text-gray-900 dark:text-slate-100">
                                @if ($log->url)
                                    <a href="{{ $log->url }}" target="_blank" rel="noopener" class="hover:text-violet-600 dark:hover:text-violet-400">{{ $log->title }}</a>
                                @else
                                    {{ $log->title }}
                                @endif
                            </td>
                            <td class="p-3 text-gray-600 dark:text-slate-300 max-w-[26rem]">
                                <span class="line-clamp-2" title="{{ $log->body }}">{{ $log->body }}</span>
                            </td>
                            <td class="p-3 text-right text-gray-600 dark:text-slate-300 whitespace-nowrap">{{ number_format($log->recipient_count) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
