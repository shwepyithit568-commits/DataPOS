@extends('layouts.storefront.app')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
@endphp

<div class="space-y-8">
    {{-- Blog Header --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 text-center relative overflow-hidden border border-slate-200/90 dark:border-slate-800/80 shadow-2xl">
        <div class="absolute -top-16 -right-16 w-48 h-48 bg-sky-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-16 w-48 h-48 bg-fuchsia-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10">
            <h1 class="text-2xl sm:text-3xl font-black font-outfit text-slate-900 dark:text-white">
                {{ __('messages.blog_title') }}
            </h1>
            <p class="mt-2 text-sm font-myanmar text-slate-500 dark:text-slate-600 max-w-2xl mx-auto">
                {{ __('messages.blog_subtitle') }}
            </p>
        </div>
    </div>

    {{-- Category filter chips --}}
    @if ($categories->count() > 0)
        <div class="flex flex-wrap items-center gap-2 justify-center">
            <a href="{{ url('/blog' . ($storeSlug ? '?store_slug=' . $storeSlug : '')) }}"
                class="px-3.5 py-1.5 rounded-full text-xs font-black transition {{ !request('category') ? 'bg-sky-600 text-white shadow-md shadow-sky-500/25' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-sky-400' }}">
                {{ __('messages.all') }}
            </a>
            @foreach ($categories as $cat)
                <a href="{{ url('/blog' . ($storeSlug ? '?store_slug=' . $storeSlug . '&' : '?') . 'category=' . urlencode($cat)) }}"
                    class="px-3.5 py-1.5 rounded-full text-xs font-black transition {{ request('category') === $cat ? 'bg-sky-600 text-white shadow-md shadow-sky-500/25' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-sky-400' }}">
                    🏷️ {{ $cat }}
                </a>
            @endforeach
        </div>
    @endif

    {{-- Posts Grid --}}
    @if ($posts->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
            @foreach ($posts as $post)
                @php
                    $postUrl = url('/blog/' . $post->slug . ($storeSlug ? '?store_slug=' . $storeSlug : ''));
                @endphp
                <article class="group bg-white dark:bg-slate-900 rounded-2xl overflow-hidden border border-slate-200/90 dark:border-slate-800/80 flex flex-col transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-sky-500/10">
                    <a href="{{ $postUrl }}" class="block relative aspect-[16/10] overflow-hidden bg-slate-200 dark:bg-slate-800">
                        @if ($post->image_path)
                            <img
                                src="{{ asset('storage/' . $post->image_path) }}"
                                alt="{{ $post->title }}"
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                loading="lazy"
                                data-img-fallback="hide"
                            />
                        @else
                            <div class="w-full h-full flex items-center justify-center text-5xl">📝</div>
                        @endif
                        <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-xs font-black bg-white/90 dark:bg-slate-900/90 backdrop-blur-md text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-slate-700 shadow-sm">
                            {{ $post->published_at?->format('M j, Y') ?? $post->created_at->format('M j, Y') }}
                        </span>
                        @if ($post->category)
                            <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-xs font-black bg-fuchsia-500/90 text-white backdrop-blur-md border border-fuchsia-300/40 shadow-sm">
                                🏷️ {{ $post->category }}
                            </span>
                        @endif
                    </a>
                    <div class="p-4 flex flex-col flex-1">
                        <h2 class="font-extrabold text-sm text-slate-900 dark:text-white leading-snug line-clamp-2 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                            <a href="{{ $postUrl }}">{{ $post->title }}</a>
                        </h2>
                        @if ($post->excerpt)
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-600 font-myanmar leading-relaxed line-clamp-3 flex-1">
                                {{ $post->excerpt }}
                            </p>
                        @endif
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-500">{{ __('messages.blog_read_more') }}</span>
                            <span class="text-sky-600 dark:text-sky-400 text-sm transition-transform duration-300 group-hover:translate-x-1">→</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $posts->links() }}
        </div>
    @else
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-12 text-center text-slate-500 dark:text-slate-600 space-y-3 border border-slate-200/90 dark:border-slate-800/80 shadow-xl">
            <div class="text-4xl">📝</div>
            <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 font-outfit">{{ __('messages.blog_empty') }}</h3>
        </div>
    @endif
</div>
@endsection
