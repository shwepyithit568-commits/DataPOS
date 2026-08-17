@extends('layouts.admin.app')

@section('content')
<div class="space-y-5 sm:space-y-6">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">{{ $moduleLabel }}</h1>
            <p class="admin-page-sub">{{ __('messages.coming_soon') }} — {{ __('messages.coming_soon_phase') }} {{ $phase }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-dashed border-violet-300 dark:border-violet-500/40 bg-white dark:bg-slate-900 p-8 sm:p-14 text-center shadow-sm">
        <span class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-500 dark:bg-violet-500/10 dark:text-violet-300" aria-hidden="true">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8">
                <path d="M21 12a9 9 0 1 1-9-9m9 0v6h-6"/>
            </svg>
        </span>

        <h2 class="mt-5 font-outfit text-xl font-bold text-slate-800 dark:text-slate-100">{{ __('messages.coming_soon_title') }}</h2>

        <p class="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-slate-500 dark:text-slate-400">
            {{ __('messages.coming_soon_text', ['phase' => $phase]) }}
        </p>

        <div class="mt-6 inline-flex items-center gap-2 rounded-full border border-violet-200 bg-violet-50 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-violet-600 dark:border-violet-500/25 dark:bg-violet-500/10 dark:text-violet-300">
            {{ __('messages.coming_soon_phase') }}
            <span class="rounded-full bg-violet-600 px-2 py-0.5 text-white dark:bg-violet-400 dark:text-violet-950">{{ $phase }}</span>
        </div>

        <div class="mt-8">
            <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}"
                class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M3 12h14m-5-5 5 5-5 5"/></svg>
                {{ __('messages.coming_soon_back') }}
            </a>
        </div>
    </div>
</div>
@endsection
