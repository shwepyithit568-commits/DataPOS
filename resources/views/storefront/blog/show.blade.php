@extends('layouts.storefront.app')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $blogUrl = url('/blog' . ($storeSlug ? '?store_slug=' . $storeSlug : ''));
@endphp

<div class="max-w-3xl mx-auto space-y-6">
    {{-- Back link --}}
    <a href="{{ $blogUrl }}" class="inline-flex items-center gap-1.5 text-xs font-black text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 transition">
        <span>←</span> <span>{{ __('messages.blog_back') }}</span>
    </a>

    {{-- Post Card --}}
    <article class="bg-white dark:bg-slate-900 rounded-3xl overflow-hidden border border-slate-200/90 dark:border-slate-800/80 shadow-2xl">
        @if ($post->image_path)
            <div class="aspect-[16/8] overflow-hidden bg-slate-200 dark:bg-slate-800">
                <img
                    src="{{ asset('storage/' . $post->image_path) }}"
                    alt="{{ $post->title }}"
                    class="w-full h-full object-cover"
                    data-img-fallback="hide"
                />
            </div>
        @endif
        <div class="p-5 sm:p-8">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="px-2.5 py-1 rounded-full text-xs font-black bg-sky-100 dark:bg-sky-950 text-sky-800 dark:text-sky-300 border border-sky-300 dark:border-sky-800">
                    📝 {{ __('messages.blog') }}
                </span>
                @if ($post->category)
                    <span style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important; color: #ffffff !important;" class="px-2.5 py-1 rounded-full text-xs font-black text-white shadow-xs border-0">
                        🏷️ {{ $post->category }}
                    </span>
                @endif
                <span class="text-xs font-bold text-slate-600 dark:text-slate-500">
                    {{ $post->published_at?->format('F j, Y') ?? $post->created_at->format('F j, Y') }}
                </span>
                @php $readMinutes = max(1, round(mb_strlen(strip_tags($post->content)) / 400)); @endphp
                <span class="text-xs font-bold text-slate-600 dark:text-slate-500">· ⏱️ {{ $readMinutes }} min read</span>
            </div>
            <h1 class="mt-3 text-xl sm:text-2xl font-black font-outfit text-slate-900 dark:text-white leading-snug">
                {{ $post->title }}
            </h1>
            <div class="mt-4 prose prose-sm dark:prose-invert max-w-none font-myanmar text-slate-700 dark:text-slate-300 leading-relaxed">
                @if (preg_match('/<[a-z][\s\S]*>/i', $post->content))
                    {!! $post->content !!}
                @else
                    {!! nl2br(e($post->content)) !!}
                @endif
            </div>
            @if ($post->tags)
                <div class="mt-5 flex flex-wrap gap-1.5">
                    @foreach (array_filter(array_map('trim', explode(',', $post->tags))) as $tag)
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-600 border border-slate-200 dark:border-slate-700">#{{ $tag }}</span>
                    @endforeach
                </div>
            @endif

            {{-- Share row — free marketing: customers share to FB / Viber / Telegram --}}
            <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2 flex-wrap">
                <span class="text-xs font-black text-slate-500 dark:text-slate-600 mr-1">{{ __('messages.share') }}:</span>
                <x-share-button
                    :url="url('/blog/' . $post->slug . ($storeSlug ? '?store_slug=' . $storeSlug : ''))"
                    :title="$post->title"
                    :text="$post->title . ' — ' . ($store->name ?? config('app.name'))"
                    button-class="w-9 h-9 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-md shadow-amber-500/30 flex items-center justify-center hover:scale-110 active:scale-95 transition-all duration-200"
                />
            </div>
        </div>
    </article>

    {{-- Prev / Next article navigation --}}
    @if ($prevPost || $nextPost)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @if ($prevPost)
                <a href="{{ url('/blog/' . $prevPost->slug . ($storeSlug ? '?store_slug=' . $storeSlug : '')) }}"
                    class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/90 dark:border-slate-800/80 hover:-translate-y-0.5 hover:shadow-xl transition-all duration-300 group">
                    <span class="text-xs font-black text-slate-600 dark:text-slate-500 uppercase">← {{ __('messages.blog_previous') }}</span>
                    <span class="block mt-1 text-xs font-extrabold text-slate-800 dark:text-slate-200 line-clamp-2 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">{{ $prevPost->title }}</span>
                </a>
            @else
                <div></div>
            @endif
            @if ($nextPost)
                <a href="{{ url('/blog/' . $nextPost->slug . ($storeSlug ? '?store_slug=' . $storeSlug : '')) }}"
                    class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/90 dark:border-slate-800/80 hover:-translate-y-0.5 hover:shadow-xl transition-all duration-300 group sm:text-right">
                    <span class="text-xs font-black text-slate-600 dark:text-slate-500 uppercase">{{ __('messages.blog_next') }} →</span>
                    <span class="block mt-1 text-xs font-extrabold text-slate-800 dark:text-slate-200 line-clamp-2 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">{{ $nextPost->title }}</span>
                </a>
            @endif
        </div>
    @endif

    {{-- Related Posts --}}
    @if ($related->count() > 0)
        <div class="pt-2">
            <h2 class="text-sm font-black text-slate-700 dark:text-slate-200 mb-3">{{ __('messages.blog_related') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach ($related as $rel)
                    @php
                        $relUrl = url('/blog/' . $rel->slug . ($storeSlug ? '?store_slug=' . $storeSlug : ''));
                    @endphp
                    <a href="{{ $relUrl }}" class="group bg-white dark:bg-slate-900 rounded-2xl overflow-hidden border border-slate-200/90 dark:border-slate-800/80 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                        @if ($rel->image_path)
                            <div class="aspect-[16/9] overflow-hidden bg-slate-200 dark:bg-slate-800">
                                <img src="{{ asset('storage/' . $rel->image_path) }}" alt="{{ $rel->title }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy" data-img-fallback="hide" />
                            </div>
                        @endif
                        <div class="p-3">
                            <h3 class="text-xs font-extrabold text-slate-800 dark:text-slate-100 leading-snug line-clamp-2 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">{{ $rel->title }}</h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- Article structured data — Google rich results (headline/date/image) --}}
@push('scripts')
<script type="application/ld+json" nonce="{{ $cspNonce }}">
{
    "@@context": "https://schema.org",
    "@type": "Article",
    "headline": {{ json_encode($post->title, JSON_UNESCAPED_UNICODE) }},
    "datePublished": "{{ ($post->published_at ?? $post->created_at)->toIso8601String() }}",
    "dateModified": "{{ $post->updated_at->toIso8601String() }}",
    "author": { "@type": "Organization", "name": {{ json_encode($store->name ?? config('app.name'), JSON_UNESCAPED_UNICODE) }} },
    "publisher": { "@type": "Organization", "name": {{ json_encode($store->name ?? config('app.name'), JSON_UNESCAPED_UNICODE) }} },
    @if ($post->image_path)"image": {{ json_encode(asset('storage/' . $post->image_path)) }},@endif
    "description": {{ json_encode($metaDescription, JSON_UNESCAPED_UNICODE) }}
}
</script>
@endpush
@endsection
