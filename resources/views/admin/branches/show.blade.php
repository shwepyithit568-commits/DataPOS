@extends('layouts.admin.app')

@section('title', $branch->name . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="w-full max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <a href="{{ route('store.admin.branches.index', ['store_slug' => $store->slug]) }}"
               class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1 mb-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>{{ __('messages.back_to_branches') }}</span>
            </a>
            <div class="flex items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit">
                    {{ $branch->name }}
                </h1>
                @if($branch->is_default)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                        {{ __('messages.branches_default_badge') }}
                    </span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('store.admin.branches.edit', ['store_slug' => $store->slug, 'branch' => $branch->id]) }}"
               class="admin-primary-btn bg-violet-600 hover:bg-violet-500">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>{{ __('messages.edit') }}</span>
            </a>
        </div>
    </div>

    {{-- Details Profile Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
        <h2 class="text-xs font-black uppercase tracking-wider text-violet-600 dark:text-violet-400 font-mono">
            {{ __('messages.branches_info_contacts') }}
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="block text-xs font-bold text-slate-400">{{ __('messages.branches_code') }}</span>
                <span class="font-bold font-mono text-slate-900 dark:text-slate-100">{{ $branch->code ?? '-' }}</span>
            </div>
            <div>
                <span class="block text-xs font-bold text-slate-400">{{ __('messages.branches_manager') }}</span>
                <span class="font-bold text-slate-900 dark:text-slate-100">{{ $branch->manager_name ?? '-' }}</span>
            </div>
            <div>
                <span class="block text-xs font-bold text-slate-400">{{ __('messages.branches_phone') }}</span>
                <span class="font-bold font-mono text-slate-900 dark:text-slate-100">{{ $branch->phone ?? '-' }}</span>
            </div>
            <div class="sm:col-span-2">
                <span class="block text-xs font-bold text-slate-400">{{ __('messages.branches_address') }}</span>
                <span class="text-slate-700 dark:text-slate-300">{{ $branch->address ?? '-' }}</span>
            </div>
            <div>
                <span class="block text-xs font-bold text-slate-400">{{ __('messages.status') }}</span>
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold {{ $branch->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                    {{ $branch->is_active ? __('messages.branches_is_active') : __('messages.inactive') }}
                </span>
            </div>
            @if($branch->notes)
                <div class="sm:col-span-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <span class="block text-xs font-bold text-slate-400">{{ __('messages.branches_notes') }}</span>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">{{ $branch->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Linked Warehouses Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
        <h2 class="text-xs font-black uppercase tracking-wider text-violet-600 dark:text-violet-400 font-mono">
            {{ __('messages.branches_linked_warehouses') }} ({{ count($branch->warehouses) }})
        </h2>

        @if($branch->warehouses->isEmpty())
            <p class="text-xs text-slate-400">{{ __('messages.branches_no_warehouses_linked') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-mono uppercase">
                            <th class="pb-2">{{ __('messages.branches_name') }}</th>
                            <th class="pb-2">{{ __('messages.code') }}</th>
                            <th class="pb-2">{{ __('messages.status') }}</th>
                            <th class="pb-2 text-right">{{ __('messages.default') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @foreach($branch->warehouses as $wh)
                            <tr>
                                <td class="py-2.5 font-bold text-slate-900 dark:text-slate-100">
                                    📦 {{ $wh->name }}
                                </td>
                                <td class="py-2.5 font-mono text-slate-500">
                                    {{ $wh->code ?? '-' }}
                                </td>
                                <td class="py-2.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $wh->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $wh->is_active ? __('messages.active') : __('messages.inactive') }}
                                    </span>
                                </td>
                                <td class="py-2.5 text-right font-mono">
                                    @if($wh->is_default)
                                        <span class="text-emerald-600 font-bold">★ {{ __('messages.default') }}</span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
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
