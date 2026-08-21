@extends('layouts.admin.app')

@section('content')
@php
    // Modules that already shipped as real pages (sidebar uses real nav-links,
    // not the placeholder route) — excluded from the roadmap grid below.
    $shippedModules = ['suppliers', 'transfers', 'warehouses', 'buy-back', 'returns'];

    // Roadmap grid: every registry module still on the roadmap, grouped by
    // phase, with the current module surfaced in the status card instead.
    $roadmap = collect($modules)
        ->reject(fn ($meta, $slug) => in_array($slug, $shippedModules, true) || $slug === $module)
        ->groupBy(fn ($meta) => $meta[1])
        ->sortKeys();

    $phaseOrder = ['Phase 2', 'Phase 3', 'Phase 4'];

    // Contextual "ready now" shortcut for modules whose sibling feature is
    // already live (e.g. Web Products → the product catalog).
    $readyLinks = [
        'web-products' => [
            'url'   => url('/store/' . $store->slug . '/admin/products'),
            'label' => __('messages.products'),
        ],
    ];
@endphp
<div class="w-full space-y-4 sm:space-y-5">

    {{-- ============================================================
         HERO HEADER — eyebrow, module title, phase subtitle
         ============================================================ --}}
    <header class="admin-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400">
                {{ $moduleLabel }}
            </p>
            <h1 class="admin-page-title mt-0.5">{{ $moduleLabel }}</h1>
            <p class="admin-page-sub mt-1">
                {{ __('messages.coming_soon') }} — {{ __('messages.coming_soon_phase') }} {{ $phase }}
            </p>
        </div>
        <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}"
           class="admin-secondary-btn shrink-0">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M3 12h14m-5-5 5 5-5 5"/></svg>
            <span class="hidden sm:inline">{{ __('messages.coming_soon_back') }}</span>
        </a>
    </header>

    {{-- ============================================================
         STATUS CARD — the module is on the roadmap, not yet built
         ============================================================ --}}
    <section class="rounded-2xl border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-800/90 px-5 py-8 sm:px-10 sm:py-12 text-center shadow-sm">
        <span class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-500 dark:bg-violet-500/10 dark:text-violet-300" aria-hidden="true">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8">
                <path d="M21 12a9 9 0 1 1-9-9m9 0v6h-6"/>
            </svg>
        </span>

        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:border-slate-600 dark:bg-slate-700/60 dark:text-slate-300">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                {{ __('messages.coming_soon_on_roadmap') }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-violet-600 px-3 py-1 text-[11px] font-black uppercase tracking-wider text-white dark:bg-violet-400 dark:text-violet-950">
                {{ __('messages.coming_soon_phase') }}
                <span class="tabular-nums">{{ $phase }}</span>
            </span>
        </div>

        <h2 class="mt-5 font-outfit text-xl sm:text-2xl font-bold text-slate-800 dark:text-slate-100">
            {{ __('messages.coming_soon_title') }}
        </h2>

        <p class="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-slate-500 dark:text-slate-400">
            {{ __('messages.coming_soon_text', ['phase' => $phase]) }}
        </p>

        <div class="mt-7 flex flex-wrap items-center justify-center gap-2.5">
            @if (isset($readyLinks[$module]))
                <a href="{{ $readyLinks[$module]['url'] }}"
                   class="admin-secondary-btn">
                    <span>{{ $readyLinks[$module]['label'] }}</span>
                </a>
            @endif
            <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}"
               class="admin-primary-btn">
                <span>{{ __('messages.coming_soon_back') }}</span>
            </a>
        </div>
    </section>

    {{-- ============================================================
         ROADMAP — every other module still in the pipeline, grouped
         by phase.  Data comes from the ComingSoonController registry.
         ============================================================ --}}
    @if ($roadmap->isNotEmpty())
        <section class="space-y-5 sm:space-y-6" aria-labelledby="roadmap-title">
            <div>
                <h2 id="roadmap-title" class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100">
                    {{ __('messages.coming_soon_roadmap') }}
                </h2>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('messages.coming_soon_roadmap_sub') }}
                </p>
            </div>

            @foreach ($phaseOrder as $phaseName)
                @php $phaseModules = $roadmap->get($phaseName, collect()); @endphp
                @if ($phaseModules->isEmpty())
                    @continue
                @endif
                <div>
                    <h3 class="mb-2 text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        {{ __('messages.coming_soon_phase') }} {{ $phaseName }}
                    </h3>
                    <div class="flex flex-wrap gap-2" role="list">
                        @foreach ($phaseModules as $slug => $meta)
                            <a href="{{ route('store.admin.coming-soon', ['store_slug' => $store->slug, 'module' => $slug]) }}"
                               role="listitem"
                               class="group inline-flex items-center gap-2 min-h-11 px-4 py-2 rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-800/90 text-sm font-semibold text-slate-700 dark:text-slate-200 transition hover:border-violet-300 dark:hover:border-violet-700 hover:shadow-sm active:scale-[.98]">
                                <span class="w-1.5 h-1.5 rounded-full bg-violet-400 group-hover:bg-violet-600 transition" aria-hidden="true"></span>
                                {{ __("messages.{$meta[0]}") }}
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 group-hover:text-violet-500 transition">{{ $phaseName }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </section>
    @endif
</div>
@endsection
