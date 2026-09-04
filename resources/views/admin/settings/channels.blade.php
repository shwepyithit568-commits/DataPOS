@extends('layouts.admin.app')

@section('title', __('messages.sales_channels') . ' - ' . $store->name)
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
                <span class="text-slate-800 dark:text-slate-200 font-medium">{{ __('messages.sales_channels') }}</span>
            </div>
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mt-0.5">{{ __('messages.sales_channels') }}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('messages.channels_description') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('store.admin.modules.index', ['store_slug' => $store->slug]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                {{ __('messages.business_modules') }}
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
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <div class="text-center">
                <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">{{ __('messages.total_channels') }}</div>
                <div class="text-base font-bold text-slate-800 dark:text-white">{{ $stats['total'] }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg p-2.5 border border-slate-200 dark:border-slate-700 flex items-center justify-center gap-3">
            <div class="h-8 w-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="text-center">
                <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">{{ __('messages.enabled_channels') }}</div>
                <div class="text-base font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['enabled'] }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg p-2.5 border border-slate-200 dark:border-slate-700 flex items-center justify-center gap-3">
            <div class="h-8 w-8 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="text-center">
                <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">{{ __('messages.disabled_channels') }}</div>
                <div class="text-base font-bold text-amber-600 dark:text-amber-400">{{ $stats['disabled'] }}</div>
            </div>
        </div>
    </div>

    {{-- Channel Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach($channels as $channelKey => $def)
            @php
                $state = $channelsState[$channelKey];
                $isEnabled = $state['is_enabled'];
                $hasBlockers = !empty($state['blockers']);
                $isProtected = $def['is_protected'];
            @endphp
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex flex-col justify-between shadow-sm">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-lg {{ $isEnabled ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/50 dark:text-primary-400' : 'bg-slate-100 text-slate-400 dark:bg-slate-700' }}">
                                @if($channelKey === 'pos')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 8h10M7 12h10M7 16h10"/></svg>
                                @elseif($channelKey === 'online_store')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                @else
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                                @endif
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ app()->getLocale() === 'my' ? $def['name_mm'] : $def['name_en'] }}
                                </h3>
                                <div class="font-mono text-[10px] text-slate-400">
                                    {{ $channelKey }}
                                </div>
                            </div>
                        </div>

                        <div>
                            @if($isProtected)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300">
                                    {{ __('messages.protected_core') }}
                                </span>
                            @elseif($isEnabled)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                    {{ __('messages.active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">
                                    {{ __('messages.disabled') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-3">
                        {{ app()->getLocale() === 'my' ? $def['description_mm'] : $def['description_en'] }}
                    </p>

                    @if(!empty($def['dependencies']))
                        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                            <span class="font-medium">{{ __('messages.requires') }}:</span>
                            @foreach($def['dependencies'] as $dep)
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 font-mono text-[10px] text-slate-700 dark:text-slate-300">{{ $dep }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if($hasBlockers)
                        <div class="mt-3 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 p-2.5 text-[11px] text-amber-800 dark:text-amber-200">
                            <div class="font-semibold flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                {{ __('messages.blockers_detected_title') }}:
                            </div>
                            <ul class="list-disc list-inside mt-1 pl-1 space-y-0.5">
                                @foreach($state['blockers'] as $b)
                                    <li>{{ __($b['message_key'], ['count' => $b['count']]) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700 flex justify-end">
                    @if($isProtected)
                        <span class="text-xs text-slate-400 italic">
                            {{ __('messages.channel_cannot_be_disabled') }}
                        </span>
                    @else
                        <form action="{{ route('store.admin.channels.toggle', ['store_slug' => $store->slug]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="channel" value="{{ $channelKey }}">
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
                    @endif
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
