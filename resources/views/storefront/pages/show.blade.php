@extends('layouts.storefront.app')

@section('title', $title . ' - ' . ($store->setting?->store_name ?? $store->name))

@section('content')
<div class="max-w-4xl mx-auto py-4 sm:py-8 px-2 sm:px-4">
    {{-- Breadcrumb Navigation --}}
    <nav aria-label="{{ __('messages.breadcrumb') ?? 'Breadcrumb' }}" class="mb-4 sm:mb-6 text-xs font-semibold text-slate-500 dark:text-slate-400 flex items-center gap-1.5 flex-wrap">
        <a href="{{ $store->slug ? url('/?store_slug=' . $store->slug) : url('/') }}" class="hover:text-sky-600 dark:hover:text-sky-400 transition flex items-center gap-1">
            <span aria-hidden="true">🏠</span>
            <span>{{ __('messages.home') }}</span>
        </a>
        <span>/</span>
        <span class="text-slate-800 dark:text-slate-200 font-bold truncate max-w-xs">{{ $title }}</span>
    </nav>

    {{-- Main Page Article Card --}}
    <article class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-4 sm:p-8 md:p-10 shadow-sm transition">
        {{-- Page Header --}}
        <header class="mb-6 sm:mb-8 border-b border-slate-100 dark:border-slate-800 pb-5 sm:pb-6">
            <h1 class="text-xl sm:text-3xl md:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
                {{ $title }}
            </h1>

            @if (!empty($summary))
                <p class="mt-2.5 sm:mt-3 text-sm sm:text-base font-medium text-slate-600 dark:text-slate-300 leading-relaxed">
                    {{ $summary }}
                </p>
            @endif

            @if ($page->published_at)
                <div class="mt-3 sm:mt-4 flex items-center gap-3 text-xs font-semibold text-slate-400 dark:text-slate-500">
                    <span class="flex items-center gap-1">
                        <span>🕒</span>
                        <time datetime="{{ $page->published_at->toISOString() }}">
                            {{ $page->published_at->format('M d, Y') }}
                        </time>
                    </span>
                </div>
            @endif
        </header>

        {{-- Featured Image (if available) --}}
        @if (!empty($page->featured_image_path))
            <div class="mb-6 sm:mb-8 overflow-hidden rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
                <img
                    src="{{ asset('storage/' . $page->featured_image_path) }}"
                    alt="{{ $title }}"
                    class="w-full max-h-[420px] object-cover object-center shadow-xs"
                    loading="lazy"
                />
            </div>
        @endif

        {{-- Markdown Rendered Content --}}
        <div class="sf-page-content text-slate-800 dark:text-slate-200 text-sm sm:text-base leading-relaxed space-y-4">
            {!! $renderedContent !!}
        </div>
    </article>
</div>

<style>
    /* Styling for rendered markdown typography */
    .sf-page-content h1 { font-size: 1.5rem; font-weight: 900; margin-top: 1.5rem; margin-bottom: 0.75rem; color: var(--sf-primary, #0ea5e9); line-height: 1.25; }
    .sf-page-content h2 { font-size: 1.25rem; font-weight: 800; margin-top: 1.25rem; margin-bottom: 0.5rem; color: #1e293b; line-height: 1.3; }
    .dark .sf-page-content h2 { color: #f8fafc; }
    .sf-page-content h3 { font-size: 1.1rem; font-weight: 700; margin-top: 1rem; margin-bottom: 0.5rem; color: #334155; }
    .dark .sf-page-content h3 { color: #e2e8f0; }
    .sf-page-content p { margin-bottom: 1rem; line-height: 1.7; }
    .sf-page-content ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; }
    .sf-page-content ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1rem; }
    .sf-page-content li { margin-bottom: 0.35rem; }
    .sf-page-content a { color: var(--sf-primary, #0ea5e9); font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
    .sf-page-content blockquote { border-left: 4px solid var(--sf-primary, #0ea5e9); padding-left: 1rem; font-style: italic; color: #64748b; margin: 1rem 0; }
    .dark .sf-page-content blockquote { color: #94a3b8; }
    .sf-page-content code { background-color: #f1f5f9; padding: 0.15rem 0.4rem; border-radius: 0.375rem; font-family: monospace; font-size: 0.875em; }
    .dark .sf-page-content code { background-color: #1e293b; }
    .sf-page-content pre { background-color: #0f172a; color: #f8fafc; padding: 1rem; border-radius: 0.75rem; overflow-x: auto; margin-bottom: 1rem; }
    .sf-page-content pre code { background-color: transparent; padding: 0; color: inherit; }
    .sf-page-content img { max-width: 100%; height: auto; border-radius: 0.75rem; margin: 1rem 0; }
    .sf-page-content table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
    .sf-page-content th, .sf-page-content td { border: 1px solid #e2e8f0; padding: 0.5rem 0.75rem; text-align: left; }
    .dark .sf-page-content th, .dark .sf-page-content td { border-color: #334155; }
    .sf-page-content th { background-color: #f8fafc; font-weight: 800; }
    .dark .sf-page-content th { background-color: #1e293b; }
</style>
@endsection
