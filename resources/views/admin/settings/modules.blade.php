@extends('layouts.admin.app')

@section('title', __('messages.business_modules') . ' - ' . $store->name)
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-2 pb-6">

    {{-- Breadcrumb & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-1">
        <div>
            <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-primary-600 dark:hover:text-primary-400">{{ __('messages.admin_dashboard') }}</a>
                <span>/</span>
                <a href="{{ route('store.admin.settings.edit', ['store_slug' => $store->slug]) }}" class="hover:text-primary-600 dark:hover:text-primary-400">{{ __('messages.settings_storefront_settings') }}</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-medium">{{ __('messages.business_modules') }}</span>
            </div>
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mt-0.5">{{ __('messages.business_modules') }}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('messages.modules_description') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('store.admin.channels.index', ['store_slug' => $store->slug]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                {{ __('messages.sales_channels') }}
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 p-3 text-xs text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
            <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 p-3 text-xs text-rose-800 dark:text-rose-200 flex items-center gap-2">
            <svg class="h-4 w-4 text-rose-600 dark:text-rose-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Centered Row-based Stat Cards --}}
    <div class="grid grid-cols-3 gap-2">
        <div class="bg-white dark:bg-slate-800 rounded-lg p-2.5 border border-slate-200 dark:border-slate-700 flex items-center justify-center gap-3">
            <div class="h-8 w-8 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center flex-shrink-0">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </div>
            <div class="text-center">
                <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">{{ __('messages.total_modules') }}</div>
                <div class="text-base font-bold text-slate-800 dark:text-white">{{ $stats['total'] }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg p-2.5 border border-slate-200 dark:border-slate-700 flex items-center justify-center gap-3">
            <div class="h-8 w-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="text-center">
                <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">{{ __('messages.enabled_modules') }}</div>
                <div class="text-base font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['enabled'] }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg p-2.5 border border-slate-200 dark:border-slate-700 flex items-center justify-center gap-3">
            <div class="h-8 w-8 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="text-center">
                <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">{{ __('messages.disabled_modules') }}</div>
                <div class="text-base font-bold text-amber-600 dark:text-amber-400">{{ $stats['disabled'] }}</div>
            </div>
        </div>
    </div>

    {{-- Capabilities by Group --}}
    @foreach($groupedCapabilities as $groupKey => $capabilities)
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
            <div class="bg-slate-50 dark:bg-slate-800/80 px-4 py-2 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    {{ __('messages.group_' . $groupKey) }}
                </span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400">
                    {{ count($capabilities) }} {{ __('messages.modules') }}
                </span>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-700/50">
                @foreach($capabilities as $capKey => $def)
                    @php
                        $state = $capabilitiesState[$capKey];
                        $isEnabled = $state['is_enabled'];
                        $hasBlockers = !empty($state['blockers']);
                    @endphp
                    <div class="p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-slate-50/50 dark:hover:bg-slate-750 transition">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs sm:text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ app()->getLocale() === 'my' ? $def['name_mm'] : $def['name_en'] }}
                                </span>
                                <span class="font-mono text-[10px] text-slate-400 dark:text-slate-500 px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 rounded">
                                    {{ $capKey }}
                                </span>
                                @if($isEnabled)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        {{ __('messages.active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">
                                        {{ __('messages.disabled') }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                {{ $def['description'] }}
                            </p>

                            @if($hasBlockers)
                                <div class="mt-2 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 p-2 text-[11px] text-amber-800 dark:text-amber-200">
                                    <div class="font-semibold flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        {{ __('messages.blockers_detected_title') }}:
                                    </div>
                                    <ul class="list-disc list-inside mt-0.5 pl-1 space-y-0.5">
                                        @foreach($state['blockers'] as $b)
                                            <li>{{ __($b['message_key'], ['count' => $b['count']]) }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 self-end sm:self-center">
                            <form action="{{ route('store.admin.modules.toggle', ['store_slug' => $store->slug]) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="capability" value="{{ $capKey }}">
                                <button type="submit"
                                    @if($isEnabled && $hasBlockers) disabled @endif
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition shadow-sm
                                        {{ $isEnabled
                                            ? ($hasBlockers ? 'bg-slate-200 text-slate-400 cursor-not-allowed dark:bg-slate-700 dark:text-slate-500' : 'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800')
                                            : 'bg-primary-600 text-white hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600' }}">
                                    @if($isEnabled)
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                                        {{ __('messages.disable') }}
                                    @else
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
                                        {{ __('messages.enable') }}
                                    @endif
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

</div>
@endsection
