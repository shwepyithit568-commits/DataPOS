@extends('layouts.storefront.app')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
@endphp

<div class="max-w-6xl mx-auto space-y-6 sm:space-y-8 pb-12">
    {{-- Blog Hero Header --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 text-center relative overflow-hidden border border-slate-200/90 dark:border-slate-800 shadow-sm">
        <div class="absolute -top-16 -right-16 w-56 h-56 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-16 w-56 h-56 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 space-y-2">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black text-white uppercase shadow-2xs border-0"
                 style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;">
                <span>📝</span>
                <span>Tech News & Articles</span>
            </div>
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black font-sans text-slate-900 dark:text-white">
                {{ __('messages.blog_title') }}
            </h1>
            <p class="text-xs sm:text-sm font-myanmar text-slate-600 dark:text-slate-400 max-w-2xl mx-auto leading-relaxed">
                {{ __('messages.blog_subtitle') }}
            </p>
        </div>
    </div>

    {{-- Category filter chips --}}
    @if ($categories->count() > 0)
        <div class="flex flex-wrap items-center gap-2 justify-center">
            <a href="{{ url('/blog' . ($storeSlug ? '?store_slug=' . $storeSlug : '')) }}"
                style="{{ !request('category') ? 'background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important; color: #ffffff !important;' : '' }}"
                class="px-4 py-1.5 rounded-full text-xs font-black transition cursor-pointer select-none border {{ !request('category') ? 'shadow-md shadow-sky-500/20 !text-white border-transparent' : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-sky-400' }}">
                {{ __('messages.all') }}
            </a>
            @foreach ($categories as $cat)
                <a href="{{ url('/blog' . ($storeSlug ? '?store_slug=' . $storeSlug . '&' : '?') . 'category=' . urlencode($cat)) }}"
                    style="{{ request('category') === $cat ? 'background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important; color: #ffffff !important;' : '' }}"
                    class="px-4 py-1.5 rounded-full text-xs font-black transition cursor-pointer select-none border {{ request('category') === $cat ? 'shadow-md shadow-sky-500/20 !text-white border-transparent' : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-sky-400' }}">
                    🏷️ {{ $cat }}
                </a>
            @endforeach
        </div>
    @endif

    {{-- Posts Grid --}}
    @if ($posts->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            @foreach ($posts as $post)
                @php
                    $postUrl = url('/blog/' . $post->slug . ($storeSlug ? '?store_slug=' . $storeSlug : ''));
                    $readMinutes = max(1, round(mb_strlen(strip_tags($post->content)) / 400));
                @endphp
                <article class="group bg-white dark:bg-slate-900 rounded-3xl overflow-hidden border border-slate-200/90 dark:border-slate-800 flex flex-col transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-sky-400 dark:hover:border-sky-600">
                    <a href="{{ $postUrl }}" class="block relative aspect-[16/10] overflow-hidden bg-slate-100 dark:bg-slate-800">
                        @if ($post->image_path)
                            <img
                                src="{{ asset('storage/' . $post->image_path) }}"
                                alt="{{ $post->title }}"
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                loading="lazy"
                                data-img-fallback="hide"
                            />
                        @else
                            <div class="w-full h-full flex items-center justify-center text-4xl bg-gradient-to-br from-sky-50 to-purple-50 dark:from-slate-800 dark:to-slate-900">📝</div>
                        @endif
                        <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-[11px] font-black bg-white/95 dark:bg-slate-900/95 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-xs backdrop-blur-md">
                            📅 {{ $post->published_at?->format('M j, Y') ?? $post->created_at->format('M j, Y') }}
                        </span>
                        @if ($post->category)
                            <span style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important; color: #ffffff !important;" class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[11px] font-black text-white shadow-xs border-0">
                                🏷️ {{ $post->category }}
                            </span>
                        @endif
                    </a>
                    <div class="p-5 flex flex-col flex-1 space-y-2">
                        <div class="flex items-center gap-2 text-[11px] font-bold text-slate-500 dark:text-slate-400">
                            <span>⏱️ {{ $readMinutes }} min read</span>
                        </div>
                        <h2 class="font-black text-sm sm:text-base text-slate-900 dark:text-white leading-snug line-clamp-2 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                            <a href="{{ $postUrl }}">{{ $post->title }}</a>
                        </h2>
                        @if ($post->excerpt)
                            <p class="text-xs text-slate-600 dark:text-slate-400 font-myanmar leading-relaxed line-clamp-3 flex-1">
                                {{ $post->excerpt }}
                            </p>
                        @endif
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between mt-auto">
                            <span class="text-xs font-bold text-sky-600 dark:text-sky-400 group-hover:underline">{{ __('messages.blog_read_more') }}</span>
                            <span class="w-7 h-7 rounded-full bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-xs transition-transform duration-300 group-hover:translate-x-1">→</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $posts->links() }}
        </div>
    @else
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-12 text-center text-slate-500 dark:text-slate-400 space-y-3 border border-slate-200/90 dark:border-slate-800 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-sky-50 dark:bg-sky-950/60 text-sky-500 flex items-center justify-center text-2xl mx-auto shadow-inner">
                📝
            </div>
            <h3 class="text-base font-black text-slate-800 dark:text-slate-200">{{ __('messages.blog_empty') }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar">မကြာမီ သတင်းနှင့် ဆောင်းပါးအသစ်များကို တင်ဆက်ပေးပါမည်</p>
        </div>
    @endif
</div>
@endsection

